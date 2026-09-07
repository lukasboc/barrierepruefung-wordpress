<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Verwaltungsseite unter Werkzeuge.
 *
 * Bewusst kleiner Funktionsumfang: Verbinden, Domain bestätigen, prüfen,
 * Ergebnis sehen. Ein Plugin, das alles kann, wird nicht gepflegt und nicht
 * freigegeben (docs/08 der Dienst-Dokumentation).
 */
class Barrierepruefung_Admin
{
    private const CAPABILITY = 'manage_options';

    /** Zwischenspeicher fuer Website, Kontingent und Befunde. */
    private const ZUSTAND = 'barrierepruefung_status';

    /**
     * Haltbarkeit des Zwischenspeichers in Sekunden.
     *
     * Kurz, weil die Seite auch waehrend einer laufenden Pruefung gelesen wird;
     * lang genug, dass ein Blick ins Backend nicht bei jedem Aufruf zwei
     * Anfragen an den Dienst ausloest.
     */
    private const ZUSTAND_DAUER = 300;

    /** Fundstellen je Abruf - dieselbe Schrittweite wie im Bericht. */
    private const FUNDSTELLEN_SCHRITT = 20;

    /**
     * Merker fuer den einmaligen Hinweis nach der Aktivierung.
     *
     * Ein Transient und keine Option: er soll von selbst verschwinden, auch
     * wenn ihn niemand je zu Gesicht bekommt.
     */
    private const HINWEIS = 'barrierepruefung_hinweis';

    /** Wie lange der Hinweis nach der Aktivierung auf seinen Auftritt wartet. */
    private const HINWEIS_DAUER = DAY_IN_SECONDS;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_notices', [$this, 'hinweis']);
        add_filter('plugin_action_links_'.BARRIEREPRUEFUNG_BASENAME, [$this, 'aktionsverweise']);
        add_action('admin_post_barrierepruefung_connect', [$this, 'handle_connect']);
        add_action('admin_post_barrierepruefung_verify', [$this, 'handle_verify']);
        add_action('admin_post_barrierepruefung_scan', [$this, 'handle_scan']);
        add_action('admin_post_barrierepruefung_refresh', [$this, 'handle_refresh']);
        add_action('admin_post_barrierepruefung_disconnect', [$this, 'handle_disconnect']);
        add_action('admin_post_barrierepruefung_reset_url', [$this, 'handle_reset_url']);
    }

    /**
     * Wird bei der Aktivierung gerufen und merkt den Hinweis vor.
     *
     * Ohne ihn endet die Aktivierung im Nichts: das Plugin legt keine Seite an,
     * die von selbst auffiele, und "Werkzeuge -> Barrierefreiheit" findet nur,
     * wer weiss, dass es sie gibt.
     */
    public static function activate(): void
    {
        set_transient(self::HINWEIS, 1, self::HINWEIS_DAUER);
    }

    /**
     * Der Hinweis nach der Aktivierung - einmal, und nur solange nichts
     * verbunden ist.
     *
     * Er verweist auf die Seite, statt selbst zu erklaeren: die Anleitung steht
     * dort, und zwei Fassungen davon liefen auseinander. Auf der Seite selbst
     * erscheint er nicht, dort steht die Anleitung ja schon.
     */
    public function hinweis(): void
    {
        if (! current_user_can(self::CAPABILITY) || ! get_transient(self::HINWEIS)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur die Frage, ob wir bereits auf der eigenen Seite stehen; keine Zustandsaenderung.
        $seite = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if ($seite === 'barrierepruefung') {
            return;
        }

        if ((new Barrierepruefung_Client)->is_connected()) {
            delete_transient(self::HINWEIS);

            return;
        }

        delete_transient(self::HINWEIS);

        printf(
            '<div class="notice notice-info"><p>%s</p><p><a class="button button-primary" href="%s">%s</a></p></div>',
            esc_html__('The accessibility checker is active but not yet connected to an account. The setup — account, API token, first scan — is explained step by step on its page.', 'barrierepruefung-de-web-accessibility-checker'),
            esc_url(admin_url('tools.php?page=barrierepruefung')),
            esc_html__('Set up the accessibility checker', 'barrierepruefung-de-web-accessibility-checker')
        );
    }

    /**
     * Der Verweis in der Plugin-Liste.
     *
     * Die Stelle, an der nach dem Aktivieren tatsaechlich gesucht wird - und
     * das einzige Mittel, das auch dann noch traegt, wenn der einmalige Hinweis
     * laengst weggeklickt ist.
     *
     * "Einstellungen" heisst er unabhaengig davon, ob schon etwas verbunden
     * ist. Es ist die Aufschrift, die neben jedem anderen Plugin steht, und
     * danach wird gesucht - eine wechselnde Beschriftung waere hier eine
     * Genauigkeit, die niemand bestellt hat. Er steht vorn, vor "Deaktivieren".
     *
     * @param  array<int|string, string>  $verweise
     * @return array<int|string, string>
     */
    public function aktionsverweise(array $verweise): array
    {
        array_unshift($verweise, sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('tools.php?page=barrierepruefung')),
            esc_html__('Settings', 'barrierepruefung-de-web-accessibility-checker')
        ));

        return $verweise;
    }

    public function add_page(): void
    {
        add_management_page(
            __('Accessibility', 'barrierepruefung-de-web-accessibility-checker'),
            __('Accessibility', 'barrierepruefung-de-web-accessibility-checker'),
            self::CAPABILITY,
            'barrierepruefung',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to view this page.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        $client = new Barrierepruefung_Client;
        $verbunden = $client->is_connected();
        $site = null;
        $befunde = [];
        $abruffehler = null;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesen eines Anzeigeparameters, keine Zustandsaenderung.
        $regel = isset($_GET['barrierepruefung_regel']) ? sanitize_text_field(wp_unslash($_GET['barrierepruefung_regel'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wie oben.
        $versatz = isset($_GET['barrierepruefung_ab']) ? max(0, (int) $_GET['barrierepruefung_ab']) : 0;

        $fundstellen = null;

        if ($verbunden) {
            $zustand = $this->zustand($client);
            $site = $zustand['site'];
            $befunde = $zustand['findings'];
            $abruffehler = $zustand['error'];

            if ($regel !== '' && is_array($site)) {
                $fundstellen = $this->fundstellen($client, $site, $regel, $versatz);
            }
        }

        include BARRIEREPRUEFUNG_PATH.'views/admin-page.php';
    }

    /**
     * Die Fundstellen einer einzelnen Regel.
     *
     * Bewusst nicht zwischengespeichert: geholt wird nur, was jemand
     * ausdruecklich aufklappt, und dann soll es der aktuelle Stand sein. Ein
     * eigener Transient je Regel muesste ausserdem in die Aufraeumpfade der
     * Deinstallation nachgetragen werden - fuer einen Abruf, der ohnehin nur
     * auf Klick geschieht, ein schlechtes Geschaeft.
     *
     * @param  array<string, mixed>  $site
     * @return array{items: list<array<string, mixed>>, total: int, offset: int, limit: int}|null
     */
    private function fundstellen(Barrierepruefung_Client $client, array $site, string $regel, int $versatz): ?array
    {
        $lauf = $site['latest_scan']['id'] ?? null;

        if (! is_string($lauf) || $lauf === '') {
            return null;
        }

        $antwort = $client->get(sprintf(
            '/scans/%s/findings/%s?limit=%d&offset=%d',
            rawurlencode($lauf),
            rawurlencode($regel),
            self::FUNDSTELLEN_SCHRITT,
            $versatz
        ));

        if (! $antwort['ok']) {
            return null;
        }

        return [
            'items' => (array) ($antwort['data']['data'] ?? []),
            'total' => (int) ($antwort['data']['meta']['total'] ?? 0),
            'offset' => (int) ($antwort['data']['meta']['offset'] ?? $versatz),
            'limit' => (int) ($antwort['data']['meta']['limit'] ?? self::FUNDSTELLEN_SCHRITT),
        ];
    }

    /**
     * Website samt Kontingent und die offenen Befunde der letzten Pruefung.
     *
     * Zwei Abrufe, deshalb zwischengespeichert. Gefiltert wird auf offene,
     * verbindliche Befunde: bewertete Mangel und blosse Empfehlungen gehoeren
     * nicht in eine Arbeitsliste. Der Verweis auf den Lauf kommt aus der
     * Antwort des Dienstes und nicht aus einer eigenen Option - sonst waere
     * eine geplante Pruefung hier unsichtbar.
     *
     * Ein fehlgeschlagener Abruf wird nicht abgelegt: wer ein falsches Token
     * richtigstellt, soll nicht fuenf Minuten weiter die alte Meldung sehen.
     *
     * @return array{site: array<string, mixed>|null, findings: list<array<string, mixed>>, error: string|null}
     */
    public function zustand(Barrierepruefung_Client $client): array
    {
        $gespeichert = get_transient(self::ZUSTAND);

        if (is_array($gespeichert)) {
            return $gespeichert;
        }

        $antwort = $client->get('/sites/'.$client->site_id());

        if (! $antwort['ok']) {
            return ['site' => null, 'findings' => [], 'error' => $antwort['error']];
        }

        $site = $antwort['data']['data'] ?? null;
        $lauf = is_array($site) ? ($site['latest_scan']['id'] ?? null) : null;
        $befunde = [];

        if (is_string($lauf) && $lauf !== '') {
            $funde = $client->get('/scans/'.rawurlencode($lauf).'/findings?binding_only=1&state=open');
            $befunde = $funde['ok'] ? (array) ($funde['data']['data'] ?? []) : [];
        }

        $zustand = ['site' => $site, 'findings' => $befunde, 'error' => null];

        set_transient(self::ZUSTAND, $zustand, self::ZUSTAND_DAUER);

        return $zustand;
    }

    public function handle_connect(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_connect');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird bereits oben in pruefe_berechtigung() per check_admin_referer() geprueft; der Sniff sieht nicht ueber Methodengrenzen hinweg.
        update_option('barrierepruefung_api_url', esc_url_raw(wp_unslash($_POST['api_url'] ?? '')));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
        update_option('barrierepruefung_token', sanitize_text_field(wp_unslash($_POST['token'] ?? '')));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
        update_option('barrierepruefung_site_id', sanitize_text_field(wp_unslash($_POST['site_id'] ?? '')));

        // Nachweise holen und selbst ausliefern - dafür braucht die Kundin
        // keinen DNS-Zugriff (docs/08 der Dienst-Dokumentation).
        $client = new Barrierepruefung_Client;
        $antwort = $client->get('/sites/'.$client->site_id().'/verification');

        if ($antwort['ok']) {
            update_option('barrierepruefung_verification_token',
                sanitize_text_field($antwort['data']['data']['meta_tag']['content'] ?? ''));
            flush_rewrite_rules();
        }

        // Der Zwischenspeicher gehoert zu einer Verbindung. Wer eine neue
        // eintraegt, darf nicht den Stand der alten zu sehen bekommen.
        delete_transient(self::ZUSTAND);

        $this->zurueck($antwort['ok'] ? 'verbunden' : 'fehler', $antwort['error']);
    }

    /**
     * Bestaetigt die Domain.
     *
     * Der Zwischenspeicher muss danach weg. Ohne das meldete die Seite oben
     * „Die Domain ist bestaetigt" und zeigte darunter weiter „Domain
     * bestaetigt: nein" samt dem Knopf, der das gerade erledigt hatte - bis zu
     * fuenf Minuten lang, und nur „Status aktualisieren" kam dagegen an. Ein
     * Widerspruch auf einem Bildschirm ist schlimmer als eine langsame Seite.
     *
     * Die Antwort traegt zwar den frischen Stammsatz der Website, aber ohne
     * Kontingent und ohne Prueflaeufe - verify() ist im Dienst eine Rueckmeldung
     * zum Nachweis, kein Statusbericht. Ihn in den Zwischenspeicher zu schreiben
     * hiesse, „Kontingent nicht abrufbar" gegen einen zweiten Abruf zu tauschen.
     *
     * Bei einem gescheiterten Versuch bleibt er stehen: dann hat sich beim
     * Dienst nichts geaendert, und der Zwischenspeicher ist weiterhin richtig.
     */
    public function handle_verify(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_verify');

        $client = new Barrierepruefung_Client;
        $antwort = $client->post('/sites/'.$client->site_id().'/verify', ['method' => 'meta_tag']);

        $bestaetigt = $antwort['ok'] && ! empty($antwort['data']['data']['verified']);

        if ($bestaetigt) {
            delete_transient(self::ZUSTAND);
        }

        $this->zurueck(
            $bestaetigt ? 'bestaetigt' : 'nicht_bestaetigt',
            $antwort['data']['data']['failure_reason'] ?? $antwort['error']
        );
    }

    public function handle_scan(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_scan');

        $client = new Barrierepruefung_Client;
        $antwort = $client->post('/sites/'.$client->site_id().'/scans');

        if ($antwort['ok']) {
            // Der Text kann sich durch die neue Prüfung ändern.
            delete_transient('barrierepruefung_declaration');

            // Ohne das zeigte die Seite bis zu fünf Minuten weiter „keine
            // laufende Prüfung", obwohl gerade eine gestartet wurde.
            delete_transient(self::ZUSTAND);
        }

        $this->zurueck($antwort['ok'] ? 'geprueft' : 'fehler', $antwort['error']);
    }

    /**
     * Holt Zustand und Befunde neu.
     *
     * Der bewusste Ersatz für ein selbsttätiges Neuladen der Seite: ein
     * automatischer Kontextwechsel wäre für Screenreader-Nutzende störend
     * (WCAG 2.2.2/3.2.5) — in einem Barrierefreiheitsprodukt kein zulässiger
     * Kompromiss. Aus demselben Grund bringt das Plugin kein Skript mit.
     */
    public function handle_refresh(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_refresh');

        delete_transient(self::ZUSTAND);

        $this->zurueck('aktualisiert');
    }

    /**
     * Trennt die Verbindung.
     *
     * Der Weg zurueck aus einer falsch eingetragenen Verbindung: ohne ihn
     * blendet is_connected() das Formular fuer immer aus, sobald Token und
     * Kennung einmal gespeichert sind - auch wenn beide falsch sind. Danach
     * bliebe nur WP-CLI, und das hat nicht jede Betreiberin.
     *
     * Entfernt wird alles, was zu diesem Konto gehoert, einschliesslich der
     * zuletzt geholten Fassung der Erklaerung: sie ist eine rechtliche Aussage
     * ueber eine Website, und sie nach dem Trennen weiter auszuliefern hiesse,
     * sie unbegrenzt weiterzuverbreiten, ohne dass noch jemand sie aktualisiert.
     * Der Shortcode zeigt dann seinen Hinweis statt eines veralteten Textes.
     *
     * Die Adresse des Dienstes bleibt stehen - sie ist kein Geheimnis, und wer
     * sich nach einem Tippfehler im Token neu verbindet, soll sie nicht noch
     * einmal abtippen muessen. Zuruecksetzen laesst sie sich einzeln.
     */
    public function handle_disconnect(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_disconnect');

        foreach (['barrierepruefung_token', 'barrierepruefung_site_id', 'barrierepruefung_verification_token', 'barrierepruefung_declaration_fallback'] as $option) {
            delete_option($option);
        }

        foreach (['barrierepruefung_declaration', self::ZUSTAND] as $transient) {
            delete_transient($transient);
        }

        $this->zurueck('getrennt');
    }

    /**
     * Setzt die Adresse des Dienstes auf den Standardwert zurueck.
     *
     * Als eigene Aktion und nicht als zweiter Absenden-Knopf im Formular: das
     * Feld ist "required", ein Formular mit leerem Feld liesse sich gar nicht
     * abschicken. Geloescht wird die Option, nicht der Standardwert
     * hineingeschrieben - Barrierepruefung_Client::api_url() faellt von selbst
     * darauf zurueck, und so wandert eine spaetere Aenderung des Standards von
     * allein in bestehende Installationen.
     */
    public function handle_reset_url(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_reset_url');

        delete_option('barrierepruefung_api_url');

        // Der abgelegte Stand kam von der alten Adresse.
        delete_transient(self::ZUSTAND);

        $this->zurueck('adresse_zurueckgesetzt');
    }

    /** Berechtigung und Nonce - beides, nicht nur eines (docs/08 der Dienst-Dokumentation). */
    private function pruefe_berechtigung(string $aktion): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        check_admin_referer($aktion);
    }

    private function zurueck(string $status, ?string $meldung = null): void
    {
        wp_safe_redirect(add_query_arg(
            array_filter([
                'page' => 'barrierepruefung',
                'barrierepruefung_status' => $status,
                'barrierepruefung_meldung' => $meldung ? rawurlencode(substr($meldung, 0, 200)) : null,
            ]),
            admin_url('tools.php')
        ));
        exit;
    }
}

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
class A11y_Checker_Admin
{
    private const CAPABILITY = 'manage_options';

    /** Zwischenspeicher fuer Website, Kontingent und Befunde. */
    private const ZUSTAND = 'a11y_checker_status';

    /**
     * Haltbarkeit des Zwischenspeichers in Sekunden.
     *
     * Kurz, weil die Seite auch waehrend einer laufenden Pruefung gelesen wird;
     * lang genug, dass ein Blick ins Backend nicht bei jedem Aufruf zwei
     * Anfragen an den Dienst ausloest.
     */
    private const ZUSTAND_DAUER = 300;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_post_a11y_checker_connect', [$this, 'handle_connect']);
        add_action('admin_post_a11y_checker_verify', [$this, 'handle_verify']);
        add_action('admin_post_a11y_checker_scan', [$this, 'handle_scan']);
        add_action('admin_post_a11y_checker_refresh', [$this, 'handle_refresh']);
        add_action('admin_post_a11y_checker_disconnect', [$this, 'handle_disconnect']);
        add_action('admin_post_a11y_checker_reset_url', [$this, 'handle_reset_url']);
    }

    public function add_page(): void
    {
        add_management_page(
            __('Accessibility', 'a11y-checker'),
            __('Accessibility', 'a11y-checker'),
            self::CAPABILITY,
            'a11y-checker',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to view this page.', 'a11y-checker'));
        }

        $client = new A11y_Checker_Client;
        $verbunden = $client->is_connected();
        $site = null;
        $befunde = [];
        $abruffehler = null;

        if ($verbunden) {
            $zustand = $this->zustand($client);
            $site = $zustand['site'];
            $befunde = $zustand['findings'];
            $abruffehler = $zustand['error'];
        }

        include A11Y_CHECKER_PATH.'views/admin-page.php';
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
    public function zustand(A11y_Checker_Client $client): array
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
        $this->pruefe_berechtigung('a11y_checker_connect');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird bereits oben in pruefe_berechtigung() per check_admin_referer() geprueft; der Sniff sieht nicht ueber Methodengrenzen hinweg.
        update_option('a11y_checker_api_url', esc_url_raw(wp_unslash($_POST['api_url'] ?? '')));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
        update_option('a11y_checker_token', sanitize_text_field(wp_unslash($_POST['token'] ?? '')));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
        update_option('a11y_checker_site_id', sanitize_text_field(wp_unslash($_POST['site_id'] ?? '')));

        // Nachweise holen und selbst ausliefern - dafür braucht die Kundin
        // keinen DNS-Zugriff (docs/08 der Dienst-Dokumentation).
        $client = new A11y_Checker_Client;
        $antwort = $client->get('/sites/'.$client->site_id().'/verification');

        if ($antwort['ok']) {
            update_option('a11y_checker_verification_token',
                sanitize_text_field($antwort['data']['data']['meta_tag']['content'] ?? ''));
            flush_rewrite_rules();
        }

        $this->zurueck($antwort['ok'] ? 'verbunden' : 'fehler', $antwort['error']);
    }

    public function handle_verify(): void
    {
        $this->pruefe_berechtigung('a11y_checker_verify');

        $client = new A11y_Checker_Client;
        $antwort = $client->post('/sites/'.$client->site_id().'/verify', ['method' => 'meta_tag']);

        $bestaetigt = $antwort['ok'] && ! empty($antwort['data']['data']['verified']);

        $this->zurueck(
            $bestaetigt ? 'bestaetigt' : 'nicht_bestaetigt',
            $antwort['data']['data']['failure_reason'] ?? $antwort['error']
        );
    }

    public function handle_scan(): void
    {
        $this->pruefe_berechtigung('a11y_checker_scan');

        $client = new A11y_Checker_Client;
        $antwort = $client->post('/sites/'.$client->site_id().'/scans');

        if ($antwort['ok']) {
            // Der Text kann sich durch die neue Prüfung ändern.
            delete_transient('a11y_checker_declaration');

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
        $this->pruefe_berechtigung('a11y_checker_refresh');

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
        $this->pruefe_berechtigung('a11y_checker_disconnect');

        foreach (['a11y_checker_token', 'a11y_checker_site_id', 'a11y_checker_verification_token', 'a11y_checker_declaration_fallback'] as $option) {
            delete_option($option);
        }

        foreach (['a11y_checker_declaration', self::ZUSTAND] as $transient) {
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
     * hineingeschrieben - A11y_Checker_Client::api_url() faellt von selbst
     * darauf zurueck, und so wandert eine spaetere Aenderung des Standards von
     * allein in bestehende Installationen.
     */
    public function handle_reset_url(): void
    {
        $this->pruefe_berechtigung('a11y_checker_reset_url');

        delete_option('a11y_checker_api_url');

        $this->zurueck('adresse_zurueckgesetzt');
    }

    /** Berechtigung und Nonce - beides, nicht nur eines (docs/08 der Dienst-Dokumentation). */
    private function pruefe_berechtigung(string $aktion): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'a11y-checker'));
        }

        check_admin_referer($aktion);
    }

    private function zurueck(string $status, ?string $meldung = null): void
    {
        wp_safe_redirect(add_query_arg(
            array_filter([
                'page' => 'a11y-checker',
                'a11y_status' => $status,
                'a11y_meldung' => $meldung ? rawurlencode(substr($meldung, 0, 200)) : null,
            ]),
            admin_url('tools.php')
        ));
        exit;
    }
}

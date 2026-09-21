<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Der Weg zur Erklärung - im Reiter „Erklärung" der Verwaltungsseite.
 *
 * Das Plugin geht diesen Weg ganz, bis zur Freigabe, kennt dabei aber kein
 * Recht: welche Schritte es gibt, was offen ist, welche Felder gelten, welche
 * Fragen der Betroffenheits-Check stellt - all das liefert der Dienst
 * (docs/08 der Dienst-Dokumentation, „Der Weg zur Erklärung über die API").
 * Hier wird es nur dargestellt und zurückgeschickt. Eine Rechtsänderung ist
 * damit eine Änderung im Dienst, kein neues Plugin.
 *
 * Aufbau: eine Übersicht mit den Schritten, je Schritt ein eigener Bildschirm,
 * am Ende die Freigabe. Jede Aktion ist ein gewöhnliches Formular über
 * admin-post.php mit Umleitung danach - kein Skript, wie überall im Plugin.
 */
class Barrierepruefung_Erklaerung
{
    private const CAPABILITY = 'manage_options';

    /**
     * Rückmeldung nach einer Aktion, je Person.
     *
     * User-Meta statt Adresszeile: Fehler je Feld und die zuletzt eingegebenen
     * Werte passen in keinen Link - und sie sollen erhalten bleiben, wenn der
     * Dienst eine Eingabe zurückweist. Gelesen wird sie genau einmal.
     */
    public const RUECKMELDUNG = 'barrierepruefung_rueckmeldung';

    /** Schritte, für die es hier einen eigenen Bildschirm gibt. */
    public const BEKANNTE_SCHRITTE = [
        'pruefung',
        'pruefschritte',
        'verantwortliche_stelle',
        'betroffenheit',
        'domain',
        'pflichtangaben',
    ];

    /** Die Angaben der Erklärung, die das Plugin kennt und zurückschickt. */
    public const ANGABEN = [
        'contact',
        'alternatives',
        'dgs_ls_url',
        'service_description',
        'enforcement_body_name',
        'enforcement_body_address',
        'enforcement_body_url',
    ];

    /** Die Seite, auf der die Erklärung eingebunden ist, erkennt man hieran. */
    private const EINBINDUNGEN = ['[barrierefreiheitserklaerung', 'wp:barrierepruefung/erklaerung'];

    public function register(): void
    {
        add_action('admin_post_barrierepruefung_pruefschritte', [$this, 'handle_pruefschritte']);
        add_action('admin_post_barrierepruefung_stelle', [$this, 'handle_stelle']);
        add_action('admin_post_barrierepruefung_betroffenheit', [$this, 'handle_betroffenheit']);
        add_action('admin_post_barrierepruefung_angaben', [$this, 'handle_angaben']);
        add_action('admin_post_barrierepruefung_freigeben', [$this, 'handle_freigeben']);
        add_action('admin_post_barrierepruefung_seite_anlegen', [$this, 'handle_seite_anlegen']);
        add_filter('admin_title', [$this, 'titel']);
    }

    /** Die Adresse eines Bildschirms im Reiter „Erklärung". */
    public static function url(string $schritt = ''): string
    {
        return add_query_arg(
            array_filter([
                'page' => 'barrierepruefung',
                'ansicht' => 'erklaerung',
                'schritt' => $schritt !== '' ? $schritt : null,
            ]),
            admin_url('tools.php')
        );
    }

    /**
     * Die id des Formularfelds zu einem Schlüssel der API.
     *
     * Die Fehlerliste oben verweist auf das Feld; `services.0` und
     * `checks.3.answer` führen zur Gruppe, zu der sie gehören.
     */
    public static function feld_id(string $schluessel): string
    {
        $wurzel = explode('.', $schluessel)[0];

        return 'barrierepruefung-feld-'.preg_replace('/[^a-z0-9]+/', '-', strtolower($wurzel));
    }

    /**
     * „Fehler: " vor dem Titel des Browserfensters.
     *
     * Ohne Skript lässt sich der Fokus nicht auf die Fehlerliste setzen. Der
     * Dokumenttitel ist das Erste, was ein Screenreader nach dem Laden
     * vorliest - dort steht der Hinweis, dass etwas zu korrigieren ist.
     */
    public function titel(string $titel): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur die Frage, ob wir auf der eigenen Seite stehen.
        $seite = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if ($seite !== 'barrierepruefung') {
            return $titel;
        }

        $rueckmeldung = get_user_meta(get_current_user_id(), self::RUECKMELDUNG, true);

        if (is_array($rueckmeldung) && ($rueckmeldung['art'] ?? '') === 'fehler') {
            /* translators: %s: title of the admin page */
            return sprintf(__('Error: %s', 'barrierepruefung-de-web-accessibility-checker'), $titel);
        }

        return $titel;
    }

    /**
     * Den Reiter ausgeben.
     *
     * Alles wird bei jedem Aufruf frisch vom Dienst geholt: hier wird
     * entschieden und freigegeben, und ein Zwischenspeicher zeigte womöglich
     * einen Stand, den es nicht mehr gibt.
     */
    public function render(Barrierepruefung_Client $client): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesen eines Anzeigeparameters.
        $schritt = isset($_GET['schritt']) ? sanitize_key(wp_unslash($_GET['schritt'])) : '';
        $rueckmeldung = $this->rueckmeldung_nehmen();
        $site_id = rawurlencode($client->site_id());

        $antwort = $client->get('/sites/'.$site_id.'/declaration/path');

        if (! $antwort['ok']) {
            // 404 ohne eigenen Grund heißt: diesen Endpunkt gibt es beim Dienst
            // (noch) nicht - etwa eine eigene Instanz in älterer Fassung.
            $nicht_unterstuetzt = $antwort['status'] === 404 && empty($antwort['data']['reason']);
            $fehler = $antwort['error'];
            include BARRIEREPRUEFUNG_PATH.'views/erklaerung-nicht-erreichbar.php';

            return;
        }

        $weg = (array) ($antwort['data']['data'] ?? []);
        $lauf = is_array($weg['scan'] ?? null) ? $weg['scan'] : null;
        $schritte = (array) ($weg['steps'] ?? []);
        $aktueller = null;

        foreach ($schritte as $eintrag) {
            if (($eintrag['key'] ?? '') === $schritt) {
                $aktueller = $eintrag;
            }
        }

        if ($schritt === 'freigabe' && $lauf !== null) {
            $entwurf = $this->daten($client->get('/sites/'.$site_id.'/declaration/draft'));
            $bereit = ($weg['next'] ?? null) === null && $schritte !== [];
            $schluessel = wp_generate_uuid4();
            include BARRIEREPRUEFUNG_PATH.'views/erklaerung-freigabe.php';

            return;
        }

        if ($aktueller === null || $lauf === null) {
            $seiten = $this->seiten_mit_erklaerung();
            include BARRIEREPRUEFUNG_PATH.'views/erklaerung-uebersicht.php';

            return;
        }

        $werte = is_array($rueckmeldung['werte'] ?? null) ? $rueckmeldung['werte'] : null;
        $fehlerliste = is_array($rueckmeldung['fehler'] ?? null) ? $rueckmeldung['fehler'] : [];

        switch ($schritt) {
            case 'pruefschritte':
                $pruefschritte = $client->get('/scans/'.rawurlencode((string) $lauf['id']).'/manual-checks');
                $daten = $this->daten($pruefschritte);
                $meta = (array) ($pruefschritte['data']['meta'] ?? []);
                break;
            case 'verantwortliche_stelle':
                $daten = $this->daten($client->get('/sites/'.$site_id.'/declaring-party'));
                break;
            case 'betroffenheit':
                $daten = $this->daten($client->get('/sites/'.$site_id.'/applicability'));
                break;
            case 'pflichtangaben':
                $daten = $this->daten($client->get('/sites/'.$site_id.'/declaration/draft'));
                break;
            default:
                $daten = null;
        }

        $ansicht = in_array($schritt, self::BEKANNTE_SCHRITTE, true) ? $schritt : 'unbekannt';

        // Einen Schritt, den der Dienst neu eingeführt hat, kennt diese
        // Fassung nicht. Er erscheint trotzdem - mit Titel, Gründen und dem
        // Weg in den Dienst -, statt einfach zu fehlen.
        include BARRIEREPRUEFUNG_PATH.'views/erklaerung-schritt.php';
    }

    // --- Aktionen ---

    public function handle_pruefschritte(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_pruefschritte');

        $client = new Barrierepruefung_Client;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird in pruefe_berechtigung() geprueft.
        $lauf = isset($_POST['scan']) ? sanitize_text_field(wp_unslash($_POST['scan'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce siehe oben; jedes Feld wird unten einzeln bereinigt.
        $eingabe = isset($_POST['checks']) && is_array($_POST['checks']) ? wp_unslash($_POST['checks']) : [];

        $checks = [];

        foreach ($eingabe as $id => $werte) {
            $checks[] = [
                'id' => (int) $id,
                'answer' => sanitize_key((string) ($werte['answer'] ?? 'unanswered')),
                'note' => sanitize_textarea_field((string) ($werte['note'] ?? '')),
            ];
        }

        $antwort = $client->put('/scans/'.rawurlencode($lauf).'/manual-checks', [
            'actor_name' => $this->handelnde_person(),
            'checks' => $checks,
        ]);

        $this->nach_dem_speichern($client, $antwort, 'pruefschritte', ['checks' => $checks]);
    }

    public function handle_stelle(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_stelle');

        $werte = [
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird in pruefe_berechtigung() geprueft.
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wie oben.
            'applies_to' => isset($_POST['applies_to']) ? sanitize_key(wp_unslash($_POST['applies_to'])) : '',
        ];

        $client = new Barrierepruefung_Client;
        $antwort = $client->put('/sites/'.rawurlencode($client->site_id()).'/declaring-party', $werte + [
            'actor_name' => $this->handelnde_person(),
        ]);

        $this->nach_dem_speichern($client, $antwort, 'verantwortliche_stelle', $werte);
    }

    public function handle_betroffenheit(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_betroffenheit');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce siehe pruefe_berechtigung(); die Schluessel werden unten einzeln bereinigt.
        $gewaehlt = isset($_POST['services']) && is_array($_POST['services']) ? wp_unslash($_POST['services']) : [];

        $werte = [
            'services' => array_values(array_map(static fn ($schluessel) => sanitize_key((string) $schluessel), $gewaehlt)),
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wie oben.
            'micro_enterprise' => ! empty($_POST['micro_enterprise']),
        ];

        $client = new Barrierepruefung_Client;
        $antwort = $client->put('/sites/'.rawurlencode($client->site_id()).'/applicability', $werte + [
            'actor_name' => $this->handelnde_person(),
        ]);

        $this->nach_dem_speichern($client, $antwort, 'betroffenheit', $werte);
    }

    public function handle_angaben(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_angaben');

        $werte = [];

        foreach (self::ANGABEN as $schluessel) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce siehe pruefe_berechtigung(); bereinigt je nach Art des Feldes direkt darunter.
            $roh = isset($_POST['angaben'][$schluessel]) ? (string) wp_unslash($_POST['angaben'][$schluessel]) : '';

            $werte[$schluessel] = in_array($schluessel, ['dgs_ls_url', 'enforcement_body_url'], true)
                ? esc_url_raw(trim($roh))
                : sanitize_textarea_field($roh);
        }

        $client = new Barrierepruefung_Client;
        $antwort = $client->put('/sites/'.rawurlencode($client->site_id()).'/declaration/details', $werte + [
            'actor_name' => $this->handelnde_person(),
        ]);

        $this->nach_dem_speichern($client, $antwort, 'pflichtangaben', $werte);
    }

    /**
     * Die Freigabe.
     *
     * Das Formular trägt die Kennung des Entwurfs, den es gezeigt hat, und
     * einen Idempotency-Key, der beim Anzeigen entstanden ist. Ein Doppelklick
     * oder ein Zurück-und-nochmal ergibt beim Dienst deshalb keine zweite
     * Fassung, und ein inzwischen geänderter Entwurf wird nicht still
     * veröffentlicht.
     */
    public function handle_freigeben(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_freigeben');

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce wird in pruefe_berechtigung() geprueft.
        $name = isset($_POST['actor_name']) ? sanitize_text_field(wp_unslash($_POST['actor_name'])) : '';
        $abweichen = ! empty($_POST['abweichen']);
        $koerper = [
            'draft_etag' => isset($_POST['draft_etag']) ? sanitize_text_field(wp_unslash($_POST['draft_etag'])) : '',
            'expected_version' => isset($_POST['expected_version']) ? (int) $_POST['expected_version'] : 0,
            'actor_name' => $name !== '' ? $name : $this->handelnde_person(),
            'conformance_status' => $abweichen && isset($_POST['conformance_status'])
                ? sanitize_key(wp_unslash($_POST['conformance_status']))
                : null,
            'override_reason' => $abweichen && isset($_POST['override_reason'])
                ? sanitize_textarea_field(wp_unslash($_POST['override_reason']))
                : null,
        ];
        $schluessel = isset($_POST['idempotency_key']) ? sanitize_text_field(wp_unslash($_POST['idempotency_key'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $client = new Barrierepruefung_Client;
        $antwort = $client->post(
            '/sites/'.rawurlencode($client->site_id()).'/declaration/publish',
            array_filter($koerper, static fn ($wert) => $wert !== null),
            ['Idempotency-Key' => $schluessel !== '' ? $schluessel : wp_generate_uuid4()]
        );

        if ($antwort['ok']) {
            // Der Shortcode soll die neue Fassung sofort zeigen, nicht nach
            // bis zu einer Stunde. Neu geholt wird gleich hier, damit auch die
            // Rückfallebene die neue Fassung trägt.
            (new Barrierepruefung_Shortcode)->auffrischen();
            delete_transient('barrierepruefung_status');

            $fassung = (array) ($antwort['data']['data'] ?? []);

            $this->rueckmeldung_merken([
                'art' => 'erfolg',
                'text' => sprintf(
                    /* translators: %s: version number */
                    __('Version %s of your accessibility statement is published.', 'barrierepruefung-de-web-accessibility-checker'),
                    (string) ($fassung['version'] ?? '')
                ),
                'veroeffentlicht' => $fassung,
            ]);

            $this->umleiten(self::url());
        }

        $grund = (string) ($antwort['data']['reason'] ?? '');

        // Nicht bereit: zurück zur Übersicht, die sagt, welcher Schritt fehlt.
        if ($grund === 'nicht_bereit') {
            $this->rueckmeldung_merken([
                'art' => 'fehler',
                'text' => (string) $antwort['error'],
                'gruende' => (array) ($antwort['data']['blockers'] ?? []),
            ]);

            $this->umleiten(self::url());
        }

        // Alles andere, auch ein geänderter Entwurf: zurück zur Freigabe, die
        // den neuen Stand zeigt - mit der Erklärung, warum sie wieder da ist.
        $this->rueckmeldung_merken([
            'art' => 'fehler',
            'text' => (string) $antwort['error'],
            'url' => (string) ($antwort['data']['url'] ?? ''),
            'fehler' => (array) ($antwort['data']['errors'] ?? []),
        ]);

        $this->umleiten(self::url('freigabe'));
    }

    /**
     * Eine Seite für die Erklärung anlegen - als Entwurf.
     *
     * Entwurf, weil eine neue öffentliche Seite eine Entscheidung ist, die
     * niemand mit einem Klick in einem Werkzeug nebenbei treffen sollte: Sie
     * erscheint in Menüs, Sitemaps und Suchmaschinen.
     */
    public function handle_seite_anlegen(): void
    {
        $this->pruefe_berechtigung('barrierepruefung_seite_anlegen');

        if (! current_user_can('edit_pages')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        $id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => __('Accessibility statement', 'barrierepruefung-de-web-accessibility-checker'),
            'post_content' => '<!-- wp:barrierepruefung/erklaerung /-->',
        ]);

        if (is_wp_error($id) || ! $id) {
            $this->rueckmeldung_merken([
                'art' => 'fehler',
                'text' => __('The page could not be created.', 'barrierepruefung-de-web-accessibility-checker'),
            ]);
        } else {
            $this->rueckmeldung_merken([
                'art' => 'erfolg',
                'text' => __('A draft page with your accessibility statement has been created. Check it, publish it and link to it from every page - usually in the footer.', 'barrierepruefung-de-web-accessibility-checker'),
                'seite' => (int) $id,
            ]);
        }

        $this->umleiten(self::url());
    }

    // --- Hilfen ---

    /**
     * Wohin es nach dem Speichern geht - und was die Seite dann sagt.
     *
     * Erfolg führt zum nächsten offenen Schritt, wenn es einen gibt: das ist
     * es, was man als Nächstes tun will. Sonst zur Übersicht. Ein Fehler führt
     * zurück zum Schritt, mit allem, was eingegeben war.
     *
     * @param  array{ok: bool, status: int, data: array<string, mixed>, error: string|null}  $antwort
     * @param  array<string, mixed>  $werte
     */
    private function nach_dem_speichern(Barrierepruefung_Client $client, array $antwort, string $schritt, array $werte): void
    {
        if ($antwort['ok']) {
            delete_transient('barrierepruefung_status');

            $weg = $client->get('/sites/'.rawurlencode($client->site_id()).'/declaration/path');
            $naechster = $weg['ok'] ? ($weg['data']['data']['next'] ?? null) : null;
            $titel = '';

            foreach ((array) ($weg['data']['data']['steps'] ?? []) as $eintrag) {
                if (($eintrag['key'] ?? null) === $naechster) {
                    $titel = (string) ($eintrag['title'] ?? '');
                }
            }

            if (is_string($naechster) && $naechster !== '' && $naechster !== $schritt) {
                $this->rueckmeldung_merken([
                    'art' => 'erfolg',
                    'text' => sprintf(
                        /* translators: %s: title of the next step */
                        __('Saved. Next step: %s', 'barrierepruefung-de-web-accessibility-checker'),
                        $titel
                    ),
                ]);

                $this->umleiten(self::url($naechster));
            }

            $this->rueckmeldung_merken([
                'art' => 'erfolg',
                'text' => __('Saved.', 'barrierepruefung-de-web-accessibility-checker'),
            ]);

            $this->umleiten(self::url());
        }

        $this->rueckmeldung_merken([
            'art' => 'fehler',
            'text' => (string) $antwort['error'],
            'url' => (string) ($antwort['data']['url'] ?? ''),
            'fehler' => (array) ($antwort['data']['errors'] ?? []),
            'werte' => $werte,
        ]);

        $this->umleiten(self::url($schritt));
    }

    /**
     * Wie der Dienst die Person nennen soll, die hier handelt.
     *
     * Der Anzeigename, weil er das ist, was in WordPress sonst überall für die
     * Person steht. Er wird als Beleg gespeichert, nicht als Identität: die
     * Verantwortung trägt im Dienst die Admin, die dem Token das Recht
     * erteilt hat.
     */
    private function handelnde_person(): string
    {
        $person = wp_get_current_user();

        return (string) ($person->display_name ?? '');
    }

    /**
     * Seiten, in denen die Erklärung schon steckt - als Shortcode oder Block.
     *
     * @return list<object>
     */
    public function seiten_mit_erklaerung(): array
    {
        $gefunden = [];

        foreach (self::EINBINDUNGEN as $suche) {
            $treffer = get_posts([
                'post_type' => ['page', 'post'],
                'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
                's' => $suche,
                'numberposts' => 5,
            ]);

            foreach ($treffer as $beitrag) {
                // Die Suche von WordPress ist unscharf - hier zählt nur, was
                // die Einbindung wirklich enthält.
                if (str_contains((string) $beitrag->post_content, $suche)) {
                    $gefunden[(int) $beitrag->ID] = $beitrag;
                }
            }
        }

        return array_values($gefunden);
    }

    /**
     * Die Nutzdaten einer Antwort, oder null samt Fehler.
     *
     * @param  array{ok: bool, status: int, data: array<string, mixed>, error: string|null}  $antwort
     * @return array<string, mixed>|null
     */
    private function daten(array $antwort): ?array
    {
        return $antwort['ok'] && is_array($antwort['data']['data'] ?? null) ? $antwort['data']['data'] : null;
    }

    /** @param array<string, mixed> $rueckmeldung */
    private function rueckmeldung_merken(array $rueckmeldung): void
    {
        update_user_meta(get_current_user_id(), self::RUECKMELDUNG, $rueckmeldung);
    }

    /** @return array<string, mixed> */
    private function rueckmeldung_nehmen(): array
    {
        $rueckmeldung = get_user_meta(get_current_user_id(), self::RUECKMELDUNG, true);
        delete_user_meta(get_current_user_id(), self::RUECKMELDUNG);

        return is_array($rueckmeldung) ? $rueckmeldung : [];
    }

    /** Berechtigung und Nonce - beides, nicht nur eines. */
    private function pruefe_berechtigung(string $aktion): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        check_admin_referer($aktion);
    }

    private function umleiten(string $ziel): void
    {
        wp_safe_redirect($ziel);
        exit;
    }
}

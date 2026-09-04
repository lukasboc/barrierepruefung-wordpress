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

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_post_a11y_checker_connect', [$this, 'handle_connect']);
        add_action('admin_post_a11y_checker_verify', [$this, 'handle_verify']);
        add_action('admin_post_a11y_checker_scan', [$this, 'handle_scan']);
        add_action('admin_post_a11y_checker_disconnect', [$this, 'handle_disconnect']);
        add_action('admin_post_a11y_checker_reset_url', [$this, 'handle_reset_url']);
    }

    public function add_page(): void
    {
        add_management_page(
            __('Barrierefreiheit', 'a11y-checker'),
            __('Barrierefreiheit', 'a11y-checker'),
            self::CAPABILITY,
            'a11y-checker',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Sie haben keine Berechtigung für diese Seite.', 'a11y-checker'));
        }

        $client = new A11y_Checker_Client;
        $verbunden = $client->is_connected();
        $site = null;

        if ($verbunden) {
            $antwort = $client->get('/sites/'.$client->site_id());
            $site = $antwort['ok'] ? ($antwort['data']['data'] ?? null) : null;
        }

        include A11Y_CHECKER_PATH.'views/admin-page.php';
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
        }

        $this->zurueck($antwort['ok'] ? 'geprueft' : 'fehler', $antwort['error']);
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

        foreach (['a11y_checker_declaration', 'a11y_checker_status'] as $transient) {
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
            wp_die(esc_html__('Sie haben keine Berechtigung für diese Aktion.', 'a11y-checker'));
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

<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Verwaltungsseite unter Werkzeuge.
 *
 * Bewusst kleiner Funktionsumfang: Verbinden, Domain bestätigen, prüfen,
 * Ergebnis sehen. Ein Plugin, das alles kann, wird nicht gepflegt und nicht
 * freigegeben (docs/08).
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

        update_option('a11y_checker_api_url', esc_url_raw(wp_unslash($_POST['api_url'] ?? '')));
        update_option('a11y_checker_token', sanitize_text_field(wp_unslash($_POST['token'] ?? '')));
        update_option('a11y_checker_site_id', sanitize_text_field(wp_unslash($_POST['site_id'] ?? '')));

        // Nachweise holen und selbst ausliefern - dafür braucht die Kundin
        // keinen DNS-Zugriff (docs/08).
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

    /** Berechtigung und Nonce - beides, nicht nur eines (docs/08). */
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

<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Liefert den Nachweis der Verfügungsgewalt selbst aus.
 *
 * Das ist der Grund, warum viele WordPress-Betreiber überhaupt zum Plugin
 * greifen: die Domain-Verifikation läuft ohne DNS-Zugriff und ohne
 * Copy-and-paste (docs/08 der Dienst-Dokumentation).
 */
class A11y_Checker_Verification
{
    private const META_NAME = 'a11y-site-verification';

    private const FILE_PATH = '.well-known/a11y-site-verification.txt';

    public function register(): void
    {
        add_action('wp_head', [$this, 'render_meta_tag']);
        add_action('init', [$this, 'register_file_route']);
        add_action('template_redirect', [$this, 'serve_file']);
    }

    public function render_meta_tag(): void
    {
        $token = get_option('a11y_checker_verification_token', '');

        if ($token === '') {
            return;
        }

        printf(
            '<meta name="%s" content="%s">'."\n",
            esc_attr(self::META_NAME),
            esc_attr($token)
        );
    }

    public function register_file_route(): void
    {
        add_rewrite_rule('^'.preg_quote(self::FILE_PATH, '/').'$', 'index.php?a11y_verification=1', 'top');
        add_rewrite_tag('%a11y_verification%', '1');
    }

    /**
     * Liefert die Nachweisdatei aus.
     *
     * Als reiner Text und ohne Theme: eine als HTML gerenderte Datei würde die
     * Prüfung des Tokens scheitern lassen.
     */
    public function serve_file(): void
    {
        if (get_query_var('a11y_verification') !== '1') {
            return;
        }

        $token = get_option('a11y_checker_verification_token', '');

        if ($token === '') {
            status_header(404);
            exit;
        }

        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo esc_html($token);
        exit;
    }
}

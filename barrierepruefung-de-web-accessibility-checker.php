<?php
/**
 * Plugin Name:       Barrierepruefung.de – Web Accessibility Checker
 * Plugin URI:        https://github.com/lukasboc/barrierepruefung-wordpress
 * Description:       Scans this site for accessibility barriers and embeds your accessibility statement via shortcode.
 * Version:           0.6.3
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Lukas Bock
 * Author URI:        https://barrierepruefung.de
 * License:           GPL-2.0-or-later
 * Text Domain:       barrierepruefung-de-web-accessibility-checker
 * Domain Path:       /languages
 *
 * Dieses Plugin ruft einen externen Dienst auf. Welche Daten dabei übertragen
 * werden, steht auf der Einstellungsseite und in der readme.txt — das verlangt
 * das Plugin-Verzeichnis, und es ist ohnehin richtig (docs/08 der Dienst-Dokumentation).
 */

if (! defined('ABSPATH')) {
    exit;
}

define('BARRIEREPRUEFUNG_VERSION', '0.6.3');
define('BARRIEREPRUEFUNG_PATH', plugin_dir_path(__FILE__));
define('BARRIEREPRUEFUNG_BASENAME', plugin_basename(__FILE__));

require_once BARRIEREPRUEFUNG_PATH.'includes/class-barrierepruefung-client.php';
require_once BARRIEREPRUEFUNG_PATH.'includes/class-barrierepruefung-verification.php';
require_once BARRIEREPRUEFUNG_PATH.'includes/class-barrierepruefung-shortcode.php';
require_once BARRIEREPRUEFUNG_PATH.'includes/class-barrierepruefung-admin.php';

/*
 * Merkt den einmaligen Hinweis vor, der nach der Aktivierung den Weg zur Seite
 * zeigt. Im Netzwerk merkt er sich nur fuer die Seite, auf der aktiviert wurde -
 * der Verweis in der Plugin-Liste traegt dort weiter.
 */
register_activation_hook(__FILE__, ['Barrierepruefung_Admin', 'activate']);

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('barrierepruefung-de-web-accessibility-checker', false, dirname(plugin_basename(__FILE__)).'/languages');

    (new Barrierepruefung_Verification)->register();
    (new Barrierepruefung_Shortcode)->register();

    if (is_admin()) {
        (new Barrierepruefung_Admin)->register();
    }
});

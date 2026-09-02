<?php
/**
 * Plugin Name:       Barrierepruefung.de – Web Accessibility Checker
 * Plugin URI:        https://github.com/lukasboc/barrierepruefung-wordpress
 * Description:       Prüft diese Website auf Barrierefreiheit und bindet die Erklärung zur Barrierefreiheit per Shortcode ein.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            lubomedia
 * License:           GPL-2.0-or-later
 * Text Domain:       a11y-checker
 *
 * Dieses Plugin ruft einen externen Dienst auf. Welche Daten dabei übertragen
 * werden, steht auf der Einstellungsseite und in der readme.txt — das verlangt
 * das Plugin-Verzeichnis, und es ist ohnehin richtig (docs/08).
 */

if (! defined('ABSPATH')) {
    exit;
}

define('A11Y_CHECKER_VERSION', '0.2.0');
define('A11Y_CHECKER_PATH', plugin_dir_path(__FILE__));

require_once A11Y_CHECKER_PATH.'includes/class-a11y-checker-client.php';
require_once A11Y_CHECKER_PATH.'includes/class-a11y-checker-verification.php';
require_once A11Y_CHECKER_PATH.'includes/class-a11y-checker-shortcode.php';
require_once A11Y_CHECKER_PATH.'includes/class-a11y-checker-admin.php';

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('a11y-checker', false, dirname(plugin_basename(__FILE__)).'/languages');

    (new A11y_Checker_Verification)->register();
    (new A11y_Checker_Shortcode)->register();

    if (is_admin()) {
        (new A11y_Checker_Admin)->register();
    }
});

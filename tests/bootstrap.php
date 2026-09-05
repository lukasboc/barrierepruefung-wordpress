<?php

require_once __DIR__.'/../vendor/autoload.php';

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
}

if (! defined('A11Y_CHECKER_PATH')) {
    define('A11Y_CHECKER_PATH', __DIR__.'/../');
}

$GLOBALS['wp_stubs'] = [
    'esc_html' => static fn ($t) => htmlspecialchars((string) $t, ENT_QUOTES),
    'esc_attr' => static fn ($t) => htmlspecialchars((string) $t, ENT_QUOTES),
    'wp_kses_post' => static fn ($t) => $t,
    '__' => static fn ($t, $d = null) => $t,
    'shortcode_atts' => static fn ($paare, $atts, $name = '') => array_merge(
        $paare,
        array_intersect_key((array) $atts, $paare)
    ),
    // Der Zwischenspeicher ist die erste Rückfallebene von fetch() - über ihn
    // lässt sich die Antwort der API einspeisen, ohne sie abzurufen. Für alle
    // anderen Schlüssel zählt, was set_transient hinterlegt hat.
    'get_transient' => static fn ($key) => $key === 'a11y_checker_declaration'
        ? ($GLOBALS['wp_erklaerung'] ?? false)
        : ($GLOBALS['wp_transients'][$key] ?? false),
];

function esc_html(...$a) { return ($GLOBALS['wp_stubs']['esc_html'])(...$a); }
function esc_attr(...$a) { return ($GLOBALS['wp_stubs']['esc_attr'])(...$a); }
function wp_kses_post(...$a) { return ($GLOBALS['wp_stubs']['wp_kses_post'])(...$a); }
function __(...$a) { return ($GLOBALS['wp_stubs']['__'])(...$a); }
function shortcode_atts(...$a) { return ($GLOBALS['wp_stubs']['shortcode_atts'])(...$a); }
function get_transient(...$a) { return ($GLOBALS['wp_stubs']['get_transient'])(...$a); }

/*
 * Zweiter Satz Attrappen: die Optionen und der Umleitungsweg der
 * Verwaltungsseite. Damit laesst sich pruefen, was Verbinden und Trennen
 * tatsaechlich in der Datenbank hinterlassen - ohne WordPress.
 */

/** Wird anstelle der Umleitung geworfen; sonst beendete exit() den Testlauf. */
final class A11y_Umleitung extends RuntimeException
{
    public function __construct(public readonly string $ziel)
    {
        parent::__construct($ziel);
    }
}

$GLOBALS['wp_options'] = [];
$GLOBALS['wp_transients'] = [];
$GLOBALS['wp_transients_geloescht'] = [];

/*
 * Antworten der API werden als Warteschlange eingespeist, jede Anfrage
 * mitgeschrieben. So laesst sich pruefen, was die Seite tatsaechlich abruft -
 * und vor allem, was sie dank Zwischenspeicher *nicht* abruft.
 */
$GLOBALS['wp_antworten'] = [];
$GLOBALS['wp_anfragen'] = [];

$GLOBALS['wp_stubs'] += [
    'set_transient' => static function ($schluessel, $wert, $dauer = 0) {
        $GLOBALS['wp_transients'][$schluessel] = $wert;

        return true;
    },
    'wp_remote_request' => static function ($url, $argumente = []) {
        $GLOBALS['wp_anfragen'][] = ['url' => $url, 'args' => $argumente];

        return array_shift($GLOBALS['wp_antworten'])
            ?? ['status' => 200, 'body' => '{"data":[]}'];
    },
    'is_wp_error' => static fn ($wert) => $wert instanceof WP_Error,
    'wp_remote_retrieve_response_code' => static fn ($antwort) => $antwort['status'] ?? 200,
    'wp_remote_retrieve_body' => static fn ($antwort) => $antwort['body'] ?? '',
    'wp_json_encode' => static fn ($wert) => json_encode($wert),
    'sanitize_text_field' => static fn ($text) => is_string($text) ? trim($text) : $text,
    'esc_url_raw' => static fn ($url) => $url,
    'wp_unslash' => static fn ($wert) => $wert,
    'flush_rewrite_rules' => static fn () => null,
    'esc_url' => static fn ($url) => $url,
    'number_format_i18n' => static fn ($zahl) => (string) $zahl,
    // Genug, um die Verwaltungsseite wirklich zu rendern - so faellt auf, wenn
    // ein Anzeigezustand gar nicht erreichbar ist.
    'esc_html_e' => static function ($text, $domain = null) { echo $text; },
    'sanitize_key' => static fn ($wert) => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $wert)),
    'wp_nonce_field' => static function ($aktion) { echo '<input type="hidden" name="_wpnonce">'; },
    'submit_button' => static function ($text, $typ = 'primary', $name = 'submit', $wrap = true, $attribute = []) {
        echo '<button>'.$text.'</button>';
    },
    'date_i18n' => static fn ($format, $zeit = null) => date('Y-m-d', $zeit ?: time()),
];

/** Minimale Nachbildung; gebraucht wird nur is_wp_error(). */
class WP_Error
{
    public function __construct(private string $meldung = 'Fehler') {}

    public function get_error_message(): string
    {
        return $this->meldung;
    }
}

$GLOBALS['wp_stubs'] += [
    'get_option' => static fn ($schluessel, $vorgabe = false) => $GLOBALS['wp_options'][$schluessel] ?? $vorgabe,
    'update_option' => static function ($schluessel, $wert) {
        $GLOBALS['wp_options'][$schluessel] = $wert;

        return true;
    },
    'delete_option' => static function ($schluessel) {
        unset($GLOBALS['wp_options'][$schluessel]);

        return true;
    },
    'delete_transient' => static function ($schluessel) {
        $GLOBALS['wp_transients_geloescht'][] = $schluessel;

        return true;
    },
    'current_user_can' => static fn ($faehigkeit) => $GLOBALS['wp_darf'] ?? true,
    'check_admin_referer' => static fn ($aktion) => true,
    'wp_die' => static function ($text) { throw new RuntimeException('wp_die: '.$text); },
    'wp_safe_redirect' => static function ($ziel) { throw new A11y_Umleitung($ziel); },
    'add_query_arg' => static fn ($argumente, $url) => $url.'?'.http_build_query($argumente),
    'admin_url' => static fn ($pfad = '') => 'https://beispiel.test/wp-admin/'.$pfad,
    'untrailingslashit' => static fn ($wert) => rtrim((string) $wert, '/'),
    'esc_html__' => static fn ($text, $domain = null) => $text,
];

function get_option(...$a) { return ($GLOBALS['wp_stubs']['get_option'])(...$a); }
function update_option(...$a) { return ($GLOBALS['wp_stubs']['update_option'])(...$a); }
function delete_option(...$a) { return ($GLOBALS['wp_stubs']['delete_option'])(...$a); }
function delete_transient(...$a) { return ($GLOBALS['wp_stubs']['delete_transient'])(...$a); }
function current_user_can(...$a) { return ($GLOBALS['wp_stubs']['current_user_can'])(...$a); }
function check_admin_referer(...$a) { return ($GLOBALS['wp_stubs']['check_admin_referer'])(...$a); }
function wp_die(...$a) { return ($GLOBALS['wp_stubs']['wp_die'])(...$a); }
function wp_safe_redirect(...$a) { return ($GLOBALS['wp_stubs']['wp_safe_redirect'])(...$a); }
function add_query_arg(...$a) { return ($GLOBALS['wp_stubs']['add_query_arg'])(...$a); }
function admin_url(...$a) { return ($GLOBALS['wp_stubs']['admin_url'])(...$a); }
function untrailingslashit(...$a) { return ($GLOBALS['wp_stubs']['untrailingslashit'])(...$a); }
function esc_html__(...$a) { return ($GLOBALS['wp_stubs']['esc_html__'])(...$a); }
function set_transient(...$a) { return ($GLOBALS['wp_stubs']['set_transient'])(...$a); }
function wp_remote_request(...$a) { return ($GLOBALS['wp_stubs']['wp_remote_request'])(...$a); }
function is_wp_error(...$a) { return ($GLOBALS['wp_stubs']['is_wp_error'])(...$a); }
function wp_remote_retrieve_response_code(...$a) { return ($GLOBALS['wp_stubs']['wp_remote_retrieve_response_code'])(...$a); }
function wp_remote_retrieve_body(...$a) { return ($GLOBALS['wp_stubs']['wp_remote_retrieve_body'])(...$a); }
function wp_json_encode(...$a) { return ($GLOBALS['wp_stubs']['wp_json_encode'])(...$a); }
function sanitize_text_field(...$a) { return ($GLOBALS['wp_stubs']['sanitize_text_field'])(...$a); }
function esc_url_raw(...$a) { return ($GLOBALS['wp_stubs']['esc_url_raw'])(...$a); }
function wp_unslash(...$a) { return ($GLOBALS['wp_stubs']['wp_unslash'])(...$a); }
function flush_rewrite_rules(...$a) { return ($GLOBALS['wp_stubs']['flush_rewrite_rules'])(...$a); }
function esc_url(...$a) { return ($GLOBALS['wp_stubs']['esc_url'])(...$a); }
function number_format_i18n(...$a) { return ($GLOBALS['wp_stubs']['number_format_i18n'])(...$a); }
function esc_html_e(...$a) { return ($GLOBALS['wp_stubs']['esc_html_e'])(...$a); }
function sanitize_key(...$a) { return ($GLOBALS['wp_stubs']['sanitize_key'])(...$a); }
function wp_nonce_field(...$a) { return ($GLOBALS['wp_stubs']['wp_nonce_field'])(...$a); }
function submit_button(...$a) { return ($GLOBALS['wp_stubs']['submit_button'])(...$a); }
function date_i18n(...$a) { return ($GLOBALS['wp_stubs']['date_i18n'])(...$a); }

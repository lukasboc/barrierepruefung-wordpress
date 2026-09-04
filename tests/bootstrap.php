<?php

require_once __DIR__.'/../vendor/autoload.php';

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
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
    // lässt sich die Antwort der API einspeisen, ohne sie abzurufen.
    'get_transient' => static fn ($key) => $GLOBALS['wp_erklaerung'] ?? false,
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
$GLOBALS['wp_transients_geloescht'] = [];

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

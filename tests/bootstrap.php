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

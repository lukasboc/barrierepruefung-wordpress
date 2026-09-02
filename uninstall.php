<?php
/**
 * Deinstallation: entfernt alle Spuren des Plugins.
 *
 * Als eigene Datei statt als register_uninstall_hook, weil WordPress sie auch
 * dann ausführt, wenn das Plugin selbst nicht mehr geladen werden kann — und
 * weil das Plugin-Verzeichnis diesen Weg erwartet (docs/08 der Dienst-Dokumentation).
 *
 * Entfernt werden auch der zwischengespeicherte Erklärungstext und der
 * Verifikations-Token: Ersterer ist ein Inhalt der Kundin, Letzterer ein
 * Geheimnis. Beides darf nach der Deinstallation nicht in der Datenbank
 * liegenbleiben.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/** Alle Optionen, die dieses Plugin je schreibt. */
const A11Y_CHECKER_OPTIONS = [
    'a11y_checker_api_url',
    'a11y_checker_token',
    'a11y_checker_site_id',
    'a11y_checker_verification_token',
    'a11y_checker_declaration_fallback',
];

/** Alle Transients, die dieses Plugin je schreibt. */
const A11Y_CHECKER_TRANSIENTS = [
    'a11y_checker_declaration',
    'a11y_checker_status',
];

function a11y_checker_purge_site(): void
{
    foreach (A11Y_CHECKER_OPTIONS as $option) {
        delete_option($option);
    }

    foreach (A11Y_CHECKER_TRANSIENTS as $transient) {
        delete_transient($transient);
    }
}

/*
 * In einem Netzwerk hat jede Unterseite ihre eigene Verbindung und ihren
 * eigenen Zwischenspeicher. Nur die aktuelle Seite zu räumen, ließe die
 * Token aller anderen zurück.
 */
if (is_multisite()) {
    $a11y_checker_seiten = get_sites(['fields' => 'ids', 'number' => 0]);

    foreach ($a11y_checker_seiten as $a11y_checker_seiten_id) {
        switch_to_blog((int) $a11y_checker_seiten_id);
        a11y_checker_purge_site();
        restore_current_blog();
    }
} else {
    a11y_checker_purge_site();
}

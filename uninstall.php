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
const BARRIEREPRUEFUNG_OPTIONS = [
    'barrierepruefung_api_url',
    'barrierepruefung_token',
    'barrierepruefung_site_id',
    'barrierepruefung_verification_token',
    'barrierepruefung_declaration_fallback',
    // Das Praefix vor 0.6.0. Wer eine Vorabfassung im Einsatz hatte, soll die
    // Reste nicht behalten - das Token darunter ist ein Geheimnis.
    'a11y_checker_api_url',
    'a11y_checker_token',
    'a11y_checker_site_id',
    'a11y_checker_verification_token',
    'a11y_checker_declaration_fallback',
];

/** Alle Transients, die dieses Plugin je schreibt. */
const BARRIEREPRUEFUNG_TRANSIENTS = [
    'barrierepruefung_declaration',
    'barrierepruefung_status',
    'barrierepruefung_hinweis',
    // Wie oben: das Praefix vor 0.6.0.
    'a11y_checker_declaration',
    'a11y_checker_status',
    'a11y_checker_hinweis',
];

function barrierepruefung_purge_site(): void
{
    foreach (BARRIEREPRUEFUNG_OPTIONS as $option) {
        delete_option($option);
    }

    foreach (BARRIEREPRUEFUNG_TRANSIENTS as $transient) {
        delete_transient($transient);
    }
}

/*
 * In einem Netzwerk hat jede Unterseite ihre eigene Verbindung und ihren
 * eigenen Zwischenspeicher. Nur die aktuelle Seite zu räumen, ließe die
 * Token aller anderen zurück.
 */
if (is_multisite()) {
    $barrierepruefung_seiten = get_sites(['fields' => 'ids', 'number' => 0]);

    foreach ($barrierepruefung_seiten as $barrierepruefung_seiten_id) {
        switch_to_blog((int) $barrierepruefung_seiten_id);
        barrierepruefung_purge_site();
        restore_current_blog();
    }
} else {
    barrierepruefung_purge_site();
}

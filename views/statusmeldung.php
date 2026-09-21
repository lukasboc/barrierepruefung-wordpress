<?php
/**
 * Die Meldung nach einer Aktion der Verwaltungsseite - für beide Reiter.
 *
 * Kommt über die Adresszeile (Barrierepruefung_Admin::zurueck()).
 */
if (! defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesen des eigenen Redirect-Status zur Anzeige, keine Zustandsaenderung; die Aktion selbst wurde bereits mit Nonce geprueft (siehe Barrierepruefung_Admin::pruefe_berechtigung()).
$status = isset($_GET['barrierepruefung_status']) ? sanitize_key(wp_unslash($_GET['barrierepruefung_status'])) : '';
// Kein rawurldecode() hier: PHP dekodiert $_GET-Werte beim Parsen des Query-Strings bereits
// einmal automatisch. Das rawurlencode() in zurueck() gleicht nur aus, dass add_query_arg()
// selbst nicht kodiert - ein zweites Dekodieren hier wuerde vom Aufrufer als Text gemeinte
// Prozent-Sequenzen (z. B. "%41") faelschlich in Zeichen ("A") verwandeln.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wie oben.
$barrierepruefung_meldung = isset($_GET['barrierepruefung_meldung']) ? sanitize_text_field(wp_unslash($_GET['barrierepruefung_meldung'])) : '';

$barrierepruefung_meldungen = [
    'verbunden' => [__('Connected. Please verify the domain now.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'bestaetigt' => [__('The domain is verified.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'nicht_bestaetigt' => [__('The domain could not be verified.', 'barrierepruefung-de-web-accessibility-checker'), 'warning'],
    'geprueft' => [__('The scan has started. It usually takes a few minutes.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'aktualisiert' => [__('The status has been refreshed.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'getrennt' => [__('The connection has been removed. Token, site ID and the stored text of the statement were deleted.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'adresse_zurueckgesetzt' => [__('The service address is back to its default value.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'fehler' => [__('An error occurred.', 'barrierepruefung-de-web-accessibility-checker'), 'error'],
];
?>
    <?php if ($status !== '' && isset($barrierepruefung_meldungen[$status])) : ?>
        <div class="notice notice-<?php echo esc_attr($barrierepruefung_meldungen[$status][1]); ?>" role="status">
            <p>
                <?php echo esc_html($barrierepruefung_meldungen[$status][0]); ?>
                <?php if ($barrierepruefung_meldung !== '') : ?>
                    <br><em><?php echo esc_html($barrierepruefung_meldung); ?></em>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

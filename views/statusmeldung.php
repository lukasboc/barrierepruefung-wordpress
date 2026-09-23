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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wie oben.
$barrierepruefung_gruende = isset($_GET['barrierepruefung_gruende']) ? array_values(array_filter(explode(',', sanitize_text_field(wp_unslash($_GET['barrierepruefung_gruende']))))) : [];

$barrierepruefung_meldungen = [
    'verbunden_bestaetigt' => [__('Connected. The domain is verified as well — you can start the first scan now.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'verbunden_nicht_bestaetigt' => [__('Connected, but the domain could not be verified yet. Fix the cause below, then choose “Verify domain now”.', 'barrierepruefung-de-web-accessibility-checker'), 'warning'],
    'bestaetigt' => [__('The domain is verified.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'nicht_bestaetigt' => [__('The domain could not be verified.', 'barrierepruefung-de-web-accessibility-checker'), 'warning'],
    'geprueft' => [__('The scan has started. It usually takes a few minutes.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'aktualisiert' => [__('The status has been refreshed.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'getrennt' => [__('The connection has been removed. Token, site ID and the stored text of the statement were deleted.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'adresse_zurueckgesetzt' => [__('The service address is back to its default value.', 'barrierepruefung-de-web-accessibility-checker'), 'success'],
    'fehler' => [__('An error occurred.', 'barrierepruefung-de-web-accessibility-checker'), 'error'],
];

/*
 * Warum der Dienst eine Domain nicht bestätigt hat, je Verfahren. Die
 * Schlüssel stammen aus SiteVerificationService im Dienst; was zu tun ist,
 * hängt an WordPress - am Cache etwa - und steht deshalb hier. Ein Grund,
 * den diese Fassung nicht kennt, erscheint roh, statt zu fehlen.
 */
$barrierepruefung_grundtexte = [
    'meta_tag:meta_tag_nicht_gefunden' => __('The verification tag was not found on the homepage. If this site uses a caching plugin or a cache at the host, clear the cache and try again.', 'barrierepruefung-de-web-accessibility-checker'),
    'meta_tag:seite_nicht_erreichbar' => __('The service could not load the homepage. Check that the site is publicly reachable — not in maintenance mode, not behind a password, not blocked by a firewall.', 'barrierepruefung-de-web-accessibility-checker'),
    'file:datei_nicht_erreichbar' => __('The service could not load the file /.well-known/a11y-site-verification.txt. Some servers answer this path themselves instead of passing it on to WordPress. If WordPress runs in a subdirectory, the service looks for the file at the root of the domain.', 'barrierepruefung-de-web-accessibility-checker'),
    'file:token_nicht_gefunden' => __('The file /.well-known/a11y-site-verification.txt contains a different code. Clear the cache if this site uses one, then choose “Verify domain now” again. If WordPress runs in a subdirectory, the service reads the file at the root of the domain instead.', 'barrierepruefung-de-web-accessibility-checker'),
    'file:ungueltige_adresse' => __('The address stored for this site at the service is not valid. Correct it there and try again.', 'barrierepruefung-de-web-accessibility-checker'),
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
            <?php if ($barrierepruefung_gruende !== []) : ?>
                <ul style="list-style:disc;margin-left:1.5em">
                    <?php foreach ($barrierepruefung_gruende as $barrierepruefung_grund) : ?>
                        <li><?php echo esc_html($barrierepruefung_grundtexte[$barrierepruefung_grund] ?? sprintf(
                            /* translators: %s: reason code reported by the service, e.g. "file:token_nicht_gefunden" */
                            __('Reason reported by the service: %s', 'barrierepruefung-de-web-accessibility-checker'),
                            $barrierepruefung_grund
                        )); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

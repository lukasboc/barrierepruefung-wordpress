<?php
if (! defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesen des eigenen Redirect-Status zur Anzeige, keine Zustandsaenderung; die Aktion selbst wurde bereits mit Nonce geprueft (siehe A11y_Checker_Admin::pruefe_berechtigung()).
$status = isset($_GET['a11y_status']) ? sanitize_key(wp_unslash($_GET['a11y_status'])) : '';
// Kein rawurldecode() hier: PHP dekodiert $_GET-Werte beim Parsen des Query-Strings bereits
// einmal automatisch. Das rawurlencode() in zurueck() gleicht nur aus, dass add_query_arg()
// selbst nicht kodiert - ein zweites Dekodieren hier wuerde vom Aufrufer als Text gemeinte
// Prozent-Sequenzen (z. B. "%41") faelschlich in Zeichen ("A") verwandeln.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wie oben.
$a11y_checker_meldung = isset($_GET['a11y_meldung']) ? sanitize_text_field(wp_unslash($_GET['a11y_meldung'])) : '';

$a11y_checker_meldungen = [
    'verbunden' => [__('Verbindung hergestellt. Bestätigen Sie jetzt die Domain.', 'a11y-checker'), 'success'],
    'bestaetigt' => [__('Die Domain ist bestätigt.', 'a11y-checker'), 'success'],
    'nicht_bestaetigt' => [__('Die Domain konnte nicht bestätigt werden.', 'a11y-checker'), 'warning'],
    'geprueft' => [__('Die Prüfung wurde gestartet. Das Ergebnis steht in wenigen Minuten bereit.', 'a11y-checker'), 'success'],
    'getrennt' => [__('Die Verbindung wurde getrennt. Token, Website-Kennung und der gespeicherte Text der Erklärung wurden entfernt.', 'a11y-checker'), 'success'],
    'adresse_zurueckgesetzt' => [__('Die Adresse des Dienstes steht wieder auf dem Standardwert.', 'a11y-checker'), 'success'],
    'fehler' => [__('Es ist ein Fehler aufgetreten.', 'a11y-checker'), 'error'],
];
?>
<div class="wrap">
    <h1><?php esc_html_e('Barrierefreiheit', 'a11y-checker'); ?></h1>

    <?php if ($status !== '' && isset($a11y_checker_meldungen[$status])) : ?>
        <div class="notice notice-<?php echo esc_attr($a11y_checker_meldungen[$status][1]); ?>" role="status">
            <p>
                <?php echo esc_html($a11y_checker_meldungen[$status][0]); ?>
                <?php if ($a11y_checker_meldung !== '') : ?>
                    <br><em><?php echo esc_html($a11y_checker_meldung); ?></em>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (! $verbunden) : ?>
        <h2><?php esc_html_e('Mit Ihrem Konto verbinden', 'a11y-checker'); ?></h2>
        <p>
            <?php esc_html_e('Legen Sie im Konto ein API-Token für diese Website an und tragen Sie es hier ein.', 'a11y-checker'); ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('a11y_checker_connect'); ?>
            <input type="hidden" name="action" value="a11y_checker_connect">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="api_url"><?php esc_html_e('Adresse des Dienstes', 'a11y-checker'); ?></label></th>
                    <td>
                        <?php // Der zuletzt eingetragene Wert, nicht stur der Standard: nach einem
                              // Tippfehler im Token soll die Adresse stehenbleiben. ?>
                        <input id="api_url" name="api_url" type="url" class="regular-text" required
                               value="<?php echo esc_attr($client->api_url()); ?>"
                               aria-describedby="api_url_hinweis">
                        <p class="description" id="api_url_hinweis">
                            <?php printf(
                                /* translators: %s: Standardadresse des Dienstes */
                                esc_html__('Standard: %s', 'a11y-checker'),
                                '<code>'.esc_html(A11y_Checker_Client::DEFAULT_API).'</code>'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="token"><?php esc_html_e('API-Token', 'a11y-checker'); ?></label></th>
                    <td>
                        <input id="token" name="token" type="password" class="regular-text" required autocomplete="off">
                        <p class="description">
                            <?php esc_html_e('Das Token gilt nur für diese Website.', 'a11y-checker'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="site_id"><?php esc_html_e('Website-Kennung', 'a11y-checker'); ?></label></th>
                    <td><input id="site_id" name="site_id" type="text" class="regular-text" required></td>
                </tr>
            </table>

            <?php submit_button(__('Verbinden', 'a11y-checker')); ?>
        </form>

        <?php // Ein eigenes Formular, weil das Feld oben "required" ist: leeren und
              // abschicken geht nicht. Nur zu sehen, wenn es etwas zurueckzusetzen gibt.
        if ($client->api_url() !== A11y_Checker_Client::DEFAULT_API) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('a11y_checker_reset_url'); ?>
                <input type="hidden" name="action" value="a11y_checker_reset_url">
                <?php submit_button(__('Adresse auf den Standardwert zurücksetzen', 'a11y-checker'), 'secondary', 'submit', false); ?>
            </form>
        <?php endif; ?>
    <?php else : ?>
        <h2><?php esc_html_e('Status', 'a11y-checker'); ?></h2>

        <?php if ($site === null) : ?>
            <p><?php esc_html_e('Die Website konnte nicht abgerufen werden. Prüfen Sie Token und Kennung.', 'a11y-checker'); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="max-width:40rem">
                <caption class="screen-reader-text"><?php esc_html_e('Status dieser Website', 'a11y-checker'); ?></caption>
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Adresse', 'a11y-checker'); ?></th>
                        <td><?php echo esc_html($site['base_url'] ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Domain bestätigt', 'a11y-checker'); ?></th>
                        <td>
                            <?php echo empty($site['verified'])
                                ? esc_html__('nein', 'a11y-checker')
                                : esc_html__('ja', 'a11y-checker'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Nächste Prüfung', 'a11y-checker'); ?></th>
                        <td>
                            <?php echo empty($site['next_run_at'])
                                ? esc_html__('nicht geplant', 'a11y-checker')
                                : esc_html(date_i18n(get_option('date_format'), strtotime($site['next_run_at']))); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                <?php if (empty($site['verified'])) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('a11y_checker_verify'); ?>
                        <input type="hidden" name="action" value="a11y_checker_verify">
                        <?php submit_button(__('Domain jetzt bestätigen', 'a11y-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php else : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('a11y_checker_scan'); ?>
                        <input type="hidden" name="action" value="a11y_checker_scan">
                        <?php submit_button(__('Jetzt prüfen', 'a11y-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <h2><?php esc_html_e('Verbindung', 'a11y-checker'); ?></h2>
        <table class="widefat striped" style="max-width:40rem">
            <caption class="screen-reader-text"><?php esc_html_e('Eingetragene Verbindung', 'a11y-checker'); ?></caption>
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Adresse des Dienstes', 'a11y-checker'); ?></th>
                    <td><code><?php echo esc_html($client->api_url()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Website-Kennung', 'a11y-checker'); ?></th>
                    <td><code><?php echo esc_html($client->site_id()); ?></code></td>
                </tr>
            </tbody>
        </table>

        <?php // Das Token steht hier absichtlich nicht - auch nicht gekuerzt. ?>
        <p class="description" id="trennen_hinweis">
            <?php esc_html_e('Trennen entfernt Token, Website-Kennung und den gespeicherten Text der Erklärung aus dieser Installation; danach lassen sich die Angaben neu eintragen. Im Konto bleibt alles bestehen, das Token gilt weiter — widerrufen wird es dort.', 'a11y-checker'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('a11y_checker_disconnect'); ?>
            <input type="hidden" name="action" value="a11y_checker_disconnect">
            <?php submit_button(__('Verbindung trennen', 'a11y-checker'), 'secondary', 'submit', false, ['aria-describedby' => 'trennen_hinweis']); ?>
        </form>

        <h2><?php esc_html_e('Erklärung einbinden', 'a11y-checker'); ?></h2>
        <p><?php esc_html_e('Fügen Sie diesen Shortcode auf der Seite ein, die Ihre Erklärung zur Barrierefreiheit tragen soll:', 'a11y-checker'); ?></p>
        <p><code>[barrierefreiheitserklaerung]</code></p>
        <p class="description">
            <?php esc_html_e('Optionen: teil="maengel" oder teil="kontakt" für einen einzelnen Abschnitt, stand="nein" ohne Datumszeile, ueberschrift="3" zum Einpassen in Ihre Überschriftenhierarchie.', 'a11y-checker'); ?>
        </p>

        <h2><?php esc_html_e('Datenübertragung', 'a11y-checker'); ?></h2>
        <p>
            <?php esc_html_e('Dieses Plugin überträgt die Adresse dieser Website sowie das API-Token an den oben eingetragenen Dienst, um Prüfungen auszulösen und die Erklärung abzurufen. Es werden keine Inhalte Ihrer Nutzerinnen und Nutzer übermittelt.', 'a11y-checker'); ?>
        </p>
    <?php endif; ?>
</div>

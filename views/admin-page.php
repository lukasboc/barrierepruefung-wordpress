<?php
if (! defined('ABSPATH')) {
    exit;
}

$status = isset($_GET['a11y_status']) ? sanitize_key(wp_unslash($_GET['a11y_status'])) : '';
$meldung = isset($_GET['a11y_meldung']) ? sanitize_text_field(rawurldecode(wp_unslash($_GET['a11y_meldung']))) : '';

$meldungen = [
    'verbunden' => [__('Verbindung hergestellt. Bestätigen Sie jetzt die Domain.', 'a11y-checker'), 'success'],
    'bestaetigt' => [__('Die Domain ist bestätigt.', 'a11y-checker'), 'success'],
    'nicht_bestaetigt' => [__('Die Domain konnte nicht bestätigt werden.', 'a11y-checker'), 'warning'],
    'geprueft' => [__('Die Prüfung wurde gestartet. Das Ergebnis steht in wenigen Minuten bereit.', 'a11y-checker'), 'success'],
    'fehler' => [__('Es ist ein Fehler aufgetreten.', 'a11y-checker'), 'error'],
];
?>
<div class="wrap">
    <h1><?php esc_html_e('Barrierefreiheit', 'a11y-checker'); ?></h1>

    <?php if ($status !== '' && isset($meldungen[$status])) : ?>
        <div class="notice notice-<?php echo esc_attr($meldungen[$status][1]); ?>" role="status">
            <p>
                <?php echo esc_html($meldungen[$status][0]); ?>
                <?php if ($meldung !== '') : ?>
                    <br><em><?php echo esc_html($meldung); ?></em>
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
                        <input id="api_url" name="api_url" type="url" class="regular-text" required
                               value="<?php echo esc_attr(A11y_Checker_Client::DEFAULT_API); ?>">
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

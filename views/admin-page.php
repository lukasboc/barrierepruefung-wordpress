<?php
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
<div class="wrap">
    <h1><?php esc_html_e('Accessibility', 'barrierepruefung-de-web-accessibility-checker'); ?></h1>

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

    <?php if (! $verbunden) : ?>
        <h2><?php esc_html_e('Connect to your account', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

        <?php
        // Die Anleitung steht ausgeschrieben da und nicht hinter einem
        // Aufklapper: es ist der erste Bildschirm nach der Aktivierung, und wer
        // hier landet, weiss noch nicht, dass es ueberhaupt ein Konto gibt.
        // Die Adressen kommen aus der eingetragenen Adresse des Dienstes, damit
        // eine eigene Instanz nicht auf barrierepruefung.de verwiesen wird.
        $barrierepruefung_konto = $client->account_url('register');
        $barrierepruefung_dienst = wp_parse_url($client->account_url(), PHP_URL_HOST) ?: $client->account_url();
        ?>

        <p>
            <?php esc_html_e('The plugin does not scan on its own. The scan, the findings and the text of your accessibility statement come from the service; this WordPress installation only fetches them and displays them. To do that it needs an API token from your account there.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </p>

        <h3><?php esc_html_e('Where to get the three values', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>

        <ol>
            <li>
                <?php printf(
                    /* translators: %s: link to the service, its host name as the link text */
                    esc_html__('Create an account at %s — or sign in there if you already have one.', 'barrierepruefung-de-web-accessibility-checker'),
                    '<a href="'.esc_url($barrierepruefung_konto).'" rel="external">'.esc_html($barrierepruefung_dienst).'</a>'
                ); ?>
            </li>
            <li>
                <?php printf(
                    /* translators: %s: address of this WordPress installation */
                    esc_html__('Add this site to your account there. Enter exactly the address of this WordPress installation: %s. If a different address is stored, the domain cannot be verified later.', 'barrierepruefung-de-web-accessibility-checker'),
                    '<code>'.esc_html(home_url('/')).'</code>'
                ); ?>
            </li>
            <li>
                <?php printf(
                    /* translators: 1: path within the service, 2: name of the section there, 3: label of the button there */
                    esc_html__('Open %1$s in your account and choose %3$s in the section %2$s.', 'barrierepruefung-de-web-accessibility-checker'),
                    '<em>'.esc_html__('Websites → your site → Embedding', 'barrierepruefung-de-web-accessibility-checker').'</em>',
                    '<em>'.esc_html__('WordPress plugin and API', 'barrierepruefung-de-web-accessibility-checker').'</em>',
                    '<em>'.esc_html__('Create token', 'barrierepruefung-de-web-accessibility-checker').'</em>'
                ); ?>
            </li>
            <li>
                <?php esc_html_e('The service now shows the API token and the site ID. Copy both into the form below — the token is shown this one time only. If it gets lost, create a new one in the same place; the old one can be revoked there.', 'barrierepruefung-de-web-accessibility-checker'); ?>
            </li>
            <li>
                <?php esc_html_e('The service address is the third value: it says where the plugin sends its requests. It is already filled in below.', 'barrierepruefung-de-web-accessibility-checker'); ?>
            </li>
        </ol>

        <p>
            <?php esc_html_e('Two steps follow after connecting, both from this page: verify the domain — one click, the plugin serves the proof itself, so you need no access to DNS — and start the first scan.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('barrierepruefung_connect'); ?>
            <input type="hidden" name="action" value="barrierepruefung_connect">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="api_url"><?php esc_html_e('Service address', 'barrierepruefung-de-web-accessibility-checker'); ?></label></th>
                    <td>
                        <?php // Der zuletzt eingetragene Wert, nicht stur der Standard: nach einem
                              // Tippfehler im Token soll die Adresse stehenbleiben. ?>
                        <input id="api_url" name="api_url" type="url" class="regular-text" required
                               value="<?php echo esc_attr($client->api_url()); ?>"
                               aria-describedby="api_url_hinweis">
                        <p class="description" id="api_url_hinweis">
                            <?php printf(
                                /* translators: %s: default address of the service */
                                esc_html__('Default: %s', 'barrierepruefung-de-web-accessibility-checker'),
                                '<code>'.esc_html(Barrierepruefung_Client::DEFAULT_API).'</code>'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="token"><?php esc_html_e('API token', 'barrierepruefung-de-web-accessibility-checker'); ?></label></th>
                    <td>
                        <input id="token" name="token" type="password" class="regular-text" required
                               autocomplete="off" aria-describedby="token_hinweis">
                        <?php // Der Hinweis auf den senkrechten Strich ist keine Spitzfindigkeit:
                              // das Token traegt vorn seine Nummer, und wer nur den Teil
                              // dahinter kopiert, bekommt eine Fehlermeldung ohne Grund. ?>
                        <p class="description" id="token_hinweis">
                            <?php esc_html_e('Paste it complete, including the number and the vertical bar in front.', 'barrierepruefung-de-web-accessibility-checker'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="site_id"><?php esc_html_e('Site ID', 'barrierepruefung-de-web-accessibility-checker'); ?></label></th>
                    <td>
                        <input id="site_id" name="site_id" type="text" class="regular-text" required
                               autocomplete="off" aria-describedby="site_id_hinweis">
                        <p class="description" id="site_id_hinweis">
                            <?php esc_html_e('The service shows it next to the token, in the same place.', 'barrierepruefung-de-web-accessibility-checker'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Connect', 'barrierepruefung-de-web-accessibility-checker')); ?>
        </form>

        <?php // Ein eigenes Formular, weil das Feld oben "required" ist: leeren und
              // abschicken geht nicht. Nur zu sehen, wenn es etwas zurueckzusetzen gibt.
        if ($client->api_url() !== Barrierepruefung_Client::DEFAULT_API) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('barrierepruefung_reset_url'); ?>
                <input type="hidden" name="action" value="barrierepruefung_reset_url">
                <?php submit_button(__('Reset address to the default value', 'barrierepruefung-de-web-accessibility-checker'), 'secondary', 'submit', false); ?>
            </form>
        <?php endif; ?>
    <?php else : ?>
        <h2><?php esc_html_e('Status', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

        <?php if ($site === null) : ?>
            <p><?php esc_html_e('The site could not be retrieved. Please check the token and the site ID.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php // Der Grund des Dienstes, damit nicht geraten werden muss. ?>
            <?php if (! empty($abruffehler)) : ?>
                <p class="description"><em><?php echo esc_html($abruffehler); ?></em></p>
            <?php endif; ?>
        <?php else : ?>
            <table class="widefat striped" style="max-width:40rem">
                <caption class="screen-reader-text"><?php esc_html_e('Status of this site', 'barrierepruefung-de-web-accessibility-checker'); ?></caption>
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Address', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                        <td><?php echo esc_html($site['base_url'] ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Domain verified', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                        <td>
                            <?php echo empty($site['verified'])
                                ? esc_html__('no', 'barrierepruefung-de-web-accessibility-checker')
                                : esc_html__('yes', 'barrierepruefung-de-web-accessibility-checker'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Next scan', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                        <td>
                            <?php echo empty($site['next_run_at'])
                                ? esc_html__('not scheduled', 'barrierepruefung-de-web-accessibility-checker')
                                : esc_html(date_i18n(get_option('date_format'), strtotime($site['next_run_at']))); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php
            // Waehrend eines Laufs ist Aktualisieren die einzige sinnvolle
            // Handlung. Der Hinweis steht deshalb vor den Knoepfen, der Knopf
            // dazu ist der hervorgehobene, und "Website pruefen" faellt weg:
            // ein zweiter Lauf verbrauchte nur Kontingent. Vorher ging der
            // graue Knopf neben dem blauen unter, und der Hinweis stand darunter
            // - genau die Stelle, an der niemand nach dem Weg sucht.
            $barrierepruefung_laeuft = ! empty($site['running_scan']);
            ?>

            <?php if ($barrierepruefung_laeuft) : ?>
                <div class="notice notice-info inline" role="status">
                    <p><strong><?php esc_html_e('A scan is running. It usually takes a few minutes.', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></p>
                    <p>
                        <?php esc_html_e('This page does not update on its own: an automatic reload would move the focus and interrupt people using a screen reader (WCAG 2.2.2). Use the button below to fetch the current state.', 'barrierepruefung-de-web-accessibility-checker'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <p>
                <?php if (empty($site['verified'])) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('barrierepruefung_verify'); ?>
                        <input type="hidden" name="action" value="barrierepruefung_verify">
                        <?php submit_button(__('Verify domain now', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php elseif (! $barrierepruefung_laeuft) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('barrierepruefung_scan'); ?>
                        <input type="hidden" name="action" value="barrierepruefung_scan">
                        <?php submit_button(__('Scan now', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php endif; ?>

                <?php // Verwirft den Zwischenspeicher; ein selbsttätiges Neuladen
                      // der Seite waere ein automatischer Kontextwechsel (WCAG 2.2.2).
                      // Waehrend eines Laufs sagt die Aufschrift, wozu der Knopf
                      // gerade da ist - "Aktualisieren" allein beantwortet die
                      // Frage nicht, die man in dem Moment hat. ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                    <?php wp_nonce_field('barrierepruefung_refresh'); ?>
                    <input type="hidden" name="action" value="barrierepruefung_refresh">
                    <?php submit_button(
                        $barrierepruefung_laeuft
                            ? __('Check whether the scan has finished', 'barrierepruefung-de-web-accessibility-checker')
                            : __('Refresh status', 'barrierepruefung-de-web-accessibility-checker'),
                        $barrierepruefung_laeuft ? 'primary' : 'secondary',
                        'submit',
                        false
                    ); ?>
                </form>
            </p>

            <h2><?php esc_html_e('Findings', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

            <?php
            // Drei Zustaende, alle ausgesprochen: noch kein Ergebnis, ein
            // Ergebnis ohne offene Befunde, oder die Arbeitsliste. Eine leere
            // Tabelle waere in allen dreien die falsche Antwort.
            $barrierepruefung_lauf = $site['latest_scan'] ?? null;
            $barrierepruefung_schweregrade = [
                'critical' => __('critical', 'barrierepruefung-de-web-accessibility-checker'),
                'serious' => __('serious', 'barrierepruefung-de-web-accessibility-checker'),
                'moderate' => __('moderate', 'barrierepruefung-de-web-accessibility-checker'),
                'minor' => __('minor', 'barrierepruefung-de-web-accessibility-checker'),
            ];
            ?>

            <?php if (! is_array($barrierepruefung_lauf) && $barrierepruefung_laeuft) : ?>
                <p><?php esc_html_e('The first scan is running. Once it has finished, the findings appear here.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php elseif (! is_array($barrierepruefung_lauf)) : ?>
                <p><?php esc_html_e('There is no scan result for this site yet. Choose “Scan now”.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php elseif (empty($befunde)) : ?>
                <p><?php esc_html_e('The last scan found no open findings.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php else : ?>
                <p>
                    <?php printf(
                        /* translators: %s: date of the last scan */
                        esc_html__('Open findings from the scan on %s. Fix them in WordPress and then start a new scan.', 'barrierepruefung-de-web-accessibility-checker'),
                        esc_html(empty($barrierepruefung_lauf['finished_at'])
                            ? __('an unknown date', 'barrierepruefung-de-web-accessibility-checker')
                            : date_i18n(get_option('date_format'), strtotime($barrierepruefung_lauf['finished_at'])))
                    ); ?>
                </p>

                <table class="widefat striped">
                    <caption class="screen-reader-text"><?php esc_html_e('Open findings by rule', 'barrierepruefung-de-web-accessibility-checker'); ?></caption>
                    <thead>
                        <tr>
                            <?php // Der Schweregrad steht als Wort in der Zelle - Farbe allein
                                  // waere hier der Fehler, den das Produkt selbst benennt (WCAG 1.4.1). ?>
                            <th scope="col"><?php esc_html_e('Severity', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Finding', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Success criterion', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Occurrences', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Affected pages', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($befunde as $barrierepruefung_befund) : ?>
                            <tr>
                                <td>
                                    <?php $barrierepruefung_grad = (string) ($barrierepruefung_befund['severity'] ?? '');
                                    echo esc_html($barrierepruefung_schweregrade[$barrierepruefung_grad] ?? $barrierepruefung_grad); ?>
                                </td>
                                <th scope="row">
                                    <?php echo esc_html($barrierepruefung_befund['title'] ?? $barrierepruefung_befund['rule'] ?? ''); ?>
                                    <?php if (! empty($barrierepruefung_befund['remediation'])) : ?>
                                        <p class="description">
                                            <?php printf(
                                                /* translators: %s: note on how to fix the finding */
                                                esc_html__('How to fix: %s', 'barrierepruefung-de-web-accessibility-checker'),
                                                esc_html($barrierepruefung_befund['remediation'])
                                            ); ?>
                                        </p>
                                    <?php endif; ?>
                                </th>
                                <td><?php echo esc_html(implode(', ', (array) ($barrierepruefung_befund['success_criteria'] ?? []))); ?></td>
                                <td>
                                    <?php
                                    // Ein gewoehnlicher Verweis, kein Aufklappen per Skript: die
                                    // Fundstellen werden erst geholt, wenn jemand sie sehen will,
                                    // und die Seite bleibt ohne JavaScript bedienbar.
                                    $barrierepruefung_regel_key = (string) ($barrierepruefung_befund['rule'] ?? '');
                                    $barrierepruefung_offen = $barrierepruefung_regel_key !== '' && $barrierepruefung_regel_key === $regel;
                                    $barrierepruefung_anzahl = number_format_i18n((int) ($barrierepruefung_befund['occurrences'] ?? 0));
                                    ?>
                                    <?php if ($barrierepruefung_regel_key === '') : ?>
                                        <?php echo esc_html($barrierepruefung_anzahl); ?>
                                    <?php elseif ($barrierepruefung_offen) : ?>
                                        <?php echo esc_html($barrierepruefung_anzahl); ?>
                                        <br>
                                        <a href="<?php echo esc_url(admin_url('tools.php?page=barrierepruefung')); ?>">
                                            <?php esc_html_e('Hide occurrences', 'barrierepruefung-de-web-accessibility-checker'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(add_query_arg(
                                            ['page' => 'barrierepruefung', 'barrierepruefung_regel' => $barrierepruefung_regel_key],
                                            admin_url('tools.php')
                                        )).'#barrierepruefung-fundstellen'; ?>">
                                            <?php printf(
                                                /* translators: %s: number of occurrences */
                                                esc_html__('Show %s occurrences', 'barrierepruefung-de-web-accessibility-checker'),
                                                esc_html($barrierepruefung_anzahl)
                                            ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $barrierepruefung_seiten = (array) ($barrierepruefung_befund['page_urls'] ?? []); ?>
                                    <?php if ($barrierepruefung_seiten === []) : ?>
                                        —
                                    <?php else : ?>
                                        <ul>
                                            <?php foreach ($barrierepruefung_seiten as $barrierepruefung_seite) : ?>
                                                <li><a href="<?php echo esc_url($barrierepruefung_seite); ?>"><?php echo esc_html($barrierepruefung_seite); ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php // Die Liste ist gedeckelt; die volle Zahl steht in "pages". ?>
                                        <?php if ((int) ($barrierepruefung_befund['pages'] ?? 0) > count($barrierepruefung_seiten)) : ?>
                                            <p class="description">
                                                <?php printf(
                                                    /* translators: %s: number of further affected pages */
                                                    esc_html__('and %s more', 'barrierepruefung-de-web-accessibility-checker'),
                                                    esc_html(number_format_i18n((int) $barrierepruefung_befund['pages'] - count($barrierepruefung_seiten)))
                                                ); ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($regel !== '') : ?>
                <h3 id="barrierepruefung-fundstellen"><?php esc_html_e('Occurrences', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>

                <?php if (! is_array($fundstellen)) : ?>
                    <p><?php esc_html_e('The occurrences could not be retrieved.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
                <?php elseif ($fundstellen['items'] === []) : ?>
                    <p><?php esc_html_e('This rule has no occurrences in the last scan.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
                <?php else : ?>
                    <p>
                        <?php printf(
                            /* translators: 1: first occurrence shown, 2: last occurrence shown, 3: total number */
                            esc_html__('Occurrences %1$s to %2$s of %3$s.', 'barrierepruefung-de-web-accessibility-checker'),
                            esc_html(number_format_i18n($fundstellen['offset'] + 1)),
                            esc_html(number_format_i18n($fundstellen['offset'] + count($fundstellen['items']))),
                            esc_html(number_format_i18n($fundstellen['total']))
                        ); ?>
                    </p>

                    <ol>
                        <?php foreach ($fundstellen['items'] as $barrierepruefung_stelle) : ?>
                            <li style="margin-bottom:1rem">
                                <?php if (! empty($barrierepruefung_stelle['page_url'])) : ?>
                                    <a href="<?php echo esc_url($barrierepruefung_stelle['page_url']); ?>"><?php echo esc_html($barrierepruefung_stelle['page_url']); ?></a><br>
                                <?php endif; ?>

                                <?php // Ohne Selektor betrifft der Befund die Seite als Ganzes. ?>
                                <code><?php echo esc_html(empty($barrierepruefung_stelle['selector'])
                                    ? __('the entire page', 'barrierepruefung-de-web-accessibility-checker')
                                    : $barrierepruefung_stelle['selector']); ?></code>

                                <?php if (! empty($barrierepruefung_stelle['ui_state'])) : ?>
                                    <p class="description">
                                        <?php printf(
                                            /* translators: %s: the state in which the element becomes visible */
                                            esc_html__('Visible only after: %s', 'barrierepruefung-de-web-accessibility-checker'),
                                            esc_html($barrierepruefung_stelle['ui_state'])
                                        ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (! empty($barrierepruefung_stelle['viewport'])) : ?>
                                    <p class="description"><?php echo esc_html($barrierepruefung_stelle['viewport']); ?></p>
                                <?php endif; ?>

                                <?php if (! empty($barrierepruefung_stelle['measurements'])) : ?>
                                    <p><?php echo esc_html(implode(' · ', (array) $barrierepruefung_stelle['measurements'])); ?></p>
                                <?php endif; ?>

                                <?php // Der Hexwert steht als Text da; ein Farbfeld allein waere
                                      // hier der falsche Bedeutungstraeger (WCAG 1.4.1). ?>
                                <?php if (! empty($barrierepruefung_stelle['colors'])) : ?>
                                    <p>
                                        <?php $barrierepruefung_farbteile = [];
                                        foreach ((array) $barrierepruefung_stelle['colors'] as $barrierepruefung_bez => $barrierepruefung_hex) {
                                            $barrierepruefung_farbteile[] = $barrierepruefung_bez.': '.$barrierepruefung_hex;
                                        }
                                        echo esc_html(implode(' · ', $barrierepruefung_farbteile)); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (! empty($barrierepruefung_stelle['summary'])) : ?>
                                    <p class="description"><?php echo esc_html($barrierepruefung_stelle['summary']); ?></p>
                                <?php endif; ?>

                                <?php if (! empty($barrierepruefung_stelle['html_snippet'])) : ?>
                                    <pre style="white-space:pre-wrap;overflow-x:auto"><code><?php echo esc_html($barrierepruefung_stelle['html_snippet']); ?></code></pre>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <?php
                    // Blaettern als gewoehnliche Verweise - kein Skript, keine
                    // Formularabsendung fuer einen reinen Lesevorgang.
                    $barrierepruefung_ab = $fundstellen['offset'];
                    $barrierepruefung_schritt = max(1, $fundstellen['limit']);
                    $barrierepruefung_blaettern = static function (int $ab) use ($regel) {
                        return esc_url(add_query_arg(
                            array_filter([
                                'page' => 'barrierepruefung',
                                'barrierepruefung_regel' => $regel,
                                'barrierepruefung_ab' => $ab > 0 ? $ab : null,
                            ]),
                            admin_url('tools.php')
                        )).'#barrierepruefung-fundstellen';
                    };
                    ?>
                    <p>
                        <?php if ($barrierepruefung_ab > 0) : ?>
                            <a href="<?php echo $barrierepruefung_blaettern(max(0, $barrierepruefung_ab - $barrierepruefung_schritt)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- in der Closure bereits mit esc_url() behandelt. ?>">
                                <?php esc_html_e('Previous occurrences', 'barrierepruefung-de-web-accessibility-checker'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($barrierepruefung_ab + count($fundstellen['items']) < $fundstellen['total']) : ?>
                            <a href="<?php echo $barrierepruefung_blaettern($barrierepruefung_ab + $barrierepruefung_schritt); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- siehe oben. ?>">
                                <?php esc_html_e('More occurrences', 'barrierepruefung-de-web-accessibility-checker'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (is_array($barrierepruefung_lauf) && ! empty($barrierepruefung_lauf['report_url'])) : ?>
                <?php // Screenshots werden bewusst nicht hierher geholt: sie in eine
                      // fremde Installation zu kopieren hiesse, sie dort ohne
                      // Aufbewahrungsfrist und ohne Zugriffspruefung liegen zu haben. ?>
                <p>
                    <?php esc_html_e('Screenshots of the occurrences are available in the full report on the service’s website. Opening it asks you to sign in first.', 'barrierepruefung-de-web-accessibility-checker'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($barrierepruefung_lauf['report_url']); ?>">
                        <?php esc_html_e('Open the full report with screenshots', 'barrierepruefung-de-web-accessibility-checker'); ?>
                    </a>
                </p>
            <?php endif; ?>

            <h2><?php esc_html_e('Quota', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

            <?php $barrierepruefung_kontingent = $site['quota'] ?? null; ?>
            <?php if (! is_array($barrierepruefung_kontingent)) : ?>
                <p><?php esc_html_e('The quota could not be retrieved.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="max-width:40rem">
                    <caption class="screen-reader-text"><?php esc_html_e('Pages scanned in the current billing period', 'barrierepruefung-de-web-accessibility-checker'); ?></caption>
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Pages scanned in this period', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                            <td>
                                <?php // Eine hinterlegte null bedeutet unbegrenzt und darf nicht als 0 erscheinen. ?>
                                <?php if ($barrierepruefung_kontingent['limit'] === null) : ?>
                                    <?php printf(
                                        /* translators: %s: number of pages scanned */
                                        esc_html__('%s (unlimited)', 'barrierepruefung-de-web-accessibility-checker'),
                                        esc_html(number_format_i18n((int) $barrierepruefung_kontingent['used']))
                                    ); ?>
                                <?php else : ?>
                                    <?php printf(
                                        /* translators: 1: pages used, 2: pages included */
                                        esc_html__('%1$s of %2$s', 'barrierepruefung-de-web-accessibility-checker'),
                                        esc_html(number_format_i18n((int) $barrierepruefung_kontingent['used'])),
                                        esc_html(number_format_i18n((int) $barrierepruefung_kontingent['limit']))
                                    ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($barrierepruefung_kontingent['limit'] !== null) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Remaining', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                                <td><?php echo esc_html(number_format_i18n((int) $barrierepruefung_kontingent['remaining'])); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($barrierepruefung_kontingent['period_end'])) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Period ends', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($barrierepruefung_kontingent['period_end']))); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <h2><?php esc_html_e('Connection', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>
        <table class="widefat striped" style="max-width:40rem">
            <caption class="screen-reader-text"><?php esc_html_e('Stored connection', 'barrierepruefung-de-web-accessibility-checker'); ?></caption>
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Service address', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                    <td><code><?php echo esc_html($client->api_url()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Site ID', 'barrierepruefung-de-web-accessibility-checker'); ?></th>
                    <td><code><?php echo esc_html($client->site_id()); ?></code></td>
                </tr>
            </tbody>
        </table>

        <?php // Das Token steht hier absichtlich nicht - auch nicht gekuerzt. ?>
        <p class="description" id="trennen_hinweis">
            <?php esc_html_e('Disconnecting removes the token, the site ID and the stored text of the statement from this installation; you can then enter the details again. Nothing changes in your account — the token stays valid and is revoked there.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('barrierepruefung_disconnect'); ?>
            <input type="hidden" name="action" value="barrierepruefung_disconnect">
            <?php submit_button(__('Disconnect', 'barrierepruefung-de-web-accessibility-checker'), 'secondary', 'submit', false, ['aria-describedby' => 'trennen_hinweis']); ?>
        </form>

        <h2><?php esc_html_e('Embed the statement', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>
        <p><?php esc_html_e('Add this shortcode to the page that should carry your accessibility statement:', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
        <p><code>[barrierefreiheitserklaerung]</code></p>
        <p class="description">
            <?php esc_html_e('Options: teil="maengel" or teil="kontakt" for a single section, stand="nein" to omit the date line, ueberschrift="3" to fit your heading hierarchy.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </p>

        <h2><?php esc_html_e('Data transfer', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>
        <p>
            <?php esc_html_e('This plugin sends the address of this site and the API token to the service configured above in order to start scans and retrieve the statement. No content belonging to your visitors is transmitted.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </p>
    <?php endif; ?>
</div>

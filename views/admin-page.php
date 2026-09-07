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
    'verbunden' => [__('Connected. Please verify the domain now.', 'a11y-checker'), 'success'],
    'bestaetigt' => [__('The domain is verified.', 'a11y-checker'), 'success'],
    'nicht_bestaetigt' => [__('The domain could not be verified.', 'a11y-checker'), 'warning'],
    'geprueft' => [__('The scan has started. It usually takes a few minutes.', 'a11y-checker'), 'success'],
    'aktualisiert' => [__('The status has been refreshed.', 'a11y-checker'), 'success'],
    'getrennt' => [__('The connection has been removed. Token, site ID and the stored text of the statement were deleted.', 'a11y-checker'), 'success'],
    'adresse_zurueckgesetzt' => [__('The service address is back to its default value.', 'a11y-checker'), 'success'],
    'fehler' => [__('An error occurred.', 'a11y-checker'), 'error'],
];
?>
<div class="wrap">
    <h1><?php esc_html_e('Accessibility', 'a11y-checker'); ?></h1>

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
        <h2><?php esc_html_e('Connect to your account', 'a11y-checker'); ?></h2>

        <?php
        // Die Anleitung steht ausgeschrieben da und nicht hinter einem
        // Aufklapper: es ist der erste Bildschirm nach der Aktivierung, und wer
        // hier landet, weiss noch nicht, dass es ueberhaupt ein Konto gibt.
        // Die Adressen kommen aus der eingetragenen Adresse des Dienstes, damit
        // eine eigene Instanz nicht auf barrierepruefung.de verwiesen wird.
        $a11y_checker_konto = $client->account_url('register');
        $a11y_checker_dienst = wp_parse_url($client->account_url(), PHP_URL_HOST) ?: $client->account_url();
        ?>

        <p>
            <?php esc_html_e('The plugin does not scan on its own. The scan, the findings and the text of your accessibility statement come from the service; this WordPress installation only fetches them and displays them. To do that it needs an API token from your account there.', 'a11y-checker'); ?>
        </p>
        <p>
            <?php esc_html_e('The token is valid for this one site: with it the plugin may start scans and read results and the statement — nothing else. It is stored in this WordPress installation only.', 'a11y-checker'); ?>
        </p>

        <h3><?php esc_html_e('Where to get the three values', 'a11y-checker'); ?></h3>

        <ol>
            <li>
                <?php printf(
                    /* translators: %s: link to the service, its host name as the link text */
                    esc_html__('Create an account at %s — or sign in there if you already have one.', 'a11y-checker'),
                    '<a href="'.esc_url($a11y_checker_konto).'" rel="external">'.esc_html($a11y_checker_dienst).'</a>'
                ); ?>
            </li>
            <li>
                <?php printf(
                    /* translators: %s: address of this WordPress installation */
                    esc_html__('Add this site to your account there. Enter exactly the address of this WordPress installation: %s. If a different address is stored, the domain cannot be verified later.', 'a11y-checker'),
                    '<code>'.esc_html(home_url('/')).'</code>'
                ); ?>
            </li>
            <li>
                <?php printf(
                    /* translators: 1: path within the service, 2: name of the section there, 3: label of the button there */
                    esc_html__('Open %1$s in your account and choose %3$s in the section %2$s.', 'a11y-checker'),
                    '<em>'.esc_html__('Websites → your site → Embedding', 'a11y-checker').'</em>',
                    '<em>'.esc_html__('WordPress plugin and API', 'a11y-checker').'</em>',
                    '<em>'.esc_html__('Create token', 'a11y-checker').'</em>'
                ); ?>
            </li>
            <li>
                <?php esc_html_e('The service now shows the API token and the site ID. Copy both into the form below — the token is shown this one time only. If it gets lost, create a new one in the same place; the old one can be revoked there.', 'a11y-checker'); ?>
            </li>
            <li>
                <?php esc_html_e('Leave the service address as it is. It only needs changing if you run the service yourself.', 'a11y-checker'); ?>
            </li>
        </ol>

        <p>
            <?php esc_html_e('Two steps follow after connecting, both from this page: verify the domain — one click, the plugin serves the proof itself, so you need no access to DNS — and start the first scan.', 'a11y-checker'); ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('a11y_checker_connect'); ?>
            <input type="hidden" name="action" value="a11y_checker_connect">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="api_url"><?php esc_html_e('Service address', 'a11y-checker'); ?></label></th>
                    <td>
                        <?php // Der zuletzt eingetragene Wert, nicht stur der Standard: nach einem
                              // Tippfehler im Token soll die Adresse stehenbleiben. ?>
                        <input id="api_url" name="api_url" type="url" class="regular-text" required
                               value="<?php echo esc_attr($client->api_url()); ?>"
                               aria-describedby="api_url_hinweis">
                        <p class="description" id="api_url_hinweis">
                            <?php printf(
                                /* translators: %s: default address of the service */
                                esc_html__('Default: %s', 'a11y-checker'),
                                '<code>'.esc_html(A11y_Checker_Client::DEFAULT_API).'</code>'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="token"><?php esc_html_e('API token', 'a11y-checker'); ?></label></th>
                    <td>
                        <input id="token" name="token" type="password" class="regular-text" required
                               autocomplete="off" aria-describedby="token_hinweis">
                        <?php // Der Hinweis auf den senkrechten Strich ist keine Spitzfindigkeit:
                              // das Token traegt vorn seine Nummer, und wer nur den Teil
                              // dahinter kopiert, bekommt eine Fehlermeldung ohne Grund. ?>
                        <p class="description" id="token_hinweis">
                            <?php esc_html_e('Paste it complete, including the number and the vertical bar in front.', 'a11y-checker'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="site_id"><?php esc_html_e('Site ID', 'a11y-checker'); ?></label></th>
                    <td>
                        <input id="site_id" name="site_id" type="text" class="regular-text" required
                               autocomplete="off" aria-describedby="site_id_hinweis">
                        <p class="description" id="site_id_hinweis">
                            <?php esc_html_e('The service shows it next to the token, in the same place.', 'a11y-checker'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Connect', 'a11y-checker')); ?>
        </form>

        <?php // Ein eigenes Formular, weil das Feld oben "required" ist: leeren und
              // abschicken geht nicht. Nur zu sehen, wenn es etwas zurueckzusetzen gibt.
        if ($client->api_url() !== A11y_Checker_Client::DEFAULT_API) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('a11y_checker_reset_url'); ?>
                <input type="hidden" name="action" value="a11y_checker_reset_url">
                <?php submit_button(__('Reset address to the default value', 'a11y-checker'), 'secondary', 'submit', false); ?>
            </form>
        <?php endif; ?>
    <?php else : ?>
        <h2><?php esc_html_e('Status', 'a11y-checker'); ?></h2>

        <?php if ($site === null) : ?>
            <p><?php esc_html_e('The site could not be retrieved. Please check the token and the site ID.', 'a11y-checker'); ?></p>
            <?php // Der Grund des Dienstes, damit nicht geraten werden muss. ?>
            <?php if (! empty($abruffehler)) : ?>
                <p class="description"><em><?php echo esc_html($abruffehler); ?></em></p>
            <?php endif; ?>
        <?php else : ?>
            <table class="widefat striped" style="max-width:40rem">
                <caption class="screen-reader-text"><?php esc_html_e('Status of this site', 'a11y-checker'); ?></caption>
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Address', 'a11y-checker'); ?></th>
                        <td><?php echo esc_html($site['base_url'] ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Domain verified', 'a11y-checker'); ?></th>
                        <td>
                            <?php echo empty($site['verified'])
                                ? esc_html__('no', 'a11y-checker')
                                : esc_html__('yes', 'a11y-checker'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Next scan', 'a11y-checker'); ?></th>
                        <td>
                            <?php echo empty($site['next_run_at'])
                                ? esc_html__('not scheduled', 'a11y-checker')
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
            $a11y_checker_laeuft = ! empty($site['running_scan']);
            ?>

            <?php if ($a11y_checker_laeuft) : ?>
                <div class="notice notice-info inline" role="status">
                    <p><strong><?php esc_html_e('A scan is running. It usually takes a few minutes.', 'a11y-checker'); ?></strong></p>
                    <p>
                        <?php esc_html_e('This page does not update on its own: an automatic reload would move the focus and interrupt people using a screen reader (WCAG 2.2.2). Use the button below to fetch the current state.', 'a11y-checker'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <p>
                <?php if (empty($site['verified'])) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('a11y_checker_verify'); ?>
                        <input type="hidden" name="action" value="a11y_checker_verify">
                        <?php submit_button(__('Verify domain now', 'a11y-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php elseif (! $a11y_checker_laeuft) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('a11y_checker_scan'); ?>
                        <input type="hidden" name="action" value="a11y_checker_scan">
                        <?php submit_button(__('Scan now', 'a11y-checker'), 'primary', 'submit', false); ?>
                    </form>
                <?php endif; ?>

                <?php // Verwirft den Zwischenspeicher; ein selbsttätiges Neuladen
                      // der Seite waere ein automatischer Kontextwechsel (WCAG 2.2.2).
                      // Waehrend eines Laufs sagt die Aufschrift, wozu der Knopf
                      // gerade da ist - "Aktualisieren" allein beantwortet die
                      // Frage nicht, die man in dem Moment hat. ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                    <?php wp_nonce_field('a11y_checker_refresh'); ?>
                    <input type="hidden" name="action" value="a11y_checker_refresh">
                    <?php submit_button(
                        $a11y_checker_laeuft
                            ? __('Check whether the scan has finished', 'a11y-checker')
                            : __('Refresh status', 'a11y-checker'),
                        $a11y_checker_laeuft ? 'primary' : 'secondary',
                        'submit',
                        false
                    ); ?>
                </form>
            </p>

            <h2><?php esc_html_e('Findings', 'a11y-checker'); ?></h2>

            <?php
            // Drei Zustaende, alle ausgesprochen: noch kein Ergebnis, ein
            // Ergebnis ohne offene Befunde, oder die Arbeitsliste. Eine leere
            // Tabelle waere in allen dreien die falsche Antwort.
            $a11y_checker_lauf = $site['latest_scan'] ?? null;
            $a11y_checker_schweregrade = [
                'critical' => __('critical', 'a11y-checker'),
                'serious' => __('serious', 'a11y-checker'),
                'moderate' => __('moderate', 'a11y-checker'),
                'minor' => __('minor', 'a11y-checker'),
            ];
            ?>

            <?php if (! is_array($a11y_checker_lauf) && $a11y_checker_laeuft) : ?>
                <p><?php esc_html_e('The first scan is running. Once it has finished, the findings appear here.', 'a11y-checker'); ?></p>
            <?php elseif (! is_array($a11y_checker_lauf)) : ?>
                <p><?php esc_html_e('There is no scan result for this site yet. Choose “Scan now”.', 'a11y-checker'); ?></p>
            <?php elseif (empty($befunde)) : ?>
                <p><?php esc_html_e('The last scan found no open findings.', 'a11y-checker'); ?></p>
            <?php else : ?>
                <p>
                    <?php printf(
                        /* translators: %s: date of the last scan */
                        esc_html__('Open findings from the scan on %s. Fix them in WordPress and then start a new scan.', 'a11y-checker'),
                        esc_html(empty($a11y_checker_lauf['finished_at'])
                            ? __('an unknown date', 'a11y-checker')
                            : date_i18n(get_option('date_format'), strtotime($a11y_checker_lauf['finished_at'])))
                    ); ?>
                </p>

                <table class="widefat striped">
                    <caption class="screen-reader-text"><?php esc_html_e('Open findings by rule', 'a11y-checker'); ?></caption>
                    <thead>
                        <tr>
                            <?php // Der Schweregrad steht als Wort in der Zelle - Farbe allein
                                  // waere hier der Fehler, den das Produkt selbst benennt (WCAG 1.4.1). ?>
                            <th scope="col"><?php esc_html_e('Severity', 'a11y-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Finding', 'a11y-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Success criterion', 'a11y-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Occurrences', 'a11y-checker'); ?></th>
                            <th scope="col"><?php esc_html_e('Affected pages', 'a11y-checker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($befunde as $a11y_checker_befund) : ?>
                            <tr>
                                <td>
                                    <?php $a11y_checker_grad = (string) ($a11y_checker_befund['severity'] ?? '');
                                    echo esc_html($a11y_checker_schweregrade[$a11y_checker_grad] ?? $a11y_checker_grad); ?>
                                </td>
                                <th scope="row">
                                    <?php echo esc_html($a11y_checker_befund['title'] ?? $a11y_checker_befund['rule'] ?? ''); ?>
                                    <?php if (! empty($a11y_checker_befund['remediation'])) : ?>
                                        <p class="description">
                                            <?php printf(
                                                /* translators: %s: note on how to fix the finding */
                                                esc_html__('How to fix: %s', 'a11y-checker'),
                                                esc_html($a11y_checker_befund['remediation'])
                                            ); ?>
                                        </p>
                                    <?php endif; ?>
                                </th>
                                <td><?php echo esc_html(implode(', ', (array) ($a11y_checker_befund['success_criteria'] ?? []))); ?></td>
                                <td>
                                    <?php
                                    // Ein gewoehnlicher Verweis, kein Aufklappen per Skript: die
                                    // Fundstellen werden erst geholt, wenn jemand sie sehen will,
                                    // und die Seite bleibt ohne JavaScript bedienbar.
                                    $a11y_checker_regel_key = (string) ($a11y_checker_befund['rule'] ?? '');
                                    $a11y_checker_offen = $a11y_checker_regel_key !== '' && $a11y_checker_regel_key === $regel;
                                    $a11y_checker_anzahl = number_format_i18n((int) ($a11y_checker_befund['occurrences'] ?? 0));
                                    ?>
                                    <?php if ($a11y_checker_regel_key === '') : ?>
                                        <?php echo esc_html($a11y_checker_anzahl); ?>
                                    <?php elseif ($a11y_checker_offen) : ?>
                                        <?php echo esc_html($a11y_checker_anzahl); ?>
                                        <br>
                                        <a href="<?php echo esc_url(admin_url('tools.php?page=a11y-checker')); ?>">
                                            <?php esc_html_e('Hide occurrences', 'a11y-checker'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(add_query_arg(
                                            ['page' => 'a11y-checker', 'a11y_regel' => $a11y_checker_regel_key],
                                            admin_url('tools.php')
                                        )).'#a11y-fundstellen'; ?>">
                                            <?php printf(
                                                /* translators: %s: number of occurrences */
                                                esc_html__('Show %s occurrences', 'a11y-checker'),
                                                esc_html($a11y_checker_anzahl)
                                            ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $a11y_checker_seiten = (array) ($a11y_checker_befund['page_urls'] ?? []); ?>
                                    <?php if ($a11y_checker_seiten === []) : ?>
                                        —
                                    <?php else : ?>
                                        <ul>
                                            <?php foreach ($a11y_checker_seiten as $a11y_checker_seite) : ?>
                                                <li><a href="<?php echo esc_url($a11y_checker_seite); ?>"><?php echo esc_html($a11y_checker_seite); ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php // Die Liste ist gedeckelt; die volle Zahl steht in "pages". ?>
                                        <?php if ((int) ($a11y_checker_befund['pages'] ?? 0) > count($a11y_checker_seiten)) : ?>
                                            <p class="description">
                                                <?php printf(
                                                    /* translators: %s: number of further affected pages */
                                                    esc_html__('and %s more', 'a11y-checker'),
                                                    esc_html(number_format_i18n((int) $a11y_checker_befund['pages'] - count($a11y_checker_seiten)))
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
                <h3 id="a11y-fundstellen"><?php esc_html_e('Occurrences', 'a11y-checker'); ?></h3>

                <?php if (! is_array($fundstellen)) : ?>
                    <p><?php esc_html_e('The occurrences could not be retrieved.', 'a11y-checker'); ?></p>
                <?php elseif ($fundstellen['items'] === []) : ?>
                    <p><?php esc_html_e('This rule has no occurrences in the last scan.', 'a11y-checker'); ?></p>
                <?php else : ?>
                    <p>
                        <?php printf(
                            /* translators: 1: first occurrence shown, 2: last occurrence shown, 3: total number */
                            esc_html__('Occurrences %1$s to %2$s of %3$s.', 'a11y-checker'),
                            esc_html(number_format_i18n($fundstellen['offset'] + 1)),
                            esc_html(number_format_i18n($fundstellen['offset'] + count($fundstellen['items']))),
                            esc_html(number_format_i18n($fundstellen['total']))
                        ); ?>
                    </p>

                    <ol>
                        <?php foreach ($fundstellen['items'] as $a11y_checker_stelle) : ?>
                            <li style="margin-bottom:1rem">
                                <?php if (! empty($a11y_checker_stelle['page_url'])) : ?>
                                    <a href="<?php echo esc_url($a11y_checker_stelle['page_url']); ?>"><?php echo esc_html($a11y_checker_stelle['page_url']); ?></a><br>
                                <?php endif; ?>

                                <?php // Ohne Selektor betrifft der Befund die Seite als Ganzes. ?>
                                <code><?php echo esc_html(empty($a11y_checker_stelle['selector'])
                                    ? __('the entire page', 'a11y-checker')
                                    : $a11y_checker_stelle['selector']); ?></code>

                                <?php if (! empty($a11y_checker_stelle['ui_state'])) : ?>
                                    <p class="description">
                                        <?php printf(
                                            /* translators: %s: the state in which the element becomes visible */
                                            esc_html__('Visible only after: %s', 'a11y-checker'),
                                            esc_html($a11y_checker_stelle['ui_state'])
                                        ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (! empty($a11y_checker_stelle['viewport'])) : ?>
                                    <p class="description"><?php echo esc_html($a11y_checker_stelle['viewport']); ?></p>
                                <?php endif; ?>

                                <?php if (! empty($a11y_checker_stelle['measurements'])) : ?>
                                    <p><?php echo esc_html(implode(' · ', (array) $a11y_checker_stelle['measurements'])); ?></p>
                                <?php endif; ?>

                                <?php // Der Hexwert steht als Text da; ein Farbfeld allein waere
                                      // hier der falsche Bedeutungstraeger (WCAG 1.4.1). ?>
                                <?php if (! empty($a11y_checker_stelle['colors'])) : ?>
                                    <p>
                                        <?php $a11y_checker_farbteile = [];
                                        foreach ((array) $a11y_checker_stelle['colors'] as $a11y_checker_bez => $a11y_checker_hex) {
                                            $a11y_checker_farbteile[] = $a11y_checker_bez.': '.$a11y_checker_hex;
                                        }
                                        echo esc_html(implode(' · ', $a11y_checker_farbteile)); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (! empty($a11y_checker_stelle['summary'])) : ?>
                                    <p class="description"><?php echo esc_html($a11y_checker_stelle['summary']); ?></p>
                                <?php endif; ?>

                                <?php if (! empty($a11y_checker_stelle['html_snippet'])) : ?>
                                    <pre style="white-space:pre-wrap;overflow-x:auto"><code><?php echo esc_html($a11y_checker_stelle['html_snippet']); ?></code></pre>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <?php
                    // Blaettern als gewoehnliche Verweise - kein Skript, keine
                    // Formularabsendung fuer einen reinen Lesevorgang.
                    $a11y_checker_ab = $fundstellen['offset'];
                    $a11y_checker_schritt = max(1, $fundstellen['limit']);
                    $a11y_checker_blaettern = static function (int $ab) use ($regel) {
                        return esc_url(add_query_arg(
                            array_filter([
                                'page' => 'a11y-checker',
                                'a11y_regel' => $regel,
                                'a11y_ab' => $ab > 0 ? $ab : null,
                            ]),
                            admin_url('tools.php')
                        )).'#a11y-fundstellen';
                    };
                    ?>
                    <p>
                        <?php if ($a11y_checker_ab > 0) : ?>
                            <a href="<?php echo $a11y_checker_blaettern(max(0, $a11y_checker_ab - $a11y_checker_schritt)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- in der Closure bereits mit esc_url() behandelt. ?>">
                                <?php esc_html_e('Previous occurrences', 'a11y-checker'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($a11y_checker_ab + count($fundstellen['items']) < $fundstellen['total']) : ?>
                            <a href="<?php echo $a11y_checker_blaettern($a11y_checker_ab + $a11y_checker_schritt); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- siehe oben. ?>">
                                <?php esc_html_e('More occurrences', 'a11y-checker'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (is_array($a11y_checker_lauf) && ! empty($a11y_checker_lauf['report_url'])) : ?>
                <?php // Screenshots werden bewusst nicht hierher geholt: sie in eine
                      // fremde Installation zu kopieren hiesse, sie dort ohne
                      // Aufbewahrungsfrist und ohne Zugriffspruefung liegen zu haben. ?>
                <p>
                    <?php esc_html_e('Screenshots of the occurrences are available in the full report on the service’s website. Opening it asks you to sign in first.', 'a11y-checker'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($a11y_checker_lauf['report_url']); ?>">
                        <?php esc_html_e('Open the full report with screenshots', 'a11y-checker'); ?>
                    </a>
                </p>
            <?php endif; ?>

            <h2><?php esc_html_e('Quota', 'a11y-checker'); ?></h2>

            <?php $a11y_checker_kontingent = $site['quota'] ?? null; ?>
            <?php if (! is_array($a11y_checker_kontingent)) : ?>
                <p><?php esc_html_e('The quota could not be retrieved.', 'a11y-checker'); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="max-width:40rem">
                    <caption class="screen-reader-text"><?php esc_html_e('Pages scanned in the current billing period', 'a11y-checker'); ?></caption>
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Pages scanned in this period', 'a11y-checker'); ?></th>
                            <td>
                                <?php // Eine hinterlegte null bedeutet unbegrenzt und darf nicht als 0 erscheinen. ?>
                                <?php if ($a11y_checker_kontingent['limit'] === null) : ?>
                                    <?php printf(
                                        /* translators: %s: number of pages scanned */
                                        esc_html__('%s (unlimited)', 'a11y-checker'),
                                        esc_html(number_format_i18n((int) $a11y_checker_kontingent['used']))
                                    ); ?>
                                <?php else : ?>
                                    <?php printf(
                                        /* translators: 1: pages used, 2: pages included */
                                        esc_html__('%1$s of %2$s', 'a11y-checker'),
                                        esc_html(number_format_i18n((int) $a11y_checker_kontingent['used'])),
                                        esc_html(number_format_i18n((int) $a11y_checker_kontingent['limit']))
                                    ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($a11y_checker_kontingent['limit'] !== null) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Remaining', 'a11y-checker'); ?></th>
                                <td><?php echo esc_html(number_format_i18n((int) $a11y_checker_kontingent['remaining'])); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($a11y_checker_kontingent['period_end'])) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Period ends', 'a11y-checker'); ?></th>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($a11y_checker_kontingent['period_end']))); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <h2><?php esc_html_e('Connection', 'a11y-checker'); ?></h2>
        <table class="widefat striped" style="max-width:40rem">
            <caption class="screen-reader-text"><?php esc_html_e('Stored connection', 'a11y-checker'); ?></caption>
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Service address', 'a11y-checker'); ?></th>
                    <td><code><?php echo esc_html($client->api_url()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Site ID', 'a11y-checker'); ?></th>
                    <td><code><?php echo esc_html($client->site_id()); ?></code></td>
                </tr>
            </tbody>
        </table>

        <?php // Das Token steht hier absichtlich nicht - auch nicht gekuerzt. ?>
        <p class="description" id="trennen_hinweis">
            <?php esc_html_e('Disconnecting removes the token, the site ID and the stored text of the statement from this installation; you can then enter the details again. Nothing changes in your account — the token stays valid and is revoked there.', 'a11y-checker'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('a11y_checker_disconnect'); ?>
            <input type="hidden" name="action" value="a11y_checker_disconnect">
            <?php submit_button(__('Disconnect', 'a11y-checker'), 'secondary', 'submit', false, ['aria-describedby' => 'trennen_hinweis']); ?>
        </form>

        <h2><?php esc_html_e('Embed the statement', 'a11y-checker'); ?></h2>
        <p><?php esc_html_e('Add this shortcode to the page that should carry your accessibility statement:', 'a11y-checker'); ?></p>
        <p><code>[barrierefreiheitserklaerung]</code></p>
        <p class="description">
            <?php esc_html_e('Options: teil="maengel" or teil="kontakt" for a single section, stand="nein" to omit the date line, ueberschrift="3" to fit your heading hierarchy.', 'a11y-checker'); ?>
        </p>

        <h2><?php esc_html_e('Data transfer', 'a11y-checker'); ?></h2>
        <p>
            <?php esc_html_e('This plugin sends the address of this site and the API token to the service configured above in order to start scans and retrieve the statement. No content belonging to your visitors is transmitted.', 'a11y-checker'); ?>
        </p>
    <?php endif; ?>
</div>

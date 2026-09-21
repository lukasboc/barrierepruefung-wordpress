<?php
/**
 * Übersicht des Wegs zur Erklärung.
 *
 * Dieselben Schritte, dieselbe Reihenfolge und dieselben Wörter wie im
 * Dienst - sie kommen von dort. Die hervorgehobene Schaltfläche nennt das
 * echte Ziel des nächsten offenen Schritts, nie ein bloßes „Weiter".
 *
 * Erwartet $weg, $lauf, $schritte, $rueckmeldung, $seiten, $client.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_zustaende = [
    'erledigt' => __('done', 'barrierepruefung-de-web-accessibility-checker'),
    'offen' => __('open', 'barrierepruefung-de-web-accessibility-checker'),
    // Kein Versäumnis: diese Angaben entstehen erst mit der Erklärung.
    'spaeter' => __('filled in before you publish', 'barrierepruefung-de-web-accessibility-checker'),
];
$barrierepruefung_dokument = (array) ($weg['document'] ?? []);
$barrierepruefung_veroeffentlicht = is_array($weg['published'] ?? null) ? $weg['published'] : null;
$barrierepruefung_naechster = null;

foreach ($schritte as $barrierepruefung_eintrag) {
    if (($barrierepruefung_eintrag['key'] ?? null) === ($weg['next'] ?? null)) {
        $barrierepruefung_naechster = $barrierepruefung_eintrag;
    }
}
?>
<?php include BARRIEREPRUEFUNG_PATH.'views/erklaerung-rueckmeldung.php'; ?>

<h2><?php esc_html_e('Your accessibility statement', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

<?php // Zuerst der Stand dessen, was schon öffentlich ist - das ist die Frage, mit der man herkommt. ?>
<?php if ($barrierepruefung_veroeffentlicht !== null) : ?>
    <div class="notice inline <?php echo empty($barrierepruefung_veroeffentlicht['outdated']) ? 'notice-success' : 'notice-warning'; ?>">
        <p>
            <?php printf(
                /* translators: 1: version number, 2: date */
                esc_html__('Version %1$s is published, as of %2$s.', 'barrierepruefung-de-web-accessibility-checker'),
                esc_html((string) ($barrierepruefung_veroeffentlicht['version'] ?? '')),
                esc_html(date_i18n(get_option('date_format'), strtotime((string) ($barrierepruefung_veroeffentlicht['published_at'] ?? 'now'))))
            ); ?>
            <?php if (! empty($barrierepruefung_veroeffentlicht['outdated'])) : ?>
                <strong><?php esc_html_e('It is out of date: a newer scan result is available. Please release a new version.', 'barrierepruefung-de-web-accessibility-checker'); ?></strong>
            <?php endif; ?>
        </p>
        <p>
            <a href="<?php echo esc_url((string) ($barrierepruefung_veroeffentlicht['public_url'] ?? '')); ?>" rel="external"><?php esc_html_e('View the published statement', 'barrierepruefung-de-web-accessibility-checker'); ?></a>
            ·
            <a href="<?php echo esc_url((string) ($barrierepruefung_veroeffentlicht['pdf_url'] ?? '')); ?>" rel="external"><?php esc_html_e('Download as PDF', 'barrierepruefung-de-web-accessibility-checker'); ?></a>
        </p>
    </div>
<?php endif; ?>

<?php if ($lauf === null) : ?>
    <p><?php esc_html_e('The statement is based on a finished scan. There is none for this site yet.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('tools.php?page=barrierepruefung')); ?>">
            <?php esc_html_e('Go to the scan', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    </p>
    <?php return; ?>
<?php endif; ?>

<?php if (! empty($barrierepruefung_dokument['title'])) : ?>
    <p>
        <strong><?php echo esc_html((string) $barrierepruefung_dokument['title']); ?></strong><br>
        <?php echo esc_html((string) ($barrierepruefung_dokument['explanation'] ?? '')); ?>
    </p>
<?php endif; ?>

<h3><?php esc_html_e('Steps', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>

<p>
    <?php printf(
        /* translators: 1: steps done, 2: total number of steps */
        esc_html__('%1$s of %2$s steps done.', 'barrierepruefung-de-web-accessibility-checker'),
        esc_html(number_format_i18n((int) ($weg['done'] ?? 0))),
        esc_html(number_format_i18n((int) ($weg['total'] ?? 0)))
    ); ?>
</p>

<?php // Eine geordnete Liste: die Reihenfolge ist Teil der Aussage. Der Zustand steht als Wort da (WCAG 1.4.1). ?>
<ol class="barrierepruefung-schritte">
    <?php foreach ($schritte as $barrierepruefung_eintrag) : ?>
        <?php $barrierepruefung_zustand = (string) ($barrierepruefung_eintrag['state'] ?? ''); ?>
        <li>
            <a href="<?php echo esc_url(Barrierepruefung_Erklaerung::url((string) ($barrierepruefung_eintrag['key'] ?? ''))); ?>">
                <?php echo esc_html((string) ($barrierepruefung_eintrag['title'] ?? '')); ?></a>
            —
            <?php echo esc_html($barrierepruefung_zustaende[$barrierepruefung_zustand] ?? $barrierepruefung_zustand); ?>
            <?php foreach ((array) ($barrierepruefung_eintrag['reasons'] ?? []) as $barrierepruefung_grund) : ?>
                <br><span class="description"><?php echo esc_html((string) ($barrierepruefung_grund['text'] ?? '')); ?></span>
            <?php endforeach; ?>
        </li>
    <?php endforeach; ?>
</ol>

<p>
    <?php if ($barrierepruefung_naechster !== null) : ?>
        <a class="button button-primary" href="<?php echo esc_url(Barrierepruefung_Erklaerung::url((string) $barrierepruefung_naechster['key'])); ?>">
            <?php printf(
                /* translators: %s: title of the next open step */
                esc_html__('Next: %s', 'barrierepruefung-de-web-accessibility-checker'),
                esc_html((string) ($barrierepruefung_naechster['title'] ?? ''))
            ); ?>
        </a>
    <?php elseif ($barrierepruefung_veroeffentlicht === null) : ?>
        <a class="button button-primary" href="<?php echo esc_url(Barrierepruefung_Erklaerung::url('freigabe')); ?>">
            <?php esc_html_e('Review and publish the statement', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    <?php elseif (! empty($barrierepruefung_veroeffentlicht['outdated'])) : ?>
        <a class="button button-primary" href="<?php echo esc_url(Barrierepruefung_Erklaerung::url('freigabe')); ?>">
            <?php esc_html_e('Release a new version', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    <?php else : ?>
        <?php // Die veröffentlichte Fassung ist aktuell. Eine hervorgehobene Freigabe lüde zu einer inhaltsgleichen neuen Version ein - der nächste Schritt ist jetzt das Einbinden weiter unten. ?>
        <a href="<?php echo esc_url(Barrierepruefung_Erklaerung::url('freigabe')); ?>">
            <?php esc_html_e('Review the draft again', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    <?php endif; ?>
</p>

<?php // Kein Schritt und kein Blocker, prägt aber den Text: bleibt im Dienst, weil es die Belegbilder braucht. ?>
<?php if (! empty($lauf['findings_url'])) : ?>
    <p class="description">
        <?php esc_html_e('Findings that are not a defect, that will not be fixed or that would be a disproportionate burden are assessed in the full report in the service, where the screenshots are. These assessments shape the text of the statement.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        <a href="<?php echo esc_url((string) $lauf['findings_url']); ?>" rel="external"><?php esc_html_e('Assess findings in the service', 'barrierepruefung-de-web-accessibility-checker'); ?></a>
    </p>
<?php endif; ?>

<?php // Das Ziel ist nicht „veröffentlicht", sondern „auf der eigenen Website zu finden". ?>
<?php if ($barrierepruefung_veroeffentlicht !== null) : ?>
    <h3><?php esc_html_e('On your website', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>

    <?php if ($seiten !== []) : ?>
        <p><?php esc_html_e('The statement is embedded on:', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
        <ul>
            <?php foreach ($seiten as $barrierepruefung_seite) : ?>
                <li>
                    <?php echo esc_html(get_the_title($barrierepruefung_seite->ID)); ?>
                    <?php if (($barrierepruefung_seite->post_status ?? '') !== 'publish') : ?>
                        (<?php esc_html_e('not published yet', 'barrierepruefung-de-web-accessibility-checker'); ?>)
                    <?php endif; ?>
                    —
                    <a href="<?php echo esc_url((string) get_permalink($barrierepruefung_seite->ID)); ?>"><?php esc_html_e('view', 'barrierepruefung-de-web-accessibility-checker'); ?></a>,
                    <a href="<?php echo esc_url((string) get_edit_post_link($barrierepruefung_seite->ID, 'raw')); ?>"><?php esc_html_e('edit', 'barrierepruefung-de-web-accessibility-checker'); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="description"><?php esc_html_e('The page shows each new version automatically. Link to it from every page of your website, usually in the footer.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <?php else : ?>
        <p><?php esc_html_e('No page of this website contains the statement yet. It has to be reachable from every page, usually through a link in the footer.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('barrierepruefung_seite_anlegen'); ?>
            <input type="hidden" name="action" value="barrierepruefung_seite_anlegen">
            <?php submit_button(__('Create a draft page with the statement', 'barrierepruefung-de-web-accessibility-checker'), 'secondary', 'submit', false); ?>
        </form>
        <p class="description"><?php esc_html_e('The page is created as a draft, so you can check it before it goes live. Alternatively, add the shortcode [barrierefreiheitserklaerung] or the block “Accessibility statement” to a page of your choice.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <?php endif; ?>
<?php endif; ?>

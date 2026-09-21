<?php
/**
 * Die Freigabe.
 *
 * Zeigt, was veröffentlicht wird, und sagt vorher, was dabei geschieht. Das
 * Formular trägt die Kennung genau dieses Entwurfs und einen beim Anzeigen
 * erzeugten Idempotency-Key: ein Doppelklick ergibt keine zweite Fassung, und
 * ein inzwischen geänderter Entwurf wird nicht still veröffentlicht.
 *
 * Erwartet $entwurf, $bereit, $schluessel, $weg, $rueckmeldung.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_person = wp_get_current_user();
$barrierepruefung_aussage = (array) ($entwurf['conformance'] ?? []);
$barrierepruefung_bezeichnungen = (array) ($barrierepruefung_aussage['labels'] ?? []);
$barrierepruefung_vorgeschlagen = (string) ($barrierepruefung_aussage['suggested'] ?? '');
$barrierepruefung_version = (int) ($entwurf['next_version'] ?? 1);
?>
<p>
    <a href="<?php echo esc_url(Barrierepruefung_Erklaerung::url()); ?>">
        <?php esc_html_e('← Back to the overview', 'barrierepruefung-de-web-accessibility-checker'); ?>
    </a>
</p>

<?php include BARRIEREPRUEFUNG_PATH.'views/erklaerung-rueckmeldung.php'; ?>

<h2><?php esc_html_e('Review and publish', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>

<?php if ($entwurf === null) : ?>
    <p><?php esc_html_e('The draft could not be retrieved from the service. Please try again in a moment.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <?php return; ?>
<?php endif; ?>

<?php // Keine Sackgasse: wer zu früh hier ist, sieht, was fehlt, und kommt dorthin. ?>
<?php if (! $bereit) : ?>
    <div class="notice inline notice-warning">
        <p><?php esc_html_e('Not all steps are done yet. You can look at the draft, but it can only be published once every step is done.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
        <p><a href="<?php echo esc_url(Barrierepruefung_Erklaerung::url()); ?>"><?php esc_html_e('See which steps are open', 'barrierepruefung-de-web-accessibility-checker'); ?></a></p>
    </div>
<?php endif; ?>

<h3 id="barrierepruefung-vorschau-titel"><?php esc_html_e('Draft', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>
<?php // Ein Scrollbereich muss per Tastatur erreichbar sein - deshalb tabindex und ein Name. ?>
<div class="card" role="region" tabindex="0" aria-labelledby="barrierepruefung-vorschau-titel"
     lang="<?php echo esc_attr((string) ($entwurf['locale'] ?? 'de')); ?>"
     style="max-width:none;max-height:32rem;overflow:auto">
    <?php echo wp_kses_post((new Barrierepruefung_Shortcode)->vorschau((string) ($entwurf['html'] ?? ''))); ?>
</div>

<h3><?php esc_html_e('Conformance statement', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>
<p>
    <?php printf(
        /* translators: %s: conformance status, e.g. "partially compliant" */
        esc_html__('Determined from the scan: %s.', 'barrierepruefung-de-web-accessibility-checker'),
        '<strong>'.esc_html((string) ($barrierepruefung_bezeichnungen[$barrierepruefung_vorgeschlagen] ?? $barrierepruefung_vorgeschlagen)).'</strong>'
    ); ?>
    <?php if (! empty($barrierepruefung_aussage['reason'])) : ?>
        <?php echo esc_html((string) $barrierepruefung_aussage['reason']); ?>
    <?php endif; ?>
</p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('barrierepruefung_freigeben'); ?>
    <input type="hidden" name="action" value="barrierepruefung_freigeben">
    <input type="hidden" name="draft_etag" value="<?php echo esc_attr((string) ($entwurf['draft_etag'] ?? '')); ?>">
    <input type="hidden" name="expected_version" value="<?php echo esc_attr((string) $barrierepruefung_version); ?>">
    <input type="hidden" name="idempotency_key" value="<?php echo esc_attr($schluessel); ?>">

    <?php // Selten gebraucht, deshalb zugeklappt. Der Haken darin zählt nur, wenn er gesetzt ist - auch zugeklappte Felder werden sonst mitgeschickt. ?>
    <details>
        <summary><?php esc_html_e('Deviate from the determined statement', 'barrierepruefung-de-web-accessibility-checker'); ?></summary>
        <p>
            <input type="checkbox" name="abweichen" value="1" id="barrierepruefung-abweichen">
            <label for="barrierepruefung-abweichen"><?php esc_html_e('I want to publish a different conformance statement', 'barrierepruefung-de-web-accessibility-checker'); ?></label>
        </p>
        <fieldset id="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('conformance_status')); ?>">
            <legend><?php esc_html_e('Conformance statement', 'barrierepruefung-de-web-accessibility-checker'); ?></legend>
            <?php foreach ((array) ($barrierepruefung_aussage['choices'] ?? []) as $barrierepruefung_wahl) : ?>
                <label style="display:block;margin:.25rem 0">
                    <input type="radio" name="conformance_status" value="<?php echo esc_attr((string) $barrierepruefung_wahl); ?>"
                           <?php checked((string) $barrierepruefung_wahl, $barrierepruefung_vorgeschlagen); ?>>
                    <?php echo esc_html((string) ($barrierepruefung_bezeichnungen[$barrierepruefung_wahl] ?? $barrierepruefung_wahl)); ?>
                </label>
            <?php endforeach; ?>
            <?php // Nur was wählbar ist, steht da: „vollständig konform" fehlt, solange etwas offen ist. ?>
        </fieldset>
        <p>
            <label for="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('override_reason')); ?>"><?php esc_html_e('Justification (required when deviating)', 'barrierepruefung-de-web-accessibility-checker'); ?></label><br>
            <textarea class="large-text" rows="3" name="override_reason" id="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('override_reason')); ?>"></textarea>
        </p>
    </details>

    <p>
        <label for="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('actor_name')); ?>"><strong><?php esc_html_e('Released by', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></label><br>
        <input type="text" class="regular-text" name="actor_name" id="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('actor_name')); ?>"
               value="<?php echo esc_attr((string) ($barrierepruefung_person->display_name ?? '')); ?>" aria-required="true"
               aria-describedby="barrierepruefung-freigabe-person-hinweis">
        <span class="description" id="barrierepruefung-freigabe-person-hinweis" style="display:block">
            <?php esc_html_e('Recorded in the version as the person who released it. Responsible in the service is the administrator who allowed this connection to publish.', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </span>
    </p>

    <h3><?php esc_html_e('What happens when you publish', 'barrierepruefung-de-web-accessibility-checker'); ?></h3>
    <ul class="ul-disc">
        <li><?php printf(
            /* translators: %s: version number */
            esc_html__('Version %s is recorded and cannot be changed afterwards. Corrections become a new version; the old one stays available as evidence.', 'barrierepruefung-de-web-accessibility-checker'),
            esc_html((string) $barrierepruefung_version)
        ); ?></li>
        <li><?php esc_html_e('The statement goes online at its public address in the service, together with a PDF.', 'barrierepruefung-de-web-accessibility-checker'); ?></li>
        <li><?php esc_html_e('Pages on this website that embed the statement show the new version right away.', 'barrierepruefung-de-web-accessibility-checker'); ?></li>
        <li><?php esc_html_e('If a later scan finds something different, the version is marked as out of date. It stays online.', 'barrierepruefung-de-web-accessibility-checker'); ?></li>
    </ul>

    <?php if ($bereit) : ?>
        <?php submit_button(
            sprintf(
                /* translators: %s: version number */
                __('Publish version %s now', 'barrierepruefung-de-web-accessibility-checker'),
                (string) $barrierepruefung_version
            ),
            'primary',
            'submit',
            false
        ); ?>
    <?php endif; ?>
</form>

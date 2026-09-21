<?php
/**
 * Schritt „Pflichtangaben der Erklärung".
 *
 * Welche Angaben gelten, sagt der Dienst je Dokumentart (`applies`); nur die
 * erscheinen. Die Beschriftungen sind die des Plugins, damit sie übersetzbar
 * sind - einen Schlüssel, den es nicht kennt, zeigt es trotzdem an.
 *
 * Erwartet $daten (der Entwurf), $werte, $fehlerliste.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_beschriftungen = [
    'contact' => [
        __('Contact for reporting barriers', 'barrierepruefung-de-web-accessibility-checker'),
        __('An e-mail address, a phone number or a form where people can report barriers they encounter on this website.', 'barrierepruefung-de-web-accessibility-checker'),
    ],
    'alternatives' => [
        __('Accessible alternatives', 'barrierepruefung-de-web-accessibility-checker'),
        __('How can people access content that is not yet accessible? For example a phone number, a document on request or a simpler version of the page.', 'barrierepruefung-de-web-accessibility-checker'),
    ],
    'dgs_ls_url' => [
        __('Explanations in sign language and easy-to-read language', 'barrierepruefung-de-web-accessibility-checker'),
        __('Address of the page with these explanations. They belong on your home page; the statement only links to them.', 'barrierepruefung-de-web-accessibility-checker'),
    ],
    'service_description' => [
        __('Description of the service', 'barrierepruefung-de-web-accessibility-checker'),
        __('What do you offer, and what do people need to know to use it? The law requires both: a general description and explanations of how the service is carried out.', 'barrierepruefung-de-web-accessibility-checker'),
    ],
    'enforcement_body_name' => [__('Name of the body', 'barrierepruefung-de-web-accessibility-checker'), ''],
    'enforcement_body_address' => [__('Address', 'barrierepruefung-de-web-accessibility-checker'), ''],
    'enforcement_body_url' => [__('Website', 'barrierepruefung-de-web-accessibility-checker'), ''],
];
$barrierepruefung_mehrzeilig = ['contact', 'alternatives', 'service_description'];

$barrierepruefung_felder = array_filter((array) ($daten['fields'] ?? []), static fn ($feld) => ! empty($feld['applies']));
$barrierepruefung_stelle = array_filter($barrierepruefung_felder, static fn ($feld) => str_starts_with((string) ($feld['key'] ?? ''), 'enforcement_body_'));
$barrierepruefung_uebrige = array_filter($barrierepruefung_felder, static fn ($feld) => ! str_starts_with((string) ($feld['key'] ?? ''), 'enforcement_body_'));

/** Ein Feld ausgeben - mit Beschriftung, Hinweis, Pflicht und Fehler. */
$barrierepruefung_feld = static function (array $feld) use ($barrierepruefung_beschriftungen, $barrierepruefung_mehrzeilig, $werte, $fehlerliste): void {
    $schluessel = (string) ($feld['key'] ?? '');
    $id = Barrierepruefung_Erklaerung::feld_id($schluessel);
    [$beschriftung, $hinweis] = $barrierepruefung_beschriftungen[$schluessel] ?? [$schluessel, ''];
    $wert = (string) ($werte[$schluessel] ?? $feld['value'] ?? '');
    $fehler = (array) ($fehlerliste[$schluessel] ?? []);
    $beschreibt = trim(($hinweis !== '' ? $id.'-hinweis ' : '').($fehler !== [] ? $id.'-fehler' : ''));
    $attribute = sprintf(
        'id="%1$s" name="angaben[%2$s]"%3$s%4$s%5$s%6$s',
        esc_attr($id),
        esc_attr($schluessel),
        ! empty($feld['required']) ? ' aria-required="true"' : '',
        ! empty($feld['max']) ? ' maxlength="'.esc_attr((string) (int) $feld['max']).'"' : '',
        $beschreibt !== '' ? ' aria-describedby="'.esc_attr($beschreibt).'"' : '',
        $fehler !== [] ? ' aria-invalid="true"' : ''
    );
    ?>
    <p>
        <label for="<?php echo esc_attr($id); ?>">
            <strong><?php echo esc_html($beschriftung); ?></strong>
            <?php if (! empty($feld['required'])) : ?>
                <?php esc_html_e('(required)', 'barrierepruefung-de-web-accessibility-checker'); ?>
            <?php endif; ?>
        </label><br>
        <?php if ($hinweis !== '') : ?>
            <span class="description" id="<?php echo esc_attr($id.'-hinweis'); ?>" style="display:block"><?php echo esc_html($hinweis); ?></span>
        <?php endif; ?>
        <?php if ($fehler !== []) : ?>
            <span id="<?php echo esc_attr($id.'-fehler'); ?>" style="display:block;color:#b32d2e"><strong><?php echo esc_html(implode(' ', array_map('strval', $fehler))); ?></strong></span>
        <?php endif; ?>
        <?php if (in_array($schluessel, $barrierepruefung_mehrzeilig, true)) : ?>
            <textarea class="large-text" rows="3" <?php echo $attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- oben Teil fuer Teil mit esc_attr() gebaut. ?>><?php echo esc_textarea($wert); ?></textarea>
        <?php else : ?>
            <input type="<?php echo ($feld['type'] ?? '') === 'url' ? 'url' : 'text'; ?>" class="regular-text" value="<?php echo esc_attr($wert); ?>" <?php echo $attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wie oben. ?>>
        <?php endif; ?>
    </p>
    <?php
};
?>
<p><?php esc_html_e('These details cannot be derived from a scan - only you know them. They appear in the statement.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('barrierepruefung_angaben'); ?>
    <input type="hidden" name="action" value="barrierepruefung_angaben">

    <?php foreach ($barrierepruefung_uebrige as $barrierepruefung_eintrag) : ?>
        <?php $barrierepruefung_feld((array) $barrierepruefung_eintrag); ?>
    <?php endforeach; ?>

    <?php if ($barrierepruefung_stelle !== []) : ?>
        <fieldset>
            <legend><strong><?php esc_html_e('Competent enforcement body', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></legend>
            <p class="description"><?php esc_html_e('Pre-filled for your jurisdiction. For regional authorities and municipalities, the body of the respective state applies - please check.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php foreach ($barrierepruefung_stelle as $barrierepruefung_eintrag) : ?>
                <?php $barrierepruefung_feld((array) $barrierepruefung_eintrag); ?>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>

    <?php submit_button(__('Save details', 'barrierepruefung-de-web-accessibility-checker')); ?>
</form>

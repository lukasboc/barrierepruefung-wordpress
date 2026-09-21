<?php
/**
 * Schritt „Wer erklärt".
 *
 * Die Arten kommen samt Erläuterung vom Dienst: von ihnen hängt ab, welches
 * Rechtsdokument entsteht.
 *
 * Erwartet $daten, $werte, $fehlerliste.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_name = (string) ($werte['name'] ?? $daten['name'] ?? '');
$barrierepruefung_art = (string) ($werte['applies_to'] ?? $daten['applies_to'] ?? '');
$barrierepruefung_name_id = Barrierepruefung_Erklaerung::feld_id('name');
$barrierepruefung_art_id = Barrierepruefung_Erklaerung::feld_id('applies_to');
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('barrierepruefung_stelle'); ?>
    <input type="hidden" name="action" value="barrierepruefung_stelle">

    <p>
        <label for="<?php echo esc_attr($barrierepruefung_name_id); ?>"><strong><?php esc_html_e('Name of the responsible body', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></label><br>
        <input type="text" class="regular-text" id="<?php echo esc_attr($barrierepruefung_name_id); ?>" name="name"
               value="<?php echo esc_attr($barrierepruefung_name); ?>"
               aria-describedby="<?php echo esc_attr($barrierepruefung_name_id.'-hinweis'); ?>"
               <?php echo isset($fehlerliste['name']) ? 'aria-invalid="true"' : ''; ?>>
        <span class="description" id="<?php echo esc_attr($barrierepruefung_name_id.'-hinweis'); ?>" style="display:block">
            <?php if (! empty($daten['inherited_name'])) : ?>
                <?php printf(
                    /* translators: %s: name of the organization in the service */
                    esc_html__('Leave empty to use “%s”. This name appears in the published statement.', 'barrierepruefung-de-web-accessibility-checker'),
                    esc_html((string) $daten['inherited_name'])
                ); ?>
            <?php else : ?>
                <?php esc_html_e('This name appears in the published statement.', 'barrierepruefung-de-web-accessibility-checker'); ?>
            <?php endif; ?>
        </span>
    </p>

    <fieldset id="<?php echo esc_attr($barrierepruefung_art_id); ?>" aria-describedby="<?php echo esc_attr($barrierepruefung_art_id.'-hinweis'); ?>">
        <legend><strong><?php esc_html_e('Type of body', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></legend>
        <p class="description" id="<?php echo esc_attr($barrierepruefung_art_id.'-hinweis'); ?>"><?php echo esc_html((string) ($daten['hint'] ?? '')); ?></p>
        <?php foreach ((array) ($daten['options'] ?? []) as $barrierepruefung_option) : ?>
            <?php $barrierepruefung_option_id = $barrierepruefung_art_id.'-'.sanitize_key((string) ($barrierepruefung_option['key'] ?? '')); ?>
            <p>
                <input type="radio" name="applies_to" id="<?php echo esc_attr($barrierepruefung_option_id); ?>"
                       value="<?php echo esc_attr((string) ($barrierepruefung_option['key'] ?? '')); ?>"
                       aria-describedby="<?php echo esc_attr($barrierepruefung_option_id.'-erlaeuterung'); ?>"
                       <?php checked($barrierepruefung_art, (string) ($barrierepruefung_option['key'] ?? '')); ?>>
                <label for="<?php echo esc_attr($barrierepruefung_option_id); ?>"><?php echo esc_html((string) ($barrierepruefung_option['label'] ?? '')); ?></label>
                <span class="description" id="<?php echo esc_attr($barrierepruefung_option_id.'-erlaeuterung'); ?>" style="display:block;margin-left:1.75rem">
                    <?php echo esc_html((string) ($barrierepruefung_option['description'] ?? '')); ?>
                </span>
            </p>
        <?php endforeach; ?>
    </fieldset>

    <?php submit_button(__('Save', 'barrierepruefung-de-web-accessibility-checker')); ?>
</form>

<?php
/**
 * Schritt „Wer erklärt".
 *
 * Die Arten kommen samt Erläuterung vom Dienst: von ihnen hängt ab, welches
 * Rechtsdokument entsteht. Dasselbe gilt für „Bund oder Land": welche Länder
 * zur Wahl stehen, sagt der Dienst - eine leere Liste heißt, dass der
 * Rechtsraum die Frage nicht kennt, und dann steht hier auch kein Feld.
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
$barrierepruefung_laender = (array) ($daten['subdivisions'] ?? []);
$barrierepruefung_land = (string) ($werte['subdivision'] ?? $daten['subdivision'] ?? '');
$barrierepruefung_land_id = Barrierepruefung_Erklaerung::feld_id('subdivision');
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

    <fieldset style="min-width:0" id="<?php echo esc_attr($barrierepruefung_art_id); ?>" aria-describedby="<?php echo esc_attr($barrierepruefung_art_id.'-hinweis'); ?>">
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

    <?php // Ohne Skript lässt sich das Feld nicht an die Art koppeln. Es steht deshalb immer da und sagt, für wen es gilt; für alle anderen verwirft der Dienst die Angabe. ?>
    <?php if ($barrierepruefung_laender !== []) : ?>
        <p>
            <label for="<?php echo esc_attr($barrierepruefung_land_id); ?>">
                <strong><?php esc_html_e('Federal or state level', 'barrierepruefung-de-web-accessibility-checker'); ?></strong>
                <?php if (! empty($daten['subdivision_required'])) : ?>
                    <?php esc_html_e('(required)', 'barrierepruefung-de-web-accessibility-checker'); ?>
                <?php endif; ?>
            </label><br>
            <select id="<?php echo esc_attr($barrierepruefung_land_id); ?>" name="subdivision"
                    aria-describedby="<?php echo esc_attr($barrierepruefung_land_id.'-hinweis'); ?>"
                    <?php echo ! empty($daten['subdivision_required']) ? 'aria-required="true"' : ''; ?>
                    <?php echo isset($fehlerliste['subdivision']) ? 'aria-invalid="true"' : ''; ?>>
                <option value=""><?php esc_html_e('— Please choose —', 'barrierepruefung-de-web-accessibility-checker'); ?></option>
                <?php foreach ($barrierepruefung_laender as $barrierepruefung_eintrag) : ?>
                    <option value="<?php echo esc_attr((string) ($barrierepruefung_eintrag['key'] ?? '')); ?>"<?php selected($barrierepruefung_land, (string) ($barrierepruefung_eintrag['key'] ?? '')); ?>><?php echo esc_html((string) ($barrierepruefung_eintrag['label'] ?? '')); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="description" id="<?php echo esc_attr($barrierepruefung_land_id.'-hinweis'); ?>" style="display:block">
                <?php esc_html_e('Only for public bodies: does the body belong to the federal level or to a state? The law the statement refers to and the competent enforcement body depend on it.', 'barrierepruefung-de-web-accessibility-checker'); ?>
            </span>
        </p>
    <?php endif; ?>

    <?php submit_button(__('Save', 'barrierepruefung-de-web-accessibility-checker')); ?>
</form>

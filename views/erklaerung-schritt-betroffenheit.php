<?php
/**
 * Schritt „Betroffenheits-Check".
 *
 * Fragen, Katalog und Fundstellen kommen fertig vom Dienst, in der Sprache
 * der Person vor dem Backend. Das Plugin stellt sie nur dar - eine eigene
 * Fassung dieser Fragen wäre eine zweite Stelle, an der eine Pflicht entsteht.
 *
 * Erwartet $daten, $werte, $fehlerliste.
 */
if (! defined('ABSPATH')) {
    exit;
}

if (empty($daten['asked'])) : ?>
    <p><?php esc_html_e('This question does not arise for this website.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <?php return;
endif;

$barrierepruefung_gewaehlt = is_array($werte['services'] ?? null)
    ? (array) $werte['services']
    : array_column(array_filter((array) ($daten['services']['options'] ?? []), static fn ($o) => ! empty($o['selected'])), 'key');
$barrierepruefung_klein = (bool) ($werte['micro_enterprise'] ?? $daten['micro_enterprise']['value'] ?? false);
$barrierepruefung_gruppe = Barrierepruefung_Erklaerung::feld_id('services');
?>
<p><?php echo esc_html((string) ($daten['description'] ?? '')); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('barrierepruefung_betroffenheit'); ?>
    <input type="hidden" name="action" value="barrierepruefung_betroffenheit">

    <fieldset id="<?php echo esc_attr($barrierepruefung_gruppe); ?>" aria-describedby="<?php echo esc_attr($barrierepruefung_gruppe.'-hinweis'); ?>">
        <legend><strong><?php echo esc_html((string) ($daten['services']['question'] ?? '')); ?></strong></legend>
        <p class="description" id="<?php echo esc_attr($barrierepruefung_gruppe.'-hinweis'); ?>"><?php echo esc_html((string) ($daten['services']['hint'] ?? '')); ?></p>

        <?php foreach ((array) ($daten['services']['options'] ?? []) as $barrierepruefung_option) : ?>
            <?php
            $barrierepruefung_schluessel = (string) ($barrierepruefung_option['key'] ?? '');
            $barrierepruefung_option_id = $barrierepruefung_gruppe.'-'.sanitize_key($barrierepruefung_schluessel);
            ?>
            <p>
                <input type="checkbox" name="services[]" id="<?php echo esc_attr($barrierepruefung_option_id); ?>"
                       value="<?php echo esc_attr($barrierepruefung_schluessel); ?>"
                       aria-describedby="<?php echo esc_attr($barrierepruefung_option_id.'-fundstelle'); ?>"
                       <?php checked(in_array($barrierepruefung_schluessel, $barrierepruefung_gewaehlt, true)); ?>>
                <label for="<?php echo esc_attr($barrierepruefung_option_id); ?>"><?php echo esc_html((string) ($barrierepruefung_option['label'] ?? '')); ?></label>
                <span class="description" id="<?php echo esc_attr($barrierepruefung_option_id.'-fundstelle'); ?>" style="display:block;margin-left:1.75rem">
                    <?php echo esc_html((string) ($barrierepruefung_option['reference'] ?? '')); ?>
                </span>
            </p>
        <?php endforeach; ?>
    </fieldset>

    <?php // Ohne Skript lässt sich die Frage nicht erst nach einem Haken oben einblenden. Sie steht deshalb da und sagt, wann sie zählt. ?>
    <fieldset id="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('micro_enterprise')); ?>">
        <legend><strong><?php esc_html_e('Only if you ticked a service above:', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></legend>
        <p>
            <input type="checkbox" name="micro_enterprise" value="1" id="barrierepruefung-kleinstunternehmen"
                   aria-describedby="barrierepruefung-kleinstunternehmen-hinweis"
                   <?php checked($barrierepruefung_klein); ?>>
            <label for="barrierepruefung-kleinstunternehmen"><?php echo esc_html((string) ($daten['micro_enterprise']['question'] ?? '')); ?></label>
            <span class="description" id="barrierepruefung-kleinstunternehmen-hinweis" style="display:block;margin-left:1.75rem">
                <?php echo esc_html((string) ($daten['micro_enterprise']['hint'] ?? '')); ?>
            </span>
        </p>
    </fieldset>

    <div class="notice inline notice-info">
        <p><strong><?php esc_html_e('Current result', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></p>
        <p><?php echo esc_html((string) ($daten['consequence'] ?? '')); ?></p>
        <?php if (! empty($daten['answered_at'])) : ?>
            <p class="description">
                <?php printf(
                    /* translators: %s: date */
                    esc_html__('Answered on %s.', 'barrierepruefung-de-web-accessibility-checker'),
                    esc_html(date_i18n(get_option('date_format'), strtotime((string) $daten['answered_at'])))
                ); ?>
            </p>
        <?php endif; ?>
    </div>

    <p class="description"><?php echo esc_html((string) ($daten['disclaimer'] ?? '')); ?></p>

    <?php submit_button(__('Save answer', 'barrierepruefung-de-web-accessibility-checker')); ?>
</form>

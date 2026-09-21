<?php
/**
 * Schritt „Prüfschritte beantworten".
 *
 * Alle auf einer Seite, offene zuerst - die Reihenfolge kommt vom Dienst. Ohne
 * Skript gibt es keinen Klick je Antwort; deshalb ein Formular für alle mit
 * einem Speichern-Knopf oben und unten. Der Dienst speichert alle oder keine.
 *
 * Erwartet $daten (Liste der Prüfschritte), $meta, $werte, $lauf.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_antworten = (array) ($meta['answers'] ?? []);

// Nach einem Fehler gelten die zuletzt eingegebenen Werte, nicht die gespeicherten.
$barrierepruefung_eingaben = [];
foreach ((array) ($werte['checks'] ?? []) as $barrierepruefung_eingabe) {
    $barrierepruefung_eingaben[(int) ($barrierepruefung_eingabe['id'] ?? 0)] = $barrierepruefung_eingabe;
}
?>
<p><?php esc_html_e('No machine can fully assess these requirements. Go through them once; your answers flow into the statement.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id('checks')); ?>">
    <?php wp_nonce_field('barrierepruefung_pruefschritte'); ?>
    <input type="hidden" name="action" value="barrierepruefung_pruefschritte">
    <input type="hidden" name="scan" value="<?php echo esc_attr((string) ($lauf['id'] ?? '')); ?>">

    <?php submit_button(__('Save answers', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit-oben'); ?>

    <?php foreach ((array) $daten as $barrierepruefung_check) : ?>
        <?php
        $barrierepruefung_id = (int) ($barrierepruefung_check['id'] ?? 0);
        $barrierepruefung_praefix = 'barrierepruefung-check-'.$barrierepruefung_id;
        $barrierepruefung_antwort = (string) ($barrierepruefung_eingaben[$barrierepruefung_id]['answer'] ?? $barrierepruefung_check['answer'] ?? 'unanswered');
        $barrierepruefung_notiz = (string) ($barrierepruefung_eingaben[$barrierepruefung_id]['note'] ?? $barrierepruefung_check['note'] ?? '');
        ?>
        <div class="card" style="max-width:none">
            <h3 id="<?php echo esc_attr($barrierepruefung_praefix.'-titel'); ?>"><?php echo esc_html((string) ($barrierepruefung_check['title'] ?? '')); ?></h3>

            <?php if (! empty($barrierepruefung_check['success_criteria'])) : ?>
                <p class="description">
                    <?php printf(
                        /* translators: %s: success criterion numbers, e.g. 2.4.3 */
                        esc_html__('Success criterion %s', 'barrierepruefung-de-web-accessibility-checker'),
                        esc_html(implode(', ', (array) $barrierepruefung_check['success_criteria']))
                    ); ?>
                </p>
            <?php endif; ?>

            <?php if (! empty($barrierepruefung_check['description'])) : ?>
                <p><?php echo esc_html((string) $barrierepruefung_check['description']); ?></p>
            <?php endif; ?>

            <?php // Was die Prüfung schon beobachtet hat - fertig formuliert vom Dienst. ?>
            <?php if (! empty($barrierepruefung_check['evidence'])) : ?>
                <p class="description"><?php echo esc_html((string) $barrierepruefung_check['evidence']); ?></p>
            <?php endif; ?>

            <?php if (! empty($barrierepruefung_check['stale'])) : ?>
                <p><strong><?php esc_html_e('The page template has changed since your last answer. Please check again.', 'barrierepruefung-de-web-accessibility-checker'); ?></strong></p>
            <?php elseif (! empty($barrierepruefung_check['inherited'])) : ?>
                <p class="description"><?php esc_html_e('Taken over from the previous scan.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
            <?php endif; ?>

            <fieldset style="min-width:0">
                <legend><?php esc_html_e('Is this requirement met?', 'barrierepruefung-de-web-accessibility-checker'); ?></legend>
                <?php foreach (['passed', 'failed', 'not_applicable', 'unanswered'] as $barrierepruefung_wert) : ?>
                    <label style="display:block;margin:.25rem 0">
                        <input type="radio"
                               name="checks[<?php echo esc_attr((string) $barrierepruefung_id); ?>][answer]"
                               value="<?php echo esc_attr($barrierepruefung_wert); ?>"
                               <?php checked($barrierepruefung_antwort, $barrierepruefung_wert); ?>>
                        <?php echo esc_html((string) ($barrierepruefung_antworten[$barrierepruefung_wert] ?? $barrierepruefung_wert)); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <p>
                <label for="<?php echo esc_attr($barrierepruefung_praefix.'-notiz'); ?>">
                    <?php echo esc_html((string) ($meta['note_hint'] ?? __('Note', 'barrierepruefung-de-web-accessibility-checker'))); ?>
                </label><br>
                <textarea id="<?php echo esc_attr($barrierepruefung_praefix.'-notiz'); ?>"
                          name="checks[<?php echo esc_attr((string) $barrierepruefung_id); ?>][note]"
                          rows="2" class="large-text"><?php echo esc_textarea($barrierepruefung_notiz); ?></textarea>
            </p>
        </div>
    <?php endforeach; ?>

    <?php submit_button(__('Save answers', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit-unten'); ?>
</form>

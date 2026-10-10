<?php
/**
 * Ein Schritt auf dem Weg zur Erklärung - der gemeinsame Rahmen.
 *
 * Oben der Weg zurück, dann der Titel, der Zustand als Wort und die Gründe,
 * die der Dienst nennt. Darunter der eigentliche Bildschirm.
 *
 * Erwartet $aktueller, $ansicht, $daten, $werte, $fehlerliste, $rueckmeldung,
 * $lauf, $client und je nach Schritt $meta.
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_zustaende = [
    'erledigt' => __('done', 'barrierepruefung-de-web-accessibility-checker'),
    'offen' => __('open', 'barrierepruefung-de-web-accessibility-checker'),
    'spaeter' => __('filled in before you publish', 'barrierepruefung-de-web-accessibility-checker'),
];
$barrierepruefung_zustand = (string) ($aktueller['state'] ?? '');
?>
<p>
    <a href="<?php echo esc_url(Barrierepruefung_Erklaerung::url()); ?>">
        <?php esc_html_e('← Back to the overview', 'barrierepruefung-de-web-accessibility-checker'); ?>
    </a>
</p>

<?php include BARRIEREPRUEFUNG_PATH.'views/erklaerung-rueckmeldung.php'; ?>

<h2><?php echo esc_html((string) ($aktueller['title'] ?? '')); ?></h2>

<p>
    <?php esc_html_e('Status:', 'barrierepruefung-de-web-accessibility-checker'); ?>
    <strong><?php echo esc_html($barrierepruefung_zustaende[$barrierepruefung_zustand] ?? $barrierepruefung_zustand); ?></strong>
</p>

<?php if (! empty($aktueller['reasons'])) : ?>
    <ul>
        <?php foreach ((array) $aktueller['reasons'] as $barrierepruefung_grund) : ?>
            <li><?php echo esc_html((string) ($barrierepruefung_grund['text'] ?? '')); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($ansicht !== 'unbekannt' && in_array($ansicht, ['pruefschritte', 'verantwortliche_stelle', 'betroffenheit', 'pflichtangaben'], true) && $daten === null) : ?>
    <p><?php esc_html_e('This step could not be retrieved from the service. Please try again in a moment.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
<?php else : ?>
    <?php include BARRIEREPRUEFUNG_PATH.'views/erklaerung-schritt-'.$ansicht.'.php'; ?>
<?php endif; ?>

<?php // Der Dienst kann einem bekannten Schritt einen Grund geben, den diese Fassung nicht beheben kann - wie „land", bevor es das Feld dazu gab. Solange der Schritt offen ist, steht deshalb auch hier der Weg in den Dienst. ?>
<?php if ($ansicht !== 'unbekannt' && $barrierepruefung_zustand === 'offen' && ! empty($aktueller['web_url'])) : ?>
    <p class="description">
        <a href="<?php echo esc_url((string) $aktueller['web_url']); ?>" rel="external">
            <?php esc_html_e('Complete this step in the service', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    </p>
<?php endif; ?>

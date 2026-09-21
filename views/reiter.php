<?php
/**
 * Die Reiter der Verwaltungsseite.
 *
 * Links mit aria-current, kein role="tablist": es sind Seitenwechsel, keine
 * Bereiche, die auf derselben Seite umgeschaltet werden - dieselbe Regel wie
 * im Dienst. Die WordPress-Klassen sorgen dafür, dass sie aussehen wie überall
 * im Backend.
 *
 * Erwartet $barrierepruefung_reiter ('pruefung' oder 'erklaerung').
 */
if (! defined('ABSPATH')) {
    exit;
}

$barrierepruefung_reiter_liste = [
    'pruefung' => [__('Scan', 'barrierepruefung-de-web-accessibility-checker'), admin_url('tools.php?page=barrierepruefung')],
    'erklaerung' => [__('Accessibility statement', 'barrierepruefung-de-web-accessibility-checker'), Barrierepruefung_Erklaerung::url()],
];
?>
<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Sections', 'barrierepruefung-de-web-accessibility-checker'); ?>">
    <?php foreach ($barrierepruefung_reiter_liste as $barrierepruefung_schluessel => [$barrierepruefung_titel, $barrierepruefung_ziel]) : ?>
        <a href="<?php echo esc_url($barrierepruefung_ziel); ?>"
           class="nav-tab<?php echo $barrierepruefung_schluessel === $barrierepruefung_reiter ? ' nav-tab-active' : ''; ?>"
           <?php echo $barrierepruefung_schluessel === $barrierepruefung_reiter ? 'aria-current="page"' : ''; ?>>
            <?php echo esc_html($barrierepruefung_titel); ?>
        </a>
    <?php endforeach; ?>
</nav>

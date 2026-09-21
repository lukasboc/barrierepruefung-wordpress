<?php
/**
 * Der Reiter „Erklärung" - Rahmen um Barrierepruefung_Erklaerung::render().
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Accessibility', 'barrierepruefung-de-web-accessibility-checker'); ?></h1>

    <?php include BARRIEREPRUEFUNG_PATH.'views/statusmeldung.php'; ?>

    <?php $barrierepruefung_reiter = 'erklaerung'; include BARRIEREPRUEFUNG_PATH.'views/reiter.php'; ?>

    <?php (new Barrierepruefung_Erklaerung)->render($client); ?>
</div>

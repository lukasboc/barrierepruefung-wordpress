<?php
/**
 * Ein Schritt, den diese Fassung des Plugins nicht kennt.
 *
 * Der Dienst hat ihn eingeführt, nachdem dieses Plugin erschienen ist. Er
 * fehlt deshalb nicht, sondern führt dorthin, wo er sich erledigen lässt.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<p><?php esc_html_e('This step can only be completed in the service for now.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
<?php if (! empty($aktueller['web_url'])) : ?>
    <p>
        <a class="button button-primary" href="<?php echo esc_url((string) $aktueller['web_url']); ?>" rel="external">
            <?php esc_html_e('Complete this step in the service', 'barrierepruefung-de-web-accessibility-checker'); ?>
        </a>
    </p>
<?php endif; ?>

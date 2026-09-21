<?php
/**
 * Schritt „Prüfung abgeschlossen" - kein Formular, nur der Weg zur Prüfung.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if (($aktueller['state'] ?? '') === 'erledigt') : ?>
    <p><?php esc_html_e('The scan the statement is based on has finished.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
<?php else : ?>
    <p><?php esc_html_e('A new scan is needed before the statement can be published.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('barrierepruefung_scan'); ?>
        <input type="hidden" name="action" value="barrierepruefung_scan">
        <input type="hidden" name="barrierepruefung_schritt" value="pruefung">
        <?php submit_button(__('Scan now', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit', false); ?>
    </form>
<?php endif; ?>
<?php if (! empty($lauf['report_url'])) : ?>
    <p><a href="<?php echo esc_url((string) $lauf['report_url']); ?>" rel="external"><?php esc_html_e('Open the full report', 'barrierepruefung-de-web-accessibility-checker'); ?></a></p>
<?php endif; ?>

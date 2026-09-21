<?php
/**
 * Schritt „Domain bestätigen" - dieselbe Bestätigung wie im Reiter „Prüfung".
 *
 * Das Plugin liefert den Nachweis selbst aus; ein Klick genügt.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if (($aktueller['state'] ?? '') === 'erledigt') : ?>
    <p><?php esc_html_e('The domain is verified.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
<?php else : ?>
    <p><?php esc_html_e('The plugin serves the proof itself, so you need no access to DNS.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('barrierepruefung_verify'); ?>
        <input type="hidden" name="action" value="barrierepruefung_verify">
        <input type="hidden" name="barrierepruefung_schritt" value="domain">
        <?php submit_button(__('Verify domain now', 'barrierepruefung-de-web-accessibility-checker'), 'primary', 'submit', false); ?>
    </form>
<?php endif; ?>

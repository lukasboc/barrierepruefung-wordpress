<?php
/**
 * Der Weg zur Erklärung lässt sich nicht abrufen.
 *
 * Zwei Fälle, beide ausgesprochen: Der Dienst kennt diesen Weg noch nicht -
 * eine eigene Instanz in älterer Fassung -, oder er antwortet gerade nicht.
 *
 * Erwartet $nicht_unterstuetzt, $fehler, $client.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($nicht_unterstuetzt) : ?>
    <p><?php esc_html_e('The service this site is connected to does not support preparing and publishing the statement from WordPress yet. You can do this in your account there.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
<?php else : ?>
    <p><?php esc_html_e('The steps to your accessibility statement could not be retrieved.', 'barrierepruefung-de-web-accessibility-checker'); ?></p>
    <?php if (! empty($fehler)) : ?>
        <p class="description"><em><?php echo esc_html((string) $fehler); ?></em></p>
    <?php endif; ?>
<?php endif; ?>
<p>
    <a href="<?php echo esc_url($client->account_url()); ?>" rel="external">
        <?php esc_html_e('Open your account in the service', 'barrierepruefung-de-web-accessibility-checker'); ?>
    </a>
</p>

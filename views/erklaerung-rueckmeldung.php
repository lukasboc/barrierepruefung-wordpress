<?php
/**
 * Was nach der letzten Aktion zu sagen ist - Erfolg oder Fehler.
 *
 * Ein Fehler steht als Liste mit Sprungmarken zu den Feldern ganz oben:
 * ohne Skript lässt sich der Fokus nicht dorthin setzen, also steht er dort,
 * wo man als Erstes liest. Der Dokumenttitel beginnt dann mit „Fehler:"
 * (Barrierepruefung_Erklaerung::titel()).
 *
 * Erwartet $rueckmeldung (array, darf leer sein).
 */
if (! defined('ABSPATH')) {
    exit;
}

if (empty($rueckmeldung['art'])) {
    return;
}

$barrierepruefung_fehler = (array) ($rueckmeldung['fehler'] ?? []);
$barrierepruefung_gruende = (array) ($rueckmeldung['gruende'] ?? []);
?>
<?php if ($rueckmeldung['art'] === 'fehler') : ?>
    <div class="notice notice-error barrierepruefung-fehlerliste" role="alert">
        <h2><?php esc_html_e('There is a problem', 'barrierepruefung-de-web-accessibility-checker'); ?></h2>
        <?php // Gibt es Meldungen je Feld, sagen sie alles - der Sammeltext wiederholte sie nur. ?>
        <?php if ($barrierepruefung_fehler === []) : ?>
            <p><?php echo esc_html((string) ($rueckmeldung['text'] ?? '')); ?></p>
        <?php endif; ?>

        <?php if ($barrierepruefung_fehler !== []) : ?>
            <ul>
                <?php foreach ($barrierepruefung_fehler as $barrierepruefung_schluessel => $barrierepruefung_meldungen) : ?>
                    <?php foreach ((array) $barrierepruefung_meldungen as $barrierepruefung_meldung_text) : ?>
                        <li>
                            <a href="#<?php echo esc_attr(Barrierepruefung_Erklaerung::feld_id((string) $barrierepruefung_schluessel)); ?>">
                                <?php echo esc_html((string) $barrierepruefung_meldung_text); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($barrierepruefung_gruende !== []) : ?>
            <ul>
                <?php foreach ($barrierepruefung_gruende as $barrierepruefung_grund) : ?>
                    <li><?php echo esc_html((string) ($barrierepruefung_grund['text'] ?? '')); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php // Wo sich das beheben lässt - etwa ein Recht, das nur im Dienst erteilt wird. ?>
        <?php if (! empty($rueckmeldung['url'])) : ?>
            <p>
                <a href="<?php echo esc_url((string) $rueckmeldung['url']); ?>" rel="external">
                    <?php esc_html_e('Fix this in the service', 'barrierepruefung-de-web-accessibility-checker'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="notice notice-success" role="status">
        <p><?php echo esc_html((string) ($rueckmeldung['text'] ?? '')); ?></p>

        <?php if (! empty($rueckmeldung['veroeffentlicht']['public_url'])) : ?>
            <p>
                <a href="<?php echo esc_url((string) $rueckmeldung['veroeffentlicht']['public_url']); ?>" rel="external">
                    <?php esc_html_e('View the published statement', 'barrierepruefung-de-web-accessibility-checker'); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if (! empty($rueckmeldung['seite'])) : ?>
            <p>
                <a href="<?php echo esc_url((string) get_edit_post_link((int) $rueckmeldung['seite'], 'raw')); ?>">
                    <?php esc_html_e('Edit the new page', 'barrierepruefung-de-web-accessibility-checker'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

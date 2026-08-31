<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Bindet den Text der Erklärung zur Barrierefreiheit ein.
 *
 * Serverseitig gerendert, nicht per JavaScript nachgeladen: sonst wäre der
 * Text für manche assistiven Technologien und ohne JavaScript gar nicht
 * vorhanden. Ein Barrierefreiheitsprodukt darf hier keinen Kompromiss machen
 * (docs/08).
 */
class A11y_Checker_Shortcode
{
    private const CACHE_KEY = 'a11y_checker_declaration';

    private const FALLBACK_KEY = 'a11y_checker_declaration_fallback';

    public function register(): void
    {
        add_shortcode('barrierefreiheitserklaerung', [$this, 'render']);
        add_action('init', [$this, 'register_block']);
    }

    /** @param array<string, string>|string $attribute */
    public function render($attribute = []): string
    {
        $attribute = shortcode_atts([
            'sprache' => '',
            'stand' => 'ja',
            'teil' => 'komplett',
            'ueberschrift' => '2',
        ], (array) $attribute, 'barrierefreiheitserklaerung');

        $erklaerung = $this->fetch();

        if ($erklaerung === null) {
            // Eine Erklärung, die wegen eines Serverausfalls von der Website
            // verschwindet, wäre ein Rechtsproblem für die Kundin (docs/08).
            return $this->hinweis(__('Die Erklärung zur Barrierefreiheit kann gerade nicht geladen werden.', 'a11y-checker'));
        }

        $html = (string) ($erklaerung['html'] ?? '');

        if ($attribute['teil'] === 'maengel') {
            $html = $this->abschnitt($html, ['Nicht barrierefreie Inhalte', 'Bekannte Einschränkungen']);
        } elseif ($attribute['teil'] === 'kontakt') {
            $html = $this->abschnitt($html, ['Feedback und Kontaktangaben', 'Kontakt bei Barrieren']);
        }

        $html = $this->ueberschriften_verschieben($html, (int) $attribute['ueberschrift']);

        if ($attribute['stand'] === 'ja' && ! empty($erklaerung['published_at'])) {
            $html .= sprintf(
                '<p class="a11y-checker-stand">%s</p>',
                esc_html(sprintf(
                    /* translators: 1: Versionsnummer, 2: Datum */
                    __('Version %1$s, Stand %2$s', 'a11y-checker'),
                    (string) ($erklaerung['version'] ?? '—'),
                    date_i18n(get_option('date_format'), strtotime((string) $erklaerung['published_at']))
                ))
            );
        }

        return sprintf(
            '<div class="a11y-checker-erklaerung" lang="%s">%s</div>',
            esc_attr((string) ($erklaerung['locale'] ?? 'de')),
            wp_kses_post($html)
        );
    }

    public function register_block(): void
    {
        if (! function_exists('register_block_type')) {
            return;
        }

        // Serverseitig gerendert - derselbe Weg wie der Shortcode, damit es
        // nur eine Ausgabe gibt, die gepflegt werden muss.
        register_block_type('a11y-checker/erklaerung', [
            'api_version' => 3,
            'title' => __('Erklärung zur Barrierefreiheit', 'a11y-checker'),
            'category' => 'widgets',
            'attributes' => [
                'teil' => ['type' => 'string', 'default' => 'komplett'],
                'stand' => ['type' => 'string', 'default' => 'ja'],
                'ueberschrift' => ['type' => 'string', 'default' => '2'],
            ],
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * Holt die Erklärung, mit Zwischenspeicher und Rückfallebene.
     *
     * @return array<string, mixed>|null
     */
    private function fetch(): ?array
    {
        $zwischengespeichert = get_transient(self::CACHE_KEY);

        if (is_array($zwischengespeichert)) {
            return $zwischengespeichert;
        }

        $client = new A11y_Checker_Client;
        $antwort = $client->get('/sites/'.$client->site_id().'/declaration');

        if (! $antwort['ok'] || empty($antwort['data']['declaration'])) {
            // Zuletzt erfolgreich geholte Fassung ausliefern - mit ihrem
            // ursprünglichen Stand-Datum.
            $rueckfall = get_option(self::FALLBACK_KEY, null);

            return is_array($rueckfall) ? $rueckfall : null;
        }

        $erklaerung = $antwort['data']['declaration'];

        set_transient(self::CACHE_KEY, $erklaerung, HOUR_IN_SECONDS);
        update_option(self::FALLBACK_KEY, $erklaerung, false);

        return $erklaerung;
    }

    /** Schneidet einen Abschnitt anhand seiner Überschrift heraus. */
    private function abschnitt(string $html, array $titel): string
    {
        foreach ($titel as $suche) {
            $muster = '#<h2[^>]*>\s*'.preg_quote($suche, '#').'.*?(?=<h2|$)#is';

            if (preg_match($muster, $html, $treffer)) {
                return $treffer[0];
            }
        }

        return $html;
    }

    /**
     * Verschiebt die Überschriftenebenen.
     *
     * Damit fügt sich der Text korrekt in die Hierarchie der Seite ein - eine
     * h1 mitten im Inhalt wäre selbst ein Verstoß (WCAG 1.3.1).
     */
    private function ueberschriften_verschieben(string $html, int $start): string
    {
        $start = max(2, min(4, $start));
        $verschiebung = $start - 2;

        if ($verschiebung === 0) {
            return $html;
        }

        return preg_replace_callback('#<(/?)h([1-6])([^>]*)>#i',
            static function (array $treffer) use ($verschiebung): string {
                $ebene = min(6, (int) $treffer[2] + $verschiebung);

                return '<'.$treffer[1].'h'.$ebene.$treffer[3].'>';
            }, $html) ?? $html;
    }

    private function hinweis(string $text): string
    {
        return '<p class="a11y-checker-hinweis">'.esc_html($text).'</p>';
    }
}

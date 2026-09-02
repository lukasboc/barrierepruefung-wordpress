<?php

use PHPUnit\Framework\TestCase;

/**
 * Prüft die Logik des Shortcodes ohne WordPress.
 *
 * Getestet wird, was tatsächlich etwas berechnet: das Verschieben der
 * Überschriftenebenen und das Herausschneiden einzelner Abschnitte. Beides ist
 * fehleranfällig und für die Barrierefreiheit der einbettenden Seite
 * entscheidend.
 */
final class ShortcodeTest extends TestCase
{
    private A11y_Checker_Shortcode $shortcode;

    protected function setUp(): void
    {
        $this->shortcode = new A11y_Checker_Shortcode;

        // Globale überleben den einzelnen Test - also vor jedem zurücksetzen.
        unset($GLOBALS['wp_erklaerung']);
    }

    private function aufrufen(string $methode, mixed ...$argumente): mixed
    {
        $reflexion = new ReflectionMethod(A11y_Checker_Shortcode::class, $methode);
        $reflexion->setAccessible(true);

        return $reflexion->invoke($this->shortcode, ...$argumente);
    }

    /**
     * Eine h1 mitten im Inhalt wäre selbst ein Verstoß (WCAG 1.3.1) - deshalb
     * muss sich der eingebettete Text in die Hierarchie der Seite einfügen.
     */
    public function test_verschiebt_die_ueberschriftenebenen(): void
    {
        $verschoben = $this->aufrufen(
            'ueberschriften_verschieben',
            '<h1>Erklärung</h1><h2>Stand</h2><p>Text</p><h3>Details</h3>',
            3
        );

        $this->assertStringContainsString('<h3>Erklärung</h3>', $verschoben);
        $this->assertStringContainsString('<h4>Stand</h4>', $verschoben);
        $this->assertStringContainsString('<h5>Details</h5>', $verschoben);
        $this->assertStringContainsString('<p>Text</p>', $verschoben);
    }

    /**
     * Der Regelfall: Die Erklärung steht unter der h1 der Seite. Ihre eigene h1
     * muss zur h2 werden - bliebe sie stehen, hätte die Seite zwei erste
     * Überschriften.
     */
    public function test_macht_bei_der_voreinstellung_aus_der_h1_eine_h2(): void
    {
        $this->assertSame(
            '<h2>Erklärung</h2><h3>Stand</h3>',
            $this->aufrufen('ueberschriften_verschieben', '<h1>Erklärung</h1><h2>Stand</h2>', 2)
        );
    }

    /**
     * Bei einem herausgeschnittenen Abschnitt ist die oberste Überschrift eine
     * h2. Gemessen wird an ihr, nicht an einer h1, die gar nicht ausgegeben
     * wird - sonst käme der Abschnitt eine Ebene zu tief heraus.
     */
    public function test_misst_an_der_obersten_ueberschrift_des_ausschnitts(): void
    {
        $html = '<h2>Nicht barrierefreie Inhalte</h2><h3>Nichtvereinbarkeit</h3>';

        $this->assertSame($html, $this->aufrufen('ueberschriften_verschieben', $html, 2));
        $this->assertSame(
            '<h3>Nicht barrierefreie Inhalte</h3><h4>Nichtvereinbarkeit</h4>',
            $this->aufrufen('ueberschriften_verschieben', $html, 3)
        );
    }

    public function test_geht_nicht_ueber_h6_hinaus(): void
    {
        $verschoben = $this->aufrufen(
            'ueberschriften_verschieben',
            '<h1>Oben</h1><h5>Tief</h5><h6>Tiefer</h6>',
            4
        );

        $this->assertStringContainsString('<h4>Oben</h4>', $verschoben);
        $this->assertStringContainsString('<h6>Tief</h6>', $verschoben);
        $this->assertStringContainsString('<h6>Tiefer</h6>', $verschoben);
    }

    public function test_laesst_einen_text_ohne_ueberschriften_unveraendert(): void
    {
        $this->assertSame(
            '<p>Nur Text</p>',
            $this->aufrufen('ueberschriften_verschieben', '<p>Nur Text</p>', 3)
        );
    }

    public function test_schneidet_den_abschnitt_mit_den_maengeln_heraus(): void
    {
        $html = '<h2>Stand der Vereinbarkeit</h2><p>teilweise</p>'
            .'<h2>Nicht barrierefreie Inhalte</h2><ul><li>Bilder ohne Alternativtext</li></ul>'
            .'<h2>Erstellung dieser Erklärung</h2><p>Selbstbewertung</p>';

        $abschnitt = $this->aufrufen('abschnitt', $html, ['Nicht barrierefreie Inhalte']);

        $this->assertStringContainsString('Bilder ohne Alternativtext', $abschnitt);
        $this->assertStringNotContainsString('Selbstbewertung', $abschnitt);
        $this->assertStringNotContainsString('Stand der Vereinbarkeit', $abschnitt);
    }

    public function test_erkennt_auch_die_ueberschrift_der_unternehmensfassung(): void
    {
        $html = '<h2>Stand der Umsetzung</h2><p>x</p>'
            .'<h2>Bekannte Einschränkungen</h2><p>Untertitel fehlen</p>';

        $this->assertStringContainsString(
            'Untertitel fehlen',
            $this->aufrufen('abschnitt', $html, ['Nicht barrierefreie Inhalte', 'Bekannte Einschränkungen'])
        );
    }

    /** Findet sich der Abschnitt nicht, ist der ganze Text besser als nichts. */
    public function test_gibt_den_ganzen_text_zurueck_wenn_der_abschnitt_fehlt(): void
    {
        $html = '<h2>Stand</h2><p>alles gut</p>';

        $this->assertSame($html, $this->aufrufen('abschnitt', $html, ['Nicht barrierefreie Inhalte']));
    }

    /**
     * Der Hinweis auf das Werkzeug (docs/07 der Dienst-Dokumentation).
     *
     * Formuliert wird er im Dienst und über das API-Feld "attribution"
     * durchgereicht; das Plugin hängt ihn nur an - innerhalb des Umschlags und
     * hinter dem Text.
     */
    public function test_haengt_den_hinweis_aus_der_antwort_hinter_den_text(): void
    {
        $GLOBALS['wp_erklaerung'] = [
            'html' => '<h1>Erklärung</h1><p>Text</p>',
            'locale' => 'de',
            'attribution' => '<p>Barrierefreiheitserklärung erstellt mit Barrierepruefung.de</p>',
        ];

        $ausgabe = $this->shortcode->render(['stand' => 'nein']);

        $this->assertStringContainsString('Barrierefreiheitserklärung erstellt mit', $ausgabe);
        $this->assertStringEndsWith('</p></div>', $ausgabe);
        $this->assertGreaterThan(strpos($ausgabe, 'Text'), strpos($ausgabe, 'erstellt mit'));
    }

    public function test_gibt_ohne_hinweis_in_der_antwort_auch_keinen_aus(): void
    {
        $GLOBALS['wp_erklaerung'] = [
            'html' => '<h1>Erklärung</h1><p>Text</p>',
            'locale' => 'de',
            'attribution' => null,
        ];

        $this->assertStringNotContainsString('erstellt mit', $this->shortcode->render(['stand' => 'nein']));
    }
}

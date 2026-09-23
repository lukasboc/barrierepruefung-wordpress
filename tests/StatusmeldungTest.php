<?php

use PHPUnit\Framework\TestCase;

/**
 * Die Meldung nach einer Aktion. Die Gründe einer gescheiterten Bestätigung
 * kommen vom Dienst als Schlüssel; „meta_tag_nicht_gefunden" hilft niemandem,
 * der nicht weiß, dass ein Seiten-Cache dahinterstecken kann.
 */
final class StatusmeldungTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
    }

    /** @param array<string, string> $abfrage */
    private function meldung(array $abfrage): string
    {
        $_GET = $abfrage;

        ob_start();
        include BARRIEREPRUEFUNG_PATH.'views/statusmeldung.php';

        return (string) ob_get_clean();
    }

    public function test_ein_fehlendes_meta_element_nennt_den_cache(): void
    {
        $html = $this->meldung([
            'barrierepruefung_status' => 'nicht_bestaetigt',
            'barrierepruefung_gruende' => 'meta_tag:meta_tag_nicht_gefunden',
        ]);

        $this->assertStringContainsString('caching plugin', $html);
        $this->assertStringNotContainsString('meta_tag_nicht_gefunden', $html);
    }

    public function test_beide_gruende_stehen_als_liste(): void
    {
        $html = $this->meldung([
            'barrierepruefung_status' => 'nicht_bestaetigt',
            'barrierepruefung_gruende' => 'meta_tag:meta_tag_nicht_gefunden,file:datei_nicht_erreichbar',
        ]);

        $this->assertSame(2, substr_count($html, '<li>'));
        $this->assertStringContainsString('/.well-known/a11y-site-verification.txt', $html);
    }

    /** Ein Grund, den diese Fassung nicht kennt, erscheint roh - statt zu fehlen. */
    public function test_ein_unbekannter_grund_erscheint_roh(): void
    {
        $html = $this->meldung([
            'barrierepruefung_status' => 'nicht_bestaetigt',
            'barrierepruefung_gruende' => 'file:ganz_neuer_grund',
        ]);

        $this->assertStringContainsString('file:ganz_neuer_grund', $html);
    }

    /** Die Adresszeile gehört nicht dem Plugin. */
    public function test_ein_grund_wird_maskiert(): void
    {
        $html = $this->meldung([
            'barrierepruefung_status' => 'nicht_bestaetigt',
            'barrierepruefung_gruende' => '<script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_verbunden_und_bestaetigt_fuehrt_zur_ersten_pruefung(): void
    {
        $html = $this->meldung(['barrierepruefung_status' => 'verbunden_bestaetigt']);

        $this->assertStringContainsString('notice-success', $html);
        $this->assertStringContainsString('start the first scan', $html);
    }

    public function test_verbunden_ohne_bestaetigung_warnt(): void
    {
        $html = $this->meldung([
            'barrierepruefung_status' => 'verbunden_nicht_bestaetigt',
            'barrierepruefung_gruende' => 'meta_tag:seite_nicht_erreichbar',
        ]);

        $this->assertStringContainsString('notice-warning', $html);
        $this->assertStringContainsString('publicly reachable', $html);
    }

    public function test_ohne_gruende_gibt_es_keine_liste(): void
    {
        $html = $this->meldung(['barrierepruefung_status' => 'bestaetigt']);

        $this->assertStringNotContainsString('<ul', $html);
    }
}

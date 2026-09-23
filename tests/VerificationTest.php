<?php

use PHPUnit\Framework\TestCase;

/**
 * Der Dienst vergibt je Verfahren einen eigenen Token. Das Meta-Element und
 * die Datei unter /.well-known/ tragen deshalb verschiedene Werte - bis 0.7.0
 * lieferte die Datei den des Meta-Elements aus, und eine Prüfung per Datei
 * konnte nie gelingen.
 */
final class VerificationTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_options'] = [
            'barrierepruefung_verification_token' => '01JMETA',
            'barrierepruefung_verification_file_token' => '01JDATEI',
        ];
    }

    public function test_die_datei_traegt_den_datei_token(): void
    {
        $this->assertSame('01JDATEI', (new Barrierepruefung_Verification)->datei_token());
    }

    /**
     * Fehlt der Datei-Token (Installation aus 0.7.0), bleibt die Datei leer -
     * der Token des Meta-Elements wäre dort falsch, nicht bloß überflüssig.
     */
    public function test_ohne_datei_token_faellt_die_datei_nicht_auf_den_meta_token_zurueck(): void
    {
        unset($GLOBALS['wp_options']['barrierepruefung_verification_file_token']);

        $this->assertSame('', (new Barrierepruefung_Verification)->datei_token());
    }

    public function test_das_meta_element_traegt_den_meta_token(): void
    {
        ob_start();
        (new Barrierepruefung_Verification)->render_meta_tag();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('name="a11y-site-verification"', $html);
        $this->assertStringContainsString('content="01JMETA"', $html);
    }
}

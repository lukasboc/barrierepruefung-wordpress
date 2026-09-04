<?php

use PHPUnit\Framework\TestCase;

/**
 * Prüft den Weg zurück aus einer eingetragenen Verbindung.
 *
 * Ohne ihn blendet is_connected() das Formular für immer aus, sobald Token und
 * Kennung einmal gespeichert sind — auch wenn beide falsch sind. Genau das ist
 * beim ersten Einrichten der wahrscheinlichste Fehler, und er wäre ohne WP-CLI
 * nicht zu beheben.
 */
final class AdminTest extends TestCase
{
    private A11y_Checker_Admin $admin;

    protected function setUp(): void
    {
        $this->admin = new A11y_Checker_Admin;

        // Globale überleben den einzelnen Test - also vor jedem zurücksetzen.
        $GLOBALS['wp_options'] = [
            'a11y_checker_api_url' => 'https://beispiel.test/api/v1',
            'a11y_checker_token' => '1|geheim',
            'a11y_checker_site_id' => 'st_123',
            'a11y_checker_verification_token' => '01JABCDEF',
            'a11y_checker_declaration_fallback' => ['html' => '<h1>Erklärung</h1>'],
        ];
        $GLOBALS['wp_transients_geloescht'] = [];
        unset($GLOBALS['wp_darf']);
    }

    /** @return string Der Statuswert, mit dem die Seite neu geladen wird. */
    private function ausfuehren(string $methode): string
    {
        try {
            $this->admin->$methode();
        } catch (A11y_Umleitung $umleitung) {
            parse_str((string) parse_url($umleitung->ziel, PHP_URL_QUERY), $abfrage);

            return (string) ($abfrage['a11y_status'] ?? '');
        }

        $this->fail('Die Aktion hat nicht umgeleitet.');
    }

    public function test_trennen_entfernt_das_token_und_die_kennung(): void
    {
        $status = $this->ausfuehren('handle_disconnect');

        $this->assertSame('getrennt', $status);
        $this->assertArrayNotHasKey('a11y_checker_token', $GLOBALS['wp_options']);
        $this->assertArrayNotHasKey('a11y_checker_site_id', $GLOBALS['wp_options']);

        // Danach zeigt die Seite wieder das Formular - das ist der ganze Zweck.
        $this->assertFalse((new A11y_Checker_Client)->is_connected());
    }

    /**
     * Der Nachweis gehört zu der Website, mit der gerade getrennt wurde. Bliebe
     * er stehen, lieferte die Installation ein Meta-Element für ein Konto aus,
     * das sie nicht mehr kennt.
     */
    public function test_trennen_entfernt_den_verifikationsnachweis(): void
    {
        $this->ausfuehren('handle_disconnect');

        $this->assertArrayNotHasKey('a11y_checker_verification_token', $GLOBALS['wp_options']);
    }

    /**
     * Die Rückfallebene ist eine rechtliche Aussage über eine Website. Sie nach
     * dem Trennen weiter auszuliefern hieße, sie unbegrenzt weiterzuverbreiten,
     * ohne dass noch jemand sie aktualisiert.
     */
    public function test_trennen_entfernt_den_gespeicherten_erklaerungstext(): void
    {
        $this->ausfuehren('handle_disconnect');

        $this->assertArrayNotHasKey('a11y_checker_declaration_fallback', $GLOBALS['wp_options']);
        $this->assertContains('a11y_checker_declaration', $GLOBALS['wp_transients_geloescht']);
    }

    /**
     * Wer sich nach einem Tippfehler im Token neu verbindet, soll die Adresse
     * nicht noch einmal abtippen müssen.
     */
    public function test_trennen_laesst_die_adresse_des_dienstes_stehen(): void
    {
        $this->ausfuehren('handle_disconnect');

        $this->assertSame('https://beispiel.test/api/v1', (new A11y_Checker_Client)->api_url());
    }

    /**
     * Zurückgesetzt wird durch Löschen der Option, nicht durch Hineinschreiben
     * des Standardwerts: so wandert eine spätere Änderung des Standards von
     * allein in bestehende Installationen.
     */
    public function test_adresse_zuruecksetzen_stellt_den_standard_wieder_her(): void
    {
        $status = $this->ausfuehren('handle_reset_url');

        $this->assertSame('adresse_zurueckgesetzt', $status);
        $this->assertArrayNotHasKey('a11y_checker_api_url', $GLOBALS['wp_options']);
        $this->assertSame(A11y_Checker_Client::DEFAULT_API, (new A11y_Checker_Client)->api_url());
    }

    /**
     * Eine Adresse, die esc_url_raw() verworfen hat, bleibt als leere Option
     * stehen — der Vorgabewert von get_option() greift dann nicht mehr.
     */
    public function test_leere_adresse_faellt_auf_den_standard_zurueck(): void
    {
        $GLOBALS['wp_options']['a11y_checker_api_url'] = '';

        $this->assertSame(A11y_Checker_Client::DEFAULT_API, (new A11y_Checker_Client)->api_url());
    }

    /** Beides gehört geprüft, nicht nur eines - hier die Berechtigung. */
    public function test_ohne_berechtigung_wird_nichts_getrennt(): void
    {
        $GLOBALS['wp_darf'] = false;

        $this->expectException(RuntimeException::class);

        try {
            $this->admin->handle_disconnect();
        } finally {
            $this->assertArrayHasKey('a11y_checker_token', $GLOBALS['wp_options']);
        }
    }
}

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
        $GLOBALS['wp_transients'] = [];
        $GLOBALS['wp_transients_geloescht'] = [];
        $GLOBALS['wp_antworten'] = [];
        $GLOBALS['wp_anfragen'] = [];
        unset($GLOBALS['wp_darf']);
    }

    /** Eine Antwort der API in die Warteschlange stellen. */
    private function antwort(array $koerper, int $status = 200): void
    {
        $GLOBALS['wp_antworten'][] = ['status' => $status, 'body' => json_encode($koerper)];
    }

    /** @return list<string> Die abgerufenen Pfade, ohne die Basisadresse. */
    private function abgerufenePfade(): array
    {
        return array_map(
            static fn ($anfrage) => str_replace('https://beispiel.test/api/v1', '', $anfrage['url']),
            $GLOBALS['wp_anfragen']
        );
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

    /*
     * Der Zustand der Seite: Website, Kontingent und Befunde. Er kostet zwei
     * Abrufe, deshalb der Zwischenspeicher - und deshalb ein Knopf, der ihn
     * verwirft, statt eines selbsttaetigen Neuladens der Seite.
     */

    public function test_der_zustand_holt_website_und_befunde(): void
    {
        $this->antwort(['data' => [
            'base_url' => 'https://beispiel.test/',
            'verified' => true,
            'latest_scan' => ['id' => 'sc_1', 'status' => 'completed'],
            'running_scan' => null,
        ]]);
        $this->antwort(['data' => [['rule' => 'axe.image-alt', 'occurrences' => 2]], 'meta' => []]);

        $zustand = $this->admin->zustand(new A11y_Checker_Client);

        $this->assertSame('https://beispiel.test/', $zustand['site']['base_url']);
        $this->assertSame('axe.image-alt', $zustand['findings'][0]['rule']);

        // Nur offene, verbindliche Befunde - das ist die Arbeitsliste.
        $this->assertSame(
            ['/sites/st_123', '/scans/sc_1/findings?binding_only=1&state=open'],
            $this->abgerufenePfade()
        );
    }

    /**
     * Ohne abgeschlossenen Lauf gibt es nichts abzurufen. Ein zweiter Abruf
     * waere hier eine Anfrage auf gut Glueck gegen einen fremden Server.
     */
    public function test_ohne_abgeschlossenen_lauf_werden_keine_befunde_geholt(): void
    {
        $this->antwort(['data' => ['verified' => true, 'latest_scan' => null, 'running_scan' => null]]);

        $zustand = $this->admin->zustand(new A11y_Checker_Client);

        $this->assertSame([], $zustand['findings']);
        $this->assertSame(['/sites/st_123'], $this->abgerufenePfade());
    }

    /** Ein Plugin auf vielen Installationen darf nicht bei jedem Aufruf anfragen. */
    public function test_der_zwischenspeicher_erspart_den_zweiten_abruf(): void
    {
        $this->antwort(['data' => ['verified' => true, 'latest_scan' => null, 'running_scan' => null]]);

        $this->admin->zustand(new A11y_Checker_Client);
        $this->admin->zustand(new A11y_Checker_Client);

        $this->assertCount(1, $GLOBALS['wp_anfragen']);
    }

    /**
     * Ein Fehler darf sich nicht festsetzen: wer das Token richtigstellt, soll
     * nicht fuenf Minuten weiter die alte Meldung sehen.
     */
    public function test_ein_fehlgeschlagener_abruf_wird_nicht_zwischengespeichert(): void
    {
        $this->antwort(['title' => 'Nicht gefunden', 'detail' => 'Unbekannt'], 404);
        $this->antwort(['data' => ['verified' => true, 'latest_scan' => null, 'running_scan' => null]]);

        $erster = $this->admin->zustand(new A11y_Checker_Client);
        $zweiter = $this->admin->zustand(new A11y_Checker_Client);

        $this->assertNull($erster['site']);
        $this->assertSame('Unbekannt', $erster['error']);
        $this->assertNotNull($zweiter['site']);
    }

    public function test_aktualisieren_verwirft_den_zwischenspeicher(): void
    {
        $status = $this->ausfuehren('handle_refresh');

        $this->assertSame('aktualisiert', $status);
        $this->assertContains('a11y_checker_status', $GLOBALS['wp_transients_geloescht']);
    }

    /**
     * Sonst zeigte die Seite nach dem Ausloesen bis zu fuenf Minuten den alten
     * Stand - und damit "keine laufende Pruefung", obwohl gerade eine laeuft.
     */
    public function test_eine_neue_pruefung_verwirft_den_zwischenspeicher(): void
    {
        $this->antwort(['data' => ['id' => 'sc_2', 'status' => 'queued']], 202);

        $status = $this->ausfuehren('handle_scan');

        $this->assertSame('geprueft', $status);
        $this->assertContains('a11y_checker_status', $GLOBALS['wp_transients_geloescht']);
        $this->assertContains('a11y_checker_declaration', $GLOBALS['wp_transients_geloescht']);
    }

    /*
     * Die Seite selbst. Gerendert wird sie mit Attrappen, damit auffaellt,
     * wenn ein Anzeigezustand gar nicht erreichbar ist - eine leere Tabelle
     * waere in jedem der drei die falsche Antwort.
     */

    private function seite(): string
    {
        ob_start();
        $this->admin->render_page();

        return (string) ob_get_clean();
    }

    public function test_die_seite_nennt_das_kontingent(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'quota' => ['metric' => 'pages', 'used' => 42, 'limit' => 500, 'remaining' => 458, 'period_end' => '2026-09-30'],
            'latest_scan' => null,
            'running_scan' => null,
        ]]);

        $seite = $this->seite();

        $this->assertStringContainsString('42 of 500', $seite);
        $this->assertStringContainsString('458', $seite);
    }

    /** Eine hinterlegte null heisst unbegrenzt - als 0 waere sie eine Falschaussage. */
    public function test_die_seite_zeigt_ein_unbegrenztes_kontingent_nicht_als_null(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'quota' => ['metric' => 'pages', 'used' => 42, 'limit' => null, 'remaining' => null, 'period_end' => null],
            'latest_scan' => null,
            'running_scan' => null,
        ]]);

        $seite = $this->seite();

        $this->assertStringContainsString('42 (unlimited)', $seite);
        $this->assertStringNotContainsString('42 of 0', $seite);
    }

    public function test_die_seite_sagt_es_wenn_noch_kein_ergebnis_vorliegt(): void
    {
        $this->antwort(['data' => ['verified' => true, 'latest_scan' => null, 'running_scan' => null]]);

        $this->assertStringContainsString('no scan result for this site yet', $this->seite());
    }

    /** Ein Ergebnis ohne Befunde ist eine gute Nachricht, keine leere Tabelle. */
    public function test_die_seite_sagt_es_wenn_keine_befunde_offen_sind(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => ['id' => 'sc_1', 'status' => 'completed', 'finished_at' => '2026-09-05T08:14:00Z'],
            'running_scan' => null,
        ]]);
        $this->antwort(['data' => [], 'meta' => []]);

        $this->assertStringContainsString('no open findings', $this->seite());
    }

    public function test_die_seite_listet_die_befunde_mit_hinweis_und_seiten(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => ['id' => 'sc_1', 'status' => 'completed', 'finished_at' => '2026-09-05T08:14:00Z', 'report_url' => 'https://beispiel.test/pruefung/sc_1'],
            'running_scan' => null,
        ]]);
        $this->antwort(['data' => [[
            'rule' => 'axe.image-alt',
            'title' => 'Bilder ohne Alternativtext',
            'remediation' => 'Ergänzen Sie ein alt-Attribut.',
            'severity' => 'critical',
            'success_criteria' => ['1.1.1'],
            'occurrences' => 12,
            'pages' => 25,
            'page_urls' => ['https://beispiel.test/'],
        ]], 'meta' => []]);

        $seite = $this->seite();

        $this->assertStringContainsString('Bilder ohne Alternativtext', $seite);
        $this->assertStringContainsString('Ergänzen Sie ein alt-Attribut.', $seite);
        $this->assertStringContainsString('https://beispiel.test/pruefung/sc_1', $seite);

        // Der Schweregrad steht als Wort da, nicht nur als Farbe (WCAG 1.4.1).
        $this->assertStringContainsString('critical', $seite);

        // Die Seitenliste ist gedeckelt; die volle Zahl darf nicht verschwinden.
        $this->assertStringContainsString('and 24 more', $seite);
    }

    public function test_die_seite_meldet_eine_laufende_pruefung(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => null,
            'running_scan' => ['id' => 'sc_9', 'status' => 'analyzing'],
        ]]);

        $this->assertStringContainsString('A scan is currently running', $this->seite());
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

<?php

use PHPUnit\Framework\TestCase;

/**
 * Der Weg zur Erklärung im Reiter „Erklärung".
 *
 * Was hier festgehalten ist, trägt die Idee des Reiters: Das Plugin zeigt,
 * was der Dienst sagt, und erfindet nichts dazu - auch keinen Schritt, den es
 * nicht kennt, weglassen. Eine Freigabe schickt genau den Entwurf zurück, der
 * gezeigt wurde, und ein Fehler führt dorthin zurück, wo er zu beheben ist.
 */
final class ErklaerungTest extends TestCase
{
    private Barrierepruefung_Erklaerung $erklaerung;

    protected function setUp(): void
    {
        $this->erklaerung = new Barrierepruefung_Erklaerung;

        $GLOBALS['wp_options'] = [
            'barrierepruefung_api_url' => 'https://beispiel.test/api/v1',
            'barrierepruefung_token' => '1|geheim',
            'barrierepruefung_site_id' => 'st_123',
        ];
        $GLOBALS['wp_transients'] = [];
        $GLOBALS['wp_transients_geloescht'] = [];
        $GLOBALS['wp_antworten'] = [];
        $GLOBALS['wp_anfragen'] = [];
        $GLOBALS['wp_user_meta'] = [];
        $GLOBALS['wp_beitraege'] = [];
        $GLOBALS['wp_neue_beitraege'] = [];
        $_GET = [];
        $_POST = [];
        unset($GLOBALS['wp_darf'], $GLOBALS['wp_sprache'], $GLOBALS['wp_erklaerung']);
    }

    // --- Hilfen ---

    private function antwort(array $koerper, int $status = 200): void
    {
        $GLOBALS['wp_antworten'][] = ['status' => $status, 'body' => json_encode($koerper)];
    }

    /** Ein Weg, wie ihn der Dienst liefert. */
    private function weg(array $ueberschreiben = []): array
    {
        return ['data' => array_merge([
            'scan' => ['id' => 'sc_1', 'report_url' => 'https://beispiel.test/pruefung/sc_1', 'findings_url' => 'https://beispiel.test/pruefung/sc_1/befunde'],
            'document' => ['type' => 'public_sector_statement', 'title' => 'Erklärung zur Barrierefreiheit (BITV 2.0)', 'explanation' => 'Öffentliche Stelle.'],
            'steps' => [
                ['key' => 'pruefung', 'title' => 'Prüfung abgeschlossen', 'state' => 'erledigt', 'reasons' => [], 'web_url' => 'https://beispiel.test/p'],
                ['key' => 'pruefschritte', 'title' => 'Prüfschritte beantworten', 'state' => 'offen', 'reasons' => [['key' => 'pruefschritte_offen', 'text' => 'Es sind noch 2 manuelle Prüfschritte offen.']], 'web_url' => 'https://beispiel.test/ps'],
                ['key' => 'pflichtangaben', 'title' => 'Pflichtangaben der Erklärung', 'state' => 'spaeter', 'reasons' => [], 'web_url' => 'https://beispiel.test/pa'],
            ],
            'done' => 2,
            'total' => 3,
            'next' => 'pruefschritte',
            'published' => null,
        ], $ueberschreiben)];
    }

    private function entwurf(array $ueberschreiben = []): array
    {
        return ['data' => array_merge([
            'scan' => ['id' => 'sc_1'],
            'locale' => 'de',
            'html' => '<h1>Erklärung</h1><h2>Kontakt</h2><p>Text</p>',
            'conformance' => [
                'suggested' => 'partial',
                'reason' => 'Es gibt offene Befunde.',
                'choices' => ['partial', 'none'],
                'labels' => ['full' => 'vollständig konform', 'partial' => 'teilweise konform', 'none' => 'nicht konform'],
            ],
            'next_version' => 2,
            'draft_etag' => 'etag-abc',
            'fields' => [
                ['key' => 'contact', 'value' => '', 'applies' => true, 'required' => true, 'max' => 500, 'type' => 'text'],
                ['key' => 'alternatives', 'value' => '', 'applies' => true, 'required' => false, 'max' => 1000, 'type' => 'text'],
                ['key' => 'dgs_ls_url', 'value' => '', 'applies' => true, 'required' => false, 'max' => 255, 'type' => 'url'],
                ['key' => 'service_description', 'value' => '', 'applies' => false, 'required' => false, 'max' => 2000, 'type' => 'text'],
                ['key' => 'enforcement_body_name', 'value' => 'Schlichtungsstelle nach § 16 BGG', 'applies' => true, 'required' => true, 'max' => 255, 'type' => 'text'],
            ],
        ], $ueberschreiben)];
    }

    private function rendern(string $schritt = ''): string
    {
        $_GET = array_filter(['page' => 'barrierepruefung', 'ansicht' => 'erklaerung', 'schritt' => $schritt ?: null]);

        ob_start();
        $this->erklaerung->render(new Barrierepruefung_Client);

        return (string) ob_get_clean();
    }

    /** @return string Das Ziel der Umleitung. */
    private function ausfuehren(string $methode): string
    {
        try {
            $this->erklaerung->$methode();
        } catch (Barrierepruefung_Umleitung $umleitung) {
            return $umleitung->ziel;
        }

        $this->fail('Die Aktion hat nicht umgeleitet.');
    }

    /** @return array<string, mixed> */
    private function gesendet(int $index): array
    {
        return (array) json_decode((string) ($GLOBALS['wp_anfragen'][$index]['args']['body'] ?? '{}'), true);
    }

    // --- Übersicht ---

    public function test_die_uebersicht_zeigt_die_schritte_des_dienstes_mit_zustand(): void
    {
        $this->antwort($this->weg());

        $html = $this->rendern();

        $this->assertStringContainsString('Prüfschritte beantworten', $html);
        $this->assertStringContainsString('Es sind noch 2 manuelle Prüfschritte offen.', $html);
        $this->assertStringContainsString('filled in before you publish', $html);
        $this->assertStringContainsString('2 of 3 steps done.', $html);
    }

    /** Die hervorgehobene Schaltfläche nennt ihr Ziel, nie ein bloßes „Weiter". */
    public function test_die_hauptschaltflaeche_fuehrt_zum_naechsten_offenen_schritt(): void
    {
        $this->antwort($this->weg());

        $html = $this->rendern();

        $this->assertMatchesRegularExpression('#button-primary" href="[^"]*schritt=pruefschritte">\s*Next: Prüfschritte beantworten#', $html);
    }

    public function test_ist_alles_erledigt_fuehrt_sie_zur_freigabe(): void
    {
        $this->antwort($this->weg(['next' => null]));

        $this->assertMatchesRegularExpression('#schritt=freigabe">\s*Review and publish the statement#', $this->rendern());
    }

    /**
     * Ein Schritt, den der Dienst nach dieser Fassung eingeführt hat, fehlt
     * nicht - er führt dorthin, wo er sich erledigen lässt.
     */
    public function test_ein_unbekannter_schritt_verweist_in_den_dienst(): void
    {
        $weg = $this->weg();
        $weg['data']['steps'][] = ['key' => 'neu_im_dienst', 'title' => 'Ein neuer Schritt', 'state' => 'offen', 'reasons' => [], 'web_url' => 'https://beispiel.test/neu'];
        $this->antwort($weg);

        $html = $this->rendern('neu_im_dienst');

        $this->assertStringContainsString('Ein neuer Schritt', $html);
        $this->assertStringContainsString('https://beispiel.test/neu', $html);
        $this->assertStringContainsString('Complete this step in the service', $html);
    }

    public function test_ein_dienst_ohne_diesen_weg_wird_benannt(): void
    {
        $this->antwort(['title' => 'Nicht gefunden', 'status' => 404], 404);

        $this->assertStringContainsString('does not support preparing and publishing', $this->rendern());
    }

    public function test_ohne_abgeschlossene_pruefung_geht_es_zur_pruefung(): void
    {
        $this->antwort($this->weg(['scan' => null, 'steps' => [], 'next' => null]));

        $html = $this->rendern();

        $this->assertStringContainsString('There is none for this site yet.', $html);
        $this->assertStringContainsString('Go to the scan', $html);
    }

    // --- Einbinden ---

    public function test_nach_der_veroeffentlichung_laesst_sich_eine_seite_als_entwurf_anlegen(): void
    {
        $this->antwort($this->weg(['next' => null, 'published' => [
            'version' => 1, 'published_at' => '2026-09-21T10:00:00Z', 'outdated' => false,
            'public_url' => 'https://beispiel.test/e/x', 'pdf_url' => 'https://beispiel.test/e/x.pdf',
        ]]));

        $html = $this->rendern();

        $this->assertStringContainsString('Version 1 is published', $html);
        $this->assertStringContainsString('Create a draft page with the statement', $html);
    }

    public function test_eine_seite_mit_dem_shortcode_wird_gefunden(): void
    {
        $GLOBALS['wp_beitraege'] = [
            (object) ['ID' => 5, 'post_content' => 'Vorher [barrierefreiheitserklaerung teil="maengel"]', 'post_status' => 'publish'],
        ];
        $this->antwort($this->weg(['next' => null, 'published' => [
            'version' => 1, 'published_at' => '2026-09-21T10:00:00Z', 'outdated' => false,
            'public_url' => 'https://beispiel.test/e/x', 'pdf_url' => 'https://beispiel.test/e/x.pdf',
        ]]));

        $html = $this->rendern();

        $this->assertStringContainsString('The statement is embedded on:', $html);
        $this->assertStringContainsString('Seite 5', $html);
        $this->assertStringNotContainsString('Create a draft page', $html);
    }

    /** Entwurf, weil eine neue öffentliche Seite eine Entscheidung ist, kein Nebeneffekt. */
    public function test_die_neue_seite_ist_ein_entwurf_mit_dem_block(): void
    {
        $this->ausfuehren('handle_seite_anlegen');

        $seite = array_values($GLOBALS['wp_neue_beitraege'])[0];

        $this->assertSame('draft', $seite['post_status']);
        $this->assertSame('page', $seite['post_type']);
        $this->assertStringContainsString('wp:barrierepruefung/erklaerung', $seite['post_content']);
    }

    // --- Schritte ---

    public function test_die_pflichtangaben_zeigen_nur_was_fuer_die_dokumentart_gilt(): void
    {
        $this->antwort($this->weg());
        $this->antwort($this->entwurf());

        $html = $this->rendern('pflichtangaben');

        $this->assertStringContainsString('name="angaben[contact]"', $html);
        $this->assertStringContainsString('name="angaben[dgs_ls_url]"', $html);
        $this->assertStringNotContainsString('name="angaben[service_description]"', $html);
        $this->assertStringContainsString('Schlichtungsstelle nach § 16 BGG', $html);
    }

    /**
     * Ein Fehler führt zurück zum Schritt, mit allem, was eingegeben war, und
     * mit einer Liste, deren Einträge zu den Feldern springen.
     */
    public function test_ein_abgewiesener_schritt_behaelt_die_eingaben_und_nennt_die_felder(): void
    {
        $_POST = ['angaben' => ['contact' => '', 'alternatives' => 'Telefon 0123']];
        $this->antwort([
            'status' => 422,
            'detail' => 'Ohne Kontakt kann niemand eine Barriere melden.',
            'errors' => ['contact' => ['Ohne Kontakt kann niemand eine Barriere melden — das ist Pflichtangabe.']],
        ], 422);

        $ziel = $this->ausfuehren('handle_angaben');

        $this->assertStringContainsString('schritt=pflichtangaben', $ziel);

        // Nach der Umleitung steht die Seite in der Adresse.
        $_GET = ['page' => 'barrierepruefung'];
        $this->assertSame('Error: Accessibility', $this->erklaerung->titel('Accessibility'));

        $this->antwort($this->weg());
        $this->antwort($this->entwurf());
        $_GET = ['page' => 'barrierepruefung'];
        $html = $this->rendern('pflichtangaben');

        $this->assertStringContainsString('There is a problem', $html);
        $this->assertStringContainsString('href="#barrierepruefung-feld-contact"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('Telefon 0123', $html);
    }

    /** Die Rückmeldung wird genau einmal gezeigt. */
    public function test_die_rueckmeldung_verschwindet_nach_dem_anzeigen(): void
    {
        update_user_meta(7, Barrierepruefung_Erklaerung::RUECKMELDUNG, ['art' => 'erfolg', 'text' => 'Gespeichert.']);

        $this->antwort($this->weg());
        $this->assertStringContainsString('Gespeichert.', $this->rendern());

        $this->antwort($this->weg());
        $this->assertStringNotContainsString('Gespeichert.', $this->rendern());
    }

    public function test_nach_dem_speichern_geht_es_zum_naechsten_offenen_schritt(): void
    {
        $_POST = ['scan' => 'sc_1', 'checks' => ['11' => ['answer' => 'passed', 'note' => ''], '12' => ['answer' => 'failed', 'note' => 'Springt']]];
        $this->antwort(['data' => [], 'meta' => ['open' => 0]]);
        $this->antwort($this->weg(['next' => 'pflichtangaben']));

        $ziel = $this->ausfuehren('handle_pruefschritte');

        $gesendet = $this->gesendet(0);
        $this->assertSame('PUT', $GLOBALS['wp_anfragen'][0]['args']['method']);
        $this->assertStringEndsWith('/scans/sc_1/manual-checks', $GLOBALS['wp_anfragen'][0]['url']);
        $this->assertSame('Wanda WordPress', $gesendet['actor_name']);
        $this->assertSame(['id' => 12, 'answer' => 'failed', 'note' => 'Springt'], $gesendet['checks'][1]);
        $this->assertStringContainsString('schritt=pflichtangaben', $ziel);
    }

    public function test_fehlt_das_recht_nennt_die_seite_den_ort_im_dienst(): void
    {
        $_POST = ['name' => '', 'applies_to' => 'business'];
        $this->antwort([
            'status' => 403,
            'detail' => 'Diesem Token fehlt das Recht.',
            'reason' => 'recht_fehlt',
            'url' => 'https://beispiel.test/websites/st_123/einbindung',
        ], 403);

        $this->ausfuehren('handle_stelle');

        $this->antwort($this->weg(['steps' => [['key' => 'verantwortliche_stelle', 'title' => 'Wer erklärt', 'state' => 'erledigt', 'reasons' => []]]]));
        $this->antwort(['data' => ['name' => '', 'options' => []]]);
        $html = $this->rendern('verantwortliche_stelle');

        $this->assertStringContainsString('Diesem Token fehlt das Recht.', $html);
        $this->assertStringContainsString('https://beispiel.test/websites/st_123/einbindung', $html);
    }

    // --- Freigabe ---

    public function test_die_freigabe_zeigt_den_entwurf_und_traegt_seine_kennung(): void
    {
        $this->antwort($this->weg(['next' => null]));
        $this->antwort($this->entwurf());

        $html = $this->rendern('freigabe');

        $this->assertStringContainsString('name="draft_etag" value="etag-abc"', $html);
        $this->assertStringContainsString('name="expected_version" value="2"', $html);
        $this->assertMatchesRegularExpression('#name="idempotency_key" value="uuid-[0-9a-f]+"#', $html);
        $this->assertStringContainsString('Publish version 2 now', $html);
        // Unter der h2 des Bildschirms beginnt der Entwurf bei h3 - keine zweite h1.
        $this->assertStringContainsString('<h3>Erklärung</h3>', $html);
        // Nur was wählbar ist, steht zur Wahl.
        $this->assertStringNotContainsString('value="full"', $html);
    }

    /** Keine Sackgasse, aber auch kein Knopf, der nur einen Fehler auslöst. */
    public function test_solange_schritte_offen_sind_gibt_es_keinen_freigabeknopf(): void
    {
        $this->antwort($this->weg());
        $this->antwort($this->entwurf());

        $html = $this->rendern('freigabe');

        $this->assertStringContainsString('See which steps are open', $html);
        $this->assertStringNotContainsString('Publish version 2 now', $html);
    }

    public function test_die_freigabe_schickt_kennung_schluessel_und_person(): void
    {
        $_POST = [
            'draft_etag' => 'etag-abc',
            'expected_version' => '2',
            'idempotency_key' => 'uuid-fest',
            'actor_name' => 'Wanda WordPress',
            // Aufgeklappt und ausgewählt, aber ohne den Haken zum Abweichen:
            // zählt nicht.
            'conformance_status' => 'none',
        ];
        $this->antwort(['data' => ['version' => 2, 'public_url' => 'https://beispiel.test/e/x']], 201);
        $this->antwort(['declaration' => ['html' => '<h1>Neu</h1>', 'version' => 2]]);

        $ziel = $this->ausfuehren('handle_freigeben');

        $anfrage = $GLOBALS['wp_anfragen'][0];
        $gesendet = $this->gesendet(0);

        $this->assertStringEndsWith('/sites/st_123/declaration/publish', $anfrage['url']);
        $this->assertSame('uuid-fest', $anfrage['args']['headers']['Idempotency-Key']);
        $this->assertSame('etag-abc', $gesendet['draft_etag']);
        $this->assertSame(2, $gesendet['expected_version']);
        $this->assertArrayNotHasKey('conformance_status', $gesendet);

        // Der Shortcode holt die neue Fassung sofort - samt Rückfallebene.
        $this->assertStringEndsWith('/sites/st_123/declaration', $GLOBALS['wp_anfragen'][1]['url']);
        $this->assertSame(['html' => '<h1>Neu</h1>', 'version' => 2], $GLOBALS['wp_options']['barrierepruefung_declaration_fallback']);
        $this->assertContains('barrierepruefung_declaration', $GLOBALS['wp_transients_geloescht']);

        $this->assertStringNotContainsString('schritt=', $ziel);
    }

    public function test_eine_abweichende_aussage_geht_nur_mit_dem_haken_mit(): void
    {
        $_POST = [
            'draft_etag' => 'etag-abc', 'expected_version' => '2', 'idempotency_key' => 'k',
            'abweichen' => '1', 'conformance_status' => 'none', 'override_reason' => 'Formulare im Relaunch',
        ];
        $this->antwort(['data' => ['version' => 2]], 201);

        $this->ausfuehren('handle_freigeben');

        $gesendet = $this->gesendet(0);
        $this->assertSame('none', $gesendet['conformance_status']);
        $this->assertSame('Formulare im Relaunch', $gesendet['override_reason']);
    }

    /** Hat sich der Entwurf geändert, geht es zurück zur Freigabe - mit dem neuen Stand. */
    public function test_ein_geaenderter_entwurf_fuehrt_zurueck_zur_freigabe(): void
    {
        $_POST = ['draft_etag' => 'alt', 'expected_version' => '2', 'idempotency_key' => 'k'];
        $this->antwort([
            'status' => 409,
            'detail' => 'Der Entwurf hat sich seit Ihrer Vorschau geändert.',
            'reason' => 'entwurf_geaendert',
            'draft_etag' => 'neu',
        ], 409);

        $ziel = $this->ausfuehren('handle_freigeben');

        $this->assertStringContainsString('schritt=freigabe', $ziel);
        $this->assertSame('Der Entwurf hat sich seit Ihrer Vorschau geändert.', $GLOBALS['wp_user_meta'][7][Barrierepruefung_Erklaerung::RUECKMELDUNG]['text']);
    }

    public function test_nicht_bereit_fuehrt_zur_uebersicht_mit_den_gruenden(): void
    {
        $_POST = ['draft_etag' => 'e', 'expected_version' => '2', 'idempotency_key' => 'k'];
        $this->antwort([
            'status' => 422,
            'detail' => 'Die Erklärung kann noch nicht veröffentlicht werden.',
            'reason' => 'nicht_bereit',
            'blockers' => [['key' => 'domain', 'text' => 'Die Domain ist nicht bestätigt.']],
        ], 422);

        $ziel = $this->ausfuehren('handle_freigeben');

        $this->assertStringNotContainsString('schritt=', $ziel);

        $this->antwort($this->weg());
        $this->assertStringContainsString('Die Domain ist nicht bestätigt.', $this->rendern());
    }

    // --- Berechtigung und Sprache ---

    public function test_ohne_berechtigung_wird_nichts_gesendet(): void
    {
        $GLOBALS['wp_darf'] = false;

        try {
            $this->erklaerung->handle_freigeben();
            $this->fail('Ohne Berechtigung hätte die Aktion abbrechen müssen.');
        } catch (RuntimeException $abbruch) {
            $this->assertStringStartsWith('wp_die', $abbruch->getMessage());
        }

        $this->assertSame([], $GLOBALS['wp_anfragen']);
    }

    /** Fachtexte des Dienstes kommen in der Sprache der Person vor dem Backend. */
    public function test_die_anfrage_nennt_die_sprache_der_person(): void
    {
        $GLOBALS['wp_sprache'] = 'en_GB';
        $this->antwort($this->weg());

        $this->rendern();

        $this->assertSame('en-GB', $GLOBALS['wp_anfragen'][0]['args']['headers']['Accept-Language']);
    }
}

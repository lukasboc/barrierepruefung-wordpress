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
        $_GET = [];
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

    /**
     * Die Befunde sind der Grund, warum jemand die Seite oeffnet; das
     * Kontingent ist die Nebenauskunft. Deshalb stehen sie zuerst.
     */
    public function test_die_befunde_stehen_ueber_dem_kontingent(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'quota' => ['metric' => 'pages', 'used' => 42, 'limit' => 500, 'remaining' => 458, 'period_end' => '2026-09-30'],
            'latest_scan' => null,
            'running_scan' => null,
        ]]);

        $seite = $this->seite();

        // Erst belegen, dass es beide Ueberschriften gibt: strpos() gaebe
        // sonst zweimal false zurueck und der Vergleich ginge blind durch.
        $this->assertStringContainsString('>Findings<', $seite);
        $this->assertStringContainsString('>Quota<', $seite);

        $this->assertLessThan(
            strpos($seite, '>Quota<'),
            strpos($seite, '>Findings<'),
            'Die Befunde muessen vor dem Kontingent stehen.'
        );
    }

    /*
     * Fundstellen. Sie werden erst geholt, wenn jemand eine Regel aufklappt -
     * ein gewoehnlicher Verweis, kein Skript.
     */

    /** @param array<string, mixed> $stelle */
    private function mitFundstellen(array $stelle, int $gesamt = 1, int $ab = 0): string
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => ['id' => 'sc_1', 'status' => 'completed', 'report_url' => 'https://beispiel.test/pruefung/sc_1'],
            'running_scan' => null,
        ]]);
        $this->antwort(['data' => [[
            'rule' => 'axe.color-contrast', 'title' => 'Kontrast', 'severity' => 'serious',
            'success_criteria' => ['1.4.3'], 'occurrences' => $gesamt, 'pages' => 1, 'page_urls' => [],
        ]], 'meta' => []]);
        $this->antwort([
            'data' => [$stelle],
            'meta' => ['rule' => 'axe.color-contrast', 'total' => $gesamt, 'limit' => 20, 'offset' => $ab],
        ]);

        $_GET['a11y_regel'] = 'axe.color-contrast';
        if ($ab > 0) {
            $_GET['a11y_ab'] = (string) $ab;
        }

        return $this->seite();
    }

    public function test_ohne_aufgeklappte_regel_werden_keine_fundstellen_geholt(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => ['id' => 'sc_1', 'status' => 'completed'],
            'running_scan' => null,
        ]]);
        $this->antwort(['data' => [], 'meta' => []]);

        $this->seite();

        $this->assertSame(
            ['/sites/st_123', '/scans/sc_1/findings?binding_only=1&state=open'],
            $this->abgerufenePfade()
        );
    }

    public function test_eine_aufgeklappte_regel_zeigt_ihre_fundstellen(): void
    {
        $seite = $this->mitFundstellen([
            'page_url' => 'https://beispiel.test/kontakt',
            'selector' => 'main > p.intro',
            'ui_state' => 'Im geöffneten Hauptmenü',
            'viewport' => 'bei 320 px Breite',
            'measurements' => ['Kontrast 2,41:1 statt 4,5:1'],
            'colors' => ['Vordergrund' => '#767676'],
            'summary' => 'Element has insufficient color contrast',
            'html_snippet' => '<p class="intro">Hallo</p>',
            'has_screenshot' => true,
        ]);

        $this->assertStringContainsString('https://beispiel.test/kontakt', $seite);
        $this->assertStringContainsString('main &gt; p.intro', $seite);
        $this->assertStringContainsString('Im geöffneten Hauptmenü', $seite);
        $this->assertStringContainsString('bei 320 px Breite', $seite);
        $this->assertStringContainsString('Kontrast 2,41:1 statt 4,5:1', $seite);
        $this->assertStringContainsString('Vordergrund: #767676', $seite);
        $this->assertStringContainsString('&lt;p class=&quot;intro&quot;&gt;', $seite);

        // Geholt wird genau diese eine Regel.
        $this->assertContains(
            '/scans/sc_1/findings/axe.color-contrast?limit=20&offset=0',
            $this->abgerufenePfade()
        );
    }

    /** Ohne Selektor betrifft der Befund die Seite als Ganzes. */
    public function test_eine_fundstelle_ohne_selektor_nennt_die_ganze_seite(): void
    {
        $seite = $this->mitFundstellen([
            'page_url' => 'https://beispiel.test/',
            'selector' => null,
            'measurements' => [],
            'colors' => [],
            'has_screenshot' => false,
        ]);

        $this->assertStringContainsString('the entire page', $seite);
    }

    /**
     * Screenshots werden nicht in die fremde Installation kopiert - dort
     * gaelte weder die Aufbewahrungsfrist noch die Zugriffspruefung. Genannt
     * wird der Weg zu ihnen, und dass er eine Anmeldung verlangt.
     */
    public function test_screenshots_werden_nicht_geladen_sondern_verwiesen(): void
    {
        $seite = $this->mitFundstellen([
            'page_url' => 'https://beispiel.test/',
            'selector' => 'img',
            'measurements' => [],
            'colors' => [],
            'has_screenshot' => true,
        ]);

        $this->assertStringContainsString('Screenshots of the occurrences are available', $seite);
        $this->assertStringContainsString('sign in', $seite);
        $this->assertStringContainsString('https://beispiel.test/pruefung/sc_1', $seite);

        // Kein Bild und kein Artefaktschluessel in der Seite.
        $this->assertStringNotContainsString('<img', $seite);
    }

    public function test_bei_vielen_fundstellen_laesst_sich_blaettern(): void
    {
        $seite = $this->mitFundstellen(
            ['page_url' => 'https://beispiel.test/', 'selector' => 'img', 'measurements' => [], 'colors' => [], 'has_screenshot' => false],
            gesamt: 30
        );

        $this->assertStringContainsString('More occurrences', $seite);
        $this->assertStringContainsString('a11y_ab=20', $seite);
        $this->assertStringNotContainsString('Previous occurrences', $seite);
    }

    public function test_auf_der_zweiten_seite_fuehrt_ein_weg_zurueck(): void
    {
        $seite = $this->mitFundstellen(
            ['page_url' => 'https://beispiel.test/', 'selector' => 'img', 'measurements' => [], 'colors' => [], 'has_screenshot' => false],
            gesamt: 30,
            ab: 20
        );

        $this->assertStringContainsString('Previous occurrences', $seite);
        $this->assertContains(
            '/scans/sc_1/findings/axe.color-contrast?limit=20&offset=20',
            $this->abgerufenePfade()
        );
    }

    /**
     * Waehrend eines Laufs ist Aktualisieren die einzige sinnvolle Handlung.
     *
     * Vorher war sie der graue Knopf neben dem blauen "Website pruefen" und der
     * Hinweis stand darunter - wer eine Pruefung angestossen hatte, sah nicht,
     * wie er an das Ergebnis kommt.
     */
    public function test_waehrend_einer_pruefung_fuehrt_der_hervorgehobene_knopf_zum_ergebnis(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => null,
            'running_scan' => ['id' => 'sc_9', 'status' => 'analyzing'],
        ]]);

        $seite = $this->seite();

        $this->assertStringContainsString('A scan is running', $seite);
        $this->assertStringContainsString('Check whether the scan has finished', $seite);

        // Ein zweiter Lauf verbrauchte nur Kontingent.
        $this->assertStringNotContainsString('Scan now', $seite);
        $this->assertStringNotContainsString('Refresh status', $seite);
    }

    /** Ohne laufende Pruefung bleibt "Website pruefen" der hervorgehobene Knopf. */
    public function test_ohne_laufende_pruefung_steht_der_start_im_vordergrund(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => null,
            'running_scan' => null,
        ]]);

        $seite = $this->seite();

        $this->assertStringContainsString('Scan now', $seite);
        $this->assertStringContainsString('Refresh status', $seite);
        $this->assertStringNotContainsString('Check whether the scan has finished', $seite);
    }

    /**
     * Der erste Lauf laeuft noch: der Verweis auf "Website pruefen" waere hier
     * ein Verweis auf einen Knopf, den es in dem Moment gar nicht gibt.
     */
    public function test_ohne_ergebnis_aber_mit_laufender_pruefung_wird_nicht_zum_start_geschickt(): void
    {
        $this->antwort(['data' => [
            'verified' => true,
            'latest_scan' => null,
            'running_scan' => ['id' => 'sc_9', 'status' => 'queued'],
        ]]);

        $seite = $this->seite();

        $this->assertStringContainsString('The first scan is running', $seite);
        $this->assertStringNotContainsString('There is no scan result for this site yet', $seite);
    }

    /*
     * Die Anleitung beim ersten Einrichten. Ohne sie stand dort nur "Legen Sie
     * im Konto ein API-Token an" - und nicht, wo dieses Konto ist.
     */

    private function unverbundeneSeite(): string
    {
        unset($GLOBALS['wp_options']['a11y_checker_token'], $GLOBALS['wp_options']['a11y_checker_site_id']);

        return $this->seite();
    }

    public function test_ohne_verbindung_erklaert_die_seite_den_weg_zum_token(): void
    {
        $seite = $this->unverbundeneSeite();

        // Warum ueberhaupt ein Konto noetig ist.
        $this->assertStringContainsString('The plugin does not scan on its own', $seite);

        // Und der Weg dorthin, Schritt fuer Schritt.
        $this->assertStringContainsString('Create an account at', $seite);
        $this->assertStringContainsString('Websites → your site → Embedding', $seite);
        $this->assertStringContainsString('Create token', $seite);
        $this->assertStringContainsString('the token is shown this one time only', $seite);

        // Und was danach kommt.
        $this->assertStringContainsString('verify the domain', $seite);
    }

    /**
     * Die eigene Adresse steht in der Anleitung.
     *
     * Wird beim Dienst eine andere Adresse hinterlegt, schlaegt spaeter der
     * Domain-Nachweis fehl - und niemand sieht, warum.
     */
    public function test_die_anleitung_nennt_die_adresse_dieser_installation(): void
    {
        $this->assertStringContainsString('https://kundin.test/', $this->unverbundeneSeite());
    }

    /**
     * Der Verweis folgt der eingetragenen Adresse des Dienstes.
     *
     * Wer eine eigene Instanz betreibt, soll nicht auf barrierepruefung.de
     * geschickt werden.
     */
    public function test_der_verweis_aufs_konto_folgt_der_adresse_des_dienstes(): void
    {
        $seite = $this->unverbundeneSeite();

        $this->assertStringContainsString('https://beispiel.test/register', $seite);
        $this->assertStringNotContainsString('https://barrierepruefung.de/register', $seite);
    }

    /**
     * Der Weg zur Seite aus der Plugin-Liste.
     *
     * Ohne ihn steht neben dem Plugin nur "Deaktivieren", und die Seite unter
     * Werkzeuge findet nur, wer weiss, dass es sie gibt. Die Aufschrift ist
     * dieselbe wie bei jedem anderen Plugin - danach wird gesucht.
     */
    public function test_die_plugin_liste_fuehrt_zur_seite(): void
    {
        $verweise = $this->admin->aktionsverweise(['deactivate' => '<a href="#">Deactivate</a>']);

        $this->assertCount(2, $verweise);
        $this->assertStringContainsString('tools.php?page=a11y-checker', $verweise[0]);
        $this->assertStringContainsString('Settings', $verweise[0]);
    }

    /** Auch ohne Verbindung - sonst fehlt er genau dann, wenn er gebraucht wird. */
    public function test_die_plugin_liste_fuehrt_auch_ohne_verbindung_zur_seite(): void
    {
        unset($GLOBALS['wp_options']['a11y_checker_token'], $GLOBALS['wp_options']['a11y_checker_site_id']);

        $verweise = $this->admin->aktionsverweise([]);

        $this->assertStringContainsString('tools.php?page=a11y-checker', $verweise[0]);
        $this->assertStringContainsString('Settings', $verweise[0]);
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

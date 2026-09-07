<?php

use PHPUnit\Framework\TestCase;

/**
 * Die Adresse des Kontos wird aus der Adresse der API abgeleitet.
 *
 * Sie traegt die Anleitung beim ersten Einrichten. Zeigte sie fest auf
 * barrierepruefung.de, schickte sie jede eigene Instanz an die falsche Stelle.
 */
final class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_options'] = [];
    }

    public function test_die_kontoadresse_schneidet_den_api_teil_ab(): void
    {
        $GLOBALS['wp_options']['a11y_checker_api_url'] = 'https://beispiel.test/api/v1';

        $client = new A11y_Checker_Client;

        $this->assertSame('https://beispiel.test', $client->account_url());
        $this->assertSame('https://beispiel.test/register', $client->account_url('register'));
    }

    /** Eine Instanz in einem Unterverzeichnis behaelt ihren Pfad. */
    public function test_die_kontoadresse_behaelt_ein_unterverzeichnis(): void
    {
        $GLOBALS['wp_options']['a11y_checker_api_url'] = 'https://beispiel.test/pruefer/api/v1';

        $this->assertSame(
            'https://beispiel.test/pruefer/register',
            (new A11y_Checker_Client)->account_url('register')
        );
    }

    /** Ohne eingetragene Adresse gilt der Standard. */
    public function test_ohne_eintrag_zeigt_die_kontoadresse_auf_den_dienst(): void
    {
        $this->assertSame(
            'https://barrierepruefung.de/register',
            (new A11y_Checker_Client)->account_url('register')
        );
    }

    /**
     * Steht dort etwas, das keine Adresse ist, faellt es auf den Standard
     * zurueck: ein Verweis ins Leere waere schlimmer als einer auf den Dienst,
     * fuer den dieses Plugin geschrieben ist.
     */
    public function test_eine_unbrauchbare_adresse_faellt_auf_den_standard_zurueck(): void
    {
        $GLOBALS['wp_options']['a11y_checker_api_url'] = 'kaputt';

        $this->assertSame(
            'https://barrierepruefung.de/register',
            (new A11y_Checker_Client)->account_url('register')
        );
    }
}

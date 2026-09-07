<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Zugang zur Public API v1.
 *
 * Nutzt ausschließlich wp_remote_* — eigene HTTP-Bibliotheken sind im
 * Plugin-Verzeichnis nicht zugelassen und wären hier auch überflüssig.
 */
class Barrierepruefung_Client
{
    public const DEFAULT_API = 'https://barrierepruefung.de/api/v1';

    public function is_connected(): bool
    {
        return $this->token() !== '' && $this->site_id() !== '';
    }

    public function token(): string
    {
        return (string) get_option('barrierepruefung_token', '');
    }

    public function site_id(): string
    {
        return (string) get_option('barrierepruefung_site_id', '');
    }

    public function api_url(): string
    {
        // Der zweite Parameter von get_option greift nur, wenn die Option
        // fehlt - nicht, wenn sie leer ist. Genau das hinterlaesst eine
        // Adresse, die esc_url_raw() verworfen hat, und jede Anfrage ginge
        // danach an einen relativen Pfad.
        $gespeichert = trim((string) get_option('barrierepruefung_api_url', ''));

        return untrailingslashit($gespeichert !== '' ? $gespeichert : self::DEFAULT_API);
    }

    /**
     * Eine Adresse im Konto beim Dienst, abgeleitet aus der Adresse der API.
     *
     * Die Anleitung beim Verbinden muss auf Registrierung und Website-Liste
     * zeigen koennen. Ein fest eingetragenes barrierepruefung.de waere dort
     * falsch, sobald jemand eine andere Adresse eingetragen hat - eine
     * Testinstanz etwa. Abgeschnitten wird deshalb nur der API-Teil des Pfades.
     *
     * Steht in der Option etwas, das gar keine Adresse ist, faellt es auf den
     * Standard zurueck: ein Verweis ins Leere waere schlimmer als einer auf den
     * Dienst, fuer den dieses Plugin geschrieben ist.
     */
    public function account_url(string $pfad = ''): string
    {
        $basis = (string) preg_replace('#/api(/v[0-9]+)?/?$#', '', $this->api_url());

        if (! preg_match('#^https?://[^/]#i', $basis)) {
            $basis = (string) preg_replace('#/api(/v[0-9]+)?/?$#', '', self::DEFAULT_API);
        }

        $basis = untrailingslashit($basis);

        return $pfad === '' ? $basis : $basis.'/'.ltrim($pfad, '/');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, error: string|null}
     */
    public function get(string $pfad, array $headers = []): array
    {
        return $this->request('GET', $pfad, null, $headers);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, error: string|null}
     */
    public function post(string $pfad, array $body = []): array
    {
        return $this->request('POST', $pfad, $body);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, error: string|null}
     */
    private function request(string $methode, string $pfad, ?array $body, array $headers = []): array
    {
        if (! $this->is_connected()) {
            return $this->fehler(0, __('The plugin is not connected to an account yet.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        $argumente = [
            'method' => $methode,
            'timeout' => 20,
            'headers' => array_merge([
                'Authorization' => 'Bearer '.$this->token(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $headers),
        ];

        if ($body !== null) {
            $argumente['body'] = wp_json_encode($body);
        }

        $antwort = wp_remote_request($this->api_url().$pfad, $argumente);

        if (is_wp_error($antwort)) {
            return $this->fehler(0, $antwort->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($antwort);
        $daten = json_decode((string) wp_remote_retrieve_body($antwort), true);
        $daten = is_array($daten) ? $daten : [];

        if ($status >= 400) {
            // Fehler kommen als problem+json; „detail" ist der lesbare Teil.
            return $this->fehler($status, $daten['detail'] ?? $daten['title'] ?? __('Unknown error.', 'barrierepruefung-de-web-accessibility-checker'));
        }

        return ['ok' => true, 'status' => $status, 'data' => $daten, 'error' => null];
    }

    private function fehler(int $status, string $meldung): array
    {
        return ['ok' => false, 'status' => $status, 'data' => [], 'error' => $meldung];
    }
}

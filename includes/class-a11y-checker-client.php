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
class A11y_Checker_Client
{
    public const DEFAULT_API = 'https://app.example.org/api/v1';

    public function is_connected(): bool
    {
        return $this->token() !== '' && $this->site_id() !== '';
    }

    public function token(): string
    {
        return (string) get_option('a11y_checker_token', '');
    }

    public function site_id(): string
    {
        return (string) get_option('a11y_checker_site_id', '');
    }

    public function api_url(): string
    {
        return untrailingslashit((string) get_option('a11y_checker_api_url', self::DEFAULT_API));
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
            return $this->fehler(0, __('Das Plugin ist noch nicht mit einem Konto verbunden.', 'a11y-checker'));
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
            return $this->fehler($status, $daten['detail'] ?? $daten['title'] ?? __('Unbekannter Fehler.', 'a11y-checker'));
        }

        return ['ok' => true, 'status' => $status, 'data' => $daten, 'error' => null];
    }

    private function fehler(int $status, string $meldung): array
    {
        return ['ok' => false, 'status' => $status, 'data' => [], 'error' => $meldung];
    }
}

<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AcadepAuraService
{
    private Client $client;
    private string $endpoint;
    private string $apiKey;

    public function __construct()
    {
        $this->endpoint = rtrim(
            env('ACADEP_AURA_URL_LAN', 'http://192.168.1.224:8080/api/v2/aura/gateway'),
            '/'
        );
        $this->apiKey = env('ACADEP_AURA_KEY', '');

        // Timeout 35s — el backend Go usa 30s internos; 5s de margen de red
        $this->client = new Client([
            'timeout'         => 35,
            'connect_timeout' => 5,
            'http_errors'     => false,  // manejamos códigos HTTP manualmente
        ]);
    }

    /**
     * Envía un prompt al AURA Gateway y devuelve la respuesta estructurada.
     *
     * @param  string  $prompt    Texto sanitizado, sin PII, sin datos sensibles
     * @param  string  $agentId   Identificador del agente (puede ser externo)
     * @return array{response:string, engine:string, model:string,
     *               tokensUsed:int, tokensRemaining:int,
     *               latencyMs:int, sessionId:string}
     * @throws \RuntimeException  En llave inválida, CAPEX agotado o error de red
     */
    public function dispatch(string $prompt, string $agentId = 'AURA_BKC_V1'): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException(
                '[ACADEP] ACADEP_AURA_KEY no está configurada en .env'
            );
        }

        $userSession = $this->generateSessionId();

        // Payload exacto esperado por el handler Go (handler_gateway.go)
        $payload = [
            'agent_id'     => $agentId,
            'user_session' => $userSession,
            'prompt'       => $prompt,
        ];

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'X-AURA-KEY'   => $this->apiKey,   // llave cruda en texto plano
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (ConnectException $e) {
            Log::error('[ACADEP] Gateway inaccesible', [
                'endpoint' => $this->endpoint,
                'error'    => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                '[ACADEP] No se pudo conectar al AURA Gateway LAN. Verificar que el binario esté corriendo.'
            );
        } catch (RequestException $e) {
            Log::error('[ACADEP] Error de transporte Guzzle', ['error' => $e->getMessage()]);
            throw new \RuntimeException('[ACADEP] Error de transporte HTTP: ' . $e->getMessage());
        }

        $statusCode = $response->getStatusCode();
        $body       = json_decode((string) $response->getBody(), true) ?? [];

        // ── Mapeo exacto de códigos HTTP del contrato Go ──────────────────────
        match ($statusCode) {
            401 => (function () use ($userSession) {
                Log::critical('[ACADEP] X-AURA-KEY inválida', ['session' => $userSession]);
                throw new \RuntimeException(
                    '[ACADEP] Llave de acceso rechazada. Contactar al administrador ACADEP.'
                );
            })(),
            402 => (function () use ($userSession) {
                Log::warning('[ACADEP] CAPEX de tokens agotado', ['session' => $userSession]);
                throw new \RuntimeException(
                    '[ACADEP] Cuota de procesamiento agotada. Renovar CAPEX con administrador ACADEP.'
                );
            })(),
            500 => (function () use ($body) {
                Log::error('[ACADEP] Error interno del Gateway', ['body' => $body]);
                throw new \RuntimeException('[ACADEP] Error interno del motor de inferencia. Reintenta en unos segundos.');
            })(),
            default => null,
        };

        if ($statusCode !== 200) {
            throw new \RuntimeException("[ACADEP] Respuesta inesperada del Gateway (HTTP {$statusCode}).");
        }

        $data = $body['data'] ?? [];

        // Alerta proactiva: menos del 10% de cuota restante
        if (isset($data['tokensRemaining']) && isset($data['tokensUsed'])) {
            $total = $data['tokensRemaining'] + $data['tokensUsed'];
            if ($total > 0 && ($data['tokensRemaining'] / $total) < 0.10) {
                Log::warning('[ACADEP] CAPEX al límite crítico (<10%)', [
                    'remaining' => $data['tokensRemaining'],
                    'tenant'    => $data['tenantName'] ?? '?',
                ]);
            }
        }

        return [
            'response'        => $data['response']        ?? '',
            'engine'          => $data['engine']          ?? 'unknown',
            'model'           => $data['model']           ?? '',
            'tokensUsed'      => (int) ($data['tokensUsed']      ?? 0),
            'tokensRemaining' => (int) ($data['tokensRemaining'] ?? 0),
            'latencyMs'       => (int) ($data['latencyMs']       ?? 0),
            'sessionId'       => $userSession,
        ];
    }

    /**
     * Genera un ID de sesión único por solicitud.
     * Formato: sess_{unix_timestamp}_{hex_6_chars}
     */
    private function generateSessionId(): string
    {
        return 'sess_' . time() . '_' . bin2hex(random_bytes(3));
    }
}

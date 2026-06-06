<?php

namespace App\Services\Providers;

use App\Services\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * AcadepProvider — Adaptador para el Nodo Soberano de IA Local (Protocolo OPEN KEY)
 *
 * Integra el Servidor Central ACADEP en la escalera de failover de AIService.
 * Registrado en AIService::ADAPTERS bajo la clave 'acadep'.
 *
 * Configuración en ai_settings (tabla):
 *   api_key      → X-AURA-KEY cifrada con Crypt::encryptString()
 *   extra_config → { "endpoint": "http://...", "agent_id": "AURA_BKC_V1", "timeout": 45 }
 *
 * Contrato de autenticación (Protocolo OPEN KEY):
 *   Header: X-AURA-KEY → llave desencriptada en runtime
 *
 * Payload enviado al servidor ACADEP:
 *   { "agent_id": "...", "user_session": "...", "prompt": "..." }
 *
 * Respuesta esperada del servidor ACADEP (se soportan ambos formatos):
 *   Formato A: { "success": true, "response": "HTML", "tokens_used": int }
 *   Formato B: { "status": "success", "transaction_id": "...", "payload": { "report_html": "..." } }
 */
class AcadepProvider implements AIProviderInterface
{
    const NAME         = 'acadep';
    const DEFAULT_TIMEOUT   = 45;
    const DEFAULT_AGENT_ID  = 'AURA_BKC_V1';

    public function request(array $payload, array $config): array
    {
        $start = microtime(true);

        $extra    = $config['extra_config'] ?? [];
        $endpoint = rtrim($extra['endpoint'] ?? '', '/');
        $agentId  = $extra['agent_id']      ?? self::DEFAULT_AGENT_ID;
        $timeout  = (int) ($extra['timeout'] ?? self::DEFAULT_TIMEOUT);
        $auraKey  = $config['api_key']       ?? '';

        if (empty($endpoint)) {
            return [
                'status'      => 'error',
                'provider'    => self::NAME,
                'response'    => '',
                'tokens_used' => 0,
                'latency_ms'  => 0,
                'error'       => 'ACADEP: endpoint no configurado en extra_config.',
            ];
        }

        // Extraer el prompt del formato estándar de AIService (array de mensajes)
        $fullPrompt = $this->buildPromptFromMessages($payload['messages'] ?? []);

        // user_session: AIService no pasa sesión — usamos un hash anónimo trazable
        $userSession = sha1(implode('|', array_column($payload['messages'] ?? [], 'content')));

        try {
            $client = new Client(['timeout' => $timeout]);

            $response = $client->post($endpoint, [
                'headers' => [
                    'X-AURA-KEY'   => $auraKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'agent_id'     => $agentId,
                    'user_session' => $userSession,
                    'prompt'       => $fullPrompt,
                ],
            ]);

            $latency = (int) round((microtime(true) - $start) * 1000);
            $body    = json_decode($response->getBody()->getContents(), true);

            if (!is_array($body)) {
                return [
                    'status'      => 'error',
                    'provider'    => self::NAME,
                    'response'    => '',
                    'tokens_used' => 0,
                    'latency_ms'  => $latency,
                    'error'       => 'ACADEP: respuesta no es JSON válido.',
                ];
            }

            // Soportar Formato A y Formato B simultáneamente
            $reportHtml  = $this->extractReport($body);
            $tokensUsed  = (int) ($body['tokens_used'] ?? $body['tokens'] ?? 0);
            $isSuccess   = $this->isSuccessResponse($body);

            if (!$isSuccess || $reportHtml === '') {
                $errorMsg = $body['error'] ?? $body['message'] ?? 'ACADEP: respuesta inválida o vacía.';
                return [
                    'status'      => 'error',
                    'provider'    => self::NAME,
                    'response'    => '',
                    'tokens_used' => 0,
                    'latency_ms'  => $latency,
                    'error'       => $errorMsg,
                ];
            }

            return [
                'status'      => 'ok',
                'provider'    => self::NAME,
                'response'    => $reportHtml,
                'tokens_used' => $tokensUsed,
                'latency_ms'  => $latency,
            ];

        } catch (ConnectException $e) {
            return [
                'status'      => 'error',
                'provider'    => self::NAME,
                'response'    => '',
                'tokens_used' => 0,
                'latency_ms'  => (int) round((microtime(true) - $start) * 1000),
                'error'       => 'ACADEP: no se pudo conectar con el servidor central.',
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

            // HTTP 402 = CAPEX agotado / cuota excedida
            if ($statusCode === 402) {
                return [
                    'status'      => 'error',
                    'provider'    => self::NAME,
                    'response'    => '',
                    'tokens_used' => 0,
                    'latency_ms'  => (int) round((microtime(true) - $start) * 1000),
                    'error'       => 'ACADEP: CAPEX agotado o cuota de tokens excedida (HTTP 402).',
                ];
            }

            return [
                'status'      => 'error',
                'provider'    => self::NAME,
                'response'    => '',
                'tokens_used' => 0,
                'latency_ms'  => (int) round((microtime(true) - $start) * 1000),
                'error'       => "ACADEP: error HTTP {$statusCode} del servidor central.",
            ];
        }
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    /**
     * Construye un prompt lineal a partir del formato de mensajes de AIService.
     * ACADEP usa un único campo "prompt" en vez de array messages.
     */
    private function buildPromptFromMessages(array $messages): string
    {
        $parts = [];
        foreach ($messages as $msg) {
            $role    = strtoupper($msg['role'] ?? 'USER');
            $content = $msg['content'] ?? '';
            if ($content !== '') {
                $parts[] = "[{$role}]\n{$content}";
            }
        }
        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Extrae el HTML del reporte de cualquiera de los dos formatos de respuesta.
     * Formato A: { "response": "HTML..." }
     * Formato B: { "payload": { "report_html": "HTML..." } }
     */
    private function extractReport(array $body): string
    {
        // Formato B (prioritario si existe)
        if (isset($body['payload']['report_html'])) {
            return (string) $body['payload']['report_html'];
        }
        // Formato A
        if (isset($body['response'])) {
            return (string) $body['response'];
        }
        return '';
    }

    /**
     * Determina si la respuesta del servidor es exitosa, soportando ambos formatos.
     */
    private function isSuccessResponse(array $body): bool
    {
        // Formato B: { "status": "success" }
        if (isset($body['status'])) {
            return $body['status'] === 'success';
        }
        // Formato A: { "success": true }
        if (isset($body['success'])) {
            return (bool) $body['success'];
        }
        return false;
    }
}

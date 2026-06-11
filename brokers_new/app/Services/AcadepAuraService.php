<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * AcadepAuraService — Cliente HTTP para el Nodo Soberano de IA (Protocolo OPEN KEY)
 *
 * Despacho híbrido LAN/WAN de dos pasos:
 *   Paso A1 — LAN (ACADEP_AURA_URL_LAN): connect_timeout 1.5s.
 *              Resuelve en microsegundos cuando el servidor está en la misma red local.
 *   Paso A2 — WAN (ACADEP_AURA_URL_WAN): IPv6 global, timeout 45s.
 *              Failover silencioso si LAN no responde (ConnectException / timeout).
 *
 * Códigos de retorno del nodo central:
 *   HTTP 200 + success:true  → ok     (respuesta válida)
 *   HTTP 200 + success:false → blocked (CAPEX / cuota agotada — congela DOM en frontend)
 *   HTTP 401                 → error  (llave inválida o revocada)
 *   HTTP 402                 → blocked (CAPEX agotado — tratado igual que blocked)
 *   HTTP 500 / ConnectException WAN → error (Capa B toma el control)
 *
 * Contrato de autenticación (Protocolo OPEN KEY):
 *   Header: X-AURA-KEY → env('ACADEP_AURA_KEY')
 *
 * Payload enviado:
 *   { "agent_id": "AURA_BKC_V1", "user_session": string, "prompt": string }
 *
 * Respuesta soportada (Formato A y B simultáneamente):
 *   Formato A: { "success": true, "response": "HTML", "tokens_used": int }
 *   Formato B: { "status": "success", "transaction_id": "...", "payload": { "report_html": "..." } }
 */
class AcadepAuraService
{
    private const AGENT_ID             = 'AURA_BKC_V1';
    private const LAN_CONNECT_TIMEOUT  = 1.5; // falla rápido si LAN no responde
    private const WAN_CONNECT_TIMEOUT  = 5;   // máx 5s de conexión WAN — conmuta a Capa B si excede
    private const WAN_RESPONSE_TIMEOUT = 45;  // una vez conectado, esperar hasta 45s la respuesta

    // ── Mapa de errores HTTP del nodo central ────────────────────────────────
    private const HTTP_ERRORS = [
        401 => 'X-AURA-KEY inválida o revocada (HTTP 401). Verifica la llave en el panel ACADEP.',
        402 => 'CAPEX de tokens agotado en el ledger central (HTTP 402). Recarga tokens en ACADEP.',
        403 => 'Acceso denegado (HTTP 403). El agent_id no está autorizado en este nodo.',
        500 => 'Error interno del servidor ACADEP (HTTP 500). Reportar al equipo central.',
    ];

    /**
     * Verifica si el nodo ACADEP está configurado con al menos una URL válida.
     *
     * ⚠️  REGLA DE SEGURIDAD PERIMETRAL — INMUTABILIDAD DE CREDENCIALES:
     *     Las credenciales de ACADEP se leen EXCLUSIVAMENTE desde las variables
     *     de entorno del servidor (.env). La tabla `ai_settings` de la BD local
     *     NO almacena, gestiona ni replica estos valores bajo ninguna circunstancia.
     *     Cualquier modificación que intente leer ACADEP_AURA_KEY o ACADEP_AURA_URL_*
     *     desde la BD viola la Ley Suprema y debe ser revertida de inmediato.
     */
    public function isConfigured(): bool
    {
        $lan = env('ACADEP_AURA_URL_LAN', '');
        $wan = env('ACADEP_AURA_URL_WAN', '');
        $key = env('ACADEP_AURA_KEY', '');

        return !empty($key)
            && strpos($key, 'ACADEP-BKC-2026-XYZ') === false  // aún es el placeholder original
            && (!empty($lan) || !empty($wan));
    }

    /**
     * Despacho híbrido LAN → WAN con failover silencioso.
     *
     * Flujo de 2 pasos:
     *   1. Intenta LAN  (ACADEP_AURA_URL_LAN)  con connect_timeout=1.5s.
     *      Si la red LAN responde (cualquier HTTP): retorna sin intentar WAN.
     *      Si ConnectException (red inalcanzable): continúa al paso 2.
     *   2. Intenta WAN  (ACADEP_AURA_URL_WAN)  con connect_timeout=5s.
     *      Si ambas capas fallan por red: retorna status='error' para que
     *      AiAnalyticsController active la Capa B (AIService comercial).
     *
     * ⚠️  CREDENCIALES INMUTABLES — FUENTE .env ÚNICAMENTE:
     *     $auraKey  → env('ACADEP_AURA_KEY')       — NUNCA leer de ai_settings
     *     $urlLan   → env('ACADEP_AURA_URL_LAN')   — NUNCA leer de BD
     *     $urlWan   → env('ACADEP_AURA_URL_WAN')   — NUNCA leer de BD
     *     Modificar estas fuentes sin autorización del Lead Architect viola
     *     la Ley Suprema y expone la seguridad perimetral del nodo soberano.
     *
     * @param  string $prompt    Prompt completo (system + user concatenados)
     * @param  string $agentId   Identificador del agente (default: AURA_BKC_V1)
     * @return array{
     *   status: 'ok'|'blocked'|'error'|'_lan_unreachable',
     *   response: string,
     *   tokens_used: int,
     *   latency_ms: int,
     *   network_layer: 'lan'|'wan'|'none',
     *   error?: string,
     *   transaction_id?: string
     * }
     */
    public function dispatch(string $prompt, string $agentId = self::AGENT_ID): array
    {
        $auraKey     = env('ACADEP_AURA_KEY', '');
        $urlLan      = rtrim(env('ACADEP_AURA_URL_LAN', ''), '/');
        $urlWan      = rtrim(env('ACADEP_AURA_URL_WAN', ''), '/');
        $globalStart = microtime(true);

        // Sesión anónima trazable — auto-generada en base al contenido del prompt
        $sessionToken = 'aiva_' . substr(sha1($prompt . microtime()), 0, 16);

        $payload = [
            'agent_id'     => $agentId,
            'user_session' => $sessionToken,
            'prompt'       => $prompt,
        ];

        // ════════════════════════════════════════════════════════════
        // PASO A1 — LAN (red local, connect_timeout 1.5 s)
        // ════════════════════════════════════════════════════════════
        if (!empty($urlLan)) {
            $result = $this->sendRequest($urlLan, $auraKey, $payload, [
                'connect_timeout' => self::LAN_CONNECT_TIMEOUT,
                'timeout'         => self::WAN_RESPONSE_TIMEOUT,
            ], $globalStart, 'lan');

            // LAN respondió (ok, blocked o error HTTP): retornar directamente.
            // Solo hacemos failover WAN si fue un error de red (ConnectException).
            if ($result['status'] !== '_lan_unreachable') {
                return $result;
            }

            Log::info('[AcadepAura] LAN no disponible — activando failover WAN', [
                'lan_url' => $urlLan,
            ]);
        }

        // ════════════════════════════════════════════════════════════
        // PASO A2 — WAN / IPv6 Global (failover silencioso)
        // connect_timeout=5s: si no hay respuesta de red en 5s,
        // ConnectException obliga al loop de AIService a conmutar
        // en caliente a la Capa B (proveedores comerciales).
        // ════════════════════════════════════════════════════════════
        if (!empty($urlWan)) {
            $result = $this->sendRequest($urlWan, $auraKey, $payload, [
                'connect_timeout' => self::WAN_CONNECT_TIMEOUT,
                'timeout'         => self::WAN_RESPONSE_TIMEOUT,
            ], $globalStart, 'wan');

            if ($result['status'] !== '_lan_unreachable') {
                return $result;
            }
        }

        // Ambas capas fallaron por red — Capa B (AIService) tomará el control
        Log::warning('[AcadepAura] Ambas capas de red (LAN + WAN) inaccesibles');
        return [
            'status'        => 'error',
            'response'      => '',
            'tokens_used'   => 0,
            'latency_ms'    => (int) round((microtime(true) - $globalStart) * 1000),
            'network_layer' => 'none',
            'error'         => 'Nodo ACADEP inaccesible en LAN y WAN. Activando proveedores de respaldo.',
        ];
    }

    // ── Helper de envío HTTP ────────────────────────────────────────────────

    /**
     * Realiza el POST al endpoint indicado y normaliza la respuesta.
     * Retorna '_lan_unreachable' como status solo cuando la red es inalcanzable
     * (ConnectException) — señal interna para activar el failover al siguiente endpoint.
     */
    private function sendRequest(
        string $url,
        string $auraKey,
        array  $payload,
        array  $guzzleOptions,
        float  $globalStart,
        string $layer
    ): array {
        try {
            $client   = new Client($guzzleOptions);
            $response = $client->post($url, [
                'headers' => [
                    'X-AURA-KEY'   => $auraKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);

            $latency     = (int) round((microtime(true) - $globalStart) * 1000);
            // ── Capturador centinela: leer el stream una sola vez ─────────
            // getContents() consume el stream; toda la lógica posterior opera
            // sobre $rawBody (string) en lugar de volver a leer el stream.
            $httpStatus  = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');
            $rawBody     = $response->getBody()->getContents();
            $body        = json_decode($rawBody, true);

            if (!is_array($body)) {
                Log::warning("[AcadepAura:{$layer}] Respuesta no es JSON válido", [
                    'http_status'  => $httpStatus,
                    'content_type' => $contentType,
                    'raw_preview'  => mb_substr($rawBody, 0, 300),
                ]);
                return [
                    'status'        => 'error',
                    'response'      => '',
                    'tokens_used'   => 0,
                    'latency_ms'    => $latency,
                    'network_layer' => $layer,
                    'error'         => "El nodo ACADEP respondió con contenido no JSON (HTTP {$httpStatus}).",
                    'http_status'   => $httpStatus,
                    'content_type'  => $contentType,
                    'raw_response'  => mb_substr($rawBody, 0, 1000),
                ];
            }

            // ── Detectar éxito en Formato A y Formato B ──────────────────
            $isSuccess  = $this->isSuccessResponse($body);
            $reportHtml = $this->extractReport($body);
            $tokensUsed = (int) ($body['tokens_used'] ?? $body['tokens'] ?? 0);

            // ── success: false en el body (CAPEX / cuota) ────────────────
            if (!$isSuccess) {
                $errorMsg = $body['error'] ?? $body['message'] ?? 'El nodo ACADEP rechazó la solicitud.';
                Log::warning("[AcadepAura:{$layer}] Servidor respondió success:false", [
                    'error'      => $errorMsg,
                    'latency_ms' => $latency,
                ]);
                return [
                    'status'        => 'blocked',
                    'response'      => '',
                    'tokens_used'   => 0,
                    'latency_ms'    => $latency,
                    'network_layer' => $layer,
                    'error'         => $errorMsg,
                    'http_status'   => $httpStatus,
                    'content_type'  => $contentType,
                    'raw_response'  => mb_substr($rawBody, 0, 1000),
                ];
            }

            Log::info("[AcadepAura:{$layer}] Respuesta exitosa", [
                'tokens_used' => $tokensUsed,
                'latency_ms'  => $latency,
            ]);

            return [
                'status'        => 'ok',
                'response'      => $reportHtml,
                'tokens_used'   => $tokensUsed,
                'latency_ms'    => $latency,
                'network_layer' => $layer,
                'transaction_id' => $body['transaction_id'] ?? null,
            ];

        } catch (ConnectException $e) {
            // Red inalcanzable — señal de failover interno (no exponer al cliente)
            Log::info("[AcadepAura:{$layer}] Red inalcanzable — activando failover", [
                'url' => $url,
            ]);
            return ['status' => '_lan_unreachable', 'response' => '', 'tokens_used' => 0,
                    'latency_ms' => 0, 'network_layer' => $layer];

        } catch (RequestException $e) {
            $statusCode     = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $latency        = (int) round((microtime(true) - $globalStart) * 1000);
            $rawErrBody     = '';
            $errContentType = '';

            // Extraer el body de la respuesta HTTP de error para diagnóstico forense
            if ($e->hasResponse()) {
                try {
                    $errContentType = $e->getResponse()->getHeaderLine('Content-Type');
                    $rawErrBody     = mb_substr((string) $e->getResponse()->getBody(), 0, 1000);
                } catch (\Throwable $_) {}
            }

            Log::warning("[AcadepAura:{$layer}] Error HTTP {$statusCode}", [
                'url'          => $url,
                'content_type' => $errContentType,
                'raw_preview'  => mb_substr($rawErrBody, 0, 200),
            ]);

            // HTTP 402 = CAPEX agotado → blocked (congela DOM)
            $resolvedStatus = ($statusCode === 402) ? 'blocked' : 'error';
            $errorMsg       = self::HTTP_ERRORS[$statusCode]
                ?? "Nodo ACADEP respondió HTTP {$statusCode}.";

            return [
                'status'        => $resolvedStatus,
                'response'      => '',
                'tokens_used'   => 0,
                'latency_ms'    => $latency,
                'network_layer' => $layer,
                'error'         => $errorMsg,
                'http_status'   => $statusCode,
                'content_type'  => $errContentType,
                'raw_response'  => $rawErrBody,
            ];
        }
    }

    // ── Helpers de parseo de respuesta ──────────────────────────────────────

    private function isSuccessResponse(array $body): bool
    {
        if (isset($body['status'])) { return $body['status'] === 'success'; }
        if (isset($body['success'])) { return (bool) $body['success']; }
        return false;
    }

    private function extractReport(array $body): string
    {
        // Formato B: { "payload": { "report_html": "..." } }
        if (isset($body['payload']['report_html'])) {
            return (string) $body['payload']['report_html'];
        }
        // Formato A: { "response": "..." }
        return (string) ($body['response'] ?? '');
    }
}

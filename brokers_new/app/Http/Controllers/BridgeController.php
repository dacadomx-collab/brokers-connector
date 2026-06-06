<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * BridgeController — Puente de autenticación Legacy → V2
 *
 * Genera tokens de un solo uso (TTL 300 s) que permiten al sistema Legacy
 * transferir la identidad del usuario autenticado hacia los módulos V2
 * sin compartir la sesión PHP ni cookies entre sistemas.
 *
 * Arquitectura: Strangler Fig Pattern (ver knowledge/04_ARQUITECTURA_V2.md)
 *
 * Resolución adaptativa de URLs (resolveFrontendBase / resolveApiBase):
 *   - Si la variable de entorno está configurada → se usa directamente (producción/staging).
 *   - Si no → se infiere desde url('/') (APP_URL) con corrección para XAMPP local.
 */
class BridgeController extends Controller
{
    /**
     * Genera un token de puente y redirige al módulo V2 de Suscripciones.
     *
     * GET /home/v2/subscription-bridge
     * Middleware: auth (sesión Legacy)
     */
    public function subscriptionBridge(Request $request)
    {
        $token        = $this->generateBridgeToken();
        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/subscriptions/index.html?token=' . $token
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    /**
     * Genera bridge token y redirige al Motor de Tasación Broker Brain IA (V2 SPA).
     *
     * GET /home/v2/broker-brain-bridge
     * Middleware: auth + company + companyPayment
     */
    public function brokerBrainBridge(Request $request)
    {
        $token        = $this->generateBridgeToken();
        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/broker-brain/index.html?token=' . $token
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    /**
     * Genera bridge token y redirige al AI Hub — Dashboard central del Broker Brain IA.
     *
     * GET /home/v2/ai-hub-bridge
     * Middleware: auth + company + companyPayment
     */
    public function aiHubBridge(Request $request)
    {
        $token        = $this->generateBridgeToken();
        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/ai-hub/index.html?token=' . $token
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    /**
     * Genera bridge token y redirige al módulo BrokerPulse AI (V2 SPA).
     * TTL reducido a 60 s — el reporte se genera en segundos; ventana corta minimiza superficie de ataque.
     *
     * GET /home/v2/analytics-bridge
     * Middleware: auth + company + companyPayment
     */
    public function analyticsBridge(Request $request)
    {
        $user = auth()->user();

        $token = Str::random(64);

        Cache::put('v2_bridge_' . $token, [
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'created_at' => now()->timestamp,
        ], 60);

        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/analytics/index.html?token=' . $token
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    /**
     * Genera bridge token y redirige al Radar de Plusvalía (V2 SPA).
     *
     * GET /home/v2/radar-bridge
     * Middleware: auth + company + companyPayment
     */
    public function radarBridge(Request $request)
    {
        $token        = $this->generateBridgeToken();
        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/radar/index.html?token=' . $token
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    /**
     * Genera un Personal Access Token de Passport y redirige al panel Super Admin V2.
     * Arquitectura: OAuth2 nativo — sin Cache, sin bridge token efímero.
     *
     * GET /home/v2/admin-bridge
     * Middleware: auth + role:super_admin
     */
    public function adminBridge(Request $request)
    {
        $accessToken = auth()->user()
            ->createToken('V2_SuperAdmin_Session')
            ->accessToken;

        $frontendBase = $this->resolveFrontendBase();
        $apiBase      = $this->resolveApiBase();

        $url = $frontendBase . '/v2/admin/security.html?access_token=' . urlencode($accessToken)
             . '&api=' . urlencode($apiBase);

        return redirect($url);
    }

    // ── Helpers privados ────────────────────────────────────────────────

    /**
     * Resuelve la URL base del frontend SPA (donde viven los archivos HTML/JS/CSS).
     *
     * Lógica:
     *  1. V2_FRONTEND_BASE en .env → se usa directamente.
     *  2. Sin configurar → los SPAs viven en {APP_URL}/newbrokers/
     *     Funciona tanto en localhost/subcarpeta como en dominio raíz de producción.
     *
     * @return string URL sin barra final
     */
    private function resolveFrontendBase(): string
    {
        // config() sobrevive a config:cache; env() directo devuelve null cuando la caché está activa.
        $frontendBase = config('services.v2_frontend_base', env('V2_FRONTEND_BASE', ''));

        if (!$frontendBase) {
            // En local:     url('/') = http://localhost/brokersconnect_dev/public_html
            //               SPAs en: http://localhost/brokersconnect_dev/public_html/newbrokers
            // En producción: url('/') = https://newbrokers.tourfindy.com
            //               SPAs en: https://newbrokers.tourfindy.com/newbrokers
            $frontendBase = rtrim(url('/'), '/') . '/newbrokers';
        }

        return rtrim($frontendBase, '/');
    }

    /**
     * Resuelve la URL base de la API de Laravel para inyectar en el parámetro &api=.
     *
     * Lógica:
     *  1. V2_API_BASE en config (o .env) → se usa directamente.
     *  2. Sin configurar + localhost (XAMPP) → url('/') que apunta a public_html.
     *  3. Sin configurar + otro host → url('/') sin modificación.
     *
     * NOTA: usa config() para que funcione tanto con como sin config:cache activo.
     *
     * @return string URL sin barra final
     */
    private function resolveApiBase(): string
    {
        // config() sobrevive a config:cache; env() directo devuelve null cuando la caché está activa.
        $apiBase = config('services.v2_api_base', env('V2_API_BASE', ''));

        if (!$apiBase) {
            // Fallback seguro: url('/') ya apunta a public_html en este entorno XAMPP
            $apiBase = rtrim(url('/'), '/');
        }

        return rtrim($apiBase, '/');
    }

    /**
     * Genera y almacena en Cache un token de un solo uso con los datos del tenant.
     *
     * @return string Token aleatorio de 64 caracteres
     */
    private function generateBridgeToken(): string
    {
        $user = auth()->user();

        $payload = [
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'created_at' => now()->timestamp,
        ];

        $token = Str::random(64);

        // TTL 300 s — margen para bootstrap de Laravel en entorno lento
        Cache::put('v2_bridge_' . $token, $payload, 300);

        return $token;
    }
}

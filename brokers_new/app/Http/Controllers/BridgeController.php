<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * BridgeController — Puente de autenticación Legacy → V2
 *
 * Genera tokens de un solo uso (TTL 60 s) que permiten al sistema Legacy
 * transferir la identidad del usuario autenticado hacia los módulos V2
 * sin compartir la sesión PHP ni cookies entre sistemas.
 *
 * Arquitectura: Strangler Fig Pattern (ver knowledge/04_ARQUITECTURA_V2.md)
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
        $token = $this->generateBridgeToken();

        // V2_FRONTEND_BASE: raíz de newbrokers/ según el entorno.
        // Vacío en producción (same-origin). En local: URL completa hasta newbrokers/.
        $frontendBase = rtrim(env('V2_FRONTEND_BASE', ''), '/');

        // V2_API_BASE: raíz de la API de Laravel según el entorno.
        // Vacío en producción (same-origin). En local: URL completa hasta public/.
        $apiBase = rtrim(env('V2_API_BASE', ''), '/');

        $url = $frontendBase . '/v2/subscriptions/index.html?token=' . $token;

        // Solo se adjunta si está configurado — el JS lo usa como prefijo de fetch.
        if ($apiBase !== '') {
            $url .= '&api=' . urlencode($apiBase);
        }

        return redirect($url);
    }

    /**
     * Genera y almacena en Cache un token de un solo uso con los datos del tenant.
     *
     * @return string Token aleatorio de 64 caracteres (hex-safe URL)
     */
    private function generateBridgeToken(): string
    {
        $user = auth()->user();

        $payload = [
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'created_at' => now()->timestamp,
        ];

        // Token criptográficamente aleatorio (256 bits de entropía)
        $token = Str::random(64);

        // TTL de 60 segundos — el módulo V2 debe intercambiarlo inmediatamente
        Cache::put('v2_bridge_' . $token, $payload, 60);

        return $token;
    }
}

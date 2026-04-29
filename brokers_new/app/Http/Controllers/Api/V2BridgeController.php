<?php

namespace App\Http\Controllers\Api;

use App\Company;
use App\Http\Controllers\Controller;
use App\Service;
use App\Services\OpenPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * V2BridgeController — Guardia de seguridad del sistema V2
 *
 * validate()   — intercambia el bridge token (60s, single-use) por un session_token (30min)
 * subscribe()  — crea suscripción usando el session_token como Bearer auth
 *
 * Arquitectura: Strangler Fig Pattern (knowledge/04_ARQUITECTURA_V2.md)
 */
class V2BridgeController extends Controller
{
    /**
     * GET /api/v2/bridge/validate?token={TOKEN}
     *
     * Nombre del método: bridgeValidate (no "validate" — colisiona con el trait
     * ValidatesRequests del Controller base; ReflectionMethod toma la firma del trait
     * y el dispatcher crashea antes de entrar al body).
     */
    public function bridgeValidate(Request $request)
    {
        try {

            $token = $request->query('token');

            if (!$token) {
                return response()->json(['success' => false, 'error' => 'Token requerido.'], 400);
            }

            $cacheKey = 'v2_bridge_' . $token;
            $payload  = Cache::get($cacheKey);

            if (!$payload) {
                return response()->json(['success' => false, 'error' => 'Enlace inválido o expirado. Regresa al panel e intenta de nuevo.'], 401);
            }

            // QUEMAR el token — un solo uso, sin posibilidad de replay
            Cache::forget($cacheKey);

            // Emitir session token (30 minutos)
            $sessionToken = Str::random(64);
            Cache::put('v2_session_' . $sessionToken, [
                'user_id'    => $payload['user_id'],
                'company_id' => $payload['company_id'],
            ], 1800);

            $company = Company::find($payload['company_id']);

            if (!$company) {
                return response()->json(['success' => false, 'error' => 'Cuenta no encontrada.'], 404);
            }

            $plans = Service::orderBy('price')
                ->get(['id', 'service', 'price', 'users_included', 'user_price', 'days_trial']);

            return response()->json([
                'success'       => true,
                'session_token' => $sessionToken,
                'company'       => [
                    'id'      => $company->id,
                    'name'    => $company->name,
                    'email'   => $company->email,
                    'package' => $company->package,
                ],
                'plans'   => $plans,
                // Devolver credenciales del entorno activo.
                // El frontend las usa para inicializar OpenPay.js con el par correcto.
                'openpay' => $this->openpayConfig(),
            ]);

        } catch (\Throwable $e) {
            // ── DEBUG PATCH ── expone el error real ignorado por APP_DEBUG=false
            return response()->json([
                '_debug' => [
                    'error' => $e->getMessage(),
                    'class' => get_class($e),
                    'file'  => str_replace(base_path(), '[root]', $e->getFile()),
                    'line'  => $e->getLine(),
                    'trace' => array_map(
                        function ($frame) {
                            return ($frame['file'] ?? '[internal]')
                                . ':' . ($frame['line'] ?? '?')
                                . ' → ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
                        },
                        array_slice($e->getTrace(), 0, 6)
                    ),
                ],
            ], 500);
        }
    }

    /**
     * POST /api/v2/subscriptions
     * Authorization: Bearer {session_token}
     *
     * ⚠ PARCHE DE DEBUG ACTIVO en el wrapper exterior.
     */
    public function subscribe(Request $request, OpenPayService $openPayService)
    {
        try {

            $sessionToken = $this->extractBearerToken($request);

            if (!$sessionToken) {
                return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
            }

            $session = Cache::get('v2_session_' . $sessionToken);

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Sesión expirada. Regresa al panel e intenta de nuevo.',
                ], 401);
            }

            $planMap = [
                1 => env('OPENPAY_PLAN_BASICO',      'plan_basico_mensual'),
                2 => env('OPENPAY_PLAN_PROFESIONAL',  'plan_profesional_mensual'),
                3 => env('OPENPAY_PLAN_ENTERPRISE',   'plan_enterprise_mensual'),
            ];

            $planId   = (int) $request->input('plan_id');
            $tokenId  = $request->input('token_id');
            $deviceId = $request->input('device_id');

            if (!$planId || !isset($planMap[$planId]) || !$tokenId || !$deviceId) {
                return response()->json(['success' => false, 'error' => 'Datos de pago incompletos.'], 422);
            }

            $company = Company::find($session['company_id']);

            if (!$company) {
                return response()->json(['success' => false, 'error' => 'Cuenta no encontrada.'], 404);
            }

            $subscription = $openPayService->createSubscription(
                $company,
                $planMap[$planId],
                $tokenId,
                $deviceId
            );

            $company->openpay_subscription_id = $subscription->id;
            $company->package = $planId;
            $company->save();

            Cache::forget('v2_session_' . $sessionToken);

            return response()->json([
                'success'         => true,
                'message'         => '¡Suscripción activada! Bienvenido a Brokers Connector.',
                'subscription_id' => $subscription->id,
            ]);

        } catch (\OpenpayApiTransactionError $e) {
            return response()->json(['success' => false, 'error' => $this->friendlyCardError($e->getErrorCode())], 422);
        } catch (\OpenpayApiRequestError $e) {
            return response()->json(['success' => false, 'error' => 'Error de configuración del procesador de pagos.'], 500);
        } catch (\OpenpayApiConnectionError $e) {
            return response()->json(['success' => false, 'error' => 'Sin conexión con el procesador de pagos. Intente de nuevo.'], 503);
        } catch (\Throwable $e) {
            // ── DEBUG PATCH ── captura cualquier error no previsto
            return response()->json([
                '_debug' => [
                    'error' => $e->getMessage(),
                    'class' => get_class($e),
                    'file'  => str_replace(base_path(), '[root]', $e->getFile()),
                    'line'  => $e->getLine(),
                    'trace' => array_map(
                        function ($frame) {
                            return ($frame['file'] ?? '[internal]')
                                . ':' . ($frame['line'] ?? '?')
                                . ' → ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
                        },
                        array_slice($e->getTrace(), 0, 6)
                    ),
                ],
            ], 500);
        }
    }

    private function openpayConfig(): array
    {
        $production = (bool) env('OPENPAY_PRODUCTION', false);

        return [
            'id'         => $production ? env('OPENPAY_ID')         : env('OPENPAY_SANDBOX_ID'),
            'public_key' => $production ? env('OPENPAY_KEY_PUBLIC')  : env('OPENPAY_SANDBOX_KEY_PUBLIC'),
            'sandbox'    => !$production,
        ];
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    private function friendlyCardError(int $code): string
    {
        $messages = [
            3001 => 'Tu tarjeta fue rechazada. Verifica los datos o usa otra tarjeta.',
            3002 => 'Tu tarjeta ha vencido.',
            3003 => 'Fondos insuficientes en tu tarjeta.',
            3004 => 'Tu tarjeta fue reportada como robada.',
            3005 => 'Tu tarjeta fue rechazada por el banco.',
            3006 => 'La operación no es permitida para este cliente.',
            3008 => 'Tu tarjeta no está habilitada para pagos en línea.',
            3009 => 'Tu tarjeta fue bloqueada.',
            3010 => 'Tu banco ha restringido la tarjeta.',
            3011 => 'Tarjeta bloqueada por el banco. Comunícate con tu banco para resolverlo.',
        ];
        return $messages[$code] ?? 'Error al procesar el pago (código ' . $code . '). Intente con otra tarjeta.';
    }
}

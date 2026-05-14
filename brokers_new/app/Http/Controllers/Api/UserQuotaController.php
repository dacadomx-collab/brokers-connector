<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * UserQuotaController — Regla de Monetización SaaS 1+1
 *
 * Valida si la empresa del usuario autenticado ya superó el plan base
 * (1 Admin + 1 Agente = 2 usuarios incluidos en $850 MXN).
 * Si se necesita usuario adicional, informa el cargo de $50 MXN/mes.
 *
 * Ruta: GET /api/v2/my-company/user-quota
 * Auth: Bearer Token (Passport auth:api)
 */
class UserQuotaController extends Controller
{
    private const BASE_USERS_INCLUDED = 2;
    private const COST_PER_EXTRA_USER = 50;
    private const BASE_PLAN_PRICE     = 850;
    private const CURRENCY            = 'MXN';

    public function check(Request $request)
    {
        $user    = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'error'   => 'El usuario no tiene empresa asociada.',
            ], 422);
        }

        // Contar usuarios activos del tenant (el GlobalScope asegura aislamiento)
        $activeUsers = $company->users()->where('active', '!=', 0)->count();

        $requiresCharge = $activeUsers >= self::BASE_USERS_INCLUDED;
        $extraCount     = max(0, $activeUsers - self::BASE_USERS_INCLUDED + 1);
        $chargeAmount   = $requiresCharge ? ($extraCount * self::COST_PER_EXTRA_USER) : 0;

        return response()->json([
            'success'          => true,
            'company_id'       => $company->id,
            'company_name'     => $company->name,
            'active_users'     => $activeUsers,
            'base_included'    => self::BASE_USERS_INCLUDED,
            'base_price'       => self::BASE_PLAN_PRICE,
            'requires_charge'  => $requiresCharge,
            'extra_users'      => $extraCount,
            'charge_amount'    => $chargeAmount,
            'currency'         => self::CURRENCY,
            'message'          => $requiresCharge
                ? "Agregar un usuario adicional genera un cargo de $" . self::COST_PER_EXTRA_USER . " " . self::CURRENCY . "/mes. Total: $" . $chargeAmount . " " . self::CURRENCY . "/mes por " . $extraCount . " usuario(s) extra."
                : "Dentro del plan base: hasta " . self::BASE_USERS_INCLUDED . " usuarios incluidos en $" . self::BASE_PLAN_PRICE . " " . self::CURRENCY . "/mes. Sin cargos adicionales.",
        ]);
    }
}

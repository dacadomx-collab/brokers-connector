<?php

namespace App\Http\Middleware;

use Closure;

/**
 * EnsureSuperAdminCompany — Control de Acceso Multi-Empresa
 *
 * Valida que el company_id del usuario autenticado esté incluido
 * en la lista de empresas autorizadas para el Dashboard Super Admin,
 * definida en la variable de entorno SUPER_ADMIN_COMPANY_IDS.
 *
 * Formato en .env:
 *   SUPER_ADMIN_COMPANY_IDS="17,55"
 *
 * Compatible con guard web (sesión) y api (Passport Bearer Token).
 * Se aplica DESPUÉS del middleware role:super_admin — no lo reemplaza.
 *
 * Comportamiento en caso de rechazo:
 *   - Rutas web  → redirect('/home') con error en sesión flash
 *   - Rutas api  → JSON 403
 */
class EnsureSuperAdminCompany
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user() ?? auth('api')->user();

        if (!$user) {
            return $this->deny($request, 'No autenticado.');
        }

        $allowedIds = $this->resolveAllowedIds();

        // Lista vacía → ninguna empresa autorizada (fail-secure)
        if (empty($allowedIds)) {
            return $this->deny($request, 'No hay empresas autorizadas configuradas en el sistema.');
        }

        if (!in_array((int) $user->company_id, $allowedIds, true)) {
            return $this->deny(
                $request,
                'Tu empresa no tiene autorización para acceder al Panel Super Admin.'
            );
        }

        return $next($request);
    }

    /**
     * Lee SUPER_ADMIN_COMPANY_IDS del .env y retorna un array de enteros.
     * Ej: "17,55" → [17, 55]
     */
    private function resolveAllowedIds(): array
    {
        $raw = env('SUPER_ADMIN_COMPANY_IDS', '');

        if (empty(trim($raw))) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    'intval',
                    explode(',', $raw)
                ),
                fn (int $id) => $id > 0
            )
        );
    }

    /**
     * Respuesta de denegación adaptada al tipo de request.
     */
    private function deny($request, string $message)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error'   => $message,
            ], 403);
        }

        return redirect('/home')->with('error', $message);
    }
}

<?php

namespace App\Http\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantUserScope — Mandamiento #2 de Seguridad Militar.
 *
 * Restringe automáticamente todas las consultas al User model
 * al company_id del usuario autenticado.
 *
 * Se omite cuando:
 *  - No hay usuario autenticado (rutas públicas, consola).
 *  - El usuario autenticado tiene rol super_admin.
 *  - El scope ya está resolviendo (evita recursión infinita durante el login).
 */
class TenantUserScope implements Scope
{
    private static bool $resolving = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolving) {
            return;
        }

        try {
            self::$resolving = true;

            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();

            if (!$user || !$user->company_id) {
                return;
            }

            if ($user->hasRole('super_admin')) {
                return;
            }

            $builder->where('users.company_id', $user->company_id);
        } finally {
            self::$resolving = false;
        }
    }
}

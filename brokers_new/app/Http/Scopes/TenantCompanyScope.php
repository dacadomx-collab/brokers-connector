<?php

namespace App\Http\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantCompanyScope — Mandamiento #2 de Seguridad Militar.
 *
 * Restringe las consultas al modelo Company a la empresa
 * del usuario autenticado. Los Super Admins quedan exentos
 * para poder gestionar todas las empresas.
 */
class TenantCompanyScope implements Scope
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

            $builder->where('companies.id', $user->company_id);
        } finally {
            self::$resolving = false;
        }
    }
}

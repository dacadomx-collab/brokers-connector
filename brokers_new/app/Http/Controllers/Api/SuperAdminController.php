<?php

namespace App\Http\Controllers\Api;

use App\AiSetting;
use App\Company;
use App\Http\Controllers\Controller;
use App\Invoice;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SuperAdminController — Panel de gestión de credenciales V2
 *
 * Autenticación: Laravel Passport (auth:api middleware en rutas).
 * Autorización:  Spatie role:super_admin middleware en rutas.
 * El controlador no valida tokens manualmente — Passport lo hace.
 */
class SuperAdminController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT A — GET /api/v2/admin/users
    // Solo retorna usuarios cuyo company_id esté en SUPER_ADMIN_COMPANY_IDS.
    // ══════════════════════════════════════════════════════════════════════════

    public function listAdmins(Request $request)
    {
        $actor          = $request->user();
        $perPage        = min((int) $request->query('per_page', 50), 200);
        $search         = trim($request->query('search', ''));
        $allowedCompIds = $this->resolveAllowedCompanyIds();

        $query = User::withoutGlobalScope(\App\Http\Scopes\TenantUserScope::class)
            ->with('roles')
            ->whereIn('company_id', $allowedCompIds)          // ← FILTRO CRÍTICO
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'super_admin']));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email',     'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage, ['id', 'full_name', 'last_name', 'email', 'company_id', 'active', 'created_at']);

        $data = collect($paginated->items())->map(function (User $u) {
            $roleNames = $u->roles->pluck('name');
            return [
                'id'         => $u->id,
                'full_name'  => trim("{$u->full_name} {$u->last_name}"),
                'email'      => $u->email,
                'company_id' => $u->company_id,
                'active'     => (bool) $u->active,
                'roles'      => $roleNames,
                'is_super'   => $roleNames->contains('super_admin'),
                'created_at' => $u->created_at?->toDateString(),
            ];
        });

        return response()->json([
            'success' => true,
            'actor'   => ['name' => trim("{$actor->full_name} {$actor->last_name}")],
            'data'    => $data,
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT B — POST /api/v2/admin/users/{id}/toggle-role
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleRole(Request $request, int $id)
    {
        $actor          = $request->user();
        $allowedCompIds = $this->resolveAllowedCompanyIds();

        $target = User::withoutGlobalScope(\App\Http\Scopes\TenantUserScope::class)->find($id);

        if (!$target) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        if (!in_array((int) $target->company_id, $allowedCompIds, true)) {
            return response()->json(['success' => false, 'error' => 'No tienes autorización para modificar usuarios de esta empresa.'], 403);
        }

        if ($target->id === $actor->id) {
            return response()->json(['success' => false, 'error' => 'No puedes modificar tu propio rol.'], 422);
        }

        if (!$target->hasRole(['Admin', 'super_admin'])) {
            return response()->json(['success' => false, 'error' => 'Solo se puede gestionar Admins y Super Admins.'], 422);
        }

        if ($target->hasRole('super_admin')) {
            $target->removeRole('super_admin');
            $target->assignRole('Admin');
            $new_role = 'Admin';
        } else {
            $target->removeRole('Admin');
            $target->assignRole('super_admin');
            $new_role = 'super_admin';
        }

        return response()->json([
            'success'  => true,
            'message'  => "Rol actualizado a {$new_role}.",
            'user_id'  => $target->id,
            'new_role' => $new_role,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT C — POST /api/v2/admin/users/{id}/reset-password
    // ══════════════════════════════════════════════════════════════════════════

    public function resetPassword(Request $request, int $id)
    {
        $allowedCompIds = $this->resolveAllowedCompanyIds();

        $target = User::withoutGlobalScope(\App\Http\Scopes\TenantUserScope::class)->find($id);

        if (!$target) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        if (!in_array((int) $target->company_id, $allowedCompIds, true)) {
            return response()->json(['success' => false, 'error' => 'No tienes autorización para resetear credenciales de usuarios de esta empresa.'], 403);
        }

        if (!$target->hasRole(['Admin', 'super_admin'])) {
            return response()->json(['success' => false, 'error' => 'Solo se puede resetear Admins y Super Admins.'], 422);
        }

        $plain = Str::upper(Str::random(4))
               . '-' . Str::upper(Str::random(4))
               . '-' . strtolower(Str::random(4))
               . '-' . rand(1000, 9999);

        $target->password = bcrypt($plain);
        $target->save();

        return response()->json([
            'success'            => true,
            'message'            => 'Contraseña temporal generada. Entrégala al usuario de forma segura.',
            'user_id'            => $target->id,
            'email'              => $target->email,
            'temporary_password' => $plain,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT D — GET /api/v2/admin/ai-settings
    // ══════════════════════════════════════════════════════════════════════════

    public function listAiSettings(Request $request)
    {
        $settings = AiSetting::orderBy('priority_order')->get()->map(function (AiSetting $s) {
            return [
                'id'             => $s->id,
                'provider_name'  => $s->provider_name,
                'api_key_masked' => $this->maskKey($s->api_key),
                'extra_config'   => $s->extra_config,
                'priority_order' => $s->priority_order,
                'is_active'      => $s->is_active,
                'company_id'     => $s->company_id,
            ];
        });

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT E — POST /api/v2/admin/ai-settings
    // ══════════════════════════════════════════════════════════════════════════

    public function storeAiSetting(Request $request)
    {
        $data = $request->validate([
            'provider_name'  => 'required|string|max:50',
            'api_key'        => 'required|string|max:512',
            'extra_config'   => 'nullable|json',
            'priority_order' => 'required|integer|min:1|max:99',
            'is_active'      => 'boolean',
            'company_id'     => 'nullable|integer|exists:companies,id',
        ]);

        $setting = AiSetting::create([
            'provider_name'  => $data['provider_name'],
            'api_key'        => encrypt($data['api_key']),
            'extra_config'   => isset($data['extra_config']) ? json_decode($data['extra_config'], true) : null,
            'priority_order' => $data['priority_order'],
            'is_active'      => $request->boolean('is_active', true),
            'company_id'     => $data['company_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Proveedor registrado.', 'id' => $setting->id], 201);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT F — PUT /api/v2/admin/ai-settings/{id}
    // ══════════════════════════════════════════════════════════════════════════

    public function updateAiSetting(Request $request, int $id)
    {
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        $data = $request->validate([
            'provider_name'  => 'sometimes|string|max:50',
            'api_key'        => 'nullable|string|max:512',
            'extra_config'   => 'nullable|json',
            'priority_order' => 'sometimes|integer|min:1|max:99',
            'is_active'      => 'boolean',
        ]);

        $payload = [];

        if (isset($data['provider_name']))  $payload['provider_name']  = $data['provider_name'];
        if (!empty($data['api_key']))        $payload['api_key']        = encrypt($data['api_key']);
        if (array_key_exists('extra_config', $data))
            $payload['extra_config'] = $data['extra_config'] ? json_decode($data['extra_config'], true) : null;
        if (isset($data['priority_order'])) $payload['priority_order'] = $data['priority_order'];
        if ($request->has('is_active'))     $payload['is_active']      = $request->boolean('is_active');

        $setting->update($payload);

        return response()->json(['success' => true, 'message' => 'Proveedor actualizado.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT G — DELETE /api/v2/admin/ai-settings/{id}
    // ══════════════════════════════════════════════════════════════════════════

    public function destroyAiSetting(Request $request, int $id)
    {
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        $setting->delete();

        return response()->json(['success' => true, 'message' => 'Proveedor eliminado.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT H — PATCH /api/v2/admin/ai-settings/{id}/toggle
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleAiSetting(Request $request, int $id)
    {
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        $setting->is_active = !$setting->is_active;
        $setting->save();

        return response()->json(['success' => true, 'message' => 'Estado actualizado.', 'is_active' => $setting->is_active]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT I — GET /api/v2/admin/companies
    // Lista todas las empresas con estatus de suscripción y último pago.
    // Bypass de TenantCompanyScope: super_admin consulta sin filtro de tenant.
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Mapa de columnas del frontend → campo real de BD (o subquery especial).
     * Claves: data-field del <th> en la SPA.
     * Valor 'raw' → se aplica orderByRaw con subquery; resto → orderBy directo.
     */
    private const COMPANY_SORT_MAP = [
        'id'           => ['type' => 'column', 'col' => 'companies.id'],
        'name'         => ['type' => 'column', 'col' => 'companies.name'],
        'email'        => ['type' => 'column', 'col' => 'companies.email'],
        'package'      => ['type' => 'column', 'col' => 'companies.package'],
        'active'       => ['type' => 'column', 'col' => 'companies.active'],
        'status_label' => ['type' => 'column', 'col' => 'companies.active'],   // activo=1 → Activa
        'created_at'   => ['type' => 'column', 'col' => 'companies.created_at'],
        'last_payment' => [
            'type' => 'raw',
            'sql'  => '(SELECT MAX(i.created_at) FROM invoices i WHERE i.company_id = companies.id AND i.status = 1)',
        ],
        'due_date'     => [
            'type' => 'raw',
            'sql'  => '(SELECT MAX(i.due_date) FROM invoices i WHERE i.company_id = companies.id AND i.status = 1)',
        ],
    ];

    public function listCompanies(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 30), 200);
        $search  = trim($request->query('search', ''));
        $sortKey = $request->query('sort_by', 'id');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Resolve sort definition — unknown key → fallback seguro a id
        $sortDef = self::COMPANY_SORT_MAP[$sortKey] ?? self::COMPANY_SORT_MAP['id'];

        $query = Company::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'phone', 'active', 'package', 'created_at'])
            ->with(['invoices' => function ($q) {
                $q->where('status', 1)->latest()->limit(1);
            }]);

        // Aplicar ordenamiento según tipo: columna directa o subquery calculada
        if ($sortDef['type'] === 'raw') {
            // $sortDir ya está sanitizado a 'asc'|'desc' — seguro en raw
            $query->orderByRaw("{$sortDef['sql']} {$sortDir}");
        } else {
            $query->orderBy($sortDef['col'], $sortDir);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(function (Company $c) {
            $lastInvoice = $c->invoices->first();
            $isPaid      = false;
            $dueDate     = null;

            if ($lastInvoice) {
                $dueDate = $lastInvoice->due_date instanceof Carbon
                    ? $lastInvoice->due_date
                    : Carbon::parse($lastInvoice->due_date);
                $isPaid  = $dueDate->greaterThan(Carbon::now());
            }

            return [
                'id'           => $c->id,
                'name'         => $c->name,
                'email'        => $c->email,
                'phone'        => $c->phone,
                'active'       => (bool) $c->active,
                'package'      => $c->package,
                'is_paid'      => $isPaid,
                'last_payment' => $lastInvoice ? $lastInvoice->created_at?->toDateString() : null,
                'due_date'     => $dueDate ? $dueDate->toDateString() : null,
                'status_label' => $this->resolveCompanyStatus($c->active, $isPaid),
                'created_at'   => $c->created_at?->toDateString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT J — PATCH /api/v2/admin/companies/{id}/toggle-status
    // Suspende o activa una empresa. Genera entrada en audit_logs.
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleCompanyStatus(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $previousStatus = (bool) $company->active;
        $company->active = !$previousStatus;
        $company->save();

        $action = $company->active ? 'activate' : 'suspend';

        DB::table('audit_logs')->insert([
            'actor_id'     => $actor->id,
            'actor_email'  => $actor->email,
            'action'       => $action,
            'target_type'  => 'company',
            'target_id'    => $company->id,
            'target_name'  => $company->name,
            'from_status'  => $previousStatus ? 'active' : 'suspended',
            'to_status'    => $company->active ? 'active' : 'suspended',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => $company->active ? 'Empresa activada.' : 'Empresa suspendida.',
            'company_id' => $company->id,
            'active'     => $company->active,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT K — DELETE /api/v2/admin/companies/{id}
    // Elimina lógicamente una empresa (soft-delete no implementado, marca active=0).
    // ══════════════════════════════════════════════════════════════════════════

    public function deleteCompany(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        // Protección: no eliminar la empresa ACADEP
        $acadepId = (int) env('ACADEP_COMPANY_ID', 0);
        if ($acadepId && $company->id === $acadepId) {
            return response()->json(['success' => false, 'error' => 'La empresa ACADEP no puede ser eliminada.'], 403);
        }

        DB::table('audit_logs')->insert([
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'action'      => 'delete',
            'target_type' => 'company',
            'target_id'   => $company->id,
            'target_name' => $company->name,
            'from_status' => $company->active ? 'active' : 'suspended',
            'to_status'   => 'deleted',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $company->active = 0;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => "Empresa {$company->name} marcada como eliminada.",
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT L — GET /api/v2/admin/audit-logs
    // Consulta el Audit Trail de acciones sobre empresas.
    // ══════════════════════════════════════════════════════════════════════════

    public function listAuditLogs(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 50), 500);

        $logs = DB::table('audit_logs')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $logs->items(),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT M — POST /api/v2/admin/companies/{id}/validate-user-quota
    // Valida la regla de monetización 1+1 antes de crear un usuario adicional.
    // Retorna si se requiere autorización de cargo de $50 MXN.
    // ══════════════════════════════════════════════════════════════════════════

    public function validateUserQuota(Request $request, int $id)
    {
        $company = Company::withoutGlobalScopes()
            ->with('users')
            ->findOrFail($id);

        $activeUsers  = $company->users->where('active', '!=', 0)->count();
        $baseIncluded = 2; // 1 Admin + 1 Agente incluidos en el plan base ($850 MXN)
        $costPerExtra = 50;

        $requiresCharge = $activeUsers >= $baseIncluded;
        $extraUsers     = max(0, $activeUsers - $baseIncluded + 1);

        return response()->json([
            'success'         => true,
            'company_id'      => $company->id,
            'company_name'    => $company->name,
            'active_users'    => $activeUsers,
            'base_included'   => $baseIncluded,
            'requires_charge' => $requiresCharge,
            'extra_users'     => $extraUsers,
            'charge_amount'   => $requiresCharge ? ($extraUsers * $costPerExtra) : 0,
            'currency'        => 'MXN',
            'message'         => $requiresCharge
                ? "La empresa ya cuenta con {$activeUsers} usuario(s). Agregar uno más genera un cargo de \${$costPerExtra} MXN/mes."
                : "La empresa está dentro del plan base (hasta {$baseIncluded} usuarios incluidos).",
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Lee SUPER_ADMIN_COMPANY_IDS del .env y retorna int[].
     * Ej: "17,55" → [17, 55]
     * Lista vacía → fail-secure (ninguna empresa autorizada).
     */
    private function resolveAllowedCompanyIds(): array
    {
        $raw = env('SUPER_ADMIN_COMPANY_IDS', '');

        if (empty(trim($raw))) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('intval', explode(',', $raw)),
                fn (int $id) => $id > 0
            )
        );
    }

    private function resolveCompanyStatus(bool $active, bool $isPaid): string
    {
        if (!$active)  return 'Suspendida';
        if (!$isPaid)  return 'Vencida';
        return 'Activa';
    }

    // ── Máscara api_key: ••••••••4o3a ────────────────────────────────────────
    private function maskKey(string $encryptedKey): string
    {
        try {
            $plain = decrypt($encryptedKey);
            return str_repeat('•', 8) . substr($plain, -4);
        } catch (\Exception $e) {
            return '••••••••••••';
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\AiSetting;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
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
    // ══════════════════════════════════════════════════════════════════════════

    public function listAdmins(Request $request)
    {
        $actor   = $request->user();
        $perPage = min((int) $request->query('per_page', 50), 200);
        $search  = trim($request->query('search', ''));

        $query = User::with('roles')
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
        $actor  = $request->user();
        $target = User::find($id);

        if (!$target) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
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
        $target = User::find($id);

        if (!$target) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
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

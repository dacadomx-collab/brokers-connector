<?php

namespace App\Http\Controllers\Api;

use App\AiPrompt;
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
 *
 * REGLA INQUEBRANTABLE (Mandamiento de Seguridad):
 *   Toda operación de escritura (crear, editar, eliminar, cambiar estado)
 *   DEBE escribir un registro en audit_logs antes de retornar éxito.
 *   Sin registro de auditoría → la operación no está completa.
 *
 * Compatibilidad: Laravel 5.8 / PHP 8.0+
 *   - PROHIBIDO usar $request->boolean() → no existe en L5.8.
 *   - PROHIBIDO usar match() sin default → PHP 8.0 solo.
 *   - Usar filter_var(..., FILTER_VALIDATE_BOOLEAN) para booleanos de HTTP.
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
            ->with(['roles', 'company'])
            ->whereIn('company_id', $allowedCompIds)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'super_admin']));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.email',      'like', "%{$search}%")
                  ->orWhere('users.full_name', 'like', "%{$search}%")
                  ->orWhere('users.last_name', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage, ['id', 'full_name', 'last_name', 'email', 'company_id', 'active', 'created_at']);

        $data = collect($paginated->items())->map(function (User $u) {
            $roleNames = $u->roles->pluck('name');
            return [
                'id'           => $u->id,
                'full_name'    => trim("{$u->full_name} {$u->last_name}"),
                'email'        => $u->email,
                'company_id'   => $u->company_id,
                'company_name' => $u->company ? $u->company->name : '—',
                'active'       => (bool) $u->active,
                'roles'        => $roleNames,
                'is_super'     => $roleNames->contains('super_admin'),
                'created_at'   => $u->created_at ? $u->created_at->toDateString() : null,
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

        $previousRole = $target->hasRole('super_admin') ? 'super_admin' : 'Admin';

        if ($target->hasRole('super_admin')) {
            $target->removeRole('super_admin');
            $target->assignRole('Admin');
            $newRole = 'Admin';
        } else {
            $target->removeRole('Admin');
            $target->assignRole('super_admin');
            $newRole = 'super_admin';
        }

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'toggle_role',
            'target_type' => 'users',
            'target_id'   => $target->id,
            'target_name' => trim("{$target->full_name} {$target->last_name}") . " <{$target->email}>",
            'from_status' => $previousRole,
            'to_status'   => $newRole,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => "Rol actualizado a {$newRole}.",
            'user_id'  => $target->id,
            'new_role' => $newRole,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT C — POST /api/v2/admin/users/{id}/reset-password
    // ══════════════════════════════════════════════════════════════════════════

    public function resetPassword(Request $request, int $id)
    {
        $actor          = $request->user();
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

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'reset_password',
            'target_type' => 'users',
            'target_id'   => $target->id,
            'target_name' => "{$target->email}",
            'from_status' => 'activo',
            'to_status'   => 'contraseña_reseteada',
        ]);

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
                'id'                   => $s->id,
                'provider_name'        => $s->provider_name,
                'api_key_masked'       => $this->maskKey($s->api_key),
                'extra_config'         => $s->extra_config,
                'priority_order'       => $s->priority_order,
                'is_active'            => $s->is_active,
                'company_id'           => $s->company_id,
                // Historial de ping — persiste entre sesiones
                'last_tested_at'       => $s->last_tested_at ? $s->last_tested_at->toIso8601String() : null,
                'last_test_status'     => $s->last_test_status,
                'last_test_latency_ms' => $s->last_test_latency_ms,
                'last_test_error'      => $s->last_test_error,
            ];
        });

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT E — POST /api/v2/admin/ai-settings
    // ══════════════════════════════════════════════════════════════════════════

    public function storeAiSetting(Request $request)
    {
        $actor = $request->user();

        $data = $request->validate([
            'provider_name'  => 'required|string|max:50',
            'api_key'        => 'required|string|max:512',
            'extra_config'   => 'nullable|json',
            'priority_order' => 'required|integer|min:1|max:99',
            'is_active'      => 'boolean',
            'company_id'     => 'nullable|integer|exists:companies,id',
        ]);

        // Laravel 5.8: $request->boolean() no existe.
        // Usamos filter_var que convierte "1", "true", true, 1 → true.
        $isActive = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);

        $setting = AiSetting::create([
            'provider_name'  => $data['provider_name'],
            'api_key'        => encrypt($data['api_key']),
            'extra_config'   => isset($data['extra_config']) ? json_decode($data['extra_config'], true) : null,
            'priority_order' => $data['priority_order'],
            'is_active'      => $isActive,
            'company_id'     => $data['company_id'] ?? null,
        ]);

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'CREATE_AI_SETTING',
            'target_type' => 'ai_settings',
            'target_id'   => $setting->id,
            'target_name' => $setting->provider_name,
            'from_status' => null,
            'to_status'   => $isActive ? 'activo' : 'inactivo',
            'extra'       => [
                'provider_name'  => $setting->provider_name,
                'priority_order' => $setting->priority_order,
                'is_active'      => $isActive,
                'extra_config'   => $setting->extra_config,
                // NUNCA se registra api_key — ni cifrada ni en claro
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Proveedor registrado.', 'id' => $setting->id], 201);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT F — PUT /api/v2/admin/ai-settings/{id}
    // ══════════════════════════════════════════════════════════════════════════

    public function updateAiSetting(Request $request, int $id)
    {
        $actor   = $request->user();
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

        $payload  = [];
        $changes  = [];

        if (isset($data['provider_name']) && $data['provider_name'] !== $setting->provider_name) {
            $changes['provider_name'] = ['de' => $setting->provider_name, 'a' => $data['provider_name']];
            $payload['provider_name'] = $data['provider_name'];
        }

        if (!empty($data['api_key'])) {
            $payload['api_key'] = encrypt($data['api_key']);
            $changes['api_key'] = '*** actualizada ***'; // NUNCA texto plano en el log
        }

        if (array_key_exists('extra_config', $data)) {
            $newExtra = $data['extra_config'] ? json_decode($data['extra_config'], true) : null;
            $payload['extra_config'] = $newExtra;
            $changes['extra_config'] = $newExtra;
        }

        if (isset($data['priority_order']) && (int) $data['priority_order'] !== (int) $setting->priority_order) {
            $changes['priority_order'] = ['de' => $setting->priority_order, 'a' => $data['priority_order']];
            $payload['priority_order'] = $data['priority_order'];
        }

        if ($request->has('is_active')) {
            // Laravel 5.8: filter_var en lugar de $request->boolean()
            $newActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            if ($newActive !== (bool) $setting->is_active) {
                $changes['is_active'] = ['de' => (bool) $setting->is_active, 'a' => $newActive];
            }
            $payload['is_active'] = $newActive;
        }

        $setting->update($payload);

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'UPDATE_AI_SETTING',
            'target_type' => 'ai_settings',
            'target_id'   => $setting->id,
            'target_name' => $setting->provider_name,
            'from_status' => null,
            'to_status'   => null,
            'extra'       => empty($changes) ? ['sin_cambios' => true] : $changes,
        ]);

        return response()->json(['success' => true, 'message' => 'Proveedor actualizado.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT G — DELETE /api/v2/admin/ai-settings/{id}
    // ══════════════════════════════════════════════════════════════════════════

    public function destroyAiSetting(Request $request, int $id)
    {
        $actor   = $request->user();
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        // Capturar datos ANTES de eliminar para el audit trail
        $providerName  = $setting->provider_name;
        $priorityOrder = $setting->priority_order;
        $wasActive     = (bool) $setting->is_active;

        $setting->delete();

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'DELETE_AI_SETTING',
            'target_type' => 'ai_settings',
            'target_id'   => $id,
            'target_name' => $providerName,
            'from_status' => $wasActive ? 'activo' : 'inactivo',
            'to_status'   => 'eliminado',
            'extra'       => [
                'provider_name'  => $providerName,
                'priority_order' => $priorityOrder,
                // api_key NUNCA se registra
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Proveedor eliminado.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT H — PATCH /api/v2/admin/ai-settings/{id}/toggle
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleAiSetting(Request $request, int $id)
    {
        $actor   = $request->user();
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        $previousActive = (bool) $setting->is_active;
        $setting->is_active = !$previousActive;
        $setting->save();

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'TOGGLE_AI_SETTING',
            'target_type' => 'ai_settings',
            'target_id'   => $setting->id,
            'target_name' => $setting->provider_name,
            'from_status' => $previousActive ? 'activo' : 'inactivo',
            'to_status'   => $setting->is_active ? 'activo' : 'inactivo',
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Estado actualizado.',
            'is_active' => $setting->is_active,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT H2 — POST /api/v2/admin/ai-settings/{id}/test
    //
    // Test de Conexión (Ping): envía un prompt mínimo al proveedor específico
    // para verificar que la API Key es válida y el servicio está disponible.
    //
    // Arquitectura: llama DIRECTAMENTE al adaptador del proveedor (NO usa
    // AIService::dispatch()) para aislar el test a ese proveedor en concreto,
    // sin activar la escalera de failover ni contaminar otros logs.
    // ══════════════════════════════════════════════════════════════════════════

    private const AI_TEST_ADAPTERS = [
        'openai'  => \App\Services\Providers\OpenAIProvider::class,
        'groq'    => \App\Services\Providers\GroqProvider::class,
        'mistral' => \App\Services\Providers\MistralProvider::class,
        'gemini'  => \App\Services\Providers\GeminiProvider::class,
    ];

    public function testAiSetting(Request $request, int $id)
    {
        $actor   = $request->user();
        $setting = AiSetting::find($id);

        if (!$setting) {
            return response()->json(['success' => false, 'error' => 'Proveedor no encontrado.'], 404);
        }

        $adapterClass = self::AI_TEST_ADAPTERS[$setting->provider_name] ?? null;

        if (!$adapterClass) {
            return response()->json([
                'success' => false,
                'error'   => "El adaptador para '{$setting->provider_name}' aún no está implementado en el sistema.",
            ], 400);
        }

        // Desencriptar la API Key — puede lanzar DecryptException si la APP_KEY cambió
        try {
            $decryptedKey = $setting->decryptedKey();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo desencriptar la API Key. La APP_KEY del sistema puede haber cambiado. Reingresa la llave.',
            ], 400);
        }

        // Prompt mínimo y económico — sin respuesta larga, sin contexto de negocio
        $testPayload = [
            'messages'    => [
                ['role' => 'user', 'content' => 'Ping. Reply strictly with the single word: pong'],
            ],
            'temperature' => 0,
            'max_tokens'  => 5,
        ];

        $testConfig = [
            'api_key'      => $decryptedKey,
            'extra_config' => $setting->extra_config ?? [],
        ];

        try {
            $adapter = new $adapterClass();
            $result  = $adapter->request($testPayload, $testConfig);
        } catch (\Exception $e) {
            // sanitizeErrorMessage() es obligatorio aquí: ConnectException de Guzzle
            // incluye la URL completa con ?key=... en su mensaje nativo.
            $errorMsg = 'Excepción al conectar: ' . $this->sanitizeErrorMessage($e->getMessage());
            $testedAt = now();

            $setting->update([
                'last_tested_at'       => $testedAt,
                'last_test_status'     => 'error',
                'last_test_latency_ms' => null,
                'last_test_error'      => mb_substr($errorMsg, 0, 65535),
            ]);

            $this->writeAuditLog([
                'actor'       => $actor,
                'action'      => 'TEST_AI_SETTING',
                'target_type' => 'ai_settings',
                'target_id'   => $setting->id,
                'target_name' => $setting->provider_name,
                'from_status' => null,
                'to_status'   => 'test_error',
                'extra'       => ['error' => mb_substr($errorMsg, 0, 300)],
            ]);

            return response()->json([
                'success'          => false,
                'error'            => $errorMsg,
                'last_tested_at'   => $testedAt->toIso8601String(),
                'last_test_status' => 'error',
            ], 400);
        }

        $testedAt = now();

        if ($result['status'] !== 'ok') {
            $rawError      = $result['error'] ?? 'El proveedor devolvió un error sin mensaje.';
            $friendlyError = $this->extractGuzzleError($rawError);

            // Persistir resultado fallido en ai_settings
            $setting->update([
                'last_tested_at'       => $testedAt,
                'last_test_status'     => 'error',
                'last_test_latency_ms' => $result['latency_ms'] ?? null,
                'last_test_error'      => mb_substr($friendlyError, 0, 65535),
            ]);

            $this->writeAuditLog([
                'actor'       => $actor,
                'action'      => 'TEST_AI_SETTING',
                'target_type' => 'ai_settings',
                'target_id'   => $setting->id,
                'target_name' => $setting->provider_name,
                'from_status' => null,
                'to_status'   => 'test_failed',
                'extra'       => ['latency_ms' => $result['latency_ms'] ?? null, 'error' => mb_substr($friendlyError, 0, 300)],
            ]);

            return response()->json([
                'success'              => false,
                'error'                => $friendlyError,
                'latency_ms'           => $result['latency_ms'] ?? null,
                'last_tested_at'       => $testedAt->toIso8601String(),
                'last_test_status'     => 'error',
                'last_test_latency_ms' => $result['latency_ms'] ?? null,
            ], 400);
        }

        // Persistir resultado exitoso en ai_settings (limpia error anterior)
        $setting->update([
            'last_tested_at'       => $testedAt,
            'last_test_status'     => 'ok',
            'last_test_latency_ms' => $result['latency_ms'],
            'last_test_error'      => null,
        ]);

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'TEST_AI_SETTING',
            'target_type' => 'ai_settings',
            'target_id'   => $setting->id,
            'target_name' => $setting->provider_name,
            'from_status' => null,
            'to_status'   => 'test_ok',
            'extra'       => ['latency_ms' => $result['latency_ms'], 'response' => mb_substr($result['response'] ?? '', 0, 50)],
        ]);

        return response()->json([
            'success'              => true,
            'message'              => "Conexión exitosa con {$setting->provider_name}. Latencia: {$result['latency_ms']} ms.",
            'response'             => $result['response'],
            'latency_ms'           => $result['latency_ms'],
            'last_tested_at'       => $testedAt->toIso8601String(),
            'last_test_status'     => 'ok',
            'last_test_latency_ms' => $result['latency_ms'],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // sanitizeErrorMessage() — Enmascaramiento de credenciales en mensajes
    //
    // REGLA DE SEGURIDAD: Las API Keys NUNCA deben llegar al frontend, ni en
    // errores ni en logs de auditoría expuestos. Este método sanitiza:
    //   - Query params: ?key=, &key=, ?api_key=, ?token=, ?access_token=
    //   - Bearer tokens en headers (leaks de Guzzle en modo verbose)
    //   - Cualquier parámetro nombrado "secret", "credential" o "password"
    //
    // Caso concreto de Gemini: Guzzle embebe la URL completa en su mensaje
    // de excepción. La URL contiene ?key=AIzaSy... en texto plano.
    // Esta función reemplaza esas cadenas por [REDACTED] antes de devolver
    // el error al frontend.
    // ══════════════════════════════════════════════════════════════════════════

    private function sanitizeErrorMessage(string $msg): string
    {
        // Query-param API keys: ?key=XXX, &api_key=XXX, ?token=XXX, etc.
        $msg = preg_replace(
            '/([?&](key|api[_\-]?key|token|access[_\-]?token|secret|credential|password)\s*=\s*)[^\s&"\'#>]+/i',
            '$1[REDACTED]',
            $msg
        );

        // Bearer tokens (en caso de leak de cabecera dentro del body de error)
        $msg = preg_replace(
            '/\bBearer\s+[A-Za-z0-9\._\-\+\/=]{8,}/i',
            'Bearer [REDACTED]',
            $msg
        );

        // Formato "Authorization: Bearer TOKEN" o "Authorization: token TOKEN"
        $msg = preg_replace(
            '/(Authorization\s*:\s*(?:Bearer|token)\s+)[A-Za-z0-9\._\-\+\/=]{8,}/i',
            '$1[REDACTED]',
            $msg
        );

        return $msg;
    }

    // ── Extrae el cuerpo del error HTTP desde el mensaje de Guzzle ─────────────
    // Guzzle incluye el body completo de la respuesta en el mensaje de excepción.
    // Para errores de API (401, 429, 400, 404) el JSON de error está embebido.
    //
    // FLUJO OBLIGATORIO:
    //   1. sanitizeErrorMessage() PRIMERO — quita credenciales de la URL
    //   2. Extraer JSON del body sanitizado
    //   3. Parsear el mensaje legible del proveedor
    private function extractGuzzleError(string $rawError): string
    {
        // Paso 1: Sanitizar ANTES de cualquier procesamiento o log
        $sanitized = $this->sanitizeErrorMessage($rawError);

        // Paso 2: Extraer el JSON del body de la respuesta HTTP
        if (preg_match('/\{.*\}/s', $sanitized, $matches)) {
            $body = json_decode($matches[0], true);
            if (is_array($body)) {
                // OpenAI / Groq / Mistral: { "error": { "message": "..." } }
                if (isset($body['error']['message'])) {
                    return (string) $body['error']['message'];
                }
                // Gemini: { "error": { "message": "...", "status": "NOT_FOUND" } }
                // También: { "message": "..." } como raíz
                if (isset($body['message'])) {
                    return (string) $body['message'];
                }
            }
        }

        // Paso 3: Si no hay JSON parseable, devolver el mensaje limpio y truncado
        $clean = preg_replace('/\s+/', ' ', $sanitized);
        return mb_substr(trim($clean), 0, 250);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT N — GET /api/v2/admin/token-stats
    //
    // Registro Maestro de Consumo IA — Agrega tokens_used de ai_messages
    // por tenant (company). Soporta filtro de período: 7d | 30d | 90d | all.
    //
    // Sin audit_log: es una consulta de solo lectura (no modifica datos).
    // Sin paginación: devuelve los top-50 tenants por consumo (suficiente para SaaS).
    // ══════════════════════════════════════════════════════════════════════════

    public function tokenStats(Request $request)
    {
        $period = $request->query('period', 'all');

        // Validar período — whitelist estricta
        $validPeriods = ['7d', '30d', '90d', 'all'];
        if (!in_array($period, $validPeriods, true)) {
            $period = 'all';
        }

        $dateFrom = null;
        if ($period === '7d')  $dateFrom = Carbon::now()->subDays(7)->startOfDay();
        if ($period === '30d') $dateFrom = Carbon::now()->subDays(30)->startOfDay();
        if ($period === '90d') $dateFrom = Carbon::now()->subDays(90)->startOfDay();

        // ── Totales globales del sistema ─────────────────────────────────────
        $globalQuery = DB::table('ai_messages')->where('tokens_used', '>', 0);
        if ($dateFrom) {
            $globalQuery->where('created_at', '>=', $dateFrom);
        }

        $global = $globalQuery->selectRaw('
            COALESCE(SUM(tokens_used), 0)    AS total_tokens,
            COUNT(*)                          AS total_messages,
            COUNT(DISTINCT conversation_id)   AS total_conversations
        ')->first();

        $totalTokens = (int) ($global->total_tokens ?? 0);

        // ── Agregación por empresa ───────────────────────────────────────────
        // JOIN: ai_messages → ai_conversations → companies
        // Se excluyen mensajes con tokens_used = 0 (system prompts sin costo)
        $companyQuery = DB::table('ai_messages AS am')
            ->join('ai_conversations AS ac', 'ac.id', '=', 'am.conversation_id')
            ->join('companies AS c',         'c.id', '=', 'ac.company_id')
            ->where('am.tokens_used', '>', 0);

        if ($dateFrom) {
            $companyQuery->where('am.created_at', '>=', $dateFrom);
        }

        $byCompany = $companyQuery
            ->selectRaw('
                c.id                                          AS company_id,
                c.name                                        AS company_name,
                COALESCE(SUM(am.tokens_used), 0)             AS total_tokens,
                COUNT(am.id)                                  AS total_messages,
                COUNT(DISTINCT ac.id)                         AS total_conversations,
                ROUND(AVG(NULLIF(am.tokens_used, 0)), 1)      AS avg_tokens_per_msg,
                MAX(am.created_at)                            AS last_activity
            ')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total_tokens')
            ->limit(50)
            ->get()
            ->map(function ($row) use ($totalTokens) {
                return [
                    'company_id'          => (int)   $row->company_id,
                    'company_name'        => (string) $row->company_name,
                    'total_tokens'        => (int)   $row->total_tokens,
                    'total_messages'      => (int)   $row->total_messages,
                    'total_conversations' => (int)   $row->total_conversations,
                    'avg_tokens_per_msg'  => (float) ($row->avg_tokens_per_msg ?? 0),
                    'pct_of_total'        => $totalTokens > 0
                        ? round(($row->total_tokens / $totalTokens) * 100, 1)
                        : 0.0,
                    'last_activity'       => $row->last_activity,
                ];
            });

        $avgPerMsg = ($global->total_messages > 0)
            ? round($global->total_tokens / $global->total_messages, 1)
            : 0;

        return response()->json([
            'success' => true,
            'period'  => $period,
            'global'  => [
                'total_tokens'           => $totalTokens,
                'total_messages'         => (int) ($global->total_messages         ?? 0),
                'total_conversations'    => (int) ($global->total_conversations    ?? 0),
                'total_companies_active' => $byCompany->count(),
                'avg_tokens_per_message' => $avgPerMsg,
            ],
            'by_company' => $byCompany,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT O — GET /api/v2/admin/ai-prompts
    // Lista todos los Prompts Maestros del Motor Cognitivo AVM.
    // Solo lectura — no genera audit_log.
    // ══════════════════════════════════════════════════════════════════════════

    public function listAiPrompts(Request $request)
    {
        $prompts = AiPrompt::orderBy('slug')->get([
            'id', 'slug', 'name', 'prompt_text',
            'system_role', 'business_context', 'immutable_rules',
            'tone_profile', 'output_schema', 'variables_schema',
            'preferred_model', 'version', 'is_active', 'updated_at',
        ]);

        return response()->json(['success' => true, 'data' => $prompts]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT P — PUT /api/v2/admin/ai-prompts/{slug}
    // Synaptic Core™: Actualiza todos los módulos del prompt.
    // Archiva la versión anterior en ai_prompt_versions (Version Control).
    // MANDAMIENTO: genera audit_log antes de retornar éxito.
    // ══════════════════════════════════════════════════════════════════════════

    public function updateAiPrompt(Request $request, string $slug)
    {
        $actor = $request->user();

        $prompt = AiPrompt::where('slug', $slug)->first();

        if (!$prompt) {
            return response()->json(['success' => false, 'error' => 'Prompt no encontrado.'], 404);
        }

        $newSystemRole  = trim($request->input('system_role',  ''));
        $newPromptText  = trim($request->input('prompt_text',  ''));
        $newName        = trim($request->input('name', $prompt->name ?? $slug));

        // Al menos system_role o prompt_text deben tener contenido
        if ($newSystemRole === '' && $newPromptText === '') {
            return response()->json([
                'success' => false,
                'error'   => 'El Rol del Sistema o el Prompt Legacy deben tener contenido.',
            ], 422);
        }

        // ── Archivar versión actual antes de sobreescribir ────────────────────
        // ai_prompt_versions puede no existir aún — try/catch silencioso.
        try {
            DB::table('ai_prompt_versions')->insert([
                'prompt_id'        => $prompt->id,
                'version'          => $prompt->version ?? 1,
                'system_role'      => $prompt->system_role,
                'business_context' => $prompt->business_context,
                'immutable_rules'  => $prompt->immutable_rules,
                'tone_profile'     => $prompt->tone_profile
                    ? json_encode($prompt->tone_profile, JSON_UNESCAPED_UNICODE)
                    : null,
                'output_schema'    => $prompt->output_schema
                    ? json_encode($prompt->output_schema, JSON_UNESCAPED_UNICODE)
                    : null,
                'variables_schema' => $prompt->variables_schema
                    ? json_encode($prompt->variables_schema, JSON_UNESCAPED_UNICODE)
                    : null,
                'preferred_model'  => $prompt->preferred_model,
                'prompt_text'      => $prompt->prompt_text,
                'changed_by_email' => $actor->email,
                'change_note'      => mb_substr($request->input('change_note', ''), 0, 255),
                'created_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            // ai_prompt_versions puede no existir — ignorar, no bloquear el save
            \Log::info('Synaptic Core: ai_prompt_versions no disponible aún', ['error' => $e->getMessage()]);
        }

        $newVersion = ($prompt->version ?? 1) + 1;

        // Parsear JSON fields — si viene como string, decodificar; si ya es array, usar directo
        $parseJson = function ($field) use ($request) {
            $raw = $request->input($field);
            if (is_array($raw))   return $raw;
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
            return null;
        };

        $prompt->update([
            'name'             => $newName !== '' ? $newName : ($prompt->name ?? $slug),
            'prompt_text'      => $newPromptText !== '' ? $newPromptText : $prompt->prompt_text,
            'system_role'      => $newSystemRole !== '' ? $newSystemRole : null,
            'business_context' => trim($request->input('business_context', '')) ?: null,
            'immutable_rules'  => trim($request->input('immutable_rules',  '')) ?: null,
            'tone_profile'     => $parseJson('tone_profile'),
            'output_schema'    => $parseJson('output_schema'),
            'variables_schema' => $parseJson('variables_schema'),
            'preferred_model'  => trim($request->input('preferred_model', '')) ?: null,
            'version'          => $newVersion,
        ]);

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'UPDATE_AI_PROMPT',
            'target_type' => 'ai_prompts',
            'target_id'   => $prompt->id,
            'target_name' => $slug,
            'from_status' => 'v' . ($newVersion - 1),
            'to_status'   => 'v' . $newVersion,
            'extra'       => [
                'chars_system_role'  => mb_strlen($newSystemRole),
                'chars_prompt_text'  => mb_strlen($newPromptText),
                'has_output_schema'  => $parseJson('output_schema') !== null,
            ],
        ]);

        return response()->json([
            'success'     => true,
            'message'     => "Synaptic Core '{$slug}' guardado como v{$newVersion}.",
            'new_version' => $newVersion,
            'updated_at'  => $prompt->fresh()->updated_at->toIso8601String(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT Q — POST /api/v2/admin/ai-prompts/{slug}/test
    // Playground del Synaptic Core™: compila y prueba el prompt en tiempo real.
    // Compila desde el cuerpo del request (no desde DB) — permite probar
    // ANTES de guardar. Sin audit_log (operación read-only de test).
    // ══════════════════════════════════════════════════════════════════════════

    public function testAiPrompt(Request $request, string $slug)
    {
        $actor = $request->user();

        // Construir un AiPrompt temporal desde el formulario (sin persistir)
        $parseJson = function ($field) use ($request) {
            $raw = $request->input($field);
            if (is_array($raw)) return $raw;
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
            return null;
        };

        $tempPrompt = new AiPrompt([
            'slug'             => $slug,
            'system_role'      => trim($request->input('system_role',      '')),
            'business_context' => trim($request->input('business_context', '')),
            'immutable_rules'  => trim($request->input('immutable_rules',  '')),
            'tone_profile'     => $parseJson('tone_profile'),
            'output_schema'    => $parseJson('output_schema'),
            'variables_schema' => $parseJson('variables_schema'),
            'preferred_model'  => trim($request->input('preferred_model', '')),
            'prompt_text'      => trim($request->input('prompt_text', '')),
        ]);

        $variables   = $request->input('variables', []);
        $testMessage = trim($request->input('test_message', 'Ejecuta una prueba de funcionamiento del sistema.'));

        $compiledPrompt = $tempPrompt->compile(is_array($variables) ? $variables : []);

        if (empty(trim($compiledPrompt))) {
            return response()->json([
                'success' => false,
                'error'   => 'El prompt compilado está vacío. Ingresa al menos el Rol del Sistema o el Prompt Legacy.',
            ], 422);
        }

        $aiService = new \App\Services\AIService();
        $companyId = (int) ($actor->company_id ?? 1);

        try {
            $result = $aiService->dispatch([
                'messages' => [
                    ['role' => 'system', 'content' => $compiledPrompt],
                    ['role' => 'user',   'content' => $testMessage],
                ],
                'temperature'     => 0.2,
                'max_tokens'      => 600,
                'response_format' => ['type' => 'json_object'],
            ], $companyId);

            return response()->json([
                'success'         => true,
                'compiled_prompt' => $compiledPrompt,
                'raw_response'    => $result['response']    ?? '',
                'latency_ms'      => $result['latency_ms']  ?? null,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Sin proveedores IA activos: ' . $e->getMessage(),
            ], 503);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error en test: ' . mb_substr($e->getMessage(), 0, 200),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT I — GET /api/v2/admin/companies
    // ══════════════════════════════════════════════════════════════════════════

    private const PAID_STATUSES = [1, 2];

    private const COMPANY_SORT_MAP = [
        'id'           => ['type' => 'column', 'col' => 'companies.id'],
        'name'         => ['type' => 'column', 'col' => 'companies.name'],
        'email'        => ['type' => 'column', 'col' => 'companies.email'],
        'package'      => ['type' => 'column', 'col' => 'companies.package'],
        'active'       => ['type' => 'column', 'col' => 'companies.active'],
        'status_label' => ['type' => 'column', 'col' => 'companies.active'],
        'created_at'   => ['type' => 'column', 'col' => 'companies.created_at'],
        'last_payment' => [
            'type' => 'raw',
            'sql'  => '(SELECT MAX(i.payday) FROM invoices i WHERE i.company_id = companies.id AND i.status IN (1,2) AND i.deleted_at IS NULL)',
        ],
        'due_date' => [
            'type' => 'raw',
            'sql'  => '(SELECT MAX(i.due_date) FROM invoices i WHERE i.company_id = companies.id AND i.status IN (1,2) AND i.deleted_at IS NULL)',
        ],
    ];

    public function listCompanies(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 30), 200);
        $search  = trim($request->query('search', ''));
        $sortKey = $request->query('sort_by', 'id');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortDef = self::COMPANY_SORT_MAP[$sortKey] ?? self::COMPANY_SORT_MAP['id'];

        $query = Company::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'phone', 'address', 'rfc', 'colony', 'zipcode', 'active', 'package', 'created_at'])
            ->with(['invoices' => function ($q) {
                $q->whereIn('status', self::PAID_STATUSES)
                  ->whereNotNull('due_date')
                  ->orderByDesc('due_date')
                  ->limit(1);
            }]);

        if ($sortDef['type'] === 'raw') {
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
                $isPaid = $dueDate->greaterThan(Carbon::now());
            }

            $paymentDate = null;
            if ($lastInvoice) {
                $paymentDate = $lastInvoice->payday
                    ? (is_string($lastInvoice->payday)
                        ? Carbon::parse($lastInvoice->payday)->toDateString()
                        : $lastInvoice->payday->toDateString())
                    : ($lastInvoice->created_at ? $lastInvoice->created_at->toDateString() : null);
            }

            return [
                'id'           => $c->id,
                'name'         => $c->name,
                'email'        => $c->email,
                'phone'        => $c->phone,
                'address'      => $c->address,
                'rfc'          => $c->rfc,
                'colony'       => $c->colony,
                'zipcode'      => $c->zipcode,
                'active'       => (bool) $c->active,
                'package'      => $c->package,
                'is_paid'      => $isPaid,
                'last_payment' => $paymentDate,
                'due_date'     => $dueDate ? $dueDate->toDateString() : null,
                'status_label' => $this->resolveCompanyStatus((bool) $c->active, $isPaid),
                'created_at'   => $c->created_at ? $c->created_at->toDateString() : null,
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
    // ══════════════════════════════════════════════════════════════════════════

    public function toggleCompanyStatus(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $previousActive  = (bool) $company->active;
        $company->active = !$previousActive;
        $company->save();

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => $company->active ? 'activate' : 'suspend',
            'target_type' => 'company',
            'target_id'   => $company->id,
            'target_name' => $company->name,
            'from_status' => $previousActive ? 'activa' : 'suspendida',
            'to_status'   => $company->active ? 'activa' : 'suspendida',
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
    // ══════════════════════════════════════════════════════════════════════════

    public function deleteCompany(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $acadepId = (int) env('ACADEP_COMPANY_ID', 0);
        if ($acadepId && $company->id === $acadepId) {
            return response()->json(['success' => false, 'error' => 'La empresa ACADEP no puede ser eliminada.'], 403);
        }

        $companyName = $company->name;

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'delete',
            'target_type' => 'company',
            'target_id'   => $company->id,
            'target_name' => $companyName,
            'from_status' => $company->active ? 'activa' : 'suspendida',
            'to_status'   => 'eliminada',
        ]);

        $company->active = 0;
        $company->save();

        return response()->json([
            'success' => true,
            'message' => "Empresa {$companyName} marcada como eliminada.",
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT J2 — PUT /api/v2/admin/companies/{id}
    // ══════════════════════════════════════════════════════════════════════════

    public function updateCompany(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $data = $request->validate([
            'email'   => 'sometimes|nullable|string|email|max:191',
            'phone'   => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:255',
            'rfc'     => 'sometimes|nullable|string|max:30',
            'colony'  => 'sometimes|nullable|string|max:100',
            'zipcode' => 'sometimes|nullable|string|max:10',
        ]);

        $changes = [];
        foreach ($data as $field => $newValue) {
            $oldValue = $company->$field;
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = ['de' => $oldValue, 'a' => $newValue];
            }
        }

        $company->fill($data);
        $company->save();

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'update',
            'target_type' => 'company',
            'target_id'   => $company->id,
            'target_name' => $company->name,
            'from_status' => null,
            'to_status'   => null,
            'extra'       => empty($changes) ? ['sin_cambios' => true] : $changes,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Datos de {$company->name} actualizados.",
            'changes' => $changes,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT J3 — PATCH /api/v2/admin/companies/{id}/subscription-override
    // ══════════════════════════════════════════════════════════════════════════

    public function subscriptionOverride(Request $request, int $id)
    {
        $actor   = $request->user();
        $company = Company::withoutGlobalScopes()->find($id);

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $data = $request->validate([
            'due_date' => 'required|date',
            'payday'   => 'nullable|date',
            'plan'     => 'nullable|integer|min:1|max:99',
            'reason'   => 'required|string|max:500',
        ]);

        $changes = [];

        $invoiceData = [
            'name'         => 'Ajuste manual — Super Admin',
            'cost_package' => 0,
            'cost_user'    => 0,
            'users'        => 0,
            'status'       => 2,
            'charge_id'    => null,
            'payday'       => $data['payday'] ? Carbon::parse($data['payday']) : Carbon::now(),
            'due_date'     => Carbon::parse($data['due_date']),
            'company_id'   => $company->id,
        ];

        DB::table('invoices')->insert(array_merge($invoiceData, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $changes['due_date'] = ['de' => 'anterior', 'a' => $data['due_date']];
        $changes['payday']   = ['de' => 'anterior', 'a' => $data['payday'] ?? Carbon::now()->toDateString()];

        if (!empty($data['plan']) && (int) $data['plan'] !== (int) $company->package) {
            $changes['plan'] = ['de' => $company->package, 'a' => $data['plan']];
            $company->package = $data['plan'];
            $company->save();
        }

        $this->writeAuditLog([
            'actor'       => $actor,
            'action'      => 'subscription_override',
            'target_type' => 'company',
            'target_id'   => $company->id,
            'target_name' => $company->name,
            'from_status' => null,
            'to_status'   => 'pagada_manual',
            'extra'       => array_merge($changes, ['motivo' => $data['reason']]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajuste financiero aplicado. Factura manual registrada.',
            'changes' => $changes,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENDPOINT L — GET /api/v2/admin/audit-logs
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
    // ENDPOINT M — GET /api/v2/admin/companies/{id}/validate-user-quota
    // ══════════════════════════════════════════════════════════════════════════

    public function validateUserQuota(Request $request, int $id)
    {
        $company = Company::withoutGlobalScopes()
            ->with('users')
            ->findOrFail($id);

        $activeUsers  = $company->users->where('active', '!=', 0)->count();
        $baseIncluded = 2;
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

    // ══════════════════════════════════════════════════════════════════════════
    // HELPER PRIVADO — writeAuditLog()
    //
    // Centraliza toda escritura en audit_logs.
    // REGLA: NUNCA incluir api_key ni contraseñas en $extra.
    // REGLA: action debe ser un código snake_case o UPPER_SNAKE (no texto libre).
    //
    // Parámetros:
    //   actor        → User autenticado (el super_admin)
    //   action       → código de acción (ver catálogo en 05_SISTEMA_V2_CORE.md)
    //   target_type  → tabla afectada: 'company' | 'users' | 'ai_settings' | ...
    //   target_id    → id del registro afectado
    //   target_name  → nombre legible del registro (empresa, email, proveedor...)
    //   from_status  → estado anterior (opcional)
    //   to_status    → estado posterior (opcional)
    //   extra        → array con detalles adicionales — se serializa a JSON
    // ══════════════════════════════════════════════════════════════════════════

    private function writeAuditLog(array $params): void
    {
        $actor = $params['actor'];

        try {
            DB::table('audit_logs')->insert([
                'actor_id'    => $actor->id,
                'actor_email' => $actor->email,
                'action'      => $params['action'],
                'target_type' => $params['target_type'] ?? 'company',
                'target_id'   => $params['target_id']   ?? null,
                'target_name' => isset($params['target_name'])
                    ? mb_substr((string) $params['target_name'], 0, 191)
                    : null,
                'from_status' => isset($params['from_status'])
                    ? mb_substr((string) $params['from_status'], 0, 50)
                    : null,
                'to_status'   => isset($params['to_status'])
                    ? mb_substr((string) $params['to_status'], 0, 50)
                    : null,
                'extra'       => isset($params['extra'])
                    ? json_encode($params['extra'], JSON_UNESCAPED_UNICODE)
                    : null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Exception $e) {
            // Audit trail no puede silenciar la operación principal,
            // pero SÍ debe registrarse en el log de sistema.
            \Log::error('SuperAdmin: falló writeAuditLog', [
                'action'    => $params['action'] ?? 'unknown',
                'target_id' => $params['target_id'] ?? null,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
        if (!$active) return 'Suspendida';
        if (!$isPaid) return 'Vencida';
        return 'Activa';
    }

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

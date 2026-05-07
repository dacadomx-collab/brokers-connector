<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\PaymentGatewaySetting;
use Illuminate\Http\Request;

/**
 * PaymentGatewayController — CRUD de pasarelas de pago V2
 *
 * Autenticación: Passport (auth:api + role:super_admin).
 * Las credenciales se encriptan con encrypt() antes de tocar la BD.
 * El frontend solo recibe valores enmascarados (••••••••xxxx).
 */
class PaymentGatewayController extends Controller
{
    // ── GET /api/v2/admin/payment-gateways ────────────────────────────────────

    public function index()
    {
        $gateways = PaymentGatewaySetting::orderBy('provider_name')->get()->map(fn ($g) => $this->format($g));
        return response()->json(['success' => true, 'data' => $gateways]);
    }

    // ── POST /api/v2/admin/payment-gateways ───────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_name' => 'required|string|max:50',
            'is_active'     => 'boolean',
            'is_sandbox'    => 'boolean',
            'credentials'   => 'nullable|array',
            'company_id'    => 'nullable|integer|exists:companies,id',
        ]);

        $gateway = PaymentGatewaySetting::create([
            'provider_name' => $data['provider_name'],
            'is_active'     => $request->boolean('is_active', false),
            'is_sandbox'    => $request->boolean('is_sandbox', true),
            'credentials'   => isset($data['credentials']) ? encrypt(json_encode($data['credentials'])) : null,
            'company_id'    => $data['company_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Pasarela registrada.', 'id' => $gateway->id], 201);
    }

    // ── PUT /api/v2/admin/payment-gateways/{id} ───────────────────────────────

    public function update(Request $request, int $id)
    {
        $gateway = PaymentGatewaySetting::find($id);
        if (! $gateway) {
            return response()->json(['success' => false, 'error' => 'Pasarela no encontrada.'], 404);
        }

        $data = $request->validate([
            'provider_name' => 'sometimes|string|max:50',
            'is_active'     => 'boolean',
            'is_sandbox'    => 'boolean',
            'credentials'   => 'nullable|array',
        ]);

        $payload = [];
        if (isset($data['provider_name']))  $payload['provider_name'] = $data['provider_name'];
        if ($request->has('is_active'))     $payload['is_active']     = $request->boolean('is_active');
        if ($request->has('is_sandbox'))    $payload['is_sandbox']    = $request->boolean('is_sandbox');
        if (array_key_exists('credentials', $data)) {
            $payload['credentials'] = $data['credentials']
                ? encrypt(json_encode($data['credentials']))
                : null;
        }

        $gateway->update($payload);

        return response()->json(['success' => true, 'message' => 'Pasarela actualizada.']);
    }

    // ── DELETE /api/v2/admin/payment-gateways/{id} ────────────────────────────

    public function destroy(int $id)
    {
        $gateway = PaymentGatewaySetting::find($id);
        if (! $gateway) {
            return response()->json(['success' => false, 'error' => 'Pasarela no encontrada.'], 404);
        }
        $gateway->delete();
        return response()->json(['success' => true, 'message' => 'Pasarela eliminada.']);
    }

    // ── PATCH /api/v2/admin/payment-gateways/{id}/toggle ─────────────────────

    public function toggle(int $id)
    {
        $gateway = PaymentGatewaySetting::find($id);
        if (! $gateway) {
            return response()->json(['success' => false, 'error' => 'Pasarela no encontrada.'], 404);
        }
        $gateway->is_active = ! $gateway->is_active;
        $gateway->save();
        return response()->json(['success' => true, 'is_active' => $gateway->is_active]);
    }

    // ── PATCH /api/v2/admin/payment-gateways/{id}/toggle-sandbox ─────────────

    public function toggleSandbox(int $id)
    {
        $gateway = PaymentGatewaySetting::find($id);
        if (! $gateway) {
            return response()->json(['success' => false, 'error' => 'Pasarela no encontrada.'], 404);
        }
        $gateway->is_sandbox = ! $gateway->is_sandbox;
        $gateway->save();
        return response()->json(['success' => true, 'is_sandbox' => $gateway->is_sandbox]);
    }

    // ── Formato de salida ─────────────────────────────────────────────────────

    private function format(PaymentGatewaySetting $g): array
    {
        return [
            'id'             => $g->id,
            'provider_name'  => $g->provider_name,
            'is_active'      => $g->is_active,
            'is_sandbox'     => $g->is_sandbox,
            'credentials'    => $g->maskedCredentials(),   // ••••••••xxxx
            'has_credentials'=> (bool) $g->credentials,
            'company_id'     => $g->company_id,
        ];
    }
}

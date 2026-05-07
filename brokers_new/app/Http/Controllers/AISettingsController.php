<?php

namespace App\Http\Controllers;

use App\AiSetting;
use Illuminate\Http\Request;

class AISettingsController extends Controller
{
    public function index()
    {
        $settings = AiSetting::orderBy('priority_order')->get()->map(function ($s) {
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

        return view('ai.settings', compact('settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_name'  => 'required|string|max:50',
            'api_key'        => 'required|string|max:512',
            'extra_config'   => 'nullable|json',
            'priority_order' => 'required|integer|min:1|max:99',
            'is_active'      => 'boolean',
            'company_id'     => 'nullable|integer|exists:companies,id',
        ]);

        AiSetting::create([
            'provider_name'  => $data['provider_name'],
            'api_key'        => encrypt($data['api_key']),
            'extra_config'   => isset($data['extra_config']) ? json_decode($data['extra_config'], true) : null,
            'priority_order' => $data['priority_order'],
            'is_active'      => $request->boolean('is_active', true),
            'company_id'     => $data['company_id'] ?? null,
        ]);

        return response()->json(['status' => 'ok', 'message' => 'Proveedor registrado.'], 201);
    }

    public function update(Request $request, AiSetting $aiSetting)
    {
        $data = $request->validate([
            'provider_name'  => 'sometimes|string|max:50',
            'api_key'        => 'sometimes|string|max:512',
            'extra_config'   => 'nullable|json',
            'priority_order' => 'sometimes|integer|min:1|max:99',
            'is_active'      => 'boolean',
        ]);

        $payload = [];

        if (isset($data['provider_name']))  $payload['provider_name']  = $data['provider_name'];
        if (isset($data['api_key']))        $payload['api_key']        = encrypt($data['api_key']);
        if (array_key_exists('extra_config', $data))
            $payload['extra_config'] = $data['extra_config'] ? json_decode($data['extra_config'], true) : null;
        if (isset($data['priority_order'])) $payload['priority_order'] = $data['priority_order'];
        if ($request->has('is_active'))     $payload['is_active']      = $request->boolean('is_active');

        $aiSetting->update($payload);

        return response()->json(['status' => 'ok', 'message' => 'Proveedor actualizado.']);
    }

    public function destroy(AiSetting $aiSetting)
    {
        $aiSetting->delete();
        return response()->json(['status' => 'ok', 'message' => 'Proveedor eliminado.']);
    }

    // Enmascara la api_key cifrada: muestra solo los últimos 4 chars del texto plano
    private function maskKey(string $encryptedKey): string
    {
        try {
            $plain = decrypt($encryptedKey);
            $tail  = substr($plain, -4);
            return str_repeat('•', 8) . $tail;
        } catch (\Exception $e) {
            return '••••••••••••';
        }
    }
}

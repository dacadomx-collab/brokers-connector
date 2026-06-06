<?php

namespace App\Services;

use App\AiSetting;
use App\Services\AcadepAuraService;
use App\Services\Providers\OpenAIProvider;
use App\Services\Providers\GroqProvider;
use App\Services\Providers\MistralProvider;
use App\Services\Providers\GeminiProvider;

class AIService
{
    // Mapa provider_name → clase adaptadora (solo proveedores comerciales SDK-compatibles)
    // AcadepProvider eliminado del mapa: el nodo soberano usa bypass directo via AcadepAuraService.
    private const ADAPTERS = [
        'openai'  => OpenAIProvider::class,
        'groq'    => GroqProvider::class,
        'mistral' => MistralProvider::class,
        'gemini'  => GeminiProvider::class,
    ];

    /**
     * Dispara el payload al proveedor de mayor prioridad.
     * Para 'acadep': bypass directo a AcadepAuraService (protocolo OPEN KEY LAN/WAN).
     * Para el resto: adapters SDK comerciales con failover en cascada.
     *
     * @param  array    $payload     ['messages' => [...], 'temperature' => float]
     * @param  int|null $company_id  Tenant que hace la solicitud
     * @return array    Respuesta estandarizada: ['status'=>'ok', 'response'=>string, ...]
     * @throws \RuntimeException Si todos los proveedores activos fallan
     */
    public function dispatch(array $payload, ?int $company_id = null): array
    {
        $providers = AiSetting::where('is_active', 1)
            ->where(function ($q) use ($company_id) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $company_id);
            })
            ->orderBy('priority_order', 'asc')
            ->get();

        if ($providers->isEmpty()) {
            throw new \RuntimeException('No hay proveedores de IA activos configurados.');
        }

        foreach ($providers as $setting) {

            // ── BYPASS ACADEP (Protocolo OPEN KEY — LAN/WAN) ─────────────────────────
            // El nodo ACADEP usa X-AURA-KEY + despacho LAN/WAN desde .env.
            // No pasa por el factory de adapters SDK (incompatible por diseño).
            if ($setting->provider_name === 'acadep') {
                $acadep = new AcadepAuraService();

                if (!$acadep->isConfigured()) {
                    \Log::info('AIService: bypass ACADEP omitido — env vars no configuradas');
                    continue;
                }

                // Construir el prompt combinando todos los mensajes del payload
                $prompt = implode("\n\n---\n\n", array_map(function ($msg) {
                    $role    = strtoupper($msg['role']    ?? 'USER');
                    $content = $msg['content'] ?? '';
                    return "[{$role}]\n{$content}";
                }, $payload['messages'] ?? []));

                $result = $acadep->dispatch($prompt, self::AGENT_ID_ACADEP);

                if ($result['status'] === 'ok') {
                    return $result;
                }

                \Log::warning('AIService: bypass ACADEP falló — activando failover comercial', [
                    'status'     => $result['status'],
                    'error'      => $result['error'] ?? 'desconocido',
                    'company_id' => $company_id,
                ]);
                continue;
            }

            // ── Adapters comerciales (OpenAI / Groq / Mistral / Gemini) ─────────────
            $adapterClass = self::ADAPTERS[$setting->provider_name] ?? null;

            if (!$adapterClass) {
                \Log::warning('AIService: adaptador no registrado', [
                    'provider_name' => $setting->provider_name,
                    'setting_id'    => $setting->id,
                ]);
                continue;
            }

            $adapter = new $adapterClass();
            $result  = $adapter->request($payload, [
                'api_key'      => $setting->decryptedKey(),
                'extra_config' => $setting->extra_config ?? [],
            ]);

            if ($result['status'] === 'ok') {
                return $result;
            }

            \Log::warning('AIService: failover activado', [
                'failed_provider' => $setting->provider_name,
                'priority'        => $setting->priority_order,
                'error'           => $result['error'] ?? 'unknown',
                'company_id'      => $company_id,
            ]);
        }

        throw new \RuntimeException('Todos los proveedores de IA fallaron. Sin respuesta disponible.');
    }

    private const AGENT_ID_ACADEP = 'AURA_BKC_V1';
}

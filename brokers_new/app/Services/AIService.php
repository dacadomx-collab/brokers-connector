<?php

namespace App\Services;

use App\AiSetting;
use App\Services\Providers\OpenAIProvider;
use App\Services\Providers\GroqProvider;

class AIService
{
    // Mapa provider_name → clase adaptadora
    private const ADAPTERS = [
        'openai' => OpenAIProvider::class,
        'groq'   => GroqProvider::class,
    ];

    /**
     * Dispara el payload al proveedor de mayor prioridad.
     * Si falla, hace failover al siguiente en la escalera.
     *
     * @param  array       $payload      ['messages' => [...], 'temperature' => float]
     * @param  int|null    $company_id   Tenant que hace la solicitud
     * @return array       Respuesta estandarizada del adaptador ganador
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
}

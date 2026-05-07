<?php

namespace App\Services\Contracts;

interface AIProviderInterface
{
    /**
     * Envía un payload al proveedor de IA y retorna la respuesta estandarizada.
     *
     * @param  array  $payload  ['messages' => [...], 'temperature' => float]
     * @param  array  $config   ['api_key' => string, 'extra_config' => array|null]
     * @return array  ['status' => 'ok'|'error', 'provider' => string,
     *                 'response' => string, 'tokens_used' => int, 'latency_ms' => int]
     */
    public function request(array $payload, array $config): array;
}

<?php

namespace App\Services\Providers;

use App\Services\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * MistralProvider — Adaptador para Mistral AI
 *
 * API OpenAI-compatible: misma estructura de messages y response.
 * Diferencias relevantes:
 *   - Endpoint: api.mistral.ai (no api.openai.com)
 *   - Temperatura: rango 0-1 (OpenAI/Groq aceptan hasta 2)
 *   - json_object: soportado desde mistral-small-latest en adelante
 *   - Modelo por defecto: mistral-small-latest (balance costo/rendimiento)
 */
class MistralProvider implements AIProviderInterface
{
    const NAME     = 'mistral';
    const ENDPOINT = 'https://api.mistral.ai/v1/chat/completions';
    const TIMEOUT  = 30;

    public function request(array $payload, array $config): array
    {
        $start = microtime(true);
        $model = $config['extra_config']['model'] ?? 'mistral-small-latest';

        try {
            $client = new Client(['timeout' => self::TIMEOUT]);

            $body = [
                'model'    => $model,
                'messages' => $payload['messages'],
                // Mistral acepta temperatura en 0-1. Clampeamos para no enviar 2.0
                'temperature' => min(1.0, max(0.0, (float) ($payload['temperature'] ?? 0.7))),
            ];

            if (isset($payload['response_format'])) {
                $body['response_format'] = $payload['response_format'];
            }

            if (isset($payload['max_tokens'])) {
                $body['max_tokens'] = $payload['max_tokens'];
            }

            $response = $client->post(self::ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $body,
            ]);

            $data    = json_decode($response->getBody()->getContents(), true);
            $latency = (int) round((microtime(true) - $start) * 1000);

            return [
                'status'      => 'ok',
                'provider'    => self::NAME,
                'response'    => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'latency_ms'  => $latency,
            ];

        } catch (RequestException $e) {
            return [
                'status'      => 'error',
                'provider'    => self::NAME,
                'response'    => '',
                'tokens_used' => 0,
                'latency_ms'  => (int) round((microtime(true) - $start) * 1000),
                'error'       => $e->getMessage(),
            ];
        }
    }
}

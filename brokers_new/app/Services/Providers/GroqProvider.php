<?php

namespace App\Services\Providers;

use App\Services\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GroqProvider implements AIProviderInterface
{
    const NAME     = 'groq';
    const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    const TIMEOUT  = 15;

    public function request(array $payload, array $config): array
    {
        $start = microtime(true);
        // Groq usa la misma interfaz OpenAI-compatible; el modelo se puede
        // configurar por tenant vía extra_config o se usa el default ultra-rápido.
        $model = $config['extra_config']['model'] ?? 'llama3-8b-8192';

        try {
            $client   = new Client(['timeout' => self::TIMEOUT]);
            $response = $client->post(self::ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $payload['messages'],
                    'temperature' => $payload['temperature'] ?? 0.7,
                ],
            ]);

            $body    = json_decode($response->getBody()->getContents(), true);
            $latency = (int) round((microtime(true) - $start) * 1000);

            return [
                'status'      => 'ok',
                'provider'    => self::NAME,
                'response'    => $body['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $body['usage']['total_tokens'] ?? 0,
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

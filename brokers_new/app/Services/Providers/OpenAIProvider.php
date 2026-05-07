<?php

namespace App\Services\Providers;

use App\Services\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAIProvider implements AIProviderInterface
{
    const NAME    = 'openai';
    const TIMEOUT = 30;

    public function request(array $payload, array $config): array
    {
        $start  = microtime(true);
        $model  = $config['extra_config']['model'] ?? 'gpt-4o';

        try {
            $client   = new Client(['timeout' => self::TIMEOUT]);
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
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

            $body       = json_decode($response->getBody()->getContents(), true);
            $latency    = (int) round((microtime(true) - $start) * 1000);

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

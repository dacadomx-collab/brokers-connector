<?php

namespace App\Services\Providers;

use App\Services\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * GeminiProvider — Adaptador para Google Gemini Generative Language API
 *
 * DIFERENCIAS CRÍTICAS vs OpenAI / Groq / Mistral:
 *
 * 1. AUTH: La API Key va como query param `?key=`, NO como Bearer header.
 *    Jamás enviar la key en el header — Google la ignora y devuelve 403.
 *
 * 2. ENDPOINT: Incluye el nombre del modelo en la URL.
 *    https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
 *
 * 3. REQUEST BODY: Usa `contents` (no `messages`) con `parts` (no `content`).
 *    Los mensajes de sistema van en un campo separado `systemInstruction`.
 *    El rol "assistant" de OpenAI se llama "model" en Gemini.
 *
 * 4. RESPONSE: Los tokens de respuesta están en `candidates[0].content.parts[0].text`
 *    (no en `choices[0].message.content`).
 *
 * 5. JSON MODE: Se activa vía `generationConfig.responseMimeType = "application/json"`
 *    (no via `response_format.type = "json_object"`). Traducción automática aquí.
 *
 * 6. MAX TOKENS: Se llama `maxOutputTokens` en `generationConfig`
 *    (no `max_tokens` al nivel raíz).
 */
class GeminiProvider implements AIProviderInterface
{
    const NAME     = 'gemini';
    const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    const TIMEOUT  = 30;

    public function request(array $payload, array $config): array
    {
        $start  = microtime(true);
        // trim() obligatorio: el usuario puede pegar el nombre con espacios invisibles.
        // 'gemini-1.5-flash' sin sufijo NO es un model ID válido en v1beta —
        // el sufijo '-latest' es obligatorio para apuntar al alias más reciente.
        $model  = trim($config['extra_config']['model'] ?? 'gemini-1.5-flash-latest');
        $apiKey = $config['api_key'];

        // El model ID de Gemini solo contiene [a-z0-9.\-] — no necesita rawurlencode.
        // La key se codifica con urlencode() por si contiene caracteres especiales (+, /).
        $endpoint = self::BASE_URL . $model . ':generateContent?key=' . urlencode($apiKey);

        // ── Traducir messages de OpenAI → contents de Gemini ─────────────────
        $systemInstruction = null;
        $contents          = [];

        foreach ($payload['messages'] as $msg) {
            $role    = $msg['role']    ?? 'user';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                // Gemini: el system prompt va en systemInstruction, no en contents
                $systemInstruction = $content;
                continue;
            }

            // Gemini usa "model" donde OpenAI usa "assistant"
            $contents[] = [
                'role'  => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $content]],
            ];
        }

        // ── Construir el body ─────────────────────────────────────────────────
        $body = ['contents' => $contents];

        if ($systemInstruction !== null) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        // ── Traducir parámetros de generación ────────────────────────────────
        $generationConfig = [];

        if (isset($payload['temperature'])) {
            $generationConfig['temperature'] = (float) $payload['temperature'];
        }

        // max_tokens → maxOutputTokens (Gemini naming)
        if (isset($payload['max_tokens'])) {
            $generationConfig['maxOutputTokens'] = (int) $payload['max_tokens'];
        }

        // response_format.type = "json_object" → responseMimeType (Gemini naming)
        if (isset($payload['response_format']) && ($payload['response_format']['type'] ?? '') === 'json_object') {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        if (!empty($generationConfig)) {
            $body['generationConfig'] = $generationConfig;
        }

        try {
            $client = new Client(['timeout' => self::TIMEOUT]);

            $response = $client->post($endpoint, [
                'headers' => [
                    // No Authorization header — la key va en la URL
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $body,
            ]);

            $data    = json_decode($response->getBody()->getContents(), true);
            $latency = (int) round((microtime(true) - $start) * 1000);

            // ── Extraer respuesta del formato Gemini ──────────────────────────
            $text       = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? 0;

            return [
                'status'      => 'ok',
                'provider'    => self::NAME,
                'response'    => $text,
                'tokens_used' => $tokensUsed,
                'latency_ms'  => $latency,
            ];

        } catch (RequestException $e) {
            // Gemini devuelve errores JSON en el cuerpo de la respuesta HTTP
            // Guzzle los captura como RequestException con el body en getMessage()
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

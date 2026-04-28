<?php

namespace App\Http\Controllers;

use App\AiConversation;
use App\AiMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    /**
     * Número máximo de mensajes de historial que se envían a OpenAI como contexto.
     */
    const CONTEXT_WINDOW = 5;

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'         => 'required|string',
            'conversation_id' => 'nullable|integer',
        ]);

        // TENANT LOCK — Mandamiento #2: company_id siempre del servidor, nunca del payload
        $company_id = auth()->user()->company_id;

        if (!$company_id) {
            return response()->json(['error' => 'No company associated with this user.'], 403);
        }

        $user_id = auth()->user()->id;

        // ── Resolución del hilo de conversación ─────────────────────────────
        if ($request->conversation_id) {
            $conversation = AiConversation::where('id', $request->conversation_id)
                ->where('company_id', $company_id)   // cross-tenant guard
                ->first();

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found or access denied.'], 403);
            }
        } else {
            $conversation = AiConversation::create([
                'company_id' => $company_id,
                'user_id'    => $user_id,
                'title'      => mb_strimwidth($request->message, 0, 80, '…'),
                'status'     => 1,
            ]);
        }

        // ── Persistir mensaje del usuario ────────────────────────────────────
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $request->message,
            'tokens_used'     => 0,
        ]);

        // ── Recuperar historial (ventana de contexto) ────────────────────────
        // Se consultan DESPUÉS de guardar el mensaje del usuario para incluirlo en el contexto.
        $history = AiMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit(self::CONTEXT_WINDOW)
            ->get()
            ->reverse()   // cronológico ascendente para OpenAI
            ->values();

        $messages = [
            [
                'role'    => 'system',
                'content' => 'Eres un asistente experto en bienes raíces de la plataforma Brokers Connector. '
                           . 'Ayudas a los agentes a optimizar sus ventas y redactar correos. '
                           . 'Responde de forma profesional, concisa y orientada a la conversión.',
            ],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg->role,
                'content' => $msg->content,
            ];
        }

        // ── Llamada a OpenAI ─────────────────────────────────────────────────
        try {
            $client = new Client(['timeout' => 30]);

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'model'       => 'gpt-3.5-turbo',
                    'messages'    => $messages,
                    'temperature' => 0.7,
                ],
            ]);

            $body        = json_decode($response->getBody()->getContents(), true);
            $aiContent   = $body['choices'][0]['message']['content'] ?? '';
            $tokensUsed  = $body['usage']['total_tokens'] ?? 0;

        } catch (RequestException $e) {
            $status  = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 503;
            $message = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)['error']['message'] ?? $e->getMessage()
                : $e->getMessage();

            return response()->json([
                'error'   => 'OpenAI API error.',
                'details' => $message,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Unexpected error communicating with AI service.',
                'details' => $e->getMessage(),
            ], 500);
        }

        // ── Persistir respuesta del asistente ────────────────────────────────
        $assistantMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $aiContent,
            'tokens_used'     => $tokensUsed,
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message_id'      => $assistantMessage->id,
            'status'          => 'ok',
            'ai_response'     => $aiContent,
            'tokens_used'     => $tokensUsed,
        ], 200);
    }

    public function generateCopy(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'prop_type'   => 'nullable|string|max:100',
            'prop_status' => 'nullable|string|max:100',
            'bedrooms'    => 'nullable|integer|min:0',
            'baths'       => 'nullable|integer|min:0',
            'price'       => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|max:10',
        ]);

        // Early exit si la API Key aún no está configurada en el servidor
        if (empty(env('OPENAI_API_KEY'))) {
            return response()->json([
                'copy' => '⚠️ La Inteligencia Artificial está lista, pero falta configurar la API Key de OpenAI en el servidor para generar el texto.',
            ], 200);
        }

        // Construir el brief de la propiedad con los datos disponibles
        $parts = array_filter([
            $request->title       ? "Título: {$request->title}" : null,
            $request->prop_type   ? "Tipo de propiedad: {$request->prop_type}" : null,
            $request->prop_status ? "Operación: {$request->prop_status}" : null,
            $request->bedrooms    ? "Recámaras: {$request->bedrooms}" : null,
            $request->baths       ? "Baños: {$request->baths}" : null,
            ($request->price && $request->price > 0)
                ? 'Precio: ' . number_format((float)$request->price, 2) . " {$request->currency}"
                : null,
        ]);

        $propertyData = implode('. ', $parts) ?: 'Propiedad en venta.';

        $messages = [
            [
                'role'    => 'system',
                'content' => 'Eres un experto copywriter inmobiliario. Escribe una descripción persuasiva, '
                           . 'atractiva y orientada a la venta para una propiedad con las siguientes características: '
                           . $propertyData
                           . '. Usa viñetas para destacar beneficios y un llamado a la acción final. '
                           . 'Sé profesional pero emocionante. Máximo 3 párrafos.',
            ],
            [
                'role'    => 'user',
                'content' => 'Genera la descripción de venta para esta propiedad.',
            ],
        ];

        try {
            $client = new Client(['timeout' => 30]);

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'model'       => 'gpt-3.5-turbo',
                    'messages'    => $messages,
                    'temperature' => 0.8,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $copy = $body['choices'][0]['message']['content'] ?? '';

        } catch (\Exception $e) {
            // Cualquier fallo (API Key inválida, red, timeout) → respuesta amigable, sin romper la UI
            return response()->json([
                'copy' => '⚠️ La Inteligencia Artificial está lista, pero falta configurar la API Key de OpenAI en el servidor para generar el texto.',
            ], 200);
        }

        return response()->json(['copy' => $copy], 200);
    }
}

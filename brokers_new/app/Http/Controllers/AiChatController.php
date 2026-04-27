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
}

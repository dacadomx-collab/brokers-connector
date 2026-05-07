<?php

namespace App\Http\Controllers;

use App\AiConversation;
use App\AiMessage;
use App\Services\AIService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    const CONTEXT_WINDOW = 5;

    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'         => 'required|string|max:2000',
            'conversation_id' => 'nullable|integer',
        ]);

        // TENANT LOCK — company_id siempre del servidor, nunca del payload
        $company_id = auth()->user()->company_id;

        if (!$company_id) {
            return response()->json(['error' => 'No company associated with this user.'], 403);
        }

        $user_id = auth()->user()->id;

        // ── Resolución del hilo de conversación ──────────────────────────────
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

        // ── Persistir mensaje del usuario (antes de llamar a la IA) ──────────
        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $request->message,
            'tokens_used'     => 0,
        ]);

        // ── Construir ventana de contexto ────────────────────────────────────
        $history = AiMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit(self::CONTEXT_WINDOW)
            ->get()
            ->reverse()
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
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        // ── Orquestador con Failover Dinámico ────────────────────────────────
        try {
            $result = $this->aiService->dispatch(
                ['messages' => $messages, 'temperature' => 0.7],
                $company_id
            );
        } catch (\RuntimeException $e) {
            \Log::error('AiChat.sendMessage: todos los proveedores fallaron', [
                'user_id'         => auth()->id(),
                'company_id'      => $company_id,
                'conversation_id' => $conversation->id,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Los sistemas de IA están experimentando alta latencia. Intente de nuevo en unos momentos.',
            ], 500);
        }

        // ── Persistir respuesta del asistente ────────────────────────────────
        $assistantMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $result['response'],
            'tokens_used'     => $result['tokens_used'],
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message_id'      => $assistantMessage->id,
            'status'          => 'ok',
            'ai_response'     => $result['response'],
            'tokens_used'     => $result['tokens_used'],
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
                'content' => 'Eres un experto copywriter inmobiliario. '
                           . 'El usuario te proporcionará los datos estructurados de una propiedad. '
                           . 'Escribe una descripción persuasiva, atractiva y orientada a la venta. '
                           . 'Usa viñetas para destacar beneficios y un llamado a la acción final. '
                           . 'Sé profesional pero emocionante. Máximo 3 párrafos.',
            ],
            [
                'role'    => 'user',
                'content' => 'Genera la descripción de venta para esta propiedad con los siguientes datos: '
                           . $propertyData,
            ],
        ];

        // ONE-SHOT: sin persistencia en BD — Regla de Piedra del CODEX
        try {
            $result = $this->aiService->dispatch(
                ['messages' => $messages, 'temperature' => 0.8],
                auth()->user()->company_id
            );

            return response()->json(['copy' => $result['response']], 200);

        } catch (\Exception $e) {
            // Catch universal — nunca expone un 500 al frontend (CODEX §generateCopy)
            return response()->json([
                'copy' => '⚠️ La Inteligencia Artificial está lista, pero falta configurar un proveedor activo en el servidor para generar el texto.',
            ], 200);
        }
    }
}

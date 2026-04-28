{{-- Widget de Chat IA — Panel de agentes Brokers Connector
     Estilos en: public_html/newbrokers/css/main.css (sección 11)
     Lógica en:  public_html/newbrokers/js/aiChat.js
     Ruta:       POST /home/ai/chat (web — CSRF + sesión)
--}}

{{-- Botón flotante --}}
<button
    id="ai-chat-btn"
    class="ai-chat-btn"
    type="button"
    aria-label="Abrir asistente IA"
    title="Asistente IA"
>
    <i class="fa fa-comments" aria-hidden="true"></i>
</button>

{{-- Ventana de chat --}}
<div id="ai-chat-window" class="ai-chat-window" role="dialog" aria-label="Chat con asistente IA">

    {{-- Header --}}
    <header class="ai-chat-header">
        <span>
            <i class="fa fa-robot" aria-hidden="true"></i>
            Asistente Brokers IA
        </span>
        <button
            id="ai-chat-close"
            class="ai-chat-header-close"
            type="button"
            aria-label="Cerrar chat"
        >
            <i class="fa fa-times" aria-hidden="true"></i>
        </button>
    </header>

    {{-- Área de mensajes --}}
    <div id="ai-chat-messages" class="ai-chat-messages" aria-live="polite" aria-atomic="false">
        <div class="ai-message-assistant">
            ¡Hola! Soy tu asistente de Brokers Connector. ¿En qué puedo ayudarte hoy?
        </div>
    </div>

    {{-- Footer con formulario --}}
    <footer class="ai-chat-footer">
        <form id="ai-chat-form" autocomplete="off">
            <textarea
                id="ai-chat-input"
                class="ai-chat-input"
                name="message"
                rows="1"
                placeholder="Escribe tu mensaje…"
                aria-label="Mensaje para el asistente"
                maxlength="2000"
            ></textarea>
            <button
                id="ai-chat-send"
                class="ai-chat-send"
                type="submit"
                aria-label="Enviar mensaje"
            >
                <i class="fa fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </footer>

</div>

<script src="{{ asset('newbrokers/js/aiChat.js') }}"></script>

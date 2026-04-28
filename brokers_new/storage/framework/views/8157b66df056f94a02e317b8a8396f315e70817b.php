


<button
    id="ai-chat-btn"
    class="ai-chat-btn"
    type="button"
    aria-label="Abrir asistente IA"
    title="Asistente IA"
>
    <i class="fa fa-comments" aria-hidden="true"></i>
</button>


<div id="ai-chat-window" class="ai-chat-window" role="dialog" aria-label="Chat con asistente IA">

    
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

    
    <div id="ai-chat-messages" class="ai-chat-messages" aria-live="polite" aria-atomic="false">
        <div class="ai-message-assistant">
            ¡Hola! Soy tu asistente de Brokers Connector. ¿En qué puedo ayudarte hoy?
        </div>
    </div>

    
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

<script src="<?php echo e(asset('js/aiChat.js')); ?>"></script>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/components/ai-chat.blade.php ENDPATH**/ ?>
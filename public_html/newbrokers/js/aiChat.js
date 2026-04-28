/**
 * aiChat.js — Widget de Chat IA para el panel de Brokers Connector.
 * Autenticación: CSRF (sesión web del panel, X-CSRF-TOKEN desde <meta>).
 * Convención: camelCase (Mandamiento #7 Frontend).
 */
(function () {
    'use strict';

    /* ── Estado del widget ─────────────────────────────────────── */
    var conversationId = null;

    /* ── Referencias DOM (se resuelven al hacer DOMContentLoaded) ─ */
    var chatBtn, chatWindow, chatMessages, chatForm, chatInput, sendBtn;

    /* ── Token CSRF desde el <meta> del layout ─────────────────── */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* ── Abrir / Cerrar ventana ─────────────────────────────────── */
    function toggleChatWindow() {
        chatWindow.classList.toggle('ai-chat-open');

        if (chatWindow.classList.contains('ai-chat-open')) {
            chatInput.focus();
        }
    }

    function closeChatWindow() {
        chatWindow.classList.remove('ai-chat-open');
    }

    /* ── Renderizado de mensajes en el DOM ──────────────────────── */
    function appendMessage(role, text) {
        var bubble = document.createElement('div');
        bubble.className = (role === 'user') ? 'ai-message-user' : 'ai-message-assistant';
        bubble.textContent = text;
        chatMessages.appendChild(bubble);
        scrollToBottom();
        return bubble;
    }

    function showTypingIndicator() {
        var indicator = document.createElement('div');
        indicator.className = 'ai-message-typing';
        indicator.id = 'ai-typing-indicator';
        indicator.textContent = 'Asistente escribiendo…';
        chatMessages.appendChild(indicator);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        var indicator = document.getElementById('ai-typing-indicator');
        if (indicator) {
            indicator.parentNode.removeChild(indicator);
        }
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    /* ── Llamada al endpoint del panel (/home/ai/chat) ──────────── */
    function sendMessage(messageText) {
        var payload = { message: messageText };

        if (conversationId) {
            payload.conversation_id = conversationId;
        }

        return fetch('/home/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  getCsrfToken(),
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) {
                    throw new Error(err.error || 'Error al comunicarse con la IA.');
                });
            }
            return response.json();
        });
    }

    /* ── Handler del formulario ─────────────────────────────────── */
    function handleSubmit(event) {
        event.preventDefault();

        var messageText = chatInput.value.trim();
        if (!messageText) return;

        chatInput.value = '';
        sendBtn.disabled = true;

        appendMessage('user', messageText);
        showTypingIndicator();

        sendMessage(messageText)
            .then(function (data) {
                removeTypingIndicator();

                // Guardar conversation_id para el hilo actual
                conversationId = data.conversation_id;

                if (data.ai_response) {
                    appendMessage('assistant', data.ai_response);
                }
            })
            .catch(function (error) {
                removeTypingIndicator();
                appendMessage('assistant', 'Error: ' + error.message);
            })
            .finally(function () {
                sendBtn.disabled = false;
                chatInput.focus();
            });
    }

    /* ── Init ───────────────────────────────────────────────────── */
    function init() {
        chatBtn      = document.getElementById('ai-chat-btn');
        chatWindow   = document.getElementById('ai-chat-window');
        chatMessages = document.getElementById('ai-chat-messages');
        chatForm     = document.getElementById('ai-chat-form');
        chatInput    = document.getElementById('ai-chat-input');
        sendBtn      = document.getElementById('ai-chat-send');

        if (!chatBtn || !chatWindow) return;

        chatBtn.addEventListener('click', toggleChatWindow);

        document.getElementById('ai-chat-close')
            .addEventListener('click', closeChatWindow);

        chatForm.addEventListener('submit', handleSubmit);

        /* Enviar con Enter, salto de línea con Shift+Enter */
        chatInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());

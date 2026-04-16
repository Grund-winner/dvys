/**
 * DVYS AI - Chat JavaScript
 * Gestion de l'interface de conversation IA
 */

document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    let isSending = false;

    if (!messagesContainer || !input || !sendBtn) return;

    // Scroll en bas
    scrollToBottom();

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    // Envoi avec Enter (Shift+Enter = nouvelle ligne)
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Envoi avec bouton
    sendBtn.addEventListener('click', sendMessage);

    async function sendMessage() {
        const message = input.value.trim();
        if (!message || isSending) return;

        isSending = true;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<div class="spinner"></div>';

        // Ajouter le message utilisateur
        appendMessage('user', message);
        input.value = '';
        input.style.height = 'auto';
        scrollToBottom();

        // Afficher l'indicateur de frappe
        const typingEl = showTyping();

        try {
            const response = await fetch('/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            // Retirer l'indicateur de frappe
            typingEl.remove();

            if (data.success) {
                appendMessage('assistant', data.response);
            } else {
                appendMessage('assistant', '❌ ' + (data.error || 'Erreur de connexion. Réessaie.'));
            }
        } catch (error) {
            typingEl.remove();
            appendMessage('assistant', '❌ Erreur de connexion. Vérifie ta connexion internet et réessaie.');
        }

        scrollToBottom();
        isSending = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
        input.focus();
    }

    function appendMessage(role, content) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-msg ' + role;

        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar';
        avatar.textContent = role === 'assistant' ? '✦' : (document.querySelector('.dashboard-greeting')?.textContent?.split(',')[1]?.trim()?.charAt(0) || 'U');

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = formatContent(content);

        msgDiv.appendChild(avatar);
        msgDiv.appendChild(bubble);
        messagesContainer.appendChild(msgDiv);
    }

    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chat-msg assistant';
        typingDiv.innerHTML = `
            <div class="chat-avatar">✦</div>
            <div class="chat-typing">
                <div class="chat-typing-dot"></div>
                <div class="chat-typing-dot"></div>
                <div class="chat-typing-dot"></div>
            </div>
        `;
        messagesContainer.appendChild(typingDiv);
        scrollToBottom();
        return typingDiv;
    }

    function scrollToBottom() {
        requestAnimationFrame(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    }

    function formatContent(text) {
        // Convertir les sauts de ligne
        let html = text.replace(/\n/g, '<br>');
        // Convertir le **gras**
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Convertir les liens
        html = html.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
        return html;
    }
});

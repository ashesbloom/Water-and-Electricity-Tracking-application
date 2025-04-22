// JavaScript for AI Chatbot Interaction (Connecting to Node.js backend)

document.addEventListener('DOMContentLoaded', () => {
    const chatbox = document.getElementById('chatbox');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');

    // --- API URL Pointing to Node.js Server ---
    // This should already be set from the previous step
    const apiUrl = 'http://localhost:3000/api/chat';
    // ---

    // Function to add a message to the chatbox
    function addMessage(message, sender) {
        // ... (rest of function unchanged) ...
        if (!chatbox) return;
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'ai-message');
        messageDiv.textContent = message;
        chatbox.appendChild(messageDiv);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

     // Function to show loading indicator
     function showLoadingIndicator() {
        // ... (rest of function unchanged) ...
        if (!chatbox) return;
        if (document.getElementById('loading-indicator')) return;
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-indicator';
        loadingDiv.classList.add('message', 'ai-message');
        loadingDiv.style.opacity = '0';
        loadingDiv.style.transform = 'translateY(10px)';
        loadingDiv.innerHTML = '<span class="dot-flashing"></span>';
        chatbox.appendChild(loadingDiv);
        void loadingDiv.offsetWidth;
        loadingDiv.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        loadingDiv.style.opacity = '1';
        loadingDiv.style.transform = 'translateY(0)';
        chatbox.scrollTop = chatbox.scrollHeight;
     }

    // Function to hide loading indicator
    function hideLoadingIndicator() {
        // ... (rest of function unchanged) ...
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.opacity = '0';
            setTimeout(() => {
                loadingIndicator.remove();
            }, 300);
        }
    }


    // Handle form submission
    if (chatForm) {
        chatForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const userMessage = messageInput.value.trim();
            if (!userMessage) return;

            addMessage(userMessage, 'user');

            messageInput.value = '';
            messageInput.disabled = true;
            sendButton.disabled = true;
            sendButton.style.opacity = '0.6';

            showLoadingIndicator();

            try {
                // --- Fetch request to Node.js backend ---
                const response = await fetch(apiUrl, { // apiUrl points to Node.js
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: userMessage }) // Sending message
                });

                hideLoadingIndicator();

                const data = await response.json();

                if (!response.ok) {
                    console.error('API Error:', response.status, data);
                    const errorMessage = data?.error || `Server error (Status: ${response.status}). Check Node.js console.`;
                    addMessage(errorMessage, 'ai');
                } else {
                    if (data && data.reply) {
                        addMessage(data.reply, 'ai');
                    } else {
                        addMessage('Sorry, I received an unexpected response format.', 'ai');
                    }
                }

            } catch (error) {
                hideLoadingIndicator();
                console.error('Fetch Error:', error);
                 if (error instanceof SyntaxError) {
                     addMessage('Sorry, the server sent an invalid response. Check Node.js console.', 'ai');
                 } else {
                     addMessage('Sorry, I couldn\'t connect to the AI assistant server. Is it running? Check connection.', 'ai');
                 }
            } finally {
                messageInput.disabled = false;
                sendButton.disabled = false;
                sendButton.style.opacity = '1';
                messageInput.focus();
            }
        });
    } else {
        console.error("Chat form not found.");
    }

     if (messageInput) {
         messageInput.focus();
     }

});

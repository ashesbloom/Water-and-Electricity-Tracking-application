    // JavaScript for AI Chatbot Interaction (Connecting to Node.js backend)

    document.addEventListener('DOMContentLoaded', () => {
        const chatbox = document.getElementById('chatbox');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');

        // --- API URL Pointing to Node.js Server ---
        const apiUrl = 'http://localhost:3000/api/chat'; // Ensure this is correct
        // ---

        // Function to add a message to the chatbox
        function addMessage(message, sender) {
            if (!chatbox) return;
            const messageDiv = document.createElement('div');
            // Add base message class and sender-specific class
            messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'ai-message');

            // Basic Markdown Handling (Code Blocks and Bold)
            let formattedMessage = message.replace(/</g, "&lt;").replace(/>/g, "&gt;"); // Basic HTML escaping
            // Code blocks (```language ... ```)
             formattedMessage = formattedMessage.replace(/```(\w*)\s*([\s\S]*?)```/g, (match, lang, code) => {
                const languageClass = lang ? `language-${lang}` : '';
                // Ensure code inside pre/code is escaped properly if needed, but usually pre handles it
                const escapedCode = code; // Keep original code for pre/code tags
                return `<pre><code class="${languageClass}">${escapedCode}</code></pre>`;
            });
            // Bold (**text**) - ensure it doesn't interfere with code blocks
            formattedMessage = formattedMessage.replace(/(?<!`)\*\*(.*?)\*\*(?!`)/g, '<strong>$1</strong>');
             // Convert newlines to <br> outside of <pre> blocks
             const parts = formattedMessage.split(/(<pre>[\s\S]*?<\/pre>)/);
             formattedMessage = parts.map(part => {
                 if (part.startsWith('<pre>')) {
                     return part; // Keep <pre> block as is
                 }
                 return part.replace(/\n/g, '<br>'); // Convert newlines elsewhere
             }).join('');


            messageDiv.innerHTML = formattedMessage; // Use innerHTML to render formatting
            chatbox.appendChild(messageDiv);
            // Scroll to bottom after adding message
            chatbox.scrollTop = chatbox.scrollHeight;
        }


         // Function to show loading indicator
         function showLoadingIndicator() {
            if (!chatbox) return;
            // Avoid adding multiple indicators
            if (document.getElementById('loading-indicator')) return;

            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loading-indicator';
            // Apply base message and AI message styling
            loadingDiv.classList.add('message', 'ai-message');
            // Initial state for animation
            loadingDiv.style.opacity = '0';
            loadingDiv.style.transform = 'translateY(10px)';
            // Content (flashing dots)
            loadingDiv.innerHTML = '<span class="dot-flashing"></span>';
            chatbox.appendChild(loadingDiv);
            // Trigger reflow to apply initial styles before transition
            void loadingDiv.offsetWidth;
            // Apply transition and final state for fade-in/slide-up effect
            loadingDiv.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            loadingDiv.style.opacity = '1';
            loadingDiv.style.transform = 'translateY(0)';
            // Scroll to bottom
            chatbox.scrollTop = chatbox.scrollHeight;
         }

        // Function to hide loading indicator
        function hideLoadingIndicator() {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                // Fade out smoothly
                loadingIndicator.style.opacity = '0';
                // Remove after transition completes
                setTimeout(() => {
                    loadingIndicator.remove();
                }, 300); // Match transition duration
            }
        }


        // Handle form submission
        if (chatForm) {
            chatForm.addEventListener('submit', async (event) => {
                event.preventDefault(); // Prevent default form submission

                const userMessage = messageInput.value.trim();
                if (!userMessage) return; // Do nothing if input is empty

                // *** Check if USER_ID is available ***
                if (typeof USER_ID === 'undefined' || USER_ID === null) {
                    console.error("User ID not found. Cannot send message.");
                    addMessage("Error: Could not identify user. Please refresh and log in again.", 'ai');
                    return;
                }
                // *** End Check ***

                addMessage(userMessage, 'user'); // Display user's message

                messageInput.value = ''; // Clear input field
                // Disable input and button during processing
                messageInput.disabled = true;
                sendButton.disabled = true;
                sendButton.style.opacity = '0.6';

                showLoadingIndicator(); // Show "typing" indicator

                try {
                    // --- Fetch request to Node.js backend ---
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        // *** MODIFIED: Send message AND userId ***
                        body: JSON.stringify({
                            message: userMessage,
                            userId: USER_ID // Include the user ID from the global variable
                        })
                        // *** END MODIFIED ***
                    });

                    hideLoadingIndicator(); // Hide "typing" indicator

                    const data = await response.json(); // Parse JSON response

                    if (!response.ok) {
                        // Handle API errors (non-200 status)
                        console.error('API Error:', response.status, data);
                        const errorMessage = data?.error || `Server error (Status: ${response.status}). Check Node.js console.`;
                        addMessage(errorMessage, 'ai');
                    } else {
                        // Handle successful response
                        if (data && data.reply) {
                            addMessage(data.reply, 'ai'); // Display AI's reply
                        } else {
                            addMessage('Sorry, I received an unexpected response format.', 'ai');
                        }
                    }

                } catch (error) {
                    // Handle network errors or other fetch issues
                    hideLoadingIndicator();
                    console.error('Fetch Error:', error);
                     if (error instanceof SyntaxError) {
                         // Handle JSON parsing errors specifically
                         addMessage('Sorry, the server sent an invalid response. Check Node.js console.', 'ai');
                     } else {
                         // General connection error
                         addMessage('Sorry, I couldn\'t connect to the AI assistant server. Is it running? Check connection.', 'ai');
                     }
                } finally {
                    // Re-enable input and button after processing completes
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    sendButton.style.opacity = '1';
                    messageInput.focus(); // Set focus back to input field
                }
            });
        } else {
            console.error("Chat form not found.");
        }

         // Set initial focus on the message input
         if (messageInput) {
             messageInput.focus();
         }

    });
    
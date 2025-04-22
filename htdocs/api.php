<?php
// --- Configuration ---
// IMPORTANT: Replace with your actual Google AI Studio API Key
// NEVER hardcode API keys directly in production code. 
// Use environment variables or secure configuration methods.
$apiKey = 'AIzaSyDn6_bpYaabGtzHoDUmv5QjF6pz8bvZ_I0'; 

// Gemini API endpoint (use the appropriate model)
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

// --- Session Management ---
// Start a session to store conversation history
session_start();

// Initialize chat history if it doesn't exist
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// --- Function to call Gemini API ---
function callGeminiApi(string $prompt, string $apiUrl, array $history): ?string 
{
    // Construct the conversation history for the API request
    $contents = [];
    foreach ($history as $entry) {
        $contents[] = [
            'role' => $entry['role'],
            'parts' => [['text' => $entry['text']]]
        ];
    }
    // Add the current user prompt
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $prompt]]
    ];

    // Prepare the data payload for the API
    $data = [
        'contents' => $contents,
        // Optional: Add generationConfig if needed (temperature, maxOutputTokens, etc.)
        // 'generationConfig' => [
        //     'temperature' => 0.7,
        //     'maxOutputTokens' => 1000,
        // ]
    ];

    // Initialize cURL session
    $ch = curl_init($apiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    curl_setopt($ch, CURLOPT_POST, true);          // Set request method to POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Set POST data (JSON encoded)
    curl_setopt($ch, CURLOPT_HTTPHEADER, [         // Set headers
        'Content-Type: application/json',
    ]);
    // Optional: Disable SSL verification if needed (use with caution, e.g., for local development)
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    $error = curl_error($ch);                         // Get cURL error (if any)

    // Close cURL session
    curl_close($ch);

    // --- Handle Response ---
    if ($error) {
        // Handle cURL errors
        error_log("cURL Error: " . $error);
        return "Error: Could not connect to the API.";
    }

    if ($httpCode !== 200) {
        // Handle API errors (non-200 status code)
        error_log("API Error: HTTP Code " . $httpCode . " Response: " . $response);
        return "Error: Received status code " . $httpCode . " from API.";
    }

    $responseData = json_decode($response, true); // Decode JSON response

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        return "Error: Could not decode API response.";
    }
    
    // --- Extract Text ---
    // Navigate the response structure to get the generated text
    // Structure might vary slightly based on API version/model
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        // Log the unexpected response structure for debugging
        error_log("Unexpected API response structure: " . $response);
        // Check for safety ratings/blocks
        if (isset($responseData['promptFeedback']['blockReason'])) {
             return "Response blocked due to: " . $responseData['promptFeedback']['blockReason'];
        }
        return "Error: Could not extract text from API response.";
    }
}

// --- Handle Form Submission ---
$userMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);

    if (!empty($userMessage)) {
        // Add user message to history
        $_SESSION['chat_history'][] = ['role' => 'user', 'text' => $userMessage];

        // Call the Gemini API
        $botResponse = callGeminiApi($userMessage, $apiUrl, $_SESSION['chat_history']);

        // Add bot response to history
        if ($botResponse) {
            $_SESSION['chat_history'][] = ['role' => 'model', 'text' => $botResponse];
        } else {
             // Handle cases where the API call failed but didn't return a specific error message
            $_SESSION['chat_history'][] = ['role' => 'model', 'text' => 'Sorry, I encountered an error. Please try again.'];
        }
        
        // Clear the input field after submission (optional)
        $userMessage = ''; 
        
        // Redirect to prevent form resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --- Reset Chat ---
if (isset($_GET['reset'])) {
    $_SESSION['chat_history'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Gemini Chatbot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Basic styling for chat bubbles */
        .chat-bubble {
            max-width: 75%;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 10px;
            word-wrap: break-word; /* Ensure long words break */
        }
        .user-bubble {
            background-color: #DCF8C6; /* Light green */
            margin-left: auto; /* Align to right */
            border-bottom-right-radius: 0;
        }
        .bot-bubble {
            background-color: #E5E7EB; /* Light gray */
            margin-right: auto; /* Align to left */
            border-bottom-left-radius: 0;
        }
         /* Style for code blocks within chat */
        .chat-bubble pre {
            background-color: #2d2d2d; /* Dark background for code */
            color: #f0f0f0; /* Light text */
            padding: 10px;
            border-radius: 8px;
            overflow-x: auto; /* Allow horizontal scrolling for long code lines */
            white-space: pre-wrap; /* Wrap long lines but preserve formatting */
            word-wrap: break-word;
        }
        .chat-bubble code {
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col h-screen font-sans">

    <header class="bg-blue-600 text-white p-4 text-center shadow-md">
        <h1 class="text-2xl font-bold">PHP Chatbot (Gemini API)</h1>
    </header>

    <div id="chatbox" class="flex-1 overflow-y-auto p-4 space-y-4 bg-white m-4 rounded-lg shadow-inner">
        <?php if (empty($_SESSION['chat_history'])): ?>
            <p class="text-center text-gray-500">Start chatting by typing a message below!</p>
        <?php else: ?>
            <?php foreach ($_SESSION['chat_history'] as $entry): ?>
                <div class="flex <?php echo ($entry['role'] === 'user') ? 'justify-end' : 'justify-start'; ?>">
                    <div class="chat-bubble <?php echo ($entry['role'] === 'user') ? 'user-bubble' : 'bot-bubble'; ?>">
                        <strong><?php echo ucfirst($entry['role']); ?>:</strong>
                        <?php 
                           // Basic Markdown rendering for code blocks (```) and bold (** **)
                           $text = htmlspecialchars($entry['text']); // Escape HTML first
                           // Render code blocks
                           $text = preg_replace('/```(\w*)\s*([\s\S]*?)```/m', '<pre><code class="language-$1">$2</code></pre>', $text);
                           // Render bold text
                           $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
                           // Convert newlines to <br> tags (outside of pre blocks)
                           // This requires a more complex regex or parsing approach. 
                           // For simplicity, we'll do a basic nl2br, but it might affect <pre> blocks.
                           // A better approach would parse Markdown properly.
                           echo nl2br($text); 
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="p-4 bg-gray-200 border-t border-gray-300">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="flex items-center space-x-2">
            <input 
                type="text" 
                name="message" 
                placeholder="Type your message..." 
                autocomplete="off"
                required
                class="flex-1 p-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?php echo htmlspecialchars($userMessage); /* Retain input if needed, though redirect clears it */ ?>">
            <button 
                type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full transition duration-150 ease-in-out shadow">
                Send
            </button>
            <a 
                href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?reset=1" 
                onclick="return confirm('Are you sure you want to reset the chat?');"
                class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-full transition duration-150 ease-in-out shadow text-sm"
                title="Reset Chat">
                Reset
            </a>
        </form>
    </footer>

    <script>
        // Auto-scroll to the bottom of the chatbox
        const chatbox = document.getElementById('chatbox');
        chatbox.scrollTop = chatbox.scrollHeight;
    </script>

</body>
</html>

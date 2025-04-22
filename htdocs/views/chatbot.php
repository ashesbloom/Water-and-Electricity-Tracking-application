<?php
// htdocs/views/chatbot.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    if (function_exists('redirectWithFlashMessage')) {
        redirectWithFlashMessage('/login', 'Please log in to use the AI Chatbot.', 'error');
    } else {
        $_SESSION['flash_message'] = 'Please log in to use the AI Chatbot.';
        $_SESSION['flash_message_type'] = 'error';
        if (!defined('BASE_URL_PATH')) { define('BASE_URL_PATH', '/tracker'); }
        $loginUrl = rtrim(BASE_URL_PATH, '/') . '/login';
        header("Location: " . $loginUrl);
        exit;
    }
}

// Define base path if not already defined
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker');
}

$username = $_SESSION['username'] ?? 'User';
$currentPage = 'chatbot';

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Usage Assistant - GridSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind config - define colors for CSS reference below
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    borderRadius: { 'lg': '0.5rem', 'xl': '0.75rem' },
                    colors: {
                        // Base Theme
                        'light-bg': '#F3F4F6',
                        'light-card-bg-raw': 'rgba(255, 255, 255, 0.7)', // Glass effect bg
                        'light-profile': '#FFFFFF',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border-raw': 'rgba(229, 231, 235, 0.6)', // Glass border
                        'light-accent': '#2563EB', // Blue 600
                        'light-accent-hover': '#1D4ED8', // Blue 700
                        'gold-accent': '#ecc931', // Gold
                        'gold-accent-hover': '#ca8a04', // Darker Gold
                        'dark-card-bg-raw': 'rgba(31, 41, 55, 0.7)', // Glass effect bg dark
                        'dark-bg': '#111827', // Gray 900
                        'dark-text-primary': '#F9FAFB', // Gray 50
                        'dark-text-secondary': '#9CA3AF', // Gray 400
                        'dark-border-raw': 'rgba(75, 85, 99, 0.6)', // Glass border dark
                        'dark-profile': 'rgba(31, 41, 55)', // Gray 800 solid
                        'dark-input-bg': 'rgba(55, 65, 81, 0.85)', // Input bg dark

                        // User Message Colors (Theme Aware)
                        'user-message-bg-light': 'rgba(59, 130, 246, 0.45)', // Blue 500 @ 45%
                        'user-message-border-light': 'rgba(37, 99, 235, 0.55)', // Blue 600 @ 55%
                        'user-message-text-light': '#1E3A8A', // Blue 800 text
                        'user-message-glow-light': 'rgba(59, 130, 246, 0.45)', // Blue 500 @ 45% glow

                        'user-message-bg-dark': 'rgba(236, 201, 49, 0.40)', // Gold @ 40% <<< CHANGED
                        'user-message-border-dark': 'rgba(236, 201, 49, 0.60)', // Gold @ 60% <<< CHANGED
                        'user-message-text-dark': '#eeeff1', // Dark brown/gold text <<< CHANGED
                        'user-message-glow-dark': 'rgba(236, 201, 49, 0.40)', // Gold @ 40% glow <<< CHANGED

                        // AI Message Colors (Neutral)
                        'ai-message-bg-light': 'rgba(229, 231, 235, 0.6)', // Gray 200 @ 60%
                        'ai-message-border-light': 'rgba(209, 213, 219, 0.7)', // Gray 300 @ 70%
                        'ai-message-text-light': '#111827', // Gray 900 text
                        'ai-message-glow-light': 'rgba(156, 163, 175, 0.45)', // Gray 400 @ 45% glow

                        'ai-message-bg-dark': 'rgba(75, 85, 99, 0.6)', // Gray 600 @ 60%
                        'ai-message-border-dark': 'rgba(107, 114, 128, 0.7)', // Gray 500 @ 70%
                        'ai-message-text-dark': '#eeeff1', // Gray 100 text
                        'ai-message-glow-dark': 'rgba(156, 163, 175, 0.45)', // Gray 400 @ 45% glow
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <style>
        /* Chat Area Container Styling (Glass Box) */
        .chat-area-box {
            position: relative;
            z-index: 0;
            overflow: hidden;
            /* Using raw values from config - slightly adjusted opacity */
            background-color: rgba(255, 255, 255, 0.7); /* light-card-bg-raw */
            border-radius: 0.75rem; /* xl */
            border: 1px solid rgba(229, 231, 235, 0.6); /* light-border-raw */
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.75); /* Slightly stronger inner highlight */
            /* Optional: Add backdrop blur */
            /* backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); */
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .dark .chat-area-box {
            background-color: rgba(31, 41, 55, 0.7); /* dark-card-bg-raw */
            border-color: rgba(75, 85, 99, 0.6); /* dark-border-raw */
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
        }

        /* Chatbox specific styling */
        #chatbox {
            height: calc(100vh - 290px); /* Adjusted height slightly more */
            overflow-y: auto;
            scroll-behavior: smooth;
            padding: 1.25rem; /* Increased padding */
        }

        /* Custom Scrollbar */
        #chatbox::-webkit-scrollbar { width: 8px; }
        #chatbox::-webkit-scrollbar-track { background: transparent; }
        #chatbox::-webkit-scrollbar-thumb { background-color: rgba(0, 0, 0, 0.25); border-radius: 4px; border: 2px solid transparent; background-clip: content-box; transition: background-color 0.2s ease; }
        .dark #chatbox::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.25); }
        #chatbox::-webkit-scrollbar-thumb:hover { background-color: rgba(0, 0, 0, 0.4); }
        .dark #chatbox::-webkit-scrollbar-thumb:hover { background-color: rgba(255, 255, 255, 0.4); }


        /* Base Message Styling with Enhanced Glass/Glow/Animation */
        .message {
            max-width: 80%;
            padding: 0.75rem 1.1rem;
            border-radius: 0.75rem; /* xl */
            margin-bottom: 1rem;
            line-height: 1.55;
            word-wrap: break-word;
            position: relative;
            z-index: 1;
            border: 1px solid transparent;
            /* Glass Effect */
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            /* Animation */
            opacity: 0;
            transform: translateY(15px) scale(0.98);
            animation: messageFadeInSmooth 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            /* Transitions */
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        }
        /* Subtle hover effect */
        .message:hover {
            transform: scale(1.01);
            /* Optional: slightly adjust shadow on hover */
            /* box-shadow: 0 4px 12px [current glow color]; */
        }

        /* Smoother Message Animation */
        @keyframes messageFadeInSmooth {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* User Message Styles (Theme Aware) */
        .user-message {
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
            /* Light Mode (Blue Accent) */
            background-color: rgba(59, 130, 246, 0.45); /* user-message-bg-light */
            border-color: rgba(37, 99, 235, 0.55); /* user-message-border-light */
            color: #1f2937; /* user-message-text-light */
            box-shadow: 0 3px 10px rgba(59, 130, 246, 0.45); /* user-message-glow-light */
        }
        .dark .user-message {
            /* Dark Mode (Gold Accent) - Using raw values from config */
            background-color: rgba(236, 201, 49, 0.40); /* user-message-bg-dark */
            border-color: rgba(236, 201, 49, 0.60); /* user-message-border-dark */
            color: #eeeff1; /* user-message-text-dark */
            box-shadow: 0 3px 10px rgba(236, 201, 49, 0.40); /* user-message-glow-dark */
        }

        /* AI Message Styles (Neutral Glassy) */
        .ai-message {
            margin-right: auto;
            border-bottom-left-radius: 0.25rem;
            /* Light Mode */
            background-color: rgba(229, 231, 235, 0.6); /* ai-message-bg-light */
            border-color: rgba(209, 213, 219, 0.7); /* ai-message-border-light */
            color: #111827; /* ai-message-text-light */
            box-shadow: 0 3px 10px rgba(156, 163, 175, 0.45); /* ai-message-glow-light */
        }
         .dark .ai-message {
            /* Dark Mode */
            background-color: rgba(75, 85, 99, 0.6); /* ai-message-bg-dark */
            border-color: rgba(107, 114, 128, 0.7); /* ai-message-border-dark */
            color: #F3F4F6; /* ai-message-text-dark */
            box-shadow: 0 3px 10px rgba(156, 163, 175, 0.45); /* ai-message-glow-dark */
        }

        /* Loading Indicator Styling */
        .ai-message .dot-flashing {
             color: #111827; /* ai-message-text-light */
        }
        .dark .ai-message .dot-flashing {
             color: #F3F4F6; /* ai-message-text-dark */
        }
        .dot-flashing {
          position: relative; width: 6px; height: 6px; border-radius: 5px;
          background-color: currentColor; color: currentColor;
          animation: dot-flashing 1s infinite linear alternate;
          animation-delay: .5s; display: inline-block; margin: 0 3px;
        }
        .dot-flashing::before, .dot-flashing::after {
          content: ''; display: inline-block; position: absolute; top: 0;
          width: 6px; height: 6px; border-radius: 5px;
          background-color: currentColor; color: currentColor;
          animation: dot-flashing 1s infinite alternate;
        }
        .dot-flashing::before { left: -12px; animation-delay: 0s; }
        .dot-flashing::after { left: 12px; animation-delay: 1s; }

        @keyframes dot-flashing {
          0% { background-color: currentColor; transform: scale(1); opacity: 0.8; }
          50%, 100% { background-color: rgba(156, 163, 175, 0.4); transform: scale(0.8); opacity: 0.5; }
        }
        .dark @keyframes dot-flashing {
             0% { background-color: currentColor; transform: scale(1); opacity: 0.8; }
             50%, 100% { background-color: rgba(107, 114, 128, 0.5); transform: scale(0.8); opacity: 0.5; }
        }

        /* Input Area Styling */
        .chat-input-area {
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            /* Inherits .chat-area-box styles */
        }

        #message-input {
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            /* Using raw values from config */
            background-color: rgba(255, 255, 255, 0.85); /* Slightly more opaque input */
            border: 1px solid rgba(209, 213, 219, 0.8); /* Slightly stronger border */
            color: #1F2937; /* light-text-primary */
            border-radius: 0.5rem; /* lg */
            padding: 0.65rem 1rem; /* Match message padding */
        }
        .dark #message-input {
             background-color: rgba(55, 65, 81, 0.9); /* dark-input-bg based */
             border-color: rgba(75, 85, 99, 0.8); /* dark-border based */
             color: #F9FAFB; /* dark-text-primary */
        }
        /* Tailwind handles focus:ring, let's add focus border */
        #message-input:focus {
             border-color: #2563EB; /* light-accent */
        }
        .dark #message-input:focus {
             border-color: #ecc931; /* gold-accent */
        }


        #send-button {
            /* Using raw values from config */
            background-color: #2563EB; /* light-accent */
            color: white;
            border-radius: 0.5rem; /* lg */
            padding: 0.65rem 1.25rem; /* Match input height, adjust width */
            transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .dark #send-button {
             background-color: #ecc931; /* gold-accent */
             color: #111827; /* dark-bg */
             box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        #send-button:hover {
             background-color: #1D4ED8; /* light-accent-hover */
             box-shadow: 0 2px 5px rgba(0,0,0,0.15);
             transform: translateY(-1px);
        }
         .dark #send-button:hover {
             background-color: #ca8a04; /* gold-accent-hover */
             box-shadow: 0 2px 5px rgba(0,0,0,0.4);
             transform: translateY(-1px);
         }
        #send-button:active {
            transform: scale(0.97) translateY(0);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
        }
        #send-button:disabled {
            opacity: 0.5; /* More pronounced disabled state */
            cursor: not-allowed;
            transform: translateY(0);
            box-shadow: none;
        }

    </style>
</head>

<body class="bg-light-bg dark:bg-dark-bg flex flex-col min-h-screen font-sans">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="flex-grow flex flex-col p-4 md:p-6 max-w-4xl mx-auto w-full">
        <h1 class="text-2xl font-semibold mb-4 text-center text-light-text-primary dark:text-dark-text-primary">
            AI Usage Assistant
        </h1>

        <div id="chat-container" class="chat-area-box flex-grow flex flex-col overflow-hidden mb-4">
            <div id="chatbox" class="flex-grow">
                <div class="message ai-message" style="opacity: 1; transform: translateY(0);"> Hello <?php echo $username; ?>! How can I help you analyze your electricity and water usage today? Ask me things like "What was my peak electricity usage last month?" or "Show notes for water usage in April".
                </div>
                </div>
        </div>

        <div class="chat-input-area chat-area-box mt-auto">
            <form id="chat-form" class="flex items-center space-x-3">
                <input type="text" id="message-input" placeholder="Ask about your usage..." required autocomplete="off"
                       class="flex-grow border rounded-lg focus:outline-none focus:ring-2 focus:ring-light-accent dark:focus:ring-gold-accent focus:border-transparent text-sm transition duration-200">
                       <button type="submit" id="send-button"
                        class="font-semibold transition-all duration-200 text-sm flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    <span class="ml-1.5">Send</span>
                </button>
            </form>
        </div>
    </main>

    <?php
      // Footer removed for cleaner chat interface
    ?>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/chatbot_script.js" defer></script>

</body>
</html>

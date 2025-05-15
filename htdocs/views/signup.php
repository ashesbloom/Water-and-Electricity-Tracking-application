<?php
// Session should already be started by index.php

// If user is already logged in, redirect to homepage
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
    header("Location: " . $baseUrl . '/homepage');
    exit;
}

// Define base path if not already defined
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker'); 
}

// --- CORRECTED MESSAGE HANDLING ---
// Get the generic message from session
$message = $_SESSION['message'] ?? null;
$message_type = 'neutral'; 

if ($message) {
    // Determine message type (simple check for keywords) 
    if (stripos($message, 'success') !== false || stripos($message, 'created') !== false) {
        $message_type = 'success';
    } elseif (stripos($message, 'invalid') !== false || stripos($message, 'required') !== false || stripos($message, 'error') !== false || stripos($message, 'failed') !== false || stripos($message, 'taken') !== false) {
        $message_type = 'error';
    }
    unset($_SESSION['message']); // Clear the generic message after retrieving it
}
// --- END CORRECTED MESSAGE HANDLING ---

// Construct the absolute form action URL
$formAction = rtrim(BASE_URL_PATH, '/') . '/auth/register'; // Action points to the register route

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GridSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind config 
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                     colors: {
                        'light-bg': '#F9FAFB',
                        'light-card-bg': '#FFFFFF',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#6B7280',
                        'light-border': '#E5E7EB',
                        'light-input-bg': '#F3F4F6',
                        'light-accent': '#3B82F6',
                        'light-accent-hover': '#2563EB',
                        'dark-bg': '#111827',
                        'dark-card-bg': '#1F2937',
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#374151',
                        'dark-input-bg': '#374151',
                        'dark-accent': '#ecc931',
                        'dark-accent-hover': '#ca8a04',
                        // Blob Colors
                        'blob-color-1': '#8B5CF6',
                        'blob-color-2': '#EC4899',
                        'blob-color-3': '#3B82F6',
                        'blob-color-4': '#F59E0B',
                        'blob-color-5': '#10B981',
                        'blob-color-6': '#6366F1',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/output.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/auth_styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-light-bg dark:bg-dark-bg">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="auth-container">

            <div class="left-panel md:w-1/2">
                <div class="mb-6">
                     <img src="<?php echo BASE_URL_PATH; ?>/asset/logo.png" alt="GridSync Logo" class="h-10 w-auto" onerror="this.parentElement.innerHTML = '<div class=\'text-2xl font-bold text-light-text-primary dark:text-dark-text-primary\'>GridSync</div>'">
                </div>

                <h1 class="text-3xl font-bold mb-2 text-light-text-primary dark:text-dark-text-primary">Create Account</h1>
                <p class="text-sm text-light-text-secondary dark:text-dark-text-secondary mb-6">Join the GridSync community</p>

                <div id="message-area" class="mb-4 text-sm min-h-[2.5rem] w-full max-w-xs mx-auto">
                    <?php if ($message): ?>
                        <?php
                            $bgColor = 'bg-gray-100 dark:bg-gray-700'; 
                            $borderColor = 'border-gray-400 dark:border-gray-600';
                            $textColor = 'text-gray-700 dark:text-gray-300';

                            if ($message_type === 'success') {
                                $bgColor = 'bg-green-100 dark:bg-green-900/30';
                                $borderColor = 'border-green-400 dark:border-green-600';
                                $textColor = 'text-green-700 dark:text-green-300';
                            } elseif ($message_type === 'error') {
                                $bgColor = 'bg-red-100 dark:bg-red-900/30';
                                $borderColor = 'border-red-400 dark:border-red-600';
                                $textColor = 'text-red-700 dark:text-red-300';
                            }
                        ?>
                        <div class="p-3 border <?php echo $borderColor; ?> <?php echo $bgColor; ?> <?php echo $textColor; ?> rounded-md">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="<?php echo htmlspecialchars($formAction); ?>" method="POST" class="w-full max-w-xs mx-auto space-y-4">
                    <div>
                         <label for="username" class="sr-only">Username</label>
                         <input type="text" id="username" name="username" required placeholder="Username" autocomplete="username"
                               class="form-input">
                    </div>
                    <div>
                         <label for="email" class="sr-only">Email</label>
                         <input type="email" id="email" name="email" required placeholder="Email" autocomplete="email"
                               class="form-input">
                    </div>
                    <div>
                         <label for="password" class="sr-only">Password</label>
                         <input type="password" id="password" name="password" required placeholder="Password" autocomplete="new-password"
                               class="form-input">
                    </div>
                     <div>
                         <label for="confirm_password" class="sr-only">Confirm Password</label>
                         <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password" autocomplete="new-password"
                               class="form-input">
                    </div>

                    <button type="submit" class="form-button-pill mt-6">Sign Up</button>

                    <p class="mt-6 text-center text-xs text-light-text-secondary dark:text-dark-text-secondary">
                        Already have an account?
                        <a href="<?php echo BASE_URL_PATH; ?>/auth/login" class="font-semibold text-light-accent dark:text-dark-accent hover:underline">Sign in</a>
                    </p>
                </form> 
             </div> 
             <div class="right-panel md:w-1/2 hidden md:flex">
                <div class="blob-container">
                    <div class="blob blob-1"></div>
                    <div class="blob blob-2"></div>
                    <div class="blob blob-3"></div>
                    <div class="blob blob-4"></div>
                    <div class="blob blob-5"></div>
                    <div class="blob blob-6"></div>
                </div>
                <div class="blur-overlay"></div>

                <div class="right-panel-content">
                    <div class="top-buttons">
                        <a href="<?php echo BASE_URL_PATH; ?>/auth/login" class="top-button">Sign In</a>
                    </div>
                    <div class="bottom-text mt-auto">
                        <h3>Track your consumption, save resources.</h3>
                        <p>Monitor your electricity and water usage to understand patterns, optimize consumption, and contribute to a sustainable future.</p>
                    </div>
                </div>
            </div> 
        </div> 
    </div> 
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/auth_script.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    const msgArea = document.getElementById('message-area');
                    if(msgArea) {
                        msgArea.innerHTML = ''; // Clear previous messages
                        const msgDiv = msgArea.querySelector('div');
                        if (msgDiv) msgDiv.remove();
                    }
                });
            });
        });
    </script>

</body>
</html>


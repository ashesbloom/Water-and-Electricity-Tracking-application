<?php
// Session should already be started by index.php

// If user is already logged in, redirect to homepage
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
    header("Location: " . $baseUrl . '/homepage');
    exit;
}

if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker'); 
}

// Get error/success messages from session
$message = $_SESSION['message'] ?? null;
$message_type = 'neutral'; 

if ($message) {

    // Determine message type (simple check for keywords)
    if (stripos($message, 'success') !== false || stripos($message, 'created') !== false) {
        $message_type = 'success';
    } elseif (stripos($message, 'invalid') !== false || stripos($message, 'required') !== false || stripos($message, 'error') !== false || stripos($message, 'failed') !== false) {
        $message_type = 'error';
    }
    unset($_SESSION['message']); // Clear the message after retrieving it
}

// Specific message for logout
if (isset($_GET['logged_out'])) {
    $message = 'You have been logged out successfully.';
    $message_type = 'success';
}

// Construct the absolute form action URL
$formAction = rtrim(BASE_URL_PATH, '/') . '/auth/login';

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - GridSync</title>
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
                    <img src="<?php echo BASE_URL_PATH; ?>/asset/logo.png" alt="GridSync Logo" class="h-10 w-auto"
                        onerror="this.parentElement.innerHTML = '<div class=\'text-2xl font-bold text-light-text-primary dark:text-dark-text-primary\'>GridSync</div>'">
                </div>

                <h1 class="text-3xl font-bold mb-2 text-light-text-primary dark:text-dark-text-primary">Welcome back!</h1>
                <p class="text-sm text-light-text-secondary dark:text-dark-text-secondary mb-6">Sign in to your dashboard</p>

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

                    <div class="mb-4">
                        <label for="phone" class="sr-only">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your Mobile Number" autocomplete="tel" class="form-input">
                    </div>

                    <div class="my-4 flex items-center">
                        <hr class="flex-grow border-t border-light-border dark:border-dark-border">
                        <span class="mx-2 text-xs text-light-text-secondary dark:text-dark-text-secondary">or</span>
                        <hr class="flex-grow border-t border-light-border dark:border-dark-border">
                    </div>

                    <div class="mb-4">
                        <label for="identifier" class="sr-only">Username or Email</label>
                        <input type="text" id="identifier" name="identifier" required placeholder="Username or Email" autocomplete="username" class="form-input">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Password" autocomplete="current-password" class="form-input">
                    </div>

                    <button type="submit" class="form-button-pill">Log In</button>
                    
                    <p class="mt-6 text-center text-xs text-light-text-secondary dark:text-dark-text-secondary">
                        Don't have an account?
                        <a href="<?php echo BASE_URL_PATH; ?>/auth/register" class="font-semibold text-light-accent dark:text-dark-accent hover:underline">Sign up</a>
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
                        <a href="<?php echo BASE_URL_PATH; ?>/auth/register" class="top-button">Sign Up</a>
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
                    if (msgArea) {
                        msgArea.innerHTML = ''; // Clear content
                        const msgDiv = msgArea.querySelector('div');
                        if (msgDiv) msgDiv.remove(); // Remove inner div as well
                    }
                });
            });

            // Note: Theme toggling should be handled by the global dynamic.js
        });
    </script>

</body>

</html>

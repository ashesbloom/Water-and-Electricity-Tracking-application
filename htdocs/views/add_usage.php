<?php
// Start session FIRST (might be redundant if index.php already started it, but safe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Keep your existing setup ---
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$currentPage = 'add_usage'; // Set current page for active link highlighting

// Define base path if not already defined (e.g., by index.php)
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker'); // Adjust if needed
}

// --- REMOVED: require_once for UsageController (now included in index.php) ---

// Redirect if not logged in (redundant if index.php checks, but safe)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL_PATH . '/signin');
    exit();
}

$userId = $_SESSION['user_id'];
$message = ''; // To store success or error messages
$messageType = ''; // 'success' or 'error'

// --- Handle Form Submission (Works for both forms now) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required fields are set
    if (isset($_POST['usage_type'], $_POST['usage_amount'], $_POST['usage_date'])) {
        // Retrieve form data
        $usageType = $_POST['usage_type'];
        $usageAmount = $_POST['usage_amount'];
        $usageDate = $_POST['usage_date'];

        // *** ADDED: Get notes based on usage type ***
        $notes = null; // Default to null
        if ($usageType === 'electricity' && isset($_POST['elect_notes'])) {
            $notes = trim($_POST['elect_notes']);
        } elseif ($usageType === 'water' && isset($_POST['water_notes'])) {
            $notes = trim($_POST['water_notes']);
        }
        // Ensure empty strings are stored as NULL if the column allows it
        if ($notes === '') {
            $notes = null;
        }
        // *** END ADDED ***

        // Instantiate the controller
        $usageController = new UsageController();

        // *** UPDATED: Call the method with the notes parameter ***
        $result = $usageController->addUsageRecord($userId, $usageType, $usageAmount, $usageDate, $notes);

        // Set feedback message based on the result
        if ($result['success']) {
            $message = htmlspecialchars($result['message']);
            $messageType = 'success';
        } else {
            $message = htmlspecialchars($result['message']);
            $messageType = 'error';
        }
    } else {
        $message = 'Please fill out all required fields (Amount and Date/Time).';
        $messageType = 'error';
    }
}
// --- End of PHP logic ---

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Usage Reading</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Keep your Tailwind config
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'light-bg': '#F3F4F6',
                        'light-card': 'rgba(255, 255, 255, 0.7)',
                        'light-profile': 'rgba(255, 255, 255)',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border': '#D1D5DB',
                        'light-accent': '#2563EB',
                        'light-accent-hover': '#1D4ED8',
                        'gold-accent': '#ecc931',
                        'dark-card': 'rgba(31, 41, 55, 0.7)',
                        'dark-bg': '#111827',
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#4B5563',
                        'dark-profile': 'rgba(31, 41, 55)',
                        'dark-input-bg': '#374151',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/output.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/add_usage_styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">

    <style>
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 5px; font-weight: bold; text-align: center; max-width: 90%; margin-left: auto; margin-right: auto; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .input-card input, .input-card select, .input-card textarea { background-color: #ffffff; border: 1px solid var(--light-border, #D1D5DB); color: var(--light-text-primary, #1F2937); border-radius: 0.375rem; padding: 0.5rem 0.75rem; width: 100%; }
        .dark .input-card input, .dark .input-card select, .dark .input-card textarea { background-color: var(--dark-input-bg, #374151); border: 1px solid var(--dark-border, #4B5563); color: var(--dark-text-primary, #F9FAFB); }
        .input-card input:focus, .input-card select:focus, .input-card textarea:focus { outline: 2px solid transparent; outline-offset: 2px; --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); --tw-ring-color: var(--light-accent, #2563EB); border-color: var(--light-accent, #2563EB); }
        .dark .input-card input:focus, .dark .input-card select:focus, .dark .input-card textarea:focus { --tw-ring-color: var(--gold-accent, #ecc931); border-color: var(--gold-accent, #ecc931); }
        .input-card label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--light-text-secondary); }
        .dark .input-card label { color: var(--dark-text-secondary); }
    </style>
</head>

<body class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-8 flex-grow">
        <h1 class="text-3xl font-bold mb-8 text-center text-light-text-primary dark:text-white">Add New Usage Readings</h1>

        <?php if (!empty($message)): ?>
            <div id="messageArea" class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div id="input-cards-wrapper" class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-6xl mx-auto">

            <section class="input-card scroll-animate scroll-animate-init bg-light-card/70 dark:bg-dark-card/70 backdrop-blur-sm border border-light-border dark:border-dark-border rounded-lg shadow-lg p-6 transition-all duration-300">
                <h2 class="section-header text-xl font-semibold mb-6 text-light-text-primary dark:text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5.268l4.993-4.992a1 1 0 011.414 1.414l-4.992 4.993H18a1 1 0 011 1v4a1 1 0 01-.553.894l-8 4A1 1 0 019 18v-5.268l-4.993 4.992a1 1 0 01-1.414-1.414l4.992-4.993H2a1 1 0 01-1-1V6a1 1 0 01.553-.894l8-4a1 1 0 011.748-.06z" clip-rule="evenodd" /></svg>
                    Electricity Usage
                </h2>
                <form action="<?php echo BASE_URL_PATH; ?>/add-usage" method="POST" class="space-y-4">
                    <input type="hidden" name="usage_type" value="electricity">
                    <div>
                        <label for="elect_usage_amount" class="block text-sm font-medium mb-1">Reading (kWh)</label>
                        <input type="number" id="elect_usage_amount" name="usage_amount" step="0.01" min="0" required placeholder="e.g., 15.5" class="form-input">
                    </div>
                    <div>
                        <label for="elect_usage_date" class="block text-sm font-medium mb-1">Date and Time</label>
                        <input type="datetime-local" id="elect_usage_date" name="usage_date" required class="form-input">
                    </div>
                    <div>
                        <label for="elect_notes" class="block text-sm font-medium mb-1">Notes (Optional)</label>
                        <textarea id="elect_notes" name="elect_notes" rows="2" placeholder="Any specific observations?" class="form-textarea"></textarea>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="form-button w-full bg-light-accent text-white py-2.5 px-4 rounded-md hover:bg-light-accent-hover dark:bg-gold-accent dark:text-gray-900 dark:hover:opacity-80 transition-all duration-300 font-semibold">Save Electricity Reading</button>
                    </div>
                </form>
            </section>

            <section class="input-card scroll-animate scroll-animate-init scroll-animate-stagger bg-light-card/70 dark:bg-dark-card/70 backdrop-blur-sm border border-light-border dark:border-dark-border rounded-lg shadow-lg p-6 transition-all duration-300" style="transition-delay: 0.1s;">
                 <h2 class="section-header text-xl font-semibold mb-6 text-light-text-primary dark:text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4.768l-1.038-1.037a1 1 0 00-1.414 1.414l2.5 2.5a1 1 0 001.414 0l2.5-2.5a1 1 0 10-1.414-1.414L11 9.768V5z" clip-rule="evenodd" /></svg>
                    Water Usage
                </h2>
                <form action="<?php echo BASE_URL_PATH; ?>/add-usage" method="POST" class="space-y-4">
                    <input type="hidden" name="usage_type" value="water">
                    <div>
                        <label for="water_usage_amount" class="block text-sm font-medium mb-1">Reading (Litres)</label>
                        <input type="number" id="water_usage_amount" name="usage_amount" step="1" min="0" required placeholder="e.g., 250" class="form-input">
                    </div>
                    <div>
                        <label for="water_usage_date" class="block text-sm font-medium mb-1">Date and Time</label>
                        <input type="datetime-local" id="water_usage_date" name="usage_date" required class="form-input">
                    </div>
                     <div>
                        <label for="water_notes" class="block text-sm font-medium mb-1">Notes (Optional)</label>
                        <textarea id="water_notes" name="water_notes" rows="2" placeholder="Any specific observations?" class="form-textarea"></textarea>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="form-button w-full bg-light-accent text-white py-2.5 px-4 rounded-md hover:bg-light-accent-hover dark:bg-gold-accent dark:text-gray-900 dark:hover:opacity-80 transition-all duration-300 font-semibold">Save Water Reading</button>
                    </div>
                </form>
            </section>

        </div>
    </main>

    <?php include(__DIR__ . '/partials/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="datetime-local"]');
            dateInputs.forEach(dateInput => {
                if (!dateInput.value) {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    const localISOTime = now.toISOString().slice(0, 16);
                    dateInput.value = localISOTime;
                }
            });
            const messageArea = document.getElementById('messageArea');
            if (messageArea) {
                setTimeout(() => {
                    messageArea.style.transition = 'opacity 0.5s ease-out';
                    messageArea.style.opacity = '0';
                    setTimeout(() => { messageArea.style.display = 'none'; }, 500);
                }, 5000);
            }
        });
    </script>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

</body>
</html>

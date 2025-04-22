<?php
// htdocs/index.php

// --- Autoload Composer Dependencies ---
// Still needed for other potential dependencies like NewsAPI
require_once __DIR__ . '/../vendor/autoload.php';

// --- Removed Gemini related use statements ---

session_start();

// --- Configuration and Setup ---
define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/htdocs/views/');
define('BASE_URL_PATH', '/tracker'); // Adjust if your base path is different

// --- REMOVED GEMINI_API_KEY constant ---

// Include necessary files
require_once BASE_PATH . '/config/database.php'; // $pdo is available here
require_once BASE_PATH . '/src/controllers/AuthController.php'; // Contains redirectWithProfileMessage
require_once BASE_PATH . '/src/controllers/UsageController.php'; // May use redirect functions

// --- Utility Functions ---
// Ensure the unified flash message function is defined before the switch statement
if (!function_exists('redirectWithFlashMessage')) {
    function redirectWithFlashMessage($routePath, $message, $type = 'info') {
        $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
        $location = $baseUrl . '/' . ltrim($routePath, '/');
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_message_type'] = $type;
        header("Location: " . $location);
        exit;
    }
}
// Note: redirectWithProfileMessage is defined in AuthController.php, which is included above.

// --- Basic Routing ---
// Get the request path relative to the base URL path
$rawPath = $_SERVER['PATH_INFO'] ?? ($_GET['route'] ?? '/');
$basePathUrl = defined('BASE_URL_PATH') ? BASE_URL_PATH : '';
if ($basePathUrl && strpos($rawPath, $basePathUrl) === 0) {
    $requestPath = substr($rawPath, strlen($basePathUrl));
} else {
    $requestPath = $rawPath;
}
$requestPath = trim($requestPath, '/');


// --- Route Definitions ---
switch ($requestPath) {
    // --- Authentication Routes ---
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleSignUp($pdo); } else { include VIEW_PATH . 'signup.php'; }
        break;
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleSignIn($pdo); } else { include VIEW_PATH . 'signin.php'; }
        break;
    case 'logout':
        handleLogout(); break;

    // --- Protected Routes ---
    case '': case 'homepage': case 'dashboard':
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Please log in.', 'error'); exit; }
        include VIEW_PATH . 'homepage.php'; break;
    case 'add-usage':
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Please log in to add usage.', 'error'); exit; }
        include VIEW_PATH . 'add_usage.php'; break;
    case 'save-usage':
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Authentication required.', 'error'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleSaveUsage($pdo); } else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/add-usage'); exit; }
        break;
    case 'statistics':
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Please log in to view statistics.', 'error'); exit; }
        include VIEW_PATH . 'statistics.php'; break;
     case 'profile':
         if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Please log in to view profile.', 'error'); exit; }
         if ($_SERVER['REQUEST_METHOD'] === 'GET') { include VIEW_PATH . 'profile.php'; } else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/profile'); exit; }
         break;
    case 'update-profile-picture':
         if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithProfileMessage('/profile', 'Authentication required.'); exit; }
         if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleUpdateProfilePicture($pdo); } else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/profile'); exit; }
         break;
    case 'update-username':
         if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithProfileMessage('/profile', 'Authentication required.'); exit; }
         if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleUpdateUsername($pdo); } else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/profile'); exit; }
         break;
    case 'update-password':
         if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithProfileMessage('/profile', 'Authentication required.'); exit; }
         if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleUpdatePassword($pdo); } else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/profile'); exit; }
         break;
    case 'news':
        include VIEW_PATH . 'news.php'; break;
    case 'contact':
        include VIEW_PATH . 'contact.php'; break;
    case 'submit-contact':
         if ($_SERVER['REQUEST_METHOD'] === 'POST') { $_SESSION['contact_message'] = 'Submission received (backend not fully implemented).'; header("Location: " . rtrim(BASE_URL_PATH, '/') . '/contact'); exit; }
         else { header("Location: " . rtrim(BASE_URL_PATH, '/') . '/contact'); exit; }
         break;
    case 'chatbot':
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { redirectWithFlashMessage('/login', 'Please log in to use the AI Chatbot.', 'error'); exit; }
        include VIEW_PATH . 'chatbot.php'; break;

    // --- REMOVED /api/chat route handler ---
    // The Node.js server now handles requests to /api/chat on its port (e.g., 3000)

    // --- Default: Not Found ---
    default:
        http_response_code(404);
        if (strpos($requestPath, '.') !== false && file_exists(__DIR__ . '/' . $requestPath)) {
             echo "404 Not Found: Resource " . htmlspecialchars($requestPath) . " cannot be served directly.";
        } else {
            echo "404 Page Not Found: The requested path '/" . htmlspecialchars($requestPath) . "' was not found.";
        }
        break;
}

// --- Utility function definition remains ---
// (redirectWithFlashMessage function is defined earlier)
?>

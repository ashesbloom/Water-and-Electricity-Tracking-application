<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// --- Configuration ---
define('BASE_URL_PATH', '/tracker'); // Adjust if needed
define('BASE_PATH', dirname(__DIR__));

// --- PHPMailer (Use statements needed for type hinting if used, logic is in Controller) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// --- Includes ---
require_once __DIR__ . '/../config/database.php'; // Assuming $pdo is created here
require_once __DIR__ . '/../src/controllers/AuthController.php'; // Assumes AuthController defines the auth handling functions
require_once __DIR__ . '/../src/controllers/UsageController.php';
require_once __DIR__ . '/../src/controllers/ContactController.php'; // Include the new ContactController

// --- Routing ---
$requestUri = $_SERVER['REQUEST_URI'];
$basePathLength = strlen(BASE_URL_PATH);
if (substr($requestUri, 0, $basePathLength) === BASE_URL_PATH) {
    $route = substr($requestUri, $basePathLength);
} else {
    $route = $requestUri;
}
$route = strtok($route, '?'); // Remove query string

if (empty($route) || $route === '/') {
    $route = '/homepage';
}

// --- View/API Directories ---
$viewDir = __DIR__ . '/views/';
$apiDir = __DIR__ . '/api/';

// --- Route requests ---
switch ($route) {
    // --- Page Routes ---
    case '/homepage':
        if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'homepage.php');
        break;
    case '/signin':
        if (isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/homepage'); exit(); }
        include($viewDir . 'signin.php');
        break;
    case '/signup':
         if (isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/homepage'); exit(); }
        include($viewDir . 'signup.php');
        break;
    case '/profile':
        if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'profile.php');
        break;
    case '/statistics':
         if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'Statistics.php');
        break;
    case '/add-usage':
         if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'add_usage.php');
        break;
    case '/contact':
        include($viewDir . 'contact.php');
        break;
    case '/news':
        include($viewDir . 'news.php');
        break;
    case '/chatbot':
         if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'chatbot.php');
        break;

    // --- Detail Page Routes ---
    case '/details/electricity':
        if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'electTodayUse.php');
        break;
    case '/details/water':
        if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        include($viewDir . 'waterTodayUse.php');
        break;

    // --- API Routes ---
    case '/api/getElectTodayUse': include($apiDir . 'getElectTodayUse.php'); break;
    case '/api/getWaterTodayUse': include($apiDir . 'getWaterTodayUse.php'); break;
    case '/api/historicalWater': include($apiDir . 'getHistoricalWater.php'); break;
    case '/api/usageOverview': include($apiDir . 'getUsageOverview.php'); break;
    case '/api/chatbotData':
        if (!class_exists('UsageController')) { http_response_code(500); echo json_encode(['error' => 'Server configuration error: UsageController not found.']); exit; }
        include($apiDir . 'getChatbotData.php');
        break;

    // --- Action Routes ---
    case '/auth/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleSignIn($pdo); } // Assumes function exists in AuthController.php
        else { header('Location: ' . BASE_URL_PATH . '/signin'); exit(); }
        break;
    case '/auth/register':
       if ($_SERVER['REQUEST_METHOD'] === 'POST') { handleSignUp($pdo); } // Assumes function exists in AuthController.php
       else { header('Location: ' . BASE_URL_PATH . '/signup'); exit(); }
       break;
    case '/auth/logout':
         handleLogout(); // Assumes function exists in AuthController.php
         exit();
        break;
    // Assuming profile update functions are also in AuthController.php or similar
    case '/update-profile-picture':
         if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) { handleUpdateProfilePicture($pdo); }
         else { header('Location: ' . BASE_URL_PATH . (isset($_SESSION['user_id']) ? '/profile' : '/signin')); exit(); }
         break;
    case '/update-username':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) { handleUpdateUsername($pdo); }
        else { header('Location: ' . BASE_URL_PATH . (isset($_SESSION['user_id']) ? '/profile' : '/signin')); exit(); }
        break;
    case '/update-password':
         if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) { handleUpdatePassword($pdo); }
         else { header('Location: ' . BASE_URL_PATH . (isset($_SESSION['user_id']) ? '/profile' : '/signin')); exit(); }
         break;
    // Contact form submission route
    case '/submit-contact':
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $contactController = new ContactController(); // Instantiate the new controller
             $contactController->submitForm(); // Call the method in the controller
         }
         else {
            header('Location: ' . BASE_URL_PATH . '/contact');
            exit();
         }
         break;

    // --- Default: 404 Not Found ---
    default:
        http_response_code(404);
        // Simple 404 page
        echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>";
        echo "<h1>404 Page Not Found</h1>";
        echo "<p>The requested route '<code>" . htmlspecialchars($route ?? 'Unknown') . "</code>' was not found.</p>";
        echo "<p><a href='" . BASE_URL_PATH . "/homepage'>Go to Homepage</a></p>";
        echo "</body></html>";
        break;
}
?>

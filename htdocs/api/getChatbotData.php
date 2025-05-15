<?php
// API endpoint for the Node.js chatbot backend to fetch user-specific usage data.

// --- Error Reporting (Enable for Debugging ONLY) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// ---

// --- Headers ---
// Set JSON header *before* any potential output (like errors)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000'); // Allow requests from Node backend
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// --- Handle OPTIONS request (preflight) ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// --- Ensure POST request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

// --- Get and Decode Input ---
$jsonPayload = file_get_contents('php://input');
$requestData = json_decode($jsonPayload, true);

// --- Validate Input ---
if (!$requestData || !isset($requestData['userId']) || !isset($requestData['intent'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters: userId and intent.']);
    exit;
}

// --- Sanitize/Filter Input ---
// Use filter_var for basic sanitization and validation
$userId = filter_var($requestData['userId'], FILTER_VALIDATE_INT);
$intent = filter_var($requestData['intent'] ?? '', FILTER_SANITIZE_STRING); // Ensure intent is a string
$parameters = $requestData['parameters'] ?? []; // Default to empty array

if ($userId === false || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid userId provided.']);
    exit;
}
if (empty($intent)) {
    http_response_code(400);
    echo json_encode(['error' => 'Intent cannot be empty.']);
    exit;
}


// --- Data Fetching Logic ---
// Ensure UsageController is loaded (should be via index.php)
if (!class_exists('UsageController')) {
    http_response_code(500);
    error_log("API Error (/api/chatbotData): UsageController class not found."); // Log the error server-side
    echo json_encode(['error' => 'Server configuration error. Cannot process request.']);
    exit;
}

$usageController = new UsageController();
$responseData = [];
$statusCode = 500; // Default to server error

try {
    // --- Intent Handling ---
    switch ($intent) {
        case 'search_notes':
            $usageType = filter_var($parameters['usageType'] ?? null, FILTER_SANITIZE_STRING);
            $keywords = filter_var($parameters['keywords'] ?? '', FILTER_SANITIZE_STRING);
            $startDate = filter_var($parameters['startDate'] ?? null, FILTER_SANITIZE_STRING);
            $endDate = filter_var($parameters['endDate'] ?? null, FILTER_SANITIZE_STRING);

            if (empty($keywords)) {
                $responseData = ['error' => 'Keywords parameter is required for search_notes intent.'];
                $statusCode = 400;
            } else {
                // DEV NOTE: Ensure searchByNotes exists and returns an array/object
                if (!method_exists($usageController, 'searchByNotes')) {
                     throw new Exception("Method searchByNotes does not exist in UsageController.");
                }
                $responseData = $usageController->searchByNotes($userId, $keywords, $usageType, $startDate, $endDate);
                $statusCode = 200;
            }
            break;

        case 'get_peak_usage':
            $usageType = filter_var($parameters['usageType'] ?? null, FILTER_SANITIZE_STRING);
            $startDate = filter_var($parameters['startDate'] ?? date('Y-m-01')); // Default start of current month
            $endDate = filter_var($parameters['endDate'] ?? date('Y-m-d'));     // Default today

            // DEV NOTE: Ensure getPeakUsageForPeriod exists and returns an array/object or null
             if (!method_exists($usageController, 'getPeakUsageForPeriod')) {
                 throw new Exception("Method getPeakUsageForPeriod does not exist in UsageController.");
             }
            $peakData = $usageController->getPeakUsageForPeriod($userId, $usageType, $startDate, $endDate);

            if (empty($peakData)) {
                // Return a structured message instead of an empty array/null if nothing found
                $responseData = ['message' => 'No usage data found for the specified period to determine peak.'];
            } else {
                $responseData = $peakData; // Assign the actual peak data
            }
            $statusCode = 200;
            break;

         case 'get_total_usage':
             $usageType = filter_var($parameters['usageType'] ?? null, FILTER_SANITIZE_STRING);
             $startDate = filter_var($parameters['startDate'] ?? date('Y-m-d')); // Default today
             $endDate = filter_var($parameters['endDate'] ?? date('Y-m-d'));     // Default today

             // DEV NOTE: Ensure getTotalUsageForPeriod exists and returns an array/object
             if (!method_exists($usageController, 'getTotalUsageForPeriod')) {
                 throw new Exception("Method getTotalUsageForPeriod does not exist in UsageController.");
             }
             $responseData = $usageController->getTotalUsageForPeriod($userId, $usageType, $startDate, $endDate);
             $statusCode = 200;
             break;

        // Add more cases for other intents as needed

        default:
            $responseData = ['error' => 'Unknown intent provided: ' . htmlspecialchars($intent)];
            $statusCode = 400;
            break;
    }
} catch (Exception $e) {
    // Catch any exception during controller method execution
    error_log("Error in getChatbotData.php API (Intent: $intent): " . $e->getMessage());
    $responseData = ['error' => 'An internal server error occurred while fetching data. Please check server logs.'];
    $statusCode = 500;
}

// --- Send the Final JSON Response ---
http_response_code($statusCode);
echo json_encode($responseData); // Ensure JSON is always echoed
exit();

?>

<?php
// API endpoint to fetch today's water usage JSON

// Session should be started by index.php
// UsageController should be included by index.php

header('Content-Type: application/json');
$response = [];
$statusCode = 500;

if (!isset($_SESSION['user_id'])) {
    $statusCode = 401;
    $response = ['error' => 'User not authenticated.'];
} elseif (!class_exists('UsageController')) {
    $statusCode = 500;
    $response = ['error' => 'Server configuration error: UsageController not found.'];
    error_log("API Error (/api/getWaterTodayUse): UsageController class not found.");
} else {
    $userId = $_SESSION['user_id'];
    try {
        $usageController = new UsageController();
        $response = $usageController->getTodayUsage($userId, 'water');
        $statusCode = 200;
    } catch (Exception $e) {
        error_log("API Error (/api/getWaterTodayUse): " . $e->getMessage());
        $response = ['error' => 'An internal server error occurred fetching water data.'];
        // Keep statusCode 500
    }
}

http_response_code($statusCode);
echo json_encode($response);
exit(); // Essential: Stop script execution
?>

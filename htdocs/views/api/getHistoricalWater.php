<?php
// API endpoint to fetch historical water usage for the last 7 days.

// Start session (required by index.php includes)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// UsageController should already be included by index.php

// Default response
$response = ['error' => 'Could not fetch data.'];
$statusCode = 500;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = ['error' => 'User not authenticated.'];
    $statusCode = 401;
} else {
    $userId = $_SESSION['user_id'];
    $usageType = 'water';

    // Calculate date range (today and previous 6 days)
    $endDate = date('Y-m-d'); // Today
    $startDate = date('Y-m-d', strtotime('-6 days')); // 6 days before today

    try {
        // Instantiate the controller
        $usageController = new UsageController();

        // Fetch daily usage data for the period
        $historicalData = $usageController->getDailyUsageForPeriod($userId, $usageType, $startDate, $endDate);

        // --- Fill missing dates with 0 usage ---
        $period = new DatePeriod(
             new DateTime($startDate),
             new DateInterval('P1D'), // Period of 1 day
             (new DateTime($endDate))->modify('+1 day') // Include end date
        );

        $formattedData = [];
        $usageMap = [];
        // Create a map of existing data for quick lookup
        foreach ($historicalData as $data) {
            $usageMap[$data['date']] = $data['usage'];
        }

        // Iterate through the date period
        foreach ($period as $date) {
            $currentDateStr = $date->format('Y-m-d');
            $formattedData[] = [
                'date' => $currentDateStr,
                'usage' => isset($usageMap[$currentDateStr]) ? $usageMap[$currentDateStr] : 0.0
            ];
        }
        // --- End filling missing dates ---


        $response = $formattedData; // Send the potentially zero-filled data
        $statusCode = 200;

    } catch (Exception $e) {
        // Log any unexpected errors during processing
        error_log("Error in getHistoricalWater.php: " . $e->getMessage());
        $response = ['error' => 'An internal server error occurred processing historical data.'];
        $statusCode = 500;
    }
}

// Output the JSON response with the correct status code
http_response_code($statusCode);
echo json_encode($response);
exit(); // Stop script execution
?>

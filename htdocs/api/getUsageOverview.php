<?php
// API endpoint to fetch historical electricity AND water usage for the last 7 days.

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

    // Calculate date range (today and previous 6 days)
    $endDate = date('Y-m-d'); // Today
    $startDate = date('Y-m-d', strtotime('-6 days')); // 6 days before today

    try {
        // Instantiate the controller
        $usageController = new UsageController();

        // Fetch data for both types
        $electricityDataRaw = $usageController->getDailyUsageForPeriod($userId, 'electricity', $startDate, $endDate);
        $waterDataRaw = $usageController->getDailyUsageForPeriod($userId, 'water', $startDate, $endDate);

        // --- Process and fill missing dates ---
        $period = new DatePeriod(
             new DateTime($startDate),
             new DateInterval('P1D'),
             (new DateTime($endDate))->modify('+1 day')
        );

        $electricityMap = [];
        foreach ($electricityDataRaw as $data) { $electricityMap[$data['date']] = $data['usage']; }

        $waterMap = [];
        foreach ($waterDataRaw as $data) { $waterMap[$data['date']] = $data['usage']; }

        $labels = []; // Dates for the chart labels
        $electricityUsage = []; // Electricity data points
        $waterUsage = []; // Water data points

        foreach ($period as $date) {
            $currentDateStr = $date->format('Y-m-d');
            $labels[] = $currentDateStr; // Add date to labels
            $electricityUsage[] = isset($electricityMap[$currentDateStr]) ? $electricityMap[$currentDateStr] : 0.0;
            $waterUsage[] = isset($waterMap[$currentDateStr]) ? $waterMap[$currentDateStr] : 0.0;
        }
        // --- End processing ---

        // Structure the response for the chart
        $response = [
            'labels' => $labels,
            'electricity' => $electricityUsage,
            'water' => $waterUsage
        ];
        $statusCode = 200;

    } catch (Exception $e) {
        // Log any unexpected errors
        error_log("Error in getUsageOverview.php: " . $e->getMessage());
        $response = ['error' => 'An internal server error occurred processing overview data.'];
        $statusCode = 500;
    }
}

// Output the JSON response with the correct status code
http_response_code($statusCode);
echo json_encode($response);
exit(); // Stop script execution
?>

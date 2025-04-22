<?php
// src/controllers/UsageController.php

// Ensure necessary files are included (like database connection, utility functions)
// These might already be handled if called from index.php where they are included.
// require_once __DIR__ . '/../../config/database.php'; // $pdo is passed as argument
require_once __DIR__ . '/AuthController.php'; // For redirectWithMessage function

/**
 * Handles the submission of the add usage form.
 *
 * @param PDO $pdo The database connection object.
 */
function handleSaveUsage($pdo) {
    // 1. Check Authentication (Essential)
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        // Redirect to login if not authenticated
        redirectWithMessage('/login', 'Please log in to save usage data.');
        return; // Stop execution
    }


    if (empty($_POST['usage_type']) || empty($_POST['date']) || empty($_POST['reading'])) {
         // Note: The form names might differ slightly based on your final form implementation
         // Adjust 'date' and 'reading' based on the actual name attributes used in add_usage.php
         $date_field = ($_POST['usage_type'] ?? 'unknown') === 'electricity' ? 'elect_date' : 'water_date';
         $reading_field = ($_POST['usage_type'] ?? 'unknown') === 'electricity' ? 'elect_reading' : 'water_reading';

         if (empty($_POST[$date_field]) || empty($_POST[$reading_field])) {
            redirectWithMessage('/add-usage', 'Usage Type, Date, and Reading are required.');
            return;
         }
    }


    $userId = $_SESSION['user_id'];
    $usageType = trim($_POST['usage_type']); // 'electricity' or 'water'
    $date = trim($_POST[$date_field]);
    $reading = trim($_POST[$reading_field]);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    $notes_field = ($_POST['usage_type'] ?? 'unknown') === 'electricity' ? 'elect_notes' : 'water_notes';
     $notes = isset($_POST[$notes_field]) ? trim($_POST[$notes_field]) : null;

    // Basic Validation (Add more as needed)
    if (!in_array($usageType, ['electricity', 'water'])) {
        redirectWithMessage('/add-usage', 'Invalid usage type.');
        return;
    }
    // Validate date format (Y-m-d)
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
         redirectWithMessage('/add-usage', 'Invalid date format. Use YYYY-MM-DD.');
         return;
    }
    // Validate reading is a non-negative number
    if (!is_numeric($reading) || floatval($reading) < 0) {
         redirectWithMessage('/add-usage', 'Reading must be a non-negative number.');
         return;
    }
    $readingValue = floatval($reading);


    // 4. Database Interaction (Requires a 'usage_readings' table)
    // --- IMPORTANT: You need to create a table in your database ---
    // Example table structure:
    // CREATE TABLE usage_readings (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     user_id INT NOT NULL,
    //     usage_type ENUM('electricity', 'water') NOT NULL,
    //     reading_date DATE NOT NULL,
    //     reading_value DECIMAL(10, 2) NOT NULL, -- Adjust precision as needed
    //     notes TEXT NULL,
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    // );

    try {
        $sql = "INSERT INTO usage_readings (user_id, usage_type, reading_date, reading_value, notes)
                VALUES (:user_id, :usage_type, :reading_date, :reading_value, :notes)";
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':usage_type', $usageType, PDO::PARAM_STR);
        $stmt->bindParam(':reading_date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':reading_value', $readingValue); // PDO handles numeric type
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Success
            redirectWithMessage('/add-usage', 'Usage reading saved successfully!');
        } else {
            // Execution failed
            redirectWithMessage('/add-usage', 'Failed to save reading.');
        }
    } catch (PDOException $e) {
        // Log error in production: error_log("Save Usage Error: " . $e->getMessage());
        redirectWithMessage('/add-usage', 'Database error occurred while saving.');
    }
}

?>

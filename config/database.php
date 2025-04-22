<?php
// config/database.php

$dbHost = 'localhost'; // Keep this as 'localhost' for XAMPP
$dbName = 'project_db'; // <<< Use the database name you just created
$dbUser = 'root'; // <<< Use the default XAMPP username
$dbPass = ''; // <<< Use an empty string for the default XAMPP password

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: prevent emulation of prepared statements for security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // echo "Connected successfully"; // Optional: uncomment to test connection temporarily
} catch(PDOException $e) {
    // In production, log the error instead of displaying it
    // error_log("Database Connection Error: " . $e->getMessage());
    die("ERROR: Could not connect. " . $e->getMessage());
}

// You can now include this file and use the $pdo object elsewhere.
?>
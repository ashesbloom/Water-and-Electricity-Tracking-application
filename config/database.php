<?php
// config/database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'project_db';
    private $username = 'root';
    private $password = '';
    public $conn = null;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch(PDOException $exception) {
                error_log("Database Connection Error (Class): " . $exception->getMessage());
                $this->conn = null;
            }
        }
        return $this->conn;
    }
}

// Global $pdo variable for procedural AuthController
$dbHost_pdo = 'localhost';
$dbName_pdo = 'project_db';
$dbUser_pdo = 'root';
$dbPass_pdo = '';
$pdo = null; // Initialize

try {
    $pdo = new PDO("mysql:host=$dbHost_pdo;dbname=$dbName_pdo;charset=utf8", $dbUser_pdo, $dbPass_pdo);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Database Connection Error (Global PDO): " . $e->getMessage());
    $pdo = null;
}
?>

-- Select the correct database first
USE `project_db`;

-- Drop the table if it exists (optional, useful for clean recreation during testing)
-- DROP TABLE IF EXISTS `users`;

-- Create the users table with the new profile picture path column
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL, -- Store hashed passwords only!
    `profile_picture_path` VARCHAR(255) NULL DEFAULT NULL, -- Path to profile picture file (nullable)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS usage_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    usage_type ENUM('electricity', 'water') NOT NULL,
    usage_amount DECIMAL(10, 2) NOT NULL, -- Amount (e.g., kWh, Liters). Adjust precision if needed.
    usage_date DATETIME NOT NULL,         -- Specific date and time of the usage reading/entry
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE -- Link to users table
);
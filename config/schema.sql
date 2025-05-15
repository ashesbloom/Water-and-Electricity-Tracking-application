USE `project_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
    `email` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
    `password_hash` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL,
    `profile_picture_path` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `usage_records` (
    `record_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `usage_type` ENUM('electricity', 'water') NOT NULL COLLATE utf8mb4_unicode_ci,
    `usage_amount` DECIMAL(10, 2) NOT NULL,
    `usage_date` DATETIME NOT NULL,
    `notes` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_usage`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

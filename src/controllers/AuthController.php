<?php
// src/controllers/AuthController.php

if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker'); // Fallback
}

function handleSignUp($pdo) {
    if ($pdo === null) {
        redirectWithMessage('/signup', 'Database connection error.');
        return;
    }
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        redirectWithMessage('/signup', 'All fields are required.');
        return;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('/signup', 'Invalid email format.');
        return;
    }
    if ($password !== $confirm_password) {
        redirectWithMessage('/signup', 'Passwords do not match.');
        return;
    }
    if (strlen($password) < 8) {
         redirectWithMessage('/signup', 'Password must be at least 8 characters long.');
         return;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetch()) {
            redirectWithMessage('/signup', 'Username or email already taken.');
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        if ($stmt->execute([':username' => $username, ':email' => $email, ':password_hash' => $passwordHash])) {
             redirectWithMessage('/signin', 'Account created successfully! Please sign in.', false);
        } else {
            redirectWithMessage('/signup', 'Failed to create account.');
        }
    } catch (PDOException $e) {
        error_log("Signup Error: " . $e->getMessage());
        redirectWithMessage('/signup', 'Database error occurred. Please try again later.');
    }
}

function handleSignIn($pdo) {
    if ($pdo === null) {
        redirectWithMessage('/signin', 'Database connection error.');
        return;
    }
    if (empty($_POST['identifier']) || empty($_POST['password'])) {
        redirectWithMessage('/signin', 'Username/Email and password are required.');
        return;
    }
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, profile_picture_path FROM users WHERE username = :identifier1 OR email = :identifier2");
        $stmt->execute([':identifier1' => $identifier, ':identifier2' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            $_SESSION['profile_picture'] = $user['profile_picture_path'];

            header("Location: " . BASE_URL_PATH . '/homepage');
            exit;
        } else {
            redirectWithMessage('/signin', 'Invalid credentials.');
        }
    } catch (PDOException $e) {
        error_log("Signin Error: " . $e->getMessage());
        redirectWithMessage('/signin', 'Database error occurred. Please try again later.');
        exit;
    }
}

function handleLogout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: " . BASE_URL_PATH . '/signin?logged_out=1');
    exit;
}

function handleUpdateUsername($pdo) {
    if ($pdo === null) {
        redirectWithProfileMessage('/profile', 'Database connection error.');
        return;
    }
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.');
        return;
    }
    if (empty($_POST['new_username'])) {
        redirectWithProfileMessage('/profile', 'New username cannot be empty.');
        return;
    }
    $newUsername = trim($_POST['new_username']);
    $userId = $_SESSION['user_id'];

    if (strlen($newUsername) < 3) {
         redirectWithProfileMessage('/profile', 'New username must be at least 3 characters long.');
         return;
    }
    if ($newUsername === $_SESSION['username']) {
         redirectWithProfileMessage('/profile', 'New username is the same as the current one.');
         return;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
        $stmtCheck->execute([':username' => $newUsername, ':user_id' => $userId]);
        if ($stmtCheck->fetch()) {
            redirectWithProfileMessage('/profile', 'Username already taken by another user.');
            return;
        }

        $stmtUpdate = $pdo->prepare("UPDATE users SET username = :new_username WHERE id = :user_id");
        if ($stmtUpdate->execute([':new_username' => $newUsername, ':user_id' => $userId])) {
            $_SESSION['username'] = $newUsername;
            redirectWithProfileMessage('/profile', 'Username updated successfully!', false);
        } else {
            redirectWithProfileMessage('/profile', 'Failed to update username.');
        }
    } catch (PDOException $e) {
        error_log("Update Username Error: " . $e->getMessage());
        redirectWithProfileMessage('/profile', 'Database error during username update.');
    }
}

function handleUpdatePassword($pdo) {
    if ($pdo === null) {
        redirectWithProfileMessage('/profile', 'Database connection error.');
        return;
    }
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.');
        return;
    }
    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
        redirectWithProfileMessage('/profile', 'All password fields are required.');
        return;
    }

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $userId = $_SESSION['user_id'];

    if ($newPassword !== $confirmPassword) {
        redirectWithProfileMessage('/profile', 'New passwords do not match.');
        return;
    }
    if (strlen($newPassword) < 8) {
         redirectWithProfileMessage('/profile', 'New password must be at least 8 characters long.');
         return;
    }

    try {
        $stmtFetch = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id");
        $stmtFetch->execute([':user_id' => $userId]);
        $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            redirectWithProfileMessage('/profile', 'User not found.');
            return;
        }
        if (!password_verify($currentPassword, $user['password_hash'])) {
            redirectWithProfileMessage('/profile', 'Incorrect current password.');
            return;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = :new_password_hash WHERE id = :user_id");
        if ($stmtUpdate->execute([':new_password_hash' => $newPasswordHash, ':user_id' => $userId])) {
            redirectWithProfileMessage('/profile', 'Password updated successfully!', false);
        } else {
            redirectWithProfileMessage('/profile', 'Failed to update password.');
        }
    } catch (PDOException $e) {
        error_log("Update Password Error: " . $e->getMessage());
        redirectWithProfileMessage('/profile', 'Database error during password update.');
    }
}

function handleUpdateProfilePicture($pdo) {
    if ($pdo === null) {
        redirectWithProfileMessage('/profile', 'Database connection error.');
        return;
    }
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.');
        return;
    }

    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No file uploaded or upload error occurred.';
        switch ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) {
            case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE: $errorMsg = 'File is too large.'; break;
            case UPLOAD_ERR_PARTIAL: $errorMsg = 'File was only partially uploaded.'; break;
            case UPLOAD_ERR_NO_FILE: $errorMsg = 'No file was selected.'; break;
        }
        redirectWithProfileMessage('/profile', $errorMsg);
        return;
    }

    $file = $_FILES['profile_picture'];
    $userId = $_SESSION['user_id'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    $fileMimeType = mime_content_type($file['tmp_name']);
    if (!in_array($fileMimeType, $allowedTypes)) {
        redirectWithProfileMessage('/profile', 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.');
        return;
    }
    if ($file['size'] > $maxFileSize) {
        redirectWithProfileMessage('/profile', 'File size exceeds limit (5MB).');
        return;
    }

    $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    $uploadDir = $basePath . '/htdocs/asset/uploads/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
             error_log("Failed to create upload directory: " . $uploadDir);
             redirectWithProfileMessage('/profile', 'Server error: Cannot create upload directory.');
             return;
        }
    }
    if (!is_writable($uploadDir)) {
         error_log("Upload directory not writable: " . $uploadDir);
         redirectWithProfileMessage('/profile', 'Server error: Upload directory not writable.');
         return;
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueFilename = $userId . '_' . time() . '.' . strtolower($fileExtension);
    $targetPath = $uploadDir . $uniqueFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $oldRelativePath = $_SESSION['profile_picture'] ?? null;
        if ($oldRelativePath) {
            $oldFullPath = $uploadDir . basename($oldRelativePath);
            if (file_exists($oldFullPath) && is_writable($oldFullPath) && is_file($oldFullPath)) {
                unlink($oldFullPath);
            } else {
                error_log("Could not delete old profile picture (doesn't exist or not writable): " . $oldFullPath);
            }
        }

        $relativePathForDb = $uniqueFilename;
        try {
            $stmtUpdate = $pdo->prepare("UPDATE users SET profile_picture_path = :new_path WHERE id = :user_id");
            if ($stmtUpdate->execute([':new_path' => $relativePathForDb, ':user_id' => $userId])) {
                $_SESSION['profile_picture'] = $relativePathForDb;
                redirectWithProfileMessage('/profile', 'Profile picture updated successfully!', false);
            } else {
                unlink($targetPath);
                redirectWithProfileMessage('/profile', 'Failed to update profile picture record.');
            }
        } catch (PDOException $e) {
            error_log("Update Profile Picture DB Error: " . $e->getMessage());
            unlink($targetPath);
            redirectWithProfileMessage('/profile', 'Database error during picture update.');
        }
    } else {
        redirectWithProfileMessage('/profile', 'Failed to move uploaded file.');
    }
}

// Utility Function for Generic Redirection with Message
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($routePath, $message, $isError = true) {
        $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
        $location = $baseUrl . '/' . ltrim($routePath, '/');
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $isError ? 'error' : 'success';
        header("Location: " . $location);
        exit;
    }
}

// Utility Function for Profile Page Redirection
if (!function_exists('redirectWithProfileMessage')) {
    function redirectWithProfileMessage($routePath, $message, $isError = true) {
        $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
        $location = $baseUrl . '/' . ltrim($routePath, '/');
        $_SESSION['profile_message'] = $message;
        $_SESSION['profile_message_type'] = $isError ? 'error' : 'success';
        header("Location: " . $location);
        exit;
    }
}

?>

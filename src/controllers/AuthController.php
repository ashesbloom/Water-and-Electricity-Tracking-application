<?php
// src/controllers/AuthController.php

// session_start(); // Session is started by index.php

// Ensure database connection is available (usually included by index.php)
// require_once __DIR__ . '/../../config/database.php';

// --- Sign Up Handler ---
function handleSignUp($pdo) {
    // Check if required fields are set and not empty
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        redirectWithMessage('/register', 'All fields are required.');
        return;
    }

    // Trim input values
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('/register', 'Invalid email format.');
        return;
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        redirectWithMessage('/register', 'Passwords do not match.');
        return;
    }

    // Validate password strength (example: minimum 8 characters)
    if (strlen($password) < 8) {
         redirectWithMessage('/register', 'Password must be at least 8 characters long.');
         return;
    }


    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetch()) {
            redirectWithMessage('/register', 'Username or email already taken.');
            return;
        }

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        if ($stmt->execute([':username' => $username, ':email' => $email, ':password_hash' => $passwordHash])) {
             // Use the generic redirect function for consistency after signup
             redirectWithMessage('/login', 'Account created successfully! Please sign in.');
        } else {
            redirectWithMessage('/register', 'Failed to create account.');
        }
    } catch (PDOException $e) {
        // In production, log the error instead of echoing
        // error_log("Signup Error: " . $e->getMessage());
        redirectWithMessage('/register', 'Database error occurred. Please try again later.');
    }
}

// --- Sign In Handler ---
function handleSignIn($pdo) {
     // Check for identifier and password
     if (empty($_POST['identifier']) || empty($_POST['password'])) {
         redirectWithMessage('/login', 'Username/Email and password are required.');
         return;
     }
     $identifier = trim($_POST['identifier']);
     $password = $_POST['password'];

     try {
         // Prepare statement to find user by username OR email
         $stmt = $pdo->prepare("SELECT id, username, password_hash, profile_picture_path FROM users WHERE username = :username_id OR email = :email_id");

         // Execute statement with unique placeholders
         $stmt->execute([
             ':username_id' => $identifier,
             ':email_id' => $identifier
            ]);

         $user = $stmt->fetch(PDO::FETCH_ASSOC);

         // Verify user exists and password is correct
         if ($user && password_verify($password, $user['password_hash'])) {
             // Regenerate session ID for security
             session_regenerate_id(true);
             // Store user info in session
             $_SESSION['user_id'] = $user['id'];
             $_SESSION['username'] = $user['username'];
             $_SESSION['logged_in'] = true;
             $_SESSION['profile_picture'] = $user['profile_picture_path']; // Store profile pic path

             // Redirect to homepage
             $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
             $location = $baseUrl . '/homepage';
             header("Location: " . $location);
             exit;
         } else {
             // Invalid credentials
             redirectWithMessage('/login', 'Invalid credentials.');
         }
     } catch (PDOException $e) {
        // Log error in production
        // error_log("Signin Error: " . $e->getMessage());
        redirectWithMessage('/login', 'Database error occurred. Please try again later.');
     }
}

// --- Logout Handler ---
function handleLogout() {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page with logout message
    $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
    // Add query parameter to indicate successful logout
    $location = $baseUrl . '/login?logged_out=1';
    header("Location: " . $location);
    exit;
}

// --- Update Username Handler ---
function handleUpdateUsername($pdo) {
    // 1. Check Authentication
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.'); // Use profile message redirect
        return;
    }

    // 2. Validate Input
    if (empty($_POST['new_username'])) {
        redirectWithProfileMessage('/profile', 'New username cannot be empty.');
        return;
    }
    $newUsername = trim($_POST['new_username']);
    $userId = $_SESSION['user_id'];

    // Optional: Add more validation (length, characters, etc.)
    if (strlen($newUsername) < 3) {
         redirectWithProfileMessage('/profile', 'New username must be at least 3 characters long.');
         return;
    }
    if ($newUsername === $_SESSION['username']) {
         redirectWithProfileMessage('/profile', 'New username is the same as the current one.');
         return;
    }


    try {
        // 3. Check if new username is already taken by ANOTHER user
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
        $stmtCheck->execute([':username' => $newUsername, ':user_id' => $userId]);
        if ($stmtCheck->fetch()) {
            redirectWithProfileMessage('/profile', 'Username already taken by another user.');
            return;
        }

        // 4. Update Username in Database
        $stmtUpdate = $pdo->prepare("UPDATE users SET username = :new_username WHERE id = :user_id");
        if ($stmtUpdate->execute([':new_username' => $newUsername, ':user_id' => $userId])) {
            // 5. Update Session
            $_SESSION['username'] = $newUsername;
            redirectWithProfileMessage('/profile', 'Username updated successfully!');
        } else {
            redirectWithProfileMessage('/profile', 'Failed to update username.');
        }
    } catch (PDOException $e) {
        // error_log("Update Username Error: " . $e->getMessage());
        redirectWithProfileMessage('/profile', 'Database error during username update.');
    }
}

// --- Update Password Handler ---
function handleUpdatePassword($pdo) {
    // 1. Check Authentication
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.');
        return;
    }

    // 2. Validate Inputs
    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
        redirectWithProfileMessage('/profile', 'All password fields are required.');
        return;
    }

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $userId = $_SESSION['user_id'];

    // Check if new passwords match
    if ($newPassword !== $confirmPassword) {
        redirectWithProfileMessage('/profile', 'New passwords do not match.');
        return;
    }

    // Validate new password strength (example)
    if (strlen($newPassword) < 8) {
         redirectWithProfileMessage('/profile', 'New password must be at least 8 characters long.');
         return;
    }

    try {
        // 3. Fetch Current Password Hash
        $stmtFetch = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id");
        $stmtFetch->execute([':user_id' => $userId]);
        $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Should not happen if user is logged in, but good practice
            redirectWithProfileMessage('/profile', 'User not found.');
            return;
        }

        // 4. Verify Current Password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            redirectWithProfileMessage('/profile', 'Incorrect current password.');
            return;
        }

        // 5. Hash New Password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // 6. Update Password in Database
        $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = :new_password_hash WHERE id = :user_id");
        if ($stmtUpdate->execute([':new_password_hash' => $newPasswordHash, ':user_id' => $userId])) {
            redirectWithProfileMessage('/profile', 'Password updated successfully!');
        } else {
            redirectWithProfileMessage('/profile', 'Failed to update password.');
        }
    } catch (PDOException $e) {
        // error_log("Update Password Error: " . $e->getMessage());
        redirectWithProfileMessage('/profile', 'Database error during password update.');
    }
}

// --- Update Profile Picture Handler ---
function handleUpdateProfilePicture($pdo) {
    // 1. Check Authentication
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || !isset($_SESSION['user_id'])) {
        redirectWithProfileMessage('/profile', 'Authentication required.');
        return;
    }

    // 2. Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No file uploaded or upload error occurred.';
        switch ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) { // Default if error code not set
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'File is too large.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'No file was selected.';
                break;
            // Add other cases as needed (UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION)
        }
        redirectWithProfileMessage('/profile', $errorMsg);
        return;
    }

    $file = $_FILES['profile_picture'];
    $userId = $_SESSION['user_id'];

    // 3. Validate File Type and Size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes)) {
        redirectWithProfileMessage('/profile', 'Invalid file type. Only JPG, PNG, GIF allowed.');
        return;
    }
    if ($file['size'] > $maxFileSize) {
        redirectWithProfileMessage('/profile', 'File size exceeds limit (5MB).');
        return;
    }

    // 4. Define Upload Directory and Generate Unique Filename
    // --- UPDATED PATH ---
    $uploadDir = BASE_PATH . '/htdocs/asset/uploads/'; // Changed path
    // --- END UPDATED PATH ---

    // IMPORTANT: Ensure this directory exists and is writable by the web server!
    if (!is_dir($uploadDir)) {
        // Attempt to create if not exists (requires permissions)
        if (!mkdir($uploadDir, 0775, true)) { // Use appropriate permissions (e.g., 0775 or 0755)
             // error_log("Failed to create upload directory: " . $uploadDir);
             redirectWithProfileMessage('/profile', 'Server error: Cannot create upload directory.');
             return;
        }
    }
    if (!is_writable($uploadDir)) {
         // error_log("Upload directory not writable: " . $uploadDir);
         redirectWithProfileMessage('/profile', 'Server error: Upload directory not writable.');
         return;
    }


    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    // Create a more unique filename (e.g., userid_timestamp.ext)
    $uniqueFilename = $userId . '_' . time() . '.' . strtolower($fileExtension);
    $targetPath = $uploadDir . $uniqueFilename;

    // 5. Move Uploaded File
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // File moved successfully

        // 6. (Optional) Delete Old Profile Picture
        $oldRelativePath = $_SESSION['profile_picture'] ?? null;
        if ($oldRelativePath) {
            // Use the *same* upload directory definition
            $oldFullPath = $uploadDir . basename($oldRelativePath); // Use basename for security
            if (file_exists($oldFullPath) && is_writable($oldFullPath)) {
                unlink($oldFullPath); // Delete the old file
            }
        }

        // 7. Update Database
        $relativePathForDb = $uniqueFilename; // Store only the filename
        try {
            $stmtUpdate = $pdo->prepare("UPDATE users SET profile_picture_path = :new_path WHERE id = :user_id");
            if ($stmtUpdate->execute([':new_path' => $relativePathForDb, ':user_id' => $userId])) {
                // 8. Update Session
                $_SESSION['profile_picture'] = $relativePathForDb;
                redirectWithProfileMessage('/profile', 'Profile picture updated successfully!');
            } else {
                // DB update failed, maybe delete the newly uploaded file?
                unlink($targetPath);
                redirectWithProfileMessage('/profile', 'Failed to update profile picture record.');
            }
        } catch (PDOException $e) {
            // error_log("Update Profile Picture DB Error: " . $e->getMessage());
            unlink($targetPath); // Clean up uploaded file if DB fails
            redirectWithProfileMessage('/profile', 'Database error during picture update.');
        }

    } else {
        // File move failed
        redirectWithProfileMessage('/profile', 'Failed to move uploaded file.');
    }
}


// --- Utility Function for Generic Redirection with Message ---
function redirectWithMessage($routePath, $message) {
    $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
    $location = $baseUrl . '/' . ltrim($routePath, '/');
    $_SESSION['message'] = $message; // Use the generic message key
    header("Location: " . $location);
    exit;
}

// --- Utility Function for Profile Page Redirection ---
function redirectWithProfileMessage($routePath, $message) {
    $baseUrl = defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '';
    $location = $baseUrl . '/' . ltrim($routePath, '/');
    $_SESSION['profile_message'] = $message; // Use the specific profile message key
    header("Location: " . $location);
    exit;
}

?>

<?php
// src/controllers/ContactController.php

// Use statements for PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
// Ensure Composer's autoloader is included (usually done in index.php, but good practice)
// Adjust path if necessary based on your project structure
require_once __DIR__ . '/../../vendor/autoload.php';

class ContactController {

    
    public function submitForm() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        
        $smtpHost = 'smtp.gmail.com';       
        $smtpPort = 587;                     
        $smtpUsername = 'mongodbassociate@gmail.com'; 
        $smtpPassword = 'yourpasswordhere'; 
        $smtpEncryption = PHPMailer::ENCRYPTION_STARTTLS;  //it can be both TLS or SSL depending upon the port we are using
        $emailFromName = 'GridSync Contact Form';     
        $emailToAddress = 'mayankpandeydk123@gmail.com'; 
        $emailToName = 'GridSync Admin';              
        
        $formType = $_POST['form_type'] ?? 'unknown';
        $messageBody = '';
        $subject = '';
        $replyToEmail = null;
        $replyToName = null;

        // Basic Validation
        if (empty($_POST['message'])) {
            $_SESSION['contact_message'] = 'Error: Message field cannot be empty.';
            $_SESSION['contact_message_type'] = 'error';
            $this->redirectBack(); // Use helper method
        }

        // Prepare email content based on form type
        if ($formType === 'contact') {
            $name = filter_var(trim($_POST['name'] ?? 'Anonymous'), FILTER_SANITIZE_STRING);
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $formSubject = filter_var(trim($_POST['subject'] ?? 'No Subject'), FILTER_SANITIZE_STRING);
            $message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);

            if (!$email) {
                 $_SESSION['contact_message'] = 'Error: Invalid email address provided.';
                 $_SESSION['contact_message_type'] = 'error';
                 $this->redirectBack();
            }

            $subject = "GridSync Contact Form: " . $formSubject;
            $messageBody = "You have received a new message from the GridSync contact form:\n\n" .
                           "Name: " . $name . "\n" .
                           "Email: " . $email . "\n" .
                           "Subject: " . $formSubject . "\n\n" .
                           "Message:\n--------------------\n" . $message;
            $replyToEmail = $email;
            $replyToName = $name;

        } elseif ($formType === 'feedback') {
            $feedbackType = filter_var(trim($_POST['feedback_type'] ?? 'Other'), FILTER_SANITIZE_STRING);
            $message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);
            $name = filter_var(trim($_POST['name'] ?? ''), FILTER_SANITIZE_STRING); // Optional
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL); // Optional

            $subject = "GridSync Feedback Received: " . $feedbackType;
            $messageBody = "You have received new feedback via the GridSync form:\n\n" .
                           "Feedback Type: " . $feedbackType . "\n\n" .
                           "Message:\n--------------------\n" . $message . "\n\n";

            if (!empty($name)) { $messageBody .= "Submitter Name: " . $name . "\n"; $replyToName = $name; }
            if ($email) { $messageBody .= "Submitter Email: " . $email . "\n"; $replyToEmail = $email; }
            else { $messageBody .= "Submitter Email: Not Provided\n"; }

        } else {
            $_SESSION['contact_message'] = 'Error: Unknown form type submitted.';
            $_SESSION['contact_message_type'] = 'error';
            $this->redirectBack();
        }

        // Instantiate PHPMailer
        $mail = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;
            $mail->Port = $smtpPort;

            // Recipients
            $mail->setFrom($smtpUsername, $emailFromName);
            $mail->addAddress($emailToAddress, $emailToName);

            // Reply-To
            if ($replyToEmail) { $mail->addReplyTo($replyToEmail, $replyToName ?? ''); }

            // Content
            $mail->isHTML(false); // Send as plain text
            $mail->Subject = $subject;
            $mail->Body = $messageBody;

            $mail->send();
            $_SESSION['contact_message'] = 'Success! Your message has been sent.';
            $_SESSION['contact_message_type'] = 'success';

        } catch (Exception $e) {
            // Log detailed error, but show generic message to user
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            $_SESSION['contact_message'] = "Error: Message could not be sent. Please try again later.";
            $_SESSION['contact_message_type'] = 'error';
        }

        // Redirect back to the contact page
        $this->redirectBack();
    }

    /**
     * Helper function to redirect back to the contact page.
     */
    private function redirectBack() {
        // Define BASE_URL_PATH if not already defined globally
        // This ensures the constant is available even if called outside index.php context (though unlikely here)
        if (!defined('BASE_URL_PATH')) {
             define('BASE_URL_PATH', '/tracker'); // Adjust if needed
        }
        header('Location: ' . BASE_URL_PATH . '/contact');
        exit;
    }
}
?>

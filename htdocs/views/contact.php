<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker');
}

$username = $_SESSION['username'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$isLoggedIn = isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;

$userEmail = ''; // Placeholder, fetch if needed

$contact_message = $_SESSION['contact_message'] ?? null;
$message_type = 'neutral';
$currentPage = 'contact';

if ($contact_message) {
    if (stripos($contact_message, 'success') !== false || stripos($contact_message, 'sent') !== false) {
        $message_type = 'success';
    } elseif (stripos($contact_message, 'error') !== false || stripos($contact_message, 'failed') !== false) {
        $message_type = 'error';
    }
    unset($_SESSION['contact_message']);
}

$formAction = rtrim(BASE_URL_PATH, '/') . '/submit-contact';

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - GridSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'light-bg': '#F3F4F6',
                        'light-card': 'rgba(255, 255, 255, 0.7)',
                        'light-profile': '#FFFFFF',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border': '#D1D5DB',
                        'light-accent': '#2563EB',
                        'light-accent-hover': '#1D4ED8',
                        'gold-accent': '#ecc931',
                        'dark-card': 'rgba(31, 41, 55, 0.7)',
                        'dark-bg': '#111827',
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#4B5563',
                        'dark-profile': 'rgba(31, 41, 55)',
                        'dark-input-bg': '#374151',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/output.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <style>
        .form-wrapper {
            /* Explicitly define transitioned properties */
            transition-property: opacity, transform; 
            transition-duration: 0.3s;
            transition-timing-function: ease-in-out;
            padding-bottom: 1rem;
            opacity: 1;
            transform: translateX(0); 
            pointer-events: auto; 
        }

        .form-wrapper.hidden {
            opacity: 0;
            pointer-events: none; 
            position: absolute; 
            width: 100%; 
            left: 0; 
            /* Removed visibility: hidden; */
            transform: translateX(-20px); 
        }
        
        #formContainer {
             position: relative;
             min-height: 450px; /* Adjust as needed */
             overflow: hidden; 
        }

        .form-toggle-button {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .form-toggle-button.inactive {
            background-color: transparent;
            color: var(--light-text-secondary);
            border-color: var(--light-border);
        }

        .dark .form-toggle-button.inactive {
            color: var(--dark-text-secondary);
            border-color: var(--dark-border);
        }

        .form-toggle-button.inactive:hover {
            background-color: rgba(0, 0, 0, 0.05);
            border-color: var(--light-text-secondary);
        }

        .dark .form-toggle-button.inactive:hover {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: var(--dark-text-secondary);
        }

        .form-toggle-button.active {
            background-color: var(--light-accent);
            color: white;
            border-color: var(--light-accent);
        }

        .dark .form-toggle-button.active {
            background-color: var(--gold-accent);
            color: var(--dark-bg);
            border-color: var(--gold-accent);
        }

        .contact-form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid var(--light-border, #D1D5DB);
            background-color: var(--light-bg, #F3F4F6);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            color: var(--light-text-primary, #1F2937);
            font-size: 0.875rem;
        }

        .dark .contact-form-input {
            background-color: var(--dark-input-bg, #374151);
            border-color: var(--dark-border, #4B5563);
            color: var(--dark-text-primary, #F9FAFB);
        }

        .contact-form-input::placeholder {
            color: var(--light-text-secondary, #6B7280);
            opacity: 0.7;
        }

        .dark .contact-form-input::placeholder {
            color: var(--dark-text-secondary, #9CA3AF);
            opacity: 0.7;
        }

        .contact-form-input:focus {
            outline: none;
            border-color: var(--light-accent, #3B82F6);
            box-shadow: 0 0 0 2px var(--light-accent, #3B82F6 / 30%);
        }

        .dark .contact-form-input:focus {
            border-color: var(--dark-accent, #ecc931);
            box-shadow: 0 0 0 2px var(--dark-accent, #ecc931 / 30%);
        }

        .contact-form-button {
            padding: 0.6rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            background-color: var(--light-accent, #2563EB);
            color: white;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .dark .contact-form-button {
            background-color: var(--gold-accent, #ecc931);
            color: #111827;
        }

        .contact-form-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            background-color: var(--light-accent-hover, #1D4ED8);
        }

        .dark .contact-form-button:hover {
            filter: brightness(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .contact-form-button:active {
            transform: scale(0.98);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body
    class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-8 flex-grow">
        <h1 class="text-3xl font-bold mb-6 text-center text-light-text-primary dark:text-white">Get In Touch</h1>
        <p class="text-center text-md text-light-text-secondary dark:text-dark-text-secondary max-w-2xl mx-auto mb-10">
            Have questions, feedback, or need assistance? We're here to help. Choose an option below.
        </p>
        
        <?php if ($contact_message): ?>
            <div id="contact-message-area" class="mb-6 text-sm max-w-xl mx-auto min-h-[2.5rem]">
                <?php
                    $c_bgColor = 'bg-gray-100 dark:bg-gray-700';
                    $c_borderColor = 'border-gray-400 dark:border-gray-600';
                    $c_textColor = 'text-gray-700 dark:text-gray-300';

                    if ($message_type === 'success') {
                        $c_bgColor = 'bg-green-100 dark:bg-green-900/30';
                        $c_borderColor = 'border-green-400 dark:border-green-600';
                        $c_textColor = 'text-green-700 dark:text-green-300';
                    } elseif ($message_type === 'error') {
                        $c_bgColor = 'bg-red-100 dark:bg-red-900/30';
                        $c_borderColor = 'border-red-400 dark:border-red-600';
                        $c_textColor = 'text-red-700 dark:text-red-300';
                    }
                ?>
                <div class="p-3 border <?php echo $c_borderColor; ?> <?php echo $c_bgColor; ?> <?php echo $c_textColor; ?> rounded-md text-center">
                    <?php echo htmlspecialchars($contact_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">

            <div class="md:col-span-1 scroll-animate scroll-animate-init">
                <div
                    class="content-box-alert bg-light-card dark:bg-dark-card p-6 rounded-lg shadow-md border border-light-border dark:border-dark-border h-full">
                    <h2 class="text-xl font-semibold mb-4 text-light-text-primary dark:text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        Contact Information
                    </h2>
                    <div class="space-y-4 text-sm text-light-text-secondary dark:text-dark-text-secondary">
                        <p>
                            <strong class="font-medium text-light-text-primary dark:text-white">Address:</strong><br>
                            123 GridSync Lane, Ludhiana, Punjab, 141001, India (Placeholder)
                        </p>
                        <p>
                            <strong class="font-medium text-light-text-primary dark:text-white">Email:</strong><br>
                            <a href="mailto:support@gridsync.example.com"
                                class="hover:underline text-light-accent dark:text-gold-accent">support@gridsync.example.com</a>
                        </p>
                        <p>
                            <strong class="font-medium text-light-text-primary dark:text-white">Phone:</strong><br>
                            +91 123 456 7890 (Placeholder)
                        </p>
                        <p class="text-xs italic mt-6">
                            Please use the form for specific inquiries or feedback.
                        </p>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 scroll-animate scroll-animate-init scroll-animate-stagger"
                style="transition-delay: 0.1s;">
                <div
                    class="content-box-alert bg-light-card dark:bg-dark-card p-6 rounded-lg shadow-md border border-light-border dark:border-dark-border">

                    <div
                        class="flex justify-center space-x-4 mb-4 border-b border-light-border dark:border-dark-border pb-4">
                        <button id="showContactFormBtn" type="button" class="form-toggle-button active">
                            Contact Us
                        </button>
                        <button id="showFeedbackFormBtn" type="button" class="form-toggle-button inactive">
                            Give Feedback
                        </button>
                    </div>

                    <div id="formContainer">

                        <div id="contactFormWrapper" class="form-wrapper">
                            <form action="<?php echo htmlspecialchars($formAction); ?>" method="POST"
                                class="space-y-5 pb-4">
                                <input type="hidden" name="form_type" value="contact">
                                <div>
                                    <label for="contact_name"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Your
                                        Name</label>
                                    <input type="text" id="contact_name" name="name"
                                        value="<?php echo htmlspecialchars($username); ?>" <?php echo $isLoggedIn ? '' : 'required'; ?> placeholder="Enter your name" class="contact-form-input">
                                </div>
                                <div>
                                    <label for="contact_email"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Your
                                        Email</label>
                                    <input type="email" id="contact_email" name="email"
                                        value="<?php echo htmlspecialchars($userEmail); ?>" <?php echo $isLoggedIn ? '' : 'required'; ?> placeholder="Enter your email address"
                                        class="contact-form-input">
                                </div>
                                <div>
                                    <label for="contact_subject"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Subject</label>
                                    <input type="text" id="contact_subject" name="subject" required
                                        placeholder="Reason for contacting" class="contact-form-input">
                                </div>
                                <div>
                                    <label for="contact_message"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Message</label>
                                    <textarea id="contact_message" name="message" rows="5" required
                                        placeholder="How can we help you?" class="contact-form-input"></textarea>
                                </div>
                                <div class="text-center pt-2">
                                    <button type="submit" class="contact-form-button">Send Message</button>
                                </div>
                            </form>
                        </div>

                        <div id="feedbackFormWrapper" class="form-wrapper hidden">
                            <form action="<?php echo htmlspecialchars($formAction); ?>" method="POST"
                                class="space-y-5 pb-4">
                                <input type="hidden" name="form_type" value="feedback">
                                <div>
                                    <label for="feedback_type"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Feedback
                                        Type</label>
                                    <select id="feedback_type" name="feedback_type" required class="contact-form-input">
                                        <option value="" disabled selected>Select type...</option>
                                        <option value="Suggestion">Suggestion</option>
                                        <option value="Bug Report">Bug Report</option>
                                        <option value="Compliment">Compliment</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="feedback_message"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Feedback
                                        / Message</label>
                                    <textarea id="feedback_message" name="message" rows="5" required
                                        placeholder="Share your thoughts..." class="contact-form-input"></textarea>
                                </div>
                                <div class="text-xs text-light-text-secondary dark:text-dark-text-secondary">
                                    You can submit feedback anonymously, or fill in your details below if you'd like us
                                    to follow up.
                                </div>
                                <div>
                                    <label for="feedback_name"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Your
                                        Name (Optional)</label>
                                    <input type="text" id="feedback_name" name="name"
                                        value="<?php echo htmlspecialchars($username); ?>"
                                        placeholder="Your name (optional)" class="contact-form-input">
                                </div>
                                <div>
                                    <label for="feedback_email"
                                        class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Your
                                        Email (Optional)</label>
                                    <input type="email" id="feedback_email" name="email"
                                        value="<?php echo htmlspecialchars($userEmail); ?>"
                                        placeholder="Your email (optional)" class="contact-form-input">
                                </div>
                                <div class="text-center pt-2">
                                    <button type="submit" class="contact-form-button">Submit Feedback</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include(__DIR__ . '/partials/footer.php'); ?>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const showContactBtn = document.getElementById('showContactFormBtn');
            const showFeedbackBtn = document.getElementById('showFeedbackFormBtn');
            const contactFormWrapper = document.getElementById('contactFormWrapper');
            const feedbackFormWrapper = document.getElementById('feedbackFormWrapper');
            const messageArea = document.getElementById('contact-message-area');
            const formContainer = document.getElementById('formContainer'); 

            function showForm(formToShow) {
                if (messageArea) messageArea.innerHTML = '';

                 if(formContainer) {
                     const currentHeight = formContainer.offsetHeight;
                     if (!contactFormWrapper.classList.contains('hidden') || !feedbackFormWrapper.classList.contains('hidden')) {
                        formContainer.style.minHeight = `${currentHeight}px`;
                     }
                 }

                if (formToShow === 'contact') {
                    contactFormWrapper.classList.remove('hidden');
                    feedbackFormWrapper.classList.add('hidden');
                    showContactBtn.classList.remove('inactive');
                    showContactBtn.classList.add('active');
                    showFeedbackBtn.classList.remove('active');
                    showFeedbackBtn.classList.add('inactive');
                } else if (formToShow === 'feedback') {
                    feedbackFormWrapper.classList.remove('hidden');
                    contactFormWrapper.classList.add('hidden');
                    showFeedbackBtn.classList.remove('inactive');
                    showFeedbackBtn.classList.add('active');
                    showContactBtn.classList.remove('active');
                    showContactBtn.classList.add('inactive');
                }
                 
                 setTimeout(() => {
                     if(formContainer) formContainer.style.minHeight = ''; 
                 }, 350); 
            }

            if (showContactBtn) {
                showContactBtn.addEventListener('click', () => showForm('contact'));
            }
            if (showFeedbackBtn) {
                showFeedbackBtn.addEventListener('click', () => showForm('feedback'));
            }

            // Initial state
            if (feedbackFormWrapper && !feedbackFormWrapper.classList.contains('hidden')) {
                feedbackFormWrapper.classList.add('hidden');
            }
            if (contactFormWrapper && contactFormWrapper.classList.contains('hidden')) {
                contactFormWrapper.classList.remove('hidden');
            }

            // Clear messages
            const formInputs = document.querySelectorAll('.contact-form-input');
            formInputs.forEach(input => {
                input.addEventListener('input', clearContactMessage);
                input.addEventListener('focus', clearContactMessage);
            });

            function clearContactMessage() {
                if (messageArea) {
                    messageArea.innerHTML = '';
                    const msgDiv = messageArea.querySelector('div');
                    if (msgDiv) msgDiv.remove();
                }
            }

        });
    </script>

</body>

</html>

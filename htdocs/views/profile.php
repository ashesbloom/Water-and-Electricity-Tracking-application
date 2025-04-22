<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    $_SESSION['message'] = 'Please log in to view your profile.';
    $loginUrl = (defined('BASE_URL_PATH') ? rtrim(BASE_URL_PATH, '/') : '') . '/login';
    header("Location: " . $loginUrl);
    exit;
}

// Define base path if not already defined
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker'); 
}

// Get user data from session
$username = $_SESSION['username'] ?? 'User';
$currentPage = 'profile'; 
$userId = $_SESSION['user_id'] ?? null; 

// Profile Picture Logic
$profilePicFilename = $_SESSION['profile_picture'] ?? null;
$profilePicBaseUrl = BASE_URL_PATH . '/asset/uploads/'; 
$profilePicPath = $profilePicFilename ? $profilePicBaseUrl . basename($profilePicFilename) : ''; 
$profilePicFallback = 'https://placehold.co/128x128/a0aec0/ffffff?text=' . substr($username, 0, 1); 
$profilePicCardFallback = 'https://placehold.co/80x80/a0aec0/ffffff?text=' . substr($username, 0, 1); 
$profilePicSrc = $profilePicPath ?: $profilePicFallback;
$profilePicCardSrc = $profilePicPath ?: $profilePicCardFallback; 

// Get profile update messages from session
$profile_message = $_SESSION['profile_message'] ?? null;
$message_type = 'neutral'; 

if ($profile_message) {
    if (stripos($profile_message, 'success') !== false || stripos($profile_message, 'updated') !== false) {
        $message_type = 'success';
    } elseif (stripos($profile_message, 'invalid') !== false || stripos($profile_message, 'required') !== false || stripos($profile_message, 'error') !== false || stripos($profile_message, 'failed') !== false) {
        $message_type = 'error';
    }
    unset($_SESSION['profile_message']); 
}


// Define form actions
$updatePictureAction = rtrim(BASE_URL_PATH, '/') . '/update-profile-picture';
$updateUsernameAction = rtrim(BASE_URL_PATH, '/') . '/update-username';
$updatePasswordAction = rtrim(BASE_URL_PATH, '/') . '/update-password';

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?>'s Profile - GridSync</title>
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
        .accordion-item:not(:last-child) { border-bottom: 1px solid var(--light-border, #D1D5DB); }
        .dark .accordion-item:not(:last-child) { border-bottom-color: var(--dark-border, #4B5563); }
        .accordion-button { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 1rem 1.5rem; text-align: left; font-weight: 600; background-color: transparent; cursor: pointer; transition: background-color 0.2s ease; }
        .accordion-button:hover { background-color: rgba(0,0,0,0.03); }
        .dark .accordion-button:hover { background-color: rgba(255,255,255,0.05); }
        .accordion-button svg { transition: transform 0.3s ease; flex-shrink: 0; }
        .accordion-button[aria-expanded="true"] svg { transform: rotate(180deg); }
        .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1), padding 0.5s ease; padding: 0 1.5rem; }
        .accordion-content.open { max-height: 500px; padding: 1.5rem; }

        .profile-pic-container { position: relative; cursor: pointer; transition: transform 0.3s ease; }
        .profile-pic-container:hover { transform: scale(1.05); }
        .profile-pic-overlay { position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; border-radius: 50%; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
        .profile-pic-container:hover .profile-pic-overlay { opacity: 1; pointer-events: auto; }
        #profileImagePreview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--light-border, #D1D5DB); margin-top: 0.75rem; opacity: 0; transition: opacity 0.5s ease-in-out; }
        .dark #profileImagePreview { border-color: var(--dark-border, #4B5563); }
        #profileImagePreview.visible { opacity: 1; }

        .profile-form-input { width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px solid var(--light-border, #D1D5DB); background-color: var(--light-bg, #F3F4F6); transition: border-color 0.3s ease, box-shadow 0.3s ease; color: var(--light-text-primary, #1F2937); font-size: 0.875rem; }
        .dark .profile-form-input { background-color: var(--dark-input-bg, #374151); border-color: var(--dark-border, #4B5563); color: var(--dark-text-primary, #F9FAFB); }
        .profile-form-input::placeholder { color: var(--light-text-secondary, #6B7280); opacity: 0.7; }
        .dark .profile-form-input::placeholder { color: var(--dark-text-secondary, #9CA3AF); opacity: 0.7; }
        .profile-form-input:focus { outline: none; border-color: var(--light-accent, #3B82F6); box-shadow: 0 0 0 2px var(--light-accent, #3B82F6 / 30%); }
        .dark .profile-form-input:focus { border-color: var(--dark-accent, #ecc931); box-shadow: 0 0 0 2px var(--dark-accent, #ecc931 / 30%); }
        .profile-form-button { padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease; background-color: var(--light-accent, #2563EB); color: white; cursor: pointer; font-size: 0.875rem; }
        .dark .profile-form-button { background-color: var(--gold-accent, #ecc931); color: #111827; }
        .profile-form-button:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); background-color: var(--light-accent-hover, #1D4ED8); }
        .dark .profile-form-button:hover { filter: brightness(1.1); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3); }
        .profile-form-button:active { transform: scale(0.98); box-shadow: inset 0 1px 3px rgba(0,0,0,0.2); }
    </style>
</head>

<body class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-8 flex-grow">
        <h1 class="text-3xl font-bold mb-8 text-center text-light-text-primary dark:text-white">My Profile</h1>

        <section class="mb-10 scroll-animate scroll-animate-init">
            <div class="content-box-glow max-w-2xl mx-auto bg-light-card dark:bg-dark-card p-6 rounded-lg shadow-lg border border-light-border dark:border-dark-border flex items-center space-x-6">
                 <img id="mainProfileImage" src="<?php echo htmlspecialchars($profilePicSrc); ?>" alt="Profile Picture"
                     class="h-24 w-24 md:h-32 md:w-32 rounded-full object-cover border-4 border-light-accent dark:border-gold-accent shadow-md flex-shrink-0"
                     onerror="this.src='<?php echo $profilePicFallback; ?>'; this.onerror=null;">
                <div>
                    <h2 class="text-2xl md:text-3xl font-semibold text-light-text-primary dark:text-white"><?php echo htmlspecialchars($username); ?></h2>
                </div>
            </div>
        </section>

         <div id="profile-message-area" class="mb-6 text-sm max-w-xl mx-auto min-h-[2.5rem]">
             <?php if ($profile_message): ?>
                 <?php
                     $p_bgColor = 'bg-gray-100 dark:bg-gray-700'; 
                     $p_borderColor = 'border-gray-400 dark:border-gray-600';
                     $p_textColor = 'text-gray-700 dark:text-gray-300';

                     if ($message_type === 'success') {
                         $p_bgColor = 'bg-green-100 dark:bg-green-900/30';
                         $p_borderColor = 'border-green-400 dark:border-green-600';
                         $p_textColor = 'text-green-700 dark:text-green-300';
                     } elseif ($message_type === 'error') {
                         $p_bgColor = 'bg-red-100 dark:bg-red-900/30';
                         $p_borderColor = 'border-red-400 dark:border-red-600';
                         $p_textColor = 'text-red-700 dark:text-red-300';
                     }
                 ?>
                 <div class="p-3 border <?php echo $p_borderColor; ?> <?php echo $p_bgColor; ?> <?php echo $p_textColor; ?> rounded-md text-center">
                     <?php echo htmlspecialchars($profile_message); ?>
                 </div>
             <?php endif; ?>
         </div>

        <section class="max-w-3xl mx-auto scroll-animate scroll-animate-init">
            <div id="profileAccordion" class="content-box bg-light-card dark:bg-dark-card rounded-lg shadow-md border border-light-border dark:border-dark-border overflow-hidden">

                <div class="accordion-item">
                    <h2>
                        <button type="button" class="accordion-button" aria-expanded="false" aria-controls="accordion-content-picture">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" /></svg>
                                Update Profile Picture
                            </span>
                            <svg class="h-5 w-5 text-light-text-secondary dark:text-dark-text-secondary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-content-picture" class="accordion-content" role="region">
                        <form action="<?php echo htmlspecialchars($updatePictureAction); ?>" method="POST" enctype="multipart/form-data" class="flex flex-col items-center space-y-4 pt-2 pb-4">
                            <div id="profilePicUpdateContainer" class="profile-pic-container mb-2">
                                 <img id="currentProfilePicInCard" src="<?php echo htmlspecialchars($profilePicCardSrc); ?>" alt="Current Profile Picture"
                                     class="h-20 w-20 rounded-full object-cover border-2 border-light-border dark:border-dark-border shadow-sm"
                                     onerror="this.src='<?php echo $profilePicCardFallback; ?>'; this.onerror=null;">
                                <div class="profile-pic-overlay">
                                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                       <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                     </svg>
                                     <span class="text-xs font-medium">Change</span>
                                </div>
                            </div>
                            <input type="file" id="profile_picture" name="profile_picture" required accept="image/png, image/jpeg, image/gif" class="hidden">
                            <label for="profile_picture" class="text-sm font-medium text-light-accent dark:text-gold-accent hover:underline cursor-pointer">
                                Click image above or here to choose file
                            </label>
                            <img id="profileImagePreview" src="#" alt="New Image Preview" class="hidden"/>
                            <button type="submit" class="profile-form-button w-full max-w-xs mt-2">Upload Picture</button>
                        </form>
                    </div>
                </div>

                <div class="accordion-item">
                     <h2>
                        <button type="button" class="accordion-button" aria-expanded="false" aria-controls="accordion-content-username">
                             <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                Update Username
                            </span>
                             <svg class="h-5 w-5 text-light-text-secondary dark:text-dark-text-secondary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </h2>
                     <div id="accordion-content-username" class="accordion-content" role="region">
                         <form action="<?php echo htmlspecialchars($updateUsernameAction); ?>" method="POST" class="space-y-3 pt-2 pb-4">
                            <div>
                                <label for="current_username" class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Current Username</label>
                                <input type="text" id="current_username" name="current_username" readonly value="<?php echo htmlspecialchars($username); ?>"
                                       class="profile-form-input bg-gray-200 dark:bg-gray-600 cursor-not-allowed">
                            </div>
                             <div>
                                <label for="new_username" class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">New Username</label>
                                <input type="text" id="new_username" name="new_username" required placeholder="Enter new username"
                                       class="profile-form-input">
                            </div>
                            <button type="submit" class="profile-form-button w-full max-w-xs mx-auto block">Save Username</button>
                        </form>
                    </div>
                </div>

                <div class="accordion-item">
                     <h2>
                        <button type="button" class="accordion-button" aria-expanded="false" aria-controls="accordion-content-password">
                             <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-light-accent dark:text-gold-accent" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" /></svg>
                                Change Password
                            </span>
                             <svg class="h-5 w-5 text-light-text-secondary dark:text-dark-text-secondary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </h2>
                     <div id="accordion-content-password" class="accordion-content" role="region">
                         <form action="<?php echo htmlspecialchars($updatePasswordAction); ?>" method="POST" class="space-y-3 pt-2 pb-4">
                             <div>
                                <label for="current_password" class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required placeholder="Enter current password"
                                       autocomplete="current-password" class="profile-form-input">
                            </div>
                             <div>
                                <label for="new_password" class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">New Password</label>
                                <input type="password" id="new_password" name="new_password" required placeholder="Enter new password"
                                       autocomplete="new-password" class="profile-form-input">
                            </div>
                             <div>
                                <label for="confirm_password" class="block text-sm font-medium text-light-text-secondary dark:text-dark-text-secondary mb-1">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password"
                                       autocomplete="new-password" class="profile-form-input">
                            </div>
                            <button type="submit" class="profile-form-button w-full max-w-xs mx-auto block">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <?php include(__DIR__ . '/partials/footer.php'); ?>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Accordion Logic
            const accordionButtons = document.querySelectorAll('.accordion-button');
            accordionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const content = document.getElementById(button.getAttribute('aria-controls'));
                    const isExpanded = button.getAttribute('aria-expanded') === 'true';
                    
                    // Close all other items
                    // accordionButtons.forEach(otherButton => {
                    //     if (otherButton !== button) {
                    //         document.getElementById(otherButton.getAttribute('aria-controls')).classList.remove('open');
                    //         otherButton.setAttribute('aria-expanded', 'false');
                    //     }
                    // });

                    // Toggle the clicked item
                    button.setAttribute('aria-expanded', !isExpanded);
                    content.classList.toggle('open');
                });
            });

            // Profile Picture Update Logic
            const profilePictureInput = document.getElementById('profile_picture');
            const imagePreview = document.getElementById('profileImagePreview');
            const currentPicInCard = document.getElementById('currentProfilePicInCard');
            const mainProfileImage = document.getElementById('mainProfileImage'); // Reference to main image
            const profilePicUpdateContainer = document.getElementById('profilePicUpdateContainer');

            if (profilePicUpdateContainer && profilePictureInput) {
                profilePicUpdateContainer.addEventListener('click', () => {
                    profilePictureInput.click(); // Trigger file input click
                });
            }

            if (profilePictureInput && imagePreview && currentPicInCard) {
                profilePictureInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imageUrl = e.target.result;
                            // Animate preview visibility
                            imagePreview.classList.remove('visible'); 
                            setTimeout(() => { 
                                imagePreview.src = imageUrl;
                                currentPicInCard.src = imageUrl; // Update image inside accordion too
                                imagePreview.classList.add('visible');
                            }, 150); 
                            // Optionally update the main profile image instantly if desired
                            // if (mainProfileImage) { mainProfileImage.src = imageUrl; }
                        }
                        reader.readAsDataURL(file);
                    } else {
                        // Handle non-image file selection or cancellation
                        imagePreview.src = '#'; // Clear preview
                        imagePreview.classList.remove('visible');
                    }
                });
            }

            // Clear Messages Logic
             const formInputs = document.querySelectorAll('.profile-form-input, #profile_picture');
             formInputs.forEach(input => {
                 input.addEventListener('input', clearProfileMessage);
                 input.addEventListener('focus', clearProfileMessage);
             });

             function clearProfileMessage() {
                  const msgArea = document.getElementById('profile-message-area');
                  if(msgArea) {
                      msgArea.innerHTML = ''; // Clear content
                      // Optionally remove the inner div if it exists
                      const msgDiv = msgArea.querySelector('div');
                      if (msgDiv) msgDiv.remove();
                  }
             }

        });
    </script>

</body>
</html>

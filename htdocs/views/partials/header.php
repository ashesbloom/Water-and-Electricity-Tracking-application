<?php
// htdocs/views/partials/header.php

// This partial assumes the following variables are set in the including file:
// - $currentPage: (string) Identifier for the current page (e.g., 'home', 'add_usage', 'news', 'stats', 'contact', 'profile')
// - $username: (string) The user's username (fetched from session)
// - BASE_URL_PATH: (constant) The base path for URLs
// - $_SESSION['profile_picture']: The filename of the profile picture (if set)

// Default current page if not set
$currentPage = $currentPage ?? ''; 

$headerProfilePicFilename = $_SESSION['profile_picture'] ?? null;
$headerProfilePicBaseUrl = BASE_URL_PATH . '/asset/uploads/'; 
$headerProfilePicPath = $headerProfilePicFilename ? $headerProfilePicBaseUrl . basename($headerProfilePicFilename) : ''; // Use basename for security
$headerProfilePicFallback = 'https://placehold.co/40x40/a0aec0/ffffff?text=' . substr($username, 0, 1);
$headerProfilePicSrc = $headerProfilePicPath ?: $headerProfilePicFallback;

// Define Nav Link Classes
$activeClasses = 'font-semibold text-light-accent dark:text-gold-accent transition-colors duration-200';
$inactiveClasses = 'text-light-text-secondary hover:text-light-accent dark:text-gray-300 dark:hover:text-gold-accent transition-colors duration-200';

?>
<header
    class="bg-light-card text-light-text-primary shadow-sm border-b border-light-border dark:bg-black/70 dark:text-white dark:border-gray-700 dark:shadow-lg p-4 sticky top-0 z-50 flex justify-between items-center backdrop-blur-sm">
     <div class="flex items-center space-x-6">
        <a href="<?php echo BASE_URL_PATH; ?>/homepage">
            <img src="<?php echo BASE_URL_PATH; ?>/asset/logo.png" alt="Logo" class="h-12 w-12 mr-4"
                onerror="this.src='https://placehold.co/40x40/a0aec0/ffffff?text=L'; this.onerror=null;">
        </a>
        <nav class="flex space-x-6">
            <a href="<?php echo BASE_URL_PATH; ?>/homepage"
               class="<?php echo ($currentPage === 'homepage') ? $activeClasses : $inactiveClasses; ?>">Home</a>
            <a href="<?php echo BASE_URL_PATH; ?>/add-usage"
                class="<?php echo ($currentPage === 'add_usage') ? $activeClasses : $inactiveClasses; ?>">Add usage</a>
            <a href="<?php echo BASE_URL_PATH; ?>/statistics"
               class="<?php echo ($currentPage === 'stats') ? $activeClasses : $inactiveClasses; ?>">Statistics</a>
            <a href="<?php echo BASE_URL_PATH; ?>/news"
               class="<?php echo ($currentPage === 'news') ? $activeClasses : $inactiveClasses; ?>">News</a>
            <a href="<?php echo BASE_URL_PATH; ?>/contact"
               class="<?php echo ($currentPage === 'contact') ? $activeClasses : $inactiveClasses; ?>">Contact</a>
        </nav>
    </div>
    <div class="relative">
         <img src="<?php echo htmlspecialchars($headerProfilePicSrc); ?>" alt="Profile" id="profileButton"
            class="h-10 w-10 rounded-full border-2 border-light-border dark:border-gray-600 cursor-pointer hover:scale-105 hover:border-light-accent dark:hover:border-gold-accent transition-all duration-200 object-cover"
            onerror="this.src='<?php echo $headerProfilePicFallback; ?>'; this.onerror=null;">
         
         <div id="dropdownMenu"
            class="dropdown-menu absolute right-0 mt-3 w-48 bg-light-profile rounded-lg shadow-xl hidden z-50 border border-light-border dark:bg-dark-profile dark:border-gray-700">
            
            <a href="<?php echo BASE_URL_PATH; ?>/profile"
               class="block w-full text-left px-4 py-2 text-sm <?php echo ($currentPage === 'profile') ? 'font-semibold text-light-accent dark:text-gold-accent' : 'text-light-text-secondary hover:bg-light-accent hover:text-white dark:text-gray-200 dark:hover:bg-gold-accent dark:hover:text-gray-900'; ?> transition-colors duration-150 rounded-t-lg">Profile</a>
            
            <a href="<?php echo BASE_URL_PATH; ?>/contact"
                  class="block w-full text-left px-4 py-2 text-sm <?php echo ($currentPage === 'contact') ? 'font-semibold text-light-accent dark:text-gold-accent' : 'text-light-text-secondary hover:bg-light-accent hover:text-white dark:text-gray-200 dark:hover:bg-gold-accent dark:hover:text-gray-900'; ?> transition-colors duration-150">Help</a>
            <a href="<?php echo BASE_URL_PATH; ?>/chatbot"
                  class="block w-full text-left px-4 py-2 text-sm <?php echo ($currentPage === 'chatbot') ? 'font-semibold text-light-accent dark:text-gold-accent' : 'text-light-text-secondary hover:bg-light-accent hover:text-white dark:text-gray-200 dark:hover:bg-gold-accent dark:hover:text-gray-900'; ?> transition-colors duration-150">Ask Ai</a>
            
            <button id="themeToggleButton"
                class="relative block w-full text-left px-4 py-2 text-sm text-light-text-secondary hover:bg-light-accent hover:text-white dark:text-gray-200 dark:hover:bg-gold-accent dark:hover:text-gray-900 transition-colors duration-150 overflow-visible">
                <span class="theme-dot inline-block w-2 h-2 rounded-full mr-2 align-middle bg-green-500 dark:bg-white transition-all duration-300"></span>
                Switch Theme 
                </button>
            
            <hr class="border-light-border dark:border-gray-600 my-1">
            
            <a href="<?php echo BASE_URL_PATH; ?>/logout"
                class="block w-full text-left px-4 py-2 text-sm text-light-text-secondary hover:bg-light-accent hover:text-white dark:text-gray-200 dark:hover:bg-gold-accent dark:hover:text-gray-900 transition-colors duration-150 rounded-b-lg">Log Out</a>
        </div>
    </div>
</header>

<?php
// Session should be started by index.php

// Get username from session, default to 'User'
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$currentPage = 'stats'; // Set current page for active link highlighting

// Define base path if not already defined
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker');
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_COOKIE['theme'] ?? 'dark') . ' stats-page-html-active'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Globe - Usage Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>

    <script>
        // Tailwind config
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
                        'dark-space': '#000000', // Specific for globe section background
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#4B5563',
                        'dark-profile': 'rgba(31, 41, 55)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/statistics_styles.css">
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/statistics_script.js" defer></script>
</head>

<body
    class="bg-light-bg dark:bg-dark-bg text-light-text-primary dark:text-dark-text-primary font-sans transition-colors duration-300">
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <div class="scroll-container">
        <section id="globe-section" class="scroll-section">
            <div id="canvas-container">
                <div class="absolute inset-0 flex items-center justify-center bg-black text-gray-400">
                    Loading Globe...
                </div>
            </div>
            <div class="info-box">
                <h2 class="text-lg font-semibold mb-2">Location Info</h2>
                <div id="info-display">
                    <p>Hover over or click the globe.</p>
                </div>
            </div>
        </section>

        <section id="map-section-container" class="scroll-section content-box scroll-animate scroll-animate-init p-6 md:p-8 flex flex-col items-center mt-40">
            <div class="text-center mb-4 mt-20">
                <h1 id="map-main-title" class="text-2xl font-bold text-light-text-primary dark:text-dark-text-primary">
                    </h1>
                <h2 id="map-subtitle" class="text-lg font-semibold mt-1 text-light-text-secondary dark:text-dark-text-secondary">
                    </h2>
            </div>

            <div id="map-toggle-buttons" class="flex justify-center space-x-3 mb-6">
                <button data-map-type="electricity" class="map-toggle-button active">
                    Electricity Tariff
                </button>
                <button data-map-type="water" class="map-toggle-button">
                    Water Tariff
                </button>
            </div>

            <div id="india-tariff-map-container" class=" flex-grow">
                <div id="india-tariff-map">
                    <svg width="800" height="700"></svg> </div>
                <div id="tooltip">Tooltip</div> <div id="map-legend" class="mt-4"></div> </div>

            <p class="text-xs text-light-text-secondary dark:text-dark-text-secondary italic mt-4">
                Source: Representative data. Hover over states for details. Water tariffs are estimates and vary significantly within states.
            </p>
        </section>
    </div>

    <div id="hover-indicator"></div>
    <div id="location-popup"></div>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

</body>
</html>

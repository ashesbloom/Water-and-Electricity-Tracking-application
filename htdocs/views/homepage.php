<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GridSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'light-bg': '#F3F4F6',
                        'light-card': 'rgba(255, 255, 255, 0.5)',
                        'light-profile': '#FFFFFF',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border': '#D1D5DB', // gray-300
                        'light-accent': '#2563EB',
                        'light-accent-hover': '#1D4ED8',
                        'gold-accent': '#ecc931', // Used for dark hover border
                        'dark-card': 'rgba(17, 24, 39, 0.8)', // Base dark card - backdrop-filter removed in CSS for performance
                        'dark-bg': '#111827',
                        'dark-text-primary': '#F9FAFB', // gray-50
                        'dark-border': '#4B5563', // gray-600
                        // Progress bar colors
                        'progress-bg-light': '#E5E7EB', // gray-200
                        'progress-bar-light': '#3B82F6', // blue-500
                        'progress-bg-dark': '#374151', // gray-700
                        'progress-bar-dark': '#F59E0B', // amber-500
                        // Water colors
                        'water-fill-light': '#60a5fa', // blue-400
                        'water-fill-dark': '#93c5fd', // blue-300
                        'water-container-light': '#e5e7eb', // gray-200
                        'water-container-dark': '#374151', // gray-700
                        'dark-text-secondary': '#9CA3AF', // Added for consistency
                        'dark-profile': 'rgba(31, 41, 55)', // Added dark profile bg
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">

    <style>
        /* Styles specific to this page can go here if needed, */
        /* but most are in homepage_styling.css */

        /* Alert Slider CSS remains unchanged */
        .alert-slider-container { position: relative; overflow: hidden; }
        .alert-slides { display: flex; transition: transform 0.5s ease-in-out; }
        .alert-slide { min-width: 100%; flex-shrink: 0; box-sizing: border-box; }
        .alert-slider-container::-webkit-scrollbar { display: none; }
        .alert-slider-container { -ms-overflow-style: none; scrollbar-width: none; }
        .alert-item { padding: 0.5rem 0; border-bottom: 1px solid var(--light-border, #D1D5DB); display: flex; align-items: center; gap: 0.75rem; }
        .dark .alert-item { border-bottom-color: var(--dark-border, #4B5563); }
        .alert-item:last-child { border-bottom: none; padding-bottom: 0; }
        .alert-item:first-child { padding-top: 0; }
        .alert-icon-warning { color: #F59E0B; }
        .dark .alert-icon-warning { color: #FCD34D; }
        .alert-icon-info { color: #3B82F6; }
        .dark .alert-icon-info { color: #60A5FA; }
        .alert-icon-danger { color: #EF4444; }
        .dark .alert-icon-danger { color: #F87171; }
        .alert-icon-system { color: #6B7280; }
        .dark .alert-icon-system { color: #9CA3AF; }
        .alert-indicator { width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #D1D5DB; transition: background-color 0.3s ease; cursor: pointer; border: none; padding: 0; }
        .dark .alert-indicator { background-color: #4B5563; }
        .alert-indicator.active { background-color: #6B7280; }
        .dark .alert-indicator.active { background-color: #9CA3AF; }

        /* Download button CSS is now moved to homepage_styling.css */
        /* Ask AI button CSS is now moved to homepage_styling.css */

    </style>
</head>

<body
    class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php
    // PHP Session/Variable setup remains the same
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
    $currentPage = 'homepage';
    if (!defined('BASE_URL_PATH')) {
        define('BASE_URL_PATH', '/tracker');
    }
    ?>

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-8 flex-grow">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-light-text-primary dark:text-dark-text-primary">
            Welcome back, <?php echo $username; ?>!
            </h1>
            <button class="uiverse text-white text-shadow-2xs" id="askAiButton" onclick="window.location.href='<?php echo BASE_URL_PATH; ?>/chatbot'">
              <div class="wrapper">
            <span>Ask Ai</span>
            <div class="circle circle-12"></div>
            <div class="circle circle-11"></div>
            <div class="circle circle-10"></div>
            <div class="circle circle-9"></div>
            <div class="circle circle-8"></div>
            <div class="circle circle-7"></div>
            <div class="circle circle-6"></div>
            <div class="circle circle-5"></div>
            <div class="circle circle-4"></div>
            <div class="circle circle-3"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-1"></div>
              </div>
            </button>
        </div>

        <div class="top-grid grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
                class="relative overflow-hidden scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg border border-light-border dark:border-dark-border dark:bg-dark-card dark:text-dark-text-primary flex flex-col">
                <div class="mist-background"></div>
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-3">Today's Electricity Usage
                </h2>
                <div class="flex items-start gap-4 flex-grow">
                    <div class="flex-1 flex flex-col">
                        <div class="flex items-baseline space-x-2 mb-3">
                            <div id="currentUsage" class="highlight-value text-5xl font-bold">0</div>
                            <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-300">kWh</span>
                        </div>
                        <div class="flex items-center space-x-3 mt-5 w-full max-w-[20rem]"> <label for="dailyGoalInput"
                                class="text-sm font-medium text-light-text-secondary dark:text-gray-300 whitespace-nowrap">Set
                                Goal:</label>
                            <input type="number" id="dailyGoalInput" name="dailyGoal" min="0" step="1"
                                placeholder="Set kWh Goal"
                                class="w-full px-3 py-1.5 border border-light-border rounded-md focus:outline-none focus:ring-2 focus:ring-light-accent dark:focus:ring-gold-accent focus:border-transparent dark:bg-gray-700 dark:border-dark-border dark:text-white text-sm">
                        </div>
                    </div>
                    <div class="flex flex-col items-center space-y-1 ml-auto mr-4 md:mr-0">
                        <div class="chart-container-small">
                            <canvas id="goalProgressChart"></canvas>
                        </div>
                        <div class="text-sm text-light-text-secondary dark:text-gray-300">Progress: <span
                                id="goalProgressText" class="font-semibold">0%</span></div>
                        <p class="text-xs text-light-text-secondary dark:text-gray-400 text-center">Today's Goal</p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL_PATH; ?>/views/electTodayUse.php"
                    class="text-xs text-center mt-3 text-light-accent dark:text-gold-accent hover:underline">View
                    Details</a>
            </div>

            <div
                class="relative overflow-hidden scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg border border-light-border dark:border-dark-border dark:bg-dark-card dark:text-dark-text-primary flex flex-col">
                <div class="mist-background"></div>
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-3">Usage Distribution
                    (Today)</h2>
                <div class="chart-container-large flex-grow">
                    <canvas id="distributionBarChart"></canvas>
                </div>
            </div>

            <div
                class="relative overflow-hidden scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg border border-light-border dark:border-dark-border dark:bg-dark-card dark:text-dark-text-primary flex flex-col">
                <div class="mist-background"></div>
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Water Usage (Today)</h2>

                <div class="flex flex-wrap justify-between items-start mb-4 gap-4">
                    <div class="flex items-baseline space-x-1">
                        <span id="currentWaterUsageValue"
                            class="text-4xl font-bold text-light-accent dark:text-gold-accent">0</span>
                        <span class="text-sm font-semibold text-light-text-secondary dark:text-gray-300">Litres</span>
                    </div>

                    <div class="flex items-center space-x-2 mt-1 flex-1 min-w-[240px]"> <label for="dailyWaterGoalInput"
                            class="text-sm font-medium text-light-text-secondary dark:text-gray-300 whitespace-nowrap">Set
                            Goal:</label>
                        <input type="number" id="dailyWaterGoalInput" name="dailyWaterGoal" min="0" step="10"
                            placeholder="Set Litres Goal"
                            class="w-full px-3 py-1.5 border border-light-border rounded-md focus:outline-none focus:ring-2 focus:ring-light-accent dark:focus:ring-gold-accent focus:border-transparent dark:bg-gray-700 dark:border-dark-border dark:text-white text-sm">
                    </div>
                </div>

                <div
                    class="water-container-horizontal bg-water-container-light dark:bg-water-container-dark border border-gray-300 dark:border-gray-600 mb-2">
                    <div id="waterLevelContainer"
                        style="width: 0%; height: 100%; position: absolute; left: 0; top: 0; bottom: 0; overflow: hidden; transition: width 1.2s ease-in-out;">
                        <div class="wave-layer bg-light-accent dark:bg-gold-accent opacity-50"
                            style="--wave-offset: 0%; --wave-duration: 8s;"></div>
                        <div class="wave-layer bg-light-accent dark:bg-gold-accent opacity-60"
                            style="--wave-offset: 25%; --wave-duration: 10s;"></div>
                        <div class="wave-layer bg-light-accent dark:bg-gold-accent opacity-70"
                            style="--wave-offset: 50%; --wave-duration: 13s;"></div>
                    </div>
                </div>

                <div id="waterGoalProgressBarContainer"
                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
                    <div id="waterGoalProgressBarInner" class="h-full bg-light-accent dark:bg-gold-accent rounded-full"
                        style="width: 0%;"></div>
                </div>
                <a href="<?php echo BASE_URL_PATH; ?>/views/waterTodayUse.php"
                    class="text-xs text-center mt-auto text-light-accent dark:text-gold-accent hover:underline">View
                    Details</a>
            </div>

            <div
                class="relative overflow-hidden scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg border border-light-border dark:border-dark-border dark:bg-dark-card dark:text-dark-text-primary flex flex-col">
                <div class="mist-background"></div>
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-3">Historical Water Usage
                    (Last 7 Days)</h2>
                <div class="chart-container-historical-water flex-grow">
                    <canvas id="historicalWaterChart"></canvas>
                </div>
            </div>

            <div
                class="relative overflow-hidden scroll-animate scroll-animate-init scroll-animate-stagger content-box-alert bg-light-card p-6 rounded-lg border border-light-border dark:border-dark-border dark:bg-dark-card dark:text-dark-text-primary md:col-span-2">
                <div class="alert-slider-container">
                    <div class="alert-slides" id="alertSlides">
                        <div class="alert-slide p-2" id="usage-alerts-slide">
                            <h3 class="text-md font-semibold mb-3 text-light-text-primary dark:text-white">My Usage
                                Alerts</h3>
                            <div class="text-sm space-y-2 text-light-text-secondary dark:text-gray-300">
                                <div class="alert-item">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 flex-shrink-0 alert-icon-warning" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 3.001-1.742 3.001H4.42c-1.53 0-2.493-1.667-1.743-3.001l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-4a1 1 0 011-1h.008a1 1 0 110 2H10a1 1 0 01-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Electricity goal exceeded yesterday (14 kWh / 12 kWh goal).</span>
                                </div>
                                <div class="alert-item">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 flex-shrink-0 alert-icon-info" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Water usage is 15% lower than last week's average.</span>
                                </div>
                                <p class="pt-2 text-xs italic">No other active usage alerts.</p>
                            </div>
                        </div>
                        <div class="alert-slide p-2" id="service-alerts-slide">
                            <h3 class="text-md font-semibold mb-3 text-light-text-primary dark:text-white">Service
                                Alerts (Ludhiana Area)</h3>
                            <div class="text-sm space-y-2 text-light-text-secondary dark:text-gray-300">
                                <div class="alert-item">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 flex-shrink-0 alert-icon-danger" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 00.293.707l2.5 2.5a1 1 0 101.414-1.414L11 10.586V5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Planned water maintenance tomorrow (Apr 21st) 9 AM - 1 PM in Sector 32.</span>
                                </div>
                                <p class="pt-2 text-xs italic">No active power outage alerts.</p>
                            </div>
                        </div>
                        <div class="alert-slide p-2" id="system-alerts-slide">
                            <h3 class="text-md font-semibold mb-3 text-light-text-primary dark:text-white">Tips & System
                                Info</h3>
                            <div class="text-sm space-y-2 text-light-text-secondary dark:text-gray-300">
                                <div class="alert-item">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 flex-shrink-0 alert-icon-system" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path
                                            d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z" />
                                        <path
                                            d="M9.002 10.005a1.5 1.5 0 10-2.997-.01l-.003.01a1.5 1.5 0 002.997.01l.003-.01z" />
                                    </svg>
                                    <span>Tip: Check for dripping taps to save water. A slow drip can waste litres
                                        daily!</span>
                                </div>
                                <div class="alert-item">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 flex-shrink-0 alert-icon-system" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Reminder: Add your usage readings regularly for accurate tracking.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button id="prevAlert"
                    class="absolute top-1/2 left-1 transform -translate-y-1/2 bg-gray-800/30 dark:bg-gray-900/50 text-white p-1 rounded-full hover:bg-gray-800/60 dark:hover:bg-gray-900/70 focus:outline-none transition-colors z-10"><svg
                        xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg></button>
                <button id="nextAlert"
                    class="absolute top-1/2 right-1 transform -translate-y-1/2 bg-gray-800/30 dark:bg-gray-900/50 text-white p-1 rounded-full hover:bg-gray-800/60 dark:hover:bg-gray-900/70 focus:outline-none transition-colors z-10"><svg
                        xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg></button>
                <div id="alertIndicators"
                    class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-1.5 z-10"></div>
            </div>
        </div>

        <div
            class="scroll-animate scroll-animate-init mt-8 bg-light-card p-6 md:p-8 rounded-lg shadow-sm border border-light-border dark:border-gray-700 dark:bg-dark-card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-light-text-primary dark:text-white">Usage Overview</h2>
                <div class="download-button-container">
                    <button type="button" id="downloadReportBtn" class="download-button">
                        <span class="circle">
                            <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="1.5" d="M12 19V5m0 14-4-4m4 4 4-4"></path>
                            </svg>
                        </span>
                        <p class="title">Download</p>
                    </button>
                </div>
            </div>

            <div class="usage-overview-buttons flex flex-wrap gap-4 mb-6">
                <button data-timeframe="today"
                    class="bg-light-accent text-white px-4 py-2 rounded hover:bg-light-accent-hover dark:bg-gold-accent dark:text-gray-900 dark:hover:opacity-80 transition-colors duration-200 font-semibold">Today</button>
                <button data-timeframe="weekly"
                    class="bg-gray-200 text-light-text-secondary px-4 py-2 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">Weekly</button>
                <button data-timeframe="monthly"
                    class="bg-gray-200 text-light-text-secondary px-4 py-2 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">Monthly</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mt-6 justify-center">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg shadow-inner w-full">
                    <h3 id="dailyUsageChartTitle"
                        class="text-md font-semibold text-light-text-secondary dark:text-gray-300 mb-3 text-center">
                        Hourly Usage (Today)</h3>
                    <div class="chart-container"><canvas id="weeklyUsageChart"></canvas></div>
                </div>
            </div>
        </div>
    </main>

    <?php include(__DIR__ . '/partials/footer.php'); // Include the footer ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/chartsAndAnimations.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>


    <script>
        // Wait for the entire HTML document to be fully loaded and parsed
        document.addEventListener('DOMContentLoaded', () => {

            // --- Goal Input Persistence & Update Trigger ---
            const electricityGoalInput = document.getElementById('dailyGoalInput');
            const electricityStorageKey = 'dailyElectricityGoal';
            const waterGoalInput = document.getElementById('dailyWaterGoalInput');
            const waterStorageKey = 'dailyWaterGoal';

            // Function to load goal from localStorage
            function loadGoal(inputElement, storageKey, updateCallback) {
                if (inputElement) {
                    const savedGoal = localStorage.getItem(storageKey);
                    inputElement.value = savedGoal !== null ? savedGoal : '';
                    // Trigger update after loading
                    if (inputElement.id === 'dailyGoalInput' && typeof window.updateGoalProgressChart === 'function') {
                         window.updateGoalProgressChart(); // Use the correct function name
                         inputElement.dispatchEvent(new Event('goalLoaded', { bubbles: true })); // Dispatch loaded event
                    } else if (inputElement.id === 'dailyWaterGoalInput' && typeof updateCallback === 'function') {
                        requestAnimationFrame(updateCallback); // Update water progress bar
                    }
                }
            }

            // Function to save goal to localStorage
            function saveGoal(inputElement, storageKey) {
                if (inputElement) {
                    localStorage.setItem(storageKey, inputElement.value);
                }
            }

            // Add event listeners for goal inputs
            if (electricityGoalInput) {
                electricityGoalInput.addEventListener('input', () => {
                    saveGoal(electricityGoalInput, electricityStorageKey);
                    // Dispatch custom event for chartsAndAnimations.js to listen
                    electricityGoalInput.dispatchEvent(new Event('goalUpdated', { bubbles: true }));
                });
            }

            if (waterGoalInput) {
                waterGoalInput.addEventListener('input', () => {
                    saveGoal(waterGoalInput, waterStorageKey);
                    // Directly call the update function if available
                    if (typeof updateWaterGoalProgress === 'function') {
                        updateWaterGoalProgress();
                    }
                });
            }

            // Load goals on page load
            loadGoal(electricityGoalInput, electricityStorageKey);
            loadGoal(waterGoalInput, waterStorageKey, window.updateWaterGoalProgress);

            // --- Scroll Animations Setup ---
            const animatedElements = document.querySelectorAll('.scroll-animate');
            if ("IntersectionObserver" in window) {
                const observerOptions = { root: null, rootMargin: '0px', threshold: 0.1 };
                const animationObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.remove('scroll-animate-init');
                            entry.target.classList.add('scroll-animate-active');
                            // Optionally add hover effect trigger for mobile
                            if (window.innerWidth < 768) { // Example breakpoint for mobile
                                entry.target.classList.add('mobile-hover-active');
                            }
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);
                animatedElements.forEach(el => animationObserver.observe(el));
            } else {
                console.warn("Intersection Observer not supported, activating animations directly.");
                animatedElements.forEach(el => {
                    el.classList.remove('scroll-animate-init');
                    el.classList.add('scroll-animate-active');
                });
            }

            // --- Alert Slider Functionality ---
            const slidesContainer = document.getElementById('alertSlides');
            const slides = slidesContainer ? slidesContainer.querySelectorAll('.alert-slide') : [];
            const prevButton = document.getElementById('prevAlert');
            const nextButton = document.getElementById('nextAlert');
            const indicatorsContainer = document.getElementById('alertIndicators');
            let indicators = [];
            let currentIndex = 0;
            const totalSlides = slides.length;

            function createIndicators() {
                if (!indicatorsContainer) return;
                indicatorsContainer.innerHTML = ''; // Clear existing indicators
                indicators = []; // Reset array
                for (let i = 0; i < totalSlides; i++) {
                    const button = document.createElement('button');
                    button.classList.add('alert-indicator');
                    button.setAttribute('aria-label', `Go to slide ${i + 1}`);
                    button.addEventListener('click', () => goToSlide(i));
                    indicatorsContainer.appendChild(button);
                    indicators.push(button);
                }
            }

            function updateIndicators(newIndex) {
                indicators.forEach((indicator, index) => {
                    if (index === newIndex) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
            }

            function goToSlide(index) {
                if (!slidesContainer || totalSlides === 0) return;
                currentIndex = (index + totalSlides) % totalSlides; // Wrap around
                const offset = -currentIndex * 100;
                slidesContainer.style.transform = `translateX(${offset}%)`;
                updateIndicators(currentIndex);
            }

            if (prevButton) { prevButton.addEventListener('click', () => { goToSlide(currentIndex - 1); }); } else { console.warn("Previous alert button not found."); }
            if (nextButton) { nextButton.addEventListener('click', () => { goToSlide(currentIndex + 1); }); } else { console.warn("Next alert button not found."); }

            if (totalSlides > 0 && indicatorsContainer) {
                createIndicators();
                goToSlide(0); // Initialize to the first slide
            } else {
                 if(slidesContainer) console.warn("No alert slides found.");
                 if(!indicatorsContainer) console.warn("Alert indicators container not found.");
                if (prevButton) prevButton.style.display = 'none';
                if (nextButton) nextButton.style.display = 'none';
                if (indicatorsContainer) indicatorsContainer.style.display = 'none';
            }

            // --- Download Report Button Functionality ---
            // Function to get data specific to the homepage report
             function getHomepageReportData() {
                 // Replace with actual logic to get relevant data for homepage report
                 // This is just placeholder data
                 return [
                      { Date: '<?php echo date("Y-m-d"); ?>', Type: 'Electricity', Reading_kWh: parseFloat(document.getElementById('currentUsage')?.textContent || '0'), Notes: 'Summary' },
                      { Date: '<?php echo date("Y-m-d"); ?>', Type: 'Water', Reading_Litres: parseFloat(document.getElementById('currentWaterUsageValue')?.textContent || '0'), Notes: 'Summary' },
                      // Add more data rows as needed (e.g., previous day)
                  ];
             }

             // Initialize the download button using the global function from dynamic.js
             if (typeof initializeDownloadButton === 'function') {
                 initializeDownloadButton('downloadReportBtn', getHomepageReportData, 'usage_summary');
             } else {
                 // This warning should no longer appear if dynamic.js is loaded correctly
                 console.warn("initializeDownloadButton function not found. Download button inactive.");
             }

             // --- Chart Initialization Trigger ---
             // Listen for the chart ready event before clicking the button
             document.addEventListener('mainChartReady', () => {
                 const todayButton = document.querySelector('.usage-overview-buttons button[data-timeframe="today"]');
                 if (todayButton && typeof todayButton.click === 'function') {
                     console.log("Main chart ready, clicking 'Today' button.");
                     todayButton.click();
                 } else {
                      console.warn("Could not find or click the 'Today' button after chart ready event.");
                 }
                 const chartTitle = document.getElementById('dailyUsageChartTitle');
                 if(chartTitle) { chartTitle.textContent = 'Hourly Usage (Today)'; } // Set default title
             }, { once: true }); // Listen only once


        }); // End DOMContentLoaded listener
    </script>
</body>

</html>

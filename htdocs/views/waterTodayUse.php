<?php
// htdocs/views/waterTodayUse.php

// Session should be started by index.php
// Get username from session, default to 'User'
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$currentPage = ''; // Assign if needed, e.g., 'water_details'

// Define base path if not already defined
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker');
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo ($_COOKIE['theme'] ?? 'light') . ' no-global-scroll'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Water Usage Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind config
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'light-bg': '#F3F4F6',
                        'light-card': 'rgba(255, 255, 255, 0.5)',
                        'light-profile': 'rgba(255, 255, 255)',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border': '#D1D5DB',
                        'light-accent': '#2563EB',
                        'light-accent-hover': '#1D4ED8',
                        'gold-accent': '#ecc931',
                        'dark-card': 'rgba(17, 24, 39, 0.8)',
                        'dark-bg': '#111827',
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#4B5563',
                        'dark-profile': 'rgba(31, 41, 55)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/output.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <style>
        .chart-container { position: relative; height: 350px; width: 100%; }
        .dropdown-menu { opacity: 0; transform: scale(0.95) translateY(-10px); transition: opacity 0.2s ease-out, transform 0.2s ease-out; pointer-events: none; }
        .dropdown-menu.show { opacity: 1; transform: scale(1) translateY(0); pointer-events: auto; }
        .theme-dot { box-shadow: 0 0 4px 1px rgba(34, 197, 94, 0.6); }
        .dark .theme-dot { box-shadow: 0 0 4px 1px rgba(255, 255, 255, 0.6); }
        .highlight-value { color: #2563EB; } /* light-accent */
        .dark .highlight-value { color: #ecc931; } /* gold-accent */

        /* Scroll animation styles */
        .scroll-animate-init { opacity: 0; transform: translateY(30px); }
        .scroll-animate-active { opacity: 1; transform: translateY(0); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        .scroll-animate-stagger:nth-child(2) { transition-delay: 0.1s; }
        .scroll-animate-stagger:nth-child(3) { transition-delay: 0.2s; }

         /* --- Download Button CSS (Referenced from homepage_styling.css) --- */
         /* No need to redefine here if homepage_styling.css is included */
    </style>

</head>

<body class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php
      // $currentPage = 'water_details'; // Example if needed for header
      include(__DIR__ . '/partials/header.php');
    ?>

    <main class="p-8 flex-grow">
         <div class="flex justify-between items-center mb-6">
             <h1 class="text-2xl font-bold text-light-text-primary dark:text-white">My Water Usage</h1>
              <div class="download-button-container">
                  <button type="button" id="downloadReportBtn" class="download-button">
                     <span class="circle">
                         <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                             <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19V5m0 14-4-4m4 4 4-4"></path>
                         </svg>
                     </span>
                     <p class="title">Download</p>
                 </button>
             </div>
              </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Today's Total Usage</h2>
                 <div class="flex items-baseline space-x-2">
                    <div id="currentWaterUsageValue" class="highlight-value text-5xl font-bold">0</div>
                    <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400">Litres</span>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Compared to yesterday: -10 Litres)</p>
             </div>

             <div class="scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Estimated Cost (This Month)</h2>
                <div class="flex items-baseline space-x-2">
                     <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400 mr-1">₹</span>
                    <div id="estimatedWaterCost" class="highlight-value text-5xl font-bold">0.00</div>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Estimated based on today's usage & ₹150/15kL rate)</p>
             </div>

             <div class="scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Peak Usage Time (Today)</h2>
                 <div id="peakTime" class="highlight-value text-4xl font-bold">--:--</div>
                <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Highest consumption hour)</p>
             </div>
        </div>


        <div class="scroll-animate scroll-animate-init bg-light-card p-6 md:p-8 rounded-lg shadow-sm border border-light-border dark:border-gray-700 dark:bg-dark-card transition-all duration-300">
            <h2 class="text-xl font-bold mb-4 text-light-text-primary dark:text-white">Usage Trends</h2>
            <div class="usage-overview-buttons flex flex-wrap gap-4 mb-6">
                <button data-timeframe="today"
                    class="content-box-alert bg-light-accent text-white px-4 py-2 rounded hover:bg-light-accent-hover dark:bg-gold-accent dark:text-gray-900 dark:hover:bg-opacity-80 transition-colors duration-200 font-semibold">Today</button>
                <button data-timeframe="weekly"
                    class="content-box-alert bg-gray-200 text-light-text-secondary px-4 py-2 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">Weekly</button>
                <button data-timeframe="monthly"
                    class="content-box-alert bg-gray-200 text-light-text-secondary px-4 py-2 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">Monthly</button>
            </div>
            <div class="grid grid-cols-1 gap-6 mt-6 ">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg shadow-inner w-full">
                    <h3 id="waterUsageChartTitle" class="text-md font-semibold text-light-text-secondary dark:text-gray-300 mb-3 text-center">
                        Loading Water Usage Chart...</h3>
                     <div class="chart-container"><canvas id="weeklyUsageChart"></canvas></div>
                 </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
             <div class="scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100">
                 <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Usage Breakdown (Future)</h2>
                 <p class="text-light-text-secondary dark:text-gray-400">This section could show usage per fixture (shower, taps, toilet) if flow meters are integrated.</p>
             </div>
             <div class="scroll-animate scroll-animate-init scroll-animate-stagger content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100">
                 <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Tips for Optimization</h2>
                 <p class="text-light-text-secondary dark:text-gray-400">Based on your usage patterns, consider:</p>
                 <ul class="list-disc list-inside text-sm text-light-text-secondary dark:text-gray-400 mt-2 space-y-1">
                     <li>Taking shorter showers.</li>
                     <li>Checking for toilet leaks regularly.</li>
                     <li>Using water-efficient appliances and fixtures.</li>
                     <li>Watering lawn/garden during cooler parts of the day.</li>
                 </ul>
             </div>
         </div>

    </main>

    <?php include(__DIR__ . '/partials/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/chartsAndAnimations.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Animations
            const waterUsageElement = document.getElementById('currentWaterUsageValue');
            const simulatedInitialWaterUsage = 185; // Example value
            if (waterUsageElement) {
                 if (typeof initializeUsageAnimation === 'function') {
                     initializeUsageAnimation(simulatedInitialWaterUsage, 'water');
                 } else {
                     console.warn("initializeUsageAnimation function not found.");
                     waterUsageElement.textContent = simulatedInitialWaterUsage;
                 }
            } else {
                console.warn("Element with ID 'currentWaterUsageValue' not found for animation.");
            }

            // Placeholder for Peak Time
            const peakTimeElement = document.getElementById('peakTime');
            if (peakTimeElement) {
                peakTimeElement.textContent = "18:00"; // Example placeholder
            } else {
                console.warn("Element with ID 'peakTime' not found.");
            }

            // --- Updated Monthly Cost Calculation ---
            const estimatedWaterCostElement = document.getElementById('estimatedWaterCost');
            const waterRatePerLitre = 150 / 15000; // Rate: Rs 150 per 15000 Litres

            // Function to calculate estimated monthly cost
            function calculateMonthlyWaterCost() {
                if (waterUsageElement && estimatedWaterCostElement) {
                    const currentDailyUsage = parseFloat(waterUsageElement.textContent);
                    if (!isNaN(currentDailyUsage)) {
                        // Get current date and days in month
                        const now = new Date();
                        const year = now.getFullYear();
                        const month = now.getMonth(); // 0-indexed (0 for Jan, 11 for Dec)
                        const daysInMonth = new Date(year, month + 1, 0).getDate();

                        // Estimate monthly usage based on today's usage
                        const estimatedMonthlyUsage = currentDailyUsage * daysInMonth;

                        // Calculate monthly cost
                        const monthlyCost = (estimatedMonthlyUsage * waterRatePerLitre).toFixed(2);
                        estimatedWaterCostElement.textContent = monthlyCost;
                    }
                }
            }

            if (waterUsageElement && estimatedWaterCostElement) {
                 // Use MutationObserver to update cost when daily usage value changes (e.g., after animation)
                 const observer = new MutationObserver(mutations => {
                     mutations.forEach(mutation => {
                         if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            calculateMonthlyWaterCost(); // Recalculate on change
                         }
                     });
                 });
                 observer.observe(waterUsageElement, { characterData: true, childList: true, subtree: true });

                 // Initial calculation after a short delay to allow animation to potentially start
                 setTimeout(calculateMonthlyWaterCost, 100);
            }
            // --- End Updated Monthly Cost Calculation ---


            // Ensure chartsAndAnimations.js clicks the 'Today' button if needed
             const todayButton = document.querySelector('.usage-overview-buttons button[data-timeframe="today"]');
             if (todayButton && typeof todayButton.click === 'function') {
                 // Maybe add a small delay to ensure chart is ready
                 // setTimeout(() => { todayButton.click(); }, 100);
             }
             const chartTitle = document.getElementById('waterUsageChartTitle');
             if(chartTitle) {
                 chartTitle.textContent = 'Hourly Water Usage (Today)'; // Default title
             }


            // Scroll Animations
            const animatedElements = document.querySelectorAll('.scroll-animate');
            if ("IntersectionObserver" in window) {
                const observerOptions = { root: null, rootMargin: '0px', threshold: 0.1 };
                const animationObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('scroll-animate-active');
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);
                animatedElements.forEach(el => {
                     if (!el.classList.contains('scroll-animate-init')) {
                         el.classList.add('scroll-animate-init');
                     }
                    animationObserver.observe(el);
                });
            } else {
                console.warn("Intersection Observer not supported, activating animations directly.");
                animatedElements.forEach(el => {
                    el.classList.remove('scroll-animate-init');
                    el.classList.add('scroll-animate-active');
                });
            }


            // --- Download Report Button Functionality ---
            // Function to get data specific to the water details report
            function getWaterReportData() {
                // --- Placeholder Data for Water Detail Report ---
                return [
                    { Time: '00:00', Usage_Litres: 10 }, { Time: '01:00', Usage_Litres: 8 },
                    { Time: '02:00', Usage_Litres: 8 }, { Time: '03:00', Usage_Litres: 9 },
                    { Time: '04:00', Usage_Litres: 12 }, { Time: '05:00', Usage_Litres: 15 },
                    { Time: '06:00', Usage_Litres: 25 }, { Time: '07:00', Usage_Litres: 30 },
                    { Time: '08:00', Usage_Litres: 35 }, { Time: '09:00', Usage_Litres: 28 },
                    { Time: '10:00', Usage_Litres: 22 }, { Time: '11:00', Usage_Litres: 20 },
                    { Time: '12:00', Usage_Litres: 18 }, { Time: '13:00', Usage_Litres: 24 },
                    { Time: '14:00', Usage_Litres: 26 }, { Time: '15:00', Usage_Litres: 30 },
                    { Time: '16:00', Usage_Litres: 33 }, { Time: '17:00', Usage_Litres: 38 }, // Example peak
                    { Time: '18:00', Usage_Litres: 35 }, { Time: '19:00', Usage_Litres: 28 },
                    { Time: '20:00', Usage_Litres: 20 }, { Time: '21:00', Usage_Litres: 15 },
                    { Time: '22:00', Usage_Litres: 12 }, { Time: '23:00', Usage_Litres: 10 }
                ];
                // --- End Placeholder Data ---
            }

            // Initialize the download button using the global function from dynamic.js
            if (typeof initializeDownloadButton === 'function') {
                initializeDownloadButton('downloadReportBtn', getWaterReportData, 'water_usage_details');
            } else {
                console.warn("initializeDownloadButton function not found. Download button inactive.");
            }


        }); // End DOMContentLoaded
    </script>

</body>
</html>

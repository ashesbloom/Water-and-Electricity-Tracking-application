<?php
// View file for Water Details page

// Session should be started by index.php
// UsageController should be included by index.php

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (defined('BASE_URL_PATH') ? BASE_URL_PATH : '') . '/signin');
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$currentPage = 'water_details'; // Set identifier for this page if needed

// --- Fetch Data for this Page ---
$todayUsageData = [];
$totalUsage = 0.0;
$peakUsageTime = '--:--';
$peakUsageValue = 0;
$estimatedCost = 0.00;
$waterRatePerLitre = 150 / 15000; // Example Rate: Rs 150 per 15000 Litres

try {
    if (!class_exists('UsageController')) {
        throw new Exception("UsageController class not loaded. Check index.php includes.");
    }
    $usageController = new UsageController();

    // Fetch hourly data for today
    $todayUsageData = $usageController->getTodayUsage($userId, 'water');

    // Calculate total and find peak
    if (!empty($todayUsageData)) {
        foreach ($todayUsageData as $data) {
            $totalUsage += $data['usage'];
            if ($data['usage'] > $peakUsageValue) {
                $peakUsageValue = $data['usage'];
                $peakUsageTime = str_pad($data['hour'], 2, '0', STR_PAD_LEFT) . ':00';
            }
        }
    }

    // Calculate estimated monthly cost based on today's total usage
    $now = new DateTime("now", new DateTimeZone('Asia/Kolkata')); // Ensure timezone
    $daysInMonth = (int)$now->format('t');
    $estimatedMonthlyUsage = $totalUsage * $daysInMonth;
    $estimatedCost = number_format($estimatedMonthlyUsage * $waterRatePerLitre, 2);
    $totalUsage = number_format($totalUsage, 0); // Format total for display (no decimals for litres)


} catch (Exception $e) {
    error_log("Error fetching water details in view: " . $e->getMessage());
    $errorMessage = "Could not load usage data at this time.";
    $totalUsage = 'N/A';
    $estimatedCost = 'N/A';
    $peakUsageTime = 'N/A';
    $peakUsageValue = 0;
    $todayUsageData = [];
}

// Pass fetched data to JavaScript
$chartDataJson = json_encode($todayUsageData);

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Water Usage Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Keep Tailwind config
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {
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
            } }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
     <style>
        /* Keep base styles */
        .chart-container { position: relative; height: 350px; width: 100%; }
        .highlight-value { color: var(--light-accent, #2563EB); }
        .dark .highlight-value { color: var(--gold-accent, #ecc931); }
        /* Add other necessary styles */
        .scroll-animate-init { opacity: 0; transform: translateY(30px); }
        .scroll-animate-active { opacity: 1; transform: translateY(0); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
    </style>
</head>

<body class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-8 flex-grow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-light-text-primary dark:text-white">My Water Usage Details</h1>
            <div class="download-button-container">
                 <button type="button" id="downloadReportBtn" class="download-button">
                    <span class="circle">
                        <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19V5m0 14-4-4m4 4 4-4"></path></svg>
                    </span>
                    <p class="title">Download</p>
                </button>
            </div>
        </div>

         <?php if (isset($errorMessage)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Today's Total Usage</h2>
                 <div class="flex items-baseline space-x-2">
                    <div id="currentWaterUsageValue" class="highlight-value text-5xl font-bold"><?php echo htmlspecialchars($totalUsage); ?></div>
                    <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400">Litres</span>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Calculated from readings)</p>
             </div>

             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Estimated Cost (This Month)</h2>
                <div class="flex items-baseline space-x-2">
                     <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400 mr-1">₹</span>
                    <div id="estimatedWaterCost" class="highlight-value text-5xl font-bold"><?php echo htmlspecialchars($estimatedCost); ?></div>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Est. based on today & ₹<?php echo number_format(150/15, 2); ?>/kL rate)</p>
             </div>

             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Peak Usage Time (Today)</h2>
                 <div id="peakTime" class="highlight-value text-4xl font-bold"><?php echo htmlspecialchars($peakUsageTime); ?></div>
                 <?php if ($peakUsageValue > 0): ?>
                    <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(~<?php echo number_format($peakUsageValue, 0); ?> Litres)</p>
                 <?php else: ?>
                    <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(No usage recorded)</p>
                 <?php endif; ?>
             </div>
        </div>

        <div class="scroll-animate scroll-animate-init bg-light-card p-6 md:p-8 rounded-lg shadow-sm border border-light-border dark:border-gray-700 dark:bg-dark-card transition-all duration-300">
            <h2 class="text-xl font-bold mb-4 text-light-text-primary dark:text-white">Hourly Usage Trend (Today)</h2>
             <div class="chart-container">
                <?php if (empty($todayUsageData) && !isset($errorMessage)): ?>
                    <p class="text-center text-light-text-secondary dark:text-gray-400">No usage data recorded for today yet.</p>
                <?php elseif (isset($errorMessage)): ?>
                     <p class="text-center text-red-600 dark:text-red-400">Could not load chart data.</p>
                <?php else: ?>
                    <canvas id="hourlyUsageChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100">
                 <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Usage Breakdown (Future)</h2>
                 <p class="text-light-text-secondary dark:text-gray-400">This section could show usage per fixture (shower, taps, toilet) if flow meters are integrated.</p>
             </div>
             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100">
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
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

    <script>
        // Embed PHP data into JavaScript only if it exists
        const todayUsageData = <?php echo $chartDataJson; ?>;
        const currentTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

        document.addEventListener('DOMContentLoaded', () => {
            const hourlyUsageCtx = document.getElementById('hourlyUsageChart')?.getContext('2d');
            let hourlyChartInstance = null;

             // Only proceed with chart logic if context and data exist
            if (hourlyUsageCtx && Array.isArray(todayUsageData) && todayUsageData.length > 0) {

                // --- Helper: Get Theme Colors (Simplified) ---
                 function getThemeColors(theme) {
                    const isDark = theme === 'dark';
                    // Use variant colors (blue/emerald) for water page
                    return {
                        gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                        tickColor: isDark ? '#93c5fd' : '#059669', // Adjusted tick color
                        legendColor: isDark ? '#cbd5e1' : '#1F2937',
                        tooltipBg: isDark ? 'rgba(0, 0, 0, 0.7)' : 'rgba(255, 255, 255, 0.8)',
                        tooltipTitle: isDark ? '#ffffff' : '#000000',
                        tooltipBody: isDark ? '#dddddd' : '#333333',
                        accentBorderColor: isDark ? '#60a5fa' : '#10b981', // Light Blue / Emerald
                        accentBgColorLine: isDark ? 'rgba(96, 165, 250, 0.2)' : 'rgba(16, 185, 129, 0.2)'
                    };
                }

                // --- Helper: Get Chart Options (Simplified) ---
                function getChartOptions(theme) {
                     const colors = getThemeColors(theme);
                     return {
                         responsive: true, maintainAspectRatio: false, animation: { duration: 800 },
                         plugins: {
                             legend: { display: false },
                             tooltip: {
                                 enabled: true, backgroundColor: colors.tooltipBg, titleColor: colors.tooltipTitle,
                                 bodyColor: colors.tooltipBody, padding: 8, boxPadding: 4, intersect: false, mode: 'index',
                                 callbacks: { // Add units to tooltip
                                     label: function(context) {
                                         let label = context.dataset.label || '';
                                         if (label) { label += ': '; }
                                         if (context.parsed.y !== null) { label += context.parsed.y + ' Litres'; }
                                         return label;
                                     }
                                 }
                             }
                         },
                         scales: {
                             y: { title: { display: true, text: 'Usage (Litres)', color: colors.tickColor }, beginAtZero: true, ticks: { color: colors.tickColor, padding: 5 }, grid: { color: colors.gridColor, drawBorder: false } },
                             x: { title: { display: true, text: 'Hour of Day', color: colors.tickColor }, ticks: { color: colors.tickColor, padding: 5 }, grid: { color: colors.gridColor } }
                         }
                     };
                }

                // --- Function to Create/Update Chart ---
                function renderHourlyChart(theme) {
                    const colors = getThemeColors(theme);
                    const labels = Array.from({ length: 24 }, (_, i) => `${i}:00`);
                    const usageMap = todayUsageData.reduce((map, item) => { map[item.hour] = item.usage; return map; }, {});
                    const dataPoints = labels.map((_, i) => usageMap[i] || 0);

                    const chartConfig = {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Water Usage', // Simpler label for tooltip
                                data: dataPoints,
                                fill: true, tension: 0.4,
                                backgroundColor: colors.accentBgColorLine,
                                borderColor: colors.accentBorderColor,
                                borderWidth: 2, pointRadius: 2, pointHoverRadius: 5,
                                pointBackgroundColor: colors.accentBorderColor
                            }]
                        },
                        options: getChartOptions(theme)
                    };

                    if (hourlyChartInstance) {
                        hourlyChartInstance.data.datasets[0].backgroundColor = colors.accentBgColorLine;
                        hourlyChartInstance.data.datasets[0].borderColor = colors.accentBorderColor;
                        hourlyChartInstance.data.datasets[0].pointBackgroundColor = colors.accentBorderColor;
                        hourlyChartInstance.options = getChartOptions(theme);
                        hourlyChartInstance.update('none');
                    } else {
                        hourlyChartInstance = new Chart(hourlyUsageCtx, chartConfig);
                    }
                }

                // Initial chart render
                renderHourlyChart(currentTheme);

                // Listen for theme changes
                window.addEventListener('themeUpdated', (event) => {
                    if (event.detail && event.detail.theme) {
                        renderHourlyChart(event.detail.theme);
                    }
                });

            } else if (!hourlyUsageCtx) {
                 console.warn("Hourly usage chart canvas context not found.");
            } // No 'else' needed if data is empty

            // --- Download Button Logic ---
            function getWaterReportData() {
                 if (!Array.isArray(todayUsageData) || todayUsageData.length === 0) {
                    alert("No usage data available to download.");
                    return null;
                }
                const reportData = [];
                const labels = Array.from({ length: 24 }, (_, i) => `${i}:00`);
                const usageMap = todayUsageData.reduce((map, item) => { map[item.hour] = item.usage; return map; }, {});
                labels.forEach((label, i) => {
                    reportData.push({ Time: label, Usage_Litres: usageMap[i] || 0 });
                });
                return reportData;
            }

            if (typeof initializeDownloadButton === 'function') {
                initializeDownloadButton('downloadReportBtn', getWaterReportData, 'water_hourly_details');
            } else {
                console.warn("initializeDownloadButton function not found. Download button inactive.");
                 const downloadBtn = document.getElementById('downloadReportBtn');
                if(downloadBtn) {
                    downloadBtn.disabled = true;
                    downloadBtn.style.opacity = '0.5';
                    downloadBtn.style.cursor = 'not-allowed';
                }
            }

        }); // End DOMContentLoaded
    </script>

</body>
</html>

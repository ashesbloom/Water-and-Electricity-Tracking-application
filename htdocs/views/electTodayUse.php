<?php
// View file for Electricity Details page

// Session should be started by index.php
// UsageController should be included by index.php

// Ensure user is logged in (checked by index.php, but good practice)
if (!isset($_SESSION['user_id'])) {
    // Redirect if accessed directly without session via index.php
    header('Location: ' . (defined('BASE_URL_PATH') ? BASE_URL_PATH : '') . '/signin');
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User'; // Get username for header
$currentPage = 'elect_details'; // Set identifier for this page if needed for header styling

// --- Fetch Data for this Page ---
$todayUsageData = []; // Initialize empty array
$totalUsage = 0.0;
$peakUsageTime = '--:--';
$peakUsageValue = 0;
$estimatedCost = 0.00;
$rate = 7; // Rs per kWh

try {
    // Check if UsageController class exists (it should if included via index.php)
    if (!class_exists('UsageController')) {
        throw new Exception("UsageController class not loaded. Check index.php includes.");
    }
    $usageController = new UsageController();

    // Fetch hourly data for today
    $todayUsageData = $usageController->getTodayUsage($userId, 'electricity');

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
    // Calculate estimated cost based on total
    $estimatedCost = number_format($totalUsage * $rate, 2);
    $totalUsage = number_format($totalUsage, 2); // Format total for display

} catch (Exception $e) {
    error_log("Error fetching electricity details in view: " . $e->getMessage());
    // Set user-friendly error message or default values
    $errorMessage = "Could not load usage data at this time.";
    $totalUsage = 'N/A';
    $estimatedCost = 'N/A';
    $peakUsageTime = 'N/A';
    $peakUsageValue = 0;
    $todayUsageData = []; // Ensure it's an empty array for JSON encoding
}

// Pass fetched data to JavaScript for the chart
$chartDataJson = json_encode($todayUsageData);

?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Electricity Usage Details</title>
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
        .highlight-value { color: var(--light-accent, #2563EB); } /* Use CSS variables with fallbacks */
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
            <h1 class="text-2xl font-bold text-light-text-primary dark:text-white">My Electricity Usage Details</h1>
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
                    <div id="currentUsage" class="highlight-value text-5xl font-bold"><?php echo htmlspecialchars($totalUsage); ?></div>
                    <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400">kWh</span>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Calculated from readings)</p>
             </div>

             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Estimated Cost (Today)</h2>
                <div class="flex items-baseline space-x-2">
                     <span class="font-semibold text-sm text-light-text-secondary dark:text-gray-400 mr-1">₹</span>
                     <div id="estimatedCost" class="highlight-value text-5xl font-bold"><?php echo htmlspecialchars($estimatedCost); ?></div>
                </div>
                 <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(Based on ₹<?php echo $rate; ?>/kWh rate)</p>
             </div>

             <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100 flex flex-col items-center justify-center">
                <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Peak Usage Time (Today)</h2>
                 <div id="peakTime" class="highlight-value text-4xl font-bold"><?php echo htmlspecialchars($peakUsageTime); ?></div>
                 <?php if ($peakUsageValue > 0): ?>
                    <p class="text-xs text-light-text-secondary dark:text-gray-500 mt-2">(~<?php echo number_format($peakUsageValue, 2); ?> kWh)</p>
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
                 <p class="text-light-text-secondary dark:text-gray-400">This section could show usage per appliance if smart plugs or similar data sources are integrated.</p>
             </div>
              <div class="scroll-animate scroll-animate-init content-box bg-light-card p-6 rounded-lg shadow-sm dark:bg-dark-card dark:text-gray-100">
                 <h2 class="text-lg font-semibold text-light-text-primary dark:text-white mb-2">Tips for Optimization</h2>
                 <p class="text-light-text-secondary dark:text-gray-400">Based on your usage patterns, consider:</p>
                 <ul class="list-disc list-inside text-sm text-light-text-secondary dark:text-gray-400 mt-2 space-y-1">
                     <li>Running dishwasher during off-peak hours.</li>
                     <li>Switching to LED lighting.</li>
                     <li>Checking appliance energy ratings.</li>
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
        const todayUsageData = <?php echo $chartDataJson; ?>; // This will be '[]' if fetch failed
        const currentTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

        document.addEventListener('DOMContentLoaded', () => {
            const hourlyUsageCtx = document.getElementById('hourlyUsageChart')?.getContext('2d');
            let hourlyChartInstance = null;

            // Only proceed with chart logic if context and data exist
            if (hourlyUsageCtx && Array.isArray(todayUsageData) && todayUsageData.length > 0) {

                // --- Helper: Get Theme Colors (Simplified for this page) ---
                function getThemeColors(theme) {
                    const isDark = theme === 'dark';
                    return {
                        gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                        tickColor: isDark ? '#cbd5e1' : '#4B5567',
                        legendColor: isDark ? '#cbd5e1' : '#1F2937',
                        tooltipBg: isDark ? 'rgba(0, 0, 0, 0.7)' : 'rgba(255, 255, 255, 0.8)',
                        tooltipTitle: isDark ? '#ffffff' : '#000000',
                        tooltipBody: isDark ? '#dddddd' : '#333333',
                        accentBorderColor: isDark ? '#ecc931' : '#2563EB', // Gold / Blue for electricity
                        accentBgColorLine: isDark ? 'rgba(236, 201, 49, 0.2)' : 'rgba(37, 99, 235, 0.2)'
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
                                         if (context.parsed.y !== null) { label += context.parsed.y + ' kWh'; }
                                         return label;
                                     }
                                 }
                             }
                         },
                         scales: {
                             y: { title: { display: true, text: 'Usage (kWh)', color: colors.tickColor }, beginAtZero: true, ticks: { color: colors.tickColor, padding: 5 }, grid: { color: colors.gridColor, drawBorder: false } },
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
                                label: 'Electricity Usage', // Simpler label for tooltip
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

                // Listen for theme changes from dynamic.js
                window.addEventListener('themeUpdated', (event) => {
                    if (event.detail && event.detail.theme) {
                        renderHourlyChart(event.detail.theme);
                    }
                });

            } else if (!hourlyUsageCtx) {
                 console.warn("Hourly usage chart canvas context not found.");
            } // No 'else' needed if data is empty, PHP handles the message

            // --- Download Button Logic ---
            function getElectricityReportData() {
                if (!Array.isArray(todayUsageData) || todayUsageData.length === 0) {
                    alert("No usage data available to download.");
                    return null; // Indicate no data
                }
                const reportData = [];
                const labels = Array.from({ length: 24 }, (_, i) => `${i}:00`);
                const usageMap = todayUsageData.reduce((map, item) => { map[item.hour] = item.usage; return map; }, {});
                labels.forEach((label, i) => {
                    reportData.push({ Time: label, Usage_kWh: usageMap[i] || 0 });
                });
                return reportData;
            }

            // Initialize download button using the global function if it exists
            if (typeof initializeDownloadButton === 'function') {
                initializeDownloadButton('downloadReportBtn', getElectricityReportData, 'electricity_hourly_details');
            } else {
                console.warn("initializeDownloadButton function not found. Download button inactive.");
                // Optionally disable the button if the function is missing
                const downloadBtn = document.getElementById('downloadReportBtn');
                if(downloadBtn) {
                    downloadBtn.disabled = true;
                    downloadBtn.style.opacity = '0.5';
                    downloadBtn.style.cursor = 'not-allowed';
                }
            }

            // --- Scroll Animations (Handled by partials_script.js or dynamic.js) ---
            // Assuming scroll animations are initialized globally

        }); // End DOMContentLoaded
    </script>

</body>
</html>

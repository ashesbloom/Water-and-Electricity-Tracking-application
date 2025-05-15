// Store chart instances globally to allow updates
let weeklyChartInstance; // Overview chart (Today/Weekly/Monthly)
let goalProgressChartInstance; // Electricity goal doughnut chart
let distributionBarChartInstance; // Electricity distribution bar chart
let historicalWaterChartInstance; // Historical water line chart

// Store references to frequently accessed DOM elements
let currentUsageElement;
let goalInputElement;
let goalProgressTextElement;
let currentWaterUsageValueElement;
let waterLevelContainerElement;
let waterGoalInputElement;
let waterGoalProgressBarInnerElement;
let todaySegmentButtonsContainer;

// Store static or placeholder data
const electricityDistributionData = [45, 25, 15, 10, 5]; // Example distribution percentages
const electricityDistributionLabels = ['Appliances', 'Lighting', 'Heating/Cooling', 'Electronics', 'Other'];
const simulatedMaxDailyWater = 400; // Max value for the water level visual effect (in Litres)

// --- Helper function for making API calls ---
/**
 * Fetches data from a specified API endpoint relative to the base URL.
 * Handles basic error checking and JSON parsing.
 * @param {string} apiUrl - The API endpoint path (e.g., '/api/getElectTodayUse').
 * @returns {Promise<object|null>} - A promise resolving to the JSON data or an error object.
 */
async function fetchData(apiUrl) {
    try {
        const baseUrl = typeof APP_BASE_URL !== 'undefined' ? APP_BASE_URL : '/tracker'; // APP_BASE_URL should be defined globally via PHP
        const fullUrl = `${baseUrl}${apiUrl}`;
        const response = await fetch(fullUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        });
        const responseText = await response.text();

        if (!response.ok) {
            let errorData = { error: `Request failed with status ${response.status}` };
            try {
                const parsedError = JSON.parse(responseText);
                if (parsedError && parsedError.error) errorData.error = parsedError.error;
            } catch (e) { /* Ignore JSON parse error on error response */ }
            console.error(`[fetchData] API Error (${response.status}) for ${apiUrl}:`, errorData.error);
            return { error: true, status: response.status, message: errorData.error };
        }

        try {
             const jsonData = JSON.parse(responseText);
             return jsonData;
        } catch (e) {
             console.error(`[fetchData] Failed to parse success JSON for ${apiUrl}:`, e, responseText);
             return { error: true, message: 'Invalid JSON received from server' };
        }
    } catch (error) {
        console.error(`[fetchData] Network or Fetch Error for ${apiUrl}:`, error);
        return { error: true, message: 'Network error or failed to fetch' };
    }
}

// --- Function to Load Initial 'Today' Data on Page Load ---
/**
 * Fetches and displays the 'Today' overview chart data when the page initially loads.
 * Handles button disabling/enabling and styling the 'Today' button as active.
 */
async function loadInitialTodayData() {
    const allTimeframeButtons = document.querySelectorAll('.usage-overview-buttons button');
    allTimeframeButtons.forEach(btn => { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); });

    try {
        await loadOverviewChartData('today'); // Fetch and display 'today' data

        // Style the 'Today' button as active
        const todayButton = document.querySelector('.usage-overview-buttons button[data-timeframe="today"]');
        if(todayButton) {
            allTimeframeButtons.forEach(btn => { // Reset styles first
                 btn.classList.remove('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900');
                 btn.classList.add('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200');
            });
            todayButton.classList.remove('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200');
            todayButton.classList.add('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900');
        } else {
            console.warn("[loadInitialTodayData] 'Today' button not found for styling.");
        }

    } catch (error) {
        console.error("[loadInitialTodayData] Error during initial data load:", error);
    } finally {
        allTimeframeButtons.forEach(btn => { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed'); });
    }
}


// --- Document Ready Event Listener ---
/**
 * Main entry point after the HTML document is fully loaded and parsed.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Cache DOM element references
    currentUsageElement = document.getElementById('currentUsage');
    goalInputElement = document.getElementById('dailyGoalInput');
    goalProgressTextElement = document.getElementById('goalProgressText');
    currentWaterUsageValueElement = document.getElementById('currentWaterUsageValue');
    waterLevelContainerElement = document.getElementById('waterLevelContainer');
    waterGoalProgressBarInnerElement = document.getElementById('waterGoalProgressBarInner');
    waterGoalInputElement = document.getElementById('dailyWaterGoalInput');
    todaySegmentButtonsContainer = document.getElementById('todayTimeSegments');

    // Initialize charts (this now also triggers the initial data load)
    initializeCharts();
    // Setup button listeners
    setupTimeframeButtons();
    setupGoalProgressListener();

    // Start usage animations
    const initialElectricityUsage = parseFloat(currentUsageElement?.textContent || '0');
    initializeUsageAnimation(initialElectricityUsage, 'electricity');
    const initialWaterUsage = parseFloat(currentWaterUsageValueElement?.textContent || '0');
    initializeUsageAnimation(initialWaterUsage, 'water');
    updateWaterFillWidth(0); // Set initial water fill

}); // --- End DOMContentLoaded ---

// --- Usage Animation ---
/**
 * Animates a numerical counter from 0 up to a target value.
 * @param {number} targetUsageValue - The final value to animate to.
 * @param {'electricity' | 'water'} type - The type of usage being animated.
 */
function initializeUsageAnimation(targetUsageValue, type) {
    let usageElement = (type === 'electricity') ? currentUsageElement : currentWaterUsageValueElement;
    let updateFunction = (type === 'electricity') ? null : updateWaterFillWidth;

    if (!usageElement) {
        // Fallback updates if element is missing
        if (updateFunction) updateFunction(parseFloat(targetUsageValue) || 0);
        if (type === 'water') updateWaterGoalProgress();
        return;
    }

    let usageCounter = 0;
    const targetValue = parseFloat(targetUsageValue) || 0;

    if (isNaN(targetValue)) {
        usageElement.textContent = 'N/A';
        if (updateFunction) updateFunction(0);
        if (type === 'water') updateWaterGoalProgress();
        return;
    }

    usageElement.textContent = (type === 'electricity') ? '0.00' : '0';
    if (updateFunction) updateFunction(0);
    if (type === 'water') updateWaterGoalProgress();

    const step = Math.max( (type === 'electricity' ? 0.1 : 1), Math.ceil(targetValue / 50));
    const intervalTime = 25; // ms

    const usageInterval = setInterval(() => {
        usageCounter += step;
        let displayValue = usageCounter;

        if (usageCounter >= targetValue) {
            displayValue = targetValue;
            clearInterval(usageInterval);
            // Dispatch event used by goal chart
            usageElement.dispatchEvent(new CustomEvent(`${type}UsageUpdated`, { bubbles: true, detail: { value: targetValue } }));
        }

        const formattedValue = (type === 'electricity') ? displayValue.toFixed(2) : Math.round(displayValue);
        usageElement.textContent = formattedValue;

        // Update related visuals
        if (updateFunction) updateFunction(displayValue);
        if (type === 'water') updateWaterGoalProgress();
    }, intervalTime);
}

// --- Chart Initialization ---
/**
 * Creates and initializes all Chart.js instances on the page.
 * Fetches initial data for the historical water chart.
 * Triggers the initial load for the main overview chart via loadInitialTodayData.
 */
async function initializeCharts() {
    const initialTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

    // 1. Initialize Weekly Usage Overview Chart
    const weeklyUsageCtx = document.getElementById('weeklyUsageChart')?.getContext('2d');
    if (weeklyUsageCtx) {
        const mainChartOptions = getChartOptions(initialTheme, 'line', 'overview');
        try {
            weeklyChartInstance = new Chart(weeklyUsageCtx, {
                type: 'line',
                data: { labels: [], datasets: [ { label: 'Electricity (kWh)', data: [], hidden: false, yAxisID: 'y' }, { label: 'Water (Litres)', data: [], hidden: false, yAxisID: 'y1' } ] },
                options: mainChartOptions
            });
            updateDatasetStyles(weeklyChartInstance, 'line', initialTheme);
            document.dispatchEvent(new Event('mainChartReady')); // Kept for potential future use

            // Trigger the initial data load
            await loadInitialTodayData();

        } catch (error) {
            console.error("[initializeCharts] Error creating weeklyChartInstance:", error);
        }
    } else { console.error("[initializeCharts] Canvas context 'weeklyUsageChart' NOT FOUND."); }

    // 2. Initialize Goal Progress Chart
    const goalProgressCtx = document.getElementById('goalProgressChart')?.getContext('2d');
    if (goalProgressCtx) {
        const goalChartOptions = getChartOptions(initialTheme, 'doughnut', 'goalProgress');
        goalProgressChartInstance = new Chart(goalProgressCtx, {
            type: 'doughnut',
            data: { labels: ['Used', 'Remaining'], datasets: [{ label: 'Goal Progress', data: [0, 100], backgroundColor: [ getThemeColors(initialTheme).goalUsedColor, getThemeColors(initialTheme).goalRemainingColor ], borderColor: getThemeColors(initialTheme).pieBorderColor, borderWidth: 1, hoverOffset: 4 }] },
            options: goalChartOptions
        });
        updateGoalProgressChart();
        if(currentUsageElement) { currentUsageElement.addEventListener('electricityUsageUpdated', updateGoalProgressChart); }
    } else { console.error("[initializeCharts] Canvas context 'goalProgressChart' NOT FOUND."); }

    // 3. Initialize Distribution Bar Chart
    const distributionBarCtx = document.getElementById('distributionBarChart')?.getContext('2d');
    if (distributionBarCtx) {
        const distributionOptions = getChartOptions(initialTheme, 'bar');
        const colors = getThemeColors(initialTheme);
        const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', colors.isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)', colors.isDark ? 'rgba(74, 222, 128, 0.7)' : 'rgba(34, 197, 94, 0.7)', colors.isDark ? 'rgba(167, 139, 250, 0.7)' : 'rgba(139, 92, 246, 0.7)', colors.isDark ? 'rgba(248, 113, 113, 0.7)' : 'rgba(239, 68, 68, 0.7)' ];
        const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', colors.isDark ? 'rgba(96, 165, 250, 0.9)' : 'rgba(59, 130, 246, 0.9)', colors.isDark ? 'rgba(74, 222, 128, 0.9)' : 'rgba(34, 197, 94, 0.9)', colors.isDark ? 'rgba(167, 139, 250, 0.9)' : 'rgba(139, 92, 246, 0.9)', colors.isDark ? 'rgba(248, 113, 113, 0.9)' : 'rgba(239, 68, 68, 0.9)' ];
        distributionBarChartInstance = new Chart(distributionBarCtx, {
            type: 'bar',
            data: { labels: electricityDistributionLabels, datasets: [{ label: 'Usage Distribution', data: electricityDistributionData, backgroundColor: distributionBarColors, hoverBackgroundColor: distributionHoverColors, borderColor: colors.barBorderColor, borderWidth: 1, borderRadius: 4 }] },
            options: distributionOptions
        });
    } else { console.error("[initializeCharts] Canvas context 'distributionBarChart' NOT FOUND."); }

    // 4. Initialize Historical Water Chart and Fetch Data
    const historicalWaterCtx = document.getElementById('historicalWaterChart')?.getContext('2d');
    if (historicalWaterCtx) {
        const historicalWaterOptions = getChartOptions(initialTheme, 'line');
        historicalWaterChartInstance = new Chart(historicalWaterCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Water Usage (Litres)', data: [], fill: true, tension: 0.4, backgroundColor: getThemeColors(initialTheme, true).accentBgColorLine, borderColor: getThemeColors(initialTheme, true).accentBorderColor, borderWidth: 2, pointRadius: 2, pointHoverRadius: 5 }] },
            options: historicalWaterOptions
        });
        updateDatasetStyles(historicalWaterChartInstance, 'line', initialTheme, true);

        const waterData = await fetchData('/api/historicalWater');
        if (waterData && !waterData.error && Array.isArray(waterData)) {
            const labels = waterData.map(item => item.date);
            const usage = waterData.map(item => item.usage);
            historicalWaterChartInstance.data.labels = labels;
            historicalWaterChartInstance.data.datasets[0].data = usage;
            historicalWaterChartInstance.update();
        } else {
            console.error("[initializeCharts] Failed to fetch or process historical water data:", waterData?.message || "Unknown error");
        }
    } else { console.error("[initializeCharts] Canvas context 'historicalWaterChart' NOT FOUND."); }
}

// --- Water Goal Progress & Fill ---
/**
 * Updates the visual progress bar for the daily water goal.
 */
function updateWaterGoalProgress() {
    if (!currentWaterUsageValueElement || !waterGoalInputElement || !waterGoalProgressBarInnerElement) { return; }
    const rawUsageText = currentWaterUsageValueElement.textContent;
    const rawGoalValue = waterGoalInputElement.value;
    const currentUsage = parseFloat(rawUsageText) || 0;
    const goal = parseFloat(rawGoalValue) || 0;
    let percentage = (goal > 0 && currentUsage >= 0) ? Math.min((currentUsage / goal) * 100, 100) : 0;
    percentage = Math.max(0, percentage); // Ensure percentage is not negative
    waterGoalProgressBarInnerElement.style.width = `${percentage}%`;
}
/**
 * Updates the width of the animated water fill effect based on current usage.
 * @param {number} currentWaterUsage - The current water usage value.
 */
function updateWaterFillWidth(currentWaterUsage) {
    if (!waterLevelContainerElement) { return; }
    const maxUsage = simulatedMaxDailyWater; // Use the defined max value for the visual
    let percentage = (maxUsage > 0 && currentWaterUsage >= 0) ? Math.min((currentWaterUsage / maxUsage) * 100, 100) : 0;
    // Set CSS variable and direct width for the water fill effect
    waterLevelContainerElement.style.setProperty('--water-level-width', `${percentage}%`);
    waterLevelContainerElement.style.width = `${percentage}%`;
}

// --- Theme Handling ---
/**
 * Provides theme-specific color values for charts.
 * @param {string} theme - The current theme ('light' or 'dark').
 * @param {boolean} [useVariant=false] - Whether to use variant accent colors (e.g., for water charts).
 * @returns {object} An object containing color values.
 */
function getThemeColors(theme, useVariant = false) {
    const isDark = theme === 'dark';
    // Define base colors for light and dark themes
    const colors = {
        isDark: isDark,
        gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
        tickColor: isDark ? '#cbd5e1' : '#4B5567', // Default tick color
        legendColor: isDark ? '#cbd5e1' : '#1F2937',
        tooltipBg: isDark ? 'rgba(0, 0, 0, 0.7)' : 'rgba(255, 255, 255, 0.8)',
        tooltipTitle: isDark ? '#ffffff' : '#000000',
        tooltipBody: isDark ? '#dddddd' : '#333333',
        barBorderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0,0,0,0.1)',
        goalUsedColor: isDark ? 'rgba(245, 158, 11, 0.9)' : 'rgba(59, 130, 246, 0.9)', // Gold / Blue
        goalRemainingColor: isDark ? 'rgba(55, 65, 81, 0.6)' : 'rgba(229, 231, 235, 0.8)', // Dark Gray / Light Gray
        goalExceededColor: isDark ? 'rgba(239, 68, 68, 0.9)' : 'rgba(220, 38, 38, 0.9)', // Red
        pieBorderColor: isDark ? '#111827' : '#F3F4F6', // Background color for contrast
        // Dimmed colors for highlighting segments
        dimmedBorderColorLight: 'rgba(37, 99, 235, 0.15)', // Dimmed blue
        dimmedBorderColorDark: 'rgba(236, 201, 49, 0.15)', // Dimmed gold
        dimmedVariantBorderColorLight: 'rgba(16, 185, 129, 0.15)', // Dimmed emerald
        dimmedVariantBorderColorDark: 'rgba(96, 165, 250, 0.15)' // Dimmed light blue
    };
    // Apply variant colors if requested (used for water-related charts)
    if (useVariant) {
        colors.accentBorderColor = isDark ? '#60a5fa' : '#10b981'; // Light Blue / Emerald
        colors.accentBgColorLine = isDark ? 'rgba(96, 165, 250, 0.2)' : 'rgba(16, 185, 129, 0.2)';
        colors.accentBgColorBar = isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(16, 185, 129, 0.7)';
        colors.tickColor = isDark ? '#93c5fd' : '#059669'; // Brighter blue / Darker green for variant ticks
        colors.dimmedBorderColor = isDark ? colors.dimmedVariantBorderColorDark : colors.dimmedVariantBorderColorLight;
    } else { // Default accent colors (Electricity)
        colors.accentBorderColor = isDark ? '#ecc931' : '#2563EB'; // Gold / Blue
        colors.accentBgColorLine = isDark ? 'rgba(236, 201, 49, 0.2)' : 'rgba(37, 99, 235, 0.2)';
        colors.accentBgColorBar = isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(37, 99, 235, 0.7)';
        colors.dimmedBorderColor = isDark ? colors.dimmedBorderColorDark : colors.dimmedBorderColorLight;
    }
    return colors;
}
/**
 * Generates Chart.js options object based on the current theme and chart type.
 * @param {string} theme - 'light' or 'dark'.
 * @param {string} [type='line'] - 'line', 'bar', or 'doughnut'.
 * @param {string|null} [chartPurpose=null] - Specific purpose ('overview', 'goalProgress') for fine-tuning.
 * @returns {object} Chart.js options configuration.
 */
function getChartOptions(theme, type = 'line', chartPurpose = null) {
    const colors = getThemeColors(theme);
    const colorsVariant = getThemeColors(theme, true); // For secondary axis if needed

    let options = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 800 }, // Standard animation duration
        plugins: {
            legend: {
                display: chartPurpose === 'overview', // Only show legend for the main overview chart
                position: 'top',
                labels: { color: colors.legendColor, boxWidth: 12, padding: 10 }
            },
            tooltip: {
                enabled: true,
                backgroundColor: colors.tooltipBg,
                titleColor: colors.tooltipTitle,
                bodyColor: colors.tooltipBody,
                padding: 8,
                boxPadding: 4,
                intersect: false, // Show tooltip even when not directly hovering over point/bar
                mode: 'index', // Show tooltips for all datasets at the same index
            }
        },
        scales: { // Default scales for line/bar
            y: { // Primary Y-axis (usually electricity)
                type: 'linear', display: true, position: 'left',
                beginAtZero: true,
                ticks: { color: colors.tickColor, padding: 5 },
                grid: { color: colors.gridColor, drawBorder: false }
            },
            x: { // X-axis
                ticks: { color: colors.tickColor, padding: 5 },
                grid: { color: colors.gridColor, display: true, drawOnChartArea: type !== 'bar' } // Hide grid lines for bar charts on x-axis
            }
        }
    };

    // Type-specific adjustments
    if (type === 'doughnut') {
        options.cutout = '70%'; // Make it a doughnut chart
        options.plugins.legend.display = false; // No legend for doughnut
        delete options.scales; // No scales needed for doughnut
        if (chartPurpose === 'goalProgress') {
            options.plugins.tooltip.enabled = false; // Disable tooltips for the simple goal chart
        }
    } else if (type === 'bar') {
        // Hide X-axis grid lines for bar charts for cleaner look
        if (options.scales?.x?.grid) options.scales.x.grid.display = false;
    }

    // Purpose-specific adjustments
    if (chartPurpose === 'overview' && type !== 'doughnut') {
        // Add a secondary Y-axis (y1) for the overview chart (for water)
        options.scales.y1 = {
            type: 'linear', display: true, position: 'right', // Position on the right
            beginAtZero: true,
            ticks: { color: colorsVariant.tickColor, padding: 5 }, // Use variant color for ticks
            grid: { drawOnChartArea: false }, // Don't draw grid lines for the secondary axis
        };
    }
    return options;
}
/**
 * Updates the colors of all existing chart instances when the theme changes.
 * @param {string} theme - The new theme ('light' or 'dark').
 */
function updateChartColors(theme) {
    const colors = getThemeColors(theme);
    const colorsVariant = getThemeColors(theme, true);

    // Update Main Overview Chart
    if (weeklyChartInstance) {
        const options = weeklyChartInstance.options;
        // Update scale colors
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colors.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colors.tickColor;
        if(options.scales?.y1?.ticks) options.scales.y1.ticks.color = colorsVariant.tickColor; // Secondary axis uses variant
        // Update plugin colors
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        if(options.plugins?.tooltip) {
            options.plugins.tooltip.backgroundColor = colors.tooltipBg;
            options.plugins.tooltip.titleColor = colors.tooltipTitle;
            options.plugins.tooltip.bodyColor = colors.tooltipBody;
        }
        // Update dataset styles (line/bar colors, etc.)
        updateDatasetStyles(weeklyChartInstance, weeklyChartInstance.config.type, theme);

        // Re-apply segment highlighting if 'Today' view is active
        const todayButton = document.querySelector('.usage-overview-buttons button[data-timeframe="today"]');
        const activeSegmentButton = document.querySelector('#todayTimeSegments .segment-button.active');
        if (todayButton && todayButton.classList.contains('bg-light-accent') && activeSegmentButton) {
             highlightTimeSegment(activeSegmentButton.dataset.segment); // Re-apply highlight with new theme colors
        }
        weeklyChartInstance.update('none'); // Update without animation
    }

    // Update Goal Progress Chart
    if (goalProgressChartInstance) {
         updateGoalProgressChart(); // This function already handles theme colors
    }

    // Update Distribution Bar Chart
    if (distributionBarChartInstance) {
        const options = distributionBarChartInstance.options;
        // Update scale/plugin colors
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colors.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colors.tickColor;
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        // Update dataset colors (using the specific distribution colors)
        const dataset = distributionBarChartInstance.data.datasets[0];
        const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', colors.isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)', colors.isDark ? 'rgba(74, 222, 128, 0.7)' : 'rgba(34, 197, 94, 0.7)', colors.isDark ? 'rgba(167, 139, 250, 0.7)' : 'rgba(139, 92, 246, 0.7)', colors.isDark ? 'rgba(248, 113, 113, 0.7)' : 'rgba(239, 68, 68, 0.7)' ];
        const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', colors.isDark ? 'rgba(96, 165, 250, 0.9)' : 'rgba(59, 130, 246, 0.9)', colors.isDark ? 'rgba(74, 222, 128, 0.9)' : 'rgba(34, 197, 94, 0.9)', colors.isDark ? 'rgba(167, 139, 250, 0.9)' : 'rgba(139, 92, 246, 0.9)', colors.isDark ? 'rgba(248, 113, 113, 0.9)' : 'rgba(239, 68, 68, 0.9)' ];
        dataset.backgroundColor = distributionBarColors;
        dataset.hoverBackgroundColor = distributionHoverColors;
        dataset.borderColor = colors.barBorderColor;
        distributionBarChartInstance.update('none');
    }

    // Update Historical Water Chart
    if (historicalWaterChartInstance) {
        const options = historicalWaterChartInstance.options;
        // Update scale/plugin colors (using variant tick colors for water)
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colorsVariant.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colorsVariant.tickColor;
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        // Update dataset styles using variant colors
        updateDatasetStyles(historicalWaterChartInstance, 'line', theme, true);
        historicalWaterChartInstance.update('none');
    }
}
/**
 * Updates the visual styles (colors, fills, points) of datasets within a chart.
 * @param {Chart} chartInstance - The Chart.js instance.
 * @param {string} type - The chart type ('line' or 'bar').
 * @param {string} theme - 'light' or 'dark'.
 * @param {boolean} [useVariantColors=false] - Whether to use variant accent colors.
 */
function updateDatasetStyles(chartInstance, type, theme, useVariantColors = false) {
    if (!chartInstance || !chartInstance.data.datasets) return;
    const colors = getThemeColors(theme);
    const colorsVariant = getThemeColors(theme, true);

    chartInstance.data.datasets.forEach((dataset, index) => {
        // Determine if this dataset should use variant colors
        // Used for the second dataset (water) in the overview chart or the historical water chart
        const isOverviewWater = (index === 1 && chartInstance.canvas.id === 'weeklyUsageChart');
        const isHistoricalWater = (chartInstance.canvas.id === 'historicalWaterChart' && index === 0);
        const currentColors = (isOverviewWater || isHistoricalWater || useVariantColors) ? colorsVariant : colors;

        if (type === 'line') {
            dataset.backgroundColor = currentColors.accentBgColorLine;
            dataset.borderColor = currentColors.accentBorderColor;
            dataset.borderWidth = 2;
            dataset.tension = 0.4; // Makes the line curved
            dataset.fill = true; // Fill area under the line
            // Show points only for historical water chart for clarity
            dataset.pointRadius = (isHistoricalWater) ? 2 : 0;
            dataset.pointHoverRadius = 5;
            dataset.pointBackgroundColor = currentColors.accentBorderColor;
            delete dataset.segment; // Ensure segment styling is removed when applying base styles
        } else { // 'bar'
            // Special handling for the distribution chart's unique colors
            if (chartInstance.canvas.id === 'distributionBarChart' && index === 0) {
                const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', /* ... other colors ... */ ];
                const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', /* ... other hover colors ... */ ];
                dataset.backgroundColor = distributionBarColors;
                dataset.hoverBackgroundColor = distributionHoverColors;
            } else { // Default bar styling
                dataset.backgroundColor = currentColors.accentBgColorBar;
                dataset.hoverBackgroundColor = currentColors.accentBorderColor; // Use border color for hover
            }
            dataset.borderColor = currentColors.barBorderColor;
            // Reset line-specific properties
            dataset.tension = 0; dataset.fill = false; dataset.pointRadius = undefined; dataset.pointHoverRadius = undefined; dataset.pointBackgroundColor = undefined;
        }
    });

    // Adjust grid display based on type
    if (chartInstance.options.scales?.x?.grid) {
        chartInstance.options.scales.x.grid.display = (type !== 'bar'); // Hide x-grid for bars
        chartInstance.options.scales.x.grid.drawOnChartArea = (type !== 'bar');
    }
    // Toggle secondary Y-axis visibility based on chart type and dataset visibility
    if (chartInstance.options.scales?.y1) {
        const isOverviewChart = chartInstance.canvas.id === 'weeklyUsageChart';
        const isDataset1Visible = chartInstance.data.datasets.length > 1 && !chartInstance.data.datasets[1].hidden;
        chartInstance.options.scales.y1.display = isOverviewChart && type !== 'doughnut' && isDataset1Visible;
    }
}
// Listen for theme changes dispatched from dynamic.js
window.addEventListener('themeUpdated', (event) => {
    if (event.detail && event.detail.theme) {
        updateChartColors(event.detail.theme);
    }
});

// --- Function to Load Overview Chart Data Based on Timeframe ---
/**
 * Fetches data based on the selected timeframe and updates the main overview chart.
 * Handles switching between line and bar chart types.
 * @param {'today' | 'weekly' | 'monthly'} timeframe - The selected timeframe.
 */
async function loadOverviewChartData(timeframe) {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const chartTitleElement = document.getElementById('dailyUsageChartTitle');

    // Ensure chart instance and title element exist
    if (!weeklyChartInstance || !chartTitleElement) {
        console.error("[loadOverviewChartData] Prerequisite missing: Chart instance or title element.");
        return;
    }

    // Show/hide the hourly segment buttons only for the 'Today' view
    if (todaySegmentButtonsContainer) {
        todaySegmentButtonsContainer.classList.toggle('hidden', timeframe !== 'today');
    }

    // Default settings
    let chartType = 'line';
    let chartTitle = 'Hourly Usage (Today)';
    let labels = [];
    let datasets = [
        { label: 'Electricity (kWh)', data: [], hidden: false, yAxisID: 'y' },
        { label: 'Water (Litres)', data: [], hidden: false, yAxisID: 'y1' }
    ];
    // Static data placeholder for 'monthly' view
    const staticMonthlyData = { type: 'bar', title: 'Weekly Usage (Last 4 Weeks)', labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], electricity: [400, 450, 420, 480], water: [1300, 1450, 1350, 1500], unitElec: 'kWh', unitWater: 'Litres' };

    try {
        // Fetch and process data based on timeframe
        if (timeframe === 'today') {
            chartType = 'line';
            chartTitle = 'Hourly Usage (Today)';
            const [elecData, waterData] = await Promise.all([
                 fetchData('/api/getElectTodayUse'),
                 fetchData('/api/getWaterTodayUse')
            ]);

            labels = Array.from({ length: 24 }, (_, i) => `${i}:00`); // Generate 24-hour labels

            // Process electricity data
            if (elecData && !elecData.error && Array.isArray(elecData)) {
                 const usageMap = elecData.reduce((map, item) => { map[item.hour] = item.usage; return map; }, {});
                 datasets[0].data = labels.map((_, i) => usageMap[i] || 0); // Fill data, defaulting to 0
            } else {
                console.error("[loadOverviewChartData('today')] Failed to fetch/process electricity data:", elecData?.message);
                datasets[0].data = labels.map(() => 0); // Use default 0 data on error
            }

            // Process water data
            if (waterData && !waterData.error && Array.isArray(waterData)) {
                 const usageMap = waterData.reduce((map, item) => { map[item.hour] = item.usage; return map; }, {});
                 datasets[1].data = labels.map((_, i) => usageMap[i] || 0);
            } else {
                console.error("[loadOverviewChartData('today')] Failed to fetch/process water data:", waterData?.message);
                datasets[1].data = labels.map(() => 0);
                datasets[1].hidden = true; // Hide water dataset if data fails
            }

        } else if (timeframe === 'weekly') {
            chartType = 'bar'; // Change chart type to bar for weekly view
            chartTitle = 'Daily Usage (Last 7 Days)';
            const overviewData = await fetchData('/api/usageOverview');
            if (overviewData && !overviewData.error && overviewData.labels && overviewData.electricity && overviewData.water) {
                 labels = overviewData.labels;
                 datasets[0].data = overviewData.electricity;
                 datasets[1].data = overviewData.water;
            } else {
                console.error("[loadOverviewChartData('weekly')] Failed to fetch/process weekly overview data:", overviewData?.message);
                labels = ['Error']; datasets[0].data = [0]; datasets[1].data = [0]; // Show error state
            }
        } else if (timeframe === 'monthly') {
            chartType = 'bar'; // Use bar chart for monthly view
            chartTitle = staticMonthlyData.title;
            labels = staticMonthlyData.labels;
            datasets[0].data = staticMonthlyData.electricity;
            datasets[0].label = `Electricity (${staticMonthlyData.unitElec})`;
            datasets[1].data = staticMonthlyData.water;
            datasets[1].label = `Water (${staticMonthlyData.unitWater})`;
        }

        // --- Update Chart Instance ---
        chartTitleElement.textContent = chartTitle; // Update the chart title display

        // Check if chart type needs to be changed
        const needsTypeChange = weeklyChartInstance.config.type !== chartType;
        if (needsTypeChange) {
             weeklyChartInstance.config.type = chartType; // Update chart type
             weeklyChartInstance.options = getChartOptions(currentTheme, chartType, 'overview'); // Get new options for the type
        }

        // Update chart data
        weeklyChartInstance.data.labels = labels;
        weeklyChartInstance.data.datasets[0].data = datasets[0].data;
        weeklyChartInstance.data.datasets[0].label = datasets[0].label;
        weeklyChartInstance.data.datasets[0].hidden = datasets[0].hidden;
        weeklyChartInstance.data.datasets[0].yAxisID = 'y'; // Ensure correct axis ID
        if (weeklyChartInstance.data.datasets[1]) { // Check if second dataset exists
            weeklyChartInstance.data.datasets[1].data = datasets[1].data;
            weeklyChartInstance.data.datasets[1].label = datasets[1].label;
            weeklyChartInstance.data.datasets[1].hidden = datasets[1].hidden;
            weeklyChartInstance.data.datasets[1].yAxisID = 'y1'; // Ensure correct axis ID
        }

        // Apply visual styles based on the (potentially new) chart type and theme
        updateDatasetStyles(weeklyChartInstance, chartType, currentTheme);

        // Handle segment highlighting for 'Today' view
        if(timeframe === 'today') {
             // Ensure 'All Day' segment button is active and highlighting is applied
             const segmentButtons = document.querySelectorAll('#todayTimeSegments .segment-button');
             segmentButtons.forEach(btn => btn.classList.remove('active'));
             const allDayBtn = document.querySelector('#todayTimeSegments button[data-segment="all"]');
             if(allDayBtn) allDayBtn.classList.add('active');
             highlightTimeSegment('all'); // Apply the default 'all day' highlight
        } else {
            // Remove any segment-specific styling if switching away from 'Today'
            weeklyChartInstance.data.datasets.forEach(dataset => delete dataset.segment);
        }

        // Update the chart on the canvas
        weeklyChartInstance.update();

    } catch (error) {
        // Handle errors during data fetching or processing
        console.error(`[loadOverviewChartData] Error processing timeframe ${timeframe}:`, error);
        chartTitleElement.textContent = `Error loading ${timeframe} data`;
        weeklyChartInstance.data.labels = ['Error'];
        weeklyChartInstance.data.datasets[0].data = [0];
        if (weeklyChartInstance.data.datasets[1]) weeklyChartInstance.data.datasets[1].data = [0];
        weeklyChartInstance.update();
    }
}


// --- Timeframe Button Setup ---
/**
 * Adds click event listeners to the timeframe buttons (Today, Weekly, Monthly).
 */
function setupTimeframeButtons() {
    const buttons = document.querySelectorAll('.usage-overview-buttons button');
    const segmentButtons = document.querySelectorAll('#todayTimeSegments .segment-button');

    buttons.forEach(button => {
        button.addEventListener('click', async () => {
            const timeframe = button.dataset.timeframe;

            // Update button styles to show active state and disable during load
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.classList.remove('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900');
                btn.classList.add('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200', 'opacity-50', 'cursor-not-allowed');
            });
            button.classList.remove('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200');
            button.classList.add('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900');

            // Load the data for the selected timeframe
            await loadOverviewChartData(timeframe);

            // Re-enable buttons after data is loaded
            buttons.forEach(btn => {
                 btn.disabled = false;
                 btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        });
    });

    // Add listeners for the hourly segment buttons (shown only for 'Today' view)
    segmentButtons.forEach(button => {
        button.addEventListener('click', () => {
            const segment = button.dataset.segment;
            highlightTimeSegment(segment); // Apply visual highlighting

            // Update active class for segment buttons
            segmentButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
}

// --- Function to Highlight Time Segments ---
/**
 * Applies visual styling (dimming/highlighting) to the 'Today' line chart
 * based on the selected time segment (e.g., Morning, Evening).
 * @param {'all' | 'night' | 'morning' | 'afternoon' | 'evening'} segment - The time segment to highlight.
 */
function highlightTimeSegment(segment) {
    // Only proceed if it's the overview chart and currently a line chart
    if (!weeklyChartInstance || weeklyChartInstance.config.type !== 'line') {
        return;
    }
    const currentTheme = localStorage.getItem('theme') || 'light';
    const colors = getThemeColors(currentTheme);
    const colorsVariant = getThemeColors(currentTheme, true);
    const chartTitleElement = document.getElementById('dailyUsageChartTitle');

    // Define time ranges for segments
    const segments = {
        night: { start: 0, end: 5, title: "Night (12AM-6AM)" },
        morning: { start: 6, end: 11, title: "Morning (6AM-12PM)" },
        afternoon: { start: 12, end: 17, title: "Afternoon (12PM-6PM)" },
        evening: { start: 18, end: 23, title: "Evening (6PM-12AM)" },
        all: { start: 0, end: 23, title: "Hourly Usage (Today)" } // Default 'all'
    };
    const selectedRange = segments[segment] || segments.all;

    // Update chart title to reflect the selected segment
    if (chartTitleElement) { chartTitleElement.textContent = selectedRange.title; }

    // Apply segment styling dynamically using Chart.js segment API
    weeklyChartInstance.data.datasets.forEach((dataset, datasetIndex) => {
        const isWater = datasetIndex === 1; // Check if it's the water dataset (index 1)
        // Determine colors based on whether it's the primary or variant dataset
        const defaultBorderColor = isWater ? colorsVariant.accentBorderColor : colors.accentBorderColor;
        const dimmedBorderColor = isWater ? colorsVariant.dimmedBorderColor : colors.dimmedBorderColor;
        const defaultBorderWidth = 2;
        const dimmedBorderWidth = 1.5; // Slightly thinner for dimmed segments

        // Define segment styling for border color and width
        dataset.segment = {
            borderColor: ctx => {
                const index = ctx.p0.parsed.x; // Get the x-index of the point
                // Check if the point falls within the selected segment range
                const isInSegment = (segment === 'all' || (index >= selectedRange.start && index <= selectedRange.end));
                return isInSegment ? defaultBorderColor : dimmedBorderColor; // Apply dimmed color if outside segment
            },
            borderWidth: ctx => {
                const index = ctx.p0.parsed.x;
                const isInSegment = (segment === 'all' || (index >= selectedRange.start && index <= selectedRange.end));
                return isInSegment ? defaultBorderWidth : dimmedBorderWidth; // Apply dimmed width if outside segment
            }
        };
        // Apply point styling based on segment
        dataset.pointBorderColor = defaultBorderColor;
        dataset.pointBackgroundColor = defaultBorderColor;
        dataset.pointRadius = ctx => { // Show points only within the highlighted segment
            const index = ctx.dataIndex;
            const isInSegment = (segment === 'all' || (index >= selectedRange.start && index <= selectedRange.end));
            return isInSegment ? 2 : 0; // Radius 0 hides the point
        };
        dataset.pointHoverRadius = ctx => { // Allow hover only within the highlighted segment
            const index = ctx.dataIndex;
            const isInSegment = (segment === 'all' || (index >= selectedRange.start && index <= selectedRange.end));
            return isInSegment ? 5 : 0;
        };
    });

    // Update the chart to apply the new segment styling
    weeklyChartInstance.update();
}


// --- Goal Progress Chart Update ---
/**
 * Updates the electricity goal progress doughnut chart based on current usage and goal input.
 */
function updateGoalProgressChart() {
    if (!goalProgressChartInstance || !currentUsageElement || !goalInputElement || !goalProgressTextElement) { return; }

    const currentUsage = parseFloat(currentUsageElement.textContent) || 0;
    const goal = parseFloat(goalInputElement.value) || 0;
    let percentage = 0;

    if (goal > 0) {
        percentage = Math.round((currentUsage / goal) * 100);
    } else {
        percentage = 0; // Treat 0 or invalid goal as 0% progress
    }

    // Update the percentage text display
    goalProgressTextElement.textContent = (goal > 0) ? `${percentage}%` : `N/A`;

    // Calculate values for the doughnut chart (capped at 100% visually)
    const displayPercentage = (goal > 0) ? Math.min(percentage, 100) : 0;
    const remainingPercentage = Math.max(0, 100 - displayPercentage);

    goalProgressChartInstance.data.datasets[0].data = [displayPercentage, remainingPercentage];

    // Update colors based on whether the goal is exceeded
    const currentTheme = localStorage.getItem('theme') || 'light';
    const colors = getThemeColors(currentTheme);
    let usedColor;

    if (goal > 0 && percentage > 100) {
        usedColor = colors.goalExceededColor; // Use red if goal exceeded
    } else {
        usedColor = colors.goalUsedColor; // Use standard accent color otherwise
    }

    goalProgressChartInstance.data.datasets[0].backgroundColor = [usedColor, colors.goalRemainingColor];
    goalProgressChartInstance.data.datasets[0].borderColor = colors.pieBorderColor; // Use background color for border
    goalProgressChartInstance.update(); // Update the chart
}
/**
 * Sets up event listeners for the goal input field.
 */
function setupGoalProgressListener() {
    if (goalInputElement) {
        // Update chart immediately on input change
        goalInputElement.addEventListener('input', updateGoalProgressChart);
        // Listen for custom events potentially dispatched elsewhere (e.g., after animation)
        goalInputElement.addEventListener('goalUpdated', updateGoalProgressChart);
        goalInputElement.addEventListener('goalLoaded', updateGoalProgressChart);
    } else { console.warn("[setupGoalProgressListener] Goal input element not found."); }

    if (currentUsageElement) {
        // Listen for the custom event dispatched when the usage animation finishes
        currentUsageElement.addEventListener('electricityUsageUpdated', updateGoalProgressChart);
    } else { console.warn("[setupGoalProgressListener] Current usage element not found."); }
 }

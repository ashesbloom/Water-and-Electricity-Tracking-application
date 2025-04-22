// Store chart instances globally
let weeklyChartInstance; 
let goalProgressChartInstance; 
let distributionBarChartInstance; 
let historicalWaterChartInstance;

// Store references to DOM elements needed for updates
let currentUsageElement;
let goalInputElement;
let goalProgressTextElement;
let currentWaterUsageValueElement;
let waterLevelContainerElement;
let waterGoalInputElement;
let waterGoalProgressBarInnerElement; 

// Store data needed across functions
// !!! IMPORTANT: Replace simulated data with actual data fetching !!!
const electricityDistributionData = [45, 25, 15, 10, 5]; 
const electricityDistributionLabels = ['Appliances', 'Lighting', 'Heating/Cooling', 'Electronics', 'Other']; 
const simulatedCurrentWaterUsage = 185; // Litres
const simulatedMaxDailyWater = 400; // Litres (used as the 100% mark for the visual)


document.addEventListener('DOMContentLoaded', () => {
    // --- Get DOM Element References ---
    currentUsageElement = document.getElementById('currentUsage');
    goalInputElement = document.getElementById('dailyGoalInput');
    goalProgressTextElement = document.getElementById('goalProgressText'); 
    currentWaterUsageValueElement = document.getElementById('currentWaterUsageValue');
    waterLevelContainerElement = document.getElementById('waterLevelContainer'); 
    waterGoalProgressBarInnerElement = document.getElementById('waterGoalProgressBarInner');
    waterGoalInputElement = document.getElementById('dailyWaterGoalInput');

    initializeCharts();

    // Initialize Usage Animations
    const initialElectricityUsage = parseFloat(currentUsageElement?.textContent || '0');
    initializeUsageAnimation(initialElectricityUsage || 120, 'electricity'); 
    initializeUsageAnimation(simulatedCurrentWaterUsage, 'water'); // Use simulated data

    setupTimeframeButtons();

    // Trigger initial state for the 'Today' button 
    const todayButton = document.querySelector('.usage-overview-buttons button[data-timeframe="today"]');
    if (todayButton) {
        // Programmatically click after a short delay to ensure chart instance is ready
        setTimeout(() => {
             if (weeklyChartInstance) { // Check if chart is initialized
                 todayButton.click();
             } else {
                 console.warn("Main chart instance not ready for initial 'Today' click.");
             }
        }, 100); // Adjust delay if needed
    } else {
        console.warn("Could not find the 'Today' button to set initial state.");
    }

    setupGoalProgressListener();

    // Initial Water Level Update (start at 0 before animation)
    updateWaterFillWidth(0); 
    
}); // --- End DOMContentLoaded ---


function initializeUsageAnimation(targetUsageValue, type) {
    let usageElement = (type === 'electricity') ? currentUsageElement : currentWaterUsageValueElement;
    let updateFunction = (type === 'electricity') ? null : updateWaterFillWidth; 

    if (!usageElement) {
         console.warn(`Element not found for ${type} animation's numerical display.`);
         if (updateFunction) {
             updateFunction(parseFloat(targetUsageValue) || 0);
         }
         if (type === 'water') {
             updateWaterGoalProgress(); // Try to update progress bar anyway
         }
         return; 
    }

    let usageCounter = 0;
    const targetValue = parseFloat(targetUsageValue) || 0; 

    if (isNaN(targetValue)) {
        console.error(`Invalid targetUsageValue for ${type} animation:`, targetUsageValue);
        usageElement.textContent = 'N/A'; 
        if (updateFunction) {
            updateFunction(0); 
        }
        if (type === 'water') {
            updateWaterGoalProgress(); // Reset progress bar on error
        }
        return; 
    }

    // Start displays at 0
    usageElement.textContent = '0';
    if (updateFunction) {
        updateFunction(0); 
    }
    if (type === 'water') {
         updateWaterGoalProgress(); // Update progress bar based on initial 0 usage
    }

    const step = Math.max(1, Math.ceil(targetValue / 50)); 
    const intervalTime = 25; 

    const usageInterval = setInterval(() => {
        usageCounter += step;
        let displayValue = usageCounter;

        if (usageCounter >= targetValue) {
            displayValue = targetValue; 
            clearInterval(usageInterval); 
            // Dispatch custom event when animation finishes
            usageElement.dispatchEvent(new Event(`${type}UsageUpdated`, { bubbles: true }));
        }

        usageElement.textContent = displayValue;
        if (updateFunction) {
            updateFunction(displayValue);
        }

        // Update water progress bar continuously during animation
        if (type === 'water') {
             updateWaterGoalProgress();
        }

    }, intervalTime); 
}


function initializeCharts() {
    const initialTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    const isDark = initialTheme === 'dark';

    // --- Initialize Main Usage Overview Chart (Line/Bar) ---
    const weeklyUsageCtx = document.getElementById('weeklyUsageChart')?.getContext('2d');
    if (weeklyUsageCtx) {
        const mainChartOptions = getChartOptions(initialTheme, 'line'); 
        weeklyChartInstance = new Chart(weeklyUsageCtx, {
            type: 'line', // Default, button click will change type
            data: {
                labels: [], // Populated by button click
                datasets: [{
                    label: 'Usage (kWh)',
                    data: [], // Populated by button click
                    backgroundColor: getThemeColors(initialTheme).accentBgColorLine,
                    borderColor: getThemeColors(initialTheme).accentBorderColor,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                 }]
            },
            options: mainChartOptions
        });
        updateDatasetStyles(weeklyChartInstance, 'line', initialTheme);
    } else {
        console.error("Canvas context with ID 'weeklyUsageChart' NOT FOUND.");
    }

    // --- Initialize Goal Progress Doughnut Chart ---
    const goalProgressCtx = document.getElementById('goalProgressChart')?.getContext('2d');
    if (goalProgressCtx) {
        const goalChartOptions = getChartOptions(initialTheme, 'doughnut', 'goalProgress'); // Disable tooltips

        goalProgressChartInstance = new Chart(goalProgressCtx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Remaining'],
                datasets: [{
                    label: 'Goal Progress',
                    data: [0, 100], // Initial state
                    backgroundColor: [ 
                        getThemeColors(initialTheme).goalUsedColor,
                        getThemeColors(initialTheme).goalRemainingColor
                    ],
                    borderColor: getThemeColors(initialTheme).pieBorderColor, 
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: goalChartOptions
        });
    } else {
        console.error("Canvas context with ID 'goalProgressChart' NOT FOUND.");
    }

    // --- Initialize Distribution Bar Chart ---
    const distributionBarCtx = document.getElementById('distributionBarChart')?.getContext('2d');
    if (distributionBarCtx) {
        const distributionOptions = getChartOptions(initialTheme, 'bar');
        const colors = getThemeColors(initialTheme); 
        const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', colors.isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)', colors.isDark ? 'rgba(74, 222, 128, 0.7)' : 'rgba(34, 197, 94, 0.7)', colors.isDark ? 'rgba(167, 139, 250, 0.7)' : 'rgba(139, 92, 246, 0.7)', colors.isDark ? 'rgba(248, 113, 113, 0.7)' : 'rgba(239, 68, 68, 0.7)' ];
        const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', colors.isDark ? 'rgba(96, 165, 250, 0.9)' : 'rgba(59, 130, 246, 0.9)', colors.isDark ? 'rgba(74, 222, 128, 0.9)' : 'rgba(34, 197, 94, 0.9)', colors.isDark ? 'rgba(167, 139, 250, 0.9)' : 'rgba(139, 92, 246, 0.9)', colors.isDark ? 'rgba(248, 113, 113, 0.9)' : 'rgba(239, 68, 68, 0.9)' ];

        distributionBarChartInstance = new Chart(distributionBarCtx, {
            type: 'bar',
            data: {
                labels: electricityDistributionLabels, 
                datasets: [{
                    label: 'Usage Distribution', 
                    data: electricityDistributionData, // Use simulated data
                    backgroundColor: distributionBarColors, 
                    hoverBackgroundColor: distributionHoverColors, 
                    borderColor: colors.barBorderColor, 
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: distributionOptions
        });
    } else {
        console.error("Canvas context with ID 'distributionBarChart' NOT FOUND.");
    }

    // --- Initialize Historical Water Usage Chart ---
    const historicalWaterCtx = document.getElementById('historicalWaterChart')?.getContext('2d');
    if (historicalWaterCtx) {
        const historicalWaterOptions = getChartOptions(initialTheme, 'line'); 
        // !!! IMPORTANT: Replace this sample data with actual fetched historical data !!!
        const historicalWaterLabels = ['6 days ago', '5 days ago', '4 days ago', '3 days ago', 'Yesterday', 'Today (-1)', 'Today']; 
        const historicalWaterData = [190, 205, 180, 195, 210, 200, 185]; // Example usage in Litres

        historicalWaterChartInstance = new Chart(historicalWaterCtx, {
            type: 'line',
            data: {
                labels: historicalWaterLabels,
                datasets: [{
                    label: 'Water Usage (Litres)',
                    data: historicalWaterData,
                    fill: true, 
                    tension: 0.4, 
                    backgroundColor: getThemeColors(initialTheme).accentBgColorLine,
                    borderColor: getThemeColors(initialTheme).accentBorderColor,
                    borderWidth: 2,
                    pointRadius: 2, 
                    pointHoverRadius: 5
                }]
            },
            options: historicalWaterOptions 
        });
        updateDatasetStyles(historicalWaterChartInstance, 'line', initialTheme);
    } else {
        console.error("Canvas context with ID 'historicalWaterChart' NOT FOUND.");
    }
}

function updateWaterGoalProgress() {
    if (!currentWaterUsageValueElement || !waterGoalInputElement || !waterGoalProgressBarInnerElement) {
        // Warning is acceptable if elements aren't present on a page using this script
        // console.warn("Missing elements for water progress update."); 
        return;
    }

    const rawUsageText = currentWaterUsageValueElement.textContent;
    const rawGoalValue = waterGoalInputElement.value;
    const currentUsage = parseFloat(rawUsageText) || 0;
    const goal = parseFloat(rawGoalValue) || 0;
    let percentage = 0;

    if (goal > 0 && currentUsage >= 0) {
        percentage = Math.min((currentUsage / goal) * 100, 100); // Cap at 100%
    } else {
        percentage = 0; // Default to 0 if goal is 0 or invalid
    }
    percentage = Math.max(0, percentage); // Ensure not negative

    waterGoalProgressBarInnerElement.style.width = `${percentage}%`;
}


function getThemeColors(theme) {
    const isDark = theme === 'dark';
    return {
        isDark: isDark,
        gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
        tickColor: isDark ? '#cbd5e1' : '#4B5567',
        legendColor: isDark ? '#cbd5e1' : '#1F2937',
        tooltipBg: isDark ? 'rgba(0, 0, 0, 0.7)' : 'rgba(255, 255, 255, 0.8)',
        tooltipTitle: isDark ? '#ffffff' : '#000000',
        tooltipBody: isDark ? '#dddddd' : '#333333',
        accentBorderColor: isDark ? '#ecc931' : '#2563EB', 
        accentBgColorLine: isDark ? 'rgba(236, 201, 49, 0.2)' : 'rgba(37, 99, 235, 0.2)', 
        accentBgColorBar: isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(37, 99, 235, 0.7)', 
        barBorderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0,0,0,0.1)',
        goalUsedColor: isDark ? 'rgba(245, 158, 11, 0.9)' : 'rgba(59, 130, 246, 0.9)', 
        goalRemainingColor: isDark ? 'rgba(55, 65, 81, 0.6)' : 'rgba(229, 231, 235, 0.8)', 
        goalExceededColor: isDark ? 'rgba(239, 68, 68, 0.9)' : 'rgba(220, 38, 38, 0.9)', 
        pieBorderColor: isDark ? '#111827' : '#F3F4F6' 
    };
}


function getChartOptions(theme, type = 'line', chartPurpose = null) { 
    const colors = getThemeColors(theme);
    let options = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 1500 }, // Increased duration
        plugins: {
            legend: {
                display: type !== 'pie' && type !== 'doughnut', 
                position: 'top',
                labels: { color: colors.legendColor, boxWidth: 12, padding: 10 }
            },
            tooltip: {
                enabled: true, 
                backgroundColor: colors.tooltipBg,
                titleColor: colors.tooltipTitle,
                bodyColor: colors.tooltipBody,
                padding: 8,
                boxPadding: 4
            }
        }
    };
    
    if (type !== 'pie' && type !== 'doughnut') {
        options.scales = {
            y: { beginAtZero: true, ticks: { color: colors.tickColor, padding: 5 }, grid: { color: colors.gridColor, drawBorder: false } },
            x: { ticks: { color: colors.tickColor, padding: 5 }, grid: { color: colors.gridColor, display: type === 'bar' ? false : true } } 
        };
    }
    
    if (type === 'doughnut') {
         options.cutout = '70%'; 
         if (chartPurpose === 'goalProgress') {
             options.plugins.tooltip.enabled = false; // Disable tooltips for goal chart
         } else {
             // Example tooltip callback for other potential doughnut charts
             options.plugins.tooltip.callbacks = {
                 label: function(context) {
                     let label = context.label || '';
                     if (label) { label += ': '; }
                     if (context.parsed !== null) { label += context.parsed + '%'; } 
                     return label;
                 }
             };
         }
    }
    return options;
}


function updateChartColors(theme) {
    const colors = getThemeColors(theme);

    // Update Main Chart (weeklyChartInstance)
    if (weeklyChartInstance) {
        const options = weeklyChartInstance.options;
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colors.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colors.tickColor;
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        if(options.plugins?.tooltip) {
            options.plugins.tooltip.backgroundColor = colors.tooltipBg;
            options.plugins.tooltip.titleColor = colors.tooltipTitle;
            options.plugins.tooltip.bodyColor = colors.tooltipBody;
        }
        updateDatasetStyles(weeklyChartInstance, weeklyChartInstance.config.type, theme);
        weeklyChartInstance.update('none'); 
    }

    // Update Goal Progress Chart Colors
    if (goalProgressChartInstance) {
        const dataset = goalProgressChartInstance.data.datasets[0];
        const currentUsage = parseFloat(currentUsageElement?.textContent || '0');
        const goal = parseFloat(goalInputElement?.value || '0');
        const percentage = (goal > 0) ? Math.round((currentUsage / goal) * 100) : 0;
        const usedColor = (percentage > 100 && goal > 0) ? colors.goalExceededColor : colors.goalUsedColor;

        dataset.backgroundColor = [usedColor, colors.goalRemainingColor];
        dataset.borderColor = colors.pieBorderColor; 
        goalProgressChartInstance.update('none'); 
    } else {
        // console.log("Goal progress chart instance not found for color update."); // Keep commented
    }

    // Update Distribution Bar Chart Colors
    if (distributionBarChartInstance) {
        const options = distributionBarChartInstance.options;
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colors.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colors.tickColor;
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        if(options.plugins?.tooltip) {
            options.plugins.tooltip.backgroundColor = colors.tooltipBg;
            options.plugins.tooltip.titleColor = colors.tooltipTitle;
            options.plugins.tooltip.bodyColor = colors.tooltipBody;
        }
        const dataset = distributionBarChartInstance.data.datasets[0];
        const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', colors.isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)', colors.isDark ? 'rgba(74, 222, 128, 0.7)' : 'rgba(34, 197, 94, 0.7)', colors.isDark ? 'rgba(167, 139, 250, 0.7)' : 'rgba(139, 92, 246, 0.7)', colors.isDark ? 'rgba(248, 113, 113, 0.7)' : 'rgba(239, 68, 68, 0.7)' ];
        const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', colors.isDark ? 'rgba(96, 165, 250, 0.9)' : 'rgba(59, 130, 246, 0.9)', colors.isDark ? 'rgba(74, 222, 128, 0.9)' : 'rgba(34, 197, 94, 0.9)', colors.isDark ? 'rgba(167, 139, 250, 0.9)' : 'rgba(139, 92, 246, 0.9)', colors.isDark ? 'rgba(248, 113, 113, 0.9)' : 'rgba(239, 68, 68, 0.9)' ];
        dataset.backgroundColor = distributionBarColors;
        dataset.hoverBackgroundColor = distributionHoverColors;
        dataset.borderColor = colors.barBorderColor;
        distributionBarChartInstance.update('none'); 
    } else {
         // console.log("Distribution bar chart instance not found for color update."); // Keep commented
    }

    // Update Historical Water Chart Colors
    if (historicalWaterChartInstance) {
        const options = historicalWaterChartInstance.options;
        if(options.scales?.x?.grid) options.scales.x.grid.color = colors.gridColor;
        if(options.scales?.y?.grid) options.scales.y.grid.color = colors.gridColor;
        if(options.scales?.x?.ticks) options.scales.x.ticks.color = colors.tickColor;
        if(options.scales?.y?.ticks) options.scales.y.ticks.color = colors.tickColor;
        if(options.plugins?.legend?.labels) options.plugins.legend.labels.color = colors.legendColor;
        if(options.plugins?.tooltip) {
            options.plugins.tooltip.backgroundColor = colors.tooltipBg;
            options.plugins.tooltip.titleColor = colors.tooltipTitle;
            options.plugins.tooltip.bodyColor = colors.tooltipBody;
        }
        updateDatasetStyles(historicalWaterChartInstance, 'line', theme); 
        historicalWaterChartInstance.update('none'); 
    } else {
         // console.log("Historical water chart instance not found for color update."); // Keep commented
    }
}


function updateDatasetStyles(chartInstance, type, theme) {
    if (!chartInstance || !chartInstance.data.datasets[0]) return;
    const colors = getThemeColors(theme);
    const dataset = chartInstance.data.datasets[0];

    if (type === 'line') {
        dataset.backgroundColor = colors.accentBgColorLine;
        dataset.borderColor = colors.accentBorderColor;
        dataset.tension = 0.4;
        dataset.fill = true;
        dataset.pointRadius = 0; // Hide points by default on main chart line
        dataset.pointHoverRadius = 5;
        dataset.pointBackgroundColor = colors.accentBorderColor;
        if (chartInstance.options.scales?.x?.grid) chartInstance.options.scales.x.grid.display = true;
        // Specific override for historical water chart points
        if (chartInstance.canvas.id === 'historicalWaterChart') {
             dataset.pointRadius = 2; // Show small points
        }

    } else { // 'bar'
        dataset.backgroundColor = colors.accentBgColorBar; 
        dataset.borderColor = colors.barBorderColor;
        dataset.tension = 0;
        dataset.fill = false;
        dataset.pointRadius = undefined; // Reset point styles for bar
        dataset.pointHoverRadius = undefined;
        dataset.pointBackgroundColor = undefined;
        if (chartInstance.options.scales?.x?.grid) chartInstance.options.scales.x.grid.display = false;
        // Apply multi-color logic only to distribution chart
        if (chartInstance.canvas.id === 'distributionBarChart') {
             const distributionBarColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.7)' : 'rgba(245, 158, 11, 0.7)', colors.isDark ? 'rgba(96, 165, 250, 0.7)' : 'rgba(59, 130, 246, 0.7)', colors.isDark ? 'rgba(74, 222, 128, 0.7)' : 'rgba(34, 197, 94, 0.7)', colors.isDark ? 'rgba(167, 139, 250, 0.7)' : 'rgba(139, 92, 246, 0.7)', colors.isDark ? 'rgba(248, 113, 113, 0.7)' : 'rgba(239, 68, 68, 0.7)' ];
             const distributionHoverColors = [ colors.isDark ? 'rgba(236, 201, 49, 0.9)' : 'rgba(245, 158, 11, 0.9)', colors.isDark ? 'rgba(96, 165, 250, 0.9)' : 'rgba(59, 130, 246, 0.9)', colors.isDark ? 'rgba(74, 222, 128, 0.9)' : 'rgba(34, 197, 94, 0.9)', colors.isDark ? 'rgba(167, 139, 250, 0.9)' : 'rgba(139, 92, 246, 0.9)', colors.isDark ? 'rgba(248, 113, 113, 0.9)' : 'rgba(239, 68, 68, 0.9)' ];
             dataset.backgroundColor = distributionBarColors;
             dataset.hoverBackgroundColor = distributionHoverColors;
        }
    }
 }


window.addEventListener('themeUpdated', (event) => {
    if (event.detail && event.detail.theme) {
        updateChartColors(event.detail.theme);
    }
});


function setupTimeframeButtons() {
    const buttons = document.querySelectorAll('.usage-overview-buttons button');
    const chartTitleElement = document.getElementById('dailyUsageChartTitle') || document.getElementById('waterUsageChartTitle'); // Support both page titles
    
    // !!! IMPORTANT: Replace simulated data with actual fetched data based on timeframe and page context (electricity vs water) !!!
    const electricityDataSets = {
        today: { type: 'line', title: 'Hourly Usage (Today)', labels: Array.from({ length: 24 }, (_, i) => `${i}:00`), data: [2, 1, 1, 1, 2, 3, 5, 7, 8, 7, 6, 5, 5, 6, 7, 9, 11, 13, 12, 10, 8, 6, 4, 3], unit: 'kWh' },
        weekly: { type: 'bar', title: 'Daily Usage (Last 7 Days)', labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [80, 90, 85, 70, 95, 100, 110], unit: 'kWh' },
        monthly: { type: 'bar', title: 'Weekly Usage (Last 4 Weeks)', labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], data: [400, 450, 420, 480], unit: 'kWh' }
    };
    const waterDataSets = {
         today: { type: 'line', title: 'Hourly Water Usage (Today)', labels: Array.from({ length: 24 }, (_, i) => `${i}:00`), data: [10, 8, 8, 9, 12, 15, 25, 30, 35, 28, 22, 20, 18, 24, 26, 30, 33, 38, 35, 28, 20, 15, 12, 10], unit: 'Litres' },
         weekly: { type: 'bar', title: 'Daily Water Usage (Last 7 Days)', labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [190, 205, 180, 195, 210, 200, 185], unit: 'Litres' },
         monthly: { type: 'bar', title: 'Weekly Water Usage (Last 4 Weeks)', labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], data: [1300, 1450, 1350, 1500], unit: 'Litres' }
    };

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const timeframe = button.dataset.timeframe;
            
            // Determine if we are on the water or electricity details page (simple check)
            // A more robust method might involve a variable passed from PHP or a data attribute on the body/chart
            const isWaterPage = !!document.getElementById('currentWaterUsageValue'); 
            const dataSets = isWaterPage ? waterDataSets : electricityDataSets;
            const selectedData = dataSets[timeframe];

            if (!weeklyChartInstance || !selectedData) {
                console.warn(`Chart instance or data for timeframe '${timeframe}' on ${isWaterPage ? 'water' : 'electricity'} page not found.`);
                return;
            }

            // Button Styling
            buttons.forEach(btn => { btn.classList.remove('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900'); btn.classList.add('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200'); });
            button.classList.remove('bg-gray-200', 'text-light-text-secondary', 'dark:bg-gray-700', 'dark:text-gray-200'); button.classList.add('bg-light-accent', 'text-white', 'dark:bg-gold-accent', 'dark:text-gray-900');

            // Update Main Chart
            if (chartTitleElement) { chartTitleElement.textContent = selectedData.title; }

            const needsTypeChange = weeklyChartInstance.config.type !== selectedData.type;
            if (needsTypeChange) {
                 weeklyChartInstance.config.type = selectedData.type;
                 const currentTheme = localStorage.getItem('theme') || 'light';
                 weeklyChartInstance.options = getChartOptions(currentTheme, selectedData.type);
            }

            weeklyChartInstance.data.labels = selectedData.labels;
            weeklyChartInstance.data.datasets[0].data = selectedData.data;
            weeklyChartInstance.data.datasets[0].label = `Usage (${selectedData.unit})`; // Update label with unit

            const currentTheme = localStorage.getItem('theme') || 'light';
            updateDatasetStyles(weeklyChartInstance, selectedData.type, currentTheme);

            weeklyChartInstance.update(); 
        });
    });
}


function updateGoalProgressChart() {
    if (!goalProgressChartInstance || !currentUsageElement || !goalInputElement || !goalProgressTextElement) {
        // console.log("Goal progress chart or related elements not ready for update."); // Keep commented
        return;
    }

    const currentUsage = parseFloat(currentUsageElement.textContent) || 0;
    const goal = parseFloat(goalInputElement.value) || 0; 
    let percentage = 0;

    if (goal > 0) {
        percentage = Math.round((currentUsage / goal) * 100);
    } else {
        percentage = 0; 
    }

    goalProgressTextElement.textContent = (goal > 0) ? `${percentage}%` : `N/A`;

    const displayPercentage = (goal > 0) ? Math.min(percentage, 100) : 0;
    const remainingPercentage = Math.max(0, 100 - displayPercentage);
    goalProgressChartInstance.data.datasets[0].data = [displayPercentage, remainingPercentage];

    const currentTheme = localStorage.getItem('theme') || 'light';
    const colors = getThemeColors(currentTheme);
    const usedColor = (percentage > 100 && goal > 0) ? colors.goalExceededColor : colors.goalUsedColor;
    goalProgressChartInstance.data.datasets[0].backgroundColor = [usedColor, colors.goalRemainingColor];
    goalProgressChartInstance.data.datasets[0].borderColor = colors.pieBorderColor;

    goalProgressChartInstance.update(); 
}


function setupGoalProgressListener() {
    // Listen for changes in the goal input 
    if (goalInputElement) {
        goalInputElement.addEventListener('goalUpdated', () => {
            updateGoalProgressChart();
        });
        // Update once when the goal is initially loaded
        goalInputElement.addEventListener('goalLoaded', () => {
             updateGoalProgressChart();
        });
    } else {
        console.warn("Goal input element not found for listener setup.");
    }

    // Listen for changes in the current electricity usage value (animation finished)
    if (currentUsageElement) {
        currentUsageElement.addEventListener('electricityUsageUpdated', () => { 
            updateGoalProgressChart();
        });
    } else {
        console.warn("Current usage element not found for listener setup.");
    }
 }


function updateWaterFillWidth(currentWaterUsage) { 
    if (!waterLevelContainerElement) { 
        // console.log("Water level container element not found for update."); // Keep commented
        return;
    }
    const maxUsage = simulatedMaxDailyWater; // Use simulated max for now
    let percentage = 0;
    if (maxUsage > 0 && currentWaterUsage >= 0) {
        percentage = Math.min((currentWaterUsage / maxUsage) * 100, 100);
    }
    waterLevelContainerElement.style.width = `${percentage}%`;
}



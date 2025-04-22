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
                        'light-profile': 'rgba(255, 255, 255)',
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
        <div class="text-center mb-4 mt-12"> <h1 class="text-2xl font-bold text-light-text-primary dark:text-dark-text-primary">India: Average Electricity Tariff by State</h1>
             <h2 class="text-lg font-semibold mt-1 text-light-text-secondary dark:text-dark-text-secondary">Average Tariff (INR/kWh)</h2>
        </div>
        <section id="map-section-container" class="scroll-section content-box scroll-animate scroll-animate-init p-6 md:p-8">
            <div id="india-tariff-map-container">
                <div id="india-tariff-map">
                    <svg width="800" height="700"></svg> </div>
                    <div id="tooltip">Tooltip</div> <div id="map-legend"> </div>
                </div>
                <p class="text-xs text-light-text-secondary dark:text-dark-text-secondary italic">
                    Source: Representative data. Hover over states for details.
                </p>
            </section>
    </div>

    <div id="hover-indicator"></div>
    <div id="location-popup"></div>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Check if D3 is loaded
            if (typeof d3 === 'undefined') {
                console.error("D3 library not loaded.");
                return; // Exit if D3 is missing
            }

            // --- Tariff Data ---
            const tariffData = [
                { state: "Andhra Pradesh", average_tariff_inr_per_kwh: 7.30 },
                { state: "Arunachal Pradesh", average_tariff_inr_per_kwh: 4.50 },
                { state: "Assam", average_tariff_inr_per_kwh: 6.75 },
                { state: "Bihar", average_tariff_inr_per_kwh: 6.15 },
                { state: "Chhattisgarh", average_tariff_inr_per_kwh: 5.90 },
                { state: "Goa", average_tariff_inr_per_kwh: 4.10 },
                { state: "Gujarat", average_tariff_inr_per_kwh: 6.25 },
                { state: "Haryana", average_tariff_inr_per_kwh: 6.50 },
                { state: "Himachal Pradesh", average_tariff_inr_per_kwh: 4.75 },
                { state: "Jharkhand", average_tariff_inr_per_kwh: 6.00 },
                { state: "Jammu and Kashmir", average_tariff_inr_per_kwh: 5.50 }, // <-- ADDED ESTIMATE
                { state: "Karnataka", average_tariff_inr_per_kwh: 7.00 },
                { state: "Kerala", average_tariff_inr_per_kwh: 7.50 },
                { state: "Madhya Pradesh", average_tariff_inr_per_kwh: 6.80 },
                { state: "Maharashtra", average_tariff_inr_per_kwh: 8.80 },
                { state: "Manipur", average_tariff_inr_per_kwh: 5.00 },
                { state: "Meghalaya", average_tariff_inr_per_kwh: 5.20 },
                { state: "Mizoram", average_tariff_inr_per_kwh: 5.10 },
                { state: "Delhi", average_tariff_inr_per_kwh: 7.00 }, 
                { state: "Nagaland", average_tariff_inr_per_kwh: 4.80 },
                { state: "Orissa", average_tariff_inr_per_kwh: 5.85 },
                { state: "Punjab", average_tariff_inr_per_kwh: 5.60 },
                { state: "Rajasthan", average_tariff_inr_per_kwh: 6.40 },
                { state: "Sikkim", average_tariff_inr_per_kwh: 4.90 },
                { state: "Tamil Nadu", average_tariff_inr_per_kwh: 6.75 },
                { state: "Telangana", average_tariff_inr_per_kwh: 7.10 },
                { state: "Tripura", average_tariff_inr_per_kwh: 5.00 },
                { state: "Uttar Pradesh", average_tariff_inr_per_kwh: 6.90 },
                { state: "Uttaranchal", average_tariff_inr_per_kwh: 5.30 },
                { state: "West Bengal", average_tariff_inr_per_kwh: 8.00 },
            ];
            const tariffDataMap = new Map(tariffData.map(d => [d.state, d.average_tariff_inr_per_kwh]));

            // --- D3 Map Setup ---
            const mapContainer = d3.select("#india-tariff-map");
            const svg = mapContainer.select("svg");
            const tooltip = d3.select("#tooltip");
            const legendContainer = d3.select("#map-legend");

            if (mapContainer.empty() || svg.empty() || tooltip.empty() || legendContainer.empty()) {
                 console.error("Map container, SVG, Tooltip, or Legend element not found. Map script exiting.");
                 return;
            }

            const containerWidth = mapContainer.node()?.getBoundingClientRect().width ?? 600;
            const width = containerWidth > 0 ? containerWidth : 600;
            const height = width * 0.9;
            svg.attr("viewBox", `0 0 ${width} ${height}`)
               .attr("preserveAspectRatio", "xMidYMid meet")
               .attr("width", "100%")
               .attr("height", height);

            // Define color scales
            const lightColors = ["#ccebc5", "#a8ddb5", "#7bccc4", "#4eb3d3", "#2b8cbe", "#0868ac", "#084081"];
            const darkColors = ["#ffffd4", "#fee391", "#fec44f", "#fe9929", "#d95f0e", "#993404"];

            let isDarkMode = document.documentElement.classList.contains('dark');
            let currentColors = isDarkMode ? darkColors : lightColors;

            const colorScale = d3.scaleQuantize().domain([4, 9]).range(currentColors).unknown("#ccc");
            const projection = d3.geoMercator().center([82, 22]).scale(width * 1.25).translate([width / 2, height / 2]);
            const path = d3.geoPath().projection(projection);
            const mapUrl = "https://raw.githubusercontent.com/geohacker/india/master/state/india_state.geojson";
            let loadedIndiaData = null;

            // Draw/Update Map function
            function drawMap(indiaData) {
                 if (!indiaData) return;
                 const states = indiaData.features;
                 if (!states || states.length === 0) { console.error("No state features found."); return; }
                 svg.selectAll("g").remove();
                 svg.append("g")
                    .selectAll(".state")
                    .data(states)
                    .join("path")
                    .attr("class", "state")
                    .attr("d", path)
                    .attr("fill", d => colorScale(tariffDataMap.get(d.properties.NAME_1)))
                    .on("mouseover", function(event, d) {
                        d3.select(this).raise();
                        const stateName = d.properties.NAME_1;
                        const tariff = tariffDataMap.get(stateName);
                        const tariffText = tariff ? `₹${tariff.toFixed(2)} / kWh` : "No data";
                        tooltip.html(`<strong>${stateName || 'Unknown'}</strong><br>${tariffText}`)
                               .classed('visible', true);
                     })
                    .on("mousemove", function(event) {
                        tooltip.style("left", (event.pageX + 15) + "px")
                               .style("top", (event.pageY - 30) + "px");
                     })
                    .on("mouseout", function() {
                        tooltip.classed('visible', false);
                     });
            }

             // Draw/Update Legend function
             function drawLegend() {
                 if (legendContainer.empty()) return;
                 legendContainer.html('');
                 const legendTitle = legendContainer.append("span").style("font-weight", "bold").style("margin-right", "10px").text("Tariff (₹/kWh):");
                 const thresholds = colorScale.thresholds();
                 const legendData = [colorScale.domain()[0], ...thresholds, colorScale.domain()[1]];
                 colorScale.range().forEach((color, i) => {
                    const lowerBound = legendData[i];
                    const upperBound = legendData[i + 1];
                    const rangeText = upperBound ? `${lowerBound.toFixed(1)}-${upperBound.toFixed(1)}` : `>${lowerBound.toFixed(1)}`;
                    const legendItem = legendContainer.append("span").attr("class", "legend-item");
                    legendItem.append("span").attr("class", "legend-color-box").style("background-color", color);
                    legendItem.append("span").text(rangeText);
                 });
                 const legendUnknown = legendContainer.append("span").attr("class", "legend-item");
                 legendUnknown.append("span").attr("class", "legend-color-box").style("background-color", colorScale.unknown());
                 legendUnknown.append("span").text("No Data");
             }

            // Load data and draw initial map/legend
            d3.json(mapUrl).then(indiaData => {
                console.log("Map data loaded successfully.");
                loadedIndiaData = indiaData;
                drawMap(loadedIndiaData);
                drawLegend();

                 // Listen for theme changes AFTER map is drawn
                 window.addEventListener('themeUpdated', (e) => {
                    console.log("Theme updated event received for map:", e.detail.theme);
                    isDarkMode = e.detail.theme === 'dark';
                    currentColors = isDarkMode ? darkColors : lightColors;
                    colorScale.range(currentColors);
                    drawMap(loadedIndiaData); // Redraw map
                    drawLegend(); // Redraw legend
                });

            }).catch(error => {
                console.error("Error loading or processing map data:", error);
                mapContainer.html("<p style='color: red; text-align: center;'>Could not load map data. Check console and network tab.</p>");
            });

        }); // End DOMContentLoaded
    </script> 

</body>

</html>
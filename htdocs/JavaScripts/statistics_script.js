document.addEventListener('DOMContentLoaded', () => {

    // --- Three.js Globe Variables & Setup ---
    let scene, camera, renderer, globe, controls, stars;
    let raycaster, mouse;
    let isGlobeRotating = true;
    let currentHoverCoords = null;
    let selectedLocationData = null;
    let selectedPoint3D = null;
    const globeContainer = document.getElementById('canvas-container');
    const hoverIndicator = document.getElementById('hover-indicator');
    const infoDisplayElement = document.getElementById('info-display');
    const locationPopup = document.getElementById('location-popup');
    const globeLoadingIndicator = globeContainer?.querySelector('div');
    // --- Throttling variables ---
    let mouseMoveTimeout = null;
    const MOUSE_MOVE_THROTTLE_DELAY = 100; // ms - Run mousemove logic max every 100ms

    // --- Geographic Data (for Globe) ---
    const continentData = {
        "continents": [
             { "type": "continent", "name": "Asia", "electricity_usage_gwh": 25000000, "water_usage_billion_cubic_meters": 3600, "bounds": { "minLon": 25, "maxLon": 180, "minLat": -11, "maxLat": 82 } },
            { "type": "continent", "name": "Europe", "electricity_usage_gwh": 8000000, "water_usage_billion_cubic_meters": 1100, "bounds": { "minLon": -25, "maxLon": 65, "minLat": 35, "maxLat": 82 } },
            { "type": "continent", "name": "Africa", "electricity_usage_gwh": 1000000, "water_usage_billion_cubic_meters": 400, "bounds": { "minLon": -18, "maxLon": 52, "minLat": -35, "maxLat": 38 } },
            { "type": "continent", "name": "North America", "electricity_usage_gwh": 12000000, "water_usage_billion_cubic_meters": 1500, "bounds": { "minLon": -169, "maxLon": -50, "minLat": 7, "maxLat": 84 } },
            { "type": "continent", "name": "South America", "electricity_usage_gwh": 2500000, "water_usage_billion_cubic_meters": 900, "bounds": { "minLon": -82, "maxLon": -34, "minLat": -56, "maxLat": 13 } },
            { "type": "continent", "name": "Australia", "electricity_usage_gwh": 1000000, "water_usage_billion_cubic_meters": 250, "bounds": { "minLon": 112, "maxLon": 154, "minLat": -44, "maxLat": -10 } },
            { "type": "continent", "name": "Antarctica", "electricity_usage_gwh": 50, "water_usage_billion_cubic_meters": 0.01, "bounds": { "minLon": -180, "maxLon": 180, "minLat": -90, "maxLat": -60 } }
        ]
    };
    const countryData = {
        "countries": [
             { "type": "country", "name": "China", "electricity_usage_gwh": 8400000, "water_usage_billion_cubic_meters": 600, "bounds": { "minLon": 73, "maxLon": 135, "minLat": 18, "maxLat": 54 } },
             { "type": "country", "name": "India", "electricity_usage_gwh": 1600000, "water_usage_billion_cubic_meters": 760, "bounds": { "minLon": 68, "maxLon": 98, "minLat": 8, "maxLat": 37 } },
             { "type": "country", "name": "United States", "electricity_usage_gwh": 4100000, "water_usage_billion_cubic_meters": 480, "bounds": { "minLon": -125, "maxLon": -66, "minLat": 24, "maxLat": 49 } },
             { "type": "country", "name": "Russia", "electricity_usage_gwh": 1100000, "water_usage_billion_cubic_meters": 430, "bounds": { "minLon": 20, "maxLon": 180, "minLat": 41, "maxLat": 82 } },
             { "type": "country", "name": "Brazil", "electricity_usage_gwh": 650000, "water_usage_billion_cubic_meters": 420, "bounds": { "minLon": -74, "maxLon": -34, "minLat": -34, "maxLat": 6 } },
             { "type": "country", "name": "Indonesia", "electricity_usage_gwh": 300000, "water_usage_billion_cubic_meters": 290, "bounds": { "minLon": 95, "maxLon": 141, "minLat": -11, "maxLat": 6 } },
             { "type": "country", "name": "Japan", "electricity_usage_gwh": 950000, "water_usage_billion_cubic_meters": 100, "bounds": { "minLon": 128, "maxLon": 146, "minLat": 30, "maxLat": 46 } },
             { "type": "country", "name": "Germany", "electricity_usage_gwh": 550000, "water_usage_billion_cubic_meters": 90, "bounds": { "minLon": 5, "maxLon": 15, "minLat": 47, "maxLat": 55 } },
             { "type": "country", "name": "Australia", "electricity_usage_gwh": 250000, "water_usage_billion_cubic_meters": 100, "bounds": { "minLon": 112, "maxLon": 154, "minLat": -44, "maxLat": -10 } },
             { "type": "country", "name": "Canada", "electricity_usage_gwh": 600000, "water_usage_billion_cubic_meters": 120, "bounds": { "minLon": -141, "maxLon": -52, "minLat": 41, "maxLat": 83 } }
        ]
    };


    // --- D3 Map Variables & Setup ---
    const mapContainer = d3.select("#india-tariff-map");
    const mapSvg = mapContainer.select("svg");
    const mapTooltip = d3.select("#tooltip");
    const mapLegendContainer = d3.select("#map-legend");
    const mapMainTitle = document.getElementById('map-main-title');
    const mapSubtitle = document.getElementById('map-subtitle');
    const mapToggleButtons = document.querySelectorAll('#map-toggle-buttons button');
    const mapUrl = "https://raw.githubusercontent.com/geohacker/india/master/state/india_state.geojson";
    let loadedIndiaGeoData = null; // To store loaded GeoJSON
    let currentMapType = 'electricity'; // Track currently displayed map ('electricity' or 'water')
    let currentMapColorScheme = []; // Store current color scheme for theme updates
    let currentMapScale = null; // Store current D3 scale

    // --- Tariff Data ---
    // Electricity Tariff Data (₹/kWh)
    const electricityTariffData = new Map([
        ["Andhra Pradesh", 7.30], ["Arunachal Pradesh", 4.50], ["Assam", 6.75],
        ["Bihar", 6.15], ["Chhattisgarh", 5.90], ["Goa", 4.10], ["Gujarat", 6.25],
        ["Haryana", 6.50], ["Himachal Pradesh", 4.75], ["Jharkhand", 6.00],
        ["Jammu and Kashmir", 5.50], ["Karnataka", 7.00], ["Kerala", 7.50],
        ["Madhya Pradesh", 6.80], ["Maharashtra", 8.80], ["Manipur", 5.00],
        ["Meghalaya", 5.20], ["Mizoram", 5.10], ["Delhi", 7.00], ["Nagaland", 4.80],
        ["Orissa", 5.85], ["Punjab", 5.60], ["Rajasthan", 6.40], ["Sikkim", 4.90],
        ["Tamil Nadu", 6.75], ["Telangana", 7.10], ["Tripura", 5.00],
        ["Uttar Pradesh", 6.90], ["Uttaranchal", 5.30], ["West Bengal", 8.00],
        ["Andaman and Nicobar Islands", null], ["Chandigarh", null],
        ["Dadra and Nagar Haveli and Daman and Diu", null],
        ["Lakshadweep", null], ["Puducherry", null], ["Ladakh", null]
    ]);

    // Water Tariff Data (₹/kL - Kilolitre) - Processed from provided JSON
    const rawWaterData = {
        "Andhra Pradesh": { "value": 119, "disclaimer": "AI-generated estimate" },
        "Arunachal Pradesh": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Assam": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Bihar": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Chhattisgarh": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Goa": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Gujarat": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Haryana": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Himachal Pradesh": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Jharkhand": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Karnataka": { "Bengaluru": 117.65 }, // Use Bengaluru for Karnataka state
        "Kerala": 226.76,
        "Madhya Pradesh": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Maharashtra": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Manipur": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Meghalaya": 125,
        "Mizoram": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Nagaland": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Odisha": { "value": 188, "disclaimer": "AI-generated estimate" }, // GeoJSON uses Orissa
        "Punjab": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Rajasthan": 529.10,
        "Sikkim": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Tamil Nadu": { "rural": 70.56, "urban": 59.52 }, // Average these
        "Telangana": { "value": 119, "disclaimer": "AI-generated estimate" },
        "Tripura": { "value": 125, "disclaimer": "AI-generated estimate" },
        "Uttar Pradesh": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Uttarakhand": { "value": 359, "disclaimer": "AI-generated estimate" }, // GeoJSON uses Uttaranchal
        "West Bengal": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Andaman and Nicobar": { "value": 119, "disclaimer": "AI-generated estimate" }, // GeoJSON uses 'Andaman and Nicobar Islands'
        "Chandigarh": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Dadra and Nagar Haveli and Daman and Diu": { "value": 188, "disclaimer": "AI-generated estimate" },
        "Delhi": 189.75,
        "Jammu and Kashmir": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Ladakh": { "value": 359, "disclaimer": "AI-generated estimate" },
        "Lakshadweep": { "value": 119, "disclaimer": "AI-generated estimate" },
        "Puducherry": { "value": 119, "disclaimer": "AI-generated estimate" }
    };

    const waterTariffData = new Map();
    // Normalize water data into Map<string, { value: number | null, disclaimer?: string }>
    for (const key in rawWaterData) {
        const value = rawWaterData[key];
        let processedValue = null;
        let disclaimer = undefined;
        let mapKey = key; // Use original key by default

        if (typeof value === 'number') {
            processedValue = value;
        } else if (typeof value === 'object' && value !== null) {
            if (value.value !== undefined) { // Standard estimate format
                processedValue = value.value;
                disclaimer = value.disclaimer;
            } else if (key === 'Karnataka' && value.Bengaluru !== undefined) {
                processedValue = value.Bengaluru; // Use Bengaluru value for Karnataka state
                disclaimer = "Bengaluru avg. used";
            } else if (key === 'Tamil Nadu' && value.rural !== undefined && value.urban !== undefined) {
                processedValue = (value.rural + value.urban) / 2; // Average rural/urban
                disclaimer = "Rural/Urban avg.";
            }
        }

        // Handle key mismatches between data and GeoJSON
        if (key === "Odisha") mapKey = "Orissa";
        if (key === "Uttarakhand") mapKey = "Uttaranchal";
        if (key === "Andaman and Nicobar") mapKey = "Andaman and Nicobar Islands";

        waterTariffData.set(mapKey, { value: processedValue, disclaimer: disclaimer });
    }
    // Ensure all states from electricity map exist in water map (with null value if needed)
    electricityTariffData.forEach((_, key) => {
        if (!waterTariffData.has(key)) {
            waterTariffData.set(key, { value: null });
        }
    });


    // --- Three.js Globe Functions ---
    /**
     * Initializes the Three.js scene, camera, renderer, globe, stars, and controls.
     */
    function initGlobe() {
        if (!globeContainer) return; // Exit if container not found

        scene = new THREE.Scene();
        createStars(10000); // Add background stars

        // Setup camera
        camera = new THREE.PerspectiveCamera(75, globeContainer.clientWidth / globeContainer.clientHeight, 0.1, 1000);
        camera.position.z = 3.8; // Initial distance from globe

        // Setup renderer
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true }); // Alpha for transparent background
        renderer.setSize(globeContainer.clientWidth, globeContainer.clientHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.outputColorSpace = THREE.SRGBColorSpace; // Correct color space
        globeContainer.appendChild(renderer.domElement); // Add canvas to container

        // Globe geometry
        const geometry = new THREE.SphereGeometry(1.5, 64, 64); // Radius, width segments, height segments

        // Placeholder texture while loading
        let visibleTexture;
        const visibleCanvas = document.createElement('canvas');
        visibleCanvas.width = 256; visibleCanvas.height = 128;
        const visibleContext = visibleCanvas.getContext('2d');
        visibleContext.fillStyle = 'rgb(50, 50, 100)'; // Placeholder color
        visibleContext.fillRect(0, 0, visibleCanvas.width, visibleCanvas.height);
        visibleTexture = new THREE.CanvasTexture(visibleCanvas);
        visibleTexture.colorSpace = THREE.SRGBColorSpace;
        visibleTexture.generateMipmaps = false;
        visibleTexture.minFilter = THREE.LinearFilter;
        visibleTexture.magFilter = THREE.LinearFilter;
        visibleTexture.needsUpdate = true;

        // Load the actual globe texture
        const visibleImage = new Image();
        visibleImage.crossOrigin = 'Anonymous'; // Needed for cross-origin textures
        visibleImage.onload = () => {
            visibleCanvas.width = visibleImage.naturalWidth;
            visibleCanvas.height = visibleImage.naturalHeight;
            visibleContext.drawImage(visibleImage, 0, 0, visibleCanvas.width, visibleCanvas.height);
            visibleTexture.needsUpdate = true; // Update texture with loaded image
            if (globeLoadingIndicator) globeLoadingIndicator.style.display = 'none'; // Hide loading text
            // Clear potential error message
            if (infoDisplayElement && infoDisplayElement.textContent.includes("Error loading map image.")) {
                infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>";
            }
        };
        visibleImage.onerror = (err) => {
            console.error("Error loading visible map texture:", visibleImage.src, err);
            if (infoDisplayElement) infoDisplayElement.innerHTML = `<p style="color: red;">Error loading map image.</p>`;
             if (globeLoadingIndicator) globeLoadingIndicator.textContent = "Error loading map.";
        };
        // Consider hosting texture locally for reliability
        const visibleImageUrl = 'https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg';
        visibleImage.src = visibleImageUrl;

        // Globe material
        const material = new THREE.MeshStandardMaterial({
            map: visibleTexture,
            color: 0xffffff, // Base color tint
            roughness: 0.85, // How rough the surface looks
            metalness: 0.1, // How metallic the surface looks
        });
        globe = new THREE.Mesh(geometry, material);
        scene.add(globe);

        // Lighting setup
        const ambientLight = new THREE.AmbientLight(0xffffff, 1.2); // Soft ambient light
        scene.add(ambientLight);
        const pointLight = new THREE.PointLight(0xffffff, 2.5, 500); // Main directional light
        pointLight.position.set(5, 5, 8);
        scene.add(pointLight);
        const pointLight2 = new THREE.PointLight(0xcccccc, 0.8, 500); // Fill light
        pointLight2.position.set(-10, -5, -10);
        scene.add(pointLight2);

        // OrbitControls for interaction
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true; // Smooths rotation
        controls.dampingFactor = 0.05;
        controls.enableZoom = false; // Disable zooming
        controls.enablePan = false; // Disable panning
        controls.rotateSpeed = 0.4;
        controls.minDistance = 2.5; // Prevent zooming too close
        controls.maxDistance = 10; // Prevent zooming too far
        controls.target.set(0, 0, 0); // Point controls at the center
        // Resume auto-rotation when interaction stops
        controls.addEventListener('end', () => { if (!selectedLocationData) { isGlobeRotating = true; } });
        // Stop auto-rotation and hide popup when interaction starts
        controls.addEventListener('start', () => { isGlobeRotating = false; hidePopup(); });

        // Raycaster for detecting clicks/hovers on the globe
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2(); // To store mouse coordinates

        // Event Listeners for interaction and resizing
        window.addEventListener('resize', onWindowResize, false);
        globeContainer.addEventListener('mousemove', onMouseMove, false);
        globeContainer.addEventListener('click', onClick, false);
        globeContainer.addEventListener('touchstart', onTouchStart, { passive: true });
        globeContainer.addEventListener('touchmove', onTouchMove, { passive: true });

        animate(); // Start the animation loop
    }

    /**
     * Creates a background starfield.
     * @param {number} starCount - The number of stars to generate.
     */
    function createStars(starCount = 10000) {
        const starGeometry = new THREE.BufferGeometry();
        const starMaterial = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.025, // Adjust size as needed
            sizeAttenuation: true, // Stars shrink with distance
            transparent: true,
            opacity: 0.9,
            depthWrite: false // Helps with transparency issues
        });
        const starVertices = [];
        const starAlphas = []; // Base alpha for flickering

        for (let i = 0; i < starCount; i++) {
            // Random positions within a large sphere
            const x = (Math.random() - 0.5) * 2000;
            const y = (Math.random() - 0.5) * 2000;
            const z = (Math.random() - 0.5) * 2000;
            const dist = Math.sqrt(x*x + y*y + z*z);
            // Only add stars within a certain distance range
            if (dist > 100 && dist < 1000) {
                starVertices.push(x, y, z);
                starAlphas.push(Math.random() * 0.5 + 0.4); // Random base opacity
            }
        }
        starGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
        starGeometry.setAttribute('baseAlpha', new THREE.Float32BufferAttribute(starAlphas, 1));
        // Separate attribute for animated alpha
        starGeometry.setAttribute('alpha', new THREE.Float32BufferAttribute(starAlphas.slice(), 1));
        stars = new THREE.Points(starGeometry, starMaterial);
        scene.add(stars);
    }

    /**
     * Handles window resize events to adjust camera and renderer.
     */
    function onWindowResize() {
        if (!globeContainer || !renderer || !camera) return;
        const width = globeContainer.clientWidth;
        const height = globeContainer.clientHeight;
        if (width === 0 || height === 0) return; // Avoid division by zero
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height);
    }

    /**
     * Converts UV coordinates (from raycaster intersection) to Latitude/Longitude.
     * @param {THREE.Vector2} uv - The UV coordinates.
     * @returns {object|null} Object with {lat, lon} or null if input is invalid.
     */
    function uvToLatLon(uv) {
        if (!uv) return null;
        const lon = (uv.x * 360) - 180;
        // Clamp y to avoid Math errors near poles
        const clampedY = Math.max(0.0, Math.min(1.0, uv.y));
        // Simplified inverse Mercator projection for latitude
        const latRad = Math.acos(2 * clampedY - 1) - Math.PI / 2;
        const lat = -latRad * (180 / Math.PI); // Radians to degrees
        return { lat, lon };
    }

    /**
     * Finds geographic data (country or continent) matching given coordinates.
     * @param {number} lat - Latitude.
     * @param {number} lon - Longitude.
     * @returns {object|null} The matching location data object or null.
     */
    function getLocationDataFromLatLon(lat, lon) {
        // Check countries first
        if (countryData?.countries) {
            for (const country of countryData.countries) {
                if (country.bounds && lat >= country.bounds.minLat && lat <= country.bounds.maxLat && lon >= country.bounds.minLon && lon <= country.bounds.maxLon) {
                    return country;
                }
            }
        }
        // Then check continents
        if (continentData?.continents) {
            // Ensure Europe check priority if needed due to potential overlap with Asia bounds
            const continentsInOrder = continentData.continents.sort((a, b) => a.name === 'Europe' ? -1 : (b.name === 'Europe' ? 1 : 0));
            for (const continent of continentsInOrder) {
                if (continent.bounds && lat >= continent.bounds.minLat && lat <= continent.bounds.maxLat && lon >= continent.bounds.minLon && lon <= continent.bounds.maxLon) {
                    // Specific check to avoid misidentifying Europe as Asia if bounds overlap
                    if (continent.name === 'Asia') {
                        const europe = continentData.continents.find(c => c.name === 'Europe');
                        if (europe?.bounds && lat >= europe.bounds.minLat && lat <= europe.bounds.maxLat && lon >= europe.bounds.minLon && lon <= europe.bounds.maxLon) {
                            continue; // Skip Asia if it's within Europe's bounds
                        }
                    }
                    return continent;
                }
            }
        }
        return null; // No match found
    }

    /**
     * Handles touch start events for mobile interaction.
     */
     function onTouchStart(event) {
        if (event.touches.length > 0) {
            isGlobeRotating = false; // Stop rotation on touch
            hidePopup();

             // Calculate touch position for raycasting
             const rect = globeContainer.getBoundingClientRect();
             mouse.x = ((event.touches[0].clientX - rect.left) / rect.width) * 2 - 1;
             mouse.y = -((event.touches[0].clientY - rect.top) / rect.height) * 2 + 1;

             // Trigger click logic immediately for faster feedback
             onClick(event.touches[0]);
        }
    }

    /**
     * Handles touch move events (primarily for OrbitControls).
     */
    function onTouchMove(event) {
         // OrbitControls handles drag rotation via its own touch listeners usually
    }

    /**
     * Handles mouse move events for hover effects (Throttled).
     */
    function onMouseMove(event) {
        // Update mouse coordinates immediately
        if (globeContainer && mouse) {
            const rect = globeContainer.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            }
        }

        // Clear any existing timeout to reset the throttle timer
        if (mouseMoveTimeout) {
            clearTimeout(mouseMoveTimeout);
        }

        // Set a new timeout to execute the raycasting logic after the delay
        mouseMoveTimeout = setTimeout(() => {
            performRaycasting(event); // Call the actual raycasting logic
        }, MOUSE_MOVE_THROTTLE_DELAY);
    }

    /**
     * Performs the actual raycasting and updates hover indicators.
     * This function is called by the throttled onMouseMove handler.
     */
    function performRaycasting(event) {
         // Basic checks for readiness
        if (!globeContainer || !globe || !camera || !raycaster || !mouse || !globe.material.map?.image || globe.material.map.image.width === 0) {
            if(hoverIndicator) hoverIndicator.style.display = 'none';
            currentHoverCoords = null;
            if (!selectedLocationData && infoDisplayElement && !infoDisplayElement.textContent.includes("Error")) {
                infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>";
            }
            return;
        }

        // Perform raycasting only if nothing is selected or hover indicator is active
        if (!selectedLocationData || (hoverIndicator && hoverIndicator.style.display !== 'none')) {
            raycaster.setFromCamera(mouse, camera); // Use the already updated mouse coordinates
            const intersects = raycaster.intersectObject(globe);

            if (intersects.length > 0) {
                const intersection = intersects[0];
                if (intersection.uv) { // Check if UV coordinates exist
                    currentHoverCoords = uvToLatLon(intersection.uv);
                    if (!selectedLocationData) { displayCoordinatesData(currentHoverCoords); } // Show lat/lon
                } else {
                    currentHoverCoords = null;
                    if (!selectedLocationData) { infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>"; }
                }
                // Position and show hover indicator (red circle)
                if (hoverIndicator) {
                    hoverIndicator.style.display = 'block';
                    hoverIndicator.style.left = `${event.clientX}px`;
                    hoverIndicator.style.top = `${event.clientY}px`;
                }
            } else {
                // No intersection - hide indicator and reset hover info
                if(hoverIndicator) hoverIndicator.style.display = 'none';
                currentHoverCoords = null;
                if (!selectedLocationData && infoDisplayElement && !infoDisplayElement.textContent.includes("Error")) {
                    infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>";
                }
            }
        } else {
            // Location is selected, hide hover indicator
            if(hoverIndicator) hoverIndicator.style.display = 'none';
        }
    }


    /**
     * Handles click events on the globe to select locations.
     */
    function onClick(event) {
        if (!mouse || !raycaster || !camera || !globe) return;

        // Use current mouse coordinates
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObject(globe);

        if (intersects.length > 0) {
            const intersection = intersects[0];
            if (intersection.uv) {
                const coords = uvToLatLon(intersection.uv);
                if (coords) {
                    const locationData = getLocationDataFromLatLon(coords.lat, coords.lon);
                    if (locationData) {
                        // Location found: Select it
                        isGlobeRotating = false;
                        selectedLocationData = locationData;
                        selectedPoint3D = intersection.point.clone();
                        displayLocationData(locationData);
                        showPopup(locationData);
                        if (hoverIndicator) hoverIndicator.style.display = 'none';
                    } else {
                        // Clicked on globe but no data (ocean): Deselect
                        deselectLocation();
                    }
                } else { deselectLocation(); } // Error converting UV
            } else { deselectLocation(); } // No UV coords on intersection
        } else { deselectLocation(); } // Clicked outside globe
    }

    /** Helper function to deselect location and resume rotation */
    function deselectLocation() {
        isGlobeRotating = true;
        selectedLocationData = null;
        selectedPoint3D = null;
        hidePopup();
        displayLocationData(null); // Display default message
    }

    /**
     * Displays latitude/longitude in the info box.
     */
    function displayCoordinatesData(coords) {
        if (coords && infoDisplayElement && !infoDisplayElement.textContent.includes("Error")) {
            infoDisplayElement.innerHTML = `
                 <div class="coords">Lat: ${coords.lat.toFixed(2)}, Lon: ${coords.lon.toFixed(2)}</div>
                 <p class="mt-1 text-xs text-gray-400">Click for details</p>
             `;
        }
    }

    /**
     * Displays selected location data in the info box.
     */
    function displayLocationData(locationData) {
        if (!infoDisplayElement) return;
        let displayHtml = "";
        if (locationData && !infoDisplayElement.textContent.includes("Error")) {
            const elecUsage = locationData.electricity_usage_gwh?.toLocaleString() ?? 'N/A';
            const waterUsage = locationData.water_usage_billion_cubic_meters?.toLocaleString() ?? 'N/A';
            displayHtml = `
                <span class="location-type">${locationData.type || 'Info'}</span>
                <div class="location-name">${locationData.name || 'Unknown'}</div>
                <div><span class="data-label">Electricity:</span> ${elecUsage} GWh</div>
                <div><span class="data-label">Water:</span> ${waterUsage} B m³</div>
                <p class="mt-1 text-xs text-gray-400">Click ocean/space to deselect</p>
            `;
        } else {
            if (currentHoverCoords) { displayCoordinatesData(currentHoverCoords); return; }
            else { displayHtml = "<p>Hover or click the globe.</p>"; }
        }
        infoDisplayElement.innerHTML = displayHtml;
    }

    /**
     * Populates the location popup with data.
     */
    function showPopup(locationData) {
        if (!locationPopup || !locationData) return;
        const elecUsage = locationData.electricity_usage_gwh?.toLocaleString() ?? 'N/A';
        const waterUsage = locationData.water_usage_billion_cubic_meters?.toLocaleString() ?? 'N/A';
        locationPopup.innerHTML = `
             <span class="location-type">${locationData.type || 'Info'}</span>
             <div class="location-name">${locationData.name || 'Unknown'}</div>
             <div><span class="data-label">Elec:</span> ${elecUsage} GWh</div>
             <div><span class="data-label">Water:</span> ${waterUsage} B m³</div>
         `;
        // Position/visibility handled in updatePopupPosition
    }

    /**
     * Hides the location popup.
     */
    function hidePopup() {
        if (!locationPopup) return;
        locationPopup.classList.remove('visible');
    }

    /**
     * Updates the screen position of the location popup based on the selected 3D point.
     */
    function updatePopupPosition() {
        if (!selectedLocationData || !selectedPoint3D || !locationPopup || !camera || !globeContainer) {
            if (locationPopup && locationPopup.classList.contains('visible')) { hidePopup(); }
            return;
        }
        const canvasRect = globeContainer.getBoundingClientRect();
        if (canvasRect.width === 0 || canvasRect.height === 0) return;

        const screenPos = selectedPoint3D.clone().project(camera);
        const screenX = ((screenPos.x + 1) / 2) * canvasRect.width + canvasRect.left;
        const screenY = ((-screenPos.y + 1) / 2) * canvasRect.height + canvasRect.top;

        locationPopup.style.left = `${screenX}px`;
        locationPopup.style.top = `${screenY}px`;

        if (!locationPopup.classList.contains('visible')) {
            locationPopup.classList.add('visible');
        }
    }

    /**
     * Applies the correct theme class (light/dark) to the location popup.
     */
    function updatePopupTheme(theme) {
        if (!locationPopup) return;
        const isDark = theme === 'dark';
        locationPopup.classList.toggle('dark-popup', isDark);
        locationPopup.classList.toggle('light-popup', !isDark);
    }

    /**
     * The main animation loop (called via requestAnimationFrame).
     */
    function animate(time) {
        requestAnimationFrame(animate); // Loop
        time *= 0.001; // Convert time to seconds

        if (isGlobeRotating && globe) { globe.rotation.y += 0.0005; } // Auto-rotate globe
        if (selectedLocationData) { updatePopupPosition(); } // Update popup position

        // Animate star flickering
        if (stars && stars.geometry.attributes.alpha) {
            const alphas = stars.geometry.attributes.alpha;
            const baseAlphas = stars.geometry.attributes.baseAlpha;
            const timeFactor = time * 7;
            for (let i = 0; i < alphas.count; i++) {
                const sineValue = Math.sin(timeFactor * (0.3 + (i % 15) * 0.08) + i * 0.5);
                const intensityFactor = (sineValue + 1) / 2 * 0.95 + 0.05;
                alphas.array[i] = baseAlphas.array[i] * intensityFactor;
                alphas.array[i] = Math.max(0.1, Math.min(1.0, alphas.array[i]));
            }
            alphas.needsUpdate = true;
            stars.rotation.y += 0.00008; // Slow background rotation
        }

        if (controls) controls.update(); // Required for OrbitControls damping
        if (renderer && scene && camera) renderer.render(scene, camera); // Render the scene
    }

    /**
     * Initializes the Three.js globe application.
     */
    function runInitGlobe() {
        try {
            if (!globeContainer) { throw new Error("Canvas container (#canvas-container) not found!"); }
            if (typeof THREE === 'undefined') { throw new Error("THREE (Three.js) library not found!"); }
            if (typeof THREE.OrbitControls === 'undefined') { throw new Error("THREE.OrbitControls not found!"); }

            initGlobe(); // Initialize the globe

            const initialTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            updatePopupTheme(initialTheme); // Apply initial theme to popup

        } catch (error) {
            console.error("Error during Globe initialization:", error);
            if (globeLoadingIndicator) globeLoadingIndicator.textContent = "Error initializing 3D scene.";
            if (infoDisplayElement) infoDisplayElement.innerHTML = `<p style="color: red;">Error initializing 3D scene: ${error.message}</p>`;
        }
    }


    // --- D3 Map Functions ---

    /**
     * Updates the map view based on the selected tariff type (electricity or water).
     * @param {'electricity' | 'water'} mapType - The type of map data to display.
     */
    function updateMapView(mapType) {
        if (!loadedIndiaGeoData || mapSvg.empty()) {
            console.error("Map data or SVG container not ready for update.");
            return;
        }
        currentMapType = mapType; // Update the state

        let dataMap, title, subtitle, unit, domain, colorsLight, colorsDark, unknownColor;

        // --- Define Settings Based on Map Type ---
        if (mapType === 'electricity') {
            dataMap = electricityTariffData;
            title = "India: Average Electricity Tariff by State";
            subtitle = "Average Tariff (₹/kWh)";
            unit = "₹/kWh";
            domain = [4, 9]; // Min/Max expected electricity tariff
            colorsLight = ["#ccebc5", "#a8ddb5", "#7bccc4", "#4eb3d3", "#2b8cbe", "#0868ac", "#084081"];
            colorsDark = ["#ffffd4", "#fee391", "#fec44f", "#fe9929", "#d95f0e", "#993404", "#4d0000"];
            unknownColor = "#ccc";
        } else { // Water
            dataMap = waterTariffData;
            title = "India: Representative Water Tariff by State";
            subtitle = "Average Tariff (₹/kL - Kilolitre)";
            unit = "₹/kL";
            domain = [50, 600]; // Min/Max expected water tariff
            colorsLight = ["#eff3ff", "#c6dbef", "#9ecae1", "#6baed6", "#4292c6", "#2171b5", "#084594"];
            colorsDark = ["#f7fbff", "#deebf7", "#c6dbef", "#9ecae1", "#6baed6", "#3182bd", "#08519c"];
            unknownColor = "#969696";
        }

        // Update Titles
        if (mapMainTitle) mapMainTitle.textContent = title;
        if (mapSubtitle) mapSubtitle.textContent = subtitle;

        // Determine current theme and colors
        const isDarkMode = document.documentElement.classList.contains('dark');
        currentMapColorScheme = isDarkMode ? colorsDark : colorsLight;

        // Update Color Scale
        currentMapScale = d3.scaleQuantize()
            .domain(domain)
            .range(currentMapColorScheme)
            .unknown(unknownColor);

        // --- Redraw Map States ---
        const mapWidth = mapContainer.node()?.getBoundingClientRect().width ?? 600;
        const width = mapWidth > 0 ? mapWidth : 600;
        const height = width * 0.9;
        mapSvg.attr("viewBox", `0 0 ${width} ${height}`)
           .attr("preserveAspectRatio", "xMidYMid meet")
           .attr("width", "100%")
           .attr("height", height);

        const projection = d3.geoMercator().fitSize([width, height], loadedIndiaGeoData);
        const path = d3.geoPath().projection(projection);

        mapSvg.selectAll("g").remove(); // Clear previous map paths
        mapSvg.append("g")
            .selectAll(".state")
            .data(loadedIndiaGeoData.features)
            .join("path")
            .attr("class", "state")
            .attr("d", path)
            .attr("fill", d => {
                const stateName = d.properties.NAME_1;
                const tariffInfo = dataMap.get(stateName);
                const value = (mapType === 'electricity') ? tariffInfo : tariffInfo?.value;
                return currentMapScale(value);
            })
            .on("mouseover", function(event, d) {
                d3.select(this).raise();
                const stateName = d.properties.NAME_1;
                const tariffInfo = dataMap.get(stateName);
                let valueText = "No data";
                let disclaimerText = "";

                if (mapType === 'electricity') {
                    if (tariffInfo !== undefined && tariffInfo !== null) {
                        valueText = `₹${tariffInfo.toFixed(2)} ${unit}`;
                    }
                } else { // Water
                    if (tariffInfo && tariffInfo.value !== undefined && tariffInfo.value !== null) {
                        valueText = `₹${tariffInfo.value.toFixed(2)} ${unit}`;
                        if (tariffInfo.disclaimer) {
                            disclaimerText = `<br><em style="font-size: 0.75em; opacity: 0.8;">(${tariffInfo.disclaimer})</em>`;
                        }
                    }
                }

                mapTooltip.html(`<strong>${stateName || 'Unknown'}</strong><br>${valueText}${disclaimerText}`)
                       .classed('visible', true);
            })
            .on("mousemove", function(event) {
                 const [mouseX, mouseY] = d3.pointer(event, mapContainer.node());
                 mapTooltip.style("left", (mouseX + 15) + "px")
                        .style("top", (mouseY - 30) + "px");
            })
            .on("mouseout", function() {
                mapTooltip.classed('visible', false);
            });

        // --- Redraw Legend ---
        drawMapLegend(currentMapScale, unit);
    }

    /**
     * Draws the map legend based on the current color scale and units.
     * @param {d3.ScaleQuantize} scale - The D3 color scale.
     * @param {string} unit - The unit label (e.g., '₹/kWh', '₹/kL').
     */
    function drawMapLegend(scale, unit) {
        if (mapLegendContainer.empty() || !scale) return;
        mapLegendContainer.html(''); // Clear previous legend

        const legendTitle = mapLegendContainer.append("span")
            .style("font-weight", "bold")
            .style("margin-right", "10px")
            .text(`Tariff (${unit}):`);

        const thresholds = scale.thresholds ? scale.thresholds() : [];
        const legendData = [scale.domain()[0], ...thresholds, scale.domain()[1]];
        const colorRange = scale.range();

        colorRange.forEach((color, i) => {
            const lowerBound = legendData[i];
            const upperBound = legendData[i + 1];
            const rangeText = upperBound !== undefined
                ? `${lowerBound.toFixed(1)} - ${upperBound.toFixed(1)}`
                : `> ${lowerBound.toFixed(1)}`;

            const legendItem = mapLegendContainer.append("span").attr("class", "legend-item");
            legendItem.append("span").attr("class", "legend-color-box").style("background-color", color);
            legendItem.append("span").text(rangeText);
        });

        const legendUnknown = mapLegendContainer.append("span").attr("class", "legend-item");
        legendUnknown.append("span").attr("class", "legend-color-box").style("background-color", scale.unknown());
        legendUnknown.append("span").text("No Data");
    }

    /**
     * Initializes the D3 map, loads GeoJSON, and sets up interactions.
     */
    function initializeMap() {
        if (typeof d3 === 'undefined') {
            console.error("D3 library not loaded.");
            mapContainer.html("<p style='color: red; text-align: center;'>Error: D3 library failed to load.</p>");
            return;
        }
        if (mapContainer.empty() || mapSvg.empty() || mapTooltip.empty() || mapLegendContainer.empty()) {
             console.error("Map container, SVG, Tooltip, or Legend element not found.");
             return;
        }

        // Load GeoJSON data
        d3.json(mapUrl).then(indiaData => {
            loadedIndiaGeoData = indiaData; // Store loaded data

            // Draw the initial map (Electricity)
            updateMapView('electricity');

            // Setup toggle button listeners AFTER data is loaded
            mapToggleButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const selectedType = button.dataset.mapType;
                    if (selectedType !== currentMapType) {
                        mapToggleButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        updateMapView(selectedType);
                    }
                });
            });

        }).catch(error => {
            console.error("Error loading or processing map GeoJSON data:", error);
            mapContainer.html("<p style='color: red; text-align: center;'>Could not load map data.</p>");
        });
    }


    // --- Initialization Calls ---
    runInitGlobe(); // Initialize the Three.js Globe
    initializeMap(); // Initialize the D3 Map

    // --- Global Event Listeners ---
    // Listen for theme changes to update BOTH Globe Popup and D3 Map colors
    window.addEventListener('themeUpdated', (event) => {
        if (event.detail && event.detail.theme) {
            const newTheme = event.detail.theme;
            // Update Globe Popup Theme
            updatePopupTheme(newTheme);

            // Update D3 Map Theme (redraws with current data type)
            if (loadedIndiaGeoData && currentMapScale) { // Ensure map is ready
                const isDarkMode = newTheme === 'dark';
                let colorsLight, colorsDark;
                // Define color schemes based on the *current* map type
                if (currentMapType === 'electricity') {
                     colorsLight = ["#ccebc5", "#a8ddb5", "#7bccc4", "#4eb3d3", "#2b8cbe", "#0868ac", "#084081"];
                     colorsDark = ["#ffffd4", "#fee391", "#fec44f", "#fe9929", "#d95f0e", "#993404", "#4d0000"];
                } else { // Water
                     colorsLight = ["#eff3ff", "#c6dbef", "#9ecae1", "#6baed6", "#4292c6", "#2171b5", "#084594"];
                     colorsDark = ["#f7fbff", "#deebf7", "#c6dbef", "#9ecae1", "#6baed6", "#3182bd", "#08519c"];
                }
                currentMapColorScheme = isDarkMode ? colorsDark : colorsLight;
                currentMapScale.range(currentMapColorScheme); // Update scale range
                updateMapView(currentMapType); // Redraw map and legend with new colors
            }
        }
    });

}); // End DOMContentLoaded listener

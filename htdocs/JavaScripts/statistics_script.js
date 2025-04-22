document.addEventListener('DOMContentLoaded', () => {

    // --- Three.js Global Variables ---
    let scene, camera, renderer, globe, controls, stars;
    let raycaster, mouse;
    let isGlobeRotating = true;
    let currentHoverCoords = null;
    let selectedLocationData = null;
    let selectedPoint3D = null; // Store the 3D point of the selected location

    // --- DOM Element References ---
    const container = document.getElementById('canvas-container');
    const hoverIndicator = document.getElementById('hover-indicator');
    const infoDisplayElement = document.getElementById('info-display');
    const locationPopup = document.getElementById('location-popup');
    const loadingIndicator = container?.querySelector('div'); 

    // --- Geographic Data ---
    // Note: Bounding boxes are approximate and might need refinement for accuracy.
    const continentData = {
        "continents": [
            { "type": "continent", "name": "Asia", "electricity_usage_gwh": 25000000, "water_usage_billion_cubic_meters": 3600, "bounds": { "minLon": 25, "maxLon": 180, "minLat": -11, "maxLat": 82 } },
            { "type": "continent", "name": "Europe", "electricity_usage_gwh": 8000000, "water_usage_billion_cubic_meters": 1100, "bounds": { "minLon": -25, "maxLon": 65, "minLat": 35, "maxLat": 82 } },
            { "type": "continent", "name": "Africa", "electricity_usage_gwh": 1000000, "water_usage_billion_cubic_meters": 400, "bounds": { "minLon": -18, "maxLon": 52, "minLat": -35, "maxLat": 38 } },
            { "type": "continent", "name": "North America", "electricity_usage_gwh": 12000000, "water_usage_billion_cubic_meters": 1500, "bounds": { "minLon": -169, "maxLon": -50, "minLat": 7, "maxLat": 84 } },
            { "type": "continent", "name": "South America", "electricity_usage_gwh": 2500000, "water_usage_billion_cubic_meters": 900, "bounds": { "minLon": -82, "maxLon": -34, "minLat": -56, "maxLat": 13 } },
            { "type": "continent", "name": "Australia", "electricity_usage_gwh": 1000000, "water_usage_billion_cubic_meters": 250, "bounds": { "minLon": 112, "maxLon": 154, "minLat": -44, "maxLat": -10 } }, // Continent bounds
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
             { "type": "country", "name": "Australia", "electricity_usage_gwh": 250000, "water_usage_billion_cubic_meters": 100, "bounds": { "minLon": 112, "maxLon": 154, "minLat": -44, "maxLat": -10 } }, // Country bounds
             { "type": "country", "name": "Canada", "electricity_usage_gwh": 600000, "water_usage_billion_cubic_meters": 120, "bounds": { "minLon": -141, "maxLon": -52, "minLat": 41, "maxLat": 83 } }
        ]
    };


    function init() {
        scene = new THREE.Scene();
        createStars(10000); 

        camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 3.8; 

        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true }); 
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.outputColorSpace = THREE.SRGBColorSpace; 
        container.appendChild(renderer.domElement); 

        const geometry = new THREE.SphereGeometry(1.5, 64, 64); 

        // Visible Texture Generation (using placeholder until image loads)
        let visibleTexture;
        const visibleCanvas = document.createElement('canvas');
        visibleCanvas.width = 256; visibleCanvas.height = 128; 
        const visibleContext = visibleCanvas.getContext('2d');
        visibleContext.fillStyle = 'rgb(50, 50, 100)'; 
        visibleContext.fillRect(0, 0, visibleCanvas.width, visibleCanvas.height);
        visibleTexture = new THREE.CanvasTexture(visibleCanvas);
        visibleTexture.colorSpace = THREE.SRGBColorSpace;
        visibleTexture.generateMipmaps = false;
        visibleTexture.minFilter = THREE.LinearFilter;
        visibleTexture.magFilter = THREE.LinearFilter;
        visibleTexture.needsUpdate = true;

        // Load the actual texture image
        const visibleImage = new Image();
        visibleImage.crossOrigin = 'Anonymous'; 
        visibleImage.onload = () => {
            visibleCanvas.width = visibleImage.naturalWidth;
            visibleCanvas.height = visibleImage.naturalHeight;
            visibleContext.drawImage(visibleImage, 0, 0, visibleCanvas.width, visibleCanvas.height);
            visibleTexture.needsUpdate = true;
            if (loadingIndicator) loadingIndicator.style.display = 'none'; 
            // Clear potential error message if map loads successfully after an error
            if (infoDisplayElement && infoDisplayElement.textContent.includes("Error loading map image.")) {
                infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>";
            }
        };
        visibleImage.onerror = (err) => {
            console.error("Error loading visible map texture:", visibleImage.src, err);
            if (infoDisplayElement) infoDisplayElement.innerHTML = `<p style="color: red;">Error loading map image.</p>`;
             if (loadingIndicator) loadingIndicator.textContent = "Error loading map.";
        };
        const visibleImageUrl = 'https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg'; // Consider hosting locally
        visibleImage.src = visibleImageUrl;

        // Globe Material & Mesh
        const material = new THREE.MeshStandardMaterial({
            map: visibleTexture,
            color: 0xffffff,
            roughness: 0.85,
            metalness: 0.1,
        });
        globe = new THREE.Mesh(geometry, material);
        scene.add(globe);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 1.2);
        scene.add(ambientLight);
        const pointLight = new THREE.PointLight(0xffffff, 2.5, 500);
        pointLight.position.set(5, 5, 8);
        scene.add(pointLight);
        const pointLight2 = new THREE.PointLight(0xcccccc, 0.8, 500);
        pointLight2.position.set(-10, -5, -10);
        scene.add(pointLight2);

        // Controls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.enableZoom = false; 
        controls.enablePan = false; 
        controls.rotateSpeed = 0.4;
        controls.minDistance = 2.5;
        controls.maxDistance = 10;
        controls.target.set(0, 0, 0);
        controls.addEventListener('end', () => { if (!selectedLocationData) { isGlobeRotating = true; } });
        controls.addEventListener('start', () => { isGlobeRotating = false; hidePopup(); });

        // Raycaster
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2();

        // Event Listeners
        window.addEventListener('resize', onWindowResize, false);
        container.addEventListener('mousemove', onMouseMove, false);
        container.addEventListener('click', onClick, false);
        container.addEventListener('touchstart', onTouchStart, { passive: true }); // Use passive for performance if preventDefault isn't needed
        container.addEventListener('touchmove', onTouchMove, { passive: true }); // Use passive for performance


        animate(); // Start animation loop
    }

    function createStars(starCount = 10000) {
        const starGeometry = new THREE.BufferGeometry();
        const starMaterial = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.025,
            sizeAttenuation: true,
            transparent: true,
            opacity: 0.9,
            depthWrite: false // Prevents stars rendering issues with transparent globe parts
        });
        const starVertices = [];
        const starAlphas = []; // Base alpha for flickering effect
        for (let i = 0; i < starCount; i++) {
            const x = (Math.random() - 0.5) * 2000;
            const y = (Math.random() - 0.5) * 2000;
            const z = (Math.random() - 0.5) * 2000;
            const dist = Math.sqrt(x*x + y*y + z*z);
            // Ensure stars are within a certain range for visibility
            if (dist > 100 && dist < 1000) { 
                starVertices.push(x, y, z);
                starAlphas.push(Math.random() * 0.5 + 0.4); // Random base alpha between 0.4 and 0.9
            }
        }
        starGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
        starGeometry.setAttribute('baseAlpha', new THREE.Float32BufferAttribute(starAlphas, 1));
        // Create a separate attribute for the animated alpha
        starGeometry.setAttribute('alpha', new THREE.Float32BufferAttribute(starAlphas.slice(), 1)); 
        stars = new THREE.Points(starGeometry, starMaterial);
        scene.add(stars);
    }

    function onWindowResize() {
        if (!container || !renderer || !camera) return;
        const width = container.clientWidth;
        const height = container.clientHeight;
        if (width === 0 || height === 0) return; // Avoid issues with 0 dimensions
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height);
    }

    function uvToLatLon(uv) {
        if (!uv) return null;
        // Convert UV coordinates (0-1 range) to Latitude/Longitude
        const lon = (uv.x * 360) - 180;
        // Clamp UV.y to avoid Math.acos domain errors at poles
        const clampedY = Math.max(0.0, Math.min(1.0, uv.y)); 
        // Inverse of the Mercator projection y-coordinate formula (simplified)
        const latRad = Math.acos(2 * clampedY - 1) - Math.PI / 2; 
        const lat = -latRad * (180 / Math.PI); // Convert radians to degrees
        return { lat, lon };
    }

    function getLocationDataFromLatLon(lat, lon) {
        // Prioritize checking country data first
        if (countryData && countryData.countries) {
            for (const country of countryData.countries) {
                // Check if coordinates fall within the country's bounding box
                if (country.bounds && lat >= country.bounds.minLat && lat <= country.bounds.maxLat && lon >= country.bounds.minLon && lon <= country.bounds.maxLon) {
                    return country;
                }
            }
        }
        // If no country matches, check continent data
        if (continentData && continentData.continents) {
            // Sort to check Europe before Asia if needed, though bounding boxes should ideally not overlap significantly
            const continentsInOrder = continentData.continents.sort((a, b) => a.name === 'Europe' ? -1 : (b.name === 'Europe' ? 1 : 0));
            for (const continent of continentsInOrder) {
                if (continent.bounds && lat >= continent.bounds.minLat && lat <= continent.bounds.maxLat && lon >= continent.bounds.minLon && lon <= continent.bounds.maxLon) {
                    // Specific check to prevent misidentifying Europe as Asia if boxes overlap
                    if (continent.name === 'Asia') {
                        const europe = continentData.continents.find(c => c.name === 'Europe');
                        if (europe && europe.bounds && lat >= europe.bounds.minLat && lat <= europe.bounds.maxLat && lon >= europe.bounds.minLon && lon <= europe.bounds.maxLon) {
                            continue; // Skip Asia if it's within Europe's bounds
                        }
                    }
                    return continent;
                }
            }
        }
        return null; // No matching location found
    }

     function onTouchStart(event) {
        if (event.touches.length > 0) {
            // Simulate mouse down for OrbitControls using the first touch point
            // Note: OrbitControls might have specific touch handlers, but this often works
            // controls.handleMouseDownRotate(event); // Or equivalent touch start method
            isGlobeRotating = false;
            hidePopup();

             // Calculate touch position relative to container for click detection
             const rect = container.getBoundingClientRect();
             mouse.x = ((event.touches[0].clientX - rect.left) / rect.width) * 2 - 1;
             mouse.y = -((event.touches[0].clientY - rect.top) / rect.height) * 2 + 1;
             
             // Trigger click logic immediately on touch start for faster feedback on mobile
             onClick(event.touches[0]); 
        }
    }

    function onTouchMove(event) {
         if (event.touches.length > 0) {
             // Simulate mouse move for OrbitControls
             // controls.handleMouseMoveRotate(event); // Or equivalent touch move method

             // Optionally update hover indicator based on touch move (can be intensive)
             // const rect = container.getBoundingClientRect();
             // mouse.x = ((event.touches[0].clientX - rect.left) / rect.width) * 2 - 1;
             // mouse.y = -((event.touches[0].clientY - rect.top) / rect.height) * 2 + 1;
             // onMouseMove(event.touches[0]); // This might be too performance intensive during drag
         }
    }


    function onMouseMove(event) {
        // Basic check for required elements/state
        if (!container || !globe || !camera || !raycaster || !mouse || !globe.material.map?.image || globe.material.map.image.width === 0) {
            if(hoverIndicator) hoverIndicator.style.display = 'none';
            currentHoverCoords = null;
            if (!selectedLocationData && infoDisplayElement && !infoDisplayElement.textContent.includes("Error")) { 
                infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>"; 
            }
            return;
        }

        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return; // Avoid division by zero

        // Calculate normalized device coordinates (-1 to +1)
        mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        // Only perform raycasting if no location is selected OR the hover indicator is currently shown (meaning we are actively hovering)
        if (!selectedLocationData || (hoverIndicator && hoverIndicator.style.display !== 'none')) {
            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObject(globe);

            if (intersects.length > 0) {
                const intersection = intersects[0];
                if (intersection.uv) {
                    currentHoverCoords = uvToLatLon(intersection.uv);
                    if (!selectedLocationData) { displayCoordinatesData(currentHoverCoords); } // Show lat/lon if nothing selected
                } else { 
                    currentHoverCoords = null; 
                    if (!selectedLocationData) { infoDisplayElement.innerHTML = "<p>Hover or click the globe.</p>"; } 
                }
                // Position and show hover indicator
                if (hoverIndicator) {
                    hoverIndicator.style.display = 'block';
                    hoverIndicator.style.left = `${event.clientX}px`; 
                    hoverIndicator.style.top = `${event.clientY}px`;
                }
            } else {
                // No intersection
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

    function onClick(event) {
        // Mouse coordinates are already updated by onMouseMove or onTouchStart
        if (!mouse || !raycaster || !camera || !globe) return;

        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObject(globe);

        if (intersects.length > 0) {
            const intersection = intersects[0];
            if (intersection.uv) {
                const coords = uvToLatLon(intersection.uv);
                if (coords) {
                    const locationData = getLocationDataFromLatLon(coords.lat, coords.lon);
                    if (locationData) {
                        // Location found - select it
                        isGlobeRotating = false;
                        selectedLocationData = locationData;
                        selectedPoint3D = intersection.point.clone(); // Store intersection point for popup positioning
                        displayLocationData(locationData); // Update info box
                        showPopup(locationData); // Show popup near click
                        if (hoverIndicator) hoverIndicator.style.display = 'none';
                    } else {
                        // Clicked on globe but no data found (e.g., ocean) - deselect
                        isGlobeRotating = true; 
                        selectedLocationData = null; 
                        selectedPoint3D = null; 
                        hidePopup();
                        // Show coordinates if available, otherwise default message
                        if (currentHoverCoords) { displayCoordinatesData(currentHoverCoords); } 
                        else { displayLocationData(null); } 
                    }
                } else {
                     // Error converting UV to Lat/Lon - deselect
                     isGlobeRotating = true; selectedLocationData = null; selectedPoint3D = null; hidePopup(); displayLocationData(null);
                }
            } else {
                 // Intersection has no UV coordinates - deselect
                 isGlobeRotating = true; selectedLocationData = null; selectedPoint3D = null; hidePopup(); displayLocationData(null);
            }
        } else {
             // Clicked outside the globe (in space) - deselect
             isGlobeRotating = true; selectedLocationData = null; selectedPoint3D = null; hidePopup(); displayLocationData(null);
        }
    }

    function displayCoordinatesData(coords) {
        if (coords && infoDisplayElement && !infoDisplayElement.textContent.includes("Error")) {
            infoDisplayElement.innerHTML = `
                 <div class="coords">Lat: ${coords.lat.toFixed(2)}, Lon: ${coords.lon.toFixed(2)}</div>
                 <p class="mt-1 text-xs text-gray-400">Click for details</p>
             `;
        }
    }

    function displayLocationData(locationData) {
        if (!infoDisplayElement) return;
        let displayHtml = "";
        if (locationData && !infoDisplayElement.textContent.includes("Error")) {
            const elecUsage = locationData.electricity_usage_gwh?.toLocaleString() ?? 'N/A';
            const waterUsage = locationData.water_usage_billion_cubic_meters?.toLocaleString() ?? 'N/A';
            displayHtml = `
                <span class="location-type">${locationData.type}</span>
                <div class="location-name">${locationData.name}</div>
                <div><span class="data-label">Electricity:</span> ${elecUsage} GWh</div>
                <div><span class="data-label">Water:</span> ${waterUsage} B m³</div>
                <p class="mt-1 text-xs text-gray-400">Click ocean/space to deselect</p>
            `;
        } else {
            // If no location data, show hover coords or default message
            if (currentHoverCoords) { displayCoordinatesData(currentHoverCoords); return; } 
            else { displayHtml = "<p>Hover or click the globe.</p>"; }
        }
        infoDisplayElement.innerHTML = displayHtml;
    }

    function showPopup(locationData) {
        if (!locationPopup || !locationData) return;
        const elecUsage = locationData.electricity_usage_gwh?.toLocaleString() ?? 'N/A';
        const waterUsage = locationData.water_usage_billion_cubic_meters?.toLocaleString() ?? 'N/A';
        locationPopup.innerHTML = `
             <span class="location-type">${locationData.type}</span>
             <div class="location-name">${locationData.name}</div>
             <div><span class="data-label">Elec:</span> ${elecUsage} GWh</div>
             <div><span class="data-label">Water:</span> ${waterUsage} B m³</div>
         `;
        // Position updated in animate loop, visibility class added in updatePopupPosition
    }

    function hidePopup() {
        if (!locationPopup) return;
        locationPopup.classList.remove('visible');
    }

    function updatePopupPosition() {
        if (!selectedLocationData || !selectedPoint3D || !locationPopup || !camera || !container) {
            if (locationPopup && locationPopup.classList.contains('visible')) { hidePopup(); }
            return;
        }
        const canvasRect = container.getBoundingClientRect();
        // Project the 3D point to 2D screen coordinates
        const screenPos = selectedPoint3D.clone().project(camera);
        // Convert normalized device coordinates (-1 to +1) to pixel coordinates
        const screenX = ((screenPos.x + 1) / 2) * canvasRect.width + canvasRect.left;
        const screenY = ((-screenPos.y + 1) / 2) * canvasRect.height + canvasRect.top;
        
        // Position the popup relative to the calculated screen coordinates
        locationPopup.style.left = `${screenX}px`;
        locationPopup.style.top = `${screenY}px`;

        // Add visibility class if not already present (triggers CSS transition)
        if (!locationPopup.classList.contains('visible')) {
            locationPopup.classList.add('visible');
        }
    }

    function updatePopupTheme(theme) {
        if (!locationPopup) return;
        console.log("Updating popup theme to:", theme); // Keep this log for theme debugging
        const isDark = theme === 'dark';
        // Use classes defined in statistics_styles.css to toggle theme
        if (isDark) {
            locationPopup.classList.add('dark-popup');
            locationPopup.classList.remove('light-popup');
        } else {
            locationPopup.classList.add('light-popup');
            locationPopup.classList.remove('dark-popup');
        }
    }

    // Listen for theme changes dispatched from dynamic.js
    window.addEventListener('themeUpdated', (event) => {
        if (event.detail && event.detail.theme) {
            updatePopupTheme(event.detail.theme);
            // Add other theme updates here if needed (e.g., star color, lighting adjustments)
        }
    });


    function animate(time) {
        requestAnimationFrame(animate);
        time *= 0.001; // Convert time to seconds for calculations

        // Auto-rotate globe if not interacting and no location selected
        if (isGlobeRotating && globe) {
            globe.rotation.y += 0.0005; 
        }

        // Update popup position if a location is selected
        if (selectedLocationData) {
            updatePopupPosition(); 
        } else {
            // hidePopup(); // Hiding is handled by click/drag logic now
        }

        // Animate stars alpha for flickering effect
        if (stars && stars.geometry.attributes.alpha) {
            const alphas = stars.geometry.attributes.alpha;
            const baseAlphas = stars.geometry.attributes.baseAlpha;
            const timeFactor = time * 7; // Speed of flicker
            for (let i = 0; i < alphas.count; i++) {
                // Use sine wave for smooth flickering based on time and star index
                const sineValue = Math.sin(timeFactor * (0.3 + (i % 15) * 0.08) + i * 0.5);
                const intensityFactor = (sineValue + 1) / 2 * 0.95 + 0.05; // Map sine (-1 to 1) to intensity (0.05 to 1)
                alphas.array[i] = baseAlphas.array[i] * intensityFactor; // Modulate base alpha
                alphas.array[i] = Math.max(0.1, Math.min(1.0, alphas.array[i])); // Clamp alpha value
            }
            alphas.needsUpdate = true; // Required for BufferAttribute changes
            stars.rotation.y += 0.00008; // Slow background star rotation
        }

        if (controls) controls.update(); // Required for damping effect on OrbitControls
        if (renderer && scene && camera) renderer.render(scene, camera); // Render the scene
    }

    // --- Initialization ---
    function runInit() {
        try {
            // Check for essential elements and libraries before starting
            if (!container) { throw new Error("Canvas container (#canvas-container) not found!"); }
            if (typeof THREE === 'undefined') { throw new Error("THREE (Three.js) library not found! Check CDN link."); }
            if (typeof THREE.OrbitControls === 'undefined') { throw new Error("THREE.OrbitControls not found! Check CDN link."); }

            init(); // Call the main initialization function

            // Apply initial theme to popup after init
            const initialTheme = localStorage.getItem('theme') || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            updatePopupTheme(initialTheme);

        } catch (error) {
            console.error("Error during initialization:", error);
            if (loadingIndicator) loadingIndicator.textContent = "Error initializing 3D scene.";
            if (infoDisplayElement) infoDisplayElement.innerHTML = `<p style="color: red;">Error initializing 3D scene: ${error.message}</p>`;
        }
    }

    // Run initialization function once DOM is ready
    runInit();

}); // End DOMContentLoaded listener

// --- dynamic.js ---

// --- Function to generate and trigger CSV download ---
function downloadCSV(data, filename = 'report.csv') {
    // Check if data is valid
    if (!data || data.length === 0) {
        console.warn('No data provided for CSV download.');
        alert('No data available to download.'); // User-friendly message
        return;
    }
    // Extract headers from the first object in the data array
    const headers = Object.keys(data[0]);
    // Map data objects to arrays of values, handling potential commas/quotes
    const rows = data.map(obj => headers.map(header => {
        // Get cell value, default to empty string if null/undefined
        let cell = obj[header] === null || obj[header] === undefined ? '' : obj[header];
        // Escape double quotes within the cell value
        cell = String(cell).replace(/"/g, '""');
        // Enclose cell in double quotes if it contains comma, double quote, or newline
        if (String(cell).includes(',') || String(cell).includes('"') || String(cell).includes('\n')) {
            cell = `"${cell}"`;
        }
        return cell;
    }));
    // Combine headers and rows into CSV string
    const csvContent = [headers.join(','), ...rows.map(row => row.join(','))].join('\n');
    // Create a Blob object for the CSV data
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    // Create a temporary link element for download
    const link = document.createElement('a');
    // Check if download attribute is supported
    if (link.download !== undefined) {
        // Create a URL for the Blob
        const url = URL.createObjectURL(blob);
        // Set link attributes for download
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        // Make link invisible and append to body
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        // Simulate a click on the link to trigger download
        link.click();
        // Clean up: remove link and revoke Blob URL
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } else {
        // Fallback for browsers that don't support download attribute
        alert('CSV download is not supported in this browser.');
    }
}

// --- Function to initialize a download button ---
function initializeDownloadButton(buttonId, getDataFunction, baseFilename = 'report') {
    // Find the download button element by its ID
    const downloadBtn = document.getElementById(buttonId);

    // Check if the button exists
    if (downloadBtn) {
        // Add click event listener to the button
        downloadBtn.addEventListener('click', () => {
            console.log(`Download button '${buttonId}' clicked`);
            // Add 'clicked' class for animation
            downloadBtn.classList.add('clicked');

            // Get the data by calling the provided function
            const reportData = getDataFunction();

            // Generate a filename with the current date
            const today = new Date();
            const dateString = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
            const reportFilename = `${baseFilename}_${dateString}.csv`;

            // Use setTimeout to allow animation to start before potential blocking download
            setTimeout(() => {
                // Call the downloadCSV function with the data and filename
                downloadCSV(reportData, reportFilename);
                // Remove the 'clicked' class after a delay (matches animation duration)
                setTimeout(() => {
                   downloadBtn.classList.remove('clicked');
                 }, 600); // Delay matches the pulse animation duration
            }, 100); // Short delay before starting download
        });
    } else {
        // Log a warning if the button is not found
        console.warn(`Download report button with ID '${buttonId}' not found.`);
    }
}


// --- Existing Theme Toggle Logic ---
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleButton = document.getElementById('themeToggleButton');
    const htmlElement = document.documentElement;

    const applyTheme = (theme) => {
        const textNode = themeToggleButton ? Array.from(themeToggleButton.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.nodeValue.includes('Switch')) : null;

        if (theme === 'dark') {
            htmlElement.classList.add('dark');
            if (themeToggleButton && textNode) {
                 textNode.nodeValue = " Switch to Light";
            }
            localStorage.setItem('theme', 'dark');
        } else {
            htmlElement.classList.remove('dark');
             if (themeToggleButton && textNode) {
                 textNode.nodeValue = " Switch to Dark";
            }
            localStorage.setItem('theme', 'light');
        }
         // Dispatch a custom event for other scripts (like charts) to listen for
         window.dispatchEvent(new CustomEvent('themeUpdated', { detail: { theme: theme } }));
    };

    const toggleTheme = () => {
        const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    };

    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', toggleTheme);
    } else {
        // Reduced severity from warn to log for non-critical missing element
        // console.log("Theme toggle button not found.");
    }

    // Apply saved theme or default on initial load
    const savedTheme = localStorage.getItem('theme');
    // Default to dark if nothing saved or system preference isn't checked
    const defaultTheme = savedTheme || 'dark';
    applyTheme(defaultTheme);

    // --- Initialize Profile Dropdown ---
    initializeProfileDropdown(); // Keep existing call

}); // End DOMContentLoaded for dynamic.js


// --- Existing Profile Dropdown Logic ---
function initializeProfileDropdown() {
    const profileButton = document.getElementById('profileButton');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (profileButton && dropdownMenu) {
        profileButton.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent click from immediately closing menu
            // Toggle visibility classes
            dropdownMenu.classList.toggle('hidden');
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown if clicking outside
        document.addEventListener('click', (event) => {
            if (!dropdownMenu.contains(event.target) && !profileButton.contains(event.target)) {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                    // Optional: Delay hiding for CSS transitions
                    setTimeout(() => {
                        dropdownMenu.classList.add('hidden');
                    }, 200); // Match transition duration if any
                }
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && dropdownMenu.classList.contains('show')) {
                 dropdownMenu.classList.remove('show');
                 setTimeout(() => {
                     dropdownMenu.classList.add('hidden');
                 }, 200);
            }
        });
    } else {
        // Use console.warn for non-critical issues
        // Reduced severity from warn to log
        // if (!profileButton) console.log("Profile button element not found.");
        // if (!dropdownMenu) console.log("Dropdown menu element not found.");
    }
}

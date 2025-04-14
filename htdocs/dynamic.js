/* // Toggle Settings Menu
function toggleSettings(){
    document.getElementById('themeButton').classList.toggle('hidden');
    document.getElementById('settingsDropdown').classList.toggle('hidden');
}
function toggleSettingsMenu() {
    const settingsDropdown = document.getElementById('settingsDropdown');
    const themeDropdown = document.getElementById('themeDropdown');

    settingsDropdown.classList.toggle('hidden');

    // Close the theme menu if open
    if (!themeDropdown.classList.contains('hidden')) {
        themeDropdown.classList.add('hidden');
    }
}

// Toggle Theme Menu (Nested Dropdown)
function toggleThemeDropdown() {
    const themeDropdown = document.getElementById('themeDropdown');

    // Hide settings menu when theme menu is shown
    const settingsDropdown = document.getElementById('settingsDropdown');
    settingsDropdown.classList.add('hidden');

    themeDropdown.classList.toggle('hidden');
}

// Set the Theme Mode
function setTheme(mode) {
    const body = document.body;
    const contentBoxes = document.querySelectorAll('.content-box');
    localStorage.setItem('theme',mode);

    if (mode === 'dark') {
        body.classList.add('bg-gray-900', 'text-white');
        body.classList.remove('bg-gray-100', 'text-black');

        contentBoxes.forEach(box => {
            box.classList.add('bg-gray-800', 'text-white');
            box.classList.remove('bg-white', 'text-black');
        });

    } else {
        body.classList.add('bg-gray-100', 'text-black');
        body.classList.remove('bg-gray-900', 'text-white');

        contentBoxes.forEach(box => {
            box.classList.add('bg-white', 'text-black');
            box.classList.remove('bg-gray-800', 'text-white');
        });
    }
    document.getElementById('themeButton').classList.toggle('hidden');
    document.getElementById('themeDropdown').classList.toggle('hidden');
}

// Apply default system theme 
function systemTheme() {
    
    window.matchMedia('(prefers-color-scheme: dark)').matches 
        ? setTheme('dark') 
        : setTheme('light');
document.getElementById('themeButton').classList.add('hidden');
document.getElementById('themeDropdown').classList.add('hidden');
}
// load theme from local storage
function loadTheme() {
    const theme = localStorage.getItem('theme');
    if(theme) {
        setTheme(theme);
    }else {
        systemTheme();
    }
    document.getElementById('themeButton').classList.add('hidden');
    document.getElementById('themeDropdown').classList.add('hidden');
}

// assigning reference of loadTheme to onload event
window.onload = loadTheme;

// Close dropdowns when clicking outside
window.onclick = function(event) {
    const settingsDropdown = document.getElementById('settingsDropdown');
    const themeDropdown = document.getElementById('themeButton');
    const themeDropdownContent = document.getElementById('themeDropdown');

    if (!event.target.closest('.relative')) {
        settingsDropdown.classList.add('hidden');
        themeDropdown.classList.add('hidden');
        themeDropdownContent.classList.add('hidden');
    }
}
    */
   // Toggle the visibility of the dropdown menu with animation
   document.getElementById('profileButton').addEventListener('click', function () {
    const dropdownMenu = document.getElementById('dropdownMenu');
    dropdownMenu.classList.toggle('hidden');

    // Add animation class to make the dropdown appear smoothly
    if (!dropdownMenu.classList.contains('hidden')) {
        setTimeout(() => dropdownMenu.classList.add('show'), 10); // Add "show" for animation
    } else {
        dropdownMenu.classList.remove('show'); // Remove "show" when hiding
    }
});

// Close the dropdown menu when clicking outside of it
window.addEventListener('click', function (event) {
    const dropdownMenu = document.getElementById('dropdownMenu');
    const profileButton = document.getElementById('profileButton');

    if (!dropdownMenu.contains(event.target) && event.target !== profileButton) {
        dropdownMenu.classList.add('hidden');
        dropdownMenu.classList.remove('show');
    }
});
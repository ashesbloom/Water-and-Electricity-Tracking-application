# Water and Electricity Tracking Application (GridSync)

A web application built with PHP, MySQL, JavaScript, and Tailwind CSS to help users track and visualize their water and electricity consumption.

## Description

GridSync allows users to register, log in, and input their daily or periodic water and electricity meter readings. The application provides a dashboard with visualizations, goal setting features, user profile management, and informational pages. It features a modern, themeable interface with various animations and interactive elements.

## Features

* **User Authentication:** Secure Sign Up, Sign In, and Logout functionality using password hashing.
* **Dashboard (`homepage.php`):**
    * Displays current day's simulated electricity and water usage.
    * Interactive goal setting for daily electricity and water usage (saved in localStorage).
    * Visual progress tracking (doughnut chart for electricity goal, animated water level for water usage).
    * Usage distribution bar chart (placeholder data).
    * Historical usage line charts (placeholder data).
    * Swipeable Alerts section (Usage Alerts, Service Alerts, Tips).
* **Add Usage (`add_usage.php`):** Forms to submit new electricity and water readings with date and optional notes.
* **Profile Management (`profile.php`):**
    * View current profile information.
    * Update username.
    * Change password (requires current password verification).
    * Upload and update profile picture (stored in `htdocs/asset/uploads/`).
    * Accordion UI for settings sections.
* **Statistics (`Statistics.php`):** Interactive 3D globe visualization displaying global usage data placeholders (using Three.js).
* **Contact & Feedback (`contact.php`):** Dynamic form allowing users to switch between sending a contact message or submitting feedback.
* **News Page (`news.php`):** Displays placeholder news articles in a responsive grid layout.
* **Theming:** Light and Dark mode support, toggled via profile dropdown and saved using localStorage.
* **Styling & Animation:**
    * Styled using Tailwind CSS utility classes and custom CSS.
    * Scroll-triggered animations for content sections.
    * Animated hover effects on cards (including animated borders in dark mode).
    * Animated footer graphic.

## Project Structure
/Water-and-Electricity-Tracking-application
├── /config
│   ├── database.php     # Database connection setup
│   └── schema.sql       # SQL schema for database tables (users, usage_readings)
│
├── /htdocs              # Web server document root
│   ├── index.php        # Front Controller (handles routing)
│   ├── /asset
│   │   ├── logo.png     # Application logo
│   │   └── /uploads/    # Directory for profile picture uploads
│   ├── /JavaScripts
│   │   ├── auth_script.js
│   │   ├── chartsAndAnimations.js # Homepage charts & animations
│   │   ├── contact_script.js    # JS for contact page form switching (if needed)
│   │   ├── dynamic.js           # Theme toggle, dropdowns
│   │   ├── partials_script.js   # Scroll animations, footer logic
│   │   └── statistics_script.js # Three.js globe logic
│   ├── /Styles
│   │   ├── add_usage_styles.css
│   │   ├── auth_styles.css      # Signin/Signup blob styles
│   │   ├── homepage_styling.css # Card hover effects, etc.
│   │   ├── partials_styling.css # Footer styles
│   │   └── statistics_styles.css# Globe page styles
│   └── /views
│       ├── add_usage.php
│       ├── contact.php
│       ├── electTodayUse.php    # Placeholder detail page
│       ├── homepage.php
│       ├── news.php
│       ├── profile.php
│       ├── signin.php
│       ├── signup.php
│       ├── Statistics.php
│       ├── waterTodayUse.php    # Placeholder detail page
│       └── /partials
│           └── footer.php       # Shared footer component
│
├── /src
│   ├── /controllers
│   │   ├── AuthController.php   # Handles auth & profile logic
│   │   └── UsageController.php  # Handles saving usage data
│   ├── input.css          # Tailwind source input
│   └── output.css         # Tailwind compiled output
│
├── package.json         # NPM dependencies (Tailwind)
├── package-lock.json    # NPM lock file
├── tailwind.config.js   # Tailwind CSS configuration (if used)
├── README.md            # This file
└── # Other config files (postcss.config.js, etc.)

## Setup & Installation

1.  **Clone Repository:** `git clone https://github.com/ashesbloom/Water-and-Electricity-Tracking-application.git`
2.  **Database Setup:**
    * Create a MySQL database (e.g., `project_db`).
    * Import the table structure using the `config/schema.sql` file. Make sure to add the `usage_readings` table definition if it's not already in your schema file.
    * Update the database credentials (`$dbName`, `$dbUser`, `$dbPass`) in `config/database.php` if they differ from the defaults (`project_db`, `root`, '').
3.  **Web Server Configuration:**
    * Set up a web server (like Apache via XAMPP, MAMP, or LAMP).
    * Configure the server's document root to point to the `/htdocs` directory of the project.
    * **Routing:** Configure URL rewriting (e.g., using Apache's `.htaccess` or virtual host settings) to direct all requests through `htdocs/index.php`. Ensure the `BASE_URL_PATH` constant defined in `htdocs/index.php` (e.g., `/tracker`) matches your server configuration (like an Apache Alias).
4.  **Directory Permissions:** Ensure the `htdocs/asset/uploads/` directory exists and is writable by the web server process (e.g., `www-data`, `apache`) to allow profile picture uploads.
5.  **Tailwind CSS (Optional):** If you modify `src/input.css` or Tailwind configuration, you may need to rebuild the `src/output.css` file. Install dependencies (`npm install`) and run the appropriate build command (e.g., `npx tailwindcss -i ./src/input.css -o ./src/output.css --watch`). The current `output.css` should work as is.
6.  **Access:** Open your web browser and navigate to the URL configured in your web server (e.g., `http://localhost/tracker/`).

## Technologies Used

* **Backend:** PHP
* **Database:** MySQL (using PDO)
* **Frontend:** HTML, Tailwind CSS, JavaScript
* **Libraries:** Chart.js, Three.js

## To Do / Future Enhancements

* Implement backend logic for the Contact/Feedback form submission.
* Fetch real data for News, Statistics Globe, and historical charts.
* Add detailed usage breakdown pages (`electTodayUse.php`, `waterTodayUse.php`).
* Implement password recovery ("Forgot Password?").
* Add more robust input validation and error handling.
* Refine UI/UX and add more sophisticated animations/transitions.
* Implement unit/integration tests.

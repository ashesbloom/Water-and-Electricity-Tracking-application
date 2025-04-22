# GridSync

A web application for AI‑enhanced tracking of water (L) and electricity (kWh) usage. GridSync helps users log daily readings, visualize consumption patterns, and receive insights to optimize resource use.

## Table of Contents
1. [Overview](#overview)
2. [Core Features](#core-features)
3. [Technology Stack](#technology-stack)
4. [Project Structure](#project-structure)
5. [Installation & Setup](#installation--setup)
6. [Operational Guide](#operational-guide)
7. [Future Enhancements](#future-enhancements)
8. [Contribution Guidelines](#contribution-guidelines)

## Overview
GridSync unifies manual data entry with AI‑driven recommendations, promoting cost savings and sustainability through detailed consumption insights.

## Core Features
- **Authentication:** Secure registration, login, hashed passwords, session management.  
- **Usage Input:** Timestamped water and electricity readings via `add_usage.php`.  
- **Dashboard:** Interactive charts (Chart.js) showing hourly/daily usage (`electTodayUse.php`, `waterTodayUse.php`).  
- **Profile Management:** Username/password updates and avatar uploads (`profile.php` → `htdocs/asset/uploads/`).  
- **AI Assistant:** Chat interface backed by Node.js for usage analysis (`chatbot.php`, `ai_backend/`).  
- **Theming:** Light/dark mode toggle stored in `localStorage`.  
- **3D Globe:** Placeholder Three.js visualization in `Statistics.php`.  
- **MVC‑Style Backend:** Controllers in `src/controllers/` for maintainable code.

## Technology Stack
- **Backend (PHP):** PHP 8.x, PDO, MySQL/MariaDB, Composer (optional)  
- **AI Backend:** Node.js 16+, Express, dotenv, OpenAI (or equivalent)  
- **Frontend:** HTML5, Tailwind 3.x, Vanilla JS (ES6+), Chart.js, Three.js  
- **Build Tools:** npm, Tailwind CLI  
- **Server:** Apache/Nginx with URL rewriting

## Project Structure
```
. ├── ai_backend/ # Node.js AI chatbot service
│ ├── server.js
│ ├── package.json
│ └── .env.example # env template for API keys & PORT
├── config/ # Global configuration
│ ├── database.php # PDO connection setup
│ └── schema.sql # users & usage_records tables
├── htdocs/ # Public web root
│ ├── index.php # Front controller
│ ├── asset/ # Static assets
│ │ ├── logo.png
│ │ └── uploads/ # User‐uploaded avatars
│ ├── views/ # PHP templates
│ │ ├── partials/ # headers, footers, etc.
│ │ └── *.php # page‑specific files
│ ├── js/ # Frontend scripts
│ ├── css/ # Compiled CSS (Tailwind output)
│ └── .htaccess # URL rewrite rules
├── src/ # Application source (MVC)
│ ├── controllers/ # Request handlers
│ ├── models/ # Data layer (optional)
│ ├── input.css # Tailwind source
│ └── output.css # Tailwind build artifact
├── vendor/ # Composer dependencies
├── composer.json # PHP package manifest
├── package.json # Node.js/Tailwind manifest
└── README.md # Project documentation
```

# Installation & Setup
### Clone and PHP backend
```bash
git clone https://github.com/your-username/gridsync.git
cd gridsync
composer install                     # if needed
mysql -u user -p gridsync_db < config/schema.sql
```
### AI backend
```bash
cd ai_backend
npm install
cp .env.example .env                # configure API_KEY and PORT
npm start
```
### Tailwind CSS (optional)
```bash
npm install                         # in project root
npx tailwindcss -i ./src/input.css -o ./src/output.css --watch
```
### Permissions
```bash
chmod -R 755 htdocs/asset/uploads/
```
## Operational Guide

- **Register** at `/signup` and **Login** at `/signin`.
- **Add Usage**: Use the *Add Usage* form to log daily usage readings.
- **Dashboard**: Visit `/homepage` or *Statistics* for graphical usage data.
- **Chatbot**: Chat with the AI via `/chatbot` for smart energy insights.
- **Profile**: Update your user info on `/profile`.
- **Theme**: Toggle light/dark mode from the header.

---

## Future Enhancements

- Bind AI chatbot to actual usage data for tailored insights.
- Implement backend logic for *Contact* and *Feedback* forms.
- Fetch live news or updates from external APIs.
- Replace placeholder globes with live energy or usage stats.
- Build detailed usage breakdown and goal tracking pages.
- Add password recovery via email or OTP.
- Improve validation, error handling, and testing workflows.
- Persist goal-setting data in the database for continuity.

---

## Contribution Guidelines

We welcome contributions from the community!

- Fork the repository.
- Create a new feature branch.
- Open a pull request describing your changes.
- Report bugs or suggest features through the [issue tracker](../../issues).

Thanks for helping us improve!

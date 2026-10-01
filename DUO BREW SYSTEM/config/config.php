<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Main Configuration File
 */

// Enable Output Buffering to prevent "headers already sent" errors
ob_start();

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Base URL (change this in production)
define('BASE_URL', 'http://localhost/DUO%20BREW%20SYSTEM/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('POS_URL', BASE_URL . 'pos/');
define('SELF_ORDER_URL', BASE_URL . 'self-order/');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'duo_brew_db');

// Business Information
define('BUSINESS_NAME', 'Duo Brew');
define('BUSINESS_TAGLINE', 'Your Daily Brew');
define('BUSINESS_ADDRESS', '123 Brew Street, Coffee City');
define('BUSINESS_CONTACT', '0917-123-4567');
define('BUSINESS_EMAIL', 'hello@duobrew.com');

// Application Settings
define('ITEMS_PER_PAGE', 20);
define('LOW_STOCK_THRESHOLD', 10);
define('ORDER_PREFIX', 'DB-');
define('CURRENCY_SYMBOL', '₱');

// Session Settings
define('SESSION_TIMEOUT', 3600); // 1 hour

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
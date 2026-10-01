<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Logout Page
 */

require_once __DIR__ . '/../config/config.php';

// Log activity if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/functions.php';
    logActivity('Logout', 'User logged out');
}

// Destroy session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . 'auth/login.php');
exit;


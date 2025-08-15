<?php
require_once 'config/config.php';

// Log logout activity
if (isLoggedIn()) {
    logActivity('LOGOUT', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>

<?php
require_once 'config/config.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to login page
header('Location: login.php');
exit();
?>

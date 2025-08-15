<?php
/**
 * Main Configuration File for Blood Bank Management System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Application settings
define('APP_NAME', 'Blood Bank Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/bloodbank/');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Blood bank specific settings
define('MIN_DONATION_AGE', 18);
define('MAX_DONATION_AGE', 65);
define('MIN_WEIGHT_KG', 50);
define('MIN_HEMOGLOBIN_MALE', 12.5);
define('MIN_HEMOGLOBIN_FEMALE', 12.0);
define('DONATION_INTERVAL_DAYS', 90);

// Blood component expiry days
define('WHOLE_BLOOD_EXPIRY_DAYS', 35);
define('RBC_EXPIRY_DAYS', 42);
define('PLASMA_EXPIRY_DAYS', 365);
define('PLATELETS_EXPIRY_DAYS', 5);
define('CRYOPRECIPITATE_EXPIRY_DAYS', 365);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Utility functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isAdmin() {
    return hasRole('admin');
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'Unknown User';
}

function generateId($prefix = '') {
    return $prefix . date('Ymd') . sprintf('%04d', rand(1, 9999));
}

function formatDate($date, $format = 'd-m-Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd-m-Y H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    return $today->diff($birth)->y;
}

function canDonate($lastDonationDate) {
    if (empty($lastDonationDate)) return true;
    
    $lastDonation = new DateTime($lastDonationDate);
    $today = new DateTime();
    $daysSinceLastDonation = $today->diff($lastDonation)->days;
    
    return $daysSinceLastDonation >= DONATION_INTERVAL_DAYS;
}

function getBloodCompatibility($bloodGroup) {
    $compatibility = [
        'A+' => ['A+', 'AB+'],
        'A-' => ['A+', 'A-', 'AB+', 'AB-'],
        'B+' => ['B+', 'AB+'],
        'B-' => ['B+', 'B-', 'AB+', 'AB-'],
        'AB+' => ['AB+'],
        'AB-' => ['AB+', 'AB-'],
        'O+' => ['A+', 'B+', 'AB+', 'O+'],
        'O-' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
    ];
    
    return $compatibility[$bloodGroup] ?? [];
}

function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function logActivity($action, $details = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([getCurrentUserId(), $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Duplicate functions removed - already declared above

// Error handling
function handleError($message, $redirect = null) {
    $_SESSION['error'] = $message;
    if ($redirect) {
        header("Location: $redirect");
        exit();
    }
}

function handleSuccess($message, $redirect = null) {
    $_SESSION['success'] = $message;
    if ($redirect) {
        header("Location: $redirect");
        exit();
    }
}

function getFlashMessage($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}

// Check session timeout
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
}

// Update last activity time
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}
?>

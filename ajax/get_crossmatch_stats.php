<?php
// Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    // Handle different include paths
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Get today's crossmatch statistics
    $stats = [];
    
    // Compatible tests today
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM cross_matching 
        WHERE crossmatch_date = CURDATE() AND result = 'compatible'
    ");
    $stats['compatible_today'] = $stmt->fetch()['count'];
    
    // Incompatible tests today
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM cross_matching 
        WHERE crossmatch_date = CURDATE() AND result = 'incompatible'
    ");
    $stats['incompatible_today'] = $stmt->fetch()['count'];
    
    // Total tests (all time)
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM cross_matching 
        WHERE result != 'pending'
    ");
    $stats['total_tests'] = $stmt->fetch()['count'];
    
    // Pending tests
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM cross_matching 
        WHERE result = 'pending'
    ");
    $stats['pending_tests'] = $stmt->fetch()['count'];
    
    sendJsonResponse([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Get crossmatch stats error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while loading crossmatch statistics.'], 500);
}
?>

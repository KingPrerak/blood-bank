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
    
    // Get inventory summary
    $summary = [];
    
    // Available units
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM blood_inventory 
        WHERE status = 'available' AND expiry_date > CURDATE()
    ");
    $summary['available'] = $stmt->fetch()['count'];
    
    // Expiring soon (within 7 days)
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM blood_inventory 
        WHERE status = 'available' 
        AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $summary['expiring_soon'] = $stmt->fetch()['count'];
    
    // Expired units
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM blood_inventory 
        WHERE status = 'expired' OR (status = 'available' AND expiry_date < CURDATE())
    ");
    $summary['expired'] = $stmt->fetch()['count'];
    
    // Issued units
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM blood_inventory 
        WHERE status = 'issued'
    ");
    $summary['issued'] = $stmt->fetch()['count'];
    
    sendJsonResponse([
        'success' => true,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    error_log("Get inventory summary error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Error loading inventory summary.'], 500);
}
?>

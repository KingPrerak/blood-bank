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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();
    
    // Get low stock threshold from settings
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'low_stock_threshold'");
    $threshold = $stmt->fetch()['setting_value'] ?? 5;
    
    // Find blood groups with low stock
    $stmt = $db->prepare("
        SELECT bg.blood_group, COUNT(bi.id) as units
        FROM blood_groups bg 
        LEFT JOIN blood_inventory bi ON bg.id = bi.blood_group_id 
            AND bi.status = 'available' 
            AND bi.expiry_date > CURDATE()
        GROUP BY bg.id, bg.blood_group 
        HAVING units < ?
        ORDER BY units ASC
    ");
    $stmt->execute([$threshold]);
    $lowStockGroups = $stmt->fetchAll();
    
    $alertsCreated = 0;
    
    if (!empty($lowStockGroups)) {
        foreach ($lowStockGroups as $group) {
            // Check if alert already exists for this blood group
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE type = 'low_stock' 
                AND message LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute(["%{$group['blood_group']}%"]);
            $existingAlert = $stmt->fetch()['count'];
            
            if ($existingAlert == 0) {
                // Create low stock alert
                $stmt = $db->prepare("
                    INSERT INTO notifications (type, title, message, priority, action_required, created_at)
                    VALUES ('low_stock', 'Low Blood Stock Alert', ?, 'high', TRUE, NOW())
                ");
                
                $message = "Blood group {$group['blood_group']} has low stock: only {$group['units']} unit(s) available (threshold: $threshold units)";
                $stmt->execute([$message]);
                
                $alertsCreated++;
            }
        }
    }
    
    // Log activity
    if ($alertsCreated > 0) {
        logActivity('LOW_STOCK_ALERTS_GENERATED', "Generated $alertsCreated low stock alerts");
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => "Generated $alertsCreated new low stock alerts for blood groups below $threshold units.",
        'alerts_created' => $alertsCreated,
        'low_stock_groups' => count($lowStockGroups)
    ]);
    
} catch (Exception $e) {
    error_log("Check low stock error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while checking low stock.'], 500);
}
?>

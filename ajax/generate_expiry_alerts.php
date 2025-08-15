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
    
    // Get expiry alert days from settings
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'expiry_alert_days'");
    $alertDays = $stmt->fetch()['setting_value'] ?? 7;
    
    // Find units expiring within alert period
    $stmt = $db->prepare("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, ' ', d.last_name) as donor_name,
               DATEDIFF(bi.expiry_date, CURDATE()) as days_to_expiry
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.status = 'available' 
        AND bi.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY bi.expiry_date ASC
    ");
    $stmt->execute([$alertDays]);
    $expiringUnits = $stmt->fetchAll();
    
    $alertsCreated = 0;
    
    if (!empty($expiringUnits)) {
        foreach ($expiringUnits as $unit) {
            // Check if alert already exists for this unit
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE type = 'expiry_alert' 
                AND message LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute(["%{$unit['bag_number']}%"]);
            $existingAlert = $stmt->fetch()['count'];
            
            if ($existingAlert == 0) {
                // Create expiry alert
                $stmt = $db->prepare("
                    INSERT INTO notifications (type, title, message, priority, action_required, created_at)
                    VALUES ('expiry_alert', 'Blood Unit Expiring Soon', ?, 'high', TRUE, NOW())
                ");
                
                $message = "Blood bag {$unit['bag_number']} ({$unit['blood_group']}) will expire in {$unit['days_to_expiry']} day(s) on " . formatDate($unit['expiry_date']);
                $stmt->execute([$message]);
                
                $alertsCreated++;
            }
        }
    }
    
    // Log activity
    if ($alertsCreated > 0) {
        logActivity('EXPIRY_ALERTS_GENERATED', "Generated $alertsCreated expiry alerts");
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => "Generated $alertsCreated new expiry alerts for units expiring within $alertDays days.",
        'alerts_created' => $alertsCreated,
        'units_expiring' => count($expiringUnits)
    ]);
    
} catch (Exception $e) {
    error_log("Generate expiry alerts error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while generating expiry alerts.'], 500);
}
?>

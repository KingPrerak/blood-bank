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
    
    // Get all expired units that are still marked as available
    $stmt = $db->prepare("
        SELECT id, bag_number, expiry_date 
        FROM blood_inventory 
        WHERE expiry_date < CURDATE() AND status = 'available'
    ");
    $stmt->execute();
    $expiredUnits = $stmt->fetchAll();
    
    if (empty($expiredUnits)) {
        sendJsonResponse(['success' => true, 'message' => 'No expired units found to mark.']);
    }
    
    $db->beginTransaction();
    
    try {
        // Update status to expired
        $stmt = $db->prepare("
            UPDATE blood_inventory 
            SET status = 'expired', updated_at = NOW() 
            WHERE expiry_date < CURDATE() AND status = 'available'
        ");
        $stmt->execute();
        $updatedCount = $stmt->rowCount();
        
        // Create notifications for expired units
        foreach ($expiredUnits as $unit) {
            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (type, title, message, priority, created_at)
                VALUES ('expiry_alert', 'Blood Unit Expired', ?, 'high', NOW())
            ");
            $message = "Blood bag {$unit['bag_number']} expired on " . formatDate($unit['expiry_date']) . " and has been marked as expired.";
            $stmt->execute([$message]);
            
            // Log wastage
            $stmt = $db->prepare("
                INSERT INTO blood_wastage (bag_id, wastage_type, wastage_date, quantity_wasted, reported_by, created_at)
                SELECT id, 'expired', CURDATE(), volume_ml, ?, NOW()
                FROM blood_inventory 
                WHERE id = ?
            ");
            $stmt->execute([getCurrentUserId(), $unit['id']]);
        }
        
        // Log activity
        logActivity('EXPIRED_UNITS_MARKED', "Marked $updatedCount expired blood units");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true, 
            'message' => "Successfully marked $updatedCount blood units as expired.",
            'count' => $updatedCount
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Mark expired error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while marking expired units.'], 500);
}
?>

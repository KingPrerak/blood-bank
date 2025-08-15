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
        SELECT id, bag_number, expiry_date, volume_ml
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
        $updatedCount = 0;
        $totalWastage = 0;
        
        foreach ($expiredUnits as $unit) {
            // Update status to expired
            $stmt = $db->prepare("UPDATE blood_inventory SET status = 'expired', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$unit['id']]);
            
            // Record wastage
            $costImpact = 1500; // Average cost per unit
            $stmt = $db->prepare("
                INSERT INTO blood_wastage (
                    bag_id, wastage_type, wastage_date, quantity_wasted, 
                    cost_impact, reported_by, created_at
                ) VALUES (?, 'expired', CURDATE(), ?, ?, ?, NOW())
            ");
            $stmt->execute([$unit['id'], $unit['volume_ml'], $costImpact, getCurrentUserId()]);
            
            $updatedCount++;
            $totalWastage += $costImpact;
        }
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, created_at)
            VALUES ('expiry_alert', 'Expired Units Marked', ?, 'high', NOW())
        ");
        $message = "Marked $updatedCount blood units as expired. Total wastage cost: ₹" . number_format($totalWastage);
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('EXPIRED_UNITS_MARKED', "Marked $updatedCount expired blood units");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Successfully marked $updatedCount blood units as expired.",
            'count' => $updatedCount,
            'wastage_cost' => $totalWastage
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Mark expired units error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while marking expired units.'], 500);
}
?>

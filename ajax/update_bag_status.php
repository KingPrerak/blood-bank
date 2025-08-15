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
    
    $bagId = (int)$_POST['bag_id'];
    $newStatus = $_POST['new_status'];
    $reason = sanitizeInput($_POST['reason']);
    
    if (empty($bagId) || empty($newStatus)) {
        sendJsonResponse(['success' => false, 'message' => 'Bag ID and new status are required'], 400);
    }
    
    $validStatuses = ['available', 'expired', 'discarded', 'quarantined', 'testing'];
    if (!in_array($newStatus, $validStatuses)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
    }
    
    // Get bag information
    $stmt = $db->prepare("
        SELECT bi.*, bg.blood_group 
        FROM blood_inventory bi 
        JOIN blood_groups bg ON bi.blood_group_id = bg.id 
        WHERE bi.id = ?
    ");
    $stmt->execute([$bagId]);
    $bag = $stmt->fetch();
    
    if (!$bag) {
        sendJsonResponse(['success' => false, 'message' => 'Blood bag not found'], 404);
    }
    
    $db->beginTransaction();
    
    try {
        // Update bag status
        $stmt = $db->prepare("UPDATE blood_inventory SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $bagId]);
        
        // If status is expired or discarded, record wastage
        if (in_array($newStatus, ['expired', 'discarded'])) {
            $wastageType = $newStatus;
            $costImpact = 1500; // Average cost per unit
            
            $stmt = $db->prepare("
                INSERT INTO blood_wastage (
                    bag_id, wastage_type, wastage_date, quantity_wasted, 
                    cost_impact, reported_by, created_at
                ) VALUES (?, ?, CURDATE(), ?, ?, ?, NOW())
            ");
            $stmt->execute([$bagId, $wastageType, $bag['volume_ml'], $costImpact, getCurrentUserId()]);
        }
        
        // If status is quarantined, create quarantine record
        if ($newStatus === 'quarantined') {
            $stmt = $db->prepare("
                INSERT INTO blood_quarantine (
                    bag_id, quarantine_reason, quarantine_date, quarantined_by, status, created_at
                ) VALUES (?, ?, CURDATE(), ?, 'quarantined', NOW())
            ");
            $stmt->execute([$bagId, $reason ?: 'Quality control check', getCurrentUserId()]);
        }
        
        // Create notification for critical status changes
        if (in_array($newStatus, ['expired', 'discarded'])) {
            $stmt = $db->prepare("
                INSERT INTO notifications (type, title, message, priority, created_at)
                VALUES ('system_alert', 'Blood Unit Status Changed', ?, 'medium', NOW())
            ");
            $message = "Blood bag {$bag['bag_number']} status changed to $newStatus";
            if ($reason) {
                $message .= ". Reason: $reason";
            }
            $stmt->execute([$message]);
        }
        
        // Log activity
        logActivity('BAG_STATUS_UPDATED', "Blood bag {$bag['bag_number']} status changed to $newStatus");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood bag {$bag['bag_number']} status updated to $newStatus successfully."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Update bag status error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while updating bag status.'], 500);
}
?>

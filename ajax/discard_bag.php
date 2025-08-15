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
    $reason = sanitizeInput($_POST['reason'] ?? 'Manual discard');
    
    if (empty($bagId)) {
        sendJsonResponse(['success' => false, 'message' => 'Bag ID is required'], 400);
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
    
    if ($bag['status'] === 'discarded') {
        sendJsonResponse(['success' => false, 'message' => 'Blood bag is already discarded'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Update bag status
        $stmt = $db->prepare("
            UPDATE blood_inventory 
            SET status = 'discarded', disposal_date = CURDATE(), disposal_reason = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reason, $bagId]);
        
        // Create disposal record
        $stmt = $db->prepare("
            INSERT INTO blood_disposals (
                bag_id, disposal_reason, disposal_date, disposal_method, 
                disposed_by, notes, created_at
            ) VALUES (?, 'other', CURDATE(), 'manual_discard', ?, ?, NOW())
        ");
        $stmt->execute([$bagId, getCurrentUserId(), $reason]);
        
        // Record wastage
        $costImpact = 1500; // Average cost per unit
        $stmt = $db->prepare("
            INSERT INTO blood_wastage (
                bag_id, wastage_type, wastage_date, quantity_wasted, 
                cost_impact, reported_by, created_at
            ) VALUES (?, 'other', CURDATE(), ?, ?, ?, NOW())
        ");
        $stmt->execute([$bagId, $bag['volume_ml'], $costImpact, getCurrentUserId()]);
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, created_at)
            VALUES ('system_alert', 'Blood Unit Discarded', ?, 'medium', NOW())
        ");
        $message = "Blood bag {$bag['bag_number']} ({$bag['blood_group']}) has been discarded. Reason: $reason";
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('BAG_DISCARDED', "Blood bag {$bag['bag_number']} discarded: $reason");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood bag {$bag['bag_number']} has been successfully discarded."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Discard bag error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while discarding the blood bag.'], 500);
}
?>

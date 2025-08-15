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
    
    if ($bag['status'] !== 'available') {
        sendJsonResponse(['success' => false, 'message' => 'Only available blood bags can be prioritized'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Create notification for prioritized bag
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, action_required, created_at)
            VALUES ('system_alert', 'Blood Unit Prioritized', ?, 'high', TRUE, NOW())
        ");
        
        $message = "Blood bag {$bag['bag_number']} ({$bag['blood_group']}) has been prioritized for immediate issue due to approaching expiry.";
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('BAG_PRIORITIZED', "Blood bag {$bag['bag_number']} prioritized for immediate issue");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood bag {$bag['bag_number']} has been prioritized for immediate issue."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Prioritize bag error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while prioritizing the blood bag.'], 500);
}
?>

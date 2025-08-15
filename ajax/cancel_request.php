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
    
    $requestId = (int)$_POST['request_id'];
    $reason = sanitizeInput($_POST['reason']);
    
    if (empty($requestId) || empty($reason)) {
        sendJsonResponse(['success' => false, 'message' => 'Request ID and reason are required'], 400);
    }
    
    // Get request information
    $stmt = $db->prepare("
        SELECT br.*, bg.blood_group 
        FROM blood_requests br 
        JOIN blood_groups bg ON br.blood_group_id = bg.id 
        WHERE br.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        sendJsonResponse(['success' => false, 'message' => 'Blood request not found'], 404);
    }
    
    if ($request['status'] === 'cancelled') {
        sendJsonResponse(['success' => false, 'message' => 'Request is already cancelled'], 400);
    }
    
    if ($request['status'] === 'fulfilled') {
        sendJsonResponse(['success' => false, 'message' => 'Cannot cancel a fulfilled request'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Update request status
        $stmt = $db->prepare("UPDATE blood_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, created_at)
            VALUES ('system_alert', 'Blood Request Cancelled', ?, 'medium', NOW())
        ");
        $message = "Blood request {$request['request_id']} for {$request['patient_name']} has been cancelled. Reason: $reason";
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('REQUEST_CANCELLED', "Blood request {$request['request_id']} cancelled: $reason");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood request {$request['request_id']} has been cancelled successfully."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Cancel request error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while cancelling the request.'], 500);
}
?>

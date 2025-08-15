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
    
    if (empty($requestId)) {
        sendJsonResponse(['success' => false, 'message' => 'Request ID is required'], 400);
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
    
    if ($request['status'] !== 'pending') {
        sendJsonResponse(['success' => false, 'message' => 'Only pending requests can be approved'], 400);
    }
    
    // Check blood availability
    $stmt = $db->prepare("
        SELECT COUNT(*) as available_units 
        FROM blood_inventory 
        WHERE blood_group_id = ? AND component_type = ? 
        AND status = 'available' AND expiry_date > CURDATE()
    ");
    $stmt->execute([$request['blood_group_id'], $request['component_type']]);
    $availability = $stmt->fetch();
    
    if ($availability['available_units'] < $request['units_required']) {
        sendJsonResponse([
            'success' => false, 
            'message' => "Insufficient blood units available. Required: {$request['units_required']}, Available: {$availability['available_units']}"
        ], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Update request status
        $stmt = $db->prepare("UPDATE blood_requests SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, created_at)
            VALUES ('system_alert', 'Blood Request Approved', ?, 'medium', NOW())
        ");
        $message = "Blood request {$request['request_id']} for {$request['patient_name']} has been approved.";
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('REQUEST_APPROVED', "Blood request {$request['request_id']} approved");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood request {$request['request_id']} has been approved successfully."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Approve request error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while approving the request.'], 500);
}
?>

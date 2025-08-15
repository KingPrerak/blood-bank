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
    
    // Get and validate input data
    $bagId = (int)$_POST['bag_id'];
    $disposalReason = $_POST['disposal_reason'];
    $disposalMethod = $_POST['disposal_method'];
    $disposalLocation = sanitizeInput($_POST['disposal_location']);
    $certificateNo = sanitizeInput($_POST['disposal_certificate_no']);
    $notes = sanitizeInput($_POST['notes']);
    $requireApproval = isset($_POST['require_approval']);
    
    // Validation
    $errors = [];
    
    if (empty($bagId)) $errors[] = 'Bag ID is required';
    if (empty($disposalReason)) $errors[] = 'Disposal reason is required';
    if (empty($disposalMethod)) $errors[] = 'Disposal method is required';
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    // Get bag information
    $stmt = $db->prepare("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, ' ', d.last_name) as donor_name
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.id = ?
    ");
    $stmt->execute([$bagId]);
    $bag = $stmt->fetch();
    
    if (!$bag) {
        sendJsonResponse(['success' => false, 'message' => 'Blood bag not found'], 404);
    }
    
    // Check if already disposed
    if ($bag['status'] === 'discarded') {
        sendJsonResponse(['success' => false, 'message' => 'Blood bag is already disposed'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Update bag status
        $stmt = $db->prepare("
            UPDATE blood_inventory 
            SET status = 'discarded', disposal_date = CURDATE(), disposal_reason = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$disposalReason, $bagId]);
        
        // Create disposal record
        $stmt = $db->prepare("
            INSERT INTO blood_disposals (
                bag_id, disposal_reason, disposal_date, disposal_method, 
                disposal_location, disposed_by, disposal_certificate_no, notes, created_at
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $bagId, $disposalReason, $disposalMethod, $disposalLocation, 
            getCurrentUserId(), $certificateNo, $notes
        ]);
        
        // Calculate cost impact (approximate cost per unit)
        $costPerUnit = 1500; // Average cost per blood unit in INR
        $costImpact = $costPerUnit;
        
        // Record wastage
        $stmt = $db->prepare("
            INSERT INTO blood_wastage (
                bag_id, wastage_type, wastage_date, quantity_wasted, 
                cost_impact, reported_by, created_at
            ) VALUES (?, ?, CURDATE(), ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $bagId, $disposalReason, $bag['volume_ml'], $costImpact, getCurrentUserId()
        ]);
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (type, title, message, priority, created_at)
            VALUES ('system_alert', 'Blood Unit Disposed', ?, 'medium', NOW())
        ");
        $message = "Blood bag {$bag['bag_number']} ({$bag['blood_group']}) has been disposed due to: " . str_replace('_', ' ', $disposalReason);
        $stmt->execute([$message]);
        
        // Log activity
        logActivity('BLOOD_UNIT_DISPOSED', "Blood bag {$bag['bag_number']} disposed - Reason: $disposalReason");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Blood unit {$bag['bag_number']} has been successfully disposed.",
            'disposal_id' => $db->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Dispose blood unit error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while disposing the blood unit.'], 500);
}
?>

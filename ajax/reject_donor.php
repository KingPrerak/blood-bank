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
    
    $donorId = (int)$_POST['donor_id'];
    $reason = sanitizeInput($_POST['reason']);
    
    if (empty($donorId) || empty($reason)) {
        sendJsonResponse(['success' => false, 'message' => 'Donor ID and reason are required'], 400);
    }
    
    // Get donor information
    $stmt = $db->prepare("SELECT * FROM donors WHERE id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        sendJsonResponse(['success' => false, 'message' => 'Donor not found'], 404);
    }
    
    $db->beginTransaction();
    
    try {
        // Create deferral record (permanent rejection)
        $stmt = $db->prepare("
            INSERT INTO donor_deferrals (donor_id, deferral_reason, deferral_type, deferral_date, created_by, created_at)
            VALUES (?, ?, 'permanent', CURDATE(), ?, NOW())
        ");
        $stmt->execute([$donorId, $reason, getCurrentUserId()]);
        
        // Update donor status
        $stmt = $db->prepare("UPDATE donors SET status = 'blacklisted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$donorId]);
        
        // Log activity
        logActivity('DONOR_REJECTED', "Donor {$donor['donor_id']} rejected: $reason");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Donor {$donor['donor_id']} has been permanently rejected."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Reject donor error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while rejecting the donor.'], 500);
}
?>

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
    
    if (empty($donorId)) {
        sendJsonResponse(['success' => false, 'message' => 'Donor ID is required'], 400);
    }
    
    // Update donor's last donation date and increment total donations
    $stmt = $db->prepare("
        UPDATE donors 
        SET last_donation_date = CURDATE(), 
            total_donations = total_donations + 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($stmt->execute([$donorId])) {
        logActivity('DONOR_UPDATED', "Updated last donation date for donor ID: $donorId");
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Donor information updated successfully'
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Failed to update donor information'], 500);
    }
    
} catch (Exception $e) {
    error_log("Update donor last donation error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while updating donor information.'], 500);
}
?>

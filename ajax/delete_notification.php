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
    
    $notificationId = (int)$_POST['notification_id'];
    
    if (empty($notificationId)) {
        sendJsonResponse(['success' => false, 'message' => 'Notification ID is required'], 400);
    }
    
    // Delete notification (only if it belongs to current user or is global)
    $stmt = $db->prepare("
        DELETE FROM notifications 
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$notificationId, getCurrentUserId()]);
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Notification not found or cannot be deleted.'], 404);
    }
    
} catch (Exception $e) {
    error_log("Delete notification error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while deleting notification.'], 500);
}
?>

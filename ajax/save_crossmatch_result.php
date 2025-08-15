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
    
    $crossmatchId = (int)$_POST['crossmatch_id'];
    $patientSampleId = sanitizeInput($_POST['patient_sample_id']);
    $performedBy = sanitizeInput($_POST['performed_by']);
    $majorCrossmatch = $_POST['major_crossmatch'];
    $minorCrossmatch = $_POST['minor_crossmatch'];
    $antibodyScreening = $_POST['antibody_screening'];
    $verifiedBy = sanitizeInput($_POST['verified_by']);
    $result = $_POST['result'];
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    $errors = [];
    if (empty($crossmatchId)) $errors[] = 'Crossmatch ID is required';
    if (empty($performedBy)) $errors[] = 'Performed by is required';
    if (empty($majorCrossmatch)) $errors[] = 'Major crossmatch result is required';
    if (empty($minorCrossmatch)) $errors[] = 'Minor crossmatch result is required';
    if (empty($antibodyScreening)) $errors[] = 'Antibody screening result is required';
    if (empty($result)) $errors[] = 'Final result is required';
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    // Get crossmatch record
    $stmt = $db->prepare("SELECT * FROM cross_matching WHERE id = ?");
    $stmt->execute([$crossmatchId]);
    $crossmatch = $stmt->fetch();
    
    if (!$crossmatch) {
        sendJsonResponse(['success' => false, 'message' => 'Crossmatch record not found'], 404);
    }
    
    $db->beginTransaction();
    
    try {
        // Update crossmatch results
        $stmt = $db->prepare("
            UPDATE cross_matching 
            SET patient_sample_id = ?, major_crossmatch = ?, minor_crossmatch = ?, 
                antibody_screening = ?, performed_by = ?, verified_by = ?, 
                result = ?, notes = ?, crossmatch_date = CURDATE(), crossmatch_time = CURTIME()
            WHERE id = ?
        ");
        $stmt->execute([
            $patientSampleId, $majorCrossmatch, $minorCrossmatch, $antibodyScreening,
            $performedBy, $verifiedBy, $result, $notes, $crossmatchId
        ]);
        
        // If compatible, allow blood issue
        if ($result === 'compatible') {
            // Update blood request to allow issue
            $stmt = $db->prepare("
                UPDATE blood_requests 
                SET status = 'approved' 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$crossmatch['request_id']]);
            
            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (type, title, message, priority, created_at)
                VALUES ('system_alert', 'Crossmatch Compatible', ?, 'high', NOW())
            ");
            $message = "Crossmatch test completed - COMPATIBLE. Blood can be issued for request.";
            $stmt->execute([$message]);
        } else {
            // Create incompatible notification
            $stmt = $db->prepare("
                INSERT INTO notifications (type, title, message, priority, created_at)
                VALUES ('system_alert', 'Crossmatch Incompatible', ?, 'critical', NOW())
            ");
            $message = "Crossmatch test completed - INCOMPATIBLE. Blood cannot be issued. Find alternative units.";
            $stmt->execute([$message]);
        }
        
        // Log activity
        logActivity('CROSSMATCH_COMPLETED', "Crossmatch test completed with result: $result");
        
        $db->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => "Crossmatch test results saved successfully. Result: " . strtoupper($result),
            'result' => $result
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Save crossmatch result error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while saving crossmatch results.'], 500);
}
?>

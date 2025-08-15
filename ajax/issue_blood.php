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
    $selectedBags = $_POST['selected_bags'] ?? [];
    $receivedBy = sanitizeInput($_POST['received_by']);
    $purpose = sanitizeInput($_POST['purpose']);
    
    if (empty($requestId) || empty($selectedBags) || empty($receivedBy)) {
        sendJsonResponse(['success' => false, 'message' => 'Request ID, blood bags, and received by are required'], 400);
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
    
    $db->beginTransaction();
    
    try {
        $issuedCount = 0;
        
        foreach ($selectedBags as $bagId) {
            // Verify bag is available
            $stmt = $db->prepare("
                SELECT * FROM blood_inventory 
                WHERE id = ? AND status = 'available' AND expiry_date > CURDATE()
            ");
            $stmt->execute([$bagId]);
            $bag = $stmt->fetch();
            
            if (!$bag) {
                continue; // Skip unavailable bags
            }
            
            // Generate issue ID
            $issueId = generateId('ISS');
            
            // Create blood issue record
            $stmt = $db->prepare("
                INSERT INTO blood_issues (
                    issue_id, request_id, bag_id, issued_date, issued_time,
                    issued_by, received_by, hospital_name, purpose, status, created_at
                ) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?, 'issued', NOW())
            ");
            $stmt->execute([
                $issueId, $requestId, $bagId, getCurrentUserId(), 
                $receivedBy, $request['hospital_name'], $purpose
            ]);
            
            // Update bag status
            $stmt = $db->prepare("UPDATE blood_inventory SET status = 'issued', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bagId]);
            
            $issuedCount++;
        }
        
        if ($issuedCount > 0) {
            // Update request status
            if ($issuedCount >= $request['units_required']) {
                $newStatus = 'fulfilled';
            } else {
                $newStatus = 'approved'; // Partially fulfilled
            }
            
            $stmt = $db->prepare("UPDATE blood_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $requestId]);
            
            // Log activity
            logActivity('BLOOD_ISSUED', "Issued $issuedCount blood units for request {$request['request_id']}");
            
            $db->commit();
            
            sendJsonResponse([
                'success' => true,
                'message' => "Successfully issued $issuedCount blood unit(s) for request {$request['request_id']}",
                'issued_count' => $issuedCount
            ]);
        } else {
            $db->rollBack();
            sendJsonResponse(['success' => false, 'message' => 'No valid blood bags were available for issue'], 400);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Issue blood error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while issuing blood.'], 500);
}
?>

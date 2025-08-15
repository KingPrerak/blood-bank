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
    $bagNumber = sanitizeInput($_POST['bag_number']);
    $componentType = $_POST['component_type'];
    $volumeMl = (int)$_POST['volume_ml'];
    $collectionDate = $_POST['collection_date'];
    $collectionTime = $_POST['collection_time'] ?? null;
    $storageLocation = sanitizeInput($_POST['storage_location']);
    $collectionStaff = sanitizeInput($_POST['collection_staff']);
    $supervisor = sanitizeInput($_POST['supervisor'] ?? '');
    $collectionNotes = sanitizeInput($_POST['collection_notes'] ?? '');
    $status = 'draft';
    
    if (empty($donorId) || empty($bagNumber)) {
        sendJsonResponse(['success' => false, 'message' => 'Donor ID and bag number are required'], 400);
    }
    
    // Check if draft already exists
    $stmt = $db->prepare("
        SELECT id FROM collection_drafts 
        WHERE donor_id = ? AND status = 'draft'
    ");
    $stmt->execute([$donorId]);
    $existingDraft = $stmt->fetch();
    
    if ($existingDraft) {
        // Update existing draft
        $stmt = $db->prepare("
            UPDATE collection_drafts SET
                bag_number = ?, component_type = ?, volume_ml = ?, 
                collection_date = ?, collection_time = ?, storage_location = ?,
                collection_staff = ?, supervisor = ?, collection_notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $bagNumber, $componentType, $volumeMl, $collectionDate, $collectionTime,
            $storageLocation, $collectionStaff, $supervisor, $collectionNotes,
            $existingDraft['id']
        ]);
        
        $message = 'Collection draft updated successfully';
    } else {
        // Create new draft
        $stmt = $db->prepare("
            INSERT INTO collection_drafts (
                donor_id, bag_number, component_type, volume_ml,
                collection_date, collection_time, storage_location,
                collection_staff, supervisor, collection_notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
        ");
        $stmt->execute([
            $donorId, $bagNumber, $componentType, $volumeMl, $collectionDate, $collectionTime,
            $storageLocation, $collectionStaff, $supervisor, $collectionNotes
        ]);
        
        $message = 'Collection draft saved successfully';
    }
    
    logActivity('COLLECTION_DRAFT_SAVED', "Collection draft saved for donor ID: $donorId, Bag: $bagNumber");
    
    sendJsonResponse([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log("Save collection draft error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while saving the draft.'], 500);
}
?>

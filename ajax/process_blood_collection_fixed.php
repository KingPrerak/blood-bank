<?php
// Handle different include paths
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
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
    
    $donorId = (int)$_POST["donor_id"];
    $bagNumber = sanitizeInput($_POST["bag_number"]);
    $componentType = $_POST["component_type"];
    $volumeMl = (int)$_POST["volume_ml"];
    $collectionDate = $_POST["collection_date"];
    $collectionTime = $_POST["collection_time"] ?? null;
    $expiryDate = $_POST["expiry_date"] ?? null;
    $storageLocation = sanitizeInput($_POST["storage_location"]);
    $collectionStaff = sanitizeInput($_POST["collection_staff"]);
    $supervisor = sanitizeInput($_POST["supervisor"] ?? '');
    $collectionNotes = sanitizeInput($_POST["collection_notes"] ?? '');
    $status = $_POST["status"] ?? 'completed';
    
    if (empty($donorId) || empty($bagNumber) || empty($componentType) || empty($volumeMl)) {
        sendJsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    // Calculate expiry date if not provided
    if (empty($expiryDate)) {
        $expiryDays = 35; // Default for whole blood
        switch ($componentType) {
            case 'Whole Blood':
                $expiryDays = 35;
                break;
            case 'Red Blood Cells':
                $expiryDays = 42;
                break;
            case 'Plasma':
                $expiryDays = 365;
                break;
            case 'Platelets':
                $expiryDays = 5;
                break;
        }
        
        $expiryDate = date('Y-m-d', strtotime($collectionDate . " + $expiryDays days"));
    }
    
    // Get donor information
    $stmt = $db->prepare("SELECT d.*, bg.blood_group FROM donors d JOIN blood_groups bg ON d.blood_group_id = bg.id WHERE d.id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        sendJsonResponse(['success' => false, 'message' => 'Donor not found'], 404);
    }
    
    // Check if bag number already exists
    $stmt = $db->prepare("SELECT id FROM blood_inventory WHERE bag_number = ?");
    $stmt->execute([$bagNumber]);
    if ($stmt->fetch()) {
        sendJsonResponse(['success' => false, 'message' => 'Bag number already exists'], 400);
    }
    
    // Insert blood inventory
    $stmt = $db->prepare("
        INSERT INTO blood_inventory (
            bag_number, blood_group_id, component_type, volume_ml,
            collection_date, collection_time, expiry_date, donor_id, 
            storage_location, collection_staff, supervisor, collection_notes,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
    ");
    
    $result = $stmt->execute([
        $bagNumber, $donor["blood_group_id"], $componentType, $volumeMl,
        $collectionDate, $collectionTime, $expiryDate, $donorId, 
        $storageLocation, $collectionStaff, $supervisor, $collectionNotes
    ]);
    
    if (!$result) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to save blood collection'], 500);
    }
    
    $inventoryId = $db->lastInsertId();
    
    // Update donor's last donation date
    $stmt = $db->prepare("UPDATE donors SET last_donation_date = ?, total_donations = total_donations + 1 WHERE id = ?");
    $stmt->execute([$collectionDate, $donorId]);
    
    // Log the activity
    logActivity('BLOOD_COLLECTION', "Blood collected from donor {$donor['donor_id']}, Bag: $bagNumber");
    
    // Return collection data for label printing
    $collectionData = [
        'bag_number' => $bagNumber,
        'blood_group' => $donor['blood_group'] ?? 'Unknown',
        'component_type' => $componentType,
        'volume_ml' => $volumeMl,
        'collection_date' => $collectionDate,
        'expiry_date' => $expiryDate,
        'storage_location' => $storageLocation,
        'collection_staff' => $collectionStaff,
        'donor_name' => $donor['first_name'] . ' ' . $donor['last_name'],
        'donor_id' => $donor['donor_id']
    ];
    
    sendJsonResponse([
        "success" => true,
        "message" => "Blood collection completed successfully",
        "bag_number" => $bagNumber,
        "collection_data" => $collectionData,
        "inventory_id" => $inventoryId
    ]);
    
} catch (Exception $e) {
    error_log("Blood collection error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while processing the blood collection.'], 500);
}
?>

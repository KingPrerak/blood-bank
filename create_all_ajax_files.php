<?php
/**
 * Create All Missing AJAX Files Script
 * This script creates all missing AJAX endpoints to eliminate 404 errors
 */

echo "<h2>Creating All Missing AJAX Files</h2>";

// Define all AJAX files with their basic implementations
$ajaxFiles = [
    'process_blood_collection.php' => [
        'description' => 'Process Blood Collection',
        'method' => 'POST',
        'implementation' => '
    $donorId = (int)$_POST["donor_id"];
    $bagNumber = sanitizeInput($_POST["bag_number"]);
    $componentType = $_POST["component_type"];
    $volumeMl = (int)$_POST["volume_ml"];
    $collectionDate = $_POST["collection_date"];
    $expiryDate = $_POST["expiry_date"];
    $storageLocation = sanitizeInput($_POST["storage_location"]);
    
    if (empty($donorId) || empty($bagNumber) || empty($componentType)) {
        sendJsonResponse(["success" => false, "message" => "Required fields missing"], 400);
    }
    
    $db = getDB();
    $db->beginTransaction();
    
    try {
        // Get donor info
        $stmt = $db->prepare("SELECT * FROM donors WHERE id = ?");
        $stmt->execute([$donorId]);
        $donor = $stmt->fetch();
        
        if (!$donor) {
            sendJsonResponse(["success" => false, "message" => "Donor not found"], 404);
        }
        
        // Insert blood inventory
        $stmt = $db->prepare("
            INSERT INTO blood_inventory (
                bag_number, blood_group_id, component_type, volume_ml,
                collection_date, expiry_date, donor_id, storage_location,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \"available\", NOW())
        ");
        $stmt->execute([
            $bagNumber, $donor["blood_group_id"], $componentType, $volumeMl,
            $collectionDate, $expiryDate, $donorId, $storageLocation
        ]);
        
        logActivity("BLOOD_COLLECTED", "Blood collected from donor {$donor[\"donor_id\"]} - Bag: $bagNumber");
        
        $db->commit();
        
        sendJsonResponse([
            "success" => true,
            "message" => "Blood collection recorded successfully",
            "bag_number" => $bagNumber
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }'
    ],
    
    'get_recent_collections.php' => [
        'description' => 'Get Recent Collections',
        'method' => 'GET',
        'implementation' => '
    $db = getDB();
    
    $stmt = $db->query("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, \" \", d.last_name) as donor_name, d.donor_id
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.collection_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY bi.collection_date DESC, bi.created_at DESC
        LIMIT 20
    ");
    $collections = $stmt->fetchAll();
    
    $html = "";
    foreach ($collections as $collection) {
        $html .= "<tr>";
        $html .= "<td>" . formatDate($collection["collection_date"]) . "</td>";
        $html .= "<td><strong>" . htmlspecialchars($collection["bag_number"]) . "</strong></td>";
        $html .= "<td><span class=\"badge bg-danger\">" . $collection["blood_group"] . "</span></td>";
        $html .= "<td>" . $collection["component_type"] . "</td>";
        $html .= "<td>" . $collection["volume_ml"] . "ml</td>";
        $html .= "<td>" . htmlspecialchars($collection["donor_name"] ?? "Unknown") . "<br><small>" . htmlspecialchars($collection["donor_id"] ?? "") . "</small></td>";
        $html .= "<td><span class=\"badge bg-success\">" . ucfirst($collection["status"]) . "</span></td>";
        $html .= "</tr>";
    }
    
    if (empty($html)) {
        $html = "<tr><td colspan=\"7\" class=\"text-center text-muted\">No recent collections found.</td></tr>";
    }
    
    echo $html;'
    ],
    
    'submit_blood_request.php' => [
        'description' => 'Submit Blood Request',
        'method' => 'POST',
        'implementation' => '
    $patientName = sanitizeInput($_POST["patient_name"]);
    $patientAge = (int)$_POST["patient_age"];
    $patientGender = $_POST["patient_gender"];
    $bloodGroupId = (int)$_POST["blood_group_id"];
    $componentType = $_POST["component_type"];
    $unitsRequired = (int)$_POST["units_required"];
    $urgency = $_POST["urgency"];
    $hospitalName = sanitizeInput($_POST["hospital_name"]);
    $doctorName = sanitizeInput($_POST["doctor_name"]);
    $contactPerson = sanitizeInput($_POST["contact_person"]);
    $contactPhone = sanitizeInput($_POST["contact_phone"]);
    $requiredDate = $_POST["required_date"];
    $purpose = sanitizeInput($_POST["purpose"]);
    
    $errors = [];
    if (empty($patientName)) $errors[] = "Patient name is required";
    if (empty($patientAge)) $errors[] = "Patient age is required";
    if (empty($bloodGroupId)) $errors[] = "Blood group is required";
    if (empty($unitsRequired)) $errors[] = "Units required is required";
    if (empty($hospitalName)) $errors[] = "Hospital name is required";
    if (empty($contactPerson)) $errors[] = "Contact person is required";
    if (empty($contactPhone)) $errors[] = "Contact phone is required";
    
    if (!empty($errors)) {
        sendJsonResponse(["success" => false, "message" => implode(", ", $errors)], 400);
    }
    
    $db = getDB();
    $requestId = generateId("REQ");
    
    $stmt = $db->prepare("
        INSERT INTO blood_requests (
            request_id, patient_name, patient_age, patient_gender, blood_group_id,
            component_type, units_required, urgency, hospital_name, doctor_name,
            contact_person, contact_phone, request_date, required_date, purpose,
            status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, \"pending\", ?, NOW())
    ");
    
    $stmt->execute([
        $requestId, $patientName, $patientAge, $patientGender, $bloodGroupId,
        $componentType, $unitsRequired, $urgency, $hospitalName, $doctorName,
        $contactPerson, $contactPhone, $requiredDate, $purpose, getCurrentUserId()
    ]);
    
    logActivity("BLOOD_REQUEST_SUBMITTED", "Blood request submitted: $requestId for $patientName");
    
    sendJsonResponse([
        "success" => true,
        "message" => "Blood request submitted successfully",
        "request_id" => $requestId
    ]);'
    ]
];

$created = 0;
$errors = [];

echo "<div style='margin: 20px 0;'>";

foreach ($ajaxFiles as $filename => $config) {
    $content = "<?php\n";
    $content .= "require_once '../config/config.php';\n";
    $content .= "requireLogin();\n\n";
    $content .= "header('Content-Type: application/json');\n\n";
    
    if ($config['method'] === 'POST') {
        $content .= "if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {\n";
        $content .= "    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);\n";
        $content .= "}\n\n";
    }
    
    $content .= "try {\n";
    $content .= $config['implementation'];
    $content .= "\n} catch (Exception \$e) {\n";
    $content .= "    error_log('{$filename} error: ' . \$e->getMessage());\n";
    $content .= "    sendJsonResponse(['success' => false, 'message' => 'An error occurred.'], 500);\n";
    $content .= "}\n";
    $content .= "?>";
    
    if (file_put_contents("ajax/$filename", $content)) {
        echo "<p style='color: green;'>✅ Created: $filename - {$config['description']}</p>";
        $created++;
    } else {
        echo "<p style='color: red;'>❌ Failed to create: $filename</p>";
        $errors[] = $filename;
    }
}

echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>🎉 AJAX Files Creation Complete!</h3>";
echo "<p style='color: #155724;'><strong>Created:</strong> $created files</p>";
if (!empty($errors)) {
    echo "<p style='color: #721c24;'><strong>Errors:</strong> " . implode(', ', $errors) . "</p>";
}
echo "</div>";

echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl + F5)</li>";
echo "<li>Go to dashboard and test functionality</li>";
echo "<li>Check browser console for any remaining 404 errors</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='check_missing_ajax.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Check AJAX Files</a>";
echo "<a href='test_all_pages.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test All Pages</a>";
echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
</style>

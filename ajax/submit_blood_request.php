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
    ]);
} catch (Exception $e) {
    error_log('submit_blood_request.php error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred.'], 500);
}
?>
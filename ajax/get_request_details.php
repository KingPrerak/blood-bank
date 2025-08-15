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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();
    
    $requestId = (int)$_GET['request_id'];
    
    if (empty($requestId)) {
        sendJsonResponse(['success' => false, 'message' => 'Request ID is required'], 400);
    }
    
    // Get request details
    $stmt = $db->prepare("
        SELECT br.*, bg.blood_group, u.full_name as created_by_name
        FROM blood_requests br
        JOIN blood_groups bg ON br.blood_group_id = bg.id
        LEFT JOIN users u ON br.created_by = u.id
        WHERE br.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        sendJsonResponse(['success' => false, 'message' => 'Blood request not found'], 404);
    }
    
    // Get compatible blood bags
    $stmt = $db->prepare("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, ' ', d.last_name) as donor_name
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.blood_group_id = ? 
        AND bi.component_type = ? 
        AND bi.status = 'available' 
        AND bi.expiry_date > CURDATE()
        ORDER BY bi.expiry_date ASC
        LIMIT 10
    ");
    $stmt->execute([$request['blood_group_id'], $request['component_type']]);
    $availableBags = $stmt->fetchAll();
    
    // Generate details HTML
    $detailsHtml = '<div class="row">';
    $detailsHtml .= '<div class="col-md-6">';
    $detailsHtml .= '<strong>Patient:</strong> ' . htmlspecialchars($request['patient_name']) . '<br>';
    $detailsHtml .= '<strong>Age/Gender:</strong> ' . $request['patient_age'] . ' years, ' . $request['patient_gender'] . '<br>';
    $detailsHtml .= '<strong>Blood Group:</strong> <span class="badge bg-danger">' . $request['blood_group'] . '</span><br>';
    $detailsHtml .= '<strong>Component:</strong> ' . $request['component_type'] . '<br>';
    $detailsHtml .= '<strong>Units Required:</strong> ' . $request['units_required'] . '<br>';
    $detailsHtml .= '</div>';
    $detailsHtml .= '<div class="col-md-6">';
    $detailsHtml .= '<strong>Hospital:</strong> ' . htmlspecialchars($request['hospital_name']) . '<br>';
    $detailsHtml .= '<strong>Doctor:</strong> ' . htmlspecialchars($request['doctor_name']) . '<br>';
    $detailsHtml .= '<strong>Contact:</strong> ' . htmlspecialchars($request['contact_person']) . '<br>';
    $detailsHtml .= '<strong>Phone:</strong> ' . htmlspecialchars($request['contact_phone']) . '<br>';
    $detailsHtml .= '<strong>Urgency:</strong> <span class="badge bg-' . ($request['urgency'] === 'emergency' ? 'danger' : ($request['urgency'] === 'urgent' ? 'warning' : 'info')) . '">' . ucfirst($request['urgency']) . '</span><br>';
    $detailsHtml .= '</div>';
    $detailsHtml .= '</div>';
    
    if ($request['purpose']) {
        $detailsHtml .= '<div class="mt-2"><strong>Purpose:</strong> ' . htmlspecialchars($request['purpose']) . '</div>';
    }
    
    sendJsonResponse([
        'success' => true,
        'request' => $request,
        'details_html' => $detailsHtml,
        'available_bags' => $availableBags
    ]);
    
} catch (Exception $e) {
    error_log("Get request details error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while loading request details.'], 500);
}
?>

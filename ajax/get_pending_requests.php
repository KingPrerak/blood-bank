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

try {
    $db = getDB();
    
    // Get pending blood requests for replacement donations
    $stmt = $db->query("
        SELECT br.id, br.request_id, br.patient_name, bg.blood_group, 
               br.units_required, br.hospital_name, br.created_at
        FROM blood_requests br 
        JOIN blood_groups bg ON br.blood_group_id = bg.id 
        WHERE br.status = 'pending' 
        ORDER BY br.created_at DESC
        LIMIT 20
    ");
    $pendingRequests = $stmt->fetchAll();
    
    $html = '';
    
    if (empty($pendingRequests)) {
        $html = '<p class="text-muted">No pending blood requests found.</p>';
    } else {
        foreach ($pendingRequests as $request) {
            $html .= '<div class="border rounded p-2 mb-2">';
            $html .= '<div class="d-flex justify-content-between">';
            $html .= '<div>';
            $html .= '<strong>' . htmlspecialchars($request['request_id']) . '</strong><br>';
            $html .= '<small>' . htmlspecialchars($request['patient_name']) . ' - ' . $request['blood_group'] . ' (' . $request['units_required'] . ' units)</small><br>';
            $html .= '<small class="text-muted">' . htmlspecialchars($request['hospital_name']) . '</small>';
            $html .= '</div>';
            $html .= '<div class="text-end">';
            $html .= '<small class="text-muted">' . formatDate($request['created_at']) . '</small>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    
    echo $html;
    
} catch (Exception $e) {
    error_log("Get pending requests error: " . $e->getMessage());
    echo '<p class="text-danger">Error loading pending requests.</p>';
}
?>

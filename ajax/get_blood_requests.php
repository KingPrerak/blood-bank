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
    
    // Get blood requests with related information
    $stmt = $db->query("
        SELECT br.*, bg.blood_group, u.full_name as created_by_name
        FROM blood_requests br
        JOIN blood_groups bg ON br.blood_group_id = bg.id
        LEFT JOIN users u ON br.created_by = u.id
        ORDER BY 
            CASE br.urgency 
                WHEN 'emergency' THEN 1 
                WHEN 'urgent' THEN 2 
                WHEN 'routine' THEN 3 
            END,
            br.required_date ASC,
            br.created_at DESC
    ");
    
    $requests = $stmt->fetchAll();
    
    $tableRows = '';
    $requestsArray = [];
    
    foreach ($requests as $request) {
        // Determine urgency badge class
        $urgencyClass = '';
        switch ($request['urgency']) {
            case 'emergency':
                $urgencyClass = 'bg-danger';
                break;
            case 'urgent':
                $urgencyClass = 'bg-warning text-dark';
                break;
            case 'routine':
                $urgencyClass = 'bg-info';
                break;
        }
        
        // Determine status badge class
        $statusClass = '';
        switch ($request['status']) {
            case 'pending':
                $statusClass = 'bg-warning text-dark';
                break;
            case 'approved':
                $statusClass = 'bg-success';
                break;
            case 'fulfilled':
                $statusClass = 'bg-primary';
                break;
            case 'cancelled':
                $statusClass = 'bg-danger';
                break;
        }
        
        $tableRows .= '<tr>';
        $tableRows .= '<td>' . htmlspecialchars($request['request_id']) . '</td>';
        $tableRows .= '<td>' . htmlspecialchars($request['patient_name']) . '<br><small class="text-muted">' . $request['patient_age'] . ' years, ' . $request['patient_gender'] . '</small></td>';
        $tableRows .= '<td><span class="badge bg-danger">' . htmlspecialchars($request['blood_group']) . '</span></td>';
        $tableRows .= '<td>' . htmlspecialchars($request['component_type']) . '</td>';
        $tableRows .= '<td>' . $request['units_required'] . '</td>';
        $tableRows .= '<td><span class="badge ' . $urgencyClass . '">' . ucfirst($request['urgency']) . '</span></td>';
        $tableRows .= '<td>' . formatDate($request['required_date']) . '</td>';
        $tableRows .= '<td>' . htmlspecialchars($request['hospital_name']) . '<br><small class="text-muted">Dr. ' . htmlspecialchars($request['doctor_name']) . '</small></td>';
        $tableRows .= '<td><span class="badge ' . $statusClass . '">' . ucfirst($request['status']) . '</span></td>';
        
        // Actions column
        $tableRows .= '<td>';
        if ($request['status'] === 'pending') {
            $tableRows .= '<button class="btn btn-sm btn-success me-1" onclick="approveRequest(' . $request['id'] . ')" title="Approve">';
            $tableRows .= '<i class="fas fa-check"></i></button>';
            $tableRows .= '<button class="btn btn-sm btn-danger" onclick="cancelRequest(' . $request['id'] . ')" title="Cancel">';
            $tableRows .= '<i class="fas fa-times"></i></button>';
        } elseif ($request['status'] === 'approved') {
            $tableRows .= '<button class="btn btn-sm btn-primary" onclick="issueBlood(' . $request['id'] . ')" title="Issue Blood">';
            $tableRows .= '<i class="fas fa-tint"></i></button>';
        }
        $tableRows .= '</td>';
        $tableRows .= '</tr>';
        
        // Add to requests array for dropdown
        if ($request['status'] === 'pending' || $request['status'] === 'approved') {
            $requestsArray[] = [
                'id' => $request['id'],
                'request_id' => $request['request_id'],
                'patient_name' => $request['patient_name'],
                'blood_group' => $request['blood_group'],
                'units_required' => $request['units_required'],
                'component_type' => $request['component_type']
            ];
        }
    }
    
    if (empty($tableRows)) {
        $tableRows = '<tr><td colspan="10" class="text-center text-muted">No blood requests found.</td></tr>';
    }
    
    sendJsonResponse([
        'success' => true,
        'table_rows' => $tableRows,
        'requests' => $requestsArray
    ]);
    
} catch (Exception $e) {
    error_log("Get blood requests error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while loading blood requests.'], 500);
}
?>

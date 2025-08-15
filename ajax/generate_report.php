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
    
    $reportType = sanitizeInput($_POST['type'] ?? '');
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    
    if (empty($reportType)) {
        sendJsonResponse(['success' => false, 'message' => 'Report type is required'], 400);
    }
    
    if (empty($dateFrom) || empty($dateTo)) {
        sendJsonResponse(['success' => false, 'message' => 'Date range is required'], 400);
    }
    
    $reportData = [];
    $reportTitle = '';
    
    switch ($reportType) {
        case 'donations':
            $reportTitle = 'Blood Donations Report';
            $stmt = $db->prepare("
                SELECT bd.donation_date, bd.donation_id, 
                       CONCAT(d.first_name, ' ', d.last_name) as donor_name,
                       d.donor_id, bg.blood_group, bd.hemoglobin_level,
                       bd.status, u.full_name as collected_by
                FROM blood_donations bd
                JOIN donors d ON bd.donor_id = d.id
                JOIN blood_groups bg ON d.blood_group_id = bg.id
                LEFT JOIN users u ON bd.created_by = u.id
                WHERE bd.donation_date BETWEEN ? AND ?
                ORDER BY bd.donation_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'requests':
            $reportTitle = 'Blood Requests Report';
            $stmt = $db->prepare("
                SELECT br.request_date, br.request_id, br.patient_name,
                       br.patient_age, br.patient_gender, bg.blood_group,
                       br.component_type, br.units_required, br.urgency,
                       br.hospital_name, br.status, u.full_name as requested_by
                FROM blood_requests br
                JOIN blood_groups bg ON br.blood_group_id = bg.id
                LEFT JOIN users u ON br.created_by = u.id
                WHERE br.request_date BETWEEN ? AND ?
                ORDER BY br.request_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'inventory':
            $reportTitle = 'Blood Inventory Report';
            $stmt = $db->prepare("
                SELECT bi.bag_number, bg.blood_group, bi.component_type,
                       bi.volume_ml, bi.collection_date, bi.expiry_date,
                       bi.status, bi.storage_location,
                       CONCAT(d.first_name, ' ', d.last_name) as donor_name,
                       DATEDIFF(bi.expiry_date, CURDATE()) as days_to_expiry
                FROM blood_inventory bi
                JOIN blood_groups bg ON bi.blood_group_id = bg.id
                LEFT JOIN donors d ON bi.donor_id = d.id
                WHERE bi.collection_date BETWEEN ? AND ?
                ORDER BY bi.collection_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'issues':
            $reportTitle = 'Blood Issues Report';
            $stmt = $db->prepare("
                SELECT bi_issue.issued_date, bi_issue.issue_id,
                       br.patient_name, br.hospital_name,
                       bg.blood_group, inv.component_type,
                       inv.bag_number, bi_issue.received_by,
                       u.full_name as issued_by
                FROM blood_issues bi_issue
                JOIN blood_requests br ON bi_issue.request_id = br.id
                JOIN blood_inventory inv ON bi_issue.bag_id = inv.id
                JOIN blood_groups bg ON inv.blood_group_id = bg.id
                LEFT JOIN users u ON bi_issue.issued_by = u.id
                WHERE bi_issue.issued_date BETWEEN ? AND ?
                ORDER BY bi_issue.issued_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll();
            break;
            
        case 'wastage':
            $reportTitle = 'Blood Wastage Report';
            $stmt = $db->prepare("
                SELECT bw.wastage_date, inv.bag_number, bg.blood_group,
                       inv.component_type, bw.wastage_type, bw.quantity_wasted,
                       bw.cost_impact, u.full_name as reported_by
                FROM blood_wastage bw
                JOIN blood_inventory inv ON bw.bag_id = inv.id
                JOIN blood_groups bg ON inv.blood_group_id = bg.id
                LEFT JOIN users u ON bw.reported_by = u.id
                WHERE bw.wastage_date BETWEEN ? AND ?
                ORDER BY bw.wastage_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll();
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid report type'], 400);
    }
    
    // Generate summary statistics
    $summary = [
        'total_records' => count($reportData),
        'date_range' => formatDate($dateFrom) . ' to ' . formatDate($dateTo),
        'generated_by' => getCurrentUserName(),
        'generated_at' => formatDateTime(date('Y-m-d H:i:s'))
    ];
    
    // Add type-specific summaries
    if ($reportType === 'donations' && !empty($reportData)) {
        $summary['total_units'] = count($reportData);
        $summary['blood_groups'] = array_count_values(array_column($reportData, 'blood_group'));
    } elseif ($reportType === 'requests' && !empty($reportData)) {
        $summary['total_units_requested'] = array_sum(array_column($reportData, 'units_required'));
        $summary['urgency_breakdown'] = array_count_values(array_column($reportData, 'urgency'));
    } elseif ($reportType === 'wastage' && !empty($reportData)) {
        $summary['total_cost_impact'] = array_sum(array_column($reportData, 'cost_impact'));
        $summary['wastage_types'] = array_count_values(array_column($reportData, 'wastage_type'));
    }
    
    // Log activity
    logActivity('REPORT_GENERATED', "Generated $reportType report for period $dateFrom to $dateTo");
    
    sendJsonResponse([
        'success' => true,
        'report_title' => $reportTitle,
        'report_type' => $reportType,
        'data' => $reportData,
        'summary' => $summary,
        'message' => "Report generated successfully with {$summary['total_records']} records"
    ]);
    
} catch (Exception $e) {
    error_log("Generate report error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while generating the report.'], 500);
}
?>

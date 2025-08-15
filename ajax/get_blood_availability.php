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

try {
    $db = getDB();
    
    // Get blood availability by group and component
    $stmt = $db->query("
        SELECT bg.blood_group, bi.component_type, COUNT(bi.id) as units,
               MIN(bi.expiry_date) as earliest_expiry
        FROM blood_groups bg 
        LEFT JOIN blood_inventory bi ON bg.id = bi.blood_group_id 
            AND bi.status = 'available' 
            AND bi.expiry_date > CURDATE()
        GROUP BY bg.id, bg.blood_group, bi.component_type
        ORDER BY bg.blood_group, bi.component_type
    ");
    $availability = $stmt->fetchAll();
    
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-sm table-bordered">';
    $html .= '<thead class="table-dark">';
    $html .= '<tr><th>Blood Group</th><th>Component</th><th>Units</th><th>Earliest Expiry</th></tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    if (empty($availability)) {
        $html .= '<tr><td colspan="4" class="text-center text-muted">No blood units available</td></tr>';
    } else {
        foreach ($availability as $item) {
            if ($item['component_type']) { // Only show rows with actual inventory
                $statusClass = $item['units'] < 5 ? 'table-warning' : '';
                $html .= '<tr class="' . $statusClass . '">';
                $html .= '<td><span class="badge bg-danger">' . $item['blood_group'] . '</span></td>';
                $html .= '<td>' . $item['component_type'] . '</td>';
                $html .= '<td>' . $item['units'] . '</td>';
                $html .= '<td>' . ($item['earliest_expiry'] ? formatDate($item['earliest_expiry']) : '-') . '</td>';
                $html .= '</tr>';
            }
        }
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Add summary
    $stmt = $db->query("
        SELECT COUNT(*) as total_units,
               COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as expiring_soon
        FROM blood_inventory 
        WHERE status = 'available' AND expiry_date > CURDATE()
    ");
    $summary = $stmt->fetch();
    
    $html .= '<div class="mt-3">';
    $html .= '<div class="row text-center">';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="card bg-success text-white">';
    $html .= '<div class="card-body">';
    $html .= '<h5>' . $summary['total_units'] . '</h5>';
    $html .= '<p class="mb-0">Total Available</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="card bg-warning text-white">';
    $html .= '<div class="card-body">';
    $html .= '<h5>' . $summary['expiring_soon'] . '</h5>';
    $html .= '<p class="mb-0">Expiring Soon</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    echo $html;
    
} catch (Exception $e) {
    error_log("Get blood availability error: " . $e->getMessage());
    echo '<p class="text-danger">Error loading blood availability.</p>';
}
?>

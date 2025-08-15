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
    
    // Get blood group wise inventory
    $stmt = $db->query("
        SELECT bg.blood_group, COUNT(bi.id) as units 
        FROM blood_groups bg 
        LEFT JOIN blood_inventory bi ON bg.id = bi.blood_group_id 
            AND bi.status = 'available' 
            AND bi.expiry_date > CURDATE()
        GROUP BY bg.id, bg.blood_group 
        ORDER BY bg.blood_group
    ");
    $bloodGroupInventory = $stmt->fetchAll();
    
    $html = '';
    
    foreach ($bloodGroupInventory as $group) {
        $lowStockClass = $group['units'] < 5 ? 'bg-warning' : 'bg-danger';
        $textClass = $group['units'] < 5 ? 'text-dark' : 'text-white';
        
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<div class="text-center p-3 border rounded ' . $lowStockClass . ' ' . $textClass . '">';
        $html .= '<h4>' . htmlspecialchars($group['blood_group']) . '</h4>';
        $html .= '<p class="mb-0">' . $group['units'] . ' Units</p>';
        
        if ($group['units'] < 5) {
            $html .= '<small><i class="fas fa-exclamation-triangle"></i> Low Stock</small>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    echo $html;
    
} catch (Exception $e) {
    error_log("Get blood group inventory error: " . $e->getMessage());
    echo '<p class="text-danger">Error loading blood group inventory.</p>';
}
?>

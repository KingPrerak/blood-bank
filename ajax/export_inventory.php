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
    
    // Get inventory data
    $stmt = $db->query("
        SELECT bi.bag_number, bg.blood_group, bi.component_type, bi.volume_ml,
               bi.collection_date, bi.expiry_date, bi.status, bi.storage_location,
               CONCAT(d.first_name, ' ', d.last_name) as donor_name, d.donor_id,
               DATEDIFF(bi.expiry_date, CURDATE()) as days_to_expiry
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        ORDER BY bi.created_at DESC
    ");
    $inventory = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="blood_inventory_' . date('Y-m-d') . '.csv"');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Bag Number', 'Blood Group', 'Component Type', 'Volume (ml)',
        'Collection Date', 'Expiry Date', 'Days to Expiry', 'Status',
        'Storage Location', 'Donor Name', 'Donor ID'
    ]);
    
    // CSV data
    foreach ($inventory as $item) {
        fputcsv($output, [
            $item['bag_number'],
            $item['blood_group'],
            $item['component_type'],
            $item['volume_ml'],
            $item['collection_date'],
            $item['expiry_date'],
            $item['days_to_expiry'],
            ucfirst($item['status']),
            $item['storage_location'] ?? 'Not specified',
            $item['donor_name'] ?? 'Unknown',
            $item['donor_id'] ?? ''
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Export inventory error: " . $e->getMessage());
    header('Content-Type: text/html');
    echo "Error exporting inventory: " . $e->getMessage();
}
?>

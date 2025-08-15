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
    
    // Calculate total wastage cost for current month
    $stmt = $db->query("
        SELECT SUM(cost_impact) as total_cost,
               COUNT(*) as total_units,
               SUM(quantity_wasted) as total_volume
        FROM blood_wastage 
        WHERE MONTH(wastage_date) = MONTH(CURDATE()) 
        AND YEAR(wastage_date) = YEAR(CURDATE())
    ");
    $result = $stmt->fetch();
    
    $totalCost = $result['total_cost'] ?? 0;
    $totalUnits = $result['total_units'] ?? 0;
    $totalVolume = $result['total_volume'] ?? 0;
    
    // Get wastage by type for current month
    $stmt = $db->query("
        SELECT wastage_type, 
               COUNT(*) as units,
               SUM(cost_impact) as cost,
               SUM(quantity_wasted) as volume
        FROM blood_wastage 
        WHERE MONTH(wastage_date) = MONTH(CURDATE()) 
        AND YEAR(wastage_date) = YEAR(CURDATE())
        GROUP BY wastage_type
    ");
    $wastageByType = $stmt->fetchAll();
    
    sendJsonResponse([
        'success' => true,
        'cost' => $totalCost,
        'units' => $totalUnits,
        'volume' => $totalVolume,
        'breakdown' => $wastageByType
    ]);
    
} catch (Exception $e) {
    error_log("Calculate wastage cost error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while calculating wastage cost.'], 500);
}
?>

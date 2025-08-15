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
    
    echo $html;
} catch (Exception $e) {
    error_log('get_recent_collections.php error: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred.'], 500);
}
?>
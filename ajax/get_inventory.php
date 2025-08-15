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
    
    // Get filters
    $bloodGroup = $_GET['blood_group'] ?? '';
    $component = $_GET['component'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if (!empty($bloodGroup)) {
        $whereConditions[] = "bi.blood_group_id = ?";
        $params[] = $bloodGroup;
    }
    
    if (!empty($component)) {
        $whereConditions[] = "bi.component_type = ?";
        $params[] = $component;
    }
    
    if (!empty($status)) {
        $whereConditions[] = "bi.status = ?";
        $params[] = $status;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(bi.bag_number LIKE ? OR d.donor_id LIKE ? OR CONCAT(d.first_name, ' ', d.last_name) LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        $whereClause
    ";
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get inventory data
    $query = "
        SELECT bi.*, bg.blood_group, 
               CONCAT(d.first_name, ' ', d.last_name) as donor_name, d.donor_id,
               DATEDIFF(bi.expiry_date, CURDATE()) as days_to_expiry
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        $whereClause
        ORDER BY bi.collection_date DESC, bi.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $inventory = $stmt->fetchAll();
    
    $tableRows = '';
    
    foreach ($inventory as $item) {
        // Determine status badge class
        $statusClass = '';
        switch ($item['status']) {
            case 'available':
                $statusClass = 'bg-success';
                break;
            case 'issued':
                $statusClass = 'bg-primary';
                break;
            case 'expired':
                $statusClass = 'bg-danger';
                break;
            case 'discarded':
                $statusClass = 'bg-secondary';
                break;
        }
        
        // Check if expiring soon (within 7 days)
        $expiryWarning = '';
        if ($item['status'] === 'available' && $item['days_to_expiry'] <= 7 && $item['days_to_expiry'] >= 0) {
            $expiryWarning = ' text-warning';
        } elseif ($item['status'] === 'available' && $item['days_to_expiry'] < 0) {
            $expiryWarning = ' text-danger';
        }
        
        $tableRows .= '<tr>';
        $tableRows .= '<td><strong>' . htmlspecialchars($item['bag_number']) . '</strong></td>';
        $tableRows .= '<td><span class="badge bg-danger">' . htmlspecialchars($item['blood_group']) . '</span></td>';
        $tableRows .= '<td>' . htmlspecialchars($item['component_type']) . '</td>';
        $tableRows .= '<td>' . $item['volume_ml'] . '</td>';
        $tableRows .= '<td>' . formatDate($item['collection_date']) . '</td>';
        $tableRows .= '<td class="' . $expiryWarning . '">' . formatDate($item['expiry_date']) . '</td>';
        $tableRows .= '<td>' . htmlspecialchars($item['donor_name'] ?? 'Unknown') . '<br><small class="text-muted">' . htmlspecialchars($item['donor_id'] ?? '') . '</small></td>';
        $tableRows .= '<td>' . htmlspecialchars($item['storage_location'] ?? 'Not specified') . '</td>';
        $tableRows .= '<td><span class="badge ' . $statusClass . '">' . ucfirst($item['status']) . '</span></td>';
        
        // Actions column
        $tableRows .= '<td>';
        if ($item['status'] === 'available') {
            $tableRows .= '<button class="btn btn-sm btn-warning me-1" onclick="showUpdateStatusModal(' . $item['id'] . ', \'' . $item['status'] . '\')" title="Update Status">';
            $tableRows .= '<i class="fas fa-edit"></i></button>';
            $tableRows .= '<button class="btn btn-sm btn-danger" onclick="discardBag(' . $item['id'] . ')" title="Discard">';
            $tableRows .= '<i class="fas fa-trash"></i></button>';
        }
        $tableRows .= '</td>';
        $tableRows .= '</tr>';
    }
    
    if (empty($tableRows)) {
        $tableRows = '<tr><td colspan="10" class="text-center text-muted">No inventory records found.</td></tr>';
    }
    
    // Generate pagination
    $pagination = '';
    if ($totalPages > 1) {
        $pagination .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
        $pagination .= '<a class="page-link" href="#" onclick="loadInventoryData(' . ($page - 1) . ')">Previous</a></li>';
        
        for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
            $pagination .= '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
            $pagination .= '<a class="page-link" href="#" onclick="loadInventoryData(' . $i . ')">' . $i . '</a></li>';
        }
        
        $pagination .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">';
        $pagination .= '<a class="page-link" href="#" onclick="loadInventoryData(' . ($page + 1) . ')">Next</a></li>';
    }
    
    sendJsonResponse([
        'success' => true,
        'table_rows' => $tableRows,
        'pagination' => $pagination,
        'total_records' => $totalRecords,
        'current_page' => $page,
        'total_pages' => $totalPages
    ]);
    
} catch (Exception $e) {
    error_log("Get inventory error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while loading inventory data.'], 500);
}
?>

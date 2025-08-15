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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();
    
    $query = sanitizeInput($_GET['query'] ?? '');
    
    if (strlen($query) < 3) {
        sendJsonResponse(['success' => false, 'message' => 'Search query must be at least 3 characters'], 400);
    }
    
    // Search donors by donor ID, name, or phone
    $stmt = $db->prepare("
        SELECT d.*, bg.blood_group,
               CONCAT(d.first_name, ' ', d.last_name) as full_name,
               DATEDIFF(CURDATE(), d.last_donation_date) as days_since_last_donation
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE (d.donor_id LIKE ? 
               OR d.first_name LIKE ? 
               OR d.last_name LIKE ? 
               OR CONCAT(d.first_name, ' ', d.last_name) LIKE ?
               OR d.phone LIKE ?)
        AND d.status = 'active'
        ORDER BY 
            CASE 
                WHEN d.donor_id = ? THEN 1
                WHEN d.donor_id LIKE ? THEN 2
                WHEN CONCAT(d.first_name, ' ', d.last_name) LIKE ? THEN 3
                ELSE 4
            END,
            d.created_at DESC
        LIMIT 10
    ");
    
    $searchTerm = "%$query%";
    $exactMatch = $query;
    $startsWith = "$query%";
    
    $stmt->execute([
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
        $exactMatch, $startsWith, $startsWith
    ]);
    $donors = $stmt->fetchAll();
    
    $results = [];
    
    foreach ($donors as $donor) {
        $canDonate = true;
        $reason = '';
        
        // Check if donor can donate (90 days interval)
        if ($donor['last_donation_date']) {
            $daysSince = $donor['days_since_last_donation'];
            if ($daysSince < DONATION_INTERVAL_DAYS) {
                $canDonate = false;
                $reason = 'Must wait ' . (DONATION_INTERVAL_DAYS - $daysSince) . ' more days';
            }
        }
        
        // Check for active deferrals
        $stmt2 = $db->prepare("
            SELECT deferral_reason, deferral_end_date, deferral_type 
            FROM donor_deferrals 
            WHERE donor_id = ? 
            AND (deferral_type = 'permanent' OR deferral_end_date > CURDATE())
            ORDER BY deferral_end_date DESC 
            LIMIT 1
        ");
        $stmt2->execute([$donor['id']]);
        $deferral = $stmt2->fetch();
        
        if ($deferral) {
            $canDonate = false;
            if ($deferral['deferral_type'] === 'permanent') {
                $reason = 'Permanently deferred: ' . $deferral['deferral_reason'];
            } else {
                $reason = 'Deferred until ' . formatDate($deferral['deferral_end_date']);
            }
        }
        
        $results[] = [
            'id' => $donor['id'],
            'donor_id' => $donor['donor_id'],
            'full_name' => $donor['full_name'],
            'blood_group' => $donor['blood_group'],
            'phone' => $donor['phone'],
            'age' => calculateAge($donor['date_of_birth']),
            'total_donations' => $donor['total_donations'],
            'last_donation_date' => $donor['last_donation_date'] ? formatDate($donor['last_donation_date']) : 'Never',
            'can_donate' => $canDonate,
            'reason' => $reason,
            'status' => $donor['status']
        ];
    }
    
    sendJsonResponse([
        'success' => true,
        'donors' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log("Search donors error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while searching donors.'], 500);
}
?>

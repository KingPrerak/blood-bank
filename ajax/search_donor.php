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
    $query = sanitizeInput($_GET['query']);
    
    if (strlen($query) < 3) {
        sendJsonResponse(['success' => false, 'message' => 'Query must be at least 3 characters long'], 400);
    }
    
    $db = getDB();
    
    // Search by donor ID, phone, or name
    $stmt = $db->prepare("
        SELECT d.*, bg.blood_group,
               TIMESTAMPDIFF(YEAR, d.date_of_birth, CURDATE()) as age
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.status = 'active' 
        AND (d.donor_id LIKE ? OR d.phone LIKE ? OR 
             CONCAT(d.first_name, ' ', d.last_name) LIKE ? OR
             d.first_name LIKE ? OR d.last_name LIKE ?)
        ORDER BY d.created_at DESC
        LIMIT 1
    ");
    
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $donor = $stmt->fetch();
    
    if ($donor) {
        // Check if donor can donate (90 days rule)
        $canDonate = canDonate($donor['last_donation_date']);
        
        if (!$canDonate) {
            $daysSinceLastDonation = 0;
            if ($donor['last_donation_date']) {
                $lastDonation = new DateTime($donor['last_donation_date']);
                $today = new DateTime();
                $daysSinceLastDonation = $today->diff($lastDonation)->days;
            }
            $daysRemaining = DONATION_INTERVAL_DAYS - $daysSinceLastDonation;
            
            sendJsonResponse([
                'success' => false, 
                'message' => "Donor cannot donate yet. Must wait $daysRemaining more days since last donation."
            ], 400);
        }
        
        // Check age eligibility
        if ($donor['age'] < MIN_DONATION_AGE || $donor['age'] > MAX_DONATION_AGE) {
            sendJsonResponse([
                'success' => false, 
                'message' => 'Donor age is not within eligible range (' . MIN_DONATION_AGE . '-' . MAX_DONATION_AGE . ' years).'
            ], 400);
        }
        
        sendJsonResponse([
            'success' => true, 
            'donor' => $donor,
            'message' => 'Donor found and eligible for donation.'
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'No active donor found with the given search criteria.'], 404);
    }
    
} catch (Exception $e) {
    error_log("Search donor error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while searching for the donor.'], 500);
}
?>

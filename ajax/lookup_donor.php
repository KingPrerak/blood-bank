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
    
    $donorId = sanitizeInput($_GET['donor_id'] ?? '');
    
    if (strlen($donorId) < 2) {
        sendJsonResponse(['success' => false, 'message' => 'Donor ID must be at least 2 characters'], 400);
    }

    // Search for donor by donor_id, phone, or name
    $stmt = $db->prepare("
        SELECT d.*, bg.blood_group,
               CONCAT(d.first_name, ' ', d.last_name) as full_name,
               DATEDIFF(CURDATE(), d.last_donation_date) as days_since_last_donation
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE (d.donor_id LIKE ?
               OR d.phone LIKE ?
               OR d.first_name LIKE ?
               OR d.last_name LIKE ?
               OR CONCAT(d.first_name, ' ', d.last_name) LIKE ?)
        AND d.status = 'active'
        ORDER BY
            CASE
                WHEN d.donor_id = ? THEN 1
                WHEN d.donor_id LIKE ? THEN 2
                WHEN d.phone = ? THEN 3
                WHEN CONCAT(d.first_name, ' ', d.last_name) LIKE ? THEN 4
                ELSE 5
            END
        LIMIT 1
    ");

    $exactMatch = $donorId;
    $likeMatch = "%$donorId%";
    $startsWith = "$donorId%";

    $stmt->execute([
        $likeMatch, $likeMatch, $likeMatch, $likeMatch, $likeMatch,
        $exactMatch, $startsWith, $exactMatch, $startsWith
    ]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        sendJsonResponse(['success' => false, 'message' => 'Donor not found']);
    }
    
    // Check if donor can donate (90 days interval)
    $canDonate = true;
    $reason = '';
    
    if ($donor['last_donation_date']) {
        $daysSince = $donor['days_since_last_donation'];
        if ($daysSince < DONATION_INTERVAL_DAYS) {
            $canDonate = false;
            $reason = 'Must wait ' . (DONATION_INTERVAL_DAYS - $daysSince) . ' more days since last donation';
        }
    }
    
    // Check if donor is deferred
    $stmt = $db->prepare("
        SELECT * FROM donor_deferrals 
        WHERE donor_id = ? AND deferral_type = 'temporary' 
        AND deferral_end_date > CURDATE()
        ORDER BY deferral_end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$donor['id']]);
    $deferral = $stmt->fetch();
    
    if ($deferral) {
        $canDonate = false;
        $reason = 'Donor is deferred until ' . formatDate($deferral['deferral_end_date']) . '. Reason: ' . $deferral['deferral_reason'];
    }
    
    // Check for permanent deferral
    $stmt = $db->prepare("
        SELECT * FROM donor_deferrals 
        WHERE donor_id = ? AND deferral_type = 'permanent'
        LIMIT 1
    ");
    $stmt->execute([$donor['id']]);
    $permanentDeferral = $stmt->fetch();
    
    if ($permanentDeferral) {
        $canDonate = false;
        $reason = 'Donor is permanently deferred. Reason: ' . $permanentDeferral['deferral_reason'];
    }
    
    $donorInfo = [
        'id' => $donor['id'],
        'donor_id' => $donor['donor_id'],
        'full_name' => $donor['full_name'],
        'first_name' => $donor['first_name'],
        'last_name' => $donor['last_name'],
        'date_of_birth' => $donor['date_of_birth'],
        'age' => calculateAge($donor['date_of_birth']),
        'gender' => $donor['gender'],
        'blood_group' => $donor['blood_group'],
        'blood_group_id' => $donor['blood_group_id'],
        'phone' => $donor['phone'],
        'email' => $donor['email'],
        'address' => $donor['address'],
        'city' => $donor['city'],
        'state' => $donor['state'],
        'pincode' => $donor['pincode'],
        'emergency_contact_name' => $donor['emergency_contact_name'],
        'emergency_contact_phone' => $donor['emergency_contact_phone'],
        'total_donations' => $donor['total_donations'],
        'last_donation_date' => $donor['last_donation_date'] ? formatDate($donor['last_donation_date']) : 'Never',
        'can_donate' => $canDonate,
        'reason' => $reason,
        'status' => $donor['status']
    ];
    
    sendJsonResponse([
        'success' => true,
        'donor' => $donorInfo
    ]);
    
} catch (Exception $e) {
    error_log("Lookup donor error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while looking up the donor.'], 500);
}
?>

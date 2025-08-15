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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    $db = getDB();
    
    // Sanitize and validate input data
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $dateOfBirth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $bloodGroupId = (int)$_POST['blood_group_id'];
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $state = sanitizeInput($_POST['state']);
    $pincode = sanitizeInput($_POST['pincode']);
    $emergencyContactName = sanitizeInput($_POST['emergency_contact_name']);
    $emergencyContactPhone = sanitizeInput($_POST['emergency_contact_phone']);
    $medicalHistory = sanitizeInput($_POST['medical_history']);
    $donationType = $_POST['donation_type'];
    $replacementRequestId = !empty($_POST['replacement_request_id']) ? (int)$_POST['replacement_request_id'] : null;
    
    // Validation
    $errors = [];
    
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($dateOfBirth)) $errors[] = 'Date of birth is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($bloodGroupId)) $errors[] = 'Blood group is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if (empty($state)) $errors[] = 'State is required';
    if (empty($pincode)) $errors[] = 'Pincode is required';
    
    // Validate phone number
    if (!validatePhone($phone)) {
        $errors[] = 'Invalid phone number format';
    }
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Validate emergency contact phone if provided
    if (!empty($emergencyContactPhone) && !validatePhone($emergencyContactPhone)) {
        $errors[] = 'Invalid emergency contact phone format';
    }
    
    // Validate age
    $age = calculateAge($dateOfBirth);
    if ($age < MIN_DONATION_AGE || $age > MAX_DONATION_AGE) {
        $errors[] = 'Donor age must be between ' . MIN_DONATION_AGE . ' and ' . MAX_DONATION_AGE . ' years';
    }
    
    // Validate replacement donation
    if ($donationType === 'replacement' && empty($replacementRequestId)) {
        $errors[] = 'Please select a blood request for replacement donation';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    // Check if phone number already exists
    $stmt = $db->prepare("SELECT id FROM donors WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendJsonResponse(['success' => false, 'message' => 'A donor with this phone number already exists'], 400);
    }
    
    // Generate donor ID
    $donorId = generateId('D');
    
    // Check if donor ID already exists (very unlikely but just in case)
    $stmt = $db->prepare("SELECT id FROM donors WHERE donor_id = ?");
    $stmt->execute([$donorId]);
    while ($stmt->fetch()) {
        $donorId = generateId('D');
        $stmt->execute([$donorId]);
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert donor
        $stmt = $db->prepare("
            INSERT INTO donors (
                donor_id, first_name, last_name, date_of_birth, gender, blood_group_id,
                phone, email, address, city, state, pincode, emergency_contact_name,
                emergency_contact_phone, medical_history, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $donorId, $firstName, $lastName, $dateOfBirth, $gender, $bloodGroupId,
            $phone, $email, $address, $city, $state, $pincode, $emergencyContactName,
            $emergencyContactPhone, $medicalHistory
        ]);
        
        $newDonorId = $db->lastInsertId();
        
        // If this is a replacement donation, create the donation record
        if ($donationType === 'replacement' && $replacementRequestId) {
            // Verify the request exists and is pending
            $stmt = $db->prepare("SELECT id FROM blood_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$replacementRequestId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid or non-pending blood request selected');
            }
            
            // Generate donation ID
            $donationId = generateId('DON');
            
            // Create donation record
            $stmt = $db->prepare("
                INSERT INTO blood_donations (
                    donation_id, donor_id, donation_type, replacement_for_request_id,
                    donation_date, status, created_by, created_at
                ) VALUES (?, ?, 'replacement', ?, CURDATE(), 'completed', ?, NOW())
            ");
            
            $stmt->execute([$donationId, $newDonorId, $replacementRequestId, getCurrentUserId()]);
            
            // Update donor's total donations
            $stmt = $db->prepare("UPDATE donors SET total_donations = total_donations + 1, last_donation_date = CURDATE() WHERE id = ?");
            $stmt->execute([$newDonorId]);
        }
        
        // Commit transaction
        $db->commit();
        
        // Log activity
        logActivity('DONOR_REGISTERED', "New donor registered: $donorId ($firstName $lastName)");
        
        $message = "Donor registered successfully with ID: $donorId";
        if ($donationType === 'replacement') {
            $message .= ". Redirecting to blood issue page...";
        } else {
            $message .= ". Redirecting to blood collection page...";
        }
        
        sendJsonResponse([
            'success' => true, 
            'message' => $message,
            'donor_id' => $donorId,
            'donation_type' => $donationType
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Donor registration error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred while registering the donor: ' . $e->getMessage()], 500);
}
?>

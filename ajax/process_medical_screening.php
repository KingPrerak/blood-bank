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
    
    // Get and validate input data
    $donorId = (int)$_POST['donor_id'];
    $weight = (float)$_POST['weight'];
    $hemoglobin = (float)$_POST['hemoglobin'];
    $bloodPressure = sanitizeInput($_POST['blood_pressure']);
    $temperature = (float)$_POST['temperature'];
    $pulseRate = (int)$_POST['pulse_rate'];
    $medicalOfficer = sanitizeInput($_POST['medical_officer']);
    $screening = $_POST['screening'] ?? [];
    $preDonationNotes = sanitizeInput($_POST['pre_donation_notes']);
    
    // Validation
    $errors = [];
    
    if (empty($donorId)) $errors[] = 'Donor ID is required';
    if ($weight < MIN_WEIGHT_KG) $errors[] = 'Weight must be at least ' . MIN_WEIGHT_KG . ' kg';
    if (empty($hemoglobin)) $errors[] = 'Hemoglobin level is required';
    if (empty($bloodPressure)) $errors[] = 'Blood pressure is required';
    if ($temperature < 95 || $temperature > 105) $errors[] = 'Temperature must be between 95-105°F';
    if ($pulseRate < 50 || $pulseRate > 120) $errors[] = 'Pulse rate must be between 50-120 bpm';
    if (empty($medicalOfficer)) $errors[] = 'Medical officer name is required';
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    // Get donor information
    $stmt = $db->prepare("
        SELECT d.*, bg.blood_group,
               TIMESTAMPDIFF(YEAR, d.date_of_birth, CURDATE()) as age
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.id = ? AND d.status = 'active'
    ");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        sendJsonResponse(['success' => false, 'message' => 'Donor not found or inactive'], 404);
    }
    
    // Check eligibility criteria
    $eligible = true;
    $deferralReasons = [];
    
    // Weight check
    if ($weight < MIN_WEIGHT_KG) {
        $eligible = false;
        $deferralReasons[] = 'Weight below minimum requirement';
    }
    
    // Hemoglobin check
    $minHemoglobin = ($donor['gender'] === 'Male') ? MIN_HEMOGLOBIN_MALE : MIN_HEMOGLOBIN_FEMALE;
    if ($hemoglobin < $minHemoglobin) {
        $eligible = false;
        $deferralReasons[] = 'Hemoglobin level below minimum requirement';
    }
    
    // Blood pressure check (basic validation)
    if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bloodPressure)) {
        $eligible = false;
        $deferralReasons[] = 'Invalid blood pressure format';
    } else {
        list($systolic, $diastolic) = explode('/', $bloodPressure);
        if ($systolic < 100 || $systolic > 180 || $diastolic < 60 || $diastolic > 100) {
            $eligible = false;
            $deferralReasons[] = 'Blood pressure outside normal range';
        }
    }
    
    // Temperature check
    if ($temperature < 97 || $temperature > 99) {
        $eligible = false;
        $deferralReasons[] = 'Temperature outside normal range';
    }
    
    // Pulse rate check
    if ($pulseRate < 60 || $pulseRate > 100) {
        $eligible = false;
        $deferralReasons[] = 'Pulse rate outside normal range';
    }
    
    // Age check
    if ($donor['age'] < MIN_DONATION_AGE || $donor['age'] > MAX_DONATION_AGE) {
        $eligible = false;
        $deferralReasons[] = 'Age outside eligible range';
    }
    
    // Check screening questions (at least 4 should be checked for eligibility)
    if (count($screening) < 4) {
        $eligible = false;
        $deferralReasons[] = 'Pre-donation screening criteria not met';
    }
    
    $db->beginTransaction();
    
    try {
        if ($eligible) {
            // Create donation record
            $donationId = generateId('DON');
            
            $stmt = $db->prepare("
                INSERT INTO blood_donations (
                    donation_id, donor_id, donation_type, donation_date,
                    hemoglobin_level, blood_pressure, weight_kg, temperature_f, pulse_rate,
                    medical_officer_name, pre_donation_screening, status, created_by, created_at
                ) VALUES (?, ?, 'voluntary', CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
            ");
            
            $screeningText = implode(', ', $screening);
            if (!empty($preDonationNotes)) {
                $screeningText .= '. Notes: ' . $preDonationNotes;
            }
            
            $stmt->execute([
                $donationId, $donorId, $hemoglobin, $bloodPressure, $weight, 
                $temperature, $pulseRate, $medicalOfficer, $screeningText, getCurrentUserId()
            ]);
            
            $newDonationId = $db->lastInsertId();
            
            // Log activity
            logActivity('MEDICAL_SCREENING_APPROVED', "Donor approved for donation: {$donor['donor_id']}");
            
            $db->commit();
            
            sendJsonResponse([
                'success' => true,
                'eligible' => true,
                'donation_id' => $donationId,
                'message' => 'Medical screening completed. Donor approved for donation.'
            ]);
            
        } else {
            // Record deferral
            $deferralReason = implode(', ', $deferralReasons);
            
            // You might want to create a deferrals table to track this
            // For now, we'll just log it
            logActivity('DONOR_DEFERRED', "Donor deferred: {$donor['donor_id']} - Reason: $deferralReason");
            
            $db->commit();
            
            sendJsonResponse([
                'success' => true,
                'eligible' => false,
                'reason' => $deferralReason,
                'message' => 'Donor has been deferred due to medical screening results.'
            ]);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Medical screening error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred during medical screening.'], 500);
}
?>

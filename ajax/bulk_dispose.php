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
    
    $bagIds = $_POST['bag_ids'] ?? [];
    
    if (empty($bagIds) || !is_array($bagIds)) {
        sendJsonResponse(['success' => false, 'message' => 'No blood bags selected for disposal'], 400);
    }
    
    $db->beginTransaction();
    
    try {
        $disposedCount = 0;
        $totalCost = 0;
        
        foreach ($bagIds as $bagId) {
            $bagId = (int)$bagId;
            
            // Get bag information
            $stmt = $db->prepare("
                SELECT bi.*, bg.blood_group 
                FROM blood_inventory bi 
                JOIN blood_groups bg ON bi.blood_group_id = bg.id 
                WHERE bi.id = ? AND bi.status IN ('available', 'expired')
            ");
            $stmt->execute([$bagId]);
            $bag = $stmt->fetch();
            
            if (!$bag) {
                continue; // Skip if bag not found or already disposed
            }
            
            // Update bag status
            $stmt = $db->prepare("
                UPDATE blood_inventory 
                SET status = 'discarded', disposal_date = CURDATE(), 
                    disposal_reason = 'Bulk disposal - expired', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$bagId]);
            
            // Create disposal record
            $stmt = $db->prepare("
                INSERT INTO blood_disposals (
                    bag_id, disposal_reason, disposal_date, disposal_method, 
                    disposed_by, notes, created_at
                ) VALUES (?, 'expired', CURDATE(), 'incineration', ?, 'Bulk disposal of expired units', NOW())
            ");
            $stmt->execute([$bagId, getCurrentUserId()]);
            
            // Record wastage
            $costImpact = 1500; // Average cost per unit
            $stmt = $db->prepare("
                INSERT INTO blood_wastage (
                    bag_id, wastage_type, wastage_date, quantity_wasted, 
                    cost_impact, reported_by, created_at
                ) VALUES (?, 'expired', CURDATE(), ?, ?, ?, NOW())
            ");
            $stmt->execute([$bagId, $bag['volume_ml'], $costImpact, getCurrentUserId()]);
            
            $disposedCount++;
            $totalCost += $costImpact;
        }
        
        if ($disposedCount > 0) {
            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (type, title, message, priority, created_at)
                VALUES ('disposal_reminder', 'Bulk Disposal Completed', ?, 'medium', NOW())
            ");
            $message = "Bulk disposal completed: $disposedCount units disposed. Total cost impact: ₹" . number_format($totalCost);
            $stmt->execute([$message]);
            
            // Log activity
            logActivity('BULK_DISPOSAL', "Bulk disposal of $disposedCount blood units completed");
            
            $db->commit();
            
            sendJsonResponse([
                'success' => true,
                'message' => "Successfully disposed $disposedCount blood units.",
                'disposed_count' => $disposedCount,
                'total_cost' => $totalCost
            ]);
        } else {
            $db->rollBack();
            sendJsonResponse(['success' => false, 'message' => 'No valid blood units were found for disposal.'], 400);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Bulk dispose error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred during bulk disposal.'], 500);
}
?>

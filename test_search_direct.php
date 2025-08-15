<?php
/**
 * Direct Search Test
 * Simple test to verify search functionality works
 */

echo "<h2>Direct Search Test</h2>";

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test search functionality directly
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = $_GET['query'];
    
    echo "<h3>Search Results for: <em>" . htmlspecialchars($query) . "</em></h3>";
    
    try {
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
        
        if (count($results) > 0) {
            echo "<p style='color: green;'>✅ Found " . count($results) . " donor(s)</p>";
            
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>JSON Response (what AJAX would return):</h4>";
            echo "<pre style='background: white; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
            echo json_encode([
                'success' => true,
                'donors' => $results,
                'count' => count($results)
            ], JSON_PRETTY_PRINT);
            echo "</pre>";
            echo "</div>";
            
            echo "<h4>Donor Details:</h4>";
            foreach ($results as $donor) {
                $statusColor = $donor['can_donate'] ? 'green' : 'orange';
                $statusText = $donor['can_donate'] ? 'Eligible' : 'Not Eligible';
                
                echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: white;'>";
                echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
                echo "<div>";
                echo "<h5 style='margin: 0; color: #dc3545;'>{$donor['donor_id']}</h5>";
                echo "<p style='margin: 5px 0; font-size: 18px; font-weight: bold;'>{$donor['full_name']}</p>";
                echo "<p style='margin: 5px 0;'><strong>Blood Group:</strong> <span style='background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px;'>{$donor['blood_group']}</span></p>";
                echo "<p style='margin: 5px 0;'><strong>Phone:</strong> {$donor['phone']}</p>";
                echo "<p style='margin: 5px 0;'><strong>Age:</strong> {$donor['age']} years</p>";
                echo "<p style='margin: 5px 0;'><strong>Total Donations:</strong> {$donor['total_donations']}</p>";
                echo "<p style='margin: 5px 0;'><strong>Last Donation:</strong> {$donor['last_donation_date']}</p>";
                echo "</div>";
                echo "<div style='text-align: center;'>";
                echo "<div style='color: $statusColor; font-weight: bold; font-size: 16px;'>$statusText</div>";
                if (!$donor['can_donate']) {
                    echo "<div style='color: orange; font-size: 12px; margin-top: 5px;'>{$donor['reason']}</div>";
                }
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ No donors found matching '$query'</p>";
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>JSON Response:</h4>";
            echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>";
            echo json_encode([
                'success' => false,
                'message' => 'No donors found matching your search.',
                'donors' => [],
                'count' => 0
            ], JSON_PRETTY_PRINT);
            echo "</pre>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Search error: " . $e->getMessage() . "</p>";
    }
}

// Show available donors for testing
echo "<h3>Available Donors for Testing</h3>";

try {
    $stmt = $db->query("
        SELECT d.donor_id, CONCAT(d.first_name, ' ', d.last_name) as full_name, 
               d.phone, bg.blood_group
        FROM donors d 
        JOIN blood_groups bg ON d.blood_group_id = bg.id 
        WHERE d.status = 'active' 
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $sampleDonors = $stmt->fetchAll();
    
    if (count($sampleDonors) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($sampleDonors) . " donors to test with</p>";
        
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background-color: #dc3545; color: white;'>";
        echo "<th>Donor ID</th><th>Name</th><th>Phone</th><th>Blood Group</th><th>Test Search</th>";
        echo "</tr>";
        
        foreach ($sampleDonors as $donor) {
            echo "<tr>";
            echo "<td><strong>{$donor['donor_id']}</strong></td>";
            echo "<td>{$donor['full_name']}</td>";
            echo "<td>{$donor['phone']}</td>";
            echo "<td><span style='background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px;'>{$donor['blood_group']}</span></td>";
            echo "<td>";
            echo "<a href='?query={$donor['donor_id']}' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>ID</a>";
            echo "<a href='?query=" . explode(' ', $donor['full_name'])[0] . "' style='background: #17a2b8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>Name</a>";
            echo "<a href='?query={$donor['phone']}' style='background: #6f42c1; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Phone</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ No donors found. Creating a test donor...</p>";
        
        // Create test donor
        $testDonorId = generateId('DON');
        $stmt = $db->prepare("
            INSERT INTO donors (
                donor_id, first_name, last_name, date_of_birth, gender, blood_group_id,
                phone, email, address, city, state, pincode, status, created_at
            ) VALUES (?, 'John', 'Doe', '1990-01-01', 'Male', 1, '9876543210', 
                     'john.doe@email.com', 'Test Address', 'Test City', 'Test State', 
                     '123456', 'active', NOW())
        ");
        
        if ($stmt->execute([$testDonorId])) {
            echo "<p style='color: green;'>✅ Created test donor: <strong>$testDonorId</strong></p>";
            echo "<p><a href='?query=$testDonorId' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Search with $testDonorId</a></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error getting donors: " . $e->getMessage() . "</p>";
}

// Search form
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>🔍 Test Search:</h4>";
echo "<form method='GET' action=''>";
echo "<div style='margin: 10px 0;'>";
echo "<input type='text' name='query' value='" . ($_GET['query'] ?? '') . "' placeholder='Enter donor ID, name, or phone' style='padding: 10px; width: 300px; margin-right: 10px; border: 1px solid #ccc; border-radius: 3px;'>";
echo "<button type='submit' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;'>Search</button>";
echo "</div>";
echo "</form>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h4>Next Steps:</h4>";
echo "<a href='fix_ajax_config_paths.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix AJAX Paths</a>";
echo "<a href='pages/blood-collection.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Blood Collection</a>";
echo "<a href='dashboard.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "</div>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
table {
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
</style>

<?php
/**
 * Test Donor Search Functionality
 * Debug script to test donor search and display
 */

echo "<h2>Testing Donor Search Functionality</h2>";

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test 1: Check if donors exist
echo "<h3>Test 1: Available Donors</h3>";

try {
    $stmt = $db->query("
        SELECT d.*, bg.blood_group,
               CONCAT(d.first_name, ' ', d.last_name) as full_name,
               DATEDIFF(CURDATE(), d.last_donation_date) as days_since_last_donation
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.status = 'active'
        ORDER BY d.created_at DESC
        LIMIT 10
    ");
    $donors = $stmt->fetchAll();
    
    if (count($donors) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($donors) . " active donors</p>";
        
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #dc3545; color: white;'>";
        echo "<th>Donor ID</th><th>Name</th><th>Blood Group</th><th>Phone</th><th>Last Donation</th><th>Can Donate</th>";
        echo "</tr>";
        
        foreach ($donors as $donor) {
            $canDonate = true;
            $reason = '';
            
            // Check donation interval
            if ($donor['last_donation_date']) {
                $daysSince = $donor['days_since_last_donation'];
                if ($daysSince < DONATION_INTERVAL_DAYS) {
                    $canDonate = false;
                    $reason = 'Must wait ' . (DONATION_INTERVAL_DAYS - $daysSince) . ' more days';
                }
            }
            
            $statusColor = $canDonate ? 'green' : 'orange';
            $statusText = $canDonate ? 'Yes' : 'No (' . $reason . ')';
            
            echo "<tr>";
            echo "<td><strong>{$donor['donor_id']}</strong></td>";
            echo "<td>{$donor['full_name']}</td>";
            echo "<td><span style='background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px;'>{$donor['blood_group']}</span></td>";
            echo "<td>{$donor['phone']}</td>";
            echo "<td>" . ($donor['last_donation_date'] ? formatDate($donor['last_donation_date']) : 'Never') . "</td>";
            echo "<td style='color: $statusColor;'>$statusText</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test search with first donor
        $testDonor = $donors[0];
        echo "<h4>Test Search Example:</h4>";
        echo "<p>Try searching for: <strong>{$testDonor['donor_id']}</strong> or <strong>{$testDonor['first_name']}</strong> or <strong>{$testDonor['phone']}</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ No active donors found</p>";
        
        // Create a test donor
        echo "<h4>Creating Test Donor:</h4>";
        $testDonorId = generateId('DON');
        $stmt = $db->prepare("
            INSERT INTO donors (
                donor_id, first_name, last_name, date_of_birth, gender, blood_group_id,
                phone, email, address, city, state, pincode, status, created_at
            ) VALUES (?, 'Test', 'Donor', '1990-01-01', 'Male', 1, '9876543210', 
                     'test@example.com', 'Test Address', 'Test City', 'Test State', 
                     '123456', 'active', NOW())
        ");
        
        if ($stmt->execute([$testDonorId])) {
            echo "<p style='color: green;'>✅ Created test donor with ID: <strong>$testDonorId</strong></p>";
            echo "<p>You can now search for: <strong>$testDonorId</strong> or <strong>Test</strong> or <strong>9876543210</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test donor</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking donors: " . $e->getMessage() . "</p>";
}

// Test 2: Test AJAX search endpoint
echo "<h3>Test 2: AJAX Search Endpoint</h3>";

if (file_exists('ajax/search_donor.php')) {
    echo "<p style='color: green;'>✅ search_donor.php exists</p>";
    
    // Test the endpoint with a sample query
    if (!empty($donors)) {
        $testQuery = $donors[0]['first_name'];
        echo "<p>Testing search for: <strong>$testQuery</strong></p>";
        
        // Simulate AJAX call
        $_GET['query'] = $testQuery;
        ob_start();

        try {
            include 'ajax/search_donor.php';
            $response = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $response = json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        } catch (Error $e) {
            ob_end_clean();
            $response = json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
        }
        
        echo "<h4>AJAX Response:</h4>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ ajax/search_donor.php not found</p>";
}

// Test 3: Interactive search form
echo "<h3>Test 3: Interactive Search Test</h3>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>🔍 Test Donor Search:</h4>";
echo "<form method='GET' action=''>";
echo "<div style='margin: 10px 0;'>";
echo "<label for='test_query'>Search Query:</label><br>";
echo "<input type='text' id='test_query' name='test_query' value='" . ($_GET['test_query'] ?? '') . "' placeholder='Enter donor ID, name, or phone' style='padding: 8px; width: 300px; margin-right: 10px;'>";
echo "<button type='submit' style='padding: 8px 15px; background: #dc3545; color: white; border: none; border-radius: 3px;'>Search</button>";
echo "</div>";
echo "</form>";

if (!empty($_GET['test_query'])) {
    $query = $_GET['test_query'];
    echo "<h4>Search Results for: <em>$query</em></h4>";
    
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
            ORDER BY d.created_at DESC
            LIMIT 5
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll();
        
        if (count($results) > 0) {
            echo "<p style='color: green;'>✅ Found " . count($results) . " matching donor(s)</p>";
            
            foreach ($results as $donor) {
                $canDonate = true;
                if ($donor['last_donation_date']) {
                    $daysSince = $donor['days_since_last_donation'];
                    if ($daysSince < DONATION_INTERVAL_DAYS) {
                        $canDonate = false;
                    }
                }
                
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>Donor ID:</strong> {$donor['donor_id']}<br>";
                echo "<strong>Name:</strong> {$donor['full_name']}<br>";
                echo "<strong>Blood Group:</strong> {$donor['blood_group']}<br>";
                echo "<strong>Phone:</strong> {$donor['phone']}<br>";
                echo "<strong>Can Donate:</strong> " . ($canDonate ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . "<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: red;'>❌ No donors found matching '$query'</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Search error: " . $e->getMessage() . "</p>";
    }
}

echo "</div>";

// Instructions
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #155724;'>📋 How to Fix Donor Display Issue:</h4>";
echo "<ol style='color: #155724;'>";
echo "<li><strong>Test Search:</strong> Use the search form above to verify donors are found</li>";
echo "<li><strong>Check Console:</strong> Press F12 in blood collection page and check for JavaScript errors</li>";
echo "<li><strong>Clear Cache:</strong> Press Ctrl+F5 to refresh the blood collection page</li>";
echo "<li><strong>Use Manual Buttons:</strong> After search, use 'Proceed to Medical Screening' or 'Skip to Blood Collection' buttons</li>";
echo "</ol>";

echo "<h4 style='color: #155724;'>🎯 Enhanced Features Added:</h4>";
echo "<ul style='color: #155724;'>";
echo "<li><strong>Better Search:</strong> Improved search with debug logging</li>";
echo "<li><strong>Manual Proceed:</strong> Buttons to manually proceed to collection</li>";
echo "<li><strong>Auto-Fill Forms:</strong> Collection form auto-fills with current date/time</li>";
echo "<li><strong>Clear Selection:</strong> Button to clear and start over</li>";
echo "</ul>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h4>Quick Actions:</h4>";
echo "<a href='pages/blood-collection.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Blood Collection</a>";
echo "<a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='pages/donor-registration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Register New Donor</a>";
echo "<button onclick='location.reload()' style='background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Test</button>";
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

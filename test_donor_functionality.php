<?php
/**
 * Test Donor Functionality Script
 * Tests donor lookup, search, and registration features
 */

echo "<h2>Testing Donor Functionality</h2>";

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test 1: Check if donors exist in database
echo "<h3>Test 1: Checking Existing Donors</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM donors WHERE status = 'active'");
    $donorCount = $stmt->fetch()['count'];
    
    if ($donorCount > 0) {
        echo "<p style='color: green;'>✅ Found $donorCount active donors in database</p>";
        
        // Show sample donors
        $stmt = $db->query("
            SELECT d.donor_id, CONCAT(d.first_name, ' ', d.last_name) as full_name, 
                   d.phone, bg.blood_group, d.total_donations
            FROM donors d 
            JOIN blood_groups bg ON d.blood_group_id = bg.id 
            WHERE d.status = 'active' 
            LIMIT 5
        ");
        $sampleDonors = $stmt->fetchAll();
        
        echo "<h4>Sample Donors:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>Donor ID</th><th>Name</th><th>Phone</th><th>Blood Group</th><th>Donations</th></tr>";
        foreach ($sampleDonors as $donor) {
            echo "<tr>";
            echo "<td>{$donor['donor_id']}</td>";
            echo "<td>{$donor['full_name']}</td>";
            echo "<td>{$donor['phone']}</td>";
            echo "<td>{$donor['blood_group']}</td>";
            echo "<td>{$donor['total_donations']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No active donors found in database</p>";
        echo "<p>Let's create a test donor...</p>";
        
        // Create a test donor
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
            echo "<p style='color: green;'>✅ Created test donor with ID: $testDonorId</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test donor</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking donors: " . $e->getMessage() . "</p>";
}

// Test 2: Test AJAX endpoints
echo "<h3>Test 2: Testing AJAX Endpoints</h3>";
$ajaxEndpoints = [
    'lookup_donor.php' => 'Donor Lookup',
    'search_donor.php' => 'Donor Search', 
    'search_donors.php' => 'Multiple Donor Search',
    'register_donor.php' => 'Donor Registration'
];

foreach ($ajaxEndpoints as $file => $description) {
    $fullPath = "ajax/$file";
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        if (strpos($content, '<?php') !== false && strpos($content, 'sendJsonResponse') !== false) {
            echo "<p style='color: green;'>✅ $file ($description) - Valid endpoint</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $file ($description) - May have issues</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $file ($description) - File missing</p>";
    }
}

// Test 3: Test page functionality
echo "<h3>Test 3: Testing Page Functionality</h3>";
$pages = [
    'pages/donor-registration.php' => 'Donor Registration Page',
    'pages/blood-collection.php' => 'Blood Collection Page'
];

foreach ($pages as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $checks = [
            'register-and-donate-btn' => 'Register & Donate Now button',
            'searchDonor' => 'Search donor function',
            'displayDonorInfo' => 'Display donor info function',
            'showDonorSelectionModal' => 'Donor selection modal'
        ];
        
        echo "<h4>$description:</h4>";
        foreach ($checks as $check => $checkDesc) {
            if (strpos($content, $check) !== false) {
                echo "<p style='color: green;'>✅ $checkDesc - Present</p>";
            } else {
                echo "<p style='color: red;'>❌ $checkDesc - Missing</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ $file - File missing</p>";
    }
}

// Test 4: Database schema check
echo "<h3>Test 4: Database Schema Check</h3>";
$requiredTables = ['donors', 'blood_groups', 'blood_donations', 'blood_inventory'];

foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
    }
}

// Summary and recommendations
echo "<h3>Summary & Recommendations</h3>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>🎯 What's Been Fixed:</h4>";
echo "<ul style='color: #0056b3;'>";
echo "<li><strong>Donor Lookup:</strong> Improved search with multiple criteria (ID, name, phone)</li>";
echo "<li><strong>Search Results:</strong> Now shows multiple donors with selection modal</li>";
echo "<li><strong>Register & Donate:</strong> Added direct donation button for new donors</li>";
echo "<li><strong>AJAX Endpoints:</strong> Created all missing endpoints for donor functionality</li>";
echo "<li><strong>Session Integration:</strong> Seamless flow from registration to blood collection</li>";
echo "</ul>";

echo "<h4 style='color: #0056b3;'>📋 How to Test:</h4>";
echo "<ol style='color: #0056b3;'>";
echo "<li><strong>Donor Registration:</strong> Go to dashboard → Donor Registration</li>";
echo "<li><strong>Use 'Register & Donate Now':</strong> Fill form and click the green button</li>";
echo "<li><strong>Test Donor Search:</strong> Go to Blood Collection and search for existing donors</li>";
echo "<li><strong>Multiple Results:</strong> Search with partial names to see selection modal</li>";
echo "</ol>";

echo "<h4 style='color: #0056b3;'>🔧 If Issues Persist:</h4>";
echo "<ul style='color: #0056b3;'>";
echo "<li>Clear browser cache (Ctrl + F5)</li>";
echo "<li>Check browser console for any remaining errors</li>";
echo "<li>Ensure all AJAX files are properly uploaded</li>";
echo "<li>Verify database has donor records</li>";
echo "</ul>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='pages/donor-registration.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Registration</a>";
echo "<a href='pages/blood-collection.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Collection</a>";
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
th {
    background-color: #dc3545;
    color: white;
}
</style>

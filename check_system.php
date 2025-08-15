<?php
/**
 * System Check Script for Blood Bank Management System
 * Checks all components and reports status
 */

require_once 'config/config.php';

echo "<h2>Blood Bank Management System - System Check</h2>";

$checks = [];
$overallStatus = true;

// 1. Database Connection Check
try {
    $db = getDB();
    $checks['database'] = ['status' => 'OK', 'message' => 'Database connection successful'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'ERROR', 'message' => 'Database connection failed: ' . $e->getMessage()];
    $overallStatus = false;
}

// 2. Required Tables Check
if ($checks['database']['status'] === 'OK') {
    $requiredTables = [
        'users', 'blood_groups', 'donors', 'blood_inventory', 
        'blood_requests', 'blood_donations', 'blood_issues', 
        'activity_logs', 'notifications'
    ];
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                $missingTables[] = $table;
            }
        } catch (Exception $e) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        $checks['tables'] = ['status' => 'OK', 'message' => 'All required tables exist'];
    } else {
        $checks['tables'] = ['status' => 'ERROR', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
        $overallStatus = false;
    }
}

// 3. Required Files Check
$requiredFiles = [
    'login.php', 'dashboard.php', 'logout.php',
    'config/config.php', 'config/database.php',
    'assets/css/dashboard.css', 'assets/js/dashboard.js',
    'pages/donor-registration.php', 'pages/blood-collection.php',
    'pages/blood-requests.php', 'pages/inventory.php'
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $checks['files'] = ['status' => 'OK', 'message' => 'All core files exist'];
} else {
    $checks['files'] = ['status' => 'WARNING', 'message' => 'Missing files: ' . implode(', ', $missingFiles)];
}

// 4. AJAX Endpoints Check
$ajaxEndpoints = [
    'ajax/register_donor.php', 'ajax/search_donor.php',
    'ajax/get_blood_requests.php', 'ajax/get_inventory.php',
    'ajax/get_pending_requests.php'
];

$missingEndpoints = [];
foreach ($ajaxEndpoints as $endpoint) {
    if (!file_exists($endpoint)) {
        $missingEndpoints[] = $endpoint;
    }
}

if (empty($missingEndpoints)) {
    $checks['ajax'] = ['status' => 'OK', 'message' => 'All AJAX endpoints exist'];
} else {
    $checks['ajax'] = ['status' => 'WARNING', 'message' => 'Missing endpoints: ' . implode(', ', $missingEndpoints)];
}

// 5. User Authentication Check
if ($checks['database']['status'] === 'OK') {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $userCount = $stmt->fetch()['count'];
        
        if ($userCount > 0) {
            $checks['users'] = ['status' => 'OK', 'message' => "$userCount active users found"];
        } else {
            $checks['users'] = ['status' => 'ERROR', 'message' => 'No active users found'];
            $overallStatus = false;
        }
    } catch (Exception $e) {
        $checks['users'] = ['status' => 'ERROR', 'message' => 'Cannot check users: ' . $e->getMessage()];
        $overallStatus = false;
    }
}

// 6. Blood Groups Check
if ($checks['database']['status'] === 'OK') {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM blood_groups");
        $bgCount = $stmt->fetch()['count'];
        
        if ($bgCount >= 8) {
            $checks['blood_groups'] = ['status' => 'OK', 'message' => "$bgCount blood groups configured"];
        } else {
            $checks['blood_groups'] = ['status' => 'WARNING', 'message' => "Only $bgCount blood groups found (expected 8)"];
        }
    } catch (Exception $e) {
        $checks['blood_groups'] = ['status' => 'ERROR', 'message' => 'Cannot check blood groups: ' . $e->getMessage()];
    }
}

// Display Results
echo "<div style='margin: 20px 0;'>";
if ($overallStatus) {
    echo "<div style='color: green; font-weight: bold; font-size: 18px;'>✅ System Status: HEALTHY</div>";
} else {
    echo "<div style='color: red; font-weight: bold; font-size: 18px;'>❌ System Status: ISSUES DETECTED</div>";
}
echo "</div>";

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #dc3545; color: white;'>";
echo "<th>Component</th><th>Status</th><th>Details</th>";
echo "</tr>";

foreach ($checks as $component => $check) {
    $statusColor = '';
    switch ($check['status']) {
        case 'OK':
            $statusColor = 'color: green; font-weight: bold;';
            break;
        case 'WARNING':
            $statusColor = 'color: orange; font-weight: bold;';
            break;
        case 'ERROR':
            $statusColor = 'color: red; font-weight: bold;';
            break;
    }
    
    echo "<tr>";
    echo "<td>" . ucfirst(str_replace('_', ' ', $component)) . "</td>";
    echo "<td style='$statusColor'>" . $check['status'] . "</td>";
    echo "<td>" . htmlspecialchars($check['message']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Quick Actions
echo "<div style='margin: 30px 0;'>";
echo "<h3>Quick Actions:</h3>";
echo "<p><a href='fix_passwords.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix Passwords</a></p>";
echo "<p><a href='test_login.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Login</a></p>";
echo "<p><a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a></p>";
echo "</div>";

// Recommendations
if (!$overallStatus) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404;'>Recommendations:</h3>";
    echo "<ul style='color: #856404;'>";
    
    if ($checks['database']['status'] === 'ERROR') {
        echo "<li>Check XAMPP MySQL service is running</li>";
        echo "<li>Verify database 'bloodbank_management' exists</li>";
        echo "<li>Run the complete SQL schema to create tables</li>";
    }
    
    if (isset($checks['tables']) && $checks['tables']['status'] === 'ERROR') {
        echo "<li>Import the complete database schema from database/bloodbank_schema.sql</li>";
    }
    
    if ($checks['users']['status'] === 'ERROR') {
        echo "<li>Run fix_passwords.php to create default users</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}
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
    margin: 20px 0;
}
th, td {
    text-align: left;
    padding: 10px;
}
th {
    background-color: #dc3545;
    color: white;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
</style>

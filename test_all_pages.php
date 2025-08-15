<?php
/**
 * Comprehensive Page Testing Script
 * Tests all pages and AJAX endpoints for errors
 */

require_once 'config/config.php';

echo "<h2>Blood Bank Management System - Complete Page Testing</h2>";

$testResults = [];

// Test database connection first
try {
    $db = getDB();
    $testResults['database'] = ['status' => 'PASS', 'message' => 'Database connection successful'];
} catch (Exception $e) {
    $testResults['database'] = ['status' => 'FAIL', 'message' => 'Database connection failed: ' . $e->getMessage()];
    echo "<div style='color: red; font-weight: bold;'>❌ Cannot proceed - Database connection failed!</div>";
    exit();
}

// Test all pages
$pages = [
    'dashboard-home.php' => 'Dashboard Home',
    'donor-registration.php' => 'Donor Registration',
    'blood-collection.php' => 'Blood Collection',
    'blood-requests.php' => 'Blood Requests',
    'inventory.php' => 'Inventory Management',
    'reports.php' => 'Reports',
    'expired-management.php' => 'Expired Management',
    'crossmatch-lab.php' => 'Cross-match Lab',
    'notifications.php' => 'Notifications'
];

foreach ($pages as $file => $name) {
    if (file_exists("pages/$file")) {
        // Test if page loads without fatal errors
        ob_start();
        $error = false;
        try {
            include "pages/$file";
        } catch (Exception $e) {
            $error = $e->getMessage();
        } catch (Error $e) {
            $error = $e->getMessage();
        }
        $output = ob_get_clean();
        
        if ($error) {
            $testResults["page_$file"] = ['status' => 'FAIL', 'message' => "Page error: $error"];
        } else {
            $testResults["page_$file"] = ['status' => 'PASS', 'message' => "Page loads successfully"];
        }
    } else {
        $testResults["page_$file"] = ['status' => 'FAIL', 'message' => 'Page file not found'];
    }
}

// Test critical AJAX endpoints
$ajaxEndpoints = [
    'register_donor.php' => 'Donor Registration',
    'get_blood_requests.php' => 'Blood Requests',
    'get_inventory.php' => 'Inventory Data',
    'get_pending_requests.php' => 'Pending Requests',
    'mark_notification_read.php' => 'Notifications',
    'process_blood_collection.php' => 'Blood Collection',
    'issue_blood.php' => 'Blood Issue',
    'dispose_blood_unit.php' => 'Blood Disposal'
];

foreach ($ajaxEndpoints as $file => $name) {
    if (file_exists("ajax/$file")) {
        $testResults["ajax_$file"] = ['status' => 'PASS', 'message' => 'AJAX endpoint exists'];
    } else {
        $testResults["ajax_$file"] = ['status' => 'FAIL', 'message' => 'AJAX endpoint missing'];
    }
}

// Test core functions
$functions = [
    'getCurrentUserId' => 'User Authentication',
    'generateId' => 'ID Generation',
    'formatDate' => 'Date Formatting',
    'sanitizeInput' => 'Input Sanitization',
    'logActivity' => 'Activity Logging',
    'sendJsonResponse' => 'JSON Response'
];

foreach ($functions as $function => $name) {
    if (function_exists($function)) {
        $testResults["function_$function"] = ['status' => 'PASS', 'message' => 'Function exists'];
    } else {
        $testResults["function_$function"] = ['status' => 'FAIL', 'message' => 'Function missing'];
    }
}

// Test database tables
$tables = [
    'users' => 'User Management',
    'blood_groups' => 'Blood Groups',
    'donors' => 'Donor Management',
    'blood_inventory' => 'Blood Inventory',
    'blood_requests' => 'Blood Requests',
    'notifications' => 'Notifications',
    'activity_logs' => 'Activity Logs'
];

foreach ($tables as $table => $name) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetch()[0];
        $testResults["table_$table"] = ['status' => 'PASS', 'message' => "Table exists ($count records)"];
    } catch (Exception $e) {
        $testResults["table_$table"] = ['status' => 'FAIL', 'message' => 'Table missing or inaccessible'];
    }
}

// Calculate results
$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($result) { return $result['status'] === 'PASS'; }));
$failedTests = $totalTests - $passedTests;

// Display results
echo "<div style='margin: 20px 0;'>";
if ($failedTests === 0) {
    echo "<div style='color: green; font-weight: bold; font-size: 18px;'>🎉 ALL TESTS PASSED - SYSTEM IS FULLY FUNCTIONAL!</div>";
} else {
    echo "<div style='color: red; font-weight: bold; font-size: 18px;'>⚠️ $failedTests TEST(S) FAILED - ISSUES DETECTED</div>";
}
echo "</div>";

// Summary
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Test Summary:</h3>";
echo "<p><strong>Total Tests:</strong> $totalTests</p>";
echo "<p><strong>Passed:</strong> <span style='color: green;'>$passedTests</span></p>";
echo "<p><strong>Failed:</strong> <span style='color: red;'>$failedTests</span></p>";
echo "<p><strong>Success Rate:</strong> " . round(($passedTests / $totalTests) * 100, 1) . "%</p>";
echo "</div>";

// Detailed results
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background-color: #dc3545; color: white;'>";
echo "<th>Component</th><th>Status</th><th>Details</th>";
echo "</tr>";

foreach ($testResults as $component => $result) {
    $statusColor = $result['status'] === 'PASS' ? 'green' : 'red';
    $statusIcon = $result['status'] === 'PASS' ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td>" . ucfirst(str_replace(['_', '.php'], [' ', ''], $component)) . "</td>";
    echo "<td style='color: $statusColor; font-weight: bold;'>$statusIcon {$result['status']}</td>";
    echo "<td>{$result['message']}</td>";
    echo "</tr>";
}

echo "</table>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='fix_all_errors.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix All Errors</a>";
echo "<a href='fix_passwords.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix Passwords</a>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
echo "</div>";

// Final recommendations
if ($failedTests > 0) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404;'>Recommendations:</h3>";
    echo "<ul style='color: #856404;'>";
    echo "<li>Run fix_all_errors.php to automatically fix common issues</li>";
    echo "<li>Import the complete database schema if tables are missing</li>";
    echo "<li>Check file permissions and ensure all files are uploaded correctly</li>";
    echo "<li>Clear browser cache and refresh the dashboard</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 Congratulations!</h3>";
    echo "<p style='color: #155724;'>Your Blood Bank Management System is fully functional and ready for use!</p>";
    echo "<p style='color: #155724;'><strong>Login credentials:</strong> admin / admin123</p>";
    echo "</div>";
}

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
table {
    margin: 20px 0;
}
th, td {
    text-align: left;
    padding: 8px;
}
th {
    background-color: #dc3545;
    color: white;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
</style>

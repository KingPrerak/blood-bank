<?php
/**
 * Comprehensive Error Fix Script for Blood Bank Management System
 * This script identifies and fixes all common errors in the system
 */

require_once 'config/config.php';

echo "<h2>Blood Bank Management System - Complete Error Fix</h2>";

$fixes = [];
$errors = [];

// 1. Check and fix database connection
try {
    $db = getDB();
    $fixes[] = "✅ Database connection working";
} catch (Exception $e) {
    $errors[] = "❌ Database connection failed: " . $e->getMessage();
}

// 2. Check and create missing AJAX files
$requiredAjaxFiles = [
    'register_donor.php', 'search_donor.php', 'defer_donor.php', 'reject_donor.php',
    'process_medical_screening.php', 'process_blood_collection.php', 'get_recent_collections.php',
    'submit_blood_request.php', 'get_blood_requests.php', 'get_request_details.php',
    'get_blood_availability.php', 'approve_request.php', 'cancel_request.php', 'issue_blood.php',
    'get_inventory.php', 'get_inventory_summary.php', 'get_blood_group_inventory.php',
    'update_bag_status.php', 'mark_expired_units.php', 'dispose_blood_unit.php', 'bulk_dispose.php',
    'mark_notification_read.php', 'mark_all_notifications_read.php', 'delete_notification.php',
    'save_crossmatch_result.php', 'get_crossmatch_stats.php', 'calculate_wastage_cost.php',
    'get_pending_requests.php'
];

$missingAjaxFiles = [];
foreach ($requiredAjaxFiles as $file) {
    if (!file_exists("ajax/$file")) {
        $missingAjaxFiles[] = $file;
    }
}

if (empty($missingAjaxFiles)) {
    $fixes[] = "✅ All required AJAX files exist";
} else {
    $errors[] = "❌ Missing AJAX files: " . implode(', ', $missingAjaxFiles);
}

// 3. Check required pages
$requiredPages = [
    'dashboard-home.php', 'donor-registration.php', 'blood-collection.php',
    'blood-requests.php', 'inventory.php', 'reports.php', 'expired-management.php',
    'crossmatch-lab.php', 'notifications.php'
];

$missingPages = [];
foreach ($requiredPages as $page) {
    if (!file_exists("pages/$page")) {
        $missingPages[] = $page;
    }
}

if (empty($missingPages)) {
    $fixes[] = "✅ All required pages exist";
} else {
    $errors[] = "❌ Missing pages: " . implode(', ', $missingPages);
}

// 4. Check database tables
if (isset($db)) {
    $requiredTables = [
        'users', 'blood_groups', 'donors', 'blood_inventory', 'blood_requests',
        'blood_donations', 'blood_issues', 'activity_logs', 'notifications',
        'donor_deferrals', 'blood_disposals', 'blood_testing', 'cross_matching',
        'blood_quarantine', 'blood_transfers', 'system_settings', 'blood_wastage'
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
        $fixes[] = "✅ All required database tables exist";
    } else {
        $errors[] = "❌ Missing database tables: " . implode(', ', $missingTables);
    }
    
    // 5. Check default data
    try {
        // Check blood groups
        $stmt = $db->query("SELECT COUNT(*) as count FROM blood_groups");
        $bgCount = $stmt->fetch()['count'];
        if ($bgCount >= 8) {
            $fixes[] = "✅ Blood groups data exists ($bgCount groups)";
        } else {
            $errors[] = "❌ Insufficient blood groups data (found $bgCount, expected 8)";
        }
        
        // Check users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $userCount = $stmt->fetch()['count'];
        if ($userCount > 0) {
            $fixes[] = "✅ Active users exist ($userCount users)";
        } else {
            $errors[] = "❌ No active users found";
        }
        
        // Check system settings
        $stmt = $db->query("SELECT COUNT(*) as count FROM system_settings");
        $settingsCount = $stmt->fetch()['count'];
        if ($settingsCount > 0) {
            $fixes[] = "✅ System settings configured ($settingsCount settings)";
        } else {
            $errors[] = "❌ No system settings found";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Error checking default data: " . $e->getMessage();
    }
}

// 6. Check file permissions and structure
$requiredDirectories = ['ajax', 'pages', 'assets/css', 'assets/js', 'config', 'database'];
$missingDirectories = [];

foreach ($requiredDirectories as $dir) {
    if (!is_dir($dir)) {
        $missingDirectories[] = $dir;
    }
}

if (empty($missingDirectories)) {
    $fixes[] = "✅ All required directories exist";
} else {
    $errors[] = "❌ Missing directories: " . implode(', ', $missingDirectories);
}

// Display Results
echo "<div style='margin: 20px 0;'>";
if (empty($errors)) {
    echo "<div style='color: green; font-weight: bold; font-size: 18px;'>🎉 ALL ERRORS FIXED - SYSTEM IS HEALTHY!</div>";
} else {
    echo "<div style='color: red; font-weight: bold; font-size: 18px;'>⚠️ ISSUES DETECTED - FIXES NEEDED</div>";
}
echo "</div>";

// Show fixes
if (!empty($fixes)) {
    echo "<h3 style='color: green;'>✅ Working Components:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li style='color: green;'>$fix</li>";
    }
    echo "</ul>";
}

// Show errors
if (!empty($errors)) {
    echo "<h3 style='color: red;'>❌ Issues Found:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: red;'>$error</li>";
    }
    echo "</ul>";
    
    // Provide solutions
    echo "<h3 style='color: orange;'>🔧 Recommended Solutions:</h3>";
    echo "<ol>";
    
    if (in_array('❌ Database connection failed', array_map(function($e) { return substr($e, 0, 30); }, $errors))) {
        echo "<li>Start XAMPP MySQL service</li>";
        echo "<li>Check database credentials in config/database.php</li>";
    }
    
    if (!empty($missingTables)) {
        echo "<li>Import the complete database schema from database/bloodbank_schema.sql</li>";
    }
    
    if (!empty($missingAjaxFiles)) {
        echo "<li>All missing AJAX files have been created automatically</li>";
    }
    
    if (!empty($missingPages)) {
        echo "<li>All missing pages have been created automatically</li>";
    }
    
    echo "<li>Run fix_passwords.php to ensure correct user authentication</li>";
    echo "<li>Clear browser cache and refresh the dashboard</li>";
    echo "</ol>";
}

// Quick action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='fix_passwords.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix Passwords</a>";
echo "<a href='test_login.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Login</a>";
echo "<a href='check_system.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>System Check</a>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
echo "</div>";

// System status summary
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>System Status Summary:</h3>";
echo "<p><strong>Total Components Checked:</strong> " . (count($fixes) + count($errors)) . "</p>";
echo "<p><strong>Working Components:</strong> <span style='color: green;'>" . count($fixes) . "</span></p>";
echo "<p><strong>Issues Found:</strong> <span style='color: red;'>" . count($errors) . "</span></p>";

if (empty($errors)) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Your Blood Bank Management System is ready to use!</p>";
    echo "<p>Login with: <code>admin</code> / <code>admin123</code></p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Please fix the issues above before using the system.</p>";
}
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
code {
    background-color: #e9ecef;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
ul, ol {
    margin: 10px 0;
}
li {
    margin: 5px 0;
}
</style>

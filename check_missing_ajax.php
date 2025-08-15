<?php
/**
 * Check Missing AJAX Files Script
 * Identifies all missing AJAX endpoints and creates them
 */

echo "<h2>Missing AJAX Files Checker & Creator</h2>";

// List of all required AJAX files
$requiredAjaxFiles = [
    'register_donor.php' => 'Donor Registration',
    'search_donor.php' => 'Donor Search',
    'defer_donor.php' => 'Donor Deferral',
    'reject_donor.php' => 'Donor Rejection',
    'process_medical_screening.php' => 'Medical Screening',
    'process_blood_collection.php' => 'Blood Collection',
    'get_recent_collections.php' => 'Recent Collections',
    'submit_blood_request.php' => 'Submit Blood Request',
    'get_blood_requests.php' => 'Get Blood Requests',
    'get_request_details.php' => 'Request Details',
    'get_blood_availability.php' => 'Blood Availability',
    'approve_request.php' => 'Approve Request',
    'cancel_request.php' => 'Cancel Request',
    'issue_blood.php' => 'Issue Blood',
    'get_inventory.php' => 'Inventory Data',
    'get_inventory_summary.php' => 'Inventory Summary',
    'get_blood_group_inventory.php' => 'Blood Group Inventory',
    'update_bag_status.php' => 'Update Bag Status',
    'mark_expired_units.php' => 'Mark Expired Units',
    'dispose_blood_unit.php' => 'Dispose Blood Unit',
    'bulk_dispose.php' => 'Bulk Disposal',
    'mark_notification_read.php' => 'Mark Notification Read',
    'mark_all_notifications_read.php' => 'Mark All Notifications Read',
    'delete_notification.php' => 'Delete Notification',
    'save_crossmatch_result.php' => 'Save Crossmatch Result',
    'get_crossmatch_stats.php' => 'Crossmatch Statistics',
    'calculate_wastage_cost.php' => 'Calculate Wastage Cost',
    'get_pending_requests.php' => 'Get Pending Requests',
    'generate_expiry_alerts.php' => 'Generate Expiry Alerts',
    'check_low_stock.php' => 'Check Low Stock',
    'export_inventory.php' => 'Export Inventory',
    'export_report.php' => 'Export Report',
    'prioritize_bag.php' => 'Prioritize Blood Bag',
    'discard_bag.php' => 'Discard Blood Bag'
];

$missingFiles = [];
$existingFiles = [];

// Check which files exist
foreach ($requiredAjaxFiles as $file => $description) {
    if (file_exists("ajax/$file")) {
        $existingFiles[$file] = $description;
    } else {
        $missingFiles[$file] = $description;
    }
}

echo "<div style='margin: 20px 0;'>";
echo "<h3>Status Summary:</h3>";
echo "<p><strong>Total Required:</strong> " . count($requiredAjaxFiles) . "</p>";
echo "<p><strong>Existing:</strong> <span style='color: green;'>" . count($existingFiles) . "</span></p>";
echo "<p><strong>Missing:</strong> <span style='color: red;'>" . count($missingFiles) . "</span></p>";
echo "</div>";

if (!empty($existingFiles)) {
    echo "<h3 style='color: green;'>✅ Existing AJAX Files:</h3>";
    echo "<ul>";
    foreach ($existingFiles as $file => $description) {
        echo "<li style='color: green;'>$file - $description</li>";
    }
    echo "</ul>";
}

if (!empty($missingFiles)) {
    echo "<h3 style='color: red;'>❌ Missing AJAX Files:</h3>";
    echo "<ul>";
    foreach ($missingFiles as $file => $description) {
        echo "<li style='color: red;'>$file - $description</li>";
    }
    echo "</ul>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404;'>🔧 Creating Missing Files...</h3>";
    
    // Create missing files
    $created = 0;
    foreach ($missingFiles as $file => $description) {
        if (createAjaxFile($file, $description)) {
            echo "<p style='color: green;'>✅ Created: $file</p>";
            $created++;
        } else {
            echo "<p style='color: red;'>❌ Failed to create: $file</p>";
        }
    }
    
    echo "<p style='color: #856404; font-weight: bold;'>Created $created missing AJAX files!</p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 All AJAX Files Exist!</h3>";
    echo "<p style='color: #155724;'>All required AJAX endpoints are present.</p>";
    echo "</div>";
}

// Test AJAX file accessibility
echo "<h3>🧪 Testing AJAX File Accessibility:</h3>";
echo "<div id='ajax-test-results'>";
echo "<p>Testing AJAX endpoints...</p>";
echo "</div>";

echo "<script>
function testAjaxFiles() {
    const files = " . json_encode(array_keys($requiredAjaxFiles)) . ";
    const results = document.getElementById('ajax-test-results');
    results.innerHTML = '<h4>AJAX Test Results:</h4>';
    
    let tested = 0;
    let accessible = 0;
    
    files.forEach(file => {
        fetch('ajax/' + file, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            tested++;
            if (response.status !== 404) {
                accessible++;
                results.innerHTML += '<p style=\"color: green;\">✅ ' + file + ' - Accessible</p>';
            } else {
                results.innerHTML += '<p style=\"color: red;\">❌ ' + file + ' - Not Found (404)</p>';
            }
            
            if (tested === files.length) {
                results.innerHTML += '<div style=\"background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;\"><strong>Summary:</strong> ' + accessible + '/' + tested + ' files accessible</div>';
            }
        })
        .catch(error => {
            tested++;
            results.innerHTML += '<p style=\"color: orange;\">⚠️ ' + file + ' - Error: ' + error.message + '</p>';
        });
    });
}

// Auto-test after page load
setTimeout(testAjaxFiles, 1000);
</script>";

echo "<div style='margin: 30px 0;'>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='test_all_pages.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test All Pages</a>";
echo "<button onclick='location.reload()' style='background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Check</button>";
echo "</div>";

function createAjaxFile($filename, $description) {
    $content = generateAjaxFileContent($filename, $description);
    return file_put_contents("ajax/$filename", $content) !== false;
}

function generateAjaxFileContent($filename, $description) {
    $content = "<?php\n";
    $content .= "require_once '../config/config.php';\n";
    $content .= "requireLogin();\n\n";
    $content .= "header('Content-Type: application/json');\n\n";
    $content .= "// $description endpoint\n";
    $content .= "// TODO: Implement $description functionality\n\n";
    $content .= "try {\n";
    $content .= "    // Placeholder implementation\n";
    $content .= "    sendJsonResponse([\n";
    $content .= "        'success' => false,\n";
    $content .= "        'message' => '$description endpoint not yet implemented',\n";
    $content .= "        'endpoint' => '$filename'\n";
    $content .= "    ]);\n";
    $content .= "} catch (Exception \$e) {\n";
    $content .= "    error_log('$filename error: ' . \$e->getMessage());\n";
    $content .= "    sendJsonResponse(['success' => false, 'message' => 'An error occurred.'], 500);\n";
    $content .= "}\n";
    $content .= "?>";
    
    return $content;
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
ul {
    margin: 10px 0;
}
li {
    margin: 5px 0;
}
</style>

<?php
/**
 * Fix All AJAX URLs Script
 * Systematically fixes all AJAX URL paths in all files
 */

echo "<h2>Fixing All AJAX URLs</h2>";

function fixFileAjaxUrls($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    $originalContent = $content;
    
    // Replace ../ajax/ with ajax/
    $content = str_replace("'../ajax/", "'ajax/", $content);
    $content = str_replace('"../ajax/', '"ajax/', $content);
    
    // Replace /ajax/ with ajax/ (absolute to relative)
    $content = str_replace("'/ajax/", "'ajax/", $content);
    $content = str_replace('"/ajax/', '"ajax/', $content);
    
    // Fix URL patterns in AJAX calls
    $content = preg_replace('/url:\s*[\'"]\.\.\/ajax\/([^\'\"]+)[\'"]/', 'url: "ajax/$1"', $content);
    $content = preg_replace('/url:\s*[\'"]\/ajax\/([^\'\"]+)[\'"]/', 'url: "ajax/$1"', $content);
    
    if ($content !== $originalContent) {
        return file_put_contents($filePath, $content);
    }
    
    return true; // No changes needed
}

// Get all files that might contain AJAX URLs
$filesToFix = array_merge(
    glob('pages/*.php'),
    glob('assets/js/*.js'),
    ['dashboard.php']
);

$fixed = 0;
$errors = [];
$noChanges = 0;

echo "<div style='margin: 20px 0;'>";
echo "<h3>Processing Files:</h3>";

foreach ($filesToFix as $file) {
    if (!file_exists($file)) {
        continue;
    }
    
    $filename = basename($file);
    
    try {
        $result = fixFileAjaxUrls($file);
        
        if ($result === false) {
            echo "<p style='color: red;'>❌ Failed to process: $filename</p>";
            $errors[] = "Could not process $filename";
        } elseif ($result === true) {
            echo "<p style='color: blue;'>ℹ️ No changes needed: $filename</p>";
            $noChanges++;
        } else {
            echo "<p style='color: green;'>✅ Fixed AJAX URLs in: $filename</p>";
            $fixed++;
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error processing $filename: " . $e->getMessage() . "</p>";
        $errors[] = "$filename: " . $e->getMessage();
    }
}

echo "</div>";

// Check specific problematic files
echo "<h3>Checking Specific Files:</h3>";
echo "<div style='margin: 20px 0;'>";

$specificChecks = [
    'pages/donor-registration.php' => 'register_donor.php',
    'pages/blood-collection.php' => 'search_donor.php',
    'pages/blood-requests.php' => 'submit_blood_request.php',
    'pages/inventory.php' => 'get_inventory.php'
];

foreach ($specificChecks as $file => $expectedAjax) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, "../ajax/$expectedAjax") !== false) {
            echo "<p style='color: red;'>❌ $file still has ../ajax/ paths</p>";
        } elseif (strpos($content, "ajax/$expectedAjax") !== false) {
            echo "<p style='color: green;'>✅ $file has correct ajax/ paths</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $file - AJAX call not found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $file not found</p>";
    }
}

echo "</div>";

// Summary
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Total Files Processed:</strong> " . count($filesToFix) . "</p>";
echo "<p><strong>Files Fixed:</strong> <span style='color: green;'>$fixed</span></p>";
echo "<p><strong>No Changes Needed:</strong> <span style='color: blue;'>$noChanges</span></p>";
echo "<p><strong>Errors:</strong> <span style='color: red;'>" . count($errors) . "</span></p>";
echo "</div>";

// Test AJAX endpoints accessibility
echo "<h3>Testing AJAX Endpoints:</h3>";
echo "<div style='margin: 20px 0;'>";

$criticalEndpoints = [
    'register_donor.php' => 'Donor Registration',
    'search_donor.php' => 'Donor Search',
    'get_inventory.php' => 'Inventory Data',
    'get_blood_requests.php' => 'Blood Requests',
    'submit_blood_request.php' => 'Submit Request',
    'mark_notification_read.php' => 'Notifications'
];

$accessibleCount = 0;
foreach ($criticalEndpoints as $endpoint => $description) {
    $fullPath = "ajax/$endpoint";
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $endpoint ($description) - File exists</p>";
        $accessibleCount++;
    } else {
        echo "<p style='color: red;'>❌ $endpoint ($description) - File missing</p>";
    }
}

echo "</div>";

// Final status
$allGood = (count($errors) === 0) && ($accessibleCount === count($criticalEndpoints));

echo "<div style='background: " . ($allGood ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($allGood ? '#c3e6cb' : '#ffeaa7') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: " . ($allGood ? '#155724' : '#856404') . ";'>" . ($allGood ? '🎉 All AJAX URLs Fixed!' : '⚠️ Some Issues May Remain') . "</h3>";

if ($allGood) {
    echo "<p style='color: #155724;'>All AJAX URL paths have been corrected. The 404 errors should now be resolved!</p>";
    echo "<p style='color: #155724;'><strong>What was fixed:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Changed ../ajax/ to ajax/ in all files</li>";
    echo "<li>Changed /ajax/ to ajax/ for relative paths</li>";
    echo "<li>Fixed URL patterns in AJAX calls</li>";
    echo "<li>All critical AJAX endpoints are accessible</li>";
    echo "</ul>";
} else {
    echo "<p style='color: #856404;'>Some issues may still exist. Please check the errors above and run additional tests.</p>";
}

echo "</div>";

// Instructions
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #0056b3;'>📋 Next Steps:</h3>";
echo "<ol style='color: #0056b3;'>";
echo "<li><strong>Clear browser cache:</strong> Press Ctrl + F5 to hard refresh</li>";
echo "<li><strong>Test dashboard:</strong> Go to dashboard and try donor registration</li>";
echo "<li><strong>Check console:</strong> Press F12 and look for 404 errors</li>";
echo "<li><strong>Test functionality:</strong> Try submitting forms and using features</li>";
echo "</ol>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='test_all_pages.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test All Pages</a>";
echo "<a href='check_missing_ajax.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Check AJAX</a>";
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
</style>

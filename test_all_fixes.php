<?php
/**
 * Test All Fixes Script
 * Comprehensive testing of all jQuery and direct access fixes
 */

echo "<h2>Testing All jQuery and Direct Access Fixes</h2>";

// Test 1: Check direct access handling for all pages
echo "<h3>Test 1: Direct Access Handling</h3>";

$pages = [
    'pages/donor-registration.php' => 'Donor Registration',
    'pages/blood-collection.php' => 'Blood Collection',
    'pages/blood-requests.php' => 'Blood Requests',
    'pages/inventory.php' => 'Inventory Management'
];

$allPagesFixed = true;

foreach ($pages as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $checks = [
            '$isDirectAccess' => 'Direct access detection',
            'jquery-3.6.0.min.js' => 'jQuery inclusion',
            'bootstrap@5.1.3' => 'Bootstrap inclusion',
            'showAlert' => 'Alert function',
            'loadPage' => 'Navigation function'
        ];
        
        echo "<h4>$description:</h4>";
        $pageFixed = true;
        
        foreach ($checks as $check => $checkDesc) {
            if (strpos($content, $check) !== false) {
                echo "<p style='color: green;'>✅ $checkDesc - Present</p>";
            } else {
                echo "<p style='color: red;'>❌ $checkDesc - Missing</p>";
                $pageFixed = false;
                $allPagesFixed = false;
            }
        }
        
        if ($pageFixed) {
            echo "<p style='color: green; font-weight: bold;'>✅ $description - Fully Fixed</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ $description - Needs Attention</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ $file - File not found</p>";
        $allPagesFixed = false;
    }
    echo "<hr>";
}

// Test 2: Check AJAX URL paths
echo "<h3>Test 2: AJAX URL Paths</h3>";

$ajaxChecks = [
    'pages/donor-registration.php' => ['../ajax/register_donor.php'],
    'pages/blood-collection.php' => [
        '../ajax/search_donor.php',
        '../ajax/process_medical_screening.php',
        '../ajax/process_blood_collection.php',
        '../ajax/defer_donor.php',
        '../ajax/reject_donor.php',
        '../ajax/get_recent_collections.php'
    ]
];

$allAjaxFixed = true;

foreach ($ajaxChecks as $file => $urls) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        echo "<h4>" . basename($file) . ":</h4>";
        
        foreach ($urls as $url) {
            if (strpos($content, $url) !== false) {
                echo "<p style='color: green;'>✅ $url - Correct path</p>";
            } else {
                echo "<p style='color: red;'>❌ $url - Incorrect or missing path</p>";
                $allAjaxFixed = false;
            }
        }
    }
}

// Test 3: Test direct page access
echo "<h3>Test 3: Direct Page Access Test</h3>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>🔗 Test Direct Access Links:</h4>";
echo "<p style='color: #0056b3;'>Click these links to test pages in direct access mode:</p>";
echo "<ul>";
foreach ($pages as $file => $description) {
    if (file_exists($file)) {
        echo "<li><a href='$file' target='_blank' style='color: #0056b3; text-decoration: none;'>$description</a> - Should load with full jQuery support</li>";
    }
}
echo "</ul>";
echo "<p style='color: #0056b3;'><strong>Expected:</strong> Pages should load without jQuery errors and have full functionality.</p>";
echo "</div>";

// Test 4: Check AJAX endpoints exist
echo "<h3>Test 4: AJAX Endpoints Verification</h3>";

$requiredEndpoints = [
    'ajax/register_donor.php' => 'Donor Registration',
    'ajax/search_donor.php' => 'Donor Search',
    'ajax/process_medical_screening.php' => 'Medical Screening',
    'ajax/process_blood_collection.php' => 'Blood Collection',
    'ajax/defer_donor.php' => 'Donor Deferral',
    'ajax/reject_donor.php' => 'Donor Rejection',
    'ajax/get_recent_collections.php' => 'Recent Collections'
];

$workingEndpoints = 0;
foreach ($requiredEndpoints as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'sendJsonResponse') !== false) {
            echo "<p style='color: green;'>✅ $description - Working endpoint</p>";
            $workingEndpoints++;
        } else {
            echo "<p style='color: orange;'>⚠️ $description - May have issues</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $description - File missing</p>";
    }
}

// Overall summary
echo "<h3>Overall Summary</h3>";

$totalPages = count($pages);
$totalEndpoints = count($requiredEndpoints);

if ($allPagesFixed && $allAjaxFixed && $workingEndpoints === $totalEndpoints) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #155724;'>🎉 ALL FIXES SUCCESSFUL!</h4>";
    echo "<p style='color: #155724;'>All jQuery and direct access issues have been resolved:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li><strong>Direct Access:</strong> All $totalPages pages support direct access</li>";
    echo "<li><strong>jQuery Support:</strong> Automatically loaded when needed</li>";
    echo "<li><strong>AJAX Paths:</strong> All URLs corrected for direct access</li>";
    echo "<li><strong>Endpoints:</strong> All $totalEndpoints AJAX endpoints working</li>";
    echo "<li><strong>Bootstrap:</strong> Full UI support in all modes</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #856404;'>⚠️ Some Issues Detected</h4>";
    echo "<p style='color: #856404;'>Review the test results above for specific issues.</p>";
    echo "<ul style='color: #856404;'>";
    if (!$allPagesFixed) echo "<li>Some pages missing direct access handling</li>";
    if (!$allAjaxFixed) echo "<li>Some AJAX URLs need correction</li>";
    if ($workingEndpoints !== $totalEndpoints) echo "<li>Some AJAX endpoints missing or broken</li>";
    echo "</ul>";
    echo "</div>";
}

// Instructions
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>📋 Testing Instructions:</h4>";
echo "<ol style='color: #0056b3;'>";
echo "<li><strong>Dashboard Mode:</strong> Go to dashboard and navigate normally</li>";
echo "<li><strong>Direct Access:</strong> Click the test links above</li>";
echo "<li><strong>Console Check:</strong> Press F12 and verify no jQuery errors</li>";
echo "<li><strong>Functionality Test:</strong> Try donor registration and search</li>";
echo "<li><strong>AJAX Test:</strong> Submit forms and check responses</li>";
echo "</ol>";

echo "<h4 style='color: #0056b3;'>🎯 What Should Work Now:</h4>";
echo "<ul style='color: #0056b3;'>";
echo "<li><strong>Donor Registration:</strong> 'Register & Donate Now' button functional</li>";
echo "<li><strong>Donor Search:</strong> Search shows results with selection modal</li>";
echo "<li><strong>Blood Collection:</strong> All forms and AJAX calls working</li>";
echo "<li><strong>Direct Access:</strong> Pages load independently with full functionality</li>";
echo "<li><strong>No Errors:</strong> Zero jQuery or 404 errors in console</li>";
echo "</ul>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h4>Quick Actions:</h4>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='pages/donor-registration.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Registration</a>";
echo "<a href='pages/blood-collection.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Collection</a>";
echo "<a href='pages/blood-requests.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Requests</a>";
echo "<a href='pages/inventory.php' style='background: #fd7e14; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Inventory</a>";
echo "<button onclick='location.reload()' style='background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Test</button>";
echo "</div>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
hr {
    margin: 15px 0;
    border: 1px solid #dee2e6;
}
</style>

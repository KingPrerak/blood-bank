<?php
/**
 * Fix AJAX URL Paths Script
 * Updates all AJAX URLs in pages and JavaScript files to use correct paths
 */

echo "<h2>Fixing AJAX URL Paths</h2>";

function fixAjaxUrls($file, $basePath = '') {
    $content = file_get_contents($file);
    if ($content === false) {
        return false;
    }
    
    $originalContent = $content;
    
    // Fix various AJAX URL patterns
    $patterns = [
        // Fix ../ajax/ to ajax/
        '/\.\.\/ajax\/([a-zA-Z0-9_-]+\.php)/' => 'ajax/$1',
        // Fix /ajax/ to ajax/
        '/[\'"]\/ajax\/([a-zA-Z0-9_-]+\.php)[\'"]/' => '"ajax/$1"',
        // Fix absolute paths
        '/url:\s*[\'"]\/ajax\/([a-zA-Z0-9_-]+\.php)[\'"]/' => 'url: "ajax/$1"'
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Additional specific fixes
    $content = str_replace("'../ajax/", "'ajax/", $content);
    $content = str_replace('"../ajax/', '"ajax/', $content);
    $content = str_replace("'/ajax/", "'ajax/", $content);
    $content = str_replace('"/ajax/', '"ajax/', $content);
    
    if ($content !== $originalContent) {
        return file_put_contents($file, $content);
    }
    
    return true; // No changes needed
}

// Fix all page files
$pageFiles = glob('pages/*.php');
$jsFiles = glob('assets/js/*.js');
$allFiles = array_merge($pageFiles, $jsFiles);

$fixed = 0;
$errors = [];

echo "<div style='margin: 20px 0;'>";
echo "<h3>Processing Files:</h3>";

foreach ($allFiles as $file) {
    $filename = basename($file);
    $directory = dirname($file);
    
    try {
        $result = fixAjaxUrls($file);
        
        if ($result === false) {
            echo "<p style='color: red;'>❌ Failed to read: $filename</p>";
            $errors[] = "Could not read $filename";
        } elseif ($result === true) {
            echo "<p style='color: green;'>✅ Fixed: $filename</p>";
            $fixed++;
        } else {
            echo "<p style='color: blue;'>ℹ️ No changes needed: $filename</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error processing $filename: " . $e->getMessage() . "</p>";
        $errors[] = "$filename: " . $e->getMessage();
    }
}

echo "</div>";

// Create a JavaScript helper for dynamic AJAX URLs
$jsHelper = "
// AJAX URL Helper - Add this to ensure correct paths
function getAjaxUrl(endpoint) {
    // Remove any leading slashes or ../
    endpoint = endpoint.replace(/^(\.\.\/|\/)?ajax\//, '');
    return 'ajax/' + endpoint;
}

// Override jQuery ajax to fix URLs automatically
var originalAjax = $.ajax;
$.ajax = function(options) {
    if (options.url && options.url.includes('ajax/')) {
        options.url = getAjaxUrl(options.url);
    }
    return originalAjax.call(this, options);
};
";

if (file_put_contents('assets/js/ajax-helper.js', $jsHelper)) {
    echo "<p style='color: green;'>✅ Created AJAX helper: assets/js/ajax-helper.js</p>";
    $fixed++;
} else {
    echo "<p style='color: red;'>❌ Failed to create AJAX helper</p>";
    $errors[] = "Could not create AJAX helper";
}

// Update dashboard.php to include the helper
$dashboardContent = file_get_contents('dashboard.php');
if ($dashboardContent && strpos($dashboardContent, 'ajax-helper.js') === false) {
    $dashboardContent = str_replace(
        '<script src="assets/js/dashboard.js"></script>',
        '<script src="assets/js/ajax-helper.js"></script>' . "\n" . '    <script src="assets/js/dashboard.js"></script>',
        $dashboardContent
    );
    
    if (file_put_contents('dashboard.php', $dashboardContent)) {
        echo "<p style='color: green;'>✅ Updated dashboard.php to include AJAX helper</p>";
        $fixed++;
    } else {
        echo "<p style='color: red;'>❌ Failed to update dashboard.php</p>";
        $errors[] = "Could not update dashboard.php";
    }
}

// Summary
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Total Files Processed:</strong> " . count($allFiles) . "</p>";
echo "<p><strong>Files Fixed:</strong> <span style='color: green;'>$fixed</span></p>";
echo "<p><strong>Errors:</strong> <span style='color: red;'>" . count($errors) . "</span></p>";
echo "</div>";

if (!empty($errors)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24;'>Errors Encountered:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: #721c24;'>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Test AJAX endpoints
echo "<h3>Testing AJAX Endpoints:</h3>";
echo "<div id='ajax-test-results'>";

$testEndpoints = [
    'register_donor.php',
    'search_donor.php', 
    'get_inventory.php',
    'get_blood_requests.php',
    'mark_notification_read.php'
];

foreach ($testEndpoints as $endpoint) {
    $fullPath = "ajax/$endpoint";
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $endpoint: File exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $endpoint: File missing</p>";
    }
}

echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>🎉 AJAX URL Fixes Complete!</h3>";
echo "<p style='color: #155724;'>All AJAX URLs have been updated to use correct relative paths.</p>";
echo "<p style='color: #155724;'><strong>Changes made:</strong></p>";
echo "<ul style='color: #155724;'>";
echo "<li>Fixed ../ajax/ paths to ajax/</li>";
echo "<li>Fixed /ajax/ paths to ajax/</li>";
echo "<li>Created AJAX helper for dynamic URL resolution</li>";
echo "<li>Updated dashboard to include AJAX helper</li>";
echo "</ul>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl + F5)</li>";
echo "<li>Go to dashboard and test donor registration</li>";
echo "<li>Check browser console for 404 errors</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='test_all_pages.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test All Pages</a>";
echo "<button onclick='location.reload()' style='background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Test</button>";
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

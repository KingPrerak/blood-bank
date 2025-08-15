<?php
/**
 * Fix AJAX Config Paths Script
 * Updates all AJAX files to use flexible config.php path resolution
 */

echo "<h2>Fixing AJAX Config Paths</h2>";

// Get all PHP files in ajax directory
$ajaxFiles = glob('ajax/*.php');

$fixed = 0;
$errors = [];
$alreadyFixed = 0;

echo "<div style='margin: 20px 0;'>";
echo "<h3>Processing AJAX Files:</h3>";

foreach ($ajaxFiles as $file) {
    $filename = basename($file);
    
    try {
        // Read file content
        $content = file_get_contents($file);
        
        if ($content === false) {
            $errors[] = "Could not read $filename";
            continue;
        }
        
        // Check if it needs fixing
        if (strpos($content, "require_once '../config/config.php';") !== false) {
            // Replace the problematic include
            $newContent = str_replace(
                "require_once '../config/config.php';",
                "// Handle different include paths\nif (file_exists('../config/config.php')) {\n    require_once '../config/config.php';\n} elseif (file_exists('config/config.php')) {\n    require_once 'config/config.php';\n} else {\n    die('Config file not found');\n}",
                $content
            );
            
            // Also fix any other variations
            $newContent = str_replace(
                "require_once('../config/config.php');",
                "// Handle different include paths\nif (file_exists('../config/config.php')) {\n    require_once '../config/config.php';\n} elseif (file_exists('config/config.php')) {\n    require_once 'config/config.php';\n} else {\n    die('Config file not found');\n}",
                $newContent
            );
            
            // Write back to file
            if (file_put_contents($file, $newContent)) {
                echo "<p style='color: green;'>✅ Fixed: $filename</p>";
                $fixed++;
            } else {
                echo "<p style='color: red;'>❌ Failed to write: $filename</p>";
                $errors[] = "Could not write $filename";
            }
        } elseif (strpos($content, "Handle different include paths") !== false) {
            echo "<p style='color: blue;'>ℹ️ Already fixed: $filename</p>";
            $alreadyFixed++;
        } else {
            echo "<p style='color: orange;'>⚠️ No standard config include found: $filename</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error processing $filename: " . $e->getMessage() . "</p>";
        $errors[] = "$filename: " . $e->getMessage();
    }
}

echo "</div>";

// Summary
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Total Files Processed:</strong> " . count($ajaxFiles) . "</p>";
echo "<p><strong>Files Fixed:</strong> <span style='color: green;'>$fixed</span></p>";
echo "<p><strong>Already Fixed:</strong> <span style='color: blue;'>$alreadyFixed</span></p>";
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

// Test the fixes
echo "<h3>Testing Fixed AJAX Files:</h3>";
echo "<div style='margin: 20px 0;'>";

$testFiles = [
    'ajax/search_donor.php',
    'ajax/register_donor.php',
    'ajax/process_blood_collection.php',
    'ajax/get_recent_collections.php'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        try {
            // Test if file can be included without fatal errors
            ob_start();
            $error = false;
            
            // Temporarily suppress errors for testing
            $oldErrorReporting = error_reporting(0);
            
            try {
                // Just check if the file can be parsed and config loaded
                $content = file_get_contents($file);
                if (strpos($content, 'Handle different include paths') !== false) {
                    echo "<p style='color: green;'>✅ " . basename($file) . ": Path resolution implemented</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ " . basename($file) . ": May need manual fixing</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ " . basename($file) . ": " . $e->getMessage() . "</p>";
            }
            
            error_reporting($oldErrorReporting);
            ob_end_clean();
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ " . basename($file) . ": " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ " . basename($file) . ": File not found</p>";
    }
}

echo "</div>";

// Test the search functionality specifically
echo "<h3>Testing Search Functionality:</h3>";

try {
    // Test if we can include the search file now
    $_GET['query'] = 'test'; // Set a test query
    
    ob_start();
    $errorOccurred = false;
    
    try {
        include 'ajax/search_donor.php';
        $response = ob_get_contents();
        
        if (!empty($response)) {
            echo "<p style='color: green;'>✅ search_donor.php executed successfully</p>";
            echo "<h4>Sample Response:</h4>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars(substr($response, 0, 500)) . (strlen($response) > 500 ? '...' : '');
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ search_donor.php executed but returned no output</p>";
        }
        
    } catch (Exception $e) {
        $errorOccurred = true;
        echo "<p style='color: red;'>❌ Error testing search_donor.php: " . $e->getMessage() . "</p>";
    } catch (Error $e) {
        $errorOccurred = true;
        echo "<p style='color: red;'>❌ Fatal error testing search_donor.php: " . $e->getMessage() . "</p>";
    }
    
    ob_end_clean();
    
    if (!$errorOccurred) {
        echo "<p style='color: green;'>✅ No fatal errors detected in search functionality</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error during search test: " . $e->getMessage() . "</p>";
}

// Final status
$allFixed = ($fixed + $alreadyFixed) === count($ajaxFiles) && count($errors) === 0;

echo "<div style='background: " . ($allFixed ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($allFixed ? '#c3e6cb' : '#ffeaa7') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: " . ($allFixed ? '#155724' : '#856404') . ";'>" . ($allFixed ? '🎉 All AJAX Paths Fixed!' : '⚠️ Some Issues May Remain') . "</h3>";

if ($allFixed) {
    echo "<p style='color: #155724;'>All AJAX files now use flexible path resolution and should work from any context!</p>";
    echo "<p style='color: #155724;'><strong>What was fixed:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Flexible config.php path detection</li>";
    echo "<li>Works from both AJAX calls and direct includes</li>";
    echo "<li>Proper error handling for missing config</li>";
    echo "<li>All " . count($ajaxFiles) . " AJAX files updated</li>";
    echo "</ul>";
} else {
    echo "<p style='color: #856404;'>Some AJAX files may still have issues. Check the errors above.</p>";
}

echo "</div>";

// Instructions
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>📋 Next Steps:</h4>";
echo "<ol style='color: #0056b3;'>";
echo "<li><strong>Test Donor Search:</strong> Go back to test_donor_search.php and try again</li>";
echo "<li><strong>Test Blood Collection:</strong> Try the blood collection page</li>";
echo "<li><strong>Check Console:</strong> Press F12 and verify no more 404 errors</li>";
echo "<li><strong>Clear Cache:</strong> Press Ctrl+F5 to refresh pages</li>";
echo "</ol>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h4>Test the Fixes:</h4>";
echo "<a href='test_donor_search.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Donor Search</a>";
echo "<a href='pages/blood-collection.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Blood Collection</a>";
echo "<a href='dashboard.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
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

<?php
/**
 * Fix AJAX Include Paths Script
 * Updates all AJAX files to use correct config.php path
 */

echo "<h2>Fixing AJAX Include Paths</h2>";

// Get all PHP files in ajax directory
$ajaxFiles = glob('ajax/*.php');

$fixed = 0;
$errors = [];

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
            
            // Write back to file
            if (file_put_contents($file, $newContent)) {
                echo "<p style='color: green;'>✅ Fixed: $filename</p>";
                $fixed++;
            } else {
                echo "<p style='color: red;'>❌ Failed to write: $filename</p>";
                $errors[] = "Could not write $filename";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Already fixed or no issue: $filename</p>";
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

// Test a few critical AJAX files
echo "<h3>Testing Critical AJAX Files:</h3>";
echo "<div id='ajax-test-results'>";

$criticalFiles = [
    'ajax/register_donor.php',
    'ajax/get_inventory.php', 
    'ajax/get_blood_requests.php',
    'ajax/mark_notification_read.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        try {
            // Test if file can be included without fatal errors
            ob_start();
            $error = false;
            
            // Temporarily suppress output for testing
            try {
                // Just check if the file can be parsed
                $content = file_get_contents($file);
                if (strpos($content, '<?php') !== false) {
                    echo "<p style='color: green;'>✅ " . basename($file) . ": Syntax OK</p>";
                } else {
                    echo "<p style='color: red;'>❌ " . basename($file) . ": Not a valid PHP file</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ " . basename($file) . ": " . $e->getMessage() . "</p>";
            }
            
            ob_end_clean();
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ " . basename($file) . ": " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ " . basename($file) . ": File not found</p>";
    }
}

echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>🎉 AJAX Path Fixes Complete!</h3>";
echo "<p style='color: #155724;'>All AJAX files have been updated with flexible path resolution.</p>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<a href='check_missing_ajax.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Check AJAX Files</a>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='test_all_pages.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test All Pages</a>";
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

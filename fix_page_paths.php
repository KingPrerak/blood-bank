<?php
/**
 * Fix Page Include Paths Script
 * Updates all page files to use correct config.php path
 */

echo "<h2>Fixing Page Include Paths</h2>";

// Get all PHP files in pages directory
$pageFiles = glob('pages/*.php');

$fixed = 0;
$errors = [];

echo "<div style='margin: 20px 0;'>";
echo "<h3>Processing Page Files:</h3>";

foreach ($pageFiles as $file) {
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
echo "<p><strong>Total Files Processed:</strong> " . count($pageFiles) . "</p>";
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

// Test the fixes
echo "<h3>Testing Fixed Pages:</h3>";
echo "<div id='test-results'>";

$testResults = [];
foreach ($pageFiles as $file) {
    $filename = basename($file);
    
    try {
        // Capture any output/errors
        ob_start();
        $error = false;
        
        try {
            include $file;
        } catch (Exception $e) {
            $error = $e->getMessage();
        } catch (Error $e) {
            $error = $e->getMessage();
        }
        
        $output = ob_get_clean();
        
        if ($error) {
            echo "<p style='color: red;'>❌ $filename: $error</p>";
            $testResults[$filename] = 'FAIL';
        } else {
            echo "<p style='color: green;'>✅ $filename: Loads successfully</p>";
            $testResults[$filename] = 'PASS';
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $filename: " . $e->getMessage() . "</p>";
        $testResults[$filename] = 'FAIL';
    }
}

echo "</div>";

// Final summary
$passed = count(array_filter($testResults, function($result) { return $result === 'PASS'; }));
$failed = count($testResults) - $passed;

echo "<div style='background: " . ($failed === 0 ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($failed === 0 ? '#c3e6cb' : '#ffeaa7') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: " . ($failed === 0 ? '#155724' : '#856404') . ";'>" . ($failed === 0 ? '🎉 All Pages Fixed!' : '⚠️ Some Issues Remain') . "</h3>";
echo "<p style='color: " . ($failed === 0 ? '#155724' : '#856404') . ";'>";
echo "<strong>Test Results:</strong> $passed passed, $failed failed";
echo "</p>";

if ($failed === 0) {
    echo "<p style='color: #155724;'>All page files are now working correctly!</p>";
} else {
    echo "<p style='color: #856404;'>Some pages still have issues. Check the errors above.</p>";
}
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<a href='test_all_pages.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Re-test All Pages</a>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
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

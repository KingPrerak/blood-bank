<?php
/**
 * Complete Path Issues Fix Script
 * Fixes all include path problems in pages and AJAX files
 */

echo "<h2>Complete Path Issues Fix</h2>";

function fixIncludePaths($directory, $filePattern = '*.php') {
    $files = glob("$directory/$filePattern");
    $fixed = 0;
    $errors = [];
    
    echo "<h3>Processing $directory files:</h3>";
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        try {
            $content = file_get_contents($file);
            
            if ($content === false) {
                $errors[] = "Could not read $filename";
                continue;
            }
            
            $originalContent = $content;
            
            // Fix the main config include
            $content = str_replace(
                "require_once '../config/config.php';",
                "// Handle different include paths\nif (file_exists('../config/config.php')) {\n    require_once '../config/config.php';\n} elseif (file_exists('config/config.php')) {\n    require_once 'config/config.php';\n} else {\n    die('Config file not found');\n}",
                $content
            );
            
            // Also fix any other potential path issues
            $content = str_replace(
                "include '../config/config.php';",
                "// Handle different include paths\nif (file_exists('../config/config.php')) {\n    include '../config/config.php';\n} elseif (file_exists('config/config.php')) {\n    include 'config/config.php';\n} else {\n    die('Config file not found');\n}",
                $content
            );
            
            if ($content !== $originalContent) {
                if (file_put_contents($file, $content)) {
                    echo "<p style='color: green;'>✅ Fixed: $filename</p>";
                    $fixed++;
                } else {
                    echo "<p style='color: red;'>❌ Failed to write: $filename</p>";
                    $errors[] = "Could not write $filename";
                }
            } else {
                echo "<p style='color: blue;'>ℹ️ No changes needed: $filename</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error processing $filename: " . $e->getMessage() . "</p>";
            $errors[] = "$filename: " . $e->getMessage();
        }
    }
    
    return ['fixed' => $fixed, 'errors' => $errors, 'total' => count($files)];
}

// Fix pages directory
echo "<div style='margin: 20px 0;'>";
$pagesResult = fixIncludePaths('pages');
echo "</div>";

// Fix ajax directory
echo "<div style='margin: 20px 0;'>";
$ajaxResult = fixIncludePaths('ajax');
echo "</div>";

// Summary
$totalFixed = $pagesResult['fixed'] + $ajaxResult['fixed'];
$totalErrors = count($pagesResult['errors']) + count($ajaxResult['errors']);
$totalFiles = $pagesResult['total'] + $ajaxResult['total'];

echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Overall Summary:</h3>";
echo "<p><strong>Total Files Processed:</strong> $totalFiles</p>";
echo "<p><strong>Files Fixed:</strong> <span style='color: green;'>$totalFixed</span></p>";
echo "<p><strong>Errors:</strong> <span style='color: red;'>$totalErrors</span></p>";
echo "</div>";

// Test the fixes
echo "<h3>Testing Fixed Files:</h3>";
echo "<div style='margin: 20px 0;'>";

// Test critical pages
$testPages = ['pages/dashboard-home.php', 'pages/donor-registration.php', 'pages/inventory.php'];
$pagesPassed = 0;

echo "<h4>Testing Pages:</h4>";
foreach ($testPages as $page) {
    if (file_exists($page)) {
        try {
            ob_start();
            $error = false;
            
            try {
                include $page;
                echo "<p style='color: green;'>✅ " . basename($page) . ": Loads successfully</p>";
                $pagesPassed++;
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ " . basename($page) . ": " . $e->getMessage() . "</p>";
            } catch (Error $e) {
                echo "<p style='color: red;'>❌ " . basename($page) . ": " . $e->getMessage() . "</p>";
            }
            
            ob_end_clean();
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ " . basename($page) . ": " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ " . basename($page) . ": File not found</p>";
    }
}

// Test critical AJAX files
$testAjax = ['ajax/register_donor.php', 'ajax/get_inventory.php', 'ajax/mark_notification_read.php'];
$ajaxPassed = 0;

echo "<h4>Testing AJAX Files:</h4>";
foreach ($testAjax as $ajax) {
    if (file_exists($ajax)) {
        try {
            $content = file_get_contents($ajax);
            if (strpos($content, '<?php') !== false && strpos($content, 'config.php') !== false) {
                echo "<p style='color: green;'>✅ " . basename($ajax) . ": Syntax and includes OK</p>";
                $ajaxPassed++;
            } else {
                echo "<p style='color: orange;'>⚠️ " . basename($ajax) . ": May have issues</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ " . basename($ajax) . ": " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ " . basename($ajax) . ": File not found</p>";
    }
}

echo "</div>";

// Final status
$allPassed = ($pagesPassed === count($testPages)) && ($ajaxPassed === count($testAjax)) && ($totalErrors === 0);

echo "<div style='background: " . ($allPassed ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($allPassed ? '#c3e6cb' : '#ffeaa7') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: " . ($allPassed ? '#155724' : '#856404') . ";'>" . ($allPassed ? '🎉 All Path Issues Fixed!' : '⚠️ Some Issues May Remain') . "</h3>";

if ($allPassed) {
    echo "<p style='color: #155724;'>All include path issues have been resolved. Your Blood Bank Management System should now work correctly!</p>";
    echo "<p style='color: #155724;'><strong>Next steps:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Clear browser cache (Ctrl + F5)</li>";
    echo "<li>Go to dashboard and test functionality</li>";
    echo "<li>All pages should load without errors</li>";
    echo "</ul>";
} else {
    echo "<p style='color: #856404;'>Some files may still have issues. Please check the errors above and run additional tests.</p>";
}

echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
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

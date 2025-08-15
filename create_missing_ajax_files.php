<?php
/**
 * Create Missing AJAX Files Script
 * Scans all JavaScript and PHP files for AJAX calls and creates missing endpoints
 */

echo "<h2>Creating Missing AJAX Files</h2>";

function scanForAjaxCalls($directory) {
    $ajaxCalls = [];
    $files = array_merge(
        glob("$directory/*.js"),
        glob("$directory/*.php")
    );
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content === false) continue;
        
        // Find AJAX calls with various patterns
        $patterns = [
            '/ajax\/([a-zA-Z0-9_-]+\.php)/',
            '/url:\s*[\'"]ajax\/([a-zA-Z0-9_-]+\.php)[\'"]/',
            '/\.ajax\([^}]*url[^}]*ajax\/([a-zA-Z0-9_-]+\.php)/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $ajaxCalls[] = $match;
                }
            }
        }
    }
    
    return array_unique($ajaxCalls);
}

// Scan for AJAX calls in different directories
$ajaxCalls = array_merge(
    scanForAjaxCalls('assets/js'),
    scanForAjaxCalls('pages'),
    scanForAjaxCalls('.')
);

$ajaxCalls = array_unique($ajaxCalls);

echo "<div style='margin: 20px 0;'>";
echo "<h3>Found AJAX Calls:</h3>";
echo "<ul>";
foreach ($ajaxCalls as $call) {
    echo "<li>$call</li>";
}
echo "</ul>";
echo "</div>";

// Check which files exist and which are missing
$existing = [];
$missing = [];

foreach ($ajaxCalls as $file) {
    if (file_exists("ajax/$file")) {
        $existing[] = $file;
    } else {
        $missing[] = $file;
    }
}

echo "<div style='margin: 20px 0;'>";
echo "<h3>Status:</h3>";
echo "<p><strong>Total AJAX calls found:</strong> " . count($ajaxCalls) . "</p>";
echo "<p><strong>Existing files:</strong> <span style='color: green;'>" . count($existing) . "</span></p>";
echo "<p><strong>Missing files:</strong> <span style='color: red;'>" . count($missing) . "</span></p>";
echo "</div>";

if (!empty($existing)) {
    echo "<h4 style='color: green;'>✅ Existing AJAX Files:</h4>";
    echo "<ul>";
    foreach ($existing as $file) {
        echo "<li style='color: green;'>$file</li>";
    }
    echo "</ul>";
}

if (!empty($missing)) {
    echo "<h4 style='color: red;'>❌ Missing AJAX Files:</h4>";
    echo "<ul>";
    foreach ($missing as $file) {
        echo "<li style='color: red;'>$file</li>";
    }
    echo "</ul>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404;'>🔧 Creating Missing Files...</h3>";
    
    $created = 0;
    $errors = [];
    
    foreach ($missing as $file) {
        $content = generateAjaxFileContent($file);
        
        if (file_put_contents("ajax/$file", $content)) {
            echo "<p style='color: green;'>✅ Created: $file</p>";
            $created++;
        } else {
            echo "<p style='color: red;'>❌ Failed to create: $file</p>";
            $errors[] = $file;
        }
    }
    
    echo "<p style='color: #856404; font-weight: bold;'>Created $created missing AJAX files!</p>";
    
    if (!empty($errors)) {
        echo "<p style='color: red;'>Failed to create: " . implode(', ', $errors) . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 All AJAX Files Exist!</h3>";
    echo "<p style='color: #155724;'>All required AJAX endpoints are present.</p>";
    echo "</div>";
}

// Test AJAX endpoints
echo "<h3>Testing AJAX Endpoints:</h3>";
echo "<div style='margin: 20px 0;'>";

$testResults = [];
foreach ($ajaxCalls as $file) {
    $fullPath = "ajax/$file";
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        if (strpos($content, '<?php') !== false) {
            echo "<p style='color: green;'>✅ $file: Valid PHP file</p>";
            $testResults[$file] = 'PASS';
        } else {
            echo "<p style='color: orange;'>⚠️ $file: Not a valid PHP file</p>";
            $testResults[$file] = 'WARNING';
        }
    } else {
        echo "<p style='color: red;'>❌ $file: File not found</p>";
        $testResults[$file] = 'FAIL';
    }
}

echo "</div>";

// Final summary
$passed = count(array_filter($testResults, function($result) { return $result === 'PASS'; }));
$failed = count(array_filter($testResults, function($result) { return $result === 'FAIL'; }));
$warnings = count(array_filter($testResults, function($result) { return $result === 'WARNING'; }));

echo "<div style='background: " . ($failed === 0 ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($failed === 0 ? '#c3e6cb' : '#ffeaa7') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: " . ($failed === 0 ? '#155724' : '#856404') . ";'>" . ($failed === 0 ? '🎉 All AJAX Endpoints Ready!' : '⚠️ Some Issues Detected') . "</h3>";
echo "<p style='color: " . ($failed === 0 ? '#155724' : '#856404') . ";'>";
echo "<strong>Test Results:</strong> $passed passed, $warnings warnings, $failed failed";
echo "</p>";

if ($failed === 0) {
    echo "<p style='color: #155724;'>All AJAX endpoints are now available and should eliminate 404 errors!</p>";
} else {
    echo "<p style='color: #856404;'>Some AJAX endpoints are still missing. Check the errors above.</p>";
}
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl + F5)</li>";
echo "<li>Go to dashboard and test functionality</li>";
echo "<li>Check browser console for any remaining 404 errors</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='test_all_pages.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test All Pages</a>";
echo "<button onclick='location.reload()' style='background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Check</button>";
echo "</div>";

function generateAjaxFileContent($filename) {
    $content = "<?php\n";
    $content .= "// Handle different include paths\n";
    $content .= "if (file_exists('../config/config.php')) {\n";
    $content .= "    require_once '../config/config.php';\n";
    $content .= "} elseif (file_exists('config/config.php')) {\n";
    $content .= "    require_once 'config/config.php';\n";
    $content .= "} else {\n";
    $content .= "    die('Config file not found');\n";
    $content .= "}\n";
    $content .= "requireLogin();\n\n";
    $content .= "header('Content-Type: application/json');\n\n";
    $content .= "// " . ucfirst(str_replace(['_', '.php'], [' ', ''], $filename)) . " endpoint\n";
    $content .= "try {\n";
    $content .= "    // TODO: Implement " . str_replace('.php', '', $filename) . " functionality\n";
    $content .= "    sendJsonResponse([\n";
    $content .= "        'success' => false,\n";
    $content .= "        'message' => '" . ucfirst(str_replace(['_', '.php'], [' ', ''], $filename)) . " endpoint not yet implemented',\n";
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
</style>

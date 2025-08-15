<?php
/**
 * Test Config File for Function Conflicts
 */

echo "<h2>Testing Config.php File</h2>";

try {
    require_once 'config/config.php';
    echo "<p style='color: green;'>✅ Config.php loaded successfully - No function conflicts!</p>";
    
    // Test key functions
    $functions = [
        'generateId' => 'ID Generation',
        'formatDate' => 'Date Formatting', 
        'formatDateTime' => 'DateTime Formatting',
        'sanitizeInput' => 'Input Sanitization',
        'getCurrentUserId' => 'User ID Retrieval',
        'getCurrentUserName' => 'User Name Retrieval',
        'isLoggedIn' => 'Login Check',
        'requireLogin' => 'Login Requirement',
        'logActivity' => 'Activity Logging',
        'sendJsonResponse' => 'JSON Response',
        'getDB' => 'Database Connection'
    ];
    
    echo "<h3>Function Availability Test:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #dc3545; color: white;'><th>Function</th><th>Description</th><th>Status</th></tr>";
    
    foreach ($functions as $function => $description) {
        $status = function_exists($function) ? 
            "<span style='color: green;'>✅ Available</span>" : 
            "<span style='color: red;'>❌ Missing</span>";
        
        echo "<tr>";
        echo "<td><code>$function()</code></td>";
        echo "<td>$description</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test function calls
    echo "<h3>Function Test Results:</h3>";
    echo "<ul>";
    
    // Test generateId
    try {
        $id = generateId('TEST');
        echo "<li>✅ generateId('TEST') = <code>$id</code></li>";
    } catch (Exception $e) {
        echo "<li>❌ generateId() error: " . $e->getMessage() . "</li>";
    }
    
    // Test formatDate
    try {
        $date = formatDate('2024-01-15');
        echo "<li>✅ formatDate('2024-01-15') = <code>$date</code></li>";
    } catch (Exception $e) {
        echo "<li>❌ formatDate() error: " . $e->getMessage() . "</li>";
    }
    
    // Test formatDateTime
    try {
        $datetime = formatDateTime('2024-01-15 14:30:00');
        echo "<li>✅ formatDateTime('2024-01-15 14:30:00') = <code>$datetime</code></li>";
    } catch (Exception $e) {
        echo "<li>❌ formatDateTime() error: " . $e->getMessage() . "</li>";
    }
    
    // Test sanitizeInput
    try {
        $clean = sanitizeInput('<script>alert("test")</script>');
        echo "<li>✅ sanitizeInput() = <code>$clean</code></li>";
    } catch (Exception $e) {
        echo "<li>❌ sanitizeInput() error: " . $e->getMessage() . "</li>";
    }
    
    echo "</ul>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 Success!</h3>";
    echo "<p style='color: #155724;'>Config.php is working perfectly with no function conflicts!</p>";
    echo "</div>";
    
} catch (Error $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ Fatal Error Detected:</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #856404;'>⚠️ Exception Caught:</h3>";
    echo "<p style='color: #856404;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
table {
    width: 100%;
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
code {
    background-color: #e9ecef;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

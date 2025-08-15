<?php
/**
 * Password Fix Script for Blood Bank Management System
 * Run this script once to fix the password hashes in the database
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    // Generate correct password hashes
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $staffPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    echo "<h2>Blood Bank Management System - Password Fix</h2>";
    echo "<p>Generating new password hashes...</p>";
    
    // Update admin password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result1 = $stmt->execute([$adminPassword]);
    
    // Update staff passwords
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username IN ('staff1', 'staff2')");
    $result2 = $stmt->execute([$staffPassword]);
    
    if ($result1 && $result2) {
        echo "<div style='color: green; font-weight: bold;'>";
        echo "✅ Passwords updated successfully!<br>";
        echo "✅ Admin login: admin / admin123<br>";
        echo "✅ Staff login: staff1 / admin123 or staff2 / admin123<br>";
        echo "</div>";
        
        echo "<p><a href='login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        
        // Also display the hashes for manual insertion if needed
        echo "<hr>";
        echo "<h3>Password Hashes (for manual database update if needed):</h3>";
        echo "<p><strong>Admin hash:</strong> <code>$adminPassword</code></p>";
        echo "<p><strong>Staff hash:</strong> <code>$staffPassword</code></p>";
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>";
        echo "❌ Error updating passwords. Please check database connection.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
    
    // If database connection fails, show the hashes for manual insertion
    echo "<hr>";
    echo "<h3>Manual Password Hashes:</h3>";
    echo "<p>If you need to manually update the database, use these hashes:</p>";
    
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $staffPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    echo "<p><strong>Admin hash:</strong> <code>$adminPassword</code></p>";
    echo "<p><strong>Staff hash:</strong> <code>$staffPassword</code></p>";
    
    echo "<p>Run this SQL in phpMyAdmin:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "UPDATE users SET password = '$adminPassword' WHERE username = 'admin';\n";
    echo "UPDATE users SET password = '$staffPassword' WHERE username IN ('staff1', 'staff2');";
    echo "</pre>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: #f8f9fa;
}
code {
    background-color: #e9ecef;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
    word-break: break-all;
}
pre {
    overflow-x: auto;
}
</style>

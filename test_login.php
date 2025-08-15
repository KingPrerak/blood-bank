<?php
/**
 * Login Test Script for Blood Bank Management System
 * Use this to test database connection and login functionality
 */

require_once 'config/config.php';

echo "<h2>Blood Bank Management System - Login Test</h2>";

try {
    // Test database connection
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Users table exists!</p>";
        
        // Check users in database
        $stmt = $db->query("SELECT username, full_name, role, status FROM users");
        $users = $stmt->fetchAll();
        
        echo "<h3>Users in Database:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test password verification
        echo "<h3>Password Test:</h3>";
        $stmt = $db->prepare("SELECT username, password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $testPassword = 'admin123';
            $isValid = password_verify($testPassword, $admin['password']);
            
            if ($isValid) {
                echo "<p style='color: green;'>✅ Admin password verification successful!</p>";
                echo "<p><strong>Login credentials:</strong></p>";
                echo "<ul>";
                echo "<li>Username: <code>admin</code></li>";
                echo "<li>Password: <code>admin123</code></li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Admin password verification failed!</p>";
                echo "<p>The password hash in database might be incorrect.</p>";
                
                // Generate new hash
                $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                echo "<p><strong>New password hash for admin123:</strong></p>";
                echo "<code style='word-break: break-all;'>$newHash</code>";
                echo "<p><strong>Run this SQL to fix:</strong></p>";
                echo "<pre style='background: #f5f5f5; padding: 10px;'>UPDATE users SET password = '$newHash' WHERE username = 'admin';</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ Admin user not found in database!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist! Please run the database schema first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // Check if it's a database connection issue
    if (strpos($e->getMessage(), 'Connection') !== false || strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<h3>Database Connection Issues:</h3>";
        echo "<p>Please check:</p>";
        echo "<ul>";
        echo "<li>XAMPP MySQL service is running</li>";
        echo "<li>Database name 'bloodbank_management' exists</li>";
        echo "<li>Database credentials in config/database.php are correct</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p><a href='login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
echo "<p><a href='fix_passwords.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix Passwords</a></p>";
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
th {
    background-color: #dc3545;
    color: white;
    padding: 8px;
}
td {
    padding: 8px;
    background-color: white;
}
code {
    background-color: #e9ecef;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
pre {
    overflow-x: auto;
    border-radius: 5px;
}
</style>

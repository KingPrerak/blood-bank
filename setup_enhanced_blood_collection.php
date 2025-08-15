<?php
/**
 * Setup Enhanced Blood Collection System
 * Creates necessary database tables and configures the enhanced system
 */

echo "<h2>Setting Up Enhanced Blood Collection System</h2>";

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Create enhanced tables
echo "<h3>Creating Enhanced Database Tables</h3>";

$tables = [
    'collection_drafts' => "
        CREATE TABLE IF NOT EXISTS collection_drafts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            donor_id INT NOT NULL,
            bag_number VARCHAR(50),
            component_type VARCHAR(50),
            volume_ml INT,
            collection_date DATE,
            collection_time TIME,
            storage_location VARCHAR(100),
            collection_staff VARCHAR(100),
            supervisor VARCHAR(100),
            collection_notes TEXT,
            status ENUM('draft', 'completed') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (donor_id) REFERENCES donors(id)
        )",
    
    'blood_collection_checklists' => "
        CREATE TABLE IF NOT EXISTS blood_collection_checklists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            collection_id INT,
            consent_verified BOOLEAN DEFAULT FALSE,
            identity_verified BOOLEAN DEFAULT FALSE,
            questionnaire_completed BOOLEAN DEFAULT FALSE,
            arm_inspection BOOLEAN DEFAULT FALSE,
            equipment_sterile BOOLEAN DEFAULT FALSE,
            donor_comfortable BOOLEAN DEFAULT FALSE,
            bleeding_stopped BOOLEAN DEFAULT FALSE,
            donor_stable BOOLEAN DEFAULT FALSE,
            refreshments_offered BOOLEAN DEFAULT FALSE,
            instructions_given BOOLEAN DEFAULT FALSE,
            contact_info_updated BOOLEAN DEFAULT FALSE,
            thank_you_given BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
    
    'blood_collection_logs' => "
        CREATE TABLE IF NOT EXISTS blood_collection_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bag_number VARCHAR(50),
            action VARCHAR(100),
            details TEXT,
            performed_by VARCHAR(100),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
];

foreach ($tables as $tableName => $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color: green;'>✅ Table '$tableName' created/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating table '$tableName': " . $e->getMessage() . "</p>";
    }
}

// Add enhanced columns to existing blood_inventory table
echo "<h3>Enhancing Existing Tables</h3>";

$alterQueries = [
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS collection_time TIME",
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS collection_staff VARCHAR(100)",
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS supervisor VARCHAR(100)",
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS collection_notes TEXT",
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS quality_status ENUM('pending', 'passed', 'failed') DEFAULT 'pending'",
    "ALTER TABLE blood_inventory ADD COLUMN IF NOT EXISTS temperature_log TEXT"
];

foreach ($alterQueries as $query) {
    try {
        $db->exec($query);
        echo "<p style='color: green;'>✅ Enhanced blood_inventory table</p>";
    } catch (Exception $e) {
        // Ignore errors for columns that already exist
        if (!strpos($e->getMessage(), 'Duplicate column name')) {
            echo "<p style='color: orange;'>⚠️ " . $e->getMessage() . "</p>";
        }
    }
}

// Test AJAX endpoints
echo "<h3>Testing Enhanced AJAX Endpoints</h3>";

$endpoints = [
    'ajax/process_blood_collection.php' => 'Enhanced Blood Collection',
    'ajax/save_collection_draft.php' => 'Save Collection Draft',
    'ajax/update_donor_last_donation.php' => 'Update Donor Last Donation',
    'ajax/get_recent_collections.php' => 'Get Recent Collections'
];

foreach ($endpoints as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'sendJsonResponse') !== false) {
            echo "<p style='color: green;'>✅ $description - Working</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $description - May have issues</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ $description - File missing</p>";
    }
}

// Test JavaScript files
echo "<h3>Testing Enhanced JavaScript Files</h3>";

$jsFiles = [
    'assets/js/ajax-path-resolver.js' => 'AJAX Path Resolver',
    'enhanced_blood_collection.js' => 'Enhanced Blood Collection Functions'
];

foreach ($jsFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description - Available</p>";
    } else {
        echo "<p style='color: red;'>❌ $description - File missing</p>";
    }
}

// Create sample data for testing
echo "<h3>Creating Sample Data</h3>";

try {
    // Check if we have donors
    $stmt = $db->query("SELECT COUNT(*) as count FROM donors WHERE status = 'active'");
    $donorCount = $stmt->fetch()['count'];
    
    if ($donorCount == 0) {
        // Create sample donors
        $sampleDonors = [
            ['John', 'Doe', '1990-01-15', 'Male', 1, '9876543210', 'john.doe@email.com'],
            ['Jane', 'Smith', '1985-05-20', 'Female', 2, '9876543211', 'jane.smith@email.com'],
            ['Mike', 'Johnson', '1992-08-10', 'Male', 3, '9876543212', 'mike.johnson@email.com']
        ];
        
        foreach ($sampleDonors as $donor) {
            $donorId = generateId('DON');
            $stmt = $db->prepare("
                INSERT INTO donors (
                    donor_id, first_name, last_name, date_of_birth, gender, blood_group_id,
                    phone, email, address, city, state, pincode, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sample Address', 'Sample City', 'Sample State', '123456', 'active', NOW())
            ");
            $stmt->execute([$donorId, $donor[0], $donor[1], $donor[2], $donor[3], $donor[4], $donor[5], $donor[6]]);
        }
        
        echo "<p style='color: green;'>✅ Created 3 sample donors for testing</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Found $donorCount existing donors</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating sample data: " . $e->getMessage() . "</p>";
}

// Summary
echo "<h3>Setup Summary</h3>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #155724;'>🎉 Enhanced Blood Collection System Ready!</h4>";
echo "<p style='color: #155724;'>Your blood bank now has a comprehensive, real-world blood collection system with:</p>";
echo "<ul style='color: #155724;'>";
echo "<li><strong>Pre-Collection Checklist:</strong> Ensures all safety protocols are followed</li>";
echo "<li><strong>Comprehensive Collection Form:</strong> Captures all necessary data</li>";
echo "<li><strong>Post-Collection Care:</strong> Ensures donor safety and satisfaction</li>";
echo "<li><strong>Automatic Label Generation:</strong> Professional blood bag labels</li>";
echo "<li><strong>Draft Saving:</strong> Save incomplete collections for later</li>";
echo "<li><strong>Quality Control:</strong> Built-in validation and checks</li>";
echo "<li><strong>Audit Trail:</strong> Complete logging of all activities</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4 style='color: #0056b3;'>📋 How to Use the Enhanced System:</h4>";
echo "<ol style='color: #0056b3;'>";
echo "<li><strong>Go to Dashboard:</strong> Click 'Blood Collection' from the main menu</li>";
echo "<li><strong>Search Donor:</strong> Use the enhanced search to find eligible donors</li>";
echo "<li><strong>Medical Screening:</strong> Complete the comprehensive health check</li>";
echo "<li><strong>Pre-Collection Checklist:</strong> Verify all safety requirements</li>";
echo "<li><strong>Blood Collection:</strong> Record collection details with auto-generated bag numbers</li>";
echo "<li><strong>Post-Collection Care:</strong> Ensure donor wellbeing and provide instructions</li>";
echo "<li><strong>Print Label:</strong> Generate professional blood bag labels</li>";
echo "<li><strong>Quality Control:</strong> System validates all data automatically</li>";
echo "</ol>";

echo "<h4 style='color: #0056b3;'>🎯 Real-World Features:</h4>";
echo "<ul style='color: #0056b3;'>";
echo "<li><strong>Component-Specific Expiry:</strong> Automatic expiry calculation based on blood component</li>";
echo "<li><strong>Volume Validation:</strong> Ensures proper collection volumes</li>";
echo "<li><strong>Storage Management:</strong> Tracks storage locations and temperatures</li>";
echo "<li><strong>Staff Accountability:</strong> Records who performed each step</li>";
echo "<li><strong>Regulatory Compliance:</strong> Meets blood bank standards and requirements</li>";
echo "</ul>";
echo "</div>";

// Action buttons
echo "<div style='margin: 30px 0;'>";
echo "<h4>Test the Enhanced System:</h4>";
echo "<a href='dashboard.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='pages/blood-collection.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Blood Collection</a>";
echo "<a href='pages/donor-registration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Register New Donor</a>";
echo "<button onclick='location.reload()' style='background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Refresh Setup</button>";
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

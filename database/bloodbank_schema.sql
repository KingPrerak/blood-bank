-- Blood Bank Management System Database Schema
-- Similar to GSCBT Blood Bank Management System

CREATE DATABASE IF NOT EXISTS bloodbank_management;
USE bloodbank_management;

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blood groups reference table
CREATE TABLE blood_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blood_group VARCHAR(5) NOT NULL UNIQUE,
    description VARCHAR(50)
);

-- Donors table
CREATE TABLE donors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_group_id INT NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    medical_history TEXT,
    last_donation_date DATE,
    total_donations INT DEFAULT 0,
    status ENUM('active', 'deferred', 'blacklisted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id)
);

-- Blood inventory table
CREATE TABLE blood_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_number VARCHAR(20) UNIQUE NOT NULL,
    blood_group_id INT NOT NULL,
    component_type ENUM('Whole Blood', 'Red Blood Cells', 'Plasma', 'Platelets', 'Cryoprecipitate') NOT NULL,
    volume_ml INT NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    donor_id INT,
    parent_bag_id INT NULL,
    batch_number VARCHAR(50),
    storage_location VARCHAR(50),
    storage_temperature DECIMAL(4,1),
    status ENUM('available', 'issued', 'expired', 'discarded', 'quarantined', 'testing', 'separated') DEFAULT 'available',
    quality_status ENUM('pending', 'passed', 'failed', 'not_tested') DEFAULT 'pending',
    is_tested BOOLEAN DEFAULT FALSE,
    testing_completed_date DATE NULL,
    disposal_date DATE NULL,
    disposal_reason TEXT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id),
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (parent_bag_id) REFERENCES blood_inventory(id)
);

-- Blood requests table
CREATE TABLE blood_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_group_id INT NOT NULL,
    component_type ENUM('Whole Blood', 'Red Blood Cells', 'Plasma', 'Platelets', 'Cryoprecipitate') NOT NULL,
    units_required INT NOT NULL,
    urgency ENUM('routine', 'urgent', 'emergency') DEFAULT 'routine',
    hospital_name VARCHAR(100) NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(15) NOT NULL,
    request_date DATE NOT NULL,
    required_date DATE NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'fulfilled', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Blood donations table
CREATE TABLE blood_donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donation_id VARCHAR(20) UNIQUE NOT NULL,
    donor_id INT NOT NULL,
    donation_type ENUM('voluntary', 'replacement') NOT NULL,
    replacement_for_request_id INT NULL,
    donation_date DATE NOT NULL,
    hemoglobin_level DECIMAL(3,1),
    blood_pressure VARCHAR(20),
    weight_kg DECIMAL(5,2),
    temperature_f DECIMAL(4,1),
    pulse_rate INT,
    medical_officer_name VARCHAR(100),
    pre_donation_screening TEXT,
    post_donation_instructions TEXT,
    adverse_reactions TEXT,
    status ENUM('completed', 'deferred', 'rejected') DEFAULT 'completed',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (replacement_for_request_id) REFERENCES blood_requests(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Blood issues table
CREATE TABLE blood_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id VARCHAR(20) UNIQUE NOT NULL,
    request_id INT NOT NULL,
    bag_id INT NOT NULL,
    issued_date DATE NOT NULL,
    issued_time TIME NOT NULL,
    issued_by INT NOT NULL,
    received_by VARCHAR(100) NOT NULL,
    hospital_name VARCHAR(100) NOT NULL,
    purpose TEXT,
    status ENUM('issued', 'returned', 'transfused') DEFAULT 'issued',
    return_date DATE NULL,
    return_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES blood_requests(id),
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
);

-- Insert default blood groups
INSERT INTO blood_groups (blood_group, description) VALUES
('A+', 'A Positive'),
('A-', 'A Negative'),
('B+', 'B Positive'),
('B-', 'B Negative'),
('AB+', 'AB Positive'),
('AB-', 'AB Negative'),
('O+', 'O Positive'),
('O-', 'O Negative');

-- Activity logs table for audit trail
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$8K1p/wgyQ1uIiT5MMxjtOeRa2QKAXVWUiGKgE5OyxrxQY9VfupBRK', 'System Administrator', 'admin@bloodbank.com', 'admin'),
('staff1', '$2y$10$8K1p/wgyQ1uIiT5MMxjtOeRa2QKAXVWUiGKgE5OyxrxQY9VfupBRK', 'Lab Technician', 'staff1@bloodbank.com', 'staff'),
('staff2', '$2y$10$8K1p/wgyQ1uIiT5MMxjtOeRa2QKAXVWUiGKgE5OyxrxQY9VfupBRK', 'Blood Bank Officer', 'staff2@bloodbank.com', 'staff');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('expiry_alert_days', '7', 'number', 'Days before expiry to show alerts'),
('low_stock_threshold', '5', 'number', 'Minimum units before low stock alert'),
('auto_dispose_expired', 'false', 'boolean', 'Automatically dispose expired units'),
('require_crossmatch', 'true', 'boolean', 'Require crossmatching before blood issue'),
('quarantine_new_units', 'false', 'boolean', 'Quarantine new blood units for testing'),
('max_donation_interval_days', '90', 'number', 'Minimum days between donations'),
('min_donor_age', '18', 'number', 'Minimum donor age'),
('max_donor_age', '65', 'number', 'Maximum donor age'),
('min_donor_weight', '50', 'number', 'Minimum donor weight in kg'),
('blood_bank_name', 'GSCBT Blood Bank', 'string', 'Blood bank name'),
('blood_bank_license', 'BB/2024/001', 'string', 'Blood bank license number'),
('enable_sms_notifications', 'false', 'boolean', 'Enable SMS notifications'),
('enable_email_notifications', 'true', 'boolean', 'Enable email notifications');

-- Donor deferrals table
CREATE TABLE donor_deferrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL,
    deferral_reason VARCHAR(255) NOT NULL,
    deferral_type ENUM('temporary', 'permanent') NOT NULL,
    deferral_date DATE NOT NULL,
    deferral_end_date DATE NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Blood bag disposal table
CREATE TABLE blood_disposals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_id INT NOT NULL,
    disposal_reason ENUM('expired', 'contaminated', 'damaged', 'quality_failure', 'other') NOT NULL,
    disposal_date DATE NOT NULL,
    disposal_method VARCHAR(100),
    disposal_location VARCHAR(100),
    disposed_by INT NOT NULL,
    supervisor_approval INT,
    disposal_certificate_no VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (disposed_by) REFERENCES users(id),
    FOREIGN KEY (supervisor_approval) REFERENCES users(id)
);

-- Blood testing results table
CREATE TABLE blood_testing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_id INT NOT NULL,
    test_type ENUM('abo_rh', 'antibody_screening', 'infectious_disease', 'quality_control') NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_result ENUM('positive', 'negative', 'reactive', 'non_reactive', 'pending') NOT NULL,
    test_date DATE NOT NULL,
    tested_by VARCHAR(100),
    equipment_used VARCHAR(100),
    batch_number VARCHAR(50),
    expiry_date_reagent DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id)
);

-- Cross matching table
CREATE TABLE cross_matching (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    bag_id INT NOT NULL,
    patient_sample_id VARCHAR(50),
    major_crossmatch ENUM('compatible', 'incompatible', 'pending') DEFAULT 'pending',
    minor_crossmatch ENUM('compatible', 'incompatible', 'pending') DEFAULT 'pending',
    antibody_screening ENUM('positive', 'negative', 'pending') DEFAULT 'pending',
    crossmatch_date DATE NOT NULL,
    crossmatch_time TIME NOT NULL,
    performed_by VARCHAR(100),
    verified_by VARCHAR(100),
    result ENUM('compatible', 'incompatible', 'pending') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES blood_requests(id),
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id)
);

-- Blood component separation table
CREATE TABLE component_separation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_bag_id INT NOT NULL,
    separation_date DATE NOT NULL,
    separation_method VARCHAR(100),
    separated_by INT NOT NULL,
    equipment_used VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (separated_by) REFERENCES users(id)
);

-- Blood quarantine table
CREATE TABLE blood_quarantine (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_id INT NOT NULL,
    quarantine_reason VARCHAR(255) NOT NULL,
    quarantine_date DATE NOT NULL,
    quarantine_end_date DATE,
    quarantined_by INT NOT NULL,
    released_by INT,
    release_date DATE,
    status ENUM('quarantined', 'released', 'disposed') DEFAULT 'quarantined',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (quarantined_by) REFERENCES users(id),
    FOREIGN KEY (released_by) REFERENCES users(id)
);

-- Blood transfer table (between locations/departments)
CREATE TABLE blood_transfers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_id INT NOT NULL,
    from_location VARCHAR(100) NOT NULL,
    to_location VARCHAR(100) NOT NULL,
    transfer_date DATE NOT NULL,
    transfer_time TIME NOT NULL,
    transferred_by INT NOT NULL,
    received_by VARCHAR(100),
    temperature_maintained ENUM('yes', 'no') DEFAULT 'yes',
    transport_conditions TEXT,
    reason VARCHAR(255),
    status ENUM('in_transit', 'completed', 'cancelled') DEFAULT 'in_transit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (transferred_by) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    type ENUM('expiry_alert', 'low_stock', 'critical_request', 'system_alert', 'disposal_reminder') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_read BOOLEAN DEFAULT FALSE,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(255),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Blood wastage tracking table
CREATE TABLE blood_wastage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bag_id INT NOT NULL,
    wastage_type ENUM('expired', 'contaminated', 'hemolysis', 'clotting', 'leakage', 'other') NOT NULL,
    wastage_date DATE NOT NULL,
    quantity_wasted INT NOT NULL,
    cost_impact DECIMAL(10,2),
    reported_by INT NOT NULL,
    investigation_required BOOLEAN DEFAULT FALSE,
    investigation_notes TEXT,
    preventive_action TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bag_id) REFERENCES blood_inventory(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Create indexes for better performance
CREATE INDEX idx_donors_blood_group ON donors(blood_group_id);
CREATE INDEX idx_donors_phone ON donors(phone);
CREATE INDEX idx_inventory_blood_group ON blood_inventory(blood_group_id);
CREATE INDEX idx_inventory_status ON blood_inventory(status);
CREATE INDEX idx_inventory_expiry ON blood_inventory(expiry_date);
CREATE INDEX idx_requests_blood_group ON blood_requests(blood_group_id);
CREATE INDEX idx_requests_status ON blood_requests(status);
CREATE INDEX idx_donations_donor ON blood_donations(donor_id);
CREATE INDEX idx_donations_date ON blood_donations(donation_date);
CREATE INDEX idx_issues_request ON blood_issues(request_id);
CREATE INDEX idx_issues_bag ON blood_issues(bag_id);
CREATE INDEX idx_deferrals_donor ON donor_deferrals(donor_id);
CREATE INDEX idx_deferrals_end_date ON donor_deferrals(deferral_end_date);
CREATE INDEX idx_disposals_date ON blood_disposals(disposal_date);
CREATE INDEX idx_testing_bag ON blood_testing(bag_id);
CREATE INDEX idx_crossmatch_request ON cross_matching(request_id);
CREATE INDEX idx_quarantine_bag ON blood_quarantine(bag_id);
CREATE INDEX idx_transfers_bag ON blood_transfers(bag_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_wastage_date ON blood_wastage(wastage_date);

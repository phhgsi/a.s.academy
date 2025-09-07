-- School Management System - Core Database Schema
-- Version: 2.0
-- Description: Core database structure with essential tables and seed data
-- Usage: This file contains all mandatory tables and data required for the system to function
-- Run this file first: mysql -u root -p school_management < schema_core.sql

-- Create database
CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- ============================================================================
-- CORE SYSTEM TABLES
-- ============================================================================

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'cashier', 'student') NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100) NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- School information table
CREATE TABLE school_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_name VARCHAR(200) NOT NULL,
    school_code VARCHAR(50),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(100),
    principal_name VARCHAR(100),
    logo VARCHAR(255),
    established_year YEAR,
    affiliation VARCHAR(100),
    board VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Academic years table
CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year_name VARCHAR(20) NOT NULL UNIQUE,
    academic_year VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_academic_year_current (is_current, is_active),
    INDEX idx_academic_year_dates (start_date, end_date)
);

-- Classes table
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10),
    class_teacher_id INT,
    academic_year VARCHAR(10),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_teacher_id) REFERENCES users(id)
);

-- Teachers table
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    qualification VARCHAR(255),
    experience_years INT DEFAULT 0,
    mobile_no VARCHAR(15),
    emergency_contact VARCHAR(15),
    address TEXT,
    department VARCHAR(100),
    joining_date DATE,
    salary DECIMAL(10,2),
    photo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Cashiers table
CREATE TABLE cashiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    mobile_no VARCHAR(15),
    emergency_contact VARCHAR(15),
    address TEXT,
    shift ENUM('morning', 'evening', 'full_day') DEFAULT 'full_day',
    joining_date DATE,
    photo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Students table (enhanced with photo support)
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admission_no VARCHAR(50) UNIQUE NOT NULL,
    roll_no VARCHAR(20),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    age INT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    blood_group VARCHAR(5),
    category VARCHAR(20),
    religion VARCHAR(30),
    mobile_no VARCHAR(15),
    parent_mobile VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    village VARCHAR(50),
    pincode VARCHAR(10),
    photo VARCHAR(255),
    aadhar_no VARCHAR(12),
    samagra_id VARCHAR(20),
    pan_no VARCHAR(10),
    scholar_no VARCHAR(50),
    class_id INT,
    academic_year VARCHAR(10),
    admission_date DATE,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    INDEX idx_age (age),
    INDEX idx_students_class (class_id, is_active),
    INDEX idx_students_admission (admission_no),
    INDEX idx_active (is_active),
    INDEX idx_class_year (class_id, academic_year)
);

-- Subjects table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    class_id INT,
    teacher_id INT,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Class subjects relationship table
CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    academic_year VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_class_subject (class_id, subject_id, academic_year)
);

-- ============================================================================
-- ACADEMIC MANAGEMENT
-- ============================================================================

-- Academic records table
CREATE TABLE academic_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_type VARCHAR(50),
    marks_obtained DECIMAL(5,2),
    total_marks DECIMAL(5,2),
    grade VARCHAR(5),
    exam_date DATE,
    academic_year VARCHAR(10),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_academic_records_student (student_id, academic_year)
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (marked_by) REFERENCES users(id),
    INDEX idx_attendance_student_date (student_id, attendance_date)
);

-- ============================================================================
-- FINANCIAL MANAGEMENT
-- ============================================================================

-- Fee structure table
CREATE TABLE fee_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    is_mandatory BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_class_year (class_id, academic_year),
    INDEX idx_fee_type (fee_type)
);

-- Fee payments table
CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_no VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    cashier_id INT,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_mode ENUM('cash', 'online', 'cheque', 'dd') DEFAULT 'cash',
    payment_date DATE NOT NULL,
    academic_year VARCHAR(10),
    fee_type VARCHAR(50),
    remarks TEXT,
    collected_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (cashier_id) REFERENCES cashiers(id),
    FOREIGN KEY (collected_by) REFERENCES users(id),
    INDEX idx_fee_payments_student (student_id, payment_date)
);

-- Expenses table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_no VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(50),
    approved_by INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================================
-- SYSTEM MANAGEMENT
-- ============================================================================

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- Activity log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at),
    INDEX idx_table_record (table_name, record_id)
);

-- Documents table for better file management
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_date TIMESTAMP NULL,
    verified_by INT,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_type (student_id, document_type),
    INDEX idx_upload_date (upload_date)
);

-- ============================================================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ============================================================================

CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role, is_active);
CREATE INDEX idx_users_role_active ON users(role, is_active);
CREATE INDEX idx_classes_academic_year ON classes(academic_year, is_active);

-- ============================================================================
-- SEED DATA - DEFAULT USERS AND SETTINGS
-- ============================================================================

-- Insert default users for all roles
-- Password for all demo users is 'password123'
INSERT INTO users (username, password, role, full_name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@school.com'),
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'John Smith', 'john.smith@school.com'),
('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 'Sarah Johnson', 'sarah.johnson@school.com'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Mike Wilson', 'mike.wilson@school.com');

-- Insert sample school information
INSERT INTO school_info (school_name, school_code, address, phone, email, principal_name, established_year, board, description)
VALUES ('A.S.ACADEMY', 'ASA001', '123 Education Street, Knowledge City', '+91-9876543210', 'info@asacademy.edu', 'Dr. John Smith', 2010, 'CBSE', 'A leading educational institution committed to excellence in education and character development.');

-- Insert academic years
INSERT INTO academic_years (year_name, academic_year, start_date, end_date, is_current, is_active) VALUES
('2024-2025', '2024-2025', '2024-04-01', '2025-03-31', TRUE, TRUE),
('2023-2024', '2023-2024', '2023-04-01', '2024-03-31', FALSE, TRUE);

-- Insert sample classes
INSERT INTO classes (class_name, section, class_teacher_id, academic_year, is_active) VALUES 
('Class 1', 'A', 2, '2024-2025', 1),
('Class 1', 'B', 2, '2024-2025', 1),
('Class 2', 'A', 2, '2024-2025', 1),
('Class 3', 'A', 2, '2024-2025', 1),
('Class 4', 'A', 2, '2024-2025', 1),
('Class 5', 'A', 2, '2024-2025', 1);

-- Insert teachers
INSERT INTO teachers (user_id, employee_id, first_name, last_name, qualification, experience_years, mobile_no, department, joining_date, is_active) VALUES
(2, 'TCH001', 'John', 'Smith', 'M.Sc Mathematics, B.Ed', 8, '9876543221', 'Mathematics', '2020-06-01', TRUE);

-- Insert cashiers
INSERT INTO cashiers (user_id, employee_id, first_name, last_name, mobile_no, shift, joining_date, is_active) VALUES
(3, 'CSH001', 'Sarah', 'Johnson', '9876543222', 'full_day', '2022-04-01', TRUE);

-- Insert sample student
INSERT INTO students (
    user_id, admission_no, first_name, last_name, father_name, mother_name, 
    date_of_birth, age, gender, blood_group, category, religion, mobile_no, 
    parent_mobile, email, address, village, pincode, class_id, 
    academic_year, admission_date, is_active
) VALUES (
    4, 'ADM20240001', 'Mike', 'Wilson', 'Robert Wilson', 'Linda Wilson', 
    '2010-05-15', 14, 'male', 'O+', 'General', 'Christianity', '9876543210', 
    '9876543211', 'mike.wilson@school.com', '123 Student Street, Education City', 
    'Education City', '123456', 1, '2024-2025', '2024-04-01', 1
);

-- Insert sample subjects
INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id, is_active) VALUES 
('Mathematics', 'MATH01', 1, 2, 1),
('English', 'ENG01', 1, 2, 1),
('Science', 'SCI01', 1, 2, 1),
('Social Studies', 'SS01', 1, 2, 1),
('Hindi', 'HIN01', 1, 2, 1);

-- Insert class-subject relationships
INSERT INTO class_subjects (class_id, subject_id, teacher_id, academic_year) VALUES
(1, 1, 1, '2024-2025'),
(1, 2, 1, '2024-2025'),
(1, 3, 1, '2024-2025'),
(1, 4, 1, '2024-2025'),
(1, 5, 1, '2024-2025');

-- Insert sample notifications
INSERT INTO notifications (user_id, type, title, message, is_read) VALUES
(1, 'info', 'Welcome', 'Welcome to the School Management System!', FALSE),
(2, 'info', 'Assignment', 'New class assignment: Class 1-A Mathematics', FALSE),
(3, 'success', 'Training', 'Fee collection training completed successfully', TRUE),
(4, 'info', 'Welcome', 'Welcome to A.S.ACADEMY!', FALSE);

-- Default system settings
INSERT INTO system_settings (setting_key, setting_value, description, setting_type, is_public) VALUES
('current_academic_year', '2024-2025', 'Currently selected academic year for the system', 'text', TRUE),
('school_session_start', '04-01', 'Academic session start date (MM-DD)', 'text', TRUE),
('school_session_end', '03-31', 'Academic session end date (MM-DD)', 'text', TRUE),
('attendance_required_percentage', '75', 'Minimum attendance percentage required', 'number', TRUE),
('late_fee_per_day', '5', 'Late fee charged per day after due date', 'number', TRUE),
('max_file_upload_size', '5242880', 'Maximum file upload size in bytes (5MB)', 'number', FALSE),
('allowed_file_types', '["pdf","jpg","jpeg","png","doc","docx"]', 'Allowed file types for uploads', 'json', FALSE),
('backup_retention_days', '30', 'Number of days to retain backup files', 'number', FALSE),
('session_timeout_minutes', '60', 'Session timeout in minutes', 'number', FALSE),
('school_currency', 'INR', 'School currency code', 'text', TRUE),
('school_timezone', 'Asia/Kolkata', 'School timezone', 'text', FALSE);

-- ============================================================================
-- PERFORMANCE VIEWS
-- ============================================================================

-- Student dashboard view
CREATE VIEW student_dashboard AS
SELECT 
    s.id,
    s.admission_no,
    s.first_name,
    s.last_name,
    s.father_name,
    s.mother_name,
    s.photo,
    c.class_name,
    c.section,
    s.academic_year,
    u.username,
    u.email,
    COUNT(DISTINCT a.id) as total_attendance,
    COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as present_days,
    ROUND((COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) / COUNT(DISTINCT a.id)) * 100, 2) as attendance_percentage
FROM students s
LEFT JOIN classes c ON s.class_id = c.id
LEFT JOIN users u ON s.user_id = u.id
LEFT JOIN attendance a ON s.id = a.student_id
WHERE s.is_active = 1
GROUP BY s.id;

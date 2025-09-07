-- School Management System - Extended Features Schema
-- Version: 2.0
-- Description: Optional tables for CMS, library, messaging, and advanced features
-- Usage: Run this file after schema_core.sql for additional features
-- Run this file: mysql -u root -p school_management < schema_extras.sql

USE school_management;

-- ============================================================================
-- CMS AND CONTENT MANAGEMENT
-- ============================================================================

-- Page content table for CMS functionality
CREATE TABLE page_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    meta_description TEXT,
    hero_image VARCHAR(255),
    content LONGTEXT,
    is_published TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_published (is_published)
);

-- News/announcements table
CREATE TABLE news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category VARCHAR(50) DEFAULT 'general',
    is_published TINYINT DEFAULT 1,
    is_featured TINYINT DEFAULT 0,
    published_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_published (is_published),
    INDEX idx_featured (is_featured),
    INDEX idx_category (category),
    INDEX idx_published_date (published_date)
);

-- Gallery table
CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    category VARCHAR(50) DEFAULT 'general',
    event_date DATE,
    is_featured TINYINT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    view_count INT DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_featured (is_featured),
    INDEX idx_category (category),
    INDEX idx_gallery_category (category, is_active, event_date),
    INDEX idx_gallery_featured (is_featured, is_active, created_at)
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(200),
    event_type ENUM('holiday', 'exam', 'meeting', 'celebration', 'sports', 'academic', 'other') DEFAULT 'other',
    target_audience ENUM('all', 'students', 'teachers', 'parents', 'staff') DEFAULT 'all',
    class_id INT,
    category VARCHAR(50) DEFAULT 'general',
    is_featured TINYINT DEFAULT 0,
    is_holiday BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_featured (is_featured),
    INDEX idx_category (category),
    INDEX idx_event_audience (target_audience, event_date),
    INDEX idx_holiday_events (is_holiday, event_date)
);

-- Contact inquiries table
CREATE TABLE contact_inquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- COMMUNICATION SYSTEM
-- ============================================================================

-- Messages table for internal communication
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT,
    recipient_type ENUM('user', 'class', 'all_students', 'all_teachers', 'all_parents') DEFAULT 'user',
    class_id INT,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    message_type ENUM('general', 'announcement', 'homework', 'exam', 'fee_reminder', 'event') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    scheduled_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_recipient_messages (recipient_id, is_read, created_at),
    INDEX idx_class_messages (class_id, recipient_type, created_at),
    INDEX idx_message_type (message_type, priority, created_at),
    INDEX idx_receiver (recipient_id),
    INDEX idx_sender (sender_id),
    INDEX idx_read_status (is_read),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- LIBRARY MANAGEMENT SYSTEM
-- ============================================================================

-- Library books table
CREATE TABLE library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    publisher VARCHAR(255),
    isbn VARCHAR(20),
    category VARCHAR(100),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    price DECIMAL(10,2),
    purchase_date DATE,
    condition_status ENUM('new', 'good', 'fair', 'poor', 'damaged') DEFAULT 'new',
    location_rack VARCHAR(50),
    description TEXT,
    publication_year YEAR,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_book_code (book_code),
    INDEX idx_title (title),
    INDEX idx_category (category),
    INDEX idx_book_category (category, is_active),
    INDEX idx_book_availability (available_copies, is_active),
    INDEX idx_book_isbn (isbn)
);

-- Book issues table
CREATE TABLE book_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_date DATE NOT NULL,
    return_date DATE,
    expected_return_date DATE NOT NULL,
    due_date DATE NOT NULL,
    fine_amount DECIMAL(8,2) DEFAULT 0,
    status ENUM('issued', 'returned', 'lost', 'damaged', 'overdue') DEFAULT 'issued',
    issued_by INT,
    returned_to INT,
    remarks TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id),
    FOREIGN KEY (returned_to) REFERENCES users(id),
    INDEX idx_student_status (student_id, status),
    INDEX idx_return_date (return_date),
    INDEX idx_student_issues (student_id, status, issue_date),
    INDEX idx_book_issues (book_id, status, issue_date),
    INDEX idx_overdue_books (due_date, status)
);

-- ============================================================================
-- ADVANCED ACADEMIC FEATURES
-- ============================================================================

-- Fee types table
CREATE TABLE fee_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_recurring BOOLEAN DEFAULT TRUE,
    category VARCHAR(50) DEFAULT 'tuition',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fee_type_category (category, is_active),
    INDEX idx_fee_type_order (sort_order, is_active)
);

-- Student fee status table
CREATE TABLE student_fee_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    due_date DATE,
    last_payment_date DATE,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_fee (student_id, fee_type_id, academic_year),
    INDEX idx_student_fee_status (student_id, status, due_date),
    INDEX idx_fee_type_status (fee_type_id, status, academic_year)
);

-- Exam schedule table
CREATE TABLE exam_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(255) NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_marks INT DEFAULT 100,
    passing_marks INT DEFAULT 40,
    exam_type ENUM('unit_test', 'monthly', 'quarterly', 'half_yearly', 'annual', 'practical') DEFAULT 'monthly',
    academic_year VARCHAR(20),
    invigilator_id INT,
    room_no VARCHAR(50),
    instructions TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (invigilator_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_exam_schedule (class_id, exam_date),
    INDEX idx_exam_subject (subject_id, exam_date),
    INDEX idx_exam_type (exam_type, academic_year)
);

-- Timetable table
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(50),
    room_no VARCHAR(50),
    academic_year VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (class_id, day_of_week, start_time, academic_year),
    INDEX idx_class_day (class_id, day_of_week),
    INDEX idx_class_timetable (class_id, day_of_week, start_time),
    INDEX idx_teacher_timetable (teacher_id, day_of_week, start_time),
    INDEX idx_academic_timetable (academic_year, is_active)
);

-- Student guardian information table
CREATE TABLE student_guardians (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    guardian_type ENUM('father', 'mother', 'guardian') NOT NULL,
    name VARCHAR(100) NOT NULL,
    occupation VARCHAR(100),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    annual_income DECIMAL(12,2),
    education VARCHAR(100),
    is_primary_contact BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_guardians (student_id, guardian_type),
    INDEX idx_primary_contact (student_id, is_primary_contact)
);

-- ============================================================================
-- HOMEWORK AND ASSIGNMENTS
-- ============================================================================

-- Assignments table
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assignment_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_marks INT DEFAULT 10,
    assignment_type ENUM('homework', 'project', 'essay', 'practical', 'research') DEFAULT 'homework',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    attachments JSON,
    instructions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    academic_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_class_assignments (class_id, due_date),
    INDEX idx_subject_assignments (subject_id, assignment_date),
    INDEX idx_teacher_assignments (assigned_by, assignment_date)
);

-- Assignment submissions table
CREATE TABLE assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    attachments JSON,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks_obtained DECIMAL(5,2),
    feedback TEXT,
    status ENUM('submitted', 'graded', 'late', 'missing') DEFAULT 'submitted',
    graded_by INT,
    graded_at TIMESTAMP NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    INDEX idx_student_submissions (student_id, status, submitted_at),
    INDEX idx_assignment_submissions (assignment_id, status, submitted_at)
);

-- ============================================================================
-- LEAVE MANAGEMENT
-- ============================================================================

-- Leave applications table
CREATE TABLE leave_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT NOT NULL,
    applicant_type ENUM('student', 'teacher', 'staff') NOT NULL,
    leave_type ENUM('sick', 'casual', 'emergency', 'personal', 'vacation') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    supporting_documents JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_applicant_leave (applicant_id, status, start_date),
    INDEX idx_leave_approval (status, start_date),
    INDEX idx_leave_type (leave_type, start_date)
);

-- ============================================================================
-- PERFORMANCE AND SYSTEM MONITORING
-- ============================================================================

-- Cache table for performance
CREATE TABLE cache_store (
    cache_key VARCHAR(255) PRIMARY KEY,
    cache_value LONGTEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
);

-- System backups log table
CREATE TABLE system_backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_type ENUM('manual', 'scheduled', 'automatic') DEFAULT 'manual',
    backup_path VARCHAR(500),
    file_size BIGINT,
    backup_status ENUM('started', 'completed', 'failed') DEFAULT 'started',
    error_message TEXT,
    created_by INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_backup_status (backup_status, started_at),
    INDEX idx_backup_type (backup_type, started_at)
);

-- ============================================================================
-- SEED DATA FOR EXTENDED FEATURES
-- ============================================================================

-- Insert sample page content
INSERT INTO page_content (slug, title, meta_description, content) VALUES
('about', 'About Us', 'Learn about our school\'s mission, vision, and commitment to educational excellence.', ''),
('admissions', 'Admissions', 'Join our school community. Learn about our admission process, requirements, and how to apply.', ''),
('faculty', 'Our Faculty', 'Meet our dedicated team of educators who are committed to student success and development.', ''),
('facilities', 'School Facilities', 'Explore our modern facilities designed to enhance learning and student development.', ''),
('achievements', 'Our Achievements', 'Discover the academic and extracurricular achievements of our students and school.', ''),
('values', 'Our Values', 'Learn about the core values that guide our educational philosophy and approach.', ''),
('international', 'International Programs', 'Explore our global education initiatives and international collaboration programs.', ''),
('safety', 'Safety Measures', 'Learn about our comprehensive safety protocols and secure learning environment.', ''),
('curriculum', 'Academic Curriculum', 'Discover our comprehensive academic program designed for holistic student development.', ''),
('news', 'News & Announcements', 'Stay updated with the latest news, announcements, and happenings at our school.', ''),
('events', 'School Events', 'View upcoming events, celebrations, and important dates in our school calendar.', ''),
('contact', 'Contact Us', 'Get in touch with us for admissions, inquiries, or any assistance you may need.', '');

-- Insert sample news
INSERT INTO news (title, content, excerpt, category, is_published, is_featured, published_date, created_by) VALUES
('Welcome to New Academic Year 2024-25', 'We are excited to welcome all students and parents to the new academic year 2024-25. This year brings new opportunities, challenges, and exciting programs designed to enhance student learning and development.', 'New academic year brings exciting opportunities for our students.', 'academic', 1, 1, '2024-04-01', 1),
('Annual Sports Day Celebrations', 'Our annual sports day was a huge success with participation from students across all grades. The event showcased amazing talent, teamwork, and sportsmanship among our students.', 'Annual sports day showcases student talent and teamwork.', 'events', 1, 1, '2024-02-15', 1),
('Science Exhibition 2024', 'Students demonstrated incredible innovation and creativity in our recent science exhibition. Projects ranged from environmental solutions to technological innovations.', 'Students showcase innovation in science exhibition.', 'academic', 1, 0, '2024-01-20', 1);

-- Insert sample events
INSERT INTO events (title, description, event_date, start_time, location, category, is_featured, created_by) VALUES
('Parent-Teacher Meeting', 'Quarterly parent-teacher meeting to discuss student progress and academic development.', '2024-09-15', '10:00:00', 'School Auditorium', 'academic', 1, 1),
('Cultural Festival', 'Annual cultural festival showcasing student talents in music, dance, drama, and arts.', '2024-10-20', '09:00:00', 'School Grounds', 'cultural', 1, 1),
('Science Fair', 'Students will present their science projects and innovations to judges and visitors.', '2024-11-10', '10:30:00', 'Science Laboratory', 'academic', 0, 1);

-- Insert default fee types
INSERT INTO fee_types (type_name, description, is_mandatory, category, sort_order) VALUES
('Tuition Fee', 'Monthly tuition fee', TRUE, 'tuition', 1),
('Admission Fee', 'One-time admission fee', TRUE, 'admission', 2),
('Examination Fee', 'Examination and assessment fee', TRUE, 'examination', 3),
('Library Fee', 'Library usage and book fee', FALSE, 'library', 4),
('Sports Fee', 'Sports and games activities fee', FALSE, 'activities', 5),
('Computer Fee', 'Computer lab and technology fee', FALSE, 'technology', 6),
('Transport Fee', 'School bus transportation fee', FALSE, 'transport', 7),
('Development Fee', 'School development and infrastructure fee', FALSE, 'development', 8);

-- ============================================================================
-- ENHANCED VIEWS FOR REPORTING
-- ============================================================================

-- Fee summary view
CREATE VIEW fee_summary AS
SELECT 
    s.id as student_id,
    s.admission_no,
    s.first_name,
    s.last_name,
    c.class_name,
    sfs.academic_year,
    ft.type_name as fee_type,
    sfs.total_amount,
    sfs.paid_amount,
    (sfs.total_amount - sfs.paid_amount) as pending_amount,
    sfs.status,
    sfs.due_date,
    sfs.last_payment_date
FROM students s
JOIN classes c ON s.class_id = c.id
LEFT JOIN student_fee_status sfs ON s.id = sfs.student_id
LEFT JOIN fee_types ft ON sfs.fee_type_id = ft.id
WHERE s.is_active = 1;

-- ============================================================================
-- TRIGGERS FOR DATA CONSISTENCY
-- ============================================================================

DELIMITER //

-- Trigger to update book availability when book is issued
CREATE TRIGGER update_book_availability_issue
AFTER INSERT ON book_issues
FOR EACH ROW
BEGIN
    UPDATE library_books 
    SET available_copies = available_copies - 1 
    WHERE id = NEW.book_id AND available_copies > 0;
END//

-- Trigger to update book availability when book is returned
CREATE TRIGGER update_book_availability_return
AFTER UPDATE ON book_issues
FOR EACH ROW
BEGIN
    IF NEW.status = 'returned' AND OLD.status != 'returned' THEN
        UPDATE library_books 
        SET available_copies = available_copies + 1 
        WHERE id = NEW.book_id;
    END IF;
END//

-- Trigger to update student fee status when payment is made
CREATE TRIGGER update_fee_status_after_payment
AFTER INSERT ON fee_payments
FOR EACH ROW
BEGIN
    DECLARE total_paid DECIMAL(10,2);
    DECLARE total_amount DECIMAL(10,2);
    DECLARE new_status VARCHAR(20);
    
    -- Calculate total paid amount for this fee type
    SELECT COALESCE(SUM(amount_paid), 0) INTO total_paid
    FROM fee_payments 
    WHERE student_id = NEW.student_id 
    AND fee_type = NEW.fee_type 
    AND academic_year = NEW.academic_year;
    
    -- Get total amount due
    SELECT COALESCE(fs.amount, 0) INTO total_amount
    FROM fee_structure fs
    JOIN students s ON s.class_id = fs.class_id
    WHERE s.id = NEW.student_id 
    AND fs.fee_type = NEW.fee_type 
    AND fs.academic_year = NEW.academic_year;
    
    -- Determine status
    IF total_paid >= total_amount THEN
        SET new_status = 'paid';
    ELSEIF total_paid > 0 THEN
        SET new_status = 'partial';
    ELSE
        SET new_status = 'pending';
    END IF;
    
    -- Update or insert fee status
    INSERT INTO student_fee_status (student_id, fee_type_id, academic_year, total_amount, paid_amount, last_payment_date, status)
    SELECT NEW.student_id, ft.id, NEW.academic_year, total_amount, total_paid, NEW.payment_date, new_status
    FROM fee_types ft WHERE ft.type_name = NEW.fee_type
    ON DUPLICATE KEY UPDATE 
        paid_amount = total_paid,
        last_payment_date = NEW.payment_date,
        status = new_status,
        updated_at = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- Additional settings for extended features
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('enable_library_module', 'true', 'boolean', 'Enable library management module', FALSE),
('enable_transport_module', 'false', 'boolean', 'Enable transport management module', FALSE),
('enable_notifications', 'true', 'boolean', 'Enable system notifications', FALSE),
('enable_attendance_sms', 'false', 'boolean', 'Enable SMS for attendance', FALSE),
('enable_fee_reminders', 'true', 'boolean', 'Enable automatic fee reminders', FALSE),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', FALSE);

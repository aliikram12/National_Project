-- ============================================
-- National College LMS - Database Schema
-- Complete Production-Ready Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS national_college CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE national_college;


-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'receptionist') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email_role (email, role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 2. COURSES TABLE
-- ============================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 3. SLOTS TABLE
-- ============================================
CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_range VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_status (status)
) ENGINE=InnoDB;

INSERT INTO slots (time_range) VALUES 
('8:00 AM - 10:00 AM'),
('10:00 AM - 12:00 PM'),
('12:00 PM - 2:00 PM'),
('2:30 PM - 4:30 PM');

-- ============================================
-- 4. STUDENTS TABLE
-- ============================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    cnic VARCHAR(20) NULL,
    address TEXT NOT NULL,
    status ENUM('active', 'struck_off') DEFAULT 'active',
    struck_off_date DATE NULL,
    struck_off_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (name),
    INDEX idx_contact (contact),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- 5. ENROLLMENTS TABLE
-- ============================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    slot_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    UNIQUE KEY uk_student_course (student_id, course_id),
    INDEX idx_enrollment_date (enrollment_date),
    INDEX idx_course_slot (course_id, slot_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 6. COURSE_TEACHERS TABLE
-- ============================================
CREATE TABLE course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    slot_id INT NOT NULL,
    assigned_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    UNIQUE KEY uk_course_teacher_slot (course_id, teacher_id, slot_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB;

-- ============================================
-- 7. ATTENDANCE TABLE
-- ============================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    slot_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'leave') NOT NULL,
    marked_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_student_date_course (student_id, date, course_id),
    INDEX idx_date (date),
    INDEX idx_student_status (student_id, status),
    INDEX idx_course_slot_date (course_id, slot_id, date)
) ENGINE=InnoDB;

-- ============================================
-- 8. ASSESSMENTS TABLE
-- ============================================
CREATE TABLE assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    date DATE NOT NULL,
    assessment_type ENUM('daily_progress', 'assignment', 'exam', 'general') DEFAULT 'general',
    notes TEXT NOT NULL,
    grade VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_date (date),
    INDEX idx_course (course_id)
) ENGINE=InnoDB;

-- ============================================
-- 9. PDF TEMPLATES TABLE
-- ============================================
CREATE TABLE pdf_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 9b. PDF SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS pdf_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_name VARCHAR(200) DEFAULT 'National College',
    college_logo VARCHAR(500) DEFAULT '',
    college_address VARCHAR(500) DEFAULT '123 Education Blvd, Lahore, Pakistan',
    college_phone VARCHAR(50) DEFAULT '+92 300 1234567',
    college_email VARCHAR(100) DEFAULT 'info@nationalcollege.edu',
    footer_text VARCHAR(500) DEFAULT 'This is a system-generated document.',
    watermark_text VARCHAR(100) DEFAULT 'NATIONAL COLLEGE',
    header_color VARCHAR(20) DEFAULT '#0A1628',
    table_header_bg VARCHAR(20) DEFAULT '#0A1628',
    table_header_color VARCHAR(20) DEFAULT '#FFFFFF',
    table_border_color VARCHAR(20) DEFAULT '#dee2e6',
    show_logo TINYINT(1) DEFAULT 1,
    show_watermark TINYINT(1) DEFAULT 0,
    show_signature TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pdf_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;

-- ============================================
-- 10. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Default PDF Template
-- ============================================
INSERT INTO pdf_templates (name, content) VALUES ('admission_form', '<h1>National College</h1><h2>Admission Form</h2><p>Student Name: {student_name}</p>');

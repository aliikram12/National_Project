-- ============================================
-- National College LMS - Database Schema
-- Complete Production-Ready Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS national_college CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE national_college;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
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

INSERT INTO users (name, email, password, role, status) VALUES 
('System Admin', 'nectofficial@gmail.com', '$2y$10$C8.c4Z0Iq8zV3l/Q0kQ6V.7V/o5Yn3XfP0Rk9E2WJ8xL3F.6p/e4K', 'admin', 'active');
-- Note: the password hash above corresponds to 'nectadmin123'

-- ============================================
-- 1b. DEPARTMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 2. COURSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS courses ( 
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration VARCHAR(50) NOT NULL,
    duration_months INT DEFAULT 1,
    department_id INT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_department (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 3. SLOTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_range VARCHAR(50) NOT NULL,
    duration VARCHAR(50) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 3b. FEE_PACKAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS fee_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    total_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    duration_months INT DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 4. ADMISSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    date_of_admission DATE NOT NULL,
    duration VARCHAR(50) NOT NULL,
    degree_type ENUM('Private', 'Government') DEFAULT 'Private',
    session_start VARCHAR(20) NOT NULL,
    session_end VARCHAR(20) NOT NULL,
    time_slot_id INT NOT NULL,
    fee_package_id INT NULL,
    student_photo VARCHAR(255) DEFAULT NULL,
    sr_number VARCHAR(50) DEFAULT NULL,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(50) DEFAULT 'Pakistani',
    cnic VARCHAR(20) NOT NULL,
    education_level VARCHAR(50) DEFAULT NULL,
    mailing_address TEXT NOT NULL,
    permanent_address TEXT NOT NULL,
    student_mobile VARCHAR(20) NOT NULL,
    guardian_mobile VARCHAR(20) NOT NULL,
    student_email VARCHAR(100) NULL,
    guardian_email VARCHAR(100) NULL,
    occupation VARCHAR(100) NULL,
    monthly_income DECIMAL(12,2) NULL,
    status ENUM('active', 'completed', 'dropped', 'transferred') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_package_id) REFERENCES fee_packages(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 5. STUDENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    status ENUM('active', 'struck_off', 'graduated') DEFAULT 'active',
    struck_off_date DATE NULL,
    struck_off_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 6. ENROLLMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    slot_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_slot (slot_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 7. ATTENDANCE TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    slot_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'leave') NOT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, slot_id, date),
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 8. ASSESSMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    assessment_type ENUM('exam', 'quiz', 'assignment', 'project') NOT NULL,
    grade VARCHAR(10) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- ============================================
-- 9. COURSE TEACHERS
-- ============================================
CREATE TABLE IF NOT EXISTS course_teachers (
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (course_id, teacher_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 10. PDF SETTINGS
-- ============================================
CREATE TABLE IF NOT EXISTS pdf_settings (
    id INT PRIMARY KEY DEFAULT 1,
    college_name VARCHAR(100) DEFAULT 'National College',
    college_logo VARCHAR(255) DEFAULT '',
    college_address VARCHAR(255) DEFAULT '123 Education Blvd, City',
    college_phone VARCHAR(50) DEFAULT '+92 300 1234567',
    college_email VARCHAR(100) DEFAULT 'info@nationalcollege.edu',
    footer_text VARCHAR(255) DEFAULT 'System Generated Document',
    watermark_text VARCHAR(100) DEFAULT 'NATIONAL COLLEGE',
    header_color VARCHAR(20) DEFAULT '#0A1628',
    table_header_bg VARCHAR(20) DEFAULT '#f1f5f9',
    table_header_color VARCHAR(20) DEFAULT '#1e293b',
    table_border_color VARCHAR(20) DEFAULT '#e2e8f0',
    show_logo TINYINT(1) DEFAULT 1,
    show_watermark TINYINT(1) DEFAULT 0,
    show_signature TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 11. LOGIN HISTORY TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB;

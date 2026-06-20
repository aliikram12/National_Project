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
-- 1b. DEPARTMENTS TABLE
-- ============================================
CREATE TABLE departments (
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
CREATE TABLE courses ( 
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
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
CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_range VARCHAR(50) NOT NULL,
    duration VARCHAR(50) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

INSERT INTO slots (time_range, duration) VALUES 
('08:00 AM - 09:00 AM', '1 Hour'),
('09:00 AM - 10:00 AM', '1 Hour'),
('10:00 AM - 11:00 AM', '1 Hour'),
('02:00 PM - 03:00 PM', '1 Hour'),
('03:00 PM - 04:00 PM', '1 Hour');

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

INSERT INTO fee_packages (name, description, total_fee, discount_percent, discount_amount, duration_months) VALUES
('Standard Package', 'Regular admission package with standard fees', 25000, 0, 0, 3),
('Discount Package', '10% discount on standard fee', 22500, 10, 0, 3),
('Scholarship Package', '25% scholarship discount', 18750, 25, 0, 3),
('Special Package', 'Custom special fee arrangement', 20000, 0, 2000, 3),
('Weekend Package', 'Weekend batch with special schedule', 30000, 0, 0, 3);

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
-- 4b. ADMISSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    date_of_admission DATE NOT NULL,
    duration VARCHAR(50) NOT NULL,
    degree_type ENUM('Private', 'Government') DEFAULT 'Private',
    session_start ENUM('January','February','March','April','May','June','July','August','September','October','November','December') NOT NULL,
    session_end ENUM('January','February','March','April','May','June','July','August','September','October','November','December') NOT NULL,
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
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    FOREIGN KEY (time_slot_id) REFERENCES slots(id) ON DELETE RESTRICT,
    FOREIGN KEY (fee_package_id) REFERENCES fee_packages(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_registration (registration_number),
    INDEX idx_course (course_id),
    INDEX idx_student_name (student_name),
    INDEX idx_father_name (father_name),
    INDEX idx_cnic (cnic),
    INDEX idx_mobile (student_mobile),
    INDEX idx_time_slot (time_slot_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
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
    college_name VARCHAR(200) DEFAULT 'National College of Technology',
    college_logo VARCHAR(500) DEFAULT '',
    college_address VARCHAR(500) DEFAULT 'National Building Near UBL Bank University Road Sargodha',
    college_phone VARCHAR(50) DEFAULT '0316-7772003 | 0316-7772004 | 00 92 048 3212277',
    college_email VARCHAR(100) DEFAULT 'ncet.sgd@gmail.com',
    footer_text VARCHAR(500) DEFAULT 'Generated by National College LMS',
    watermark_text VARCHAR(100) DEFAULT 'NATIONAL COLLEGE OF TECHNOLOGY',
    header_color VARCHAR(20) DEFAULT '#0a192f',
    table_header_bg VARCHAR(20) DEFAULT '#0a192f',
    table_header_color VARCHAR(20) DEFAULT '#ffffff',
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
INSERT INTO pdf_templates (name, content) VALUES ('admission_form', '<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; font-family: Inter, sans-serif; border-top: 6px solid #0A2540;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f4f8; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h1 style="color: #0A2540; margin: 0; font-size: 28px; font-weight: 700;">NATIONAL COLLEGE OF TECHNOLOGY</h1>
            <p style="color: #718096; margin: 5px 0 0 0; font-size: 13px; letter-spacing: 1px;">EXCELLENCE IN EDUCATION & LEADERSHIP</p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 1px;">Admission Record</div>
            <div style="font-size: 16px; font-weight: 600; color: #1a56db; margin-top: 4px;">REF-{enrollment_date}</div>
        </div>
    </div>
    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h2 style="color: #0A2540; font-size: 16px; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">Student Information</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr><td style="padding: 8px 0; color: #718096; width: 140px;">Full Name</td><td style="font-weight: 600; color: #1e293b;">{student_name}</td></tr>
            <tr><td style="padding: 8px 0; color: #718096;">Father Name</td><td style="font-weight: 600; color: #1e293b;">{father_name}</td></tr>
            <tr><td style="padding: 8px 0; color: #718096;">Date of Birth</td><td style="font-weight: 600; color: #1e293b;">{dob}</td></tr>
            <tr><td style="padding: 8px 0; color: #718096;">Contact</td><td style="font-weight: 600; color: #1e293b;">{contact}</td></tr>
            <tr><td style="padding: 8px 0; color: #718096;">Address</td><td style="font-weight: 600; color: #1e293b;">{address}</td></tr>
        </table>
    </div>
    <h2 style="color: #0A2540; font-size: 16px; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">Enrollment Details</h2>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 40px;">
        <thead>
            <tr style="background: #0A2540; color: white;">
                <th style="padding: 12px; text-align: left;">Program</th>
                <th style="padding: 12px; text-align: left;">Duration</th>
                <th style="padding: 12px; text-align: left;">Time Slot</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{course_name}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">{duration}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1a56db;">{time_slot}</td>
            </tr>
        </tbody>
    </table>
    <div style="display: flex; justify-content: space-between; margin-top: 80px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <div style="text-align: center; width: 180px;">
            <div style="border-bottom: 1px solid #2D3748; height: 30px;"></div>
            <div style="font-size: 11px; color: #718096; margin-top: 5px;">Student Signature</div>
        </div>
        <div style="text-align: center; width: 180px;">
            <div style="border-bottom: 1px solid #2D3748; height: 30px;"></div>
            <div style="font-size: 11px; color: #718096; margin-top: 5px;">Receptionist</div>
        </div>
        <div style="text-align: center; width: 180px;">
            <div style="border-bottom: 1px solid #2D3748; height: 30px;"></div>
            <div style="font-size: 11px; color: #718096; margin-top: 5px;">Principal</div>
        </div>
    </div>
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #a0aec0;">
        National College of Technology LMS &bull; Generated on {enrollment_date}
    </div>
</div>');

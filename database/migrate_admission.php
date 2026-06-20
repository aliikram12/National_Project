<?php
/**
 * National College LMS - Admission Module Migration
 * Run this script ONCE to create/update admission tables.
 */

require __DIR__ . '/../config/db.php';

echo "=== Admission Module Migration ===\n\n";

try {
    // ============================================
    // 1. UPDATE SLOTS TABLE
    // ============================================
    $pdo->exec("ALTER TABLE slots ADD COLUMN IF NOT EXISTS duration VARCHAR(50) NULL AFTER time_range");
    $pdo->exec("ALTER TABLE slots ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER duration");
    $pdo->exec("ALTER TABLE slots ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

    // ============================================
    // 2. UPDATE COURSES TABLE
    // ============================================
    $pdo->exec("ALTER TABLE courses ADD COLUMN IF NOT EXISTS code VARCHAR(20) NULL AFTER id");
    $pdo->exec("ALTER TABLE courses ADD COLUMN IF NOT EXISTS fee DECIMAL(10,2) DEFAULT 0.00 AFTER description");

    // ============================================
    // 3. CREATE FEE_PACKAGES TABLE
    // ============================================
    $pdo->exec("
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
        ) ENGINE=InnoDB
    ");

    // ============================================
    // 4. CREATE / UPDATE ADMISSIONS TABLE
    // ============================================
    $pdo->exec("
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
        ) ENGINE=InnoDB
    ");

    // Keep older installs compatible by adding course_id and copying program_id.
    $hasAdmissions = $pdo->query("SHOW TABLES LIKE 'admissions'")->rowCount() > 0;
    if ($hasAdmissions) {
        $hasCourseId = $pdo->query("SHOW COLUMNS FROM admissions LIKE 'course_id'")->rowCount() > 0;
        $hasProgramId = $pdo->query("SHOW COLUMNS FROM admissions LIKE 'program_id'")->rowCount() > 0;

        if (!$hasCourseId) {
            $pdo->exec("ALTER TABLE admissions ADD COLUMN course_id INT NULL AFTER registration_number");
        }
        if ($hasProgramId && !$hasCourseId) {
            $pdo->exec("UPDATE admissions SET course_id = program_id WHERE course_id IS NULL");
        }
        $pdo->exec("ALTER TABLE admissions MODIFY course_id INT NULL");
        $pdo->exec("UPDATE admissions SET course_id = 1 WHERE course_id IS NULL AND EXISTS (SELECT 1 FROM courses LIMIT 1)");
        $pdo->exec("ALTER TABLE admissions MODIFY course_id INT NOT NULL");
        $pdo->exec("ALTER TABLE admissions ADD INDEX IF NOT EXISTS idx_course (course_id)");
        $pdo->exec("ALTER TABLE admissions ADD INDEX IF NOT EXISTS idx_father_name (father_name)");
        $pdo->exec("ALTER TABLE admissions ADD INDEX IF NOT EXISTS idx_time_slot (time_slot_id)");
    }

    // ============================================
    // 5. SEED FEE_PACKAGES (if empty)
    // ============================================
    $count = $pdo->query("SELECT COUNT(*) FROM fee_packages")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO fee_packages (name, description, total_fee, discount_percent, discount_amount, duration_months) VALUES (?, ?, ?, ?, ?, ?)");
        $packages = [
            ['Standard Package', 'Regular admission package with standard fees', 25000, 0, 0, 3],
            ['Discount Package', '10% discount on standard fee', 22500, 10, 0, 3],
            ['Scholarship Package', '25% scholarship discount', 18750, 25, 0, 3],
            ['Special Package', 'Custom special fee arrangement', 20000, 0, 2000, 3],
            ['Weekend Package', 'Weekend batch with special schedule', 30000, 0, 0, 3],
        ];
        foreach ($packages as $p) {
            $stmt->execute($p);
        }
        echo "✓ Seeded " . count($packages) . " fee packages\n";
    }

    // ============================================
    // 6. SEED SLOTS with durations
    // ============================================
    $count = $pdo->query("SELECT COUNT(*) FROM slots")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO slots (time_range, duration, status) VALUES (?, ?, 'active')");
        $slots = [
            ['08:00 AM - 09:00 AM', '1 Hour'],
            ['09:00 AM - 10:00 AM', '1 Hour'],
            ['10:00 AM - 11:00 AM', '1 Hour'],
            ['02:00 PM - 03:00 PM', '1 Hour'],
            ['03:00 PM - 04:00 PM', '1 Hour'],
        ];
        foreach ($slots as $s) {
            $stmt->execute($s);
        }
        echo "✓ Seeded " . count($slots) . " time slots\n";
    } else {
        $pdo->exec("UPDATE slots SET duration='1 Hour' WHERE duration IS NULL");
    }

    // ============================================
    // 7. UPDATE COURSES with codes if missing
    // ============================================
    $stmt = $pdo->query("SELECT id, name FROM courses WHERE code IS NULL OR code = ''");
    $courses = $stmt->fetchAll();
    $updateStmt = $pdo->prepare("UPDATE courses SET code = ? WHERE id = ?");
    foreach ($courses as $c) {
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $c['name']), 0, 6));
        $updateStmt->execute([$code . '-' . str_pad($c['id'], 3, '0', STR_PAD_LEFT), $c['id']]);
    }
    if (!empty($courses)) echo "✓ Updated " . count($courses) . " course codes\n";

    echo "\n=== Migration completed successfully! ===\n";
    echo "You can now access the enhanced Admission Management Module.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

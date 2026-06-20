<?php
require_once __DIR__ . '/../config/db.php';
ob_start();
try {
    echo "Starting full migration...\n";

    // 1. COURSES TABLE
    try {
        $pdo->exec("ALTER TABLE courses ADD COLUMN code VARCHAR(20) NULL AFTER id");
        echo "Added 'code' to courses.\n";
    } catch (PDOException $e) {}
    
    try {
        $pdo->exec("ALTER TABLE courses ADD COLUMN fee DECIMAL(10,2) DEFAULT 0.00 AFTER description");
        echo "Added 'fee' to courses.\n";
    } catch (PDOException $e) {}

    try {
        $pdo->exec("ALTER TABLE courses ADD COLUMN duration_months INT DEFAULT 1 AFTER duration");
        echo "Added 'duration_months' to courses.\n";
    } catch (PDOException $e) {}

    // 2. SLOTS TABLE
    try {
        $pdo->exec("ALTER TABLE slots ADD COLUMN duration VARCHAR(50) NULL AFTER time_range");
        echo "Added 'duration' to slots.\n";
    } catch (PDOException $e) {}

    // 3. FEE PACKAGES TABLE
    $pdo->exec("CREATE TABLE IF NOT EXISTS fee_packages (
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
    ) ENGINE=InnoDB");
    
    $count = $pdo->query("SELECT COUNT(*) FROM fee_packages")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO fee_packages (name, description, total_fee, discount_percent, discount_amount, duration_months) VALUES 
        ('Standard Package', 'Regular admission package with standard fees', 25000, 0, 0, 3),
        ('Discount Package', '10% discount on standard fee', 22500, 10, 0, 3)");
    }

    // 4. ADMISSIONS TABLE
    $stmt = $pdo->query("SHOW TABLES LIKE 'admissions'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE IF NOT EXISTS admissions (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;";
        $pdo->exec($sql);
        echo "Admissions table created.\n";
    } else {
        $columns = [
            'registration_number' => "VARCHAR(50) NOT NULL",
            'course_id' => "INT NOT NULL",
            'date_of_admission' => "DATE NOT NULL",
            'duration' => "VARCHAR(50) NOT NULL",
            'degree_type' => "ENUM('Private', 'Government') DEFAULT 'Private'",
            'session_start' => "VARCHAR(20) NOT NULL",
            'session_end' => "VARCHAR(20) NOT NULL",
            'time_slot_id' => "INT NOT NULL",
            'fee_package_id' => "INT NULL",
            'student_photo' => "VARCHAR(255) DEFAULT NULL",
            'sr_number' => "VARCHAR(50) DEFAULT NULL",
            'student_name' => "VARCHAR(100) NOT NULL",
            'father_name' => "VARCHAR(100) NOT NULL",
            'gender' => "ENUM('Male', 'Female', 'Other') DEFAULT 'Male'",
            'date_of_birth' => "DATE NOT NULL",
            'nationality' => "VARCHAR(50) DEFAULT 'Pakistani'",
            'cnic' => "VARCHAR(20) NOT NULL",
            'mailing_address' => "TEXT NOT NULL",
            'permanent_address' => "TEXT NOT NULL",
            'student_mobile' => "VARCHAR(20) NOT NULL",
            'guardian_mobile' => "VARCHAR(20) NOT NULL",
            'student_email' => "VARCHAR(100) NULL",
            'guardian_email' => "VARCHAR(100) NULL",
            'occupation' => "VARCHAR(100) NULL",
            'monthly_income' => "DECIMAL(12,2) NULL",
            'status' => "ENUM('active', 'completed', 'dropped', 'transferred') DEFAULT 'active'",
            'created_by' => "INT NOT NULL"
        ];

        foreach ($columns as $col => $def) {
            try { $pdo->exec("ALTER TABLE admissions ADD COLUMN $col $def"); } catch (PDOException $e) {}
        }
        
        try { $pdo->exec("ALTER TABLE admissions RENAME COLUMN program_id TO course_id"); } catch (PDOException $e) {}
        echo "Admissions table updated.\n";
    }

    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
ob_end_clean();

<?php
require __DIR__ . '/../config/db.php';

try {
    echo "Starting admissions table migration...\n";

    // First check if the table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admissions'");
    if ($stmt->rowCount() == 0) {
        echo "Admissions table does not exist. Creating it...\n";
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
            FOREIGN KEY (time_slot_id) REFERENCES slots(id) ON DELETE RESTRICT,
            FOREIGN KEY (fee_package_id) REFERENCES fee_packages(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB;";
        $pdo->exec($sql);
        echo "Admissions table created successfully.\n";
    } else {
        echo "Admissions table exists. Updating columns...\n";
        
        $columns = [
            'registration_number' => "VARCHAR(50) NOT NULL",
            'course_id' => "INT NOT NULL", // In admission_form.php, it's called program_id. Let's make it course_id to match schema.
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
            try {
                $pdo->exec("ALTER TABLE admissions ADD COLUMN $col $def");
                echo "Added column $col.\n";
            } catch (PDOException $e) {
                // Column probably already exists, which is fine
                // But we should attempt to MODIFY it in case it's the wrong type
                try {
                    $pdo->exec("ALTER TABLE admissions MODIFY COLUMN $col $def");
                    // echo "Modified column $col.\n";
                } catch (PDOException $e2) {
                    echo "Error modifying $col: " . $e2->getMessage() . "\n";
                }
            }
        }
        
        // Also check if program_id exists instead of course_id and rename if needed
        try {
            $pdo->exec("ALTER TABLE admissions RENAME COLUMN program_id TO course_id");
            echo "Renamed program_id to course_id.\n";
        } catch (PDOException $e) {}

        echo "Columns updated successfully.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

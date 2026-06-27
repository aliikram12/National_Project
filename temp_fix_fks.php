<?php
require 'config/db.php';

try {
    // Drop existing foreign keys that point to the `students` table
    $pdo->exec("ALTER TABLE attendance DROP FOREIGN KEY attendance_ibfk_1");
    $pdo->exec("ALTER TABLE assessments DROP FOREIGN KEY assessments_ibfk_1");

    // Add new foreign keys pointing to the `admissions` table
    $pdo->exec("ALTER TABLE attendance ADD CONSTRAINT fk_attendance_admissions FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE assessments ADD CONSTRAINT fk_assessments_admissions FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE");

    echo "Foreign keys successfully updated to reference the admissions table!\n";
} catch (PDOException $e) {
    // If it fails, maybe the key names are different. Let's try to find the key names dynamically.
    echo "Error updating foreign keys: " . $e->getMessage() . "\n";
    
    // Try to get the foreign key names from information_schema
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'national_college' AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME = 'students'");
    $attendanceFk = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'national_college' AND TABLE_NAME = 'assessments' AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME = 'students'");
    $assessmentsFk = $stmt->fetchColumn();
    
    if ($attendanceFk) {
        $pdo->exec("ALTER TABLE attendance DROP FOREIGN KEY `$attendanceFk`");
        $pdo->exec("ALTER TABLE attendance ADD CONSTRAINT fk_attendance_admissions FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE");
        echo "Dynamically dropped and recreated attendance FK.\n";
    }
    
    if ($assessmentsFk) {
        $pdo->exec("ALTER TABLE assessments DROP FOREIGN KEY `$assessmentsFk`");
        $pdo->exec("ALTER TABLE assessments ADD CONSTRAINT fk_assessments_admissions FOREIGN KEY (student_id) REFERENCES admissions(id) ON DELETE CASCADE");
        echo "Dynamically dropped and recreated assessments FK.\n";
    }
}
?>

<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "<h1>Starting Database Cleaning and Seeding...</h1>";

    // 1. Disable Foreign Key Checks to truncate safely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 2. Truncate Tables
    $tablesToTruncate = [
        'assessments',
        'attendance',
        'enrollments',
        'admissions',
        'students',
        'slots',
        'courses',
        'course_teachers',
        'login_history',
        'fee_packages',
        'users'
    ];

    foreach ($tablesToTruncate as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "Cleared table: $table<br>";
    }

    // 3. Re-enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 4. Seed Users (1 Admin, 1 Teacher, 1 Receptionist)
    $password = password_hash('password123', PASSWORD_DEFAULT);
    
    $users = [
        ['name' => 'Admin User', 'email' => 'admin@national.edu', 'role' => 'admin', 'password' => $password],
        ['name' => 'Teacher User', 'email' => 'teacher@national.edu', 'role' => 'teacher', 'password' => $password],
        ['name' => 'Receptionist User', 'email' => 'reception@national.edu', 'role' => 'receptionist', 'password' => $password]
    ];
    
    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, role, password, status) VALUES (?, ?, ?, ?, 'active')");
    foreach ($users as $u) {
        $stmtUser->execute([$u['name'], $u['email'], $u['role'], $u['password']]);
    }
    echo "<br>Created 3 default users (admin, teacher, receptionist). Password: password123<br>";

    // 5. Seed Slots
    $slots = [
        '8:30 to 10:00',
        '10:00 to 12:00',
        '12:00 to 02:00',
        '02:30 to 04:30'
    ];
    $stmtSlot = $pdo->prepare("INSERT INTO slots (time_range, duration, status) VALUES (?, 'Custom', 'active')");
    foreach ($slots as $s) {
        $stmtSlot->execute([$s]);
    }
    echo "<br>Created 4 time slots.<br>";

    // 6. Seed Courses (21 subjects * 3 durations = 63 courses)
    $subjects = [
        'Computer applications',
        'Data entry operator',
        'Office management',
        'Computerized accounting',
        'Web designing & development',
        'Python',
        'Graphic designing',
        'Video editing',
        'Spoken English',
        'Digital marketing',
        'Amazon (E-Commerce)',
        'Shopify (E-Commerce)',
        'Short hand',
        'Nebosh',
        'Iosh',
        'Osha',
        'HSE Officer',
        'Safety inspector',
        'Civil surveyor',
        'Auto cad',
        'Quantity surveyor'
    ];

    $durations = [
        ['text' => '3 Months', 'months' => 3],
        ['text' => '6 Months', 'months' => 6],
        ['text' => '1 Year', 'months' => 12]
    ];

    $stmtCourse = $pdo->prepare("INSERT INTO courses (name, duration, duration_months, status, fee) VALUES (?, ?, ?, 'active', 20000)");
    
    $courseCount = 0;
    foreach ($subjects as $subject) {
        foreach ($durations as $duration) {
            $courseName = $subject . ' - ' . $duration['text'];
            $stmtCourse->execute([$courseName, $duration['text'], $duration['months']]);
            $courseCount++;
        }
    }
    echo "<br>Created $courseCount courses.<br>";

    // 7. Insert default Fee Package so admissions form works
    $pdo->exec("INSERT INTO fee_packages (name, description, total_fee, discount_percent, discount_amount, duration_months) VALUES 
        ('Standard Package', 'Regular admission package with standard fees', 20000, 0, 0, 3)");

    echo "<h2><span style='color:green'>Done! Database is clean and seeded correctly.</span></h2>";

} catch (Exception $e) {
    echo "<h2><span style='color:red'>Error: " . $e->getMessage() . "</span></h2>";
}
?>

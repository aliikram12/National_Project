<?php
require __DIR__ . '/../config/db.php';

// Disable foreign key checks for truncation
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// Clear existing data
$pdo->exec('TRUNCATE TABLE users');
$pdo->exec('TRUNCATE TABLE courses');
$pdo->exec('TRUNCATE TABLE slots');
$pdo->exec('TRUNCATE TABLE students');
$pdo->exec('TRUNCATE TABLE enrollments');
$pdo->exec('TRUNCATE TABLE course_teachers');

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// 1. Add Users (Admin, Teacher, Receptionist)
$users = [
    ['Admin User', 'admin@national.edu', 'admin123', 'admin'],
    ['Teacher Ali', 'teacher@national.edu', 'teacher123', 'teacher'],
    ['Receptionist Sara', 'reception@national.edu', 'reception123', 'receptionist'],
];

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
foreach ($users as $u) {
    $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]]);
}
$teacher_id = $pdo->query("SELECT id FROM users WHERE role='teacher' LIMIT 1")->fetchColumn();

// 2. Add Time Slots
$pdo->exec("INSERT INTO slots (time_range) VALUES ('8:00 AM - 10:00 AM'), ('10:00 AM - 12:00 PM'), ('12:00 PM - 2:00 PM'), ('2:30 PM - 4:30 PM')");
$slot_id = $pdo->query("SELECT id FROM slots LIMIT 1")->fetchColumn();

// 3. Add Courses
$pdo->exec("INSERT INTO courses (name, duration, description) VALUES 
('Computer Science', '6 Months', 'Learn programming and software development.'),
('Graphic Design', '3 Months', 'Adobe Photoshop, Illustrator, and UI/UX.')");
$course_id = $pdo->query("SELECT id FROM courses LIMIT 1")->fetchColumn();

// 4. Assign Teacher to Course & Slot
$pdo->exec("INSERT INTO course_teachers (course_id, teacher_id, slot_id) VALUES ($course_id, $teacher_id, $slot_id)");

// 5. Add Dummy Students
$stmt = $pdo->prepare("INSERT INTO students (name, father_name, dob, contact, address) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['John Doe', 'Richard Doe', '2002-05-15', '1234567890', '123 Main St, City']);
$student1 = $pdo->lastInsertId();

$stmt->execute(['Jane Smith', 'William Smith', '2001-08-20', '0987654321', '456 Oak St, City']);
$student2 = $pdo->lastInsertId();

// 6. Enroll Students
$pdo->exec("INSERT INTO enrollments (student_id, course_id, slot_id, enrollment_date) VALUES 
($student1, $course_id, $slot_id, '2023-10-01'),
($student2, $course_id, $slot_id, '2023-10-02')");

echo "Database seeded successfully!\n";
?>

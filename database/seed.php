<?php
require __DIR__ . '/../config/db.php';

echo "=== National College LMS - Database Seeder ===\n\n";

// Disable foreign key checks for truncation
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// Clear existing data
$tables = ['notifications', 'assessments', 'attendance', 'course_teachers', 'enrollments', 'students', 'courses', 'users', 'pdf_templates'];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// ============================================
// 1. USERS
// ============================================
$users = [
    ['Super Admin',        'admin@national.edu',      'admin123',      'admin'],
    ['Prof. Ahmad Khan',   'ahmad@national.edu',      'teacher123',    'teacher'],
    ['Prof. Fatima Noor',  'fatima@national.edu',     'teacher123',    'teacher'],
    ['Prof. Bilal Raza',   'bilal@national.edu',      'teacher123',    'teacher'],
    ['Prof. Ayesha Malik', 'ayesha@national.edu',     'teacher123',    'teacher'],
    ['Prof. Usman Ali',    'usman@national.edu',      'teacher123',    'teacher'],
    ['Sara Receptionist',  'reception@national.edu',  'reception123',  'receptionist'],
    ['Hina Receptionist',  'hina@national.edu',       'reception123',  'receptionist'],
];

$userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$userIds = [];
foreach ($users as $u) {
    $userStmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]]);
    $userIds[] = $pdo->lastInsertId();
}
echo "✓ " . count($users) . " users created\n";

// Get teacher IDs
$teacherIds = [];
$teacherStmt = $pdo->query("SELECT id FROM users WHERE role='teacher' ORDER BY id");
while ($row = $teacherStmt->fetch()) {
    $teacherIds[] = $row['id'];
}

// ============================================
// 2. COURSES (13 courses as required)
// ============================================
$courses = [
    ['Web Development',       '6 Months', 'Learn HTML, CSS, JavaScript, PHP and build modern responsive websites and web applications.'],
    ['Graphic Designing',     '4 Months', 'Master Adobe Photoshop, Illustrator, and InDesign for print and digital media design.'],
    ['UI/UX Design',          '5 Months', 'User experience research, wireframing, prototyping with Figma and Adobe XD.'],
    ['Digital Marketing',     '3 Months', 'SEO, social media marketing, Google Ads, email marketing and analytics.'],
    ['SEO',                   '2 Months', 'Search Engine Optimization techniques, keyword research, on-page and off-page SEO.'],
    ['Flutter Development',   '6 Months', 'Cross-platform mobile app development with Flutter and Dart programming.'],
    ['Python Programming',    '4 Months', 'Python fundamentals, data structures, OOP, and introduction to data science.'],
    ['PHP Development',       '5 Months', 'Server-side development with PHP, MySQL, Laravel framework and RESTful APIs.'],
    ['MERN Stack',            '8 Months', 'Full-stack development with MongoDB, Express.js, React.js and Node.js.'],
    ['Cyber Security',        '6 Months', 'Network security, ethical hacking, penetration testing, and security auditing.'],
    ['AI & Machine Learning', '8 Months', 'Machine learning algorithms, neural networks, TensorFlow and real-world AI projects.'],
    ['Office Management',     '3 Months', 'Microsoft Office Suite, business communication, data entry and office administration.'],
    ['Video Editing',         '3 Months', 'Adobe Premiere Pro, After Effects, DaVinci Resolve and professional video production.'],
];

$courseStmt = $pdo->prepare("INSERT INTO courses (name, duration, description) VALUES (?, ?, ?)");
$courseIds = [];
foreach ($courses as $c) {
    $courseStmt->execute([$c[0], $c[1], $c[2]]);
    $courseIds[] = $pdo->lastInsertId();
}
echo "✓ " . count($courses) . " courses created\n";

// ============================================
// 3. ASSIGN TEACHERS TO COURSES
// ============================================
$assignments = [
    [$courseIds[0], $teacherIds[0], 1],  // Web Dev - Ahmad - Slot 1
    [$courseIds[0], $teacherIds[0], 2],  // Web Dev - Ahmad - Slot 2
    [$courseIds[1], $teacherIds[1], 1],  // Graphic Design - Fatima - Slot 1
    [$courseIds[2], $teacherIds[1], 3],  // UI/UX - Fatima - Slot 3
    [$courseIds[3], $teacherIds[2], 2],  // Digital Marketing - Bilal - Slot 2
    [$courseIds[4], $teacherIds[2], 4],  // SEO - Bilal - Slot 4
    [$courseIds[5], $teacherIds[3], 1],  // Flutter - Ayesha - Slot 1
    [$courseIds[6], $teacherIds[3], 3],  // Python - Ayesha - Slot 3
    [$courseIds[7], $teacherIds[0], 3],  // PHP Dev - Ahmad - Slot 3
    [$courseIds[8], $teacherIds[4], 2],  // MERN - Usman - Slot 2
    [$courseIds[9], $teacherIds[4], 4],  // Cyber Security - Usman - Slot 4
    [$courseIds[10], $teacherIds[3], 4], // AI/ML - Ayesha - Slot 4
    [$courseIds[11], $teacherIds[1], 4], // Office Mgmt - Fatima - Slot 4
    [$courseIds[12], $teacherIds[2], 1], // Video Editing - Bilal - Slot 1
];

$assignStmt = $pdo->prepare("INSERT INTO course_teachers (course_id, teacher_id, slot_id) VALUES (?, ?, ?)");
foreach ($assignments as $a) {
    $assignStmt->execute($a);
}
echo "✓ " . count($assignments) . " teacher-course assignments created\n";

// ============================================
// 4. STUDENTS (40 realistic students)
// ============================================
$studentNames = [
    ['Ali Hassan',         'Imran Hassan',         '2003-03-15', '03001234567', '45 Gulberg III, Lahore'],
    ['Sana Tariq',         'Muhammad Tariq',        '2004-07-22', '03112345678', '12 DHA Phase 5, Lahore'],
    ['Usman Ghani',        'Abdul Ghani',           '2002-11-08', '03221234567', '78 Model Town, Lahore'],
    ['Zainab Fatima',      'Khurram Shahzad',       '2003-05-19', '03331234567', '23 Johar Town, Lahore'],
    ['Hamza Riaz',         'Riaz Ahmed',            '2004-01-30', '03441234567', '56 Cantt Area, Lahore'],
    ['Amna Bibi',          'Zaheer Abbas',          '2003-09-12', '03001234568', '89 Garden Town, Lahore'],
    ['Faisal Mehmood',     'Mehmood Akhtar',        '2002-04-25', '03112345679', '34 Iqbal Town, Lahore'],
    ['Nida Hussain',       'Ghulam Hussain',        '2004-06-18', '03221234568', '67 Township, Lahore'],
    ['Shahzaib Khan',      'Amir Khan',             '2003-12-03', '03331234568', '90 Gulshan Ravi, Lahore'],
    ['Mehwish Rani',       'Sultan Ahmed',          '2004-08-27', '03441234568', '13 Shadman, Lahore'],
    ['Talha Saeed',        'Muhammad Saeed',        '2003-02-14', '03001234569', '46 Defence Road, Lahore'],
    ['Hira Bashir',        'Bashir Ahmed',          '2002-10-09', '03112345680', '79 Faisal Town, Lahore'],
    ['Owais Raza',         'Raza Muhammad',          '2004-04-01', '03221234569', '24 Wapda Town, Lahore'],
    ['Sadia Parveen',      'Muhammad Akram',        '2003-07-16', '03331234569', '57 Valencia Town, Lahore'],
    ['Danish Ali',         'Muhammad Ali',           '2002-09-23', '03441234569', '80 EME Society, Lahore'],
    ['Rimsha Kanwal',      'Tariq Mahmood',         '2004-03-11', '03001234570', '15 Bahria Town, Lahore'],
    ['Asad Iqbal',         'Muhammad Iqbal',        '2003-11-28', '03112345681', '48 Lake City, Lahore'],
    ['Madiha Naz',         'Nazir Ahmed',           '2004-05-06', '03221234570', '81 Askari Colony, Lahore'],
    ['Bilal Ahmad',        'Liaquat Ahmad',         '2002-08-19', '03331234570', '26 GT Road, Lahore'],
    ['Anum Sheikh',        'Sheikh Rasheed',        '2003-01-07', '03441234570', '59 Mall Road, Lahore'],
    ['Kamran Yousaf',      'Muhammad Yousaf',       '2004-10-14', '03001234571', '82 Cavalry Ground, Lahore'],
    ['Farhat Jabeen',      'Jabeen Akhtar',         '2003-06-22', '03112345682', '17 Allama Iqbal Town, Lahore'],
    ['Waqar Hassan',       'Hasan Ali',             '2002-12-31', '03221234571', '50 Chungi, Lahore'],
    ['Bushra Qadir',       'Abdul Qadir',           '2004-02-17', '03331234571', '83 Barkat Market, Lahore'],
    ['Junaid Jamshed',     'Jamshed Akhtar',        '2003-08-05', '03441234571', '28 Thokar Niaz Baig, Lahore'],
    ['Rabia Aslam',        'Muhammad Aslam',        '2004-11-20', '03001234572', '61 Shahdara, Lahore'],
    ['Nabeel Ashraf',      'Muhammad Ashraf',       '2002-07-13', '03112345683', '84 Raiwind Road, Lahore'],
    ['Tayyaba Rehman',     'Abdul Rehman',          '2003-04-26', '03221234572', '19 Multan Road, Lahore'],
    ['Adeel Mustafa',      'Mustafa Khan',          '2004-09-08', '03331234572', '52 Ferozpur Road, Lahore'],
    ['Sajida Begum',       'Nazar Hussain',         '2003-10-15', '03441234572', '85 Canal Road, Lahore'],
    ['Irfan Haider',       'Haider Ali',            '2002-06-02', '03001234573', '30 Davis Road, Lahore'],
    ['Kiran Shahzadi',     'Shahzad Hussain',       '2004-01-19', '03112345684', '63 Jail Road, Lahore'],
    ['Mohsin Raza',        'Ghulam Raza',           '2003-03-28', '03221234573', '86 Queens Road, Lahore'],
    ['Laiba Akram',        'Muhammad Akram',        '2004-12-11', '03331234573', '21 Main Boulevard, Lahore'],
    ['Zubair Aslam',       'Aslam Pervez',          '2002-05-24', '03441234573', '54 Empress Road, Lahore'],
    ['Sumera Yousaf',      'Muhammad Yousaf',       '2003-09-07', '03001234574', '87 Beadon Road, Lahore'],
    ['Atif Raza',          'Raza Hussain',          '2004-08-15', '03112345685', '32 McLeod Road, Lahore'],
    ['Arooj Zahra',        'Zahra Bashir',          '2003-02-20', '03221234574', '65 Cooper Road, Lahore'],
    ['Shoaib Malik',       'Malik Riaz',            '2002-11-16', '03331234574', '88 Abbot Road, Lahore'],
    ['Nimra Batool',       'Ghulam Batool',         '2004-07-03', '03441234574', '23 Lawrence Road, Lahore'],
];

$studentStmt = $pdo->prepare("INSERT INTO students (name, father_name, dob, contact, address) VALUES (?, ?, ?, ?, ?)");
$studentIds = [];
foreach ($studentNames as $s) {
    $studentStmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4]]);
    $studentIds[] = $pdo->lastInsertId();
}
echo "✓ " . count($studentNames) . " students created\n";

// ============================================
// 5. ENROLLMENTS (distribute students across courses and slots)
// ============================================
$enrollData = [
    // Web Development - Slot 1 & 2
    [$studentIds[0],  $courseIds[0], 1], [$studentIds[1],  $courseIds[0], 1],
    [$studentIds[2],  $courseIds[0], 2], [$studentIds[3],  $courseIds[0], 2],
    [$studentIds[4],  $courseIds[0], 1],
    // Graphic Designing - Slot 1
    [$studentIds[5],  $courseIds[1], 1], [$studentIds[6],  $courseIds[1], 1],
    [$studentIds[7],  $courseIds[1], 1],
    // UI/UX Design - Slot 3
    [$studentIds[8],  $courseIds[2], 3], [$studentIds[9],  $courseIds[2], 3],
    [$studentIds[10], $courseIds[2], 3],
    // Digital Marketing - Slot 2
    [$studentIds[11], $courseIds[3], 2], [$studentIds[12], $courseIds[3], 2],
    // SEO - Slot 4
    [$studentIds[13], $courseIds[4], 4], [$studentIds[14], $courseIds[4], 4],
    // Flutter Development - Slot 1
    [$studentIds[15], $courseIds[5], 1], [$studentIds[16], $courseIds[5], 1],
    [$studentIds[17], $courseIds[5], 1],
    // Python Programming - Slot 3
    [$studentIds[18], $courseIds[6], 3], [$studentIds[19], $courseIds[6], 3],
    [$studentIds[20], $courseIds[6], 3],
    // PHP Development - Slot 3
    [$studentIds[21], $courseIds[7], 3], [$studentIds[22], $courseIds[7], 3],
    // MERN Stack - Slot 2
    [$studentIds[23], $courseIds[8], 2], [$studentIds[24], $courseIds[8], 2],
    [$studentIds[25], $courseIds[8], 2],
    // Cyber Security - Slot 4
    [$studentIds[26], $courseIds[9], 4], [$studentIds[27], $courseIds[9], 4],
    // AI & Machine Learning - Slot 4
    [$studentIds[28], $courseIds[10], 4], [$studentIds[29], $courseIds[10], 4],
    [$studentIds[30], $courseIds[10], 4],
    // Office Management - Slot 4
    [$studentIds[31], $courseIds[11], 4], [$studentIds[32], $courseIds[11], 4],
    // Video Editing - Slot 1
    [$studentIds[33], $courseIds[12], 1], [$studentIds[34], $courseIds[12], 1],
    [$studentIds[35], $courseIds[12], 1],
    // Extra enrollments for variety
    [$studentIds[36], $courseIds[0], 2], [$studentIds[37], $courseIds[3], 2],
    [$studentIds[38], $courseIds[6], 3], [$studentIds[39], $courseIds[9], 4],
];

$enrollStmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, slot_id, enrollment_date) VALUES (?, ?, ?, ?)");
$enrollDates = ['2025-09-01','2025-09-02','2025-09-03','2025-09-05','2025-09-08','2025-09-10','2025-09-12','2025-09-15','2025-10-01','2025-10-05','2025-10-10','2025-10-15','2025-11-01','2025-11-05','2025-11-10','2025-11-15','2025-12-01','2025-12-05','2025-12-10','2025-12-15','2026-01-05','2026-01-10','2026-01-15','2026-01-20','2026-02-01','2026-02-05','2026-02-10','2026-02-15','2026-03-01','2026-03-05','2026-03-10','2026-03-15','2026-04-01','2026-04-05','2026-04-10','2026-04-15','2026-04-20','2026-04-25','2026-05-01','2026-05-05'];
$enrollCount = 0;
foreach ($enrollData as $idx => $ed) {
    $date = $enrollDates[$idx % count($enrollDates)];
    $enrollStmt->execute([$ed[0], $ed[1], $ed[2], $date]);
    $enrollCount++;
}
echo "✓ $enrollCount enrollments created\n";

// ============================================
// 6. ATTENDANCE DATA (realistic distribution)
// ============================================
$attendanceStmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, slot_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?, ?)");
$statuses = ['present', 'present', 'present', 'present', 'absent', 'present', 'present', 'leave', 'present', 'present'];
$attendanceCount = 0;

// Generate attendance for last 30 days for enrolled students
foreach ($enrollData as $ed) {
    $studentId = $ed[0];
    $courseId = $ed[1];
    $slotId = $ed[2];
    
    // Find teacher for this course/slot
    $tStmt = $pdo->prepare("SELECT teacher_id FROM course_teachers WHERE course_id = ? AND slot_id = ? LIMIT 1");
    $tStmt->execute([$courseId, $slotId]);
    $teacherId = $tStmt->fetchColumn();
    
    for ($day = 30; $day >= 1; $day -= 2) { // Every other day
        $date = date('Y-m-d', strtotime("-$day days"));
        $status = $statuses[array_rand($statuses)];
        
        try {
            $attendanceStmt->execute([$studentId, $courseId, $slotId, $date, $status, $teacherId]);
            $attendanceCount++;
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
}
echo "✓ $attendanceCount attendance records created\n";

// Mark 3 students as struck off (simulate 3+ absences)
$struckOffStudents = [$studentIds[14], $studentIds[27], $studentIds[38]];
foreach ($struckOffStudents as $sId) {
    $pdo->prepare("UPDATE students SET status='struck_off', struck_off_date=CURDATE(), struck_off_reason='Exceeded 3 absences in a month' WHERE id=?")->execute([$sId]);
}
echo "✓ 3 students marked as struck off\n";

// ============================================
// 7. ASSESSMENTS DATA
// ============================================
$assessmentStmt = $pdo->prepare("INSERT INTO assessments (student_id, teacher_id, course_id, date, assessment_type, notes, grade) VALUES (?, ?, ?, ?, ?, ?, ?)");
$assessmentNotes = [
    ['daily_progress', 'Student is showing excellent progress in understanding core concepts. Actively participates in class discussions.', 'A'],
    ['assignment', 'Assignment submitted on time. Code quality is good but needs improvement in error handling.', 'B+'],
    ['daily_progress', 'Good attendance and punctuality. Needs to focus more on practical exercises.', 'B'],
    ['general', 'Student has completed the mid-term project successfully. Demonstrated good teamwork skills.', 'A-'],
    ['assignment', 'Late submission but quality of work is satisfactory. Provided detailed documentation.', 'B-'],
    ['exam', 'Scored well in the written exam. Practical skills need improvement.', 'B+'],
    ['daily_progress', 'Excellent lab work performance. Helps fellow students with their queries.', 'A'],
    ['general', 'Average performance. Recommended additional practice sessions.', 'C+'],
];

$assessmentCount = 0;
foreach ($enrollData as $idx => $ed) {
    if ($idx >= 20) break; // First 20 students
    $studentId = $ed[0];
    $courseId = $ed[1];
    $slotId = $ed[2];
    
    $tStmt = $pdo->prepare("SELECT teacher_id FROM course_teachers WHERE course_id = ? AND slot_id = ? LIMIT 1");
    $tStmt->execute([$courseId, $slotId]);
    $teacherId = $tStmt->fetchColumn();
    if (!$teacherId) continue;
    
    $note = $assessmentNotes[$idx % count($assessmentNotes)];
    $date = date('Y-m-d', strtotime("-" . ($idx * 3 + 1) . " days"));
    $assessmentStmt->execute([$studentId, $teacherId, $courseId, $date, $note[0], $note[1], $note[2]]);
    $assessmentCount++;
}
echo "✓ $assessmentCount assessment records created\n";

// ============================================
// 8. NOTIFICATIONS
// ============================================
$notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
$notifStmt->execute([$userIds[0], 'System Update', 'The LMS system has been updated to version 2.0 with new features.', 'info']);
$notifStmt->execute([$userIds[0], 'New Enrollment', '5 new students enrolled this week across multiple courses.', 'success']);
$notifStmt->execute([null, 'Maintenance Scheduled', 'System maintenance scheduled for next Sunday 2 AM - 4 AM.', 'warning']);
echo "✓ 3 notifications created\n";

// ============================================
// 9. UPDATE PDF TEMPLATE
// ============================================
$pdfTemplate = '
<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; font-family: Inter, sans-serif; border-top: 6px solid #0A2540;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f4f8; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h1 style="color: #0A2540; margin: 0; font-size: 28px; font-weight: 700;">NATIONAL COLLEGE</h1>
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
        National College LMS &bull; Generated on {enrollment_date}
    </div>
</div>';

$pdo->prepare("UPDATE pdf_templates SET content = ? WHERE name = 'admission_form'")->execute([$pdfTemplate]);
echo "✓ PDF template updated\n";

echo "\n=== Database seeded successfully! ===\n";
echo "\nDemo Credentials:\n";
echo "  Admin:        admin@national.edu / admin123\n";
echo "  Teacher:      ahmad@national.edu / teacher123\n";
echo "  Receptionist: reception@national.edu / reception123\n";
?>

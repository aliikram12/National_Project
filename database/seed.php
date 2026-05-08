<?php
require __DIR__ . '/../config/db.php';

echo "=== National College LMS - Database Seeder ===\n\n";

// Disable foreign key checks for truncation
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// Clear existing data
$tables = ['notifications', 'assessments', 'attendance', 'course_teachers', 'enrollments', 'students', 'slots', 'courses', 'users', 'pdf_templates'];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// ============================================
// 1. USERS
// ============================================
$users = [
    // Admin
    ['Mustafa Ahmad',        'admin@national.edu',      'admin123',      'admin', 'active'],
    
    // Receptionist
    ['Miss Zeenia',          'zeenia@national.edu',     'reception123',  'receptionist', 'active'],
    
    // Teachers
    ['Mr Asad-Ullah',        'asad@national.edu',       'teacher123',    'teacher', 'active'],
    ['Mr Amjid',             'amjid@national.edu',      'teacher123',    'teacher', 'active'],
    ['Miss Bisma',           'bisma@national.edu',      'teacher123',    'teacher', 'active'],
    ['Mr Ali Ikram',         'ali@national.edu',        'teacher123',    'teacher', 'active'],
    ['Miss Kiran',           'kiran@national.edu',      'teacher123',    'teacher', 'active'],
    ['Mr Amir',              'amir@national.edu',       'teacher123',    'teacher', 'active'],
];

$userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
$userIds = [];
foreach ($users as $u) {
    $userStmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3], $u[4]]);
    $userIds[] = $pdo->lastInsertId();
}
echo "✓ " . count($users) . " users created\n";

// Get teacher IDs by name to assign properly
$teacherMap = [];
$tStmt = $pdo->query("SELECT id, name FROM users WHERE role='teacher'");
while ($row = $tStmt->fetch()) {
    $teacherMap[$row['name']] = $row['id'];
}

// ============================================
// 2. COURSES
// ============================================
$courseNames = [
    'IT Courses', 'Regular E-Commerce Courses', 'Regular Civil Courses', 'Competency Based Diplomas',
    'Web Development', 'Graphic Designing', 'Computer Application', 'Data Entry Operator',
    'Python Programming', 'Office Management', 'Computerized Accounting', 'Blogging',
    'Web Designing', 'Shorthand', 'Photography', 'Video Editing', 'Digital Marketing',
    'Shopify Development', 'Freelancing', 'E-Commerce', 'Amazon Virtual Assistant',
    'NEBOSH', 'OTHM (Level-3)', 'IOSH Managing Safely (UK)', 'OSHA (USA) Workplace Safety',
    'HSE Officer (Pakistan)', 'Safety Inspector (Pakistan)', 'Civil Surveyor (Pakistan)',
    'AutoCAD (2D & 3D)', 'Quantity Surveyor (QS)', '3 Months Diploma', '6 Months Diploma',
    '1 Year Diploma', '2 Year Diploma', '3 Year Diploma'
];

$courseStmt = $pdo->prepare("INSERT INTO courses (name, duration, description) VALUES (?, ?, ?)");
$courseIds = [];
foreach ($courseNames as $idx => $cName) {
    // Generate some random duration and description
    $duration = (strpos($cName, 'Diploma') !== false) ? $cName : '3 Months';
    $desc = "Professional course for $cName at National College of Technology.";
    $courseStmt->execute([$cName, $duration, $desc]);
    $courseIds[$cName] = $pdo->lastInsertId();
}
echo "✓ " . count($courseNames) . " courses created\n";

// ============================================
// 3. SLOTS
// ============================================
$slots = [
    '8:00 AM - 10:00 AM',
    '10:00 AM - 12:00 PM',
    '12:00 PM - 2:00 PM',
    '2:30 PM - 4:30 PM',
    '4:30 PM - 6:30 PM',
    '6:30 PM - 8:30 PM'
];
$slotStmt = $pdo->prepare("INSERT INTO slots (time_range) VALUES (?)");
$slotIds = [];
foreach ($slots as $s) {
    $slotStmt->execute([$s]);
    $slotIds[] = $pdo->lastInsertId();
}
echo "✓ " . count($slots) . " slots created\n";

// ============================================
// 4. ASSIGN TEACHERS TO COURSES
// ============================================
// IT Teachers: Mr Asad-Ullah, Miss Bisma, Mr Ali Ikram, Miss Kiran
// Civil Teachers: Mr Amjid, Mr Amir

$itTeachers = [$teacherMap['Mr Asad-Ullah'], $teacherMap['Miss Bisma'], $teacherMap['Mr Ali Ikram'], $teacherMap['Miss Kiran']];
$civilTeachers = [$teacherMap['Mr Amjid'], $teacherMap['Mr Amir']];

$assignments = [];
$assignStmt = $pdo->prepare("INSERT INTO course_teachers (course_id, teacher_id, slot_id) VALUES (?, ?, ?)");
$courseTeacherMap = [];

foreach ($courseNames as $cName) {
    $cId = $courseIds[$cName];
    // Determine teacher type based on course name
    if (strpos(strtolower($cName), 'civil') !== false || strpos(strtolower($cName), 'autocad') !== false || strpos(strtolower($cName), 'surveyor') !== false || strpos(strtolower($cName), 'safety') !== false || strpos(strtolower($cName), 'nebosh') !== false || strpos(strtolower($cName), 'osh') !== false || strpos(strtolower($cName), 'hse') !== false) {
        $tId = $civilTeachers[array_rand($civilTeachers)];
    } else {
        $tId = $itTeachers[array_rand($itTeachers)];
    }
    
    // Assign to a random slot
    $sId = $slotIds[array_rand($slotIds)];
    
    try {
        $assignStmt->execute([$cId, $tId, $sId]);
        $assignments[] = [$cId, $tId, $sId];
        $courseTeacherMap[$cId] = ['teacher_id' => $tId, 'slot_id' => $sId];
    } catch(Exception $e) {}
}
echo "✓ Teachers assigned to courses\n";

// ============================================
// 5. STUDENTS
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
];

$studentStmt = $pdo->prepare("INSERT INTO students (name, father_name, dob, contact, address) VALUES (?, ?, ?, ?, ?)");
$studentIdList = [];
foreach ($studentNames as $s) {
    $studentStmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4]]);
    $studentIdList[] = $pdo->lastInsertId();
}
echo "✓ " . count($studentNames) . " students created\n";

// ============================================
// 6. ENROLLMENTS
// ============================================
$enrollStmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, slot_id, enrollment_date) VALUES (?, ?, ?, ?)");
$enrollDates = ['2026-05-01','2026-05-02','2026-05-03','2026-05-05','2026-05-08'];
$enrollCount = 0;

$cIdsList = array_values($courseIds);
foreach ($studentIdList as $idx => $sId) {
    // Each student gets 2 random courses
    $r1 = $cIdsList[array_rand($cIdsList)];
    $r2 = $cIdsList[array_rand($cIdsList)];
    while($r2 == $r1) $r2 = $cIdsList[array_rand($cIdsList)];
    
    $date = $enrollDates[$idx % count($enrollDates)];
    
    $ct1 = $courseTeacherMap[$r1];
    $enrollStmt->execute([$sId, $r1, $ct1['slot_id'], $date]);
    $enrollCount++;
    
    $ct2 = $courseTeacherMap[$r2];
    $enrollStmt->execute([$sId, $r2, $ct2['slot_id'], $date]);
    $enrollCount++;
}
echo "✓ $enrollCount enrollments created\n";

// ============================================
// 7. ATTENDANCE & ASSESSMENTS
// ============================================
$attendanceStmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, slot_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?, ?)");
$assessmentStmt = $pdo->prepare("INSERT INTO assessments (student_id, teacher_id, course_id, date, assessment_type, notes, grade) VALUES (?, ?, ?, ?, ?, ?, ?)");

$statuses = ['present', 'present', 'present', 'present', 'absent'];
$attendanceCount = 0;
$assessmentCount = 0;

$enrollments = $pdo->query("SELECT * FROM enrollments")->fetchAll();
foreach ($enrollments as $e) {
    $studentId = $e['student_id'];
    $courseId = $e['course_id'];
    $slotId = $e['slot_id'];
    $teacherId = $courseTeacherMap[$courseId]['teacher_id'];
    
    // 5 days of attendance
    for ($day = 5; $day >= 1; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        $status = $statuses[array_rand($statuses)];
        try {
            $attendanceStmt->execute([$studentId, $courseId, $slotId, $date, $status, $teacherId]);
            $attendanceCount++;
        } catch(Exception $ex) {}
    }
    
    // 1 assessment
    $date = date('Y-m-d', strtotime("-1 days"));
    $assessmentStmt->execute([$studentId, $teacherId, $courseId, $date, 'daily_progress', 'Doing well in the assignments and practical works.', 'A']);
    $assessmentCount++;
}

echo "✓ $attendanceCount attendance records created\n";
echo "✓ $assessmentCount assessment records created\n";

// ============================================
// 8. UPDATE PDF TEMPLATE
// ============================================
$pdfTemplate = '
<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; font-family: Inter, sans-serif; border-top: 6px solid #0A2540;">
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
</div>';

$pdo->prepare("UPDATE pdf_templates SET content = ? WHERE name = 'admission_form'")->execute([$pdfTemplate]);

$pdo->prepare("UPDATE pdf_settings SET 
    college_name='National College of Technology', 
    college_address='National Building Near UBL Bank University Road Sargodha',
    college_phone='0316-7772003 | 0316-7772004 | 00 92 048 3212277',
    college_email='ncet.sgd@gmail.com',
    footer_text='Generated by National College LMS',
    watermark_text='NATIONAL COLLEGE OF TECHNOLOGY'
")->execute();

echo "✓ PDF template updated\n";

echo "\n=== Database seeded successfully! ===\n";
echo "\nDemo Credentials:\n";
echo "  Admin:        admin@national.edu / admin123\n";
echo "  Teacher:      asad@national.edu / teacher123\n";
echo "  Receptionist: zeenia@national.edu / reception123\n";
?>

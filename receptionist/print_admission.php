<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$student_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.name as course_name, c.duration, sl.time_range, e.enrollment_date 
    FROM students s 
    JOIN enrollments e ON s.id = e.student_id 
    JOIN courses c ON e.course_id = c.id 
    JOIN slots sl ON e.slot_id = sl.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

// Fetch template from DB
$templateStmt = $pdo->query("SELECT content FROM pdf_templates WHERE name='admission_form'");
$templateRow = $templateStmt->fetch();
$template = $templateRow ? $templateRow['content'] : '<h1>National College</h1><h2>Admission Form</h2><p>Name: {student_name}</p>';

// Replace placeholders
$html = str_replace(
    ['{student_name}', '{father_name}', '{dob}', '{contact}', '{address}', '{course_name}', '{duration}', '{time_slot}', '{enrollment_date}'],
    [$student['name'], $student['father_name'], $student['dob'], $student['contact'], $student['address'], $student['course_name'], $student['duration'], $student['time_range'], date('d M Y', strtotime($student['enrollment_date']))],
    $template
);

// If template doesn't have structure, wrap it in some clean HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Admission - <?php echo htmlspecialchars($student['name']); ?></title>
    <style>
        body { font-family: 'Inter', 'Poppins', sans-serif; color: #333; margin: 0; padding: 40px; }
        .print-area { max-width: 800px; margin: 0 auto; border: 2px solid #0A2540; padding: 40px; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #0A2540; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #0A2540; margin: 0; font-size: 32px; }
        .header h2 { color: #555; margin: 5px 0 0; font-size: 20px; text-transform: uppercase; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .details-table th, .details-table td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        .details-table th { background: #f8f9fa; width: 30%; color: #0A2540; }
        .signatures { display: flex; justify-content: space-between; margin-top: 80px; }
        .signature-line { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .print-area { border: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0A2540; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px;">Print / Save as PDF</button>
        <a href="index.php" style="margin-left: 10px; color: #0A2540; text-decoration: none;">Back to Dashboard</a>
    </div>

    <div class="print-area">
        <div class="header">
            <h1>National College</h1>
            <h2>Student Admission Form</h2>
        </div>

        <table class="details-table">
            <tr>
                <th>Registration ID</th>
                <td><strong>NC-<?php echo date('Y'); ?>-<?php echo str_pad($student['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
            </tr>
            <tr>
                <th>Student Name</th>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
            </tr>
            <tr>
                <th>Father's Name</th>
                <td><?php echo htmlspecialchars($student['father_name']); ?></td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td><?php echo date('d M Y', strtotime($student['dob'])); ?></td>
            </tr>
            <tr>
                <th>Contact</th>
                <td><?php echo htmlspecialchars($student['contact']); ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo htmlspecialchars($student['address']); ?></td>
            </tr>
            <tr>
                <th>Course Enrolled</th>
                <td><?php echo htmlspecialchars($student['course_name']); ?> (<?php echo htmlspecialchars($student['duration']); ?>)</td>
            </tr>
            <tr>
                <th>Allocated Time Slot</th>
                <td><?php echo htmlspecialchars($student['time_range']); ?></td>
            </tr>
            <tr>
                <th>Date of Admission</th>
                <td><?php echo date('d M Y', strtotime($student['enrollment_date'])); ?></td>
            </tr>
        </table>

        <div class="signatures">
            <div class="signature-line">Student Signature</div>
            <div class="signature-line">Receptionist Signature</div>
            <div class="signature-line">Admin Signature</div>
        </div>
    </div>

</body>
</html>

<?php
require '../config/db.php';
requireRole('receptionist');

if (!isset($_GET['id'])) { die("Invalid request."); }
$stmt = $pdo->prepare("SELECT s.*, c.name as course_name, c.duration, sl.time_range, e.enrollment_date FROM students s JOIN enrollments e ON s.id=e.student_id JOIN courses c ON e.course_id=c.id JOIN slots sl ON e.slot_id=sl.id WHERE s.id=?");
$stmt->execute([(int)$_GET['id']]);
$s = $stmt->fetch();
if (!$s) die("Student not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Form - <?php echo e($s['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;color:#1e293b;padding:40px;background:#f8fafc}
        .no-print{text-align:center;margin-bottom:24px}
        .no-print button,.no-print a{padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
        .no-print .print-btn{background:#1a56db;color:#fff;border:none}
        .no-print .print-btn:hover{background:#3b82f6}
        .no-print .back-link{color:#1a56db;margin-left:16px}
        .form-container{max-width:800px;margin:0 auto;background:#fff;border-top:6px solid #0A1628;box-shadow:0 4px 20px rgba(0,0,0,.08);border-radius:0 0 8px 8px}
        .form-header{display:flex;justify-content:space-between;align-items:center;padding:32px 40px;border-bottom:2px solid #f1f5f9}
        .form-header h1{font-size:24px;color:#0A1628;font-weight:800;letter-spacing:-.02em}
        .form-header .tagline{color:#64748b;font-size:12px;letter-spacing:1px;margin-top:4px}
        .form-header .ref{text-align:right}
        .form-header .ref .label{font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px}
        .form-header .ref .id{font-size:16px;font-weight:700;color:#1a56db;margin-top:2px}
        .form-body{padding:32px 40px}
        .section-title{font-size:13px;font-weight:700;color:#0A1628;text-transform:uppercase;letter-spacing:.08em;padding-bottom:8px;border-bottom:2px solid #e2e8f0;margin-bottom:16px}
        .info-grid{display:grid;grid-template-columns:140px 1fr;gap:0;margin-bottom:24px}
        .info-grid .label{padding:10px 0;color:#64748b;font-size:13px;font-weight:500}
        .info-grid .value{padding:10px 0;font-weight:600;font-size:14px;color:#1e293b;border-bottom:1px solid #f8fafc}
        .enroll-table{width:100%;border-collapse:collapse;margin-bottom:32px}
        .enroll-table th{background:#0A1628;color:#fff;padding:12px 16px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;font-weight:600;text-align:left}
        .enroll-table td{padding:14px 16px;border-bottom:1px solid #e2e8f0;font-size:14px}
        .enroll-table td.highlight{font-weight:700;color:#1a56db}
        .signatures{display:flex;justify-content:space-between;margin-top:80px;padding-top:20px}
        .sig-box{text-align:center;width:160px}
        .sig-box .line{border-bottom:1px solid #334155;height:40px;margin-bottom:6px}
        .sig-box .name{font-size:11px;color:#64748b}
        .form-footer{text-align:center;padding:20px 40px;font-size:10px;color:#94a3b8;border-top:1px solid #f1f5f9}
        @media print{
            body{padding:0;background:#fff}
            .no-print{display:none}
            .form-container{box-shadow:none;border-top-width:4px}
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" class="print-btn"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg> Print / Save as PDF</button>
    <a href="index.php" class="back-link">← Back to Dashboard</a>
</div>

<div class="form-container">
    <div class="form-header">
        <div>
            <h1>NATIONAL COLLEGE</h1>
            <div class="tagline">EXCELLENCE IN EDUCATION & LEADERSHIP</div>
        </div>
        <div class="ref">
            <div class="label">Registration No.</div>
            <div class="id">NC-<?php echo date('Y'); ?>-<?php echo str_pad($s['id'],4,'0',STR_PAD_LEFT); ?></div>
        </div>
    </div>
    <div class="form-body">
        <div class="section-title">Student Information</div>
        <div class="info-grid">
            <div class="label">Full Name</div><div class="value"><?php echo e($s['name']); ?></div>
            <div class="label">Father's Name</div><div class="value"><?php echo e($s['father_name']); ?></div>
            <div class="label">Date of Birth</div><div class="value"><?php echo formatDate($s['dob']); ?></div>
            <div class="label">Contact</div><div class="value"><?php echo e($s['contact']); ?></div>
            <div class="label">Address</div><div class="value"><?php echo e($s['address']); ?></div>
        </div>

        <div class="section-title">Enrollment Details</div>
        <table class="enroll-table">
            <thead><tr><th>Program / Course</th><th>Duration</th><th>Time Slot</th><th>Admission Date</th></tr></thead>
            <tbody><tr>
                <td class="highlight"><?php echo e($s['course_name']); ?></td>
                <td><?php echo e($s['duration']); ?></td>
                <td class="highlight"><?php echo e($s['time_range']); ?></td>
                <td><?php echo formatDate($s['enrollment_date']); ?></td>
            </tr></tbody>
        </table>

        <div class="signatures">
            <div class="sig-box"><div class="line"></div><div class="name">Student Signature</div></div>
            <div class="sig-box"><div class="line"></div><div class="name">Receptionist</div></div>
            <div class="sig-box"><div class="line"></div><div class="name">Principal</div></div>
        </div>
    </div>
    <div class="form-footer">
        National College LMS &bull; This document was generated on <?php echo date('d M Y \a\t h:i A'); ?>
    </div>
</div>
</body>
</html>

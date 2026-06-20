<?php
/**
 * National College LMS - Print Admission Form
 */

require '../config/db.php';
$user = getCurrentUser($pdo);
if (!$user || !in_array($user['role'], ['admin', 'receptionist'])) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) { die("Invalid request."); }
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT a.*, c.name as course_name, c.duration as course_duration, c.fee, c.code as course_code,
                       s.time_range, u.name as created_by_name
                       FROM admissions a
                       JOIN courses c ON a.course_id = c.id
                       JOIN slots s ON a.time_slot_id = s.id
                       JOIN users u ON a.created_by = u.id
                       WHERE a.id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) die("Admission record not found.");

// Get fee package details
$feePkgName = '—';
$feePkgTotal = 0;
$feePkgDiscount = 0;
if ($a['fee_package_id']) {
    $fp = $pdo->prepare("SELECT * FROM fee_packages WHERE id = ?");
    $fp->execute([$a['fee_package_id']]);
    $fpRow = $fp->fetch();
    if ($fpRow) {
        $feePkgName = $fpRow['name'];
        $feePkgTotal = $fpRow['total_fee'];
        $feePkgDiscount = $fpRow['discount_percent'] + $fpRow['discount_amount'];
    }
}

$regNum = $a['registration_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form - <?php echo e($a['student_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 40px 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print { text-align: center; margin-bottom: 24px; }
        .no-print button, .no-print a {
            padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
            gap: 8px; transition: all 0.2s; border: none; font-family: inherit;
        }
        .no-print .print-btn { background: #15803d; color: #fff; }
        .no-print .print-btn:hover { background: #166534; }
        .no-print .back-btn { color: #64748b; margin-left: 16px; border: 1px solid #e2e8f0; }
        .no-print .back-btn:hover { background: #fff; color: #1e293b; }

        .print-admission-form {
            max-width: 800px; margin: 0 auto; background: #fff;
            border-top: 6px solid #15803d; border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden;
        }

        .print-header {
            padding: 28px 40px; display: flex; justify-content: space-between;
            align-items: flex-start; border-bottom: 2px solid #f1f5f9;
        }
        .print-header .college {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .print-header .college .logo {
            width: 60px; height: 60px; border-radius: 8px; background: #15803d; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold;
        }
        .print-header .college h1 {
            font-size: 22px; color: #14532d; font-weight: 800;
            letter-spacing: -0.01em; margin: 0;
        }
        .print-header .college .tagline {
            font-size: 11px; color: #64748b; letter-spacing: 1.5px;
            margin-top: 4px; text-transform: uppercase;
        }
        .print-header .reg { text-align: right; }
        .print-header .reg .lbl {
            font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;
        }
        .print-header .reg .val {
            font-size: 16px; font-weight: 700; color: #15803d; margin-top: 3px;
            font-family: 'Courier New', monospace;
        }

        .print-body { padding: 28px 40px; }

        .print-section-title {
            font-size: 12px; font-weight: 700; color: #14532d;
            text-transform: uppercase; letter-spacing: 0.08em;
            padding-bottom: 8px; border-bottom: 2px solid #bbf7d0;
            margin: 24px 0 16px 0; display: flex; align-items: center; gap: 8px;
        }
        .print-section-title:first-child { margin-top: 0; }
        .print-section-title i { font-size: 13px; color: #15803d; }

        .print-info-grid {
            display: grid; grid-template-columns: 160px 1fr; gap: 0; margin-bottom: 4px;
        }
        .print-info-grid .row-label {
            padding: 9px 0; color: #64748b; font-size: 13px; font-weight: 500;
        }
        .print-info-grid .row-value {
            padding: 9px 0; font-weight: 600; font-size: 14px; color: #1e293b;
            border-bottom: 1px solid #f8fafc;
        }

        .print-table {
            width: 100%; border-collapse: collapse; margin-bottom: 4px;
        }
        .print-table th {
            background: #15803d; color: #fff; padding: 11px 16px;
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
            font-weight: 600; text-align: left;
        }
        .print-table td {
            padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px;
        }
        .print-table td.highlight { font-weight: 700; color: #15803d; }

        .print-photo-area {
            display: flex; gap: 24px; align-items: flex-start;
        }
        .print-photo {
            width: 110px; height: 130px; border-radius: 8px; object-fit: cover;
            border: 2px solid #e2e8f0; background: #f8fafc; flex-shrink: 0;
        }
        .print-photo-placeholder {
            width: 110px; height: 130px; border-radius: 8px;
            background: #f1f5f9; display: flex; align-items: center;
            justify-content: center; color: #cbd5e1; font-size: 40px;
            border: 2px solid #e2e8f0; flex-shrink: 0;
        }

        .print-signatures {
            display: flex; justify-content: space-between;
            margin-top: 60px; padding-top: 20px; border-top: 1px solid #e2e8f0;
        }
        .print-sig-box { text-align: center; width: 160px; }
        .print-sig-box .line {
            border-bottom: 1px solid #334155; height: 36px; margin-bottom: 6px;
        }
        .print-sig-box .name { font-size: 11px; color: #64748b; }

        .print-footer {
            text-align: center; padding: 16px 40px; font-size: 10px;
            color: #94a3b8; border-top: 1px solid #f1f5f9;
        }

        .fee-highlight {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f0fdf4; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; color: #15803d;
            border: 1px solid #bbf7d0;
        }

        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .print-admission-form {
                box-shadow: none !important; border-radius: 0 !important;
                border-top-width: 5px; max-width: 100% !important;
            }
        }
        @media (max-width: 600px) {
            .print-header { flex-direction: column; gap: 12px; padding: 20px; }
            .print-body { padding: 20px; }
            .print-info-grid { grid-template-columns: 1fr; }
            .print-info-grid .row-label { font-weight: 700; color: #475569; }
            .print-signatures { flex-direction: column; gap: 20px; }
            .print-photo-area { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print / Save as PDF</button>
    <a href="admissions.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="student_profile.php?id=<?php echo $a['id']; ?>" class="back-btn" style="margin-left:8px"><i class="fas fa-user"></i> Profile</a>
</div>

<div class="print-admission-form">
    <div class="print-header">
        <div class="college">
            <div class="logo"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <h1>NATIONAL COLLEGE OF TECHNOLOGY</h1>
                <div class="tagline">Excellence in Education & Leadership</div>
            </div>
        </div>
        <div class="reg">
            <div class="lbl">Registration Number</div>
            <div class="val"><?php echo e($regNum); ?></div>
        </div>
    </div>

    <div class="print-body">
        <!-- Admission Info -->
        <div class="print-section-title"><i class="fas fa-file-signature"></i> Admission Information</div>
        <div class="print-photo-area" style="margin-bottom:16px">
            <?php if ($a['student_photo'] && file_exists('../uploads/students/' . $a['student_photo'])): ?>
                <img src="../uploads/students/<?php echo e($a['student_photo']); ?>" class="print-photo" alt="Student Photo">
            <?php else: ?>
                <div class="print-photo-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div style="flex:1">
                <div class="print-info-grid">
                    <div class="row-label">Sr Number</div><div class="row-value"><?php echo e($a['sr_number'] ?? '—'); ?></div>
                    <div class="row-label">Date of Admission</div><div class="row-value"><?php echo date('d M Y', strtotime($a['date_of_admission'])); ?></div>
                    <div class="row-label">Duration</div><div class="row-value"><?php echo e($a['duration']); ?></div>
                    <div class="row-label">Degree Type</div><div class="row-value"><?php echo e($a['degree_type']); ?></div>
                    <div class="row-label">Session</div><div class="row-value"><?php echo e($a['session_start']); ?> - <?php echo e($a['session_end']); ?></div>
                    <div class="row-label">Time Slot</div><div class="row-value"><?php echo e($a['time_range']); ?></div>
                    <div class="row-label">Fee Package</div><div class="row-value">
                        <?php echo e($feePkgName); ?>
                        <?php if ($feePkgTotal > 0): ?>
                            <span class="fee-highlight">Rs. <?php echo number_format($feePkgTotal); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Info -->
        <div class="print-section-title"><i class="fas fa-user"></i> Student Information</div>
        <div class="print-info-grid">
            <div class="row-label">Full Name</div><div class="row-value"><?php echo e($a['student_name']); ?></div>
            <div class="row-label">Father's Name</div><div class="row-value"><?php echo e($a['father_name']); ?></div>
            <div class="row-label">Gender</div><div class="row-value"><?php echo e($a['gender']); ?></div>
            <div class="row-label">Date of Birth</div><div class="row-value"><?php echo date('d M Y', strtotime($a['date_of_birth'])); ?></div>
            <div class="row-label">Nationality</div><div class="row-value"><?php echo e($a['nationality']); ?></div>
            <div class="row-label">CNIC</div><div class="row-value"><?php echo e($a['cnic']); ?></div>
        </div>

        <!-- Contact Info -->
        <div class="print-section-title"><i class="fas fa-address-book"></i> Contact & Address</div>
        <div class="print-info-grid">
            <div class="row-label">Mailing Address</div><div class="row-value"><?php echo e($a['mailing_address']); ?></div>
            <div class="row-label">Permanent Address</div><div class="row-value"><?php echo e($a['permanent_address']); ?></div>
            <div class="row-label">Student Mobile</div><div class="row-value"><?php echo e($a['student_mobile']); ?></div>
            <div class="row-label">Guardian Mobile</div><div class="row-value"><?php echo e($a['guardian_mobile']); ?></div>
            <div class="row-label">Student Email</div><div class="row-value"><?php echo !empty($a['student_email']) ? e($a['student_email']) : '—'; ?></div>
            <div class="row-label">Guardian Email</div><div class="row-value"><?php echo !empty($a['guardian_email']) ? e($a['guardian_email']) : '—'; ?></div>
            <div class="row-label">Occupation</div><div class="row-value"><?php echo !empty($a['occupation']) ? e($a['occupation']) : '—'; ?></div>
            <div class="row-label">Monthly Income</div><div class="row-value"><?php echo !empty($a['monthly_income']) ? 'Rs. ' . number_format($a['monthly_income']) : '—'; ?></div>
        </div>

        <!-- Course Info Table -->
        <div class="print-section-title"><i class="fas fa-book-open"></i> Course Details</div>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Programme / Course</th>
                    <th>Course Code</th>
                    <th>Duration</th>
                    <th>Time Slot</th>
                    <th>Admission Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="highlight"><?php echo e($a['course_name']); ?></td>
                    <td><?php echo e($a['course_code'] ?? '—'); ?></td>
                    <td><?php echo e($a['duration']); ?></td>
                    <td class="highlight"><?php echo e($a['time_range']); ?></td>
                    <td><?php echo date('d M Y', strtotime($a['date_of_admission'])); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="print-signatures">
            <div class="print-sig-box">
                <div class="line"></div>
                <div class="name">Student Signature</div>
            </div>
            <div class="print-sig-box">
                <div class="line"></div>
                <div class="name">Receptionist</div>
            </div>
            <div class="print-sig-box">
                <div class="line"></div>
                <div class="name">Principal</div>
            </div>
        </div>
    </div>

    <div class="print-footer">
        National College of Technology LMS &bull; Generated on <?php echo date('d M Y \a\t h:i A'); ?>
        &bull; Registration #: <?php echo e($regNum); ?>
    </div>
</div>
</body>
</html>

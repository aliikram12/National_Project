<?php
/**
 * National College LMS - Print Admission Form (A4 Optimized, Two-Column Layout)
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
            color: #0f172a;
            background: #e2e8f0;
            padding: 30px 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Action Buttons ── */
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button, .no-print a {
            padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
            gap: 8px; transition: all 0.2s; border: none; font-family: inherit;
        }
        .no-print .print-btn { background: #1e3a8a; color: #fff; }
        .no-print .print-btn:hover { background: #172554; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30,58,138,.3); }
        .no-print .back-btn { color: #64748b; margin-left: 12px; border: 1px solid #cbd5e1; background: #fff; }
        .no-print .back-btn:hover { background: #f8fafc; color: #1e293b; }

        /* ── A4 Form Container ── */
        .print-form {
            width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff;
            border-top: 6px solid #1e3a8a;
            box-shadow: 0 8px 40px rgba(0,0,0,.12);
            overflow: hidden; position: relative;
            padding: 0;
        }

        /* Watermark */
        .print-form::before {
            content: 'NATIONAL COLLEGE';
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-40deg);
            font-size: 72px; font-weight: 900;
            color: rgba(30, 58, 138, 0.03);
            z-index: 0; pointer-events: none; white-space: nowrap;
        }

        /* ── Header ── */
        .form-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 32px; border-bottom: 2px solid #1e3a8a;
            position: relative; z-index: 1; background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }
        .form-header .college { display: flex; align-items: center; gap: 14px; }
        .form-header .logo {
            width: 52px; height: 52px; border-radius: 10px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 26px; flex-shrink: 0;
        }
        .form-header h1 {
            font-size: 18px; color: #1e3a8a; font-weight: 800;
            letter-spacing: -0.01em; text-transform: uppercase; line-height: 1.2;
        }
        .form-header .tagline {
            font-size: 9px; color: #3b82f6; letter-spacing: 2px;
            margin-top: 2px; text-transform: uppercase; font-weight: 600;
        }
        .form-header .reg-box {
            text-align: right; background: #1e3a8a; color: #fff;
            padding: 10px 18px; border-radius: 8px; min-width: 160px;
        }
        .form-header .reg-box .lbl {
            font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px;
            opacity: .7; font-weight: 500;
        }
        .form-header .reg-box .val {
            font-size: 14px; font-weight: 700; letter-spacing: 1px; margin-top: 2px;
        }

        /* ── Form Title Strip ── */
        .form-title-strip {
            text-align: center; padding: 8px; background: #1e3a8a; color: #fff;
            font-size: 13px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase;
        }

        /* ── Body ── */
        .form-body {
            padding: 20px 32px 16px; position: relative; z-index: 1;
        }

        /* ── Section Title ── */
        .sec-title {
            font-size: 10px; font-weight: 700; color: #1e3a8a;
            text-transform: uppercase; letter-spacing: 1.5px;
            padding: 6px 12px; margin: 16px 0 10px 0;
            background: linear-gradient(90deg, #eff6ff, transparent);
            border-left: 3px solid #1e3a8a; border-radius: 0 4px 4px 0;
            display: flex; align-items: center; gap: 8px;
        }
        .sec-title:first-child { margin-top: 0; }
        .sec-title i { font-size: 11px; }

        /* ── Two Column Info Grid ── */
        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 0 28px; margin-bottom: 4px;
        }
        .info-row {
            display: flex; padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row .lbl {
            color: #64748b; font-size: 11px; font-weight: 500;
            min-width: 110px; flex-shrink: 0;
        }
        .info-row .val {
            font-weight: 600; font-size: 12px; color: #1e293b;
        }
        .info-row.full {
            grid-column: 1 / -1;
        }

        /* ── Photo + Admission Info Area ── */
        .admission-top {
            display: flex; gap: 20px; align-items: flex-start;
        }
        .photo-box {
            width: 100px; height: 120px; border-radius: 8px;
            border: 2px solid #e2e8f0; overflow: hidden; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc;
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; }
        .photo-box .placeholder { color: #cbd5e1; font-size: 36px; }

        /* ── Course Details Table ── */
        .course-table {
            width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 11px;
        }
        .course-table th {
            background: #1e3a8a; color: #fff; padding: 8px 10px;
            font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px;
            font-weight: 600; text-align: left;
        }
        .course-table td {
            padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px;
        }
        .course-table td.hl { font-weight: 700; color: #1e3a8a; }

        /* ── Fee Summary ── */
        .fee-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eff6ff; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; color: #1e3a8a;
            border: 1px solid #bfdbfe;
        }

        /* ── Signatures ── */
        .signatures {
            display: flex; justify-content: space-between;
            margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0;
        }
        .sig-box { text-align: center; width: 140px; }
        .sig-box .line { border-bottom: 1px solid #334155; height: 32px; margin-bottom: 4px; }
        .sig-box .name { font-size: 9px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Footer ── */
        .form-footer {
            text-align: center; padding: 10px 32px; font-size: 9px;
            color: #94a3b8; border-top: 1px solid #f1f5f9;
        }

        /* ── Declaration ── */
        .declaration {
            margin-top: 16px; padding: 10px 14px;
            background: #fefce8; border: 1px solid #fde68a; border-radius: 6px;
            font-size: 10px; color: #92400e; line-height: 1.5;
        }
        .declaration strong { color: #78350f; }

        /* ── Print Styles ── */
        @media print {
            body { padding: 0; margin: 0; background: #fff; }
            .no-print { display: none !important; }
            .print-form {
                box-shadow: none !important; width: 100% !important;
                min-height: auto; border-top-width: 4px;
            }
        }
        @media (max-width: 600px) {
            .print-form { width: 100%; min-height: auto; }
            .form-header { flex-direction: column; gap: 12px; padding: 16px; }
            .form-body { padding: 16px; }
            .info-grid { grid-template-columns: 1fr; }
            .signatures { flex-direction: column; gap: 16px; align-items: center; }
            .admission-top { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print / Save as PDF</button>
    <a href="admissions.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="student_profile.php?id=<?php echo $a['id']; ?>" class="back-btn" style="margin-left:8px"><i class="fas fa-user"></i> Profile</a>
</div>

<div class="print-form">
    <!-- Header -->
    <div class="form-header">
        <div class="college">
            <div class="logo"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <h1>National College<br>of Technology</h1>
                <div class="tagline">Excellence in Education & Leadership</div>
            </div>
        </div>
        <div class="reg-box">
            <div class="lbl">Registration Number</div>
            <div class="val"><?php echo e($regNum); ?></div>
        </div>
    </div>

    <div class="form-title-strip">Admission Form</div>

    <div class="form-body">
        <!-- ─── Admission Information + Photo ─── -->
        <div class="sec-title"><i class="fas fa-file-signature"></i> Admission Information</div>
        <div class="admission-top">
            <div class="photo-box">
                <?php if ($a['student_photo'] && file_exists('../uploads/students/' . $a['student_photo'])): ?>
                    <img src="../uploads/students/<?php echo e($a['student_photo']); ?>" alt="Student Photo">
                <?php else: ?>
                    <div class="placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>
            <div style="flex:1">
                <div class="info-grid">
                    <div class="info-row"><div class="lbl">Sr Number</div><div class="val"><?php echo e($a['sr_number'] ?? '—'); ?></div></div>
                    <div class="info-row"><div class="lbl">Date of Admission</div><div class="val"><?php echo date('d M Y', strtotime($a['date_of_admission'])); ?></div></div>
                    <div class="info-row"><div class="lbl">Duration</div><div class="val"><?php echo e($a['duration']); ?></div></div>
                    <div class="info-row"><div class="lbl">Degree Type</div><div class="val"><?php echo e($a['degree_type']); ?></div></div>
                    <div class="info-row"><div class="lbl">Session</div><div class="val"><?php echo e($a['session_start']); ?> — <?php echo e($a['session_end']); ?></div></div>
                    <div class="info-row"><div class="lbl">Time Slot</div><div class="val"><?php echo e($a['time_range']); ?></div></div>
                    <div class="info-row full"><div class="lbl">Fee Package</div><div class="val"><?php echo e($feePkgName); ?> <?php if ($feePkgTotal > 0): ?><span class="fee-badge">Rs. <?php echo number_format($feePkgTotal); ?></span><?php endif; ?></div></div>
                </div>
            </div>
        </div>

        <!-- ─── Student Information ─── -->
        <div class="sec-title"><i class="fas fa-user"></i> Student Information</div>
        <div class="info-grid">
            <div class="info-row"><div class="lbl">Full Name</div><div class="val"><?php echo e($a['student_name']); ?></div></div>
            <div class="info-row"><div class="lbl">Father's Name</div><div class="val"><?php echo e($a['father_name']); ?></div></div>
            <div class="info-row"><div class="lbl">Gender</div><div class="val"><?php echo e($a['gender']); ?></div></div>
            <div class="info-row"><div class="lbl">Date of Birth</div><div class="val"><?php echo date('d M Y', strtotime($a['date_of_birth'])); ?></div></div>
            <div class="info-row"><div class="lbl">Nationality</div><div class="val"><?php echo e($a['nationality']); ?></div></div>
            <div class="info-row"><div class="lbl">CNIC</div><div class="val"><?php echo e($a['cnic']); ?></div></div>
        </div>

        <!-- ─── Contact & Address ─── -->
        <div class="sec-title"><i class="fas fa-address-book"></i> Contact & Address</div>
        <div class="info-grid">
            <div class="info-row full"><div class="lbl">Mailing Address</div><div class="val"><?php echo e($a['mailing_address']); ?></div></div>
            <div class="info-row full"><div class="lbl">Permanent Address</div><div class="val"><?php echo e($a['permanent_address']); ?></div></div>
            <div class="info-row"><div class="lbl">Student Mobile</div><div class="val"><?php echo e($a['student_mobile']); ?></div></div>
            <div class="info-row"><div class="lbl">Guardian Mobile</div><div class="val"><?php echo e($a['guardian_mobile']); ?></div></div>
            <div class="info-row"><div class="lbl">Student Email</div><div class="val"><?php echo !empty($a['student_email']) ? e($a['student_email']) : '—'; ?></div></div>
            <div class="info-row"><div class="lbl">Guardian Email</div><div class="val"><?php echo !empty($a['guardian_email']) ? e($a['guardian_email']) : '—'; ?></div></div>
            <div class="info-row"><div class="lbl">Occupation</div><div class="val"><?php echo !empty($a['occupation']) ? e($a['occupation']) : '—'; ?></div></div>
            <div class="info-row"><div class="lbl">Monthly Income</div><div class="val"><?php echo !empty($a['monthly_income']) ? 'Rs. ' . number_format($a['monthly_income']) : '—'; ?></div></div>
        </div>

        <!-- ─── Course Details Table ─── -->
        <div class="sec-title"><i class="fas fa-book-open"></i> Course Details</div>
        <table class="course-table">
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
                    <td class="hl"><?php echo e($a['course_name']); ?></td>
                    <td><?php echo e($a['course_code'] ?? '—'); ?></td>
                    <td><?php echo e($a['duration']); ?></td>
                    <td class="hl"><?php echo e($a['time_range']); ?></td>
                    <td><?php echo date('d M Y', strtotime($a['date_of_admission'])); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- ─── Declaration ─── -->
        <div class="declaration">
            <strong>Declaration:</strong> I hereby declare that the above information is correct and complete. I agree to abide by all the rules and regulations of the institution. I understand that providing false information may lead to cancellation of my admission.
        </div>

        <!-- ─── Signatures ─── -->
        <div class="signatures">
            <div class="sig-box">
                <div class="line"></div>
                <div class="name">Student Signature</div>
            </div>
            <div class="sig-box">
                <div class="line"></div>
                <div class="name">Guardian Signature</div>
            </div>
            <div class="sig-box">
                <div class="line"></div>
                <div class="name">Receptionist</div>
            </div>
            <div class="sig-box">
                <div class="line"></div>
                <div class="name">Principal</div>
            </div>
        </div>
    </div>

    <div class="form-footer">
        National College of Technology &bull; Generated on <?php echo date('d M Y \a\t h:i A'); ?>
        &bull; Reg #: <?php echo e($regNum); ?>
        &bull; Prepared by: <?php echo e($a['created_by_name']); ?>
    </div>
</div>
</body>
</html>

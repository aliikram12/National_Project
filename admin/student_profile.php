<?php
/**
 * National College LMS - Student Profile Page
 * Accessible by: Admin & Receptionist
 */

require '../config/db.php';
$user = getCurrentUser($pdo);
if (!$user || !in_array($user['role'], ['admin', 'receptionist'])) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) { die("Invalid request."); }
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT a.*, c.name as course_name, c.code as course_code, c.duration as course_duration,
                       s.time_range, fp.name as fee_package_name, fp.total_fee, fp.discount_percent
                       FROM admissions a
                       JOIN courses c ON a.course_id = c.id
                       JOIN slots s ON a.time_slot_id = s.id
                       LEFT JOIN fee_packages fp ON a.fee_package_id = fp.id
                       WHERE a.id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) die("Admission record not found.");

$pageTitle = 'Student Profile';
?>
<?php include '../includes/dashboard_header.php'; ?>
<link rel="stylesheet" href="../assets/css/admission.css">

<div class="admission-form-wrapper">
    <!-- Profile Header Card -->
    <div class="admission-card" style="overflow:hidden">
        <div class="profile-header">
            <?php if ($a['student_photo'] && file_exists('../uploads/students/' . $a['student_photo'])): ?>
                <img src="../uploads/students/<?php echo e($a['student_photo']); ?>" class="profile-photo" alt="Student Photo">
            <?php else: ?>
                <div class="profile-photo-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div class="profile-info">
                <h2><?php echo e($a['student_name']); ?></h2>
                <div class="reg"><i class="fas fa-id-badge" style="margin-right:6px"></i><?php echo e($a['registration_number']); ?></div>
                <div style="margin-top:8px">
                    <span class="status-badge status-<?php echo e($a['status']); ?>"><?php echo ucfirst(e($a['status'])); ?></span>
                    <?php if ($a['fee_package_id']): ?>
                        <span class="fee-highlight" style="margin-left:8px"><i class="fas fa-tag"></i> <?php echo e($a['fee_package_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="profile-card">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="profile-info-grid">
                <div class="info-item"><span class="info-label">Full Name</span><span class="info-value"><?php echo e($a['student_name']); ?></span></div>
                <div class="info-item"><span class="info-label">Father's Name</span><span class="info-value"><?php echo e($a['father_name']); ?></span></div>
                <div class="info-item"><span class="info-label">Gender</span><span class="info-value"><?php echo e($a['gender']); ?></span></div>
                <div class="info-item"><span class="info-label">Date of Birth</span><span class="info-value"><?php echo formatDate($a['date_of_birth'], 'd M Y'); ?></span></div>
                <div class="info-item"><span class="info-label">Nationality</span><span class="info-value"><?php echo e($a['nationality']); ?></span></div>
                <div class="info-item"><span class="info-label">CNIC</span><span class="info-value" style="font-family:monospace;letter-spacing:0.05em"><?php echo e($a['cnic']); ?></span></div>
                <div class="info-item"><span class="info-label">Sr Number</span><span class="info-value"><?php echo e($a['sr_number'] ?? '—'); ?></span></div>
                <div class="info-item"><span class="info-label">Degree Type</span><span class="info-value"><?php echo e($a['degree_type']); ?></span></div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="profile-card">
            <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
            <div class="profile-info-grid">
                <div class="info-item"><span class="info-label">Programme / Course</span><span class="info-value"><?php echo e($a['course_name']); ?></span></div>
                <div class="info-item"><span class="info-label">Course Code</span><span class="info-value"><?php echo e($a['course_code'] ?? '—'); ?></span></div>
                <div class="info-item"><span class="info-label">Duration</span><span class="info-value"><?php echo e($a['duration']); ?></span></div>
                <div class="info-item"><span class="info-label">Time Slot</span><span class="info-value"><?php echo e($a['time_range']); ?></span></div>
                <div class="info-item"><span class="info-label">Session</span><span class="info-value"><?php echo e($a['session_start']); ?> - <?php echo e($a['session_end']); ?></span></div>
                <div class="info-item"><span class="info-label">Date of Admission</span><span class="info-value"><?php echo formatDate($a['date_of_admission'], 'd M Y'); ?></span></div>
                <div class="info-item"><span class="info-label">Registration #</span><span class="info-value" style="font-family:monospace;color:var(--admission-green);font-weight:700"><?php echo e($a['registration_number']); ?></span></div>
                <div class="info-item"><span class="info-label">Status</span><span class="info-value"><span class="status-badge status-<?php echo e($a['status']); ?>"><?php echo ucfirst(e($a['status'])); ?></span></span></div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="profile-card">
            <h3><i class="fas fa-address-book"></i> Contact Information</h3>
            <div class="profile-info-grid">
                <div class="info-item"><span class="info-label">Student Mobile</span><span class="info-value"><?php echo e($a['student_mobile']); ?></span></div>
                <div class="info-item"><span class="info-label">Guardian Mobile</span><span class="info-value"><?php echo e($a['guardian_mobile']); ?></span></div>
                <div class="info-item"><span class="info-label">Student Email</span><span class="info-value"><?php echo e($a['student_email'] ?? '—'); ?></span></div>
                <div class="info-item"><span class="info-label">Guardian Email</span><span class="info-value"><?php echo e($a['guardian_email'] ?? '—'); ?></span></div>
                <div class="info-item"><span class="info-label">Mailing Address</span><span class="info-value"><?php echo e($a['mailing_address']); ?></span></div>
                <div class="info-item"><span class="info-label">Permanent Address</span><span class="info-value"><?php echo e($a['permanent_address']); ?></span></div>
            </div>
        </div>

        <!-- Occupation & Income -->
        <div class="profile-card">
            <h3><i class="fas fa-briefcase"></i> Occupation & Income</h3>
            <div class="profile-info-grid">
                <div class="info-item"><span class="info-label">Occupation</span><span class="info-value"><?php echo e($a['occupation'] ?? '—'); ?></span></div>
                <div class="info-item"><span class="info-label">Monthly Income</span><span class="info-value"><?php echo $a['monthly_income'] ? 'Rs. ' . number_format($a['monthly_income']) : '—'; ?></span></div>
            </div>
        </div>

        <!-- Fee Information -->
        <div class="profile-card">
            <h3><i class="fas fa-rupee-sign"></i> Fee Information</h3>
            <div class="profile-info-grid">
                <div class="info-item"><span class="info-label">Fee Package</span><span class="info-value">
                    <?php echo $a['fee_package_name'] ? e($a['fee_package_name']) : '—'; ?>
                </span></div>
                <div class="info-item"><span class="info-label">Total Fee</span><span class="info-value">
                    <?php echo $a['total_fee'] ? 'Rs. ' . number_format($a['total_fee']) : '—'; ?>
                </span></div>
                <div class="info-item"><span class="info-label">Discount</span><span class="info-value">
                    <?php echo $a['discount_percent'] ? $a['discount_percent'] . '%' : '0%'; ?>
                </span></div>
                <div class="info-item"><span class="info-label">Admission Date</span><span class="info-value"><?php echo formatDate($a['date_of_admission'], 'd M Y'); ?></span></div>
                <div class="info-item"><span class="info-label">Created By</span><span class="info-value"><?php echo e($a['created_by_name']); ?></span></div>
                <div class="info-item"><span class="info-label">Created At</span><span class="info-value"><?php echo formatDate($a['created_at'], 'd M Y h:i A'); ?></span></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="padding:0 24px 24px;display:flex;gap:12px;flex-wrap:wrap">
            <a href="admission_form.php?id=<?php echo $a['id']; ?>" class="btn admission-btn-primary"><i class="fas fa-edit"></i> Edit Admission</a>
            <a href="print_admission.php?id=<?php echo $a['id']; ?>" class="btn admission-btn-outline" target="_blank"><i class="fas fa-print"></i> Print Form</a>
            <a href="admissions.php" class="btn admission-btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

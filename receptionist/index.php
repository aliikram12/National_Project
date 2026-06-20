<?php
require '../config/db.php';
requireRole('receptionist');

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$todayAdmissions = $pdo->query("SELECT COUNT(*) FROM admissions WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$weeklyAdmissions = $pdo->query("SELECT COUNT(*) FROM admissions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

$recent = $pdo->query("SELECT a.id, a.registration_number, a.student_name, c.name as course_name, sl.time_range, a.date_of_admission 
                        FROM admissions a JOIN courses c ON a.course_id=c.id JOIN slots sl ON a.time_slot_id=sl.id 
                        ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3><?php echo $totalStudents; ?></h3><p>Total Students</p></div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info"><h3><?php echo $todayAdmissions; ?></h3><p>Today's Admissions</p></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info"><h3><?php echo $weeklyAdmissions; ?></h3><p>This Week</p></div>
    </div>
    <div class="stat-card info" style="cursor:pointer" onclick="location.href='admission_form.php'">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info"><h3 style="font-size:18px">New Admission</h3><p>Click to register</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history" style="margin-right:8px;color:var(--royal)"></i> Recent Admissions</h3>
        <a href="admissions.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Reg ID</th><th>Student</th><th>Course</th><th>Slot</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
                <td style="font-family:monospace;color:var(--gray-500);font-size:13px"><?php echo e($r['registration_number']); ?></td>
                <td><strong><?php echo e($r['student_name']); ?></strong></td>
                <td><?php echo e($r['course_name']); ?></td>
                <td><span class="badge badge-info" style="font-size:11px"><i class="far fa-clock"></i> <?php echo e($r['time_range']); ?></span></td>
                <td style="font-size:13px;color:var(--gray-500)"><?php echo formatDate($r['date_of_admission']); ?></td>
                <td>
                    <a href="print_admission.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-print"></i> Print</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recent)): ?><tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No admissions yet.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

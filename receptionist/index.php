<?php
require '../config/db.php';
requireRole('receptionist');

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$todayAdmissions = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE DATE(enrollment_date)=CURDATE()")->fetchColumn();
$weeklyAdmissions = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

$recent = $pdo->query("SELECT s.id as student_id, s.name, c.name as course_name, sl.time_range, e.enrollment_date 
                        FROM enrollments e JOIN students s ON e.student_id=s.id JOIN courses c ON e.course_id=c.id JOIN slots sl ON e.slot_id=sl.id 
                        ORDER BY e.enrollment_date DESC LIMIT 10")->fetchAll();
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
    <div class="stat-card info" style="cursor:pointer" onclick="location.href='admissions.php'">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info"><h3 style="font-size:18px">New Admission</h3><p>Click to register</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history" style="margin-right:8px;color:var(--royal)"></i> Recent Admissions</h3>
        <a href="students.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Reg ID</th><th>Student</th><th>Course</th><th>Slot</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
                <td style="font-family:monospace;color:var(--gray-500);font-size:13px">NC-<?php echo str_pad($r['student_id'],4,'0',STR_PAD_LEFT); ?></td>
                <td><strong><?php echo e($r['name']); ?></strong></td>
                <td><?php echo e($r['course_name']); ?></td>
                <td><span class="badge badge-info" style="font-size:11px"><i class="far fa-clock"></i> <?php echo e($r['time_range']); ?></span></td>
                <td style="font-size:13px;color:var(--gray-500)"><?php echo formatDate($r['enrollment_date']); ?></td>
                <td><a href="print_admission.php?id=<?php echo $r['student_id']; ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-print"></i> Print</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recent)): ?><tr><td colspan="6"><div class="empty-state"><i class="fas fa-inbox"></i><p>No admissions yet.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

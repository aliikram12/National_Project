<?php
require '../config/db.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];
// Get all courses assigned to this teacher, and for each course find active slots from admissions
$stmt = $pdo->prepare("SELECT c.name as course_name, c.id as course_id, s.time_range, s.id as slot_id,
                       (SELECT COUNT(*) FROM admissions a WHERE a.course_id=c.id AND a.time_slot_id=s.id AND a.status='active') as student_count
                       FROM course_teachers ct 
                       JOIN courses c ON ct.course_id=c.id 
                       JOIN admissions a ON a.course_id=c.id
                       JOIN slots s ON a.time_slot_id=s.id 
                       WHERE ct.teacher_id=? AND a.status='active'
                       GROUP BY c.id, s.id");
$stmt->execute([$teacher_id]);
$classes = $stmt->fetchAll();
$totalStudents = array_sum(array_column($classes, 'student_count'));
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-chalkboard"></i></div>
        <div class="stat-info"><h3><?php echo count($classes); ?></h3><p>Assigned Classes</p></div>
    </div>
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info"><h3><?php echo $totalStudents; ?></h3><p>Total Students</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-list" style="margin-right:8px;color:var(--royal)"></i> My Class Schedule</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Course</th><th>Time Slot</th><th>Students</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($classes as $c): ?>
            <tr>
                <td><strong style="font-size:15px"><?php echo e($c['course_name']); ?></strong></td>
                <td><span class="badge badge-info" style="font-size:13px;padding:8px 14px"><i class="far fa-clock"></i> <?php echo e($c['time_range']); ?></span></td>
                <td><span style="font-weight:700;font-size:16px;color:var(--gray-700)"><?php echo $c['student_count']; ?></span></td>
                <td>
                    <div style="display:flex;gap:8px">
                        <a href="attendance.php?course_id=<?php echo $c['course_id']; ?>&slot_id=<?php echo $c['slot_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-check-square"></i> Attendance</a>
                        <a href="assessments.php?course_id=<?php echo $c['course_id']; ?>&slot_id=<?php echo $c['slot_id']; ?>" class="btn btn-sm" style="background:var(--cyan);color:#fff"><i class="fas fa-star"></i> Assessment</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($classes)): ?>
            <tr><td colspan="4"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No courses assigned. Contact the administrator.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

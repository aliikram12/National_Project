<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('../login.php');
}

$teacher_id = $_SESSION['user_id'];

// Get courses assigned to this teacher
$stmt = $pdo->prepare("SELECT c.name as course_name, c.id as course_id, s.time_range, s.id as slot_id,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.slot_id = s.id) as student_count
                       FROM course_teachers ct 
                       JOIN courses c ON ct.course_id = c.id 
                       JOIN slots s ON ct.slot_id = s.id 
                       WHERE ct.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_courses = $stmt->fetchAll();

// Calculate total students handled by teacher
$totalStudents = 0;
foreach($assigned_courses as $ac) {
    $totalStudents += $ac['student_count'];
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card info">
        <div class="icon"><i class="fas fa-chalkboard"></i></div>
        <div class="info">
            <h3 style="color: var(--accent-color);"><?php echo count($assigned_courses); ?></h3>
            <p>Assigned Classes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-user-graduate"></i></div>
        <div class="info">
            <h3 style="color: var(--primary-color);"><?php echo $totalStudents; ?></h3>
            <p>Total Students</p>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 20px; font-size: 18px; color: var(--primary-color);"><i class="fas fa-list-ul" style="margin-right: 10px;"></i> My Class Schedule & Management</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Time Slot</th>
                    <th>Enrolled Students</th>
                    <th>Quick Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_courses as $ac): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-color); font-size: 15px;">
                                <?php echo htmlspecialchars($ac['course_name']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size: 13px; padding: 8px 12px;">
                                <i class="far fa-clock"></i> <?php echo htmlspecialchars($ac['time_range']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--light-text); font-size: 16px;">
                                <?php echo $ac['student_count']; ?> Students
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="attendance.php?course_id=<?php echo $ac['course_id']; ?>&slot_id=<?php echo $ac['slot_id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px; box-shadow: none;">
                                    <i class="fas fa-check-square"></i> Mark Attendance
                                </a>
                                <a href="assessments.php?course_id=<?php echo $ac['course_id']; ?>&slot_id=<?php echo $ac['slot_id']; ?>" class="btn btn-accent" style="padding: 8px 15px; font-size: 13px; box-shadow: none;">
                                    <i class="fas fa-star"></i> Assessment
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assigned_courses)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
                            <i class="fas fa-folder-open" style="font-size: 48px; color: #E2E8F0; margin-bottom: 15px; display: block;"></i>
                            <div style="color: var(--light-text); font-size: 16px;">No courses assigned yet. Please contact the administrator.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

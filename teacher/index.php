<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('../login.php');
}

$teacher_id = $_SESSION['user_id'];

// Get courses assigned to this teacher
$stmt = $pdo->prepare("SELECT c.name as course_name, c.id as course_id, s.time_range, s.id as slot_id 
                       FROM course_teachers ct 
                       JOIN courses c ON ct.course_id = c.id 
                       JOIN slots s ON ct.slot_id = s.id 
                       WHERE ct.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_courses = $stmt->fetchAll();

?>
<?php include '../includes/dashboard_header.php'; ?>

<h2>Teacher Dashboard</h2>
<p style="color: var(--light-text); margin-bottom: 20px;">Welcome to the teacher panel. Manage your classes and students.</p>

<div class="card">
    <h3>My Assigned Courses & Slots</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Time Slot</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_courses as $ac): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ac['course_name']); ?></strong></td>
                        <td><span class="badge badge-success"><?php echo htmlspecialchars($ac['time_range']); ?></span></td>
                        <td>
                            <a href="attendance.php?course_id=<?php echo $ac['course_id']; ?>&slot_id=<?php echo $ac['slot_id']; ?>" class="btn btn-primary" style="padding: 5px 15px; font-size: 12px;">Mark Attendance</a>
                            <a href="assessments.php?course_id=<?php echo $ac['course_id']; ?>&slot_id=<?php echo $ac['slot_id']; ?>" class="btn btn-accent" style="padding: 5px 15px; font-size: 12px;">Assessments</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assigned_courses)): ?>
                    <tr>
                        <td colspan="3" class="text-center">No courses assigned yet. Please contact the administrator.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

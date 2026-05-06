<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

// Receptionist Analytics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$recentAdmissions = $pdo->query("SELECT s.name, c.name as course_name, sl.time_range, e.enrollment_date 
                                 FROM enrollments e 
                                 JOIN students s ON e.student_id = s.id 
                                 JOIN courses c ON e.course_id = c.id 
                                 JOIN slots sl ON e.slot_id = sl.id 
                                 ORDER BY e.enrollment_date DESC LIMIT 5")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<h2>Receptionist Dashboard</h2>
<p style="color: var(--light-text); margin-bottom: 20px;">Welcome to the admission management panel.</p>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="info">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Total Registered Students</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color: var(--accent-color);">
        <div class="icon" style="color: var(--accent-color);"><i class="fas fa-user-plus"></i></div>
        <div class="info">
            <h3>+</h3>
            <p><a href="admissions.php" style="color: inherit;">New Admission</a></p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Recent Admissions</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Slot</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAdmissions as $adm): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($adm['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($adm['course_name']); ?></td>
                        <td><span class="badge badge-success"><?php echo htmlspecialchars($adm['time_range']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($adm['enrollment_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentAdmissions)): ?>
                    <tr><td colspan="4" class="text-center">No recent admissions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

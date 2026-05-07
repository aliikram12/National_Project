<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

// Receptionist Analytics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$todayAdmissions = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE DATE(enrollment_date) = CURDATE()")->fetchColumn();

$recentAdmissions = $pdo->query("SELECT s.id as student_id, s.name, c.name as course_name, sl.time_range, e.enrollment_date 
                                 FROM enrollments e 
                                 JOIN students s ON e.student_id = s.id 
                                 JOIN courses c ON e.course_id = c.id 
                                 JOIN slots sl ON e.slot_id = sl.id 
                                 ORDER BY e.enrollment_date DESC LIMIT 8")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="info">
            <h3 style="color: var(--primary-color);"><?php echo $totalStudents; ?></h3>
            <p>Total Registered Students</p>
        </div>
    </div>
    <div class="stat-card success">
        <div class="icon"><i class="fas fa-calendar-day"></i></div>
        <div class="info">
            <h3 style="color: #38A169;"><?php echo $todayAdmissions; ?></h3>
            <p>Admissions Today</p>
        </div>
    </div>
    <div class="stat-card info" style="cursor: pointer; transition: transform 0.3s;" onclick="window.location.href='admissions.php'">
        <div class="icon"><i class="fas fa-user-plus"></i></div>
        <div class="info">
            <h3 style="color: var(--accent-color); font-size: 20px; line-height: 28px;">New Admission</h3>
            <p>Click to register student</p>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 18px; color: var(--primary-color); margin: 0;"><i class="fas fa-history" style="margin-right: 10px;"></i> Recent Admissions Log</h3>
        <a href="students.php" class="btn btn-primary" style="padding: 6px 15px; font-size: 12px; box-shadow: none;">View All Students</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Reg ID</th>
                    <th>Student Name</th>
                    <th>Course Enrolled</th>
                    <th>Allocated Slot</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAdmissions as $adm): ?>
                    <tr>
                        <td><span style="color: var(--light-text); font-family: monospace; font-size: 13px;">NC-<?php echo str_pad($adm['student_id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-color); font-size: 14px;">
                                <?php echo htmlspecialchars($adm['name']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($adm['course_name']); ?></td>
                        <td><span class="badge badge-success" style="font-size: 11px;"><i class="far fa-clock" style="margin-right:4px;"></i><?php echo htmlspecialchars($adm['time_range']); ?></span></td>
                        <td><span style="color: var(--light-text); font-size: 13px;"><?php echo date('M d, Y', strtotime($adm['enrollment_date'])); ?></span></td>
                        <td>
                            <a href="print_admission.php?id=<?php echo $adm['student_id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: #EBF8FF; color: var(--primary-color); box-shadow: none;" target="_blank">
                                <i class="fas fa-print"></i> Print PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentAdmissions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #E2E8F0; margin-bottom: 15px; display: block;"></i>
                            <div style="color: var(--light-text); font-size: 16px;">No recent admissions found.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

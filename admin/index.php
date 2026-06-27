<?php
require '../config/db.php';
requireRole('admin');

// Analytics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$struckOff = $totalStudents - $activeStudents;
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status='active'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND status='active'")->fetchColumn();
$weeklyAdmissions = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$monthlyAdmissions = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

// Chart: Students per course
$courseChart = $pdo->query("SELECT c.name, COUNT(e.student_id) as cnt FROM courses c LEFT JOIN enrollments e ON c.id=e.course_id GROUP BY c.id ORDER BY cnt DESC LIMIT 10")->fetchAll();
$cNames = array_column($courseChart, 'name');
$cCounts = array_column($courseChart, 'cnt');

// Chart: Students per slot
$slotChart = $pdo->query("SELECT s.time_range, COUNT(e.student_id) as cnt FROM slots s LEFT JOIN enrollments e ON s.id=e.slot_id GROUP BY s.id")->fetchAll();
$sNames = array_column($slotChart, 'time_range');
$sCounts = array_column($slotChart, 'cnt');

// Recent admissions
$recent = $pdo->query("SELECT s.name as student_name, c.name as course_name, e.enrollment_date FROM enrollments e JOIN students s ON e.student_id=s.id JOIN courses c ON e.course_id=c.id ORDER BY e.enrollment_date DESC LIMIT 6")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3><?php echo $totalStudents; ?></h3><p>Total Students</p></div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
        <div class="stat-info"><h3><?php echo $activeStudents; ?></h3><p>Active Students</p></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="fas fa-user-times"></i></div>
        <div class="stat-info"><h3><?php echo $struckOff; ?></h3><p>Struck Off</p></div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-info"><h3><?php echo $totalCourses; ?></h3><p>Active Courses</p></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info"><h3><?php echo $totalTeachers; ?></h3><p>Teachers</p></div>
    </div>
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info"><h3><?php echo $weeklyAdmissions; ?></h3><p>This Week</p></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar" style="margin-right:8px;color:var(--royal)"></i> Enrollment by Course</h3></div>
        <div style="height:280px;position:relative"><canvas id="courseChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-history" style="margin-right:8px;color:var(--royal)"></i> Recent Admissions</h3></div>
        <?php foreach ($recent as $r): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--gray-100)">
            <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;color:var(--royal);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0"><i class="fas fa-user-graduate"></i></div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:14px;color:var(--gray-800)"><?php echo e($r['student_name']); ?></div>
                <div style="font-size:12px;color:var(--gray-500)"><?php echo e($r['course_name']); ?></div>
            </div>
            <div style="font-size:11px;color:var(--gray-400)"><?php echo formatDate($r['enrollment_date'], 'M d'); ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($recent)): ?><div class="empty-state"><p>No admissions yet.</p></div><?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-clock" style="margin-right:8px;color:var(--cyan)"></i> By Time Slot</h3></div>
        <div style="height:220px;position:relative"><canvas id="slotChart"></canvas></div>
    </div>
    <div class="quick-action-card">
        <h3><i class="fas fa-bolt" style="margin-right:8px"></i> Quick Actions</h3>
        <div class="quick-action-grid">
            <a href="users.php"><i class="fas fa-user-plus"></i> Add New User</a>
            <a href="courses.php"><i class="fas fa-book"></i> Manage Courses</a>
            <a href="students.php"><i class="fas fa-user-graduate"></i> View Students</a>
            <a href="reports.php"><i class="fas fa-download"></i> Download Reports</a>
        </div>
    </div>
</div>

<?php 
// Fetch recent logins for dashboard
$recentLogins = [];
try {
    $recentLogins = $pdo->query("SELECT lh.*, u.name as user_name, u.role as user_role 
                                 FROM login_history lh 
                                 JOIN users u ON lh.user_id = u.id 
                                 ORDER BY lh.login_time DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {}
?>
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-history" style="margin-right:8px;color:var(--royal)"></i> Recent User Logins</h3>
        <a href="login_history.php" class="btn btn-sm btn-outline-primary" style="font-size:12px;padding:4px 10px;">View Full History</a>
    </div>
    <div class="table-responsive">
        <table style="margin-bottom:0;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Login Time</th>
                    <th>Status / Duration</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogins as $row): 
                    $loginTime = new DateTime($row['login_time']);
                    $logoutTime = $row['logout_time'] ? new DateTime($row['logout_time']) : null;
                    
                    $durationStr = '—';
                    if ($logoutTime) {
                        $diff = $loginTime->diff($logoutTime);
                        $durationParts = [];
                        if ($diff->h > 0) $durationParts[] = $diff->h . 'h';
                        if ($diff->i > 0) $durationParts[] = $diff->i . 'm';
                        $durationStr = 'Logged out (' . (!empty($durationParts) ? implode(' ', $durationParts) : '< 1m') . ')';
                    } elseif ($loginTime->diff(new DateTime())->h < 12) {
                        $durationStr = '<span class="text-success" style="font-weight:600;"><i class="fas fa-circle" style="font-size:8px;animation:pulse 2s infinite"></i> Active Now</span>';
                    } else {
                        $durationStr = '<span class="text-warning">Session Expired</span>';
                    }
                ?>
                <tr>
                    <td><strong><?php echo e($row['user_name']); ?></strong></td>
                    <td><span style="text-transform:capitalize;font-size:11px;" class="badge badge-<?php echo $row['user_role'] === 'admin' ? 'primary' : ($row['user_role'] === 'teacher' ? 'success' : 'warning'); ?>"><?php echo e($row['user_role']); ?></span></td>
                    <td style="font-size:13px"><?php echo $loginTime->format('M d, h:i A'); ?></td>
                    <td style="font-size:13px;"><?php echo $durationStr; ?></td>
                    <td style="font-size:12px; color:var(--gray-500); font-family:monospace;"><?php echo e($row['ip_address'] ?: 'Unknown'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentLogins)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding:20px;color:var(--gray-500)">No recent logins found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}
</style>

<script>
Chart.defaults.font.family="'Inter',sans-serif";
Chart.defaults.color='#64748b';
new Chart(document.getElementById('courseChart'),{type:'bar',data:{labels:<?php echo json_encode($cNames);?>,datasets:[{label:'Students',data:<?php echo json_encode($cCounts);?>,backgroundColor:'rgba(26,86,219,0.8)',borderRadius:6,barThickness:28}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false},ticks:{maxRotation:45}}}}});
new Chart(document.getElementById('slotChart'),{type:'doughnut',data:{labels:<?php echo json_encode($sNames);?>,datasets:[{data:<?php echo json_encode($sCounts);?>,backgroundColor:['#1a56db','#06b6d4','#10b981','#f59e0b'],borderWidth:0,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,usePointStyle:true,padding:12}}}}});
</script>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

// Advanced Analytics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$struckOffStudents = $totalStudents - $activeStudents;
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

// Chart Data
$coursesQuery = $pdo->query("SELECT c.name, COUNT(e.student_id) as count FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id");
$courseNames = [];
$courseCounts = [];
while($row = $coursesQuery->fetch()) {
    $courseNames[] = $row['name'];
    $courseCounts[] = $row['count'];
}

$slotsQuery = $pdo->query("SELECT s.time_range, COUNT(e.student_id) as count FROM slots s LEFT JOIN enrollments e ON s.id = e.slot_id GROUP BY s.id");
$slotNames = [];
$slotCounts = [];
while($row = $slotsQuery->fetch()) {
    $slotNames[] = $row['time_range'];
    $slotCounts[] = $row['count'];
}

// Recent Activities
$recentActivities = $pdo->query("
    SELECT s.name as student_name, c.name as course_name, e.enrollment_date 
    FROM enrollments e 
    JOIN students s ON e.student_id = s.id 
    JOIN courses c ON e.course_id = c.id 
    ORDER BY e.enrollment_date DESC LIMIT 5
")->fetchAll();

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="info">
            <h3 style="color: var(--primary-color);"><?php echo $totalStudents; ?></h3>
            <p>Total Enrollments</p>
        </div>
    </div>
    <div class="stat-card success">
        <div class="icon"><i class="fas fa-user-check"></i></div>
        <div class="info">
            <h3 style="color: #38A169;"><?php echo $activeStudents; ?></h3>
            <p>Active Students</p>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="icon"><i class="fas fa-user-times"></i></div>
        <div class="info">
            <h3 style="color: #E53E3E;"><?php echo $struckOffStudents; ?></h3>
            <p>Struck Off</p>
        </div>
    </div>
    <div class="stat-card info">
        <div class="icon"><i class="fas fa-book-open"></i></div>
        <div class="info">
            <h3 style="color: var(--accent-color);"><?php echo $totalCourses; ?></h3>
            <p>Active Courses</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Main Chart -->
    <div class="card" style="display: flex; flex-direction: column;">
        <h3 style="margin-bottom: 20px; font-size: 16px; color: var(--primary-color);">Student Enrollment by Course</h3>
        <div style="flex: 1; min-height: 250px; position: relative;">
            <canvas id="courseChart"></canvas>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <h3 style="margin-bottom: 20px; font-size: 16px; color: var(--primary-color);">Recent Admissions</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($recentActivities as $activity): ?>
                <div style="display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #EBF8FF; color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px; color: var(--text-color);"><?php echo htmlspecialchars($activity['student_name']); ?></div>
                        <div style="font-size: 12px; color: var(--light-text);">Joined <?php echo htmlspecialchars($activity['course_name']); ?></div>
                    </div>
                    <div style="margin-left: auto; font-size: 11px; color: var(--light-text);">
                        <?php echo date('M d', strtotime($activity['enrollment_date'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($recentActivities)): ?>
                <p style="color: var(--light-text); font-size: 14px;">No recent activities found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-top: 24px;">
    <div class="card">
        <h3 style="margin-bottom: 20px; font-size: 16px; color: var(--primary-color);">Distribution by Time Slot</h3>
        <div style="position: relative; height: 220px;">
            <canvas id="slotChart"></canvas>
        </div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-light)); color: white;">
        <h3 style="margin-bottom: 20px; font-size: 18px; color: white;">System Configuration Quick Actions</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <a href="users.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; justify-content: flex-start; padding: 15px;">
                <i class="fas fa-user-plus" style="margin-right: 10px;"></i> Add New User
            </a>
            <a href="courses.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; justify-content: flex-start; padding: 15px;">
                <i class="fas fa-book" style="margin-right: 10px;"></i> Manage Courses
            </a>
            <a href="pdf_templates.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; justify-content: flex-start; padding: 15px;">
                <i class="fas fa-file-pdf" style="margin-right: 10px;"></i> Edit PDF Template
            </a>
            <a href="reports.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; justify-content: flex-start; padding: 15px;">
                <i class="fas fa-download" style="margin-right: 10px;"></i> Download Reports
            </a>
        </div>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#718096';

    const courseCtx = document.getElementById('courseChart').getContext('2d');
    new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($courseNames); ?>,
            datasets: [{
                label: 'Enrolled Students',
                data: <?php echo json_encode($courseCounts); ?>,
                backgroundColor: 'rgba(0, 210, 211, 0.8)',
                borderColor: '#00D2D3',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#EDF2F7' } },
                x: { grid: { display: false } }
            }
        }
    });

    const slotCtx = document.getElementById('slotChart').getContext('2d');
    new Chart(slotCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($slotNames); ?>,
            datasets: [{
                data: <?php echo json_encode($slotCounts); ?>,
                backgroundColor: ['#0A2540', '#00D2D3', '#48BB78', '#F56565'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true } }
            },
            cutout: '70%'
        }
    });
</script>

<?php include '../includes/dashboard_footer.php'; ?>

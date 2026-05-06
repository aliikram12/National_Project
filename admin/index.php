<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

// Analytics Queries
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$struckOffStudents = $totalStudents - $activeStudents;

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

?>
<?php include '../includes/dashboard_header.php'; ?>

<h2>Admin Dashboard</h2>
<p style="color: var(--light-text); margin-bottom: 20px;">System overview and analytics.</p>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="info">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Total Students</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color: #28a745;">
        <div class="icon" style="color: #28a745;"><i class="fas fa-user-check"></i></div>
        <div class="info">
            <h3><?php echo $activeStudents; ?></h3>
            <p>Active Students</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color: #dc3545;">
        <div class="icon" style="color: #dc3545;"><i class="fas fa-user-times"></i></div>
        <div class="info">
            <h3><?php echo $struckOffStudents; ?></h3>
            <p>Struck Off</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <h3>Students per Course</h3>
        <canvas id="courseChart" height="200"></canvas>
    </div>
    <div class="card">
        <h3>Students per Slot</h3>
        <canvas id="slotChart" height="200"></canvas>
    </div>
</div>

<script>
    const courseCtx = document.getElementById('courseChart').getContext('2d');
    new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($courseNames); ?>,
            datasets: [{
                label: 'Students',
                data: <?php echo json_encode($courseCounts); ?>,
                backgroundColor: 'rgba(10, 37, 64, 0.7)',
                borderColor: 'rgba(10, 37, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } }
        }
    });

    const slotCtx = document.getElementById('slotChart').getContext('2d');
    new Chart(slotCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($slotNames); ?>,
            datasets: [{
                data: <?php echo json_encode($slotCounts); ?>,
                backgroundColor: ['#0A2540', '#FFC107', '#28a745', '#dc3545'],
            }]
        }
    });
</script>

<?php include '../includes/dashboard_footer.php'; ?>

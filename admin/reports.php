<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Contact', 'Status', 'Course', 'Time Slot', 'Enrollment Date']);
    
    $query = $pdo->query("SELECT s.id, s.name, s.contact, s.status, c.name as course_name, sl.time_range, e.enrollment_date 
                          FROM students s 
                          JOIN enrollments e ON s.id = e.student_id 
                          JOIN courses c ON e.course_id = c.id 
                          JOIN slots sl ON e.slot_id = sl.id");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Reports & Exports</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Generate and download system reports.</p>

    <div style="display: flex; gap: 20px;">
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
            <i class="fas fa-file-csv fa-3x" style="color: #28a745; margin-bottom: 15px;"></i>
            <h4>All Students CSV</h4>
            <p style="color: var(--light-text); font-size: 14px;">Download a complete list of all students and their enrollment details.</p>
            <a href="?export=csv" class="btn btn-primary mt-2">Download CSV</a>
        </div>
        
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
            <i class="fas fa-file-pdf fa-3x" style="color: #dc3545; margin-bottom: 15px;"></i>
            <h4>Students List PDF</h4>
            <p style="color: var(--light-text); font-size: 14px;">Print a complete list of students directly from the browser.</p>
            <button onclick="window.print()" class="btn btn-primary mt-2">Print to PDF</button>
        </div>
    </div>
</div>

<div class="card no-print">
    <h3>Assign Teacher to Course</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Assign a teacher to a specific course and time slot.</p>
    <?php
        $teachers = $pdo->query("SELECT id, name FROM users WHERE role='teacher'")->fetchAll();
        $courses = $pdo->query("SELECT id, name FROM courses")->fetchAll();
        $slots = $pdo->query("SELECT id, time_range FROM slots")->fetchAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_teacher') {
            if (verifyCsrfToken($_POST['csrf_token'])) {
                $t_id = $_POST['teacher_id'];
                $c_id = $_POST['course_id'];
                $s_id = $_POST['slot_id'];
                $stmt = $pdo->prepare("INSERT INTO course_teachers (course_id, teacher_id, slot_id) VALUES (?, ?, ?)");
                if ($stmt->execute([$c_id, $t_id, $s_id])) {
                    echo '<div class="alert alert-success">Teacher assigned successfully.</div>';
                }
            }
        }
    ?>
    <form method="POST" action="">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="assign_teacher">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Teacher</label>
                <select name="teacher_id" class="form-control" required>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Course</label>
                <select name="course_id" class="form-control" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Slot</label>
                <select name="slot_id" class="form-control" required>
                    <?php foreach ($slots as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['time_range']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 46px;">Assign</button>
        </div>
    </form>
</div>

<!-- Print Only Area -->
<style>
    .print-only { display: none; }
    @media print {
        body { background: white; }
        .sidebar, .topbar, .no-print, .card:not(.print-only) { display: none !important; }
        .print-only { display: block; width: 100%; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    }
</style>
<div class="print-only">
    <h2>National College - All Students Report</h2>
    <p>Generated on: <?php echo date('d M Y'); ?></p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Course</th>
                <th>Slot</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = $pdo->query("SELECT s.name, s.contact, s.status, c.name as course_name, sl.time_range 
                                  FROM students s 
                                  JOIN enrollments e ON s.id = e.student_id 
                                  JOIN courses c ON e.course_id = c.id 
                                  JOIN slots sl ON e.slot_id = sl.id");
            while ($row = $query->fetch()):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td><?php echo htmlspecialchars($row['time_range']); ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

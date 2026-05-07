<?php
require '../config/db.php';
requireRole('admin');

$reportType = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$courseId = $_GET['course_id'] ?? '';
$slotId = $_GET['slot_id'] ?? '';

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $type = $_GET['csv_type'] ?? 'students';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $type . '_report_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    
    if ($type === 'students') {
        fputcsv($out, ['ID','Name','Father','Contact','Course','Slot','Status','Enrolled']);
        $q = $pdo->query("SELECT s.id,s.name,s.father_name,s.contact,c.name as course,sl.time_range,s.status,e.enrollment_date FROM students s LEFT JOIN enrollments e ON s.id=e.student_id LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN slots sl ON e.slot_id=sl.id");
        while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    } elseif ($type === 'attendance') {
        fputcsv($out, ['Student','Course','Date','Status']);
        $q = $pdo->query("SELECT s.name,c.name,a.date,a.status FROM attendance a JOIN students s ON a.student_id=s.id JOIN courses c ON a.course_id=c.id ORDER BY a.date DESC");
        while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    } elseif ($type === 'assessments') {
        fputcsv($out, ['Student','Teacher','Course','Date','Type','Notes','Grade']);
        $q = $pdo->query("SELECT s.name,u.name,c.name,a.date,a.assessment_type,a.notes,a.grade FROM assessments a JOIN students s ON a.student_id=s.id JOIN users u ON a.teacher_id=u.id JOIN courses c ON a.course_id=c.id ORDER BY a.date DESC");
        while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    }
    fclose($out); exit;
}

$courses = $pdo->query("SELECT id,name FROM courses")->fetchAll();
$slots = $pdo->query("SELECT id,time_range FROM slots")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="grid-3 mb-3">
    <div class="report-card">
        <i class="fas fa-users" style="color:var(--royal)"></i>
        <h4>Students Report</h4>
        <p>Export complete student list with enrollment details.</p>
        <a href="?export=csv&csv_type=students" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> CSV</a>
        <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="report-card">
        <i class="fas fa-calendar-check" style="color:var(--green)"></i>
        <h4>Attendance Report</h4>
        <p>Export attendance records with date and status filters.</p>
        <a href="?export=csv&csv_type=attendance" class="btn btn-success btn-sm"><i class="fas fa-download"></i> CSV</a>
    </div>
    <div class="report-card">
        <i class="fas fa-clipboard-list" style="color:var(--orange)"></i>
        <h4>Assessment Report</h4>
        <p>Export assessment notes, grades and progress records.</p>
        <a href="?export=csv&csv_type=assessments" class="btn btn-warning btn-sm"><i class="fas fa-download"></i> CSV</a>
    </div>
</div>

<!-- Printable Report -->
<style>
@media print{.no-print{display:none!important}.print-only{display:block!important}.main-content{margin-left:0!important}}
</style>
<div class="print-only">
    <div style="text-align:center;margin-bottom:30px;border-bottom:3px solid #0A1628;padding-bottom:20px">
        <h1 style="margin:0;font-size:28px;color:#0A1628">NATIONAL COLLEGE</h1>
        <p style="margin:4px 0;color:#666;font-size:13px;letter-spacing:1px">EXCELLENCE IN EDUCATION & LEADERSHIP</p>
        <h2 style="margin:12px 0 4px;font-size:18px;color:#333">Students Report</h2>
        <p style="color:#888;font-size:12px">Generated: <?php echo date('d M Y, h:i A'); ?></p>
    </div>
    <table>
        <thead><tr><th>Reg ID</th><th>Name</th><th>Father</th><th>Contact</th><th>Course</th><th>Slot</th><th>Status</th></tr></thead>
        <tbody>
        <?php
        $pq = $pdo->query("SELECT s.*,c.name as course,sl.time_range FROM students s LEFT JOIN enrollments e ON s.id=e.student_id LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN slots sl ON e.slot_id=sl.id ORDER BY s.id");
        while($r=$pq->fetch()):
        ?>
        <tr>
            <td>NC-<?php echo str_pad($r['id'],4,'0',STR_PAD_LEFT); ?></td>
            <td><?php echo e($r['name']); ?></td>
            <td><?php echo e($r['father_name']); ?></td>
            <td><?php echo e($r['contact']); ?></td>
            <td><?php echo e($r['course'] ?? '—'); ?></td>
            <td><?php echo e($r['time_range'] ?? '—'); ?></td>
            <td><?php echo ucfirst($r['status']); ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';
requireRole('receptionist');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $type = $_GET['csv_type'] ?? 'admissions';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$type.'_report_'.date('Y-m-d').'.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Contact','Course','Slot','Enrollment Date','Status']);
    $q = $pdo->query("SELECT s.id,s.name,s.contact,c.name,sl.time_range,e.enrollment_date,s.status FROM students s JOIN enrollments e ON s.id=e.student_id JOIN courses c ON e.course_id=c.id JOIN slots sl ON e.slot_id=sl.id ORDER BY e.enrollment_date DESC");
    while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    fclose($out); exit;
}
$courses = $pdo->query("SELECT id,name FROM courses")->fetchAll();
$slots = $pdo->query("SELECT id,time_range FROM slots")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="grid-2 mb-3">
    <div class="report-card">
        <i class="fas fa-file-csv" style="color:var(--green)"></i>
        <h4>Admissions CSV</h4>
        <p>Download all admissions with course and slot details.</p>
        <a href="?export=csv&csv_type=admissions" class="btn btn-success btn-sm"><i class="fas fa-download"></i> Download</a>
    </div>
    <div class="report-card">
        <i class="fas fa-print" style="color:var(--royal)"></i>
        <h4>Print Admissions List</h4>
        <p>Print a formatted list for office records.</p>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<style>@media print{.no-print{display:none!important}.print-only{display:block!important}.main-content{margin-left:0!important}}</style>
<div class="print-only print-card">
    <div style="text-align:center;margin-bottom:24px;border-bottom:3px solid #0A1628;padding-bottom:16px">
        <h1 style="margin:0;font-size:24px;color:#0A1628">NATIONAL COLLEGE</h1>
        <p style="margin:4px 0;color:#666;font-size:12px;letter-spacing:1px">ADMISSIONS REPORT</p>
        <p style="color:#888;font-size:11px">Generated: <?php echo date('d M Y, h:i A'); ?></p>
    </div>
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>Course</th><th>Slot</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php $pq = $pdo->query("SELECT s.name,s.contact,c.name as course,sl.time_range,e.enrollment_date,s.status FROM students s JOIN enrollments e ON s.id=e.student_id JOIN courses c ON e.course_id=c.id JOIN slots sl ON e.slot_id=sl.id ORDER BY e.enrollment_date DESC");
        while($r=$pq->fetch()): ?>
        <tr>
            <td><?php echo e($r['name']); ?></td><td><?php echo e($r['contact']); ?></td>
            <td><?php echo e($r['course']); ?></td><td><?php echo e($r['time_range']); ?></td>
            <td><?php echo formatDate($r['enrollment_date']); ?></td><td><?php echo ucfirst($r['status']); ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

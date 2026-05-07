<?php
require '../config/db.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// CSV Export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=teacher_'.$type.'_'.date('Y-m-d').'.csv');
    $out = fopen('php://output', 'w');
    
    if ($type === 'attendance') {
        fputcsv($out, ['Student','Course','Date','Status']);
        $q = $pdo->prepare("SELECT s.name,c.name,a.date,a.status FROM attendance a JOIN students s ON a.student_id=s.id JOIN courses c ON a.course_id=c.id WHERE a.marked_by=? ORDER BY a.date DESC");
        $q->execute([$teacher_id]);
        while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    } elseif ($type === 'assessments') {
        fputcsv($out, ['Student','Course','Date','Type','Grade','Notes']);
        $q = $pdo->prepare("SELECT s.name,c.name,a.date,a.assessment_type,a.grade,a.notes FROM assessments a JOIN students s ON a.student_id=s.id JOIN courses c ON a.course_id=c.id WHERE a.teacher_id=? ORDER BY a.date DESC");
        $q->execute([$teacher_id]);
        while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
    }
    fclose($out); exit;
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="grid-2">
    <div class="report-card">
        <i class="fas fa-calendar-check" style="color:var(--green)"></i>
        <h4>Attendance Report</h4>
        <p>Export attendance records you've marked.</p>
        <a href="?export=attendance" class="btn btn-success btn-sm"><i class="fas fa-download"></i> Download CSV</a>
    </div>
    <div class="report-card">
        <i class="fas fa-clipboard-list" style="color:var(--cyan)"></i>
        <h4>Assessment Report</h4>
        <p>Export all your assessment notes and grades.</p>
        <a href="?export=assessments" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download CSV</a>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

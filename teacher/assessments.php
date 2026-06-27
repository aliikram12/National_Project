<?php
require '../config/db.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;
$slot_id = $_GET['slot_id'] ?? null;

// Show class selection if no params
if (!$course_id || !$slot_id) {
    $classes = $pdo->prepare("SELECT c.id as course_id, c.name, s.id as slot_id, s.time_range 
                              FROM course_teachers ct 
                              JOIN courses c ON ct.course_id=c.id 
                              JOIN admissions a ON a.course_id=c.id 
                              JOIN slots s ON a.time_slot_id=s.id 
                              WHERE ct.teacher_id=? AND a.status='active' 
                              GROUP BY c.id, s.id");
    $classes->execute([$teacher_id]);
    $classList = $classes->fetchAll();
    
    include '../includes/dashboard_header.php';
    echo '<div class="card"><div class="card-header"><h3><i class="fas fa-clipboard-list" style="margin-right:8px;color:var(--cyan)"></i> Select Class for Assessment</h3></div><div class="grid-2">';
    foreach ($classList as $cl) {
        echo '<a href="assessments.php?course_id='.$cl['course_id'].'&slot_id='.$cl['slot_id'].'" class="course-card" style="cursor:pointer">';
        echo '<div class="course-icon" style="background:#ecfeff;color:var(--cyan)"><i class="fas fa-clipboard-list"></i></div>';
        echo '<div><h4>'.e($cl['name']).'</h4><p>'.e($cl['time_range']).'</p></div></a>';
    }
    if (empty($classList)) echo '<div class="empty-state" style="grid-column:1/-1"><i class="fas fa-folder-open"></i><p>No classes assigned.</p></div>';
    echo '</div></div>';
    include '../includes/dashboard_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_assessment') {
        $sid = (int)$_POST['student_id'];
        $date = sanitizeInput($_POST['date']);
        $type = sanitizeInput($_POST['assessment_type']);
        $notes = sanitizeInput($_POST['notes']);
        $grade = sanitizeInput($_POST['grade'] ?? '');
        
        $pdo->prepare("INSERT INTO assessments (student_id, teacher_id, course_id, date, assessment_type, notes, grade) VALUES (?,?,?,?,?,?,?)")
            ->execute([$sid, $teacher_id, $course_id, $date, $type, $notes, $grade]);
        setFlash('success', 'Assessment saved.');
        redirect("assessments.php?course_id=$course_id&slot_id=$slot_id");
    }
}

$students = $pdo->prepare("SELECT a.id, a.student_name as name FROM admissions a WHERE a.course_id=? AND a.time_slot_id=? AND a.status='active'");
$students->execute([$course_id, $slot_id]);
$students = $students->fetchAll();

$assessments = [];
if ($students) {
    $ids = array_column($students, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$teacher_id, $course_id]);
    $aStmt = $pdo->prepare("SELECT a.*, adm.student_name FROM assessments a JOIN admissions adm ON a.student_id=adm.id WHERE a.student_id IN ($ph) AND a.teacher_id=? AND a.course_id=? ORDER BY a.date DESC LIMIT 20");
    $aStmt->execute($params);
    $assessments = $aStmt->fetchAll();
}

$courseName = $pdo->prepare("SELECT name FROM courses WHERE id=?"); $courseName->execute([$course_id]); $courseName = $courseName->fetchColumn();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-clipboard-list" style="margin-right:8px;color:var(--cyan)"></i> <?php echo e($courseName); ?> — Assessment</h3>
        <a href="index.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <form method="POST">
        <?php csrfField(); ?><input type="hidden" name="action" value="add_assessment">
        <div class="form-row">
            <div class="form-group"><label>Student</label>
                <select name="student_id" class="form-control" required>
                    <option value="">— Select —</option>
                    <?php foreach($students as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Type</label>
                <select name="assessment_type" class="form-control" required>
                    <option value="daily_progress">Daily Progress</option>
                    <option value="assignment">Assignment</option>
                    <option value="exam">Exam</option>
                    <option value="general">General</option>
                </select>
            </div>
            <div class="form-group"><label>Grade</label><input type="text" name="grade" class="form-control" placeholder="e.g. A, B+, C"></div>
        </div>
        <div class="form-group"><label>Notes / Remarks</label><textarea name="notes" class="form-control" rows="3" required placeholder="Enter assessment notes..."></textarea></div>
        <button class="btn btn-primary"><i class="fas fa-save"></i> Save Assessment</button>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3>Previous Assessments</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Student</th><th>Date</th><th>Type</th><th>Grade</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach($assessments as $a): ?>
            <tr>
                <td><strong><?php echo e($a['student_name']); ?></strong></td>
                <td style="font-size:13px"><?php echo formatDate($a['date']); ?></td>
                <td><span class="badge badge-primary"><?php echo ucfirst(str_replace('_',' ',$a['assessment_type'])); ?></span></td>
                <td><strong style="color:var(--royal)"><?php echo e($a['grade'] ?: '—'); ?></strong></td>
                <td style="font-size:13px;max-width:300px"><?php echo nl2br(e($a['notes'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($assessments)): ?><tr><td colspan="5" class="text-center" style="padding:40px;color:var(--gray-500)">No assessments yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;
$slot_id = $_GET['slot_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

// If no course/slot, show selection
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
    echo '<div class="card"><div class="card-header"><h3><i class="fas fa-calendar-check" style="margin-right:8px;color:var(--royal)"></i> Select Class for Attendance</h3></div>';
    echo '<div class="grid-2">';
    foreach ($classList as $cl) {
        echo '<a href="attendance.php?course_id='.$cl['course_id'].'&slot_id='.$cl['slot_id'].'" class="course-card" style="cursor:pointer">';
        echo '<div class="course-icon"><i class="fas fa-book-open"></i></div>';
        echo '<div><h4>'.e($cl['name']).'</h4><p>'.e($cl['time_range']).'</p></div></a>';
    }
    if (empty($classList)) echo '<div class="empty-state" style="grid-column:1/-1"><i class="fas fa-folder-open"></i><p>No classes assigned.</p></div>';
    echo '</div></div>';
    include '../includes/dashboard_footer.php';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['attendance'])) {
        try {
            $pdo->beginTransaction();
            foreach ($_POST['attendance'] as $student_id => $status) {
                $student_id = (int)$student_id;
                $status = in_array($status, ['present','absent','leave']) ? $status : 'present';
                
                $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id=? AND date=? AND course_id=?");
                $check->execute([$student_id, $date, $course_id]);
                $exists = $check->fetch();
                
                if ($exists) {
                    $pdo->prepare("UPDATE attendance SET status=? WHERE id=?")->execute([$status, $exists['id']]);
                } else {
                    $pdo->prepare("INSERT INTO attendance (student_id, course_id, slot_id, date, status, marked_by) VALUES (?,?,?,?,?,?)")
                        ->execute([$student_id, $course_id, $slot_id, $date, $status, $teacher_id]);
                }
                
                if ($status === 'absent') {
                    $monthStart = date('Y-m-01', strtotime($date));
                    $monthEnd = date('Y-m-t', strtotime($date));
                    $absCount = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='absent' AND date BETWEEN ? AND ?");
                    $absCount->execute([$student_id, $monthStart, $monthEnd]);
                    if ($absCount->fetchColumn() >= 3) {
                        $pdo->prepare("UPDATE admissions SET status='dropped' WHERE id=? AND status='active'")->execute([$student_id]);
                    }
                }
            }
            $pdo->commit();
            setFlash('success', 'Attendance saved successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error saving attendance.');
        }
        redirect("attendance.php?course_id=$course_id&slot_id=$slot_id&date=$date");
    }
}

$students = $pdo->prepare("SELECT a.id, a.student_name as name, a.status, a.student_mobile as contact FROM admissions a WHERE a.course_id=? AND a.time_slot_id=?");
$students->execute([$course_id, $slot_id]);
$students = $students->fetchAll();

$marked = [];
$mStmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date=? AND course_id=?");
$mStmt->execute([$date, $course_id]);
foreach ($mStmt->fetchAll() as $m) $marked[$m['student_id']] = $m['status'];

$courseName = $pdo->prepare("SELECT name FROM courses WHERE id=?"); $courseName->execute([$course_id]); $courseName = $courseName->fetchColumn();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check" style="margin-right:8px;color:var(--royal)"></i> <?php echo e($courseName); ?> — Attendance</h3>
        <a href="index.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <p style="color:var(--gray-500);font-size:13px;margin-bottom:16px"><i class="fas fa-info-circle"></i> Rule: 3+ absences in a month → automatic struck off</p>
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
        <input type="hidden" name="course_id" value="<?php echo e($course_id); ?>">
        <input type="hidden" name="slot_id" value="<?php echo e($slot_id); ?>">
        <div class="form-group" style="margin-bottom:0"><label>Date</label><input type="date" name="date" class="form-control" value="<?php echo e($date); ?>"></div>
        <button class="btn btn-primary" style="height:44px"><i class="fas fa-sync"></i> Load</button>
    </form>
</div>

<div class="card">
    <form method="POST">
        <?php csrfField(); ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Student</th><th>Contact</th><th>Status</th><th>Attendance</th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo e($s['name']); ?></strong></td>
                    <td style="font-size:13px;color:var(--gray-500)"><?php echo e($s['contact']); ?></td>
                    <td><span class="badge <?php echo $s['status']==='active'?'badge-success':'badge-danger'; ?>"><?php echo $s['status']==='active'?'Active':'Struck Off'; ?></span></td>
                    <td>
                        <?php $cur = $marked[$s['id']] ?? 'present'; ?>
                        <select name="attendance[<?php echo $s['id']; ?>]" class="form-control" style="width:auto;min-width:120px" <?php echo $s['status']==='struck_off'?'disabled':''; ?>>
                            <option value="present" <?php echo $cur==='present'?'selected':''; ?>>✅ Present</option>
                            <option value="absent" <?php echo $cur==='absent'?'selected':''; ?>>❌ Absent</option>
                            <option value="leave" <?php echo $cur==='leave'?'selected':''; ?>>📋 Leave</option>
                        </select>
                        <?php if($s['status']==='struck_off'): ?><small style="color:var(--red);margin-left:8px">Needs re-admission</small><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($students)): ?><tr><td colspan="4" class="text-center" style="padding:40px;color:var(--gray-500)">No students enrolled.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if(!empty($students)): ?>
        <div style="text-align:right;margin-top:20px"><button class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Attendance</button></div>
        <?php endif; ?>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

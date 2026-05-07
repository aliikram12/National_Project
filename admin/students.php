<?php
require '../config/db.php';
requireRole('admin');

$search = sanitizeInput($_GET['search'] ?? '');
$courseFilter = sanitizeInput($_GET['course'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');

$query = "SELECT s.*, c.name as course_name, sl.time_range, e.enrollment_date FROM students s LEFT JOIN enrollments e ON s.id=e.student_id LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN slots sl ON e.slot_id=sl.id WHERE 1=1";
$params = [];

if ($search) { $query .= " AND (s.name LIKE ? OR s.contact LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($courseFilter) { $query .= " AND c.id = ?"; $params[] = $courseFilter; }
if ($statusFilter) { $query .= " AND s.status = ?"; $params[] = $statusFilter; }
$query .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($query); $stmt->execute($params); $students = $stmt->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE status='active'")->fetchAll();

// Re-admission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if ($_POST['action'] === 'readmit' && isset($_POST['id'])) {
        $pdo->prepare("UPDATE students SET status='active', struck_off_date=NULL, struck_off_reason=NULL WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Student re-admitted successfully.'); redirect('students.php');
    }
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="filter-bar no-print">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;width:100%">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or contact..." value="<?php echo e($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:160px">
            <label>Course</label>
            <select name="course" class="form-control">
                <option value="">All Courses</option>
                <?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $courseFilter==$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:130px">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                <option value="struck_off" <?php echo $statusFilter==='struck_off'?'selected':''; ?>>Struck Off</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:44px"><i class="fas fa-search"></i> Filter</button>
        <a href="students.php" class="btn btn-outline" style="height:44px"><i class="fas fa-times"></i> Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3>Students (<?php echo count($students); ?>)</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Reg ID</th><th>Name</th><th>Contact</th><th>Course</th><th>Slot</th><th>Status</th><th>Enrolled</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td style="font-family:monospace;color:var(--gray-500);font-size:13px">NC-<?php echo str_pad($s['id'],4,'0',STR_PAD_LEFT); ?></td>
                <td><strong><?php echo e($s['name']); ?></strong><br><span style="font-size:12px;color:var(--gray-500)">S/o <?php echo e($s['father_name']); ?></span></td>
                <td style="font-size:13px"><?php echo e($s['contact']); ?></td>
                <td><?php echo e($s['course_name'] ?? '—'); ?></td>
                <td><span class="badge badge-info" style="font-size:11px"><?php echo e($s['time_range'] ?? '—'); ?></span></td>
                <td><span class="badge <?php echo $s['status']==='active'?'badge-success':'badge-danger'; ?>"><?php echo $s['status']==='active'?'Active':'Struck Off'; ?></span></td>
                <td style="font-size:13px;color:var(--gray-500)"><?php echo formatDate($s['enrollment_date']); ?></td>
                <td>
                    <?php if($s['status']==='struck_off'): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Re-admit this student?')">
                        <?php csrfField(); ?><input type="hidden" name="action" value="readmit"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button class="btn btn-sm btn-success" title="Re-admit"><i class="fas fa-redo"></i> Re-admit</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($students)): ?><tr><td colspan="8" class="text-center" style="padding:40px;color:var(--gray-500)">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

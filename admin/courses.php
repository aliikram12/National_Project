<?php
require '../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = sanitizeInput($_POST['name']);
        $duration = sanitizeInput($_POST['duration']);
        $desc = sanitizeInput($_POST['description']);
        if ($name && $duration) {
            $pdo->prepare("INSERT INTO courses (name, duration, description) VALUES (?, ?, ?)")->execute([$name, $duration, $desc]);
            setFlash('success', 'Course added.');
        }
    } elseif ($action === 'update') {
        $pdo->prepare("UPDATE courses SET name=?, duration=?, description=? WHERE id=?")->execute([
            sanitizeInput($_POST['name']), sanitizeInput($_POST['duration']), sanitizeInput($_POST['description']), $_POST['id']
        ]);
        setFlash('success', 'Course updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Course deleted.');
    } elseif ($action === 'assign_teacher') {
        $check = $pdo->prepare("SELECT * FROM course_teachers WHERE course_id=? AND teacher_id=?");
        $check->execute([$_POST['course_id'], $_POST['teacher_id']]);
        if ($check->fetch()) { setFlash('warning', 'This teacher is already assigned to this course.'); }
        else {
            $pdo->prepare("INSERT INTO course_teachers (course_id, teacher_id) VALUES (?, ?)")->execute([$_POST['course_id'], $_POST['teacher_id']]);
            setFlash('success', 'Teacher assigned to course.');
        }
    }
    redirect('courses.php');
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY id DESC")->fetchAll();
$teachers = $pdo->query("SELECT id, name FROM users WHERE role='teacher' AND status='active'")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots WHERE status='active'")->fetchAll();
$assignments = $pdo->query("SELECT ct.course_id, ct.teacher_id, c.name as course, u.name as teacher, ct.assigned_at FROM course_teachers ct JOIN courses c ON ct.course_id=c.id JOIN users u ON ct.teacher_id=u.id ORDER BY ct.assigned_at DESC")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-plus-circle" style="margin-right:8px;color:var(--royal)"></i> Add Course</h3></div>
    <form method="POST">
        <?php csrfField(); ?><input type="hidden" name="action" value="add">
        <div style="display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:14px;align-items:flex-end">
            <div class="form-group" style="margin-bottom:0"><label>Name</label><input type="text" name="name" class="form-control" required placeholder="e.g. Web Development"></div>
            <div class="form-group" style="margin-bottom:0"><label>Duration</label><input type="text" name="duration" class="form-control" required placeholder="e.g. 6 Months"></div>
            <div class="form-group" style="margin-bottom:0"><label>Description</label><input type="text" name="description" class="form-control" placeholder="Brief description"></div>
            <button class="btn btn-primary" style="height:44px"><i class="fas fa-plus"></i> Add</button>
        </div>
    </form>
</div>

<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><h3>Courses (<?php echo count($courses); ?>)</h3></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Name</th><th>Duration</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?php echo e($c['name']); ?></strong><br><span style="font-size:12px;color:var(--gray-500)"><?php echo e($c['description']); ?></span></td>
                    <td><span class="badge badge-primary"><?php echo e($c['duration']); ?></span></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-link" style="margin-right:8px;color:var(--cyan)"></i> Assign Teacher</h3></div>
        <form method="POST" class="mb-3">
            <?php csrfField(); ?><input type="hidden" name="action" value="assign_teacher">
            <div class="form-group"><label>Teacher</label><select name="teacher_id" class="form-control" required><?php foreach($teachers as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo e($t['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Course</label><select name="course_id" class="form-control" required><?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div>
            <button class="btn btn-primary" style="width:100%"><i class="fas fa-check"></i> Assign</button>
        </form>
        <h4 style="font-size:14px;margin-bottom:10px;color:var(--gray-600)">Current Assignments</h4>
        <?php foreach($assignments as $a): ?>
        <div style="padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px">
            <strong><?php echo e($a['teacher']); ?></strong> → <?php echo e($a['course']); ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

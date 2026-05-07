<?php
require '../config/db.php';
requireRole('receptionist');

$search = sanitizeInput($_GET['search'] ?? '');
$query = "SELECT s.*, c.name as course_name, sl.time_range FROM students s LEFT JOIN enrollments e ON s.id=e.student_id LEFT JOIN courses c ON e.course_id=c.id LEFT JOIN slots sl ON e.slot_id=sl.id";
$params = [];
if ($search) { $query .= " WHERE s.name LIKE ? OR s.contact LIKE ? OR c.name LIKE ?"; $params = ["%$search%","%$search%","%$search%"]; }
$query .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($query); $stmt->execute($params); $students = $stmt->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h2 style="font-size:20px">Students (<?php echo count($students); ?>)</h2>
    <form method="GET" style="display:flex;gap:10px">
        <input type="text" name="search" class="form-control" placeholder="Search name, contact, course..." value="<?php echo e($search); ?>" style="width:280px">
        <button class="btn btn-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Reg ID</th><th>Name</th><th>Contact</th><th>Course</th><th>Slot</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td style="font-family:monospace;font-size:13px;color:var(--gray-500)">NC-<?php echo date('Y',strtotime($s['created_at'])); ?>-<?php echo str_pad($s['id'],4,'0',STR_PAD_LEFT); ?></td>
                <td><strong><?php echo e($s['name']); ?></strong></td>
                <td style="font-size:13px"><?php echo e($s['contact']); ?></td>
                <td><?php echo e($s['course_name'] ?? '—'); ?></td>
                <td><span class="badge badge-info" style="font-size:11px"><?php echo e($s['time_range'] ?? '—'); ?></span></td>
                <td><span class="badge <?php echo $s['status']==='active'?'badge-success':'badge-danger'; ?>"><?php echo $s['status']==='active'?'Active':'Struck Off'; ?></span></td>
                <td><a href="print_admission.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-print"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($students)): ?><tr><td colspan="7" class="text-center" style="padding:40px;color:var(--gray-500)">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

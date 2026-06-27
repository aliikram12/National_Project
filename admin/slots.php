<?php
require '../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && !empty($_POST['time_range'])) {
        $pdo->prepare("INSERT INTO slots (time_range) VALUES (?)")->execute([sanitizeInput($_POST['time_range'])]);
        setFlash('success', 'Slot added.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM slots WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Slot deleted.');
    }
    redirect('slots.php');
}

$slots = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM admissions a WHERE a.time_slot_id=s.id AND a.status='active') as student_count FROM slots s")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-plus-circle" style="margin-right:8px;color:var(--royal)"></i> Add Time Slot</h3></div>
    <form method="POST" style="display:flex;gap:14px;align-items:flex-end">
        <?php csrfField(); ?><input type="hidden" name="action" value="add">
        <div class="form-group" style="margin-bottom:0;flex:1"><label>Time Range</label><input type="text" name="time_range" class="form-control" required placeholder="e.g. 8:00 AM - 10:00 AM"></div>
        <button class="btn btn-primary" style="height:44px"><i class="fas fa-plus"></i> Add</button>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3>Time Slots (<?php echo count($slots); ?>)</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>ID</th><th>Time Range</th><th>Students</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($slots as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><strong><?php echo e($s['time_range']); ?></strong></td>
                <td><span class="badge badge-primary"><?php echo $s['student_count']; ?> enrolled</span></td>
                <td><span class="badge badge-success"><?php echo ucfirst($s['status']); ?></span></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this slot?')">
                        <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $time_range = $_POST['time_range'];

            $stmt = $pdo->prepare("INSERT INTO slots (time_range) VALUES (?)");
            $stmt->execute([$time_range]);
            $message = "Slot added successfully.";
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM slots WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Slot deleted successfully.";
        }
    }
}

$slots = $pdo->query("SELECT * FROM slots")->fetchAll();

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Slot Management</h3>
    <?php if ($message): ?>
        <div class="alert alert-success mt-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" class="mt-2" style="display: flex; gap: 15px; align-items: end;">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="add">
        
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <label>Time Range</label>
            <input type="text" name="time_range" class="form-control" required placeholder="e.g. 8:00 AM - 10:00 AM">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 46px;">Add Slot</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time Range</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $slot): ?>
                    <tr>
                        <td><?php echo $slot['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($slot['time_range']); ?></strong></td>
                        <td>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this slot?');">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 5px 10px; background: #dc3545; font-size: 12px;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

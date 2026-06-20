<?php
/**
 * National College LMS - Fee Packages Management (Admin only)
 */

require '../config/db.php';
requireRole('admin');

$pageTitle = 'Fee Packages Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = sanitizeInput($_POST['name']);
        $desc = sanitizeInput($_POST['description']);
        $totalFee = (float)$_POST['total_fee'];
        $discountPct = (float)$_POST['discount_percent'];
        $discountAmt = (float)$_POST['discount_amount'];
        $duration = (int)$_POST['duration_months'];
        if ($name && $totalFee > 0) {
            $pdo->prepare("INSERT INTO fee_packages (name, description, total_fee, discount_percent, discount_amount, duration_months) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $desc, $totalFee, $discountPct, $discountAmt, $duration]);
            setFlash('success', 'Fee package added.');
        }
    } elseif ($action === 'update') {
        $pdo->prepare("UPDATE fee_packages SET name=?, description=?, total_fee=?, discount_percent=?, discount_amount=?, duration_months=? WHERE id=?")->execute([
            sanitizeInput($_POST['name']), sanitizeInput($_POST['description']),
            (float)$_POST['total_fee'], (float)$_POST['discount_percent'],
            (float)$_POST['discount_amount'], (int)$_POST['duration_months'], $_POST['id']
        ]);
        setFlash('success', 'Fee package updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("UPDATE fee_packages SET status='inactive' WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Fee package deactivated.');
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE fee_packages SET status='active' WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Fee package activated.');
    }
    redirect('fee_packages.php');
}

$packages = $pdo->query("SELECT * FROM fee_packages ORDER BY id DESC")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>
<link rel="stylesheet" href="../assets/css/admission.css">

<!-- Add / Edit Package -->
<div class="admission-card mb-3" style="max-width:800px;margin:0 auto 24px">
    <div class="admission-card-header" style="border-radius:12px 12px 0 0">
        <h3><i class="fas fa-tags"></i> <?php echo isset($_GET['edit']) ? 'Edit Fee Package' : 'Add Fee Package'; ?></h3>
    </div>
    <div class="admission-card-body">
        <?php
        $editPkg = null;
        if (isset($_GET['edit'])) {
            $stmt = $pdo->prepare("SELECT * FROM fee_packages WHERE id = ?");
            $stmt->execute([(int)$_GET['edit']]);
            $editPkg = $stmt->fetch();
        }
        ?>
        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="<?php echo $editPkg ? 'update' : 'add'; ?>">
            <?php if ($editPkg): ?><input type="hidden" name="id" value="<?php echo $editPkg['id']; ?>"><?php endif; ?>
            <div class="admission-form-row triple">
                <div class="admission-form-group">
                    <label>Package Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo e($editPkg['name'] ?? ''); ?>" required placeholder="e.g. Standard Package">
                </div>
                <div class="admission-form-group">
                    <label>Total Fee (Rs.) <span class="required-star">*</span></label>
                    <input type="number" name="total_fee" class="form-control" value="<?php echo e($editPkg['total_fee'] ?? ''); ?>" required placeholder="25000" min="0" step="100">
                </div>
                <div class="admission-form-group">
                    <label>Duration (Months)</label>
                    <input type="number" name="duration_months" class="form-control" value="<?php echo e($editPkg['duration_months'] ?? 3); ?>" min="1" max="60">
                </div>
            </div>
            <div class="admission-form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this package..."><?php echo e($editPkg['description'] ?? ''); ?></textarea>
            </div>
            <div class="admission-form-row" style="max-width:400px">
                <div class="admission-form-group">
                    <label>Discount (%)</label>
                    <input type="number" name="discount_percent" class="form-control" value="<?php echo e($editPkg['discount_percent'] ?? 0); ?>" min="0" max="100" step="0.5">
                </div>
                <div class="admission-form-group">
                    <label>Discount Amount (Rs.)</label>
                    <input type="number" name="discount_amount" class="form-control" value="<?php echo e($editPkg['discount_amount'] ?? 0); ?>" min="0" step="100">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px">
                <button type="submit" class="btn admission-btn-primary"><i class="fas fa-save"></i> <?php echo $editPkg ? 'Update' : 'Add Package'; ?></button>
                <?php if ($editPkg): ?><a href="fee_packages.php" class="btn admission-btn-outline">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Fee Packages List -->
<div class="admission-card">
    <div class="admission-card-header" style="border-radius:12px 12px 0 0">
        <h3><i class="fas fa-list"></i> Fee Packages (<?php echo count($packages); ?>)</h3>
    </div>
    <div class="admission-card-body" style="padding:0">
        <div style="overflow-x:auto">
            <table class="admission-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Package Name</th>
                        <th>Description</th>
                        <th>Total Fee</th>
                        <th>Discount</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($packages)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No fee packages found.</td></tr>
                <?php else: ?>
                <?php foreach ($packages as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><strong><?php echo e($p['name']); ?></strong></td>
                        <td style="font-size:12px;color:var(--gray-500);max-width:200px"><?php echo e($p['description']); ?></td>
                        <td><strong style="color:var(--admission-green)">Rs. <?php echo number_format($p['total_fee']); ?></strong></td>
                        <td>
                            <?php if ($p['discount_percent'] > 0): ?>
                                <span class="badge badge-danger"><?php echo $p['discount_percent']; ?>%</span>
                            <?php endif; ?>
                            <?php if ($p['discount_amount'] > 0): ?>
                                <span class="badge badge-warning">Rs. <?php echo number_format($p['discount_amount']); ?></span>
                            <?php endif; ?>
                            <?php if ($p['discount_percent'] == 0 && $p['discount_amount'] == 0): ?>
                                <span style="color:var(--gray-400);font-size:12px">None</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $p['duration_months']; ?> months</td>
                        <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="?edit=<?php echo $p['id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php if ($p['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this package?')">
                                        <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button class="action-btn action-btn-delete" title="Deactivate"><i class="fas fa-ban"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Activate this package?')">
                                        <?php csrfField(); ?><input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button class="action-btn action-btn-view" title="Activate"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

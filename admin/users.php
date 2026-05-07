<?php
require '../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = sanitizeInput($_POST['role']);
        $errors = validateRequired(['name'=>'Name','email'=>'Email','password'=>'Password','role'=>'Role'], $_POST);
        if (!validateEmail($email)) $errors[] = 'Invalid email.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($errors) { setFlash('danger', implode(' ', $errors)); }
        else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email=?"); $check->execute([$email]);
            if ($check->fetch()) { setFlash('danger', 'Email already exists.'); }
            else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                setFlash('success', 'User added successfully.');
            }
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        if ($_POST['id'] == $_SESSION['user_id']) { setFlash('danger', 'Cannot delete yourself.'); }
        else { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['id']]); setFlash('success', 'User deleted.'); }
    } elseif ($action === 'reset_password' && isset($_POST['id'])) {
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) < 6) { setFlash('danger', 'Password must be at least 6 characters.'); }
        else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $_POST['id']]); setFlash('success', 'Password reset.'); }
    } elseif ($action === 'toggle_status' && isset($_POST['id'])) {
        $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=? AND id!=?")->execute([$_POST['id'], $_SESSION['user_id']]);
        setFlash('success', 'User status updated.');
    }
    redirect('users.php');
}

$users = $pdo->query("SELECT id, name, email, role, status, last_login, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card mb-3">
    <div class="card-header"><h3><i class="fas fa-user-plus" style="margin-right:8px;color:var(--royal)"></i> Add New User</h3></div>
    <form method="POST">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="add">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:14px;align-items:flex-end">
            <div class="form-group" style="margin-bottom:0"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group" style="margin-bottom:0"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group" style="margin-bottom:0"><label>Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <div class="form-group" style="margin-bottom:0"><label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="receptionist">Receptionist</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:44px"><i class="fas fa-plus"></i> Add</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3>All Users (<?php echo count($users); ?>)</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?php echo e($u['name']); ?></strong></td>
                <td style="color:var(--gray-500)"><?php echo e($u['email']); ?></td>
                <td><span class="badge <?php echo $u['role']==='admin'?'badge-danger':($u['role']==='teacher'?'badge-warning':'badge-success'); ?>"><?php echo ucfirst($u['role']); ?></span></td>
                <td><span class="badge <?php echo $u['status']==='active'?'badge-success':'badge-gray'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                <td style="font-size:13px;color:var(--gray-500)"><?php echo $u['last_login'] ? formatDate($u['last_login'], 'M d, Y H:i') : 'Never'; ?></td>
                <td>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <div style="display:flex;gap:6px">
                        <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                            <button class="btn btn-sm btn-outline" title="Toggle Status"><i class="fas fa-power-off"></i></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="var p=prompt('New password (min 6 chars):');if(!p||p.length<6){alert('Min 6 chars');return false;}this.new_password.value=p;return true;">
                            <?php csrfField(); ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?php echo $u['id']; ?>"><input type="hidden" name="new_password" value="">
                            <button class="btn btn-sm btn-warning" title="Reset Password"><i class="fas fa-key"></i></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                            <?php csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                            <button class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php else: ?><span class="badge badge-info">You</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

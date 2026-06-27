<?php
require '../config/db.php';
requireRole('admin');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$search = sanitizeInput($_GET['search'] ?? '');
$roleFilter = sanitizeInput($_GET['role'] ?? '');

$query = "SELECT lh.*, u.name as user_name, u.role as user_role 
          FROM login_history lh 
          JOIN users u ON lh.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (u.name LIKE ? OR lh.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $query .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$query .= " ORDER BY lh.login_time DESC";

$countQuery = preg_replace('/SELECT .+? FROM/is', 'SELECT COUNT(*) FROM', $query);
$countQuery = preg_replace('/ORDER BY .+$/i', '', $countQuery);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$page = max(1, min($page, $totalPages ?: 1));
$offset = ($page - 1) * $perPage;

$query .= " LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();

$pageTitle = 'Login History';
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="filter-bar no-print">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;width:100%">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
            <label>Live Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search user name or IP..." value="<?php echo e($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:160px">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $roleFilter==='admin'?'selected':''; ?>>Admin</option>
                <option value="teacher" <?php echo $roleFilter==='teacher'?'selected':''; ?>>Teacher</option>
                <option value="receptionist" <?php echo $roleFilter==='receptionist'?'selected':''; ?>>Receptionist</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:44px"><i class="fas fa-search"></i> Search</button>
        <a href="login_history.php" class="btn btn-outline" style="height:44px"><i class="fas fa-times"></i> Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>User Login History</h3>
        <span class="badge badge-info">Total Records: <?php echo number_format($total); ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Duration</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): 
                    $loginTime = new DateTime($row['login_time']);
                    $logoutTime = $row['logout_time'] ? new DateTime($row['logout_time']) : null;
                    
                    $durationStr = '—';
                    if ($logoutTime) {
                        $diff = $loginTime->diff($logoutTime);
                        $durationParts = [];
                        if ($diff->h > 0) $durationParts[] = $diff->h . 'h';
                        if ($diff->i > 0) $durationParts[] = $diff->i . 'm';
                        if ($diff->s > 0) $durationParts[] = $diff->s . 's';
                        $durationStr = !empty($durationParts) ? implode(' ', $durationParts) : '< 1s';
                    } elseif ($loginTime->diff(new DateTime())->h < 12) {
                        $durationStr = '<span class="text-success"><i class="fas fa-circle" style="font-size:8px"></i> Active</span>';
                    }
                ?>
                <tr>
                    <td><strong><?php echo e($row['user_name']); ?></strong></td>
                    <td><span style="text-transform:capitalize" class="badge badge-<?php echo $row['user_role'] === 'admin' ? 'primary' : ($row['user_role'] === 'teacher' ? 'success' : 'warning'); ?>"><?php echo e($row['user_role']); ?></span></td>
                    <td style="font-size:13px"><?php echo $loginTime->format('M d, Y h:i A'); ?></td>
                    <td style="font-size:13px"><?php echo $logoutTime ? $logoutTime->format('M d, Y h:i A') : '—'; ?></td>
                    <td style="font-size:13px; font-weight:600;"><?php echo $durationStr; ?></td>
                    <td style="font-size:12px; color:var(--gray-500); font-family:monospace;"><?php echo e($row['ip_address'] ?: 'Unknown'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding:40px;color:var(--gray-500)">No login history found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div style="padding:16px;border-top:1px solid var(--gray-200);display:flex;justify-content:center;gap:4px">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>" 
               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"
               style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

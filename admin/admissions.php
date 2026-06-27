<?php
/**
 * National College LMS - Admissions Management
 * Accessible by: Admin (full access), Receptionist (create/edit/view/print/search)
 */

require __DIR__ . '/../config/db.php';
@include __DIR__ . '/../database/run_migrations.php'; // Temporary auto-migrate

$user = getCurrentUser($pdo);
if (!$user || !in_array($user['role'], ['admin', 'receptionist'])) {
    redirect('../index.php');
}

$role = $user['role'];
$canCreate = in_array($role, ['admin', 'receptionist']);
$canEdit = in_array($role, ['admin', 'receptionist']);
$canDelete = $role === 'admin';
$canView = in_array($role, ['admin', 'receptionist']);
$canPrint = in_array($role, ['admin', 'receptionist']);

$search = sanitizeInput($_GET['search'] ?? '');
$courseFilter = sanitizeInput($_GET['course'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$slotFilter = sanitizeInput($_GET['slot'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$params = [];
$query = "SELECT a.*, c.name as course_name, s.time_range, fp.name as fee_package_name,
                 u.name as created_by_name
          FROM admissions a
          JOIN courses c ON a.course_id = c.id
          LEFT JOIN slots s ON a.time_slot_id = s.id
          LEFT JOIN fee_packages fp ON a.fee_package_id = fp.id
          LEFT JOIN users u ON a.created_by = u.id
          WHERE 1=1";

if ($search) {
    $query .= " AND (a.student_name LIKE ? OR a.registration_number LIKE ? OR a.cnic LIKE ?
                          OR a.student_mobile LIKE ? OR a.father_name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($courseFilter) { $query .= " AND c.id = ?"; $params[] = $courseFilter; }
if ($statusFilter) { $query .= " AND a.status = ?"; $params[] = $statusFilter; }
if ($slotFilter) { $query .= " AND s.id = ?"; $params[] = $slotFilter; }

$query .= " ORDER BY a.id DESC";

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
$admissions = $stmt->fetchAll();

$courses = $pdo->query("SELECT id, name FROM courses WHERE status='active' ORDER BY name")->fetchAll();
$slots = $pdo->query("SELECT id, time_range FROM slots WHERE status='active' ORDER BY id")->fetchAll();

$statTotal = $pdo->query("SELECT COUNT(*) FROM admissions")->fetchColumn();
$statActive = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status='active'")->fetchColumn();
$statCompleted = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status='completed'")->fetchColumn();
$statThisMonth = $pdo->query("SELECT COUNT(*) FROM admissions WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();

// Fetch course-wise stats
$courseStats = $pdo->query("
    SELECT 
        c.name, 
        COUNT(a.id) as total_admissions,
        SUM(CASE WHEN a.status = 'active' THEN 1 ELSE 0 END) as active_admissions,
        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_admissions
    FROM courses c
    LEFT JOIN admissions a ON c.id = a.course_id
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$pageTitle = 'Admission Management';
?>
<?php include __DIR__ . '/../includes/dashboard_header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<link rel="stylesheet" href="../assets/css/admission.css">

<!-- Overall Stats -->
<div class="admission-stats-grid">
    <div class="admission-stat-card">
        <div class="stat-icon-box green"><i class="fas fa-file-alt"></i></div>
        <div class="stat-text"><h4><?php echo number_format($statTotal); ?></h4><span>Total Admissions</span></div>
    </div>
    <div class="admission-stat-card">
        <div class="stat-icon-box blue"><i class="fas fa-user-check"></i></div>
        <div class="stat-text"><h4><?php echo number_format($statActive); ?></h4><span>Active Students</span></div>
    </div>
    <div class="admission-stat-card">
        <div class="stat-icon-box green"><i class="fas fa-check-double"></i></div>
        <div class="stat-text"><h4><?php echo number_format($statCompleted); ?></h4><span>Completed</span></div>
    </div>
    <div class="admission-stat-card">
        <div class="stat-icon-box orange"><i class="fas fa-calendar"></i></div>
        <div class="stat-text"><h4><?php echo number_format($statThisMonth); ?></h4><span>This Month</span></div>
    </div>
</div>

<!-- Course Wise Stats -->
<h4 style="margin-bottom: 16px; color: var(--navy); font-weight: 700;">Course Wise Admissions</h4>
<div class="admission-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
    <?php foreach($courseStats as $cStat): 
        $active = (int)$cStat['active_admissions'];
        $total = (int)$cStat['total_admissions'];
    ?>
    <div class="admission-stat-card" style="padding: 18px;">
        <div class="stat-icon-box blue" style="width: 42px; height: 42px; font-size: 16px;"><i class="fas fa-book"></i></div>
        <div class="stat-text" style="flex: 1;">
            <h5 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: var(--navy);"><?php echo e($cStat['name']); ?></h5>
            <div style="display: flex; gap: 12px; font-size: 12px; color: var(--gray-500);">
                <span><strong><?php echo $total; ?></strong> Total</span>
                <span style="color: var(--royal);"><strong><?php echo $active; ?></strong> Active</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($courseStats)): ?>
        <p style="color: var(--gray-500);">No active courses found.</p>
    <?php endif; ?>
</div>

<div class="admission-search-bar">
    <form method="GET" id="searchForm" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;width:100%">
        <div class="form-group" style="margin-bottom:0;flex:2;min-width:220px">
            <label>Live Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name, reg #, CNIC, mobile, father, course..." value="<?php echo e($search); ?>" autocomplete="off">
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:160px">
            <label>Course</label>
            <select name="course" class="form-control">
                <option value="">All Courses</option>
                <?php foreach($courses as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $courseFilter===$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:130px">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                <option value="completed" <?php echo $statusFilter==='completed'?'selected':''; ?>>Completed</option>
                <option value="dropped" <?php echo $statusFilter==='dropped'?'selected':''; ?>>Dropped</option>
                <option value="transferred" <?php echo $statusFilter==='transferred'?'selected':''; ?>>Transferred</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:150px">
            <label>Time Slot</label>
            <select name="slot" class="form-control">
                <option value="">All Slots</option>
                <?php foreach($slots as $sl): ?>
                <option value="<?php echo $sl['id']; ?>" <?php echo $slotFilter===$sl['id']?'selected':''; ?>><?php echo e($sl['time_range']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="page" value="1">
        <button type="submit" class="btn admission-btn-primary"><i class="fas fa-search"></i> Search</button>
        <a href="admissions.php" class="btn admission-btn-outline"><i class="fas fa-times"></i> Clear</a>
        <?php if ($canCreate): ?>
        <a href="admission_form.php" class="btn admission-btn-primary" style="margin-left:auto"><i class="fas fa-plus"></i> New Admission</a>
        <?php endif; ?>
    </form>
</div>

<?php if (isset($_SESSION['flash'])): renderFlash(); endif; ?>

<div class="admission-table-wrapper">
    <div style="overflow-x:auto">
        <table class="admission-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Reg #</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Course</th>
                    <th>Time Slot</th>
                    <th>Session</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admissionsTableBody">
            <?php if (empty($admissions)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:60px 20px;color:var(--gray-400)">
                        <i class="fas fa-inbox" style="font-size:48px;display:block;margin-bottom:12px;color:var(--gray-300)"></i>
                        <h4 style="color:var(--gray-500);margin-bottom:4px">No admissions found</h4>
                        <p style="font-size:13px">Try adjusting your search or filter criteria</p>
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($admissions as $a): ?>
                <tr>
                    <td>
                        <?php if ($a['student_photo'] && file_exists(__DIR__ . '/../uploads/students/' . $a['student_photo'])): ?>
                            <img src="../uploads/students/<?php echo e($a['student_photo']); ?>" class="student-photo-thumb" alt="Photo" style="cursor:pointer" onclick="showPhotoModal('../uploads/students/<?php echo e($a['student_photo']); ?>')">
                        <?php else: ?>
                            <div class="student-photo-thumb-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong style="font-family:monospace;color:var(--admission-green);font-size:13px"><?php echo e($a['registration_number']); ?></strong></td>
                    <td><strong><?php echo e($a['student_name']); ?></strong></td>
                    <td><?php echo e($a['father_name']); ?></td>
                    <td><?php echo e($a['course_name']); ?></td>
                    <td><span class="badge badge-info" style="font-size:11px"><?php echo e($a['time_range'] ?? '—'); ?></span></td>
                    <td style="font-size:12px;color:var(--gray-500)"><?php echo e($a['session_start']); ?> - <?php echo e($a['session_end']); ?></td>
                    <td style="font-size:13px"><?php echo e($a['student_mobile']); ?></td>
                    <td><span class="status-badge status-<?php echo e($a['status']); ?>"><?php echo ucfirst(e($a['status'])); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <?php if ($canView): ?>
                            <a href="student_profile.php?id=<?php echo $a['id']; ?>" class="action-btn action-btn-view" title="View Profile"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <?php if ($canEdit): ?>
                            <a href="admission_form.php?id=<?php echo $a['id']; ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <?php if ($canPrint): ?>
                            <a href="print_admission.php?id=<?php echo $a['id']; ?>" class="action-btn action-btn-print" title="Print" target="_blank"><i class="fas fa-print"></i></a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button class="action-btn action-btn-delete" title="Delete" onclick="confirmDelete(<?php echo $a['id']; ?>, '<?php echo e($a['student_name']); ?>')"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admission-pagination" id="admissionPagination">
        <?php if ($totalPages > 1): ?>
        <?php if ($page > 1): ?>
            <a href="?<?php echo $_SERVER['QUERY_STRING']; ?>&page=<?php echo $page-1; ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="?<?php echo $_SERVER['QUERY_STRING']; ?>&page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo $_SERVER['QUERY_STRING']; ?>&page=<?php echo $page+1; ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="confirm-modal-overlay" id="deleteModal">
    <div class="confirm-modal">
        <div class="icon-circle"><i class="fas fa-trash-alt"></i></div>
        <h4>Delete Admission</h4>
        <p>Are you sure you want to delete the admission for <strong id="deleteStudentName"></strong>? This action cannot be undone.</p>
        <div class="btn-group">
            <button class="btn admission-btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" style="display:inline">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn" style="background:#ef4444;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-weight:600;cursor:pointer;font-family:inherit"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </div>
    </div>
</div>

<div class="photo-preview-overlay" id="photoModal">
    <div style="text-align:center">
        <img id="photoModalImg" src="" alt="Preview" style="max-width:350px;max-height:350px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <br><br>
        <button class="btn btn-outline" onclick="closePhotoModal()" style="background:#fff;color:#333;border:1px solid #ddd;padding:8px 20px;border-radius:8px;cursor:pointer">Close</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function(){
    $('.form-control[data-search-enabled]').select2({
        placeholder: 'Search...',
        allowClear: true,
        width: '100%'
    });

    const searchForm = document.getElementById('searchForm');
    const tableBody = document.getElementById('admissionsTableBody');
    const pagination = document.getElementById('admissionPagination');
    let ajaxPage = 1;
    let ajaxTimer = null;

    function formDataToParams() {
        return new URLSearchParams(new FormData(searchForm)).toString();
    }

    function renderPagination(pageData) {
        if (!pageData || pageData.totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '';
        if (pageData.page > 1) {
            html += '<button type="button" class="page-btn" data-page="' + (pageData.page - 1) + '"><i class="fas fa-chevron-left"></i></button>';
        } else {
            html += '<span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>';
        }

        const start = Math.max(1, pageData.page - 3);
        const end = Math.min(pageData.totalPages, pageData.page + 3);
        for (let i = start; i <= end; i++) {
            html += '<button type="button" class="page-btn ' + (i === pageData.page ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }

        if (pageData.page < pageData.totalPages) {
            html += '<button type="button" class="page-btn" data-page="' + (pageData.page + 1) + '"><i class="fas fa-chevron-right"></i></button>';
        } else {
            html += '<span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>';
        }
        pagination.innerHTML = html;
    }

    function loadAdmissions(page) {
        ajaxPage = page || 1;
        const params = formDataToParams();
        fetch('ajax.php?action=search_admissions&' + params, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Unable to load admissions.');
                return;
            }
            tableBody.innerHTML = data.rows || '<tr><td colspan="10" style="text-align:center;padding:60px 20px;color:var(--gray-400)">No admissions found</td></tr>';
            renderPagination(data.pagination);
        })
        .catch(() => alert('AJAX search failed. Please reload the page.'));
    }

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadAdmissions(1);
    });

    searchForm.querySelectorAll('input[name="search"], select[name="course"], select[name="status"], select[name="slot"]').forEach(function(el) {
        el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', function() {
            clearTimeout(ajaxTimer);
            ajaxTimer = setTimeout(() => loadAdmissions(1), 350);
        });
    });

    pagination.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-page]');
        if (!btn || btn.classList.contains('disabled')) return;
        loadAdmissions(parseInt(btn.dataset.page));
    });
});

function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteStudentName').textContent = name;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

function showPhotoModal(src) {
    document.getElementById('photoModalImg').src = src;
    document.getElementById('photoModal').classList.add('show');
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.remove('show');
}

document.getElementById('photoModal').addEventListener('click', function(e) {
    if (e.target === this) closePhotoModal();
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php include __DIR__ . '/../includes/dashboard_footer.php'; ?>

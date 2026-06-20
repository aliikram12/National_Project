<?php
/**
 * National College LMS - AJAX Endpoints for Admission Module
 */

require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$user = getCurrentUser($pdo);
if (!$user || !in_array($user['role'], ['admin', 'receptionist'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'load_courses':
            $stmt = $pdo->prepare("SELECT id, code, name, duration, duration_months, fee FROM courses WHERE status='active' ORDER BY name");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'load_slots':
            $stmt = $pdo->prepare("SELECT id, time_range, duration FROM slots WHERE status='active' ORDER BY time_range");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'load_fee_packages':
            $stmt = $pdo->prepare("SELECT id, name, total_fee, discount_percent, discount_amount, duration_months FROM fee_packages WHERE status='active' ORDER BY name");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get_course_details':
            $courseId = (int)($_GET['course_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, name, duration, duration_months, fee FROM courses WHERE id = ? AND status='active'");
            $stmt->execute([$courseId]);
            $course = $stmt->fetch();
            if ($course) {
                echo json_encode(['success' => true, 'data' => $course]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Course not found']);
            }
            break;

        case 'validate_reg_no':
            $regNo = sanitizeInput($_GET['registration_number'] ?? '');
            $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
            $sql = "SELECT id FROM admissions WHERE registration_number = ?";
            $params = [$regNo];
            if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'exists' => (bool)$stmt->fetch()]);
            break;

        case 'search_admissions':
            $search = sanitizeInput($_GET['search'] ?? '');
            $courseFilter = isset($_GET['course']) && $_GET['course'] !== '' ? (int)$_GET['course'] : 0;
            $statusFilter = sanitizeInput($_GET['status'] ?? '');
            $slotFilter = isset($_GET['slot']) && $_GET['slot'] !== '' ? (int)$_GET['slot'] : 0;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $params = [];

            $query = "SELECT a.*, c.name as course_name, s.time_range, fp.name as fee_package_name, u.name as created_by_name
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
            $total = (int)$countStmt->fetchColumn();
            $totalPages = ceil($total / $perPage);
            $page = max(1, min($page, $totalPages ?: 1));
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $perPage OFFSET $offset";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $html = '';
            foreach ($rows as $a) {
                $photo = '';
                if (!empty($a['student_photo']) && file_exists(__DIR__ . '/../uploads/students/' . $a['student_photo'])) {
                    $src = '../uploads/students/' . e($a['student_photo']);
                    $photo = '<img src="' . $src . '" class="student-photo-thumb" alt="Photo" style="cursor:pointer" onclick="showPhotoModal(\'' . $src . '\')">';
                } else {
                    $photo = '<div class="student-photo-thumb-placeholder"><i class="fas fa-user"></i></div>';
                }

                $deleteButton = '';
                if ($user['role'] === 'admin') {
                    $name = str_replace("'", "\\'", $a['student_name']);
                    $deleteButton = '<button class="action-btn action-btn-delete" title="Delete" onclick="confirmDelete(' . (int)$a['id'] . ', \'' . e($name) . '\')"><i class="fas fa-trash"></i></button>';
                }

                $html .= '<tr>';
                $html .= '<td>' . $photo . '</td>';
                $html .= '<td><strong style="font-family:monospace;color:var(--admission-green);font-size:13px">' . e($a['registration_number']) . '</strong></td>';
                $html .= '<td><strong>' . e($a['student_name']) . '</strong></td>';
                $html .= '<td>' . e($a['father_name']) . '</td>';
                $html .= '<td>' . e($a['course_name']) . '</td>';
                $html .= '<td><span class="badge badge-info" style="font-size:11px">' . e($a['time_range'] ?? '—') . '</span></td>';
                $html .= '<td style="font-size:12px;color:var(--gray-500)">' . e($a['session_start']) . ' - ' . e($a['session_end']) . '</td>';
                $html .= '<td style="font-size:13px">' . e($a['student_mobile']) . '</td>';
                $html .= '<td><span class="status-badge status-' . e($a['status']) . '">' . ucfirst(e($a['status'])) . '</span></td>';
                $html .= '<td><div class="action-btns">';
                $html .= '<a href="student_profile.php?id=' . (int)$a['id'] . '" class="action-btn action-btn-view" title="View Profile"><i class="fas fa-eye"></i></a>';
                $html .= '<a href="admission_form.php?id=' . (int)$a['id'] . '" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>';
                $html .= '<a href="print_admission.php?id=' . (int)$a['id'] . '" class="action-btn action-btn-print" title="Print" target="_blank"><i class="fas fa-print"></i></a>';
                $html .= $deleteButton;
                $html .= '</div></td>';
                $html .= '</tr>';
            }

            echo json_encode([
                'success' => true,
                'rows' => $html,
                'pagination' => [
                    'page' => $page,
                    'totalPages' => $totalPages,
                    'total' => $total,
                ],
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

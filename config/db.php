<?php
/**
 * National College LMS - Database Configuration & Security Layer
 * Handles: DB connection, session management, CSRF, auth, input validation
 */

// Session configuration (must be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (30 min inactivity)
define('SESSION_TIMEOUT', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['timeout_message'] = 'Your session has expired. Please login again.';
}
$_SESSION['last_activity'] = time();

// Database Configuration
$host = 'localhost';
$dbname = 'national_college';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $db_username,
        $db_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die('<div style="text-align:center;padding:50px;font-family:Inter,sans-serif;">
         <h2 style="color:#E53E3E;">Database Connection Error</h2>
         <p>Please ensure MySQL is running and the <code>national_college</code> database exists.</p>
         <p>Run <code>database/schema.sql</code> and then <code>database/seed.php</code> to set up the database.</p></div>');
}

// ============================================
// Authentication Functions
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function hasAnyRole($roles) {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../index.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        http_response_code(403);
        die('<div style="text-align:center;padding:50px;font-family:Inter,sans-serif;">
             <h2 style="color:#E53E3E;">Access Denied</h2>
             <p>You do not have permission to access this page.</p>
             <a href="../index.php" style="color:#1a56db;">Return to Home</a></div>');
    }
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT id, name, email, role, status, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// ============================================
// CSRF Protection
// ============================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function csrfMeta() {
    echo '<meta name="csrf-token" content="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

// ============================================
// Input Validation & Sanitization
// ============================================

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateRequired($fields, $data) {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "$label is required.";
        }
    }
    return $errors;
}

// ============================================
// XSS Protection Helper
// ============================================

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================
// Flash Messages
// ============================================

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? 'fa-check-circle' : ($flash['type'] === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
        echo '<div class="alert alert-' . e($flash['type']) . '" id="flash-alert">
                <i class="fas ' . $icon . '"></i> ' . e($flash['message']) . '
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
              </div>';
    }
}

// ============================================
// Notification Helpers
// ============================================

function getUnreadNotificationCount($pdo, $userId = null) {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    }
    return $stmt->fetchColumn();
}

// ============================================
// Pagination Helper
// ============================================

function paginate($pdo, $query, $params, $page, $perPage = 15) {
    $countQuery = preg_replace('/SELECT .+? FROM/is', 'SELECT COUNT(*) FROM', $query);
    $countQuery = preg_replace('/ORDER BY .+$/i', '', $countQuery);
    $countQuery = preg_replace('/LIMIT .+$/i', '', $countQuery);
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages ?: 1));
    $offset = ($page - 1) * $perPage;
    
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'totalPages' => $totalPages,
        'perPage' => $perPage,
    ];
}

// ============================================
// Date Format Helper
// ============================================

function formatDate($date, $format = 'd M Y') {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>

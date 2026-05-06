<?php
session_start();

$host = 'localhost';
$dbname = 'national_college';
$username = 'root'; // default xampp username
$password = ''; // default xampp password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // We should not reveal DB details in production, but for local it's fine.
    // If DB doesn't exist, we might want to tell the user to run schema.sql
    die("Database connection failed: " . $e->getMessage());
}

// Function to check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}
?>

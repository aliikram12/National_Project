<?php
require 'config/db.php'; // Need db connection for updating login history

session_start();

if (isset($_SESSION['login_history_id'])) {
    try {
        $pdo->prepare("UPDATE login_history SET logout_time = NOW() WHERE id = ?")
            ->execute([$_SESSION['login_history_id']]);
    } catch(PDOException $e) {}
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
header("Location: index.php");
exit;
?>

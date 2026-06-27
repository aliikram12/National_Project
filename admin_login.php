<?php
require 'config/db.php';

// Handle login POST
if (isLoggedIn()) {
    redirect($_SESSION['user_role'] . '/index.php');
}

$error = $_SESSION['timeout_message'] ?? '';
unset($_SESSION['timeout_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token expired. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = 'admin'; // Hardcoded for this page

        if (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } elseif (empty($password)) {
            $error = "Password is required.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                // Record login history
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $pdo->prepare("INSERT INTO login_history (user_id, login_time, ip_address, user_agent) VALUES (?, NOW(), ?, ?)")
                    ->execute([$user['id'], $ip_address, $user_agent]);
                $_SESSION['login_history_id'] = $pdo->lastInsertId();

                redirect('admin/index.php');
            } else {
                $error = "Invalid credentials or account inactive.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | National College</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #0f172a; /* Dark background for admin */
            background-image: radial-gradient(circle at top right, #1e293b, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-wrapper {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .icon {
            font-size: 48px;
            color: var(--navy);
            margin-bottom: 16px;
        }
        .login-header h2 {
            color: var(--navy);
            margin: 0 0 8px;
        }
        .login-header p {
            color: var(--gray-500);
            font-size: 14px;
            margin: 0;
        }
        .admin-badge {
            display: inline-block;
            background: #eff6ff;
            color: var(--royal);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            border: 1px solid #bfdbfe;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--gray-700);
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--navy);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: #0f172a;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-header">
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
        <div class="admin-badge">Admin Portal</div>
        <h2>National College</h2>
        <p>Secure Administrator Login</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php csrfField(); ?>
        <input type="hidden" name="login" value="1">
        
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Administrator Email</label>
            <input type="email" name="email" class="form-control" required placeholder="Enter admin email">
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Enter your password">
        </div>
        
        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Secure Login</button>
    </form>
</div>

</body>
</html>

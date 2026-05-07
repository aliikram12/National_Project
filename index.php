<?php
require 'config/db.php';

if (isLoggedIn()) {
    redirect($_SESSION['user_role'] . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Validate user with specific role
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            redirect($user['role'] . '/index.php');
        } else {
            $error = "Invalid email, password, or role selected.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>National College | Login Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color), #0d3660);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .login-header h2 {
            font-size: 28px;
            color: var(--primary-color);
        }
        .login-header p {
            color: var(--light-text);
        }
        
        /* Demo credentials section */
        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
            font-size: 13px;
        }
        .demo-credentials h4 {
            margin-bottom: 10px;
            color: var(--primary-color);
            font-size: 15px;
        }
        .demo-credentials ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .demo-credentials li {
            margin-bottom: 5px;
            color: #555;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="fas fa-graduation-cap"></i>
        <h2>National College</h2>
        <p>Centralized Login Portal</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfField(); ?>
        
        <div class="form-group">
            <label for="role">Select Portal Role</label>
            <select name="role" id="role" class="form-control" required>
                <option value="admin">Administrator Panel</option>
                <option value="teacher">Teacher Panel</option>
                <option value="receptionist">Receptionist Panel</option>
            </select>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" required placeholder="Enter your email">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 16px; padding: 12px;">Login to Portal</button>
    </form>

    <div class="demo-credentials">
        <h4>Demo Login Credentials</h4>
        <ul>
            <li><strong>Admin:</strong> admin@national.edu / admin123</li>
            <li><strong>Teacher:</strong> teacher@national.edu / teacher123</li>
            <li><strong>Receptionist:</strong> reception@national.edu / reception123</li>
        </ul>
        <p style="margin-top: 10px; color: #888;">Dummy data (students, courses, slots) is already loaded for testing.</p>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | National College</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for Admin Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-graduation-cap"></i> National College
            </div>
            <div class="sidebar-nav">
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a href="courses.php"><i class="fas fa-book"></i> Courses</a>
                    <a href="slots.php"><i class="fas fa-clock"></i> Time Slots</a>
                    <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
                    <a href="pdf_templates.php"><i class="fas fa-file-pdf"></i> PDF Templates</a>
                <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
                    <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="assessments.php"><i class="fas fa-clipboard-list"></i> Assessments</a>
                <?php elseif ($_SESSION['user_role'] === 'receptionist'): ?>
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="admissions.php"><i class="fas fa-user-plus"></i> Admissions</a>
                    <a href="students.php"><i class="fas fa-user-graduate"></i> Students</a>
                    <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
                <?php endif; ?>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="topbar">
                <div>
                    <strong>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)</strong>
                </div>
                <div>
                    <a href="../logout.php" class="btn btn-primary" style="padding: 5px 15px; font-size: 14px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="content-area">

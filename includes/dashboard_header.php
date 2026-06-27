<?php if (!isLoggedIn()) { redirect('../index.php'); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="National College LMS - Learning & Admission Management System">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> | National College</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <?php csrfMeta(); ?>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-graduation-cap"></i>
                <span>National College</span>
            </div>
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo e($_SESSION['user_name']); ?></div>
                    <div class="user-role"><?php echo e($_SESSION['user_role']); ?></div>
                </div>
            </div>
            <div class="sidebar-nav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <div class="nav-label">Main</div>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <div class="nav-label">Admissions</div>
                    <a href="admissions.php" class="<?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> All Admissions</a>
                    <a href="admission_form.php" class="<?php echo $current_page == 'admission_form.php' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> New Admission</a>
                    <div class="nav-label">Management</div>
                    <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Users</a>
                    <a href="students.php" class="<?php echo $current_page == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Students</a>
                    <a href="courses.php" class="<?php echo $current_page == 'courses.php' ? 'active' : ''; ?>"><i class="fas fa-book-open"></i> Courses</a>
                    <a href="slots.php" class="<?php echo $current_page == 'slots.php' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Time Slots</a>
                    <a href="fee_packages.php" class="<?php echo $current_page == 'fee_packages.php' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Fee Packages</a>
                    <div class="nav-label">Reports</div>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-export"></i> Reports & Export</a>
                    <a href="login_history.php" class="<?php echo $current_page == 'login_history.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Login History</a>

                <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
                    <div class="nav-label">Main</div>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i> My Classes</a>
                    <div class="nav-label">Actions</div>
                    <a href="attendance.php" class="<?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="assessments.php" class="<?php echo $current_page == 'assessments.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Assessments</a>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> My Reports</a>

                <?php elseif ($_SESSION['user_role'] === 'receptionist'): ?>
                    <div class="nav-label">Main</div>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
                    <div class="nav-label">Admissions</div>
                    <a href="admissions.php" class="<?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> All Admissions</a>
                    <a href="admission_form.php" class="<?php echo $current_page == 'admission_form.php' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> New Admission</a>
                    <div class="nav-label">Management</div>
                    <a href="students.php" class="<?php echo $current_page == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Students</a>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Reports</a>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="flex items-center gap-2">
                    <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <div class="page-title"><?php
                        $titles = [
                            'index.php' => 'Dashboard',
                            'users.php' => 'User Management',
                            'students.php' => 'Student Management',
                            'courses.php' => 'Course Management',
                            'slots.php' => 'Time Slots',
                            'fee_packages.php' => 'Fee Packages',
                            'reports.php' => 'Reports & Export',
                            'admissions.php' => 'Admission Management',
                            'admission_form.php' => 'New Admission',
                            'print_admission.php' => 'Print Admission',
                            'student_profile.php' => 'Student Profile',
                            'attendance.php' => 'Attendance',
                            'assessments.php' => 'Assessments',
                        ];
                        echo $titles[$current_page] ?? 'Dashboard';
                    ?></div>
                </div>
                <div class="topbar-right">
                    <span class="topbar-date"><i class="far fa-calendar"></i> <?php echo date('D, M d, Y'); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-outline" style="color:var(--red);border-color:var(--red)"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            <div class="content-area">
                <?php renderFlash(); ?>

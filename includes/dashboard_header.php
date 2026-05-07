<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | National College</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-graduation-cap"></i> <span>National College</span>
            </div>
            
            <div class="sidebar-user">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <div style="font-size: 12px; color: var(--accent-color); text-transform: uppercase; letter-spacing: 0.05em;"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
                </div>
            </div>

            <div class="sidebar-nav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Overview</a>
                    <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> User Management</a>
                    <a href="courses.php" class="<?php echo $current_page == 'courses.php' ? 'active' : ''; ?>"><i class="fas fa-book-open"></i> Courses</a>
                    <a href="slots.php" class="<?php echo $current_page == 'slots.php' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Time Slots</a>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-export"></i> Reports & Export</a>
                    <a href="pdf_templates.php" class="<?php echo $current_page == 'pdf_templates.php' ? 'active' : ''; ?>"><i class="fas fa-file-pdf"></i> PDF Builder</a>
                
                <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i> My Classes</a>
                    <a href="attendance.php" class="<?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="assessments.php" class="<?php echo $current_page == 'assessments.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Assessments</a>
                
                <?php elseif ($_SESSION['user_role'] === 'receptionist'): ?>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="admissions.php" class="<?php echo $current_page == 'admissions.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> New Admission</a>
                    <a href="students.php" class="<?php echo $current_page == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Students List</a>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Export Data</a>
                <?php endif; ?>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <?php 
                        $titles = [
                            'index.php' => 'Dashboard Overview',
                            'users.php' => 'User Management',
                            'courses.php' => 'Course Settings',
                            'slots.php' => 'Time Slots Configuration',
                            'reports.php' => 'System Reports',
                            'pdf_templates.php' => 'PDF Template Builder',
                            'admissions.php' => 'New Student Admission',
                            'students.php' => 'Enrolled Students',
                            'attendance.php' => 'Mark Attendance',
                            'assessments.php' => 'Student Assessments'
                        ];
                        echo $titles[$current_page] ?? 'Dashboard Panel';
                    ?>
                </div>
                <div class="flex items-center" style="gap: 15px;">
                    <span style="color: var(--light-text); font-size: 14px;"><i class="far fa-calendar"></i> <?php echo date('l, M d, Y'); ?></span>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 16px; font-size: 13px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="content-area">

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
        $role = sanitizeInput($_POST['role'] ?? '');

        if (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } elseif (empty($password)) {
            $error = "Password is required.";
        } elseif (!in_array($role, ['admin','teacher','receptionist'])) {
            $error = "Invalid role selected.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                redirect($user['role'] . '/index.php');
            } else {
                $error = "Invalid credentials or account inactive.";
            }
        }
    }
}

$coursesForDisplay = [];
try {
    $coursesForDisplay = $pdo->query("SELECT name, duration, description FROM courses WHERE status='active' LIMIT 9")->fetchAll();
} catch(Exception $e) {}

// Stats for hero
$totalStudents = 0; $totalCourses = 0; $totalTeachers = 0;
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status='active'")->fetchColumn();
    $totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND status='active'")->fetchColumn();
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="National College - Premier Learning Management & Admission System. Empowering education through technology.">
    <title>National College | Learning Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="landing-nav" id="mainNav">
    <div class="container">
        <a href="#" class="logo"><i class="fas fa-graduation-cap"></i> National College</a>
        <button class="hamburger" id="hamburgerBtn" onclick="document.querySelector('.nav-links').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#features">Features</a>
            <a href="#courses">Courses</a>
            <a href="#contact">Contact</a>
            <button class="login-portal-btn" onclick="openLoginModal()">
                <i class="fas fa-sign-in-alt"></i> Login Portal
            </button>
        </div>
    </div>
</nav>

<!-- ===== LOGIN MODAL ===== -->
<div class="modal-overlay" id="loginModal">
    <div class="login-modal">
        <button class="modal-close" onclick="closeLoginModal()">&times;</button>
        <div class="modal-header">
            <div class="modal-icon"><i class="fas fa-graduation-cap"></i></div>
            <h2>Welcome Back</h2>
            <p>Sign in to your portal</p>
        </div>
        <div class="modal-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:16px;border-radius:10px"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="role-tabs">
                <div class="role-tab active" onclick="selectRole('admin',this)" data-email="admin@national.edu" data-pass="admin123">
                    <i class="fas fa-user-shield"></i> Admin
                </div>
                <div class="role-tab" onclick="selectRole('teacher',this)" data-email="ahmad@national.edu" data-pass="teacher123">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher
                </div>
                <div class="role-tab" onclick="selectRole('receptionist',this)" data-email="reception@national.edu" data-pass="reception123">
                    <i class="fas fa-concierge-bell"></i> Reception
                </div>
            </div>

            <form method="POST" class="modal-form" id="loginForm">
                <?php csrfField(); ?>
                <input type="hidden" name="login" value="1">
                <input type="hidden" name="role" id="modalRole" value="admin">
                <div class="form-group">
                    <label for="modalEmail"><i class="fas fa-envelope" style="margin-right:4px;color:#94a3b8"></i> Email Address</label>
                    <input type="email" name="email" id="modalEmail" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="modalPassword"><i class="fas fa-lock" style="margin-right:4px;color:#94a3b8"></i> Password</label>
                    <input type="password" name="password" id="modalPassword" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
            </form>
            <div class="modal-demo">
                <strong>Demo:</strong> admin@national.edu / admin123
            </div>
        </div>
    </div>
</div>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
    <div class="container">
        <div>
            <h1>Empowering Education<br>Through <span>Technology</span></h1>
            <p>National College's comprehensive Learning Management & Admission System. Streamline admissions, track attendance, manage assessments, and generate professional reports — all in one platform.</p>
            <div class="hero-actions">
                <a href="javascript:void(0)" onclick="openLoginModal()" class="btn-hero-primary"><i class="fas fa-rocket"></i> Get Started</a>
                <a href="#features" class="btn-hero-outline"><i class="fas fa-play-circle"></i> Learn More</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><h3><?php echo $totalStudents ?: '500'; ?>+</h3><p>Students Enrolled</p></div>
                <div class="hero-stat"><h3><?php echo $totalCourses ?: '12'; ?>+</h3><p>Active Courses</p></div>
                <div class="hero-stat"><h3><?php echo $totalTeachers ?: '20'; ?>+</h3><p>Expert Teachers</p></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-graphic">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
    </div>
</section>

<!-- ===== ABOUT ===== -->
<section class="section" id="about" style="background:#fff">
    <div class="container">
        <div class="section-header">
            <span class="label">About Us</span>
            <h2>Why National College?</h2>
            <p>We provide world-class education with modern tools and technology, preparing students for the digital future.</p>
        </div>
        <div class="grid-3">
            <div style="text-align:center;padding:24px">
                <div style="width:60px;height:60px;background:#eff6ff;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--royal)"><i class="fas fa-award"></i></div>
                <h4 style="margin-bottom:8px">Excellence in Education</h4>
                <p style="color:var(--gray-500);font-size:14px">Industry-aligned curriculum designed by expert professionals.</p>
            </div>
            <div style="text-align:center;padding:24px">
                <div style="width:60px;height:60px;background:#ecfdf5;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--green)"><i class="fas fa-users"></i></div>
                <h4 style="margin-bottom:8px">Expert Faculty</h4>
                <p style="color:var(--gray-500);font-size:14px">Learn from experienced teachers with real industry expertise.</p>
            </div>
            <div style="text-align:center;padding:24px">
                <div style="width:60px;height:60px;background:#fffbeb;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--gold)"><i class="fas fa-laptop-code"></i></div>
                <h4 style="margin-bottom:8px">Modern Infrastructure</h4>
                <p style="color:var(--gray-500);font-size:14px">State-of-the-art labs and digital learning management tools.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="section" id="features">
    <div class="container">
        <div class="section-header">
            <span class="label">Platform Features</span>
            <h2>Everything You Need</h2>
            <p>A comprehensive system designed for administrators, teachers, and reception staff.</p>
        </div>
        <div class="grid-3">
            <div class="feature-card">
                <div class="icon-box" style="background:#eff6ff;color:var(--royal)"><i class="fas fa-user-shield"></i></div>
                <h4>Admin Dashboard</h4>
                <p>Complete control over users, courses, students, slots, and analytics with powerful reporting tools.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box" style="background:#ecfdf5;color:var(--green)"><i class="fas fa-calendar-check"></i></div>
                <h4>Attendance System</h4>
                <p>Mark, edit, and track attendance with automatic struck-off rules for absent students.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box" style="background:#fef2f2;color:var(--red)"><i class="fas fa-clipboard-list"></i></div>
                <h4>Assessment Tracking</h4>
                <p>Add daily progress, assignment remarks, and exam grades for every student.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box" style="background:#fffbeb;color:var(--gold)"><i class="fas fa-user-plus"></i></div>
                <h4>Admission Management</h4>
                <p>Register new students, assign courses and slots, with instant PDF form generation.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box" style="background:#ecfeff;color:var(--cyan)"><i class="fas fa-file-pdf"></i></div>
                <h4>PDF & CSV Reports</h4>
                <p>Generate professional reports with advanced filters for attendance, assessments, and admissions.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box" style="background:#f5f3ff;color:#7c3aed"><i class="fas fa-shield-alt"></i></div>
                <h4>Secure & Role-Based</h4>
                <p>CSRF protection, password hashing, session management, and role-based access control.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== COURSES ===== -->
<section class="section" id="courses" style="background:#fff">
    <div class="container">
        <div class="section-header">
            <span class="label">Our Programs</span>
            <h2>Courses We Offer</h2>
            <p>Industry-relevant programs designed to build real-world skills.</p>
        </div>
        <div class="grid-3">
            <?php
            $icons = ['fa-code','fa-palette','fa-pen-ruler','fa-bullhorn','fa-search','fa-mobile-alt','fa-python','fa-php','fa-layer-group','fa-lock','fa-brain','fa-briefcase','fa-video'];
            foreach ($coursesForDisplay as $i => $c): ?>
            <div class="course-card">
                <div class="course-icon"><i class="fas <?php echo $icons[$i % count($icons)]; ?>"></i></div>
                <div>
                    <h4><?php echo e($c['name']); ?></h4>
                    <p><?php echo e($c['duration']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($coursesForDisplay)): ?>
                <p style="color:var(--gray-500);grid-column:1/-1;text-align:center">Run the database seeder to load courses.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="section" id="contact">
    <div class="container">
        <div class="section-header">
            <span class="label">Get in Touch</span>
            <h2>Contact Us</h2>
            <p>Have questions? Reach out to our admissions team.</p>
        </div>
        <div class="grid-3" style="max-width:800px;margin:0 auto">
            <div style="text-align:center;padding:24px">
                <i class="fas fa-map-marker-alt" style="font-size:28px;color:var(--royal);margin-bottom:12px"></i>
                <h4 style="font-size:15px;margin-bottom:6px">Address</h4>
                <p style="color:var(--gray-500);font-size:13px">123 Education Blvd, Lahore, Pakistan</p>
            </div>
            <div style="text-align:center;padding:24px">
                <i class="fas fa-phone-alt" style="font-size:28px;color:var(--royal);margin-bottom:12px"></i>
                <h4 style="font-size:15px;margin-bottom:6px">Phone</h4>
                <p style="color:var(--gray-500);font-size:13px">+92 300 1234567</p>
            </div>
            <div style="text-align:center;padding:24px">
                <i class="fas fa-envelope" style="font-size:28px;color:var(--royal);margin-bottom:12px"></i>
                <h4 style="font-size:15px;margin-bottom:6px">Email</h4>
                <p style="color:var(--gray-500);font-size:13px">info@nationalcollege.edu</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="landing-footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand">
                <h3><i class="fas fa-graduation-cap"></i> National College</h3>
                <p>Empowering students with industry-ready skills through modern education and cutting-edge technology since 2010.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#courses">Courses</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Courses</h4>
                <ul>
                    <?php foreach(array_slice($coursesForDisplay, 0, 5) as $c): ?>
                    <li><a href="#courses"><?php echo e($c['name']); ?></a></li>
                    <?php endforeach; ?>
                    <?php if(empty($coursesForDisplay)): ?>
                    <li><a href="#courses">Web Development</a></li>
                    <li><a href="#courses">Graphic Design</a></li>
                    <li><a href="#courses">Digital Marketing</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Info</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> <span>123 Education Blvd, Lahore, Pakistan</span></li>
                    <li><i class="fas fa-phone-alt"></i> <span>+92 300 1234567</span></li>
                    <li><i class="fas fa-envelope"></i> <span>info@nationalcollege.edu</span></li>
                    <li><i class="fas fa-clock"></i> <span>Mon - Sat: 8:00 AM - 5:00 PM</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            &copy; <?php echo date('Y'); ?> National College. All rights reserved. Built with excellence.
        </div>
    </div>
</footer>

<script>
// Navbar scroll effect
window.addEventListener('scroll', function(){
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
});

// Login Modal
function openLoginModal() {
    document.getElementById('loginModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) closeLoginModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLoginModal();
});

// Role selection
function selectRole(role, el) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('modalRole').value = role;
    document.getElementById('modalEmail').value = el.dataset.email || '';
    document.getElementById('modalPassword').value = el.dataset.pass || '';
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth',block:'start'}); }
    });
});

// Auto-open modal if there was a login error
<?php if ($error): ?>
document.addEventListener('DOMContentLoaded', function(){ openLoginModal(); });
<?php endif; ?>
</script>
</body>
</html>

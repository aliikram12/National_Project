<?php
/**
 * National College LMS - Admission Form
 * Create / Edit Admission
 * Accessible by: Admin & Receptionist
 */

require __DIR__ . '/../config/db.php';
@include __DIR__ . '/../database/run_migrations.php';

$user = getCurrentUser($pdo);
if (!$user || !in_array($user['role'], ['admin', 'receptionist'])) {
    redirect('../index.php');
}

$role = $user['role'];
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$editId = $isEdit ? (int)$_GET['id'] : 0;

// Fetch admission data if editing
$admission = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ?");
    $stmt->execute([$editId]);
    $admission = $stmt->fetch();
    if (!$admission) {
        setFlash('danger', 'Admission record not found.');
        redirect('admissions.php');
    }
}

// Get dynamic data
$courses = $pdo->query("SELECT id, code, name, duration, duration_months, fee FROM courses WHERE status='active' ORDER BY name")->fetchAll();
$slots = $pdo->query("SELECT id, time_range, duration FROM slots WHERE status='active' ORDER BY id")->fetchAll();
$feePackages = $pdo->query("SELECT id, name, total_fee, discount_percent, discount_amount FROM fee_packages WHERE status='active' ORDER BY name")->fetchAll();

// Generate next registration number
if (!$isEdit) {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM admissions");
    $count = $countStmt->fetchColumn();
    $regNum = 'NCT-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $errors = validateAdmission($_POST, $_FILES, $pdo, $editId);
        if (!empty($errors)) {
            foreach ($errors as $err) setFlash('danger', $err);
            $admission = $_POST; // repopulate
            $admission['student_photo'] = $_POST['existing_photo'] ?? '';
        } else {
            $photoName = $_POST['existing_photo'] ?? null;
            
            // Handle photo upload
            if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handlePhotoUpload($_FILES['student_photo'], $editId ?: null);
                if ($uploadResult['success']) {
                    $photoName = $uploadResult['filename'];
                    // Delete old photo if editing
                    if ($editId && $admission['student_photo'] && file_exists('../uploads/students/' . $admission['student_photo'])) {
                        @unlink('../uploads/students/' . $admission['student_photo']);
                    }
                } else {
                    setFlash('danger', $uploadResult['error']);
                    $admission = $_POST;
                    $admission['student_photo'] = $photoName;
                    include '../includes/dashboard_header.php';
                    renderForm($admission, $isEdit, $editId, $courses, $slots, $feePackages, $regNum ?? '', $role);
                    include '../includes/dashboard_footer.php';
                    exit;
                }
            }

            $data = [
                ':registration_number' => sanitizeInput($_POST['registration_number']),
                ':student_photo' => $photoName,
                ':sr_number' => sanitizeInput($_POST['sr_number'] ?? ''),
                ':course_id' => (int)$_POST['course_id'],
                ':date_of_admission' => $_POST['date_of_admission'],
                ':duration' => sanitizeInput($_POST['duration']),
                ':degree_type' => sanitizeInput($_POST['degree_type'] ?? 'Private'),
                ':session_start' => sanitizeInput($_POST['session_start']),
                ':session_end' => sanitizeInput($_POST['session_end']),
                ':time_slot_id' => (int)$_POST['time_slot_id'],
                ':fee_package_id' => !empty($_POST['fee_package_id']) ? (int)$_POST['fee_package_id'] : null,
                ':student_name' => sanitizeInput($_POST['student_name']),
                ':father_name' => sanitizeInput($_POST['father_name']),
                ':gender' => sanitizeInput($_POST['gender'] ?? 'Male'),
                ':date_of_birth' => $_POST['date_of_birth'],
                ':nationality' => sanitizeInput($_POST['nationality'] ?? 'Pakistani'),
                ':cnic' => sanitizeInput($_POST['cnic']),
                ':mailing_address' => sanitizeInput($_POST['mailing_address']),
                ':permanent_address' => sanitizeInput($_POST['permanent_address']),
                ':student_mobile' => sanitizeInput($_POST['student_mobile']),
                ':guardian_mobile' => sanitizeInput($_POST['guardian_mobile']),
                ':student_email' => !empty($_POST['student_email']) ? sanitizeInput($_POST['student_email']) : null,
                ':guardian_email' => !empty($_POST['guardian_email']) ? sanitizeInput($_POST['guardian_email']) : null,
                ':occupation' => !empty($_POST['occupation']) ? sanitizeInput($_POST['occupation']) : null,
                ':monthly_income' => !empty($_POST['monthly_income']) ? (float)$_POST['monthly_income'] : null,
                ':status' => sanitizeInput($_POST['status'] ?? 'active'),
            ];

            try {
                if ($isEdit) {
                    $data[':id'] = $editId;
                    $sql = "UPDATE admissions SET registration_number=:registration_number,
                            student_photo=:student_photo, sr_number=:sr_number,
                            course_id=:course_id, date_of_admission=:date_of_admission,
                            duration=:duration, degree_type=:degree_type,
                            session_start=:session_start, session_end=:session_end,
                            time_slot_id=:time_slot_id, fee_package_id=:fee_package_id,
                            student_name=:student_name, father_name=:father_name,
                            gender=:gender, date_of_birth=:date_of_birth,
                            nationality=:nationality, cnic=:cnic,
                            mailing_address=:mailing_address, permanent_address=:permanent_address,
                            student_mobile=:student_mobile, guardian_mobile=:guardian_mobile,
                            student_email=:student_email, guardian_email=:guardian_email,
                            occupation=:occupation, monthly_income=:monthly_income,
                            status=:status, updated_at=NOW() WHERE id=:id";
                    $pdo->prepare($sql)->execute($data);
                    setFlash('success', 'Admission record updated successfully.');
                } else {
                    $data[':created_by'] = $user['id'];
                    $sql = "INSERT INTO admissions (registration_number, student_photo, sr_number,
                            course_id, date_of_admission, duration, degree_type,
                            session_start, session_end, time_slot_id, fee_package_id,
                            student_name, father_name, gender, date_of_birth, nationality, cnic,
                            mailing_address, permanent_address, student_mobile, guardian_mobile,
                            student_email, guardian_email, occupation, monthly_income,
                            status, created_by) VALUES (
                            :registration_number, :student_photo, :sr_number,
                            :course_id, :date_of_admission, :duration, :degree_type,
                            :session_start, :session_end, :time_slot_id, :fee_package_id,
                            :student_name, :father_name, :gender, :date_of_birth, :nationality, :cnic,
                            :mailing_address, :permanent_address, :student_mobile, :guardian_mobile,
                            :student_email, :guardian_email, :occupation, :monthly_income,
                            :status, :created_by)";
                    $pdo->prepare($sql)->execute($data);
                    setFlash('success', 'Admission created successfully.');
                }
                redirect('admissions.php');
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'registration_number') !== false) {
                    setFlash('danger', 'Registration number already exists. Please use a different one.');
                } else {
                    setFlash('danger', 'Database error. Please try again. ' . $e->getMessage());
                }
                $admission = $_POST;
                $admission['student_photo'] = $photoName ?? ($admission['student_photo'] ?? '');
                include '../includes/dashboard_header.php';
                renderForm($admission, $isEdit, $editId, $courses, $slots, $feePackages, $regNum ?? '', $role);
                include '../includes/dashboard_footer.php';
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $delId = (int)$_POST['id'];
        $delStmt = $pdo->prepare("SELECT student_photo FROM admissions WHERE id = ?");
        $delStmt->execute([$delId]);
        $delRow = $delStmt->fetch();
        if ($delRow && $delRow['student_photo'] && file_exists('../uploads/students/' . $delRow['student_photo'])) {
            @unlink('../uploads/students/' . $delRow['student_photo']);
        }
        $pdo->prepare("DELETE FROM admissions WHERE id = ?")->execute([$delId]);
        setFlash('success', 'Admission deleted.');
        redirect('admissions.php');
    }
}

$pageTitle = $isEdit ? 'Edit Admission' : 'New Admission';

include '../includes/dashboard_header.php';
renderForm($admission, $isEdit, $editId, $courses, $slots, $feePackages, $regNum ?? '', $role);
include '../includes/dashboard_footer.php';

// Helper function
function validateAdmission($post, $files, $pdo, $editId) {
    $errors = [];
    
    $required = [
        'registration_number' => 'Registration Number',
        'course_id' => 'Programme / Course',
        'date_of_admission' => 'Date of Admission',
        'duration' => 'Duration',
        'time_slot_id' => 'Time Slot',
        'student_name' => 'Student Name',
        'father_name' => "Father's Name",
        'cnic' => 'CNIC Number',
        'student_mobile' => 'Student Mobile',
    ];
    
    foreach ($required as $field => $label) {
        if (empty($post[$field]) || trim($post[$field]) === '') {
            $errors[] = "$label is required.";
        }
    }
    
    // Photo validation (required for new)
    if (!$editId && empty($post['existing_photo'])) {
        if (!isset($files['student_photo']) || $files['student_photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Student photo is required.';
        }
    }
    
    // CNIC format
    if (!empty($post['cnic'])) {
        if (!preg_match('/^\d{5}-\d{7}-\d{1}$/', $post['cnic'])) {
            $errors[] = 'CNIC format is invalid. Use: XXXXX-XXXXXXX-X';
        }
    }
    
    // Mobile format
    if (!empty($post['student_mobile']) && !preg_match('/^03\d{9}$/', $post['student_mobile'])) {
        $errors[] = 'Student mobile must be 11 digits starting with 03.';
    }
    if (!empty($post['guardian_mobile']) && !preg_match('/^03\d{9}$/', $post['guardian_mobile'])) {
        $errors[] = "Guardian's mobile must be 11 digits starting with 03.";
    }
    
    // Email validation
    if (!empty($post['student_email']) && !filter_var($post['student_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid student email address.';
    }
    if (!empty($post['guardian_email']) && !filter_var($post['guardian_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid guardian email address.';
    }
    
    // Date validation
    if (!empty($post['date_of_birth'])) {
        $d = DateTime::createFromFormat('Y-m-d', $post['date_of_birth']);
        if (!$d || $d->format('Y-m-d') !== $post['date_of_birth']) {
            $errors[] = 'Invalid date of birth.';
        }
    }
    
    // Check registration number uniqueness
    if (!empty($post['registration_number'])) {
        $q = "SELECT id FROM admissions WHERE registration_number = ?" . ($editId ? " AND id != ?" : "");
        $stmt = $pdo->prepare($q);
        $params = $editId ? [$post['registration_number'], $editId] : [$post['registration_number']];
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'Registration number already exists.';
        }
    }
    
    return $errors;
}

function handlePhotoUpload($file, $editId = null) {
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $uploadDir = '../uploads/students/';
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Photo size must be less than 2MB.'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG and PNG images are allowed.'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . uniqid() . '_' . time() . '.' . $ext;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => false, 'error' => 'Failed to upload photo. Please try again.'];
    }
    
    return ['success' => true, 'filename' => $filename];
}

function renderForm($admission, $isEdit, $editId, $courses, $slots, $feePackages, $regNum, $role) {
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<link rel="stylesheet" href="../assets/css/admission.css">

<div class="admission-form-wrapper">
    <div class="admission-card">
        <div class="admission-card-header">
            <h3>
                <i class="fas fa-user-plus"></i>
                <?php echo $isEdit ? 'Edit Admission Record' : 'Create New Admission'; ?>
            </h3>
            <div class="header-actions">
                <a href="admissions.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>
        </div>
        <div class="admission-card-body">
            <form method="POST" id="admissionForm" enctype="multipart/form-data" novalidate>
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="existing_photo" id="existingPhoto" value="<?php echo e($admission['student_photo'] ?? ''); ?>">

                <!-- ===== SECTION 1: ADMISSION INFORMATION ===== -->
                <div class="admission-section">
                    <div class="admission-section-header">
                        <div class="section-icon"><i class="fas fa-file-signature"></i></div>
                        <h4>Admission Information</h4>
                    </div>
                    <div class="admission-section-body">
                        <div class="row gx-4">
                            <!-- Admission Fields -->
                            <div class="col-md-9 order-2 order-md-1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                                        <input type="text" name="registration_number" class="form-control fw-bold text-success" value="<?php echo e($admission['registration_number'] ?? ($regNum ?? '')); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sr Number</label>
                                        <input type="text" name="sr_number" class="form-control" value="<?php echo e($admission['sr_number'] ?? ''); ?>" placeholder="Auto or manual entry">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Programme / Course <span class="text-danger">*</span></label>
                                        <select name="course_id" class="form-select searchable" required>
                                            <option value="">— Select Programme —</option>
                                            <?php foreach($courses as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo ($admission['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?> (<?php echo e($c['duration']); ?>) - <?php echo e($c['code']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Admission <span class="text-danger">*</span></label>
                                        <input type="date" name="date_of_admission" class="form-control" value="<?php echo e($admission['date_of_admission'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duration <span class="text-danger">*</span></label>
                                        <select name="duration" class="form-select" required>
                                            <option value="">— Select —</option>
                                            <option value="3 Months" <?php echo ($admission['duration'] ?? '') === '3 Months' ? 'selected' : ''; ?>>3 Months</option>
                                            <option value="6 Months" <?php echo ($admission['duration'] ?? '') === '6 Months' ? 'selected' : ''; ?>>6 Months</option>
                                            <option value="1 Year" <?php echo ($admission['duration'] ?? '') === '1 Year' ? 'selected' : ''; ?>>1 Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Degree Type</label>
                                        <select name="degree_type" class="form-select">
                                            <option value="Private" <?php echo ($admission['degree_type'] ?? 'Private') === 'Private' ? 'selected' : ''; ?>>Private</option>
                                            <option value="Government" <?php echo ($admission['degree_type'] ?? '') === 'Government' ? 'selected' : ''; ?>>Government</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Time Slot <span class="text-danger">*</span></label>
                                        <select name="time_slot_id" class="form-select searchable" required>
                                            <option value="">— Select Slot —</option>
                                            <?php foreach($slots as $sl): ?>
                                            <option value="<?php echo $sl['id']; ?>" <?php echo ($admission['time_slot_id'] ?? '') == $sl['id'] ? 'selected' : ''; ?>><?php echo e($sl['time_range']); ?> (<?php echo e($sl['duration']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Session Start Month</label>
                                        <select name="session_start" class="form-select">
                                            <?php
                                            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                            $curMonth = $admission['session_start'] ?? date('F');
                                            foreach ($months as $m):
                                            ?>
                                            <option value="<?php echo $m; ?>" <?php echo $curMonth === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Session End Month</label>
                                        <select name="session_end" class="form-select">
                                            <?php
                                            $curEnd = $admission['session_end'] ?? date('F', strtotime('+3 months'));
                                            foreach ($months as $m):
                                            ?>
                                            <option value="<?php echo $m; ?>" <?php echo $curEnd === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo Upload -->
                            <div class="col-md-3 order-1 order-md-2 mb-4 d-flex flex-column align-items-center">
                                <label class="form-label w-100 text-center">Student Photo <span class="text-danger">*</span></label>
                                <div class="photo-upload-area <?php echo (!empty($admission['student_photo']) && file_exists('../uploads/students/' . ($admission['student_photo'] ?? ''))) ? 'has-photo' : ''; ?>" id="photoUploadArea">
                                    <input type="file" name="student_photo" id="studentPhotoInput" accept="image/jpeg,image/jpg,image/png" onchange="previewPhoto(event)">
                                    <div id="photoPreviewContainer">
                                        <?php if (!empty($admission['student_photo']) && file_exists('../uploads/students/' . $admission['student_photo'])): ?>
                                            <img src="../uploads/students/<?php echo e($admission['student_photo']); ?>" id="photoPreview" alt="Student Photo">
                                            <button type="button" class="photo-remove-btn" onclick="removePhoto()" title="Remove photo"><i class="fas fa-times"></i></button>
                                        <?php else: ?>
                                            <div class="photo-placeholder" id="photoPlaceholder">
                                                <i class="fas fa-camera mb-2 fs-2"></i>
                                                <span>Upload Photo</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2">JPG, PNG (Max 2MB)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION 2: STUDENT INFORMATION ===== -->
                <div class="admission-section mt-4">
                    <div class="admission-section-header">
                        <div class="section-icon"><i class="fas fa-user"></i></div>
                        <h4>Student Information</h4>
                    </div>
                    <div class="admission-section-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" name="student_name" class="form-control" value="<?php echo e($admission['student_name'] ?? ''); ?>" required placeholder="Full name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Father Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" class="form-control" value="<?php echo e($admission['father_name'] ?? ''); ?>" required placeholder="Father's full name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male" <?php echo ($admission['gender'] ?? 'Male') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($admission['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($admission['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?php echo e($admission['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?php echo e($admission['nationality'] ?? 'Pakistani'); ?>" placeholder="Pakistani">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CNIC Number <span class="text-danger">*</span></label>
                                <input type="text" name="cnic" class="form-control" value="<?php echo e($admission['cnic'] ?? ''); ?>" required placeholder="35202-1234567-1" maxlength="15" pattern="[0-9]{5}-[0-9]{7}-[0-9]{1}" title="Format: XXXXX-XXXXXXX-X">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION 3: CONTACT & ADDRESS ===== -->
                <div class="admission-section mt-4">
                    <div class="admission-section-header">
                        <div class="section-icon"><i class="fas fa-address-book"></i></div>
                        <h4>Contact & Address Information</h4>
                    </div>
                    <div class="admission-section-body">
                        <div class="mb-3">
                            <label class="form-label">Mailing Address</label>
                            <textarea name="mailing_address" class="form-control" rows="2" placeholder="Current mailing address"><?php echo e($admission['mailing_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2" placeholder="Permanent residential address"><?php echo e($admission['permanent_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Student Mobile <span class="text-danger">*</span></label>
                                <input type="text" name="student_mobile" class="form-control" value="<?php echo e($admission['student_mobile'] ?? ''); ?>" required placeholder="03XXXXXXXXX" pattern="03[0-9]{9}" maxlength="11">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Father / Guardian Mobile</label>
                                <input type="text" name="guardian_mobile" class="form-control" value="<?php echo e($admission['guardian_mobile'] ?? ''); ?>" placeholder="03XXXXXXXXX" pattern="03[0-9]{9}" maxlength="11">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Student Email</label>
                                <input type="email" name="student_email" class="form-control" value="<?php echo e($admission['student_email'] ?? ''); ?>" placeholder="student@example.com">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent / Guardian Email</label>
                                <input type="email" name="guardian_email" class="form-control" value="<?php echo e($admission['guardian_email'] ?? ''); ?>" placeholder="parent@example.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION 4: OCCUPATION & INCOME ===== -->
                <div class="admission-section mt-4">
                    <div class="admission-section-header">
                        <div class="section-icon"><i class="fas fa-briefcase"></i></div>
                        <h4>Occupation & Income</h4>
                    </div>
                    <div class="admission-section-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Father / Guardian Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="<?php echo e($admission['occupation'] ?? ''); ?>" placeholder="e.g. Business, Teacher">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monthly Income (Rs.)</label>
                                <input type="number" name="monthly_income" class="form-control" value="<?php echo e($admission['monthly_income'] ?? ''); ?>" placeholder="e.g. 50000" min="0" step="1000">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION 5: FEE PACKAGE ===== -->
                <div class="admission-section mt-4">
                    <div class="admission-section-header">
                        <div class="section-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <h4>Fee Package</h4>
                    </div>
                    <div class="admission-section-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Fee Package (Optional)</label>
                                <select name="fee_package_id" class="form-select searchable">
                                    <option value="">— None —</option>
                                    <?php foreach($feePackages as $fp): ?>
                                    <option value="<?php echo $fp['id']; ?>" data-fee="<?php echo $fp['total_fee']; ?>" <?php echo ($admission['fee_package_id'] ?? '') == $fp['id'] ? 'selected' : ''; ?>><?php echo e($fp['name']); ?> - Rs. <?php echo number_format($fp['total_fee']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== HIDDEN STATUS FIELD ===== -->
                <?php if ($isEdit): ?>
                <div class="admission-section mt-4 border border-warning border-dashed">
                    <div class="admission-section-body bg-light">
                        <div class="row">
                            <div class="col-md-4 mb-0">
                                <label class="form-label text-warning fw-bold">Admission Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo ($admission['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo ($admission['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="dropped" <?php echo ($admission['status'] ?? '') === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                                    <option value="transferred" <?php echo ($admission['status'] ?? '') === 'transferred' ? 'selected' : ''; ?>>Transferred</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="admissions.php" class="btn btn-outline-secondary px-4"><i class="fas fa-times me-2"></i> Cancel</a>
                    <?php if ($isEdit): ?>
                    <button type="button" class="btn btn-outline-danger px-4" onclick="confirmDelete(<?php echo $editId; ?>, '<?php echo e(addslashes($admission['student_name'])); ?>')"><i class="fas fa-trash me-2"></i> Delete</button>
                    <?php endif; ?>
                    <button type="submit" class="btn admission-btn-primary px-5" id="submitBtn">
                        <i class="fas fa-save me-2"></i> <?php echo $isEdit ? 'Update Admission' : 'Submit Admission'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="mb-3 text-danger">
                <i class="fas fa-exclamation-triangle fa-3x"></i>
            </div>
            <h4 class="modal-title mb-3">Delete Admission</h4>
            <p>Are you sure you want to delete the admission for <strong id="deleteStudentName"></strong>? This action cannot be undone.</p>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display:inline">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger px-4"><i class="fas fa-trash me-2"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function(){
    $('.searchable').select2({
        placeholder: 'Select an option...',
        allowClear: true,
        width: '100%',
        theme: "bootstrap-5"
    });
});

function previewPhoto(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (file.size > 2 * 1024 * 1024) {
        alert('Photo size must be less than 2MB.');
        event.target.value = '';
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        alert('Only JPG and PNG images are allowed.');
        event.target.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const container = document.getElementById('photoPreviewContainer');
        container.innerHTML = '<img src="' + e.target.result + '" id="photoPreview" alt="Student Photo" style="width:100%;height:100%;object-fit:cover;border-radius:10px">';
        const area = document.getElementById('photoUploadArea');
        area.classList.add('has-photo');
    };
    reader.readAsDataURL(file);
}

function removePhoto() {
    document.getElementById('existingPhoto').value = '';
    document.getElementById('studentPhotoInput').value = '';
    const container = document.getElementById('photoPreviewContainer');
    container.innerHTML = '<div class="photo-placeholder" id="photoPlaceholder"><i class="fas fa-camera mb-2 fs-2"></i><span>Upload Photo</span></div>';
    document.getElementById('photoUploadArea').classList.remove('has-photo');
}

function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteStudentName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Real-time CNIC validation
document.querySelector('input[name="cnic"]').addEventListener('input', function() {
    this.value = formatCNIC(this.value);
});

function formatCNIC(val) {
    val = val.replace(/[^0-9]/g, '');
    if (val.length > 13) val = val.substring(0, 13);
    if (val.length > 12) val = val.substring(0, 5) + '-' + val.substring(5, 12) + '-' + val.substring(12);
    else if (val.length > 5) val = val.substring(0, 5) + '-' + val.substring(5);
    return val;
}

// Mobile validation
document.querySelector('input[name="student_mobile"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
});
const gm = document.querySelector('input[name="guardian_mobile"]');
if (gm) {
    gm.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
    });
}

// Form validation on submit
document.getElementById('admissionForm').addEventListener('submit', function(e) {
    const requiredFields = [
        ['student_name', 'Student Name'],
        ['father_name', "Father's Name"],
        ['cnic', 'CNIC Number'],
        ['student_mobile', 'Student Mobile'],
        ['course_id', 'Programme / Course'],
        ['date_of_admission', 'Date of Admission'],
        ['duration', 'Duration'],
        ['time_slot_id', 'Time Slot']
    ];
    
    let valid = true;
    requiredFields.forEach(function(f) {
        const el = document.querySelector('[name="' + f[0] + '"]');
        if (el && !el.value.trim()) {
            el.classList.add('is-invalid');
            valid = false;
        } else if (el) {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        }
    });
    
    // Photo required for new admissions
    const existingPhoto = document.getElementById('existingPhoto').value;
    const studentPhotoInput = document.getElementById('studentPhotoInput').value;
    if (!existingPhoto && !studentPhotoInput) {
        alert("Student Photo is required.");
        valid = false;
    }

    // CNIC format validation
    const cnicEl = document.querySelector('[name="cnic"]');
    const cnicRegex = /^\d{5}-\d{7}-\d{1}$/;
    if (cnicEl && cnicEl.value && !cnicRegex.test(cnicEl.value)) {
        alert('CNIC format is invalid. Use: XXXXX-XXXXXXX-X');
        cnicEl.classList.add('is-invalid');
        valid = false;
    }
    
    // Mobile validation
    ['student_mobile', 'guardian_mobile'].forEach(function(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (el && el.value && !/^03\d{9}$/.test(el.value)) {
            alert(name === 'student_mobile' ? 'Student mobile must start with 03 and be 11 digits.' : "Guardian's mobile must start with 03 and be 11 digits.");
            el.classList.add('is-invalid');
            valid = false;
        }
    });
    
    if (!valid) {
        e.preventDefault();
        const firstError = document.querySelector('.is-invalid');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
<?php
}
?>

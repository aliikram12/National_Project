<?php
require '../config/db.php';
requireRole('receptionist');

$courses = $pdo->query("SELECT * FROM courses WHERE status='active'")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots WHERE status='active'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $name = sanitizeInput($_POST['name']);
    $father = sanitizeInput($_POST['father_name']);
    $dob = $_POST['dob'];
    $contact = sanitizeInput($_POST['contact']);
    $address = sanitizeInput($_POST['address']);
    $courseId = (int)$_POST['course_id'];
    $slotId = (int)$_POST['slot_id'];
    
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($father)) $errors[] = "Father's name is required.";
    if (!validateDate($dob)) $errors[] = 'Invalid date of birth.';
    if (!validatePhone($contact)) $errors[] = 'Invalid contact number.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (!$courseId) $errors[] = 'Select a course.';
    if (!$slotId) $errors[] = 'Select a time slot.';
    
    if ($errors) { setFlash('danger', implode(' ', $errors)); }
    else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO students (name, father_name, dob, contact, address) VALUES (?,?,?,?,?)")
                ->execute([$name, $father, $dob, $contact, $address]);
            $studentId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO enrollments (student_id, course_id, slot_id, enrollment_date) VALUES (?,?,?,CURDATE())")
                ->execute([$studentId, $courseId, $slotId]);
            $pdo->commit();
            redirect("print_admission.php?id=$studentId");
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Admission failed. Please try again.');
        }
    }
    redirect('admissions.php');
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="max-width:800px;margin:0 auto">
    <div class="card-header"><h3><i class="fas fa-user-plus" style="margin-right:8px;color:var(--royal)"></i> New Student Admission</h3></div>
    <form method="POST">
        <?php csrfField(); ?>
        <div class="form-row">
            <div class="form-group"><label>Student Name *</label><input type="text" name="name" class="form-control" required placeholder="Full name"></div>
            <div class="form-group"><label>Father's Name *</label><input type="text" name="father_name" class="form-control" required placeholder="Father's full name"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Date of Birth *</label><input type="date" name="dob" class="form-control" required></div>
            <div class="form-group"><label>Contact Number *</label><input type="text" name="contact" class="form-control" required placeholder="03XX-XXXXXXX"></div>
        </div>
        <div class="form-group"><label>Address *</label><input type="text" name="address" class="form-control" required placeholder="Full address"></div>
        <div class="form-row">
            <div class="form-group"><label>Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">— Select Course —</option>
                    <?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?> (<?php echo e($c['duration']); ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Time Slot *</label>
                <select name="slot_id" class="form-control" required>
                    <option value="">— Select Slot —</option>
                    <?php foreach($slots as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo e($s['time_range']); ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="text-center mt-3"><button class="btn btn-primary btn-lg"><i class="fas fa-check-circle"></i> Complete Admission & Print</button></div>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

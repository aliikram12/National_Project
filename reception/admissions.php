<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

$courses = $pdo->query("SELECT * FROM courses")->fetchAll();
$slots = $pdo->query("SELECT * FROM slots")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $name = $_POST['name'];
        $father_name = $_POST['father_name'];
        $dob = $_POST['dob'];
        $contact = $_POST['contact'];
        $address = $_POST['address'];
        $course_id = $_POST['course_id'];
        $slot_id = $_POST['slot_id'];
        $enrollment_date = date('Y-m-d');

        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO students (name, father_name, dob, contact, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $father_name, $dob, $contact, $address]);
            $student_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, slot_id, enrollment_date) VALUES (?, ?, ?, ?)");
            $stmt2->execute([$student_id, $course_id, $slot_id, $enrollment_date]);

            $pdo->commit();

            // Redirect to print page
            redirect("print_admission.php?id=" . $student_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to add admission: " . $e->getMessage();
        }
    }
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h3>New Admission Form</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Fill in the student details to register.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfField(); ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Student Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Father's Name *</label>
                <input type="text" name="father_name" class="form-control" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Date of Birth *</label>
                <input type="date" name="dob" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label>Address *</label>
            <input type="text" name="address" class="form-control" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Select Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['duration']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Select Time Slot *</label>
                <select name="slot_id" class="form-control" required>
                    <option value="">-- Choose Slot --</option>
                    <?php foreach ($slots as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['time_range']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-2 text-center">
            <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">Complete Admission & Print PDF</button>
        </div>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('../login.php');
}

$course_id = $_GET['course_id'] ?? null;
$slot_id = $_GET['slot_id'] ?? null;
$teacher_id = $_SESSION['user_id'];
$message = '';

if (!$course_id || !$slot_id) {
    die("Course and Slot are required.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_assessment') {
        $student_id = $_POST['student_id'];
        $date = $_POST['date'];
        $notes = $_POST['notes'];
        
        $stmt = $pdo->prepare("INSERT INTO assessments (student_id, teacher_id, date, notes) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $teacher_id, $date, $notes])) {
            $message = "Assessment added successfully.";
        } else {
            $message = "Failed to add assessment.";
        }
    }
}

// Fetch students
$stmt = $pdo->prepare("SELECT s.id, s.name FROM students s 
                       JOIN enrollments e ON s.id = e.student_id 
                       WHERE e.course_id = ? AND e.slot_id = ?");
$stmt->execute([$course_id, $slot_id]);
$students = $stmt->fetchAll();

// Fetch previous assessments for these students by this teacher
$assessments = [];
if ($students) {
    $student_ids = array_column($students, 'id');
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $params = array_merge($student_ids, [$teacher_id]);
    
    $astmt = $pdo->prepare("SELECT a.*, s.name as student_name FROM assessments a 
                            JOIN students s ON a.student_id = s.id 
                            WHERE a.student_id IN ($placeholders) AND a.teacher_id = ? ORDER BY a.date DESC");
    $astmt->execute($params);
    $assessments = $astmt->fetchAll();
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Add Student Assessment</h3>
    <?php if ($message): ?>
        <div class="alert alert-success mt-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" class="mt-2">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="add_assessment">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" class="form-control" required>
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Assessment Notes / Progress</label>
            <textarea name="notes" class="form-control" rows="4" required placeholder="Enter student progress notes here..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Assessment</button>
    </form>
</div>

<div class="card">
    <h3>Previous Assessments</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $assessment): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($assessment['student_name']); ?></strong></td>
                        <td><?php echo date('d M Y', strtotime($assessment['date'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($assessment['notes'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($assessments)): ?>
                    <tr><td colspan="3" class="text-center">No assessments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

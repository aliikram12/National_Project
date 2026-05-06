<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('../login.php');
}

$course_id = $_GET['course_id'] ?? null;
$slot_id = $_GET['slot_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

if (!$course_id || !$slot_id) {
    die("Course and Slot are required.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } elseif (isset($_POST['attendance'])) {
        $attendanceData = $_POST['attendance'];
        
        try {
            $pdo->beginTransaction();
            
            foreach ($attendanceData as $student_id => $status) {
                // Check if already marked for this date
                $checkStmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
                $checkStmt->execute([$student_id, $date]);
                $exists = $checkStmt->fetch();
                
                if ($exists) {
                    $updStmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
                    $updStmt->execute([$status, $exists['id']]);
                } else {
                    $insStmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
                    $insStmt->execute([$student_id, $date, $status]);
                }
                
                // If absent, check total absences this month
                if ($status === 'absent') {
                    $monthStart = date('Y-m-01', strtotime($date));
                    $monthEnd = date('Y-m-t', strtotime($date));
                    
                    $absStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'absent' AND date BETWEEN ? AND ?");
                    $absStmt->execute([$student_id, $monthStart, $monthEnd]);
                    $absentCount = $absStmt->fetchColumn();
                    
                    if ($absentCount >= 3) {
                        // Mark student as struck_off
                        $strkStmt = $pdo->prepare("UPDATE students SET status = 'struck_off' WHERE id = ?");
                        $strkStmt->execute([$student_id]);
                    }
                }
            }
            
            $pdo->commit();
            $message = "Attendance saved successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Failed to save attendance: " . $e->getMessage();
        }
    }
}

// Fetch students for this course and slot
$stmt = $pdo->prepare("SELECT s.id, s.name, s.status, s.contact FROM students s 
                       JOIN enrollments e ON s.id = e.student_id 
                       WHERE e.course_id = ? AND e.slot_id = ?");
$stmt->execute([$course_id, $slot_id]);
$students = $stmt->fetchAll();

// Fetch already marked attendance for today
$attStmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date = ?");
$attStmt->execute([$date]);
$markedAttendanceRaw = $attStmt->fetchAll();
$markedAttendance = [];
foreach ($markedAttendanceRaw as $ma) {
    $markedAttendance[$ma['student_id']] = $ma['status'];
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Mark Attendance</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Rule: If a student is absent 3 times in a month, they will be automatically struck off.</p>

    <?php if ($message): ?>
        <div class="alert alert-success mt-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <form method="GET" action="" style="display: flex; gap: 15px; align-items: end; margin-bottom: 20px;">
        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
        <input type="hidden" name="slot_id" value="<?php echo htmlspecialchars($slot_id); ?>">
        
        <div class="form-group" style="margin-bottom: 0;">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 46px;">Load Students</button>
    </form>
</div>

<div class="card">
    <form method="POST" action="">
        <?php csrfField(); ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Contact</th>
                        <th>Current Status</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['contact']); ?></td>
                            <td>
                                <?php if ($student['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Struck Off</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $currentStatus = $markedAttendance[$student['id']] ?? 'present'; ?>
                                <select name="attendance[<?php echo $student['id']; ?>]" class="form-control" style="width: auto;" <?php echo $student['status'] === 'struck_off' ? 'disabled' : ''; ?>>
                                    <option value="present" <?php echo $currentStatus === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $currentStatus === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="leave" <?php echo $currentStatus === 'leave' ? 'selected' : ''; ?>>Leave</option>
                                </select>
                                <?php if ($student['status'] === 'struck_off'): ?>
                                    <small style="color: red; margin-left: 10px;">Requires Re-Admission</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="4" class="text-center">No students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($students)): ?>
            <div class="mt-2 text-right" style="text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">Save Attendance</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

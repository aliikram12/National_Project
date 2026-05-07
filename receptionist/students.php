<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

$search = $_GET['search'] ?? '';

$query = "SELECT s.*, c.name as course_name, sl.time_range 
          FROM students s 
          LEFT JOIN enrollments e ON s.id = e.student_id 
          LEFT JOIN courses c ON e.course_id = c.id 
          LEFT JOIN slots sl ON e.slot_id = sl.id";

if ($search) {
    $query .= " WHERE s.name LIKE ? OR s.contact LIKE ? OR c.name LIKE ?";
    $stmt = $pdo->prepare($query . " ORDER BY s.id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $students = $stmt->fetchAll();
} else {
    $query .= " ORDER BY s.id DESC";
    $students = $pdo->query($query)->fetchAll();
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Students List</h2>
    <form method="GET" style="display: flex; gap: 10px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name, contact, course..." value="<?php echo htmlspecialchars($search); ?>" style="width: 300px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Reg ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Course</th>
                    <th>Slot</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td>NC-<?php echo date('Y', strtotime($student['created_at'])); ?>-<?php echo str_pad($student['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['contact']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['time_range']); ?></td>
                        <td>
                            <?php if ($student['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Struck Off</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="print_admission.php?id=<?php echo $student['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" target="_blank"><i class="fas fa-print"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" class="text-center">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $duration = $_POST['duration'];
            $description = $_POST['description'];

            $stmt = $pdo->prepare("INSERT INTO courses (name, duration, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $duration, $description]);
            $message = "Course added successfully.";
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Course deleted successfully.";
        }
    }
}

$courses = $pdo->query("SELECT * FROM courses")->fetchAll();

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Course Management</h3>
    <?php if ($message): ?>
        <div class="alert alert-success mt-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" class="mt-2" style="display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 15px; align-items: end;">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="add">
        
        <div class="form-group" style="margin-bottom: 0;">
            <label>Course Name</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Computer Science">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Duration</label>
            <input type="text" name="duration" class="form-control" required placeholder="e.g. 6 Months">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Description</label>
            <input type="text" name="description" class="form-control" placeholder="Brief description">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 46px;">Add Course</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($course['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($course['duration']); ?></td>
                        <td><?php echo htmlspecialchars($course['description']); ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 5px 10px; background: #dc3545; font-size: 12px;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } elseif (isset($_POST['content'])) {
        $content = $_POST['content'];
        $stmt = $pdo->prepare("UPDATE pdf_templates SET content = ? WHERE name = 'admission_form'");
        if ($stmt->execute([$content])) {
            $message = "Template updated successfully.";
        }
    }
}

$templateStmt = $pdo->query("SELECT content FROM pdf_templates WHERE name='admission_form'");
$templateRow = $templateStmt->fetch();
$template = $templateRow ? $templateRow['content'] : '';

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card">
    <h3>PDF Template Editor (Admission Form)</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Use HTML to design the PDF template. Placeholders: <code>{student_name}</code>, <code>{father_name}</code>, <code>{dob}</code>, <code>{contact}</code>, <code>{address}</code>, <code>{course_name}</code>, <code>{duration}</code>, <code>{time_slot}</code>, <code>{enrollment_date}</code></p>
    
    <?php if ($message): ?>
        <div class="alert alert-success mt-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfField(); ?>
        <div class="form-group">
            <textarea name="content" class="form-control" rows="15" style="font-family: monospace;" required><?php echo htmlspecialchars($template); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Template</button>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

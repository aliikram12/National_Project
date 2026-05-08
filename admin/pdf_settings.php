<?php
require '../config/db.php';
requireRole('admin');

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM pdf_settings WHERE id = 1");
$settings = $stmt->fetch();

if (!$settings) {
    $pdo->query("INSERT INTO pdf_settings (id) VALUES (1)");
    $settings = $pdo->query("SELECT * FROM pdf_settings WHERE id = 1")->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $college_name = sanitizeInput($_POST['college_name']);
    $college_address = sanitizeInput($_POST['college_address']);
    $college_phone = sanitizeInput($_POST['college_phone']);
    $college_email = sanitizeInput($_POST['college_email']);
    $footer_text = sanitizeInput($_POST['footer_text']);
    $watermark_text = sanitizeInput($_POST['watermark_text']);
    $header_color = sanitizeInput($_POST['header_color']);
    $table_header_bg = sanitizeInput($_POST['table_header_bg']);
    $table_header_color = sanitizeInput($_POST['table_header_color']);
    $table_border_color = sanitizeInput($_POST['table_border_color']);
    
    $show_logo = isset($_POST['show_logo']) ? 1 : 0;
    $show_watermark = isset($_POST['show_watermark']) ? 1 : 0;
    $show_signature = isset($_POST['show_signature']) ? 1 : 0;

    // Handle logo upload
    $college_logo = $settings['college_logo'];
    if (isset($_FILES['college_logo']) && $_FILES['college_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['college_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = '../assets/img/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['college_logo']['tmp_name'], $upload_dir . $new_filename)) {
                $college_logo = $new_filename;
            }
        } else {
            setFlash('danger', 'Invalid file type. Only JPG and PNG allowed.');
        }
    }

    $updateStmt = $pdo->prepare("UPDATE pdf_settings SET 
        college_name = ?, college_logo = ?, college_address = ?, college_phone = ?, college_email = ?,
        footer_text = ?, watermark_text = ?, header_color = ?, table_header_bg = ?, table_header_color = ?,
        table_border_color = ?, show_logo = ?, show_watermark = ?, show_signature = ? WHERE id = 1");
    
    $success = $updateStmt->execute([
        $college_name, $college_logo, $college_address, $college_phone, $college_email,
        $footer_text, $watermark_text, $header_color, $table_header_bg, $table_header_color,
        $table_border_color, $show_logo, $show_watermark, $show_signature
    ]);

    if ($success) {
        setFlash('success', 'PDF settings updated successfully.');
        redirect('pdf_settings.php');
    } else {
        setFlash('danger', 'Failed to update settings.');
    }
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="max-width:900px; margin:0 auto;">
    <div class="card-header">
        <h3><i class="fas fa-file-pdf" style="margin-right:8px;color:var(--red)"></i> PDF Export Settings</h3>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <?php csrfField(); ?>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <!-- Column 1: Header Info -->
            <div>
                <h4 style="margin-bottom:16px;color:var(--navy);border-bottom:1px solid var(--gray-200);padding-bottom:8px;">Header Information</h4>
                
                <div class="form-group">
                    <label>College Name</label>
                    <input type="text" name="college_name" class="form-control" value="<?php echo e($settings['college_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>College Logo</label>
                    <?php if($settings['college_logo']): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../assets/img/<?php echo e($settings['college_logo']); ?>" style="max-height:60px; border-radius:4px; border:1px solid var(--gray-200); padding:4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="college_logo" class="form-control" accept="image/jpeg,image/png">
                    <small style="color:var(--gray-500)">Leave blank to keep current logo. Recommended height: 60px.</small>
                </div>
                
                <div class="form-group">
                    <label>College Address</label>
                    <textarea name="college_address" class="form-control" rows="2" required><?php echo e($settings['college_address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="college_phone" class="form-control" value="<?php echo e($settings['college_phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="college_email" class="form-control" value="<?php echo e($settings['college_email']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Column 2: Styling & Options -->
            <div>
                <h4 style="margin-bottom:16px;color:var(--navy);border-bottom:1px solid var(--gray-200);padding-bottom:8px;">Styling & Colors</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Header Text Color</label>
                        <input type="color" name="header_color" class="form-control" style="height:44px;padding:4px" value="<?php echo e($settings['header_color']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Table Border Color</label>
                        <input type="color" name="table_border_color" class="form-control" style="height:44px;padding:4px" value="<?php echo e($settings['table_border_color']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Table Header BG</label>
                        <input type="color" name="table_header_bg" class="form-control" style="height:44px;padding:4px" value="<?php echo e($settings['table_header_bg']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Table Header Text</label>
                        <input type="color" name="table_header_color" class="form-control" style="height:44px;padding:4px" value="<?php echo e($settings['table_header_color']); ?>">
                    </div>
                </div>
                
                <h4 style="margin:24px 0 16px;color:var(--navy);border-bottom:1px solid var(--gray-200);padding-bottom:8px;">Additional Settings</h4>
                
                <div class="form-group">
                    <label>Footer Text</label>
                    <input type="text" name="footer_text" class="form-control" value="<?php echo e($settings['footer_text']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Watermark Text</label>
                    <input type="text" name="watermark_text" class="form-control" value="<?php echo e($settings['watermark_text']); ?>">
                </div>
                
                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:20px;background:var(--gray-50);padding:16px;border-radius:8px;border:1px solid var(--gray-200);">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--gray-700)">
                        <input type="checkbox" name="show_logo" value="1" <?php echo $settings['show_logo'] ? 'checked' : ''; ?>> Show Logo
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--gray-700)">
                        <input type="checkbox" name="show_watermark" value="1" <?php echo $settings['show_watermark'] ? 'checked' : ''; ?>> Enable Watermark
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--gray-700)">
                        <input type="checkbox" name="show_signature" value="1" <?php echo $settings['show_signature'] ? 'checked' : ''; ?>> Show Signature Area
                    </label>
                </div>
            </div>
        </div>
        
        <div style="text-align:right; margin-top:32px; padding-top:20px; border-top:1px solid var(--gray-200);">
            <a href="reports.php" class="btn btn-outline" style="margin-right:12px;">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Settings</button>
        </div>
    </form>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

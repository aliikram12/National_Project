<?php
require '../config/db.php';
requireRole('admin');

// Pre-fetch reference data for filters
$courses = $pdo->query("SELECT id, name FROM courses")->fetchAll();
$slots = $pdo->query("SELECT id, time_range FROM slots")->fetchAll();
$teachers = $pdo->query("SELECT id, name FROM users WHERE role='teacher'")->fetchAll();

// Get filters
$reportType = $_GET['type'] ?? 'admissions';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default to 1st of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-t'); // Default to last day of current month
$courseId = $_GET['course_id'] ?? '';
$slotId = $_GET['slot_id'] ?? '';
$teacherId = $_GET['teacher_id'] ?? '';
$status = $_GET['status'] ?? '';

// Build Query based on Report Type
$params = [];
$query = "";

if ($reportType === 'admissions') {
    $query = "SELECT s.id, s.name, s.father_name, s.contact, c.name as course, sl.time_range as slot, e.enrollment_date, s.status 
              FROM students s 
              JOIN enrollments e ON s.id=e.student_id 
              JOIN courses c ON e.course_id=c.id 
              JOIN slots sl ON e.slot_id=sl.id 
              WHERE e.enrollment_date BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($courseId) { $query .= " AND e.course_id = ?"; $params[] = $courseId; }
    if ($slotId) { $query .= " AND e.slot_id = ?"; $params[] = $slotId; }
    if ($status) { $query .= " AND s.status = ?"; $params[] = $status; }
    
    $query .= " ORDER BY e.enrollment_date DESC";
    
} elseif ($reportType === 'attendance') {
    $query = "SELECT s.name as student, c.name as course, sl.time_range as slot, a.date, a.status, u.name as marked_by 
              FROM attendance a 
              JOIN students s ON a.student_id=s.id 
              JOIN courses c ON a.course_id=c.id 
              JOIN slots sl ON a.slot_id=sl.id
              LEFT JOIN users u ON a.marked_by=u.id
              WHERE a.date BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($courseId) { $query .= " AND a.course_id = ?"; $params[] = $courseId; }
    if ($slotId) { $query .= " AND a.slot_id = ?"; $params[] = $slotId; }
    if ($teacherId) { $query .= " AND a.marked_by = ?"; $params[] = $teacherId; }
    if ($status) { $query .= " AND a.status = ?"; $params[] = $status; }
    
    $query .= " ORDER BY a.date DESC, s.name ASC";

} elseif ($reportType === 'assessments') {
    $query = "SELECT s.name as student, u.name as teacher, c.name as course, a.date, a.assessment_type, a.grade, a.notes 
              FROM assessments a 
              JOIN students s ON a.student_id=s.id 
              JOIN users u ON a.teacher_id=u.id 
              JOIN courses c ON a.course_id=c.id 
              WHERE a.date BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($courseId) { $query .= " AND a.course_id = ?"; $params[] = $courseId; }
    if ($teacherId) { $query .= " AND a.teacher_id = ?"; $params[] = $teacherId; }
    
    $query .= " ORDER BY a.date DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Handle Export
if (isset($_GET['export']) && !empty($results)) {
    $exportFormat = $_GET['export'];
    
    if ($exportFormat === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=report_' . $reportType . '_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        
        // Write headers based on first row keys
        if (!empty($results)) {
            fputcsv($out, array_map('ucfirst', array_map(function($key) { return str_replace('_', ' ', $key); }, array_keys($results[0]))));
            foreach ($results as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    } 
    elseif ($exportFormat === 'pdf') {
        require_once '../includes/pdf_helper.php';
        
        $pdf = new NationalCollegePDF($pdo);
        $pdf->AddPage();
        $settings = $pdf->getSettings();
        
        // Report Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0,0,0);
        $title = ucfirst($reportType) . " Report";
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, "Period: " . formatDate($dateFrom) . " to " . formatDate($dateTo), 0, 1, 'C');
        
        if ($courseId || $slotId || $teacherId || $status) {
            $filters = [];
            if ($courseId) {
                $cname = $pdo->prepare("SELECT name FROM courses WHERE id=?"); $cname->execute([$courseId]);
                $filters[] = "Course: " . $cname->fetchColumn();
            }
            if ($slotId) {
                $sname = $pdo->prepare("SELECT time_range FROM slots WHERE id=?"); $sname->execute([$slotId]);
                $filters[] = "Slot: " . $sname->fetchColumn();
            }
            if ($teacherId) {
                $tname = $pdo->prepare("SELECT name FROM users WHERE id=?"); $tname->execute([$teacherId]);
                $filters[] = "Teacher: " . $tname->fetchColumn();
            }
            if ($status) $filters[] = "Status: " . ucfirst($status);
            
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 6, "Filters applied: " . implode(" | ", $filters), 0, 1, 'C');
        }
        
        $pdf->Ln(5);
        
        // Table HTML
        list($r, $g, $b) = sscanf($settings['table_header_bg'], "#%02x%02x%02x");
        list($tr, $tg, $tb) = sscanf($settings['table_header_color'], "#%02x%02x%02x");
        
        $html = '<table cellpadding="5" cellspacing="0" style="width:100%; border: 1px solid '.$settings['table_border_color'].'; font-size:9pt;">';
        $html .= '<tr style="background-color:'.$settings['table_header_bg'].'; color:'.$settings['table_header_color'].'; font-weight:bold;">';
        
        $headers = array_keys($results[0]);
        foreach ($headers as $h) {
            $html .= '<th style="border-bottom: 1px solid '.$settings['table_border_color'].';">' . ucfirst(str_replace('_', ' ', $h)) . '</th>';
        }
        $html .= '</tr>';
        
        $fill = false;
        foreach ($results as $row) {
            $bgcolor = $fill ? '#f9fafb' : '#ffffff';
            $html .= '<tr style="background-color:'.$bgcolor.';">';
            foreach ($row as $val) {
                $html .= '<td style="border-bottom: 1px solid '.$settings['table_border_color'].';">' . htmlspecialchars($val ?? '') . '</td>';
            }
            $html .= '</tr>';
            $fill = !$fill;
        }
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Signatures
        if ($settings['show_signature']) {
            $pdf->Ln(20);
            $y = $pdf->GetY();
            if ($y > ($pdf->getPageHeight() - 60)) {
                $pdf->AddPage();
                $y = $pdf->GetY();
            }
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetDrawColor(0,0,0);
            
            $pdf->Line(20, $y, 70, $y);
            $pdf->SetXY(20, $y + 2);
            $pdf->Cell(50, 5, 'Prepared By', 0, 0, 'C');
            
            $pdf->Line(140, $y, 190, $y);
            $pdf->SetXY(140, $y + 2);
            $pdf->Cell(50, 5, 'Authorized Signature', 0, 0, 'C');
        }
        
        $pdf->Output('report_' . $reportType . '_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <h2>Advanced Reports Dashboard</h2>
    <a href="pdf_settings.php" class="btn btn-outline"><i class="fas fa-cog"></i> PDF Customization</a>
</div>

<!-- FILTER PANEL -->
<div class="report-filters">
    <h4><i class="fas fa-filter" style="color:var(--royal)"></i> Report Parameters</h4>
    <form method="GET" id="reportForm">
        <div class="filter-grid">
            <div class="form-group" style="margin-bottom:0">
                <label>Report Type</label>
                <select name="type" class="form-control" onchange="document.getElementById('reportForm').submit()">
                    <option value="admissions" <?php echo $reportType==='admissions'?'selected':''; ?>>Admissions Report</option>
                    <option value="attendance" <?php echo $reportType==='attendance'?'selected':''; ?>>Attendance Report</option>
                    <option value="assessments" <?php echo $reportType==='assessments'?'selected':''; ?>>Assessments Report</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo e($dateFrom); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo e($dateTo); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Course</label>
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $courseId==$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($reportType !== 'assessments'): ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Slot</label>
                <select name="slot_id" class="form-control">
                    <option value="">All Slots</option>
                    <?php foreach($slots as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo $slotId==$s['id']?'selected':''; ?>><?php echo e($s['time_range']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($reportType !== 'admissions'): ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Teacher</label>
                <select name="teacher_id" class="form-control">
                    <option value="">All Teachers</option>
                    <?php foreach($teachers as $t): ?><option value="<?php echo $t['id']; ?>" <?php echo $teacherId==$t['id']?'selected':''; ?>><?php echo e($t['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($reportType === 'admissions'): ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status==='active'?'selected':''; ?>>Active</option>
                    <option value="struck_off" <?php echo $status==='struck_off'?'selected':''; ?>>Struck Off</option>
                    <option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
                </select>
            </div>
            <?php elseif ($reportType === 'attendance'): ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Attendance</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="present" <?php echo $status==='present'?'selected':''; ?>>Present</option>
                    <option value="absent" <?php echo $status==='absent'?'selected':''; ?>>Absent</option>
                    <option value="leave" <?php echo $status==='leave'?'selected':''; ?>>Leave</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary" style="height:44px;flex:1"><i class="fas fa-search"></i> Generate</button>
                <a href="reports.php" class="btn btn-outline" style="height:44px"><i class="fas fa-times"></i></a>
            </div>
        </div>
    </form>
</div>

<!-- RESULTS SECTION -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table" style="margin-right:8px;color:var(--navy)"></i> Results (<?php echo count($results); ?> Records)</h3>
        
        <?php if (!empty($results)): ?>
        <div class="export-toolbar">
            <button onclick="exportData('csv')" class="btn btn-sm btn-success"><i class="fas fa-file-csv"></i> Export CSV</button>
            <button onclick="exportData('pdf')" class="btn btn-sm" style="background:#dc2626;color:#fff"><i class="fas fa-file-pdf"></i> Export PDF</button>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <?php if (empty($results)): ?>
            <div class="empty-state">
                <i class="fas fa-search" style="font-size:36px"></i>
                <p>No records found for the selected criteria.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($results[0]) as $header): ?>
                            <th><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $header))); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <?php foreach ($row as $col => $val): ?>
                                <td>
                                    <?php 
                                    if ($col === 'status') {
                                        $badge = 'badge-gray';
                                        if ($val === 'active' || $val === 'present') $badge = 'badge-success';
                                        elseif ($val === 'struck_off' || $val === 'absent') $badge = 'badge-danger';
                                        elseif ($val === 'leave') $badge = 'badge-warning';
                                        echo "<span class='badge {$badge}'>".ucfirst($val)."</span>";
                                    } elseif ($col === 'enrollment_date' || $col === 'date') {
                                        echo formatDate($val);
                                    } else {
                                        echo htmlspecialchars($val ?? '—');
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function exportData(format) {
    const url = new URL(window.location.href);
    url.searchParams.set('export', format);
    window.location.href = url.toString();
}
</script>

<?php include '../includes/dashboard_footer.php'; ?>

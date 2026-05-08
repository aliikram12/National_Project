<?php
require '../config/db.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// Pre-fetch reference data
$stmt = $pdo->prepare("SELECT c.id, c.name FROM course_teachers ct JOIN courses c ON ct.course_id=c.id WHERE ct.teacher_id=?");
$stmt->execute([$teacher_id]);
$courses = $stmt->fetchAll();

// Get filters
$reportType = $_GET['type'] ?? 'attendance';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-t');
$courseId = $_GET['course_id'] ?? '';
$status = $_GET['status'] ?? '';

// Build Query
$params = [];
$query = "";

if ($reportType === 'attendance') {
    $query = "SELECT s.name as student, c.name as course, a.date, a.status 
              FROM attendance a 
              JOIN students s ON a.student_id=s.id 
              JOIN courses c ON a.course_id=c.id 
              WHERE a.marked_by = ? AND a.date BETWEEN ? AND ?";
    $params[] = $teacher_id;
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($courseId) { $query .= " AND a.course_id = ?"; $params[] = $courseId; }
    if ($status) { $query .= " AND a.status = ?"; $params[] = $status; }
    
    $query .= " ORDER BY a.date DESC, s.name ASC";

} elseif ($reportType === 'assessments') {
    $query = "SELECT s.name as student, c.name as course, a.date, a.assessment_type, a.grade, a.notes 
              FROM assessments a 
              JOIN students s ON a.student_id=s.id 
              JOIN courses c ON a.course_id=c.id 
              WHERE a.teacher_id = ? AND a.date BETWEEN ? AND ?";
    $params[] = $teacher_id;
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($courseId) { $query .= " AND a.course_id = ?"; $params[] = $courseId; }
    
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
        header('Content-Disposition: attachment; filename=my_' . $reportType . '_' . date('Ymd_His') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_map('ucfirst', array_map(function($key) { return str_replace('_', ' ', $key); }, array_keys($results[0]))));
        foreach ($results as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    } 
    elseif ($exportFormat === 'pdf') {
        require_once '../includes/pdf_helper.php';
        $pdf = new NationalCollegePDF($pdo);
        $pdf->AddPage();
        $settings = $pdf->getSettings();
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0,0,0);
        $title = "My " . ucfirst($reportType) . " Report";
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, "Period: " . formatDate($dateFrom) . " to " . formatDate($dateTo), 0, 1, 'C');
        
        list($r, $g, $b) = sscanf($settings['table_header_bg'], "#%02x%02x%02x");
        list($tr, $tg, $tb) = sscanf($settings['table_header_color'], "#%02x%02x%02x");
        
        // Define widths based on report type
        $widths = [];
        if ($reportType === 'attendance') {
            $widths = ['#' => '5%', 'Student' => '30%', 'Course' => '30%', 'Date' => '20%', 'Status' => '15%'];
        } elseif ($reportType === 'assessments') {
            $widths = ['#' => '5%', 'Student' => '20%', 'Course' => '20%', 'Date' => '15%', 'Assessment Type' => '15%', 'Grade' => '5%', 'Notes' => '20%'];
        } else {
            $widths = ['#' => '5%'];
        }
        
        $html = '<table cellpadding="6" cellspacing="0" style="width:100%; border: 1px solid '.$settings['table_border_color'].'; font-size:9pt; text-align:left;">';
        $html .= '<tr style="background-color:'.$settings['table_header_bg'].'; color:'.$settings['table_header_color'].'; font-weight:bold;">';
        
        $headers = array_keys($results[0]);
        $html .= '<th style="border-bottom: 1px solid '.$settings['table_border_color'].'; width: 5%;">#</th>';
        
        foreach ($headers as $h) {
            $headerName = ucfirst(str_replace('_', ' ', $h));
            if ($h === 'id') continue;
            $w = isset($widths[$headerName]) ? 'width:'.$widths[$headerName].';' : '';
            $html .= '<th style="border-bottom: 1px solid '.$settings['table_border_color'].'; '.$w.'">' . $headerName . '</th>';
        }
        $html .= '</tr>';
        
        $fill = false;
        $srNo = 1;
        foreach ($results as $row) {
            $bgcolor = $fill ? '#f9fafb' : '#ffffff';
            $html .= '<tr style="background-color:'.$bgcolor.';">';
            $html .= '<td style="border-bottom: 1px solid '.$settings['table_border_color'].';">' . $srNo++ . '</td>';
            foreach ($row as $col => $val) {
                if ($col === 'id') continue;
                $html .= '<td style="border-bottom: 1px solid '.$settings['table_border_color'].';">' . htmlspecialchars($val ?? '') . '</td>';
            }
            $html .= '</tr>';
            $fill = !$fill;
        }
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('my_' . $reportType . '_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }
}
?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="report-filters">
    <h4><i class="fas fa-filter" style="color:var(--royal)"></i> Report Parameters</h4>
    <form method="GET" id="reportForm">
        <div class="filter-grid">
            <div class="form-group" style="margin-bottom:0">
                <label>Report Type</label>
                <select name="type" class="form-control" onchange="document.getElementById('reportForm').submit()">
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
                <label>My Courses</label>
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach($courses as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $courseId==$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($reportType === 'attendance'): ?>
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
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table" style="margin-right:8px;color:var(--navy)"></i> Results (<?php echo count($results); ?>)</h3>
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
                <p>No records found.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <?php foreach (array_keys($results[0]) as $header): ?>
                            <?php if ($header === 'id') continue; ?>
                            <th><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $header))); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $srNo = 1; foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo $srNo++; ?></td>
                            <?php foreach ($row as $col => $val): ?>
                                <?php if ($col === 'id') continue; ?>
                                <td>
                                    <?php 
                                    if ($col === 'status') {
                                        $badge = 'badge-gray';
                                        if ($val === 'active' || $val === 'present') $badge = 'badge-success';
                                        elseif ($val === 'struck_off' || $val === 'absent') $badge = 'badge-danger';
                                        elseif ($val === 'leave') $badge = 'badge-warning';
                                        echo "<span class='badge {$badge}'>".ucfirst($val)."</span>";
                                    } elseif ($col === 'date') {
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

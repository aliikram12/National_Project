<?php
require '../config/db.php';
requireRole('receptionist');

// Pre-fetch reference data
$courses = $pdo->query("SELECT id, name FROM courses")->fetchAll();
$slots = $pdo->query("SELECT id, time_range FROM slots")->fetchAll();

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-t');
$courseId = $_GET['course_id'] ?? '';
$slotId = $_GET['slot_id'] ?? '';
$status = $_GET['status'] ?? '';

// Build Query
$params = [];
$query = "SELECT s.id, s.name, s.contact, c.name as course, sl.time_range as slot, e.enrollment_date, s.status 
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

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Handle Export
if (isset($_GET['export']) && !empty($results)) {
    $exportFormat = $_GET['export'];
    
    if ($exportFormat === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=admissions_' . date('Ymd_His') . '.csv');
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
        $pdf->Cell(0, 10, "Admissions Report", 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, "Period: " . formatDate($dateFrom) . " to " . formatDate($dateTo), 0, 1, 'C');
        
        list($r, $g, $b) = sscanf($settings['table_header_bg'], "#%02x%02x%02x");
        list($tr, $tg, $tb) = sscanf($settings['table_header_color'], "#%02x%02x%02x");
        
        // Define widths based on report type
        $widths = ['#' => '5%', 'Name' => '15%', 'Contact' => '15%', 'Course' => '20%', 'Slot' => '15%', 'Enrollment Date' => '15%', 'Status' => '15%'];
        
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
        $pdf->Output('admissions_' . date('Ymd_His') . '.pdf', 'D');
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
            
            <div class="form-group" style="margin-bottom:0">
                <label>Slot</label>
                <select name="slot_id" class="form-control">
                    <option value="">All Slots</option>
                    <?php foreach($slots as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo $slotId==$s['id']?'selected':''; ?>><?php echo e($s['time_range']); ?></option><?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:0">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status==='active'?'selected':''; ?>>Active</option>
                    <option value="struck_off" <?php echo $status==='struck_off'?'selected':''; ?>>Struck Off</option>
                </select>
            </div>
            
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary" style="height:44px;flex:1"><i class="fas fa-search"></i> Generate</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table" style="margin-right:8px;color:var(--navy)"></i> Admissions Results (<?php echo count($results); ?>)</h3>
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
                                        if ($val === 'active') $badge = 'badge-success';
                                        elseif ($val === 'struck_off') $badge = 'badge-danger';
                                        echo "<span class='badge {$badge}'>".ucfirst($val)."</span>";
                                    } elseif ($col === 'enrollment_date') {
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

<?php
require '../config/db.php';

if (!isLoggedIn() || !hasRole('receptionist')) {
    redirect('../login.php');
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=admissions_report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Contact', 'Course', 'Time Slot', 'Enrollment Date']);
    
    $query = $pdo->query("SELECT s.id, s.name, s.contact, c.name as course_name, sl.time_range, e.enrollment_date 
                          FROM students s 
                          JOIN enrollments e ON s.id = e.student_id 
                          JOIN courses c ON e.course_id = c.id 
                          JOIN slots sl ON e.slot_id = sl.id");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

?>
<?php include '../includes/dashboard_header.php'; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Admissions Reports</h3>
    <p style="color: var(--light-text); margin-bottom: 20px;">Export the admissions data.</p>

    <div style="display: flex; gap: 20px;">
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
            <i class="fas fa-file-csv fa-3x" style="color: #28a745; margin-bottom: 15px;"></i>
            <h4>Export Admissions to CSV</h4>
            <a href="?export=csv" class="btn btn-primary mt-2">Download CSV</a>
        </div>
        
        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">
            <i class="fas fa-file-pdf fa-3x" style="color: #dc3545; margin-bottom: 15px;"></i>
            <h4>Print Admissions List</h4>
            <button onclick="window.print()" class="btn btn-primary mt-2">Print (PDF)</button>
        </div>
    </div>
</div>

<style>
    .print-only { display: none; }
    @media print {
        body { background: white; }
        .sidebar, .topbar, .no-print, .card { display: none !important; }
        .print-only { display: block; width: 100%; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    }
</style>
<div class="print-only">
    <h2>Admissions Report</h2>
    <p>Generated on: <?php echo date('d M Y'); ?></p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Course</th>
                <th>Slot</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = $pdo->query("SELECT s.name, s.contact, c.name as course_name, sl.time_range, e.enrollment_date 
                                  FROM students s 
                                  JOIN enrollments e ON s.id = e.student_id 
                                  JOIN courses c ON e.course_id = c.id 
                                  JOIN slots sl ON e.slot_id = sl.id");
            while ($row = $query->fetch()):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td><?php echo htmlspecialchars($row['time_range']); ?></td>
                <td><?php echo date('d M Y', strtotime($row['enrollment_date'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/dashboard_footer.php'; ?>

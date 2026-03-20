<?php
include 'config.php';
include 'header.php';

// Handle procedure calls
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_bills'])) {
        $bill_month = $_POST['bill_month'] . '-01';
        if ($conn->query("CALL sp_generate_monthly_bills('$bill_month')")) {
            $message = "Monthly bills generated successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['apply_fines'])) {
        if ($conn->query("CALL sp_apply_late_fines()")) {
            $message = "Late fines applied successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['disconnect_overdue'])) {
        $days = $_POST['days_overdue'];
        if ($conn->query("CALL sp_disconnect_overdue_institutions($days)")) {
            $message = "Overdue institutions disconnected successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Get summary data using the new views (with error handling)
$defaulter_summary = $conn->query("SELECT * FROM view_defaulter_summary ORDER BY days_overdue DESC");
if (!$defaulter_summary) {
    // View might not exist yet, create a fallback query
    $defaulter_summary = $conn->query("
        SELECT 
            i.institution_name,
            ic.category_name,
            cp.full_name as contact_person,
            cp.phone as contact_phone,
            mb.total_amount as amount_due,
            mb.due_date,
            DATEDIFF(CURDATE(), mb.due_date) as days_overdue,
            CASE 
                WHEN DATEDIFF(CURDATE(), mb.due_date) >= 60 THEN 'Critical'
                WHEN DATEDIFF(CURDATE(), mb.due_date) >= 30 THEN 'Warning'
                ELSE 'Notice'
            END as overdue_status
        FROM institutions i
        JOIN institution_categories ic ON i.category_id = ic.category_id
        JOIN monthly_bills mb ON i.institution_id = mb.institution_id
        LEFT JOIN contact_persons cp ON i.institution_id = cp.institution_id AND cp.is_primary = 1
        WHERE mb.payment_status = 'Unpaid'
        AND mb.due_date < CURDATE()
        ORDER BY days_overdue DESC
    ");
}

$monthly_revenue = $conn->query("SELECT * FROM view_monthly_revenue LIMIT 12");
$institution_performance = $conn->query("SELECT * FROM view_institution_performance WHERE outstanding_balance > 0");
?>

<div class="container-fluid">
    <h2>Database Procedures & Automation</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Procedure Execution Cards -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Generate Monthly Bills
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Month</label>
                            <input type="month" name="bill_month" class="form-control" required 
                                   value="<?php echo date('Y-m'); ?>">
                        </div>
                        <button type="submit" name="generate_bills" class="btn btn-primary w-100">
                            Generate Bills
                        </button>
                    </form>
                    <p class="text-muted mt-2">
                        <small>Creates unpaid bills for all active institutions</small>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning">
                    Apply Late Fines
                </div>
                <div class="card-body">
                    <form method="POST">
                        <button type="submit" name="apply_fines" class="btn btn-warning w-100">
                            Apply 15% Fines
                        </button>
                    </form>
                    <p class="text-muted mt-2">
                        <small>Applies 15% fine to all overdue unpaid bills</small>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    Auto-Disconnect
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Days Overdue Threshold</label>
                            <input type="number" name="days_overdue" class="form-control" value="60" min="1">
                        </div>
                        <button type="submit" name="disconnect_overdue" class="btn btn-danger w-100">
                            Disconnect Overdue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Display Views Data -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    Defaulter Summary
                </div>
                <div class="card-body">
                    <table class="table table-striped datatable">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Category</th>
                                <th>Contact</th>
                                <th>Amount Due</th>
                                <th>Days Overdue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($defaulter_summary && $defaulter_summary->num_rows > 0): ?>
                                <?php while ($row = $defaulter_summary->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['institution_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo $row['category_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo ($row['contact_person'] ?? 'N/A') . '<br>' . ($row['contact_phone'] ?? ''); ?></td>
                                    <td>KSh <?php echo number_format($row['amount_due'] ?? 0, 2); ?></td>
                                    <td><?php echo $row['days_overdue'] ?? 0; ?> days</td>
                                    <td>
                                        <span class="badge <?php 
                                            $status = $row['overdue_status'] ?? 'Notice';
                                            echo $status == 'Critical' ? 'bg-danger' : 
                                                ($status == 'Warning' ? 'bg-warning' : 'bg-info'); 
                                        ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No defaulters found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Message -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>System Status:</strong>
                <ul class="mb-0 mt-2">
                    <?php
                    // Check if procedures exist
                    $proc_check = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'azani_isp'");
                    $proc_count = $proc_check ? $proc_check->num_rows : 0;
                    
                    // Check if triggers exist
                    $trigger_check = $conn->query("SHOW TRIGGERS FROM azani_isp");
                    $trigger_count = $trigger_check ? $trigger_check->num_rows : 0;
                    
                    // Check if views exist
                    $view_check = $conn->query("SHOW FULL TABLES IN azani_isp WHERE TABLE_TYPE LIKE 'VIEW'");
                    $view_count = $view_check ? $view_check->num_rows : 0;
                    ?>
                    <li>Stored Procedures: <strong><?php echo $proc_count; ?></strong> found</li>
                    <li>Triggers: <strong><?php echo $trigger_count; ?></strong> found</li>
                    <li>Views: <strong><?php echo $view_count; ?></strong> found</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
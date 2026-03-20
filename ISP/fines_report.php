<?php
include 'config.php';
include 'header.php';

// Handle manual fine application
if (isset($_POST['apply_single_fine'])) {
    $bill_id = $_POST['bill_id'];
    
    // Get current cost
    $res = $conn->query("SELECT bandwidth_cost, institution_id FROM monthly_bills WHERE bill_id = $bill_id");
    $row = $res->fetch_assoc();
    $fine = $row['bandwidth_cost'] * 0.15;
    
    // Apply fine
    $conn->query("
        UPDATE monthly_bills 
        SET overdue_fine = $fine, 
            total_amount = bandwidth_cost + $fine 
        WHERE bill_id = $bill_id
    ");
    
    // Log who applied the fine (using session or current user)
    $applied_by = $_SESSION['username'] ?? 'Admin';
    
    $success = "Fine of KSh " . number_format($fine, 2) . " applied successfully to bill #$bill_id";
}

// Handle bulk fine application
if (isset($_POST['apply_all_fines'])) {
    $conn->query("CALL sp_apply_late_fines()");
    $success = "All late fines applied successfully!";
}

// Get statistics
$stats = [];

// Total fines collected
$total_fines = $conn->query("
    SELECT SUM(overdue_fine) as total 
    FROM monthly_bills 
    WHERE overdue_fine > 0
")->fetch_assoc()['total'] ?? 0;

// Number of fines applied
$fines_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM monthly_bills 
    WHERE overdue_fine > 0
")->fetch_assoc()['count'] ?? 0;

// Average fine amount
$avg_fine = $conn->query("
    SELECT AVG(overdue_fine) as avg 
    FROM monthly_bills 
    WHERE overdue_fine > 0
")->fetch_assoc()['avg'] ?? 0;

// Get all bills with fines applied
$fines_applied = $conn->query("
    SELECT 
        mb.bill_id,
        mb.bill_month,
        mb.bandwidth_cost,
        mb.overdue_fine,
        mb.total_amount,
        mb.due_date,
        mb.payment_status,
        DATEDIFF(CURDATE(), mb.due_date) as days_overdue,
        i.institution_id,
        i.institution_name,
        i.registration_number,
        ic.category_name,
        cp.full_name as contact_person,
        cp.phone as contact_phone
    FROM monthly_bills mb
    JOIN institutions i ON mb.institution_id = i.institution_id
    JOIN institution_categories ic ON i.category_id = ic.category_id
    LEFT JOIN contact_persons cp ON i.institution_id = cp.institution_id AND cp.is_primary = 1
    WHERE mb.overdue_fine > 0
    ORDER BY mb.due_date DESC
");

// Get bills eligible for fines but not yet applied
$eligible_for_fines = $conn->query("
    SELECT 
        mb.bill_id,
        mb.bill_month,
        mb.bandwidth_cost,
        mb.due_date,
        DATEDIFF(CURDATE(), mb.due_date) as days_overdue,
        i.institution_name,
        i.institution_id
    FROM monthly_bills mb
    JOIN institutions i ON mb.institution_id = i.institution_id
    WHERE mb.payment_status = 'Unpaid' 
    AND mb.due_date < CURDATE()
    AND (mb.overdue_fine IS NULL OR mb.overdue_fine = 0)
    ORDER BY mb.due_date ASC
");

// Get fine payment history (fines that have been paid)
$paid_fines = $conn->query("
    SELECT 
        mb.bill_id,
        mb.bill_month,
        mb.overdue_fine,
        mb.total_amount,
        i.institution_name,
        p.payment_date,
        p.receipt_number
    FROM monthly_bills mb
    JOIN institutions i ON mb.institution_id = i.institution_id
    JOIN payments p ON i.institution_id = p.institution_id
    WHERE mb.overdue_fine > 0 
    AND mb.payment_status = 'Paid'
    AND p.payment_type = 'Monthly'
    ORDER BY p.payment_date DESC
    LIMIT 20
");
?>

<div class="container-fluid">
    <h2>Fines Management Report</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-header">Total Fines Applied</div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($total_fines, 2); ?></h3>
                    <small>From <?php echo $fines_count; ?> bills</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-header">Average Fine</div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($avg_fine, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-header">Eligible for Fines</div>
                <div class="card-body">
                    <h3><?php echo $eligible_for_fines->num_rows; ?></h3>
                    <small>Bills pending fine</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-header">Paid Fines</div>
                <div class="card-body">
                    <h3><?php echo $paid_fines->num_rows; ?></h3>
                    <small>Fines collected</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Fine Management Actions
                </div>
                <div class="card-body">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="apply_all_fines" class="btn btn-warning btn-lg" 
                                onclick="return confirm('Apply 15% fines to ALL eligible overdue bills?')">
                            <i class="bi bi-exclamation-triangle"></i> Apply All Pending Fines
                        </button>
                    </form>
                    <a href="defaulters.php" class="btn btn-info btn-lg ms-2">
                        <i class="bi bi-list"></i> View All Defaulters
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fines Applied Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <span>Fines Already Applied (<?php echo $fines_applied->num_rows; ?>)</span>
                    <span class="badge bg-light text-dark">Total: KSh <?php echo number_format($total_fines, 2); ?></span>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover datatable">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Category</th>
                                <th>Bill Month</th>
                                <th>Original Amount</th>
                                <th>Fine (15%)</th>
                                <th>Total Due</th>
                                <th>Days Overdue</th>
                                <th>Status</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fines_applied && $fines_applied->num_rows > 0): ?>
                                <?php while ($fine = $fines_applied->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $fine['institution_name']; ?></strong><br>
                                        <small><?php echo $fine['registration_number']; ?></small>
                                    </td>
                                    <td><?php echo $fine['category_name']; ?></td>
                                    <td><?php echo date('F Y', strtotime($fine['bill_month'])); ?></td>
                                    <td>KSh <?php echo number_format($fine['bandwidth_cost'], 2); ?></td>
                                    <td class="text-danger">
                                        <strong>KSh <?php echo number_format($fine['overdue_fine'], 2); ?></strong>
                                    </td>
                                    <td>KSh <?php echo number_format($fine['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $fine['days_overdue'] >= 60 ? 'bg-danger' : 
                                                ($fine['days_overdue'] >= 30 ? 'bg-warning' : 'bg-info'); 
                                        ?>">
                                            <?php echo $fine['days_overdue']; ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fine['payment_status'] == 'Paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $fine['contact_person'] ?? 'N/A'; ?><br>
                                        <small><?php echo $fine['contact_phone'] ?? ''; ?></small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No fines have been applied yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Eligible for Fines Table -->
    <?php if ($eligible_for_fines->num_rows > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-exclamation-circle"></i> Pending Fines to Apply (<?php echo $eligible_for_fines->num_rows; ?>)
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Bill Month</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine Amount (15%)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($eligible = $eligible_for_fines->fetch_assoc()): 
                                $fine_amount = $eligible['bandwidth_cost'] * 0.15;
                            ?>
                            <tr>
                                <td><?php echo $eligible['institution_name']; ?></td>
                                <td><?php echo date('F Y', strtotime($eligible['bill_month'])); ?></td>
                                <td>KSh <?php echo number_format($eligible['bandwidth_cost'], 2); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($eligible['due_date'])); ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo $eligible['days_overdue']; ?> days</span>
                                </td>
                                <td class="text-danger">KSh <?php echo number_format($fine_amount, 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="bill_id" value="<?php echo $eligible['bill_id']; ?>">
                                        <button type="submit" name="apply_single_fine" class="btn btn-sm btn-warning"
                                                onclick="return confirm('Apply KSh <?php echo number_format($fine_amount, 2); ?> fine?')">
                                            Apply Fine
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Paid Fines History -->
    <?php if ($paid_fines->num_rows > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check-circle"></i> Paid Fines History (Last 20)
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Bill Month</th>
                                <th>Fine Amount</th>
                                <th>Total Paid</th>
                                <th>Payment Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($paid = $paid_fines->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $paid['institution_name']; ?></td>
                                <td><?php echo date('F Y', strtotime($paid['bill_month'])); ?></td>
                                <td class="text-danger">KSh <?php echo number_format($paid['overdue_fine'], 2); ?></td>
                                <td>KSh <?php echo number_format($paid['total_amount'], 2); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($paid['payment_date'])); ?></td>
                                <td><?php echo $paid['receipt_number']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Fine Calculation Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-info-circle"></i> Fine Calculation Rules
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>How Fines Are Calculated:</h5>
                            <ul>
                                <li>Fine is <strong>15%</strong> of the original bandwidth cost</li>
                                <li>Applied automatically on the 16th of each month</li>
                                <li>Can be applied manually from this page</li>
                                <li>Fines are added to the total amount due</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Example Calculation:</h5>
                            <?php
                            $example = $conn->query("SELECT bandwidth_cost FROM monthly_bills WHERE bandwidth_cost > 0 LIMIT 1")->fetch_assoc();
                            $example_cost = $example['bandwidth_cost'] ?? 3500;
                            $example_fine = $example_cost * 0.15;
                            ?>
                            <table class="table table-sm">
                                <tr>
                                    <td>Original Bill:</td>
                                    <td>KSh <?php echo number_format($example_cost, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>15% Fine:</td>
                                    <td>KSh <?php echo number_format($example_fine, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Due:</th>
                                    <th>KSh <?php echo number_format($example_cost + $example_fine, 2); ?></th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons for better visuals -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

<?php include 'footer.php'; ?>
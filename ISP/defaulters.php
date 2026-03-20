<?php
include 'config.php';
include 'header.php';

if (isset($_POST['apply_fine'])) {
    $bill_id = $_POST['bill_id'];
    // Get current cost
    $res = $conn->query("SELECT bandwidth_cost FROM monthly_bills WHERE bill_id = $bill_id");
    $row = $res->fetch_assoc();
    $fine = $row['bandwidth_cost'] * 0.15;

    // FIX: Use bandwidth_cost instead of amount_due
    $conn->query("
        UPDATE monthly_bills 
        SET overdue_fine = $fine, total_amount = bandwidth_cost + $fine 
        WHERE bill_id = $bill_id
    ");
    $success = "Fine applied successfully";
}

$defaulters = $conn->query("
    SELECT mb.*, i.institution_name, i.registration_number, 
           ic.category_name, cp.full_name as contact_person, cp.phone
    FROM monthly_bills mb
    JOIN institutions i ON mb.institution_id = i.institution_id
    JOIN institution_categories ic ON i.category_id = ic.category_id
    LEFT JOIN contact_persons cp ON i.institution_id = cp.institution_id AND cp.is_primary = 1
    WHERE mb.payment_status = 'Unpaid' 
    AND mb.due_date < CURDATE()
    ORDER BY mb.due_date ASC
");
?>

<div class="container-fluid">
    <h2>List of Defaulters</h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-warning">Institutions with Overdue Payments</div>
        <div class="card-body">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Institution</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Fine</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($defaulter = $defaulters->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $defaulter['institution_name']; ?></td>
                            <td><?php echo $defaulter['contact_person']; ?></td>
                            <td><?php echo $defaulter['phone']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($defaulter['due_date'])); ?></td>
                            <td>KSh <?php echo number_format($defaulter['bandwidth_cost'], 2); ?></td>
                            <td>KSh <?php echo number_format($defaulter['overdue_fine'], 2); ?></td>
                            <td>KSh <?php echo number_format($defaulter['total_amount'], 2); ?></td>
                            <td>
                                <?php if ($defaulter['overdue_fine'] == 0): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="bill_id" value="<?php echo $defaulter['bill_id']; ?>">
                                        <button type="submit" name="apply_fine" class="btn btn-sm btn-warning">Apply 15%
                                            Fine</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Fine Applied</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
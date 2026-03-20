<?php
include 'config.php';
include 'header.php';

// Process disconnection
if (isset($_POST['disconnect'])) {
    $bill_id = $_POST['bill_id'];
    $institution_id = $_POST['institution_id'];
    
    $conn->begin_transaction();
    
    try {
        // Update bill status
        $conn->query("UPDATE monthly_bills SET payment_status = 'Disconnected' WHERE bill_id = $bill_id");
        
        // Update institution status
        $conn->query("UPDATE institutions SET connection_status = 'Disconnected' WHERE institution_id = $institution_id");
        
        // Record disconnection
        $conn->query("
            INSERT INTO disconnection_history (institution_id, bill_id, disconnection_date, reason) 
            VALUES ($institution_id, $bill_id, CURDATE(), 'Non-payment')
        ");
        
        $conn->commit();
        $success = "Institution disconnected successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Process reconnection
if (isset($_POST['reconnect'])) {
    $institution_id = $_POST['institution_id'];
    $bill_id = $_POST['bill_id'];
    $reconnection_fee = 1000;
    
    $conn->begin_transaction();
    
    try {
        // Update disconnection history
        $conn->query("
            UPDATE disconnection_history 
            SET reconnection_date = CURDATE(), reconnection_fee_paid = TRUE 
            WHERE institution_id = $institution_id AND bill_id = $bill_id
        ");
        
        // Update institution status
        $conn->query("UPDATE institutions SET connection_status = 'Active' WHERE institution_id = $institution_id");
        
        // Record reconnection fee payment
        $receipt = 'RCT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $conn->query("
            INSERT INTO payments (institution_id, payment_type, amount, receipt_number) 
            VALUES ($institution_id, 'Reconnection', $reconnection_fee, '$receipt')
        ");
        
        $conn->commit();
        $success = "Institution reconnected successfully. Reconnection fee: KSh 1,000";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Get institutions with disconnection issues
$disconnected = $conn->query("
    SELECT i.*, ic.category_name, dh.disconnection_date, dh.reconnection_date,
           mb.bill_month, mb.total_amount as amount_due
    FROM institutions i
    JOIN institution_categories ic ON i.category_id = ic.category_id
    LEFT JOIN disconnection_history dh ON i.institution_id = dh.institution_id
    LEFT JOIN monthly_bills mb ON dh.bill_id = mb.bill_id
    WHERE i.connection_status = 'Disconnected' 
       OR (dh.reconnection_date IS NULL AND dh.disconnection_date IS NOT NULL)
    ORDER BY dh.disconnection_date DESC
");
?>

<div class="container-fluid">
    <h2>Disconnection Issues</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    Disconnected Institutions
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Category</th>
                                <th>Disconnection Date</th>
                                <th>Amount Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($inst = $disconnected->fetch_assoc()): 
                                if (!$inst['reconnection_date']): 
                            ?>
                            <tr>
                                <td><?php echo $inst['institution_name']; ?></td>
                                <td><?php echo $inst['category_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($inst['disconnection_date'])); ?></td>
                                <td>KSh <?php echo number_format($inst['amount_due'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="institution_id" value="<?php echo $inst['institution_id']; ?>">
                                        <input type="hidden" name="bill_id" value="<?php echo $inst['bill_id']; ?>">
                                        <button type="submit" name="reconnect" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Process reconnection with KSh 1,000 fee?')">
                                            Reconnect
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php 
                                endif;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    At Risk of Disconnection (Over 30 days)
                </div>
                <div class="card-body">
                    <?php
                    $at_risk = $conn->query("
                        SELECT i.*, ic.category_name, mb.bill_id, mb.bill_month, 
                               mb.total_amount, mb.due_date,
                               DATEDIFF(CURDATE(), mb.due_date) as days_overdue
                        FROM institutions i
                        JOIN institution_categories ic ON i.category_id = ic.category_id
                        JOIN monthly_bills mb ON i.institution_id = mb.institution_id
                        WHERE i.connection_status = 'Active' 
                        AND mb.payment_status = 'Unpaid'
                        AND mb.due_date < CURDATE()
                        AND DATEDIFF(CURDATE(), mb.due_date) >= 30
                        ORDER BY days_overdue DESC
                    ");
                    ?>
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Days Overdue</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($risk = $at_risk->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $risk['institution_name']; ?></td>
                                <td><span class="badge bg-danger"><?php echo $risk['days_overdue']; ?> days</span></td>
                                <td>KSh <?php echo number_format($risk['total_amount'], 2); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="bill_id" value="<?php echo $risk['bill_id']; ?>">
                                        <input type="hidden" name="institution_id" value="<?php echo $risk['institution_id']; ?>">
                                        <button type="submit" name="disconnect" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Disconnect this institution?')">
                                            Disconnect
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
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Disconnection History
                </div>
                <div class="card-body">
                    <?php
                    $history = $conn->query("
                        SELECT dh.*, i.institution_name, mb.bill_month, mb.total_amount
                        FROM disconnection_history dh
                        JOIN institutions i ON dh.institution_id = i.institution_id
                        JOIN monthly_bills mb ON dh.bill_id = mb.bill_id
                        ORDER BY dh.disconnection_date DESC
                        LIMIT 50
                    ");
                    ?>
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Bill Month</th>
                                <th>Disconnection Date</th>
                                <th>Reconnection Date</th>
                                <th>Reconnection Fee Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($hist = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $hist['institution_name']; ?></td>
                                <td><?php echo date('F Y', strtotime($hist['bill_month'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($hist['disconnection_date'])); ?></td>
                                <td>
                                    <?php echo $hist['reconnection_date'] ? date('d/m/Y', strtotime($hist['reconnection_date'])) : 'Not reconnected'; ?>
                                </td>
                                <td>
                                    <?php if ($hist['reconnection_fee_paid']): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
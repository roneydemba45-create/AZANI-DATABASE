<?php
include 'config.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $institution_id = $_POST['institution_id'];
    $payment_type = $_POST['payment_type'];
    $amount = $_POST['amount'];
    $receipt_number = 'RCT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO payments (institution_id, payment_type, amount, receipt_number) 
            VALUES ($institution_id, '$payment_type', $amount, '$receipt_number')";
    
    if ($conn->query($sql)) {
        // Update institution payment status based on payment type
        if ($payment_type == 'Registration') {
            $conn->query("UPDATE institutions SET registration_fee_paid = TRUE WHERE institution_id = $institution_id");
        } elseif ($payment_type == 'Installation') {
            $conn->query("UPDATE institutions SET installation_fee_paid = TRUE WHERE institution_id = $institution_id");
        } elseif ($payment_type == 'Reconnection') {
            $conn->query("UPDATE institutions SET connection_status = 'Active' WHERE institution_id = $institution_id");
        }
        
        $success = "Payment captured successfully. Receipt Number: $receipt_number";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get institutions for dropdown
$institutions = $conn->query("
    SELECT i.*, ic.category_name, bp.bandwidth_mbps 
    FROM institutions i 
    JOIN institution_categories ic ON i.category_id = ic.category_id 
    JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id 
    ORDER BY i.institution_name
");
?>

<div class="container-fluid">
    <h2>Capture Payments</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            Payment Form
        </div>
        <div class="card-body">
            <form method="POST" action="" id="paymentForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Institution</label>
                        <select name="institution_id" class="form-control" required id="institutionSelect">
                            <option value="">Select Institution</option>
                            <?php while($inst = $institutions->fetch_assoc()): ?>
                                <option value="<?php echo $inst['institution_id']; ?>" 
                                        data-reg-fee="<?php echo $inst['registration_fee_paid']; ?>"
                                        data-inst-fee="<?php echo $inst['installation_fee_paid']; ?>"
                                        data-bandwidth="<?php echo $inst['bandwidth_mbps']; ?>">
                                    <?php echo $inst['institution_name']; ?> (<?php echo $inst['category_name']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-control" required id="paymentType">
                            <option value="">Select Payment Type</option>
                            <option value="Registration">Registration Fee (KSh 8,500)</option>
                            <option value="Installation">Installation Fee (KSh 10,000)</option>
                            <option value="Monthly">Monthly Payment</option>
                            <option value="Computer">Computer Purchase</option>
                            <option value="LAN">LAN Installation</option>
                            <option value="Reconnection">Reconnection Fee (KSh 1,000)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Amount (KSh)</label>
                        <input type="number" name="amount" class="form-control" required id="amount" step="0.01">
                    </div>
                </div>
                
                <div id="paymentInfo" class="alert alert-info" style="display: none;"></div>
                
                <button type="submit" class="btn btn-primary">Process Payment</button>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            Recent Payments
        </div>
        <div class="card-body">
            <table class="table table-striped" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Institution</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_payments = $conn->query("
                        SELECT p.*, i.institution_name 
                        FROM payments p 
                        JOIN institutions i ON p.institution_id = i.institution_id 
                        ORDER BY p.payment_date DESC 
                        LIMIT 20
                    ");
                    while($payment = $recent_payments->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $payment['receipt_number']; ?></td>
                        <td><?php echo $payment['institution_name']; ?></td>
                        <td><?php echo $payment['payment_type']; ?></td>
                        <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['payment_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('paymentType').addEventListener('change', function() {
    var type = this.value;
    var amountField = document.getElementById('amount');
    var infoDiv = document.getElementById('paymentInfo');
    
    if (type == 'Registration') {
        amountField.value = 8500;
        infoDiv.style.display = 'block';
        infoDiv.innerHTML = 'Registration fee is KSh 8,500';
    } else if (type == 'Installation') {
        amountField.value = 10000;
        infoDiv.style.display = 'block';
        infoDiv.innerHTML = 'Installation fee is KSh 10,000';
    } else if (type == 'Reconnection') {
        amountField.value = 1000;
        infoDiv.style.display = 'block';
        infoDiv.innerHTML = 'Reconnection fee is KSh 1,000';
    } else {
        amountField.value = '';
        infoDiv.style.display = 'none';
    }
});
</script>

<?php include 'footer.php'; ?>
<?php
include 'config.php';
include 'header.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";
$error = "";

// Handle Bill Generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $bill_month = $_POST['bill_month']; // Format: YYYY-MM

    if (empty($bill_month)) {
        $error = "Please select a valid month.";
    } else {
        // 1. Find all institutions that are ACTIVE and have a bandwidth plan
        $sql = "SELECT i.institution_id, i.institution_name, bp.monthly_cost 
                FROM institutions i
                JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id
                WHERE i.connection_status = 'Active'";

        $result = $conn->query($sql);

        if (!$result) {
            $error = "Database Error: " . $conn->error . "<br><strong>Did you run the SQL update script?</strong> The 'connection_status' column might be missing.";
        } elseif ($result->num_rows == 0) {
            $error = "No active institutions found to bill. Make sure you have registered institutions and their status is 'Active'.";
        } else {
            $count = 0;
            $skipped = 0;

            while ($inst = $result->fetch_assoc()) {
                $inst_id = $inst['institution_id'];
                $cost = $inst['monthly_cost'];

                // Create a proper date for the first of the month
                $bill_date = $bill_month . "-01";
                // Due date is the 15th of the same month
                $due_date = $bill_month . "-15";

                // 2. Check if bill already exists to prevent duplicates
                $check_sql = "SELECT bill_id FROM monthly_bills 
                              WHERE institution_id = ? AND bill_month = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("is", $inst_id, $bill_date);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 0) {
                    // 3. Insert the new bill
                    $insert_sql = "INSERT INTO monthly_bills 
                                   (institution_id, bill_month, bandwidth_cost, total_amount, due_date, payment_status) 
                                   VALUES (?, ?, ?, ?, ?, 'Unpaid')";
                    $stmt_insert = $conn->prepare($insert_sql);
                    $stmt_insert->bind_param("isdds", $inst_id, $bill_date, $cost, $cost, $due_date);

                    if ($stmt_insert->execute()) {
                        $count++;
                    }
                } else {
                    $skipped++;
                }
            }

            if ($count > 0) {
                $message = "Success! Generated $count new bills.";
                if ($skipped > 0) {
                    $message .= " Skipped $skipped institutions (bills already existed).";
                }
            } else {
                $message = "No new bills generated. Bills might already exist for this month.";
            }
        }
    }
}
?>

<div class="container-fluid">
    <h2>Generate Monthly Bills</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Create Bills for Institutions</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Select Billing Month</label>
                            <input type="month" name="bill_month" class="form-control" required
                                value="<?php echo date('Y-m'); ?>">
                        </div>
                        <button type="submit" name="generate" class="btn btn-warning btn-lg w-100">
                            Generate Bills
                        </button>
                    </form>
                    <p class="mt-3 text-muted">
                        <small>Clicking this button will create an "Unpaid" bill for every institution with status
                            'Active'.</small>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Quick System Check</div>
                <div class="card-body">
                    <?php
                    // Helper check to see why bills might not generate
                    $inst_count = $conn->query("SELECT COUNT(*) as total FROM institutions")->fetch_assoc()['total'];
                    $active_count = $conn->query("SELECT COUNT(*) as total FROM institutions WHERE connection_status = 'Active'")->fetch_assoc()['total'];
                    ?>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Institutions in DB:
                            <span class="badge bg-primary rounded-pill">
                                <?php echo $inst_count; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Institutions with Status 'Active':
                            <span class="badge bg-success rounded-pill">
                                <?php echo $active_count; ?>
                            </span>
                        </li>
                    </ul>
                    <div class="alert alert-info mt-2">
                        <small>Generated bills require 'Active' institutions. If the Active count is 0, register an
                            institution or check their status.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
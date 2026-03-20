<?php
include 'config.php';
include 'header.php';

// Get statistics
$stats = [];

// Total institutions
$result = $conn->query("SELECT COUNT(*) as total FROM institutions");
$stats['total_institutions'] = $result->fetch_assoc()['total'];

// Active institutions
$result = $conn->query("SELECT COUNT(*) as total FROM institutions WHERE connection_status = 'Active'");
$stats['active_institutions'] = $result->fetch_assoc()['total'];

// Defaulters
$result = $conn->query("
    SELECT COUNT(DISTINCT i.institution_id) as total 
    FROM institutions i 
    JOIN monthly_bills mb ON i.institution_id = mb.institution_id 
    WHERE mb.payment_status = 'Unpaid' AND mb.due_date < CURDATE()
");
$stats['defaulters'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(amount) as total FROM payments");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Monthly revenue
$result = $conn->query("
    SELECT SUM(amount) as total 
    FROM payments 
    WHERE MONTH(payment_date) = MONTH(CURDATE()) 
    AND YEAR(payment_date) = YEAR(CURDATE())
");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;
?>

<div class="container-fluid">
    <h2>Dashboard</h2>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Total Institutions</h5>
                    <h2><?php echo $stats['total_institutions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Active Connections</h5>
                    <h2><?php echo $stats['active_institutions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Defaulters</h5>
                    <h2><?php echo $stats['defaulters']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2>KSh <?php echo number_format($stats['total_revenue'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Recent Registrations
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Registration #</th>
                                <th>Institution</th>
                                <th>Category</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("
                                SELECT i.*, ic.category_name 
                                FROM institutions i 
                                JOIN institution_categories ic ON i.category_id = ic.category_id 
                                ORDER BY i.registration_date DESC 
                                LIMIT 5
                            ");
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['registration_number']; ?></td>
                                <td><?php echo $row['institution_name']; ?></td>
                                <td><?php echo $row['category_name']; ?></td>
                                <td><?php echo $row['registration_date']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Recent Payments
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Institution</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("
                                SELECT p.*, i.institution_name 
                                FROM payments p 
                                JOIN institutions i ON p.institution_id = i.institution_id 
                                ORDER BY p.payment_date DESC 
                                LIMIT 5
                            ");
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['receipt_number']; ?></td>
                                <td><?php echo $row['institution_name']; ?></td>
                                <td><?php echo $row['payment_type']; ?></td>
                                <td>KSh <?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['payment_date'])); ?></td>
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
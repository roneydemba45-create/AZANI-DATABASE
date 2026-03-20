<?php
include 'config.php';
include 'header.php';

// 1. Revenue Summary
 $revenue_sql = "SELECT payment_type, SUM(amount) as total_amount, COUNT(*) as count 
                FROM payments 
                GROUP BY payment_type";
 $revenue_result = $conn->query($revenue_sql);

 $total_revenue_sql = "SELECT SUM(amount) as total FROM payments";
 $total_revenue = $conn->query($total_revenue_sql)->fetch_assoc()['total'] ?? 0;

// 2. Institution Statistics
// By Category
 $cat_sql = "SELECT ic.category_name, COUNT(i.institution_id) as total 
            FROM institutions i 
            RIGHT JOIN institution_categories ic ON i.category_id = ic.category_id 
            GROUP BY ic.category_name";
 $cat_result = $conn->query($cat_sql);

// By Bandwidth
 $bw_sql = "SELECT bp.bandwidth_mbps, COUNT(i.institution_id) as total 
           FROM institutions i 
           RIGHT JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id 
           GROUP BY bp.bandwidth_mbps";
 $bw_result = $conn->query($bw_sql);

// 3. Collection Efficiency (Paid vs Unpaid)
 $paid_sql = "SELECT SUM(total_amount) as paid FROM monthly_bills WHERE payment_status = 'Paid'";
 $paid_amount = $conn->query($paid_sql)->fetch_assoc()['paid'] ?? 0;

 $unpaid_sql = "SELECT SUM(total_amount) as unpaid FROM monthly_bills WHERE payment_status = 'Unpaid'";
 $unpaid_amount = $conn->query($unpaid_sql)->fetch_assoc()['unpaid'] ?? 0;
?>

<div class="container-fluid">
    <h2>System Reports</h2>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-header">Total Revenue Collected</div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($total_revenue, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-header">Paid Bills Total</div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($paid_amount, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-header">Outstanding Debt</div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($unpaid_amount, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue Breakdown -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Revenue Breakdown by Type</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $revenue_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['payment_type']; ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td>KSh <?php echo number_format($row['total_amount'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Institution Statistics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Institutions by Category</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Institutions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $cat_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['category_name']; ?></td>
                                <td><?php echo $row['total']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">Institutions by Bandwidth</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Bandwidth</th>
                                <th>Total Institutions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $bw_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['bandwidth_mbps']; ?> MBPS</td>
                                <td><?php echo $row['total']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detailed Payment List -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Recent Payment History (Last 50)</div>
                <div class="card-body">
                    <table class="table table-bordered datatable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Institution</th>
                                <th>Payment Type</th>
                                <th>Amount</th>
                                <th>Receipt #</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $payments_list = $conn->query("
                                SELECT p.payment_date, i.institution_name, p.payment_type, p.amount, p.receipt_number 
                                FROM payments p 
                                JOIN institutions i ON p.institution_id = i.institution_id 
                                ORDER BY p.payment_date DESC 
                                LIMIT 50
                            ");
                            while($row = $payments_list->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['payment_date'])); ?></td>
                                <td><?php echo $row['institution_name']; ?></td>
                                <td><?php echo $row['payment_type']; ?></td>
                                <td>KSh <?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['receipt_number']; ?></td>
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
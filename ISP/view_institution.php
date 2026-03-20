<?php
include 'config.php';
include 'header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: registered_institutions.php");
    exit();
}

$institution_id = intval($_GET['id']);

// Fetch Institution Details
$inst_sql = "SELECT i.*, ic.category_name, bp.bandwidth_mbps, bp.monthly_cost 
             FROM institutions i 
             JOIN institution_categories ic ON i.category_id = ic.category_id 
             JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id 
             WHERE i.institution_id = ?";
$stmt = $conn->prepare($inst_sql);
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("<div class='container-fluid mt-5'><div class='alert alert-danger'>Institution not found.</div></div>");
}

$inst = $result->fetch_assoc();

// Fetch Contact Persons
$contacts = $conn->query("SELECT * FROM contact_persons WHERE institution_id = $institution_id");

// Fetch Infrastructure
$infra = $conn->query("SELECT ir.*, lnp.min_nodes, lnp.max_nodes 
                       FROM infrastructure_requirements ir 
                       LEFT JOIN lan_node_pricing lnp ON ir.lan_pricing_id = lnp.price_id 
                       WHERE ir.institution_id = $institution_id")->fetch_assoc();

// Fetch Recent Bills
$bills = $conn->query("SELECT * FROM monthly_bills WHERE institution_id = $institution_id ORDER BY bill_month DESC LIMIT 12");

// Fetch Payment History
$payments = $conn->query("SELECT * FROM payments WHERE institution_id = $institution_id ORDER BY payment_date DESC LIMIT 10");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <?php echo htmlspecialchars($inst['institution_name']); ?>
        </h2>
        <div>
            <span class="badge <?php
            echo $inst['connection_status'] == 'Active' ? 'bg-success' : ($inst['connection_status'] == 'Suspended' ? 'bg-warning' : 'bg-danger');
            ?> fs-6 p-2">
                Status:
                <?php echo $inst['connection_status']; ?>
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Details -->
        <div class="col-md-6">
            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header">Institution Details</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Reg. Number:</th>
                            <td>
                                <?php echo $inst['registration_number']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>
                                <?php echo $inst['category_name']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>
                                <?php echo $inst['email']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>
                                <?php echo $inst['phone']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Physical Address:</th>
                            <td>
                                <?php echo $inst['physical_address']; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Registration Date:</th>
                            <td>
                                <?php echo date('d M Y', strtotime($inst['registration_date'])); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Connection & Fees -->
            <div class="card mb-3">
                <div class="card-header">Connection & Billing</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Bandwidth:</th>
                            <td>
                                <?php echo $inst['bandwidth_mbps']; ?> Mbps
                            </td>
                        </tr>
                        <tr>
                            <th>Monthly Cost:</th>
                            <td>KSh
                                <?php echo number_format($inst['monthly_cost'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Registration Fee:</th>
                            <td>
                                <?php if ($inst['registration_fee_paid']): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid (KSh 8,500)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Installation Fee:</th>
                            <td>
                                <?php if ($inst['installation_fee_paid']): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid (KSh 10,000)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Contact Persons -->
            <div class="card mb-3">
                <div class="card-header">Contact Persons</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $contacts->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo $c['full_name']; ?>
                                        <?php if ($c['is_primary'])
                                            echo "<span class='badge bg-info'>Primary</span>"; ?>
                                    </td>
                                    <td>
                                        <?php echo $c['designation']; ?>
                                    </td>
                                    <td>
                                        <?php echo $c['phone']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Infrastructure & History -->
        <div class="col-md-6">
            <!-- Infrastructure -->
            <div class="card mb-3">
                <div class="card-header">Infrastructure Status</div>
                <div class="card-body">
                    <?php if ($infra): ?>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Users:</th>
                                <td>
                                    <?php echo $infra['number_of_users']; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Computers:</th>
                                <td>
                                    <?php
                                    if ($infra['has_computers']) {
                                        echo "Yes (" . $infra['computers_purchased'] . " purchased)";
                                    } else {
                                        echo "None on site";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>LAN Nodes:</th>
                                <td>
                                    <?php echo $infra['lan_nodes_required']; ?> nodes
                                </td>
                            </tr>
                            <tr>
                                <th>LAN Install Cost:</th>
                                <td>KSh
                                    <?php echo number_format($infra['lan_cost'], 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Readiness:</th>
                                <td>
                                    <?php if ($infra['infrastructure_ready']): ?>
                                        <span class="badge bg-success">Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending Assessment</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No infrastructure data recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card mb-3">
                <div class="card-header">Recent Payments</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($p['payment_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo $p['payment_type']; ?>
                                    </td>
                                    <td>KSh
                                        <?php echo number_format($p['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo $p['receipt_number']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Bills -->
            <div class="card">
                <div class="card-header">Billing History</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $bills->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('F Y', strtotime($b['bill_month'])); ?>
                                    </td>
                                    <td>KSh
                                        <?php echo number_format($b['total_amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($b['due_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php
                                        echo $b['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning';
                                        ?>">
                                            <?php echo $b['payment_status']; ?>
                                        </span>
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
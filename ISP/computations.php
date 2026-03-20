<?php
include 'config.php';
include 'header.php';

// Fetch pricing data from database
$bandwidths = $conn->query("SELECT * FROM bandwidth_packages ORDER BY bandwidth_mbps");
$lan_pricing = $conn->query("SELECT * FROM lan_node_pricing ORDER BY min_nodes");

// Get institutions for dropdown with their infrastructure data
$institutions = $conn->query("
    SELECT i.institution_id, i.institution_name, i.bandwidth_id, 
           ir.lan_nodes_required, ir.computers_purchased,
           ic.category_name, bp.bandwidth_mbps, bp.monthly_cost
    FROM institutions i
    JOIN institution_categories ic ON i.category_id = ic.category_id
    JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id
    LEFT JOIN infrastructure_requirements ir ON i.institution_id = ir.institution_id
    ORDER BY i.institution_name
");

// Initialize variables
$results = null;
$input = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = $_POST;
    
    // Check if institution is selected
    if (isset($_POST['institution_id']) && !empty($_POST['institution_id'])) {
        $institution_id = $_POST['institution_id'];
        
        // Get institution data including infrastructure
        $inst_data = $conn->query("
            SELECT i.*, ir.lan_nodes_required, ir.computers_purchased,
                   bp.monthly_cost, bp.bandwidth_mbps
            FROM institutions i
            JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id
            LEFT JOIN infrastructure_requirements ir ON i.institution_id = ir.institution_id
            WHERE i.institution_id = $institution_id
        ")->fetch_assoc();
        
        // 1. Get Bandwidth Cost
        $bandwidth_mbps = $inst_data['bandwidth_mbps'];
        $monthly_bandwidth_cost = $inst_data['monthly_cost'];
        
        // 2. Calculate LAN Cost
        $lan_nodes = intval($inst_data['lan_nodes_required'] ?? $_POST['lan_nodes'] ?? 0);
        $lan_cost = 0;
        $lan_tier = "N/A";
        
        if ($lan_nodes > 0) {
            $lan_res = $conn->query("
                SELECT * FROM lan_node_pricing 
                WHERE $lan_nodes BETWEEN min_nodes AND max_nodes
                LIMIT 1
            ");
            if ($lan_res->num_rows > 0) {
                $lan_row = $lan_res->fetch_assoc();
                $lan_cost = $lan_row['cost'];
                $lan_tier = $lan_row['min_nodes'] . " - " . $lan_row['max_nodes'] . " nodes";
            }
        }
        
        // 3. Calculate Computer Cost
        $computers = intval($inst_data['computers_purchased'] ?? $_POST['computers'] ?? 0);
        $computer_cost = $computers * 40000;
        
        // 4. Check fee payment status
        $registration_paid = $inst_data['registration_fee_paid'] ? 'Paid' : 'Unpaid';
        $installation_paid = $inst_data['installation_fee_paid'] ? 'Paid' : 'Unpaid';
        
        // 5. Fixed Fees
        $registration_fee = 8500;
        $installation_fee = 10000;
        $processing_fee = 1000;
        
        // 6. Calculate initial costs (only if not paid)
        $registration_due = $inst_data['registration_fee_paid'] ? 0 : $registration_fee;
        $installation_due = $inst_data['installation_fee_paid'] ? 0 : $installation_fee;
        
        // 7. Totals
        $total_initial_cost = $registration_due + $installation_due + $processing_fee + $lan_cost + $computer_cost;
        $total_monthly_cost = $monthly_bandwidth_cost;
        
        $results = [
            'institution_name' => $inst_data['institution_name'],
            'bandwidth_mbps' => $bandwidth_mbps,
            'monthly_bandwidth_cost' => $monthly_bandwidth_cost,
            'lan_nodes' => $lan_nodes,
            'lan_cost' => $lan_cost,
            'lan_tier' => $lan_tier,
            'computers' => $computers,
            'computer_cost' => $computer_cost,
            'registration_fee' => $registration_fee,
            'installation_fee' => $installation_fee,
            'registration_paid' => $registration_paid,
            'installation_paid' => $installation_paid,
            'registration_due' => $registration_due,
            'installation_due' => $installation_due,
            'processing_fee' => $processing_fee,
            'total_initial' => $total_initial_cost,
            'total_monthly' => $total_monthly_cost
        ];
    } else {
        // Manual calculation mode (existing code)
        $bandwidth_id = $_POST['bandwidth_id'];
        $bw_row = $conn->query("SELECT monthly_cost, bandwidth_mbps FROM bandwidth_packages WHERE bandwidth_id = $bandwidth_id")->fetch_assoc();
        $monthly_bandwidth_cost = $bw_row['monthly_cost'];
        $bandwidth_mbps = $bw_row['bandwidth_mbps'];
        
        $lan_nodes = intval($_POST['lan_nodes']);
        $lan_cost = 0;
        $lan_tier = "N/A";
        
        if ($lan_nodes > 0) {
            $lan_res = $conn->query("
                SELECT * FROM lan_node_pricing 
                WHERE $lan_nodes BETWEEN min_nodes AND max_nodes
                LIMIT 1
            ");
            if ($lan_res->num_rows > 0) {
                $lan_row = $lan_res->fetch_assoc();
                $lan_cost = $lan_row['cost'];
                $lan_tier = $lan_row['min_nodes'] . " - " . $lan_row['max_nodes'] . " nodes";
            }
        }
        
        $computers = intval($_POST['computers']);
        $computer_cost = $computers * 40000;
        
        $registration_fee = 8500;
        $installation_fee = 10000;
        $processing_fee = 1000;
        
        $total_initial_cost = $registration_fee + $installation_fee + $processing_fee + $lan_cost + $computer_cost;
        $total_monthly_cost = $monthly_bandwidth_cost;
        
        $results = [
            'bandwidth_mbps' => $bandwidth_mbps,
            'monthly_bandwidth_cost' => $monthly_bandwidth_cost,
            'lan_nodes' => $lan_nodes,
            'lan_cost' => $lan_cost,
            'lan_tier' => $lan_tier,
            'computers' => $computers,
            'computer_cost' => $computer_cost,
            'registration_fee' => $registration_fee,
            'installation_fee' => $installation_fee,
            'registration_paid' => 'N/A',
            'installation_paid' => 'N/A',
            'registration_due' => $registration_fee,
            'installation_due' => $installation_fee,
            'processing_fee' => $processing_fee,
            'total_initial' => $total_initial_cost,
            'total_monthly' => $total_monthly_cost
        ];
    }
}
?>

<div class="container-fluid">
    <h2>Cost Computation & Quotation</h2>
    <p class="text-muted">Select an institution to automatically calculate costs based on their requirements.</p>

    <div class="row">
        <!-- Input Form -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white">Select Institution or Enter Manual</div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="computationTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="institution-tab" data-bs-toggle="tab" data-bs-target="#institution" type="button" role="tab">From Institution</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Manual Entry</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="computationTabContent">
                        <!-- Institution Selection Tab -->
                        <div class="tab-pane fade show active" id="institution" role="tabpanel">
                            <form method="POST" action="" id="institutionForm">
                                <div class="mb-3">
                                    <label class="form-label">Select Institution</label>
                                    <select name="institution_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">-- Choose Institution --</option>
                                        <?php 
                                        $institutions->data_seek(0);
                                        while($inst = $institutions->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $inst['institution_id']; ?>"
                                                <?php echo (isset($input['institution_id']) && $input['institution_id'] == $inst['institution_id']) ? 'selected' : ''; ?>>
                                                <?php echo $inst['institution_name']; ?> (<?php echo $inst['category_name']; ?> - <?php echo $inst['bandwidth_mbps']; ?> Mbps)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <?php if (isset($input['institution_id']) && !empty($input['institution_id'])): ?>
                                    <div class="alert alert-info">
                                        <strong>Auto-loaded from institution:</strong><br>
                                        LAN Nodes: <?php echo $results['lan_nodes']; ?><br>
                                        Computers: <?php echo $results['computers']; ?><br>
                                        Bandwidth: <?php echo $results['bandwidth_mbps']; ?> Mbps
                                    </div>
                                <?php endif; ?>
                                
                                <noscript>
                                    <button type="submit" class="btn btn-primary">Load Institution</button>
                                </noscript>
                            </form>
                        </div>
                        
                        <!-- Manual Entry Tab -->
                        <div class="tab-pane fade" id="manual" role="tabpanel">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Bandwidth Package</label>
                                    <select name="bandwidth_id" class="form-control">
                                        <?php 
                                        $bandwidths->data_seek(0);
                                        while($bw = $bandwidths->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $bw['bandwidth_id']; ?>">
                                                <?php echo $bw['bandwidth_mbps']; ?> Mbps (KSh <?php echo number_format($bw['monthly_cost']); ?>/m)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Number of LAN Nodes Required</label>
                                    <input type="number" name="lan_nodes" class="form-control" value="0" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Computers to Purchase</label>
                                    <input type="number" name="computers" class="form-control" value="0" min="0">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Calculate Manual</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reference Table -->
            <div class="card mt-3">
                <div class="card-header">LAN Pricing Reference</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nodes</th>
                                <th>Cost (KSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $lan_pricing->data_seek(0);
                            while($lan = $lan_pricing->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $lan['min_nodes']; ?> - <?php echo $lan['max_nodes']; ?></td>
                                <td><?php echo number_format($lan['cost']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="col-md-7">
            <?php if ($results): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        Quotation Results 
                        <?php if (isset($results['institution_name'])): ?>
                            - <?php echo $results['institution_name']; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-3">Initial Setup & Installation Costs</h5>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Registration Fee (KSh 8,500)</td>
                                    <td class="text-end">
                                        <?php if ($results['registration_paid'] == 'Paid'): ?>
                                            <span class="badge bg-success">Already Paid</span>
                                        <?php elseif ($results['registration_paid'] == 'Unpaid'): ?>
                                            KSh <?php echo number_format($results['registration_due'], 2); ?>
                                            <small class="text-danger">(Due)</small>
                                        <?php else: ?>
                                            KSh <?php echo number_format($results['registration_fee'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Installation Fee (KSh 10,000)</td>
                                    <td class="text-end">
                                        <?php if ($results['installation_paid'] == 'Paid'): ?>
                                            <span class="badge bg-success">Already Paid</span>
                                        <?php elseif ($results['installation_paid'] == 'Unpaid'): ?>
                                            KSh <?php echo number_format($results['installation_due'], 2); ?>
                                            <small class="text-danger">(Due)</small>
                                        <?php else: ?>
                                            KSh <?php echo number_format($results['installation_fee'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Processing / Service Fee</td>
                                    <td class="text-end">KSh <?php echo number_format($results['processing_fee'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>LAN Installation (<?php echo $results['lan_nodes']; ?> nodes)</td>
                                    <td class="text-end">KSh <?php echo number_format($results['lan_cost'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Computer Purchase (<?php echo $results['computers']; ?> units)</td>
                                    <td class="text-end">KSh <?php echo number_format($results['computer_cost'], 2); ?></td>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <td>Total Initial Payable</td>
                                    <td class="text-end">KSh <?php echo number_format($results['total_initial'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <hr>
                        
                        <h5 class="mb-3">Recurring Monthly Costs</h5>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Bandwidth (<?php echo $results['bandwidth_mbps']; ?> Mbps)</td>
                                    <td class="text-end">KSh <?php echo number_format($results['monthly_bandwidth_cost'], 2); ?></td>
                                </tr>
                                <tr class="table-info fw-bold">
                                    <td>Total Monthly Payable</td>
                                    <td class="text-end">KSh <?php echo number_format($results['total_monthly'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="mt-3 d-flex justify-content-between">
                            <button onclick="window.print()" class="btn btn-outline-secondary">Print Quote</button>
                            
                            <?php if (isset($results['institution_name'])): ?>
                                <a href="capture_payments.php?institution_id=<?php echo $input['institution_id']; ?>" 
                                   class="btn btn-success">
                                    Process Payment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card text-center p-5">
                    <div class="card-body">
                        <h4 class="text-muted">Select an institution or enter requirements to generate a quotation</h4>
                        <p class="text-muted mt-2">
                            When you select an institution, their infrastructure requirements will be automatically loaded.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-submit when institution is selected (works with JavaScript enabled)
document.querySelector('select[name="institution_id"]').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('institutionForm').submit();
    }
});

// Handle tab switching
document.querySelector('#manual-tab').addEventListener('click', function() {
    window.location.href = 'computations.php?tab=manual';
});

// Check URL parameter for tab
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('tab') === 'manual') {
    document.querySelector('#manual-tab').click();
}
</script>

<?php include 'footer.php'; ?>
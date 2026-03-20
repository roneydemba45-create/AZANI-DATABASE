<?php
include 'config.php';
include 'header.php';

// Process infrastructure update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_infrastructure'])) {
    $institution_id = $_POST['institution_id'];
    $number_of_users = $_POST['number_of_users'];
    $has_computers = isset($_POST['has_computers']) ? 1 : 0;
    $computers_purchased = $_POST['computers_purchased'] ?? 0;
    $lan_nodes_required = $_POST['lan_nodes_required'] ?? 0;
    
    // Calculate computer cost
    $computer_cost = $computers_purchased * 40000;
    
    // Calculate LAN cost based on nodes
    $lan_cost = 0;
    $lan_pricing_id = null;
    
    if ($lan_nodes_required > 0) {
        $pricing = $conn->query("
            SELECT price_id, cost FROM lan_node_pricing 
            WHERE $lan_nodes_required BETWEEN min_nodes AND max_nodes
        ")->fetch_assoc();
        
        if ($pricing) {
            $lan_pricing_id = $pricing['price_id'];
            $lan_cost = $pricing['cost'];
        }
    }
    
    $infrastructure_ready = isset($_POST['infrastructure_ready']) ? 1 : 0;
    
    $sql = "UPDATE infrastructure_requirements SET 
            number_of_users = $number_of_users,
            has_computers = $has_computers,
            computers_purchased = $computers_purchased,
            total_computer_cost = $computer_cost,
            lan_nodes_required = $lan_nodes_required,
            lan_pricing_id = " . ($lan_pricing_id ?: 'NULL') . ",
            lan_cost = $lan_cost,
            infrastructure_ready = $infrastructure_ready,
            assessment_date = CURDATE()
            WHERE institution_id = $institution_id";
    
    if ($conn->query($sql)) {
        $success = "Infrastructure requirements updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get institutions for dropdown
$institutions = $conn->query("
    SELECT i.*, ic.category_name, ir.* 
    FROM institutions i
    JOIN institution_categories ic ON i.category_id = ic.category_id
    LEFT JOIN infrastructure_requirements ir ON i.institution_id = ir.institution_id
    ORDER BY i.institution_name
");
?>

<div class="container-fluid">
    <h2>Infrastructure Requirements</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Update Infrastructure Requirements
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Select Institution</label>
                            <select name="institution_id" class="form-control" required id="institutionSelect">
                                <option value="">Select Institution</option>
                                <?php 
                                $institutions->data_seek(0);
                                while($inst = $institutions->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $inst['institution_id']; ?>"
                                            data-users="<?php echo $inst['number_of_users'] ?? ''; ?>"
                                            data-computers="<?php echo $inst['has_computers'] ?? ''; ?>"
                                            data-computers-purchased="<?php echo $inst['computers_purchased'] ?? ''; ?>"
                                            data-lan-nodes="<?php echo $inst['lan_nodes_required'] ?? ''; ?>"
                                            data-ready="<?php echo $inst['infrastructure_ready'] ?? ''; ?>">
                                        <?php echo $inst['institution_name']; ?> (<?php echo $inst['category_name']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Number of Users</label>
                            <input type="number" name="number_of_users" class="form-control" required id="numberOfUsers" min="1">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="has_computers" class="form-check-input" id="hasComputers">
                            <label class="form-check-label">Institution has computers</label>
                        </div>
                        
                        <div class="mb-3" id="computersPurchasedDiv" style="display: none;">
                            <label class="form-label">Number of Computers to Purchase</label>
                            <input type="number" name="computers_purchased" class="form-control" id="computersPurchased" min="0" value="0">
                            <small class="text-muted">Cost per computer: KSh 40,000</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">LAN Nodes Required</label>
                            <input type="number" name="lan_nodes_required" class="form-control" id="lanNodes" min="0" value="0">
                            <div id="lanCostInfo" class="mt-2 text-info"></div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="infrastructure_ready" class="form-check-input" id="infrastructureReady">
                            <label class="form-check-label">Infrastructure Ready for Installation</label>
                        </div>
                        
                        <button type="submit" name="update_infrastructure" class="btn btn-primary">Update Infrastructure</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Infrastructure Requirements Summary
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Users</th>
                                <th>Computers</th>
                                <th>LAN Nodes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $institutions->data_seek(0);
                            while($inst = $institutions->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $inst['institution_name']; ?></td>
                                <td><?php echo $inst['number_of_users'] ?? 'Not set'; ?></td>
                                <td>
                                    <?php 
                                    if ($inst['has_computers']) {
                                        echo $inst['computers_purchased'] . ' purchased';
                                    } else {
                                        echo 'No';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $inst['lan_nodes_required'] ?? '0'; ?></td>
                                <td>
                                    <?php if ($inst['infrastructure_ready']): ?>
                                        <span class="badge bg-success">Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Ready</span>
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

<script>
document.getElementById('institutionSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('numberOfUsers').value = selected.dataset.users || '';
    document.getElementById('hasComputers').checked = selected.dataset.computers == '1';
    document.getElementById('computersPurchased').value = selected.dataset.computersPurchased || '0';
    document.getElementById('lanNodes').value = selected.dataset.lanNodes || '0';
    document.getElementById('infrastructureReady').checked = selected.dataset.ready == '1';
    
    toggleComputersField();
    updateLANCost();
});

document.getElementById('hasComputers').addEventListener('change', toggleComputersField);
document.getElementById('lanNodes').addEventListener('input', updateLANCost);

function toggleComputersField() {
    var hasComputers = document.getElementById('hasComputers').checked;
    document.getElementById('computersPurchasedDiv').style.display = hasComputers ? 'block' : 'none';
}

function updateLANCost() {
    var nodes = parseInt(document.getElementById('lanNodes').value) || 0;
    var cost = 0;
    
    if (nodes >= 2 && nodes <= 10) cost = 10000;
    else if (nodes >= 11 && nodes <= 20) cost = 20000;
    else if (nodes >= 21 && nodes <= 40) cost = 30000;
    else if (nodes >= 41 && nodes <= 100) cost = 40000;
    
    document.getElementById('lanCostInfo').innerHTML = nodes > 0 ? 
        'Estimated LAN Cost: KSh ' + cost.toLocaleString() : '';
}
</script>

<?php include 'footer.php'; ?>
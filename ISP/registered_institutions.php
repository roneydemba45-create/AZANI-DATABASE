<?php
include 'config.php';
include 'header.php';

// Get all registered institutions with details
$institutions = $conn->query("
    SELECT i.*, ic.category_name, bp.bandwidth_mbps, bp.monthly_cost,
           cp.full_name as contact_person, cp.phone as contact_phone
    FROM institutions i
    JOIN institution_categories ic ON i.category_id = ic.category_id
    JOIN bandwidth_packages bp ON i.bandwidth_id = bp.bandwidth_id
    LEFT JOIN contact_persons cp ON i.institution_id = cp.institution_id AND cp.is_primary = 1
    ORDER BY i.registration_date DESC
");
?>

<div class="container-fluid">
    <h2>Registered Institutions</h2>
    
    <div class="card">
        <div class="card-header">
            List of All Registered Institutions
        </div>
        <div class="card-body">
            <table class="table table-striped" id="institutionsTable">
                <thead>
                    <tr>
                        <th>Reg. Number</th>
                        <th>Institution Name</th>
                        <th>Category</th>
                        <th>Bandwidth</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($inst = $institutions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $inst['registration_number']; ?></td>
                        <td><?php echo $inst['institution_name']; ?></td>
                        <td><?php echo $inst['category_name']; ?></td>
                        <td><?php echo $inst['bandwidth_mbps']; ?> MBPS</td>
                        <td><?php echo $inst['contact_person']; ?></td>
                        <td><?php echo $inst['contact_phone']; ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($inst['connection_status']) {
                                case 'Active':
                                    $status_class = 'badge bg-success';
                                    break;
                                case 'Suspended':
                                    $status_class = 'badge bg-warning';
                                    break;
                                case 'Disconnected':
                                    $status_class = 'badge bg-danger';
                                    break;
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo $inst['connection_status']; ?></span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($inst['registration_date'])); ?></td>
                        <td>
                            <a href="view_institution.php?id=<?php echo $inst['institution_id']; ?>" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#institutionsTable').DataTable();
});
</script>

<?php include 'footer.php'; ?>
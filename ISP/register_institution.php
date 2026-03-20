<?php
include 'config.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate registration number
    $reg_number = 'AZN' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $institution_name = $conn->real_escape_string($_POST['institution_name']);
    $category_id = $_POST['category_id'];
    $physical_address = $conn->real_escape_string($_POST['physical_address']);
    $postal_address = $conn->real_escape_string($_POST['postal_address']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $bandwidth_id = $_POST['bandwidth_id'];
    
    // Contact person details
    $contact_name = $conn->real_escape_string($_POST['contact_name']);
    $contact_designation = $conn->real_escape_string($_POST['contact_designation']);
    $contact_phone = $conn->real_escape_string($_POST['contact_phone']);
    $contact_email = $conn->real_escape_string($_POST['contact_email']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert institution
        $sql = "INSERT INTO institutions (registration_number, institution_name, category_id, 
                physical_address, postal_address, email, phone, bandwidth_id) 
                VALUES ('$reg_number', '$institution_name', $category_id, 
                '$physical_address', '$postal_address', '$email', '$phone', $bandwidth_id)";
        
        if ($conn->query($sql)) {
            $institution_id = $conn->insert_id;
            
            // Insert contact person
            $sql = "INSERT INTO contact_persons (institution_id, full_name, designation, phone, email, is_primary) 
                    VALUES ($institution_id, '$contact_name', '$contact_designation', 
                    '$contact_phone', '$contact_email', 1)";
            $conn->query($sql);
            
            // Insert infrastructure requirements
            $sql = "INSERT INTO infrastructure_requirements (institution_id) VALUES ($institution_id)";
            $conn->query($sql);
            
            $conn->commit();
            $success = "Institution registered successfully. Registration Number: $reg_number";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Get categories and bandwidth packages for dropdowns
$categories = $conn->query("SELECT * FROM institution_categories");
$bandwidths = $conn->query("SELECT * FROM bandwidth_packages");
?>

<div class="container-fluid">
    <h2>Register Institution</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            Institution Registration Form
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <h5>Institution Details</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Institution Name</label>
                        <input type="text" name="institution_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo $cat['category_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Physical Address</label>
                        <textarea name="physical_address" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Postal Address</label>
                        <input type="text" name="postal_address" class="form-control">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bandwidth Package</label>
                        <select name="bandwidth_id" class="form-control" required>
                            <option value="">Select Bandwidth</option>
                            <?php while($bw = $bandwidths->fetch_assoc()): ?>
                                <option value="<?php echo $bw['bandwidth_id']; ?>">
                                    <?php echo $bw['bandwidth_mbps']; ?> MBPS - KSh <?php echo number_format($bw['monthly_cost'], 2); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <h5 class="mt-4">Contact Person Details</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="contact_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="contact_designation" class="form-control" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="contact_phone" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="contact_email" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Register Institution</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
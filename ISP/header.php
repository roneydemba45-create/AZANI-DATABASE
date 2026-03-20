<?php
// header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azani ISP Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: rgba(255,255,255,0.1);
            padding-left: 25px;
        }
        .sidebar a.active {
            background-color: #3498db;
        }
        .content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        .dashboard-card {
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3 text-white text-center">
                    <h4>Azani ISP</h4>
                    <hr>
                </div>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    Dashboard
                </a>
                <a href="register_institution.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register_institution.php' ? 'active' : ''; ?>">
                    Register Institution
                </a>
                <a href="capture_payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'capture_payments.php' ? 'active' : ''; ?>">
                    Capture Payments
                </a>
                <a href="infrastructure.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'infrastructure.php' ? 'active' : ''; ?>">
                    Infrastructure
                </a>
                <a href="registered_institutions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'registered_institutions.php' ? 'active' : ''; ?>">
                    Registered Institutions
                </a>
                <a href="defaulters.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'defaulters.php' ? 'active' : ''; ?>">
                    Defaulters
                </a>
                <a href="disconnection_issues.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'disconnection_issues.php' ? 'active' : ''; ?>">
                    Disconnection Issues
                </a>
                <a href="computations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'computations.php' ? 'active' : ''; ?>">
                    Computations
                </a>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    Reports
                </a>
                <a href="procedures_demo.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'procedures_demo.php' ? 'active' : ''; ?>">
    Procedures & Automation
</a>
<a href="fines_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'fines_report.php' ? 'active' : ''; ?>">
    Fines Report
</a>
            </div>
            <div class="col-md-10 content">
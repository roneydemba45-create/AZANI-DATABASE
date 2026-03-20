<?php
// config.php - Database configuration
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'azani_isp';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');
?>
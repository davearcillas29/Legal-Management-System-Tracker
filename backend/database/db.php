<?php
// Database configuration
$host = 'localhost';
$username = 'root'; 
$db_password = '';  
$database = 'legal_tracker_db'; 

// Create database connection using MySQLi
$conn = new mysqli($host, $username, $db_password, $database);

// Check if the connection failed
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set character set to UTF-8 for proper text encoding
$conn->set_charset("utf8");
?>
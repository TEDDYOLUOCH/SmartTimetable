<?php
// Database configuration
$host = 'localhost';
$db   = 'smart_timetable'; // Change to your actual database name
$user = 'root';           // Default XAMPP user
$pass = '';               // Default XAMPP password is empty

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 
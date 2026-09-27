<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "hotel_management";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

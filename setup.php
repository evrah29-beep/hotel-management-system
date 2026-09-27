<?php
require_once 'config/database.php';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO admin (name, email, password) VALUES ('System Admin', 'afrikhotel', '$password')";
if ($conn->query($sql) === TRUE) {
    echo "Admin created! You can now log in.";
}
?>
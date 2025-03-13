<?php
$host = "localhost";  // Change if using a different host
$dbname = "employee_manager";  // Your database name
$username = "root";  // Default XAMPP MySQL username
$password = "";  // Default is empty for XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

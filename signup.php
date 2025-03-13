<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    try {
        $stmt = $conn->prepare("INSERT INTO managers (username, password_hash) VALUES (:username, :password_hash)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password_hash', $password);  // Updated to match the column name
        $stmt->execute();
        header("Location: login.html"); // Redirect to login page after successful signup
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

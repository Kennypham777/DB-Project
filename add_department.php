<?php
// Add new department logic
require 'config.php';
session_start();

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departmentName = $_POST['departmentname'];

    try {
        // Insert new department (without supervisor)
        $stmt = $conn->prepare("INSERT INTO departments (departmentname, manager_id) VALUES (:departmentname, :manager_id)");
        $stmt->bindParam(':departmentname', $departmentName);
        $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
        $stmt->execute();

        // Redirect to home page after adding department
        header("Location: home.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Add New Department</h2>

<form action="add_department.php" method="POST">
    <label for="departmentname">Department Name:</label>
    <input type="text" name="departmentname" required>
    <button type="submit">Add Department</button>
</form>

<div class="center-container">
    <a href="home.php" class="btn">Back to Departments</a>
</div>

</body>
</html>

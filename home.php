<?php
// Home page - Shows departments and allows supervisor assignment
require 'config.php';
session_start();

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all departments created by the logged-in manager
$stmt = $conn->prepare("SELECT * FROM departments WHERE manager_id = :manager_id");
$stmt->bindParam(':manager_id', $_SESSION['manager_id']);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navigation Bar for Tabs -->
<div class="navbar">
    <a href="home.php">Departments</a>
    <a href="employees.php">Employees</a>
    <a href="scheduling.php">Scheduling</a>
    <a href="logout.php">Logout</a>
</div>

<h2>Departments</h2>

<!-- Button to add new department -->
<a href="add_department.php">Add New Department</a>

<table>
    <thead>
        <tr>
            <th>Department Name</th>
            <th>Supervisor</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($departments as $department) {
            // Get supervisor name using supervisorSSN from departments table
            $supervisorSSN = $department['supervisorSSN'];
            if ($supervisorSSN) {
                $stmt = $conn->prepare("SELECT name FROM employees WHERE ssn = :ssn");
                $stmt->bindParam(':ssn', $supervisorSSN);
                $stmt->execute();
                $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
                $supervisorName = $supervisor ? $supervisor['name'] : 'No Supervisor Assigned';
            } else {
                $supervisorName = 'No Supervisor Assigned';
            }

            echo "<tr>";
            echo "<td>{$department['departmentname']}</td>";
            echo "<td>{$supervisorName}</td>";
            echo "<td><a href='edit_department.php?departmentID=" . $department['departmentID'] . "'>Edit</a> | <a href='delete_department.php?departmentID=" . $department['departmentID'] . "'>Delete</a></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>

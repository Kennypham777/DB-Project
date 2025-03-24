<?php
// Delete Department page - Allows the manager to delete a department
require 'config.php';
session_start();

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Handle department deletion
if (isset($_GET['departmentID'])) {
    $departmentID = $_GET['departmentID'];

    // Make sure the department belongs to the logged-in manager
    $stmt = $conn->prepare("SELECT * FROM departments WHERE departmentID = :departmentID AND manager_id = :manager_id");
    $stmt->bindParam(':departmentID', $departmentID);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($department) {
        // Remove supervisor assignment from the department
        $stmt = $conn->prepare("UPDATE departments SET supervisorSSN = NULL WHERE departmentID = :departmentID");
        $stmt->bindParam(':departmentID', $departmentID);
        $stmt->execute();

        // Clear department field for employees in this department
        $stmt = $conn->prepare("UPDATE employees SET departmentID = NULL WHERE departmentID = :departmentID");
        $stmt->bindParam(':departmentID', $departmentID);
        $stmt->execute();

        // Delete the department
        $stmt = $conn->prepare("DELETE FROM departments WHERE departmentID = :departmentID");
        $stmt->bindParam(':departmentID', $departmentID);
        $stmt->execute();

        // Redirect to the home page (departments list)
        header("Location: home.php");
        exit();
    } else {
        echo "Department not found or you do not have permission to delete this department.";
        exit();
    }
} else {
    echo "Department ID is required.";
    exit();
}
?>

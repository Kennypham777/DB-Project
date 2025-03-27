<?php
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['ssn'])) {
    $ssn = $_GET['ssn'];

    try {
        // Delete the employee from the supervisors table if they exist
        $stmt = $conn->prepare("DELETE FROM supervisors WHERE ssn = :ssn");
        $stmt->bindParam(':ssn', $ssn);
        $stmt->execute();

        // Delete the employee from the employees table
        $stmt = $conn->prepare("DELETE FROM employees WHERE ssn = :ssn");
        $stmt->bindParam(':ssn', $ssn);
        $stmt->execute();

        // Redirect to the employees list
        header("Location: employees.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No employee found to delete.";
}
?>

<?php
session_start();
require 'config.php';

if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Process the form when it's submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ssn = $_POST['ssn'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $salary = $_POST['salary'];
    $jobrole = $_POST['jobrole'];
    $departmentID = $_POST['departmentID'] ? $_POST['departmentID'] : NULL; // Make department optional
    $isSupervisor = isset($_POST['supervisor']) ? true : false;

    try {
        // Insert employee into the employees table
        $stmt = $conn->prepare("INSERT INTO employees (ssn, name, email, salary, jobrole, departmentID, manager_id) VALUES (:ssn, :name, :email, :salary, :jobrole, :departmentID, :manager_id)");
        $stmt->bindParam(':ssn', $ssn);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':jobrole', $jobrole);
        $stmt->bindParam(':departmentID', $departmentID);
        $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
        $stmt->execute();

        // If the employee is a supervisor, insert them into the supervisors table
        if ($isSupervisor) {
            $stmt = $conn->prepare("INSERT INTO supervisors (ssn) VALUES (:ssn)");
            $stmt->bindParam(':ssn', $ssn);
            $stmt->execute();
        }

        // Redirect back to the employee list page
        header("Location: employees.php");
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
    <title>Add Employee</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Add New Employee</h2>

<form action="add_employee.php" method="POST">
    <label for="ssn">SSN:</label>
    <input type="text" name="ssn" required><br>

    <label for="name">Name:</label>
    <input type="text" name="name" required><br>

    <label for="email">Email:</label>
    <input type="email" name="email" required><br>

    <label for="salary">Hourly Rate:</label>
    <input type="number" name="salary" required><br>

    <label for="jobrole">Job Role:</label>
    <input type="text" name="jobrole" required><br>

    <label for="departmentID">Department:</label>
    <select name="departmentID">
        <option value="">-- Select Department (Optional) --</option>
        <?php
        // Get departments for dropdown
        $stmt = $conn->prepare("SELECT * FROM departments WHERE manager_id = :manager_id");
        $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($departments as $department) {
            echo "<option value='" . $department['departmentID'] . "'>" . $department['departmentname'] . "</option>";
        }
        ?>
    </select><br>

    <label for="supervisor">Is Supervisor:</label>
    <input type="checkbox" name="supervisor"><br>

    <button type="submit">Add Employee</button>
</form>

</body>
</html>

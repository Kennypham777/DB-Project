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

    // Fetch the current employee details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE ssn = :ssn AND manager_id = :manager_id");
    $stmt->bindParam(':ssn', $ssn);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo "Employee not found.";
        exit();
    }

    // Fetch departments for the dropdown
    $departments = [];
    $stmt = $conn->prepare("SELECT departmentID, departmentname FROM departments WHERE manager_id = :manager_id");
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if employee is a supervisor
    $is_supervisor = false;
    $stmt = $conn->prepare("SELECT 1 FROM supervisors WHERE ssn = :ssn");
    $stmt->bindParam(':ssn', $ssn);
    $stmt->execute();
    if ($stmt->fetch()) {
        $is_supervisor = true;
    }

    // Handle form submission to update employee details
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $salary = $_POST['salary'];
        $jobrole = $_POST['jobrole'];
        // Make department optional, set to NULL if not selected
        $departmentID = $_POST['departmentID'] ? $_POST['departmentID'] : NULL;
        $is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;

        try {
            // Update employee details in the employees table
            $stmt = $conn->prepare("UPDATE employees SET name = :name, email = :email, salary = :salary, jobrole = :jobrole, departmentID = :departmentID WHERE ssn = :ssn");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':salary', $salary);
            $stmt->bindParam(':jobrole', $jobrole);
            $stmt->bindParam(':departmentID', $departmentID);
            $stmt->bindParam(':ssn', $ssn);
            $stmt->execute();

            // If employee is marked as supervisor, add them to the supervisors table
            if ($is_supervisor) {
                $stmt = $conn->prepare("INSERT IGNORE INTO supervisors (ssn) VALUES (:ssn)");
                $stmt->bindParam(':ssn', $ssn);
                $stmt->execute();
            } else {
                // If employee is no longer a supervisor, remove from the supervisors table
                $stmt = $conn->prepare("DELETE FROM supervisors WHERE ssn = :ssn");
                $stmt->bindParam(':ssn', $ssn);
                $stmt->execute();
            }

            // Redirect back to the employees page
            header("Location: employees.php");
            exit();

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    echo "No employee found to edit.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Edit Employee</h2>

<form action="edit_employee.php?ssn=<?php echo $employee['ssn']; ?>" method="POST">
    <label for="name">Name:</label>
    <input type="text" name="name" value="<?php echo $employee['name']; ?>" required><br>

    <label for="email">Email:</label>
    <input type="email" name="email" value="<?php echo $employee['email']; ?>" required><br>

    <label for="salary">Salary:</label>
    <input type="number" name="salary" value="<?php echo $employee['salary']; ?>" required><br>

    <label for="jobrole">Job Role:</label>
    <input type="text" name="jobrole" value="<?php echo $employee['jobrole']; ?>" required><br>

    <label for="departmentID">Department:</label>
    <select name="departmentID">
        <option value="">-- Select Department (Optional) --</option>
        <?php
        foreach ($departments as $department) {
            $selected = ($employee['departmentID'] == $department['departmentID']) ? 'selected' : '';
            echo "<option value='" . $department['departmentID'] . "' $selected>" . $department['departmentname'] . "</option>";
        }
        ?>
    </select><br>

    <label for="is_supervisor">Is Supervisor:</label>
    <input type="checkbox" name="is_supervisor" <?php echo $is_supervisor ? 'checked' : ''; ?>><br>

    <button type="submit">Save Changes</button>
</form>

<a href="employees.php">Back to Employees List</a>

</body>
</html>

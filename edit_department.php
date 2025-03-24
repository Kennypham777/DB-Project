<?php
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Check if departmentID is provided in the URL
if (isset($_GET['departmentID'])) {
    $departmentID = $_GET['departmentID'];

    // Fetch department details
    $stmt = $conn->prepare("SELECT * FROM departments WHERE departmentID = :departmentID AND manager_id = :manager_id");
    $stmt->bindParam(':departmentID', $departmentID);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {
        echo "Department not found or you don't have permission to edit this department.";
        exit();
    }

    // Fetch only employees who are supervisors
    $stmt = $conn->prepare("SELECT e.ssn, e.name 
                            FROM employees e 
                            JOIN supervisors s ON e.ssn = s.ssn 
                            WHERE e.manager_id = :manager_id");
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the form submission when it's submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $departmentname = $_POST['departmentname'];
        $supervisorSSN = $_POST['supervisorSSN'];

        try {
            // Check if the supervisor is already assigned to another department
            if ($supervisorSSN) {
                $stmt = $conn->prepare("SELECT * FROM departments WHERE supervisorSSN = :supervisorSSN");
                $stmt->bindParam(':supervisorSSN', $supervisorSSN);
                $stmt->execute();
                $existingSupervisor = $stmt->fetch(PDO::FETCH_ASSOC);

                // If the supervisor is already assigned to another department, prevent the update
                if ($existingSupervisor && $existingSupervisor['departmentID'] != $departmentID) {
                    echo "The selected supervisor is already assigned to another department.";
                    echo '<br><a href="home.php">Back to Home</a>';
                    exit();
                }
            }

            // Update department details
            $stmt = $conn->prepare("UPDATE departments SET departmentname = :departmentname, supervisorSSN = :supervisorSSN WHERE departmentID = :departmentID");
            $stmt->bindParam(':departmentname', $departmentname);
            $stmt->bindParam(':supervisorSSN', $supervisorSSN);
            $stmt->bindParam(':departmentID', $departmentID);
            $stmt->execute();

            // If a supervisor is selected, update their departmentID
            if ($supervisorSSN) {
                $stmt = $conn->prepare("UPDATE employees SET departmentID = :departmentID WHERE ssn = :ssn");
                $stmt->bindParam(':departmentID', $departmentID);
                $stmt->bindParam(':ssn', $supervisorSSN);
                $stmt->execute();
            }

            // Redirect back to home page after successful update
            header("Location: home.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Edit Department</h2>

<form action="edit_department.php?departmentID=<?php echo $department['departmentID']; ?>" method="POST">
    <label for="departmentname">Department Name:</label>
    <input type="text" name="departmentname" value="<?php echo $department['departmentname']; ?>" required><br>

    <label for="supervisorSSN">Assign Supervisor:</label>
    <select name="supervisorSSN">
        <option value="">-- Select Supervisor --</option>
        <?php
        // Loop through supervisors and populate the dropdown
        foreach ($supervisors as $supervisor) {
            $selected = ($department['supervisorSSN'] == $supervisor['ssn']) ? 'selected' : '';
            echo "<option value='" . $supervisor['ssn'] . "' $selected>" . $supervisor['name'] . "</option>";
        }
        ?>
    </select><br>

    <button type="submit">Save Changes</button>
</form>

<a href="home.php">Back to Departments</a>

</body>
</html>

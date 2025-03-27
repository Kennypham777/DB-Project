<?php
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Get the SSN of the employee whose payroll is being viewed
if (isset($_GET['ssn'])) {
    $employeeSSN = $_GET['ssn'];

    // Fetch employee details including salary
    $stmt = $conn->prepare("SELECT name, salary FROM employees WHERE ssn = :ssn AND manager_id = :manager_id");
    $stmt->bindParam(':ssn', $employeeSSN);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo "Employee not found or you don't have permission to view their payroll.";
        exit();
    }

    // Fetch all payroll records for this employee, sorted by the most recent
    $stmt = $conn->prepare("SELECT * FROM payroll WHERE employeeSSN = :ssn ORDER BY paymentdate DESC");
    $stmt->bindParam(':ssn', $employeeSSN);
    $stmt->execute();
    $payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the form submission to add a payroll record
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payroll'])) {
        $hoursworked = $_POST['hoursworked'];
        $paymentamount = $hoursworked * $employee['salary']; // Calculate payment amount based on salary and hours worked
        $paymentdate = $_POST['paymentdate'];

        try {
            // Insert the new payroll record into the database
            $stmt = $conn->prepare("INSERT INTO payroll (employeeSSN, paymentamount, paymentdate, hoursworked, manager_id) 
                                    VALUES (:employeeSSN, :paymentamount, :paymentdate, :hoursworked, :manager_id)");
            $stmt->bindParam(':employeeSSN', $employeeSSN);
            $stmt->bindParam(':paymentamount', $paymentamount);
            $stmt->bindParam(':paymentdate', $paymentdate);
            $stmt->bindParam(':hoursworked', $hoursworked);
            $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
            $stmt->execute();

            // Redirect to the same page to avoid form resubmission
            header("Location: view_payroll.php?ssn=" . $employeeSSN);
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Handle deletion of payroll records
    if (isset($_GET['delete_payrollID'])) {
        $payrollID = $_GET['delete_payrollID'];

        try {
            // Delete the payroll record
            $stmt = $conn->prepare("DELETE FROM payroll WHERE payrollID = :payrollID AND employeeSSN = :ssn");
            $stmt->bindParam(':payrollID', $payrollID);
            $stmt->bindParam(':ssn', $employeeSSN);
            $stmt->execute();

            // Redirect to the same page after deletion
            header("Location: view_payroll.php?ssn=" . $employeeSSN);
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    echo "No employee selected.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payroll - <?php echo htmlspecialchars($employee['name']); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <a href="home.php">Departments</a>
    <a href="employees.php">Employees</a>
    <a href="scheduling.php">Scheduling</a>
    <a href="logout.php">Logout</a>
</div>

<h2>Payroll Records for <?php echo htmlspecialchars($employee['name']); ?></h2>

<!-- Add Payroll Form -->
<h3>Add New Payroll Record</h3>
<form action="view_payroll.php?ssn=<?php echo $employeeSSN; ?>" method="POST">
    <label for="hoursworked">Hours Worked:</label>
    <input type="number" name="hoursworked" step="0.01" required oninput="calculatePayment()"><br><br>

    <label for="paymentamount">Payment Amount:</label>
    <input type="number" name="paymentamount" value="<?php echo $employee['salary']; ?>" readonly><br><br>

    <label for="paymentdate">Payment Date:</label>
    <input type="date" name="paymentdate" required><br><br>

    <button type="submit" name="add_payroll">Add Payroll</button>
</form>

<!-- Payroll Records Table -->
<h3>Payroll Records</h3>
<table>
    <thead>
        <tr>
            <th>Payment Amount</th>
            <th>Payment Date</th>
            <th>Hours Worked</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($payrollRecords as $record) {
            echo "<tr>";
            echo "<td>\${$record['paymentamount']}</td>";
            echo "<td>{$record['paymentdate']}</td>";
            echo "<td>{$record['hoursworked']}</td>";
            echo "<td><a href='view_payroll.php?ssn={$employeeSSN}&delete_payrollID={$record['payrollID']}'>Delete</a></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<script>
    // Function to calculate payment amount based on hours worked
    function calculatePayment() {
        var hoursWorked = parseFloat(document.querySelector('[name="hoursworked"]').value);
        var salary = <?php echo $employee['salary']; ?>; // Employee's salary fetched from the database
        var paymentAmount = hoursWorked * salary;
        document.querySelector('[name="paymentamount"]').value = paymentAmount.toFixed(2);
    }
</script>

</body>
</html>

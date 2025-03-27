<?php
// Start session and require config
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Store sorting preferences in session
if (isset($_GET['sortBy'])) {
    $_SESSION['sortBy'] = $_GET['sortBy'];
}
if (isset($_GET['order'])) {
    $_SESSION['order'] = $_GET['order'];
}

// Default sorting values (if none in session)
$sortBy = isset($_SESSION['sortBy']) ? $_SESSION['sortBy'] : 'name';
$order = isset($_SESSION['order']) && $_SESSION['order'] == 'desc' ? 'desc' : 'asc';

// SQL query to fetch employees sorted by the selected criteria
$query = "SELECT employees.ssn, employees.name, employees.email, employees.salary, employees.jobrole, employees.departmentID, departments.departmentname, 
                 IF(EXISTS(SELECT 1 FROM supervisors WHERE ssn = employees.ssn), 'Yes', 'No') AS is_supervisor 
          FROM employees
          LEFT JOIN departments ON employees.departmentID = departments.departmentID
          WHERE employees.manager_id = :manager_id
          ORDER BY $sortBy $order";

$stmt = $conn->prepare($query);
$stmt->bindParam(':manager_id', $_SESSION['manager_id']);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees</title>
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

<h2>Employees</h2>

<!-- Add Employee Button -->
<a href="add_employee.php">Add New Employee</a><br><br>

<!-- Sort Buttons -->
<div class="sort-buttons">
    <a href="employees.php?sortBy=departmentname&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>">Sort by Department</a> |
    <a href="employees.php?sortBy=name&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>">Sort by Name</a> |
    <a href="employees.php?sortBy=is_supervisor&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>">Sort by Supervisor Status</a>
</div>

<!-- Employees Table -->
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Hourly Rate</th>
            <th>Job Role</th>
            <th>Department</th>
            <th>Supervisor Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($employees as $employee) {
            echo "<tr>";
            echo "<td>{$employee['name']}</td>";
            echo "<td>{$employee['email']}</td>";
            echo "<td>\${$employee['salary']}</td>";
            echo "<td>{$employee['jobrole']}</td>";
            echo "<td>{$employee['departmentname']}</td>";
            echo "<td>{$employee['is_supervisor']}</td>";
            echo "<td>
                    <a href='edit_employee.php?ssn={$employee['ssn']}'>Edit</a> | 
                    <a href='delete_employee.php?ssn={$employee['ssn']}'>Delete</a> | 
                    <a href='view_payroll.php?ssn={$employee['ssn']}'>View Payroll</a> | 
                    <a href='view_schedule.php?ssn={$employee['ssn']}'>View/Edit Schedule</a>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>

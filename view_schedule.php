<?php
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Get the SSN of the employee whose schedule is being viewed/edited
if (isset($_GET['ssn'])) {
    $employeeSSN = $_GET['ssn'];

    // Fetch employee details (name) from the database
    $stmt = $conn->prepare("SELECT name FROM employees WHERE ssn = :ssn AND manager_id = :manager_id");
    $stmt->bindParam(':ssn', $employeeSSN);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the employee exists
    if (!$employee) {
        echo "Employee not found.";
        exit();
    }

    // Fetch current schedule (shiftstart and shiftend for each day) from the database
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE employeeSSN = :ssn AND manager_id = :manager_id");
    $stmt->bindParam(':ssn', $employeeSSN);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    // Process the form submission to save schedule (shiftstart and shiftend for each day)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_schedule'])) {
        // Get the start/end times for each day
        $startTimes = $_POST['starttime'];  // Array of start times for each day
        $endTimes = $_POST['endtime'];  // Array of end times for each day

        try {
            // Update schedule in the database (or insert if new)
            if ($schedule) {
                $stmt = $conn->prepare("UPDATE schedules SET
                    monday_start = :monday_start, monday_end = :monday_end,
                    tuesday_start = :tuesday_start, tuesday_end = :tuesday_end,
                    wednesday_start = :wednesday_start, wednesday_end = :wednesday_end,
                    thursday_start = :thursday_start, thursday_end = :thursday_end,
                    friday_start = :friday_start, friday_end = :friday_end,
                    saturday_start = :saturday_start, saturday_end = :saturday_end,
                    sunday_start = :sunday_start, sunday_end = :sunday_end
                    WHERE employeeSSN = :ssn AND manager_id = :manager_id");

                // Bind the start and end times for each day
                $stmt->bindParam(':monday_start', $startTimes[0]);
                $stmt->bindParam(':monday_end', $endTimes[0]);
                $stmt->bindParam(':tuesday_start', $startTimes[1]);
                $stmt->bindParam(':tuesday_end', $endTimes[1]);
                $stmt->bindParam(':wednesday_start', $startTimes[2]);
                $stmt->bindParam(':wednesday_end', $endTimes[2]);
                $stmt->bindParam(':thursday_start', $startTimes[3]);
                $stmt->bindParam(':thursday_end', $endTimes[3]);
                $stmt->bindParam(':friday_start', $startTimes[4]);
                $stmt->bindParam(':friday_end', $endTimes[4]);
                $stmt->bindParam(':saturday_start', $startTimes[5]);
                $stmt->bindParam(':saturday_end', $endTimes[5]);
                $stmt->bindParam(':sunday_start', $startTimes[6]);
                $stmt->bindParam(':sunday_end', $endTimes[6]);
                $stmt->bindParam(':ssn', $employeeSSN);
                $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
                $stmt->execute();
            } else {
                // If no schedule exists, insert a new record
                $stmt = $conn->prepare("INSERT INTO schedules (employeeSSN, monday_start, monday_end, tuesday_start, tuesday_end, wednesday_start, wednesday_end, thursday_start, thursday_end, friday_start, friday_end, saturday_start, saturday_end, sunday_start, sunday_end, manager_id)
                VALUES (:ssn, :monday_start, :monday_end, :tuesday_start, :tuesday_end, :wednesday_start, :wednesday_end, :thursday_start, :thursday_end, :friday_start, :friday_end, :saturday_start, :saturday_end, :sunday_start, :sunday_end, :manager_id)");

                // Bind the start and end times for each day
                $stmt->bindParam(':monday_start', $startTimes[0]);
                $stmt->bindParam(':monday_end', $endTimes[0]);
                $stmt->bindParam(':tuesday_start', $startTimes[1]);
                $stmt->bindParam(':tuesday_end', $endTimes[1]);
                $stmt->bindParam(':wednesday_start', $startTimes[2]);
                $stmt->bindParam(':wednesday_end', $endTimes[2]);
                $stmt->bindParam(':thursday_start', $startTimes[3]);
                $stmt->bindParam(':thursday_end', $endTimes[3]);
                $stmt->bindParam(':friday_start', $startTimes[4]);
                $stmt->bindParam(':friday_end', $endTimes[4]);
                $stmt->bindParam(':saturday_start', $startTimes[5]);
                $stmt->bindParam(':saturday_end', $endTimes[5]);
                $stmt->bindParam(':sunday_start', $startTimes[6]);
                $stmt->bindParam(':sunday_end', $endTimes[6]);
                $stmt->bindParam(':ssn', $employeeSSN);
                $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
                $stmt->execute();
            }

            // Reload the schedule after saving to show it in a table
            // Re-fetch the updated schedule from the database
            $stmt = $conn->prepare("SELECT * FROM schedules WHERE employeeSSN = :ssn AND manager_id = :manager_id");
            $stmt->bindParam(':ssn', $employeeSSN);
            $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
            $stmt->execute();
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>View/Edit Schedule - <?php echo htmlspecialchars($employee['name']); ?></title>
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

<h2>Schedule for <?php echo htmlspecialchars($employee['name']); ?></h2>
<!-- Display saved schedule -->
<?php if ($schedule): ?>
    <table border="1">
        <!-- First row for days of the week -->
        <tr>
            <th>Employee Name</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Saturday</th>
            <th>Sunday</th>
        </tr>

        <!-- Row for the employee's schedule -->
        <tr>
            <td><?php echo htmlspecialchars($employee['name']); ?></td>
            
            <!-- Monday schedule -->
<td>
    <?php 
        echo ($schedule['monday_start'] == '00:00:00' || !$schedule['monday_start']) ? '-' : (new DateTime($schedule['monday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['monday_end']))->format('g:i A');
    ?>
</td>

<!-- Tuesday schedule -->
<td>
    <?php 
        echo ($schedule['tuesday_start'] == '00:00:00' || !$schedule['tuesday_start']) ? '-' : (new DateTime($schedule['tuesday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['tuesday_end']))->format('g:i A');
    ?>
</td>

<!-- Wednesday schedule -->
<td>
    <?php 
        echo ($schedule['wednesday_start'] == '00:00:00' || !$schedule['wednesday_start']) ? '-' : (new DateTime($schedule['wednesday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['wednesday_end']))->format('g:i A');
    ?>
</td>

<!-- Thursday schedule -->
<td>
    <?php 
        echo ($schedule['thursday_start'] == '00:00:00' || !$schedule['thursday_start']) ? '-' : (new DateTime($schedule['thursday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['thursday_end']))->format('g:i A');
    ?>
</td>

<!-- Friday schedule -->
<td>
    <?php 
        echo ($schedule['friday_start'] == '00:00:00' || !$schedule['friday_start']) ? '-' : (new DateTime($schedule['friday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['friday_end']))->format('g:i A');
    ?>
</td>

<!-- Saturday schedule -->
<td>
    <?php 
        echo ($schedule['saturday_start'] == '00:00:00' || !$schedule['saturday_start']) ? '-' : (new DateTime($schedule['saturday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['saturday_end']))->format('g:i A');
    ?>
</td>

<!-- Sunday schedule -->
<td>
    <?php 
        echo ($schedule['sunday_start'] == '00:00:00' || !$schedule['sunday_start']) ? '-' : (new DateTime($schedule['sunday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['sunday_end']))->format('g:i A');
    ?>
</td>

        </tr>
    </table>
<?php endif; ?>
<!-- Schedule Form -->
<form action="view_schedule.php?ssn=<?php echo $employeeSSN; ?>" method="POST">
    <h3>Select Days of the Week:</h3>
    <!--<p>Note: 12:00AM to 12:00AM is reserved to indicate the employee doesn't work on that day.</p>-->
    <?php
    $daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    foreach ($daysOfWeek as $index => $day) {
        // Dynamically retrieve the start and end times from the $schedule array for each day
        $startTime = $schedule[strtolower($day) . '_start'] ?? ''; // Convert to lowercase for consistency
        $endTime = $schedule[strtolower($day) . '_end'] ?? '';

        // If start or end time is 00:00:00, set it to empty string to display as --:-- --
        if ($startTime == '00:00:00') $startTime = '';
        if ($endTime == '00:00:00') $endTime = '';

        echo "<label>$day</label><br>";
        echo "<label>Start Time: <input type='time' name='starttime[]' value='$startTime'></label><br>";
        echo "<label>End Time: <input type='time' name='endtime[]' value='$endTime'></label><br><br>";
    }
    ?>

    <button type="submit" name="save_schedule">Save Schedule</button>
</form>




</body>
</html>

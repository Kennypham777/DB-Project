<?php
session_start();
require 'config.php';

// Check if the manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all employees for the logged-in manager, including department and supervisor status
$stmt = $conn->prepare("
    SELECT e.ssn, e.name, e.departmentID, 
           CASE WHEN d.supervisorSSN = e.ssn THEN 1 ELSE 0 END AS is_supervisor 
    FROM employees e 
    LEFT JOIN departments d ON e.departmentID = d.departmentID 
    WHERE e.manager_id = :manager_id
");
$stmt->bindParam(':manager_id', $_SESSION['manager_id']);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch schedule for each employee
$schedules = [];
foreach ($employees as $employee) {
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE employeeSSN = :ssn AND manager_id = :manager_id");
    $stmt->bindParam(':ssn', $employee['ssn']);
    $stmt->bindParam(':manager_id', $_SESSION['manager_id']);
    $stmt->execute();
    $schedules[$employee['ssn']] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to check if two shifts overlap
function isOverlap($start1, $end1, $start2, $end2) {
    return (strtotime($start1) < strtotime($end2) && strtotime($end1) > strtotime($start2));
}

// Array to store overlapping shifts for each day
$overlaps = [
    'monday' => [],
    'tuesday' => [],
    'wednesday' => [],
    'thursday' => [],
    'friday' => [],
    'saturday' => [],
    'sunday' => []
];

// Loop through employees and check for overlaps
foreach ($employees as $employee) {
    if ($employee['is_supervisor']) continue; // Skip supervisors in conflict detection

    $schedule = $schedules[$employee['ssn']] ?? null;
    if ($schedule) {
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $startKey = $day . '_start';
            $endKey = $day . '_end';
            if ($schedule[$startKey] != '00:00:00' && $schedule[$endKey] != '00:00:00') {
                foreach ($employees as $otherEmployee) {
                    if ($otherEmployee['ssn'] != $employee['ssn'] 
                        && !$otherEmployee['is_supervisor'] // Skip supervisors
                        && $otherEmployee['departmentID'] == $employee['departmentID']) { // Only compare within the same department

                        $otherSchedule = $schedules[$otherEmployee['ssn']] ?? null;
                        if ($otherSchedule && $otherSchedule[$startKey] != '00:00:00' && $otherSchedule[$endKey] != '00:00:00') {
                            // Check for overlap
                            if (isOverlap($schedule[$startKey], $schedule[$endKey], $otherSchedule[$startKey], $otherSchedule[$endKey])) {
                                $overlaps[$day][$employee['ssn']][] = $otherEmployee['ssn'];
                                $overlaps[$day][$otherEmployee['ssn']][] = $employee['ssn'];
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedules</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .overlap {
            background-color: red; /* Highlight overlapping shifts */
            color: white;
        }

        /* Add hover effect for the overlapping shifts */
        .highlighted {
            background-color:rgb(255, 119, 119); /* Lighter red for hover highlights */
        }
    </style>
<script>
    // Function to add event listeners to overlap cells
    function addHoverEvents() {
        // Get all the overlap cells
        let overlapCells = document.querySelectorAll('.overlap');
        
        overlapCells.forEach(cell => {
            // Add mouseover event to highlight related cells
            cell.addEventListener('mouseover', function() {
                let day = cell.getAttribute('data-day');
                let departmentId = cell.getAttribute('data-department-id');
                
                // Find all related overlaps for the same day and same department
                let relatedCells = document.querySelectorAll(`.overlap[data-day="${day}"][data-department-id="${departmentId}"]`);
                relatedCells.forEach(relatedCell => {
                    relatedCell.classList.add('highlighted');
                });
            });
            
            // Remove highlighting on mouseout
            cell.addEventListener('mouseout', function() {
                let day = cell.getAttribute('data-day');
                let departmentId = cell.getAttribute('data-department-id');
                
                // Remove highlighting for all related overlaps on the same day and same department
                let relatedCells = document.querySelectorAll(`.overlap[data-day="${day}"][data-department-id="${departmentId}"]`);
                relatedCells.forEach(relatedCell => {
                    relatedCell.classList.remove('highlighted');
                });
            });
        });
    }
</script>

</head>
<body onload="addHoverEvents()">

<!-- Navigation Bar -->
<div class="navbar">
    <a href="home.php">Departments</a>
    <a href="employees.php">Employees</a>
    <a href="scheduling.php">Scheduling</a>
    <a href="logout.php">Logout</a>
</div>

<h2>Employee Schedules</h2>

<!-- Display schedules for all employees -->
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

    <!-- Loop through employees to display each schedule -->
    <?php foreach ($employees as $employee): ?>
        <tr>
            <!-- Employee name with clickable link -->
            <td>
                <a href="view_schedule.php?ssn=<?php echo $employee['ssn']; ?>">
                    <?php echo htmlspecialchars($employee['name']); ?>
                </a>
            </td>
            
            <!-- Monday schedule -->
            <td class="<?php echo (isset($overlaps['monday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="monday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    $schedule = $schedules[$employee['ssn']] ?? null;
                    echo ($schedule && $schedule['monday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['monday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['monday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Tuesday schedule -->
            <td class="<?php echo (isset($overlaps['tuesday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="tuesday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['tuesday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['tuesday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['tuesday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Wednesday schedule -->
            <td class="<?php echo (isset($overlaps['wednesday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="wednesday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['wednesday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['wednesday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['wednesday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Thursday schedule -->
            <td class="<?php echo (isset($overlaps['thursday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="thursday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['thursday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['thursday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['thursday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Friday schedule -->
            <td class="<?php echo (isset($overlaps['friday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="friday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['friday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['friday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['friday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Saturday schedule -->
            <td class="<?php echo (isset($overlaps['saturday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="saturday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['saturday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['saturday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['saturday_end']))->format('g:i A') : '-';
                ?>
            </td>

            <!-- Sunday schedule -->
            <td class="<?php echo (isset($overlaps['sunday'][$employee['ssn']])) ? 'overlap' : ''; ?>"
                data-employee-ssn="<?php echo $employee['ssn']; ?>"
                data-day="sunday"
                data-department-id="<?php echo $employee['departmentID']; ?>">
                <?php 
                    echo ($schedule && $schedule['sunday_start'] != '00:00:00') ? 
                        (new DateTime($schedule['sunday_start']))->format('g:i A') . ' - ' . (new DateTime($schedule['sunday_end']))->format('g:i A') : '-';
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>


</body>
</html>

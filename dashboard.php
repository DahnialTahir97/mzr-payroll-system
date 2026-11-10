
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

// Total Active Employees
$stmt = $conn->query(
    "SELECT COUNT(*) FROM employees
     WHERE employment_status = 'active'"
);
$total_employees = $stmt->fetchColumn();

// Total Attendance Today
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM attendance
     WHERE attendance_date = CURDATE()
     AND status = 'present'"
);
$stmt->execute();
$total_attendance = $stmt->fetchColumn();

// Total Payroll This Month
$current_month = date('Y-m');

$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM payroll
     WHERE salary_month = ?"
);
$stmt->execute([$current_month]);
$total_payroll = $stmt->fetchColumn();

// Total Net Salary This Month
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(net_salary), 0)
     FROM payroll
     WHERE salary_month = ?"
);
$stmt->execute([$current_month]);
$total_salary = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - MZR FOOD SERVICES</title>
</head>

<body>

<h1>MZR FOOD SERVICES</h1>

<h2>Payroll Management System</h2>

<hr>

<h3>
    Welcome,
    <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
</h3>

<p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

<hr>

<h2>Dashboard Summary</h2>

<table border="1" cellpadding="12">

<tr>
    <th>Total Active Employees</th>
    <th>Attendance Today</th>
    <th>Payroll Records This Month</th>
    <th>Total Net Salary This Month</th>
</tr>

<tr>
    <td><?php echo $total_employees; ?></td>
    <td><?php echo $total_attendance; ?></td>
    <td><?php echo $total_payroll; ?></td>
    <td>RM <?php echo number_format($total_salary, 2); ?></td>
</tr>

</table>

<hr>

<h2>System Modules</h2>

<ul>

    <li>
        <a href="employees.php">Employee Management</a>
        - Manage employee information
    </li>

    <li>
        <a href="attendance.php">Attendance & Overtime</a>
        - Record employee attendance and overtime
    </li>

    <li>
        <a href="components.php">Allowances & Deductions</a>
        - Manage salary allowances and deductions
    </li>

    <li>
        <a href="payroll.php">Payroll Calculation</a>
        - Calculate employee salaries
    </li>

    <li>
        <a href="payslip.php">Payslip Generation</a>
        - View and print employee payslips
    </li>

    <li>
        <a href="reports.php">Salary Reports</a>
        - View monthly salary reports
    </li>

</ul>

<hr>

<h2>Account</h2>

<ul>
    <li>
        <a href="logout.php">Logout</a>
    </li>
</ul>

</body>
</html>

<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$payrollId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($payrollId <= 0) {
    die("Invalid payslip ID. Please return to Payroll Management.");
}

$stmt = $conn->prepare(
    "SELECT p.*, e.employee_code, e.full_name, e.position,
            e.ic_number, e.phone, e.email
     FROM payroll p
     INNER JOIN employees e ON p.employee_id = e.id
     WHERE p.id = ?"
);
$stmt->execute([$payrollId]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    die("Payslip record not found.");
}

$stmt = $conn->prepare(
    "SELECT component_name, amount
     FROM salary_components
     WHERE employee_id = ?
       AND component_type = 'allowance'
       AND LEFT(component_month, 7) = ?
     ORDER BY component_name"
);
$stmt->execute([$payroll['employee_id'], $payroll['salary_month']]);
$allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare(
    "SELECT component_name, amount
     FROM salary_components
     WHERE employee_id = ?
       AND component_type = 'deduction'
       AND LEFT(component_month, 7) = ?
     ORDER BY component_name"
);
$stmt->execute([$payroll['employee_id'], $payroll['salary_month']]);
$deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function moneyValue($value): string {
    return number_format((float)($value ?? 0), 2);
}

$statutoryItems = [
    'EPF (KWSP)' => (float)($payroll['epf_employee'] ?? 0),
    'SOCSO (PERKESO)' => (float)($payroll['socso_employee'] ?? 0),
    'EIS (SIP)' => (float)($payroll['eis_employee'] ?? 0),
    'Monthly Tax Deduction (PCB)' => (float)($payroll['pcb'] ?? 0)
];

$employerItems = [
    'EPF (KWSP)' => (float)($payroll['epf_employer'] ?? 0),
    'SOCSO (PERKESO)' => (float)($payroll['socso_employer'] ?? 0),
    'EIS (SIP)' => (float)($payroll['eis_employer'] ?? 0)
];

$totalStatutoryEmployee = array_sum($statutoryItems);
$totalEmployerContribution = array_sum($employerItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - MZR FOOD SERVICES</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 25px; color: #263238; }
        .payslip { max-width: 850px; background: #fff; margin: 20px auto; padding: 35px; border: 1px solid #ddd; }
        .company { text-align: center; border-bottom: 2px solid #243b53; padding-bottom: 15px; }
        h2, h3 { color: #243b53; }
        h3 { margin-top: 28px; margin-bottom: 8px; }
        .details { margin-top: 22px; line-height: 1.9; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 10px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #243b53; color: white; }
        .amount { text-align: right; white-space: nowrap; }
        .total { font-weight: bold; background: #f0f3f6; }
        .net { font-size: 18px; font-weight: bold; background: #e2f0d9; }
        .note { color: #666; font-size: 12px; line-height: 1.5; }
        .actions { max-width: 850px; margin: auto; display: flex; gap: 10px; }
        button, .back { padding: 10px 16px; background: #1769aa; color: white; border: 0; text-decoration: none; cursor: pointer; border-radius: 4px; font-size: 14px; }
        @media (max-width: 600px) {
            body { padding: 8px; }
            .payslip { padding: 16px; }
            .details { grid-template-columns: 1fr; }
            th, td { padding: 8px 6px; font-size: 13px; }
        }
        @media print {
            body { background: white; padding: 0; }
            .actions { display: none; }
            .payslip { border: 0; margin: 0 auto; padding: 15px; }
        }
    </style>
</head>
<body>
<div class="actions">
    <a class="back" href="payroll.php?month=<?= urlencode($payroll['salary_month']) ?>">Back to Payroll</a>
    <button type="button" onclick="window.print()">Print Payslip</button>
</div>

<div class="payslip">
    <div class="company">
        <h2>MZR FOOD SERVICES</h2>
        <p>EMPLOYEE SALARY PAYSLIP</p>
    </div>

    <div class="details">
        <div><strong>Employee Name:</strong> <?= htmlspecialchars($payroll['full_name']) ?></div>
        <div><strong>Employee Code:</strong> <?= htmlspecialchars($payroll['employee_code']) ?></div>
        <div><strong>Position:</strong> <?= htmlspecialchars($payroll['position'] ?? '-') ?></div>
        <div><strong>Salary Month:</strong> <?= htmlspecialchars($payroll['salary_month']) ?></div>
        <div><strong>Payment Status:</strong> <?= htmlspecialchars(ucfirst($payroll['payment_status'])) ?></div>
        <div><strong>IC Number:</strong> <?= htmlspecialchars($payroll['ic_number'] ?? '-') ?></div>
    </div>

    <h3>Earnings</h3>
    <table>
        <tr><th>Description</th><th class="amount">Amount (RM)</th></tr>
        <tr><td>Basic Salary</td><td class="amount"><?= moneyValue($payroll['basic_salary']) ?></td></tr>
        <?php foreach ($allowances as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['component_name']) ?></td>
            <td class="amount"><?= moneyValue($item['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr><td>Overtime Pay</td><td class="amount"><?= moneyValue($payroll['overtime_pay']) ?></td></tr>
        <tr class="total"><td>Gross Salary</td><td class="amount"><?= moneyValue($payroll['gross_salary']) ?></td></tr>
    </table>

    <h3>Employee Deductions</h3>
    <table>
        <tr><th>Description</th><th class="amount">Amount (RM)</th></tr>
        <?php if (count($deductions) > 0): ?>
            <?php foreach ($deductions as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['component_name']) ?></td>
                <td class="amount"><?= moneyValue($item['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td>No other deductions</td><td class="amount">0.00</td></tr>
        <?php endif; ?>
        <tr class="total"><td>Total Other Deductions</td><td class="amount"><?= moneyValue(array_sum(array_column($deductions, 'amount'))) ?></td></tr>
    </table>

    <h3>Statutory Contributions (Employee)</h3>
    <table>
        <tr><th>Contribution</th><th class="amount">Amount (RM)</th></tr>
        <?php foreach ($statutoryItems as $label => $amount): ?>
        <tr><td><?= htmlspecialchars($label) ?></td><td class="amount"><?= moneyValue($amount) ?></td></tr>
        <?php endforeach; ?>
        <tr class="total"><td>Total Employee Statutory Contributions</td><td class="amount"><?= moneyValue($totalStatutoryEmployee) ?></td></tr>
    </table>

    <h3>Employer Contributions (Not Deducted From Employee Salary)</h3>
    <table>
        <tr><th>Contribution</th><th class="amount">Amount (RM)</th></tr>
        <?php foreach ($employerItems as $label => $amount): ?>
        <tr><td><?= htmlspecialchars($label) ?></td><td class="amount"><?= moneyValue($amount) ?></td></tr>
        <?php endforeach; ?>
        <tr class="total"><td>Total Employer Contributions</td><td class="amount"><?= moneyValue($totalEmployerContribution) ?></td></tr>
    </table>

    <table>
        <tr class="total">
            <td>Total Deduction Recorded in Payroll</td>
            <td class="amount">RM <?= moneyValue($payroll['total_deduction']) ?></td>
        </tr>
        <tr class="net">
            <td>NET SALARY</td>
            <td class="amount">RM <?= moneyValue($payroll['net_salary']) ?></td>
        </tr>
    </table>

    <p class="note">
        This is a computer-generated payslip. Statutory contribution amounts shown above are read from the payroll record.
        They will remain RM0.00 until the contribution calculation is configured and the payroll is generated with those values.
        Employer contributions are displayed for information and are not employee salary deductions.
    </p>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";
$selectedId = (int)($_POST['employee_id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_statutory'])) {
    verify_csrf();
    $employeeId = (int)($_POST['employee_id'] ?? 0);

    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $citizenship = $_POST['citizenship'] ?? 'malaysian';
    $epfMemberSince = trim($_POST['epf_member_since'] ?? '');
    $taxResident = $_POST['tax_resident'] ?? 'resident';
    $maritalStatus = $_POST['marital_status'] ?? 'single';
    $spouseWorking = isset($_POST['spouse_working']) ? 1 : 0;
    $childrenCount = max(0, (int)($_POST['children_count'] ?? 0));
    $disabledEmployee = isset($_POST['disabled_employee']) ? 1 : 0;
    $taxNumber = trim($_POST['tax_number'] ?? '');
    $zakatMonthly = max(0, (float)($_POST['zakat_monthly'] ?? 0));
    $previousIncome = max(0, (float)($_POST['previous_employment_income'] ?? 0));
    $previousEpf = max(0, (float)($_POST['previous_epf_contribution'] ?? 0));
    $previousPcb = max(0, (float)($_POST['previous_pcb_paid'] ?? 0));
    $socsoBefore55 = isset($_POST['socso_contributed_before_55']) ? 1 : 0;
    $eisBefore57 = isset($_POST['eis_contributed_before_57']) ? 1 : 0;

    $validCitizenship = ['malaysian', 'permanent_resident', 'non_malaysian'];
    $validTaxResident = ['resident', 'non_resident'];
    $validMarital = ['single', 'married'];

    if ($employeeId <= 0) {
        $error = "Sila pilih pekerja.";
    } elseif (!in_array($citizenship, $validCitizenship, true) ||
              !in_array($taxResident, $validTaxResident, true) ||
              !in_array($maritalStatus, $validMarital, true)) {
        $error = "Pilihan maklumat pekerja tidak sah.";
    } else {
        try {
            $stmt = $conn->prepare(
                "UPDATE employees SET
                    date_of_birth = ?, citizenship = ?, epf_member_since = ?,
                    tax_resident = ?, marital_status = ?, spouse_working = ?,
                    children_count = ?, disabled_employee = ?, tax_number = ?,
                    zakat_monthly = ?, previous_employment_income = ?,
                    previous_epf_contribution = ?, previous_pcb_paid = ?,
                    socso_contributed_before_55 = ?, eis_contributed_before_57 = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $dateOfBirth !== '' ? $dateOfBirth : null,
                $citizenship,
                $epfMemberSince !== '' ? $epfMemberSince : null,
                $taxResident,
                $maritalStatus,
                $spouseWorking,
                $childrenCount,
                $disabledEmployee,
                $taxNumber !== '' ? $taxNumber : null,
                $zakatMonthly,
                $previousIncome,
                $previousEpf,
                $previousPcb,
                $socsoBefore55,
                $eisBefore57,
                $employeeId
            ]);

            $message = "Maklumat statutori berjaya dikemaskini.";
            $selectedId = $employeeId;
        } catch (PDOException $e) {
            $error = "Tidak berjaya menyimpan maklumat. Semak struktur jadual employees. Butiran: " . $e->getMessage();
        }
    }
}

$employees = $conn->query(
    "SELECT id, employee_code, full_name
     FROM employees
     ORDER BY full_name"
)->fetchAll(PDO::FETCH_ASSOC);

$employee = null;
if ($selectedId > 0) {
    $stmt = $conn->prepare(
        "SELECT id, employee_code, full_name, date_of_birth, citizenship,
                epf_member_since, tax_resident, marital_status, spouse_working,
                children_count, disabled_employee, tax_number, zakat_monthly,
                previous_employment_income, previous_epf_contribution,
                previous_pcb_paid, socso_contributed_before_55,
                eis_contributed_before_57
         FROM employees WHERE id = ?"
    );
    $stmt->execute([$selectedId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}
function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Statutory Information - MZR FOOD SERVICES</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f9;color:#243b53;padding:24px}
.container{max-width:950px;margin:auto}
.card{background:#fff;padding:24px;border-radius:8px;margin:18px 0}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px}
label{display:block;font-weight:bold;margin:10px 0 6px}
input,select{width:100%;padding:10px;border:1px solid #ccd3da;border-radius:4px;box-sizing:border-box}
.checkbox{display:flex;align-items:center;gap:9px;margin:13px 0}
.checkbox input{width:auto}
button{background:#1769aa;color:#fff;border:0;border-radius:4px;padding:11px 18px;cursor:pointer}
.success{background:#d4edda;padding:12px;margin:12px 0}
.error{background:#f8d7da;padding:12px;margin:12px 0}
.note{background:#fff3cd;padding:13px;line-height:1.5;margin:15px 0}
@media(max-width:650px){body{padding:10px}.grid{grid-template-columns:1fr}.card{padding:16px}}
</style>
</head>
<body>
<div class="container">
<h2>MZR FOOD SERVICES - Employee Statutory Information</h2>
<p><a href="employees.php">Back to Employees</a> | <a href="dashboard.php">Dashboard</a></p>

<?php if ($message): ?><div class="success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
<form method="GET">
<label>Select Employee</label>
<select name="id" required>
<option value="">-- Select Employee --</option>
<?php foreach ($employees as $item): ?>
<option value="<?= (int)$item['id'] ?>" <?= $selectedId === (int)$item['id'] ? 'selected' : '' ?>>
<?= h($item['employee_code'] . ' - ' . $item['full_name']) ?>
</option>
<?php endforeach; ?>
</select>
<br><br><button type="submit">Load Employee</button>
</form>
</div>

<?php if ($employee): ?>
<div class="card">
<h3><?= h($employee['employee_code'] . ' - ' . $employee['full_name']) ?></h3>
<div class="note">
Isi maklumat berdasarkan rekod sebenar pekerja. Untuk dua soalan sejarah caruman di bawah, pilih “Ya” hanya jika pekerja memang pernah mencarum sebelum umur yang dinyatakan. Jangan membuat andaian.
</div>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
<div class="grid">
<div>
<label>Date of Birth</label>
<input type="date" name="date_of_birth" value="<?= h($employee['date_of_birth']) ?>">
</div>
<div>
<label>Citizenship</label>
<select name="citizenship">
<option value="malaysian" <?= $employee['citizenship']==='malaysian'?'selected':'' ?>>Malaysian</option>
<option value="permanent_resident" <?= $employee['citizenship']==='permanent_resident'?'selected':'' ?>>Permanent Resident</option>
<option value="non_malaysian" <?= $employee['citizenship']==='non_malaysian'?'selected':'' ?>>Non-Malaysian</option>
</select>
</div>
<div>
<label>EPF Member Since</label>
<input type="date" name="epf_member_since" value="<?= h($employee['epf_member_since']) ?>">
</div>
<div>
<label>Tax Residency</label>
<select name="tax_resident">
<option value="resident" <?= $employee['tax_resident']==='resident'?'selected':'' ?>>Tax Resident</option>
<option value="non_resident" <?= $employee['tax_resident']==='non_resident'?'selected':'' ?>>Non-Resident</option>
</select>
</div>
<div>
<label>Marital Status</label>
<select name="marital_status">
<option value="single" <?= $employee['marital_status']==='single'?'selected':'' ?>>Single</option>
<option value="married" <?= $employee['marital_status']==='married'?'selected':'' ?>>Married</option>
</select>
</div>
<div>
<label>Number of Children</label>
<input type="number" min="0" name="children_count" value="<?= (int)$employee['children_count'] ?>">
</div>
<div>
<label>Tax Number (if available)</label>
<input type="text" name="tax_number" value="<?= h($employee['tax_number']) ?>">
</div>
<div>
<label>Monthly Zakat (RM)</label>
<input type="number" min="0" step="0.01" name="zakat_monthly" value="<?= h($employee['zakat_monthly']) ?>">
</div>
<div>
<label>Previous Employment Income (RM)</label>
<input type="number" min="0" step="0.01" name="previous_employment_income" value="<?= h($employee['previous_employment_income']) ?>">
</div>
<div>
<label>Previous EPF Contribution (RM)</label>
<input type="number" min="0" step="0.01" name="previous_epf_contribution" value="<?= h($employee['previous_epf_contribution']) ?>">
</div>
<div>
<label>Previous PCB Paid (RM)</label>
<input type="number" min="0" step="0.01" name="previous_pcb_paid" value="<?= h($employee['previous_pcb_paid']) ?>">
</div>
</div>

<label class="checkbox">
<input type="checkbox" name="spouse_working" value="1" <?= !empty($employee['spouse_working'])?'checked':'' ?>>
Spouse is working
</label>
<label class="checkbox">
<input type="checkbox" name="disabled_employee" value="1" <?= !empty($employee['disabled_employee'])?'checked':'' ?>>
Employee is registered as a person with disability (OKU)
</label>
<hr>
<h3>Contribution History</h3>
<label class="checkbox">
<input type="checkbox" name="socso_contributed_before_55" value="1" <?= !empty($employee['socso_contributed_before_55'])?'checked':'' ?>>
Employee had PERKESO contributions before age 55
</label>
<label class="checkbox">
<input type="checkbox" name="eis_contributed_before_57" value="1" <?= !empty($employee['eis_contributed_before_57'])?'checked':'' ?>>
Employee had SIP/EIS contributions before age 57
</label>
<br>
<button type="submit" name="save_statutory">Save Statutory Information</button>
</form>
</div>
<?php elseif ($selectedId > 0): ?>
<div class="error">Employee record not found.</div>
<?php endif; ?>
</div>
</body>
</html>

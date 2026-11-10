<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $month = $_POST['salary_month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        set_flash('Bulan payroll tidak sah.', 'danger');
        header('Location: payroll.php'); exit;
    }

    try {
        $pdo->beginTransaction();
        $employees = $pdo->query("SELECT * FROM employees WHERE employment_status='active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $findAttendance = $pdo->prepare("SELECT COALESCE(SUM(overtime_hours),0) FROM attendance WHERE employee_id=? AND DATE_FORMAT(attendance_date,'%Y-%m')=?");
        $findComponents = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN component_type='allowance' THEN amount ELSE 0 END),0) AS allowance, COALESCE(SUM(CASE WHEN component_type='deduction' THEN amount ELSE 0 END),0) AS deduction FROM salary_components WHERE employee_id=? AND component_month=?");
        $findPayroll = $pdo->prepare("SELECT id FROM payroll WHERE employee_id=? AND salary_month=? LIMIT 1");
        $update = $pdo->prepare("UPDATE payroll SET basic_salary=?, total_allowance=?, total_deduction=?, overtime_pay=?, gross_salary=?, net_salary=?, overtime_hours=?, epf_employee=?, epf_employer=?, socso_employee=?, socso_employer=?, eis_employee=?, eis_employer=?, pcb=0, employer_total_contribution=?, approval_status='pending', approved_by=NULL, approved_at=NULL WHERE id=?");
        $insert = $pdo->prepare("INSERT INTO payroll (employee_id, salary_month, basic_salary, total_allowance, total_deduction, overtime_pay, gross_salary, net_salary, overtime_hours, epf_employee, epf_employer, socso_employee, socso_employer, eis_employee, eis_employer, pcb, employer_total_contribution) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        foreach ($employees as $emp) {
            $findAttendance->execute([$emp['id'], $month]);
            $otHours = (float)$findAttendance->fetchColumn();
            $findComponents->execute([$emp['id'], $month]);
            $component = $findComponents->fetch(PDO::FETCH_ASSOC);
            $basic = (float)$emp['basic_salary'];
            $allowance = (float)$component['allowance'];
            $manualDeduction = (float)$component['deduction'];

            // Kadar OT tetap 1.5; semua jam OT diambil daripada rekod kehadiran bulan dipilih.
            $otPay = ($basic / 26 / 8) * 1.5 * $otHours;
            $gross = $basic + $allowance + $otPay;
            $kwspEmployee = round($gross * 0.11, 2);
            $socsoEmployee = round($gross * 0.0015, 2); // Anggaran projek berdasarkan arahan pengguna.
            $eisEmployee = round($gross * 0.002, 2);
            $kwspEmployerRate = ($emp['citizenship'] ?? 'malaysian') === 'non_malaysian' ? 0.02 : (($basic + $allowance) <= 5000 ? 0.13 : 0.12);
            if (($emp['citizenship'] ?? 'malaysian') === 'malaysian' && !empty($emp['date_of_birth'])) {
                $age = (int)date_diff(date_create($emp['date_of_birth']), date_create($month . '-01'))->y;
                if ($age >= 60) $kwspEmployerRate = 0.04;
            }
            $kwspEmployer = round($gross * $kwspEmployerRate, 2);
            $socsoEmployer = round($gross * 0.015, 2); // Anggaran projek; bukan jadual rasmi PERKESO.
            $eisEmployer = round($gross * 0.002, 2);
            $totalDeduction = $manualDeduction + $kwspEmployee + $socsoEmployee + $eisEmployee;
            $net = $gross - $totalDeduction;
            $employerTotal = $kwspEmployer + $socsoEmployer + $eisEmployer;

            $findPayroll->execute([$emp['id'], $month]);
            $existingId = $findPayroll->fetchColumn();
            if ($existingId) {
                $update->execute([$basic,$allowance,$totalDeduction,$otPay,$gross,$net,$otHours,$kwspEmployee,$kwspEmployer,$socsoEmployee,$socsoEmployer,$eisEmployee,$eisEmployer,$employerTotal,$existingId]);
            } else {
                $insert->execute([$emp['id'],$month,$basic,$allowance,$totalDeduction,$otPay,$gross,$net,$otHours,$kwspEmployee,$kwspEmployer,$socsoEmployee,$socsoEmployer,$eisEmployee,$eisEmployer,0,$employerTotal]);
            }
        }
        $pdo->commit();
        set_flash('Payroll '.$month.' berjaya dijana/dikemas kini. Jam OT dikumpulkan secara automatik daripada kehadiran bulan tersebut.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('Payroll gagal: '.$e->getMessage(), 'danger');
    }
    header('Location: payroll.php?month='.urlencode($month)); exit;
}

$stmt = $pdo->prepare("SELECT p.*, e.employee_code, e.full_name FROM payroll p JOIN employees e ON e.id=p.employee_id WHERE p.salary_month=? ORDER BY e.full_name");
$stmt->execute([$month]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/includes/layout.php';
page_start('Pengiraan Gaji','payroll');
?>
<div class="page-intro"><div><h2>Pengiraan gaji</h2><p>Jana payroll bulanan. Semua jam OT dikira automatik daripada rekod Kehadiran & OT bagi bulan dipilih.</p></div></div>
<div class="alert alert-warning">Formula OT projek: gaji asas ÷ 26 ÷ 8 × 1.5 × jumlah jam OT. Gandaan OT tidak perlu dimasukkan setiap kali. Kadar PERKESO 0.15% ialah anggaran projek, bukan jadual rasmi. PCB belum dikira.</div>
<div class="row g-4"><div class="col-xl-4"><div class="panel-card"><h3 class="mb-3">Proses payroll</h3><form method="post" onsubmit="return confirm('Jana atau kemas kini payroll untuk semua pekerja aktif bagi bulan ini?')"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label class="form-label">Bulan gaji</label><input class="form-control mb-3" type="month" name="salary_month" value="<?=h($month)?>" required><button class="btn btn-primary w-100">Jana / Kemas kini payroll</button></form></div></div>
<div class="col-xl-8"><div class="panel-card"><h3 class="mb-3">Sejarah payroll · <?=h($month)?></h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pekerja</th><th>OT</th><th>Kasar</th><th>Potongan</th><th>Bersih</th><th>Slip</th></tr></thead><tbody><?php foreach($records as $r):?><tr><td><b><?=h($r['full_name'])?></b><small class="d-block muted"><?=h($r['employee_code'])?></small></td><td><?=number_format((float)$r['overtime_hours'],2)?> jam<br><small><?=money($r['overtime_pay'])?></small></td><td><?=money($r['gross_salary'])?></td><td><?=money($r['total_deduction'])?></td><td><b><?=money($r['net_salary'])?></b></td><td><a class="btn btn-sm btn-outline-primary" href="payslip.php?id=<?=(int)$r['id']?>">Payslip</a></td></tr><?php endforeach;?><?php if(!$records):?><tr><td colspan="6" class="text-center muted">Belum ada payroll untuk bulan ini. Pilih bulan dan klik Jana / Kemas kini.</td></tr><?php endif;?></tbody></table></div></div></div></div>
<?php page_end(); ?>

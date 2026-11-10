<?php
require_once __DIR__.'/includes/auth.php';
require_roles(['manager']);

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', $month)) $month = date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['payroll_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    if ($id > 0 && in_array($decision, ['approved','rejected'], true)) {
        $check = $pdo->prepare("SELECT approval_status FROM payroll WHERE id=?");
        $check->execute([$id]);
        $currentStatus = $check->fetchColumn();
        if (!$currentStatus) {
            set_flash('Rekod payroll tidak dijumpai.','danger');
        } elseif ($currentStatus === 'approved' && $decision !== 'approved') {
            set_flash('Slip yang telah disahkan tidak boleh ditukar melalui halaman ini.','danger');
        } else {
            $approve = $pdo->prepare("UPDATE payroll SET approval_status=?, approved_by=?, approved_at=NOW() WHERE id=?");
            $approve->execute([$decision, (int)$_SESSION['user_id'], $id]);
            set_flash($decision === 'approved' ? 'Slip gaji berjaya disahkan.' : 'Slip gaji ditolak.');
        }
        $returnMonth = $_POST['month'] ?? date('Y-m');
        header('Location: manager_portal.php?month='.urlencode($returnMonth)); exit;
    }
    $bonus = filter_var($_POST['bonus_amount'] ?? null, FILTER_VALIDATE_FLOAT);
    $returnMonth = $_POST['month'] ?? date('Y-m');
    if (!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', $returnMonth)) $returnMonth = date('Y-m');

    if ($id <= 0 || $bonus === false || $bonus < 0 || $bonus > 1000000) {
        set_flash('Jumlah bonus tidak sah. Masukkan nilai RM 0 atau lebih.','danger');
        header('Location: manager_portal.php?month='.urlencode($returnMonth)); exit;
    }

    try {
        $pdo->beginTransaction();
        $q = $pdo->prepare("SELECT p.id,p.employee_id,p.salary_month,p.approval_status,e.basic_salary,e.citizenship,e.date_of_birth
                            FROM payroll p JOIN employees e ON e.id=p.employee_id WHERE p.id=? FOR UPDATE");
        $q->execute([$id]);
        $record = $q->fetch(PDO::FETCH_ASSOC);
        if (!$record) throw new RuntimeException('Rekod payroll tidak dijumpai.');
        if ($record['approval_status'] === 'approved') throw new RuntimeException('Slip yang telah disahkan tidak boleh diedit. Hubungi Admin untuk pembetulan.');

        $employeeId = (int)$record['employee_id'];
        $salaryMonth = $record['salary_month'];

        // Bonus disimpan sebagai komponen elaun supaya dipaparkan pada slip gaji.
        $findBonus = $pdo->prepare("SELECT id FROM salary_components WHERE employee_id=? AND component_type='allowance' AND component_name='Bonus' AND component_month=? ORDER BY id LIMIT 1");
        $findBonus->execute([$employeeId, $salaryMonth]);
        $bonusId = $findBonus->fetchColumn();
        if ($bonus > 0) {
            if ($bonusId) {
                $u = $pdo->prepare("UPDATE salary_components SET amount=? WHERE id=?");
                $u->execute([$bonus, $bonusId]);
            } else {
                $i = $pdo->prepare("INSERT INTO salary_components (employee_id,component_type,component_name,amount,component_month) VALUES (?,'allowance','Bonus',?,?)");
                $i->execute([$employeeId, $bonus, $salaryMonth]);
            }
        } elseif ($bonusId) {
            $d = $pdo->prepare("DELETE FROM salary_components WHERE employee_id=? AND component_type='allowance' AND component_name='Bonus' AND component_month=?");
            $d->execute([$employeeId, $salaryMonth]);
        }

        // Kira semula jumlah elaun, potongan dan caruman menggunakan formula projek yang sama seperti payroll.php.
        $c = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN component_type='allowance' THEN amount ELSE 0 END),0) allowance,
                                   COALESCE(SUM(CASE WHEN component_type='deduction' THEN amount ELSE 0 END),0) deduction
                            FROM salary_components WHERE employee_id=? AND component_month=?");
        $c->execute([$employeeId, $salaryMonth]);
        $components = $c->fetch(PDO::FETCH_ASSOC);

        $basic = (float)$record['basic_salary'];
        $allowance = (float)$components['allowance'];
        $manualDeduction = (float)$components['deduction'];
        $old = $pdo->prepare("SELECT overtime_hours FROM payroll WHERE id=?");
        $old->execute([$id]);
        $otHours = (float)$old->fetchColumn();
        $otPay = ($basic / 26 / 8) * 1.5 * $otHours;
        $gross = $basic + $allowance + $otPay;
        $kwspEmployee = round($gross * 0.11, 2);
        $socsoEmployee = round($gross * 0.0015, 2);
        $eisEmployee = round($gross * 0.002, 2);
        $citizenship = $record['citizenship'] ?? 'malaysian';
        $kwspEmployerRate = ($citizenship === 'non_malaysian') ? 0.02 : (($basic + $allowance) <= 5000 ? 0.13 : 0.12);
        if ($citizenship === 'malaysian' && !empty($record['date_of_birth'])) {
            $age = (int)date_diff(date_create($record['date_of_birth']), date_create($salaryMonth.'-01'))->y;
            if ($age >= 60) $kwspEmployerRate = 0.04;
        }
        $kwspEmployer = round($gross * $kwspEmployerRate, 2);
        $socsoEmployer = round($gross * 0.015, 2);
        $eisEmployer = round($gross * 0.002, 2);
        $totalDeduction = $manualDeduction + $kwspEmployee + $socsoEmployee + $eisEmployee;
        $net = $gross - $totalDeduction;
        $employerTotal = $kwspEmployer + $socsoEmployer + $eisEmployer;
        if ($net < 0) throw new RuntimeException('Gaji bersih menjadi negatif. Semak potongan.');

        $u = $pdo->prepare("UPDATE payroll SET total_allowance=?,total_deduction=?,overtime_pay=?,gross_salary=?,net_salary=?,epf_employee=?,epf_employer=?,socso_employee=?,socso_employer=?,eis_employee=?,eis_employer=?,employer_total_contribution=?,approval_status='pending',approved_by=NULL,approved_at=NULL WHERE id=?");
        $u->execute([$allowance,$totalDeduction,$otPay,$gross,$net,$kwspEmployee,$kwspEmployer,$socsoEmployee,$socsoEmployer,$eisEmployee,$eisEmployer,$employerTotal,$id]);
        $pdo->commit();
        set_flash('Bonus berjaya dikemas kini. Jumlah gaji dikira semula dan status slip ditetapkan semula kepada Pending.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('Bonus gagal dikemas kini: '.$e->getMessage(),'danger');
    }
    header('Location: manager_portal.php?month='.urlencode($returnMonth)); exit;
}

$s=$pdo->prepare("SELECT p.id,p.employee_id,p.salary_month,p.gross_salary,p.net_salary,p.total_allowance,p.approval_status,
                         e.employee_code,e.full_name,
                         COALESCE((SELECT SUM(sc.amount) FROM salary_components sc WHERE sc.employee_id=p.employee_id AND sc.component_type='allowance' AND sc.component_name='Bonus' AND sc.component_month=p.salary_month),0) AS bonus_amount
                  FROM payroll p JOIN employees e ON e.id=p.employee_id
                  WHERE p.salary_month=?
                  ORDER BY FIELD(p.approval_status,'pending','rejected','approved'),e.full_name");
$s->execute([$month]); $rows=$s->fetchAll(PDO::FETCH_ASSOC);
$s=$pdo->prepare("SELECT approval_status,COUNT(*) total FROM payroll WHERE salary_month=? GROUP BY approval_status");$s->execute([$month]);$counts=['pending'=>0,'approved'=>0,'rejected'=>0];foreach($s->fetchAll(PDO::FETCH_ASSOC) as $c)$counts[$c['approval_status']] = (int)$c['total'];
$attendanceTodayStmt = $pdo->prepare("SELECT COUNT(*) total, SUM(CASE WHEN check_in IS NOT NULL THEN 1 ELSE 0 END) checked_in, SUM(CASE WHEN check_out IS NOT NULL THEN 1 ELSE 0 END) checked_out FROM attendance WHERE attendance_date=CURDATE()");
$attendanceTodayStmt->execute();
$attendanceToday = $attendanceTodayStmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'checked_in'=>0,'checked_out'=>0];
require __DIR__.'/includes/layout.php'; page_start('Pantau & Sahkan Slip','manager');
?>
<div class="page-intro"><div><h2>Pantauan dan pengesahan slip gaji</h2><p>Semak payroll, tambah atau kemas kini bonus, kemudian sahkan slip untuk dipaparkan kepada pekerja.</p></div></div>
<div class="panel-card mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h3 class="mb-1">Kehadiran pekerja hari ini</h3><p class="muted mb-0">Pantau waktu check in dan check out yang direkodkan oleh pekerja.</p></div><div class="d-flex gap-3"><div><small class="muted d-block">Check in</small><strong><?= (int)$attendanceToday['checked_in'] ?></strong></div><div><small class="muted d-block">Check out</small><strong><?= (int)$attendanceToday['checked_out'] ?></strong></div></div><a class="btn btn-outline-primary" href="attendance.php"><i class="bi bi-calendar-check me-1"></i> Lihat kehadiran</a></div></div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="metric-card"><div class="metric-label">Menunggu pengesahan</div><div class="metric-value"><?= $counts['pending'] ?></div></div></div>
<div class="col-md-4"><div class="metric-card"><div class="metric-label">Disahkan</div><div class="metric-value"><?= $counts['approved'] ?></div></div></div>
<div class="col-md-4"><div class="metric-card"><div class="metric-label">Ditolak</div><div class="metric-value"><?= $counts['rejected'] ?></div></div></div>
</div>
<div class="panel-card mb-4"><form method="get" class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Bulan payroll</label><input class="form-control" type="month" name="month" value="<?=h($month)?>"></div><div class="col-md-2"><button class="btn btn-primary w-100">Papar</button></div></form></div>
<div class="panel-card"><h3 class="mb-3">Rekod payroll · <?=h($month)?></h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pekerja</th><th>Bonus (RM)</th><th>Gaji kasar</th><th>Gaji bersih</th><th>Status</th><th>Semakan</th><th>Tindakan</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr>
<td><b><?=h($r['full_name'])?></b><small class="d-block muted"><?=h($r['employee_code'])?></small></td>
<td><?php if($r['approval_status']!=='approved'):?><form method="post" class="d-flex gap-2 align-items-center" style="min-width:190px"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="payroll_id" value="<?=(int)$r['id']?>"><input type="hidden" name="month" value="<?=h($month)?>"><input class="form-control form-control-sm" type="number" name="bonus_amount" min="0" max="1000000" step="0.01" value="<?=number_format((float)$r['bonus_amount'],2,'.','')?>" aria-label="Bonus RM"><button class="btn btn-sm btn-outline-primary" title="Simpan bonus"><i class="bi bi-pencil-square"></i> Edit</button></form><?php else:?><?=number_format((float)$r['bonus_amount'],2)?> <small class="muted">(dikunci)</small><?php endif;?></td>
<td><?=money($r['gross_salary'])?></td><td><b><?=money($r['net_salary'])?></b></td>
<td><?php $st=$r['approval_status'];?><span class="badge <?=$st==='approved'?'bg-success':($st==='rejected'?'bg-danger':'bg-warning text-dark')?>"><?=h(ucfirst($st))?></span></td>
<td><a class="btn btn-sm btn-outline-secondary" href="payslip.php?id=<?=(int)$r['id']?>">Semak slip</a></td>
<td><?php if($st!=='approved'):?><form method="post" class="d-inline" onsubmit="return confirm('Sahkan slip gaji ini?')"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="payroll_id" value="<?=(int)$r['id']?>"><button class="btn btn-sm btn-success" name="decision" value="approved">Sahkan</button></form><?php endif;?>
<?php if($st!=='rejected'):?><form method="post" class="d-inline" onsubmit="return confirm('Tolak slip gaji ini?')"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="payroll_id" value="<?=(int)$r['id']?>"><button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Tolak</button></form><?php endif;?></td>
</tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="7" class="text-center muted">Tiada rekod payroll untuk bulan ini.</td></tr><?php endif;?>
</tbody></table></div><small class="muted">Simpan bonus dahulu sebelum mengesahkan slip. Setiap perubahan bonus akan mengira semula jumlah gaji dan menetapkan status kembali kepada Pending.</small></div>
<?php page_end(); ?>

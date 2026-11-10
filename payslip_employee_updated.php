<?php
require_once __DIR__.'/includes/auth.php';require_login();
$id=(int)($_GET['id']??0);
$role=$_SESSION['role']??'';
if(!in_array($role,['admin','manager','employee'],true)){http_response_code(403);exit('Akses slip gaji ditolak.');}
if($role==='employee'){
 $s=$pdo->prepare("SELECT employee_id,approval_status FROM payroll WHERE id=?");
 $s->execute([$id]); $access=$s->fetch(PDO::FETCH_ASSOC);
 if(!$access || (int)$access['employee_id']!==(int)($_SESSION['employee_id']??0) || $access['approval_status']!=='approved'){
  http_response_code(403); exit('Slip gaji ini belum disahkan atau bukan milik anda.');
 }
}
$s=$pdo->prepare("SELECT p.*,e.employee_code,e.full_name,e.position FROM payroll p JOIN employees e ON e.id=p.employee_id WHERE p.id=?");$s->execute([$id]);$p=$s->fetch();if(!$p){http_response_code(404);exit('Slip gaji tidak dijumpai.');}
require __DIR__.'/includes/layout.php';page_start('Slip Gaji');?>
<div class="d-flex justify-content-between mb-3 no-print"><h2>Slip gaji</h2><button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button></div>
<div class="panel-card payslip"><div class="text-center border-bottom pb-3 mb-4"><h2 class="fw-bold">MZR FOOD SERVICES</h2><div class="muted">PENYATA GAJI PEKERJA</div><h5 class="mt-3"><?=h($p['salary_month'])?></h5></div>
<div class="row mb-4"><div class="col-6"><small class="muted">Nama pekerja</small><div class="fw-bold"><?=h($p['full_name'])?></div><small><?=h($p['employee_code'])?></small></div><div class="col-6"><small class="muted">Jawatan</small><div class="fw-bold"><?=h($p['position'])?></div></div></div>
<?php
// Paparkan setiap komponen pendapatan secara berasingan (contoh: Bonus, Elaun Makan).
$componentStmt = $pdo->prepare(
    "SELECT component_name, amount
     FROM salary_components
     WHERE employee_id = ?
       AND component_type = 'allowance'
       AND LEFT(component_month, 7) = ?
     ORDER BY CASE WHEN LOWER(component_name) = 'bonus' THEN 0 ELSE 1 END, component_name"
);
$componentStmt->execute([(int)$p['employee_id'], substr((string)$p['salary_month'], 0, 7)]);
$earningComponents = $componentStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h5>Pendapatan</h5>
<table class="table">
<tr><td>Gaji asas</td><td class="text-end">RM <?=number_format((float)$p['basic_salary'],2)?></td></tr>
<?php if ($earningComponents): ?>
    <?php foreach ($earningComponents as $component): ?>
        <tr>
            <td><?=h($component['component_name'])?></td>
            <td class="text-end">RM <?=number_format((float)$component['amount'],2)?></td>
        </tr>
    <?php endforeach; ?>
<?php elseif ((float)$p['total_allowance'] > 0): ?>
    <tr><td>Elaun (butiran tidak tersedia)</td><td class="text-end">RM <?=number_format((float)$p['total_allowance'],2)?></td></tr>
<?php endif; ?>
<tr><td>Kerja lebih masa (<?=number_format((float)$p['overtime_hours'],2)?> jam)</td><td class="text-end">RM <?=number_format((float)$p['overtime_pay'],2)?></td></tr>
<tr class="table-light fw-bold"><td>Gaji kasar</td><td class="text-end">RM <?=number_format((float)$p['gross_salary'],2)?></td></tr>
</table>
<h5 class="mt-4">Potongan pekerja</h5><table class="table"><?php foreach(['KWSP'=>'epf_employee','PERKESO'=>'socso_employee','SIP/EIS'=>'eis_employee','PCB'=>'pcb'] as $label=>$key):?><tr><td><?=h($label)?></td><td class="text-end">RM <?=number_format((float)($p[$key]??0),2)?></td></tr><?php endforeach;?><tr><td>Potongan lain</td><td class="text-end">RM <?=number_format(max(0,(float)$p['total_deduction']-(float)$p['epf_employee']-(float)$p['socso_employee']-(float)$p['eis_employee']-(float)$p['pcb']),2)?></td></tr></table>
<div class="net-box"><span>GAJI BERSIH</span><strong>RM <?=number_format((float)$p['net_salary'],2)?></strong></div>
<p class="muted small text-center mt-4">Dijana oleh MZR FOOD SERVICES Payroll Management System.</p></div>
<?php page_end();?>
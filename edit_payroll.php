<?php
require_once __DIR__.'/includes/auth.php';require_admin();
$id=(int)($_GET['id']??$_POST['id']??0);if(!$id){set_flash('Rekod payroll tidak dijumpai.','danger');header('Location: payroll.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$fields=['basic_salary','total_allowance','total_deduction','overtime_hours','overtime_pay','epf_employee','epf_employer','socso_employee','socso_employer','eis_employee','eis_employer','pcb'];$v=[];
 foreach($fields as $f){$v[$f]=(float)($_POST[$f]??0);if($v[$f]<0){set_flash('Nilai tidak boleh negatif.','danger');header('Location: edit_payroll.php?id='.$id);exit;}}
 $s=$pdo->prepare('SELECT id FROM payroll WHERE id=?');$s->execute([$id]);if(!$s->fetchColumn()){set_flash('Rekod payroll tidak dijumpai.','danger');header('Location: payroll.php');exit;}
 $gross=$v['basic_salary']+$v['total_allowance']+$v['overtime_pay'];$net=$gross-$v['total_deduction'];$employer=$v['epf_employer']+$v['socso_employer']+$v['eis_employer'];
 $q=$pdo->prepare('UPDATE payroll SET basic_salary=?,total_allowance=?,total_deduction=?,overtime_hours=?,overtime_pay=?,gross_salary=?,net_salary=?,epf_employee=?,epf_employer=?,socso_employee=?,socso_employer=?,eis_employee=?,eis_employer=?,pcb=?,employer_total_contribution=? WHERE id=?');
 $q->execute([$v['basic_salary'],$v['total_allowance'],$v['total_deduction'],$v['overtime_hours'],$v['overtime_pay'],$gross,$net,$v['epf_employee'],$v['epf_employer'],$v['socso_employee'],$v['socso_employer'],$v['eis_employee'],$v['eis_employer'],$v['pcb'],$employer,$id]);set_flash('Rekod payroll berjaya dikemas kini.');header('Location: payroll.php');exit;
}
$s=$pdo->prepare('SELECT p.*,e.employee_code,e.full_name FROM payroll p JOIN employees e ON e.id=p.employee_id WHERE p.id=?');$s->execute([$id]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r){set_flash('Rekod payroll tidak dijumpai.','danger');header('Location: payroll.php');exit;}
require __DIR__.'/includes/layout.php';page_start('Edit Payroll','payroll');
$labels=['basic_salary'=>'Gaji asas','total_allowance'=>'Jumlah elaun','total_deduction'=>'Jumlah semua potongan','overtime_hours'=>'Jam OT','overtime_pay'=>'Bayaran OT','epf_employee'=>'KWSP pekerja','epf_employer'=>'KWSP majikan','socso_employee'=>'PERKESO pekerja','socso_employer'=>'PERKESO majikan','eis_employee'=>'SIP pekerja','eis_employer'=>'SIP majikan','pcb'=>'PCB'];
?>
<div class="page-intro"><div><h2>Edit rekod payroll</h2><p><?=h($r['employee_code'].' - '.$r['full_name'])?> · Bulan <?=h($r['salary_month'])?></p></div><a class="btn btn-light" href="payroll.php">Kembali</a></div>
<div class="panel-card"><div class="alert alert-info">Kemaskini amaun di bawah. Gaji kasar, gaji bersih dan jumlah caruman majikan akan dikira semula secara automatik apabila disimpan. Jumlah potongan ialah jumlah keseluruhan termasuk potongan statutori.</div><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=$id?>"><div class="row g-3">
<?php foreach($labels as $field=>$label):?><div class="col-md-4"><label class="form-label"><?=h($label)?><?=in_array($field,['basic_salary','total_allowance','total_deduction','overtime_hours','overtime_pay'],true)?'':' (RM)'?></label><input class="form-control" type="number" min="0" step="0.01" name="<?=h($field)?>" value="<?=h($r[$field]??0)?>" required></div><?php endforeach;?>
</div><div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Simpan perubahan</button><a class="btn btn-light" href="payroll.php">Batal</a></div></form></div><?php page_end();?>

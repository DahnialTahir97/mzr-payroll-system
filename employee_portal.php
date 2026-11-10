<?php
require_once __DIR__.'/includes/auth.php';
require_roles(['employee']);

$eid = (int)($_SESSION['employee_id'] ?? 0);
$s = $pdo->prepare("SELECT employee_code,full_name FROM employees WHERE id=?");
$s->execute([$eid]);
$emp = $s->fetch(PDO::FETCH_ASSOC);
if (!$emp) { http_response_code(404); exit('Rekod pekerja tidak dijumpai. Hubungi Admin.'); }

$today = date('Y-m-d');
$now = date('H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['attendance_action'] ?? '';
    try {
        $pdo->beginTransaction();
        $find = $pdo->prepare("SELECT id,check_in,check_out FROM attendance WHERE employee_id=? AND attendance_date=? ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $find->execute([$eid, $today]);
        $attendance = $find->fetch(PDO::FETCH_ASSOC);

        if ($action === 'check_in') {
            if ($attendance && !empty($attendance['check_in'])) {
                throw new RuntimeException('Anda sudah check in untuk hari ini.');
            }
            if ($attendance) {
                $update = $pdo->prepare("UPDATE attendance SET check_in=?,status='present' WHERE id=?");
                $update->execute([$now, (int)$attendance['id']]);
            } else {
                $insert = $pdo->prepare("INSERT INTO attendance (employee_id,attendance_date,check_in,status) VALUES (?,?,?,'present')");
                $insert->execute([$eid, $today, $now]);
            }
            $message = 'Check in berjaya direkodkan pada '.date('h:i A', strtotime($now)).'.';
        } elseif ($action === 'check_out') {
            if (!$attendance || empty($attendance['check_in'])) {
                throw new RuntimeException('Anda perlu check in dahulu sebelum check out.');
            }
            if (!empty($attendance['check_out'])) {
                throw new RuntimeException('Anda sudah check out untuk hari ini.');
            }
            $update = $pdo->prepare("UPDATE attendance SET check_out=? WHERE id=?");
            $update->execute([$now, (int)$attendance['id']]);
            $message = 'Check out berjaya direkodkan pada '.date('h:i A', strtotime($now)).'.';
        } else {
            throw new RuntimeException('Tindakan kehadiran tidak sah.');
        }
        $pdo->commit();
        set_flash($message);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash($e->getMessage(), 'danger');
    }
    header('Location: employee_portal.php'); exit;
}

$todayStmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id=? AND attendance_date=? ORDER BY id DESC LIMIT 1");
$todayStmt->execute([$eid, $today]);
$todayAttendance = $todayStmt->fetch(PDO::FETCH_ASSOC);

$historyStmt = $pdo->prepare("SELECT attendance_date,check_in,check_out,status FROM attendance WHERE employee_id=? ORDER BY attendance_date DESC,id DESC LIMIT 7");
$historyStmt->execute([$eid]);
$attendanceHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$s = $pdo->prepare("SELECT id,salary_month,net_salary FROM payroll WHERE employee_id=? AND approval_status='approved' ORDER BY salary_month DESC LIMIT 24");
$s->execute([$eid]);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
require __DIR__.'/includes/layout.php'; page_start('Slip Gaji Saya','employee');?>
<div class="hero-panel"><div><span class="eyebrow">PORTAL PEKERJA</span><h1>Hai, <?=h($emp['full_name'])?></h1><p>Semak slip gaji anda yang telah disahkan oleh Manager.</p></div><div class="hero-icon"><i class="bi bi-person-badge"></i></div></div>
<div class="row g-4 mb-4">
<div class="col-lg-5">
<div class="panel-card h-100">
<div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1">Kehadiran hari ini</h3><p class="muted mb-0"><?=h(date('d M Y'))?></p></div><span class="badge bg-primary">Self-service</span></div>
<div class="row g-3 mb-3">
<div class="col-6"><div class="p-3 rounded-3 bg-light"><small class="text-secondary d-block">Waktu masuk</small><strong class="fs-5"><?=!empty($todayAttendance['check_in'])?h(date('h:i A',strtotime($todayAttendance['check_in']))):'--:--'?></strong></div></div>
<div class="col-6"><div class="p-3 rounded-3 bg-light"><small class="text-secondary d-block">Waktu keluar</small><strong class="fs-5"><?=!empty($todayAttendance['check_out'])?h(date('h:i A',strtotime($todayAttendance['check_out']))):'--:--'?></strong></div></div>
</div>
<div class="d-flex gap-2 flex-wrap">
<?php if(empty($todayAttendance['check_in'])): ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><button class="btn btn-success" name="attendance_action" value="check_in"><i class="bi bi-box-arrow-in-right me-1"></i> Check In</button></form>
<?php else: ?>
<button class="btn btn-success" disabled><i class="bi bi-check-circle me-1"></i> Sudah Check In</button>
<?php endif; ?>
<?php if(!empty($todayAttendance['check_in']) && empty($todayAttendance['check_out'])): ?>
<form method="post" onsubmit="return confirm('Anda pasti mahu check out sekarang?')"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><button class="btn btn-outline-primary" name="attendance_action" value="check_out"><i class="bi bi-box-arrow-right me-1"></i> Check Out</button></form>
<?php elseif(!empty($todayAttendance['check_out'])): ?>
<button class="btn btn-outline-secondary" disabled><i class="bi bi-check2-all me-1"></i> Selesai Hari Ini</button>
<?php else: ?>
<button class="btn btn-outline-secondary" disabled>Check out selepas check in</button>
<?php endif; ?>
</div>
<small class="muted d-block mt-3">Masa direkod secara automatik mengikut masa server sistem.</small>
</div>
</div>
<div class="col-lg-7">
<div class="panel-card h-100"><h3 class="mb-3">Sejarah kehadiran terkini</h3><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tarikh</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr></thead><tbody>
<?php foreach($attendanceHistory as $a): ?><tr><td><?=h(date('d/m/Y',strtotime($a['attendance_date'])))?></td><td><?=!empty($a['check_in'])?h(date('h:i A',strtotime($a['check_in']))):'-'?></td><td><?=!empty($a['check_out'])?h(date('h:i A',strtotime($a['check_out']))):'-'?></td><td><?=h(ucwords(str_replace('_',' ',$a['status']??'present')))?></td></tr><?php endforeach; ?>
<?php if(!$attendanceHistory): ?><tr><td colspan="4" class="text-center muted">Belum ada rekod kehadiran.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div>
</div>
<div class="panel-card"><h3 class="mb-3">Slip gaji yang telah disahkan</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Bulan</th><th>Gaji bersih</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=h($r['salary_month'])?></td><td><b>RM <?=number_format((float)$r['net_salary'],2)?></b></td><td><span class="badge bg-success">Disahkan</span></td><td><a class="btn btn-sm btn-outline-primary" href="payslip.php?id=<?=(int)$r['id']?>">Lihat / Cetak</a></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="4" class="text-center muted">Belum ada slip gaji yang disahkan.</td></tr><?php endif;?>
</tbody></table></div></div><?php page_end();?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'manager', 'employee'], true)) {
    http_response_code(403);
    exit('Akses slip gaji ditolak.');
}

if ($role === 'employee') {
    $accessStmt = $pdo->prepare("SELECT employee_id, approval_status FROM payroll WHERE id = ?");
    $accessStmt->execute([$id]);
    $access = $accessStmt->fetch(PDO::FETCH_ASSOC);
    if (!$access || (int)$access['employee_id'] !== (int)($_SESSION['employee_id'] ?? 0) || $access['approval_status'] !== 'approved') {
        http_response_code(403);
        exit('Slip gaji ini belum disahkan atau bukan milik anda.');
    }
}

$stmt = $pdo->prepare("SELECT p.*, e.employee_code, e.full_name, e.position FROM payroll p JOIN employees e ON e.id = p.employee_id WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    exit('Slip gaji tidak dijumpai.');
}

require __DIR__ . '/includes/layout.php';

$moneyValue = static fn($value): string => number_format((float)($value ?? 0), 2);
$statutory = [
    'KWSP' => (float)($p['epf_employee'] ?? 0),
    'PERKESO' => (float)($p['socso_employee'] ?? 0),
    'SIP / EIS' => (float)($p['eis_employee'] ?? 0),
    'PCB' => (float)($p['pcb'] ?? 0),
];
$otherDeductions = max(0, (float)($p['total_deduction'] ?? 0) - array_sum($statutory));

page_start('Slip Gaji');
?>
<style>
.payslip-page{max-width:980px;margin:0 auto 28px}
.payslip-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}
.payslip-toolbar h2{font-size:22px;font-weight:800;margin:0;color:#172644}
.payslip-sheet{background:#fff;border:1px solid #e7ecf5;border-radius:20px;overflow:hidden;box-shadow:0 14px 40px rgba(27,45,82,.09)}
.payslip-banner{padding:30px 34px;background:linear-gradient(120deg,#14213d,#315bd2);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px}
.payslip-brand{display:flex;align-items:center;gap:14px}
.payslip-logo{width:54px;height:54px;border-radius:15px;background:rgba(255,255,255,.16);display:grid;place-items:center;font-weight:900;font-size:20px;letter-spacing:1px}
.payslip-brand h2{font-size:20px;font-weight:800;margin:0 0 5px;color:#fff}
.payslip-brand p{margin:0;color:#dce6ff;font-size:11px;letter-spacing:2px;font-weight:700}
.payslip-period{text-align:right}
.payslip-period small{display:block;color:#dce6ff;font-size:10px;text-transform:uppercase;letter-spacing:1.5px}
.payslip-period strong{font-size:19px;color:#fff}
.payslip-body{padding:30px 34px}
.payslip-section-title{font-size:12px;font-weight:800;letter-spacing:1.1px;text-transform:uppercase;color:#7887a2;margin:25px 0 12px}
.payslip-employee{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;background:#f6f8fc;border:1px solid #edf1f7;border-radius:14px;padding:18px}
.payslip-field small{display:block;color:#8793a8;font-size:10px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px}
.payslip-field strong{font-size:13px;color:#233451;overflow-wrap:anywhere}
.payslip-table{width:100%;border-collapse:collapse}
.payslip-table th{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#8491a8;background:#f7f9fc;padding:11px 14px;text-align:left;border-bottom:1px solid #e9eef6}
.payslip-table td{font-size:13px;color:#34435e;padding:12px 14px;border-bottom:1px solid #eef1f6}
.payslip-table .amount{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.payslip-table .total-row td{font-weight:800;color:#1d2d4b;background:#fafbfe}
.payslip-net{margin-top:24px;padding:20px 22px;border-radius:15px;background:linear-gradient(110deg,#172b50,#24477f);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:15px}
.payslip-net span{font-size:11px;font-weight:800;letter-spacing:1.3px}
.payslip-net strong{font-size:26px;font-weight:800;font-variant-numeric:tabular-nums}
.payslip-note{font-size:11px;line-height:1.6;color:#8792a5;text-align:center;margin:24px 0 0}
.payslip-footer{display:flex;justify-content:space-between;gap:12px;border-top:1px solid #edf1f6;padding:16px 34px;color:#98a3b5;font-size:10px}
@media(max-width:650px){.payslip-banner{padding:22px;align-items:flex-start;flex-direction:column}.payslip-period{text-align:left}.payslip-body{padding:20px}.payslip-employee{grid-template-columns:repeat(2,minmax(0,1fr));padding:14px}.payslip-table th,.payslip-table td{padding:10px 8px;font-size:11px}.payslip-net{padding:17px}.payslip-net strong{font-size:21px}.payslip-footer{padding:14px 20px;flex-direction:column}}
@media print{
 @page{size:A4;margin:12mm}
 body{background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
 .sidebar,.topbar,.footer,.no-print{display:none!important}
 .main-content{margin:0!important;min-height:0!important}
 .content-wrap{padding:0!important}
 .payslip-page{max-width:none;margin:0}
 .payslip-sheet{border:0;box-shadow:none;border-radius:0}
 .payslip-banner{padding:20px 24px}
 .payslip-body{padding:20px 24px}
 .payslip-footer{padding:12px 24px}
}
</style>

<div class="payslip-page">
    <div class="payslip-toolbar no-print">
        <div>
            <h2>Slip Gaji</h2>
            <small class="muted">Penyata pendapatan dan potongan pekerja</small>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="payroll.php?month=<?= urlencode((string)$p['salary_month']) ?>">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button class="btn btn-primary" type="button" onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak / PDF
            </button>
        </div>
    </div>

    <article class="payslip-sheet">
        <header class="payslip-banner">
            <div class="payslip-brand">
                <div class="payslip-logo">MZR</div>
                <div>
                    <h2>MZR FOOD SERVICES</h2>
                    <p>PAYROLL MANAGEMENT SYSTEM</p>
                </div>
            </div>
            <div class="payslip-period">
                <small>Penyata gaji bagi</small>
                <strong><?= h($p['salary_month']) ?></strong>
            </div>
        </header>

        <div class="payslip-body">
            <div class="payslip-section-title" style="margin-top:0">Maklumat pekerja</div>
            <div class="payslip-employee">
                <div class="payslip-field"><small>Nama pekerja</small><strong><?= h($p['full_name']) ?></strong></div>
                <div class="payslip-field"><small>Kod pekerja</small><strong><?= h($p['employee_code']) ?></strong></div>
                <div class="payslip-field"><small>Jawatan</small><strong><?= h($p['position'] ?: '-') ?></strong></div>
            </div>

            <div class="payslip-section-title">Pendapatan</div>
            <table class="payslip-table">
                <thead><tr><th>Butiran</th><th class="amount">Jumlah (RM)</th></tr></thead>
                <tbody>
                    <tr><td>Gaji asas</td><td class="amount"><?= $moneyValue($p['basic_salary']) ?></td></tr>
                    <tr><td>Jumlah elaun</td><td class="amount"><?= $moneyValue($p['total_allowance']) ?></td></tr>
                    <tr><td>Kerja lebih masa (<?= $moneyValue($p['overtime_hours']) ?> jam)</td><td class="amount"><?= $moneyValue($p['overtime_pay']) ?></td></tr>
                    <tr class="total-row"><td>Gaji kasar</td><td class="amount"><?= $moneyValue($p['gross_salary']) ?></td></tr>
                </tbody>
            </table>

            <div class="payslip-section-title">Potongan pekerja</div>
            <table class="payslip-table">
                <thead><tr><th>Butiran</th><th class="amount">Jumlah (RM)</th></tr></thead>
                <tbody>
                    <?php foreach ($statutory as $label => $amount): ?>
                    <tr><td><?= h($label) ?></td><td class="amount"><?= $moneyValue($amount) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td>Potongan lain</td><td class="amount"><?= $moneyValue($otherDeductions) ?></td></tr>
                    <tr class="total-row"><td>Jumlah potongan</td><td class="amount"><?= $moneyValue($p['total_deduction']) ?></td></tr>
                </tbody>
            </table>

            <div class="payslip-net">
                <span>GAJI BERSIH</span>
                <strong>RM <?= $moneyValue($p['net_salary']) ?></strong>
            </div>

            <p class="payslip-note">
                Slip gaji ini dijana secara elektronik oleh MZR FOOD SERVICES Payroll Management System.
                Sila hubungi pihak pengurusan sekiranya terdapat sebarang pertanyaan mengenai penyata ini.
            </p>
        </div>
        <footer class="payslip-footer">
            <span>MZR FOOD SERVICES</span>
            <span>Dokumen sulit · Untuk kegunaan pekerja sahaja</span>
        </footer>
    </article>
</div>
<?php page_end(); ?>

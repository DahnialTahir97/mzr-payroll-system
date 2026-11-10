<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// Halakan role manager dan employee ke portal masing-masing.
if (($_SESSION['role'] ?? '') === 'manager') { header('Location: manager_portal.php'); exit; }
if (($_SESSION['role'] ?? '') === 'employee') {
    header('Location: employee_portal.php');
    exit;
}

require_once __DIR__ . '/includes/layout.php';

$month = date('Y-m');
$totalEmployees = 0;
$inactiveEmployees = 0;
$totalPayroll = 0;
$monthlyGross = 0.00;
$monthlyNet = 0.00;
$recentPayroll = [];
$attendanceToday = 0;

try {
    $totalEmployees = (int)$conn->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'active'")->fetchColumn();
    $inactiveEmployees = (int)$conn->query("SELECT COUNT(*) FROM employees WHERE employment_status <> 'active'")->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM payroll WHERE salary_month = ?");
    $stmt->execute([$month]);
    $totalPayroll = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COALESCE(SUM(gross_salary),0), COALESCE(SUM(net_salary),0) FROM payroll WHERE salary_month = ?");
    $stmt->execute([$month]);
    $totals = $stmt->fetch(PDO::FETCH_NUM);
    if ($totals) { $monthlyGross = (float)$totals[0]; $monthlyNet = (float)$totals[1]; }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?");
    $stmt->execute([date('Y-m-d')]);
    $attendanceToday = (int)$stmt->fetchColumn();

    $stmt = $conn->query("SELECT p.id, p.salary_month, p.gross_salary, p.net_salary, p.payment_status, e.employee_code, e.full_name FROM payroll p INNER JOIN employees e ON e.id = p.employee_id ORDER BY p.created_at DESC, p.id DESC LIMIT 6");
    $recentPayroll = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Dashboard kekal boleh dipaparkan jika jadual/medan pilihan belum wujud.
    $dashboardNotice = 'Sebahagian ringkasan belum tersedia. Semak struktur jadual database jika angka tidak dipaparkan.';
}

function rm($amount) { return 'RM ' . number_format((float)$amount, 2); }
page_start('Dashboard', 'dashboard');
?>
<style>
.dashboard-hero{background:linear-gradient(120deg,#172554 0%,#24458b 58%,#3f67d8 100%);border-radius:20px;padding:30px 32px;color:#fff;position:relative;overflow:hidden;margin-bottom:24px;box-shadow:0 14px 32px rgba(23,37,84,.16)}
.dashboard-hero:after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-55px;top:-105px;box-shadow:0 0 0 28px rgba(255,255,255,.04),0 0 0 58px rgba(255,255,255,.035)}
.hero-kicker{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c7d7ff;font-weight:800}.hero-title{font-size:28px;font-weight:800;margin:9px 0 7px}.hero-desc{color:#dce6ff;margin:0;max-width:620px}.hero-date{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:9px 13px;border-radius:30px;font-size:13px;margin-top:20px}
.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}.stat-card{background:#fff;border:1px solid #e8edf6;border-radius:16px;padding:20px;box-shadow:0 5px 18px rgba(25,45,85,.035);min-width:0}.stat-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:17px}.stat-label{font-size:12px;color:#71809b;font-weight:700;letter-spacing:.5px;text-transform:uppercase}.stat-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;font-size:19px}.stat-value{font-size:25px;font-weight:800;color:#172554;line-height:1.2}.stat-note{font-size:12px;color:#8a97ad;margin-top:7px}.section-card{background:#fff;border:1px solid #e8edf6;border-radius:17px;padding:22px;box-shadow:0 5px 18px rgba(25,45,85,.035);height:100%}.section-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}.section-heading h3{font-size:16px;font-weight:800;color:#172554;margin:0}.section-heading p{font-size:12px;color:#8793a8;margin:5px 0 0}.quick-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.quick-link{display:flex;align-items:center;gap:12px;text-decoration:none;color:#263653;padding:14px;border:1px solid #e9eef7;border-radius:13px;transition:.18s;background:#fff}.quick-link:hover{border-color:#b9c9f8;background:#f7f9ff;transform:translateY(-2px);color:#244fc2}.quick-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#edf2ff;color:#315bd0;font-size:18px;flex-shrink:0}.quick-link strong{display:block;font-size:13px}.quick-link small{display:block;color:#8a97ad;font-size:11px;margin-top:3px}.table-clean{width:100%;border-collapse:collapse}.table-clean th{font-size:10px;letter-spacing:.7px;color:#8a97ad;text-transform:uppercase;font-weight:800;padding:10px 9px;border-bottom:1px solid #e9eef6;white-space:nowrap}.table-clean td{font-size:12px;color:#34415a;padding:13px 9px;border-bottom:1px solid #f0f3f8}.table-clean tr:last-child td{border-bottom:0}.employee-name{font-weight:700;color:#1c2b49}.muted{font-size:11px;color:#8b98ad;margin-top:3px}.status-pill{display:inline-block;padding:5px 9px;border-radius:20px;background:#e9f8f0;color:#16834d;font-size:10px;font-weight:800}.empty-state{text-align:center;padding:28px 10px;color:#8b98ad;font-size:13px}
@media(max-width:1100px){.stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.quick-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.dashboard-hero{padding:23px}.hero-title{font-size:23px}.stat-grid{grid-template-columns:1fr 1fr;gap:10px}.stat-card{padding:15px}.stat-value{font-size:19px}.quick-grid{grid-template-columns:1fr}.section-card{padding:16px}.table-wrap{overflow-x:auto}}
</style>

<section class="dashboard-hero">
    <div class="hero-kicker">MZR Food Services · Payroll Management</div>
    <div class="hero-title">Selamat datang, <?= e($_SESSION['full_name'] ?? 'Administrator') ?>.</div>
    <p class="hero-desc">Urus pekerja, kehadiran dan pembayaran gaji syarikat dalam satu sistem yang tersusun.</p>
    <div class="hero-date"><i class="bi bi-calendar3"></i><?= date('l, d F Y') ?></div>
</section>

<?php if (!empty($dashboardNotice)): ?><div class="alert alert-warning py-2 small"><?= e($dashboardNotice) ?></div><?php endif; ?>

<section class="stat-grid">
    <div class="stat-card"><div class="stat-top"><span class="stat-label">Pekerja aktif</span><span class="stat-icon" style="background:#eaf1ff;color:#315bd0"><i class="bi bi-people-fill"></i></span></div><div class="stat-value"><?= number_format($totalEmployees) ?></div><div class="stat-note">Pekerja berstatus aktif</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label">Tidak aktif</span><span class="stat-icon" style="background:#fff3e8;color:#d97706"><i class="bi bi-person-dash-fill"></i></span></div><div class="stat-value"><?= number_format($inactiveEmployees) ?></div><div class="stat-note">Rekod pekerja tidak aktif</div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label">Rekod payroll bulan ini</span><span class="stat-icon" style="background:#e8f8f1;color:#15945a"><i class="bi bi-receipt"></i></span></div><div class="stat-value"><?= number_format($totalPayroll) ?></div><div class="stat-note"><?= date('F Y') ?></div></div>
    <div class="stat-card"><div class="stat-top"><span class="stat-label">Jumlah gaji bersih</span><span class="stat-icon" style="background:#f1edff;color:#7652c8"><i class="bi bi-wallet2"></i></span></div><div class="stat-value" style="font-size:21px"><?= rm($monthlyNet) ?></div><div class="stat-note">Payroll bulan semasa</div></div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="section-card">
            <div class="section-heading"><div><h3>Akses pantas</h3><p>Pilih modul untuk mula mengurus operasi payroll.</p></div><i class="bi bi-grid fs-4 text-primary"></i></div>
            <div class="quick-grid">
                <a class="quick-link" href="employees.php"><span class="quick-icon"><i class="bi bi-people"></i></span><span><strong>Pengurusan pekerja</strong><small>Tambah dan kemas kini pekerja</small></span></a>
                <a class="quick-link" href="attendance.php"><span class="quick-icon"><i class="bi bi-calendar-check"></i></span><span><strong>Kehadiran & OT</strong><small>Rekod waktu kerja dan OT</small></span></a>
                <a class="quick-link" href="components.php"><span class="quick-icon"><i class="bi bi-wallet"></i></span><span><strong>Elaun & potongan</strong><small>Urus komponen gaji</small></span></a>
                <a class="quick-link" href="payroll.php"><span class="quick-icon"><i class="bi bi-calculator"></i></span><span><strong>Pengiraan gaji</strong><small>Jana payroll bulanan</small></span></a>
                <a class="quick-link" href="reports.php"><span class="quick-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span><strong>Laporan gaji</strong><small>Semak dan cetak laporan</small></span></a>
                <a class="quick-link" href="employee_statutory.php"><span class="quick-icon"><i class="bi bi-shield-check"></i></span><span><strong>Maklumat statutori</strong><small>Semak maklumat caruman</small></span></a>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="section-card">
            <div class="section-heading"><div><h3>Ringkasan bulan ini</h3><p>Status operasi semasa</p></div><span class="status-pill">BULAN INI</span></div>
            <div class="d-flex justify-content-between py-3 border-bottom"><span class="text-secondary small"><i class="bi bi-calendar-check me-2"></i>Rekod kehadiran hari ini</span><strong><?= number_format($attendanceToday) ?></strong></div>
            <div class="d-flex justify-content-between py-3 border-bottom"><span class="text-secondary small"><i class="bi bi-cash-stack me-2"></i>Jumlah gaji kasar</span><strong><?= rm($monthlyGross) ?></strong></div>
            <div class="d-flex justify-content-between py-3"><span class="text-secondary small"><i class="bi bi-wallet2 me-2"></i>Jumlah gaji bersih</span><strong class="text-primary"><?= rm($monthlyNet) ?></strong></div>
            <a href="payroll.php" class="btn btn-primary w-100 mt-2"><i class="bi bi-arrow-right-circle me-2"></i>Buka pengiraan gaji</a>
        </section>
    </div>
</div>

<section class="section-card mt-4">
    <div class="section-heading"><div><h3>Payroll terkini</h3><p>Enam rekod payroll yang terakhir disimpan.</p></div><a href="reports.php" class="btn btn-sm btn-outline-primary">Lihat laporan <i class="bi bi-arrow-right ms-1"></i></a></div>
    <div class="table-wrap"><table class="table-clean"><thead><tr><th>Bulan</th><th>Pekerja</th><th>Gaji kasar</th><th>Gaji bersih</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if ($recentPayroll): foreach ($recentPayroll as $row): ?><tr><td><?= e(date('M Y', strtotime($row['salary_month'] . '-01'))) ?></td><td><div class="employee-name"><?= e($row['full_name']) ?></div><div class="muted"><?= e($row['employee_code']) ?></div></td><td><?= rm($row['gross_salary']) ?></td><td><strong><?= rm($row['net_salary']) ?></strong></td><td><span class="status-pill"><?= e(ucfirst($row['payment_status'] ?? 'Generated')) ?></span></td><td><a class="btn btn-sm btn-light" href="payslip.php?id=<?= (int)$row['id'] ?>">Payslip</a></td></tr><?php endforeach; else: ?><tr><td colspan="6" class="empty-state">Belum ada rekod payroll. Jana payroll untuk melihat rekod di sini.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php page_end(); ?>

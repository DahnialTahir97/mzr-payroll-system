<?php
require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money($v) {
    return 'RM ' . number_format((float)$v, 2);
}

function page_start($title, $active = '') {
    $role = $_SESSION['role'] ?? '';
    $home = $role === 'employee' ? 'employee_portal.php' : ($role === 'manager' ? 'manager_portal.php' : 'index.php');
?>
<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213d">
    <title><?= h($title) ?> | MZR Payroll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<aside class="sidebar">
    <a class="brand" href="<?= h($home) ?>">
        <span class="brand-mark">M</span>
        <span>MZR <small>PAYROLL</small></span>
    </a>

    <div class="side-label">MENU UTAMA</div>

    <?php if ($role === 'admin'): ?>
        <a class="side-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a class="side-link <?= $active === 'employees' ? 'active' : '' ?>" href="employees.php"><i class="bi bi-people-fill"></i> Pengurusan Pekerja</a>
        <a class="side-link <?= $active === 'attendance' ? 'active' : '' ?>" href="attendance.php"><i class="bi bi-calendar2-check-fill"></i> Kehadiran & OT</a>
        <a class="side-link <?= $active === 'components' ? 'active' : '' ?>" href="components.php"><i class="bi bi-wallet2"></i> Elaun & Potongan</a>
        <a class="side-link <?= $active === 'payroll' ? 'active' : '' ?>" href="payroll.php"><i class="bi bi-calculator-fill"></i> Pengiraan Gaji</a>
        <a class="side-link <?= $active === 'reports' ? 'active' : '' ?>" href="reports.php"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan</a>
    <?php elseif ($role === 'manager'): ?>
        <a class="side-link <?= $active === 'manager' ? 'active' : '' ?>" href="manager_portal.php"><i class="bi bi-clipboard-check"></i> Pantau & Sahkan Slip</a>
        <a class="side-link <?= $active === 'attendance' ? 'active' : '' ?>" href="attendance.php"><i class="bi bi-calendar2-check-fill"></i> Kehadiran Pekerja</a>
    <?php else: ?>
        <a class="side-link <?= $active === 'employee' ? 'active' : '' ?>" href="employee_portal.php"><i class="bi bi-receipt"></i> Portal Saya</a>
    <?php endif; ?>

    <div class="sidebar-bottom">
        <div class="user-chip">
            <div class="avatar"><?= h(strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1))) ?></div>
            <div class="user-details">
                <strong><?= h($_SESSION['full_name'] ?? 'Pengguna') ?></strong>
                <small><?= h(strtoupper($role)) ?></small>
            </div>
        </div>
        <a class="logout" href="logout.php"><i class="bi bi-box-arrow-right"></i> Log keluar</a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <span class="eyebrow">MZR FOOD SERVICES</span>
            <div class="top-title"><?= h($title) ?></div>
        </div>
        <div class="top-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></div>
    </header>
    <div class="content-wrap">
        <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="alert alert-<?= h($flash['type'] ?? 'success') ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>
<?php
}

function page_end() {
?>
    </div>
    <footer class="footer">© <?= date('Y') ?> MZR FOOD SERVICES · Payroll Management System</footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

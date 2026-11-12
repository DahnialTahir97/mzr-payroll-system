<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = trim($_POST['employee_code'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $salaryRaw = $_POST['basic_salary'] ?? '';
        $salary = filter_var($salaryRaw, FILTER_VALIDATE_FLOAT);
        $status = $_POST['employment_status'] ?? 'active';

        $valid = $code !== '' && $name !== '' && $position !== ''
            && $salary !== false && $salary >= 0
            && in_array($status, ['active', 'inactive'], true)
            && ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL));

        if (!$valid) {
            set_flash('Sila semak maklumat wajib, gaji dan format e-mel.', 'danger');
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO employees
                    (employee_code, full_name, email, phone, position, basic_salary, employment_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$code, $name, $email, $phone, $position, $salary, $status]);
                set_flash('Pekerja berjaya ditambah.');
            } catch (PDOException $e) {
                error_log('Add employee failed: ' . $e->getMessage());
                set_flash('Gagal tambah pekerja. Pastikan kod pekerja belum digunakan.', 'danger');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            set_flash('Rekod pekerja tidak sah.', 'danger');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM employees WHERE id = ?');
                $stmt->execute([$id]);
                set_flash($stmt->rowCount() ? 'Rekod pekerja dipadam.' : 'Pekerja tidak dijumpai.', $stmt->rowCount() ? 'success' : 'warning');
            } catch (PDOException $e) {
                error_log('Delete employee failed: ' . $e->getMessage());
                set_flash('Pekerja mempunyai rekod berkaitan. Tukar status kepada Tidak Aktif.', 'danger');
            }
        }
    } else {
        set_flash('Tindakan tidak dikenali.', 'danger');
    }

    header('Location: employees.php');
    exit;
}

$rows = $pdo->query('SELECT * FROM employees ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/includes/layout.php';
page_start('Pengurusan Pekerja', 'employees');
?>

<div class="page-intro">
    <div>
        <h2>Pengurusan pekerja</h2>
        <p>Daftar pekerja, kemas kini maklumat dan urus status pekerjaan.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <section class="panel-card">
            <h3 class="mb-3">Daftar pekerja</h3>
            <form method="post" action="employees.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="add">

                <label class="form-label" for="employee_code">Kod pekerja *</label>
                <input class="form-control mb-3" id="employee_code" name="employee_code" maxlength="50" required>

                <label class="form-label" for="full_name">Nama penuh *</label>
                <input class="form-control mb-3" id="full_name" name="full_name" maxlength="150" required>

                <label class="form-label" for="position">Jawatan *</label>
                <input class="form-control mb-3" id="position" name="position" maxlength="100" required>

                <label class="form-label" for="email">E-mel</label>
                <input class="form-control mb-3" id="email" type="email" name="email" autocomplete="email">

                <label class="form-label" for="phone">Telefon</label>
                <input class="form-control mb-3" id="phone" type="tel" name="phone" autocomplete="tel" inputmode="tel">

                <label class="form-label" for="basic_salary">Gaji asas (RM) *</label>
                <input class="form-control mb-3" id="basic_salary" type="number" name="basic_salary" min="0" step="0.01" inputmode="decimal" required>

                <label class="form-label" for="employment_status">Status</label>
                <select class="form-select mb-3" id="employment_status" name="employment_status">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak aktif</option>
                </select>

                <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-person-plus me-1"></i> Simpan pekerja
                </button>
            </form>
        </section>
    </div>

    <div class="col-xl-8">
        <section class="panel-card">
            <div class="panel-heading employee-list-heading">
                <h3>Senarai pekerja <span class="count-pill"><?= count($rows) ?></span></h3>
                <input class="form-control search-box" id="searchEmp" type="search" placeholder="Cari nama, kod atau jawatan..." aria-label="Cari pekerja">
            </div>

            <div class="table-responsive employee-table-wrap">
                <table class="table employee-table" id="empTable">
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>Nama / jawatan</th>
                            <th>Gaji asas</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td data-label="Kod"><?= h($r['employee_code']) ?></td>
                            <td data-label="Nama / jawatan">
                                <strong><?= h($r['full_name']) ?></strong>
                                <small class="d-block muted"><?= h($r['position']) ?></small>
                            </td>
                            <td data-label="Gaji asas" class="money">RM <?= number_format((float)$r['basic_salary'], 2) ?></td>
                            <td data-label="Status">
                                <span class="badge <?= $r['employment_status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $r['employment_status'] === 'active' ? 'Aktif' : 'Tidak aktif' ?>
                                </span>
                            </td>
                            <td data-label="Tindakan">
                                <div class="employee-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="edit_employee.php?id=<?= (int)$r['id'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Edit / Update
                                    </a>
                                    <form method="post" action="employees.php" onsubmit="return confirm('Padam rekod pekerja ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash me-1"></i> Padam
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <tr class="empty-employee-row">
                            <td colspan="5" class="text-center muted">Belum ada pekerja. Gunakan borang Daftar pekerja untuk menambah rekod.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="employee-mobile-hint mb-0 mt-3"><i class="bi bi-info-circle me-1"></i> Pada telefon, setiap pekerja dipaparkan sebagai kad supaya butang tindakan mudah dicapai.</p>
        </section>
    </div>
</div>

<script>
(function () {
    const search = document.getElementById('searchEmp');
    const table = document.getElementById('empTable');
    if (!search || !table) return;

    search.addEventListener('input', function () {
        const query = this.value.trim().toLocaleLowerCase();
        table.querySelectorAll('tbody tr:not(.empty-employee-row)').forEach(function (row) {
            row.hidden = !row.innerText.toLocaleLowerCase().includes(query);
        });
    });
})();
</script>

<?php page_end(); ?>

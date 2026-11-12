<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit('ID pekerja tidak sah.');
}

$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    http_response_code(404);
    exit('Pekerja tidak dijumpai.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $code = trim($_POST['employee_code'] ?? '');
    $name = trim($_POST['full_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $salaryRaw = $_POST['basic_salary'] ?? '';
    $salary = filter_var($salaryRaw, FILTER_VALIDATE_FLOAT);
    $status = $_POST['employment_status'] ?? '';

    if ($code === '') $errors[] = 'Kod pekerja wajib diisi.';
    if ($name === '') $errors[] = 'Nama penuh wajib diisi.';
    if ($position === '') $errors[] = 'Jawatan wajib diisi.';
    if ($salary === false || $salary < 0) $errors[] = 'Gaji asas mesti nombor yang sah dan tidak negatif.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format e-mel tidak sah.';
    if (!in_array($status, ['active', 'inactive'], true)) $errors[] = 'Status pekerjaan tidak sah.';

    if (!$errors) {
        try {
            $update = $pdo->prepare(
                'UPDATE employees
                 SET employee_code = ?, full_name = ?, email = ?, phone = ?,
                     position = ?, basic_salary = ?, employment_status = ?
                 WHERE id = ?'
            );
            $update->execute([$code, $name, $email, $phone, $position, $salary, $status, $id]);
            set_flash('Maklumat pekerja berjaya dikemas kini.');
            header('Location: employees.php');
            exit;
        } catch (PDOException $e) {
            error_log('Update employee failed: ' . $e->getMessage());
            $errors[] = 'Gagal mengemas kini pekerja. Pastikan kod pekerja tidak digunakan oleh pekerja lain.';
        }
    }

    // Keep the submitted values in the form when validation fails.
    $emp = array_merge($emp, [
        'employee_code' => $code,
        'full_name' => $name,
        'position' => $position,
        'email' => $email,
        'phone' => $phone,
        'basic_salary' => $salaryRaw,
        'employment_status' => $status
    ]);
}

require __DIR__ . '/includes/layout.php';
page_start('Edit Pekerja', 'employees');
?>

<div class="page-intro">
    <div>
        <h2>Kemas kini pekerja</h2>
        <p>Semak maklumat sebelum menyimpan perubahan.</p>
    </div>
    <a href="employees.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<div class="panel-card employee-edit-card">
    <h3 class="mb-3">Maklumat pekerja</h3>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Maklumat belum disimpan:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="edit_employee.php?id=<?= $id ?>" class="employee-edit-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="employee_code">Kod pekerja *</label>
                <input class="form-control" id="employee_code" name="employee_code" maxlength="50" value="<?= h($emp['employee_code'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="full_name">Nama penuh *</label>
                <input class="form-control" id="full_name" name="full_name" maxlength="150" value="<?= h($emp['full_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="position">Jawatan *</label>
                <input class="form-control" id="position" name="position" maxlength="100" value="<?= h($emp['position'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="basic_salary">Gaji asas (RM) *</label>
                <input class="form-control" id="basic_salary" type="number" name="basic_salary" min="0" step="0.01" inputmode="decimal" value="<?= h($emp['basic_salary'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">E-mel (pilihan)</label>
                <input class="form-control" id="email" type="email" name="email" autocomplete="email" value="<?= h($emp['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Telefon (pilihan)</label>
                <input class="form-control" id="phone" type="tel" name="phone" autocomplete="tel" inputmode="tel" value="<?= h($emp['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="employment_status">Status pekerjaan</label>
                <select class="form-select" id="employment_status" name="employment_status" required>
                    <option value="active" <?= ($emp['employment_status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= ($emp['employment_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Tidak aktif</option>
                </select>
            </div>
        </div>

        <div class="employee-edit-actions mt-4">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i> Simpan perubahan</button>
            <a href="employees.php" class="btn btn-light">Batal</a>
        </div>
    </form>
</div>

<?php page_end(); ?>

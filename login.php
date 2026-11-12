<?php
// MZR FOOD SERVICES - Login (compatible with password OR password_hash column)
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';

if (!function_exists('mzr_e')) {
    function mzr_e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!empty($_SESSION['user_id'])) {
    $role = strtolower(isset($_SESSION['role']) ? $_SESSION['role'] : '');
    header('Location: ' . ($role === 'employee' ? 'employee_portal.php' : ($role === 'manager' ? 'manager_portal.php' : 'index.php')));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Sila masukkan username dan kata laluan.';
    } else {
        try {
            // Detect the actual password column in the existing users table.
            $columnRows = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
            $columns = array();
            foreach ($columnRows as $columnRow) {
                $columns[] = $columnRow['Field'];
            }

            if (!in_array('username', $columns, true)) {
                throw new Exception('Column username tiada dalam jadual users.');
            }
            if (in_array('password_hash', $columns, true)) {
                $passwordColumn = 'password_hash';
            } elseif (in_array('password', $columns, true)) {
                $passwordColumn = 'password';
            } else {
                throw new Exception('Jadual users tiada column password atau password_hash.');
            }

            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute(array($username));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $validPassword = false;
            if ($user && isset($user[$passwordColumn])) {
                $stored = (string)$user[$passwordColumn];
                // Prefer secure password hashes; support legacy plaintext only for existing accounts.
                $info = password_get_info($stored);
                if (!empty($info['algo'])) {
                    $validPassword = password_verify($password, $stored);
                } else {
                    $validPassword = hash_equals($stored, $password);
                    // Upgrade legacy plaintext password to a secure hash after successful login.
                    if ($validPassword && $passwordColumn === 'password_hash') {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare('UPDATE users SET `' . $passwordColumn . '` = ? WHERE id = ?');
                        $update->execute(array($newHash, $user['id']));
                    }
                }
            }

            if (!$user || !$validPassword) {
                $error = 'Username atau kata laluan tidak tepat.';
            } else {
                if (!isset($user['id']) || !isset($user['role'])) {
                    throw new Exception('Jadual users mesti mempunyai column id dan role.');
                }
                $role = strtolower((string)$user['role']);
                if (!in_array($role, array('admin', 'manager', 'employee'), true)) {
                    throw new Exception('Peranan akaun tidak sah. Sila hubungi Admin.');
                }
                if ($role === 'employee' && (empty($user['employee_id']) || !in_array('employee_id', $columns, true))) {
                    $error = 'Akaun pekerja belum dipautkan kepada rekod pekerja.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = (string)$user['username'];
                    $_SESSION['full_name'] = isset($user['full_name']) ? $user['full_name'] : $user['username'];
                    $_SESSION['role'] = $role;
                    $_SESSION['employee_id'] = isset($user['employee_id']) ? $user['employee_id'] : null;
                    header('Location: ' . ($role === 'employee' ? 'employee_portal.php' : ($role === 'manager' ? 'manager_portal.php' : 'index.php')));
                    exit;
                }
            }
        } catch (PDOException $e) {
            // Avoid exposing database credentials or SQL details to visitors.
            $error = 'Tidak dapat memproses login. Semak jadual users dan sambungan database.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log Masuk | MZR Payroll</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;padding:22px;display:flex;align-items:center;justify-content:center;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#101d38,#244b86);color:#172554}.shell{width:100%;max-width:940px;min-height:540px;display:grid;grid-template-columns:1fr 1fr;background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.25)}.brand{padding:55px 45px;background:linear-gradient(145deg,#142541,#24477b);color:#fff;display:flex;flex-direction:column;justify-content:center}.mark{width:66px;height:66px;border-radius:18px;background:#4169e1;display:flex;align-items:center;justify-content:center;font-size:25px;font-weight:800;letter-spacing:1px;margin-bottom:25px}.eyebrow{font-size:11px;letter-spacing:3px;font-weight:bold;color:#a9c2f7}.brand h1{font-size:34px;line-height:1.2;margin:18px 0}.brand p{color:#d4def0;line-height:1.7}.brand-foot{margin-top:65px;font-size:10px;letter-spacing:2px;color:#9fb2d2}.panel{padding:58px 48px;display:flex;flex-direction:column;justify-content:center}.tag{font-size:11px;font-weight:bold;letter-spacing:2px;color:#4169e1}.panel h2{font-size:29px;margin:12px 0 8px;color:#172554}.sub{font-size:14px;color:#718096;margin:0 0 30px}.field{margin-bottom:20px}.field label{display:block;font-size:13px;font-weight:bold;margin-bottom:8px;color:#334155}.field input{width:100%;padding:14px 15px;border:1px solid #d9e2ef;border-radius:10px;font-size:15px;outline:none}.field input:focus{border-color:#4169e1;box-shadow:0 0 0 3px #4169e11c}.submit{width:100%;border:0;border-radius:10px;padding:15px;background:#4169e1;color:#fff;font-size:15px;font-weight:bold;cursor:pointer}.submit:hover{background:#3156c5}.error{background:#fff0f0;color:#b42318;border:1px solid #ffd2d2;border-radius:9px;padding:12px;margin-bottom:18px;font-size:13px}.note{font-size:12px;color:#8793a7;margin-top:22px;line-height:1.6}.copyright{text-align:center;font-size:11px;color:#9aa5b5;margin-top:28px}@media(max-width:700px){body{padding:12px;background:#f1f5fb}.shell{max-width:430px;min-height:0;display:block;border-radius:18px}.brand{padding:28px 26px}.mark{width:52px;height:52px;margin-bottom:12px}.brand h1{font-size:24px;margin:8px 0}.brand p{font-size:13px;margin:0}.brand-foot{display:none}.panel{padding:32px 25px}.panel h2{font-size:25px}.sub{margin-bottom:23px}}
</style>
</head>
<body>
<div class="shell">
  <section class="brand">
    <div class="mark">MZR</div>
    <div class="eyebrow">MZR FOOD SERVICES</div>
    <h1>Pengurusan gaji, lebih mudah.</h1>
    <p>Satu platform untuk mengurus pekerja, kehadiran dan penggajian dengan lebih teratur.</p>
    <div class="brand-foot">PAYROLL MANAGEMENT SYSTEM</div>
  </section>
  <section class="panel">
    <div class="tag">SELAMAT DATANG</div>
    <h2>Log masuk</h2>
    <p class="sub">Masukkan akaun Admin, Manager atau Pekerja.</p>
    <?php if ($error !== ''): ?><div class="error"><?php echo mzr_e($error); ?></div><?php endif; ?>
    <form method="post" action="">
      <div class="field"><label for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" value="<?php echo mzr_e(isset($_POST['username']) ? $_POST['username'] : ''); ?>" required autofocus></div>
      <div class="field"><label for="password">Kata laluan</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <button class="submit" type="submit">Log Masuk&nbsp; →</button>
    </form>
    <div class="note">Akses sistem ditentukan berdasarkan peranan akaun yang didaftarkan oleh Admin.</div>
    <div class="copyright">© <?php echo date('Y'); ?> MZR FOOD SERVICES</div>
  </section>
</div>
</body>
</html>

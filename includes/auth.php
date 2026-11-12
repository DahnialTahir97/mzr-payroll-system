<?php
require_once __DIR__ . '/bootstrap.php';

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_roles(array $roles): void
{
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function require_admin(): void
{
    require_roles(['admin']);
}

// Payroll setup and administrative records are restricted to Admin.
function require_manager(): void
{
    require_roles(['admin']);
}

function require_payroll_reviewer(): void
{
    require_roles(['admin', 'manager']);
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    $stored = (string)($_SESSION['csrf_token'] ?? '');
    if ($stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(419);
        exit('Permintaan tidak sah atau sesi telah tamat. Sila muat semula halaman.');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

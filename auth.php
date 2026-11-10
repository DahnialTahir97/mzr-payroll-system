<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
function require_login(): void { if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; } }
function require_roles(array $roles): void { require_login(); if (!in_array($_SESSION['role'] ?? '', $roles, true)) { http_response_code(403); exit('Akses ditolak.'); } }
function require_admin(): void { require_roles(['admin']); }
function require_manager(): void { require_roles(['admin']); }
function require_payroll_reviewer(): void { require_roles(['admin','manager']); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(419); exit('Permintaan tidak sah.'); } }
function set_flash(string $m,string $t='success'): void { $_SESSION['flash']=['message'=>$m,'type'=>$t]; }
?>
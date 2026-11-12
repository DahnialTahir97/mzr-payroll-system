<?php
// Minimal deployment health check. Never returns database credentials or SQL errors.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/config/database.php';
    $pdo->query('SELECT 1');
    echo json_encode([
        'status' => 'ok',
        'application' => 'MZR Payroll',
        'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        'database' => 'connected',
        'timezone' => date_default_timezone_get()
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    error_log('MZR health check failed: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'database' => 'unavailable']);
}

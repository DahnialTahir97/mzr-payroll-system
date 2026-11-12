<?php
/**
 * Application bootstrap.
 * Uses the primary MySQL database for shared PHP sessions so logins survive
 * Vercel container scale-out and requests routed to different instances.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/database_session_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', '7200');

    $secureCookie = (
        (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $sessionHandler = new MzrDatabaseSessionHandler($pdo);
    session_set_save_handler($sessionHandler, true);
    session_start();
}

<?php

require_once __DIR__ . '/db-mysqli.php';
require_once __DIR__ . '/remember-me.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/studenthub-web-frameworks/',
    'secure' => !empty($_SERVER['HTTPS'])
                && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$timeoutSeconds = 900; // 15 minutes

if (isset($_SESSION['user_id'])) {
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if ($lastActivity === 0 || time() - $lastActivity >= $timeoutSeconds) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax'
            ]);
        }

        session_destroy();

        header('Location: ../html/login.html?reason=timeout');
        exit;
    }

    $_SESSION['last_activity'] = time();
    return;
}

// No active session: attempt Remember-Me restoration.
if (restoreRememberedUser($conn)) {
    return;
}

header('Location: ../html/login.html?reason=login');
exit;
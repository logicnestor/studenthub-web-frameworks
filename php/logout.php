<?php

require_once __DIR__ . '/db-mysqli.php';
require_once __DIR__ . '/remember-me.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

clearRememberCookie($conn);

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

header('Location: ../html/login.html?reason=loggedout');
exit;
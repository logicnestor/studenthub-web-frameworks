<?php

const REMEMBER_COOKIE = 'studenthub_remember';
const REMEMBER_DAYS = 30;
const REMEMBER_PATH = '/studenthub-web-frameworks/';

function rememberCookieOptions(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => REMEMBER_PATH,
        'secure'   => !empty($_SERVER['HTTPS'])
                      && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
}

function clearRememberCookie(mysqli $conn): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';

    if (is_string($cookie) && strpos($cookie, ':') !== false) {
        [$selector] = explode(':', $cookie, 2);

        if (preg_match('/^[a-f0-9]{32}$/', $selector)) {
            $stmt = $conn->prepare(
                'DELETE FROM remember_tokens WHERE selector = ?'
            );

            if ($stmt) {
                $stmt->bind_param('s', $selector);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    setcookie(
        REMEMBER_COOKIE,
        '',
        rememberCookieOptions(time() - 3600)
    );

    unset($_COOKIE[REMEMBER_COOKIE]);
}

function issueRememberToken(mysqli $conn, int $userId): void
{
    // Revoke the previous browser token before issuing a new one.
    clearRememberCookie($conn);

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expires = time() + (REMEMBER_DAYS * 86400);
    $expiresAt = date('Y-m-d H:i:s', $expires);

    $stmt = $conn->prepare(
        'INSERT INTO remember_tokens
         (user_id, selector, token_hash, expires_at, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );

    if (!$stmt) {
        throw new RuntimeException('Could not prepare remember-token insert.');
    }

    $stmt->bind_param(
        'isss',
        $userId,
        $selector,
        $tokenHash,
        $expiresAt
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Could not save remember token.');
    }

    $stmt->close();

    setcookie(
        REMEMBER_COOKIE,
        $selector . ':' . $validator,
        rememberCookieOptions($expires)
    );
}

function restoreRememberedUser(mysqli $conn): bool
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';

    if (!is_string($cookie) || strpos($cookie, ':') === false) {
        return false;
    }

    [$selector, $validator] = explode(':', $cookie, 2);

    if (
        !preg_match('/^[a-f0-9]{32}$/', $selector) ||
        !preg_match('/^[a-f0-9]{64}$/', $validator)
    ) {
        clearRememberCookie($conn);
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT rt.user_id, rt.token_hash, u.username, u.name,
                u.email, u.role
         FROM remember_tokens rt
         JOIN users u ON u.user_id = rt.user_id
         WHERE rt.selector = ?
           AND rt.expires_at > NOW()
         LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (
        !$record ||
        !in_array($record['role'], ['student', 'admin'], true) ||
        !hash_equals(
            $record['token_hash'],
            hash('sha256', $validator)
        )
    ) {
        clearRememberCookie($conn);
        return false;
    }

    // Delete the used token so it cannot be reused after restoration.
    $delete = $conn->prepare(
        'DELETE FROM remember_tokens WHERE selector = ?'
    );

    if ($delete) {
        $delete->bind_param('s', $selector);
        $delete->execute();
        $delete->close();
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $record['user_id'];
    $_SESSION['username'] = $record['username'];
    $_SESSION['name'] = $record['name'];
    $_SESSION['email'] = $record['email'];
    $_SESSION['role'] = $record['role'];
    $_SESSION['last_activity'] = time();

    $loginUpdate = $conn->prepare(
        'UPDATE users SET last_login = NOW() WHERE user_id = ?'
    );

    if ($loginUpdate) {
        $loginUpdate->bind_param('i', $_SESSION['user_id']);
        $loginUpdate->execute();
        $loginUpdate->close();
    }

    // Issue a fresh token after successful restoration.
    issueRememberToken($conn, $_SESSION['user_id']);

    return true;
}
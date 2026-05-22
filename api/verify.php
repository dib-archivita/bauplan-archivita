<?php
/**
 * GET /api/verify.php?token=…
 *   Prüft Magic-Link, legt Session-Cookie an, Redirect zur App.
 */
declare(strict_types=1);

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$token = (string)($_GET['token'] ?? '');
if ($token === '' || strlen($token) > 128) {
    header('Location: ' . APP_URL . '/login.html?err=invalid');
    exit;
}

$userId = magic_link_verify($token);
if (!$userId) {
    header('Location: ' . APP_URL . '/login.html?err=expired');
    exit;
}

session_create($userId);
audit_log($userId, 'user.login_success', 'user', (string)$userId);

header('Location: ' . APP_URL . '/');
exit;

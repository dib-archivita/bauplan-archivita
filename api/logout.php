<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$u = current_user();
if ($u) audit_log((int)$u['id'], 'user.logout', 'user', (string)$u['id']);
session_destroy();
json_response(['ok' => true]);

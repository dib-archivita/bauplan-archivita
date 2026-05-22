<?php
/**
 * GET /api/me.php
 *   Liefert aktuellen User oder 401.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$u = require_user();
json_response([
    'id'    => (int)$u['id'],
    'email' => $u['email'],
    'name'  => $u['name'],
    'role'  => $u['role'],
]);

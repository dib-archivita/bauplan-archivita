<?php
/**
 * /api/users.php — Nutzerverwaltung (nur Admin)
 *
 *   GET                     Liste aller User
 *   POST  {email,name,role} anlegen
 *   PATCH {id, ...}         ändern (Rolle, Name, aktiv-Flag)
 *   DELETE {id}             löschen
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$u = require_user();
if ($u['role'] !== ROLE_ADMIN) json_error('Nur Admin', 403);

$method = $_SERVER['REQUEST_METHOD'];
$db = db();

if ($method === 'GET') {
    $stmt = $db->query(
        'SELECT id, email, name, role, active, last_login_at, created_at
         FROM users ORDER BY role, name'
    );
    json_response(['ok' => true, 'users' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $b = read_json_body();
    $email = strtolower(trim((string)($b['email'] ?? '')));
    $name  = str_clip((string)($b['name'] ?? ''), 120);
    $role  = $b['role'] ?? ROLE_VIEWER;

    if (!valid_email($email)) json_error('Ungültige Email', 400);
    if ($name === '')          json_error('Name fehlt', 400);
    if (!isset(ROLE_RANK[$role])) json_error('Rolle ungültig', 400);

    $stmt = $db->prepare('INSERT INTO users (email, name, role) VALUES (:e, :n, :r)');
    try {
        $stmt->execute([':e' => $email, ':n' => $name, ':r' => $role]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') json_error('Email bereits vergeben', 409);
        throw $e;
    }
    $id = (int) $db->lastInsertId();
    audit_log((int)$u['id'], 'user.create', 'user', (string)$id, ['email'=>$email,'role'=>$role]);
    json_response(['ok' => true, 'id' => $id]);
}

if ($method === 'PATCH') {
    $b = read_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_error('id fehlt', 400);

    if ($id === (int)$u['id'] && isset($b['role']) && $b['role'] !== ROLE_ADMIN) {
        json_error('Du kannst dich nicht selbst degradieren.', 400);
    }

    $allowed = ['name','role','active'];
    $sets = []; $params = [':id' => $id];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $b)) {
            $sets[] = "$k = :$k";
            $params[":$k"] = $b[$k];
        }
    }
    if (!$sets) json_error('Nichts zu ändern', 400);

    $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($params);
    audit_log((int)$u['id'], 'user.update', 'user', (string)$id, $b);
    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    $b = read_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_error('id fehlt', 400);
    if ($id === (int)$u['id']) json_error('Du kannst dich nicht selbst löschen.', 400);
    $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
    audit_log((int)$u['id'], 'user.delete', 'user', (string)$id);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt', 405);

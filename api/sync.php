<?php
/**
 * /api/sync.php — Live-Sync der Plan-Änderungen
 *
 *   GET  ?since=<ISO>        → { overrides:[...], custom:[...], server_time }
 *                              since optional → ohne since = ALLES (Initial-Load)
 *
 *   POST { op:"override", entity_type, entity_key, field, value }
 *   POST { op:"custom_add", item_type, client_id, parent_key, after_key, data }
 *   POST { op:"custom_update", client_id, data }
 *   POST { op:"custom_delete", client_id }
 *
 *   Rollen:
 *     viewer  → nur GET
 *     worker  → GET + override(field in status/progress/notiz)
 *     architekt/admin → alles
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$u = require_user();
$db = db();
$method = $_SERVER['REQUEST_METHOD'];
$role = $u['role'];

// ── GET: aktuelle Änderungen liefern ─────────────────────────────────
if ($method === 'GET') {
    require_role($u, 'state.read');
    $since = $_GET['since'] ?? null;
    $validSince = $since && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since);

    if ($validSince) {
        $ovStmt = $db->prepare(
            'SELECT o.entity_type, o.entity_key, o.field, o.value, o.updated_at,
                    u.name AS updated_by_name
             FROM overrides o LEFT JOIN users u ON u.id = o.updated_by
             WHERE o.updated_at > :since ORDER BY o.updated_at'
        );
        $ovStmt->execute([':since' => $since]);
        $ciStmt = $db->prepare(
            'SELECT c.*, u.name AS created_by_name
             FROM custom_items c LEFT JOIN users u ON u.id = c.created_by
             WHERE c.updated_at > :since ORDER BY c.id'
        );
        $ciStmt->execute([':since' => $since]);
    } else {
        $ovStmt = $db->query(
            'SELECT o.entity_type, o.entity_key, o.field, o.value, o.updated_at,
                    u.name AS updated_by_name
             FROM overrides o LEFT JOIN users u ON u.id = o.updated_by
             ORDER BY o.updated_at'
        );
        $ciStmt = $db->query(
            'SELECT c.*, u.name AS created_by_name
             FROM custom_items c LEFT JOIN users u ON u.id = c.created_by
             ORDER BY c.id'
        );
    }

    $custom = [];
    foreach ($ciStmt->fetchAll() as $row) {
        $row['data'] = json_decode($row['data'] ?? '{}', true);
        $row['deleted'] = (int)$row['deleted'];
        $custom[] = $row;
    }

    json_response([
        'ok'          => true,
        'server_time' => date('Y-m-d H:i:s'),
        'overrides'   => $ovStmt->fetchAll(),
        'custom'      => $custom,
    ]);
}

// ── POST: Änderung schreiben ─────────────────────────────────────────
if ($method === 'POST') {
    $b  = read_json_body();
    $op = $b['op'] ?? '';

    // Welche Felder darf ein Worker ändern?
    $workerFields = ['status', 'progress', 'notiz'];

    if ($op === 'override') {
        $type  = $b['entity_type'] ?? '';
        $key   = str_clip((string)($b['entity_key'] ?? ''), 120);
        $field = str_clip((string)($b['field'] ?? ''), 40);
        $value = $b['value'] ?? null;

        if (!in_array($type, ['task','section','kfw'], true)) json_error('entity_type ungültig', 400);
        if ($key === '' || $field === '') json_error('key/field fehlt', 400);

        // Rollen-Check
        if ($role === ROLE_VIEWER) json_error('Keine Berechtigung', 403);
        if ($role === ROLE_WORKER && !in_array($field, $workerFields, true)) {
            json_error('Worker darf nur Status/Notiz ändern', 403);
        }

        $stmt = $db->prepare(
            'INSERT INTO overrides (entity_type, entity_key, field, value, updated_by)
             VALUES (:t, :k, :f, :v, :uid)
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)'
        );
        $stmt->execute([
            ':t' => $type, ':k' => $key, ':f' => $field,
            ':v' => $value !== null ? (string)$value : null,
            ':uid' => (int)$u['id'],
        ]);
        audit_log((int)$u['id'], 'sync.override', $type, $key, ['field'=>$field, 'value'=>$value]);
        json_response(['ok' => true, 'server_time' => date('Y-m-d H:i:s')]);
    }

    if ($op === 'custom_add') {
        if (!in_array($role, [ROLE_ADMIN, ROLE_ARCHITEKT], true)) json_error('Keine Berechtigung', 403);
        $itemType  = $b['item_type'] ?? 'task';
        $clientId  = str_clip((string)($b['client_id'] ?? ''), 120);
        $parentKey = $b['parent_key'] ?? null;
        $afterKey  = $b['after_key'] ?? null;
        $data      = $b['data'] ?? [];
        if ($clientId === '') json_error('client_id fehlt', 400);

        $stmt = $db->prepare(
            'INSERT INTO custom_items (item_type, client_id, parent_key, after_key, data, created_by)
             VALUES (:it, :cid, :pk, :ak, :data, :uid)
             ON DUPLICATE KEY UPDATE data = VALUES(data), parent_key = VALUES(parent_key),
                                     after_key = VALUES(after_key), deleted = 0'
        );
        $stmt->execute([
            ':it' => $itemType === 'section' ? 'section' : 'task',
            ':cid' => $clientId,
            ':pk' => $parentKey,
            ':ak' => $afterKey,
            ':data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ':uid' => (int)$u['id'],
        ]);
        audit_log((int)$u['id'], 'sync.custom_add', $itemType, $clientId, $data);
        json_response(['ok' => true, 'server_time' => date('Y-m-d H:i:s')]);
    }

    if ($op === 'custom_update') {
        if ($role === ROLE_VIEWER) json_error('Keine Berechtigung', 403);
        $clientId = str_clip((string)($b['client_id'] ?? ''), 120);
        $data     = $b['data'] ?? [];
        if ($clientId === '') json_error('client_id fehlt', 400);
        // Worker darf nur status/notiz/progress in data ändern
        if ($role === ROLE_WORKER) {
            $data = array_intersect_key($data, array_flip($workerFields));
            if (!$data) json_error('Worker darf nur Status/Notiz ändern', 403);
            // Merge mit bestehenden data
            $cur = $db->prepare('SELECT data FROM custom_items WHERE client_id = :c');
            $cur->execute([':c' => $clientId]);
            $existing = json_decode($cur->fetchColumn() ?: '{}', true);
            $data = array_merge($existing, $data);
        }
        $stmt = $db->prepare('UPDATE custom_items SET data = :data WHERE client_id = :c');
        $stmt->execute([':data' => json_encode($data, JSON_UNESCAPED_UNICODE), ':c' => $clientId]);
        audit_log((int)$u['id'], 'sync.custom_update', 'task', $clientId, $data);
        json_response(['ok' => true, 'server_time' => date('Y-m-d H:i:s')]);
    }

    if ($op === 'custom_delete') {
        if (!in_array($role, [ROLE_ADMIN, ROLE_ARCHITEKT], true)) json_error('Keine Berechtigung', 403);
        $clientId = str_clip((string)($b['client_id'] ?? ''), 120);
        $db->prepare('UPDATE custom_items SET deleted = 1 WHERE client_id = :c')
           ->execute([':c' => $clientId]);
        audit_log((int)$u['id'], 'sync.custom_delete', 'task', $clientId);
        json_response(['ok' => true, 'server_time' => date('Y-m-d H:i:s')]);
    }

    json_error('Unbekannte Operation', 400);
}

json_error('Methode nicht erlaubt', 405);

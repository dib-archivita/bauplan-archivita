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

// WICHTIG: server_time MUSS aus derselben Uhr kommen wie updated_at (MySQL),
// sonst greift der ?since-Filter daneben und der Auto-Poll verpasst Änderungen.
// 2 Sek. Sicherheitspuffer → minimaler Overlap (Apply ist idempotent), nichts geht verloren.
$serverTime = $db->query('SELECT DATE_SUB(NOW(), INTERVAL 2 SECOND)')->fetchColumn();

// KV-Tabelle bei Bedarf anlegen (generischer Sync für Neben-Tabs)
$db->exec(
  'CREATE TABLE IF NOT EXISTS kv_state (
     k VARCHAR(160) NOT NULL PRIMARY KEY,
     v MEDIUMTEXT NULL,
     updated_by INT UNSIGNED NULL,
     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     INDEX idx_kv_updated (updated_at)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

// ── Einmal-Migration: Plan-Ursprung KW19 → KW23 (bar_left −168 px) ────
// v81: Der Hauptzeitplan beginnt jetzt visuell bei KW23 (left:0). Bereits
// gespeicherte (gedraggte) Balken haben bar_left noch im alten KW19-System
// und müssen einmalig um 168 px (= 4 Wochen) nach links gezogen werden,
// sonst springen sie 4 Wochen zu weit rechts. Läuft genau EINMAL: wer zuerst
// pollt (egal welche Rolle), triggert es serverseitig BEVOR overrides
// ausgeliefert werden → kein Fenster mit falsch positionierten Balken.
// Claim + Shift + Flag liegen in EINER Transaktion → kein Doppel-Shift bei
// gleichzeitigen Requests, kein hängender Zwischenzustand bei Abbruch.
if ($method === 'GET' && ($_GET['migrate'] ?? '') === 'status') {
    if ($role !== ROLE_ADMIN) json_error('Nur Admin', 403);
    $flag = $db->query("SELECT v FROM kv_state WHERE k = 'origin_kw23_migrated'")->fetchColumn();
    json_response(['origin_kw23_migrated' => ($flag === false ? null : $flag)]);
}
$kw23Done = $db->query("SELECT v FROM kv_state WHERE k = 'origin_kw23_migrated'")->fetchColumn();
if ($kw23Done === false) {
    try {
        $db->beginTransaction();
        // Atomar beanspruchen: bei gleichzeitigem Request verliert der zweite
        // (INSERT IGNORE → rowCount 0) und überspringt den Shift.
        $claim = $db->prepare("INSERT IGNORE INTO kv_state (k, v, updated_by) VALUES ('origin_kw23_migrated', 'running', :uid)");
        $claim->execute([':uid' => (int)$u['id']]);
        if ($claim->rowCount() === 1) {
            $SHIFT = 168;
            $ovFixed = 0; $ciFixed = 0;
            // overrides: field='bar_left' → max(0, value − 168)
            $rows = $db->query("SELECT id, value FROM overrides WHERE field = 'bar_left'")->fetchAll();
            $upd  = $db->prepare('UPDATE overrides SET value = :v WHERE id = :id');
            foreach ($rows as $r) {
                if (!is_numeric($r['value'])) continue;
                $nv = max(0, (int)round((float)$r['value']) - $SHIFT);
                $upd->execute([':v' => (string)$nv, ':id' => $r['id']]);
                $ovFixed++;
            }
            // custom_items.data (JSON): bar_left → max(0, −168); bei Clamp bar_width um Überhang kürzen
            $rows = $db->query("SELECT id, data FROM custom_items WHERE data LIKE '%bar_left%'")->fetchAll();
            $upd  = $db->prepare('UPDATE custom_items SET data = :d WHERE id = :id');
            foreach ($rows as $r) {
                $d = json_decode($r['data'] ?? '{}', true);
                if (!is_array($d) || !array_key_exists('bar_left', $d) || !is_numeric($d['bar_left'])) continue;
                $nl = (int)round((float)$d['bar_left']) - $SHIFT;
                if ($nl < 0) {
                    if (isset($d['bar_width']) && is_numeric($d['bar_width'])) {
                        $d['bar_width'] = max(0, (int)round((float)$d['bar_width']) + $nl);
                    }
                    $nl = 0;
                }
                $d['bar_left'] = $nl;
                $upd->execute([':d' => json_encode($d, JSON_UNESCAPED_UNICODE), ':id' => $r['id']]);
                $ciFixed++;
            }
            $db->prepare("UPDATE kv_state SET v = :v, updated_by = :uid WHERE k = 'origin_kw23_migrated'")
               ->execute([':v' => json_encode(['shift' => $SHIFT, 'overrides' => $ovFixed, 'custom' => $ciFixed], JSON_UNESCAPED_UNICODE), ':uid' => (int)$u['id']]);
            $db->commit();
            audit_log((int)$u['id'], 'sync.migrate_origin_kw23', 'maintenance', null, ['overrides' => $ovFixed, 'custom' => $ciFixed]);
        } else {
            // Jemand anderes migriert gerade → nichts tun.
            $db->commit();
        }
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        // Claim ist mit der Transaktion zurückgerollt → nächster Request versucht es erneut.
        error_log('migrate_origin_kw23 failed: ' . $e->getMessage());
    }
}

// ── Einmal-Migration v90: Fester Tages-Maßstab — Balken-Koordinaten ×3 (42→126px/Woche = 18px/Tag).
// Läuft NACH der KW23-Migration, genau einmal, atomar, vor Auslieferung der Overrides. ──
$x3Done = $db->query("SELECT v FROM kv_state WHERE k = 'scale_day_x3_migrated'")->fetchColumn();
if ($x3Done === false) {
    try {
        $db->beginTransaction();
        $claim = $db->prepare("INSERT IGNORE INTO kv_state (k, v, updated_by) VALUES ('scale_day_x3_migrated', 'running', :uid)");
        $claim->execute([':uid' => (int)$u['id']]);
        if ($claim->rowCount() === 1) {
            $ovFixed = 0; $ciFixed = 0;
            // overrides: bar_left + bar_width ×3
            $rows = $db->query("SELECT id, value FROM overrides WHERE field IN ('bar_left','bar_width')")->fetchAll();
            $upd  = $db->prepare('UPDATE overrides SET value = :v WHERE id = :id');
            foreach ($rows as $r) {
                if (!is_numeric($r['value'])) continue;
                $upd->execute([':v' => (string)((int)round((float)$r['value']) * 3), ':id' => $r['id']]);
                $ovFixed++;
            }
            // custom_items.data (JSON): bar_left + bar_width ×3
            $rows = $db->query("SELECT id, data FROM custom_items WHERE data LIKE '%bar_left%' OR data LIKE '%bar_width%'")->fetchAll();
            $upd  = $db->prepare('UPDATE custom_items SET data = :d WHERE id = :id');
            foreach ($rows as $r) {
                $d = json_decode($r['data'] ?? '{}', true);
                if (!is_array($d)) continue;
                $ch = false;
                if (isset($d['bar_left'])  && is_numeric($d['bar_left']))  { $d['bar_left']  = (int)round((float)$d['bar_left'])  * 3; $ch = true; }
                if (isset($d['bar_width']) && is_numeric($d['bar_width'])) { $d['bar_width'] = (int)round((float)$d['bar_width']) * 3; $ch = true; }
                if (!$ch) continue;
                $upd->execute([':d' => json_encode($d, JSON_UNESCAPED_UNICODE), ':id' => $r['id']]);
                $ciFixed++;
            }
            $db->prepare("UPDATE kv_state SET v = :v, updated_by = :uid WHERE k = 'scale_day_x3_migrated'")
               ->execute([':v' => json_encode(['factor' => 3, 'overrides' => $ovFixed, 'custom' => $ciFixed], JSON_UNESCAPED_UNICODE), ':uid' => (int)$u['id']]);
            $db->commit();
            audit_log((int)$u['id'], 'sync.migrate_scale_day_x3', 'maintenance', null, ['overrides' => $ovFixed, 'custom' => $ciFixed]);
        } else {
            $db->commit();
        }
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('migrate_scale_day_x3 failed: ' . $e->getMessage());
    }
}

// ── GET ?cleanup=glyphs : Einmal-Bereinigung per URL (nur Admin) ──────
if ($method === 'GET' && ($_GET['cleanup'] ?? '') === 'glyphs') {
    if ($role !== ROLE_ADMIN) json_error('Nur Admin', 403);
    $clock = "\u{1F550}"; $cross = "\u{2715}";
    $strip = function (?string $s) use ($clock, $cross) {
        if ($s === null) return null;
        return trim(preg_replace('/\s+/u', ' ', str_replace([$clock, $cross], '', $s)));
    };
    $ovFixed = 0; $ciFixed = 0;
    $rows = $db->query("SELECT id, value FROM overrides WHERE value LIKE '%{$clock}%' OR value LIKE '%{$cross}%'")->fetchAll();
    $upd = $db->prepare('UPDATE overrides SET value = :v WHERE id = :id');
    foreach ($rows as $r) { $upd->execute([':v' => $strip($r['value']), ':id' => $r['id']]); $ovFixed++; }
    $rows = $db->query("SELECT id, data FROM custom_items WHERE data LIKE '%{$clock}%' OR data LIKE '%{$cross}%'")->fetchAll();
    $upd = $db->prepare('UPDATE custom_items SET data = :d WHERE id = :id');
    foreach ($rows as $r) {
        $d = json_decode($r['data'] ?? '{}', true) ?: [];
        foreach ($d as $k => $v) { if (is_string($v)) $d[$k] = $strip($v); }
        $upd->execute([':d' => json_encode($d, JSON_UNESCAPED_UNICODE), ':id' => $r['id']]); $ciFixed++;
    }
    audit_log((int)$u['id'], 'sync.cleanup_glyphs', 'maintenance', null, ['overrides'=>$ovFixed, 'custom'=>$ciFixed]);
    json_response(['ok' => true, 'overrides_fixed' => $ovFixed, 'custom_fixed' => $ciFixed]);
}

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
        $kvStmt = $db->prepare('SELECT k, v FROM kv_state WHERE updated_at > :since ORDER BY updated_at');
        $kvStmt->execute([':since' => $since]);
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
        $kvStmt = $db->query('SELECT k, v FROM kv_state ORDER BY updated_at');
    }

    $custom = [];
    foreach ($ciStmt->fetchAll() as $row) {
        $row['data'] = json_decode($row['data'] ?? '{}', true);
        $row['deleted'] = (int)$row['deleted'];
        $custom[] = $row;
    }

    json_response([
        'ok'          => true,
        'server_time' => $serverTime,
        'overrides'   => $ovStmt->fetchAll(),
        'custom'      => $custom,
        'kv'          => $kvStmt->fetchAll(),
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
        json_response(['ok' => true, 'server_time' => $serverTime]);
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
        json_response(['ok' => true, 'server_time' => $serverTime]);
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
        json_response(['ok' => true, 'server_time' => $serverTime]);
    }

    if ($op === 'kv_set') {
        // Generischer Key-Value-Sync (Neben-Tabs). Viewer/Worker dürfen nicht schreiben.
        if (!in_array($role, [ROLE_ADMIN, ROLE_ARCHITEKT], true)) json_error('Keine Berechtigung', 403);
        $k = str_clip((string)($b['key'] ?? ''), 160);
        if ($k === '') json_error('key fehlt', 400);
        $v = $b['value'] ?? null;
        $stmt = $db->prepare(
            'INSERT INTO kv_state (k, v, updated_by) VALUES (:k, :v, :uid)
             ON DUPLICATE KEY UPDATE v = VALUES(v), updated_by = VALUES(updated_by)'
        );
        $stmt->execute([':k' => $k, ':v' => $v !== null ? (string)$v : null, ':uid' => (int)$u['id']]);
        json_response(['ok' => true, 'server_time' => $serverTime]);
    }

    if ($op === 'cleanup_glyphs') {
        // Einmal-Bereinigung: entfernt verirrte Button-Symbole (🕐 / ✕) aus gespeicherten Werten
        if ($role !== ROLE_ADMIN) json_error('Nur Admin', 403);
        $clock = "\u{1F550}"; // 🕐
        $cross = "\u{2715}";  // ✕
        $strip = function (?string $s) use ($clock, $cross) {
            if ($s === null) return null;
            $s = str_replace([$clock, $cross], '', $s);
            return trim(preg_replace('/\s+/u', ' ', $s));
        };

        $ovFixed = 0; $ciFixed = 0;

        // overrides.value
        $rows = $db->query("SELECT id, value FROM overrides WHERE value LIKE '%{$clock}%' OR value LIKE '%{$cross}%'")->fetchAll();
        $upd = $db->prepare('UPDATE overrides SET value = :v WHERE id = :id');
        foreach ($rows as $r) {
            $upd->execute([':v' => $strip($r['value']), ':id' => $r['id']]);
            $ovFixed++;
        }

        // custom_items.data (JSON) — pro String-Feld säubern
        $rows = $db->query("SELECT id, data FROM custom_items WHERE data LIKE '%{$clock}%' OR data LIKE '%{$cross}%'")->fetchAll();
        $upd = $db->prepare('UPDATE custom_items SET data = :d WHERE id = :id');
        foreach ($rows as $r) {
            $d = json_decode($r['data'] ?? '{}', true) ?: [];
            foreach ($d as $k => $v) { if (is_string($v)) $d[$k] = $strip($v); }
            $upd->execute([':d' => json_encode($d, JSON_UNESCAPED_UNICODE), ':id' => $r['id']]);
            $ciFixed++;
        }

        audit_log((int)$u['id'], 'sync.cleanup_glyphs', 'maintenance', null, ['overrides'=>$ovFixed, 'custom'=>$ciFixed]);
        json_response(['ok' => true, 'overrides_fixed' => $ovFixed, 'custom_fixed' => $ciFixed, 'server_time' => $serverTime]);
    }

    if ($op === 'custom_delete') {
        if (!in_array($role, [ROLE_ADMIN, ROLE_ARCHITEKT], true)) json_error('Keine Berechtigung', 403);
        $clientId = str_clip((string)($b['client_id'] ?? ''), 120);
        $db->prepare('UPDATE custom_items SET deleted = 1 WHERE client_id = :c')
           ->execute([':c' => $clientId]);
        audit_log((int)$u['id'], 'sync.custom_delete', 'task', $clientId);
        json_response(['ok' => true, 'server_time' => $serverTime]);
    }

    json_error('Unbekannte Operation', 400);
}

json_error('Methode nicht erlaubt', 405);

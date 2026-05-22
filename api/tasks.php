<?php
/**
 * /api/tasks.php — CRUD für Hauptzeitplan-Aufgaben
 *
 *   GET    ?id=…       Einzeltask
 *   POST   {task...}   anlegen (architekt+)
 *   PATCH  {id, …}     ändern (worker darf nur status/progress/notiz; architekt+ alles)
 *   DELETE {id}        löschen (architekt+)
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$u = require_user();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    require_role($u, 'tasks.read');
    $id = (string)($_GET['id'] ?? '');
    if ($id === '') json_error('id fehlt', 400);
    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $t = $stmt->fetch();
    if (!$t) json_error('Task nicht gefunden', 404);
    json_response($t);
}

if ($method === 'POST') {
    // Anlegen — nur architekt + admin
    if (!can($u['role'], ROLE_ADMIN === $u['role'] ? 'tasks.create' : 'tasks.create')
        && !in_array($u['role'], [ROLE_ADMIN, ROLE_ARCHITEKT], true)) {
        json_error('Keine Berechtigung', 403);
    }
    $b = read_json_body();
    if (empty($b['id']) || empty($b['name'])) json_error('id und name benötigt', 400);

    $stmt = db()->prepare(
        'INSERT INTO tasks (id, section_id, unit_id, name, gewerk, firma, status, progress,
                            bar_left, bar_width, kw_start, kw_end, notiz, sort_order, custom)
         VALUES (:id, :sec, :unit, :name, :gewerk, :firma, :status, :prog,
                 :bl, :bw, :ks, :ke, :notiz, :sort, 1)'
    );
    $stmt->execute([
        ':id'     => str_clip((string)$b['id'], 40),
        ':sec'    => $b['section_id'] ?? null,
        ':unit'   => $b['unit_id'] ?? null,
        ':name'   => str_clip((string)$b['name'], 255),
        ':gewerk' => $b['gewerk'] ?? null,
        ':firma'  => $b['firma'] ?? null,
        ':status' => $b['status'] ?? 'geplant',
        ':prog'   => (int)($b['progress'] ?? 0),
        ':bl'     => (int)($b['bar_left'] ?? 0),
        ':bw'     => (int)($b['bar_width'] ?? 0),
        ':ks'     => $b['kw_start'] ?? null,
        ':ke'     => $b['kw_end'] ?? null,
        ':notiz'  => $b['notiz'] ?? null,
        ':sort'   => (int)($b['sort_order'] ?? 0),
    ]);
    audit_log((int)$u['id'], 'task.create', 'task', (string)$b['id'], ['new' => $b]);
    json_response(['ok' => true, 'id' => $b['id']]);
}

if ($method === 'PATCH') {
    $b = read_json_body();
    if (empty($b['id'])) json_error('id fehlt', 400);
    $id = (string) $b['id'];

    // Was darf dieser User updaten?
    if (in_array($u['role'], [ROLE_ADMIN, ROLE_ARCHITEKT], true)) {
        $allowed = ['section_id','unit_id','name','gewerk','firma','status',
                    'progress','bar_left','bar_width','kw_start','kw_end','notiz','sort_order'];
    } elseif ($u['role'] === ROLE_WORKER) {
        require_role($u, 'tasks.update_status');
        $allowed = ['status', 'progress', 'notiz'];
    } else {
        json_error('Keine Berechtigung', 403);
    }

    // Alten Wert für Audit holen
    $old = db()->prepare('SELECT * FROM tasks WHERE id = :id');
    $old->execute([':id' => $id]);
    $oldRow = $old->fetch();
    if (!$oldRow) json_error('Task nicht gefunden', 404);

    $sets = []; $params = [':id' => $id];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $b)) {
            $sets[] = "$k = :$k";
            $params[":$k"] = $b[$k];
        }
    }
    if (!$sets) json_error('Nichts zu ändern', 400);

    $stmt = db()->prepare('UPDATE tasks SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($params);

    audit_log((int)$u['id'], 'task.update', 'task', $id, [
        'changed' => array_intersect_key($b, array_flip($allowed)),
    ]);
    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    if (!in_array($u['role'], [ROLE_ADMIN, ROLE_ARCHITEKT], true)) {
        json_error('Keine Berechtigung', 403);
    }
    $b = read_json_body();
    $id = (string)($b['id'] ?? '');
    if ($id === '') json_error('id fehlt', 400);
    $stmt = db()->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $id]);
    audit_log((int)$u['id'], 'task.delete', 'task', $id);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt', 405);

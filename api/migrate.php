<?php
/**
 * POST /api/migrate.php
 *
 *   Einmalige Übernahme der localStorage-Daten in MySQL.
 *   Aufruf vom sync.js, nur durch Admin.
 *
 *   Body (vom Client gesammelt):
 *   {
 *     "task_statuses":  {"T 4.01": "fertig", ...},
 *     "task_names":     {"T 4.01": "Neuer Name", ...},
 *     "task_firma":     {"T 4.01": "Kamil", ...},
 *     "task_gewerk":    {"T 4.01": "Trockenbau", ...},
 *     "bar_positions":  {"T 4.01": {"left":168,"width":84}, ...},
 *     "task_notiz":     {"T 4.01": "manuell ...", ...},
 *     "employees":      [...],
 *     "orders":         [...],
 *     "custom_tasks":   [...]
 *   }
 *
 *   Setzt nach Erfolg: settings.migrated_from_localstorage = '1'
 */
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$u = require_user();
if ($u['role'] !== ROLE_ADMIN) json_error('Nur Admin', 403);

$b = read_json_body();
$db = db();
$db->beginTransaction();

$counts = ['tasks_updated' => 0, 'tasks_created' => 0,
           'mitarbeiter' => 0, 'orders' => 0];

try {

// 1) Task-Status, -Name, -Firma, -Gewerk, -Notiz, -Bar-Positionen mergen
$tids = array_unique(array_merge(
    array_keys($b['task_statuses'] ?? []),
    array_keys($b['task_names'] ?? []),
    array_keys($b['task_firma'] ?? []),
    array_keys($b['task_gewerk'] ?? []),
    array_keys($b['bar_positions'] ?? []),
    array_keys($b['task_notiz'] ?? []),
));

foreach ($tids as $tid) {
    $tid = (string) $tid;
    // Existiert die Task in der DB?
    $check = $db->prepare('SELECT id FROM tasks WHERE id = :id');
    $check->execute([':id' => $tid]);
    if ($check->fetch()) {
        // UPDATE
        $sets = [];
        $params = [':id' => $tid];
        if (isset($b['task_statuses'][$tid])) {
            $st = mapStatus($b['task_statuses'][$tid]);
            if ($st) { $sets[] = 'status = :s'; $params[':s'] = $st; }
        }
        if (isset($b['task_names'][$tid]))   { $sets[] = 'name = :n';   $params[':n'] = (string)$b['task_names'][$tid]; }
        if (isset($b['task_firma'][$tid]))   { $sets[] = 'firma = :f';  $params[':f'] = (string)$b['task_firma'][$tid]; }
        if (isset($b['task_gewerk'][$tid]))  { $sets[] = 'gewerk = :g'; $params[':g'] = (string)$b['task_gewerk'][$tid]; }
        if (isset($b['task_notiz'][$tid]))   { $sets[] = 'notiz = :nz'; $params[':nz'] = (string)$b['task_notiz'][$tid]; }
        if (isset($b['bar_positions'][$tid])) {
            $bp = $b['bar_positions'][$tid];
            if (isset($bp['left']))  { $sets[] = 'bar_left = :bl';  $params[':bl'] = (int)$bp['left']; }
            if (isset($bp['width'])) { $sets[] = 'bar_width = :bw'; $params[':bw'] = (int)$bp['width']; }
        }
        if ($sets) {
            $db->prepare('UPDATE tasks SET ' . implode(', ', $sets) . ' WHERE id = :id')
               ->execute($params);
            $counts['tasks_updated']++;
        }
    } else {
        // INSERT als custom Task
        $st = isset($b['task_statuses'][$tid]) ? mapStatus($b['task_statuses'][$tid]) : 'geplant';
        $bp = $b['bar_positions'][$tid] ?? [];
        $db->prepare(
            'INSERT INTO tasks (id, name, status, gewerk, firma, notiz, bar_left, bar_width, custom)
             VALUES (:id, :n, :s, :g, :f, :nz, :bl, :bw, 1)'
        )->execute([
            ':id' => $tid,
            ':n'  => $b['task_names'][$tid] ?? $tid,
            ':s'  => $st ?: 'geplant',
            ':g'  => $b['task_gewerk'][$tid] ?? null,
            ':f'  => $b['task_firma'][$tid] ?? null,
            ':nz' => $b['task_notiz'][$tid] ?? null,
            ':bl' => (int)($bp['left'] ?? 0),
            ':bw' => (int)($bp['width'] ?? 0),
        ]);
        $counts['tasks_created']++;
    }
}

// 2) Mitarbeiter
if (!empty($b['employees']) && is_array($b['employees'])) {
    // Bestehende löschen (idempotent)
    $db->exec('DELETE FROM mitarbeiter_gewerke');
    $db->exec('DELETE FROM urlaub');
    $db->exec('DELETE FROM mitarbeiter');

    $insM = $db->prepare(
        'INSERT INTO mitarbeiter (name, stunden_wo, ab_kw, bis_kw, aktiv, sort_order)
         VALUES (:n, :h, :ab, :bis, :ak, :so)'
    );
    $insG = $db->prepare(
        'INSERT IGNORE INTO mitarbeiter_gewerke (mitarbeiter_id, gewerk) VALUES (:m, :g)'
    );
    $insU = $db->prepare(
        'INSERT IGNORE INTO urlaub (mitarbeiter_id, kw, year, bemerkung) VALUES (:m, :k, :y, :b)'
    );

    $i = 0;
    foreach ($b['employees'] as $emp) {
        if (!is_array($emp) || empty($emp['name'])) continue;
        $insM->execute([
            ':n'   => substr((string)$emp['name'], 0, 120),
            ':h'   => (int)($emp['stundenWo'] ?? $emp['hours'] ?? 40),
            ':ab'  => $emp['abKw']  ?? $emp['ab']  ?? null,
            ':bis' => $emp['bisKw'] ?? $emp['bis'] ?? null,
            ':ak'  => isset($emp['aktiv']) ? (int)$emp['aktiv'] : 1,
            ':so'  => $i++,
        ]);
        $mid = (int) $db->lastInsertId();
        foreach (($emp['gewerke'] ?? []) as $g) {
            if (!$g) continue;
            $insG->execute([':m' => $mid, ':g' => substr((string)$g, 0, 60)]);
        }
        foreach (($emp['urlaub'] ?? []) as $url) {
            if (!isset($url['kw'])) continue;
            $insU->execute([
                ':m' => $mid,
                ':k' => (int)$url['kw'],
                ':y' => (int)($url['year'] ?? 2026),
                ':b' => $url['bemerkung'] ?? null,
            ]);
        }
        $counts['mitarbeiter']++;
    }
}

// 3) Bestellungen
if (!empty($b['orders']) && is_array($b['orders'])) {
    $db->exec('DELETE FROM bestellungen');
    $ins = $db->prepare(
        'INSERT INTO bestellungen (bezeichnung, lieferant, status, bestelldatum, lieferdatum, task_id, betrag_netto, notiz)
         VALUES (:bz, :lf, :st, :bd, :ld, :tid, :bn, :nz)'
    );
    foreach ($b['orders'] as $o) {
        if (!is_array($o)) continue;
        $ins->execute([
            ':bz'  => substr((string)($o['bezeichnung'] ?? $o['name'] ?? '—'), 0, 190),
            ':lf'  => $o['lieferant'] ?? null,
            ':st'  => mapOrderStatus($o['status'] ?? 'offen'),
            ':bd'  => $o['bestelldatum'] ?? null,
            ':ld'  => $o['lieferdatum'] ?? null,
            ':tid' => $o['task_id'] ?? null,
            ':bn'  => isset($o['betrag']) ? (float)$o['betrag'] : null,
            ':nz'  => $o['notiz'] ?? null,
        ]);
        $counts['orders']++;
    }
}

// Custom Tasks (z.B. via "+" Button angelegt)
if (!empty($b['custom_tasks']) && is_array($b['custom_tasks'])) {
    foreach ($b['custom_tasks'] as $ct) {
        if (!is_array($ct) || empty($ct['id'])) continue;
        $check = $db->prepare('SELECT id FROM tasks WHERE id = :id');
        $check->execute([':id' => $ct['id']]);
        if ($check->fetch()) continue;
        $db->prepare(
            'INSERT INTO tasks (id, name, status, gewerk, firma, custom)
             VALUES (:id, :n, :s, :g, :f, 1)'
        )->execute([
            ':id' => $ct['id'],
            ':n'  => $ct['name'] ?? $ct['id'],
            ':s'  => mapStatus($ct['status'] ?? 'geplant') ?: 'geplant',
            ':g'  => $ct['gewerk'] ?? null,
            ':f'  => $ct['firma'] ?? null,
        ]);
        $counts['tasks_created']++;
    }
}

setting_set('migrated_from_localstorage', '1');
setting_set('migrated_at', date('Y-m-d H:i:s'));

audit_log((int)$u['id'], 'migrate.localstorage', 'system', null, $counts);
$db->commit();

json_response([
    'ok'      => true,
    'counts'  => $counts,
    'summary' => "{$counts['tasks_updated']} Aufgaben aktualisiert, "
               . "{$counts['tasks_created']} neu, "
               . "{$counts['mitarbeiter']} Mitarbeiter, "
               . "{$counts['orders']} Bestellungen.",
]);

} catch (Throwable $t) {
    $db->rollBack();
    error_log('migrate failed: ' . $t->getMessage());
    json_error('Migration fehlgeschlagen: ' . $t->getMessage(), 500);
}

// ---------------------------------------------------------------------
function mapStatus(?string $s): ?string {
    if (!$s) return null;
    $s = trim(mb_strtolower($s));
    // bestehende Klassen → DB-Enum
    return match (true) {
        in_array($s, ['fertig','done','abgeschlossen','erledigt'], true) => 'fertig',
        in_array($s, ['wip','laufend','arbeit','in arbeit'], true)        => 'laufend',
        in_array($s, ['geplant','planned'], true)                          => 'geplant',
        in_array($s, ['verzögert','verzoegert','delayed'], true)           => 'verzögert',
        in_array($s, ['priorität','prio','priority'], true)                => 'priorität',
        // Prozent-Werte wie "50%" → laufend
        preg_match('/^\d+%?$/', $s) === 1                                   => 'laufend',
        default => null,
    };
}
function mapOrderStatus(?string $s): string {
    $s = trim(mb_strtolower((string)$s));
    return match (true) {
        in_array($s, ['bestellt','order','ordered'], true)     => 'bestellt',
        in_array($s, ['geliefert','delivered','da'], true)     => 'geliefert',
        in_array($s, ['storniert','cancelled','canceled'], true)=> 'storniert',
        default => 'offen',
    };
}

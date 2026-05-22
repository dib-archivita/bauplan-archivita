<?php
/**
 * GET /api/state.php
 *   Liefert kompletten Zustand: tasks, einheiten, mitarbeiter, urlaub,
 *   bestellungen, sections, settings. Für initialen Dashboard-Load.
 *
 *   Optional ?since=<timestamp>  — liefert nur, was nach diesem Zeitpunkt
 *   geändert wurde (für Polling alle 5 Sek).
 */
declare(strict_types=1);
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$u = require_user();
require_role($u, 'state.read');

$since = $_GET['since'] ?? null;
$where = '';
$params = [];
if ($since && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
    $where = ' WHERE updated_at > :since';
    $params[':since'] = $since;
}

function fetchAll(PDO $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$db = db();

$tasks       = fetchAll($db, "SELECT * FROM tasks $where ORDER BY sort_order", $params);
$einheiten   = fetchAll($db, "SELECT * FROM einheiten $where ORDER BY sort_order", $params);
$sections    = fetchAll($db, "SELECT * FROM task_sections $where ORDER BY sort_order", $params);
$mitarbeiter = fetchAll($db, "SELECT * FROM mitarbeiter $where ORDER BY sort_order", $params);
$gewerke     = fetchAll($db, 'SELECT * FROM mitarbeiter_gewerke');
$urlaub      = fetchAll($db, 'SELECT * FROM urlaub');
$bestellungen= fetchAll($db, "SELECT * FROM bestellungen $where ORDER BY id DESC", $params);

// Settings als key→value
$settings = [];
foreach (fetchAll($db, 'SELECT key_str, value_str FROM settings') as $r) {
    $settings[$r['key_str']] = $r['value_str'];
}

// Audit-Retention-Warnung: gibt es Einträge älter als Retention?
$audit_warning = null;
$retDays = (int) setting_get('audit_retention_days', (string) AUDIT_RETENTION_DEFAULT_DAYS);
if ($retDays > 0 && $u['role'] === ROLE_ADMIN) {
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS n FROM audit_log
         WHERE created_at < NOW() - INTERVAL :d DAY'
    );
    $stmt->execute([':d' => $retDays]);
    $oldCount = (int) $stmt->fetchColumn();
    if ($oldCount > 0) {
        $ignored = setting_get('audit_warning_dismissed_until');
        if (!$ignored || $ignored < date('Y-m-d H:i:s')) {
            $audit_warning = [
                'old_entries' => $oldCount,
                'retention_days' => $retDays,
            ];
        }
    }
}

json_response([
    'ok'           => true,
    'server_time'  => date('Y-m-d H:i:s'),
    'user'         => ['id'=>(int)$u['id'],'email'=>$u['email'],'name'=>$u['name'],'role'=>$u['role']],
    'tasks'        => $tasks,
    'einheiten'    => $einheiten,
    'sections'     => $sections,
    'mitarbeiter'  => $mitarbeiter,
    'gewerke'      => $gewerke,
    'urlaub'       => $urlaub,
    'bestellungen' => $bestellungen,
    'settings'     => $settings,
    'audit_warning'=> $audit_warning,
]);

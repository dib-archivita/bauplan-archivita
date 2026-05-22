<?php
/**
 * /api/audit.php — Audit-Log lesen, Retention verwalten
 *
 *   GET                        Audit-Einträge (nur Admin), letzte 200
 *   GET ?action=warning_dismiss   Banner für 14 Tage ausblenden
 *   GET ?action=export            JSON-Download aller alten Einträge
 *   POST {retention_days: N}      Retention setzen (0 = unbegrenzt)
 *   POST {action: "delete_old"}   Sofort löschen, was außerhalb Retention liegt
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

$u = require_user();
if ($u['role'] !== ROLE_ADMIN) json_error('Nur Admin', 403);

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'warning_dismiss') {
        setting_set('audit_warning_dismissed_until',
            date('Y-m-d H:i:s', time() + 14 * 86400));
        json_response(['ok' => true]);
    }

    if ($action === 'export') {
        $retDays = (int) setting_get('audit_retention_days', (string) AUDIT_RETENTION_DEFAULT_DAYS);
        $stmt = $db->prepare(
            'SELECT * FROM audit_log
             WHERE created_at < NOW() - INTERVAL :d DAY
             ORDER BY id'
        );
        $stmt->execute([':d' => $retDays]);
        $rows = $stmt->fetchAll();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_export_' . date('Y-m-d') . '.json"');
        echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Liste (paginiert)
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(500, max(10, (int)($_GET['size'] ?? 200)));
    $offset = ($page - 1) * $size;
    $stmt = $db->prepare(
        'SELECT a.*, u.email AS user_email, u.name AS user_name
         FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.id DESC LIMIT :lim OFFSET :off'
    );
    $stmt->bindValue(':lim', $size, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $total = (int) $db->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();

    json_response([
        'ok'    => true,
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page'  => $page,
        'size'  => $size,
        'retention_days' => (int) setting_get('audit_retention_days', (string) AUDIT_RETENTION_DEFAULT_DAYS),
    ]);
}

if ($method === 'POST') {
    $b = read_json_body();

    if (isset($b['retention_days'])) {
        $d = (int) $b['retention_days'];
        if ($d < 0 || $d > 3650) json_error('retention_days 0..3650', 400);
        setting_set('audit_retention_days', (string)$d);
        setting_set('audit_warning_dismissed_until', null);
        audit_log((int)$u['id'], 'audit.set_retention', 'settings', null, ['days' => $d]);
        json_response(['ok' => true]);
    }

    if (($b['action'] ?? '') === 'delete_old') {
        $retDays = (int) setting_get('audit_retention_days', (string) AUDIT_RETENTION_DEFAULT_DAYS);
        if ($retDays <= 0) json_error('Retention ist auf "unbegrenzt"', 400);
        $stmt = $db->prepare(
            'DELETE FROM audit_log WHERE created_at < NOW() - INTERVAL :d DAY'
        );
        $stmt->execute([':d' => $retDays]);
        $n = $stmt->rowCount();
        audit_log((int)$u['id'], 'audit.purge', 'audit_log', null, ['deleted' => $n]);
        json_response(['ok' => true, 'deleted' => $n]);
    }

    json_error('Aktion unbekannt', 400);
}

json_error('Methode nicht erlaubt', 405);

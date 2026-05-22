<?php
/**
 * Allgemeine Helfer: JSON-Responses, Rollen-Check, Input-Validation, Audit
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ---------- JSON-Antwort ----------------------------------------------
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $msg, int $status = 400, array $extra = []): void {
    json_response(array_merge(['error' => $msg], $extra), $status);
}

function read_json_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) json_error('Ungültiges JSON', 400);
    return $data;
}

// ---------- Rollen ----------------------------------------------------
const ROLE_ADMIN     = 'admin';
const ROLE_ARCHITEKT = 'architekt';
const ROLE_WORKER    = 'worker';
const ROLE_VIEWER    = 'viewer';

const ROLE_RANK = [
    ROLE_VIEWER    => 1,
    ROLE_WORKER    => 2,
    ROLE_ARCHITEKT => 3,
    ROLE_ADMIN     => 4,
];

function role_at_least(string $userRole, string $minRole): bool {
    return (ROLE_RANK[$userRole] ?? 0) >= (ROLE_RANK[$minRole] ?? 99);
}

/**
 * Welche Aktionen darf welche Rolle?
 *   admin     – alles inkl. Nutzerverwaltung
 *   architekt – alles AUSSER Nutzerverwaltung (Variante B vom User)
 *   worker    – nur Aufgaben-Status + Fortschritt + Notiz ändern, KEINE Anlage/Löschung
 *   viewer    – nur lesen
 */
function can(string $userRole, string $action): bool {
    return match (true) {
        // Admin darf alles
        $userRole === ROLE_ADMIN => true,

        // Architekt: alles außer Nutzerverwaltung + Rollen ändern
        $userRole === ROLE_ARCHITEKT => !in_array($action, [
            'users.create', 'users.update', 'users.delete',
            'users.read',  // wer's noch sehen darf, eigene Entscheidung
        ], true),

        // Worker: nur Status / Progress / Notiz ändern, lesen erlaubt
        $userRole === ROLE_WORKER => in_array($action, [
            'state.read', 'tasks.read', 'tasks.update_status',
            'audit.read_own',
        ], true),

        // Viewer: nur lesen
        $userRole === ROLE_VIEWER => in_array($action, [
            'state.read', 'tasks.read',
        ], true),

        default => false,
    };
}

function require_role(array $user, string $action): void {
    if (!can($user['role'], $action)) {
        json_error('Keine Berechtigung für diese Aktion', 403, ['action' => $action]);
    }
}

// ---------- Validation ------------------------------------------------
function valid_email(string $e): bool {
    return (bool) filter_var($e, FILTER_VALIDATE_EMAIL);
}

function str_clip(string $s, int $max): string {
    return mb_substr(trim($s), 0, $max, 'UTF-8');
}

// ---------- Audit-Log -------------------------------------------------
function audit_log(?int $userId, string $action, string $entity, ?string $entityId = null, ?array $payload = null): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, payload, ip)
             VALUES (:uid, :ac, :en, :eid, :pl, :ip)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':ac'  => $action,
            ':en'  => $entity,
            ':eid' => $entityId,
            ':pl'  => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $t) {
        // Audit darf nie den Request blocken
        error_log('audit_log failed: ' . $t->getMessage());
    }
}

// ---------- Settings (key-value) --------------------------------------
function setting_get(string $key, ?string $default = null): ?string {
    $stmt = db()->prepare('SELECT value_str FROM settings WHERE key_str = :k');
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch();
    return $row ? $row['value_str'] : $default;
}

function setting_set(string $key, ?string $value): void {
    $stmt = db()->prepare(
        'INSERT INTO settings (key_str, value_str) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE value_str = VALUES(value_str)'
    );
    $stmt->execute([':k' => $key, ':v' => $value]);
}

<?php
/**
 * Authentifizierung: Magic-Link, Session, Rollen-Check
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const SESSION_COOKIE = 'bauplan_sess';

// ---------------------------------------------------------------------
//  TOKEN-GENERIERUNG
// ---------------------------------------------------------------------
function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));   // 64 Zeichen
}
function hash_token(string $token): string {
    return hash('sha256', $token . APP_SECRET);
}

// ---------------------------------------------------------------------
//  RATE LIMIT (gegen Login-Mail-Spam)
// ---------------------------------------------------------------------
function rate_limit_check(string $key): bool {
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS n FROM rate_limit
         WHERE key_str = :k AND created_at > NOW() - INTERVAL :w MINUTE'
    );
    $stmt->execute([':k' => $key, ':w' => RATE_LIMIT_WINDOW_MIN]);
    return ((int)$stmt->fetchColumn()) < RATE_LIMIT_MAX;
}
function rate_limit_hit(string $key): void {
    $stmt = db()->prepare('INSERT INTO rate_limit (key_str) VALUES (:k)');
    $stmt->execute([':k' => $key]);
    // Alte Einträge gelegentlich aufräumen (1 % der Calls)
    if (random_int(0, 99) === 0) {
        db()->exec('DELETE FROM rate_limit WHERE created_at < NOW() - INTERVAL 1 DAY');
    }
}

// ---------------------------------------------------------------------
//  MAGIC LINK: erzeugen + Mail senden
// ---------------------------------------------------------------------
function magic_link_create(int $userId): string {
    $token = generate_token();
    $hash  = hash_token($token);
    $stmt = db()->prepare(
        'INSERT INTO magic_tokens (user_id, token_hash, expires_at, ip, user_agent)
         VALUES (:u, :h, NOW() + INTERVAL :m MINUTE, :ip, :ua)'
    );
    $stmt->execute([
        ':u'  => $userId,
        ':h'  => $hash,
        ':m'  => TOKEN_MINUTES,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
    return $token;
}

function magic_link_verify(string $token): ?int {
    $hash = hash_token($token);
    $stmt = db()->prepare(
        'SELECT id, user_id FROM magic_tokens
         WHERE token_hash = :h
           AND used_at IS NULL
           AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([':h' => $hash]);
    $row = $stmt->fetch();
    if (!$row) return null;

    // Single-Use: sofort verbrannt markieren
    $upd = db()->prepare('UPDATE magic_tokens SET used_at = NOW() WHERE id = :id');
    $upd->execute([':id' => $row['id']]);

    return (int) $row['user_id'];
}

// ---------------------------------------------------------------------
//  SESSION: anlegen + lesen + löschen
// ---------------------------------------------------------------------
function session_create(int $userId): string {
    $token = generate_token();
    $hash  = hash_token($token);
    $stmt = db()->prepare(
        'INSERT INTO sessions (id, user_id, expires_at, ip, user_agent)
         VALUES (:id, :u, NOW() + INTERVAL :d DAY, :ip, :ua)'
    );
    $stmt->execute([
        ':id' => $hash,
        ':u'  => $userId,
        ':d'  => SESSION_DAYS,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
    // Login-Timestamp am User updaten
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :u')
        ->execute([':u' => $userId]);

    setcookie(SESSION_COOKIE, $token, [
        'expires'  => time() + SESSION_DAYS * 86400,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function current_user(): ?array {
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($token === '') return null;
    $hash = hash_token($token);
    $stmt = db()->prepare(
        'SELECT u.id, u.email, u.name, u.role, u.active
         FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.id = :id
           AND s.expires_at > NOW()
           AND u.active = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $hash]);
    $u = $stmt->fetch();
    if (!$u) return null;

    // last_seen aktualisieren (max. 1×/Min)
    db()->prepare('UPDATE sessions SET last_seen_at = NOW()
                   WHERE id = :id AND last_seen_at < NOW() - INTERVAL 60 SECOND')
        ->execute([':id' => $hash]);
    return $u;
}

function require_user(): array {
    $u = current_user();
    if (!$u) json_error('Nicht eingeloggt', 401);
    return $u;
}

function session_destroy(): void {
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($token !== '') {
        $hash = hash_token($token);
        db()->prepare('DELETE FROM sessions WHERE id = :id')->execute([':id' => $hash]);
    }
    setcookie(SESSION_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

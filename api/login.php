<?php
/**
 * POST /api/login.php
 *   Body: { "email": "user@example.de" }
 *
 *   Erzeugt Magic-Link, schickt Mail. Antwortet IMMER mit 200 + "ok"
 *   (auch wenn Mail unbekannt — sonst kann man Email-Adressen enumerieren).
 */
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Methode nicht erlaubt', 405);
}

$body  = read_json_body();
$email = strtolower(trim((string)($body['email'] ?? '')));

if (!valid_email($email)) {
    json_error('Ungültige Email-Adresse', 400);
}

// Rate-Limit pro Email und pro IP
$ipKey = 'login_ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$emKey = 'login_email:' . $email;
if (!rate_limit_check($ipKey) || !rate_limit_check($emKey)) {
    json_error('Zu viele Login-Versuche. Bitte später erneut versuchen.', 429);
}
rate_limit_hit($ipKey);
rate_limit_hit($emKey);

// User suchen
$stmt = db()->prepare('SELECT id, email, name, active FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
$user = $stmt->fetch();

// Existiert + ist aktiv? → Magic-Link erzeugen + mailen
if ($user && (int)$user['active'] === 1) {
    $token = magic_link_create((int) $user['id']);
    $link  = APP_URL . '/api/verify.php?token=' . urlencode($token);
    $ok = send_magic_link_mail($user['email'], $user['name'], $link);

    audit_log((int)$user['id'], 'user.login_request', 'user', (string)$user['id'], [
        'mail_sent' => $ok,
    ]);
    // Bei DEBUG: Link in Response ausgeben für lokales Testen
    if (DEBUG_MODE && !$ok) {
        json_response(['ok' => true, 'debug_link' => $link]);
    }
}

// IMMER ok antworten — keine Enumeration
json_response(['ok' => true, 'msg' => 'Falls die Adresse registriert ist, wurde ein Login-Link verschickt.']);

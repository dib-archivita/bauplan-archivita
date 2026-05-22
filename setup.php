<?php
/**
 * setup.php — EINMALIG nach dem ersten Upload aufrufen.
 *
 *   1) Prüft DB-Verbindung
 *   2) Prüft, ob Schema importiert ist
 *   3) Legt den ersten Admin-User aus config.php an (FIRST_ADMIN_EMAIL/NAME)
 *   4) Schickt eine Test-Login-Mail
 *
 * NACH ERFOLGREICHEM SETUP:  Diese Datei UMBENENNEN oder LÖSCHEN
 * (sonst kann jeder, der die URL kennt, Admin-Accounts triggern).
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/mailer.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Bauzeitenplan Archivita — Setup ===\n\n";

// 1) DB-Verbindung
try {
    db()->query('SELECT 1');
    echo "[1/4] ✓ DB-Verbindung OK\n";
} catch (Throwable $t) {
    echo "[1/4] ✗ DB-Verbindung fehlgeschlagen: " . $t->getMessage() . "\n";
    exit;
}

// 2) Schema vorhanden?
try {
    $tables = db()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('users', $tables, true)) {
        echo "[2/4] ✗ Tabelle 'users' fehlt — schema.sql noch nicht importiert!\n";
        echo "      Öffne phpMyAdmin, wähle DB " . DB_NAME . ", → Importieren → schema.sql\n";
        exit;
    }
    echo "[2/4] ✓ Schema vorhanden (" . count($tables) . " Tabellen)\n";
} catch (Throwable $t) {
    echo "[2/4] ✗ Fehler: " . $t->getMessage() . "\n"; exit;
}

// 3) Admin anlegen (oder skip wenn existiert)
$email = strtolower(FIRST_ADMIN_EMAIL);
$stmt = db()->prepare('SELECT id, role FROM users WHERE email = :e');
$stmt->execute([':e' => $email]);
$existing = $stmt->fetch();
if ($existing) {
    if ($existing['role'] !== ROLE_ADMIN) {
        db()->prepare('UPDATE users SET role = :r, active = 1 WHERE id = :id')
            ->execute([':r' => ROLE_ADMIN, ':id' => $existing['id']]);
        echo "[3/4] ✓ Bestehender User $email auf Admin-Rolle gesetzt (id={$existing['id']})\n";
    } else {
        echo "[3/4] ✓ Admin-User $email existiert bereits (id={$existing['id']})\n";
    }
    $adminId = (int) $existing['id'];
} else {
    db()->prepare(
        'INSERT INTO users (email, name, role, active) VALUES (:e, :n, :r, 1)'
    )->execute([
        ':e' => $email,
        ':n' => FIRST_ADMIN_NAME,
        ':r' => ROLE_ADMIN,
    ]);
    $adminId = (int) db()->lastInsertId();
    echo "[3/4] ✓ Admin-User $email angelegt (id=$adminId)\n";
}

// 4) Test-Login-Mail schicken
$token = magic_link_create($adminId);
$link  = APP_URL . '/api/verify.php?token=' . urlencode($token);
$ok    = send_magic_link_mail($email, FIRST_ADMIN_NAME, $link);

if ($ok) {
    echo "[4/4] ✓ Login-Mail an $email verschickt\n";
} else {
    echo "[4/4] ✗ Mailversand fehlgeschlagen — prüfe SMTP-Daten in config.php\n";
    echo "       Notfall-Link (15 Min gültig):\n       $link\n";
}

echo "\n=== Setup abgeschlossen ===\n";
echo "WICHTIG: setup.php nun UMBENENNEN oder LÖSCHEN!\n";
echo "         z.B. zu setup_done_" . date('Ymd') . ".php\n";

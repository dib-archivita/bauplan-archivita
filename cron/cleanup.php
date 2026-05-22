<?php
/**
 * cron/cleanup.php — täglich per webgo-Cronjob aufrufen
 *
 * Aufgaben:
 *   1) Abgelaufene Sessions löschen
 *   2) Abgelaufene Magic-Tokens löschen
 *   3) Audit-Log: 7 Tage vor Ablauf der Retention → Mail-Warnung an alle Admins
 *      (Banner im UI erscheint automatisch, sobald state.php aufgerufen wird)
 *
 * Audit-Einträge selbst werden NICHT automatisch gelöscht — Admin muss
 * explizit zustimmen oder "unbegrenzt" setzen.
 *
 * Webgo-Cron-Setup:
 *   KIS → Cronjobs → täglich z.B. 04:00 → Befehl:
 *     /usr/bin/php /home/web111/htdocs/bauplan/cron/cleanup.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/mailer.php';

$db = db();
echo "[" . date('c') . "] Cleanup-Lauf gestartet\n";

// 1) Sessions
$n = $db->exec('DELETE FROM sessions WHERE expires_at < NOW()');
echo "  - Sessions gelöscht: $n\n";

// 2) Magic-Tokens (älter als 24h ODER benutzt)
$n = $db->exec(
    'DELETE FROM magic_tokens WHERE expires_at < NOW() - INTERVAL 1 DAY
                                 OR (used_at IS NOT NULL AND used_at < NOW() - INTERVAL 1 DAY)'
);
echo "  - Magic-Tokens gelöscht: $n\n";

// 3) Rate-Limit-Einträge älter als 1 Tag
$n = $db->exec('DELETE FROM rate_limit WHERE created_at < NOW() - INTERVAL 1 DAY');
echo "  - Rate-Limit-Einträge gelöscht: $n\n";

// 4) Audit-Warnung 7 Tage vor Ablauf — Mail an Admins
$retDays = (int) setting_get('audit_retention_days', (string) AUDIT_RETENTION_DEFAULT_DAYS);
if ($retDays > 0) {
    $warnDate = date('Y-m-d', time() + 7 * 86400);
    $key = 'audit_warning_mail_sent_' . $warnDate;
    if (!setting_get($key)) {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM audit_log WHERE created_at < NOW() - INTERVAL :d DAY'
        );
        $stmt->execute([':d' => $retDays - 7]);
        $upcoming = (int) $stmt->fetchColumn();

        if ($upcoming > 0) {
            $admins = $db->query("SELECT email, name FROM users WHERE role='admin' AND active=1")->fetchAll();
            foreach ($admins as $a) {
                send_mail(
                    $a['email'],
                    'Audit-Log: ' . $upcoming . ' Einträge werden in 7 Tagen gelöscht',
                    "Hallo {$a['name']},\n\nim Bauzeitenplan werden in 7 Tagen $upcoming Audit-Einträge gelöscht (älter als $retDays Tage).\n\nFalls du sie behalten willst, kannst du im Dashboard:\n - die Aufbewahrung verlängern\n - oder die alten Einträge als JSON exportieren\n\nLink: " . APP_URL . "/\n"
                );
            }
            setting_set($key, '1');
            echo "  - Audit-Warnung an " . count($admins) . " Admin(s) verschickt\n";
        }
    }
}

echo "[" . date('c') . "] Cleanup fertig.\n";

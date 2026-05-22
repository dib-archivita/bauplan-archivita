<?php
declare(strict_types=1);
/**
 * index.php — temporäre Startseite bis das echte Dashboard kommt.
 * Prüft Login, leitet sonst auf login.html.
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

$u = current_user();
if (!$u) {
    header('Location: login.html');
    exit;
}
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Bauzeitenplan Archivita</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg,#f5f6fa 0%,#e0e7ff 100%);
    min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #1e293b; }
  .card { background: #fff; border-radius: 18px; padding: 40px 36px; max-width: 560px; width: 100%;
    box-shadow: 0 10px 40px rgba(15,23,42,.08); border: 1px solid #e8e9ed; }
  .logo { font-size: 32px; margin-bottom: 8px; }
  h1 { font-size: 22px; font-weight: 800; letter-spacing: -.02em; margin-bottom: 6px; }
  .sub { color: #64748b; font-size: 13px; margin-bottom: 28px; font-weight: 500; }
  .ok { background: #d1fae5; color: #065f46; padding: 14px 18px; border-radius: 12px;
    font-size: 14px; font-weight: 600; margin-bottom: 20px; border: 1px solid #6ee7b7; }
  .info { background: #f8fafc; border-radius: 12px; padding: 16px 18px; font-size: 13px;
    line-height: 1.6; margin-bottom: 20px; }
  .info b { color: #1e293b; }
  .role { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px;
    font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    background: #fee2e2; color: #b91c1c; }
  .btn { display: inline-block; padding: 11px 20px; border-radius: 10px; text-decoration: none;
    font-weight: 700; font-size: 14px; margin-right: 8px; }
  .btn-primary { background: #2563eb; color: #fff; }
  .btn-secondary { background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; }
  .next { color: #64748b; font-size: 12px; margin-top: 20px; line-height: 1.6; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">🏗️</div>
  <h1>Bauzeitenplan Archivita</h1>
  <div class="sub">Wilhelm-Binder-Straße 15 · VS-Villingen</div>

  <div class="ok">✓ Login erfolgreich — Multi-User-System läuft!</div>

  <div class="info">
    <b>Eingeloggt als:</b> <?= htmlspecialchars($u['name']) ?><br>
    <b>E-Mail:</b> <?= htmlspecialchars($u['email']) ?><br>
    <b>Rolle:</b> <span class="role role-<?= $u['role'] ?>"><?= $u['role'] ?></span><br>
    <b>Server-Zeit:</b> <?= date('d.m.Y H:i:s') ?>
  </div>

  <a href="#" onclick="logout();return false" class="btn btn-secondary">Logout</a>
  <a href="api/me.php" target="_blank" class="btn btn-primary">/api/me.php anzeigen</a>

  <div class="next">
    <b>Nächster Schritt:</b><br>
    Hier kommt gleich das eigentliche Bauzeitenplan-Dashboard rein. Wir integrieren als
    Nächstes <code>bauzeitenplan_archivita.html</code> in dieses System, damit die
    Aufgaben, Mitarbeiter und Bestellungen alle in der MySQL-Datenbank landen
    und für alle Nutzer live synchronisiert sind.
  </div>
</div>

<script>
async function logout() {
  await fetch('api/logout.php', { method: 'POST', credentials: 'same-origin' });
  location.href = 'login.html?logged_out=1';
}
</script>
</body>
</html>

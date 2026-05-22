<?php
/**
 * Bauzeitenplan Archivita — Konfiguration
 *
 * SO BENUTZT DU DIESE DATEI:
 *   1. Kopiere diese Datei zu  config.php
 *   2. Trage deine echten Werte ein (Platzhalter ersetzen)
 *   3. config.php NIE in Git committen
 *   4. config.php per FTP ins /bauplan/ Verzeichnis hochladen
 *
 * SICHERHEIT:
 *   .htaccess im selben Ordner blockt direkten Zugriff auf config.php
 */

// =====================================================================
//  DATENBANK  (webgo MySQL)
// =====================================================================
const DB_HOST = 'localhost';
const DB_NAME = 'web111_db3';
const DB_USER = 'web111_3';
const DB_PASS = 'HIER_DEIN_DB_PASSWORT_EINTRAGEN';
const DB_CHARSET = 'utf8mb4';

// =====================================================================
//  SMTP  (Magic-Link-Mailversand über webgo)
// =====================================================================
const SMTP_HOST   = 's305.goserver.host';
const SMTP_PORT   = 587;
const SMTP_SECURE = 'tls';                      // STARTTLS
const SMTP_USER   = 'web111pXX';                // Postfachname aus KIS — NICHT die Email!
const SMTP_PASS   = 'HIER_DEIN_SMTP_PASSWORT_EINTRAGEN';
const MAIL_FROM       = 'bauplan@dashauptwerk.de';
const MAIL_FROM_NAME  = 'Bauzeitenplan Archivita';

// =====================================================================
//  APP
// =====================================================================
const APP_URL  = 'https://bauplan.crossfit-hauptwerk.de';
const APP_NAME = 'Bauzeitenplan Archivita';

// Sicherheits-Geheimnis (für Token-Signierung). EINMAL setzen, dann nie ändern.
// Erzeugen mit:  php -r "echo bin2hex(random_bytes(32));"
const APP_SECRET = 'HIER_64_ZEICHEN_ZUFALLS_HEX_EINTRAGEN';

// Erster Admin-Account — wird beim ersten Aufruf von setup.php angelegt.
const FIRST_ADMIN_EMAIL = 'dib@archivita.de';
const FIRST_ADMIN_NAME  = 'DIB Admin';

// =====================================================================
//  SESSION & SECURITY
// =====================================================================
const SESSION_DAYS   = 30;        // Wie lange bleibt User eingeloggt
const TOKEN_MINUTES  = 15;        // Magic-Link gilt 15 Min
const RATE_LIMIT_MAX = 3;         // Max. Login-Mails pro Adresse pro Stunde
const RATE_LIMIT_WINDOW_MIN = 60;

// =====================================================================
//  AUDIT-LOG
// =====================================================================
// Default-Aufbewahrung in Tagen. Im UI änderbar (Setting "audit_retention_days").
// 0 = unbegrenzt (nie löschen)
const AUDIT_RETENTION_DEFAULT_DAYS = 365;

// =====================================================================
//  DEBUG  (für Live: false!)
// =====================================================================
const DEBUG_MODE = false;

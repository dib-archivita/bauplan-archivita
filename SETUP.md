# Bauzeitenplan Archivita — Setup-Anleitung

Diese Anleitung führt dich Schritt für Schritt durch das **erste Hochladen
und Einrichten** auf webgo. Dauer: ca. **20–30 Min**.

---

## 0. Voraussetzungen (sollte alles erledigt sein)

- ✅ Subdomain `plan.crossfit-hauptwerk.de` angelegt, SSL aktiv
- ✅ MySQL-Datenbank `web111_db3` angelegt, User `web111_3` + Passwort
- ✅ E-Mail-Postfach `bauplan@dashauptwerk.de` angelegt, Postfachname `web111p19`
- ✅ PHP-Version auf 8.5 (oder 8.4) eingestellt

---

## 1. FTP-Client einrichten (einmalig)

1. **FileZilla** kostenlos laden: https://filezilla-project.org/
2. Installieren und starten
3. Datei → Servermanager → **Neuer Server**:
   - Server: `s305.goserver.host`
   - Port: `22` (SFTP) oder `21` (FTP) — versuch erst SFTP
   - Protokoll: **SFTP – SSH File Transfer Protocol**
   - Verbindungsart: Normal
   - Benutzer: dein webgo-FTP-User (im KIS unter „FTP-Verwaltung")
   - Passwort: dein webgo-FTP-Passwort
4. **Verbinden** klicken
5. Du landest im Home-Verzeichnis. Wechsle in den Subdomain-Ordner:
   - Pfad ist meistens `/bauplan/` oder `/htdocs/bauplan/`

---

## 2. Datenbank einrichten

### 2a. Schema importieren

1. Öffne **https://s305.goserver.host/phpmyadmin**
2. Logge dich ein mit `web111_3` + DB-Passwort
3. Links die Datenbank **`web111_db3`** anklicken
4. Oben Reiter **„Importieren"**
5. „Datei zum Importieren" → wähle **`schema.sql`** aus dem Backend-Ordner
6. Unten **„Importieren"** klicken
7. Erfolgsmeldung: „X Abfragen erfolgreich" → Schema ist drin

### 2b. Tabellen prüfen

Links sollten jetzt **12 Tabellen** stehen: `users`, `tasks`, `task_sections`,
`einheiten`, `mitarbeiter`, `mitarbeiter_gewerke`, `urlaub`, `bestellungen`,
`magic_tokens`, `sessions`, `rate_limit`, `audit_log`, `settings`.

---

## 3. config.php ausfüllen

1. **Lokal** kopieren: `config.example.php` → `config.php`
2. Öffne `config.php` in einem Texteditor
3. Folgende Werte eintragen:

```php
// DB
const DB_PASS = 'DEIN_DB_PASSWORT';     // das, was du im PM gespeichert hast

// SMTP
const SMTP_USER = 'web111p19';          // ist schon richtig
const SMTP_PASS = 'DEIN_MAIL_PASSWORT'; // Passwort von bauplan@dashauptwerk.de

// APP_SECRET — EINMALIG generieren:
// Terminal:  php -r "echo bin2hex(random_bytes(32));"
// Wenn du kein lokales PHP hast: nimm 64 Zeichen Zufalls-Hex
// von z.B. https://www.random.org/strings/  (Hex, 64 Zeichen)
const APP_SECRET = 'a1b2c3...64-zeichen-hex';

// FIRST_ADMIN_EMAIL — schon korrekt: dib@archivita.de
```

4. **Speichern**

---

## 4. Dateien hochladen

### Lokal vorhanden im Ordner `bauplan_backend/`:

```
.htaccess
config.php                  ← die ausgefüllte Version
schema.sql                  ← nur zum Import in phpMyAdmin
setup.php                   ← einmaliger Setup-Lauf
login.html
SETUP.md                    ← diese Datei

lib/
  ├─ db.php
  ├─ auth.php
  ├─ helpers.php
  └─ mailer.php

api/
  ├─ login.php
  ├─ verify.php
  ├─ logout.php
  ├─ me.php
  ├─ state.php
  ├─ tasks.php
  ├─ users.php
  ├─ audit.php
  └─ migrate.php

cron/
  └─ cleanup.php

assets/
  ├─ sync.js
  └─ admin.js
```

### In FileZilla:

1. Linke Seite (lokal): in den Ordner `bauplan_backend/` navigieren
2. Rechte Seite (Server): in den Subdomain-Ordner `/bauplan/`
3. **Alle Dateien außer `schema.sql` markieren → Drag & Drop nach rechts**
   (`schema.sql` hast du schon in phpMyAdmin importiert)

### Wichtig

- `config.php` MUSS nach `/bauplan/config.php` (im Root der Subdomain)
- Ordner-Struktur muss erhalten bleiben (lib/, api/, cron/, assets/)

---

## 5. Bauzeitenplan-HTML als index.html hochladen

1. Die Datei `bauzeitenplan_archivita.html` umbenennen zu `index.html`
2. **Vor dem `</body>`-Tag** diese zwei Zeilen einfügen:
   ```html
   <script src="assets/sync.js"></script>
   <script src="assets/admin.js"></script>
   ```
3. Hochladen nach `/bauplan/index.html`

---

## 6. Setup-Skript ausführen

Im Browser aufrufen:

```
https://plan.crossfit-hauptwerk.de/setup.php
```

Du solltest eine Textausgabe sehen:

```
=== Bauzeitenplan Archivita — Setup ===

[1/4] ✓ DB-Verbindung OK
[2/4] ✓ Schema vorhanden (12 Tabellen)
[3/4] ✓ Admin-User dib@archivita.de angelegt (id=1)
[4/4] ✓ Login-Mail an dib@archivita.de verschickt
```

→ Du bekommst eine Mail mit Login-Link → drauf klicken → eingeloggt.

### ⚠️ WICHTIG nach erfolgreichem Setup

`setup.php` **löschen oder umbenennen**! Z.B. zu `setup_done_20260522.php`.
Sonst könnte jeder mit der URL einen neuen Admin-Account triggern.

In FileZilla: rechte Seite, `setup.php` → rechtsklick → „Umbenennen".

---

## 7. Cron-Job einrichten

Für tägliches Aufräumen (alte Sessions/Tokens, Audit-Warnmail):

1. webgo KIS → **Cronjobs** → **Neuer Cronjob**
2. Zeit: täglich 04:00 Uhr
3. Befehl:
   ```
   /usr/bin/php /home/web111/htdocs/bauplan/cron/cleanup.php
   ```
   (Pfad ggf. anpassen — der exakte Pfad steht im webgo-KIS unter „Webspace-Pfad")
4. **Speichern**

---

## 8. Test-Durchlauf

1. **Login** auf `https://plan.crossfit-hauptwerk.de`
   → leitet auf `/login.html` um
2. **Mail eingeben** (`dib@archivita.de`) → Login-Link anfordern
3. **Mail prüfen** (Posteingang oder Spam) → Link klicken
4. **Dashboard öffnet sich** mit:
   - Oben rechts: User-Bar mit Name + Rolle „Admin" + Logout
   - Buttons „👥 Nutzer" und „📋 Audit"
5. **Lokale Daten migrieren**:
   Falls du vorher auf demselben Rechner mit dem alten Bauzeitenplan
   gearbeitet hast, fragt der Browser einmal:
   *"Lokale Daten gefunden — jetzt übertragen?"* → **Ja**.
6. **Nutzer anlegen** über „👥 Nutzer":
   - Architekt: Mail + Name → Rolle „Architekt"
   - 5 Vorarbeiter: Rolle „Worker"
   - 5 Betrachter: Rolle „Viewer"
7. **Logout**, dann mit einem der angelegten Accounts einloggen → testen ob
   Rechte stimmen (Worker darf nur Status klicken, Viewer kann nur sehen).

---

## 9. Häufige Probleme

### „Service vorübergehend nicht erreichbar"
→ DB-Verbindung. `config.php` hat falsches DB-Passwort. In FileZilla
   `config.php` öffnen, korrigieren, neu hochladen.

### „Tabelle 'users' fehlt"
→ Schema noch nicht in phpMyAdmin importiert. Siehe Schritt 2a.

### Login-Mail kommt nicht an
- **Spam-Ordner** prüfen
- In `config.php`: stimmt `SMTP_USER = 'web111p19'`? (NICHT die Mail-Adresse, sondern der Postfachname)
- Stimmt das `SMTP_PASS`?
- Test: `setup.php` nochmal aufrufen — wenn dort „Mailversand fehlgeschlagen" steht,
  sind die SMTP-Daten falsch.

### Subdomain zeigt „Apache Default Page"
→ DNS noch nicht durch. 1 Stunde warten. Oder `index.html` noch nicht
   hochgeladen.

### „Diese Verbindung ist nicht sicher" / Zertifikatsfehler
→ Let's Encrypt noch nicht ausgestellt. webgo KIS → SSL prüfen.

---

## 10. Künftige Updates

Bei Code-Änderungen:
1. Geänderte Datei lokal speichern
2. In FileZilla per Drag&Drop hochladen
3. Live in 5 Sek

Bei DB-Änderungen:
1. `migrations/00X_xxx.sql` in phpMyAdmin importieren
2. Oder per `migrate.php` falls eingerichtet

---

## Sicherheits-Checkliste

- [ ] `setup.php` nach erfolgreichem Setup umbenannt/gelöscht
- [ ] `config.php` enthält APP_SECRET (64 Zeichen Zufalls-Hex)
- [ ] `config.php` ist NICHT in Git committed
- [ ] HTTPS funktioniert (Schloss-Symbol im Browser)
- [ ] Login-Mail kommt an
- [ ] Cron-Job läuft täglich
- [ ] Audit-Log zeigt eigene Login-Events

Fertig. 🚀

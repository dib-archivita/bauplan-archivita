# Bauzeitenplan HAUPTWERK — Projekt-Kontext

> **Für neue Claude-Session:** Lies diese Datei zuerst. Dann bist du im Bilde.

## 🏗️ Projekt

**Bauzeitenplan-Dashboard** für **Archivita GmbH**
- Adresse: Wilhelm-Binder-Str. 15, VS-Villingen
- Domain: **plan.crossfit-hauptwerk.de**
- Mail-Domain: dashauptwerk.de (bauplan@dashauptwerk.de)
- Hosting: **webgo** (PHP 8.5 + MySQL)
- Repo: https://github.com/dib-archivita/bauplan-archivita.git
- Auto-Deploy: GitHub Actions (lftp/FTPS) → `git push` = live ~2 Min
- Lokaler Pfad: `/Users/upjoy/Code/bauzeitenplan/bauplan_backend/`
- Aktuelle Version: **bauplan-v83** (Stand: SW-Cache in `sw.js`)

## 🔐 Auth & Rollen

Magic-Link-Login, 15-Min-Token, 30-Tage-Session, max. 12 User.

| Rolle | Rechte |
|---|---|
| **admin** (DIB, dib@archivita.de) | Alles inkl. Nutzerverwaltung + Verlauf |
| **architekt** (Polier) | Alles außer Nutzerverwaltung |
| **worker** (bis 5×) | Nur Status / erledigt / Notiz |
| **viewer** (bis 5×) | Nur Lesen |

⚠️ **NIE**: Passwörter / Tokens im Chat. `config.php` nie in Git (.gitignore). Credentials nur via GitHub Secrets.

## 🧱 Tech-Stack & Deployment

- **Frontend**: 1 große `index.php` (~9000 Zeilen) + Assets (`assets/*.js`, `sw.js`)
- **Backend**: `/api/sync.php` + `/inc/` (auth, db, helpers, mailer)
- **DB**: MySQL `web111_db3` (Tabellen: `users`, `sessions`, `audit_log`, `overrides`, `custom_items`, `kv_state`)
- **Service Worker** `sw.js`: network-first; bei JEDER deployten Änderung CACHE_NAME hochzählen (sonst lädt der Browser Stale)
- **Auto-Reload-Logik**: `assets/mobile.js` registriert SW + reload-on-update — alle offenen Tabs holen neue Version selbst

## 🔄 Sync-Architektur

**Alles** synct über DB:
- **Hauptzeitplan-Aufgaben**: `overrides` (Spalten: entity_type, entity_key, field, value) + `custom_items` (neu angelegte Aufgaben/Sections, JSON `data`)
- **Neben-Tabs** (Bestellungen, Budget, Kapazität, Einheiten, TODs, Gewerke-Liste, task-mh-*): **generischer KV-Sync** via `kv_state`-Tabelle (Key `bo-orders-v3`, `cost-values`, `unit-costs`, `kap-mitarbeiter-v10`, `unit-registry`, `gewerke-list-v1`, `task-times-v1`, `budget-custom-v1`, Prefix `task-mh-`, `todo-kw-`, `cost-name-`)
- **Poll**: 5 Sek (`assets/sync2.js`), server_time aus MySQL `NOW() - 2s` Puffer → konsistente Uhr
- **localStorage-Monkey-Patch** auf `Storage.prototype.setItem/removeItem` (Safari-tauglich): jedes whitelisted setItem pusht automatisch an KV

`window.PlanSync` API: `pushOverride`, `pushCustomAdd/Update/Delete`, `pushKV`, `isApplyingRemote`, `forcePoll`.

## 📁 Wichtige Dateien

| Datei | Funktion |
|---|---|
| `index.php` | Hauptansicht (Hauptzeitplan/TODs/Einheiten/Budget/Kapazität/Bestellungen) — alle Tabs, Inline-Scripts, base HTML |
| `assets/sync2.js` | Live-Sync-Layer (overrides + custom_items + kv_state) |
| `assets/sync.js` | Auth/Userbar/Rolle (Sync-Teile deaktiviert) |
| `assets/changes.js` | Änderungs-Tracking (localStorage + push) + `cleanText()` ohne Button-Symbole |
| `assets/section-edit.js` | Sections/KFW editierbar, +Aufgabe/+Bereich, Löschen, Undo, ⌘Z-FAB, `plainCellText()` |
| `assets/history-modal.js` | 🕐 Verlauf-Modal (Admin) |
| `assets/bar-labels.js` | Balken-Labels + Hover-Tooltip |
| `assets/mobile.js` | SW-Registrierung + Auto-Reload + Heute-FAB |
| `api/sync.php` | GET overrides/custom/kv ?since=…; POST `override`, `custom_add/update/delete`, `kv_set`, `cleanup_glyphs`; auto-CREATE kv_state |
| `migrations/002_sync_tables.sql` | overrides + custom_items Schema |
| `migrations/003_kv_state.sql` | KV-Tabelle |
| `.github/workflows/deploy.yml` | lftp-Push zu webgo + config.php aus Secrets |

## ✅ Was funktioniert (Stand v80)

- ✅ Magic-Link-Auth, Rollen, Audit-Log
- ✅ Sync **aller** Tab-Daten via DB (Multi-User Realtime)
- ✅ Hauptzeitplan: editierbare Sections/KFW/Tasks, +/✕ Buttons, Undo
- ✅ Status-Dropdown im Hauptzeitplan mit 12 Stufen (geplant → vorbereitung → begonnen → 25/50/75/90 % → abnahme → fertig + ⭐ Priorität, ⏸ Pause, ⚠ verzögert, ✕ abgebrochen) — **kein Cycling, echtes Dropdown**
- ✅ Bestellungen: Inline-Editing (Status, Verantwortl., bis-KW als Modal-only, Lieferung als Datepicker), Status-Pills statt Dropdown, „⚠ verzögert"-Quickfilter (bis-KW < heute), Spalte Verantwortlich ausgeblendet, „Lieferant" statt „Firma", Löschen
- ✅ Budget: Bestellungen-Übersicht „verbindlich/Schätzung", eigene Custom-Positionen, Position-Namen inline editierbar (KV `cost-name-*`)
- ✅ Kapazität:
  - Cockpit oben (KPI-Karten, klickbar)
  - Sub-Tab **🎯 Gewerke-Übersicht** (Karten pro Gewerk)
  - **👥 Mitarbeiter** als Karten mit Avatar + Datum-Picker für Von-Bis + „+ Urlaub"-Inline-Editor (editierbar, löschbar)
  - **📅 Kalender** mit „Nur Überlast"-Filter
  - **Gastromatic-Stub** für künftigen Urlaubs-Import
  - `cascadeRenameKapEmployees` + `cascadeClearKapEmployees`
- ✅ TODs: Inline-Status-Dot + „▶ begonnen" / „✓ erledigt" Buttons + Gewerke-Filter (Zeitstempel-Anzeige aktuell deaktiviert, Erfassung läuft im Hintergrund)
- ✅ Gewerke-Verwaltung im Hauptzeitplan-Filter (🔧-Modal): Name + Farb-Picker + 🗑 löschen + neu, alphabetisch sortiert, **Cascade-Rename** durch alle Verweise (Aufgaben, Bestellungen, Mitarbeiter), **Cascade-Clear** bei Löschung
- ✅ Mannstunden im Bar-Editor verknüpft mit Kapa
- ✅ Tab-Auswahl persistent über Reload (localStorage `active-tab`)
- ✅ FAB-Buttons (+, ⏰, Heute) nur im Hauptzeitplan-Tab via `body[data-active-tab]` + `!important`
- ✅ Status-Übersichtsbalken oben passt sich pro Tab an
- ✅ Hauptplan-Filter blendet leere Sections + KfW-Banner aus
- ✅ Heat-Strip im Hauptzeitplan oben (nur bei aktivem Gewerk-Filter): Auslastungs-% pro KW farbig
- ✅ Cascade-Shift bei Bar-Drag fällt auf Gewerk zurück wenn kein `data-unit`
- ✅ Neue Aufgaben bekommen Default-Balken (current KW + 4 Wochen)
- ✅ Hauptzeitplan startet visuell bei KW23 (left:0, seit v81 echte Migration — kein Scroll-Lock mehr)
- ✅ **Tages-Grid (v82)**: feine Tageslinien (alle 6px = 1 Tag) + kräftigere Wochenlinie (42px) + Wochenend-Schattierung in `.gantt-row-inner` (reines CSS-Overlay, keine Balken-/DB-Änderung). Folgeoption falls Tage zu schmal: Umschalter Wochen-/breite Tagesansicht (Variante C, noch nicht gebaut).

## ⚠️ Aktuelle Tradeoffs / offene Punkte

1. ✅ **KW 19–22 endgültig weg (v81)** — gelöst per echter Quell-Migration statt Runtime-Shift:
   - `ORIGIN_KW` 19→23 überall, `left:0` = KW23 = 1. Juni 2026. Header (KW + Monat) neu generiert ab KW23, alle ~292 Balken `left −168` (3 KW22-Tasks auf KW23 geklemmt), Grid-Breite 3768→3600, CSS-Hack + Scroll-Lock raus.
   - Today-Line `ORIGIN` = 1. Juni 2026, `todayKW` Basis 23. mobile.js Heute-FAB-Origin angepasst. section-edit.js / sync2.js Breiten + ORIGIN_KW mit.
   - **DB-Migration** (`api/sync.php`): gespeicherte `bar_left` in `overrides` + `custom_items` einmalig −168 (atomar, Flag `origin_kw23_migrated` in `kv_state`, läuft beim ersten Poll, kein Doppel-Shift). Status: `GET /api/sync.php?migrate=status` (Admin).
   - Lehre: Runtime-Margin-Shift (v69/v79) scheiterte an X-Origin-Mismatch + Today-Line-Doppel-Shift + dynamischen Zeilen. Quell-Migration ist robust.

1b. ✅ **Gantt-Spaltenbug behoben (v83)**: `#main-gantt` hatte 6 `<col>` aber nur 5 Zellen/Zeile — KFW-Header nutzten `colspan="5"` + Gantt-`td` (= 6 Spalten), während Task-/Header-/Section-Zeilen 5 Spalten hatten. → verwaiste Phantom-Spalte (~3600px), Gantt landete je Zeilentyp in col5/col6, Tabelle ~7950px statt ~4300, Spalten zu breit / Sticky-Header misaligned. **Fix**: orphan `<col 80px>` raus → colgroup `[auto,60,100,100,3600]` (5 Spalten), alle 6 KFW-`colspan="5"`→`colspan="4"`. Lokal mit voller Tabelle + sticky.js im Preview-Harness vorher/nachher verifiziert (7953→4326px, Header=Body). **`table-layout` bleibt `auto`** — `fixed` würde adaptive Status-Spalte (lange Status-Texte) klemmen.

2. **Gastromatic-Integration** fehlt — Stub ist drin, aber noch keine API-Anbindung. Brauche API-Key + Mitarbeiter-Mapping.

3. **User-Accounts** (Architekt + 5 Worker + 5 Viewer) noch nicht angelegt — User wollte das später machen.

4. **Excel-Import** (`HAUPTWERK_Zeitplanung_GESAMT.xlsx`, Raumbuch) noch nicht implementiert.

5. **Mail-Benachrichtigungen** bei Status-Änderungen noch nicht implementiert (SMTP ist konfiguriert).

## 🔧 Wichtige Konstanten

- `ORIGIN_KW = 23` (Projektstart-Referenz im JS; **seit v81**, davor 19) → `left:0` = KW23
- `PX_PER_WEEK = 42` (eine Woche = 42 px in der Gantt)
- KW 23 = 1. Juni 2026 (Projektstart, jetzt linker Rand `left:0`)
- Heute (Stand v80): real KW von aktuellem Datum, dynamisch
- Status-Werte siehe `STATUS_OPTIONS` Array
- Default-Mannstunden pro Task: 40 (in localStorage `task-mh-<tid>`)

## 🏷️ Workflow für Updates

```bash
# Änderungen in index.php / assets/* / api/* / migrations/*
# Service Worker Cache hochzählen (sw.js → CACHE_NAME = 'bauplan-vXX')
git add ...
git commit -m "..."
git push  # Auto-Deploy via GitHub Actions ~2 Min

# Verify live:
curl -s "https://plan.crossfit-hauptwerk.de/sw.js" | grep CACHE_NAME
```

## 🔍 Wenn Probleme auftauchen

1. **Cache-Problem?** SW Cache hochzählen + User soll hart reloaden (⌘+Shift+R)
2. **Layout broken nach Filter?** → `applyFilters()` in `index.php` prüfen, dann `updateTodayLine()`
3. **Sync hängt?** → `assets/sync2.js`, `setIndicator()` zeigt Status unten links
4. **PHP-Fehler in api/sync.php?** → audit_log signature (`string` nicht null), serverTime aus MySQL nehmen
5. **Safari setItem-Patch nicht aktiv?** → Storage.prototype-Override prüfen + `__syncKV()` als Fallback

## 📋 Sync-Whitelist (KV)

```js
EXACT = ['bo-orders-v3', 'cost-values', 'unit-costs', 'kap-mitarbeiter-v10',
        'unit-registry', 'gewerke-list-v1', 'task-times-v1', 'budget-custom-v1']
PREFIX = ['task-mh-', 'todo-kw-', 'cost-name-']
```

## 💬 Wie wir arbeiten

- User ist nicht Entwickler — schreibt Anweisungen umgangssprachlich auf Deutsch
- Antworten kurz, konkret, deutsch
- Bei Code-Änderungen: SW-Cache hochzählen, committen, pushen, warten bis live (`until curl … grep CACHE_NAME`), dann melden
- Bei Layout-Themen: vorsichtig — Sticky-Spalten + Gantt-Wrap haben fragile X-Koordinaten
- User schickt oft Screenshots wenn was kaputt aussieht

---

**Letzte Hand-Off (v81)**: KW 19–22 endgültig entfernt per Quell-Migration (ORIGIN_KW 19→23, alle Koordinaten −168, Header neu generiert, DB-`bar_left`-Migration). Lokal verifiziert (Header-Div-Balance, Konstanten, geklemmte KW22-Balken). **Noch live zu prüfen**: User-Screenshot Hauptzeitplan auf Wide-Screen — KW23 muss linker Rand sein, Today-Line + Sticky-Spalten korrekt, gedraggte Balken nicht verschoben. v82 ergänzt Tages-Grid (lokal im Harness `_gridtest.html`/Preview verifiziert, gelöscht). Danach offen: User-Accounts / Excel-Import / Gastromatic / Mail.

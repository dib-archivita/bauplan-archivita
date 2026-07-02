# Bauzeitenplan HAUPTWERK — Projekt-Kontext

> **Für neue Claude-Session: Lies diese Datei zuerst.** Sie ist der konsolidierte
> Stand. Antworten kurz/konkret/deutsch. User ist **kein** Entwickler.

## 🏗️ Projekt
- **Bauzeitenplan-Dashboard** für **Archivita GmbH**, Wilhelm-Binder-Str. 15, VS-Villingen
- Domain: **plan.crossfit-hauptwerk.de** · Mail: bauplan@dashauptwerk.de
- Hosting: **webgo** (PHP 8.5 + MySQL `web111_db3`)
- Repo: `https://github.com/dib-archivita/bauplan-archivita.git` (Branch **main**)
- Lokaler Pfad: `/Users/upjoy/Code/bauzeitenplan/bauplan_backend/`
- **Auto-Deploy**: `git push` auf `main` → GitHub Actions (lftp/FTPS) → live in ~2 Min
- Aktuelle Version: **bauplan-v102** (Stand: `CACHE_NAME` in `sw.js`)

## 🔐 Auth & Rollen
Magic-Link-Login, 15-Min-Token, 30-Tage-Session, max. 12 User.

| Rolle | Rechte |
|---|---|
| **admin** (DIB, dib@archivita.de) | Alles inkl. Nutzerverwaltung + Verlauf |
| **architekt** (Polier) | Alles außer Nutzerverwaltung |
| **worker** (≤5) | Nur Status / erledigt / Notiz |
| **viewer** (≤5) | Nur Lesen |

⚠️ **NIE** Passwörter/Tokens im Chat. `config.php` nie in Git (.gitignore), nur via GitHub Secrets.

## 🧱 Tech-Stack
- **Frontend**: 1 große `index.php` (~9300 Zeilen): alle Tabs (Hauptzeitplan/TODs/Einheiten/Budget/Kapazität/Bestellungen), base-HTML + viele **Inline-`<script>`-IIFEs** + `<style>`. Plus `assets/*.js`, `sw.js`.
- **Backend**: `api/sync.php` + `inc/` (auth, db, helpers, mailer). `cron/cleanup.php` (täglich).
- **Service Worker** `sw.js`: network-first. **Bei JEDER deployten Browser-Änderung `CACHE_NAME` hochzählen** (sonst Stale). `assets/mobile.js` registriert SW + reload-on-update → offene Tabs holen neue Version selbst.

## 🔄 Sync (alles über DB, Poll 5 s in `assets/sync2.js`)
- **Hauptzeitplan-Tasks**: `overrides` (entity_type, entity_key, field, value) + `custom_items` (neue Tasks/Sections, JSON `data`).
- **Neben-Tabs**: generischer **KV-Sync** via `kv_state` (Keys siehe Whitelist unten).
- `server_time` aus MySQL `NOW()-2s` (konsistente Uhr). localStorage-Monkey-Patch auf `setItem/removeItem` pusht whitelisted Keys automatisch.
- `window.PlanSync`: `pushOverride`, `pushCustomAdd/Update/Delete`, `pushKV`, `isApplyingRemote`, `forcePoll`.
- **KV-Whitelist**: EXACT = `bo-orders-v3, cost-values, unit-costs, kap-mitarbeiter-v10, unit-registry, gewerke-list-v1, task-times-v1, budget-custom-v1` · PREFIX = `task-mh-, todo-kw-, cost-name-`.

## 📁 Wichtige Dateien
| Datei | Funktion |
|---|---|
| `index.php` | Hauptansicht, alle Tabs, Inline-IIFEs (Bar-Editor, Drag-Engine, Heat-Strip, Today-Line, Filter), `<style>` |
| `assets/sync2.js` | Live-Sync (overrides+custom+kv); wendet `bar_left/bar_width` direkt an |
| `assets/sync.js` | Auth/Userbar/Rolle |
| `assets/section-edit.js` | Sections/KFW editierbar, +Aufgabe/+Bereich, Löschen, **Undo-Stack** (`window.pushUndo` exponiert), ⌘Z-FAB |
| `assets/sticky.js` | Geklonter Sticky-Tabellenkopf; misst Spaltenbreiten an **erster sichtbarer** task-row |
| `assets/bar-labels.js` | `.bar-label` (Aufgabe·Firma) auf Balken + Hover-Tooltip |
| `assets/mobile.js` | SW-Registrierung + Auto-Reload + Heute-FAB |
| `api/sync.php` | GET overrides/custom/kv `?since=`; POST `override/custom_*/kv_set/cleanup_glyphs`; **2 Einmal-DB-Migrationen** (s.u.); `?migrate=status` (Admin) |
| `cron/cleanup.php` | Tägl. Cron: löscht abgelaufene Sessions/Tokens/Rate-Limit + Audit-Warnmail. **Nur CLI** (HTTP→403). |
| `.github/workflows/deploy.yml` | lftp-Push zu webgo + config.php aus Secrets |

## 📐 Gantt-Koordinatensystem (WICHTIG — fragil!)
- **`ORIGIN_KW = 23`**, **`PX_PER_WEEK = 126`** → **`left:0` = KW23 = Mo 1. Juni 2026**, **1 Tag = 18 px**.
- **Echte Pixel, kein Zoom/scaleX** (war früher unscharf). Grid-Breite **10800 px**. Es gibt **nur die Tagesansicht** (kein Wochen-/Tages-Umschalter mehr; `window.GANTT_Z=1` ist nur Legacy-No-op).
- `#main-gantt` = **5 Spalten** (`colgroup [auto,60,100,100,3600]→ jetzt 126er, Breite via width:10800`): Aufgabe, Status, Gewerk, Firma, Gantt. **KFW-/Section-Header spannen `colspan="4"` + 1 Gantt-`td`** (NICHT 5/6 — sonst Phantom-Spalte!).
- `.gantt-row-inner` (Breite 10800) trägt das **Tagesraster** als CSS-Background (18px Tag / 126px Woche / Wochenend-Schatten 90–126). `.kw-label{width:126px}`, Mo–So-Header `.gantt-day-header` (immer sichtbar, `ensureDayHeader`, 18px/Tag).
- Balken: `<div class="gantt-bar" style="left:Npx;width:Mpx">` (N,M Vielfache von 18). Datum↔px im Bar-Editor: `pxFromDate/dateFromPx`, `PX_PER_DAY=18`, ORIGIN 1.6.2026.
- **Geteilter Helfer `window.ganttSetBar(bar,left,width,sync)`** setzt Style + localStorage `bar-pos-` + Sync — genutzt von Editor, Drag-Undo, Undo-Restore.
- Today-Line + Heat-Strip rechnen mit `PX_PER_WEEK=126`.

## ✅ Was funktioniert
- Magic-Link-Auth, Rollen, Audit-Log; **Multi-User-Realtime-Sync** aller Tabs.
- **Hauptzeitplan**: editierbare Sections/KFW/Tasks (+/✕, Undo inkl. **Balken-Drag/Resize/Editor** via ⌘Z/↺-FAB), 12-Stufen-Status-Dropdown.
- **Tag-genaues Planen**: Balken klicken → **Bar-Editor mit Von/Bis-Datum**; Drag/Resize rasten auf **Tage** (18px, min 1 Tag); Mo–So-Kopf; alles **scharf**.
- **Status-Filter über die Counter-Cards** (v94): keine separate Status-Leiste mehr. Die 4 Summary-Cards (Abgeschlossen/In Arbeit/Geplant/Verzögert) sind klickbar → `filterStatusCard(cat)` setzt `activeStatusCat`, nochmal klicken = alle, immer nur eine aktiv (farbiger Ring). **„✕ Alle anzeigen"-Button** (`#status-clear-btn` / `clearStatusFilter`) erscheint in der Summary-Leiste, sobald ein Filter aktiv ist (expliziter Reset, v95). `applyFilters` matcht per `classifyStatus(st)===activeStatusCat` (identisch zur Zähl-Logik → Klick zeigt genau die gezählten Zeilen; „priorität" zählt/filtert als *In Arbeit*). Cards filtern nur im Hauptzeitplan (Guard auf `body[data-active-tab=hauptwerk]`; `syncStatusCardHighlight` blendet Markierung in anderen Tabs aus).
- **Mehrfach-Gewerk-Filter** (`selectedGewerke`-Array; Pills toggeln; „Alle Gewerke" leert; `applyFilters` = Vereinigung). **Auslastungs-Streifen gestapelt** als `tr.kapa-heat-row` oben in tbody (Label `colspan=4` im festen Bereich, Zellen in Gantt-Spalte; `renderHeatStrip`/`buildHeatCells` lesen `window.selectedGewerke`).
- **Alle Bereichs-/Phasen-Header einheitlich Navy** (`.kfw-header-row`, Farbvarianten entfernt).
- Bestellungen (Inline-Edit, Status-Pills, „⚠ verzögert"-Filter), Budget (verbindlich/Schätzung + Custom-Positionen), Kapazität (Cockpit, Gewerke-Übersicht, Mitarbeiter-Karten, Kalender, Gastromatic-**Stub**), TODs.
- **Gewerke-Verwaltung** (🔧-Modal: Name/Farbe/löschen, Cascade-Rename/Clear). Mannstunden im Bar-Editor ↔ Kapa. Tab-Auswahl persistent.
- **Blower-Door-Test nur in T 5.1** (`data-task-type="blowerdoor"`, `data-unit="T_5_1"`; aktuell Platzhalter-Balken ~KW30 — User kann via Editor umdatieren). Budget-Positionen „Blowerdoor 9 WE/14 Einh." separat (unberührt).

## 🗄️ DB-Migrationen (in `api/sync.php`, nach kv_state-CREATE)
Beide **atomar** (INSERT-IGNORE-Flag in `kv_state`), laufen **einmalig beim ersten Poll** VOR Auslieferung der Overrides (kein Doppel-Effekt, kein Falsch-Render-Fenster):
1. `origin_kw23_migrated` — alle `bar_left` (overrides+custom) **−168** (KW19→KW23-Ursprung).
2. `scale_day_x3_migrated` — alle `bar_left`+`bar_width` **×3** (42→126 px/Woche).
Frontend-Einmal-Cleanup `coord-scale-v126` (localStorage) verwirft alte 42-Basis-`bar-pos-`-Caches.

## ⚠️ Offene Punkte / nächste Schritte
1. **User-Accounts** (Architekt + Worker + Viewer) noch nicht angelegt.
2. **Excel-Import** (`HAUPTWERK_Zeitplanung_GESAMT.xlsx`, Raumbuch) nicht implementiert.
3. **Gastromatic-Urlaubs-Import**: Stub da, keine API-Anbindung (braucht API-Key + Mitarbeiter-Mapping).
4. **Mail bei Status-Änderungen** nicht implementiert (SMTP konfiguriert).
5. Heat-Strips scrollen vertikal mit (nicht sticky). Kleine farbige KFW-Badges/Border-Akzente noch uneinheitlich (Hintergrund ist einheitlich Navy).

## 🛠️ Cron / webgo
- webgo-Kundenportal (KIS): **https://login.webgo.de** → Vertrag wählen → **Vertragsübersicht → Cronjobs**.
- Korrekter Cron-Befehl (Slash vorne!): `/usr/bin/php /home/www/bauplan/cron/cleanup.php`. (Bug-Lektion: `usr/bin/php` ohne `/` → tägliche Fehlermail, Skript lief nie.)
- `cron/cleanup.php` ist **CLI-only** (HTTP→403). Crontab kann ich **nicht** per Deploy ändern (nur Dateien), das macht der User im KIS.

## 🏷️ Workflow für Updates
```bash
# 1. Code ändern (index.php / assets/* / api/* / cron/*)
# 2. Bei Browser-Änderung: sw.js CACHE_NAME hochzählen (bauplan-vXX). Backend-only (cron/api) → kein Bump nötig.
git add <dateien> && git commit -m "..." && git push        # Co-Authored-By Claude … anhängen
# 3. Live verifizieren (Deploy ~45–120s):
#    until [ "$(curl -s 'https://plan.crossfit-hauptwerk.de/sw.js?cb=$RANDOM' | grep -oE 'bauplan-v[0-9]+')" = "bauplan-vXX" ]; do sleep 15; done
# 4. User um Hard-Reload (⌘+Shift+R) + ggf. Screenshot bitten.
```

## 🔬 Verify-Strategie (lokal kein PHP/MySQL/config.php; Seite ist login-gated → 302)
- **Statische/CSS/JS-Layout-Teile lokal prüfen** via **standalone HTML-Harness** + Preview-Tools (`.claude/launch.json` „static" = python http.server; Root ist `/Users/upjoy/Code`, also URL `/bauplan_backend/<datei>.html`). Harness danach **löschen** (nicht committen).
- Große mechanische Änderungen (Koordinaten-Migration etc.) als **Python-Skript in `/tmp`** (deterministisch, mit Verifikations-Ausgabe), dann Ergebnis prüfen.
- Deployte **Assets** (mobile.js/sync2.js) ohne Login per curl prüfbar; `index.php`/Editor/Sync nur durch User-Test (Screenshot).
- JPEG-Screenshots taugen **nicht** für Schärfe-Urteile.

## 💡 Fragile Stellen / Lehren
- Gantt-Layout: Sticky-Spalten + Tabellen-`colgroup` + Gantt-Breite sind empfindlich. **Spaltenanzahl konsistent halten** (5 cols; KFW/Section = colspan 4 + Gantt-td). `table-layout:auto` lassen (fixed klemmt adaptive Status-Spalte).
- **Kein scaleX-Zoom** mehr für die Tagesansicht — echte Pixel (Schärfe). Falls Maßstab geändert werden soll: alle Koordinaten + DB ×Faktor migrieren (Muster: `scale_day_x3`).
- `audit_log`-Signatur: `(int uid, string action, string entityType, ?string entityKey, array meta)`.
- PDO ist im **Exception-Modus** (`inc/db.php`) → try/catch + Transaktion für Migrationen.

---
**v102:** Löschen-✕ (und 🕐-Verlauf) bei **langen Aufgaben-Namen** nicht erreichbar. Ursache: `.task-name-cell` ist `overflow:hidden; white-space:nowrap`; das ✕ war `float:right`, das 🕐 `inline-block` → bei langem Namen schob der nicht-umbrechende Text die Buttons aus dem sichtbaren Bereich (kurze Namen ok → „nicht alle löschbar"). Fix: `.task-name-cell{position:relative}`; `.se-row-del` (✕) → `position:absolute;right:2px` (section-edit.js); `.se-history-btn` (🕐) → `position:absolute;right:26px` (history-modal.js). Beide `z-index:6`, mittig, immer am rechten Rand. Per Screenshot verifiziert (kurz + lang).

**v101:** Letzte Tabellenzeile war unlesbar (schwebende `position:fixed`-Overlays lagen drüber). Fix: `.gantt-wrap` `padding-bottom` 24→**120px** (letzte Zeile scrollt über die Overlays frei); `#sync-indicator` tiefer (`bottom:10px`, `left:16px`, `opacity:.92`). Schwebende FABs (`.btn-new-task`+, `.btn-toggle-panel`🕐, `#se-undo-fab`↶, `#today-fab`) unten rechts unverändert — durch das Bottom-Padding decken sie die letzte Zeile nicht mehr ab.

**v100:** **Custom-Bereiche (per „+ Bereich" angelegte Sections) endlich voll funktionsfähig.** 3 Bugs behoben: (1) Nach Reload baut `sync2.js buildCustomSectionRow` sie neu auf, aber der `rowObs`-Beobachter in `section-edit.js` band nur `tr.task-row` → Custom-Bereiche hatten **keine ✕/+Aufgabe-Knöpfe & keinen editierbaren Namen**. Jetzt behandelt `rowObs` auch `tr.section-row` (makeEditableText + addAddTaskButton). (2) `makeEditableText`-Umbenennen rief **gar kein PlanSync** → Umbenennungen von Custom-Bereichen syncen jetzt via `pushCustomUpdate(client_id,{name})` (stabil, kein Positions-Drift), und `applyCustom` wendet Section-Namens-Updates an. (3) `deleteSection` nutzte bei Custom-Bereichen `pushOverride('section',section-idx-N,...)` statt `pushCustomDelete(client_id)` → Löschung griff nach Reload nicht. Jetzt: Custom → `pushCustomDelete`. **Offen:** Umbenennen STATISCHER Sections/KfW synct weiterhin nicht bzw. positions-fragil (separate Baustelle; echte Kur = stabile data-row-keys).

**v99 + WICHTIGE Lehre (Positions-Anker):** Bereichs-/KfW-**Umbenennungen** werden als Overrides mit **positions-basiertem Key** gespeichert: `section-idx-N` / `kfw-idx-N` (= `tbody.children[N]` in `sectionByKey`/`kfwByKey`, sync2.js). **Verschiebt sich die Zeilenstruktur** (mein v96-Insert, Custom-Bereiche, gelöschte Tasks…), zeigt `N` auf die **falsche Zeile** → Umbenennung „driftet". Symptom hier: `section-idx-38`=„Windkraftanlage" landete auf einem **KfW-Überpunkt** (weil `applyOverride` den Namen typ-blind via `tbody.children[38]` setzte). **v99-Fix:** `applyOverride` ist jetzt **typ-sicher** (section-Rename nur auf `.section-row`, kfw-Rename nur auf `.kfw-header-row`) → Cross-Typ-Drift ausgeschlossen. **Rest-Problem (offen):** Drift innerhalb desselben Typs bleibt möglich. **Echte Kur** = stabile, positions-unabhängige Keys (z. B. `data-row-key` an statische Rows + `activate()`/`sectionByKey` darauf umstellen) — setzt aber bestehende positionelle Overrides zurück (Renames neu eintippen). DB-Stand einsehbar über `GET /api/sync.php` (eingeloggt). Aktuelle Renames in DB: `section-idx-6`=Kaltwassersatz, `section-idx-38`=Windkraftanlage, `section-idx-39`=Batteriespeicher 400KW, `kfw-idx-40`=HBO Gebäudehülle.

**v98:** **Zuklappen repariert** — Klick auf den ▶/▼-Pfeil einer `section-row` klappt deren Aufgaben zu/auf. Alter Code (index.php ~1270) lief VOR dem Tabellen-HTML → band an nichts (Pfeile blieben ▶, klicken tat nichts). Neu: **Event-Delegation auf `document`** (`window.toggleSectionCollapse`), nur der `.section-arrow` ist Auslöser (außerhalb `.editable-text` → Umbenennen bleibt), `data-collapsed`-Attribut, Pfeile initial ▼. CSS: `.section-arrow` cursor+hover.

**v96→v97 (REVERT):** Versuch, den Haustechnik-Bereich „Aufzüge" **statisch im Code** zu duplizieren („Aufzug klein/groß"), wurde **zurückgenommen**. Grund: Der Nutzer hat eigene Bereiche in der App angelegt (custom_items, z. B. „Windkraftanlage"). Deren Einsortierung nutzt **absolute `tbody.children[idx]`-Anker** (`sectionByKey`/`kfwByKey` in `sync2.js`) — das statische Einfügen von 23 Zeilen **verschiebt diese Indizes** und platziert die App-Bereiche falsch. **Lehre:** Bereiche/Aufgaben, die zur bestehenden (DB-)Struktur passen sollen, NICHT statisch in `index.php` einfügen → als **custom_item** über `PlanSync.pushCustomAdd` anlegen (wie „+ Bereich"/„+ Aufgabe"). Ideal wäre eine echte **„Bereich duplizieren"-Funktion** in `section-edit.js` (Section-custom_add + je Task ein task-custom_add, Tasks per `after_key`=vorherige tid verketten — das ist tid-basiert & robust; nur Section-Anker bleibt positions-fragil).

**Letzte Hand-Off (v95):** Status-Filter umgebaut — separate Status-Leiste raus, stattdessen die 4 Counter-Cards oben klickbar (kategoriebasiert via `classifyStatus`, nur eine aktiv, farbiger Ring, nur im Hauptzeitplan) + „✕ Alle anzeigen"-Button als expliziter Reset (v95). Davor (v93): Tagesansicht fertig (fester 126px-Maßstab, scharf, Mo–So, tag-genaues Drag/Editor/Undo, KW-Raster fluchtet), Header einheitlich Navy, Blower-Door nur in T 5.1, Cron-Fehlermail behoben + `cleanup.php` CLI-abgesichert. **Nächste sinnvolle Schritte:** User-Accounts, Excel/Raumbuch-Import, Mail-Benachrichtigungen, Gastromatic.

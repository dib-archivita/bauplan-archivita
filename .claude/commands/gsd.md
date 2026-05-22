---
description: Get Shit Done — Code- und Plan-Audit + Auto-Fix
---

# /gsd — Get Shit Done

Führe einen kompletten Plausibilitäts- und Funktions-Check durch und behebe ALLES, was problematisch ist. **Nicht nur berichten — wirklich umsetzen.** Am Ende committen + pushen, damit alles live geht.

## Was zu prüfen ist

### 1. Bauzeitenplan-Logik (`index.php` + `assets/sync.js`)

- **KW-Berechnungen**: ORIGIN_KW=19, PX_PER_WEEK=42. Stimmen alle `left:`/`width:`-Werte zu den behaupteten KW-Bereichen?
- **Today-Line**: positioniert sich korrekt auf aktueller KW (heute = `date()`)? Erscheint sie als durchgehende Linie?
- **Jahreswechsel**: KW52/2026 → KW1/2027 nahtlos? KW-Labels stimmen?
- **Phasen**: Phase 1 (W 5.2, W 5.1, T 5.1) → Phase 2 (T 4.01–4.08) → Phase 3 (T 4.22–4.09 + Maisonette) → Phase 4 (W 5.3). Reihenfolge + Gruppierung korrekt?
- **Einheiten**: 22 Wohnungen + Büro. Werden überall (Hauptzeitplan, Einheiten-Tab, Budget, Kapazität) konsistent gezählt?
- **Stat-Karten**: Summen (Fertig, In Arbeit, Geplant, Verzögert) addieren sich richtig?
- **Gewerk-Liste**: 10 konsolidierte Gewerke. Filter im Hauptzeitplan deckt sie ab?
- **Mitarbeiter**: Sanitär (Elmar, Urim), Elektro (Architronik), Maler (Chris, Adnan, Ighor, Leiharbeiter), Allrounder (Kamil, Rene, Micha, Serhi, Danil)?
- **Bestellungen / TODs**: Verknüpfung zum Hauptzeitplan funktioniert?

### 2. Backend-Code (`inc/`, `api/`)

- **PHP-Syntax** korrekt? `php -l` auf jeder Datei
- **Funktionsnamen**: keine Konflikte mit PHP-Builtins (wie früher `session_destroy`)
- **Inkludes**: alle `require_once __DIR__ . '/../inc/…'` funktionieren?
- **Datentypen**: `declare(strict_types=1)` konsequent?
- **PDO**: deprecated Konstanten ersetzen (z.B. `PDO::MYSQL_ATTR_INIT_COMMAND` → `Pdo\Mysql::ATTR_INIT_COMMAND` falls PHP 8.5)
- **Audit-Log**: bei jeder relevanten Aktion korrekt eingebunden?
- **Rollen-Check**: `architekt` = alles außer Nutzerverwaltung. `worker` = nur Status/Notiz. `viewer` = nur lesen. Stimmt das in jedem Endpoint?
- **Magic-Link / Session**: Tokens sicher gehasht, einmalig nutzbar, korrekt abläufig?

### 3. Frontend (`assets/sync.js`, `assets/admin.js`)

- **CSS-Selektoren**: keine zu generischen Klassen, die unbeabsichtigt durchschlagen (wie früher `.role-admin` auf body)
- **Event-Handler**: feuern bei den richtigen Elementen?
- **Polling**: läuft mit `since`-Timestamp korrekt?
- **localStorage-Migration**: idempotent, lässt sich nicht zweimal ausführen?
- **Rollen-basiertes UI**: Edit-Felder werden bei Worker/Viewer wirklich deaktiviert?

### 4. Deploy & Infrastruktur

- **`.github/workflows/deploy.yml`**: lftp-Konfig stabil? `--no-perms` drin? `.git` Reste auf Server werden weggeräumt?
- **`config.php`-Generierung**: alle nötigen Secrets vorhanden? Sind alle Konstanten richtig benannt?
- **`.htaccess`**: HTTPS-Force, Schutz für `config.php`, `index.html/php` als DirectoryIndex korrekt?
- **DB-Schema (`schema.sql`)**: Foreign Keys konsistent? Spalten matchen den PHP-Code?
- **Cronjob `cron/cleanup.php`**: läuft fehlerfrei? Pfade stimmen?

### 5. UX / Konsistenz

- **Datumsformat**: überall deutsch (DD.MM.YYYY)? KW immer mit „KW"-Prefix?
- **Texte**: keine Tippfehler in sichtbarem UI (z.B. „Prioriät" → „Priorität")?
- **Farben**: Status-Farben (geplant=blau, laufend=orange, fertig=grün, verzögert=rot, priorität=lila/orange) konsistent über alle Tabs?
- **Mobile-Tauglichkeit**: zumindest die wichtigsten Buttons erreichbar?

## Workflow

1. **Erst analysieren**: Suche systematisch nach Issues. Erstelle eine TaskList mit allen Findings.
2. **Dann fixen**: Arbeite jede Task ab. Bei jedem Fix:
   - Edit der Datei
   - Bei größeren Änderungen kurzer Kommentar warum
3. **Push am Ende**: Einen sauberen Commit mit Liste aller Änderungen, dann `git push`.
4. **Verifikation**: GitHub Actions checken, ob Deploy grün ist.
5. **Report**: Am Ende **kompakte Liste** was geändert wurde:
   - ✅ Was funktioniert jetzt korrekt
   - ⚠️ Was wurde gefunden + gefixt
   - 🔮 Was nicht gefixt werden konnte und warum (z.B. erfordert User-Entscheidung)

## Stilregeln

- **Nicht jede Mini-Anpassung melden** während der Arbeit — sammeln und am Ende zusammenfassen
- **Bei Mehrdeutigkeiten** (z.B. „welcher Mitarbeiter heißt jetzt wie?"): kurz nachfragen statt raten
- **Keine bestehenden Features kaputt machen** — wenn unsicher: lieber drinlassen und in den Report
- **Backwards-compatible** wenn möglich
- Output **unter 800 Wörter** am Ende

# Bauzeitenplan Archivita

Multi-User-Dashboard für die Baustelle Wilhelm-Binder-Str. 15, VS-Villingen.

- **Live**: https://bauplan.crossfit-hauptwerk.de
- **Hosting**: webgo SSD Business
- **Backend**: PHP 8.5 + MySQL
- **Auth**: Magic-Link via Email

## Setup

Siehe [SETUP.md](SETUP.md) für die komplette Einrichtungsanleitung.

## Rollen

| Rolle | Rechte |
|---|---|
| **admin** | Alles inkl. Nutzerverwaltung |
| **architekt** | Alles außer Nutzerverwaltung |
| **worker** | Status / Fortschritt / Notiz ändern |
| **viewer** | Nur lesen |

## Auto-Deploy

Jeder Push auf `main` wird automatisch per SFTP nach `/bauplan/` auf webgo deployed
(siehe `.github/workflows/deploy.yml`).

Die Datei `config.php` ist **nicht** im Repo (`.gitignore`) und liegt nur lokal
und auf dem Server.

-- =====================================================================
-- Bauzeitenplan Archivita — DB-Schema
-- Multi-User mit Rollen, Magic-Link-Auth, Audit-Log
-- MySQL 8.x, PHP 8.5
-- Import in phpMyAdmin: Datenbank `web111_db3` auswählen → "Importieren"
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';

-- ---------------------------------------------------------------------
-- USERS  (4 Rollen: admin, architekt, worker, viewer)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  role          ENUM('admin','architekt','worker','viewer') NOT NULL DEFAULT 'viewer',
  active        TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_active (role, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MAGIC_TOKENS  (Login-Tokens, 15 Min gültig, single-use)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS magic_tokens (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL UNIQUE,         -- SHA-256 vom Token
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  ip          VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mt_user (user_id),
  INDEX idx_mt_expires (expires_at),
  CONSTRAINT fk_mt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SESSIONS  (Cookie-Sessions, 30 Tage gültig)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
  id            CHAR(64) PRIMARY KEY,            -- SHA-256 vom Session-Cookie-Wert
  user_id       INT UNSIGNED NOT NULL,
  expires_at    DATETIME NOT NULL,
  ip            VARCHAR(45) NULL,
  user_agent    VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sess_user (user_id),
  INDEX idx_sess_expires (expires_at),
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- RATE_LIMIT  (max. 3 Login-Mails pro Mail pro Stunde gegen Spam)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limit (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key_str     VARCHAR(190) NOT NULL,              -- z.B. "login:user@x.de" oder "ip:1.2.3.4"
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rl_key_time (key_str, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- EINHEITEN  (Wohnungen / Büro / Gewerbe)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS einheiten (
  id            VARCHAR(20) PRIMARY KEY,           -- "T 4.01", "W 5.2"
  typ           VARCHAR(60) NOT NULL DEFAULT '',   -- "Studio", "2-Zimmer", "Maisonette"
  m2            DECIMAL(8,2) NOT NULL DEFAULT 0,
  phase         TINYINT NOT NULL DEFAULT 1,        -- 1..4
  og            VARCHAR(10) NOT NULL DEFAULT '',   -- "3.OG", "4.OG"
  bemerkung     TEXT NULL,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_einheiten_phase (phase, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TASK_SECTIONS  (Section-Header im Hauptzeitplan, z.B. "OHG Haustechnik")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS task_sections (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(190) NOT NULL,
  kfw         VARCHAR(10) NULL,                   -- "A", "B", "C" oder NULL
  phase       TINYINT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sec_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TASKS  (Hauptzeitplan-Aufgaben)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
  id           VARCHAR(40) PRIMARY KEY,            -- "T 4.01", "11.04", "13.01" — bestehende IDs übernommen
  section_id   INT UNSIGNED NULL,
  unit_id      VARCHAR(20) NULL,                   -- FK auf einheiten.id (optional)
  name         VARCHAR(255) NOT NULL,
  gewerk       VARCHAR(60) NULL,                   -- "Sanitär", "Elektro", ...
  firma        VARCHAR(120) NULL,
  status       ENUM('geplant','laufend','fertig','verzögert','priorität') NOT NULL DEFAULT 'geplant',
  progress     TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- 0..100
  bar_left     INT NOT NULL DEFAULT 0,              -- px-Position links
  bar_width    INT NOT NULL DEFAULT 0,              -- px-Breite
  kw_start     SMALLINT NULL,                       -- continuous KW (KW19/2026 = 1)
  kw_end       SMALLINT NULL,
  notiz        TEXT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  custom       TINYINT(1) NOT NULL DEFAULT 0,       -- 1 = User-Created, 0 = aus Original-HTML
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tasks_section (section_id),
  INDEX idx_tasks_unit (unit_id),
  INDEX idx_tasks_gewerk (gewerk),
  INDEX idx_tasks_status (status),
  CONSTRAINT fk_tasks_section FOREIGN KEY (section_id) REFERENCES task_sections(id) ON DELETE SET NULL,
  CONSTRAINT fk_tasks_unit FOREIGN KEY (unit_id) REFERENCES einheiten(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MITARBEITER  (Kapazitätsplanung)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mitarbeiter (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  stunden_wo  SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  ab_kw       SMALLINT NULL,                        -- continuous KW
  bis_kw      SMALLINT NULL,
  aktiv       TINYINT(1) NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mitarbeiter_gewerke (
  mitarbeiter_id  INT UNSIGNED NOT NULL,
  gewerk          VARCHAR(60) NOT NULL,
  PRIMARY KEY (mitarbeiter_id, gewerk),
  CONSTRAINT fk_mg_ma FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urlaub (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mitarbeiter_id  INT UNSIGNED NOT NULL,
  kw              SMALLINT NOT NULL,                 -- continuous KW
  year            SMALLINT NOT NULL,
  bemerkung       VARCHAR(120) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_urlaub (mitarbeiter_id, kw, year),
  CONSTRAINT fk_url_ma FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- BESTELLUNGEN
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bestellungen (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bezeichnung   VARCHAR(190) NOT NULL,
  lieferant     VARCHAR(120) NULL,
  status        ENUM('bestellt','geliefert','offen','storniert') NOT NULL DEFAULT 'offen',
  bestelldatum  DATE NULL,
  lieferdatum   DATE NULL,
  task_id       VARCHAR(40) NULL,
  betrag_netto  DECIMAL(10,2) NULL,
  notiz         TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bo_status (status),
  INDEX idx_bo_task (task_id),
  CONSTRAINT fk_bo_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- AUDIT_LOG  (Wer hat wann was geändert)
-- Aufbewahrung: konfigurierbar in config.php (AUDIT_RETENTION_DAYS)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,                   -- NULL = System-Aktion
  action      VARCHAR(60) NOT NULL,                -- z.B. "task.update", "user.login"
  entity      VARCHAR(40) NOT NULL,                -- z.B. "task", "user", "bestellung"
  entity_id   VARCHAR(40) NULL,                    -- z.B. die task-id
  payload     JSON NULL,                           -- {"old":{...},"new":{...}}
  ip          VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_entity (entity, entity_id),
  INDEX idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SETTINGS  (key-value, z.B. "last_migration", "schema_version")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  key_str     VARCHAR(80) PRIMARY KEY,
  value_str   TEXT NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (key_str, value_str) VALUES
  ('schema_version', '1'),
  ('migrated_from_localstorage', '0')
ON DUPLICATE KEY UPDATE value_str = VALUES(value_str);

-- ---------------------------------------------------------------------
-- SEED: erster Admin-User (Mail wird in config.php / setup.php gesetzt)
-- ---------------------------------------------------------------------
-- siehe setup.php — wird beim ersten Aufruf einmalig angelegt

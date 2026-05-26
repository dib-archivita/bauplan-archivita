-- =====================================================================
-- Migration 002 — Live-Sync Tabellen
-- Import in phpMyAdmin: DB web111_db3 → Importieren → diese Datei
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- OVERRIDES — Änderungen an BESTEHENDEN (HTML-)Elementen
-- Jede (entity_type, entity_key, field)-Kombi hat genau EINEN aktuellen Wert.
-- field='deleted' value='1'  → Element gilt als gelöscht.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS overrides (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type  ENUM('task','section','kfw') NOT NULL,
  entity_key   VARCHAR(120) NOT NULL,           -- data-tid / section-idx-N / kfw-idx-N
  field        VARCHAR(40)  NOT NULL,           -- status|name|firma|gewerk|bar_left|bar_width|notiz|deleted
  value        TEXT NULL,
  updated_by   INT UNSIGNED NULL,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_override (entity_type, entity_key, field),
  INDEX idx_ov_updated (updated_at),
  CONSTRAINT fk_ov_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CUSTOM_ITEMS — NEU angelegte Aufgaben / Bereiche (nicht im Basis-HTML)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS custom_items (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_type    ENUM('task','section') NOT NULL,
  client_id    VARCHAR(120) NOT NULL UNIQUE,    -- z.B. custom-1716...-1001
  parent_key   VARCHAR(120) NULL,               -- Section-Key (für task) / KFW-Key (für section)
  after_key    VARCHAR(120) NULL,               -- nach welchem Element einfügen (Reihenfolge)
  data         JSON NOT NULL,                   -- {name,status,gewerk,firma,bar_left,bar_width,notiz}
  deleted      TINYINT(1) NOT NULL DEFAULT 0,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ci_parent (parent_key),
  INDEX idx_ci_updated (updated_at),
  CONSTRAINT fk_ci_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync-Settings
INSERT INTO settings (key_str, value_str) VALUES ('sync_enabled', '1')
ON DUPLICATE KEY UPDATE value_str = VALUES(value_str);

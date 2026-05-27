-- =====================================================================
-- Migration 003 — Generischer Key-Value-Sync für Neben-Tabs
-- (Bestellungen, Budget, Kapazität, Einheiten, manuelle TODs)
-- Wird von api/sync.php auch automatisch angelegt (CREATE TABLE IF NOT EXISTS).
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS kv_state (
  k           VARCHAR(160) NOT NULL PRIMARY KEY,   -- localStorage-Key (z.B. bo-orders-v3, task-mh-<tid>)
  v           MEDIUMTEXT NULL,                      -- serialisierter Wert (JSON / HTML)
  updated_by  INT UNSIGNED NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kv_updated (updated_at),
  CONSTRAINT fk_kv_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

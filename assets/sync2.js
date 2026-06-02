/**
 * sync2.js — Live Multi-User Sync
 *
 *  - Beim Laden: holt alle overrides + custom_items aus /api/sync.php, wendet sie aufs DOM an
 *  - Bei lokalem Edit: pushOverride/pushCustom → POST an /api/sync.php
 *  - Polling (5s): holt fremde Änderungen seit letztem Sync → wendet sie an
 *
 *  Window-API:
 *    window.PlanSync.pushOverride(type, key, field, value)
 *    window.PlanSync.pushCustomAdd(itemType, clientId, parentKey, afterKey, data)
 *    window.PlanSync.pushCustomUpdate(clientId, data)
 *    window.PlanSync.pushCustomDelete(clientId)
 *    window.PlanSync.isApplyingRemote()   → true während Remote-Anwendung (Loops vermeiden)
 */
(function () {
  'use strict';

  const API = 'api/sync.php';
  const POLL_MS = 5000;

  // Status-Maps (gespiegelt vom Haupt-Script, inkl. neuer Stufen)
  const STATUS_LABELS = {
    'geplant':'—', 'vorbereitung':'Vorbereitung',
    'laufend':'laufend', 'begonnen':'begonnen',
    'fortschritt_25':'25 %', 'fortschritt_50':'50 %',
    'fortschritt_75':'75 %', 'fortschritt_90':'90 %',
    'abnahme':'Abnahme',
    'abgeschlossen':'✓ fertig', 'fertig':'✓ fertig',
    'pausiert':'⏸ Pause',
    'verzögert':'⚠ verzögert',
    'abgebrochen':'✕ abgebrochen',
    'priorität':'Priorität',
  };
  const STATUS_CSS = {
    'geplant':'status-planned','vorbereitung':'status-planned',
    'laufend':'status-wip','begonnen':'status-wip',
    'fortschritt_25':'status-wip','fortschritt_50':'status-wip',
    'fortschritt_75':'status-wip','fortschritt_90':'status-wip',
    'abnahme':'status-wip',
    'abgeschlossen':'status-done','fertig':'status-done',
    'pausiert':'status-delayed','verzögert':'status-delayed',
    'abgebrochen':'status-cancelled',
    'priorität':'status-prio',
  };

  let lastSync = null;
  let remoteUntil = 0;          // applyingRemote bis zu diesem Zeitpunkt (ms)
  let pollTimer = null;

  function beginRemote() { remoteUntil = Date.now() + 400; }  // deckt async Observer ab
  function isApplyingRemoteNow() { return Date.now() < remoteUntil; }

  // ── Helpers ────────────────────────────────────────────────────────
  function rowByTid(tid) {
    return document.querySelector('tr.task-row[data-tid="' + cssEscape(tid) + '"]');
  }
  function cssEscape(s) {
    return (window.CSS && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^\w-]/g, '\\$&');
  }
  function sectionByKey(key) {
    // key = "section-idx-N"
    const m = /section-idx-(\d+)/.exec(key);
    if (!m) return null;
    const idx = parseInt(m[1], 10);
    const tb = document.querySelector('#main-gantt tbody');
    return tb ? tb.children[idx] : null;
  }
  function kfwByKey(key) {
    const m = /kfw-idx-(\d+)/.exec(key);
    if (!m) return null;
    const idx = parseInt(m[1], 10);
    const tb = document.querySelector('#main-gantt tbody');
    return tb ? tb.children[idx] : null;
  }

  // Gewerk inkl. Farbe setzen (nutzt window.applyGewerk + window.GEWERKE aus index.php)
  function applyGewerkStyled(row, value) {
    const td = row.children[2];
    let span = td && (td.querySelector('.gewerk-badge') || td.querySelector('span'));
    if (td && !span) {
      span = document.createElement('span');
      span.className = 'gewerk-badge';
      td.appendChild(span);
    }
    const list = window.GEWERKE || [];
    const g = list.find(x => x.name === value) || { name: value, bg: '#f1f5f9', fg: '#64748b' };
    if (window.applyGewerk && span) {
      window.applyGewerk(span, row, g);   // setzt Text + Farbe + data-gewerk (Echo via isApplyingRemote geblockt)
    } else if (span) {
      span.textContent = value || '+ Gewerk';
      row.setAttribute('data-gewerk', value || '');
    }
  }

  // ── KV (Neben-Tabs) anwenden ──────────────────────────────────────
  function applyKV(item) {
    if (!item || item.k == null) return;
    beginRemote();
    try {
      const cur = localStorage.getItem(item.k);
      if (cur === item.v) return;                 // nichts geändert
      if (item.v == null) localStorage.removeItem(item.k);
      else localStorage.setItem(item.k, item.v);
      // Feature-spezifisches Re-Render (in index.php definiert)
      if (typeof window.__applyKVUpdate === 'function') {
        window.__applyKVUpdate(item.k, item.v);
      }
    } finally { /* beginRemote-Fenster läuft aus */ }
  }

  // ── Apply einzelne Override aufs DOM ──────────────────────────────
  function applyOverride(ov) {
    beginRemote();
    try {
      if (ov.entity_type === 'task') {
        const row = rowByTid(ov.entity_key);
        if (!row) return;
        switch (ov.field) {
          case 'status': {
            row.setAttribute('data-status', ov.value);
            const badge = row.querySelector('.status-badge');
            if (badge) {
              badge.textContent = STATUS_LABELS[ov.value] || ov.value;
              badge.className = 'status-badge ' + (STATUS_CSS[ov.value] || 'status-planned');
            }
            break;
          }
          case 'name': {
            const cell = row.querySelector('.task-name-cell');
            if (cell) setCellText(cell, ov.value);
            break;
          }
          case 'firma': {
            const cell = row.children[3];
            if (cell) setCellText(cell, ov.value);
            break;
          }
          case 'gewerk': {
            applyGewerkStyled(row, ov.value);
            break;
          }
          case 'bar_left': {
            const bar = row.querySelector('.gantt-bar');
            if (bar) bar.style.left = parseInt(ov.value, 10) + 'px';
            break;
          }
          case 'bar_width': {
            const bar = row.querySelector('.gantt-bar');
            if (bar) bar.style.width = parseInt(ov.value, 10) + 'px';
            break;
          }
          case 'notiz': {
            row.setAttribute('data-notiz', ov.value || '');
            break;
          }
          case 'deadline': {
            const tid = ov.entity_key;
            if (ov.value) localStorage.setItem('bar-deadline-' + tid, ov.value);
            else localStorage.removeItem('bar-deadline-' + tid);
            break;
          }
          case 'deleted': {
            if (ov.value === '1') row.remove();
            break;
          }
        }
      } else if (ov.entity_type === 'section') {
        const sec = sectionByKey(ov.entity_key);
        if (!sec) return;
        if (ov.field === 'name') {
          const et = sec.querySelector('.editable-text');
          if (et) et.textContent = ov.value;
        } else if (ov.field === 'deleted' && ov.value === '1') {
          // Section + folgende task-rows entfernen
          let next = sec.nextElementSibling;
          while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
            const r = next; next = next.nextElementSibling; r.remove();
          }
          sec.remove();
        }
      } else if (ov.entity_type === 'kfw') {
        const kfw = kfwByKey(ov.entity_key);
        if (!kfw) return;
        if (ov.field === 'name') {
          const et = kfw.querySelector('.editable-text');
          if (et) et.textContent = ov.value;
        }
      }
    } finally {
      
    }
  }

  // Setzt Text einer Zelle ohne die Action-Buttons (✕, 🕐) zu verlieren
  function sanitizeText(text) {
    // Entferne versehentlich mitgespeicherte Button-Symbole (🕐 / ✕)
    return String(text == null ? '' : text)
      .replace(/[🕐✕]/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function setCellText(cell, text) {
    text = sanitizeText(text);
    const buttons = Array.from(cell.querySelectorAll(':scope > button, :scope > .editable-text'));
    // Falls editable-text-span vorhanden (sections), nur dessen Text setzen
    const et = cell.querySelector(':scope > .editable-text');
    if (et) { et.textContent = text; return; }
    // Sonst: text-Nodes ersetzen, Buttons behalten
    Array.from(cell.childNodes).forEach(n => {
      if (n.nodeType === Node.TEXT_NODE) n.remove();
    });
    cell.insertBefore(document.createTextNode(text), cell.firstChild);
  }

  // ── Apply custom item (neue Aufgabe / Section) ────────────────────
  function applyCustom(item) {
    beginRemote();
    try {
      const existing = document.querySelector('[data-tid="' + cssEscape(item.client_id) + '"]')
                    || document.querySelector('[data-client-id="' + cssEscape(item.client_id) + '"]');
      if (item.deleted) {
        if (existing) existing.remove();
        return;
      }
      if (existing) {
        // Update vorhandenes
        if (item.item_type === 'task') updateCustomTaskRow(existing, item.data);
        return;
      }
      if (item.item_type === 'task') {
        const row = buildCustomTaskRow(item);
        insertCustomTask(row, item);
      } else if (item.item_type === 'section') {
        const row = buildCustomSectionRow(item);
        insertCustomSection(row, item);
      }
    } finally {
      
    }
  }

  function buildCustomTaskRow(item) {
    const d = item.data || {};
    const row = document.createElement('tr');
    row.className = 'task-row';
    row.setAttribute('data-tid', item.client_id);
    row.setAttribute('data-client-id', item.client_id);
    row.setAttribute('data-status', d.status || 'geplant');
    row.setAttribute('data-gewerk', d.gewerk || '');
    row.setAttribute('data-custom', '1');
    const st = d.status || 'geplant';
    row.innerHTML =
      '<td class="task-name-cell">' + esc(sanitizeText(d.name) || 'Neue Aufgabe') + '</td>' +
      '<td><span class="status-badge ' + (STATUS_CSS[st]||'status-planned') + '">' + (STATUS_LABELS[st]||'—') + '</span></td>' +
      '<td style="padding:2px 5px;font-size:10px"><span class="gewerk-badge" style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;background:#f1f5f9;color:#64748b;border:1px solid #64748b40">' + esc(sanitizeText(d.gewerk) || '+ Gewerk') + '</span></td>' +
      '<td style="padding:2px 5px;font-size:10px;color:#64748b">' + esc(sanitizeText(d.firma) || '—') + '</td>' +
      '<td><div class="gantt-row-inner" style="width:3600px">' +
        (d.bar_width ? '<div class="gantt-bar ' + (STATUS_CSS[st]||'status-planned') + '" style="left:' + (d.bar_left||0) + 'px;width:' + d.bar_width + 'px"></div>' : '') +
      '</div></td>';
    return row;
  }
  function updateCustomTaskRow(row, d) {
    if (d.name != null) setCellText(row.querySelector('.task-name-cell'), d.name);
    if (d.status != null) {
      row.setAttribute('data-status', d.status);
      const badge = row.querySelector('.status-badge');
      if (badge) { badge.textContent = STATUS_LABELS[d.status]||d.status; badge.className = 'status-badge ' + (STATUS_CSS[d.status]||'status-planned'); }
    }
    if (d.firma != null && row.children[3]) setCellText(row.children[3], d.firma);
    if (d.gewerk != null) applyGewerkStyled(row, d.gewerk);
    if (d.bar_left != null || d.bar_width != null) {
      const bar = row.querySelector('.gantt-bar');
      if (bar) {
        if (d.bar_left != null) bar.style.left = parseInt(d.bar_left, 10) + 'px';
        if (d.bar_width != null) bar.style.width = parseInt(d.bar_width, 10) + 'px';
      }
    }
    if (d.deadline != null) {
      const tid = row.getAttribute('data-tid');
      if (d.deadline) localStorage.setItem('bar-deadline-' + tid, d.deadline);
      else localStorage.removeItem('bar-deadline-' + tid);
    }
  }
  function insertCustomTask(row, item) {
    let anchor = null;
    if (item.after_key) anchor = rowByTid(item.after_key) || sectionByKey(item.after_key);
    if (!anchor && item.parent_key) anchor = sectionByKey(item.parent_key);
    if (anchor) anchor.insertAdjacentElement('afterend', row);
    else {
      const tb = document.querySelector('#main-gantt tbody');
      if (tb) tb.appendChild(row);
    }
  }
  function buildCustomSectionRow(item) {
    const d = item.data || {};
    const row = document.createElement('tr');
    row.className = 'section-row';
    row.setAttribute('data-client-id', item.client_id);
    row.innerHTML =
      '<td class="section-name" colspan="4"><span class="section-arrow">▶</span> ' + esc(d.name || 'Neuer Bereich') +
      ' <span class="progress-pill">0/0 ✓</span></td>' +
      '<td><div class="gantt-row-inner" style="width:3600px"></div></td>';
    return row;
  }
  function insertCustomSection(row, item) {
    let anchor = item.parent_key ? kfwByKey(item.parent_key) : null;
    if (anchor) anchor.insertAdjacentElement('afterend', row);
    else {
      const tb = document.querySelector('#main-gantt tbody');
      if (tb) tb.appendChild(row);
    }
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // ── Sync-Indikator ─────────────────────────────────────────────────
  function setIndicator(state, info) {
    let el = document.getElementById('sync-indicator');
    if (!el) {
      el = document.createElement('div');
      el.id = 'sync-indicator';
      el.style.cssText = 'position:fixed;bottom:24px;left:24px;z-index:9997;font-family:Inter,sans-serif;font-size:11px;font-weight:600;display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #e2e8f0;padding:5px 10px;border-radius:999px;box-shadow:0 2px 8px rgba(15,23,42,.08);color:#64748b';
      el.innerHTML = '<span class="si-dot" style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block"></span><span class="si-text">Sync</span>';
      document.body.appendChild(el);
    }
    const dot = el.querySelector('.si-dot');
    const txt = el.querySelector('.si-text');
    if (state === 'ok')   { dot.style.background = '#22c55e'; txt.textContent = info || 'Synchron'; }
    if (state === 'sync') { dot.style.background = '#2563eb'; txt.textContent = 'Synchronisiere…'; }
    if (state === 'error'){ dot.style.background = '#ef4444'; txt.textContent = info || 'Sync-Fehler'; }
  }

  // ── Fetch + Apply ──────────────────────────────────────────────────
  async function fetchAndApply(initial) {
    setIndicator('sync');
    try {
      const url = (initial || !lastSync) ? API : (API + '?since=' + encodeURIComponent(lastSync));
      const res = await fetch(url, { credentials: 'same-origin' });
      if (res.status === 401) { location.href = 'login.html'; return; }
      if (!res.ok) {
        const txt = await res.text();
        console.error('[sync] GET fehlgeschlagen', res.status, txt.slice(0,200));
        setIndicator('error', 'Sync-Fehler ' + res.status);
        return;
      }
      const data = await res.json();
      lastSync = data.server_time;
      const nOv = (data.overrides || []).length;
      const nCi = (data.custom || []).length;
      const nKv = (data.kv || []).length;
      (data.overrides || []).forEach(applyOverride);
      (data.custom || []).forEach(applyCustom);
      (data.kv || []).forEach(applyKV);
      if (window.__recountStats) window.__recountStats();
      setIndicator('ok', nOv + nCi + nKv > 0 ? `↻ ${nOv+nCi+nKv} Änderung(en)` : 'Synchron');
    } catch (e) {
      console.error('[sync] GET exception', e);
      setIndicator('error', 'Netzwerk-Fehler');
    }
  }

  // ── Push ───────────────────────────────────────────────────────────
  async function post(body) {
    setIndicator('sync');
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(body),
      });
      if (res.status === 401) { location.href = 'login.html'; return null; }
      const data = await res.json().catch(() => ({}));
      if (data && data.server_time) lastSync = data.server_time;
      if (!res.ok) {
        console.error('[sync] POST fehlgeschlagen', res.status, data);
        setIndicator('error', (data && data.error) || ('Fehler ' + res.status));
        showToast((data && data.error) || ('Speichern fehlgeschlagen (' + res.status + ')'));
      } else {
        setIndicator('ok', 'Gespeichert');
      }
      return data;
    } catch (e) {
      console.error('[sync] POST exception', e);
      setIndicator('error', 'Netzwerk-Fehler');
      return null;
    }
  }

  function showToast(msg) {
    let t = document.getElementById('sync-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'sync-toast';
      t.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#7f1d1d;color:#fff;padding:8px 14px;border-radius:8px;font-family:Inter,sans-serif;font-size:12px;z-index:99999;box-shadow:0 6px 20px rgba(0,0,0,0.2)';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => { t.style.opacity = '0'; }, 3000);
  }

  // Säubert Text-Felder eines Daten-Objekts (name/firma/gewerk) von Button-Symbolen
  const TEXT_FIELDS = ['name', 'firma', 'gewerk'];
  function sanitizeData(data) {
    if (!data || typeof data !== 'object') return data;
    const out = Object.assign({}, data);
    TEXT_FIELDS.forEach(f => { if (typeof out[f] === 'string') out[f] = sanitizeText(out[f]); });
    return out;
  }

  // ── Public API ─────────────────────────────────────────────────────
  window.PlanSync = {
    isApplyingRemote() { return isApplyingRemoteNow(); },
    pushOverride(type, key, field, value) {
      if (isApplyingRemoteNow()) return;
      if (TEXT_FIELDS.indexOf(field) !== -1 && typeof value === 'string') value = sanitizeText(value);
      post({ op: 'override', entity_type: type, entity_key: key, field, value });
    },
    pushCustomAdd(itemType, clientId, parentKey, afterKey, data) {
      if (isApplyingRemoteNow()) return;
      post({ op: 'custom_add', item_type: itemType, client_id: clientId, parent_key: parentKey, after_key: afterKey, data: sanitizeData(data) });
    },
    pushCustomUpdate(clientId, data) {
      if (isApplyingRemoteNow()) return;
      post({ op: 'custom_update', client_id: clientId, data: sanitizeData(data) });
    },
    pushCustomDelete(clientId) {
      if (isApplyingRemoteNow()) return;
      post({ op: 'custom_delete', client_id: clientId });
    },
    pushKV(key, value) {
      if (isApplyingRemoteNow()) return;
      post({ op: 'kv_set', key: key, value: value });
    },
    forcePoll() { fetchAndApply(false); },
  };

  // Einmalige DB-Bereinigung verirrter Button-Symbole (nur Admin, einmal pro Browser)
  async function cleanupGlyphsOnce() {
    if (!document.body.classList.contains('role-admin')) return;
    if (localStorage.getItem('glyph-cleanup-done') === '1') return;
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ op: 'cleanup_glyphs' }),
      });
      if (res.ok) {
        const d = await res.json().catch(() => ({}));
        localStorage.setItem('glyph-cleanup-done', '1');
        if ((d.overrides_fixed || 0) + (d.custom_fixed || 0) > 0) {
          console.log('[sync] DB bereinigt:', d.overrides_fixed, 'overrides,', d.custom_fixed, 'custom');
          lastSync = null;            // erzwinge Voll-Refresh mit sauberen Werten
          fetchAndApply(true);
        }
      }
    } catch (e) { /* still */ }
  }

  // ── Init ───────────────────────────────────────────────────────────
  function init() {
    fetchAndApply(true).then(() => {
      cleanupGlyphsOnce();
      pollTimer = setInterval(() => fetchAndApply(false), POLL_MS);
    });
    // Bei Tab-Fokus sofort syncen
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) fetchAndApply(false);
    });
  }

  // Warten bis Auth + Haupt-Dashboard da sind (sync.js setzt user-bar)
  function waitForReady(tries) {
    tries = tries || 0;
    if (document.getElementById('main-gantt') || tries > 40) { init(); return; }
    setTimeout(() => waitForReady(tries + 1), 150);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => waitForReady());
  } else {
    waitForReady();
  }
})();

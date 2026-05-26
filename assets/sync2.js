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

  // Status-Maps (gespiegelt vom Haupt-Script)
  const STATUS_LABELS = {
    'geplant':'—', 'laufend':'laufend', 'abgeschlossen':'✓',
    'fortschritt_50':'50%', 'fortschritt_75':'75%', 'fortschritt_90':'90%',
    'verzögert':'⚠', 'priorität':'Priorität', 'fertig':'✓',
  };
  const STATUS_CSS = {
    'geplant':'status-planned','laufend':'status-wip','abgeschlossen':'status-done',
    'fortschritt_50':'status-wip','fortschritt_75':'status-wip','fortschritt_90':'status-wip',
    'verzögert':'status-delayed','priorität':'status-prio','fertig':'status-done',
  };

  let lastSync = null;
  let applyingRemote = false;
  let pollTimer = null;

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

  // ── Apply einzelne Override aufs DOM ──────────────────────────────
  function applyOverride(ov) {
    applyingRemote = true;
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
            const badge = row.querySelector('.gewerk-badge') || row.children[2]?.querySelector('span');
            if (badge) badge.textContent = ov.value;
            row.setAttribute('data-gewerk', ov.value);
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
      applyingRemote = false;
    }
  }

  // Setzt Text einer Zelle ohne die Action-Buttons (✕, 🕐) zu verlieren
  function setCellText(cell, text) {
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
    applyingRemote = true;
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
      applyingRemote = false;
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
      '<td class="task-name-cell">' + esc(d.name || 'Neue Aufgabe') + '</td>' +
      '<td><span class="status-badge ' + (STATUS_CSS[st]||'status-planned') + '">' + (STATUS_LABELS[st]||'—') + '</span></td>' +
      '<td style="padding:2px 5px;font-size:10px"><span class="gewerk-badge" style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;background:#f1f5f9;color:#64748b;border:1px solid #64748b40">' + esc(d.gewerk || '+ Gewerk') + '</span></td>' +
      '<td style="padding:2px 5px;font-size:10px;color:#64748b">' + esc(d.firma || '—') + '</td>' +
      '<td><div class="gantt-row-inner" style="width:3768px">' +
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
      '<td><div class="gantt-row-inner" style="width:3768px"></div></td>';
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

  // ── Fetch + Apply ──────────────────────────────────────────────────
  async function fetchAndApply(initial) {
    try {
      const url = (initial || !lastSync) ? API : (API + '?since=' + encodeURIComponent(lastSync));
      const res = await fetch(url, { credentials: 'same-origin' });
      if (res.status === 401) { location.href = 'login.html'; return; }
      if (!res.ok) return;
      const data = await res.json();
      lastSync = data.server_time;
      (data.overrides || []).forEach(applyOverride);
      (data.custom || []).forEach(applyCustom);
      // Nach Anwendung: Counter / Stats neu (falls section-edit.js geladen)
      if (window.__recountStats) window.__recountStats();
    } catch (e) { /* still */ }
  }

  // ── Push ───────────────────────────────────────────────────────────
  async function post(body) {
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(body),
      });
      if (res.status === 401) { location.href = 'login.html'; return null; }
      const data = await res.json();
      if (data && data.server_time) lastSync = data.server_time;
      if (!res.ok && data && data.error) showToast(data.error);
      return data;
    } catch (e) { return null; }
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

  // ── Public API ─────────────────────────────────────────────────────
  window.PlanSync = {
    isApplyingRemote() { return applyingRemote; },
    pushOverride(type, key, field, value) {
      if (applyingRemote) return;
      post({ op: 'override', entity_type: type, entity_key: key, field, value });
    },
    pushCustomAdd(itemType, clientId, parentKey, afterKey, data) {
      if (applyingRemote) return;
      post({ op: 'custom_add', item_type: itemType, client_id: clientId, parent_key: parentKey, after_key: afterKey, data });
    },
    pushCustomUpdate(clientId, data) {
      if (applyingRemote) return;
      post({ op: 'custom_update', client_id: clientId, data });
    },
    pushCustomDelete(clientId) {
      if (applyingRemote) return;
      post({ op: 'custom_delete', client_id: clientId });
    },
    forcePoll() { fetchAndApply(false); },
  };

  // ── Init ───────────────────────────────────────────────────────────
  function init() {
    fetchAndApply(true).then(() => {
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

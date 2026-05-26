/**
 * changes.js — Änderungs-Tracking pro Aufgabe (Zeitstempel + Bearbeiter)
 *
 *  Speichert in localStorage:
 *    task-history-v1: { "<tid>": [{ts, user, field, old, new}, ...] }
 *
 *  Erkennt automatisch:
 *    - Status-Wechsel (data-status Attribut)
 *    - Text-Edits in task-name-cell, task-firma-cell (4. td)
 *    - Section-Name und KFW-Banner Edits
 *
 *  Reicht die Info nach außen via window.TaskHistory.get(tid).
 *  Letzte Änderung wird im Bar-Tooltip angezeigt (bar-labels.js liest darauf zu).
 *
 *  TODO später: an /api/tasks.php PATCH andocken (audit_log liefert die
 *  Server-seitige Wahrheit). Aktuell ist localStorage das Primary-Storage.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'task-history-v1';
  const MAX_PER_TASK = 100;

  function load() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch (e) { return {}; }
  }
  function save(h) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(h)); }
    catch (e) { console.warn('history save failed', e); }
  }
  let history = load();

  function currentUser() {
    // Lese aus dem User-Bar (sync.js setzt das)
    const el = document.querySelector('#user-bar .ub-name');
    if (el && el.textContent.trim()) return el.textContent.trim();
    // Fallback: body role-* Klasse
    if (document.body.classList.contains('role-admin')) return 'Admin';
    if (document.body.classList.contains('role-architekt')) return 'Architekt';
    if (document.body.classList.contains('role-worker')) return 'Worker';
    if (document.body.classList.contains('role-viewer')) return 'Viewer';
    return 'Unbekannt';
  }

  function log(tid, field, oldVal, newVal) {
    if (!tid) return;
    if (oldVal === newVal) return;
    if (!history[tid]) history[tid] = [];
    history[tid].push({
      ts: Date.now(),
      user: currentUser(),
      field,
      old: oldVal != null ? String(oldVal).slice(0, 200) : '',
      new: newVal != null ? String(newVal).slice(0, 200) : '',
    });
    if (history[tid].length > MAX_PER_TASK) {
      history[tid] = history[tid].slice(-MAX_PER_TASK);
    }
    save(history);
  }

  // ── Status-Änderungen via MutationObserver ───────────────────────
  const lastStatus = new WeakMap();
  function initStatusObserver() {
    document.querySelectorAll('tr.task-row').forEach((row) => {
      lastStatus.set(row, row.getAttribute('data-status') || '');
    });
    const obs = new MutationObserver((muts) => {
      for (const m of muts) {
        if (m.type !== 'attributes' || m.attributeName !== 'data-status') continue;
        const row = m.target;
        const tid = row.getAttribute('data-tid');
        const newVal = row.getAttribute('data-status') || '';
        const oldVal = lastStatus.get(row) ?? '';
        if (newVal !== oldVal) {
          log(tid, 'Status', oldVal, newVal);
          lastStatus.set(row, newVal);
          // → Backend-Sync (nicht bei Remote-Anwendung)
          if (window.PlanSync && !window.PlanSync.isApplyingRemote()) {
            window.PlanSync.pushOverride('task', tid, 'status', newVal);
          }
        }
      }
    });
    document.querySelectorAll('tr.task-row').forEach((row) => {
      obs.observe(row, { attributes: true, attributeFilter: ['data-status'] });
    });
    // Falls neue Rows später hinzukommen → erneut beobachten
    const addObs = new MutationObserver((muts) => {
      for (const m of muts) {
        for (const n of m.addedNodes) {
          if (n.nodeType !== 1) continue;
          if (n.matches && n.matches('tr.task-row')) {
            lastStatus.set(n, n.getAttribute('data-status') || '');
            obs.observe(n, { attributes: true, attributeFilter: ['data-status'] });
          }
          if (n.querySelectorAll) {
            n.querySelectorAll('tr.task-row').forEach((r) => {
              lastStatus.set(r, r.getAttribute('data-status') || '');
              obs.observe(r, { attributes: true, attributeFilter: ['data-status'] });
            });
          }
        }
      }
    });
    addObs.observe(document.body, { childList: true, subtree: true });
  }

  // ── Text-Edits via Capture-Phase focus/blur ───────────────────────
  const PRE = '__changes_pre';
  function initTextObserver() {
    document.addEventListener('focusin', (e) => {
      const t = e.target;
      if (!t || !t.isContentEditable) return;
      t[PRE] = (t.textContent || '').replace(/✕\s*$/, '').trim();
    }, true);
    document.addEventListener('focusout', (e) => {
      const t = e.target;
      if (!t || !(PRE in t)) return;
      const newVal = (t.textContent || '').replace(/✕\s*$/, '').trim();
      const oldVal = t[PRE];
      delete t[PRE];
      if (newVal === oldVal) return;
      // Was wurde geändert?
      const row = t.closest && t.closest('tr.task-row');
      const sectionRow = t.closest && t.closest('tr.section-row');
      const kfwRow = t.closest && t.closest('tr.kfw-header-row');
      const syncable = window.PlanSync && !window.PlanSync.isApplyingRemote();
      if (row) {
        const tid = row.getAttribute('data-tid');
        let field = 'Text', syncField = 'name';
        if (t.classList.contains('task-name-cell') || t === row.children[0]) { field = 'Aufgabe'; syncField = 'name'; }
        else if (t.classList.contains('task-firma-cell') || t === row.children[3]) { field = 'Firma'; syncField = 'firma'; }
        log(tid, field, oldVal, newVal);
        if (syncable) {
          // Custom-Tasks via custom_update, Basis-Tasks via override
          if (row.getAttribute('data-custom') === '1' || row.getAttribute('data-client-id')) {
            const cid = row.getAttribute('data-client-id') || tid;
            window.PlanSync.pushCustomUpdate(cid, { [syncField]: newVal });
          } else {
            window.PlanSync.pushOverride('task', tid, syncField, newVal);
          }
        }
      } else if (sectionRow) {
        const idx = Array.from(sectionRow.parentNode.children).indexOf(sectionRow);
        log('section-idx-' + idx, 'Abschnitt', oldVal, newVal);
        if (syncable) window.PlanSync.pushOverride('section', 'section-idx-' + idx, 'name', newVal);
      } else if (kfwRow) {
        const idx = Array.from(kfwRow.parentNode.children).indexOf(kfwRow);
        log('kfw-idx-' + idx, 'KfW-Bereich', oldVal, newVal);
        if (syncable) window.PlanSync.pushOverride('kfw', 'kfw-idx-' + idx, 'name', newVal);
      }
    }, true);
  }

  // ── Public API ────────────────────────────────────────────────────
  window.TaskHistory = {
    get(tid) {
      return (history[tid] || []).slice();
    },
    getLast(tid) {
      const arr = history[tid] || [];
      return arr.length ? arr[arr.length - 1] : null;
    },
    all() {
      return JSON.parse(JSON.stringify(history));
    },
    clear() {
      history = {};
      save(history);
    },
    // Formatter für UI
    formatTimestamp(ts) {
      const d = new Date(ts);
      const now = new Date();
      const diffMin = Math.round((now - d) / 60000);
      if (diffMin < 1) return 'gerade eben';
      if (diffMin < 60) return `vor ${diffMin} Min`;
      const diffH = Math.round(diffMin / 60);
      if (diffH < 24) return `vor ${diffH} Std`;
      const diffD = Math.round(diffH / 24);
      if (diffD < 7) return `vor ${diffD} Tag${diffD === 1 ? '' : 'en'}`;
      // Älter: konkretes Datum
      return d.toLocaleString('de-DE', {
        day: '2-digit', month: '2-digit', year: '2-digit',
        hour: '2-digit', minute: '2-digit'
      });
    },
  };

  // ── Init ──────────────────────────────────────────────────────────
  function init() {
    initStatusObserver();
    initTextObserver();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

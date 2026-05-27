/**
 * sync.js — verbindet das bestehende Dashboard mit dem PHP-Backend
 *
 * Eingebunden via <script src="assets/sync.js"></script> AM ENDE von index.html.
 *
 * Aufgaben:
 *  - Auth-Check beim Laden (sonst → login.html)
 *  - Initial-Load des kompletten State aus /api/state.php
 *  - localStorage-Migration beim ersten Login des Admin (einmalig)
 *  - Polling alle 5 Sek: holt Änderungen seit letztem Sync
 *  - Edit-Hooks: bei Status-/Name-/Firma-/Gewerk-/Bar-Änderungen → PATCH zum Server
 *  - Rollen-basiertes UI-Disabling (Worker/Viewer können vieles nicht klicken)
 *  - Logout-Button + Audit-Banner
 */
(function() {
  'use strict';

  const API = {
    me:     'api/me.php',
    state:  'api/state.php',
    tasks:  'api/tasks.php',
    users:  'api/users.php',
    audit:  'api/audit.php',
    logout: 'api/logout.php',
  };

  // ── State ──────────────────────────────────────────────────────────
  const State = {
    user: null,
    lastSync: null,
    polling: null,
    POLL_MS: 5000,
  };

  // ── Init ───────────────────────────────────────────────────────────
  async function init() {
    try {
      const meRes = await fetch(API.me, { credentials: 'same-origin' });
      if (meRes.status === 401) { redirectToLogin(); return; }
      if (!meRes.ok) { showError('Server-Fehler beim Login-Check.'); return; }
      State.user = await meRes.json();
    } catch (e) {
      showError('Server nicht erreichbar.');
      return;
    }

    injectUserBar();
    applyRoleRestrictions();

    // HINWEIS: State-Polling + Edit-Hooks + Migration sind DEAKTIVIERT.
    // Der Live-Sync läuft jetzt komplett über sync2.js (Overlay-Sync via /api/sync.php).
    // Das alte /api/state.php + /api/tasks.php-Patching würde nur Konflikte + 404s erzeugen.
    // (Funktionen bleiben im Code für evtl. spätere Nutzung, werden aber nicht aufgerufen.)
  }

  // ── User-Bar oben rechts ───────────────────────────────────────────
  function injectUserBar() {
    const u = State.user;
    const bar = document.createElement('div');
    bar.id = 'user-bar';
    bar.innerHTML = `
      <div class="ub-inner">
        <span class="ub-name">${escapeHtml(u.name)}</span>
        <span class="ub-role role-${u.role}">${roleLabel(u.role)}</span>
        <button class="ub-logout" id="ub-logout">Logout</button>
      </div>`;
    document.body.appendChild(bar);

    const style = document.createElement('style');
    style.textContent = `
      #user-bar { position: fixed; top: 12px; right: 16px; z-index: 9999;
        font-family: 'Inter', sans-serif; font-size: 12px; }
      .ub-inner { background: #fff; border: 1px solid #e8e9ed; border-radius: 999px;
        padding: 6px 6px 6px 14px; display: flex; align-items: center; gap: 10px;
        box-shadow: 0 2px 10px rgba(15,23,42,.08); }
      .ub-name { font-weight: 700; color: #1e293b; }
      .ub-role { padding: 2px 9px; border-radius: 999px; font-size: 10px; font-weight: 700;
        letter-spacing: 0.04em; text-transform: uppercase; }
      .ub-role.role-admin { background: #fee2e2; color: #b91c1c; }
      .ub-role.role-architekt { background: #dbeafe; color: #1d4ed8; }
      .ub-role.role-worker { background: #fef3c7; color: #b45309; }
      .ub-role.role-viewer { background: #f1f5f9; color: #64748b; }
      .ub-logout { background: #1e293b; color: #fff; border: none; padding: 5px 13px;
        border-radius: 999px; font-size: 11px; font-weight: 600; cursor: pointer;
        font-family: inherit; }
      .ub-logout:hover { background: #0f172a; }

      /* Read-only Mode (Viewer / Worker bei bestimmten Feldern) */
      body.role-viewer [contenteditable="true"],
      body.role-worker .editable-architekt-only {
        background: transparent !important;
        cursor: default !important;
      }
      body.role-viewer [contenteditable="true"],
      body.role-worker .editable-architekt-only[contenteditable="true"] {
        pointer-events: none;
      }
      body.role-viewer button:not(#ub-logout):not(.viewer-allowed),
      body.role-viewer .editable,
      body.role-viewer .gantt-bar { pointer-events: none; }
    `;
    document.head.appendChild(style);

    document.getElementById('ub-logout').addEventListener('click', logout);
    document.body.classList.add('role-' + u.role);
  }

  function roleLabel(r) {
    return { admin: 'Admin', architekt: 'Architekt', worker: 'Worker', viewer: 'Viewer' }[r] || r;
  }

  // ── Rollen-basiertes UI-Disabling ──────────────────────────────────
  function applyRoleRestrictions() {
    const role = State.user.role;
    if (role === 'admin') return;  // alles erlaubt
    if (role === 'architekt') return;  // alles außer Nutzerverwaltung (UI-seitig per tab-versteck)

    // worker + viewer: contenteditable abschalten außer status-relevant
    if (role === 'worker' || role === 'viewer') {
      document.querySelectorAll('[contenteditable="true"]').forEach(el => {
        if (!el.classList.contains('worker-editable')) {
          el.setAttribute('contenteditable', 'false');
        }
      });
    }
  }

  // ── State-Sync ─────────────────────────────────────────────────────
  async function syncState(initial) {
    try {
      const url = initial ? API.state : `${API.state}?since=${encodeURIComponent(State.lastSync)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      if (res.status === 401) { redirectToLogin(); return; }
      if (!res.ok) return;
      const data = await res.json();
      State.lastSync = data.server_time;

      // Audit-Banner zeigen
      if (data.audit_warning) showAuditBanner(data.audit_warning);

      // Daten ins DOM patchen (nur bei initial = true voll, sonst nur Diff)
      if (initial) {
        applyFullState(data);
      } else {
        applyPartialState(data);
      }
    } catch (e) {
      // Stille Polling-Fehler (z.B. Netzwerk weg) — versuche es beim nächsten Tick
    }
  }

  function applyFullState(data) {
    // Im ersten Schritt: nur Status/Bemerkungen aus DB auf bestehende task-rows mappen.
    // (Full Rebuild des Gantts kommt mit dem Migrations-Script.)
    if (data.tasks) {
      data.tasks.forEach(t => updateTaskRow(t));
    }
    // Bestellungen + Mitarbeiter + Urlaub kommen in v2 dran.
  }

  function applyPartialState(data) {
    if (data.tasks && data.tasks.length) {
      data.tasks.forEach(t => updateTaskRow(t));
    }
  }

  function updateTaskRow(t) {
    const row = document.querySelector(`tr[data-tid="${cssEscape(t.id)}"]`);
    if (!row) return;
    // Status
    if (t.status) row.setAttribute('data-status', t.status);
    // Bar-Position
    const bar = row.querySelector('.gantt-bar');
    if (bar) {
      if (t.bar_left != null) bar.style.left = t.bar_left + 'px';
      if (t.bar_width != null) bar.style.width = t.bar_width + 'px';
      if (t.status) {
        bar.classList.remove('status-done','status-wip','status-planned','status-delayed','status-prio');
        bar.classList.add('status-' + statusToClass(t.status));
      }
    }
    // Firma / Name / Gewerk falls Feld da
    const firmaCell = row.querySelector('.task-firma-cell');
    if (firmaCell && t.firma != null) firmaCell.textContent = t.firma;
    const nameCell = row.querySelector('.task-name-cell');
    if (nameCell && t.name && !nameCell.matches(':focus')) nameCell.textContent = t.name;
    const gewerkBadge = row.querySelector('.gewerk-badge');
    if (gewerkBadge && t.gewerk) gewerkBadge.textContent = t.gewerk;
  }

  function statusToClass(s) {
    return { 'fertig':'done', 'laufend':'wip', 'geplant':'planned',
             'verzögert':'delayed', 'priorität':'prio' }[s] || 'planned';
  }

  // ── Edit-Hooks: Änderungen ans Backend pushen ──────────────────────
  function installEditHooks() {
    const role = State.user.role;
    const canEditAll = role === 'admin' || role === 'architekt';
    const canEditStatus = canEditAll || role === 'worker';

    // Status-Klicks (vom bestehenden HTML)
    document.addEventListener('click', async (e) => {
      const statusEl = e.target.closest('[data-status-toggle]');
      if (statusEl && canEditStatus) {
        const row = statusEl.closest('tr[data-tid]');
        if (row) {
          const tid = row.getAttribute('data-tid');
          // Status wird vom bestehenden Code geändert — wir lesen ihn nach kurzem Delay
          setTimeout(() => {
            const newStatus = row.getAttribute('data-status');
            patchTask(tid, { status: newStatus });
          }, 50);
        }
      }
    }, true);

    // Inline-Edits via contenteditable blur
    document.addEventListener('blur', (e) => {
      const t = e.target;
      if (!t || !t.hasAttribute || !t.hasAttribute('contenteditable')) return;
      const row = t.closest('tr[data-tid]');
      if (!row) return;
      const tid = row.getAttribute('data-tid');
      const val = t.textContent.trim();
      if (t.classList.contains('task-name-cell') && canEditAll) {
        patchTask(tid, { name: val });
      } else if (t.classList.contains('task-firma-cell') && canEditAll) {
        patchTask(tid, { firma: val });
      } else if (t.classList.contains('worker-editable') && canEditStatus) {
        patchTask(tid, { notiz: val });
      }
    }, true);

    // Bar-Drag/Resize (vom bestehenden Code) — wenn die Bar bewegt wird, lesen wir
    // nach mouseup die neuen left/width-Werte.
    document.addEventListener('mouseup', () => {
      if (!canEditAll) return;
      // kurz warten, bis Bestands-Code die Werte gesetzt hat
      setTimeout(() => {
        document.querySelectorAll('.gantt-bar.dirty').forEach(bar => {
          const row = bar.closest('tr[data-tid]');
          if (!row) return;
          const tid = row.getAttribute('data-tid');
          const left = parseInt(bar.style.left, 10) || 0;
          const width = parseInt(bar.style.width, 10) || 0;
          patchTask(tid, { bar_left: left, bar_width: width });
          bar.classList.remove('dirty');
        });
      }, 100);
    });
  }

  async function patchTask(id, payload) {
    payload.id = id;
    try {
      const res = await fetch(API.tasks, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });
      if (res.status === 401) redirectToLogin();
      if (res.status === 403) showToast('Keine Berechtigung für diese Änderung.');
    } catch (e) {
      showToast('Speichern fehlgeschlagen — wird beim nächsten Sync wiederholt.');
    }
  }

  // ── Audit-Banner ───────────────────────────────────────────────────
  function showAuditBanner(info) {
    if (document.getElementById('audit-banner')) return;
    const b = document.createElement('div');
    b.id = 'audit-banner';
    b.innerHTML = `
      <div class="ab-inner">
        <span class="ab-icon">⚠️</span>
        <div class="ab-text">
          <strong>${info.old_entries}</strong> Audit-Einträge sind älter als
          <strong>${info.retention_days} Tage</strong> und können gelöscht werden.
        </div>
        <button class="ab-btn" data-act="extend">Aufbewahrung verlängern</button>
        <button class="ab-btn" data-act="export">Als JSON exportieren</button>
        <button class="ab-btn danger" data-act="delete">Jetzt löschen</button>
        <button class="ab-btn ghost" data-act="dismiss">Erst mal ignorieren</button>
      </div>`;
    document.body.appendChild(b);
    const style = document.createElement('style');
    style.textContent = `
      #audit-banner { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
        background: #fff8e1; border: 1.5px solid #f59e0b; border-radius: 14px; padding: 14px 18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 99999; max-width: 720px;
        font-family: 'Inter', sans-serif; font-size: 13px; }
      .ab-inner { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
      .ab-icon { font-size: 20px; }
      .ab-text { flex: 1; min-width: 240px; color: #78350f; }
      .ab-btn { padding: 6px 12px; border-radius: 8px; border: 1px solid #d97706;
        background: #fff; color: #b45309; font-weight: 600; font-size: 12px; cursor: pointer;
        font-family: inherit; }
      .ab-btn:hover { background: #fef3c7; }
      .ab-btn.danger { border-color: #ef4444; color: #b91c1c; }
      .ab-btn.danger:hover { background: #fee2e2; }
      .ab-btn.ghost { border-color: #cbd5e1; color: #64748b; }
    `;
    document.head.appendChild(style);
    b.addEventListener('click', async (e) => {
      const btn = e.target.closest('.ab-btn');
      if (!btn) return;
      const act = btn.dataset.act;
      if (act === 'dismiss') {
        await fetch(API.audit + '?action=warning_dismiss', { credentials: 'same-origin' });
        b.remove();
      } else if (act === 'export') {
        location.href = API.audit + '?action=export';
      } else if (act === 'extend') {
        const days = prompt('Neue Aufbewahrung in Tagen (0 = unbegrenzt, max. 3650):', '730');
        if (days != null) {
          await fetch(API.audit, { method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ retention_days: parseInt(days, 10) }) });
          b.remove();
        }
      } else if (act === 'delete') {
        if (confirm('Wirklich alle Einträge außerhalb der Aufbewahrung jetzt löschen?')) {
          await fetch(API.audit, { method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_old' }) });
          b.remove();
        }
      }
    });
  }

  // ── localStorage-Migration ─────────────────────────────────────────
  async function maybeMigrate() {
    if (State.user.role !== 'admin') return;
    // Server-Setting prüfen: schon migriert?
    const stateRes = await fetch(API.state, { credentials: 'same-origin' });
    const data = await stateRes.json();
    if (data.settings && data.settings.migrated_from_localstorage === '1') return;

    // Sammeln, was wir lokal haben
    const payload = collectLocalStorage();
    if (!payload.has_anything) return;

    if (!confirm(`Es wurden lokale Daten gefunden (${payload.summary}). Jetzt in die Datenbank übertragen?`)) {
      return;
    }

    const res = await fetch('api/migrate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const result = await res.json();
    if (result.ok) {
      alert(`Migration erfolgreich!\n${result.summary || ''}`);
      location.reload();
    } else {
      alert('Migration fehlgeschlagen: ' + (result.error || 'unbekannt'));
    }
  }

  function collectLocalStorage() {
    const out = {
      task_statuses: {},
      task_names: {},
      task_firma: {},
      task_gewerk: {},
      bar_positions: {},
      task_notiz: {},
      employees: null,
      orders: null,
      custom_tasks: null,
      has_anything: false,
      summary: '',
    };
    let count = 0;
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (!k) continue;
      const v = localStorage.getItem(k);
      if (k === 'task-statuses') { try { out.task_statuses = JSON.parse(v); count += Object.keys(out.task_statuses).length; } catch(e){} }
      else if (k.startsWith('task-name-')) { out.task_names[k.slice(10)] = v; count++; }
      else if (k.startsWith('task-firma-')) { out.task_firma[k.slice(11)] = v; count++; }
      else if (k.startsWith('task-gewerk-')) { out.task_gewerk[k.slice(12)] = v; count++; }
      else if (k.startsWith('bar-pos-')) { try { out.bar_positions[k.slice(8)] = JSON.parse(v); count++; } catch(e){} }
      else if (k.startsWith('task-mh-')) { out.task_notiz[k.slice(8)] = v; count++; }
      else if (k === 'kap-employees-v3' || k === 'kap-mitarbeiter-v10') { try { out.employees = JSON.parse(v); } catch(e){} }
      else if (k === 'bo-orders-v3') { try { out.orders = JSON.parse(v); } catch(e){} }
      else if (k === 'custom-tasks') { try { out.custom_tasks = JSON.parse(v); } catch(e){} }
    }
    out.has_anything = count > 0 || out.employees || out.orders || out.custom_tasks;
    out.summary = `${count} Einträge` +
      (out.employees ? `, ${(out.employees||[]).length} Mitarbeiter` : '') +
      (out.orders ? `, ${(out.orders||[]).length} Bestellungen` : '');
    return out;
  }

  // ── Logout ────────────────────────────────────────────────────────
  async function logout() {
    try { await fetch(API.logout, { method: 'POST', credentials: 'same-origin' }); } catch (e) {}
    if (State.polling) clearInterval(State.polling);
    location.href = 'login.html?logged_out=1';
  }

  // ── Utils ──────────────────────────────────────────────────────────
  function redirectToLogin() {
    if (State.polling) clearInterval(State.polling);
    location.href = 'login.html';
  }
  function showError(msg) {
    document.body.innerHTML = `<div style="padding:40px;font-family:Inter;text-align:center;color:#b91c1c">${escapeHtml(msg)}</div>`;
  }
  function showToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;padding:10px 16px;border-radius:10px;font-family:Inter;font-size:13px;z-index:99999;box-shadow:0 10px 30px rgba(0,0,0,0.2)';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function cssEscape(s) {
    return (window.CSS && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^\w-]/g, '\\$&');
  }

  // ── Markiere geänderte Bars für nächsten mouseup ──────────────────
  // (Wenn der Bestands-Code Bars verschiebt, soll er .dirty setzen. Falls nicht,
  //  hier minimaler Fallback: jeden Drag auf einer Bar markiert sie.)
  document.addEventListener('mousedown', (e) => {
    const bar = e.target.closest('.gantt-bar');
    if (bar) bar.classList.add('dirty');
  }, true);

  // ── Start ─────────────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

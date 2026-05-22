/**
 * admin.js — Nutzerverwaltungs-Tab + Audit-Log-Anzeige
 *
 * Wird nur geladen wenn der eingeloggte User Admin ist.
 * Hängt sich an einen Container <div id="admin-tab"></div> oder zeigt einen
 * Floating-Button "Nutzer verwalten" oben rechts an.
 */
(function() {
  'use strict';
  if (!document.body.classList.contains('role-admin')) return;

  const API = {
    users:  'api/users.php',
    audit:  'api/audit.php',
  };

  // Floating "Nutzer" Button neben User-Bar
  const ub = document.getElementById('user-bar');
  if (ub) {
    const btn = document.createElement('button');
    btn.className = 'ub-logout';
    btn.style.marginRight = '4px';
    btn.style.background = '#2563eb';
    btn.textContent = '👥 Nutzer';
    btn.onclick = openUserManager;
    ub.querySelector('.ub-inner').insertBefore(btn, document.getElementById('ub-logout'));

    const auditBtn = document.createElement('button');
    auditBtn.className = 'ub-logout';
    auditBtn.style.marginRight = '4px';
    auditBtn.style.background = '#64748b';
    auditBtn.textContent = '📋 Audit';
    auditBtn.onclick = openAuditLog;
    ub.querySelector('.ub-inner').insertBefore(auditBtn, document.getElementById('ub-logout'));
  }

  // ── Modal-Helfer ──────────────────────────────────────────────────
  function modal(title, contentHtml) {
    const old = document.getElementById('admin-modal');
    if (old) old.remove();
    const m = document.createElement('div');
    m.id = 'admin-modal';
    m.innerHTML = `
      <div class="am-backdrop"></div>
      <div class="am-card">
        <div class="am-head">
          <h2>${escapeHtml(title)}</h2>
          <button class="am-close">✕</button>
        </div>
        <div class="am-body">${contentHtml}</div>
      </div>`;
    document.body.appendChild(m);
    if (!document.getElementById('admin-modal-style')) {
      const s = document.createElement('style');
      s.id = 'admin-modal-style';
      s.textContent = `
        #admin-modal { position: fixed; inset: 0; z-index: 100000; font-family: 'Inter', sans-serif; }
        .am-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.45); backdrop-filter: blur(2px); }
        .am-card { position: relative; max-width: 920px; margin: 4vh auto; background: #fff;
          border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); max-height: 92vh;
          display: flex; flex-direction: column; }
        .am-head { padding: 18px 24px; border-bottom: 1px solid #e8e9ed;
          display: flex; justify-content: space-between; align-items: center; }
        .am-head h2 { margin: 0; font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
        .am-close { background: #f1f5f9; border: none; width: 32px; height: 32px;
          border-radius: 8px; cursor: pointer; font-size: 16px; color: #64748b; }
        .am-close:hover { background: #e2e8f0; color: #1e293b; }
        .am-body { padding: 20px 24px; overflow: auto; }
        .am-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .am-table th { text-align: left; padding: 10px 8px; font-size: 11px; color: #475569;
          font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
          border-bottom: 1px solid #e8e9ed; }
        .am-table td { padding: 9px 8px; border-bottom: 1px solid #f1f5f9; }
        .am-table tr:hover td { background: #fafbfc; }
        .am-input, .am-select { padding: 7px 10px; border: 1.5px solid #e2e8f0;
          border-radius: 7px; font-family: inherit; font-size: 13px; }
        .am-input:focus, .am-select:focus { outline: none; border-color: #2563eb;
          box-shadow: 0 0 0 3px rgba(37,99,235,.10); }
        .am-btn { padding: 7px 13px; border-radius: 8px; border: 1px solid #e2e8f0;
          background: #fff; cursor: pointer; font-weight: 600; font-size: 12px;
          font-family: inherit; transition: all .12s; }
        .am-btn:hover { background: #f8fafc; }
        .am-btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .am-btn-primary:hover { background: #1d4ed8; }
        .am-btn-danger { color: #b91c1c; border-color: #fca5a5; }
        .am-btn-danger:hover { background: #fee2e2; }
        .am-form-row { display: grid; grid-template-columns: 1fr 1fr 140px 100px; gap: 8px;
          padding: 12px; background: #f8fafc; border-radius: 10px; margin-bottom: 16px;
          align-items: end; }
        .am-form-row label { font-size: 11px; font-weight: 600; color: #64748b;
          display: block; margin-bottom: 4px; }
        .am-form-row input, .am-form-row select { width: 100%; }
        .am-role-badge { padding: 3px 10px; border-radius: 999px; font-size: 10px;
          font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .role-admin-bg     { background: #fee2e2; color: #b91c1c; }
        .role-architekt-bg { background: #dbeafe; color: #1d4ed8; }
        .role-worker-bg    { background: #fef3c7; color: #b45309; }
        .role-viewer-bg    { background: #f1f5f9; color: #64748b; }
      `;
      document.head.appendChild(s);
    }
    m.querySelector('.am-backdrop').onclick = () => m.remove();
    m.querySelector('.am-close').onclick = () => m.remove();
    return m;
  }

  // ── Nutzerverwaltung ───────────────────────────────────────────────
  async function openUserManager() {
    const m = modal('Nutzerverwaltung', '<div>Lade…</div>');
    const res = await fetch(API.users, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) { m.querySelector('.am-body').innerHTML = '<div style="color:#b91c1c">Fehler beim Laden</div>'; return; }

    const body = m.querySelector('.am-body');
    body.innerHTML = `
      <div class="am-form-row" id="add-user-form">
        <div><label>E-Mail</label><input class="am-input" id="nu-email" type="email" placeholder="vorname@firma.de"></div>
        <div><label>Name</label><input class="am-input" id="nu-name" type="text" placeholder="Vor- und Nachname"></div>
        <div><label>Rolle</label>
          <select class="am-select" id="nu-role">
            <option value="viewer">Viewer (nur lesen)</option>
            <option value="worker">Worker (Status setzen)</option>
            <option value="architekt">Architekt (alles außer Nutzer)</option>
            <option value="admin">Admin (alles)</option>
          </select>
        </div>
        <div><button class="am-btn am-btn-primary" id="nu-add">+ Anlegen</button></div>
      </div>

      <table class="am-table" id="user-table">
        <thead>
          <tr><th>E-Mail</th><th>Name</th><th>Rolle</th><th>Status</th><th>Letzter Login</th><th></th></tr>
        </thead>
        <tbody></tbody>
      </table>`;

    function renderUsers(users) {
      const tb = body.querySelector('#user-table tbody');
      tb.innerHTML = users.map(u => `
        <tr data-uid="${u.id}">
          <td>${escapeHtml(u.email)}</td>
          <td>${escapeHtml(u.name)}</td>
          <td>
            <select class="am-select us-role" data-uid="${u.id}">
              ${['admin','architekt','worker','viewer'].map(r =>
                `<option value="${r}" ${u.role===r?'selected':''}>${roleLabel(r)}</option>`).join('')}
            </select>
          </td>
          <td>
            <label style="font-size:12px"><input type="checkbox" class="us-active" data-uid="${u.id}" ${u.active==1?'checked':''}>
              ${u.active==1 ? 'aktiv' : 'gesperrt'}</label>
          </td>
          <td style="color:#64748b;font-size:12px">${u.last_login_at ? formatDate(u.last_login_at) : '—'}</td>
          <td><button class="am-btn am-btn-danger us-del" data-uid="${u.id}">Löschen</button></td>
        </tr>`).join('');
    }
    renderUsers(data.users);

    body.querySelector('#nu-add').onclick = async () => {
      const email = body.querySelector('#nu-email').value.trim();
      const name  = body.querySelector('#nu-name').value.trim();
      const role  = body.querySelector('#nu-role').value;
      if (!email || !name) { alert('E-Mail und Name benötigt'); return; }
      const r = await fetch(API.users, { method:'POST', credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ email, name, role }) });
      const d = await r.json();
      if (d.ok) {
        body.querySelector('#nu-email').value = '';
        body.querySelector('#nu-name').value = '';
        openUserManager();  // reload
      } else {
        alert(d.error || 'Fehler');
      }
    };

    body.addEventListener('change', async (e) => {
      if (e.target.matches('.us-role')) {
        const uid = e.target.dataset.uid;
        await fetch(API.users, { method:'PATCH', credentials:'same-origin',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ id: parseInt(uid,10), role: e.target.value }) });
      } else if (e.target.matches('.us-active')) {
        const uid = e.target.dataset.uid;
        await fetch(API.users, { method:'PATCH', credentials:'same-origin',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ id: parseInt(uid,10), active: e.target.checked ? 1 : 0 }) });
      }
    });

    body.addEventListener('click', async (e) => {
      if (e.target.matches('.us-del')) {
        if (!confirm('User wirklich löschen?')) return;
        const uid = e.target.dataset.uid;
        await fetch(API.users, { method:'DELETE', credentials:'same-origin',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ id: parseInt(uid,10) }) });
        openUserManager();
      }
    });
  }

  // ── Audit-Log ─────────────────────────────────────────────────────
  async function openAuditLog() {
    const m = modal('Audit-Log', '<div>Lade…</div>');
    const res = await fetch(API.audit, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) { m.querySelector('.am-body').innerHTML = '<div style="color:#b91c1c">Fehler beim Laden</div>'; return; }

    const body = m.querySelector('.am-body');
    body.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding:10px 14px;background:#f8fafc;border-radius:10px;font-size:13px">
        <span>Aufbewahrung:</span>
        <strong>${data.retention_days === 0 ? 'unbegrenzt' : data.retention_days + ' Tage'}</strong>
        <button class="am-btn" id="audit-set">Ändern</button>
        <span style="flex:1"></span>
        <span style="color:#64748b">${data.total} Einträge gesamt</span>
        <button class="am-btn" id="audit-export">⬇ Export (alte)</button>
      </div>
      <table class="am-table">
        <thead><tr><th>Zeit</th><th>User</th><th>Aktion</th><th>Entity</th><th>IP</th></tr></thead>
        <tbody>${data.items.map(r => `
          <tr>
            <td style="white-space:nowrap;color:#64748b;font-size:12px">${formatDate(r.created_at)}</td>
            <td>${escapeHtml(r.user_name || '—')}<br><span style="color:#94a3b8;font-size:11px">${escapeHtml(r.user_email || '')}</span></td>
            <td><code>${escapeHtml(r.action)}</code></td>
            <td>${escapeHtml(r.entity)} ${r.entity_id ? '<code>' + escapeHtml(r.entity_id) + '</code>' : ''}</td>
            <td style="color:#94a3b8;font-size:11px">${escapeHtml(r.ip || '')}</td>
          </tr>`).join('')}</tbody>
      </table>`;

    body.querySelector('#audit-set').onclick = async () => {
      const v = prompt('Aufbewahrungsdauer in Tagen (0 = unbegrenzt, max. 3650):', String(data.retention_days));
      if (v == null) return;
      const days = parseInt(v, 10);
      if (isNaN(days) || days < 0 || days > 3650) { alert('Wert 0..3650'); return; }
      await fetch(API.audit, { method:'POST', credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ retention_days: days }) });
      openAuditLog();
    };
    body.querySelector('#audit-export').onclick = () => {
      location.href = API.audit + '?action=export';
    };
  }

  // ── Utils ─────────────────────────────────────────────────────────
  function roleLabel(r) {
    return { admin: 'Admin', architekt: 'Architekt', worker: 'Worker', viewer: 'Viewer' }[r] || r;
  }
  function formatDate(s) {
    if (!s) return '';
    const d = new Date(s.replace(' ','T'));
    return d.toLocaleString('de-DE', { day:'2-digit', month:'2-digit', year:'2-digit', hour:'2-digit', minute:'2-digit' });
  }
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
})();

/**
 * history-modal.js — Verlauf-Anzeige pro Aufgabe
 *
 *  Klick auf Aufgabenname (mit Shift) ODER auf 🕐-Icon → Modal mit kompletter Historie
 *  Liest Daten aus window.TaskHistory (changes.js)
 */
(function () {
  'use strict';

  function injectCSS() {
    if (document.getElementById('history-modal-styles')) return;
    const s = document.createElement('style');
    s.id = 'history-modal-styles';
    s.textContent = `
      #task-history-modal {
        position: fixed; inset: 0;
        z-index: 100000;
        font-family: 'Inter', sans-serif;
        display: none;
      }
      #task-history-modal.open { display: block; }
      #task-history-modal .thm-backdrop {
        position: absolute; inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(2px);
      }
      #task-history-modal .thm-card {
        position: relative;
        max-width: 540px; margin: 6vh auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        max-height: 84vh;
        display: flex; flex-direction: column;
      }
      #task-history-modal .thm-head {
        padding: 16px 20px;
        border-bottom: 1px solid #e8e9ed;
        display: flex; justify-content: space-between; align-items: center;
      }
      #task-history-modal .thm-head h2 {
        font-size: 16px; font-weight: 800; letter-spacing: -0.02em; margin: 0;
      }
      #task-history-modal .thm-head .thm-sub {
        font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px;
      }
      #task-history-modal .thm-close {
        background: #f1f5f9; border: none; width: 30px; height: 30px;
        border-radius: 8px; cursor: pointer; font-size: 14px; color: #64748b;
      }
      #task-history-modal .thm-close:hover { background: #e2e8f0; color: #1e293b; }
      #task-history-modal .thm-body {
        padding: 12px 20px 20px;
        overflow: auto;
      }
      #task-history-modal .thm-entry {
        padding: 10px 12px;
        border-radius: 10px;
        background: #f8fafc;
        margin-bottom: 6px;
        display: flex; gap: 10px; align-items: flex-start;
      }
      #task-history-modal .thm-entry:hover { background: #eff6ff; }
      #task-history-modal .thm-icon {
        width: 28px; height: 28px; border-radius: 50%;
        background: #2563eb; color: #fff;
        display: grid; place-items: center;
        font-size: 12px; font-weight: 800;
        flex: none;
      }
      #task-history-modal .thm-content { flex: 1; }
      #task-history-modal .thm-line1 {
        font-size: 12px; font-weight: 600; color: #1e293b;
      }
      #task-history-modal .thm-line2 {
        font-size: 11px; color: #64748b; margin-top: 2px;
      }
      #task-history-modal .thm-change {
        margin-top: 4px; font-size: 11px;
        display: flex; gap: 6px; flex-wrap: wrap;
      }
      #task-history-modal .thm-old {
        text-decoration: line-through;
        color: #94a3b8;
        background: #fee2e2;
        padding: 1px 6px;
        border-radius: 4px;
      }
      #task-history-modal .thm-new {
        background: #d1fae5;
        color: #065f46;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 600;
      }
      #task-history-modal .thm-empty {
        padding: 30px 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
      }

      /* History-Trigger-Button im task-row */
      .se-history-btn {
        margin-left: 6px;
        padding: 2px 6px;
        border: none;
        background: transparent;
        color: #cbd5e1;
        font-size: 11px;
        cursor: pointer;
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.15s, background 0.12s, color 0.12s;
        line-height: 1;
      }
      tr.task-row:hover .se-history-btn { opacity: 1; }
      .se-history-btn:hover {
        background: #dbeafe;
        color: #2563eb;
      }
      .se-history-btn.has-history { color: #64748b; opacity: 1; }
    `;
    document.head.appendChild(s);
  }

  function buildModal() {
    if (document.getElementById('task-history-modal')) return;
    const m = document.createElement('div');
    m.id = 'task-history-modal';
    m.innerHTML = `
      <div class="thm-backdrop"></div>
      <div class="thm-card">
        <div class="thm-head">
          <div>
            <h2 id="thm-title">Verlauf</h2>
            <div class="thm-sub" id="thm-sub"></div>
          </div>
          <button class="thm-close" aria-label="Schließen">✕</button>
        </div>
        <div class="thm-body" id="thm-body"></div>
      </div>`;
    document.body.appendChild(m);
    m.querySelector('.thm-backdrop').addEventListener('click', closeModal);
    m.querySelector('.thm-close').addEventListener('click', closeModal);
  }

  function openModal(tid, taskName) {
    buildModal();
    const m = document.getElementById('task-history-modal');
    m.querySelector('#thm-title').textContent = taskName || 'Verlauf';
    m.querySelector('#thm-sub').textContent = 'Alle Änderungen an dieser Aufgabe';
    const body = m.querySelector('#thm-body');
    const hist = (window.TaskHistory && window.TaskHistory.get(tid)) || [];
    if (!hist.length) {
      body.innerHTML = '<div class="thm-empty">Noch keine Änderungen aufgezeichnet</div>';
    } else {
      body.innerHTML = hist.slice().reverse().map((e) => {
        const initials = (e.user || '?').split(/\s+/).map(w => w[0] || '').join('').slice(0, 2).toUpperCase() || '?';
        const ago = window.TaskHistory.formatTimestamp(e.ts);
        return `
          <div class="thm-entry">
            <div class="thm-icon">${initials}</div>
            <div class="thm-content">
              <div class="thm-line1">${esc(e.user)} · ${esc(e.field)}</div>
              <div class="thm-line2">${esc(ago)} · ${new Date(e.ts).toLocaleString('de-DE')}</div>
              ${e.old || e.new ? `
                <div class="thm-change">
                  ${e.old ? `<span class="thm-old">${esc(e.old)}</span>` : ''}
                  ${e.new ? `<span class="thm-new">${esc(e.new)}</span>` : ''}
                </div>` : ''}
            </div>
          </div>`;
      }).join('');
    }
    m.classList.add('open');
  }

  function closeModal() {
    const m = document.getElementById('task-history-modal');
    if (m) m.classList.remove('open');
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // History-Button pro Aufgabe einbauen
  function addHistoryButtons() {
    document.querySelectorAll('tr.task-row').forEach((row) => {
      if (row.querySelector(':scope .se-history-btn')) return;
      const nameCell = row.querySelector('.task-name-cell');
      if (!nameCell) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'se-history-btn';
      btn.title = 'Verlauf anzeigen';
      btn.innerHTML = '🕐';
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();
        const tid = row.getAttribute('data-tid');
        const taskName = (nameCell.textContent || '').replace(/✕\s*$/, '').replace(/🕐\s*$/, '').trim();
        openModal(tid, taskName);
      });
      // Vor dem Delete-Button einfügen wenn vorhanden
      const delBtn = nameCell.querySelector('.se-row-del');
      if (delBtn) {
        nameCell.insertBefore(btn, delBtn);
      } else {
        nameCell.appendChild(btn);
      }
      // Falls History für diesen Task existiert → permanent sichtbar
      updateHistoryBtnVisibility(row, btn);
    });
  }

  function updateHistoryBtnVisibility(row, btn) {
    const tid = row.getAttribute('data-tid');
    if (!tid || !window.TaskHistory) return;
    const has = window.TaskHistory.get(tid).length > 0;
    btn.classList.toggle('has-history', has);
  }

  // Re-Run wenn neue Rows hinzukommen
  function watch() {
    const obs = new MutationObserver(() => {
      clearTimeout(watch._t);
      watch._t = setTimeout(addHistoryButtons, 200);
    });
    obs.observe(document.body, { childList: true, subtree: true });
  }

  function init() {
    injectCSS();
    addHistoryButtons();
    watch();
    // Escape schließt Modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
    // Sichtbarkeits-Update alle 5 Sek (für Tasks, die neue History bekommen)
    setInterval(() => {
      document.querySelectorAll('.se-history-btn').forEach((btn) => {
        const row = btn.closest('tr.task-row');
        if (row) updateHistoryBtnVisibility(row, btn);
      });
    }, 5000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * bar-labels.js — Bar-Labels + Tooltips für Gantt-Bars
 *
 * B1: Tooltip beim Hover auf einen Bar → zeigt Aufgabe, Status, Gewerk, Firma, KW, Notiz
 * B2: Label IM Bar selbst → Aufgabenname + Gewerk (gekürzt wenn nötig)
 *
 * Liest die Daten aus der jeweiligen task-row (erste 4 td) und dem Bar selbst.
 * Funktioniert komplett ohne Backend-Änderungen.
 */
(function () {
  'use strict';

  let tooltipEl = null;
  let activeBar = null;

  // ── Style ─────────────────────────────────────────────────────────
  function injectCSS() {
    if (document.getElementById('bar-labels-styles')) return;
    const s = document.createElement('style');
    s.id = 'bar-labels-styles';
    s.textContent = `
      /* In-Bar-Label (sichtbar IM Bar) */
      .gantt-bar { position: relative; }
      .gantt-bar .bar-label {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        padding: 0 8px;
        font-size: 10px;
        font-weight: 700;
        color: #fff;
        text-shadow: 0 1px 1px rgba(0,0,0,0.18);
        letter-spacing: 0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
        line-height: 1;
      }
      /* Wenn Bar zu klein für Text: Label rechts neben dem Bar */
      .gantt-bar.label-outside .bar-label {
        position: absolute;
        left: calc(100% + 6px);
        right: auto;
        color: #1e293b;
        text-shadow: none;
        font-weight: 600;
        background: rgba(255,255,255,0.7);
        padding: 1px 5px;
        border-radius: 4px;
        backdrop-filter: blur(2px);
        white-space: nowrap;
        max-width: 280px;
      }

      /* Bei hellem Bar-Hintergrund: dunklerer Text */
      .gantt-bar.status-planned .bar-label,
      .gantt-bar[class*="status-planned"] .bar-label {
        color: #475569;
        text-shadow: none;
      }

      /* Custom-Tooltip (Hover) */
      #bar-tooltip {
        position: fixed;
        z-index: 99999;
        background: #0f172a;
        color: #f1f5f9;
        padding: 12px 14px;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        line-height: 1.5;
        max-width: 320px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35), 0 2px 6px rgba(0,0,0,0.2);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.12s;
        transform: translateY(4px);
      }
      #bar-tooltip.visible {
        opacity: 1;
        transform: translateY(0);
      }
      #bar-tooltip .bt-title {
        font-weight: 700;
        font-size: 13px;
        color: #fff;
        margin-bottom: 4px;
        letter-spacing: -0.01em;
      }
      #bar-tooltip .bt-row {
        display: flex;
        gap: 6px;
        margin-top: 2px;
        align-items: baseline;
      }
      #bar-tooltip .bt-key {
        color: #94a3b8;
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.04em;
        min-width: 50px;
      }
      #bar-tooltip .bt-val { color: #e2e8f0; }
      #bar-tooltip .bt-pill {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        margin-right: 4px;
      }
      #bar-tooltip .bt-pill-blue { background: #1e3a8a; color: #93c5fd; }
      #bar-tooltip .bt-pill-orange { background: #7c2d12; color: #fdba74; }
      #bar-tooltip .bt-pill-green { background: #14532d; color: #86efac; }
      #bar-tooltip .bt-pill-red { background: #7f1d1d; color: #fca5a5; }
      #bar-tooltip .bt-pill-purple { background: #581c87; color: #d8b4fe; }
      #bar-tooltip .bt-pill-gray { background: #334155; color: #cbd5e1; }
      #bar-tooltip .bt-notiz {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #334155;
        color: #cbd5e1;
        font-style: italic;
        font-size: 11px;
      }
      #bar-tooltip .bt-history {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #334155;
        color: #cbd5e1;
        font-size: 11px;
        line-height: 1.4;
      }

      /* Bar selbst beim Hover hervorheben */
      .gantt-bar:hover {
        outline: 2px solid #2563eb;
        outline-offset: 1px;
        z-index: 10;
      }
    `;
    document.head.appendChild(s);
  }

  // ── Helper ────────────────────────────────────────────────────────
  function trimText(s, maxLen) {
    s = (s || '').trim();
    return s.length > maxLen ? s.substr(0, maxLen - 1) + '…' : s;
  }

  function getRowData(row) {
    if (!row) return {};
    const cells = row.children;
    return {
      aufgabe: (cells[0]?.textContent || '').trim(),
      status: (cells[1]?.textContent || '').trim(),
      gewerk: (cells[2]?.textContent || '').trim(),
      firma:  (cells[3]?.textContent || '').trim(),
    };
  }

  function statusToPill(status) {
    const s = (status || '').toLowerCase();
    if (/(fertig|done|abgeschlossen|erledigt)/.test(s)) return 'bt-pill-green';
    if (/(verzögert|delayed)/.test(s)) return 'bt-pill-red';
    if (/(priorität|prio)/.test(s)) return 'bt-pill-orange';
    if (/(laufend|arbeit|\d+\s*%)/.test(s)) return 'bt-pill-orange';
    if (/(geplant)/.test(s) || s === '—' || s === '') return 'bt-pill-gray';
    return 'bt-pill-blue';
  }

  function statusBadgeColorFromBar(bar) {
    // Bar-Hintergrund-Klasse → Tooltip-Pill-Farbe
    const cls = bar.className;
    if (cls.includes('status-done')) return 'bt-pill-green';
    if (cls.includes('status-wip')) return 'bt-pill-orange';
    if (cls.includes('status-planned')) return 'bt-pill-gray';
    if (cls.includes('status-delayed')) return 'bt-pill-red';
    if (cls.includes('status-prio')) return 'bt-pill-purple';
    return 'bt-pill-blue';
  }

  function getBarMeta(bar) {
    // Titel-Attribut enthält oft KW-Bereich + Hinweis aus früherem Code
    const title = bar.getAttribute('title') || '';
    const row = bar.closest('tr.task-row');
    const rd = getRowData(row);
    // KW aus Title-Attribut extrahieren (Format "KW40–41 · ..." oder "KW40-41")
    const kwMatch = title.match(/KW\s*\d+\s*[–-]\s*\d+|KW\s*\d+/i);
    const kw = kwMatch ? kwMatch[0].replace(/\s+/g, '') : '';
    // Notiz/Hinweis ist alles nach " · " im Title
    const notizMatch = title.match(/·\s*(.+)$/);
    const notiz = notizMatch ? notizMatch[1].trim() : '';
    return Object.assign(rd, { title, kw, notiz });
  }

  // ── B2: Bar-Labels einbauen ──────────────────────────────────────
  function addBarLabels() {
    document.querySelectorAll('.gantt-bar').forEach((bar) => {
      // skip wenn schon hat
      if (bar.querySelector(':scope > .bar-label')) return;
      const meta = getBarMeta(bar);
      if (!meta.aufgabe) return;

      // Label-Text bauen
      const parts = [meta.aufgabe];
      if (meta.firma && meta.firma !== '—' && meta.firma !== '+ Gewerk') {
        parts.push(meta.firma);
      }
      const labelText = parts.join(' · ');

      const label = document.createElement('span');
      label.className = 'bar-label';
      label.textContent = labelText;
      bar.appendChild(label);

      // Wenn Bar zu schmal für Text: Label rechts daneben anzeigen
      // Schwelle: Bar < 80px → Label outside
      const barWidth = parseInt(bar.style.width, 10) || bar.getBoundingClientRect().width;
      if (barWidth < 80) {
        bar.classList.add('label-outside');
      }
    });
  }

  // ── B1: Hover-Tooltip ─────────────────────────────────────────────
  function ensureTooltip() {
    if (tooltipEl) return tooltipEl;
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'bar-tooltip';
    document.body.appendChild(tooltipEl);
    return tooltipEl;
  }

  function showTooltip(bar, evt) {
    const tt = ensureTooltip();
    const m = getBarMeta(bar);

    const statusPill = statusBadgeColorFromBar(bar);

    let html = '';
    html += `<div class="bt-title">${escapeHtml(m.aufgabe || '—')}</div>`;
    if (m.status) {
      html += `<div class="bt-row"><span class="bt-pill ${statusPill}">${escapeHtml(m.status)}</span></div>`;
    }
    if (m.gewerk && m.gewerk !== '—') {
      html += `<div class="bt-row"><span class="bt-key">Gewerk</span><span class="bt-val">${escapeHtml(m.gewerk)}</span></div>`;
    }
    if (m.firma && m.firma !== '—' && m.firma !== '+ Gewerk') {
      html += `<div class="bt-row"><span class="bt-key">Firma</span><span class="bt-val">${escapeHtml(m.firma)}</span></div>`;
    }
    if (m.kw) {
      html += `<div class="bt-row"><span class="bt-key">Zeitraum</span><span class="bt-val">${escapeHtml(m.kw)}</span></div>`;
    }
    if (m.notiz) {
      html += `<div class="bt-notiz">📝 ${escapeHtml(m.notiz)}</div>`;
    }

    // Letzte Änderung anzeigen (aus changes.js)
    const row = bar.closest('tr.task-row');
    const tid = row ? row.getAttribute('data-tid') : null;
    if (tid && window.TaskHistory) {
      const last = window.TaskHistory.getLast(tid);
      if (last) {
        const ago = window.TaskHistory.formatTimestamp(last.ts);
        html += `<div class="bt-history">✏️ ${escapeHtml(last.field)} geändert ${escapeHtml(ago)}<br><span style="color:#94a3b8;font-size:10px">von ${escapeHtml(last.user)}</span></div>`;
      }
    }

    tt.innerHTML = html;
    positionTooltip(tt, evt);
    requestAnimationFrame(() => tt.classList.add('visible'));
  }

  function hideTooltip() {
    if (!tooltipEl) return;
    tooltipEl.classList.remove('visible');
    activeBar = null;
  }

  function positionTooltip(tt, evt) {
    // Tooltip neben dem Mauszeiger positionieren (mit Edge-Detection)
    const pad = 14;
    const rect = tt.getBoundingClientRect();
    const ttW = rect.width || 200;
    const ttH = rect.height || 60;
    let x = evt.clientX + pad;
    let y = evt.clientY + pad;
    if (x + ttW > window.innerWidth - 8)  x = evt.clientX - ttW - pad;
    if (y + ttH > window.innerHeight - 8) y = evt.clientY - ttH - pad;
    tt.style.left = Math.max(8, x) + 'px';
    tt.style.top  = Math.max(8, y) + 'px';
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // ── Event-Wiring ──────────────────────────────────────────────────
  function wireEvents() {
    document.addEventListener('mouseover', (e) => {
      const bar = e.target.closest('.gantt-bar');
      if (!bar) return;
      if (activeBar === bar) return;
      activeBar = bar;
      showTooltip(bar, e);
    });
    document.addEventListener('mousemove', (e) => {
      if (!activeBar || !tooltipEl) return;
      positionTooltip(tooltipEl, e);
    });
    document.addEventListener('mouseout', (e) => {
      const bar = e.target.closest('.gantt-bar');
      if (!bar) return;
      // Wechsle nur wenn wirklich raus aus dem Bar
      const to = e.relatedTarget;
      if (to && bar.contains(to)) return;
      hideTooltip();
    });
    // Touch: Tap zeigt Tooltip kurz an
    document.addEventListener('touchstart', (e) => {
      const bar = e.target.closest('.gantt-bar');
      if (!bar) return;
      const touch = e.touches[0];
      showTooltip(bar, { clientX: touch.clientX, clientY: touch.clientY });
      setTimeout(hideTooltip, 2500);
    }, { passive: true });
  }

  // ── Re-Run nach DOM-Änderungen ────────────────────────────────────
  let observer = null;
  function watchForNewBars() {
    if (observer) observer.disconnect();
    observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        for (const node of m.addedNodes) {
          if (node.nodeType !== 1) continue;
          if (node.classList?.contains('gantt-bar') || node.querySelector?.('.gantt-bar')) {
            scheduleRebuild();
            return;
          }
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
  let rebuildTimer = null;
  function scheduleRebuild() {
    clearTimeout(rebuildTimer);
    rebuildTimer = setTimeout(addBarLabels, 100);
  }

  // ── Init ──────────────────────────────────────────────────────────
  function init() {
    injectCSS();
    addBarLabels();
    wireEvents();
    watchForNewBars();
    // Sicherheits-Re-Runs
    setTimeout(addBarLabels, 500);
    setTimeout(addBarLabels, 1500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

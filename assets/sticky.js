/**
 * sticky.js — Top + Left Sticky (Excel-Style Frozen Cols)
 *
 *  TOP: Header / Stats / Tabs / Filter + KW/Monat-Spaltenkopf
 *  LEFT: Aufgabe / Status / Gewerk / Firma bleiben beim Horizontal-Scroll stehen
 *
 *  Da .gantt-wrap overflow-x:auto hat, kann thead nicht naiv sticky-top sein.
 *  → Clone-Pattern: thead wird außerhalb als 2-teiliger Flex-Container nachgebaut
 *    (Fixed-Cols + scrollbare Timeline). Timeline wird per JS mit Haupt-Scroll
 *    synchronisiert.
 *
 *  Für tbody werden die ersten 4 td von task-rows mit position:sticky left:X
 *  versehen. Sticky-context = .gantt-wrap (overflow-x:auto). Funktioniert dort.
 */
(function () {
  'use strict';

  const STICKY_SELECTOR = '.header, .summary, .tabs, .filter-bar';
  let cumulativeTop = 0;
  let cloneHeader = null;
  let scrollSyncer = null;

  function injectBaseCSS() {
    if (document.getElementById('sticky-styles')) return;
    const s = document.createElement('style');
    s.id = 'sticky-styles';
    s.textContent = `
      /* Originale thead unsichtbar (Platz bleibt) */
      #main-gantt.has-sticky-clone thead { visibility: hidden; }

      /* Sticky-Clone Container */
      #gantt-sticky-header {
        position: sticky;
        z-index: 30;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        display: flex;
        align-items: stretch;
      }
      #gantt-sticky-header .gsh-fixed {
        flex: none;
        display: flex;
        z-index: 2;
        background: #fff;
        box-shadow: 6px 0 12px -4px rgba(0,0,0,0.06);
      }
      #gantt-sticky-header .gsh-th {
        background: #fafbfc;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
        font-size: 11px;
        color: #475569;
        letter-spacing: 0.02em;
        padding: 8px 10px;
        display: flex;
        align-items: center;
      }
      #gantt-sticky-header .gsh-scroll {
        flex: 1;
        overflow: hidden;
        position: relative;
      }
      #gantt-sticky-header .gsh-timeline {
        will-change: transform;
      }

      /* gantt-wrap: horizontaler Scroll, vertikal sichtbar */
      .gantt-wrap { overflow-x: auto; overflow-y: visible; }

      /* ══════ LEFT-STICKY: erste 4 Spalten in Task-Rows ══════ */
      /* !important nötig wegen Existenz von tr.task-row td:nth-child(3) { position:relative } */
      #main-gantt tbody tr.task-row > td:nth-child(1),
      #main-gantt tbody tr.task-row > td:nth-child(2),
      #main-gantt tbody tr.task-row > td:nth-child(3),
      #main-gantt tbody tr.task-row > td:nth-child(4) {
        position: sticky !important;
        z-index: 5 !important;
        background: #fff !important;
      }
      #main-gantt tbody tr.task-row > td:nth-child(1) { left: 0 !important; }
      #main-gantt tbody tr.task-row > td:nth-child(2) { left: var(--c1w, 280px) !important; }
      #main-gantt tbody tr.task-row > td:nth-child(3) { left: var(--c12w, 340px) !important; }
      #main-gantt tbody tr.task-row > td:nth-child(4) {
        left: var(--c123w, 440px) !important;
        box-shadow: 6px 0 12px -4px rgba(0,0,0,0.06);
      }
      #main-gantt tbody tr.task-row:hover > td:nth-child(-n+4) {
        background: #fafbfc !important;
      }

      /* Section/KFW-Header-Rows mit colspan: erste cell sticky-left */
      #main-gantt tbody tr.section-row > td:first-child,
      #main-gantt tbody tr.kfw-header-row > td:first-child {
        position: sticky !important;
        left: 0 !important;
        z-index: 6 !important;
        box-shadow: 6px 0 12px -4px rgba(0,0,0,0.06);
      }
      #main-gantt tbody tr.section-row > td:first-child { background: #f8fafc !important; }
      /* kfw-header-row hat eigene dark backgrounds via .kfw-a/-b/-c — preserve */
      #main-gantt tbody tr.kfw-header-row.kfw-a > td:first-child { background: #2563eb !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row.kfw-b > td:first-child { background: #7c3aed !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row.kfw-c > td:first-child { background: #ea580c !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row[style*="16a34a"] > td:first-child { background: #16a34a !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row[style*="d97706"] > td:first-child { background: #d97706 !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row[style*="7c3aed"] > td:first-child { background: #7c3aed !important; color: #fff !important; }
      #main-gantt tbody tr.kfw-header-row[style*="94a3b8"] > td:first-child { background: #94a3b8 !important; color: #fff !important; }

      /* Mobile-Optimierungen */
      @media (max-width: 760px) {
        .summary { padding: 10px 16px !important; gap: 6px !important; }
        .summary .card { padding: 6px 8px !important; flex: 1; min-width: 0; }
        .summary .num { font-size: 14px !important; }
        .summary .lbl { font-size: 9px !important; }
        .header { padding: 12px 16px !important; }
        .header h1, .h1 { font-size: 14px !important; }
        .header .sub  { font-size: 10px !important; }
        .tabs { padding: 6px 12px 0 !important; gap: 4px !important;
                overflow-x: auto; white-space: nowrap; flex-wrap: nowrap !important; }
        .tab  { font-size: 11px !important; padding: 6px 10px !important;
                flex: none !important; }
        .filter-bar { padding: 6px 12px !important; gap: 4px !important;
                      overflow-x: auto; flex-wrap: nowrap !important; }
        .filter-bar label { font-size: 9px !important; }
        .filter-bar .filter-btn,
        .filter-bar button { font-size: 10px !important; padding: 3px 9px !important; }

        #user-bar { top: 6px !important; right: 8px !important; }
        #user-bar .ub-name { display: none; }
        #global-search { top: 38px !important; right: 8px !important;
                         left: 8px !important; min-width: auto !important;
                         max-width: calc(100vw - 16px) !important; }
      }
    `;
    document.head.appendChild(s);
  }

  function applyPageSticky() {
    const stickies = Array.from(document.querySelectorAll(STICKY_SELECTOR));
    cumulativeTop = 0;
    let zIndex = 50;

    stickies.forEach((el) => {
      const inHiddenTab = el.closest('.tab-content:not(.active)') !== null;
      if (inHiddenTab) {
        el.style.position = ''; el.style.top = ''; el.style.zIndex = '';
        return;
      }
      el.style.position = 'sticky';
      el.style.top = cumulativeTop + 'px';
      el.style.zIndex = String(zIndex--);
      if (!el.style.background) el.style.background = '#fff';
      cumulativeTop += el.getBoundingClientRect().height;
    });

    if (cloneHeader) cloneHeader.style.top = cumulativeTop + 'px';
  }

  function measureColumnWidths(table) {
    const refRow = table.querySelector('tbody tr.task-row');
    if (!refRow) return null;
    const widths = [];
    refRow.querySelectorAll('td').forEach((td) => {
      widths.push(td.getBoundingClientRect().width);
    });
    return widths;
  }

  function setColumnCSSVars(colWidths) {
    if (!colWidths || colWidths.length < 4) return;
    const root = document.documentElement;
    root.style.setProperty('--c1w',   colWidths[0] + 'px');
    root.style.setProperty('--c12w',  (colWidths[0] + colWidths[1]) + 'px');
    root.style.setProperty('--c123w', (colWidths[0] + colWidths[1] + colWidths[2]) + 'px');
    root.style.setProperty('--c1234w',(colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3]) + 'px');
  }

  function buildCloneHeader() {
    const wrap = document.querySelector('.gantt-wrap');
    const table = document.getElementById('main-gantt');
    if (!wrap || !table) return;

    const colWidths = measureColumnWidths(table);
    setColumnCSSVars(colWidths);

    // Existing entfernen
    const oldClone = document.getElementById('gantt-sticky-header');
    if (oldClone) oldClone.remove();

    // Container
    cloneHeader = document.createElement('div');
    cloneHeader.id = 'gantt-sticky-header';

    // ─── Linker Teil: 4 fixe Spalten ───
    const fixed = document.createElement('div');
    fixed.className = 'gsh-fixed';
    const labels = ['Aufgabe', 'Status', 'Gewerk', 'Firma'];
    labels.forEach((label, i) => {
      const th = document.createElement('div');
      th.className = 'gsh-th';
      th.textContent = label;
      const w = colWidths ? colWidths[i] : [280, 60, 100, 100][i];
      th.style.width = w + 'px';
      fixed.appendChild(th);
    });
    cloneHeader.appendChild(fixed);

    // ─── Rechter Teil: Scrollbare Timeline ───
    const scrollBox = document.createElement('div');
    scrollBox.className = 'gsh-scroll';
    const timelineInner = document.createElement('div');
    timelineInner.className = 'gsh-timeline';

    // Originale Timeline-Header + KW-Header klonen
    const originalThead = table.querySelector('thead');
    if (originalThead) {
      const timelineHeader = originalThead.querySelector('.gantt-timeline-header');
      const kwHeader = originalThead.querySelector('.gantt-kw-header');
      if (timelineHeader) timelineInner.appendChild(timelineHeader.cloneNode(true));
      if (kwHeader) timelineInner.appendChild(kwHeader.cloneNode(true));
      // Breite setzen
      const tlWidth = (timelineHeader && timelineHeader.offsetWidth) || 3768;
      timelineInner.style.width = tlWidth + 'px';
    }
    scrollBox.appendChild(timelineInner);
    cloneHeader.appendChild(scrollBox);

    // Vor .gantt-wrap einfügen
    wrap.parentNode.insertBefore(cloneHeader, wrap);
    table.classList.add('has-sticky-clone');

    // Scroll-Sync: nur das Timeline-Inner verschieben (Fixed-Cols bleiben stehen)
    if (scrollSyncer) wrap.removeEventListener('scroll', scrollSyncer);
    scrollSyncer = () => {
      timelineInner.style.transform = `translateX(${-wrap.scrollLeft}px)`;
    };
    wrap.addEventListener('scroll', scrollSyncer, { passive: true });
  }

  function remeasureClone() {
    const table = document.getElementById('main-gantt');
    if (!table || !cloneHeader) return;
    const colWidths = measureColumnWidths(table);
    setColumnCSSVars(colWidths);
    if (!colWidths) return;
    // Fixed-cols neue Breiten geben
    const fixedCells = cloneHeader.querySelectorAll('.gsh-th');
    fixedCells.forEach((th, i) => {
      if (colWidths[i] != null) th.style.width = colWidths[i] + 'px';
    });
  }

  let resizeTimer = null;
  function scheduleApply(delay = 50) {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      applyPageSticky();
      remeasureClone();
    }, delay);
  }

  function init() {
    injectBaseCSS();
    applyPageSticky();
    buildCloneHeader();
    applyPageSticky();

    window.addEventListener('resize', () => scheduleApply(100));
    document.addEventListener('click', () => scheduleApply(150), true);

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(() => scheduleApply(30));
    }
    setTimeout(() => { applyPageSticky(); scheduleApply(0); }, 500);
    setTimeout(() => { applyPageSticky(); scheduleApply(0); }, 1500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

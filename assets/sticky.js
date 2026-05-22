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
    // ALWAYS replace — sonst werden gecachte/alte Styles nicht überschrieben
    const old = document.getElementById('sticky-styles');
    if (old) old.remove();
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

      /* Left-Sticky: wird per JS (transform-based) erledigt, kein CSS hier nötig.
         Hover-State trotzdem unterstützen, damit die fixierten Cells mit dem Rest highlighten. */
      #main-gantt tbody tr.task-row:hover > td:nth-child(-n+4) {
        background-color: #fafbfc !important;
      }

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
    const fixedCells = cloneHeader.querySelectorAll('.gsh-th');
    fixedCells.forEach((th, i) => {
      if (colWidths[i] != null) th.style.width = colWidths[i] + 'px';
    });
  }

  // Frozen-Cols via Wrapper-Div + CSS position:sticky (NICHT transform).
  // position:sticky läuft synchron mit dem nativen Scroll → kein Jitter.
  // Wir wickeln den td-Inhalt in ein <div>, das die Sticky-Eigenschaft trägt
  // (sticky direkt auf <td> ist in Browsern wegen display:table-cell unzuverlässig).
  function setupFrozenCols() {
    const wrap = document.querySelector('.gantt-wrap');
    const table = document.getElementById('main-gantt');
    if (!wrap || !table) return;

    if (table.dataset.frozenInit === '1') {
      // schon initialisiert → nur Sticky-Lefts neu setzen (Spaltenbreiten könnten sich geändert haben)
      const colWidths = measureColumnWidths(table);
      if (colWidths) applyStickyLefts(colWidths);
      return;
    }
    table.dataset.frozenInit = '1';

    function wrapCell(td, opts) {
      if (td.querySelector(':scope > .frozen-content')) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'frozen-content';
      wrapper.dataset.fci = String(opts.idx);  // 0..3 für task-row; "all" für section/kfw
      wrapper.style.cssText = [
        'display:block',
        'position:sticky',
        // 'left' wird nachher via applyStickyLefts gesetzt
        'background:' + (opts.bg || '#fff'),
        'z-index:' + (opts.z || 5),
        'min-height:100%',
        'box-sizing:border-box',
        opts.shadow ? 'box-shadow:6px 0 12px -4px rgba(0,0,0,0.06)' : ''
      ].filter(Boolean).join(';');
      while (td.firstChild) wrapper.appendChild(td.firstChild);
      td.appendChild(wrapper);
      td.style.position = 'relative';
      td.style.overflow = 'visible';   // Wrapper darf aus td "rausschauen" wenn sticky
    }

    // Task-Rows
    table.querySelectorAll('tbody tr.task-row').forEach((row) => {
      const tds = row.children;
      for (let i = 0; i < 4 && i < tds.length; i++) {
        wrapCell(tds[i], { bg: '#fff', z: 5, shadow: (i === 3), idx: i });
      }
    });

    // Section-Rows
    table.querySelectorAll('tbody tr.section-row > td:first-child').forEach((td) => {
      wrapCell(td, { bg: '#f8fafc', z: 6, shadow: true, idx: 'sec' });
    });

    // KFW-Rows (Hintergrund vom row übernehmen)
    table.querySelectorAll('tbody tr.kfw-header-row > td:first-child').forEach((td) => {
      const rowBg = getComputedStyle(td.parentElement).backgroundColor || '#1e293b';
      wrapCell(td, { bg: rowBg, z: 6, shadow: true, idx: 'kfw' });
    });

    const colWidths = measureColumnWidths(table);
    if (colWidths) applyStickyLefts(colWidths);
  }

  function applyStickyLefts(colWidths) {
    // Cumulative left-Werte pro Spalte
    const lefts = [
      0,
      colWidths[0],
      colWidths[0] + colWidths[1],
      colWidths[0] + colWidths[1] + colWidths[2],
    ];

    document.querySelectorAll('.frozen-content').forEach((w) => {
      const idx = w.dataset.fci;
      if (idx === 'sec' || idx === 'kfw') {
        w.style.left = '0';
      } else {
        const i = parseInt(idx, 10);
        if (!isNaN(i) && lefts[i] != null) {
          w.style.left = lefts[i] + 'px';
        }
      }
    });
  }

  let resizeTimer = null;
  function scheduleApply(delay = 50) {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      applyPageSticky();
      remeasureClone();
      setupFrozenCols();
    }, delay);
  }

  function init() {
    injectBaseCSS();
    applyPageSticky();
    buildCloneHeader();
    setupFrozenCols();
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

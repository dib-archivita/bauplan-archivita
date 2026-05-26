/**
 * sticky.js — Top-Sticky-Header + KW/Monat-Spaltenkopf
 *
 * Lösung für 2 Probleme:
 *  (a) Header / Stats / Tabs / Filter sollen beim Scrollen oben kleben
 *  (b) Tabellenkopf (Monat-Labels + KW-Labels + Aufgabe/Status/Gewerk/Firma)
 *      soll auch oben kleben — geht aber NICHT mit normalem position:sticky,
 *      weil .gantt-wrap overflow-x:auto hat (das bricht sticky für descendants).
 *
 * (a) wird mit position:sticky + kumulativem top: gelöst (DOM-Reihenfolge).
 * (b) wird mit einem CLONED header gelöst, der OBERHALB der .gantt-wrap eingefügt
 *     wird (also außerhalb des Overflow-Containers). Der Clone wird per JS
 *     horizontal synchron zur .gantt-wrap gescrollt.
 */
(function () {
  'use strict';

  const STICKY_SELECTOR = '.header, .summary, .tabs, .filter-bar';
  let cumulativeTop = 0;
  let originalThead = null;
  let cloneHeader = null;
  let scrollSyncer = null;

  function injectBaseCSS() {
    if (document.getElementById('sticky-styles')) return;
    const s = document.createElement('style');
    s.id = 'sticky-styles';
    s.textContent = `
      /* Original-thead wird verborgen sobald Clone aktiv ist —
         bleibt aber als Layout-Anker für Spaltenbreiten erhalten */
      #main-gantt.has-sticky-clone thead { visibility: hidden; }

      /* Sticky Clone Container */
      #gantt-sticky-header {
        position: sticky;
        z-index: 30;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        overflow: hidden;
        will-change: transform;
      }
      #gantt-sticky-header .gsh-inner-table {
        margin: 0; border-collapse: collapse;
        will-change: transform;
      }

      /* gantt-wrap muss overflow-x:auto haben für horizontal scroll */
      .gantt-wrap { overflow-x: auto; overflow-y: visible; }

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
        el.style.position = '';
        el.style.top = '';
        el.style.zIndex = '';
        return;
      }
      el.style.position = 'sticky';
      el.style.top = cumulativeTop + 'px';
      el.style.zIndex = String(zIndex--);
      if (!el.style.background) el.style.background = '#fff';

      const h = el.getBoundingClientRect().height;
      cumulativeTop += h;
    });

    // Clone-Header positionieren
    if (cloneHeader) {
      cloneHeader.style.top = cumulativeTop + 'px';
    }
  }

  function measureOriginalColumns(table) {
    // Hole eine Referenz-Zeile aus dem tbody zum Messen
    // (thead hat zusammengefasste cells, tbody hat alle Spalten)
    const refRow = table.querySelector('tbody tr.task-row');
    if (!refRow) return null;
    const widths = [];
    refRow.querySelectorAll('td').forEach(td => {
      widths.push(td.getBoundingClientRect().width);
    });
    return widths;
  }

  function buildCloneHeader() {
    const wrap = document.querySelector('.gantt-wrap');
    const table = document.getElementById('main-gantt');
    if (!wrap || !table) return;

    originalThead = table.querySelector('thead');
    if (!originalThead) return;

    // Existing Clone weg
    const oldClone = document.getElementById('gantt-sticky-header');
    if (oldClone) oldClone.remove();

    // Spalten-Breiten aus dem Original messen
    const colWidths = measureOriginalColumns(table);

    // Container
    cloneHeader = document.createElement('div');
    cloneHeader.id = 'gantt-sticky-header';

    // Mini-Tabelle mit fixiertem Layout + explizite Spaltenbreiten
    const miniTable = document.createElement('table');
    miniTable.className = 'gantt-table gsh-inner-table';
    miniTable.style.tableLayout = 'fixed';
    miniTable.style.borderCollapse = 'collapse';

    // colgroup mit den GEMESSENEN Breiten (überschreibt das Auto-Layout)
    const newCg = document.createElement('colgroup');
    if (colWidths && colWidths.length) {
      colWidths.forEach((w) => {
        const c = document.createElement('col');
        c.style.width = w + 'px';
        newCg.appendChild(c);
      });
    } else {
      // Fallback: original colgroup klonen
      const cg = table.querySelector('colgroup');
      if (cg) {
        Array.from(cg.children).forEach(col => newCg.appendChild(col.cloneNode(true)));
      }
    }
    miniTable.appendChild(newCg);

    // thead klonen
    const theadClone = originalThead.cloneNode(true);
    miniTable.appendChild(theadClone);

    // Gesamtbreite = Summe der gemessenen Breiten (oder Fallback)
    const totalWidth = colWidths
      ? colWidths.reduce((a, b) => a + b, 0)
      : table.scrollWidth;
    miniTable.style.width = totalWidth + 'px';

    cloneHeader.appendChild(miniTable);

    // Vor .gantt-wrap einfügen (so dass es im Page-Sticky-Flow ist)
    wrap.parentNode.insertBefore(cloneHeader, wrap);

    // Original-thead optisch verstecken (Platz bleibt erhalten)
    table.classList.add('has-sticky-clone');

    // Horizontal-Scroll synchronisieren
    if (scrollSyncer) wrap.removeEventListener('scroll', scrollSyncer);
    scrollSyncer = () => {
      const x = wrap.scrollLeft;
      miniTable.style.transform = `translateX(${-x}px)`;
    };
    wrap.addEventListener('scroll', scrollSyncer, { passive: true });
  }

  function remeasureClone() {
    if (!cloneHeader) return;
    const table = document.getElementById('main-gantt');
    if (!table) return;
    const colWidths = measureOriginalColumns(table);
    if (!colWidths) return;
    const inner = cloneHeader.querySelector('.gsh-inner-table');
    if (!inner) return;

    const cg = inner.querySelector('colgroup');
    if (cg) {
      const cols = cg.querySelectorAll('col');
      colWidths.forEach((w, i) => {
        if (cols[i]) cols[i].style.width = w + 'px';
      });
    }
    inner.style.width = colWidths.reduce((a, b) => a + b, 0) + 'px';
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
    applyPageSticky(); // nochmal, damit clone-header die richtige top hat

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

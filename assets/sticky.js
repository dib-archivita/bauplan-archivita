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

      /* Two-Pane: linke pinned-pane + rechte scroll-wrap */
      .gantt-flex { display: flex; position: relative; align-items: stretch; }

      /* Pinned-Tabelle teilt sich Styling mit Original */
      #pinned-pane .pinned-table { font-size: 11.5px; }
      #pinned-pane thead th { padding: 10px 12px !important; background: #fafbfc !important; font-size: 10.5px !important; text-transform: uppercase; font-weight: 700; color: #475569; }
      #pinned-pane td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
      #pinned-pane tr.section-row td { background: #f8fafc; font-weight: 700; }
      #pinned-pane tr.kfw-header-row td { color: #fff; font-weight: 700; padding: 9px 12px; }
      #pinned-pane tr.kfw-header-row.kfw-a td { background: #2563eb; }
      #pinned-pane tr.kfw-header-row.kfw-b td { background: #7c3aed; }
      #pinned-pane tr.kfw-header-row.kfw-c td { background: #ea580c; }

      /* Hauptzeitplan: erste 4 Spalten ausblenden wenn pinned-pane aktiv */
      #main-gantt.has-pinned > colgroup > col:nth-child(-n+4) { width: 0 !important; }
      #main-gantt.has-pinned > thead > tr > th:nth-child(-n+4) { display: none; }
      #main-gantt.has-pinned > tbody > tr.task-row > td:nth-child(-n+4) { display: none; }
      #main-gantt.has-pinned > tbody > tr.section-row > td.section-name { display: none; }
      #main-gantt.has-pinned > tbody > tr.kfw-header-row > td:first-child { display: none; }

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

  /**
   * Frozen-Cols via Two-Pane Layout (Linear / Airtable Pattern).
   *
   * Statt position:sticky auf td (unzuverlässig) machen wir eine sauber
   * getrennte Layout-Struktur:
   *
   *   .gantt-flex (flex container)
   *     ├─ .pinned-pane  (linke Pane, flex:none, sticky-left zum Viewport)
   *     │     Clone der ersten 4 Spalten als eigene Tabelle
   *     │     Kein horizontaler Scroll
   *     └─ .gantt-wrap   (rechte Pane, flex:1, overflow-x:auto)
   *           Bestehende Haupt-Tabelle mit allen Spalten
   *           Erste 4 Spalten werden CSS-versteckt (visibility:hidden, Platz bleibt)
   *
   * Beide Panes scrollen vertikal mit der Seite (kein eigener vertical scroll).
   * Row-Höhen müssen identisch sein — wir messen das Original und setzen
   * die Pin-Pane row-Heights via inline style.
   */
  let pinnedPane = null;
  let pinnedSyncObserver = null;

  function buildFrozenCols() {
    const wrap = document.querySelector('.gantt-wrap');
    const table = document.getElementById('main-gantt');
    if (!wrap || !table) return;

    // Existing pane entfernen
    const oldPane = document.getElementById('pinned-pane');
    if (oldPane) oldPane.remove();
    table.classList.remove('has-pinned');

    // Wrap das gantt-wrap nicht doppelt ein
    let flex = wrap.parentElement;
    if (!flex.classList.contains('gantt-flex')) {
      flex = document.createElement('div');
      flex.className = 'gantt-flex';
      flex.style.cssText = 'display:flex;position:relative;align-items:stretch';
      wrap.parentNode.insertBefore(flex, wrap);
      flex.appendChild(wrap);
    }

    // Spaltenbreiten aus Original messen
    const colWidths = measureOriginalColumns(table);
    if (!colWidths) return;
    const totalPinnedWidth = colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3];

    // Pinned-Pane (linke Tabelle = Clone der ersten 4 cols)
    pinnedPane = document.createElement('div');
    pinnedPane.id = 'pinned-pane';
    pinnedPane.style.cssText = [
      'flex:none',
      'width:' + totalPinnedWidth + 'px',
      'background:#fff',
      'position:relative',
      'z-index:10',
      'box-shadow:6px 0 12px -4px rgba(0,0,0,0.08)',
      'overflow:hidden'   // verhindert dass cloned content rauslappt
    ].join(';');

    const pinnedTable = document.createElement('table');
    pinnedTable.className = 'gantt-table pinned-table';
    pinnedTable.style.cssText = 'width:100%;table-layout:fixed;border-collapse:collapse;margin:0';
    // colgroup mit den ersten 4 Breiten
    const newCg = document.createElement('colgroup');
    for (let i = 0; i < 4; i++) {
      const c = document.createElement('col');
      c.style.width = colWidths[i] + 'px';
      newCg.appendChild(c);
    }
    pinnedTable.appendChild(newCg);

    // Kein thead in der pinned-table — die sticky-clone-header oben übernimmt das schon.

    // Body: für jede Row im Original eine entsprechende Row im Clone
    const newTbody = document.createElement('tbody');
    const origTbody = table.querySelector('tbody');
    if (origTbody) {
      Array.from(origTbody.children).forEach((origRow) => {
        const newRow = document.createElement('tr');
        // Klassen + data-attribute übernehmen für Sync (z.B. data-tid)
        newRow.className = origRow.className;
        Array.from(origRow.attributes).forEach(attr => {
          if (attr.name !== 'style') newRow.setAttribute(attr.name, attr.value);
        });
        // Höhe vom Original einfrieren (für vertikale Alignment-Synchronität)
        const rowHeight = origRow.getBoundingClientRect().height;
        newRow.style.height = rowHeight + 'px';

        const origCells = origRow.children;
        if (origRow.classList.contains('kfw-header-row') || origRow.classList.contains('section-row')) {
          // Diese Rows haben colspan-cell als erstes
          if (origCells[0]) {
            const newCell = origCells[0].cloneNode(true);
            // Setze colspan auf 4 (oder weniger), da pinned-table nur 4 cols hat
            newCell.setAttribute('colspan', '4');
            newRow.appendChild(newCell);
          }
        } else {
          // task-row: erste 4 cells klonen
          for (let i = 0; i < 4 && i < origCells.length; i++) {
            newRow.appendChild(origCells[i].cloneNode(true));
          }
        }
        newTbody.appendChild(newRow);
      });
    }
    pinnedTable.appendChild(newTbody);
    pinnedPane.appendChild(pinnedTable);
    flex.insertBefore(pinnedPane, wrap);

    // Im Hauptzeitplan die ersten 4 Spalten visuell ausblenden
    // (Platz bleibt erhalten damit Layout stimmt, aber Inhalt ist hidden
    //  damit nichts doppelt sichtbar ist)
    table.classList.add('has-pinned');

    // Sticky-Verhalten: pinned-pane stickt links zum gantt-flex
    // pinned-pane ist NICHT in einer overflow-Box → kann am body scrollen vertikal
    pinnedPane.style.position = 'sticky';
    pinnedPane.style.left = '0';
  }

  function init() {
    injectBaseCSS();
    applyPageSticky();
    buildCloneHeader();
    buildFrozenCols();      // NEU: Two-Pane Layout (linke Pin-Pane + rechte Scroll-Pane)
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

/**
 * sticky.js — Top-Bar Sticky-Stacking
 *
 * Iteriert ALLE Sticky-Kandidaten (.header, .summary, .tabs, .filter-bar)
 * in DOM-Reihenfolge und setzt `top:` kumulativ. So stacken sie sich beim
 * Scrollen exakt aufeinander, egal wie viele filter-bars (z.B. Gewerk +
 * Status) da sind. Danach kommt der gantt-table thead darunter sticky.
 *
 * Re-Run bei Resize + Tab-Wechsel + Filter-Klick.
 */
(function () {
  'use strict';

  const STICKY_SELECTOR = '.header, .summary, .tabs, .filter-bar';

  function injectBaseCSS() {
    if (document.getElementById('sticky-styles')) return;
    const s = document.createElement('style');
    s.id = 'sticky-styles';
    s.textContent = `
      /* Tabellenkopf sticky-Default */
      #main-gantt thead { position: sticky; z-index: 30;
                          background: #fff;
                          box-shadow: 0 1px 0 #e2e8f0; }
      #main-gantt thead th { background: #fafbfc !important; }
      /* gantt-wrap braucht overflow-x für horizontal-scroll, y muss sichtbar bleiben */
      .gantt-wrap { overflow-x: auto; overflow-y: visible; }

      /* Mobile-Optimierungen für die Sticky-Bar */
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

  function applySticky() {
    // Sammele die Elemente in DOM-Reihenfolge — Inline-Styles statt CSS-Vars
    // damit jeder Filter-Bar etc. seinen EIGENEN top:-Wert bekommt.
    const stickies = Array.from(document.querySelectorAll(STICKY_SELECTOR));
    let cumulativeTop = 0;
    let zIndex = 50;

    stickies.forEach((el) => {
      // Nicht alle Treffer sind im Hauptseiten-Layout — nur die innerhalb body, NICHT
      // Inhalts-Tabs außerhalb des aktuellen Tabs.
      // Wir filtern: Eltern .tab-content visible? Ansonsten ignorieren.
      const inHiddenTab = el.closest('.tab-content:not(.active)') !== null;
      if (inHiddenTab) {
        // einfach nicht-sticky lassen
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

    // gantt-table thead sticky direkt darunter
    document.querySelectorAll('#main-gantt thead').forEach((thead) => {
      thead.style.position = 'sticky';
      thead.style.top = cumulativeTop + 'px';
      thead.style.zIndex = '29';
      thead.style.background = '#fff';
    });
  }

  let resizeTimer = null;
  function scheduleApply(delay = 50) {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(applySticky, delay);
  }

  function init() {
    injectBaseCSS();
    applySticky();
    window.addEventListener('resize', () => scheduleApply(100));
    // Bei Tab-/Filter-Klicks Inhalte können sich ändern → re-measure
    document.addEventListener('click', () => scheduleApply(150), true);
    // Nach allen Fonts geladen → re-measure
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(() => scheduleApply(30));
    }
    // Sicherheits-Re-Run nach 500ms (für späten DOM-Aufbau durch sync.js / admin.js)
    setTimeout(applySticky, 500);
    setTimeout(applySticky, 1500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * sticky.js — Macht Header / Tabs / Filter / KW-Header beim Scrollen sticky.
 *
 * Funktionsweise:
 *  - Misst nach DOM-Ready & beim Resize die Höhe von .header, .summary,
 *    .tabs und .filter-bar
 *  - Setzt CSS-Variablen mit den kumulativen Top-Offsets
 *  - Stellt für jedes Element + die gantt-table thead position:sticky her
 *  - Funktioniert mobile und desktop, ohne Hardcoded-Pixel
 */
(function () {
  'use strict';

  function inject() {
    if (document.getElementById('sticky-styles')) return;
    const s = document.createElement('style');
    s.id = 'sticky-styles';
    s.textContent = `
      .header,
      .summary,
      .tabs,
      .filter-bar {
        position: sticky !important;
        z-index: 40;
        background: #fff;
      }
      .header   { top: 0; z-index: 44; }
      .summary  { top: var(--st-h, 60px); z-index: 43; }
      .tabs     { top: var(--st-hs, 130px); z-index: 42; }
      .filter-bar { top: var(--st-hst, 175px); z-index: 41;
                    box-shadow: 0 1px 0 #e2e8f0; }

      /* Tabellenkopf darunter sticky */
      #main-gantt thead { position: sticky; top: var(--st-all, 220px); z-index: 39;
                          background: #fff; box-shadow: 0 1px 0 #e2e8f0; }
      #main-gantt thead th { background: #fafbfc !important; }

      /* gantt-wrap braucht overflow:auto damit horizontal scrollt — aber thead muss durch */
      .gantt-wrap { overflow-x: auto; overflow-y: visible; }

      /* Mobile: Stat-Cards kleiner */
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

        /* User-bar + Suchleiste kompakter */
        #user-bar { top: 6px !important; right: 8px !important; }
        #user-bar .ub-name { display: none; }
        #global-search { top: 38px !important; right: 8px !important;
                         left: 8px !important; min-width: auto !important;
                         max-width: calc(100vw - 16px) !important; }
      }
    `;
    document.head.appendChild(s);
  }

  function measure() {
    const h = (sel) => {
      const el = document.querySelector(sel);
      return el ? el.getBoundingClientRect().height : 0;
    };
    const hH = h('.header');
    const hS = h('.summary');
    const hT = h('.tabs');
    const hF = h('.filter-bar');

    const root = document.documentElement;
    root.style.setProperty('--st-h',   hH + 'px');
    root.style.setProperty('--st-hs',  (hH + hS) + 'px');
    root.style.setProperty('--st-hst', (hH + hS + hT) + 'px');
    root.style.setProperty('--st-all', (hH + hS + hT + hF) + 'px');
  }

  function init() {
    inject();
    measure();
    let resizeTimer = null;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(measure, 80);
    });
    // Nach Tab-Wechsel / Filter-Klick neu messen (DOM könnte sich verändern)
    document.addEventListener('click', () => setTimeout(measure, 100), true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

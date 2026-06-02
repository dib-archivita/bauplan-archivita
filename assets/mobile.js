/**
 * mobile.js — Mobile-Specifika
 *
 *  • Service-Worker registrieren (PWA)
 *  • „Heute"-FAB: springt zur aktuellen KW in der Timeline
 *  • Touch-Target-Optimierungen
 *  • Pinch-Zoom für Gantt-Bereich
 *  • Pull-To-Refresh suppression (verhindert unbeabsichtigte Page-Reloads)
 *  • Visuelles Tap-Feedback
 */
(function () {
  'use strict';

  // ── 1. Service Worker registrieren + Auto-Update ──────────────────
  if ('serviceWorker' in navigator) {
    let reloadingForSW = false;

    function reloadOnce() {
      if (reloadingForSW) return;
      reloadingForSW = true;
      // Einmaliger Reload, damit der Tab die neuen Assets lädt
      window.location.reload();
    }

    // Wenn ein neuer SW die Kontrolle übernimmt → Seite einmal neu laden
    navigator.serviceWorker.addEventListener('controllerchange', reloadOnce);

    // sw.js postet bei activate {type:'sw-updated'}
    navigator.serviceWorker.addEventListener('message', (e) => {
      if (e.data && e.data.type === 'sw-updated') reloadOnce();
    });

    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').then((reg) => {
        // Regelmäßig auf neue Version prüfen (alle 60s) + sofort beim Laden
        reg.update().catch(() => {});
        setInterval(() => reg.update().catch(() => {}), 60000);
        // Neuer SW gefunden → installiert → aktivieren lassen (skipWaiting in sw.js)
        reg.addEventListener('updatefound', () => {
          const nw = reg.installing;
          if (!nw) return;
          nw.addEventListener('statechange', () => {
            if (nw.state === 'activated' && navigator.serviceWorker.controller) reloadOnce();
          });
        });
      }).catch(() => {});
    });
  }

  // ── 2. CSS-Helpers für Mobile ─────────────────────────────────────
  function injectMobileCSS() {
    if (document.getElementById('mobile-styles')) return;
    const s = document.createElement('style');
    s.id = 'mobile-styles';
    s.textContent = `
      /* "Heute"-FAB */
      #today-fab {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 100;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 12px 22px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.03em;
        cursor: pointer;
        box-shadow: 0 6px 24px rgba(239, 68, 68, .35), 0 2px 6px rgba(0,0,0,.15);
        display: none;
        align-items: center;
        gap: 6px;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
      }
      #today-fab:active { transform: translateX(-50%) scale(0.95); }
      #today-fab.visible { display: inline-flex; }

      /* Snap-Scroll für Tabs auf Mobile */
      @media (max-width: 760px) {
        .tabs { scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
        .tab  { scroll-snap-align: start; }
      }

      /* Vergrößerte Touch-Targets */
      @media (pointer: coarse) {
        .status-badge,
        .filter-btn,
        .gewerk-badge,
        .tab,
        button {
          min-height: 36px;
          touch-action: manipulation;
          -webkit-tap-highlight-color: rgba(37, 99, 235, .15);
        }
        .gantt-bar { min-height: 24px; touch-action: manipulation; }
        .task-name-cell { padding: 10px 8px !important; }
        .status-badge { padding: 5px 12px !important; font-size: 11px !important; }
      }

      /* Tap-Feedback: kurze Hervorhebung */
      .tap-flash { animation: tap-flash .25s ease-out; }
      @keyframes tap-flash {
        0%   { background: rgba(37, 99, 235, .15); }
        100% { background: transparent; }
      }

      /* Hover-only-Effekte für Touch deaktivieren */
      @media (hover: none) {
        .gantt-bar:hover { transform: none !important; box-shadow: none !important; }
        .task-row:hover  { background: transparent !important; }
        .summary .card:hover { transform: none !important; }
        button:hover { transform: none !important; }
      }

      /* Pull-to-refresh / Bounce verhindern */
      html, body {
        overscroll-behavior-y: contain;
      }
    `;
    document.head.appendChild(s);
  }

  // ── 3. "Heute"-FAB ────────────────────────────────────────────────
  function setupTodayFAB() {
    const ORIGIN_KW = 23;
    const PX_PER_WEEK = 126;
    const origin = new Date(2026, 5, 1); // 1. Juni 2026 = KW23 Montag

    const fab = document.createElement('button');
    fab.id = 'today-fab';
    fab.innerHTML = '📍 Heute';
    fab.title = 'Zur aktuellen Woche springen';
    document.body.appendChild(fab);

    function jumpToToday() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const days = Math.round((today - origin) / 86400000);
      const px = (days / 7) * PX_PER_WEEK;

      // Gantt horizontal scrollen
      const wrap = document.querySelector('.gantt-wrap');
      if (wrap) {
        wrap.scrollTo({ left: Math.max(0, px - 200), behavior: 'smooth' });
      }
      // Page-vertikal zum Hauptzeitplan
      const tabActive = document.getElementById('tab-hauptwerk');
      if (tabActive && !tabActive.classList.contains('active')) {
        const tabBtn = document.querySelector('.tab[onclick*="hauptwerk"]');
        if (tabBtn) tabBtn.click();
      }
    }

    fab.addEventListener('click', jumpToToday);

    // Zeige FAB nur wenn man weit weg von "Heute" gescrollt ist
    const wrap = document.querySelector('.gantt-wrap');
    if (!wrap) {
      fab.classList.add('visible');
      return;
    }
    function checkVisible() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const days = Math.round((today - origin) / 86400000);
      const todayPx = (days / 7) * PX_PER_WEEK;
      const visibleStart = wrap.scrollLeft;
      const visibleEnd = visibleStart + wrap.clientWidth;
      const tooFar = todayPx < visibleStart - 100 || todayPx > visibleEnd + 100;
      fab.classList.toggle('visible', tooFar);
    }
    wrap.addEventListener('scroll', checkVisible, { passive: true });
    setTimeout(checkVisible, 200);
  }

  // ── 4. Tap-Feedback für Tasks ─────────────────────────────────────
  function setupTapFeedback() {
    document.addEventListener('touchstart', (e) => {
      const row = e.target.closest('.task-row');
      if (row) {
        row.classList.add('tap-flash');
        setTimeout(() => row.classList.remove('tap-flash'), 300);
      }
    }, { passive: true });
  }

  // ── 5. Pinch-Zoom für Gantt ───────────────────────────────────────
  function setupPinchZoom() {
    const wrap = document.querySelector('.gantt-wrap');
    if (!wrap) return;

    let initialDistance = 0;
    let currentZoom = 1;
    let pinching = false;

    function distance(t) {
      const dx = t[0].clientX - t[1].clientX;
      const dy = t[0].clientY - t[1].clientY;
      return Math.hypot(dx, dy);
    }

    wrap.addEventListener('touchstart', (e) => {
      if (e.touches.length === 2) {
        initialDistance = distance(e.touches);
        pinching = true;
      }
    }, { passive: true });

    wrap.addEventListener('touchmove', (e) => {
      if (!pinching || e.touches.length !== 2) return;
      e.preventDefault();
      const d = distance(e.touches);
      const ratio = d / initialDistance;
      currentZoom = Math.max(0.5, Math.min(2.5, currentZoom * ratio));
      initialDistance = d;
      const table = wrap.querySelector('.gantt-table');
      if (table) {
        table.style.transformOrigin = '0 0';
        table.style.transform = `scaleX(${currentZoom})`;
      }
    }, { passive: false });

    wrap.addEventListener('touchend', () => {
      pinching = false;
    });

    // Double-Tap = Zoom-Reset
    let lastTap = 0;
    wrap.addEventListener('touchend', (e) => {
      const now = Date.now();
      if (now - lastTap < 300 && e.touches.length === 0) {
        currentZoom = 1;
        const table = wrap.querySelector('.gantt-table');
        if (table) table.style.transform = 'scaleX(1)';
      }
      lastTap = now;
    });
  }

  // ── 6. iOS Safari: 100vh-Fix ──────────────────────────────────────
  function setupIOSHeight() {
    const setVh = () => {
      document.documentElement.style.setProperty('--vh', window.innerHeight * 0.01 + 'px');
    };
    setVh();
    window.addEventListener('resize', setVh);
    window.addEventListener('orientationchange', setVh);
  }

  // ── Init ──────────────────────────────────────────────────────────
  function init() {
    injectMobileCSS();
    setupTodayFAB();
    setupTapFeedback();
    setupPinchZoom();
    setupIOSHeight();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * search.js — Globale Suche über alles
 *
 * - Suchfeld oben rechts neben User-Bar
 * - Cmd/Ctrl+K öffnet/fokussiert
 * - Filtert in Echtzeit:
 *   • Hauptzeitplan (task-rows + sections)
 *   • TODs-Tab
 *   • Einheiten-Tab
 *   • Budget-Tab
 *   • Kapazität-Tab
 *   • Bestellungen-Tab
 *  - Sections / Phasen werden ausgeblendet wenn keine Treffer drin
 *  - ESC oder X räumt auf
 */
(function () {
  'use strict';

  const KEY_NORMS = (s) => (s || '').toString().toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/ä/g, 'a').replace(/ö/g, 'o').replace(/ü/g, 'u').replace(/ß/g, 'ss');

  function init() {
    injectUI();
    bindShortcuts();
  }

  function injectUI() {
    // Suchfeld im DOM neben user-bar
    const ub = document.getElementById('user-bar');
    const wrap = document.createElement('div');
    wrap.id = 'global-search';
    wrap.innerHTML = `
      <input type="text" id="gs-input"
             placeholder="🔍 Suche … (⌘K)"
             aria-label="Suche im Bauzeitenplan">
      <button id="gs-clear" aria-label="Löschen" title="Löschen (Esc)">✕</button>
      <span id="gs-count" aria-live="polite"></span>
    `;
    document.body.appendChild(wrap);

    const style = document.createElement('style');
    style.textContent = `
      #global-search { position: fixed; top: 12px; right: 200px; z-index: 9998;
        font-family: 'Inter', sans-serif; display: flex; align-items: center;
        gap: 4px; background: #fff; border: 1px solid #e8e9ed; border-radius: 999px;
        box-shadow: 0 2px 10px rgba(15,23,42,.08); padding: 4px 8px 4px 10px;
        min-width: 240px; transition: min-width .15s; }
      #global-search:focus-within { min-width: 360px; }
      #gs-input { border: none; outline: none; background: transparent; flex: 1;
        font-family: inherit; font-size: 13px; padding: 4px 0; min-width: 0; }
      #gs-clear { background: #f1f5f9; border: none; width: 22px; height: 22px;
        border-radius: 50%; cursor: pointer; font-size: 11px; color: #64748b;
        display: none; padding: 0; line-height: 22px; }
      #gs-clear:hover { background: #e2e8f0; color: #1e293b; }
      #global-search.active #gs-clear { display: inline-block; }
      #gs-count { font-size: 11px; color: #64748b; font-weight: 600;
        white-space: nowrap; margin-left: 4px; min-width: 50px; text-align: right; }

      /* Highlight */
      mark.gs-hit { background: #fef3c7; color: #92400e; padding: 0 2px;
        border-radius: 3px; font-weight: 600; }

      /* Versteckte Zeilen */
      .gs-hide { display: none !important; }

      /* Phasen / Sections die keine Treffer haben */
      .gs-hide-section { display: none !important; }

      @media (max-width: 900px) {
        #global-search { right: 12px; top: 56px; min-width: 200px; }
      }
    `;
    document.head.appendChild(style);

    const input = wrap.querySelector('#gs-input');
    const clear = wrap.querySelector('#gs-clear');
    const count = wrap.querySelector('#gs-count');

    let debounce = null;
    input.addEventListener('input', () => {
      wrap.classList.toggle('active', !!input.value);
      clearTimeout(debounce);
      debounce = setTimeout(() => doSearch(input.value, count), 120);
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (input.value) {
          input.value = '';
          wrap.classList.remove('active');
          doSearch('', count);
        } else {
          input.blur();
        }
      }
    });
    clear.addEventListener('click', () => {
      input.value = '';
      wrap.classList.remove('active');
      input.focus();
      doSearch('', count);
    });
  }

  function bindShortcuts() {
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const inp = document.getElementById('gs-input');
        if (inp) { inp.focus(); inp.select(); }
      }
    });
  }

  function unmarkAll(root) {
    root.querySelectorAll('mark.gs-hit').forEach(m => {
      const t = document.createTextNode(m.textContent);
      m.parentNode.replaceChild(t, m);
    });
  }

  function markInElement(el, normQuery, rawQuery) {
    // Nur Textnodes durchsuchen, die nicht im script/style sind
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
      acceptNode(n) {
        const p = n.parentNode;
        if (!p) return NodeFilter.FILTER_REJECT;
        const tag = p.nodeName.toLowerCase();
        if (tag === 'script' || tag === 'style' || tag === 'mark') return NodeFilter.FILTER_REJECT;
        if (n.nodeValue.trim().length === 0) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      },
    });
    const matches = [];
    let cur;
    while ((cur = walker.nextNode())) {
      const norm = KEY_NORMS(cur.nodeValue);
      if (norm.includes(normQuery)) matches.push(cur);
    }
    matches.forEach(textNode => {
      const original = textNode.nodeValue;
      const normOrig = KEY_NORMS(original);
      const idx = normOrig.indexOf(normQuery);
      if (idx < 0) return;
      const before = original.slice(0, idx);
      const hit = original.slice(idx, idx + rawQuery.length);
      const after = original.slice(idx + rawQuery.length);
      const frag = document.createDocumentFragment();
      if (before) frag.appendChild(document.createTextNode(before));
      const mark = document.createElement('mark');
      mark.className = 'gs-hit';
      mark.textContent = hit;
      frag.appendChild(mark);
      if (after) frag.appendChild(document.createTextNode(after));
      textNode.parentNode.replaceChild(frag, textNode);
    });
  }

  function doSearch(q, countEl) {
    const root = document.body;
    // alte Markierungen weg
    unmarkAll(root);
    // alle versteckten zurücksetzen
    root.querySelectorAll('.gs-hide, .gs-hide-section').forEach(el => {
      el.classList.remove('gs-hide', 'gs-hide-section');
    });

    if (!q || q.length < 2) {
      countEl.textContent = q ? `${q.length}/2 Zeichen` : '';
      return;
    }
    const norm = KEY_NORMS(q);
    let hits = 0;

    // Suche durchführen über alle relevanten Container
    const containers = [
      ...document.querySelectorAll('tr.task-row'),       // Hauptzeitplan-Aufgaben
      ...document.querySelectorAll('tr.section-row'),    // Sections behalten (zeig dropdown rein/raus)
      ...document.querySelectorAll('tr.kfw-header-row'), // KfW-Banner — bleiben sichtbar
      ...document.querySelectorAll('[data-search-row]'), // Custom rows (z.B. Einheiten, Bestellungen)
      ...document.querySelectorAll('#tab-todos tr'),
      ...document.querySelectorAll('#tab-wohnungen tr'),
      ...document.querySelectorAll('#tab-kosten tr'),
      ...document.querySelectorAll('#tab-bestellungen tr'),
      ...document.querySelectorAll('#tab-kapazitaet tr'),
    ];

    // Pass 1: task-rows verstecken wenn kein Match
    document.querySelectorAll('tr.task-row').forEach(row => {
      const txt = KEY_NORMS(row.textContent);
      // Auch data-attributes durchsuchen
      const dataExtra = KEY_NORMS([row.dataset.gewerk, row.dataset.unit, row.dataset.tid, row.dataset.taskType].join(' '));
      const match = txt.includes(norm) || dataExtra.includes(norm);
      if (match) {
        hits++;
        markInElement(row, norm, q);
      } else {
        row.classList.add('gs-hide');
      }
    });

    // Pass 2: section-rows ausblenden falls keine task-row mehr in folgender Gruppe sichtbar ist
    document.querySelectorAll('tr.section-row').forEach(sec => {
      let next = sec.nextElementSibling;
      let anyVisible = false;
      while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
        if (next.classList.contains('task-row') && !next.classList.contains('gs-hide')) {
          anyVisible = true;
          break;
        }
        next = next.nextElementSibling;
      }
      if (!anyVisible) sec.classList.add('gs-hide-section');
    });

    // Pass 3: andere Tabellen (Einheiten, Bestellungen, Kapazität, etc.)
    document.querySelectorAll('#tab-wohnungen tr, #tab-bestellungen tr, #tab-kapazitaet tr, #tab-todos tr, #tab-kosten tr').forEach(row => {
      if (!row.children.length) return;          // skip headers without cells
      const txt = KEY_NORMS(row.textContent);
      if (txt.includes(norm)) {
        hits++;
        markInElement(row, norm, q);
      } else {
        if (!row.querySelector('th')) row.classList.add('gs-hide');
      }
    });

    countEl.textContent = hits === 0 ? 'keine Treffer' : `${hits} Treffer`;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

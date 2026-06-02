/**
 * section-edit.js — Sections + KFW-Banner editierbar + "+" Buttons + Counter
 *
 *  ✏️  Section-Namen (▶ Stromversorgung) per Klick editierbar
 *  ✏️  KFW-Banner-Namen (OHG Haustechnik) per Klick editierbar
 *  ➕  "+ Aufgabe" Button pro Section
 *  ➕  "+ Bereich" Button pro KFW-Banner
 *  📊  Counter "X/Y ✓" wird automatisch aktualisiert
 *  💾  Persistierung in localStorage (Sync via API kommt später)
 *
 *  Worker / Viewer können NICHT editieren — nur Admin + Architekt.
 */
(function () {
  'use strict';

  // Liest reinen Text einer Zelle: nur direkte Text-Nodes, ohne Button-Symbole (🕐/✕)
  function plainCellText(el) {
    if (!el) return '';
    let txt = '';
    el.childNodes.forEach((n) => { if (n.nodeType === Node.TEXT_NODE) txt += n.textContent; });
    return txt.replace(/[🕐✕]/g, '').replace(/\s+/g, ' ').trim();
  }

  const STORAGE_KEY = 'section-edit-v1';

  function loadOverrides() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch (e) { return {}; }
  }
  function saveOverrides(o) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(o)); } catch (e) {}
  }
  let overrides = loadOverrides();

  // Rolle vom Body lesen (von sync.js gesetzt — kann verzögert kommen)
  function isEditor() {
    return document.body.classList.contains('role-admin')
        || document.body.classList.contains('role-architekt');
  }
  // Optimistisch: solange noch keine Rolle gesetzt ist (frühe Phase), nehmen wir
  // an, dass Editor erlaubt — die Buttons werden via CSS gehidet wenn role-worker/viewer
  function isEditorOptimistic() {
    return !document.body.classList.contains('role-worker')
        && !document.body.classList.contains('role-viewer');
  }

  // ═════════ Style ═════════
  function injectCSS() {
    if (document.getElementById('section-edit-styles')) return;
    const s = document.createElement('style');
    s.id = 'section-edit-styles';
    s.textContent = `
      /* Editierbarkeit visualisieren */
      .section-name .editable-text,
      .kfw-header-row .editable-text {
        cursor: text;
        border-radius: 4px;
        padding: 2px 6px;
        margin: -2px -6px;
        transition: background 0.12s;
      }
      .section-name .editable-text:hover,
      .kfw-header-row .editable-text:hover {
        background: rgba(37, 99, 235, 0.10);
      }
      .section-name .editable-text:focus,
      .kfw-header-row .editable-text:focus {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
        background: #fff;
        color: #1e293b;
      }
      .kfw-header-row .editable-text:focus { background: #fff; }

      /* "+" Buttons */
      .se-add-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 12px;
        padding: 3px 10px;
        border: 1px dashed #94a3b8;
        background: transparent;
        color: #64748b;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 600;
        border-radius: 999px;
        cursor: pointer;
        transition: all 0.12s;
        line-height: 1;
      }
      .se-add-btn:hover {
        border-color: #2563eb;
        color: #2563eb;
        background: #eff6ff;
      }
      .kfw-header-row .se-add-btn {
        border-color: rgba(255,255,255,0.5);
        color: rgba(255,255,255,0.95);
      }
      .kfw-header-row .se-add-btn:hover {
        border-color: #fff;
        color: #fff;
        background: rgba(255,255,255,0.15);
      }

      /* Delete-Section Button (klein, daneben) */
      .se-del-btn {
        margin-left: 6px;
        padding: 2px 6px;
        border: none;
        background: transparent;
        color: #94a3b8;
        font-size: 11px;
        cursor: pointer;
        border-radius: 4px;
        opacity: 0;
        transition: opacity 0.15s, background 0.12s;
      }
      .section-row:hover .se-del-btn,
      .kfw-header-row:hover .se-del-btn {
        opacity: 0.7;
      }
      .se-del-btn:hover {
        background: #fee2e2;
        color: #b91c1c;
        opacity: 1 !important;
      }

      /* Nicht-Editor sieht keine + Buttons / Edit-Hover / Undo-FAB */
      body.role-worker .se-add-btn,
      body.role-viewer .se-add-btn,
      body.role-worker .se-del-btn,
      body.role-viewer .se-del-btn,
      body.role-worker .se-row-del,
      body.role-viewer .se-row-del,
      body.role-worker #se-undo-fab,
      body.role-viewer #se-undo-fab { display: none !important; }
      body.role-worker .editable-text,
      body.role-viewer .editable-text { pointer-events: none; }

      /* Counter-Pill rückwirkend ansprechbar */
      .progress-pill { transition: background 0.2s; }
      .progress-pill.full {
        background: #d1fae5;
        color: #065f46;
      }
    `;
    document.head.appendChild(s);
  }

  // ═════════ Inline-Edit aktivieren ═════════
  function makeEditableText(host, key, type) {
    if (host.querySelector(':scope > .editable-text')) return;

    // Original-Text als Editable umschließen, OHNE die Badges/Buttons mitzunehmen
    // Wir packen die TEXT-Nodes des Host in einen Span.
    const textNodes = [];
    for (const node of Array.from(host.childNodes)) {
      if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
        textNodes.push(node);
      }
    }
    if (!textNodes.length) return;

    const span = document.createElement('span');
    span.className = 'editable-text';
    if (isEditorOptimistic()) span.contentEditable = 'true';
    span.dataset.editKey = key;
    span.dataset.editType = type;
    span.textContent = textNodes.map(n => n.textContent.trim()).join(' ');
    // Override anwenden falls vorhanden
    if (overrides[key]) span.textContent = overrides[key];

    // Original-Text-Nodes entfernen
    textNodes.forEach(n => n.remove());

    // Span einfügen — nach .section-arrow / .kfw-badge falls vorhanden
    const arrow = host.querySelector('.section-arrow');
    const badge = host.querySelector('.kfw-badge');
    if (arrow) {
      arrow.insertAdjacentElement('afterend', span);
      // Leerzeichen davor
      span.insertAdjacentText('beforebegin', ' ');
    } else if (badge) {
      badge.insertAdjacentElement('afterend', span);
      span.insertAdjacentText('beforebegin', ' ');
    } else {
      host.insertBefore(span, host.firstChild);
    }

    // Save-Logik
    let preEditValue = span.textContent;
    span.addEventListener('focus', () => {
      preEditValue = span.textContent;
    });
    span.addEventListener('blur', () => {
      const newVal = span.textContent.trim();
      if (newVal && newVal !== preEditValue) {
        const oldValForUndo = preEditValue;
        overrides[key] = newVal;
        saveOverrides(overrides);
        pushUndo({
          label: 'Text geändert',
          undo: () => {
            span.textContent = oldValForUndo;
            overrides[key] = oldValForUndo;
            saveOverrides(overrides);
          },
        });
      }
    });
    span.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        span.blur();
      }
      if (e.key === 'Escape') {
        span.textContent = preEditValue;
        span.blur();
      }
    });
  }

  // ═════════ "+ Aufgabe" Button pro Section ═════════
  function addAddTaskButton(sectionRow) {
    if (sectionRow.querySelector('.se-add-btn')) return;
    const host = sectionRow.querySelector('.section-name');
    if (!host) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'se-add-btn se-add-task';
    btn.innerHTML = '+ Aufgabe';
    btn.title = 'Neue Aufgabe in diesem Abschnitt';
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      addNewTaskAfter(sectionRow);
    });
    host.appendChild(btn);

    // Delete-Section-Button
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'se-del-btn';
    delBtn.innerHTML = '✕';
    delBtn.title = 'Abschnitt löschen';
    delBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      deleteSection(sectionRow);
    });
    host.appendChild(delBtn);
  }

  // ═════════ "+ Bereich" Button pro KFW-Banner ═════════
  function addAddSectionButton(kfwRow) {
    if (kfwRow.querySelector('.se-add-btn')) return;
    const host = kfwRow.querySelector('.task-name-cell');
    if (!host) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'se-add-btn se-add-section';
    btn.innerHTML = '+ Bereich';
    btn.title = 'Neuen Abschnitt in diesem KfW-Block';
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      addNewSectionInKfw(kfwRow);
    });
    host.appendChild(btn);
  }

  // ═════════ Neue Task einfügen ═════════
  let nextTid = 1000;
  function generateTid() {
    return 'custom-' + Date.now() + '-' + (nextTid++);
  }

  function addNewTaskAfter(sectionRow) {
    const tid = generateTid();
    const tableWidth = 10800;
    const row = document.createElement('tr');
    row.className = 'task-row';
    row.setAttribute('data-status', 'geplant');
    row.setAttribute('data-gewerk', '');
    row.setAttribute('data-phase', 'haustechnik');
    row.setAttribute('data-unit', '');
    row.setAttribute('data-task-type', 'other');
    row.setAttribute('data-tid', tid);
    row.setAttribute('data-custom', '1');
    // Default-Balken: ab aktueller KW, Dauer 4 Wochen
    const PX_PER_WEEK = 126;
    const ORIGIN_KW = 23;
    const nowContKW = (typeof window.dateToContKW === 'function')
      ? (window.dateToContKW(new Date().toISOString().slice(0,10)) || 23)
      : 23;
    const barLeft = Math.max(0, (nowContKW - ORIGIN_KW)) * PX_PER_WEEK;
    const barWidth = 4 * PX_PER_WEEK;
    row.innerHTML = `
      <td class="task-name-cell" contenteditable="${isEditorOptimistic() ? 'true' : 'false'}">Neue Aufgabe</td>
      <td><span class="status-badge status-planned">—</span></td>
      <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">
        <span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px solid #64748b40">+ Gewerk</span>
      </td>
      <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px" contenteditable="${isEditorOptimistic() ? 'true' : 'false'}">—</td>
      <td><div class="gantt-row-inner" style="width:${tableWidth}px"><div class="gantt-bar status-planned" style="left:${barLeft}px;width:${barWidth}px"></div></div></td>
    `;
    // Nach LETZTER bestehender task-row dieses Sections einfügen (sonst direkt nach section-row)
    let insertAfter = sectionRow;
    let next = sectionRow.nextElementSibling;
    while (next && next.classList.contains('task-row')) {
      insertAfter = next;
      next = next.nextElementSibling;
    }
    insertAfter.insertAdjacentElement('afterend', row);

    // Edit-Bindings + Delete-Button für die neue Row
    addTaskRowEditing(row);

    // Focus auf Aufgaben-Name
    const nameCell = row.querySelector('.task-name-cell');
    if (nameCell && isEditorOptimistic()) {
      nameCell.focus();
      // Text markieren
      const range = document.createRange();
      range.selectNodeContents(nameCell);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }

    // Counter updaten
    updateSectionCounter(sectionRow);
    updateHeaderStats();

    // Backend-Sync: neue Aufgabe als custom_item
    if (window.PlanSync) {
      const secIdx = Array.from(sectionRow.parentNode.children).indexOf(sectionRow);
      const afterIdx = Array.from(insertAfter.parentNode.children).indexOf(insertAfter);
      row.setAttribute('data-client-id', tid);
      window.PlanSync.pushCustomAdd('task', tid, 'section-idx-' + secIdx,
        insertAfter.classList.contains('task-row') ? insertAfter.getAttribute('data-tid') : 'section-idx-' + secIdx,
        { name: 'Neue Aufgabe', status: 'geplant', gewerk: '', firma: '', bar_left: barLeft, bar_width: barWidth });
    }

    // Undo-Eintrag
    pushUndo({
      label: 'Neue Aufgabe hinzugefügt',
      undo: () => {
        if (window.PlanSync) window.PlanSync.pushCustomDelete(tid);
        row.remove();
        updateSectionCounter(sectionRow);
      },
    });
  }

  // ═════════ Neue Section in KFW einfügen ═════════
  function addNewSectionInKfw(kfwRow) {
    const secClientId = 'custom-sec-' + Date.now() + '-' + (nextTid++);
    const row = document.createElement('tr');
    row.className = 'section-row';
    row.setAttribute('data-client-id', secClientId);
    row.innerHTML = `
      <td class="section-name" colspan="4">
        <span class="section-arrow">▶</span> Neuer Bereich
        <span class="progress-pill">0/0 ✓</span>
      </td>
      <td><div class="gantt-row-inner" style="width:10800px"></div></td>
    `;
    // Am Ende des KFW-Blocks einfügen (vor nächster kfw-header-row oder am Ende)
    let insertAfter = kfwRow;
    let next = kfwRow.nextElementSibling;
    while (next && !next.classList.contains('kfw-header-row')) {
      insertAfter = next;
      next = next.nextElementSibling;
    }
    insertAfter.insertAdjacentElement('afterend', row);

    // Editable + Add-Buttons hinzufügen
    makeEditableText(row.querySelector('.section-name'), 'section-' + generateTid(), 'section');
    addAddTaskButton(row);

    // Focus auf Name
    const editText = row.querySelector('.editable-text');
    if (editText && isEditorOptimistic()) {
      editText.focus();
      const range = document.createRange();
      range.selectNodeContents(editText);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }

    // Backend-Sync: neue Section
    if (window.PlanSync) {
      const kfwIdx = Array.from(kfwRow.parentNode.children).indexOf(kfwRow);
      window.PlanSync.pushCustomAdd('section', secClientId, 'kfw-idx-' + kfwIdx, null, { name: 'Neuer Bereich' });
    }

    pushUndo({
      label: 'Neuer Bereich hinzugefügt',
      undo: () => {
        if (window.PlanSync) window.PlanSync.pushCustomDelete(secClientId);
        row.remove();
      },
    });
  }

  // ═════════ Undo-Stack ═════════
  const undoStack = [];
  const MAX_UNDO = 50;
  function pushUndo(action) {
    undoStack.push(action);
    if (undoStack.length > MAX_UNDO) undoStack.shift();
    updateUndoFab();
  }
  // Auch von außerhalb (Bar-Editor / Drag-Engine in index.php) nutzbar machen
  window.pushUndo = pushUndo;
  function updateUndoFab() {
    const fab = document.getElementById('se-undo-fab');
    if (!fab) return;
    const countEl = fab.querySelector('.fab-count');
    if (countEl) countEl.textContent = undoStack.length;
    fab.style.opacity = undoStack.length === 0 ? '0.4' : '1';
    fab.style.cursor = undoStack.length === 0 ? 'not-allowed' : 'pointer';
  }
  function performUndo() {
    if (!undoStack.length) {
      showToast('Nichts zum Rückgängig-Machen', 'warn');
      return;
    }
    const action = undoStack.pop();
    try {
      action.undo();
      showToast(action.label ? '↶ ' + action.label : '↶ Rückgängig', 'ok');
      updateHeaderStats();
      updateUndoFab();
    } catch (e) {
      showToast('Rückgängig fehlgeschlagen: ' + e.message, 'warn');
    }
  }

  // ═════════ Task-Row: Delete-Button + Text-Edit-Undo ═════════
  function addTaskRowEditing(row) {
    // Duplikate aufräumen falls vorhanden (mehrfache ✕)
    const nc = row.querySelector('.task-name-cell');
    if (nc) {
      const dels = nc.querySelectorAll('.se-row-del');
      for (let i = 1; i < dels.length; i++) dels[i].remove();
    }
    if (row.dataset.seInit === '1') return;
    row.dataset.seInit = '1';

    if (isEditorOptimistic()) {
      // Delete-Button als kleines ✕ rechts in der task-name-cell
      const nameCell = row.querySelector('.task-name-cell');
      if (nameCell && !nameCell.querySelector(':scope > .se-row-del')) {
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'se-row-del';
        delBtn.innerHTML = '✕';
        delBtn.title = 'Aufgabe löschen';
        // KRITISCH: contenteditable=false → Symbol landet nie im editierbaren Text
        delBtn.setAttribute('contenteditable', 'false');
        delBtn.style.cssText = [
          'float:right',
          'margin-left:8px',
          'background:transparent',
          'border:none',
          'color:#cbd5e1',
          'cursor:pointer',
          'padding:2px 6px',
          'border-radius:4px',
          'font-size:11px',
          'opacity:0',
          'transition:opacity 0.15s,background 0.12s,color 0.12s',
          'line-height:1'
        ].join(';');
        delBtn.addEventListener('mouseenter', () => {
          delBtn.style.background = '#fee2e2';
          delBtn.style.color = '#b91c1c';
        });
        delBtn.addEventListener('mouseleave', () => {
          delBtn.style.background = 'transparent';
          delBtn.style.color = '#cbd5e1';
        });
        delBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          e.preventDefault();
          deleteTaskRow(row);
        });
        nameCell.appendChild(delBtn);
        // Show on row hover
        row.addEventListener('mouseenter', () => { delBtn.style.opacity = '1'; });
        row.addEventListener('mouseleave', () => { delBtn.style.opacity = '0'; });
      }
    }

    // Text-Edit-Undo wird global delegiert (siehe bindGlobalTextUndo) — nichts mehr hier.
  }

  // ═════════ Global delegierter Text-Edit-Undo ═════════
  // Wir benutzen ein einziges Capture-Phase focus/blur-Listener-Paar,
  // weil sync.js & andere Scripts contentEditable LATER setzen.
  const PRE_EDIT_KEY = '__se_pre';
  function bindGlobalTextUndo() {
    document.addEventListener('focusin', (e) => {
      const t = e.target;
      if (!t || !t.isContentEditable) return;
      // Nur in task-row Cells (Aufgabe / Firma) tracken
      const row = t.closest && t.closest('tr.task-row');
      if (!row) return;
      // Snapshot vom Klartext (ohne Button-Symbole)
      t[PRE_EDIT_KEY] = plainCellText(t);
    }, true);

    document.addEventListener('focusout', (e) => {
      const t = e.target;
      if (!t || !(PRE_EDIT_KEY in t)) return;
      const row = t.closest && t.closest('tr.task-row');
      if (!row) { delete t[PRE_EDIT_KEY]; return; }
      let newVal = plainCellText(t);
      const oldVal = t[PRE_EDIT_KEY];
      delete t[PRE_EDIT_KEY];
      if (newVal !== oldVal) {
        pushUndo({
          label: 'Aufgabentext geändert',
          undo: () => {
            // Delete-Button retten
            const del = t.querySelector(':scope > .se-row-del');
            t.textContent = oldVal;
            if (del) t.appendChild(del);
          },
        });
      }
    }, true);
  }

  function deleteTaskRow(row) {
    const nc = row.querySelector('.task-name-cell');
    const taskName = (nc ? plainCellText(nc) : '') || 'Aufgabe';
    if (!confirm(`Aufgabe "${taskName.slice(0,50)}" löschen?\n\nMit ⌘Z / Ctrl+Z rückgängig machbar.`)) return;

    const parent = row.parentNode;
    const anchor = row.nextSibling;
    // Section finden für Counter-Update
    let sec = row.previousElementSibling;
    while (sec && !sec.classList.contains('section-row') && !sec.classList.contains('kfw-header-row')) {
      sec = sec.previousElementSibling;
    }
    const sectionForCount = (sec && sec.classList.contains('section-row')) ? sec : null;

    // Backend-Sync: Löschung melden
    const tid = row.getAttribute('data-tid');
    const isCustom = row.getAttribute('data-custom') === '1' || row.getAttribute('data-client-id');
    if (window.PlanSync && tid) {
      if (isCustom) window.PlanSync.pushCustomDelete(row.getAttribute('data-client-id') || tid);
      else window.PlanSync.pushOverride('task', tid, 'deleted', '1');
    }

    row.remove();
    if (sectionForCount) updateSectionCounter(sectionForCount);
    updateHeaderStats();

    pushUndo({
      label: `Aufgabe "${taskName.slice(0,30)}" gelöscht`,
      undo: () => {
        if (anchor && anchor.parentNode === parent) {
          parent.insertBefore(row, anchor);
        } else {
          parent.appendChild(row);
        }
        if (sectionForCount) updateSectionCounter(sectionForCount);
        // Sync: Wiederherstellung
        if (window.PlanSync && tid) {
          if (isCustom) window.PlanSync.pushCustomAdd('task', row.getAttribute('data-client-id') || tid, null, null, {
            name: taskName, status: row.getAttribute('data-status') || 'geplant',
          });
          else window.PlanSync.pushOverride('task', tid, 'deleted', '0');
        }
      },
    });

    showToast(`Aufgabe gelöscht — ⌘Z zum Rückgängig`, 'ok', 6000);
  }

  // ═════════ Section löschen (mit Undo-Tracking) ═════════
  function deleteSection(sectionRow) {
    const taskCount = countTasksInSection(sectionRow);
    if (taskCount > 0) {
      if (!confirm(`Diesen Abschnitt mit ${taskCount} Aufgabe(n) löschen?\n\nMit ⌘Z / Ctrl+Z rückgängig machbar.`)) return;
    } else {
      if (!confirm('Diesen Abschnitt löschen?')) return;
    }
    // Snapshot: section + alle nachfolgenden task-rows
    const removed = [{ node: sectionRow, parent: sectionRow.parentNode, anchor: sectionRow.nextSibling }];
    const toRemoveNodes = [];
    let next = sectionRow.nextElementSibling;
    while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
      removed.push({ node: next, parent: next.parentNode, anchor: next.nextSibling });
      toRemoveNodes.push(next);
      next = next.nextElementSibling;
    }
    // Backend-Sync: Section-Löschung melden (Section + alle Tasks darin)
    if (window.PlanSync) {
      const secIdx = Array.from(sectionRow.parentNode.children).indexOf(sectionRow);
      window.PlanSync.pushOverride('section', 'section-idx-' + secIdx, 'deleted', '1');
      toRemoveNodes.forEach(n => {
        if (n.classList.contains('task-row')) {
          const t = n.getAttribute('data-tid');
          if (t) {
            if (n.getAttribute('data-custom') === '1' || n.getAttribute('data-client-id'))
              window.PlanSync.pushCustomDelete(n.getAttribute('data-client-id') || t);
            else window.PlanSync.pushOverride('task', t, 'deleted', '1');
          }
        }
      });
    }

    // Reihenfolge umkehren für Wiederherstellung
    toRemoveNodes.forEach(n => n.remove());
    sectionRow.remove();
    updateHeaderStats();

    pushUndo({
      label: `Abschnitt "${(sectionRow.textContent || '').trim().slice(0, 30)}" gelöscht`,
      undo: () => {
        // In ORIGINALER Reihenfolge wieder einfügen (Vorwärts-Iteration)
        // removed-Array hat die ursprüngliche Reihenfolge
        for (let i = 0; i < removed.length; i++) {
          const { node, parent, anchor } = removed[i];
          if (anchor && anchor.parentNode === parent) {
            parent.insertBefore(node, anchor);
          } else {
            parent.appendChild(node);
          }
        }
      },
    });

    showToast(`Abschnitt gelöscht — ⌘Z zum Rückgängig`, 'ok', 6000);
  }

  // ═════════ Toast / Notification ═════════
  function showToast(msg, type, ms) {
    let t = document.getElementById('se-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'se-toast';
      t.style.cssText = [
        'position:fixed',
        'bottom:24px',
        'left:50%',
        'transform:translateX(-50%) translateY(10px)',
        'background:#1e293b',
        'color:#fff',
        'padding:10px 16px',
        'border-radius:10px',
        'font-family:Inter,sans-serif',
        'font-size:13px',
        'font-weight:600',
        'z-index:99999',
        'box-shadow:0 10px 30px rgba(0,0,0,0.25)',
        'opacity:0',
        'transition:opacity 0.2s, transform 0.2s',
        'pointer-events:auto',
        'cursor:pointer',
        'display:flex',
        'align-items:center',
        'gap:10px'
      ].join(';');
      document.body.appendChild(t);
    }
    t.innerHTML = '';
    const span = document.createElement('span');
    span.textContent = msg;
    t.appendChild(span);
    if (type === 'ok' && /⌘Z/.test(msg)) {
      const btn = document.createElement('button');
      btn.textContent = 'Rückgängig';
      btn.style.cssText = 'background:#2563eb;color:#fff;border:none;padding:5px 12px;border-radius:6px;font-weight:700;font-size:12px;cursor:pointer';
      btn.addEventListener('click', performUndo);
      t.appendChild(btn);
    }
    t.style.background = type === 'warn' ? '#7f1d1d' : '#1e293b';
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => {
      t.style.opacity = '0';
      t.style.transform = 'translateX(-50%) translateY(10px)';
    }, ms || 3000);
  }

  // ═════════ Counter X/Y berechnen ═════════
  function countTasksInSection(sectionRow) {
    let total = 0;
    let next = sectionRow.nextElementSibling;
    while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
      if (next.classList.contains('task-row')) total++;
      next = next.nextElementSibling;
    }
    return total;
  }
  function countDoneInSection(sectionRow) {
    let done = 0;
    let next = sectionRow.nextElementSibling;
    while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
      if (next.classList.contains('task-row')) {
        const status = next.getAttribute('data-status') || '';
        if (status === 'fertig' || /^fortschritt_100/.test(status)) done++;
      }
      next = next.nextElementSibling;
    }
    return done;
  }
  function updateSectionCounter(sectionRow) {
    const total = countTasksInSection(sectionRow);
    const done = countDoneInSection(sectionRow);
    const pill = sectionRow.querySelector('.progress-pill');
    if (!pill) return;
    pill.textContent = `${done}/${total} ✓`;
    pill.classList.toggle('full', total > 0 && done === total);
  }
  function updateAllSectionCounters() {
    document.querySelectorAll('tr.section-row').forEach(updateSectionCounter);
  }

  // ═════════ Stat-Cards oben updaten ═════════
  function updateHeaderStats() {
    const tasks = Array.from(document.querySelectorAll('tr.task-row'));
    const total = tasks.length;
    let done = 0, wip = 0, delayed = 0, planned = 0;
    tasks.forEach(t => {
      const s = t.getAttribute('data-status') || '';
      if (s === 'fertig' || s === 'abgeschlossen' || /^fortschritt_100/.test(s)) done++;
      else if (s === 'verzögert' || s === 'pausiert') delayed++;
      else if (s === 'laufend' || s === 'begonnen' || s === 'abnahme' || /^fortschritt_\d/.test(s)) wip++;
      else planned++;  // geplant, vorbereitung, abgebrochen, etc.
    });
    // Stat-Card-Nummer suchen und updaten (basiert auf Header-Struktur)
    document.querySelectorAll('.card').forEach(card => {
      const lbl = (card.querySelector('.lbl')?.textContent || '').toLowerCase();
      const numEl = card.querySelector('.num');
      if (!numEl) return;
      if (lbl.includes('abgeschlossen')) numEl.textContent = done;
      else if (lbl.includes('arbeit'))    numEl.textContent = wip;
      else if (lbl.includes('geplant'))   numEl.textContent = planned;
      else if (lbl.includes('verzögert'))  numEl.textContent = delayed;
    });
    // Tab-Counter (z.B. "Hauptzeitplan (401 Aufgaben)")
    document.querySelectorAll('.tab').forEach(tab => {
      const txt = tab.textContent;
      const m = txt.match(/\((\d+)\s*Aufgaben?\)/);
      if (m) tab.innerHTML = tab.innerHTML.replace(/\(\d+\s*Aufgaben?\)/, `(${total} Aufgaben)`);
    });
    updateAllSectionCounters();
  }
  // Für sync2.js: nach Remote-Änderungen neu zählen
  window.__recountStats = updateHeaderStats;

  // ═════════ Init ═════════
  function activate() {
    injectCSS();

    document.querySelectorAll('tr.section-row').forEach((row) => {
      const host = row.querySelector('.section-name');
      if (host) {
        // unique key: position innerhalb tbody als fallback
        const idx = Array.from(row.parentNode.children).indexOf(row);
        const key = 'section-idx-' + idx;
        makeEditableText(host, key, 'section');
        addAddTaskButton(row);
      }
    });

    document.querySelectorAll('tr.kfw-header-row').forEach((row) => {
      const host = row.querySelector('.task-name-cell');
      if (host) {
        const idx = Array.from(row.parentNode.children).indexOf(row);
        const key = 'kfw-idx-' + idx;
        makeEditableText(host, key, 'kfw');
        addAddSectionButton(row);
      }
    });

    // Counter erstmalig aktualisieren
    updateHeaderStats();

    // ⌘Z / Ctrl+Z für Undo
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
        // Nicht in editable-Feldern abfangen (sonst kein normales Undo im Text)
        const ae = document.activeElement;
        if (ae && (ae.isContentEditable || ae.tagName === 'INPUT' || ae.tagName === 'TEXTAREA')) return;
        e.preventDefault();
        performUndo();
      }
    });

    // Floating Undo-Button (dauerhaft sichtbar)
    if (isEditorOptimistic() && !document.getElementById('se-undo-fab')) {
      const fab = document.createElement('button');
      fab.id = 'se-undo-fab';
      fab.title = 'Letzte Aktion rückgängig machen (⌘Z / Ctrl+Z)';
      fab.innerHTML = '<span style="font-size:14px;line-height:1">↶</span><span class="fab-label">Rückgängig</span><span class="fab-count" style="background:#e2e8f0;color:#475569;padding:1px 7px;border-radius:999px;font-size:10px;font-weight:700;min-width:18px;text-align:center">0</span>';
      fab.style.cssText = [
        'position:fixed',
        'bottom:24px',
        'right:24px',
        'z-index:9998',
        'background:#fff',
        'color:#1e293b',
        'border:1px solid #e2e8f0',
        'padding:8px 14px 8px 12px',
        'border-radius:999px',
        'font-family:Inter,sans-serif',
        'font-size:12px',
        'font-weight:700',
        'cursor:pointer',
        'box-shadow:0 4px 14px rgba(15,23,42,0.10)',
        'display:flex',
        'align-items:center',
        'gap:6px',
        'transition:all 0.12s'
      ].join(';');
      fab.addEventListener('mouseenter', () => {
        fab.style.transform = 'translateY(-1px)';
        fab.style.boxShadow = '0 6px 18px rgba(15,23,42,0.16)';
      });
      fab.addEventListener('mouseleave', () => {
        fab.style.transform = '';
        fab.style.boxShadow = '0 4px 14px rgba(15,23,42,0.10)';
      });
      fab.addEventListener('click', performUndo);
      document.body.appendChild(fab);
      updateUndoFab();
    }

    // Task-Row Delete-Buttons
    document.querySelectorAll('tr.task-row').forEach(addTaskRowEditing);
    // Globaler Text-Edit-Undo (capture-phase, da contentEditable async gesetzt wird)
    bindGlobalTextUndo();

    // Status-Klicks beobachten → Counter neu berechnen
    document.addEventListener('click', (e) => {
      const statusBadge = e.target.closest('.status-badge');
      if (statusBadge) {
        // Status-Change passiert vom bestehenden Code → kurz warten, dann counter neu
        setTimeout(updateHeaderStats, 80);
      }
    }, true);

    // MutationObserver auf data-status changes (für externe Status-Updates)
    const mo = new MutationObserver((muts) => {
      let hit = false;
      for (const m of muts) {
        if (m.type === 'attributes' && m.attributeName === 'data-status') { hit = true; break; }
      }
      if (hit) {
        clearTimeout(activate._t);
        activate._t = setTimeout(updateHeaderStats, 100);
      }
    });
    document.querySelectorAll('tr.task-row').forEach(t =>
      mo.observe(t, { attributes: true, attributeFilter: ['data-status'] })
    );

    // ChildList-Observer: neue (z.B. gesyncte) Aufgaben-Zeilen bekommen Delete-Button + Status-Watch
    const rowObs = new MutationObserver((muts) => {
      let added = false;
      for (const m of muts) {
        m.addedNodes && m.addedNodes.forEach((n) => {
          if (n.nodeType !== 1) return;
          const rows = n.matches && n.matches('tr.task-row') ? [n]
                     : (n.querySelectorAll ? Array.from(n.querySelectorAll('tr.task-row')) : []);
          rows.forEach((r) => {
            addTaskRowEditing(r);
            mo.observe(r, { attributes: true, attributeFilter: ['data-status'] });
            added = true;
          });
        });
      }
      if (added) { clearTimeout(rowObs._t); rowObs._t = setTimeout(updateHeaderStats, 120); }
    });
    const tbody = document.querySelector('#main-gantt tbody');
    if (tbody) rowObs.observe(tbody, { childList: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', activate);
  } else {
    activate();
  }
})();

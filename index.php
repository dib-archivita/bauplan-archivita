<?php
declare(strict_types=1);
/**
 * index.php — Bauzeitenplan Archivita Dashboard
 * Lädt das komplette Gantt-Dashboard nach Auth-Check.
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

$user = current_user();
if (!$user) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover,user-scalable=yes">
<meta name="theme-color" content="#2563eb">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Bauplan">
<meta name="mobile-web-app-capable" content="yes">
<meta name="format-detection" content="telephone=no">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'%3E%3Crect width='180' height='180' rx='40' fill='%232563eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='central' text-anchor='middle' font-size='100'%3E%F0%9F%8F%97%EF%B8%8F%3C/text%3E%3C/svg%3E">
<title>Detailzeitplan – Archivita GmbH VS-Villingen</title>
<style>




:root {
  --c-done:     #22c55e;
  --c-wip:      #f59e0b;
  --c-planned:  #3b82f6;
  --c-delayed:  #ef4444;
  --c-prio:     #f97316;
  --c-cancelled:#9ca3af;
  --c-kfw-a:    #2563eb;
  --c-kfw-b:    #7c3aed;
  --c-kfw-c:    #ea580c;
  --row-h: 22px;
  --name-w: 130px;
  --meta-w: 160px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:12px;background:#f8fafc;color:#1e293b}

/* ── Header ── */
.header{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between}
.header h1{font-size:20px;font-weight:700;letter-spacing:.5px}
.header .sub{opacity:.8;font-size:11px;margin-top:3px}
.header .kfw-chips{display:flex;gap:8px;flex-wrap:wrap}
.kfw-chip{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1.5px solid rgba(255,255,255,.4)}
.kfw-chip.a{background:rgba(37,99,235,.4)} .kfw-chip.b{background:rgba(124,58,237,.4)} .kfw-chip.c{background:rgba(234,88,12,.4)}

/* ── Summary Cards ── */
.summary{display:flex;gap:12px;padding:16px 24px;background:#fff;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 18px;min-width:120px;text-align:center}
.card .num{font-size:26px;font-weight:700} .card .lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
.card.done .num{color:var(--c-done)} .card.wip .num{color:var(--c-wip)} .card.planned .num{color:var(--c-planned)} .card.delayed .num{color:var(--c-delayed)}
.progress-bar-wrap{flex:1;min-width:200px;display:flex;align-items:center;gap:10px;padding:0 8px}
.progress-bar{height:16px;background:#e2e8f0;border-radius:8px;overflow:hidden;flex:1}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--c-done),#16a34a);border-radius:8px;transition:width .5s}
.progress-pct{font-weight:700;font-size:16px;color:var(--c-done)}

/* ── Tabs ── */
.tabs{display:flex;gap:2px;padding:8px 24px 0;background:#fff;border-bottom:2px solid #e2e8f0}
.tab{padding:8px 18px;cursor:pointer;border-radius:6px 6px 0 0;font-weight:600;font-size:12px;color:#64748b;border:1px solid transparent;border-bottom:none;transition:all .15s}
.tab:hover{background:#f1f5f9} .tab.active{background:#fff;border-color:#e2e8f0;color:#2563eb;margin-bottom:-2px;border-bottom:2px solid #fff}

/* ── Filter Bar ── */
.filter-bar{padding:10px 24px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.filter-bar label{font-size:11px;font-weight:600;color:#64748b;margin-right:4px}
.filter-btn{padding:4px 10px;border-radius:20px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-size:11px;font-weight:500;transition:all .15s}
.filter-btn:hover,.filter-btn.active{background:#2563eb;color:#fff;border-color:#2563eb}

/* ── Gantt-Container ── */
.tab-content{display:none} .tab-content.active{display:block}
.gantt-wrap{overflow:auto;padding:0 24px 24px}
.gantt-table{border-collapse:collapse;min-width:100%;table-layout:auto}
.gantt-table td,.gantt-table th{border:none;padding:1px 0;vertical-align:middle}

/* ── Fixed columns ── */
.task-id{width:44px;min-width:44px;max-width:44px;padding:0 4px;font-size:10px;color:#94a3b8;font-family:monospace;text-align:right;white-space:nowrap}
.task-name-cell{min-width:80px;padding:0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.section-name{font-weight:600;font-size:11.5px;padding:0 6px;cursor:pointer;user-select:none}

/* ── KfW header row ── */
.kfw-header-row{background:#1e293b;color:#fff}
.kfw-header-row td{padding:6px 6px;font-weight:700;font-size:12px}
.kfw-a .kfw-badge{background:var(--c-kfw-a)} .kfw-b .kfw-badge{background:var(--c-kfw-b)} .kfw-c .kfw-badge{background:var(--c-kfw-c)}
.kfw-a{border-left:4px solid var(--c-kfw-a)} .kfw-b{border-left:4px solid var(--c-kfw-b)} .kfw-c{border-left:4px solid var(--c-kfw-c)}
.kfw-badge{padding:2px 7px;border-radius:4px;font-size:10px;margin-right:6px;color:#fff}
.budget-tag{font-size:10px;opacity:.7;font-weight:400}

/* ── Section row ── */
.section-row{background:#f1f5f9}
.section-row td{padding:4px 0;border-top:1px solid #cbd5e1}
.progress-pill{font-size:9px;margin-left:8px;background:#e2e8f0;padding:1px 6px;border-radius:10px;color:#64748b}

/* ── Task row ── */
.task-row{height:var(--row-h);transition:background .1s}
.task-row:hover{background:#f0f9ff}
.task-row td{border-bottom:1px solid #f1f5f9}

/* ── Status badges ── */
.status-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:600;white-space:nowrap}
.status-done{background:#dcfce7;color:#166534} .status-wip{background:#fef3c7;color:#92400e}
.status-planned{background:#dbeafe;color:#1e40af} .status-delayed{background:#fee2e2;color:#991b1b}
.status-prio{background:#ffedd5;color:#9a3412} .status-cancelled{background:#f3f4f6;color:#6b7280;text-decoration:line-through}

/* ── Gewerk tags ── */
.gewerk-tag{display:inline-block;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;margin-left:4px;color:#fff}
.gw-dib{background:#0f766e} .gw-sf{background:#7c3aed} .gw-kdm{background:#b45309}
.gw-ibw{background:#1d4ed8} .gw-zelsius{background:#0369a1} .gw-haushahn{background:#be185d}
.gw-sanitaer{background:#065f46} .gw-elektro{background:#92400e} .gw-other{background:#475569}

/* ── Gantt timeline ── */
.gantt-timeline-header{position:relative;height:36px;overflow:hidden}
.gantt-kw-header{position:relative;height:28px;overflow:hidden;border-bottom:1px solid #e2e8f0}
.month-label{position:absolute;top:0;height:18px;font-size:10px;font-weight:700;text-align:center;color:#334155;background:#f8fafc;border-right:1px solid #e2e8f0;border-bottom:2px solid #cbd5e1;display:flex;align-items:center;justify-content:center}
.kw-label{position:absolute;top:0;height:28px;font-size:10px;color:#475569;border-right:1px solid #e2e8f0;padding:4px 3px;overflow:hidden;width:126px;font-weight:600}
.gantt-row-inner{position:relative;height:var(--row-h);overflow:hidden}
/* Tages-Grid (fester Tages-Maßstab, echte Pixel): Tageslinie 18px, kräftigere Wochenlinie 126px, Wochenend-Schattierung (Sa/So = letzte 36px der Woche). left:0 = KW23-Montag → Tag-/Wochengrenzen exakt ausgerichtet (7×18 = 126). */
.gantt-row-inner{
  background-image:
    repeating-linear-gradient(90deg, rgba(100,116,139,.26) 0, rgba(100,116,139,.26) 1px, transparent 1px, transparent 126px),
    repeating-linear-gradient(90deg, rgba(148,163,184,.15) 0, rgba(148,163,184,.15) 1px, transparent 1px, transparent 18px),
    repeating-linear-gradient(90deg, transparent 0, transparent 90px, rgba(148,163,184,.08) 90px, rgba(148,163,184,.08) 126px);
}
.no-date-label{position:absolute;left:4px;top:50%;transform:translateY(-50%);font-size:9px;color:#94a3b8;font-style:italic}

/* ── Gantt bars ── */
.gantt-bar{position:absolute;top:3px;height:16px;border-radius:4px;opacity:.85;transition:opacity .15s;cursor:default;overflow:hidden}
.gantt-bar:hover{opacity:1;z-index:10}
.gantt-bar.status-done{background:var(--c-done)}
.gantt-bar.status-wip{background:linear-gradient(90deg,var(--c-wip),#d97706)}
.gantt-bar.status-planned{background:var(--c-planned)}
.gantt-bar.status-delayed{background:var(--c-delayed)}
.gantt-bar.status-prio{background:var(--c-prio)}
.gantt-bar.status-cancelled{background:var(--c-cancelled)}
.bar-label{font-size:9px;color:#fff;padding:3px 4px;white-space:nowrap;overflow:hidden}

/* Wochentag-Header (Mo–So) — fester Tages-Maßstab, immer sichtbar (kein Zoom/Transform mehr) */
.gantt-day-header { display: block; }

/* ── Today line ── */
.today-line{position:absolute;top:0;bottom:0;width:2px;background:#ef4444;z-index:20;pointer-events:none}
.today-line::before{content:"Heute";position:absolute;top:-18px;left:-14px;font-size:9px;color:#ef4444;font-weight:700;white-space:nowrap}

/* ── TODOs ── */
.todos-wrap{padding:16px 24px}
.todo-cols{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.todo-col h3{font-size:13px;font-weight:700;margin-bottom:10px;color:#1e3a5f;padding-bottom:6px;border-bottom:2px solid #e2e8f0}
.todo-list{list-style:none;display:flex;flex-direction:column;gap:6px}
.todo-list li{display:flex;align-items:center;gap:8px;padding:6px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:11.5px}
.todo-person{display:inline-block;padding:1px 7px;border-radius:3px;font-size:10px;font-weight:700;color:#fff;min-width:30px;text-align:center}

/* ── Raumbuch ── */
.raumbuch-wrap{padding:16px 24px;display:grid;grid-template-columns:1fr 1fr;gap:20px}
.raumbuch-section{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.rb-title{font-size:13px;font-weight:700;padding:10px 14px;background:#f1f5f9;border-bottom:1px solid #e2e8f0;color:#1e3a5f}
.rb-table{width:100%;border-collapse:collapse;font-size:11px}
.rb-table th{background:#f8fafc;padding:6px 10px;text-align:left;font-weight:600;font-size:10px;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e2e8f0}
.rb-table td{padding:5px 10px;border-bottom:1px solid #f1f5f9}
.rb-table tr:hover{background:#f0f9ff}
.rb-id{font-family:monospace;font-size:10px;color:#94a3b8} .rb-num{text-align:right;font-weight:600}

/* ── Print ── */
@media print {
  .tabs,.filter-bar,.header .kfw-chips{display:none}
  .tab-content{display:block!important}
  .gantt-wrap{overflow:visible}
  body{background:#fff}
}

/* ── Legend ── */
.legend{display:flex;gap:12px;flex-wrap:wrap;padding:8px 24px;background:#fff;border-bottom:1px solid #e2e8f0;align-items:center}
.legend-item{display:flex;align-items:center;gap:4px;font-size:10px;color:#475569}
.legend-dot{width:12px;height:12px;border-radius:3px}

/* ── Dependency Engine ── */
#delay-panel{
  position:fixed;bottom:24px;right:24px;z-index:1000;
  background:#fff;border:1.5px solid #2563eb;border-radius:12px;
  box-shadow:0 4px 24px rgba(37,99,235,.2);padding:16px 20px;
  min-width:280px;font-size:12px;
  transition:all .3s;
  display:none;  /* DEFAULT HIDDEN */
}
#delay-panel h3{margin:0 0 12px;font-size:13px;font-weight:700;color:#1e293b;
  display:flex;align-items:center;gap:8px}
.delay-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.delay-row label{flex:1;color:#475569;font-size:11px}
.delay-input{width:64px;padding:3px 6px;border:1px solid #e2e8f0;border-radius:5px;
  font-size:12px;text-align:right;background:#f8fafc}
.delay-input:focus{outline:2px solid #2563eb40;border-color:#2563eb}
.delay-unit{color:#94a3b8;font-size:10px;white-space:nowrap}
.delay-actions{display:flex;gap:6px;margin-top:12px;padding-top:10px;
  border-top:1px solid #f1f5f9}
.btn-apply{flex:1;padding:5px;background:#2563eb;color:#fff;border:none;
  border-radius:6px;cursor:pointer;font-size:11px;font-weight:600}
.btn-reset{padding:5px 10px;background:#f1f5f9;color:#64748b;border:none;
  border-radius:6px;cursor:pointer;font-size:11px}
.btn-toggle-panel{position:fixed;bottom:24px;right:24px;z-index:1001;
  background:#2563eb;color:#fff;border:none;border-radius:50%;width:44px;height:44px;
  font-size:18px;cursor:pointer;box-shadow:0 2px 8px rgba(37,99,235,.4);
  display:flex;align-items:center;justify-content:center}
.gantt-bar{transition:left .4s cubic-bezier(.4,0,.2,1), width .4s cubic-bezier(.4,0,.2,1)}
.gantt-bar.shifted{outline:2px solid #f59e0b;outline-offset:1px}
.delay-badge{position:absolute;top:-8px;left:0;background:#f59e0b;color:#fff;
  font-size:8px;font-weight:700;padding:1px 4px;border-radius:3px;white-space:nowrap;
  pointer-events:none}
.delay-info{font-size:10px;color:#f59e0b;margin-top:6px;padding:4px 8px;
  background:#fffbeb;border-radius:4px;border:1px solid #fde68a;display:none}


/* ── Gantt Drag/Resize ── */
.gantt-bar { position: relative; }
.gantt-bar.drag-active { opacity: .7; outline: 2px dashed #2563eb; cursor: grabbing !important; z-index: 100; }
.gantt-bar .resize-handle { position: absolute; top: 0; width: 6px; height: 100%; cursor: ew-resize; background: transparent; z-index: 5; }
.gantt-bar .resize-handle.left { left: 0; }
.gantt-bar .resize-handle.right { right: 0; }
.gantt-bar:hover .resize-handle { background: rgba(0,0,0,.15); }
.gantt-bar.dep-highlight { outline: 2px dashed #f59e0b; outline-offset: 1px; }
.gantt-drag-info { position: fixed; background: #1e293b; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 11px; pointer-events: none; z-index: 10001; box-shadow: 0 2px 8px rgba(0,0,0,.3); white-space: nowrap; }


/* ── Inline Edit ── */
.task-name-cell[contenteditable=true], .task-firma-cell[contenteditable=true] {
  outline: 1px dashed transparent;
  cursor: text;
}
.task-name-cell[contenteditable=true]:hover, .task-firma-cell[contenteditable=true]:hover {
  outline-color: #2563eb;
  background: #eff6ff;
}
.task-name-cell[contenteditable=true]:focus, .task-firma-cell[contenteditable=true]:focus {
  outline: 2px solid #2563eb;
  background: #fff;
}
.gewerk-badge { cursor: pointer; }
.gewerk-badge:hover { outline: 1px solid #94a3b8; }
.gewerk-dropdown {
  position: fixed; background: #fff; border: 1px solid #e2e8f0;
  border-radius: 6px; padding: 4px 0; min-width: 140px;
  box-shadow: 0 4px 12px rgba(0,0,0,.12); z-index: 10500;
  max-height: 280px; overflow-y: auto;
}
.gewerk-dropdown div {
  padding: 5px 12px; font-size: 11px; cursor: pointer;
}
.gewerk-dropdown div:hover { background: #eff6ff; }
.btn-new-task {
  position: fixed; bottom: 80px; right: 24px; z-index: 1001;
  background: #16a34a; color: #fff; border: none; border-radius: 50%;
  width: 44px; height: 44px; font-size: 22px; cursor: pointer;
  box-shadow: 0 2px 8px rgba(22,163,74,.4); display: flex;
  align-items: center; justify-content: center;
}
/* FAB-Buttons nur im Hauptzeitplan zeigen */
body:not([data-active-tab="hauptwerk"]) #today-fab,
body:not([data-active-tab="hauptwerk"]) #btn-toggle-panel,
body:not([data-active-tab="hauptwerk"]) .btn-new-task { display: none !important; }
/* Verantwortlich-Spalte in den Budget-Tabellen ausblenden */
#tab-kosten div[id^="block-"] table th:nth-child(2),
#tab-kosten div[id^="block-"] table td:nth-child(2) { display: none; }


/* ── Gewerke Multi-Select ── */
.gw-picker-badges { display: flex; flex-wrap: wrap; gap: 3px; cursor: pointer; min-height: 24px; padding: 3px; border: 1px solid transparent; border-radius: 5px; }
.gw-picker-badges:hover { border-color: #cbd5e1; background: #f8fafc; }
.gw-picker-badges .gw-badge { font-size: 10px; padding: 2px 7px; border-radius: 8px; font-weight: 600; white-space: nowrap; }
.gw-picker-badges .gw-empty { color: #94a3b8; font-style: italic; font-size: 11px; padding: 2px 7px; }
.gw-multi-dropdown {
  position: fixed; background: #fff; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 8px; min-width: 220px;
  box-shadow: 0 8px 24px rgba(0,0,0,.15); z-index: 10500;
  max-height: 320px; overflow-y: auto;
}
.gw-multi-dropdown label {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 8px; font-size: 11px; cursor: pointer; border-radius: 4px;
}
.gw-multi-dropdown label:hover { background: #f1f5f9; }
.gw-multi-dropdown label input { cursor: pointer; margin: 0; }
.gw-multi-dropdown .gw-actions {
  display: flex; gap: 6px; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 6px;
}
.gw-multi-dropdown .gw-actions button {
  flex: 1; padding: 5px; font-size: 10px; border: 1px solid #e2e8f0;
  border-radius: 4px; background: #fff; cursor: pointer;
}
.gw-multi-dropdown .gw-actions button:hover { background: #f1f5f9; }


/* ── Urlaub Multi-Select ── */
.urlaub-cell { padding: 4px 8px; cursor: pointer; min-height: 24px; border: 1px solid transparent; border-radius: 5px; display: flex; flex-wrap: wrap; gap: 3px; align-items: center; }
.urlaub-cell:hover { border-color: #cbd5e1; background: #fffbeb; }
.urlaub-cell .ur-badge { background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 8px; font-size: 9px; font-weight: 600; white-space: nowrap; }
.urlaub-cell .ur-empty { color: #cbd5e1; font-size: 11px; font-style: italic; }
.urlaub-modal {
  position: fixed; background: #fff; border: 1px solid #e2e8f0;
  border-radius: 10px; padding: 14px; min-width: 360px;
  box-shadow: 0 8px 24px rgba(0,0,0,.18); z-index: 10500;
  max-height: 420px; overflow-y: auto;
}
.urlaub-modal h4 { margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #1e293b; }
.urlaub-modal .ur-row { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; font-size: 11px; }
.urlaub-modal .ur-row input, .urlaub-modal .ur-row select { padding: 4px 6px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 11px; }
.urlaub-modal .ur-row input[type=number] { width: 50px; text-align: center; }
.urlaub-modal .ur-row select { width: 75px; }
.urlaub-modal .ur-row button { background: #fee2e2; color: #dc2626; border: none; border-radius: 4px; padding: 3px 8px; cursor: pointer; font-size: 10px; }
.urlaub-modal .ur-actions { display: flex; gap: 6px; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 8px; }



/* ══════════════ DESIGN REFRESH (Stufe 1+2) ══════════════ */
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important; letter-spacing: -0.01em; }

/* Header */
.header { background: linear-gradient(180deg, #fff 0%, #fafbfc 100%); box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.h1 { font-weight: 800; letter-spacing: -0.02em; }

/* Tabs als Pills */
.tabs { padding: 12px 24px 14px; background: #fafbfc; border-bottom: 1px solid #e8e9ed; gap: 6px; }
.tab { padding: 7px 16px; border-radius: 999px; transition: all 0.15s; font-weight: 600; font-size: 12.5px; }
.tab:not(.active):hover { background: #f1f5f9; color: #1e293b; }
.tab.active { background: #2563eb; color: #fff; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.22); }

/* Filter Bar */
.filter-bar { background: #fff; padding: 10px 24px; border-bottom: 1px solid #e8e9ed; gap: 6px; }
.filter-bar label { font-weight: 600; color: #64748b; font-size: 11px; letter-spacing: 0.02em; }
.filter-btn { padding: 4px 12px !important; border-radius: 999px !important; border: 1px solid #e2e8f0 !important; background: #fff !important; font-size: 11px; font-weight: 600; transition: all 0.12s; cursor: pointer; }
.filter-btn:hover:not(.active) { background: #f8fafc !important; border-color: #cbd5e1 !important; transform: translateY(-1px); }
.filter-btn.active { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.20); }

/* Status-Badges pastell */
.status-badge {
  padding: 3px 9px !important;
  border-radius: 999px !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  letter-spacing: 0.02em;
}
.status-done { background: #d1fae5 !important; color: #047857 !important; }
.status-wip { background: #fef3c7 !important; color: #b45309 !important; }
.status-planned { background: #f1f5f9 !important; color: #64748b !important; }
.status-delayed { background: #fee2e2 !important; color: #b91c1c !important; }
.status-prio { background: #fed7aa !important; color: #c2410c !important; }

/* Gantt-Bars: weiche Übergänge + Rounded */
.gantt-bar {
  border-radius: 4px !important;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.gantt-bar:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.12); transform: translateY(-1px); }

/* Tabellen */
table { border-collapse: separate !important; border-spacing: 0 !important; }
th { background: #fafbfc !important; font-weight: 700 !important; letter-spacing: 0.02em; font-size: 11px !important; color: #475569 !important; padding: 8px 10px !important; border-bottom: 1px solid #e8e9ed !important; }
.task-row { transition: background 0.1s; }
.task-row:hover { background: #fafbfc; }
.task-row td { border-bottom: 1px solid #f1f5f9 !important; }

/* Section-Rows: weichere Optik */
.section-row td { background: #f8fafc !important; font-weight: 700 !important; }
.section-name { color: #1e293b !important; }

/* KFW-Header */
.kfw-header-row { box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
.kfw-header-row td { padding: 9px 12px !important; letter-spacing: 0.02em; }

/* Buttons general */
button:not(.tab):not(.filter-btn) {
  transition: all 0.12s;
  letter-spacing: -0.01em;
}
button:not(.tab):not(.filter-btn):hover { transform: translateY(-1px); }

/* Inputs */
input[type=number], input[type=text], select {
  font-family: 'Inter', -apple-system, sans-serif !important;
  transition: border-color 0.12s, box-shadow 0.12s;
}
input[type=number]:focus, input[type=text]:focus, select:focus {
  outline: none !important;
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
}

/* Section-arrow Buttons (Aufgaben aufklappen) */
.section-arrow { transition: transform 0.15s; }

/* Stat-Karten im Header */
.summary { gap: 10px; }
.summary .card {
  border-radius: 12px !important;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.03);
  border: 1px solid #e8e9ed;
  transition: transform 0.12s, box-shadow 0.12s;
}
.summary .card:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
.summary .num { font-weight: 800; letter-spacing: -0.02em; }
.summary .lbl { font-weight: 600; }

/* Progress-Pills */
.progress-pill {
  border-radius: 999px !important;
  font-weight: 700;
  letter-spacing: 0.02em;
}

/* Bestellungstabelle weicher */
#bo-table th { font-size: 10px !important; color: #475569 !important; letter-spacing: 0.04em; text-transform: uppercase; }

/* Legend weg falls noch da */
.legend { display: none !important; }

/* Caret in Einheiten */
.caret { transition: transform 0.15s; }

/* Tooltip-style verbessern */
[title] { cursor: help; }

/* Dropdown-Menüs etwas weicher */
.gewerk-dropdown, .gw-multi-dropdown, .urlaub-modal {
  border-radius: 10px !important;
  box-shadow: 0 10px 32px rgba(0,0,0,0.12) !important;
  border: 1px solid #e8e9ed !important;
}

/* ══════════════ END DESIGN REFRESH ══════════════ */


/* ══════════════ DESIGN REFRESH STUFE 3 ══════════════ */

/* Header: Lesbarkeit FIXEN + sanfter Gradient */
.header {
  background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%) !important;
  border-bottom: 1px solid #e2e8f0 !important;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
  padding: 18px 28px !important;
}
.h1 { color: #0f172a !important; font-weight: 800 !important; font-size: 18px !important; }
.sub { color: #475569 !important; font-size: 12px !important; font-weight: 500 !important; }

/* Summary-Cards (189, 10, 146, 1, 55%) - viel besser lesbar */
.summary { gap: 12px !important; }
.summary .card {
  background: #fff !important;
  padding: 10px 16px !important;
  border-radius: 12px !important;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04) !important;
  border: 1px solid #e2e8f0 !important;
  min-width: 80px !important;
}
.summary .num {
  font-size: 22px !important;
  font-weight: 800 !important;
  letter-spacing: -0.02em !important;
  line-height: 1.1 !important;
}
.summary .card.done .num { color: #047857 !important; }
.summary .card.wip .num { color: #b45309 !important; }
.summary .card.planned .num { color: #1e40af !important; }
.summary .card.delayed .num { color: #b91c1c !important; }
.summary .lbl {
  font-size: 9px !important;
  font-weight: 700 !important;
  color: #475569 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.06em !important;
  margin-top: 2px !important;
}

/* Counter-Cards als Status-Filter (nur im Hauptzeitplan klickbar) */
body[data-active-tab="hauptwerk"] #header-summary .card { cursor: pointer !important; }
body[data-active-tab="hauptwerk"] #header-summary .card:active { transform: translateY(0) scale(0.98); }
#header-summary .card.card-filter-active {
  box-shadow: 0 0 0 2.5px #fff, 0 0 0 5px currentColor, 0 4px 14px rgba(15,23,42,0.18) !important;
}
#header-summary .card.done.card-filter-active    { color: #047857; background: #ecfdf5 !important; }
#header-summary .card.wip.card-filter-active     { color: #b45309; background: #fffbeb !important; }
#header-summary .card.planned.card-filter-active { color: #1e40af; background: #eff6ff !important; }
#header-summary .card.delayed.card-filter-active { color: #b91c1c; background: #fef2f2 !important; }
.status-clear-chip { align-self: center; padding: 7px 14px; border-radius: 999px; border: 1.5px solid #cbd5e1; background: #fff; color: #475569; font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background .12s, border-color .12s; }
.status-clear-chip:hover { background: #f1f5f9; border-color: #94a3b8; }

/* Progress-Bar */
.progress-bar-wrap { gap: 10px !important; }
.progress-bar {
  height: 10px !important;
  border-radius: 999px !important;
  background: #f1f5f9 !important;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
}
.progress-fill {
  background: linear-gradient(90deg, #16a34a 0%, #22c55e 100%) !important;
  border-radius: 999px !important;
  box-shadow: 0 1px 3px rgba(22, 163, 74, 0.25);
}
.progress-pct { font-size: 14px !important; font-weight: 800 !important; color: #16a34a !important; }
.progress-bar-wrap > div:last-child { color: #64748b !important; font-weight: 600 !important; }

/* KFW-Banner (OHG Haustechnik etc.) - moderner */
.kfw-header-row { 
  background: linear-gradient(90deg, #1e293b 0%, #334155 100%) !important;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.10);
}
.kfw-header-row td { color: #fff !important; font-weight: 700 !important; letter-spacing: 0.02em; padding: 11px 14px !important; }
.kfw-badge { 
  background: rgba(255,255,255,0.18) !important;
  backdrop-filter: blur(8px);
  border-radius: 6px !important;
  padding: 3px 8px !important;
  font-weight: 700 !important;
}

/* Phasen-Banner: einheitlich wie alle Bereichs-Header (Navy, siehe .kfw-header-row oben).
   Die früheren Einzelfarben (grün/orange/lila/grau) sind entfernt — alle gleich. */

/* Section-Rows: subtiler */
.section-row td {
  background: linear-gradient(90deg, #f8fafc 0%, #fff 100%) !important;
  border-left: 3px solid #cbd5e1 !important;
  font-size: 12.5px;
  padding: 9px 12px !important;
}
.section-arrow { color: #2563eb !important; font-weight: 700; }

/* Task-Rows: mehr Atmung */
.task-row td {
  padding: 6px 10px !important;
  font-size: 11.5px;
}

/* Tabellen-Header */
.table thead th, table.gantt-table thead th {
  padding: 10px 12px !important;
  background: #fafbfc !important;
  font-size: 10.5px !important;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #475569 !important;
  font-weight: 700 !important;
}

/* Bestellungs-Modal & andere Modale: weicher */
#bo-modal > div, #new-task-modal > div, #bar-editor-modal > div {
  border-radius: 16px !important;
  box-shadow: 0 25px 50px rgba(15, 23, 42, 0.25) !important;
}

/* Buttons: einheitlicher Look */
button.btn-apply, button[onclick*="save"], button[onclick*="Create"] {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  color: #fff !important;
  border: none !important;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
  border-radius: 8px !important;
  padding: 7px 16px !important;
  font-weight: 600;
  transition: all 0.15s;
}
button.btn-apply:hover, button[onclick*="save"]:hover, button[onclick*="Create"]:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(37, 99, 235, 0.30);
}

/* Subtile Animation bei Tab-Wechsel */
.tab-content { animation: fadeIn 0.25s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

/* Smooth scroll innerhalb gantt-wrap */
.gantt-wrap { scroll-behavior: smooth; }

/* Gantt-Bars: noch weicher */
.gantt-bar.status-done { box-shadow: 0 1px 3px rgba(22,163,74,0.25); }
.gantt-bar.status-wip { box-shadow: 0 1px 3px rgba(245,158,11,0.25); }
.gantt-bar.status-planned { box-shadow: 0 1px 2px rgba(100,116,139,0.20); }
.gantt-bar.status-delayed { box-shadow: 0 1px 3px rgba(239,68,68,0.25); }

/* Filter-Bar verbessert */
.filter-bar { background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%) !important; }

/* Card-Hover für Einheiten-Cards */
.wohn-main:hover td { background: #eff6ff !important; }

/* Tabellen Border-Radius außen */
.table, table.gantt-table {
  border-radius: 12px;
  overflow: hidden;
}

/* ══════════════ END STUFE 3 ══════════════ */


/* ══════════════ GANTT-BAR FARBEN FIX ══════════════ */
.gantt-bar { opacity: 1 !important; height: 18px !important; top: 2px !important; }
.gantt-bar.status-done {
  background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%) !important;
  border: 1px solid #15803d;
  box-shadow: 0 1px 3px rgba(22, 163, 74, 0.30);
}
.gantt-bar.status-wip {
  background: linear-gradient(180deg, #fbbf24 0%, #d97706 100%) !important;
  border: 1px solid #b45309;
  box-shadow: 0 1px 3px rgba(217, 119, 6, 0.30);
}
.gantt-bar.status-planned {
  background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%) !important;
  border: 1px solid #1d4ed8;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.30);
}
.gantt-bar.status-delayed {
  background: linear-gradient(180deg, #f87171 0%, #dc2626 100%) !important;
  border: 1px solid #b91c1c;
  box-shadow: 0 1px 3px rgba(220, 38, 38, 0.30);
}
.gantt-bar.status-prio {
  background: linear-gradient(180deg, #fb923c 0%, #ea580c 100%) !important;
  border: 1px solid #c2410c;
  box-shadow: 0 1px 3px rgba(234, 88, 12, 0.30);
}
.gantt-bar.status-cancelled {
  background: #cbd5e1 !important;
  border: 1px solid #94a3b8;
  opacity: 0.6 !important;
}
.gantt-bar:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
  z-index: 50;
}
/* ══════════════ END BAR FIX ══════════════ */

/* ══════════════ DROPDOWN FIX ══════════════ */
.gewerk-dropdown, .gw-multi-dropdown, .urlaub-modal {
  z-index: 99999 !important;
  position: fixed !important;
  pointer-events: auto !important;
}

/* Make sure gewerk-td receives clicks */
tr.task-row td:nth-child(3) {
  cursor: pointer;
  position: relative;
  z-index: 1;
}
tr.task-row td:nth-child(3):hover {
  background: #eff6ff !important;
  outline: 1px solid #2563eb40;
  outline-offset: -1px;
}

/* Stop fadeIn from interfering with dropdowns */
.tab-content {
  animation: none !important;
}

/* Ensure dropdowns are above everything */
.gewerk-dropdown {
  background: #fff !important;
  border: 1px solid #cbd5e1 !important;
  box-shadow: 0 12px 28px rgba(0,0,0,0.20) !important;
  min-width: 180px !important;
  max-height: 400px !important;
  overflow-y: auto !important;
  padding: 6px 0 !important;
}
.gewerk-dropdown div {
  padding: 7px 14px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: background 0.1s;
}
.gewerk-dropdown div:hover {
  background: #f1f5f9 !important;
}
/* ══════════════ END DROPDOWN FIX ══════════════ */

/* ══════════════ BARS NACH GEWERK ══════════════ */
/* Sanitär/Heizung (blau) */
tr.task-row[data-gewerk="Sanitär/Heizung"] .gantt-bar,
tr.task-row[data-gewerk="Sanitär"] .gantt-bar,
tr.task-row[data-gewerk="Heizung"] .gantt-bar,
tr.task-row[data-gewerk="Lüftung"] .gantt-bar,
tr.task-row[data-gewerk="Klima"] .gantt-bar {
  background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%) !important;
  border-color: #1d4ed8 !important;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.30) !important;
}

/* Elektro (orange) */
tr.task-row[data-gewerk="Elektro"] .gantt-bar {
  background: linear-gradient(180deg, #fb923c 0%, #d97706 100%) !important;
  border-color: #b45309 !important;
  box-shadow: 0 1px 3px rgba(217, 119, 6, 0.30) !important;
}

/* Maler/Gipser (rot-orange) */
tr.task-row[data-gewerk="Maler/Gipser"] .gantt-bar,
tr.task-row[data-gewerk="Maler"] .gantt-bar {
  background: linear-gradient(180deg, #fb7185 0%, #ea580c 100%) !important;
  border-color: #c2410c !important;
  box-shadow: 0 1px 3px rgba(234, 88, 12, 0.30) !important;
}

/* Trockenbau (indigo) */
tr.task-row[data-gewerk="Trockenbau"] .gantt-bar {
  background: linear-gradient(180deg, #818cf8 0%, #6366f1 100%) !important;
  border-color: #4f46e5 !important;
  box-shadow: 0 1px 3px rgba(99, 102, 241, 0.30) !important;
}

/* Bodenbelag (braun) */
tr.task-row[data-gewerk="Bodenbelag"] .gantt-bar {
  background: linear-gradient(180deg, #d97706 0%, #92400e 100%) !important;
  border-color: #78350f !important;
  box-shadow: 0 1px 3px rgba(146, 64, 14, 0.30) !important;
}

/* Fliesen (teal) */
tr.task-row[data-gewerk="Fliesen"] .gantt-bar {
  background: linear-gradient(180deg, #2dd4bf 0%, #0f766e 100%) !important;
  border-color: #115e59 !important;
  box-shadow: 0 1px 3px rgba(15, 118, 110, 0.30) !important;
}

/* Estrich (violett) */
tr.task-row[data-gewerk="Estrich"] .gantt-bar {
  background: linear-gradient(180deg, #a78bfa 0%, #7c3aed 100%) !important;
  border-color: #6d28d9 !important;
  box-shadow: 0 1px 3px rgba(124, 58, 237, 0.30) !important;
}

/* Schreiner/Endmontage/Möblierung (dunkelbraun) */
tr.task-row[data-gewerk="Schreiner/Endmontage"] .gantt-bar,
tr.task-row[data-gewerk="Schreiner"] .gantt-bar,
tr.task-row[data-gewerk="Möblierung"] .gantt-bar,
tr.task-row[data-gewerk="Endmontage"] .gantt-bar {
  background: linear-gradient(180deg, #d97706 0%, #78350f 100%) !important;
  border-color: #451a03 !important;
  box-shadow: 0 1px 3px rgba(120, 53, 15, 0.30) !important;
}

/* Brandschutz (dunkelrot) */
tr.task-row[data-gewerk="Brandschutz"] .gantt-bar {
  background: linear-gradient(180deg, #ef4444 0%, #991b1b 100%) !important;
  border-color: #7f1d1d !important;
  box-shadow: 0 1px 3px rgba(153, 27, 27, 0.30) !important;
}

/* Dach/Fassade/WDVS/Dachdecker (dunkelgrün) */
tr.task-row[data-gewerk="Dach/Fassade"] .gantt-bar,
tr.task-row[data-gewerk="Dachdecker"] .gantt-bar,
tr.task-row[data-gewerk="WDVS"] .gantt-bar,
tr.task-row[data-gewerk="Fassade"] .gantt-bar,
tr.task-row[data-gewerk="Dämmung"] .gantt-bar,
tr.task-row[data-gewerk="Fenster"] .gantt-bar {
  background: linear-gradient(180deg, #10b981 0%, #065f46 100%) !important;
  border-color: #064e3b !important;
  box-shadow: 0 1px 3px rgba(6, 95, 70, 0.30) !important;
}

/* Planung/Architekt (indigo) */
tr.task-row[data-gewerk="Planung/Architekt"] .gantt-bar,
tr.task-row[data-gewerk="Planung"] .gantt-bar,
tr.task-row[data-gewerk="Architekt"] .gantt-bar,
tr.task-row[data-gewerk="Gutachter"] .gantt-bar {
  background: linear-gradient(180deg, #818cf8 0%, #4338ca 100%) !important;
  border-color: #3730a3 !important;
  box-shadow: 0 1px 3px rgba(67, 56, 202, 0.30) !important;
}

/* Abbruch (grau) */
tr.task-row[data-gewerk="Abbruch"] .gantt-bar {
  background: linear-gradient(180deg, #9ca3af 0%, #4b5563 100%) !important;
  border-color: #374151 !important;
  box-shadow: 0 1px 3px rgba(75, 85, 99, 0.30) !important;
}

/* Aufzug (anthrazit) */
tr.task-row[data-gewerk="Aufzug"] .gantt-bar {
  background: linear-gradient(180deg, #64748b 0%, #1e293b 100%) !important;
  border-color: #0f172a !important;
  box-shadow: 0 1px 3px rgba(30, 41, 59, 0.30) !important;
}

/* Gerüst (gelb-grau) */
tr.task-row[data-gewerk="Gerüst"] .gantt-bar {
  background: linear-gradient(180deg, #fbbf24 0%, #ca8a04 100%) !important;
  border-color: #a16207 !important;
  box-shadow: 0 1px 3px rgba(202, 138, 4, 0.30) !important;
}

/* Status-Overlays (zusätzlich zur Gewerk-Farbe als Muster/Outline) */
/* Verzögert: roter Rand */
tr.task-row[data-status="verzögert"] .gantt-bar {
  outline: 2px solid #dc2626 !important;
  outline-offset: 1px;
}
/* Abgeschlossen: grünes diagonales Streifen-Pattern */
tr.task-row[data-status="abgeschlossen"] .gantt-bar::after {
  content: "";
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(45deg, transparent 0, transparent 4px, rgba(255,255,255,0.25) 4px, rgba(255,255,255,0.25) 7px);
  pointer-events: none;
}
/* Priorität: orange dicker Border */
tr.task-row[data-status="priorität"] .gantt-bar {
  outline: 2px solid #f97316 !important;
  outline-offset: 1px;
}
/* ══════════════ END BARS NACH GEWERK ══════════════ */

/* ══════════════ TODAY LINE + TIMELINE SHIFT ══════════════ */
.today-line {
  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 2px !important;
  background: #ef4444 !important;
  z-index: 30 !important;
  pointer-events: none;
  box-shadow: 0 0 8px rgba(239, 68, 68, 0.50);
}
.today-line::before {
  content: "HEUTE";
  position: absolute;
  top: -18px;
  left: -22px;
  background: #ef4444;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
/* Today-line über alle Zeilen erweitern */
.gantt-table { position: relative; }
.gantt-row-inner { position: relative; overflow: visible !important; }
/* In jeder Aufgaben-Zeile: kein "HEUTE"-Label wiederholen (nur Strich) */
.today-line-row::before { content: none !important; display: none !important; }
/* ══════════════ END ══════════════ */
</style>
</head>
<body>

<div class="header">
  <div>
    <div class="h1">Bauzeitenplan HAUPTWERK</div>
    <div class="sub">Wilhelm-Binder-Str. 15 · VS-Villingen · Stand <span id="stand-time"><?php
      $tz = new DateTimeZone('Europe/Berlin');
      $now = new DateTime('now', $tz);
      echo $now->format('j.n.Y, H:i') . ' Uhr';
    ?></span></div>
  </div>
</div>
</div>

<div class="summary" id="header-summary">
  <div class="card done" data-cat="done" role="button" tabindex="0" onclick="filterStatusCard('done',this)" title="Nur abgeschlossene Aufgaben zeigen — nochmal klicken: alle"><div class="num" id="hdr-done">—</div><div class="lbl">Abgeschlossen</div></div>
  <div class="card wip" data-cat="wip" role="button" tabindex="0" onclick="filterStatusCard('wip',this)" title="Nur Aufgaben in Arbeit (inkl. Priorität) zeigen — nochmal klicken: alle"><div class="num" id="hdr-wip">—</div><div class="lbl">In Arbeit</div></div>
  <div class="card planned" data-cat="planned" role="button" tabindex="0" onclick="filterStatusCard('planned',this)" title="Nur geplante Aufgaben zeigen — nochmal klicken: alle"><div class="num" id="hdr-plan">—</div><div class="lbl">Geplant</div></div>
  <div class="card delayed" data-cat="delayed" role="button" tabindex="0" onclick="filterStatusCard('delayed',this)" title="Nur verzögerte Aufgaben zeigen — nochmal klicken: alle"><div class="num" id="hdr-delay">—</div><div class="lbl">Verzögert</div></div>
  <button type="button" id="status-clear-btn" class="status-clear-chip" onclick="clearStatusFilter()" style="display:none" title="Status-Filter aufheben — alle Aufgaben zeigen">✕ Alle anzeigen</button>
  <div class="progress-bar-wrap">
    <div class="progress-bar">
      <div id="hdr-prog-fill" class="progress-fill" style="width:0%"></div>
    </div>
    <div class="progress-pct" id="hdr-prog-pct">—</div>
    <div style="font-size:10px;color:#64748b" id="hdr-prog-txt">Fertigstellung</div>
  </div>
</div>


<!-- ── Delay Info Bar ── -->
<div id="delay-info-bar" style="display:none;gap:6px;flex-wrap:wrap;
     padding:6px 24px;background:#fffbeb;border-bottom:1px solid #fde68a;
     align-items:center;font-size:10px">
  <span style="font-weight:600;color:#92400e;margin-right:4px">⏰ Aktive Verzögerungen:</span>
</div>

<!-- ── Floating Delay Panel ── -->
<div id="delay-panel">
  <h3>⏰ Terminsteuerung
    <button onclick="togglePanel()" style="margin-left:auto;background:none;border:none;
      cursor:pointer;font-size:14px;color:#94a3b8">✕</button>
  </h3>
  <p style="font-size:10px;color:#64748b;margin:-6px 0 10px">
    Verzögerung in Wochen eingeben — alle abhängigen Gewerke verschieben sich automatisch
  </p>
  
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#1e40af;
          display:inline-block;flex-shrink:0"></span>
        <label>Phase 2 — Roh & Installation</label>
        <input type="number" class="delay-input" data-delay-group="ph1b_roh"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;
          display:inline-block;flex-shrink:0"></span>
        <label>Estrich Phase 2</label>
        <input type="number" class="delay-input" data-delay-group="ph1b_estrich"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#1e40af;
          display:inline-block;flex-shrink:0"></span>
        <label>Phase 2 — Ausbau</label>
        <input type="number" class="delay-input" data-delay-group="ph1b_ausbau"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#0f766e;
          display:inline-block;flex-shrink:0"></span>
        <label>Phase 3 — Roh & Installation</label>
        <input type="number" class="delay-input" data-delay-group="ph2_roh"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;
          display:inline-block;flex-shrink:0"></span>
        <label>Estrich Phase 3</label>
        <input type="number" class="delay-input" data-delay-group="ph2_estrich"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#0f766e;
          display:inline-block;flex-shrink:0"></span>
        <label>Phase 3 — Ausbau</label>
        <input type="number" class="delay-input" data-delay-group="ph2_ausbau"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;
          display:inline-block;flex-shrink:0"></span>
        <label>OHG Haustechnik</label>
        <input type="number" class="delay-input" data-delay-group="haustechnik"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
      <div class="delay-row">
        <span style="width:8px;height:8px;border-radius:50%;background:#ea580c;
          display:inline-block;flex-shrink:0"></span>
        <label>WEG Hochbau Ost</label>
        <input type="number" class="delay-input" data-delay-group="hochbau"
               value="0" min="-52" max="52" step="0.5">
        <span class="delay-unit">Wo</span>
      </div>
  <div class="delay-actions">
    <button id="btn-apply-delays" class="btn-apply" onclick="onApply()">▶ Anwenden</button>
    <button class="btn-reset" onclick="onReset()">↺ Reset</button>
  </div>
</div>
<button id="btn-toggle-panel" class="btn-toggle-panel" onclick="togglePanel()">⏰</button>

<div class="tabs">
  <div class="tab active" onclick="showTab('hauptwerk',this)">📊 Hauptzeitplan (374 Aufgaben)</div>
  <div class="tab" onclick="showTab('todos',this)">✅ TODs / Was liegt an (KW21–30)</div>
  <div class="tab" onclick="showTab('wohnungen',this)">🏠 Einheiten</div>
  <div class="tab" onclick="showTab('kosten',this)">💶 Budgetplanung</div>
  <div class="tab" onclick="showTab('kapazitaet',this)">👷 Kapazität</div>
  <div class="tab" onclick="showTab('bestellungen',this)">📦 Bestellungen</div>
</div>

<script>
function showTab(name, el) {
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  var tc = document.getElementById('tab-'+name);
  if (tc) tc.classList.add('active');
  if (el) el.classList.add('active');
  else {
    // Wenn ohne el aufgerufen (z.B. beim Wiederherstellen), passenden Tab-Button finden
    var tabBtn = document.querySelector('.tab[onclick*="showTab(\'' + name + '\'"]');
    if (tabBtn) tabBtn.classList.add('active');
  }
  // Body-Attribut für CSS-basierte FAB-Steuerung
  document.body.dataset.activeTab = name;
  try { localStorage.setItem('active-tab', name); } catch(e) {}
  if (name === 'bestellungen' && typeof renderOrders === 'function') renderOrders();
  if (name === 'kosten' && typeof window.renderCostOrders === 'function') window.renderCostOrders();
  if (name === 'kosten' && typeof window.renderBudgetCustom === 'function') window.renderBudgetCustom();
  if (name === 'kosten' && typeof window.makeBudgetPositionsEditable === 'function') window.makeBudgetPositionsEditable();
  if (name === 'kapazitaet' && typeof window.renderKapaCockpit === 'function') window.renderKapaCockpit();
  if (typeof window.updateTabSummary === 'function') window.updateTabSummary(name);
}
// Beim Laden: zuletzt aktiven Tab wiederherstellen
document.addEventListener('DOMContentLoaded', function () {
  // Standard-Tab-Attribut setzen (CSS-Anker für FAB-Visibility)
  if (!document.body.dataset.activeTab) document.body.dataset.activeTab = 'hauptwerk';
  try {
    var saved = localStorage.getItem('active-tab');
    if (saved && saved !== 'hauptwerk' && document.getElementById('tab-' + saved)) {
      showTab(saved, null);
    }
  } catch (e) {}
  // Budget-Positionen einmalig initialisieren (Overrides anwenden + editierbar machen)
  if (typeof window.makeBudgetPositionsEditable === 'function') {
    setTimeout(window.makeBudgetPositionsEditable, 200);
  }
});

// Status-Übersichtsbalken je Tab anpassen
window.updateTabSummary = function (tabName) {
  // FAB "+ Neue Aufgabe", ⏰-Toggle und "Heute"-Button nur im Hauptzeitplan zeigen
  var fabPlus = document.querySelector('.btn-new-task');
  var fabClock = document.getElementById('btn-toggle-panel');
  var fabToday = document.getElementById('today-fab');
  var isMain = (tabName === 'hauptwerk');
  if (fabPlus)  fabPlus.style.display  = isMain ? '' : 'none';
  if (fabClock) fabClock.style.display = isMain ? '' : 'none';
  if (fabToday) fabToday.style.display = isMain ? '' : 'none';

  var bar = document.getElementById('header-summary');
  if (!bar) return;
  // Tabs ohne sinnvolle Status-Statistik → Balken ausblenden
  var hideTabs = ['kosten', 'kapazitaet', 'wohnungen', 'todos'];
  if (hideTabs.indexOf(tabName) !== -1) { bar.style.display = 'none'; return; }
  bar.style.display = '';

  function set(id, v) { var e = document.getElementById(id); if (e) e.textContent = v; }
  function setLbl(card, txt) { var l = card.querySelector('.lbl'); if (l) l.textContent = txt; }

  if (tabName === 'bestellungen') {
    var done = 0, wip = 0, planned = 0, delayed = 0;
    (window.boOrders || []).forEach(function(o){
      var s = o.status || '';
      if (s === 'geliefert') done++;
      else if (s === 'ausstehend' || s === 'Lieferung ausstehend') delayed++;
      else if (s === 'laufend' || s === 'bestellt' || s === 'AB erhalten' || s === 'Angebot freigegeben') wip++;
      else planned++;  // geplant + Angebot angefordert/erhalten/geprüft
    });
    set('hdr-done', done); set('hdr-wip', wip); set('hdr-plan', planned); set('hdr-delay', delayed);
    // Labels für Bestellungen leicht angepasst
    var cards = bar.querySelectorAll('.card');
    if (cards[0]) setLbl(cards[0], 'Geliefert');
    if (cards[1]) setLbl(cards[1], 'In Arbeit');
    if (cards[2]) setLbl(cards[2], 'In Vorbereitung');
    if (cards[3]) setLbl(cards[3], 'Ausstehend');
    // Fortschrittsbalken
    var total = done + wip + planned + delayed;
    var fill = document.getElementById('hdr-prog-fill');
    if (fill) fill.style.width = (total ? Math.round(done/total*100) : 0) + '%';
  } else {
    // Hauptzeitplan: ursprüngliche Labels wiederherstellen + recount
    var cards2 = bar.querySelectorAll('.card');
    if (cards2[0]) setLbl(cards2[0], 'Abgeschlossen');
    if (cards2[1]) setLbl(cards2[1], 'In Arbeit');
    if (cards2[2]) setLbl(cards2[2], 'Geplant');
    if (cards2[3]) setLbl(cards2[3], 'Verzögert');
    if (typeof window.__recountStats === 'function') window.__recountStats();
  }
  // Aktive Status-Card-Hervorhebung mit dem aktuellen Tab synchronisieren (nur im Hauptzeitplan sichtbar)
  if (typeof syncStatusCardHighlight === 'function') syncStatusCardHighlight();
};

var activeGewerk = 'all';            // Rückwärtskompat (Einzel-Anzeige)
var selectedGewerke = [];            // Mehrfachauswahl Gewerke (leer = alle). Referenz NIE neu zuweisen (nur push/splice/length=0).
window.selectedGewerke = selectedGewerke;
var activeStatusCat = null;   // Status-Kategorie-Filter (done|wip|planned|delayed) oder null = alle; gesteuert über die Counter-Cards oben

// Mapping: alte einzelne Gewerk-Namen → neue konsolidierte
function mapGewerk(g) {
  if (!g) return '';
  if (['Sanitär','Heizung','Klima','Lüftung'].indexOf(g) >= 0) return 'Sanitär/Heizung';
  if (['Maler','Gipser','Maler/Trockenbau'].indexOf(g) >= 0) return 'Maler/Gipser';
  // Trockenbau bleibt separat
  if (['Bodenbelag/Fliesen'].indexOf(g) >= 0) return 'Bodenbelag';  // Defaultauflösung
  if (['Schreiner','Möblierung','Endmontage'].indexOf(g) >= 0) return 'Schreiner/Endmontage';
  if (['Dachdecker','WDVS','Fassade','Dämmung','Fenster'].indexOf(g) >= 0) return 'Dach/Fassade';
  if (['Planung','Architekt','Gutachter'].indexOf(g) >= 0) return 'Planung/Architekt';
  return g;  // Elektro, Estrich, Brandschutz, Trockenbau, Bodenbelag, Fliesen bleiben gleich
}
window.mapGewerk = mapGewerk;

function filterGewerk(gw) {
  if (gw === 'all') {
    selectedGewerke.length = 0;                         // alles leeren
  } else {
    var idx = selectedGewerke.indexOf(gw);
    if (idx >= 0) selectedGewerke.splice(idx, 1);       // abwählen
    else selectedGewerke.push(gw);                      // hinzufügen (Mehrfach)
  }
  activeGewerk = selectedGewerke.length === 1 ? selectedGewerke[0]
              : (selectedGewerke.length === 0 ? 'all' : '__multi__');
  window.activeGewerk = activeGewerk;
  applyFilters();
  updateGewerkPills();
}

// Pills entsprechend selectedGewerke hervorheben
function updateGewerkPills() {
  document.querySelectorAll('#tab-hauptwerk .filter-btn').forEach(function (b) {
    var oc = b.getAttribute('onclick') || '';
    if (b.classList.contains('gw-filter-btn')) {
      var t = (b.textContent || '').trim();
      b.classList.toggle('active', selectedGewerke.indexOf(t) >= 0);
    } else if (oc.indexOf("filterGewerk('all')") >= 0) {
      b.classList.toggle('active', selectedGewerke.length === 0);
    }
  });
}
window.updateGewerkPills = updateGewerkPills;

// ── Fester Tages-Maßstab: 126px/Woche = 18px/Tag, echte Pixel (kein Zoom/scaleX → scharf).
// Es gibt nur EINE Ansicht (keine Wochenansicht mehr). GANTT_Z bleibt 1 (Kompat für
// vorhandene *GANTT_Z-Stellen, die damit No-ops sind). ──
window.GANTT_Z = 1;
// Einmal-Cleanup bei Umstellung auf 126px-Basis: alte (42-Basis) bar-pos-Caches verwerfen.
// Balken-Positionen kommen aus statischem HTML + DB-Sync (beide 126-Basis).
(function(){ try { if (localStorage.getItem('coord-scale-v126') !== '1') {
  Object.keys(localStorage).forEach(function(k){ if (k.indexOf('bar-pos-') === 0) localStorage.removeItem(k); });
  localStorage.setItem('coord-scale-v126', '1');
} } catch(e){} })();

// Wochentag-Kopf (Mo–So), immer sichtbar. Tag 0 = KW23-Montag (1. Juni 2026), 18px/Tag.
function ensureDayHeader() {
  var kwh = document.querySelector('#tab-hauptwerk .gantt-kw-header');
  if (!kwh || kwh.parentNode.querySelector('.gantt-day-header')) return;
  var wd = ['Mo','Di','Mi','Do','Fr','Sa','So'], days = 82 * 7, html = '';
  for (var n = 0; n < days; n++) html += '<span style="position:absolute;top:0;left:' + (n*18) + 'px;width:18px;height:14px;font-size:8px;line-height:14px;text-align:center;color:#94a3b8;font-weight:600;overflow:hidden">' + wd[n%7] + '</span>';
  var dh = document.createElement('div');
  dh.className = 'gantt-day-header';
  dh.style.cssText = 'position:relative;height:14px;width:10800px;border-bottom:1px solid #f1f5f9';
  dh.innerHTML = html;
  kwh.parentNode.insertBefore(dh, kwh.nextSibling);
}
window.ensureDayHeader = ensureDayHeader;
// Beim Laden erzeugen (mehrfach versucht, da Tabelle/Sticky verzögert kommen können).
(function () {
  function t() { ensureDayHeader(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { setTimeout(t, 300); });
  else setTimeout(t, 300);
  setTimeout(t, 1200);
})();

// Counter-Card oben klicken = nach Status-Kategorie filtern (toggelt; nochmal klicken = alle).
// Kategorien identisch zu classifyStatus(), damit ein Klick genau die gezählten Zeilen zeigt.
function filterStatusCard(cat, cardEl) {
  // Cards sind global sichtbar (auch im Bestellungen-Tab) – der Filter wirkt nur auf den Hauptzeitplan.
  if (document.body.dataset.activeTab !== 'hauptwerk') showTab('hauptwerk', null);
  activeStatusCat = (activeStatusCat === cat) ? null : cat;
  syncStatusCardHighlight();
  applyFilters();
}
window.filterStatusCard = filterStatusCard;

// Aktive Counter-Card hervorheben (nur im Hauptzeitplan; in anderen Tabs nichts markieren).
function syncStatusCardHighlight() {
  var onMain = document.body.dataset.activeTab === 'hauptwerk';
  document.querySelectorAll('#header-summary .card').forEach(function (c) {
    c.classList.toggle('card-filter-active', onMain && c.dataset.cat === activeStatusCat);
  });
  // "✕ Alle anzeigen"-Button nur zeigen, wenn im Hauptzeitplan ein Status-Filter aktiv ist
  var clr = document.getElementById('status-clear-btn');
  if (clr) clr.style.display = (onMain && activeStatusCat) ? '' : 'none';
}
window.syncStatusCardHighlight = syncStatusCardHighlight;

// Status-Filter komplett aufheben (alle Aufgaben zeigen) — vom "✕ Alle anzeigen"-Button.
function clearStatusFilter() {
  activeStatusCat = null;
  syncStatusCardHighlight();
  applyFilters();
}
window.clearStatusFilter = clearStatusFilter;

// Tastatur-Bedienung der Counter-Cards (Enter/Leertaste), da role="button".
document.addEventListener('keydown', function (e) {
  if (e.key !== 'Enter' && e.key !== ' ') return;
  var card = e.target.closest && e.target.closest('#header-summary .card[data-cat]');
  if (!card) return;
  e.preventDefault();
  filterStatusCard(card.dataset.cat, card);
});

function applyFilters() {
  var norm = (typeof window.mapGewerk === 'function') ? window.mapGewerk : function(x){return x;};
  var sel = selectedGewerke;                       // Mehrfachauswahl (leer = alle)

  document.querySelectorAll('#main-gantt .task-row').forEach(row => {
    var gw = row.dataset.gewerk || '';
    var st = row.dataset.status || '';
    var gwN = norm(gw);
    var gwOk = sel.length === 0 || sel.some(function (g) {
      return gwN === norm(g) || gw === g || gw.includes(g);
    });
    var stOk = !activeStatusCat || classifyStatus(st) === activeStatusCat;
    row.style.display = (gwOk && stOk) ? '' : 'none';
  });

  // Leere Sections (keine sichtbaren task-rows zwischen Section und nächster Section/KfW) ausblenden
  var anyFilter = (selectedGewerke.length > 0) || !!activeStatusCat;
  var tbody = document.querySelector('#main-gantt tbody');
  if (!tbody) return;
  var sections = [], kfws = [];
  Array.from(tbody.children).forEach(function (tr, i) {
    if (tr.classList.contains('section-row')) sections.push({ row: tr, i: i });
    if (tr.classList.contains('kfw-header-row')) kfws.push({ row: tr, i: i });
  });
  function hasVisibleTaskAfter(idx, stopIdx) {
    var children = tbody.children;
    for (var j = idx + 1; j < stopIdx && j < children.length; j++) {
      var n = children[j];
      if (n.classList && (n.classList.contains('section-row') || n.classList.contains('kfw-header-row'))) break;
      if (n.classList && n.classList.contains('task-row') && n.style.display !== 'none') return true;
    }
    return false;
  }
  // Section-Sichtbarkeit
  sections.forEach(function (s, k) {
    if (!anyFilter) { s.row.style.display = ''; return; }
    var nextIdx = (sections[k+1] ? sections[k+1].i : 1e9);
    var nextKfw = kfws.find(function(x){ return x.i > s.i; });
    var stop = Math.min(nextIdx, nextKfw ? nextKfw.i : 1e9);
    s.row.style.display = hasVisibleTaskAfter(s.i, stop) ? '' : 'none';
  });
  // KfW-Sichtbarkeit (zeigen, wenn mindestens eine Section darunter sichtbar bleibt)
  kfws.forEach(function (k, idx) {
    if (!anyFilter) { k.row.style.display = ''; return; }
    var nextKfw = kfws[idx+1];
    var stop = nextKfw ? nextKfw.i : 1e9;
    var anyVisible = false;
    for (var j = k.i + 1; j < stop && j < tbody.children.length; j++) {
      var n = tbody.children[j];
      if (!n) break;
      if (n.classList && n.classList.contains('section-row') && n.style.display !== 'none') { anyVisible = true; break; }
      if (n.classList && n.classList.contains('task-row') && n.style.display !== 'none') { anyVisible = true; break; }
    }
    k.row.style.display = anyVisible ? '' : 'none';
  });
  // Heat-Strip + Today-Line synchron halten
  if (typeof window.renderKapaHeatStrip === 'function') setTimeout(window.renderKapaHeatStrip, 30);
  if (typeof window.updateTodayLine === 'function') setTimeout(window.updateTodayLine, 50);
}

function clearFilters() {
  selectedGewerke.length = 0; activeGewerk = 'all'; window.activeGewerk = 'all'; activeStatusCat = null;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('.filter-btn').classList.add('active');
  syncStatusCardHighlight();
  document.querySelectorAll('#main-gantt .task-row').forEach(r => r.style.display = '');
  if (typeof window.renderKapaHeatStrip === 'function') setTimeout(window.renderKapaHeatStrip, 30);
}

// Abschnitte zusammenklappen
document.querySelectorAll('.section-row .section-name').forEach(el => {
  el.addEventListener('click', function() {
    var tr = this.closest('tr');
    var next = tr.nextElementSibling;
    var collapsed = false;
    while (next && !next.classList.contains('section-row') && !next.classList.contains('kfw-header-row')) {
      next.style.display = next.style.display === 'none' ? '' : 'none';
      if (next.style.display === 'none') collapsed = true;
      next = next.nextElementSibling;
    }
    this.querySelector('.section-arrow').textContent = collapsed ? '▶' : '▼';
  });
  el.querySelector('.section-arrow').textContent = '▼';
});


function filterWohn(val, btn) {
  document.querySelectorAll('#wohn-filters button').forEach(b => {
    b.style.background = '#f8fafc'; b.style.color = '#374151'; b.style.borderColor = '#e2e8f0';
  });
  btn.style.background = '#2563eb'; btn.style.color = '#fff'; btn.style.borderColor = '#2563eb';
  document.querySelectorAll('.wohn-card').forEach(c => {
    if (val === 'all') { c.style.display = ''; return; }
    const typ = c.dataset.typ || '';
    const ph  = c.dataset.phase || '';
    const df  = c.dataset.diff || '0';
    const match =
      (val === '2zi'      && typ === '2zi') ||
      (val === 'studio'   && typ === 'studio') ||
      (val === 'gewerbe'  && typ === 'gewerbe') ||
      (val === 'ph1'      && ph  === 'ph1') ||
      (val === 'ph2'      && ph  === 'ph2') ||
      (val === 'diff'     && df  === '1');
    c.style.display = match ? '' : 'none';
  });
}
function scrollToCard(id) {
  const card = document.getElementById('card-' + id.replace(/ /g,'_').replace(/\./g,'_'));
  if (card) card.scrollIntoView({behavior:'smooth', block:'start'});
}


// Sync today-line in merged GH section
(function() {
  var tl = document.getElementById('today-line-gh');
  var main = document.querySelector('#main-gantt .today-line');
  if (tl && main) {
    tl.style.left = "0px";
    tl.style.top = '0'; tl.style.bottom = '0';
    tl.style.width = '2px';
    tl.style.background = '#ef4444';
    tl.style.position = 'absolute';
    tl.style.zIndex = '20';
    tl.style.pointerEvents = 'none';
  }
})();


// Hauptzeitplan: Scroll-Lock auf KW23 (links davon nicht erreichbar)
// KW 19–22 sind nur über horizontalen Scroll im Plan ausgeblendet, NICHT layout-shift
(function () {
  var KW23_PX = 0;
  function lockScroll(wrap) {
    if (!wrap || wrap.dataset.kw23Locked) return;
    wrap.dataset.kw23Locked = '1';
    wrap.scrollLeft = KW23_PX;
    wrap.addEventListener('scroll', function () {
      if (wrap.scrollLeft < KW23_PX) wrap.scrollLeft = KW23_PX;
    }, { passive: true });
  }
  function init() { document.querySelectorAll('.gantt-wrap').forEach(lockScroll); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
  window.addEventListener('load', init);
  setTimeout(init, 600);
})();


// (WEG HBO Override entfernt – Tab existiert nicht mehr)

</script>

<!-- ── HAUPTZEITPLAN ── -->
<div id="tab-hauptwerk" class="tab-content active">


<div class="filter-bar">
  <label>Filter Gewerk:</label>
  <button class="filter-btn active" onclick="filterGewerk('all')">Alle Gewerke</button>
  <span id="gewerk-filter-pills" style="display:contents"></span>
  <button class="filter-btn" id="gewerk-filter-manage" onclick="window.openGewerkeManager()" title="Gewerke verwalten — anlegen, umbenennen, Farbe, löschen" style="margin-left:auto;padding:4px 11px;border-radius:14px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:11px;font-weight:600;cursor:pointer">🔧 Gewerke verwalten</button>
</div>
<!-- Status-Filter jetzt über die Counter-Cards oben (filterStatusCard) — separate Leiste entfernt -->


<div class="gantt-wrap">
<table class="gantt-table" id="main-gantt">
<colgroup>
  <col>
  <col style="width:60px">
  <col style="width:100px">
  <col style="width:100px">
  <col style="width:10800px">
</colgroup>
<thead>
<tr>
  <th class="task-name-cell">Aufgabe</th>
  <th>Status</th>
    <th>Gewerk</th>
    <th>Firma</th>
  <th>
    <div class="gantt-timeline-header" style="width:10800px"><div class="month-label" style="left:0px;width:504px">Jun 26</div><div class="month-label" style="left:504px;width:504px">Jul 26</div><div class="month-label" style="left:1008px;width:630px">Aug 26</div><div class="month-label" style="left:1638px;width:504px">Sep 26</div><div class="month-label" style="left:2142px;width:504px">Okt 26</div><div class="month-label" style="left:2646px;width:630px">Nov 26</div><div class="month-label" style="left:3276px;width:504px">Dez 26</div><div class="month-label" style="left:3780px;width:630px">Jan 27</div><div class="month-label" style="left:4410px;width:504px">Feb 27</div><div class="month-label" style="left:4914px;width:504px">Mär 27</div><div class="month-label" style="left:5418px;width:504px">Apr 27</div><div class="month-label" style="left:5922px;width:630px">Mai 27</div><div class="month-label" style="left:6552px;width:504px">Jun 27</div><div class="month-label" style="left:7056px;width:504px">Jul 27</div><div class="month-label" style="left:7560px;width:630px">Aug 27</div><div class="month-label" style="left:8190px;width:504px">Sep 27</div><div class="month-label" style="left:8694px;width:630px">Okt 27</div><div class="month-label" style="left:9324px;width:504px">Nov 27</div><div class="month-label" style="left:9828px;width:504px">Dez 27</div></div>

    </div>
    <div class="gantt-kw-header" style="width:10800px;position:relative"><div class="kw-label" style="left:0px">KW23</div><div class="kw-label" style="left:126px">KW24</div><div class="kw-label" style="left:252px">KW25</div><div class="kw-label" style="left:378px">KW26</div><div class="kw-label" style="left:504px">KW27</div><div class="kw-label" style="left:630px">KW28</div><div class="kw-label" style="left:756px">KW29</div><div class="kw-label" style="left:882px">KW30</div><div class="kw-label" style="left:1008px">KW31</div><div class="kw-label" style="left:1134px">KW32</div><div class="kw-label" style="left:1260px">KW33</div><div class="kw-label" style="left:1386px">KW34</div><div class="kw-label" style="left:1512px">KW35</div><div class="kw-label" style="left:1638px">KW36</div><div class="kw-label" style="left:1764px">KW37</div><div class="kw-label" style="left:1890px">KW38</div><div class="kw-label" style="left:2016px">KW39</div><div class="kw-label" style="left:2142px">KW40</div><div class="kw-label" style="left:2268px">KW41</div><div class="kw-label" style="left:2394px">KW42</div><div class="kw-label" style="left:2520px">KW43</div><div class="kw-label" style="left:2646px">KW44</div><div class="kw-label" style="left:2772px">KW45</div><div class="kw-label" style="left:2898px">KW46</div><div class="kw-label" style="left:3024px">KW47</div><div class="kw-label" style="left:3150px">KW48</div><div class="kw-label" style="left:3276px">KW49</div><div class="kw-label" style="left:3402px">KW50</div><div class="kw-label" style="left:3528px">KW51</div><div class="kw-label" style="left:3654px">KW52</div><div class="kw-label" style="left:3780px">KW1</div><div class="kw-label" style="left:3906px">KW2</div><div class="kw-label" style="left:4032px">KW3</div><div class="kw-label" style="left:4158px">KW4</div><div class="kw-label" style="left:4284px">KW5</div><div class="kw-label" style="left:4410px">KW6</div><div class="kw-label" style="left:4536px">KW7</div><div class="kw-label" style="left:4662px">KW8</div><div class="kw-label" style="left:4788px">KW9</div><div class="kw-label" style="left:4914px">KW10</div><div class="kw-label" style="left:5040px">KW11</div><div class="kw-label" style="left:5166px">KW12</div><div class="kw-label" style="left:5292px">KW13</div><div class="kw-label" style="left:5418px">KW14</div><div class="kw-label" style="left:5544px">KW15</div><div class="kw-label" style="left:5670px">KW16</div><div class="kw-label" style="left:5796px">KW17</div><div class="kw-label" style="left:5922px">KW18</div><div class="kw-label" style="left:6048px">KW19</div><div class="kw-label" style="left:6174px">KW20</div><div class="kw-label" style="left:6300px">KW21</div><div class="kw-label" style="left:6426px">KW22</div><div class="kw-label" style="left:6552px">KW23</div><div class="kw-label" style="left:6678px">KW24</div><div class="kw-label" style="left:6804px">KW25</div><div class="kw-label" style="left:6930px">KW26</div><div class="kw-label" style="left:7056px">KW27</div><div class="kw-label" style="left:7182px">KW28</div><div class="kw-label" style="left:7308px">KW29</div><div class="kw-label" style="left:7434px">KW30</div><div class="kw-label" style="left:7560px">KW31</div><div class="kw-label" style="left:7686px">KW32</div><div class="kw-label" style="left:7812px">KW33</div><div class="kw-label" style="left:7938px">KW34</div><div class="kw-label" style="left:8064px">KW35</div><div class="kw-label" style="left:8190px">KW36</div><div class="kw-label" style="left:8316px">KW37</div><div class="kw-label" style="left:8442px">KW38</div><div class="kw-label" style="left:8568px">KW39</div><div class="kw-label" style="left:8694px">KW40</div><div class="kw-label" style="left:8820px">KW41</div><div class="kw-label" style="left:8946px">KW42</div><div class="kw-label" style="left:9072px">KW43</div><div class="kw-label" style="left:9198px">KW44</div><div class="kw-label" style="left:9324px">KW45</div><div class="kw-label" style="left:9450px">KW46</div><div class="kw-label" style="left:9576px">KW47</div><div class="kw-label" style="left:9702px">KW48</div><div class="kw-label" style="left:9828px">KW49</div><div class="kw-label" style="left:9954px">KW50</div><div class="kw-label" style="left:10080px">KW51</div><div class="kw-label" style="left:10206px">KW52</div></div>

    </div>
  </th>
</tr>
</thead>
<tbody>
        <tr class="kfw-header-row kfw-b">
          <td class="task-name-cell" colspan="4">
            <span class="kfw-badge kfw-b">KfW</span>
            OHG Haustechnik
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Stromversorgung
            <span class="progress-pill">2/5 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="Sanitär" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-1">
          
          <td class="task-name-cell">Hausanschluss, Abwasser / Regenwasser (Entwässerungskonzept)</td>
          <td><span class="status-badge status-wip">Antrag ergänz. (SF)</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="Elektro" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-2">
          
          <td class="task-name-cell">NSHV</td>
          <td><span class="status-badge status-wip">Bestellung (DIB)</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:2106px;width:126px" title="11.04 NSHV | Bestellung (DIB) | —"></div></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="Elektro" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-3">
          
          <td class="task-name-cell">Batteriespeicher</td>
          <td><span class="status-badge status-wip">ist da</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:2358px;width:126px" title="11.05 Batteriespeicher | ist da | —"></div></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Kaltwassersatz (5,02m x 2,59m, 4,1 Tonnen)
            <span class="progress-pill">4/5 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="fortschritt_75" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-4">
          
          <td class="task-name-cell">Installation</td>
          <td><span class="status-badge status-wip">75 %</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:2106px;width:126px" title="12.05 Installation | 75 % | —"></div></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Wärmepumpen
            <span class="progress-pill">2/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="verzögert" data-gewerk="Heizung" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-5">
          
          <td class="task-name-cell">Sole Wasser Wärmepumpe Brigach (Überlaufwasser Erwärmung)</td>
          <td><span class="status-badge status-delayed">dauert</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-delayed" style="left:2610px;width:126px" title="13.01 Sole Wasser Wärmepumpe Brigach (Überlaufwasser Erwärmung) | dauert | —"></div></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Lüftungsanlagen 2+3 (Shedhalle)
            <span class="progress-pill">2/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-6">
          
          <td class="task-name-cell">Installation</td>
          <td><span class="status-badge status-wip">beginnt nach Lieferung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Lüftungskanäle / Heizungsoptimierung
            <span class="progress-pill">0/1 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-7">
          
          <td class="task-name-cell">Installation</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Regen- & Schmutzwasserleitungen
            <span class="progress-pill">0/2 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Sanitär" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-10">
          
          <td class="task-name-cell">Installation Regen + Schmutzwasserleitung</td>
          <td><span class="status-badge status-prio">Priorität</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Tiefbau" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-11">
          
          <td class="task-name-cell">Hof asphaltieren</td>
          <td><span class="status-badge status-prio">Priorität</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f5f5f4;color:#78716c;border:1px solid #78716c40">Tiefbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Aufzüge
            <span class="progress-pill">3/25 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Brandschutz" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-12">
          
          <td class="task-name-cell">- Schachtentrauchung</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-13">
          
          <td class="task-name-cell">- Demontage Sockel</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-14">
          
          <td class="task-name-cell">- Demontage Kabinen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-15">
          
          <td class="task-name-cell">- Sockel wegspitzen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="" data-task-type="tueren" data-tid="haustechnik-tueren-1">
          
          <td class="task-name-cell">- Demontage Türen und Installation Absturzsicherung</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Gerüst" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-16">
          
          <td class="task-name-cell">- Gerüst in 4. OG einsetzen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f9fafb;color:#9ca3af;border:1px solid #9ca3af40">Gerüst</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-17">
          
          <td class="task-name-cell">- Lieferung</td>
          <td><span class="status-badge status-wip">läuft</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:1098px;width:4410px" title="18.10 - Lieferung | läuft | Haushahn"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-18">
          
          <td class="task-name-cell">- Installation</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:5508px;width:1026px" title="18.11 - Installation | — | Haushahn"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-19">
          
          <td class="task-name-cell">- Demontage Aufzugsraum</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Brandschutz" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-20">
          
          <td class="task-name-cell">- Schachtentrauchung</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-21">
          
          <td class="task-name-cell">- Demontage Sockel</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-22">
          
          <td class="task-name-cell">- Demontage Kabinen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-23">
          
          <td class="task-name-cell">- Sockel wegspitzen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="" data-task-type="tueren" data-tid="haustechnik-tueren-2">
          
          <td class="task-name-cell">- Demontage Türen und Installation Absturzsicherung</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Gerüst" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-24">
          
          <td class="task-name-cell">- Gerüst in 4. OG einsetzen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f9fafb;color:#9ca3af;border:1px solid #9ca3af40">Gerüst</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-25">
          
          <td class="task-name-cell">- Decken Schlitzen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-26">
          
          <td class="task-name-cell">- Schachtverkleinerung mit CBS</td>
          <td><span class="status-badge status-wip">Auftrag? (ZOM)</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:3240px;width:126px" title="18.20 - Schachtverkleinerung mit CBS | Auftrag? (ZOM) | CBS"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-27">
          
          <td class="task-name-cell">- Traversen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-28">
          
          <td class="task-name-cell">- Deckenschlitze zumachen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-29">
          
          <td class="task-name-cell">- Rahmen / Verkleinerung EG bauen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="laufend" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-30">
          
          <td class="task-name-cell">- Lieferung</td>
          <td><span class="status-badge status-wip">läuft</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" style="left:1098px;width:4914px" title="18.24 - Lieferung | läuft | Haushahn"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-31">
          
          <td class="task-name-cell">- Installation</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:6012px;width:522px" title="18.25 - Installation | — | Haushahn"></div></div></td>
        </tr>
            
                <tr class="kfw-header-row kfw-c">
          <td class="task-name-cell" colspan="4">
            <span class="kfw-badge kfw-c">KfW</span>
            WEG Hochbau Ost (Gebäudehülle)
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Aussenwand, Fassaden
            <span class="progress-pill">1/9 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dämmung" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-43">
          
          <td class="task-name-cell">Treppenhaus Nord - Dämmelemente</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Dämmung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fenster" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-44">
          
          <td class="task-name-cell">Fenster umsetzen (Sport 1-3)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0369a1;border:1px solid #0369a140">Fenster</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fenster" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-45">
          
          <td class="task-name-cell">Fenster umsetzen (Rest)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0369a1;border:1px solid #0369a140">Fenster</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="WDVS" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-46">
          
          <td class="task-name-cell">Straßenseite - WDVS / Mix</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#d1fae5;color:#059669;border:1px solid #05966940">WDVS</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-47">
          
          <td class="task-name-cell">Balkonseite - WDVS</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fenster" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-48">
          
          <td class="task-name-cell">Treppenhaus Nord - Fenster</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0369a1;border:1px solid #0369a140">Fenster</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fenster" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-49">
          
          <td class="task-name-cell">Straßenseite - Fenster</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0369a1;border:1px solid #0369a140">Fenster</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="WDVS" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-50">
          
          <td class="task-name-cell">Straßenseite - PR-Fassade Büro, Eingang, Kidsarea etc.</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#d1fae5;color:#059669;border:1px solid #05966940">WDVS</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Balkone
            <span class="progress-pill">0/6 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="fortschritt_90" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-51">
          
          <td class="task-name-cell">Balkone Rückseite (3+4. OG)</td>
          <td><span class="status-badge status-wip">90 %</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-52">
          
          <td class="task-name-cell">Geländer Rückseite (3+4. OG)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-53">
          
          <td class="task-name-cell">Entwässerung Rückseite (3+4. OG)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="fortschritt_75" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-54">
          
          <td class="task-name-cell">Brüstungen heraussägen (3+4. OG)</td>
          <td><span class="status-badge status-wip">75 %</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-55">
          
          <td class="task-name-cell">Balkone Straßenseite (3. OG)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-56">
          
          <td class="task-name-cell">Geländer Straßenseite (3. OG)</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Hebe- / Schiebeelemente
            <span class="progress-pill">0/2 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="fortschritt_80" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-57">
          
          <td class="task-name-cell">Balkon, Hebe-/Schiebeelemente - 3. OG</td>
          <td><span class="status-badge status-wip">80 %</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="fortschritt_80" data-gewerk="Schlosser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-other-58">
          
          <td class="task-name-cell">Balkon, Hebe-/Schiebeelemente - 4. OG</td>
          <td><span class="status-badge status-wip">80 %</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Schlosser</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        
            
                
        
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Vordach 1.OG
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-100">
          <td class="task-name-cell">UK Sandwich</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:0px;width:126px" title="KW23"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-101">
          <td class="task-name-cell">Sandwichpaneel 60 mm</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:0px;width:126px" title="KW23"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-102">
          <td class="task-name-cell">Flüssigkunststoff</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:0px;width:126px" title="KW23"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Dach Kaltwassersatz
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="verzögert" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-103" title="Material klären">
          <td class="task-name-cell">Dämmung <span style="color:#94a3b8;font-size:10px">· Material klären</span></td>
          <td><span class="status-badge status-delayed">Material fehlt</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-delayed" style="left:252px;width:252px" title="KW25–26 · Material klären"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-104">
          <td class="task-name-cell">Flüssigkunststoff</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:0px;width:252px" title="KW23–24"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-105">
          <td class="task-name-cell">Wanne</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:0px;width:252px" title="KW23–24"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Dach Brückenbau
            <span class="progress-pill">0/2 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-106">
          <td class="task-name-cell">Rinnen</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:126px;width:252px" title="KW24–25"></div></div></td>
        </tr>
        <tr class="task-row" data-status="verzögert" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-107" title="Bestellen">
          <td class="task-name-cell">Regenrohr <span style="color:#94a3b8;font-size:10px">· Bestellen</span></td>
          <td><span class="status-badge status-delayed">verzögert</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-delayed" style="left:378px;width:252px" title="KW26–27 · Bestellen"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Dach TH Nord
            <span class="progress-pill">0/6 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-108" title="Wann? Kran?">
          <td class="task-name-cell">Decke öffnen für Abbruch (Aufzug) <span style="color:#94a3b8;font-size:10px">· Wann? Kran?</span></td>
          <td><span class="status-badge status-prio">Priorität</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-prio" style="left:630px;width:378px" title="KW28–30 · Wann? Kran?"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-109" title="Unterkonstruktion planen">
          <td class="task-name-cell">Sandwich + UK <span style="color:#94a3b8;font-size:10px">· Unterkonstruktion planen</span></td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:378px" title="KW25–27 · Unterkonstruktion planen"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-110">
          <td class="task-name-cell">Rinnen</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:378px" title="KW25–27"></div></div></td>
        </tr>
        <tr class="task-row" data-status="verzögert" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-111" title="Bestellen">
          <td class="task-name-cell">Regenrohr <span style="color:#94a3b8;font-size:10px">· Bestellen</span></td>
          <td><span class="status-badge status-delayed">verzögert</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-delayed" style="left:504px;width:378px" title="KW27–29 · Bestellen"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-112">
          <td class="task-name-cell">Kantteile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:378px" title="KW25–27"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-113">
          <td class="task-name-cell">ABS</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:378px" title="KW25–27"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Ostseite — EG P.R.-Fassade
            <span class="progress-pill">0/6 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-114">
          <td class="task-name-cell">Holzkonstruktion</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-115">
          <td class="task-name-cell">Raico-Profile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-116">
          <td class="task-name-cell">Glasscheiben</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sonstige" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-117">
          <td class="task-name-cell">Jalousien</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Sonstige</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner/Endmontage" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-118">
          <td class="task-name-cell">Türe</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#b91c1c;border:1px solid #b91c1c40">Schreiner/Endmontage</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-119">
          <td class="task-name-cell">Lamellenfenster</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Ostseite — 1.OG
            <span class="progress-pill">0/8 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-120">
          <td class="task-name-cell">Fenster versetzen 1.+2.OG</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:630px;width:504px" title="KW28–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-121" title="Prüfen">
          <td class="task-name-cell">Fensterbänke <span style="color:#94a3b8;font-size:10px">· Prüfen</span></td>
          <td><span class="status-badge status-prio">Priorität</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-prio" style="left:630px;width:504px" title="KW28–31 · Prüfen"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler/Gipser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-122">
          <td class="task-name-cell">WDVS</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#d1fae5;color:#059669;border:1px solid #05966940">Maler/Gipser</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:630px;width:504px" title="KW28–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="verzögert" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-123">
          <td class="task-name-cell">Regenrohr</td>
          <td><span class="status-badge status-delayed">Material fehlt</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-delayed" style="left:882px;width:504px" title="KW30–33"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sonstige" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-124">
          <td class="task-name-cell">Jalousien 1.+2.OG (Farbe?)</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #47556940">Sonstige</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:630px;width:504px" title="KW28–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Elektro" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-125" title="Besprechen / Planen">
          <td class="task-name-cell">Beleuchtung <span style="color:#94a3b8;font-size:10px">· Besprechen / Planen</span></td>
          <td><span class="status-badge status-prio">Priorität</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#b45309;border:1px solid #b4530940">Elektro</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-prio" style="left:630px;width:504px" title="KW28–31 · Besprechen / Planen"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-126">
          <td class="task-name-cell">Kante Süd-Ost</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:630px;width:504px" title="KW28–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-127">
          <td class="task-name-cell">Kante Nord-Ost</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:630px;width:504px" title="KW28–31"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> 4.OG Erweiterung Terrasse
            <span class="progress-pill">0/4 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-128">
          <td class="task-name-cell">Terrassenplatte 45×90×2</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0e7ff;color:#4f46e5;border:1px solid #4f46e540">Bodenbelag</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:756px;width:378px" title="KW29–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-129">
          <td class="task-name-cell">Stelzlager</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0e7ff;color:#4f46e5;border:1px solid #4f46e540">Bodenbelag</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:756px;width:378px" title="KW29–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-130">
          <td class="task-name-cell">UK für Geländer</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:756px;width:378px" title="KW29–31"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-131">
          <td class="task-name-cell">Glasgeländer</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:756px;width:378px" title="KW29–31"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Westseite — TH Nord
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-132">
          <td class="task-name-cell">Sandwich + UK</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:378px;width:378px" title="KW26–28"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-133">
          <td class="task-name-cell">Kantteile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:378px;width:378px" title="KW26–28"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-134">
          <td class="task-name-cell">Fenster + Lamellenfenster</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:378px;width:378px" title="KW26–28"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Südseite
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-135">
          <td class="task-name-cell">Fenster 3.OG</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:504px;width:378px" title="KW27–29"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-136">
          <td class="task-name-cell">Sandwich + UK (TH Nord)</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:504px;width:378px" title="KW27–29"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-137">
          <td class="task-name-cell">Kantteile (TH Nord)</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:504px;width:378px" title="KW27–29"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Nordseite — Eingang TH Nord
            <span class="progress-pill">0/6 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner/Endmontage" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-138">
          <td class="task-name-cell">Holzkonstruktion</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#b91c1c;border:1px solid #b91c1c40">Schreiner/Endmontage</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-139">
          <td class="task-name-cell">Raico-Profile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-140">
          <td class="task-name-cell">Glasscheiben</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner/Endmontage" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-141">
          <td class="task-name-cell">Türe — Dormakaba</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#b91c1c;border:1px solid #b91c1c40">Schreiner/Endmontage</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-142">
          <td class="task-name-cell">Rosenfelder</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-143">
          <td class="task-name-cell">Fundamente + Bodenplatte</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1134px;width:504px" title="KW32–35"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Nordseite — Fassade
            <span class="progress-pill">0/4 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="priorität" data-gewerk="Elektro" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-144" title="Besprechen / Planen">
          <td class="task-name-cell">Beleuchtung <span style="color:#94a3b8;font-size:10px">· Besprechen / Planen</span></td>
          <td><span class="status-badge status-prio">Priorität</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#b45309;border:1px solid #b4530940">Elektro</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-prio" style="left:1260px;width:378px" title="KW33–35 · Besprechen / Planen"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler/Gipser" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-145">
          <td class="task-name-cell">WDVS 4.OG</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#d1fae5;color:#059669;border:1px solid #05966940">Maler/Gipser</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1260px;width:378px" title="KW33–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-146">
          <td class="task-name-cell">Sandwich + UK</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1260px;width:378px" title="KW33–35"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-147">
          <td class="task-name-cell">Kantteile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1260px;width:378px" title="KW33–35"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Nordseite — TH Nord 4.OG W 5.3
            <span class="progress-pill">0/4 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-148">
          <td class="task-name-cell">Fenster 4.OG W 5.3</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1386px;width:378px" title="KW34–36"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-149">
          <td class="task-name-cell">Sandwich + UK</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1386px;width:378px" title="KW34–36"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-150">
          <td class="task-name-cell">Kantteile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1386px;width:378px" title="KW34–36"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-151">
          <td class="task-name-cell">P.R.-Fassade</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:1386px;width:378px" title="KW34–36"></div></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> Brückenbau — 1.OG untere Decke
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-152">
          <td class="task-name-cell">Sandwich + UK</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:252px" title="KW25–26"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-153">
          <td class="task-name-cell">Dämmung 60 mm</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:252px" title="KW25–26"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Dach/Fassade" data-phase="haustechnik" data-unit="" data-task-type="other" data-tid="haustechnik-gh-154">
          <td class="task-name-cell">Kantteile</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#9a3412;border:1px solid #9a341240">Dach/Fassade</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:252px;width:252px" title="KW25–26"></div></div></td>
        </tr>
                        <tr class="kfw-header-row" style="background:#94a3b8">
          <td class="task-name-cell" colspan="4">
            🏗️ PHASE 1 — W 5.2 + W 5.1 + T 5.1 · 4.OG · 364.98 m²
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> W 5.2 — Penthouse (236.50 m²) Phase 1 — nur Kleinigkeiten
            <span class="progress-pill">0/3 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="haustechnik" data-unit="W_5_2" data-task-type="other" data-tid="W_5_2-kleinigkeiten-1">
          <td class="task-name-cell">W 5.2 · Kleinigkeiten / Restarbeiten</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="haustechnik" data-unit="W_5_2" data-task-type="other" data-tid="W_5_2-kleinigkeiten-2">
          <td class="task-name-cell">W 5.2 · Malerarbeiten Nachbesserung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#ea580c;border:1px solid #ea580c40">Maler</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Endmontage" data-phase="haustechnik" data-unit="W_5_2" data-task-type="other" data-tid="W_5_2-kleinigkeiten-3">
          <td class="task-name-cell">W 5.2 · Endmontage / Abnahme</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Endmontage</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
                <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> W 5.1 — 2-Zimmer (74.44 m²) Phase 1 — nur Kleinigkeiten
            <span class="progress-pill">0/2 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="haustechnik" data-unit="W_5_1" data-task-type="other" data-tid="W_5_1-kleinigkeiten-1">
          <td class="task-name-cell">W 5.1 · Kleinigkeiten / Restarbeiten</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Endmontage" data-phase="haustechnik" data-unit="W_5_1" data-task-type="other" data-tid="W_5_1-kleinigkeiten-2">
          <td class="task-name-cell">W 5.1 · Endmontage / Abnahme</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Endmontage</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 5.1 — Studio (54.04 m²) Phase 1 (Wellnessbereich)
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Abbruch" data-phase="haustechnik" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-abbruch">
          <td class="task-name-cell">T 5.1 · Abbrucharbeiten</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Abbruch</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="haustechnik" data-unit="T_5_1" data-task-type="innen" data-tid="T_5_1-trockenbau">
          <td class="task-name-cell">T 5.1 · Trockenbau / Innenwände</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="haustechnik" data-unit="T_5_1" data-task-type="elektro" data-tid="T_5_1-elektro">
          <td class="task-name-cell">T 5.1 · Elektroinstallation</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#d97706;border:1px solid #d9770640">Elektro</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="haustechnik" data-unit="T_5_1" data-task-type="fbhz" data-tid="T_5_1-fbhz">
          <td class="task-name-cell">T 5.1 · FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fecaca;color:#dc2626;border:1px solid #dc262640">Heizung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="haustechnik" data-unit="T_5_1" data-task-type="estrich" data-tid="T_5_1-estrich">
          <td class="task-name-cell">T 5.1 · Estrich</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e9d5ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="haustechnik" data-unit="T_5_1" data-task-type="brandschutz" data-tid="T_5_1-brandschutz">
          <td class="task-name-cell">T 5.1 · Brandschutz</td>
          <td><span class="status-badge status-done">✓</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fliesen" data-phase="haustechnik" data-unit="T_5_1" data-task-type="fliesen" data-tid="T_5_1-fliesen">
          <td class="task-name-cell">T 5.1 · Bad-Fliesen</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ccfbf1;color:#0f766e;border:1px solid #0f766e40">Fliesen</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär" data-phase="haustechnik" data-unit="T_5_1" data-task-type="sanitaer" data-tid="T_5_1-sanitaer-end">
          <td class="task-name-cell">T 5.1 · Sanitär Endmontage</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="haustechnik" data-unit="T_5_1" data-task-type="maler" data-tid="T_5_1-maler">
          <td class="task-name-cell">T 5.1 · Streichen / Malerarbeiten</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#ea580c;border:1px solid #ea580c40">Maler</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="haustechnik" data-unit="T_5_1" data-task-type="boden" data-tid="T_5_1-boden">
          <td class="task-name-cell">T 5.1 · Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fed7aa;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_5_1" data-task-type="kueche" data-tid="T_5_1-kueche">
          <td class="task-name-cell">T 5.1 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_5_1" data-task-type="moeblierung" data-tid="T_5_1-moebel">
          <td class="task-name-cell">T 5.1 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Gutachter" data-phase="haustechnik" data-unit="T_5_1" data-task-type="blowerdoor" data-tid="T_5_1-blowerdoor-1">
          <td class="task-name-cell">Blowerdoor-Test + Abnahme</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#1d4ed8;border:1px solid #1d4ed840">Gutachter</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" style="left:882px;width:126px" title="Blowerdoor-Test"></div></div></td>
        </tr>
        <tr class="kfw-header-row" style="background:#16a34a">
          <td class="task-name-cell" colspan="4">
            🏗️ PHASE 2 — T 4.01 – T 4.08 · 3.OG · 397.99 m² · Estrich KW28
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.01 — 3-Zimmer (72.35 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_01" data-task-type="innen" data-tid="T_4_01-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_01" data-task-type="elektro" data-tid="T_4_01-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_01" data-task-type="sanitaer" data-tid="T_4_01-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_01" data-task-type="fbhz" data-tid="T_4_01-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_01" data-task-type="estrich" data-tid="T_4_01-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_01" data-task-type="brandschutz" data-tid="T_4_01-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_01" data-task-type="decke" data-tid="T_4_01-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="492" data-base-width="42" style="left:1476px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_01" data-task-type="maler" data-tid="T_4_01-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="534" data-base-width="84" style="left:1602px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_01" data-task-type="boden" data-tid="T_4_01-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="618" data-base-width="30" style="left:1854px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_01" data-task-type="tueren" data-tid="T_4_01-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="618" data-base-width="42" style="left:1854px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_01" data-task-type="endmontage" data-tid="T_4_01-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="660" data-base-width="42" style="left:1980px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_01" data-task-type="kueche" data-tid="T_4_01-kueche">
          <td class="task-name-cell">T 4.01 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_01" data-task-type="moeblierung" data-tid="T_4_01-moebel">
          <td class="task-name-cell">T 4.01 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.02 — 2-Zimmer (60.20 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_02" data-task-type="innen" data-tid="T_4_02-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_02" data-task-type="elektro" data-tid="T_4_02-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="6" style="left:0px;width:18px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_02" data-task-type="sanitaer" data-tid="T_4_02-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="0" data-base-width="9" style="left:0px;width:27px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_02" data-task-type="fbhz" data-tid="T_4_02-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="0" data-base-width="30" style="left:0px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_02" data-task-type="estrich" data-tid="T_4_02-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_02" data-task-type="brandschutz" data-tid="T_4_02-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_02" data-task-type="decke" data-tid="T_4_02-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="507" data-base-width="42" style="left:1521px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_02" data-task-type="maler" data-tid="T_4_02-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="549" data-base-width="84" style="left:1647px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_02" data-task-type="boden" data-tid="T_4_02-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="633" data-base-width="30" style="left:1899px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_02" data-task-type="tueren" data-tid="T_4_02-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="633" data-base-width="42" style="left:1899px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_02" data-task-type="endmontage" data-tid="T_4_02-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="675" data-base-width="42" style="left:2025px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_02" data-task-type="kueche" data-tid="T_4_02-kueche">
          <td class="task-name-cell">T 4.02 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_02" data-task-type="moeblierung" data-tid="T_4_02-moebel">
          <td class="task-name-cell">T 4.02 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.03 — 2-Zimmer (54.79 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_03" data-task-type="innen" data-tid="T_4_03-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="18" style="left:0px;width:54px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_03" data-task-type="elektro" data-tid="T_4_03-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="0" data-base-width="18" style="left:0px;width:54px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_03" data-task-type="sanitaer" data-tid="T_4_03-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="0" data-base-width="39" style="left:0px;width:117px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_03" data-task-type="fbhz" data-tid="T_4_03-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="18" data-base-width="42" style="left:54px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_03" data-task-type="estrich" data-tid="T_4_03-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_03" data-task-type="brandschutz" data-tid="T_4_03-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_03" data-task-type="decke" data-tid="T_4_03-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="522" data-base-width="42" style="left:1566px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_03" data-task-type="maler" data-tid="T_4_03-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="564" data-base-width="84" style="left:1692px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_03" data-task-type="boden" data-tid="T_4_03-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="648" data-base-width="30" style="left:1944px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_03" data-task-type="tueren" data-tid="T_4_03-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="648" data-base-width="42" style="left:1944px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_03" data-task-type="endmontage" data-tid="T_4_03-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="690" data-base-width="42" style="left:2070px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_03" data-task-type="kueche" data-tid="T_4_03-kueche">
          <td class="task-name-cell">T 4.03 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_03" data-task-type="moeblierung" data-tid="T_4_03-moebel">
          <td class="task-name-cell">T 4.03 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.04 — 2-Zimmer (54.79 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_04" data-task-type="innen" data-tid="T_4_04-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="6" data-base-width="42" style="left:18px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_04" data-task-type="elektro" data-tid="T_4_04-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="6" data-base-width="42" style="left:18px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_04" data-task-type="sanitaer" data-tid="T_4_04-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="27" data-base-width="42" style="left:81px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_04" data-task-type="fbhz" data-tid="T_4_04-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="48" data-base-width="42" style="left:144px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_04" data-task-type="estrich" data-tid="T_4_04-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_04" data-task-type="brandschutz" data-tid="T_4_04-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_04" data-task-type="decke" data-tid="T_4_04-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="537" data-base-width="42" style="left:1611px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_04" data-task-type="maler" data-tid="T_4_04-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="579" data-base-width="84" style="left:1737px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_04" data-task-type="boden" data-tid="T_4_04-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="663" data-base-width="30" style="left:1989px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_04" data-task-type="tueren" data-tid="T_4_04-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="663" data-base-width="42" style="left:1989px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_04" data-task-type="endmontage" data-tid="T_4_04-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="705" data-base-width="42" style="left:2115px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_04" data-task-type="kueche" data-tid="T_4_04-kueche">
          <td class="task-name-cell">T 4.04 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_04" data-task-type="moeblierung" data-tid="T_4_04-moebel">
          <td class="task-name-cell">T 4.04 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.05 — Studio (31.55 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_05" data-task-type="innen" data-tid="T_4_05-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="36" data-base-width="30" style="left:108px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_05" data-task-type="elektro" data-tid="T_4_05-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="36" data-base-width="42" style="left:108px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_05" data-task-type="sanitaer" data-tid="T_4_05-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="57" data-base-width="42" style="left:171px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_05" data-task-type="fbhz" data-tid="T_4_05-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="78" data-base-width="42" style="left:234px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_05" data-task-type="estrich" data-tid="T_4_05-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_05" data-task-type="brandschutz" data-tid="T_4_05-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_05" data-task-type="decke" data-tid="T_4_05-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="552" data-base-width="42" style="left:1656px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_05" data-task-type="maler" data-tid="T_4_05-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="594" data-base-width="42" style="left:1782px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_05" data-task-type="boden" data-tid="T_4_05-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="636" data-base-width="30" style="left:1908px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_05" data-task-type="tueren" data-tid="T_4_05-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="636" data-base-width="42" style="left:1908px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_05" data-task-type="endmontage" data-tid="T_4_05-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="678" data-base-width="42" style="left:2034px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_05" data-task-type="kueche" data-tid="T_4_05-kueche">
          <td class="task-name-cell">T 4.05 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_05" data-task-type="moeblierung" data-tid="T_4_05-moebel">
          <td class="task-name-cell">T 4.05 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.06 — Studio (31.62 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_06" data-task-type="innen" data-tid="T_4_06-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="66" data-base-width="30" style="left:198px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_06" data-task-type="elektro" data-tid="T_4_06-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="66" data-base-width="42" style="left:198px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_06" data-task-type="sanitaer" data-tid="T_4_06-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="87" data-base-width="42" style="left:261px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_06" data-task-type="fbhz" data-tid="T_4_06-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="108" data-base-width="42" style="left:324px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_06" data-task-type="estrich" data-tid="T_4_06-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_06" data-task-type="brandschutz" data-tid="T_4_06-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_06" data-task-type="decke" data-tid="T_4_06-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="567" data-base-width="42" style="left:1701px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_06" data-task-type="maler" data-tid="T_4_06-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="609" data-base-width="42" style="left:1827px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_06" data-task-type="boden" data-tid="T_4_06-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="651" data-base-width="30" style="left:1953px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_06" data-task-type="tueren" data-tid="T_4_06-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="651" data-base-width="42" style="left:1953px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_06" data-task-type="endmontage" data-tid="T_4_06-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="693" data-base-width="42" style="left:2079px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_06" data-task-type="kueche" data-tid="T_4_06-kueche">
          <td class="task-name-cell">T 4.06 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_06" data-task-type="moeblierung" data-tid="T_4_06-moebel">
          <td class="task-name-cell">T 4.06 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.07 — Studio (31.67 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_07" data-task-type="innen" data-tid="T_4_07-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="96" data-base-width="30" style="left:288px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_07" data-task-type="elektro" data-tid="T_4_07-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="96" data-base-width="42" style="left:288px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_07" data-task-type="sanitaer" data-tid="T_4_07-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="117" data-base-width="42" style="left:351px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_07" data-task-type="fbhz" data-tid="T_4_07-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="138" data-base-width="42" style="left:414px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_07" data-task-type="estrich" data-tid="T_4_07-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_07" data-task-type="brandschutz" data-tid="T_4_07-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_07" data-task-type="decke" data-tid="T_4_07-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="582" data-base-width="42" style="left:1746px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_07" data-task-type="maler" data-tid="T_4_07-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="624" data-base-width="42" style="left:1872px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_07" data-task-type="boden" data-tid="T_4_07-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="666" data-base-width="30" style="left:1998px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_07" data-task-type="tueren" data-tid="T_4_07-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="666" data-base-width="42" style="left:1998px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_07" data-task-type="endmontage" data-tid="T_4_07-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="708" data-base-width="42" style="left:2124px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_07" data-task-type="kueche" data-tid="T_4_07-kueche">
          <td class="task-name-cell">T 4.07 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_07" data-task-type="moeblierung" data-tid="T_4_07-moebel">
          <td class="task-name-cell">T 4.07 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.08 — 2-Zimmer (61.02 m²) Phase 2
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_08" data-task-type="innen" data-tid="T_4_08-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="126" data-base-width="42" style="left:378px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_08" data-task-type="elektro" data-tid="T_4_08-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="126" data-base-width="42" style="left:378px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_08" data-task-type="sanitaer" data-tid="T_4_08-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="147" data-base-width="42" style="left:441px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_08" data-task-type="fbhz" data-tid="T_4_08-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="168" data-base-width="42" style="left:504px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_08" data-task-type="estrich" data-tid="T_4_08-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_08" data-task-type="brandschutz" data-tid="T_4_08-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_08" data-task-type="decke" data-tid="T_4_08-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="597" data-base-width="42" style="left:1791px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_08" data-task-type="maler" data-tid="T_4_08-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="639" data-base-width="84" style="left:1917px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_08" data-task-type="boden" data-tid="T_4_08-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="723" data-base-width="30" style="left:2169px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_08" data-task-type="tueren" data-tid="T_4_08-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="723" data-base-width="42" style="left:2169px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_08" data-task-type="endmontage" data-tid="T_4_08-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="765" data-base-width="42" style="left:2295px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_08" data-task-type="kueche" data-tid="T_4_08-kueche">
          <td class="task-name-cell">T 4.08 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_08" data-task-type="moeblierung" data-tid="T_4_08-moebel">
          <td class="task-name-cell">T 4.08 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
                <tr class="kfw-header-row" style="background:#d97706">
          <td class="task-name-cell" colspan="4">
            🏗️ PHASE 3 — T 4.22 → T 4.09 (Maisonette) · 3.OG · 627.91 m² · Estrich KW33
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.22 — Büro (102.73 m²) Phase 3 — Praxis-Einheit
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_22" data-task-type="innen" data-tid="T_4_22-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="324" data-base-width="42" style="left:972px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_22" data-task-type="elektro" data-tid="T_4_22-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="324" data-base-width="42" style="left:972px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_22" data-task-type="sanitaer" data-tid="T_4_22-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="345" data-base-width="42" style="left:1035px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_22" data-task-type="fbhz" data-tid="T_4_22-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="366" data-base-width="42" style="left:1098px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_22" data-task-type="estrich" data-tid="T_4_22-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_22" data-task-type="brandschutz" data-tid="T_4_22-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_22" data-task-type="decke" data-tid="T_4_22-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="780" data-base-width="42" style="left:2340px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_22" data-task-type="maler" data-tid="T_4_22-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="822" data-base-width="84" style="left:2466px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_22" data-task-type="boden" data-tid="T_4_22-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="906" data-base-width="30" style="left:2718px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_22" data-task-type="tueren" data-tid="T_4_22-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="906" data-base-width="42" style="left:2718px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_22" data-task-type="endmontage" data-tid="T_4_22-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="948" data-base-width="42" style="left:2844px;width:126px"></div></div></td>
        </tr>
                <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.20 — Studio (31.63 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_20" data-task-type="innen" data-tid="T_4_20-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="294" data-base-width="30" style="left:882px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_20" data-task-type="elektro" data-tid="T_4_20-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="294" data-base-width="42" style="left:882px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_20" data-task-type="sanitaer" data-tid="T_4_20-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="315" data-base-width="42" style="left:945px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_20" data-task-type="fbhz" data-tid="T_4_20-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="336" data-base-width="42" style="left:1008px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_20" data-task-type="estrich" data-tid="T_4_20-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_20" data-task-type="brandschutz" data-tid="T_4_20-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_20" data-task-type="decke" data-tid="T_4_20-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="765" data-base-width="42" style="left:2295px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_20" data-task-type="maler" data-tid="T_4_20-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="807" data-base-width="42" style="left:2421px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_20" data-task-type="boden" data-tid="T_4_20-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="849" data-base-width="30" style="left:2547px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_20" data-task-type="tueren" data-tid="T_4_20-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="849" data-base-width="42" style="left:2547px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_20" data-task-type="endmontage" data-tid="T_4_20-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="891" data-base-width="42" style="left:2673px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_20" data-task-type="kueche" data-tid="T_4_20-kueche">
          <td class="task-name-cell">T 4.20 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_20" data-task-type="moeblierung" data-tid="T_4_20-moebel">
          <td class="task-name-cell">T 4.20 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.18 — Studio (31.55 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_18" data-task-type="innen" data-tid="T_4_18-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="264" data-base-width="30" style="left:792px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_18" data-task-type="elektro" data-tid="T_4_18-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="264" data-base-width="42" style="left:792px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_18" data-task-type="sanitaer" data-tid="T_4_18-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="285" data-base-width="42" style="left:855px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_18" data-task-type="fbhz" data-tid="T_4_18-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="306" data-base-width="42" style="left:918px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_18" data-task-type="estrich" data-tid="T_4_18-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_18" data-task-type="brandschutz" data-tid="T_4_18-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_18" data-task-type="decke" data-tid="T_4_18-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="750" data-base-width="42" style="left:2250px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_18" data-task-type="maler" data-tid="T_4_18-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="792" data-base-width="42" style="left:2376px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_18" data-task-type="boden" data-tid="T_4_18-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="834" data-base-width="30" style="left:2502px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_18" data-task-type="tueren" data-tid="T_4_18-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="834" data-base-width="42" style="left:2502px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_18" data-task-type="endmontage" data-tid="T_4_18-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="876" data-base-width="42" style="left:2628px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_18" data-task-type="kueche" data-tid="T_4_18-kueche">
          <td class="task-name-cell">T 4.18 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_18" data-task-type="moeblierung" data-tid="T_4_18-moebel">
          <td class="task-name-cell">T 4.18 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.16 — Studio (31.60 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_16" data-task-type="innen" data-tid="T_4_16-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="234" data-base-width="30" style="left:702px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_16" data-task-type="elektro" data-tid="T_4_16-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="234" data-base-width="42" style="left:702px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_16" data-task-type="sanitaer" data-tid="T_4_16-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="255" data-base-width="42" style="left:765px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_16" data-task-type="fbhz" data-tid="T_4_16-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="276" data-base-width="42" style="left:828px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_16" data-task-type="estrich" data-tid="T_4_16-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_16" data-task-type="brandschutz" data-tid="T_4_16-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_16" data-task-type="decke" data-tid="T_4_16-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="735" data-base-width="42" style="left:2205px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_16" data-task-type="maler" data-tid="T_4_16-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="777" data-base-width="42" style="left:2331px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_16" data-task-type="boden" data-tid="T_4_16-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="819" data-base-width="30" style="left:2457px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_16" data-task-type="tueren" data-tid="T_4_16-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="819" data-base-width="42" style="left:2457px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_16" data-task-type="endmontage" data-tid="T_4_16-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="861" data-base-width="42" style="left:2583px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_16" data-task-type="kueche" data-tid="T_4_16-kueche">
          <td class="task-name-cell">T 4.16 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_16" data-task-type="moeblierung" data-tid="T_4_16-moebel">
          <td class="task-name-cell">T 4.16 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.14 — Studio (31.57 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_14" data-task-type="innen" data-tid="T_4_14-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="204" data-base-width="30" style="left:612px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_14" data-task-type="elektro" data-tid="T_4_14-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="204" data-base-width="42" style="left:612px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_14" data-task-type="sanitaer" data-tid="T_4_14-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="225" data-base-width="42" style="left:675px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_14" data-task-type="fbhz" data-tid="T_4_14-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="246" data-base-width="42" style="left:738px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_14" data-task-type="estrich" data-tid="T_4_14-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_14" data-task-type="brandschutz" data-tid="T_4_14-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_14" data-task-type="decke" data-tid="T_4_14-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="720" data-base-width="42" style="left:2160px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_14" data-task-type="maler" data-tid="T_4_14-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="762" data-base-width="42" style="left:2286px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_14" data-task-type="boden" data-tid="T_4_14-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="804" data-base-width="30" style="left:2412px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_14" data-task-type="tueren" data-tid="T_4_14-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="804" data-base-width="42" style="left:2412px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_14" data-task-type="endmontage" data-tid="T_4_14-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="846" data-base-width="42" style="left:2538px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_14" data-task-type="kueche" data-tid="T_4_14-kueche">
          <td class="task-name-cell">T 4.14 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_14" data-task-type="moeblierung" data-tid="T_4_14-moebel">
          <td class="task-name-cell">T 4.14 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.13 — Studio (32.73 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_13" data-task-type="innen" data-tid="T_4_13-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="174" data-base-width="30" style="left:522px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_13" data-task-type="elektro" data-tid="T_4_13-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="174" data-base-width="42" style="left:522px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_13" data-task-type="sanitaer" data-tid="T_4_13-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="195" data-base-width="42" style="left:585px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_13" data-task-type="fbhz" data-tid="T_4_13-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="216" data-base-width="42" style="left:648px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_13" data-task-type="estrich" data-tid="T_4_13-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_13" data-task-type="brandschutz" data-tid="T_4_13-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_13" data-task-type="decke" data-tid="T_4_13-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="705" data-base-width="42" style="left:2115px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_13" data-task-type="maler" data-tid="T_4_13-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="747" data-base-width="42" style="left:2241px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_13" data-task-type="boden" data-tid="T_4_13-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="789" data-base-width="30" style="left:2367px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_13" data-task-type="tueren" data-tid="T_4_13-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="789" data-base-width="42" style="left:2367px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_13" data-task-type="endmontage" data-tid="T_4_13-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="831" data-base-width="42" style="left:2493px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_13" data-task-type="kueche" data-tid="T_4_13-kueche">
          <td class="task-name-cell">T 4.13 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_13" data-task-type="moeblierung" data-tid="T_4_13-moebel">
          <td class="task-name-cell">T 4.13 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.12 — Studio (31.18 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_12" data-task-type="innen" data-tid="T_4_12-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="144" data-base-width="30" style="left:432px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_12" data-task-type="elektro" data-tid="T_4_12-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="144" data-base-width="42" style="left:432px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_12" data-task-type="sanitaer" data-tid="T_4_12-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="165" data-base-width="42" style="left:495px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_12" data-task-type="fbhz" data-tid="T_4_12-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="186" data-base-width="42" style="left:558px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_12" data-task-type="estrich" data-tid="T_4_12-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_12" data-task-type="brandschutz" data-tid="T_4_12-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_12" data-task-type="decke" data-tid="T_4_12-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="690" data-base-width="42" style="left:2070px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_12" data-task-type="maler" data-tid="T_4_12-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="732" data-base-width="42" style="left:2196px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_12" data-task-type="boden" data-tid="T_4_12-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="774" data-base-width="30" style="left:2322px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_12" data-task-type="tueren" data-tid="T_4_12-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="774" data-base-width="42" style="left:2322px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_12" data-task-type="endmontage" data-tid="T_4_12-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="816" data-base-width="42" style="left:2448px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_12" data-task-type="kueche" data-tid="T_4_12-kueche">
          <td class="task-name-cell">T 4.12 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_12" data-task-type="moeblierung" data-tid="T_4_12-moebel">
          <td class="task-name-cell">T 4.12 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.11 — 2-Zimmer (61.01 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_11" data-task-type="innen" data-tid="T_4_11-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="114" data-base-width="42" style="left:342px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_11" data-task-type="elektro" data-tid="T_4_11-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="114" data-base-width="42" style="left:342px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_11" data-task-type="sanitaer" data-tid="T_4_11-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="135" data-base-width="42" style="left:405px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_11" data-task-type="fbhz" data-tid="T_4_11-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="156" data-base-width="42" style="left:468px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_11" data-task-type="estrich" data-tid="T_4_11-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_11" data-task-type="brandschutz" data-tid="T_4_11-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_11" data-task-type="decke" data-tid="T_4_11-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="675" data-base-width="42" style="left:2025px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_11" data-task-type="maler" data-tid="T_4_11-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="717" data-base-width="84" style="left:2151px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_11" data-task-type="boden" data-tid="T_4_11-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="801" data-base-width="30" style="left:2403px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_11" data-task-type="tueren" data-tid="T_4_11-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="801" data-base-width="42" style="left:2403px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_11" data-task-type="endmontage" data-tid="T_4_11-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="843" data-base-width="42" style="left:2529px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_11" data-task-type="kueche" data-tid="T_4_11-kueche">
          <td class="task-name-cell">T 4.11 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_11" data-task-type="moeblierung" data-tid="T_4_11-moebel">
          <td class="task-name-cell">T 4.11 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.10 — Studio (31.98 m²) Phase 3
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_10" data-task-type="innen" data-tid="T_4_10-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="84" data-base-width="30" style="left:252px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="T_4_10" data-task-type="elektro" data-tid="T_4_10-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="84" data-base-width="42" style="left:252px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_4_10" data-task-type="sanitaer" data-tid="T_4_10-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="105" data-base-width="42" style="left:315px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_4_10" data-task-type="fbhz" data-tid="T_4_10-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="126" data-base-width="42" style="left:378px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_4_10" data-task-type="estrich" data-tid="T_4_10-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="252" style="left:1224px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="T_4_10" data-task-type="brandschutz" data-tid="T_4_10-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="408" data-base-width="30" style="left:1224px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_4_10" data-task-type="decke" data-tid="T_4_10-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="660" data-base-width="42" style="left:1980px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_4_10" data-task-type="maler" data-tid="T_4_10-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="702" data-base-width="42" style="left:2106px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="T_4_10" data-task-type="boden" data-tid="T_4_10-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="744" data-base-width="30" style="left:2232px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_4_10" data-task-type="tueren" data-tid="T_4_10-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="744" data-base-width="42" style="left:2232px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="T_4_10" data-task-type="endmontage" data-tid="T_4_10-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="786" data-base-width="42" style="left:2358px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_10" data-task-type="kueche" data-tid="T_4_10-kueche">
          <td class="task-name-cell">T 4.10 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_10" data-task-type="moeblierung" data-tid="T_4_10-moebel">
          <td class="task-name-cell">T 4.10 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> T 4.09 — Maisonette (177.01 m²) Phase 3/4 — +196.45 m² 4.OG
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_09" data-task-type="innen" data-tid="T_4_09-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="156" data-base-width="42" style="left:468px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph1b" data-unit="T_4_09" data-task-type="elektro" data-tid="T_4_09-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="156" data-base-width="42" style="left:468px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph1b" data-unit="T_4_09" data-task-type="sanitaer" data-tid="T_4_09-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="177" data-base-width="42" style="left:531px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph1b" data-unit="T_4_09" data-task-type="fbhz" data-tid="T_4_09-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="198" data-base-width="42" style="left:594px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph1b" data-unit="T_4_09" data-task-type="estrich" data-tid="T_4_09-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="252" style="left:720px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph1b" data-unit="T_4_09" data-task-type="brandschutz" data-tid="T_4_09-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="240" data-base-width="30" style="left:720px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph1b" data-unit="T_4_09" data-task-type="decke" data-tid="T_4_09-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="612" data-base-width="42" style="left:1836px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph1b" data-unit="T_4_09" data-task-type="maler" data-tid="T_4_09-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="654" data-base-width="84" style="left:1962px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph1b" data-unit="T_4_09" data-task-type="boden" data-tid="T_4_09-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="738" data-base-width="30" style="left:2214px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph1b" data-unit="T_4_09" data-task-type="tueren" data-tid="T_4_09-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="738" data-base-width="42" style="left:2214px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph1b" data-unit="T_4_09" data-task-type="endmontage" data-tid="T_4_09-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="780" data-base-width="42" style="left:2340px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Schreiner" data-phase="haustechnik" data-unit="T_4_09" data-task-type="kueche" data-tid="T_4_09-kueche">
          <td class="task-name-cell">T 4.09 · Kücheneinbau</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Möblierung" data-phase="haustechnik" data-unit="T_4_09" data-task-type="moeblierung" data-tid="T_4_09-moebel">
          <td class="task-name-cell">T 4.09 · Möblierung</td>
          <td><span class="status-badge status-planned">—</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Möblierung</span></td>
          <td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>

        
                        <tr class="kfw-header-row" style="background:#7c3aed">
          <td class="task-name-cell" colspan="4">
            🏗️ PHASE 4 — W 5.3 · 4.OG · 288.14 m² (Penthouse)
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
<tr class="section-row">

          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> W 5.3 — Penthouse (288.14 m²) Phase 4 — 4.OG (Penthouse)
            <span class="progress-pill">0/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="W_5_3" data-task-type="innen" data-tid="W_5_3-innen-1">

          <td class="task-name-cell">Innenwände 2. Beplankung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="84" data-base-width="42" style="left:252px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Elektro" data-phase="ph2" data-unit="W_5_3" data-task-type="elektro" data-tid="W_5_3-elektro-1">

          <td class="task-name-cell">Elektroinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#d97706;border:1px solid #d9770640">Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-wip" data-base-left="84" data-base-width="42" style="left:252px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="W_5_3" data-task-type="sanitaer" data-tid="W_5_3-sanitaer-1">

          <td class="task-name-cell">Sanitärinstallation 1. Fix</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="105" data-base-width="42" style="left:315px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="W_5_3" data-task-type="fbhz" data-tid="W_5_3-fbhz-1">

          <td class="task-name-cell">FBHZ Rohrleitungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="126" data-base-width="42" style="left:378px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="W_5_3" data-task-type="estrich" data-tid="W_5_3-estrich-1">

          <td class="task-name-cell">Estrich (inkl. Trocknung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">Chini</td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="168" data-base-width="252" style="left:504px;width:756px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Brandschutz" data-phase="ph2" data-unit="W_5_3" data-task-type="brandschutz" data-tid="W_5_3-brandschutz-1">

          <td class="task-name-cell">Brandschutz / Abschottungen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#991b1b;border:1px solid #991b1b40">Brandschutz</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="168" data-base-width="30" style="left:504px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="W_5_3" data-task-type="decke" data-tid="W_5_3-decke-1">

          <td class="task-name-cell">Abhangdecken / Unterdecken</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="336" data-base-width="42" style="left:1008px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="W_5_3" data-task-type="maler" data-tid="W_5_3-maler-1">

          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="378" data-base-width="84" style="left:1134px;width:252px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Bodenbelag" data-phase="ph2" data-unit="W_5_3" data-task-type="boden" data-tid="W_5_3-boden-1">

          <td class="task-name-cell">Bodenbelag Vinyl</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fef3c7;color:#92400e;border:1px solid #92400e40">Bodenbelag</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="462" data-base-width="30" style="left:1386px;width:90px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="W_5_3" data-task-type="tueren" data-tid="W_5_3-tueren-1">

          <td class="task-name-cell">Türen montieren</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="462" data-base-width="42" style="left:1386px;width:126px"></div></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Sanitär / Elektro" data-phase="ph2" data-unit="W_5_3" data-task-type="endmontage" data-tid="W_5_3-endmontage-1">

          <td class="task-name-cell">Sanitär/Elektro Endmontage</td>
          <td><span class="status-badge status-planned">in Planung</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#7c3aed;border:1px solid #7c3aed40">Sanitär / Elektro</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar status-planned" data-base-left="504" data-base-width="42" style="left:1512px;width:126px"></div></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> WELLNESSBEREICH
            <span class="progress-pill">1/11 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Abbruch" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-1">
          
          <td class="task-name-cell">Abbruch</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3f4f6;color:#6b7280;border:1px solid #6b728040">Abbruch</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-2">
          
          <td class="task-name-cell">Trockenbau</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Heizung" data-phase="ph2" data-unit="T_5_1" data-task-type="fbhz" data-tid="T_5_1-fbhz-2">
          
          <td class="task-name-cell">FBHZ</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fee2e2;color:#dc2626;border:1px solid #dc262640">Heizung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Estrich" data-phase="ph2" data-unit="T_5_1" data-task-type="estrich" data-tid="T_5_1-estrich-2">
          
          <td class="task-name-cell">Estrich</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f3e8ff;color:#7c3aed;border:1px solid #7c3aed40">Estrich</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Abdichtung" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-3">
          
          <td class="task-name-cell">Abdichtung</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px solid #64748b40">Abdichtung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Fliesen" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-4">
          
          <td class="task-name-cell">Fliesen</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ccfbf1;color:#0f766e;border:1px solid #0f766e40">Fliesen</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Schreiner" data-phase="ph2" data-unit="T_5_1" data-task-type="tueren" data-tid="T_5_1-tueren-2">
          
          <td class="task-name-cell">Türen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#fde68a;color:#78350f;border:1px solid #78350f40">Schreiner</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Glas / Metall" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-5">
          
          <td class="task-name-cell">Glaswände</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0284c7;border:1px solid #0284c740">Glas / Metall</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="geplant" data-gewerk="Maler" data-phase="ph2" data-unit="T_5_1" data-task-type="maler" data-tid="T_5_1-maler-2">
          
          <td class="task-name-cell">Malerarbeiten</td>
          <td><span class="status-badge status-planned">—</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ffedd5;color:#ea580c;border:1px solid #ea580c40">Maler</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-6">
          
          <td class="task-name-cell">…</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="section-row">
          
          <td class="section-name" colspan="4">
            <span class="section-arrow">▶</span> HAUPTWERK
            <span class="progress-pill">2/12 ✓</span>
          </td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Abdichtung" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-7">
          
          <td class="task-name-cell">Abdichtung Heizmittelraum</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px solid #64748b40">Abdichtung</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Fenster" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-8">
          
          <td class="task-name-cell">Lamellenfenster / RWA (Treppenhaus)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#e0f2fe;color:#0369a1;border:1px solid #0369a140">Fenster</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-9">
          
          <td class="task-name-cell">Büroumzug (Planung)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-10">
          
          <td class="task-name-cell">Büroumzug</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-11">
          
          <td class="task-name-cell">Ausbau alter Bürostandort (Höhenrestaurant, Physio, o.ä.?)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Trockenbau" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-12">
          
          <td class="task-name-cell">ICF-Kidsräume fertigstellen (inkl. Heizung, Trockenbau,…)</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#ede9fe;color:#6366f1;border:1px solid #6366f140">Trockenbau</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-13">
          
          <td class="task-name-cell">Sanitärbereiche 2. OG</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="Sanitär" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-14">
          
          <td class="task-name-cell">Sanitärbereiche 3. OG</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"><span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#dbeafe;color:#2563eb;border:1px solid #2563eb40">Sanitär</span></td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-15">
          
          <td class="task-name-cell">Lager Susi / Upjoy fertigstellen</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
        <tr class="task-row" data-status="abgeschlossen" data-gewerk="" data-phase="ph2" data-unit="T_5_1" data-task-type="other" data-tid="T_5_1-other-16">
          
          <td class="task-name-cell">28.12</td>
          <td><span class="status-badge status-done">✓</span>
              </td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">—</td><td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px"></td>
          <td><div class="gantt-row-inner" style="width:10800px"></div></td>
        </tr>
</tbody>
</table>
</div>
</div>

<!-- ── GEBÄUDEHÜLLE ── -->


<!-- ── TODs ── -->
<div id="tab-todos" class="tab-content">
<div class="todos-wrap" style="padding:20px 24px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
      <h2 style="font-size:16px;font-weight:700;color:#1e293b;margin:0">
        📋 TOD-Liste — KW21 bis KW30
      </h2>
      <p style="font-size:11px;color:#64748b;margin:4px 0 0">
        26 Aufgaben aus Gantt · Manuelle TODs werden im Browser gespeichert
      </p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="exportTodos()" style="padding:5px 12px;border-radius:6px;border:1px solid #e2e8f0;
        background:#f8fafc;font-size:11px;cursor:pointer;color:#374151">
        📤 TODs exportieren
      </button>
    </div>
  </div>

  <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div class="kw-card" data-kw="21" style="
          background:#eff6ff;border:1.5px solid #2563eb;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 21</span>
            <span style="background:#2563eb;color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:10px;margin-left:8px">📍 Aktuelle Woche</span>
            <div style="font-size:10px;color:#64748b;margin-top:1px">18. May – 24. May 2026</div>
          </div>
          <span style="font-size:18px">🔨</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Hausanschluss, Abwasser / Regenwasser (Entwässerungskonzept)</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 28. Sep</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #d97706"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Batteriespeicher</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 12. Oct</span><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Elektro</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #94a3b8"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Installation</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 28. Sep</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #d97706"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Elektroinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 1. Jun</span><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Elektro</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #6366f1"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Innenwände 2. Beplankung</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 1. Jun</span><span style="background:#6366f118;color:#6366f1;border:1px solid #6366f140;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Trockenbau</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw21" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="21" onblur="saveTodo(this)" placeholder="TODs für KW21 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="22" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 22</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">25. May – 31. May 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <p style="font-size:11px;color:#94a3b8;margin:0 0 10px">— keine Aufgaben startend —</p>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw22" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="22" onblur="saveTodo(this)" placeholder="TODs für KW22 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="23" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 23</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">1. Jun – 7. Jun 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #d97706"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">PV-Anlage</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 1. Jun</span><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Elektro</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 1. Jun</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 1. Jun</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw23" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="23" onblur="saveTodo(this)" placeholder="TODs für KW23 hier eingeben…"></div>
      </div></div><div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div class="kw-card" data-kw="24" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 24</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">8. Jun – 14. Jun 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 15. Jun</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 15. Jun</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw24" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="24" onblur="saveTodo(this)" placeholder="TODs für KW24 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="25" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 25</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">15. Jun – 21. Jun 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 22. Jun</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 22. Jun</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw25" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="25" onblur="saveTodo(this)" placeholder="TODs für KW25 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="26" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 26</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">22. Jun – 28. Jun 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 29. Jun</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 29. Jun</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw26" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="26" onblur="saveTodo(this)" placeholder="TODs für KW26 hier eingeben…"></div>
      </div></div><div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div class="kw-card" data-kw="27" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 27</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">29. Jun – 5. Jul 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 6. Jul</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 6. Jul</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #7c3aed"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Estrich (inkl. Trocknung)</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 10. Aug</span><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Estrich</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #991b1b"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Brandschutz / Abschottungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 29. Jun</span><span style="background:#991b1b18;color:#991b1b;border:1px solid #991b1b40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Brandschutz</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw27" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="27" onblur="saveTodo(this)" placeholder="TODs für KW27 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="28" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 28</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">6. Jul – 12. Jul 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #7c3aed"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Estrich (inkl. Trocknung)</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 17. Aug</span><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Estrich</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #991b1b"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Brandschutz / Abschottungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 13. Jul</span><span style="background:#991b1b18;color:#991b1b;border:1px solid #991b1b40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Brandschutz</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 13. Jul</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 13. Jul</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw28" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="28" onblur="saveTodo(this)" placeholder="TODs für KW28 hier eingeben…"></div>
      </div>
      <div class="kw-card" data-kw="29" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 29</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">13. Jul – 19. Jul 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 20. Jul</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 20. Jul</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw29" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="29" onblur="saveTodo(this)" placeholder="TODs für KW29 hier eingeben…"></div>
      </div></div><div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div class="kw-card" data-kw="30" style="
          background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
          padding:14px 16px;display:flex;flex-direction:column;gap:0;
          min-width:280px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:14px;font-weight:700;color:#1e293b">KW 30</span>
            
            <div style="font-size:10px;color:#64748b;margin-top:1px">20. Jul – 26. Jul 2026</div>
          </div>
          <span style="font-size:18px">📋</span>
        </div>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">📌 Aus Gantt</div><ul style="list-style:none;display:flex;flex-direction:column;gap:4px;margin:0 0 10px;padding:0"><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #dc2626"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">FBHZ Rohrleitungen</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 27. Jul</span><span style="background:#dc262618;color:#dc2626;border:1px solid #dc262640;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Heizung</span></li><li style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:#f8fafc;border-radius:5px;border-left:3px solid #2563eb"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#94a3b8;flex-shrink:0"></span><span style="flex:1;font-size:11px;color:#1e293b">Sanitärinstallation 1. Fix</span><span style="font-size:9px;color:#94a3b8;white-space:nowrap">bis 27. Jul</span><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600;white-space:nowrap">Sanitär</span></li></ul>
        <div style="font-size:10px;font-weight:600;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">✏️ Manuelle TODs</div><div id="manual-kw30" contenteditable="true" style="min-height:52px;padding:6px 8px;border:1px dashed #cbd5e1;border-radius:5px;font-size:11px;color:#374151;line-height:1.6;outline:none;background:#fafafa" data-kw="30" onblur="saveTodo(this)" placeholder="TODs für KW30 hier eingeben…"></div>
      </div></div>

</div>

<script>
(function(){
  // Gespeicherte TODs laden
  document.querySelectorAll('[data-kw][contenteditable]').forEach(function(el){
    var key = 'todo-kw-' + el.dataset.kw;
    var saved = localStorage.getItem(key);
    if (saved) el.innerHTML = saved;
    else if (!el.innerHTML.trim()) el.innerHTML = '';
  });
})();
function saveTodo(el){
  var key = 'todo-kw-' + el.dataset.kw;
  localStorage.setItem(key, el.innerHTML);
  if (window.__syncKV) window.__syncKV(key, el.innerHTML);
}
function clearTodo(kw){
  localStorage.removeItem('todo-kw-' + kw);
  var el = document.getElementById('manual-kw'+kw);
  if(el) el.innerHTML = '';
}
function exportTodos(){
  var out = {};
  document.querySelectorAll('[data-kw][contenteditable]').forEach(function(el){
    var txt = el.innerText.trim();
    if(txt) out['KW'+el.dataset.kw] = txt;
  });
  alert(JSON.stringify(out, null, 2));
}
</script>
</div>

<!-- ── RAUMBUCH ── -->
<div id="tab-wohnungen" class="tab-content">
<div style="padding:20px 24px">



<!-- ── KENNZAHLEN ── -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
  <div style="background:#eff6ff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;flex:1;min-width:130px">
    <div style="font-size:18px">🏠</div>
    <div style="font-size:22px;font-weight:800;color:#2563eb">21</div>
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Wohneinheiten</div>
  </div>
  <div style="background:#fffbeb;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;flex:1;min-width:130px">
    <div style="font-size:18px">🏢</div>
    <div style="font-size:22px;font-weight:800;color:#d97706">1</div>
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Gewerbeeinheiten</div>
  </div>
  <div style="background:#ecfdf5;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;flex:1;min-width:130px">
    <div style="font-size:18px">📐</div>
    <div style="font-size:22px;font-weight:800;color:#059669">1614 m²</div>
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Gesamtfläche</div>
  </div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;flex:1;min-width:130px">
    <div style="font-size:18px">🏠</div>
    <div style="font-size:22px;font-weight:800;color:#2563eb">1511 m²</div>
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Wohnen</div>
  </div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;flex:1;min-width:130px">
    <div style="font-size:18px">🏢</div>
    <div style="font-size:22px;font-weight:800;color:#d97706">103 m²</div>
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Gewerbe</div>
  </div>
  
</div>

<!-- ── PHASEN- / STOCKWERK-AUFTEILUNG ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:24px">

  <div style="background:#fff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px 16px">
    <div style="font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Phase 2 · 3.OG</div>
    <div style="font-size:24px;font-weight:800;color:#16a34a">397.99 m²</div>
    <div style="font-size:11px;color:#64748b;margin-top:2px">8 Einheiten (T 4.01 – T 4.08)</div>
  </div>

  <div style="background:#fff;border:1.5px solid #c4b5fd;border-radius:10px;padding:14px 16px">
    <div style="font-size:11px;font-weight:700;color:#d97706;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Phase 3 · 3.OG</div>
    <div style="font-size:24px;font-weight:800;color:#d97706">385.98 m²</div>
    <div style="font-size:11px;color:#64748b;margin-top:2px">9 Einheiten (T 4.10 – T 4.22) · + T 4.09 Maisonette</div>
  </div>

  <div style="background:#fff;border:1.5px solid #6ee7b7;border-radius:10px;padding:14px 16px">
    <div style="font-size:11px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Phase 4 · 4.OG</div>
    <div style="font-size:24px;font-weight:800;color:#7c3aed">599.08 m²</div>
    <div style="font-size:11px;color:#64748b;margin-top:2px">3 Einheiten (W 5.1, W 5.2, W 5.3) · + T 4.09 4.OG-Teil</div>
  </div>

  <div style="background:#1e293b;border:1.5px solid #1e293b;border-radius:10px;padding:14px 16px;color:#fff">
    <div style="font-size:11px;font-weight:700;color:#fbbf24;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Gesamt 3.OG + 4.OG</div>
    <div style="font-size:24px;font-weight:800;color:#fbbf24">1.614,10 m²</div>
    <div style="font-size:11px;color:#cbd5e1;margin-top:2px">21 Einheiten · 3.OG + 4.OG</div>
  </div>
</div>

<!-- ── FILTER ── -->
<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap" id="wohn-filters">
  <button onclick="filterWohnNew('all',this)" class="wohn-fbtn active" style="padding:6px 14px;border-radius:6px;border:1px solid #2563eb;background:#2563eb;color:#fff;cursor:pointer;font-size:12px;font-weight:600">Alle (21)</button>
  <button onclick="filterWohnNew('p1',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #16a34a40;background:#fff;color:#16a34a;cursor:pointer;font-size:12px;font-weight:600">Phase 2 (8)</button>
  <button onclick="filterWohnNew('p2',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #d9770640;background:#fff;color:#d97706;cursor:pointer;font-size:12px;font-weight:600">Phase 3 (9)</button>
  <button onclick="filterWohnNew('maisonette',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #16a34a40;background:#fff;color:#16a34a;cursor:pointer;font-size:12px;font-weight:600">Maisonette (1)</button>
  <button onclick="filterWohnNew('p3',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #7c3aed40;background:#fff;color:#7c3aed;cursor:pointer;font-size:12px;font-weight:600">Phase 4 (3)</button>
  <span style="border-left:1px solid #e2e8f0;margin:0 4px"></span>
  <button onclick="filterWohnNew('studio',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#374151;cursor:pointer;font-size:12px;font-weight:600">Studio (11)</button>
  <button onclick="filterWohnNew('2zi',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#374151;cursor:pointer;font-size:12px;font-weight:600">2-Zimmer (6)</button>
  <button onclick="filterWohnNew('3zi',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #0891b240;background:#fff;color:#0891b2;cursor:pointer;font-size:12px;font-weight:600">3-Zimmer (1)</button>
  <button onclick="filterWohnNew('penthouse',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #ca8a0440;background:#fff;color:#ca8a04;cursor:pointer;font-size:12px;font-weight:600">Penthouse (2)</button>
  <button onclick="filterWohnNew(\'buero\',this)" class="wohn-fbtn" style="padding:6px 14px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#374151;cursor:pointer;font-size:12px;font-weight:600">Büro (1)</button>
  <div style="flex:1"></div>
  <button onclick="expandAllRooms()" style="padding:6px 12px;border-radius:6px;border:1px solid #16a34a40;background:#f0fdf4;color:#16a34a;cursor:pointer;font-size:11px;font-weight:600">▼ Alle Räume zeigen</button>
  <button onclick="collapseAllRooms()" style="padding:6px 12px;border-radius:6px;border:1px solid #94a3b840;background:#f8fafc;color:#64748b;cursor:pointer;font-size:11px;font-weight:600">▲ Alle einklappen</button>
</div>

<!-- ── EINHEITEN-TABELLE ── -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">
  <table id="wohn-table" style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
      <tr style="background:#f1f5f9">
        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#64748b;font-weight:700">ID</th>
        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Typ</th>
        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Phase</th>
        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Stock</th>
        <th style="padding:8px 12px;text-align:right;font-size:10px;color:#64748b;font-weight:700">Fläche</th>
        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Bemerkung</th>
      </tr>
    </thead>
    <tbody id="wohn-tbody">
<tr class="wohn-section-header" style="background:#94a3b815;border-top:2px solid #94a3b880;border-bottom:2px solid #94a3b880"><td colspan="6" style="padding:10px 14px;font-size:13px;font-weight:800;color:#94a3b8;letter-spacing:.3px">🏗️ PHASE 1 <span style="font-size:11px;font-weight:600;opacity:.75;margin-left:8px">· 3 Einh. · 364.98 m² · W 5.2 + W 5.1 + T 5.1 · 4.OG</span></td></tr>
<tr class="wohn-main" data-uid="W 5.2" data-idx="1" data-phase="Phase 1" data-typ="Penthouse" onclick="toggleRooms('W_5_2')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-W_5_2" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> W 5.2</td>
  <td style="padding:8px 12px"><span style="background:#ca8a0418;color:#ca8a04;border:1px solid #ca8a0440;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Penthouse</span></td>
  <td style="padding:8px 12px"><span style="background:#94a3b818;color:#94a3b8;border:1px solid #94a3b840;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 1</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">4.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">236.50 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-W_5_2" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume W 5.2 · 8 Innenraumräume · Σ 89.77 m²<span style="color:#dc2626;font-size:10px;margin-left:8px">Δ +146.73 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0220</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Kind 3</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">11.36 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0240</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Kind 2</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">11.15 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0250</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Kind 1</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">13.46 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0260</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Zimmer 2</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">18.81 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0270</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad 1</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">8.58 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0280</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.91 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0290</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Ankleide</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.85 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0300</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Zimmer 1</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">18.65 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="W 5.1" data-idx="20" data-phase="Phase 1" data-typ="2-Zimmer" onclick="toggleRooms('W_5_1')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-W_5_1" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> W 5.1</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#94a3b818;color:#94a3b8;border:1px solid #94a3b840;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 1</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">4.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">74.44 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-W_5_1" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume W 5.1 · 0 Innenraumräume · Σ 0.00 m²</div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr><td colspan="6" style="padding:8px 36px;font-size:11px;color:#94a3b8;font-style:italic">– keine Plan-Räume erfasst –</td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 5.1" data-idx="0" data-phase="Phase 1" data-typ="Studio" onclick="toggleRooms('T_5_1')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_5_1" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 5.1</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#94a3b818;color:#94a3b8;border:1px solid #94a3b840;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 1</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">4.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">54.04 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-T_5_1" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 5.1 · 4 Innenraumräume · Σ 54.98 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.94 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0610</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Küche/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">32.06 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0590</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">13.43 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0580</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.37 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0570</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">6.12 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-section-header" style="background:#16a34a15;border-top:2px solid #16a34a80;border-bottom:2px solid #16a34a80"><td colspan="6" style="padding:10px 14px;font-size:13px;font-weight:800;color:#16a34a;letter-spacing:.3px">🏗️ PHASE 2 <span style="font-size:11px;font-weight:600;opacity:.75;margin-left:8px">· 8 Einh. · 397.99 m² · T 4.01 – T 4.08 · 3.OG</span></td></tr>
<tr class="wohn-main" data-uid="T 4.01" data-idx="2" data-phase="Phase 2" data-typ="3-Zimmer" onclick="toggleRooms('T_4_01')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_01" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.01</td>
  <td style="padding:8px 12px"><span style="background:#0891b218;color:#0891b2;border:1px solid #0891b240;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">3-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">72.35 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_01" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.01 · 5 Innenraumräume · Σ 62.82 m²<span style="color:#dc2626;font-size:10px;margin-left:8px">Δ +9.53 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0890</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">34.42 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0900</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">10.68 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0920</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Küche</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">13.59 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0910</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">1.79 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0901</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abst.</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.34 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.02" data-idx="3" data-phase="Phase 2" data-typ="2-Zimmer" onclick="toggleRooms('T_4_02')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_02" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.02</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">60.20 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_02" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.02 · 5 Innenraumräume · Σ 57.37 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ +2.83 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0580</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur/Küche/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">17.15 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0590</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">19.38 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0610</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">14.01 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0620</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.70 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0660</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.13 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.03" data-idx="4" data-phase="Phase 2" data-typ="2-Zimmer" onclick="toggleRooms('T_4_03')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_03" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.03</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">54.79 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_03" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.03 · 3 Innenraumräume · Σ 55.24 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.45 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0440</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.16 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0470</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0440b</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen + Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">27.44 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.04" data-idx="5" data-phase="Phase 2" data-typ="2-Zimmer" onclick="toggleRooms('T_4_04')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_04" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.04</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">54.79 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_04" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.04 · 6 Innenraumräume · Σ 50.15 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ +4.64 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0770</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.82 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0840</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.69 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0750</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.95 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0740</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.60 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0760</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur (Garderobe)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.85 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0680</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Büro/Schlafzimmer</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">11.24 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.05" data-idx="6" data-phase="Phase 2" data-typ="Studio" onclick="toggleRooms('T_4_05')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_05" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.05</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.55 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_05" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.05 · 3 Innenraumräume · Σ 31.51 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ +0.04 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0850</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.18 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0860</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0840</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.69 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.06" data-idx="7" data-phase="Phase 2" data-typ="Studio" onclick="toggleRooms('T_4_06')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_06" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.06</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.62 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_06" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.06 · 3 Innenraumräume · Σ 33.48 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ -1.86 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0690</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">28.29 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0720</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abstellraum</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.13 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0730</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.06 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.07" data-idx="8" data-phase="Phase 2" data-typ="Studio" onclick="toggleRooms('T_4_07')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_07" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.07</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.67 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_07" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.07 · 3 Innenraumräume · Σ 31.66 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ +0.01 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0820</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.22 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0800</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0810</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.08" data-idx="9" data-phase="Phase 2" data-typ="2-Zimmer" onclick="toggleRooms('T_4_08')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_08" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.08</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 2</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">61.02 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_08" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.08 · 7 Innenraumräume · Σ 56.22 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ +4.80 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0450</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">24.66 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0540</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abst</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.63 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0530</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">13.79 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0560</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WM</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">1.95 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0550</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.38 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0570</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Garderobe</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.03 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0590</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen extra</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">5.78 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-section-header" style="background:#d9770615;border-top:2px solid #d9770680;border-bottom:2px solid #d9770680"><td colspan="6" style="padding:10px 14px;font-size:13px;font-weight:800;color:#d97706;letter-spacing:.3px">🏗️ PHASE 3 <span style="font-size:11px;font-weight:600;opacity:.75;margin-left:8px">· 10 Einh. · 562.99 m² · T 4.22 – T 4.10 + T 4.09 (Maisonette) · 3.OG</span></td></tr>
<tr class="wohn-main" data-uid="T 4.22" data-idx="19" data-phase="Phase 3" data-typ="Büro" onclick="toggleRooms('T_4_22')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_22" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.22</td>
  <td style="padding:8px 12px"><span style="background:#37415118;color:#374151;border:1px solid #37415140;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Büro</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">102.73 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_22" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.22 · 9 Innenraumräume · Σ 102.77 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.04 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0120</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Empfang</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">24.19 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G050</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Behandlung 1</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">12.36 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0100</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Behandlung 2</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">13.84 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G090</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Behandlung 3</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">18.63 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0130</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Büro</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">12.04 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G080</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Tee-Küche</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.16 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0110</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">9.43 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G070</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC D</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.41 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G060</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC H</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.71 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.20" data-idx="18" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_20')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_20" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.20</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.63 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_20" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.20 · 3 Innenraumräume · Σ 31.62 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ +0.01 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0150</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0160</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0170</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.18 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.18" data-idx="17" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_18')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_18" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.18</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.55 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_18" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.18 · 3 Innenraumräume · Σ 31.60 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.05 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0200</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0190</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0180</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.16 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.16" data-idx="16" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_16')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_16" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.16</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.60 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_16" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.16 · 3 Innenraumräume · Σ 31.62 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.02 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0230</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0240</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0250</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.18 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.14" data-idx="15" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_14')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_14" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.14</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.57 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_14" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.14 · 3 Innenraumräume · Σ 31.62 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ -0.05 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0280</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0270</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0260</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.18 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.13" data-idx="14" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_13')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_13" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.13</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">32.73 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_13" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.13 · 3 Innenraumräume · Σ 32.68 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ +0.05 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G01030</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.08 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G01020</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">5.42 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G01010</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.18 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.12" data-idx="13" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_12')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_12" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.12</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.18 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_12" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.12 · 6 Innenraumräume · Σ 43.22 m²<span style="color:#dc2626;font-size:10px;margin-left:8px">Δ -12.04 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0360</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.94 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0350</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0410</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">5.48 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0420</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.04 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0400</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abstellraum</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.99 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0430</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.97 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.11" data-idx="12" data-phase="Phase 3" data-typ="2-Zimmer" onclick="toggleRooms('T_4_11')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_11" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.11</td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">2-Zimmer</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">61.01 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_11" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.11 · 6 Innenraumräume · Σ 56.94 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ +4.07 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0960</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">24.71 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0940</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">16.78 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0930</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abstellraum</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.98 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0970</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.04 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0980</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">5.48 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0990</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.95 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.10" data-idx="11" data-phase="Phase 3" data-typ="Studio" onclick="toggleRooms('T_4_10')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_10" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.10</td>
  <td style="padding:8px 12px"><span style="background:#2563eb18;color:#2563eb;border:1px solid #2563eb40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Studio</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">31.98 m²</td>
  <td style="padding:8px 12px"></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_10" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.10 · 3 Innenraumräume · Σ 31.60 m²<span style="color:#16a34a;font-size:10px;margin-left:8px">Δ +0.38 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0470</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen/Kochen/Schlafen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">23.64 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0480</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.80 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0490</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.16 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-main" data-uid="T 4.09" data-idx="10" data-phase="Phase 3" data-typ="Maisonette" onclick="toggleRooms('T_4_09')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-T_4_09" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> T 4.09</td>
  <td style="padding:8px 12px"><span style="background:#16a34a18;color:#16a34a;border:1px solid #16a34a40;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Maisonette</span></td>
  <td style="padding:8px 12px"><span style="background:#d9770618;color:#d97706;border:1px solid #d9770640;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 3/4</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">3.OG+4.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">177.01 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-T_4_09" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume T 4.09 · 14 Innenraumräume · Σ 181.64 m²<span style="color:#f59e0b;font-size:10px;margin-left:8px">Δ -4.63 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0510</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">24.56 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0500</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur/Küche/Essen (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">9.75 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0670</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">8.03 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0650</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">7.03 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0640</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">WC (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">2.13 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">3G0660</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Schlafen (3.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">15.40 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0120</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Speisekammer (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.10 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0130</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Kochen (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">16.09 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0140</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Essen (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">18.28 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G050</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Bad (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">6.92 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0150</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">42.44 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G060</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Waschküche (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">3.96 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0110</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Gästezimmer (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">15.72 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G070</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Abstellraum (4.OG)</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">7.23 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
<tr class="wohn-section-header" style="background:#7c3aed15;border-top:2px solid #7c3aed80;border-bottom:2px solid #7c3aed80"><td colspan="6" style="padding:10px 14px;font-size:13px;font-weight:800;color:#7c3aed;letter-spacing:.3px">🏗️ PHASE 4 <span style="font-size:11px;font-weight:600;opacity:.75;margin-left:8px">· 1 Einh. · 288.14 m² · W 5.3 · 4.OG</span></td></tr>
<tr class="wohn-main" data-uid="W 5.3" data-idx="21" data-phase="Phase 4" data-typ="Penthouse" onclick="toggleRooms('W_5_3')" style="border-bottom:1px solid #f1f5f9;cursor:pointer">
  <td style="padding:8px 12px;font-weight:700;color:#1e293b;font-size:13px"><span class="caret" id="caret-W_5_3" style="display:inline-block;width:12px;color:#94a3b8;transition:transform .15s">▶</span> W 5.3</td>
  <td style="padding:8px 12px"><span style="background:#ca8a0418;color:#ca8a04;border:1px solid #ca8a0440;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700">Penthouse</span></td>
  <td style="padding:8px 12px"><span style="background:#7c3aed18;color:#7c3aed;border:1px solid #7c3aed40;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700">Phase 4</span></td>
  <td style="padding:8px 12px;color:#64748b;font-size:11px">4.OG</td>
  <td style="padding:8px 12px;text-align:right;font-weight:700;color:#2563eb;font-size:13px">288.14 m²</td>
  <td style="padding:8px 12px"><span style="font-size:10px;color:#64748b;font-style:italic"></span></td>
</tr>
<tr class="wohn-detail" id="rooms-W_5_3" style="display:none">
  <td colspan="6" style="padding:0;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:2px solid #e2e8f0">
    <div style="padding:8px 16px 8px 36px;background:#eff6ff;font-size:11px;color:#1e40af;font-weight:600">📐 Räume W 5.3 · 7 Innenraumräume · Σ 123.22 m²<span style="color:#dc2626;font-size:10px;margin-left:8px">Δ +164.92 m² vs. Estrich</span></div>
    <table style="width:100%;border-collapse:collapse"><tbody><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0480</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Kochen/Essen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">44.60 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0490</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Gästezimmer</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">16.85 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0500</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Eingang</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">12.18 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0510</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Gästebad</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.47 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0520</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Flur</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">4.96 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0530</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Arbeiten</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">18.28 m²</td><td></td></tr><tr style="background:#fafafa"><td style="padding:5px 12px 5px 36px;font-size:10px;color:#94a3b8;font-family:monospace;width:90px">4G0550</td><td style="padding:5px 12px;font-size:11px;color:#475569" colspan="3">Wohnen</td><td style="padding:5px 12px;text-align:right;font-size:11px;color:#1e293b;font-weight:600">21.88 m²</td><td></td></tr></tbody></table>
  </td>
</tr>
</tbody></table>
  </td>
</tr>
    
  </table>
</div>

</div>

<script>
window.WOHNUNGEN_DATA = [{"id": "T 5.1", "typ": "Studio", "m2": 54.04, "phase": "Phase 1", "og": "4.OG", "bem": "Wellnessbereich"}, {"id": "W 5.2", "typ": "Penthouse", "m2": 236.5, "phase": "Phase 1", "og": "4.OG", "bem": "Penthouse · nur Kleinigkeiten"}, {"id": "T 4.01", "typ": "3-Zimmer", "m2": 72.35, "phase": "Phase 2", "og": "3.OG", "bem": "Wohnen/Essen + separate Küche"}, {"id": "T 4.02", "typ": "2-Zimmer", "m2": 60.2, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.03", "typ": "2-Zimmer", "m2": 54.79, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.04", "typ": "2-Zimmer", "m2": 54.79, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.05", "typ": "Studio", "m2": 31.55, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.06", "typ": "Studio", "m2": 31.62, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.07", "typ": "Studio", "m2": 31.67, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.08", "typ": "2-Zimmer", "m2": 61.02, "phase": "Phase 2", "og": "3.OG", "bem": ""}, {"id": "T 4.09", "typ": "Maisonette", "m2": 177.01, "phase": "Phase 3", "og": "3.OG+4.OG", "bem": "2-stöckig"}, {"id": "T 4.10", "typ": "Studio", "m2": 31.98, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.11", "typ": "2-Zimmer", "m2": 61.01, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.12", "typ": "Studio", "m2": 31.18, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.13", "typ": "Studio", "m2": 32.73, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.14", "typ": "Studio", "m2": 31.57, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.16", "typ": "Studio", "m2": 31.6, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.18", "typ": "Studio", "m2": 31.55, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.20", "typ": "Studio", "m2": 31.63, "phase": "Phase 3", "og": "3.OG", "bem": ""}, {"id": "T 4.22", "typ": "Büro", "m2": 102.73, "phase": "Phase 3", "og": "3.OG", "bem": "Büro-/Praxis-Einheit"}, {"id": "W 5.1", "typ": "2-Zimmer", "m2": 74.44, "phase": "Phase 1", "og": "4.OG", "bem": "kleine Wohnung · nur Kleinigkeiten"}, {"id": "W 5.3", "typ": "Penthouse", "m2": 288.14, "phase": "Phase 4", "og": "4.OG", "bem": "Penthouse"}];
function toggleRooms(safeId) {
  var det = document.getElementById('rooms-' + safeId);
  var caret = document.getElementById('caret-' + safeId);
  if (!det) return;
  var isHidden = (det.style.display === 'none');
  if (isHidden) {
    det.style.display = '';
    if (caret) caret.style.transform = 'rotate(90deg)';
  } else {
    det.style.display = 'none';
    if (caret) caret.style.transform = 'rotate(0deg)';
  }
}
function expandAllRooms() {
  document.querySelectorAll('.wohn-detail').forEach(function(r) { r.style.display = ''; });
  document.querySelectorAll('.caret').forEach(function(c) { c.style.transform = 'rotate(90deg)'; });
}
function collapseAllRooms() {
  document.querySelectorAll('.wohn-detail').forEach(function(r) { r.style.display = 'none'; });
  document.querySelectorAll('.caret').forEach(function(c) { c.style.transform = 'rotate(0deg)'; });
}
function filterWohnNew(mode, btn) {
  document.querySelectorAll('.wohn-fbtn').forEach(function(b){ b.classList.remove('active'); b.style.background='#fff'; b.style.color=b.style.borderColor && b.style.borderColor.startsWith('rgb(22') ? '#16a34a' : (b.style.borderColor && b.style.borderColor.startsWith('rgb(217') ? '#d97706' : (b.style.borderColor && b.style.borderColor.startsWith('rgb(220') ? '#dc2626' : (b.style.borderColor && b.style.borderColor.startsWith('rgb(124') ? '#7c3aed' : '#374151'))); b.style.borderColor=b.style.borderColor || '#e2e8f0'; });
  btn.classList.add('active'); btn.style.background='#2563eb'; btn.style.color='#fff'; btn.style.borderColor='#2563eb';
  var rows = document.querySelectorAll('#wohn-tbody tr.wohn-main');
  var total = 0, count = 0;
  rows.forEach(function(tr) {
    var idx = parseInt(tr.dataset.idx, 10);
    var u = window.WOHNUNGEN_DATA[idx];
    var safe = u.id.replace(/ /g,'_').replace(/\./g,'_');
    var detailRow = document.getElementById('rooms-' + safe);
    var show = (mode === 'all')
      || (mode === 'p1'      && u.phase === 'Phase 2')
      || (mode === 'p2'      && u.phase === 'Phase 3')
      || (mode === 'maisonette' && u.typ === 'Maisonette')
      || (mode === 'p3'      && u.phase === 'Phase 4')
      || (mode === 'studio'  && u.typ === 'Studio')
      || (mode === '2zi'     && u.typ === '2-Zimmer')
      || (mode === '3zi'     && u.typ === '3-Zimmer')
      || (mode === 'penthouse' && u.typ === 'Penthouse')
      || (mode === 'maisonette' && u.typ === 'Maisonette')
      || (mode === 'buero' && u.typ === 'Büro');
    tr.style.display = show ? '' : 'none';
    if (detailRow && !show) detailRow.style.display = 'none';
    if (show) { total += u.m2; count++; }
  });
  // Section-Headers basierend auf Filter ausblenden wenn keine Mitglieder
  document.querySelectorAll('tr.wohn-section-header').forEach(function(sh){ sh.style.display = (mode==='all') ? '' : 'none'; });
  var wt = document.getElementById('wohn-total'); if (wt) wt.textContent = total.toFixed(2) + ' m²';
}
</script>
</div>

<div id="tab-kosten" class="tab-content">
<div style="padding:20px 24px;max-width:1400px">

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div>
    <h1 style="font-size:16px;font-weight:800;color:#1e293b;margin:0">💶 Budgetplanung</h1>
    <p style="font-size:11px;color:#64748b;margin:4px 0 0">Wilhelm-Binder-Str. 15 · VS-Villingen · Alle Beträge editierbar · Auto-Summen · Werte werden lokal gespeichert</p>
  </div>
  <div style="display:flex;gap:8px">
    <button onclick="resetCostDefaults()" style="padding:6px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:11px;cursor:pointer;background:#f8fafc;color:#374151">↺ Standardwerte</button>
    <button onclick="exportCostCSV()" style="padding:6px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:11px;cursor:pointer;background:#f8fafc;color:#374151">📤 CSV Export</button>
  </div>
</div>

<!-- SUMMARY KARTEN -->
<div id="cost-summary-cards" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px"></div>

<!-- BESTELLUNGEN-ÜBERSICHT (automatisch aus Bestellungen) -->
<div id="cost-orders-overview" style="background:#fff;border:1.5px solid #16a34a30;border-radius:10px;margin-bottom:20px;overflow:hidden"></div>

<!-- EIGENE BUDGET-POSITIONEN (frei editierbar) -->
<div id="budget-custom-block" style="background:#fff;border:1.5px solid #2563eb30;border-radius:10px;margin-bottom:20px;overflow:hidden">
  <div style="padding:12px 16px;background:#eff6ff;border-bottom:1px solid #bfdbfe;display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-size:13px;font-weight:800;color:#1e40af">📝 Eigene Budget-Positionen</div>
      <div style="font-size:10px;color:#2563eb;margin-top:2px">Frei editierbar — alle Werte werden in die Gesamtsumme aufgenommen</div>
    </div>
    <button onclick="window.addBudgetCustom()" style="padding:6px 14px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">+ Position</button>
  </div>
  <div id="budget-custom-body"></div>
</div>

<!-- PLAUSIBILITÄT -->
<div id="cost-plausibility" style="background:#fff;border:1.5px solid #2563eb20;border-radius:10px;margin-bottom:20px;overflow:hidden">
  <div style="padding:10px 16px;background:#eff6ff;border-bottom:1px solid #bfdbfe">
    <span style="font-size:13px;font-weight:700;color:#1e40af">🔍 Plausibilitätsprüfung (aktualisiert sich automatisch)</span>
  </div>
  <div id="cost-plaus-body" style="padding:12px 16px;font-size:11px;color:#64748b">Lädt…</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
<div>
  <!-- Linke Spalte: Haustechnik + Hochbau -->
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-haustechnik"><div style="padding:10px 16px;background:#1e293b12;border-bottom:1px solid #1e293b25;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#1e293b">⚙️ OHG Haustechnik</span><span id="total-haustechnik" style="font-size:14px;font-weight:800;color:#1e293b">474.000 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Stromversorgung – Hausanschluss / Abwasser</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-0" type="number" value="18000" min="0" step="100" data-section="haustechnik" data-default="18000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">NSHV Niederspannungshauptverteilung</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-1" type="number" value="32000" min="0" step="100" data-section="haustechnik" data-default="32000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Batteriespeicher (Installation)</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-2" type="number" value="8000" min="0" step="100" data-section="haustechnik" data-default="8000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Kaltwassersatz (5,02×2,59m) – Installation</td><td style="padding:5px 10px;text-align:center"><span style="background:#d9770615;color:#d97706;border:1px solid #d9770630;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">EGA</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-3" type="number" value="52000" min="0" step="100" data-section="haustechnik" data-default="52000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sole-Wasser-Wärmepumpe Brigach</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-4" type="number" value="58000" min="0" step="100" data-section="haustechnik" data-default="58000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lüftungsanlagen 2+3 – Shedhalle</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-5" type="number" value="44000" min="0" step="100" data-section="haustechnik" data-default="44000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lüftungskanäle / Heizungsoptimierung</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-6" type="number" value="28000" min="0" step="100" data-section="haustechnik" data-default="28000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lüftungsanlage 4 HBO</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-7" type="number" value="35000" min="0" step="100" data-section="haustechnik" data-default="35000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Regen- & Schmutzwasserleitungen</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-8" type="number" value="24000" min="0" step="100" data-section="haustechnik" data-default="24000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Aufzüge – Umbau 2 Stück</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-9" type="number" value="145000" min="0" step="100" data-section="haustechnik" data-default="145000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Gerüstarbeiten Außen</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-10" type="number" value="18000" min="0" step="100" data-section="haustechnik" data-default="18000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Brandschutz Abschottungen</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-haustechnik-11" type="number" value="12000" min="0" step="100" data-section="haustechnik" data-default="12000" onchange="updateCostSection('haustechnik')" oninput="updateCostSection('haustechnik')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#1e293b0d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#1e293b">Summe ⚙️ OHG Haustechnik</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#1e293b" id="subtotal-haustechnik">474.000 €</td></tr></tfoot></table></div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-hochbau"><div style="padding:10px 16px;background:#1e293b12;border-bottom:1px solid #1e293b25;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#1e293b">🏗 WEG Hochbau Ost – Gebäudehülle</span><span id="total-hochbau" style="font-size:14px;font-weight:800;color:#1e293b">107.307 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Vakuumdämmung Dach HBO</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-0" type="number" value="28800" min="0" step="100" data-section="hochbau" data-default="28800" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Estrich Ausgleich Dach</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-1" type="number" value="15000" min="0" step="100" data-section="hochbau" data-default="15000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Schweißbahn / Kaltestreifen</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-2" type="number" value="2500" min="0" step="100" data-section="hochbau" data-default="2500" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Bodenbelag Terrasse</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-3" type="number" value="6500" min="0" step="100" data-section="hochbau" data-default="6500" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Rinne Erweiterung Terrasse</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-4" type="number" value="6000" min="0" step="100" data-section="hochbau" data-default="6000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Unterkonstruktion Terrasse (UK)</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-5" type="number" value="20000" min="0" step="100" data-section="hochbau" data-default="20000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Glasgeländer Terrasse</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-6" type="number" value="11107" min="0" step="100" data-section="hochbau" data-default="11107" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">P.R.F Fassade (Glas+Holz+Raico)</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-7" type="number" value="3000" min="0" step="100" data-section="hochbau" data-default="3000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Dämmung + UK Holz Fassade</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-8" type="number" value="2000" min="0" step="100" data-section="hochbau" data-default="2000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Fassade Ostseite (Putz+Farbe)</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-9" type="number" value="3200" min="0" step="100" data-section="hochbau" data-default="3200" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Kran Dach TH Nord + Brückenbau</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-10" type="number" value="6000" min="0" step="100" data-section="hochbau" data-default="6000" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Fassade Westseite (Putz+Farbe)</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hochbau-11" type="number" value="3200" min="0" step="100" data-section="hochbau" data-default="3200" onchange="updateCostSection('hochbau')" oninput="updateCostSection('hochbau')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#1e293b0d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#1e293b">Summe 🏗 WEG Hochbau Ost – Gebäudehülle</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#1e293b" id="subtotal-hochbau">107.307 €</td></tr></tfoot></table></div>
  <!-- Gesamttabelle -->
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">
    <div style="padding:10px 16px;background:#1e293b">
      <span style="font-size:13px;font-weight:700;color:#fff">📋 Gesamtrechnung</span>
    </div>
    <table style="width:100%;border-collapse:collapse">
    <tbody id="cost-totals-body"></tbody>
    </table>
  </div>
</div>
<div>
  <!-- Rechte Spalte: Apartments + Wellness + Hauptwerk -->
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-apt_ph1b"><div style="padding:10px 16px;background:#1e40af12;border-bottom:1px solid #1e40af25;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#1e40af">🏘 Apartments Phase 2 (8 Einh., T 4.01–T 4.08)</span><span id="total-apt_ph1b" style="font-size:14px;font-weight:800;color:#1e40af">522.000 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Trockenbau / Innenwände 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-0" type="number" value="82800" min="0" step="100" data-section="apt_ph1b" data-default="82800" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Elektroinstallation 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-1" type="number" value="70200" min="0" step="100" data-section="apt_ph1b" data-default="70200" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sanitärinstallation 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-2" type="number" value="85500" min="0" step="100" data-section="apt_ph1b" data-default="85500" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Heizung / FBHZ 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-3" type="number" value="49500" min="0" step="100" data-section="apt_ph1b" data-default="49500" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Estrich inkl. Trocknung 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-4" type="number" value="41400" min="0" step="100" data-section="apt_ph1b" data-default="41400" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Brandschutz / Abschottungen 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-5" type="number" value="12600" min="0" step="100" data-section="apt_ph1b" data-default="12600" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Abhangdecken / Unterdecken 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-6" type="number" value="28800" min="0" step="100" data-section="apt_ph1b" data-default="28800" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Malerarbeiten 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-7" type="number" value="34200" min="0" step="100" data-section="apt_ph1b" data-default="34200" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Bodenbelag Vinyl 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-8" type="number" value="37800" min="0" step="100" data-section="apt_ph1b" data-default="37800" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Türen montieren 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-9" type="number" value="52200" min="0" step="100" data-section="apt_ph1b" data-default="52200" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Blowerdoor-Test + Abnahme 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-10" type="number" value="8550" min="0" step="100" data-section="apt_ph1b" data-default="8550" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Reserve / Nebenarbeiten 9 WE</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph1b-11" type="number" value="18450" min="0" step="100" data-section="apt_ph1b" data-default="18450" onchange="updateCostSection('apt_ph1b')" oninput="updateCostSection('apt_ph1b')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#1e40af0d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#1e40af">Summe 🏘 Apartments Phase 2 (8 Einh., T 4.01–T 4.08)</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#1e40af" id="subtotal-apt_ph1b">522.000 €</td></tr></tfoot></table></div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-apt_ph2"><div style="padding:10px 16px;background:#0f766e12;border-bottom:1px solid #0f766e25;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#0f766e">🏘 Apartments Phase 3/4 (T 4.09 Maisonette · T 4.10–T 4.22 · W 5.3)</span><span id="total-apt_ph2" style="font-size:14px;font-weight:800;color:#0f766e">813.700 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Trockenbau / Innenwände 13 WE + W5.3</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-0" type="number" value="130200" min="0" step="100" data-section="apt_ph2" data-default="130200" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Elektroinstallation 13 WE + W5.3</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-1" type="number" value="109200" min="0" step="100" data-section="apt_ph2" data-default="109200" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sanitärinstallation 13 WE + W5.3</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-2" type="number" value="133000" min="0" step="100" data-section="apt_ph2" data-default="133000" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Heizung / FBHZ 13 WE + W5.3</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-3" type="number" value="77000" min="0" step="100" data-section="apt_ph2" data-default="77000" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Estrich inkl. Trocknung 13 WE + W5.3</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-4" type="number" value="64400" min="0" step="100" data-section="apt_ph2" data-default="64400" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Brandschutz / Abschottungen 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-5" type="number" value="19600" min="0" step="100" data-section="apt_ph2" data-default="19600" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Abhangdecken / Unterdecken 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-6" type="number" value="44800" min="0" step="100" data-section="apt_ph2" data-default="44800" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Malerarbeiten 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-7" type="number" value="53200" min="0" step="100" data-section="apt_ph2" data-default="53200" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Bodenbelag Vinyl 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-8" type="number" value="58800" min="0" step="100" data-section="apt_ph2" data-default="58800" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Türen montieren 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-9" type="number" value="81200" min="0" step="100" data-section="apt_ph2" data-default="81200" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Blowerdoor-Test + Abnahme 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-10" type="number" value="13300" min="0" step="100" data-section="apt_ph2" data-default="13300" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Reserve / Nebenarbeiten 14 Einh.</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-apt_ph2-11" type="number" value="29000" min="0" step="100" data-section="apt_ph2" data-default="29000" onchange="updateCostSection('apt_ph2')" oninput="updateCostSection('apt_ph2')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#0f766e0d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#0f766e">Summe 🏘 Apartments Phase 3/4 (T 4.09 Maisonette · T 4.10–T 4.22 · W 5.3)</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#0f766e" id="subtotal-apt_ph2">813.700 €</td></tr></tfoot></table></div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-wellness"><div style="padding:10px 16px;background:#0891b212;border-bottom:1px solid #0891b225;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#0891b2">🏊 Wellnessbereich T 5.1 (ohne Phase, gewerblich)</span><span id="total-wellness" style="font-size:14px;font-weight:800;color:#0891b2">233.500 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Abbrucharbeiten Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#d9770615;color:#d97706;border:1px solid #d9770630;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">EGA</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-0" type="number" value="12000" min="0" step="100" data-section="wellness" data-default="12000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Trockenbau / Decken Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-1" type="number" value="38000" min="0" step="100" data-section="wellness" data-default="38000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Heizung / FBHZ Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-2" type="number" value="20000" min="0" step="100" data-section="wellness" data-default="20000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Estrich Wellnessbereich</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-3" type="number" value="9500" min="0" step="100" data-section="wellness" data-default="9500" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Abdichtung Nassbereich</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-4" type="number" value="14000" min="0" step="100" data-section="wellness" data-default="14000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Fliesenarbeiten ca. 180m²</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-5" type="number" value="32000" min="0" step="100" data-section="wellness" data-default="32000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Türen + Glaswände Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-6" type="number" value="26000" min="0" step="100" data-section="wellness" data-default="26000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Malerarbeiten Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-7" type="number" value="9000" min="0" step="100" data-section="wellness" data-default="9000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sanitär Wellness (Endmontage)</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-8" type="number" value="28000" min="0" step="100" data-section="wellness" data-default="28000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Elektro / Beleuchtung Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-9" type="number" value="18000" min="0" step="100" data-section="wellness" data-default="18000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lüftung / Entfeuchtung Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-10" type="number" value="15000" min="0" step="100" data-section="wellness" data-default="15000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Reserve Wellness</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-wellness-11" type="number" value="12000" min="0" step="100" data-section="wellness" data-default="12000" onchange="updateCostSection('wellness')" oninput="updateCostSection('wellness')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#0891b20d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#0891b2">Summe 🏊 Wellnessbereich T 5.1 (ohne Phase, gewerblich)</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#0891b2" id="subtotal-wellness">233.500 €</td></tr></tfoot></table></div>
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px;overflow:hidden" id="block-hauptwerk"><div style="padding:10px 16px;background:#37415112;border-bottom:1px solid #37415125;display:flex;align-items:center;justify-content:space-between"><span style="font-size:13px;font-weight:700;color:#374151">🏢 HAUPTWERK / Sonderbereiche</span><span id="total-hauptwerk" style="font-size:14px;font-weight:800;color:#374151">207.000 €</span></div><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f8fafc"><th style="padding:6px 10px;text-align:left;font-size:10px;color:#94a3b8">Position</th><th style="padding:6px 10px;text-align:center;font-size:10px;color:#94a3b8">Verantwortl.</th><th style="padding:6px 10px;text-align:right;font-size:10px;color:#94a3b8">Betrag Netto</th></tr></thead><tbody><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Abdichtung Heizmittelraum</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-0" type="number" value="14000" min="0" step="100" data-section="hauptwerk" data-default="14000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lamellenfenster / RWA Treppenhaus</td><td style="padding:5px 10px;text-align:center"><span style="background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">Architronik</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-1" type="number" value="28000" min="0" step="100" data-section="hauptwerk" data-default="28000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">ICF-Kidsräume Fertigstellung 3.OG</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-2" type="number" value="52000" min="0" step="100" data-section="hauptwerk" data-default="52000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sanitärbereiche 2.OG Erneuerung</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-3" type="number" value="22000" min="0" step="100" data-section="hauptwerk" data-default="22000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Sanitärbereiche 3.OG Ausbau</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-4" type="number" value="16000" min="0" step="100" data-section="hauptwerk" data-default="16000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Lager / Nebenräume Fertigstellung</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-5" type="number" value="18000" min="0" step="100" data-section="hauptwerk" data-default="18000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Büroumzug / Neugestaltung</td><td style="padding:5px 10px;text-align:center"><span style="background:#2563eb15;color:#2563eb;border:1px solid #2563eb30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">DIB</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-6" type="number" value="35000" min="0" step="100" data-section="hauptwerk" data-default="35000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr><tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-size:11px;color:#1e293b">Reserve Hauptwerk</td><td style="padding:5px 10px;text-align:center"><span style="background:#16a34a15;color:#16a34a;border:1px solid #16a34a30;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:700">HEG</span></td><td style="padding:5px 10px;text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:4px"><input id="cost-hauptwerk-7" type="number" value="22000" min="0" step="100" data-section="hauptwerk" data-default="22000" onchange="updateCostSection('hauptwerk')" oninput="updateCostSection('hauptwerk')" style="width:100px;text-align:right;padding:3px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:11px;font-weight:600;color:#2563eb"><span style="font-size:10px;color:#94a3b8">€</span></div></td></tr></tbody><tfoot><tr style="background:#3741510d"><td colspan="2" style="padding:7px 10px;font-size:11px;font-weight:700;color:#374151">Summe 🏢 HAUPTWERK / Sonderbereiche</td><td style="padding:7px 10px;text-align:right;font-size:13px;font-weight:800;color:#374151" id="subtotal-hauptwerk">207.000 €</td></tr></tfoot></table></div>
</div>
</div>


<div style="margin-top:16px;padding:12px 16px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;font-size:11px;color:#92400e">
  ⚠ <strong>Hinweis:</strong> Alle Beträge sind editierbare Netto-Schätzwerte (±15%). Änderungen werden im Browser gespeichert. 
  Für verbindliche Kosten sind Angebote einzuholen. Reserve (8%) deckt Unvorhergesehenes ab.
</div>
</div>
</div>
<!-- ── BESTELLUNGEN ── -->

<!-- ══════════════ KAPAZITÄT TAB ══════════════ -->
<div id="tab-kapazitaet" class="tab-content">
<div style="padding:20px 24px;max-width:1600px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <div>
      <h1 style="font-size:16px;font-weight:800;color:#1e293b;margin:0">👷 Kapazitätsplanung</h1>
      <p style="font-size:11px;color:#64748b;margin:4px 0 0">Mitarbeiter, Mannstunden, Wochenkapazität — Sync über die DB</p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="kapImportGastromatic()" title="Importiert Urlaube aus dem Dienstplaner Gastromatic — Anbindung wird vorbereitet" style="padding:6px 12px;border-radius:6px;border:1px dashed #cbd5e1;background:#fff;font-size:11px;cursor:pointer;color:#64748b">📥 Urlaub aus Gastromatic (in Vorbereitung)</button>
      <button onclick="exportKap()" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;font-size:11px;cursor:pointer">📤 Export CSV</button>
    </div>
  </div>

  <!-- COCKPIT: KPI-Karten -->
  <div id="kap-cockpit" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px"></div>

  <!-- TABS innerhalb des Kapazität-Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:14px;border-bottom:1px solid #e2e8f0">
    <div class="kap-subtab active" data-sub="ma" onclick="showKapSub('ma',this)" style="padding:8px 16px;cursor:pointer;font-size:12px;font-weight:700;border-bottom:2px solid #2563eb;color:#2563eb">👥 Mitarbeiter</div>
    <div class="kap-subtab" data-sub="gw" onclick="showKapSub('gw',this)" style="padding:8px 16px;cursor:pointer;font-size:12px;font-weight:600;color:#64748b">🎯 Gewerke-Übersicht</div>
    <div class="kap-subtab" data-sub="kal" onclick="showKapSub('kal',this)" style="padding:8px 16px;cursor:pointer;font-size:12px;font-weight:600;color:#64748b">📅 Kalender / Auslastung</div>
    <div class="kap-subtab" data-sub="zu" onclick="showKapSub('zu',this)" style="padding:8px 16px;cursor:pointer;font-size:12px;font-weight:600;color:#64748b">🔗 Aufgaben-Zuordnung</div>
  </div>

  <!-- SUB-TAB 1: Mitarbeiter-Karten -->
  <div id="kap-sub-ma" class="kap-sub" style="display:block">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="font-size:14px;font-weight:700;margin:0">👥 Mitarbeiter</h2>
      <button onclick="addEmployee()" style="padding:6px 14px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">+ Mitarbeiter</button>
    </div>
    <div id="kap-ma-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px"></div>
    <div style="margin-top:8px;font-size:10px;color:#94a3b8">💡 Tipp: Auf einen Wert klicken zum Bearbeiten. Gewerk-Tags klicken öffnet den Picker.</div>
  </div>

  <!-- SUB-TAB: Gewerke-Übersicht (Mitarbeiter + Aufgaben + Auslastung pro Gewerk) -->
  <div id="kap-sub-gw" class="kap-sub" style="display:none">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 12px">🎯 Gewerke-Übersicht</h2>
    <div style="font-size:11px;color:#94a3b8;margin-bottom:14px">Pro Gewerk: zugeordnete Mitarbeiter · Aufgaben-Anzahl · KW-Auslastung als Mini-Heatmap</div>
    <div id="kap-gw-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px"></div>
  </div>

  <!-- SUB-TAB 2: Kalender / Auslastung -->
  <div id="kap-sub-kal" class="kap-sub" style="display:none">
    <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 10px">
      <h2 style="font-size:14px;font-weight:700;margin:0">📅 Wochen-Auslastung pro Gewerk</h2>
      <div style="display:flex;gap:4px">
        <button id="kal-filter-all"      onclick="setKalFilter('all',this)"      class="kal-filter active" style="padding:3px 11px;border-radius:14px;border:1.5px solid #2563eb;background:#2563eb;color:#fff;font-size:11px;font-weight:600;cursor:pointer">Alle KW</button>
        <button id="kal-filter-overload" onclick="setKalFilter('overload',this)" class="kal-filter" style="padding:3px 11px;border-radius:14px;border:1.5px solid #dc262640;background:#fff;color:#dc2626;font-size:11px;font-weight:600;cursor:pointer">⚠ Nur Überlast</button>
      </div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;max-height:600px">
      <table id="kap-kal-table" style="width:100%;border-collapse:collapse;font-size:11px;min-width:1400px">
        <thead id="kap-kal-thead"></thead>
        <tbody id="kap-kal-tbody"></tbody>
      </table>
    </div>
    <div style="margin-top:8px;font-size:10px;color:#94a3b8">
      💡 Grün = unter 80% Auslastung · Gelb = 80–100% · Rot = Überlast >100% (zusätzliche Kapazität nötig)
    </div>
  </div>

  <!-- SUB-TAB 3: Aufgaben-Zuordnung -->
  <div id="kap-sub-zu" class="kap-sub" style="display:none">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 12px">🔗 Aufgaben-Zuordnung (Mannstunden pro Aufgabe)</h2>
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:11px;color:#1e40af">
      Hier kannst du pro Aufgabe die <b>geplanten Mannstunden</b> eintragen. Die Wochen-Auslastung berücksichtigt diese automatisch.
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;max-height:600px;overflow-y:auto">
      <table style="width:100%;border-collapse:collapse;font-size:11px">
        <thead style="position:sticky;top:0;background:#f8fafc;z-index:2">
          <tr>
            <th style="padding:7px 10px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Aufgabe</th>
            <th style="padding:7px 10px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Einheit</th>
            <th style="padding:7px 10px;text-align:left;font-size:10px;color:#64748b;font-weight:700">Gewerk</th>
            <th style="padding:7px 10px;text-align:center;font-size:10px;color:#64748b;font-weight:700">Start KW</th>
            <th style="padding:7px 10px;text-align:center;font-size:10px;color:#64748b;font-weight:700">Dauer Wo</th>
            <th style="padding:7px 10px;text-align:center;font-size:10px;color:#64748b;font-weight:700">Mannstunden</th>
          </tr>
        </thead>
        <tbody id="kap-zu-tbody"></tbody>
      </table>
    </div>
  </div>

</div>
</div>

<script id="kapazitaet-engine">
(function(){
  var ORIGIN_KW = 23;
  var PX_PER_WEEK = 126;

  // KW-Anzeige mit Jahres-Trennung (KW23/2026 = KW23, KW53 = KW1/2027 ...)
  function kwToDisplay(contKw) {
    if (contKw <= 52) return { kw: contKw, year: 2026 };
    var delta = contKw - 52;
    var year = 2027;
    while (delta > 52) { delta -= 52; year++; }
    return { kw: delta, year: year };
  }
  window.kwToDisplay = kwToDisplay;
  function kwLabel(contKw) {
    var d = kwToDisplay(contKw);
    return 'KW' + d.kw + '/' + (d.year % 100);
  }
  window.kwLabel = kwLabel;

  // KW+Jahr ↔ kontinuierliche KW Konvertierung (Bezug: KW23/2026 = continuous 23)
  function ky2c(kw, year) {
    // continuous = (year - 2026)*52 + kw
    return (year - 2026) * 52 + kw;
  }
  function c2ky(contKw) {
    if (contKw <= 52) return { kw: contKw, year: 2026 };
    var year = 2026 + Math.floor((contKw - 1) / 52);
    var kw = ((contKw - 1) % 52) + 1;
    return { kw: kw, year: year };
  }
  window.ky2c = ky2c;
  window.c2ky = c2ky;



  // Mitarbeiter-Liste (Stand 21.05.2026)
  var DEFAULT_EMPLOYEES = [
    // SANITÄR/HEIZUNG (2)
    {id:'san_elmar', name:'Elmar',           gewerke:['Sanitär/Heizung'],              std:40, von:23, bis:52},
    {id:'san_urim',  name:'Urim',            gewerke:['Sanitär/Heizung'],              std:40, von:23, bis:52},
    // ELEKTRO (2 von Architronik, Vollzeit)
    {id:'el_arch1',  name:'Architronik 1',   gewerke:['Elektro'],                      std:40, von:23, bis:52},
    {id:'el_arch2',  name:'Architronik 2',   gewerke:['Elektro'],                      std:40, von:23, bis:52},
    // MALER/GIPSER (4)
    {id:'ma_chris',  name:'Chris',           gewerke:['Maler/Gipser'],                 std:40, von:23, bis:52},
    {id:'ma_adnan',  name:'Adnan',           gewerke:['Maler/Gipser'],                 std:40, von:23, bis:52},
    {id:'ma_ighor',  name:'Ighor',           gewerke:['Maler/Gipser'],                 std:40, von:23, bis:52},
    {id:'ma_leih',   name:'Leiharbeiter',    gewerke:['Maler/Gipser'],                 std:40, von:23, bis:52},
    // ALLROUNDER — ALLE IDENTISCH wie Kamil
    {id:'al_kamil',  name:'Kamil',           gewerke:['Trockenbau','Bodenbelag','Fliesen','Estrich','Brandschutz','Schreiner/Endmontage'], std:40, von:23, bis:52},
    {id:'al_rene',   name:'Rene',            gewerke:['Trockenbau','Bodenbelag','Fliesen','Estrich','Brandschutz','Schreiner/Endmontage'], std:40, von:23, bis:52},
    {id:'al_micha',  name:'Micha',           gewerke:['Trockenbau','Bodenbelag','Fliesen','Estrich','Brandschutz','Schreiner/Endmontage'], std:40, von:23, bis:52},
    {id:'al_serhi',  name:'Serhi',           gewerke:['Trockenbau','Bodenbelag','Fliesen','Estrich','Brandschutz','Schreiner/Endmontage'], std:40, von:23, bis:52},
    {id:'al_danil',  name:'Danil',           gewerke:['Trockenbau','Bodenbelag','Fliesen','Estrich','Brandschutz','Schreiner/Endmontage'], std:40, von:23, bis:52},
  ];

  function loadEmployees() {
    var saved = JSON.parse(localStorage.getItem('kap-mitarbeiter-v10') || 'null');
    return saved || DEFAULT_EMPLOYEES.slice();
  }
  function saveEmployees(arr) {
    var json = JSON.stringify(arr);
    localStorage.setItem('kap-mitarbeiter-v10', json);
    if (window.__syncKV) window.__syncKV('kap-mitarbeiter-v10', json);
  }
  var employees = loadEmployees();

  // ── Gewerke-Picker (Multi-Select mit Checkboxen) ──────────────────
  var GEWERKE_LIST = [
    {name:'Sanitär/Heizung',     bg:'#dbeafe', fg:'#2563eb'},
    {name:'Elektro',             bg:'#fed7aa', fg:'#d97706'},
    {name:'Maler/Gipser',        bg:'#fed7aa', fg:'#ea580c'},
    {name:'Trockenbau',          bg:'#dbeafe', fg:'#6366f1'},
    {name:'Bodenbelag',          bg:'#fef3c7', fg:'#92400e'},
    {name:'Fliesen',             bg:'#ccfbf1', fg:'#0f766e'},
    {name:'Estrich',             bg:'#e9d5ff', fg:'#7c3aed'},
    {name:'Schreiner/Endmontage',bg:'#fde68a', fg:'#78350f'},
    {name:'Brandschutz',         bg:'#fee2e2', fg:'#991b1b'},
    {name:'Dach/Fassade',        bg:'#d1fae5', fg:'#065f46'},
    {name:'Planung/Architekt',   bg:'#e0e7ff', fg:'#4338ca'},
    {name:'Sonstige',            bg:'#f1f5f9', fg:'#64748b'},
  ];
  function gwColors(name) {
    var list = window.GEWERKE || GEWERKE_LIST;
    var g = list.find(function(x){return x.name === name;});
    return g || {bg:'#f1f5f9', fg:'#64748b'};
  }
  function gwBadgesHtml(arr) {
    if (!arr || !arr.length) return '<span class="gw-empty">Klick für Auswahl</span>';
    return arr.map(function(g){
      var c = gwColors(g);
      return '<span class="gw-badge" style="background:' + c.bg + ';color:' + c.fg + ';border:1px solid ' + c.fg + '40">' + g + '</span>';
    }).join('');
  }
  window.gwBadgesHtml = gwBadgesHtml;

  // ── Urlaub / Abwesenheit Multi-Manager ─────────────────────────────
  function urlaubBadgesHtml(arr) {
    if (!arr || !arr.length) return '<span class="ur-empty">+ Urlaub eintragen</span>';
    return arr.map(function(u){
      var vy = u.vonYear % 100;
      var by = u.bisYear % 100;
      var txt = 'KW' + u.vonKw + (vy !== by || u.vonYear !== 2026 ? '/' + vy : '') + '–' + u.bisKw + '/' + by;
      return '<span class="ur-badge">🏖 ' + txt + '</span>';
    }).join('');
  }
  window.urlaubBadgesHtml = urlaubBadgesHtml;
window.openUrlaubModal = function(cell) {
    var idx = parseInt(cell.dataset.idx, 10);
    var emp = employees[idx];
    if (!emp.urlaub) emp.urlaub = [];
    document.querySelectorAll('.urlaub-modal').forEach(function(d){d.remove();});

    var modal = document.createElement('div');
    modal.className = 'urlaub-modal';
    modal.innerHTML = '<h4>🏖 Urlaub / Abwesenheit für ' + emp.name + '</h4>' +
      '<div id="ur-rows"></div>' +
      '<div class="ur-actions">' +
        '<button onclick="urAdd()" style="flex:1;padding:6px;background:#16a34a;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:11px;font-weight:600">+ Eintrag</button>' +
        '<button onclick="urClose()" style="padding:6px 10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:5px;cursor:pointer;font-size:11px">Schließen</button>' +
      '</div>';
    document.body.appendChild(modal);

    var rect = cell.getBoundingClientRect();
    var top = rect.bottom + 4;
    if (top + 420 > window.innerHeight) top = Math.max(8, rect.top - 420 - 4);
    modal.style.left = rect.left + 'px';
    modal.style.top = top + 'px';

    var rows = modal.querySelector('#ur-rows');

    function renderRows() {
      if (!emp.urlaub.length) {
        rows.innerHTML = '<p style="font-size:11px;color:#94a3b8;font-style:italic;margin:0 0 6px">Noch kein Urlaub eingetragen.</p>';
        return;
      }
      rows.innerHTML = emp.urlaub.map(function(u, i){
        return '<div class="ur-row">' +
          '<span style="color:#92400e;font-weight:600;min-width:30px">Von:</span>' +
          '<span>KW</span><input type="number" min="1" max="52" value="' + u.vonKw + '" data-ui="' + i + '" data-uf="vonKw">' +
          '<select data-ui="' + i + '" data-uf="vonYear"><option value="2026"' + (u.vonYear === 2026 ? ' selected' : '') + '>2026</option><option value="2027"' + (u.vonYear === 2027 ? ' selected' : '') + '>2027</option><option value="2028"' + (u.vonYear === 2028 ? ' selected' : '') + '>2028</option></select>' +
          '<span>Bis: KW</span><input type="number" min="1" max="52" value="' + u.bisKw + '" data-ui="' + i + '" data-uf="bisKw">' +
          '<select data-ui="' + i + '" data-uf="bisYear"><option value="2026"' + (u.bisYear === 2026 ? ' selected' : '') + '>2026</option><option value="2027"' + (u.bisYear === 2027 ? ' selected' : '') + '>2027</option><option value="2028"' + (u.bisYear === 2028 ? ' selected' : '') + '>2028</option></select>' +
          '<button onclick="urDel(' + i + ')">🗑</button>' +
        '</div>';
      }).join('');
      rows.querySelectorAll('input,select').forEach(function(inp){
        inp.addEventListener('change', function(){
          var ui = +this.dataset.ui;
          var uf = this.dataset.uf;
          var val = +this.value;
          emp.urlaub[ui][uf] = val;
          saveEmployees(employees);
          cell.innerHTML = urlaubBadgesHtml(emp.urlaub);
          renderKalender();
        });
      });
    }

    window.urAdd = function(){
      emp.urlaub.push({vonKw: 1, vonYear: 2026, bisKw: 1, bisYear: 2026});
      saveEmployees(employees);
      cell.innerHTML = urlaubBadgesHtml(emp.urlaub);
      renderRows();
      renderKalender();
    };
    window.urDel = function(i){
      emp.urlaub.splice(i,1);
      saveEmployees(employees);
      cell.innerHTML = urlaubBadgesHtml(emp.urlaub);
      renderRows();
      renderKalender();
    };
    window.urClose = function(){ modal.remove(); };

    renderRows();

    setTimeout(function(){
      document.addEventListener('click', function close(e){
        if (modal.contains(e.target) || cell.contains(e.target)) return;
        modal.remove();
        document.removeEventListener('click', close);
      });
    }, 10);
  };



  window.openGewerkPicker = function(badgeDiv) {
    var idx = parseInt(badgeDiv.dataset.idx, 10);
    var current = employees[idx].gewerke || [];
    document.querySelectorAll('.gw-multi-dropdown').forEach(function(d){d.remove();});

    var dd = document.createElement('div');
    dd.className = 'gw-multi-dropdown';

    (window.GEWERKE || GEWERKE_LIST).forEach(function(g){
      if (!g.name) return;
      var lbl = document.createElement('label');
      var cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.value = g.name;
      cb.checked = current.indexOf(g.name) >= 0;
      var dot = document.createElement('span');
      dot.style.cssText = 'display:inline-block;width:10px;height:10px;border-radius:50%;background:' + g.fg;
      var txt = document.createElement('span');
      txt.textContent = g.name;
      txt.style.color = g.fg;
      txt.style.fontWeight = '600';
      lbl.appendChild(cb);
      lbl.appendChild(dot);
      lbl.appendChild(txt);
      dd.appendChild(lbl);
    });

    var actions = document.createElement('div');
    actions.className = 'gw-actions';
    var btnApply = document.createElement('button');
    btnApply.textContent = '✓ Übernehmen';
    btnApply.style.background = '#2563eb';
    btnApply.style.color = '#fff';
    btnApply.style.border = '1px solid #2563eb';
    btnApply.onclick = function(e){
      e.stopPropagation();
      var sel = Array.from(dd.querySelectorAll('input[type=checkbox]:checked')).map(function(c){return c.value;});
      employees[idx].gewerke = sel;
      saveEmployees(employees);
      badgeDiv.innerHTML = gwBadgesHtml(sel);
      renderKalender();
      dd.remove();
    };
    var btnCancel = document.createElement('button');
    btnCancel.textContent = 'Abbrechen';
    btnCancel.onclick = function(e){ e.stopPropagation(); dd.remove(); };
    actions.appendChild(btnApply);
    actions.appendChild(btnCancel);
    dd.appendChild(actions);

    document.body.appendChild(dd);
    var rect = badgeDiv.getBoundingClientRect();
    var top = rect.bottom + 4;
    var left = rect.left;
    // Falls Dropdown unten rausfällt: nach oben öffnen
    if (top + 320 > window.innerHeight) top = rect.top - 320 - 4;
    dd.style.left = left + 'px';
    dd.style.top = top + 'px';

    setTimeout(function(){
      document.addEventListener('click', function close(e){
        if (dd.contains(e.target)) return;
        dd.remove();
        document.removeEventListener('click', close);
      });
    }, 10);
  };



  // ── Mitarbeiter-Tabelle rendern ──────────────────────────────────────
  function initials(n) {
    return (n||'?').split(/\s+/).map(function(p){return p[0]||'';}).join('').slice(0,2).toUpperCase();
  }
  function colorFromString(s) {
    var h = 0; for (var i=0; i<s.length; i++) h = (h*31 + s.charCodeAt(i)) | 0;
    var hue = Math.abs(h) % 360; return 'hsl(' + hue + ' 65% 50%)';
  }
  // KW + Jahr → ISO-Datum des Wochen-Montags (YYYY-MM-DD)
  function kwYearToISODate(kw, year) {
    var jan4 = new Date(Date.UTC(year, 0, 4));
    var dayOfWeek = (jan4.getUTCDay() + 6) % 7; // 0=Mo
    var monday = new Date(jan4);
    monday.setUTCDate(jan4.getUTCDate() - dayOfWeek + (kw - 1) * 7);
    return monday.toISOString().slice(0, 10);
  }
  // Datum (YYYY-MM-DD, TT.MM.YYYY oder TT.MM) → {kw, year}
  function parseFlexDate(s) {
    s = (s || '').trim();
    if (!s) return null;
    // YYYY-MM-DD
    var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    var d;
    if (m) d = new Date(Date.UTC(+m[1], +m[2]-1, +m[3]));
    else {
      // TT.MM.YYYY oder TT.MM
      m = s.match(/^(\d{1,2})\.(\d{1,2})(?:\.(\d{2,4}))?$/);
      if (!m) return null;
      var yr = m[3] ? +m[3] : new Date().getFullYear();
      if (yr < 100) yr += 2000;
      d = new Date(Date.UTC(yr, +m[2]-1, +m[1]));
    }
    if (isNaN(d.getTime())) return null;
    // ISO-Woche
    var target = new Date(d.valueOf());
    var dayNr = (d.getUTCDay() + 6) % 7;
    target.setUTCDate(target.getUTCDate() - dayNr + 3);
    var jan4 = new Date(Date.UTC(target.getUTCFullYear(), 0, 4));
    var iso = 1 + Math.ceil((target - jan4) / 86400000 / 7);
    return { kw: iso, year: target.getUTCFullYear() };
  }
  function fmtKw(u) {
    return 'KW ' + u.vonKw + (u.vonYear !== u.bisYear ? '/' + u.vonYear : '')
      + ' – KW ' + u.bisKw + '/' + u.bisYear;
  }

  function urlaubListHtml(urls, empIdx) {
    var pills = '';
    if (!urls || !urls.length) {
      pills = '<span style="color:#cbd5e1;font-size:11px;font-style:italic">keine Urlaube</span>';
    } else {
      pills = urls.map(function(u, i){
        return '<span class="urlaub-pill" data-eidx="' + empIdx + '" data-uidx="' + i + '" '
          + 'style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;margin-right:4px;margin-bottom:3px;cursor:pointer" '
          + 'title="Klick zum Bearbeiten">'
          + fmtKw(u)
          + '<button data-uidx="' + i + '" class="urlaub-del" title="Löschen" style="background:none;border:none;color:#b45309;cursor:pointer;font-size:11px;padding:0 0 0 4px;line-height:1">×</button>'
        + '</span>';
      }).join('');
    }
    // Editor-Container immer anhängen (egal ob Urlaube vorhanden) — sonst greift "+ Urlaub" ins Leere
    return pills + '<div class="urlaub-editor" data-eidx="' + empIdx + '" style="display:none"></div>';
  }

  function fmtDateShort(iso) {
    if (!iso) return '';
    try {
      var d = new Date(iso + 'T12:00:00');
      return d.toLocaleDateString('de-DE', {day:'2-digit', month:'short', year:'numeric'});
    } catch (e) { return iso; }
  }

  function renderMA() {
    var cont = document.getElementById('kap-ma-cards');
    if (!cont) return;
    cont.innerHTML = employees.map(function(e, idx) {
      var v = c2ky(e.von), b = c2ky(e.bis);
      var vonISO = kwYearToISODate(v.kw, v.year);
      var bisISO = kwYearToISODate(b.kw, b.year);
      var ini = initials(e.name);
      var ac = colorFromString(e.id || e.name || ('e'+idx));
      return '<div class="kap-card" data-idx="' + idx + '" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 1px 3px rgba(15,23,42,.04);display:flex;flex-direction:column;gap:12px;position:relative">'
        // Header: Avatar + Name + Delete
        + '<div style="display:flex;align-items:center;gap:10px">'
          + '<div style="width:38px;height:38px;border-radius:50%;background:' + ac + ';color:#fff;font-weight:800;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">' + ini + '</div>'
          + '<input value="' + escapeHtml(e.name) + '" data-idx="' + idx + '" data-field="name" class="kap-edit" style="flex:1;border:none;background:transparent;font-size:14px;font-weight:700;color:#1e293b;outline:none;padding:2px;border-bottom:1px dashed transparent" onfocus="this.style.borderBottomColor=\'#cbd5e1\'" onblur="this.style.borderBottomColor=\'transparent\'">'
          + '<button onclick="delEmployee(' + idx + ')" title="Mitarbeiter löschen" style="background:transparent;border:none;color:#cbd5e1;cursor:pointer;font-size:14px;line-height:1;padding:4px">🗑</button>'
        + '</div>'
        // Gewerke
        + '<div>'
          + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Gewerke</div>'
          + '<div class="gw-picker-badges" data-idx="' + idx + '" onclick="openGewerkPicker(this)" style="cursor:pointer;min-height:24px">' + gwBadgesHtml(e.gewerke) + '</div>'
        + '</div>'
        // Stunden + Verfügbar via Datums-Picker
        + '<div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">'
          + '<div>'
            + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Stunden / Woche</div>'
            + '<input type="number" min="0" max="80" value="' + e.std + '" data-idx="' + idx + '" data-field="std" class="kap-edit" style="width:72px;text-align:center;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;font-weight:700;padding:4px 6px">'
          + '</div>'
          + '<div style="flex:1;min-width:160px">'
            + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Verfügbar von – bis</div>'
            + '<div style="display:flex;align-items:center;gap:4px">'
              + '<input type="date" value="' + vonISO + '" data-idx="' + idx + '" data-field="von_date" class="kap-edit" style="border:1px solid #e2e8f0;border-radius:5px;font-size:11px;padding:3px 5px;font-family:inherit;flex:1;min-width:0">'
              + '<span style="color:#94a3b8">–</span>'
              + '<input type="date" value="' + bisISO + '" data-idx="' + idx + '" data-field="bis_date" class="kap-edit" style="border:1px solid #e2e8f0;border-radius:5px;font-size:11px;padding:3px 5px;font-family:inherit;flex:1;min-width:0">'
            + '</div>'
            + '<div style="font-size:9px;color:#94a3b8;margin-top:3px">KW ' + v.kw + '/' + v.year + ' – KW ' + b.kw + '/' + b.year + '</div>'
          + '</div>'
        + '</div>'
        // Urlaub-Liste
        + '<div>'
          + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">'
            + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px">🏖 Urlaub / Abwesenheit</div>'
            + '<button onclick="kapAddUrlaub(' + idx + ')" style="background:transparent;border:1px dashed #cbd5e1;color:#2563eb;border-radius:6px;padding:2px 8px;font-size:10px;font-weight:600;cursor:pointer">+ Urlaub</button>'
          + '</div>'
          + '<div class="kap-urlaub-list" data-idx="' + idx + '">' + urlaubListHtml(e.urlaub || [], idx) + '</div>'
        + '</div>'
      + '</div>';
    }).join('');
    // Wire inputs in cards
    cont.querySelectorAll('.kap-edit').forEach(function(inp){
      inp.addEventListener('change', function(){
        var idx = +this.dataset.idx;
        var f = this.dataset.field;
        var emp = employees[idx];
        if (f === 'std') emp.std = +this.value;
        else if (f === 'von_date') {
          var ky = parseFlexDate(this.value);
          if (ky) emp.von = ky2c(ky.kw, ky.year);
        }
        else if (f === 'bis_date') {
          var ky2 = parseFlexDate(this.value);
          if (ky2) emp.bis = ky2c(ky2.kw, ky2.year);
        }
        else emp[f] = this.value;
        saveEmployees(employees);
        renderMA(); renderKalender(); renderKapaCockpit();
      });
    });
    // Wire urlaub × delete buttons + Pill-Klick zum Bearbeiten
    cont.querySelectorAll('.urlaub-del').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.stopPropagation();
        var idx = +this.closest('.kap-urlaub-list').dataset.idx;
        var uidx = +this.dataset.uidx;
        employees[idx].urlaub = employees[idx].urlaub || [];
        employees[idx].urlaub.splice(uidx, 1);
        saveEmployees(employees);
        renderMA(); renderKalender(); renderKapaCockpit();
      });
    });
    cont.querySelectorAll('.urlaub-pill').forEach(function(pill){
      pill.addEventListener('click', function(e){
        if (e.target.classList.contains('urlaub-del')) return;
        e.stopPropagation();
        openUrlaubEditor(+this.dataset.eidx, +this.dataset.uidx);
      });
    });
  }

  // Inline-Editor unter der Urlaubs-Liste anzeigen (zum Hinzufügen oder Bearbeiten)
  function openUrlaubEditor(empIdx, urlIdx) {
    var emp = employees[empIdx];
    if (!emp) return;
    var list = document.querySelector('.kap-urlaub-list[data-idx="' + empIdx + '"]');
    if (!list) return;
    var ed = list.querySelector('.urlaub-editor');
    if (!ed) return;
    var existing = (typeof urlIdx === 'number') ? (emp.urlaub || [])[urlIdx] : null;
    var fromISO = existing ? kwYearToISODate(existing.vonKw, existing.vonYear) : '';
    var toISO   = existing ? kwYearToISODate(existing.bisKw, existing.bisYear) : '';
    ed.style.display = '';
    ed.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:8px;padding:8px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px';
    ed.innerHTML =
        '<span style="font-size:10px;font-weight:700;color:#64748b">' + (existing ? '✏ Bearbeiten' : '＋ Neuer Urlaub') + '</span>'
      + '<input class="ur-from" type="date" value="' + fromISO + '" style="border:1px solid #e2e8f0;border-radius:4px;padding:3px 6px;font-size:11px;font-family:inherit">'
      + '<span style="font-size:11px;color:#64748b">bis</span>'
      + '<input class="ur-to" type="date" value="' + toISO + '" style="border:1px solid #e2e8f0;border-radius:4px;padding:3px 6px;font-size:11px;font-family:inherit">'
      + '<button class="ur-save" style="background:#16a34a;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:10px;font-weight:700;cursor:pointer">✓ speichern</button>'
      + '<button class="ur-cancel" style="background:#fff;color:#64748b;border:1px solid #e2e8f0;border-radius:5px;padding:4px 10px;font-size:10px;font-weight:600;cursor:pointer">abbrechen</button>';

    ed.querySelector('.ur-cancel').onclick = function(){ ed.style.display = 'none'; ed.innerHTML = ''; };
    ed.querySelector('.ur-save').onclick = function(){
      var fv = ed.querySelector('.ur-from').value;
      var tv = ed.querySelector('.ur-to').value;
      var a = parseFlexDate(fv), b2 = parseFlexDate(tv);
      if (!a || !b2) { alert('Bitte gültiges Von- und Bis-Datum auswählen.'); return; }
      // Sortieren falls falsch herum
      var aC = ky2c(a.kw, a.year), bC = ky2c(b2.kw, b2.year);
      if (bC < aC) { var tmp = a; a = b2; b2 = tmp; }
      emp.urlaub = emp.urlaub || [];
      var rec = { vonKw: a.kw, vonYear: a.year, bisKw: b2.kw, bisYear: b2.year };
      if (typeof urlIdx === 'number') emp.urlaub[urlIdx] = rec;
      else emp.urlaub.push(rec);
      saveEmployees(employees);
      renderMA(); renderKalender(); renderKapaCockpit();
    };
  }

  // Öffentliche Funktion vom "+ Urlaub"-Button (neuer Urlaub)
  window.kapAddUrlaub = function (idx) { openUrlaubEditor(idx); };
  window.kapEditUrlaub = function (idx, uidx) { openUrlaubEditor(idx, uidx); };

  // Gastromatic-Import-Stub
  window.kapImportGastromatic = function () {
    alert(
      '📥 Urlaubs-Import aus Gastromatic\n\n' +
      'Diese Anbindung ist in Vorbereitung.\n\n' +
      'Was wir dafür brauchen:\n' +
      '• API-Endpoint + API-Key von Gastromatic\n' +
      '• Mapping: Gastromatic-Mitarbeiter ↔ Mitarbeiter im Dashboard\n' +
      '  (z.B. über E-Mail-Adresse oder externe ID)\n\n' +
      'Sobald die Daten verfügbar sind, baue ich den Sync\n' +
      'als Hintergrund-Job (z.B. nächtlich) — Urlaube werden\n' +
      'dann automatisch in die Mitarbeiterkarten übernommen.'
    );
  };

  // Heatmap-Filter (Alle / Nur Überlast)
  var kalFilter = 'all';
  window.setKalFilter = function (mode, btn) {
    kalFilter = mode;
    document.querySelectorAll('.kal-filter').forEach(function(b){
      var on = (b === btn);
      b.classList.toggle('active', on);
      if (on) { b.style.background = (mode==='overload' ? '#dc2626' : '#2563eb'); b.style.color = '#fff'; b.style.borderColor = (mode==='overload' ? '#dc2626' : '#2563eb'); }
      else { b.style.background = '#fff'; b.style.color = (b.id === 'kal-filter-overload' ? '#dc2626' : '#2563eb'); b.style.borderColor = (b.id === 'kal-filter-overload' ? '#dc262640' : '#2563eb'); }
    });
    renderKalender();
  };

  // Cockpit: 3 KPI-Karten — Aktuelle Woche / Engpässe / größte Überlast
  function renderKapaCockpit() {
    var el = document.getElementById('kap-cockpit');
    if (!el) return;
    var demand = tasksByGewerkAndKw();
    var supply = capacityByGewerkAndKw();
    var nowKW = window.dateToContKW ? window.dateToContKW(new Date().toISOString().slice(0,10)) : 0;

    var gewerke = getAllGewerke();
    var thisWeekDemand = 0, thisWeekSupply = 0;
    gewerke.forEach(function(g){
      thisWeekDemand += (demand[g]||{})[nowKW] || 0;
      thisWeekSupply += (supply[g]||{})[nowKW] || 0;
    });
    var thisLoad = thisWeekSupply > 0 ? Math.round(thisWeekDemand / thisWeekSupply * 100) : 0;

    var bottlenecks = []; // {kw, gewerk, overload}
    for (var dk = 0; dk < 4; dk++) {
      var kw = nowKW + dk;
      gewerke.forEach(function(g){
        var d = (demand[g]||{})[kw] || 0;
        var s = (supply[g]||{})[kw] || 0;
        if (s > 0 && d > s) bottlenecks.push({ kw: kw, gewerk: g, over: Math.round((d - s)) });
      });
    }
    bottlenecks.sort(function(a,b){ return b.over - a.over; });

    var maxOver = bottlenecks.length ? bottlenecks[0] : null;

    function card(icon, lbl, value, sub, color, action) {
      return '<div onclick="' + action + '" '
        + 'style="background:#fff;border:1.5px solid ' + color + '30;border-radius:10px;padding:12px 16px;flex:1;min-width:200px;cursor:pointer;transition:transform .12s,box-shadow .12s" '
        + 'onmouseenter="this.style.transform=\'translateY(-2px)\';this.style.boxShadow=\'0 4px 12px rgba(15,23,42,.08)\'" '
        + 'onmouseleave="this.style.transform=\'\';this.style.boxShadow=\'\'">'
        + '<div style="font-size:18px">' + icon + '</div>'
        + '<div style="font-size:18px;font-weight:800;color:' + color + ';line-height:1.2">' + value + '</div>'
        + '<div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-top:2px">' + lbl + '</div>'
        + '<div style="font-size:10px;color:#94a3b8">' + sub + '</div>'
      + '</div>';
    }

    var loadColor = thisLoad > 100 ? '#dc2626' : thisLoad > 80 ? '#d97706' : '#15803d';
    var loadIcon = thisLoad > 100 ? '🔴' : thisLoad > 80 ? '🟡' : '🟢';
    el.innerHTML =
        card(loadIcon, 'Auslastung diese Woche (KW ' + nowKW + ')', thisLoad + '%', Math.round(thisWeekDemand) + 'h / ' + Math.round(thisWeekSupply) + 'h', loadColor, "kapJumpToCalendar(false)")
      + card('📊', 'Engpässe nächste 4 Wochen', bottlenecks.length + ' Stück', bottlenecks.length ? bottlenecks.slice(0,3).map(function(b){return 'KW' + b.kw + ' ' + b.gewerk;}).join(' · ') : 'keine Überlastung', bottlenecks.length ? '#dc2626' : '#15803d', "kapJumpToCalendar(true)")
      + card('⚠', 'Größte Überlastung', maxOver ? '+' + maxOver.over + 'h' : '—', maxOver ? maxOver.gewerk + ' · KW ' + maxOver.kw : 'alles im grünen Bereich', maxOver ? '#dc2626' : '#15803d', "kapJumpToCalendar(true)");
  }
  window.renderKapaCockpit = renderKapaCockpit;

  // Klick auf Cockpit-Karte → Kalender-Subtab öffnen, optional "Nur Überlast"-Filter
  window.kapJumpToCalendar = function (overloadOnly) {
    // Subtab Kalender aktivieren
    if (typeof window.showKapSub === 'function') {
      var tab = document.querySelector('.kap-subtab[data-sub="kal"]');
      window.showKapSub('kal', tab);
    }
    // Filter setzen
    var btn = document.getElementById(overloadOnly ? 'kal-filter-overload' : 'kal-filter-all');
    if (btn && typeof window.setKalFilter === 'function') {
      window.setKalFilter(overloadOnly ? 'overload' : 'all', btn);
    }
    // Sanft hoch zur Tabelle scrollen
    setTimeout(function(){
      var anchor = document.getElementById('kap-kal-table');
      if (anchor && anchor.scrollIntoView) anchor.scrollIntoView({behavior:'smooth', block:'start'});
    }, 100);
  };

  window.addEmployee = function(){
    employees.push({id:'ma'+Date.now(), name:'Neuer Mitarbeiter', gewerke:[], std:40, von:23, bis:52});
    saveEmployees(employees);
    renderMA(); renderKalender(); renderKapaCockpit();
  };
  window.delEmployee = function(idx){
    if (!confirm('Mitarbeiter "' + employees[idx].name + '" löschen?')) return;
    employees.splice(idx,1);
    saveEmployees(employees);
    renderMA(); renderKalender(); renderKapaCockpit();
  };

  // ── Kalender: Wochen-Auslastung pro Gewerk ─────────────────────────
  // Konsolidiere alten Gewerk-Namen auf den kanonischen Namen
  function gnorm(g) {
    if (!g) return '';
    return (typeof window.mapGewerk === 'function') ? window.mapGewerk(g) : g;
  }
  function getAllGewerke() {
    var set = new Set();
    employees.forEach(function(e){ e.gewerke.forEach(function(g){ var n = gnorm(g); if (n) set.add(n); }); });
    document.querySelectorAll('tr.task-row[data-gewerk]').forEach(function(tr){
      var g = gnorm(tr.getAttribute('data-gewerk'));
      if (g) set.add(g);
    });
    return Array.from(set).sort(function(a,b){
      return a.localeCompare(b, 'de', { sensitivity: 'base' });
    });
  }

  function getKWRange() {
    return { start: 23, end: 104 };  // 2026 KW23 bis 2027 KW52  // KW23 bis KW60
  }

  function tasksByGewerkAndKw() {
    // Map: gewerk → kw → mannstunden_sum
    var map = {};
    document.querySelectorAll('tr.task-row').forEach(function(tr){
      var gewerk = gnorm(tr.getAttribute('data-gewerk') || '');
      if (!gewerk) return;
      var bar = tr.querySelector('.gantt-bar');
      if (!bar || !bar.style.width) return;
      var left = parseInt(bar.style.left,10) || 0;
      var width = parseInt(bar.style.width,10) || 0;
      if (width === 0) return;
      var startKw = ORIGIN_KW + Math.round(left / PX_PER_WEEK);
      var weeks = Math.round(width / PX_PER_WEEK);
      var tid = tr.getAttribute('data-tid');
      var mh = parseInt(localStorage.getItem('task-mh-' + tid) || '40', 10);  // default 40 mh
      var perWeek = mh / Math.max(1, weeks);
      for (var k = 0; k < weeks; k++) {
        var kw = startKw + k;
        if (!map[gewerk]) map[gewerk] = {};
        map[gewerk][kw] = (map[gewerk][kw] || 0) + perWeek;
      }
    });
    return map;
  }

  function isUrlaub(emp, contKw) {
    if (!emp.urlaub || !emp.urlaub.length) return false;
    var d = c2ky(contKw);
    return emp.urlaub.some(function(u){
      var vonC = ky2c(u.vonKw, u.vonYear);
      var bisC = ky2c(u.bisKw, u.bisYear);
      return contKw >= vonC && contKw <= bisC;
    });
  }

  function capacityByGewerkAndKw() {
    var map = {};
    employees.forEach(function(emp){
      emp.gewerke.forEach(function(gw0){
        var gw = gnorm(gw0);
        if (!gw) return;
        if (!map[gw]) map[gw] = {};
        for (var kw = emp.von; kw <= emp.bis; kw++) {
          if (isUrlaub(emp, kw)) continue;  // Skip Urlaubs-Wochen
          var perGewerk = emp.std / Math.max(1, emp.gewerke.length);
          map[gw][kw] = (map[gw][kw] || 0) + perGewerk;
        }
      });
    });
    return map;
  }

  function renderKalender() {
    var thead = document.getElementById('kap-kal-thead');
    var tbody = document.getElementById('kap-kal-tbody');
    if (!thead || !tbody) return;

    var rng = getKWRange();
    var gewerke = getAllGewerke();
    var demand = tasksByGewerkAndKw();
    var supply = capacityByGewerkAndKw();

    // Header
    var head = '<tr style="background:#1e293b;color:#fff">'
      + '<th style="padding:6px 10px;text-align:left;font-size:10px;font-weight:700;position:sticky;left:0;background:#1e293b;z-index:3;min-width:140px">Gewerk</th>';
    for (var kw = rng.start; kw <= rng.end; kw++) {
      var d = kwToDisplay(kw);
      var lbl = 'KW' + d.kw + (d.year === 2026 ? '' : '<br><span style="font-size:8px;opacity:.7">' + d.year + '</span>');
      head += '<th style="padding:5px 4px;font-size:9px;font-weight:600;min-width:45px">' + lbl + '</th>';
    }
    head += '</tr>';
    thead.innerHTML = head;

    // Standard: Gewerke ohne Bedarf UND ohne Kapazität ausblenden (kein Rauschen)
    var visibleGewerke = gewerke.filter(function(gw){
      for (var kk = rng.start; kk <= rng.end; kk++) {
        if (((demand[gw]||{})[kk] || 0) > 0) return true;
        if (((supply[gw]||{})[kk] || 0) > 0) return true;
      }
      return false;
    });
    // "Nur Überlast"-Filter: zusätzlich auf überbuchte Gewerke einschränken
    if (kalFilter === 'overload') {
      visibleGewerke = visibleGewerke.filter(function(gw){
        for (var kk = rng.start; kk <= rng.end; kk++) {
          var dd = (demand[gw]||{})[kk] || 0;
          var ss = (supply[gw]||{})[kk] || 0;
          if (ss > 0 && dd > ss) return true;
        }
        return false;
      });
    }

    // Body
    var body = '';
    visibleGewerke.forEach(function(gw){
      body += '<tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 10px;font-weight:600;font-size:11px;position:sticky;left:0;background:#fff;border-right:1px solid #e2e8f0">' + gw + '</td>';
      for (var kw = rng.start; kw <= rng.end; kw++) {
        var d = (demand[gw] || {})[kw] || 0;
        var s = (supply[gw] || {})[kw] || 0;
        var ratio = s > 0 ? d / s : (d > 0 ? Infinity : 0);
        var col = '#fff';
        if (d === 0 && s === 0) col = '#f9fafb';
        else if (d === 0) col = '#f0fdf4'; // hat Kapazität ohne Bedarf
        else if (ratio > 1) col = '#fee2e2'; // Überlast
        else if (ratio > 0.8) col = '#fef3c7'; // gelb
        else col = '#dcfce7'; // grün

        var text = '';
        if (d > 0 || s > 0) {
          text = Math.round(d) + '/' + Math.round(s);
        }
        var tip = 'KW' + kw + ' · ' + gw + ' · Bedarf ' + d.toFixed(1) + 'h, Kapazität ' + s.toFixed(1) + 'h';
        body += '<td style="padding:3px;text-align:center;font-size:9px;background:' + col + ';border-right:1px solid #f1f5f9" title="' + tip + '">' + text + '</td>';
      }
      body += '</tr>';
    });
    if (!visibleGewerke.length) {
      body = '<tr><td colspan="' + (rng.end - rng.start + 2) + '" style="padding:20px;text-align:center;color:#15803d;font-size:12px">✓ Keine Überlastungen — alle Gewerke im Plan.</td></tr>';
    }
    tbody.innerHTML = body;
  }

  // ── Aufgaben-Zuordnung: Tabelle ────────────────────────────────────
  function renderZuordnung() {
    var tbody = document.getElementById('kap-zu-tbody');
    if (!tbody) return;
    var rows = [];
    document.querySelectorAll('tr.task-row').forEach(function(tr){
      var nameCell = tr.querySelector('.task-name-cell');
      var name = nameCell ? nameCell.textContent.trim() : '?';
      var gewerk = tr.getAttribute('data-gewerk') || '';
      var unit = tr.getAttribute('data-unit') || '';
      var tid = tr.getAttribute('data-tid') || '';
      var bar = tr.querySelector('.gantt-bar');
      if (!bar || !bar.style.width) return;
      var left = parseInt(bar.style.left,10) || 0;
      var width = parseInt(bar.style.width,10) || 0;
      if (width === 0) return;
      var startKw = ORIGIN_KW + Math.round(left / PX_PER_WEEK);
      var weeks = Math.round(width / PX_PER_WEEK);
      var mh = localStorage.getItem('task-mh-' + tid) || '40';
      rows.push({tid, name, unit, gewerk, startKw, weeks, mh});
    });
    rows.sort(function(a,b){return a.startKw - b.startKw;});
    tbody.innerHTML = rows.map(function(r){
      return '<tr style="border-bottom:1px solid #f1f5f9">'
        + '<td style="padding:6px 10px;font-size:11px">' + escapeHtml(r.name) + '</td>'
        + '<td style="padding:6px 10px;font-size:11px;color:#64748b">' + r.unit + '</td>'
        + '<td style="padding:6px 10px;font-size:11px">' + (r.gewerk ? '<span style="background:#dbeafe;color:#2563eb;padding:1px 6px;border-radius:8px;font-size:9px;font-weight:600">' + r.gewerk + '</span>' : '<span style="color:#94a3b8;font-size:10px">—</span>') + '</td>'
        + '<td style="padding:6px 10px;text-align:center;font-size:11px">KW ' + r.startKw + '</td>'
        + '<td style="padding:6px 10px;text-align:center;font-size:11px">' + r.weeks + '</td>'
        + '<td style="padding:6px 10px;text-align:center"><input type="number" min="0" step="1" value="' + r.mh + '" data-tid="' + r.tid + '" class="kap-mh" style="width:70px;text-align:center;border:1px solid #e2e8f0;border-radius:4px;font-size:11px;padding:3px 5px"></td>'
        + '</tr>';
    }).join('') || '<tr><td colspan="6" style="padding:20px;text-align:center;color:#94a3b8;font-size:12px">Keine Aufgaben mit Zeitplan gefunden</td></tr>';

    tbody.querySelectorAll('.kap-mh').forEach(function(inp){
      inp.addEventListener('change', function(){
        var k = 'task-mh-' + this.dataset.tid;
        localStorage.setItem(k, this.value);
        if (window.__syncKV) window.__syncKV(k, this.value);
        renderKalender();
      });
    });
  }

  // ── Sub-Tab Wechsel ─────────────────────────────────────────────────
  window.showKapSub = function(sub, el) {
    document.querySelectorAll('.kap-subtab').forEach(function(t){
      t.classList.remove('active');
      t.style.borderBottom = '';
      t.style.color = '#64748b';
      t.style.fontWeight = '600';
    });
    el.classList.add('active');
    el.style.borderBottom = '2px solid #2563eb';
    el.style.color = '#2563eb';
    el.style.fontWeight = '700';
    document.querySelectorAll('.kap-sub').forEach(function(d){d.style.display='none';});
    document.getElementById('kap-sub-' + sub).style.display = 'block';
    if (sub === 'kal') renderKalender();
    if (sub === 'zu') renderZuordnung();
    if (sub === 'gw') renderKapaGewerke();
  };

  // Gewerke-Übersicht — pro Gewerk eine Karte
  function renderKapaGewerke() {
    var cont = document.getElementById('kap-gw-grid');
    if (!cont) return;
    var demand = tasksByGewerkAndKw();
    var supply = capacityByGewerkAndKw();
    var rng = getKWRange();
    var gewerke = getAllGewerke();
    var gMap = {}; (window.GEWERKE || []).forEach(function(g){ gMap[g.name] = g; });
    var nowKW = (typeof window.dateToContKW === 'function') ? window.dateToContKW(new Date().toISOString().slice(0,10)) : 23;

    cont.innerHTML = gewerke.map(function (gw) {
      var meta = gMap[gw] || { bg: '#f1f5f9', fg: '#475569' };
      // Mitarbeiter dieser Gewerk-Zuordnung
      var emps = employees.filter(function(e){ return (e.gewerke||[]).map(gnorm).indexOf(gw) >= 0; });
      // Aufgaben (Hauptzeitplan)
      var tasks = [];
      document.querySelectorAll('tr.task-row').forEach(function(tr){
        if (gnorm(tr.getAttribute('data-gewerk') || '') !== gw) return;
        var nameEl = tr.querySelector('.task-name-cell');
        if (!nameEl) return;
        var name = (nameEl.textContent || '').replace(/[🕐✕]/g,'').replace(/\s+/g,' ').trim();
        if (name) tasks.push(name);
      });
      // Auslastung this-week + max
      var thisWeekD = (demand[gw]||{})[nowKW] || 0;
      var thisWeekS = (supply[gw]||{})[nowKW] || 0;
      var thisLoad = thisWeekS > 0 ? Math.round(thisWeekD / thisWeekS * 100) : 0;
      var maxLoad = 0;
      for (var kk = rng.start; kk <= rng.end; kk++) {
        var d = (demand[gw]||{})[kk] || 0, s = (supply[gw]||{})[kk] || 0;
        if (s > 0) { var r = Math.round(d/s*100); if (r > maxLoad) maxLoad = r; }
      }
      var loadCol = thisLoad > 100 ? '#dc2626' : thisLoad > 80 ? '#d97706' : '#15803d';
      // Mini-Heatmap (32 KWs ab now)
      var stripCells = '';
      for (var kw = nowKW; kw < nowKW + 32; kw++) {
        var dd = (demand[gw]||{})[kw] || 0, ss = (supply[gw]||{})[kw] || 0;
        var col = '#f1f5f9';
        if (ss > 0 && dd > ss) col = '#dc2626';
        else if (ss > 0 && dd / ss > 0.8) col = '#f59e0b';
        else if (dd > 0) col = '#16a34a';
        var pct2 = ss > 0 ? Math.round(dd/ss*100) : 0;
        stripCells += '<div title="KW ' + kw + ' · ' + Math.round(dd) + 'h / ' + Math.round(ss) + 'h (' + pct2 + '%)" style="flex:1;background:' + col + ';border-right:1px solid #fff"></div>';
      }
      // Mitarbeiter-Chips
      var empChips = emps.length
        ? emps.map(function(e){ return '<span style="display:inline-block;background:' + meta.bg + ';color:' + meta.fg + ';border:1px solid ' + meta.fg + '40;border-radius:8px;padding:2px 8px;font-size:10px;font-weight:700;margin:2px 3px 0 0">' + (e.name || '?') + ' · ' + (e.std || 0) + 'h</span>'; }).join('')
        : '<span style="font-size:10px;color:#cbd5e1;font-style:italic">keine Mitarbeiter zugeordnet</span>';
      // Aufgaben-Anzahl + erste 3
      var taskPreview = tasks.length
        ? '<div style="font-size:10px;color:#64748b;margin-top:4px">' + tasks.length + ' Aufgabe(n)' + (tasks.length > 3 ? ' — z. B.: ' + tasks.slice(0,3).join(' · ') + ' …' : ': ' + tasks.join(' · ')) + '</div>'
        : '<div style="font-size:10px;color:#cbd5e1;margin-top:4px;font-style:italic">keine Aufgaben</div>';

      return '<div style="background:#fff;border:1.5px solid ' + meta.fg + '30;border-radius:12px;padding:12px;box-shadow:0 1px 3px rgba(15,23,42,.04);display:flex;flex-direction:column;gap:8px">'
        // Header
        + '<div style="display:flex;justify-content:space-between;align-items:center">'
          + '<div style="display:flex;align-items:center;gap:8px">'
            + '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + meta.fg + '"></span>'
            + '<span style="font-size:14px;font-weight:800;color:#1e293b">' + gw + '</span>'
          + '</div>'
          + '<span style="background:' + loadCol + '15;color:' + loadCol + ';border:1px solid ' + loadCol + '40;border-radius:8px;padding:3px 9px;font-size:11px;font-weight:800">' + thisLoad + '%</span>'
        + '</div>'
        // Mini-Heatmap
        + '<div>'
          + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Auslastung KW ' + nowKW + '–' + (nowKW+31) + ' · max ' + maxLoad + '%</div>'
          + '<div style="display:flex;height:14px;border-radius:3px;overflow:hidden;border:1px solid #e2e8f0">' + stripCells + '</div>'
        + '</div>'
        // Mitarbeiter
        + '<div>'
          + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">👥 Mitarbeiter</div>'
          + '<div>' + empChips + '</div>'
        + '</div>'
        // Aufgaben
        + '<div>'
          + '<div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px">📋 Aufgaben</div>'
          + taskPreview
        + '</div>'
      + '</div>';
    }).join('');
    if (!cont.innerHTML) cont.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:12px">Noch keine Gewerke mit Daten.</div>';
  }
  window.renderKapaGewerke = renderKapaGewerke;

  // CSV Export
  window.exportKap = function() {
    var lines = ['Name;Gewerke;Std/Woche;Von KW;Bis KW'];
    employees.forEach(function(e){
      lines.push([e.name, e.gewerke.join('|'), e.std, e.von, e.bis].join(';'));
    });
    var blob = new Blob([lines.join('\n')], {type:'text/csv'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Kapazitaet_Mitarbeiter.csv';
    a.click();
  };

  function escapeHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Init on tab show
  document.addEventListener('click', function(e){
    var btn = e.target.closest('[onclick*="showTab"]');
    if (btn && btn.getAttribute('onclick').indexOf('kapazitaet') >= 0) {
      setTimeout(function(){ renderMA(); }, 100);
    }
  });

  // Auch direkt einmal initialisieren
  setTimeout(function(){
    renderMA();
  }, 500);

  // Exporte für generischen KV-Sync (sync2.js → __applyKVUpdate)
  window.renderKalender = renderKalender;
  window.renderMA       = renderMA;
  window.loadEmployees  = loadEmployees;
  // Cascade-Rename: alle Mitarbeiter-Gewerk-Einträge umbenennen
  window.cascadeRenameKapEmployees = function (oldName, newName) {
    var changed = 0;
    employees.forEach(function(e){
      if (!e.gewerke) return;
      for (var i = 0; i < e.gewerke.length; i++) {
        if (e.gewerke[i] === oldName) { e.gewerke[i] = newName; changed++; }
      }
    });
    if (changed > 0) { saveEmployees(employees); renderMA(); renderKalender(); renderKapaCockpit(); }
  };
  // Cascade-Clear: das gelöschte Gewerk aus allen Mitarbeitern entfernen
  window.cascadeClearKapEmployees = function (oldName) {
    var changed = 0;
    employees.forEach(function(e){
      if (!e.gewerke) return;
      var before = e.gewerke.length;
      e.gewerke = e.gewerke.filter(function(g){ return g !== oldName; });
      if (e.gewerke.length !== before) changed++;
    });
    if (changed > 0) { saveEmployees(employees); renderMA(); renderKalender(); renderKapaCockpit(); }
  };

  // Reload nach Remote-Sync (employees ist Closure-Variable → von außen nicht setzbar)
  window.kapReload = function () {
    try {
      employees = loadEmployees();
      renderMA();
      renderKalender();
      renderKapaCockpit();
    } catch (e) {}
  };
})();
</script>


<div id="tab-bestellungen" class="tab-content">
<div style="padding:20px 24px">

<!-- HEADER + FILTER -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
  <div>
    <h1 style="font-size:16px;font-weight:800;color:#1e293b;margin:0">📦 Bestellungen &amp; Material</h1>
    <p style="font-size:11px;color:#64748b;margin:4px 0 0">Alle offenen und laufenden Bestellungen · verknüpft mit TOD-Karten und Verantwortlichen</p>
  </div>
  <button onclick="openAddOrder()" style="padding:7px 14px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">+ Neue Bestellung</button>
</div>

<!-- FILTER-LEISTE -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
  <span style="font-size:11px;font-weight:600;color:#64748b">Verantwortlich:</span>
  <button class="bo-filter active" data-group="verant" data-val="" onclick="setFilter('verant','',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #e2e8f0;background:#f1f5f9;font-size:11px;font-weight:600;cursor:pointer">Alle</button>
  <button class="bo-filter" data-group="verant" data-val="DIB" onclick="setFilter('verant','DIB',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #2563eb40;background:#eff6ff;color:#2563eb;font-size:11px;font-weight:700;cursor:pointer">DIB</button>
  <button class="bo-filter" data-group="verant" data-val="HEG" onclick="setFilter('verant','HEG',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #16a34a40;background:#f0fdf4;color:#16a34a;font-size:11px;font-weight:700;cursor:pointer">HEG</button>
  <button class="bo-filter" data-group="verant" data-val="EGA" onclick="setFilter('verant','EGA',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #d9770640;background:#fffbeb;color:#d97706;font-size:11px;font-weight:700;cursor:pointer">EGA</button>
  <button class="bo-filter" data-group="verant" data-val="Architronik" onclick="setFilter('verant','Architronik',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #7c3aed40;background:#f5f3ff;color:#7c3aed;font-size:11px;font-weight:700;cursor:pointer">Architronik</button>
  <button class="bo-filter" data-group="verant" data-val="offen" onclick="setFilter('verant','offen',this)"
    style="padding:3px 12px;border-radius:20px;border:1.5px solid #94a3b840;background:#f8fafc;color:#64748b;font-size:11px;font-weight:700;cursor:pointer">offen</button>
  <span style="font-size:11px;font-weight:600;color:#64748b;margin-left:8px">Status:</span>
  <span id="bo-status-pills" style="display:contents"></span>
  <span style="font-size:11px;font-weight:600;color:#64748b;margin-left:8px">Gewerk:</span>
  <select id="bo-gewerk-filter" onchange="renderOrders()"
    style="padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;color:#374151">
    <option value="">Alle Gewerke</option>
  </select>

  <span style="font-size:11px;font-weight:600;color:#64748b;margin-left:8px">GH-Bereich:</span>
  <select id="bo-gh-filter" onchange="renderOrders()"
    style="padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;color:#374151">
    <option value="">Alle</option>
    <option value="__gh__">Nur GH-Checkliste</option>
    <option value="Dach HBO">Dach HBO</option>
    <option value="Dach KWS">Dach KWS</option>
    <option value="Dach Brücke">Dach Brücke</option>
    <option value="Dach TH Nord">Dach TH Nord</option>
    <option value="Ostseite">Ostseite</option>
    <option value="4OG Terrasse">4.OG Terrasse</option>
    <option value="Westseite">Westseite</option>
    <option value="TH Nord">TH Nord</option>
    <option value="Südseite">Südseite</option>
    <option value="Nordseite">Nordseite</option>
    <option value="TH Nord 5OG">TH Nord 5.OG</option>
    <option value="Brückenbau">Brückenbau</option>
  </select>
  <input id="bo-search" type="text" placeholder="Suche…" oninput="renderOrders()"
    style="padding:4px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:11px;margin-left:172px;width:140px">
</div>

<!-- SUMMARY BADGES -->
<div id="bo-summary" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px"></div>

<!-- TABELLE -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">
<table style="width:100%;border-collapse:collapse" id="bo-table">
<thead>
  <tr style="background:#f8fafc">
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">ID</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Position</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Gewerk</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Verantwortlich</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Lieferant</th>
    <th style="padding:8px 10px;text-align:center;font-size:10px;color:#94a3b8;font-weight:600">bis KW</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Status</th>
    <th style="padding:8px 10px;text-align:center;font-size:10px;color:#94a3b8;font-weight:600">Lieferung</th>
    <th style="padding:8px 10px;text-align:right;font-size:10px;color:#94a3b8;font-weight:600">Betrag (Netto)</th>
    <th style="padding:8px 10px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Hinweis</th>
    <th style="padding:8px 10px;text-align:center;font-size:10px;color:#94a3b8;font-weight:600">Akt.</th>
  </tr>
</thead>
<tbody id="bo-tbody"></tbody>
<tfoot>
  <tr style="background:#1e293b">
    <td colspan="8" style="padding:8px 10px;font-size:12px;font-weight:700;color:#fff">Gesamt (gefiltert)</td>
    <td id="bo-total" style="padding:8px 10px;text-align:right;font-size:13px;font-weight:800;color:#fbbf24"></td>
    <td colspan="2"></td>
  </tr>
</tfoot>
</table>
</div>

<!-- ADD/EDIT MODAL -->
<div id="bo-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <h3 id="bo-modal-title" style="font-size:15px;font-weight:700;color:#1e293b;margin:0 0 16px">Neue Bestellung</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div style="grid-column:1/-1">
        <label style="font-size:11px;font-weight:600;color:#64748b">Position / Beschreibung *</label>
        <input id="bo-m-name" type="text" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Gewerk</label>
        <select id="bo-m-gewerk" onchange="if(this.value==='__add__'){ var nm=prompt('Neues Gewerk:'); if(nm){ window.addGewerk(nm); this.value=nm; } else { this.value=''; } }" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box"></select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Kategorie</label>
        <select id="bo-m-kat" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
          <option>Material</option><option>Montage</option><option>Miete</option><option>Sonstiges</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Verantwortlich *</label>
        <select id="bo-m-verant" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
          <option>DIB</option><option>HEG</option><option>EGA</option><option>Architronik</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Lieferant</label>
        <input id="bo-m-firma" type="text" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">bis KW (z.B. 28)</label>
        <input id="bo-m-kw" type="number" min="1" max="80" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Lieferung (Datum)</label>
        <input id="bo-m-kw-lief-date" type="date" oninput="(function(v){var k=window.dateToContKW(v);document.getElementById('bo-m-kw-lief-preview').textContent=k?('→ KW '+k):'';})(this.value)" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;font-family:inherit">
        <div id="bo-m-kw-lief-preview" style="margin-top:4px;font-size:10px;color:#15803d;font-weight:700"></div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Status</label>
        <select id="bo-m-status" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
          <option>geplant</option>
          <option>Angebot angefordert</option>
          <option>Angebot erhalten</option>
          <option>Angebot geprüft</option>
          <option>Angebot freigegeben</option>
          <option>AB erhalten</option>
          <option>bestellt</option>
          <option>Lieferung ausstehend</option>
          <option>geliefert</option>
          <option>laufend</option>
          <option>ausstehend</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b">Betrag Netto (€)</label>
        <input id="bo-m-betrag" type="number" min="0" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
      </div>
      <div style="grid-column:1/-1">
        <label style="font-size:11px;font-weight:600;color:#64748b">Hinweis</label>
        <input id="bo-m-hinweis" type="text" style="width:100%;margin-top:3px;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box">
      </div>
      <div style="grid-column:1/-1">
        <label style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:600;color:#64748b;cursor:pointer">
          <input id="bo-m-tod" type="checkbox" style="width:15px;height:15px">
          In TOD-Liste der fälligen KW eintragen (Hinweis in Wochenplanung)
        </label>
      </div>
    </div>
    <input id="bo-m-id" type="hidden">
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
      <button onclick="closeModal()" style="padding:7px 16px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;cursor:pointer;background:#f8fafc">Abbrechen</button>
      <button onclick="saveOrder()" style="padding:7px 16px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">Speichern</button>
    </div>
  </div>
</div>

</div>
</div>

<script id="kosten-bestellungen-engine">
// ════════════ KOSTEN ENGINE ════════════

const COST_SECTIONS = [{"id": "haustechnik", "title": "⚙️ OHG Haustechnik", "color": "#7c3aed", "items": [{"name": "Stromversorgung – Hausanschluss / Abwasser", "betrag": 18000, "verant": "DIB"}, {"name": "NSHV Niederspannungshauptverteilung", "betrag": 32000, "verant": "DIB"}, {"name": "Batteriespeicher (Installation)", "betrag": 8000, "verant": "DIB"}, {"name": "Kaltwassersatz (5,02×2,59m) – Installation", "betrag": 52000, "verant": "EGA"}, {"name": "Sole-Wasser-Wärmepumpe Brigach", "betrag": 58000, "verant": "HEG"}, {"name": "Lüftungsanlagen 2+3 – Shedhalle", "betrag": 44000, "verant": "HEG"}, {"name": "Lüftungskanäle / Heizungsoptimierung", "betrag": 28000, "verant": "HEG"}, {"name": "Lüftungsanlage 4 HBO", "betrag": 35000, "verant": "HEG"}, {"name": "Regen- & Schmutzwasserleitungen", "betrag": 24000, "verant": "HEG"}, {"name": "Aufzüge – Umbau 2 Stück", "betrag": 145000, "verant": "DIB"}, {"name": "Gerüstarbeiten Außen", "betrag": 18000, "verant": "Architronik"}, {"name": "Brandschutz Abschottungen", "betrag": 12000, "verant": "DIB"}]}, {"id": "hochbau", "title": "🏗 WEG Hochbau Ost – Gebäudehülle", "color": "#0f766e", "items": [{"name": "Vakuumdämmung Dach HBO", "betrag": 28800, "verant": "Architronik"}, {"name": "Estrich Ausgleich Dach", "betrag": 15000, "verant": "HEG"}, {"name": "Schweißbahn / Kaltestreifen", "betrag": 2500, "verant": "HEG"}, {"name": "Bodenbelag Terrasse", "betrag": 6500, "verant": "Architronik"}, {"name": "Rinne Erweiterung Terrasse", "betrag": 6000, "verant": "Architronik"}, {"name": "Unterkonstruktion Terrasse (UK)", "betrag": 20000, "verant": "Architronik"}, {"name": "Glasgeländer Terrasse", "betrag": 11107, "verant": "Architronik"}, {"name": "P.R.F Fassade (Glas+Holz+Raico)", "betrag": 3000, "verant": "Architronik"}, {"name": "Dämmung + UK Holz Fassade", "betrag": 2000, "verant": "Architronik"}, {"name": "Fassade Ostseite (Putz+Farbe)", "betrag": 3200, "verant": "Architronik"}, {"name": "Kran Dach TH Nord + Brückenbau", "betrag": 6000, "verant": "Architronik"}, {"name": "Fassade Westseite (Putz+Farbe)", "betrag": 3200, "verant": "Architronik"}]}, {"id": "apt_ph1b", "title": "🏘 Apartments Phase 2 (8 Einh., T 4.01–T 4.08)", "color": "#1e40af", "items": [{"name": "Trockenbau / Innenwände 9 WE", "betrag": 82800, "verant": "HEG"}, {"name": "Elektroinstallation 9 WE", "betrag": 70200, "verant": "DIB"}, {"name": "Sanitärinstallation 9 WE", "betrag": 85500, "verant": "HEG"}, {"name": "Heizung / FBHZ 9 WE", "betrag": 49500, "verant": "HEG"}, {"name": "Estrich inkl. Trocknung 9 WE", "betrag": 41400, "verant": "HEG"}, {"name": "Brandschutz / Abschottungen 9 WE", "betrag": 12600, "verant": "DIB"}, {"name": "Abhangdecken / Unterdecken 9 WE", "betrag": 28800, "verant": "HEG"}, {"name": "Malerarbeiten 9 WE", "betrag": 34200, "verant": "Architronik"}, {"name": "Bodenbelag Vinyl 9 WE", "betrag": 37800, "verant": "Architronik"}, {"name": "Türen montieren 9 WE", "betrag": 52200, "verant": "Architronik"}, {"name": "Blowerdoor-Test + Abnahme 9 WE", "betrag": 8550, "verant": "DIB"}, {"name": "Reserve / Nebenarbeiten 9 WE", "betrag": 18450, "verant": "HEG"}]}, {"id": "apt_ph2", "title": "🏘 Apartments Phase 3/4 (T 4.09 Maisonette · T 4.10–T 4.22 · W 5.3)", "color": "#0f766e", "items": [{"name": "Trockenbau / Innenwände 13 WE + W5.3", "betrag": 130200, "verant": "HEG"}, {"name": "Elektroinstallation 13 WE + W5.3", "betrag": 109200, "verant": "DIB"}, {"name": "Sanitärinstallation 13 WE + W5.3", "betrag": 133000, "verant": "HEG"}, {"name": "Heizung / FBHZ 13 WE + W5.3", "betrag": 77000, "verant": "HEG"}, {"name": "Estrich inkl. Trocknung 13 WE + W5.3", "betrag": 64400, "verant": "HEG"}, {"name": "Brandschutz / Abschottungen 14 Einh.", "betrag": 19600, "verant": "DIB"}, {"name": "Abhangdecken / Unterdecken 14 Einh.", "betrag": 44800, "verant": "HEG"}, {"name": "Malerarbeiten 14 Einh.", "betrag": 53200, "verant": "Architronik"}, {"name": "Bodenbelag Vinyl 14 Einh.", "betrag": 58800, "verant": "Architronik"}, {"name": "Türen montieren 14 Einh.", "betrag": 81200, "verant": "Architronik"}, {"name": "Blowerdoor-Test + Abnahme 14 Einh.", "betrag": 13300, "verant": "DIB"}, {"name": "Reserve / Nebenarbeiten 14 Einh.", "betrag": 29000, "verant": "HEG"}]}, {"id": "wellness", "title": "🏊 Wellnessbereich T 5.1 (ohne Phase, gewerblich)", "color": "#0891b2", "items": [{"name": "Abbrucharbeiten Wellness", "betrag": 12000, "verant": "EGA"}, {"name": "Trockenbau / Decken Wellness", "betrag": 38000, "verant": "HEG"}, {"name": "Heizung / FBHZ Wellness", "betrag": 20000, "verant": "HEG"}, {"name": "Estrich Wellnessbereich", "betrag": 9500, "verant": "HEG"}, {"name": "Abdichtung Nassbereich", "betrag": 14000, "verant": "HEG"}, {"name": "Fliesenarbeiten ca. 180m²", "betrag": 32000, "verant": "HEG"}, {"name": "Türen + Glaswände Wellness", "betrag": 26000, "verant": "Architronik"}, {"name": "Malerarbeiten Wellness", "betrag": 9000, "verant": "Architronik"}, {"name": "Sanitär Wellness (Endmontage)", "betrag": 28000, "verant": "HEG"}, {"name": "Elektro / Beleuchtung Wellness", "betrag": 18000, "verant": "DIB"}, {"name": "Lüftung / Entfeuchtung Wellness", "betrag": 15000, "verant": "HEG"}, {"name": "Reserve Wellness", "betrag": 12000, "verant": "HEG"}]}, {"id": "hauptwerk", "title": "🏢 HAUPTWERK / Sonderbereiche", "color": "#374151", "items": [{"name": "Abdichtung Heizmittelraum", "betrag": 14000, "verant": "HEG"}, {"name": "Lamellenfenster / RWA Treppenhaus", "betrag": 28000, "verant": "Architronik"}, {"name": "ICF-Kidsräume Fertigstellung 3.OG", "betrag": 52000, "verant": "DIB"}, {"name": "Sanitärbereiche 2.OG Erneuerung", "betrag": 22000, "verant": "HEG"}, {"name": "Sanitärbereiche 3.OG Ausbau", "betrag": 16000, "verant": "HEG"}, {"name": "Lager / Nebenräume Fertigstellung", "betrag": 18000, "verant": "HEG"}, {"name": "Büroumzug / Neugestaltung", "betrag": 35000, "verant": "DIB"}, {"name": "Reserve Hauptwerk", "betrag": 22000, "verant": "HEG"}]}];

const VERANT_COLORS = {"DIB": "#2563eb", "HEG": "#16a34a", "EGA": "#d97706", "Architronik": "#7c3aed"};
const STATUS_COLORS = {"geliefert": ["#dcfce7", "#15803d"], "bestellt": ["#dbeafe", "#2563eb"], "laufend": ["#fef3c7", "#b45309"], "geplant": ["#f1f5f9", "#64748b"], "ausstehend": ["#fee2e2", "#dc2626"]};

// Kosten aus localStorage oder Defaults
function getCostValues() {
  var saved = JSON.parse(localStorage.getItem('cost-values') || '{}');
  return saved;
}
function saveCostValues() {
  var vals = {};
  document.querySelectorAll('input[data-section]').forEach(function(inp) {
    vals[inp.id] = parseFloat(inp.value) || 0;
  });
  var json = JSON.stringify(vals);
  localStorage.setItem('cost-values', json);
  if (window.__syncKV) window.__syncKV('cost-values', json);
}
function loadCostValues() {
  var saved = getCostValues();
  Object.keys(saved).forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = saved[id];
  });
  COST_SECTIONS.forEach(function(sec) { updateCostSection(sec.id); });
}

// Positionen-Namen in den Budget-Tabellen editierbar machen + Overrides anwenden
window.makeBudgetPositionsEditable = function () {
  document.querySelectorAll('#tab-kosten div[id^="block-"] tbody tr').forEach(function (tr) {
    var nameCell = tr.children[0];
    var input = tr.querySelector('input[data-section]');
    if (!nameCell || !input) return;
    var posId = input.id;
    if (!posId) return;
    var key = 'cost-name-' + posId;
    // Override anwenden
    var saved = localStorage.getItem(key);
    if (saved !== null && saved !== nameCell.textContent.trim()) {
      nameCell.textContent = saved;
    }
    if (nameCell.dataset.editInit === '1') return;
    nameCell.dataset.editInit = '1';
    nameCell.contentEditable = 'true';
    nameCell.style.cursor = 'text';
    nameCell.style.outline = 'none';
    nameCell.addEventListener('focus', function(){ this.style.background = '#eff6ff'; });
    nameCell.addEventListener('blur', function(){
      this.style.background = '';
      var v = (this.textContent || '').replace(/\s+/g,' ').trim();
      if (!v) return;
      localStorage.setItem(key, v);
      if (window.__syncKV) window.__syncKV(key, v);
    });
    nameCell.addEventListener('keydown', function(e){
      if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
    });
  });
};
// Eingehende Override für eine Position übernehmen
window.__applyBudgetPositionName = function (posId, name) {
  if (!posId) return;
  var input = document.getElementById(posId);
  if (!input) return;
  var tr = input.closest('tr');
  if (!tr) return;
  var nameCell = tr.children[0];
  if (nameCell && nameCell.textContent.trim() !== name) nameCell.textContent = name;
};
function resetCostDefaults() {
  if (!confirm('Alle Kosten auf Standardwerte zurücksetzen?')) return;
  localStorage.removeItem('cost-values');
  document.querySelectorAll('input[data-section]').forEach(function(inp) {
    inp.value = inp.dataset.default;
  });
  COST_SECTIONS.forEach(function(sec) { updateCostSection(sec.id); });
}
function updateCostSection(sid) {
  var total = 0;
  document.querySelectorAll('input[data-section="' + sid + '"]').forEach(function(inp) {
    total += parseFloat(inp.value) || 0;
  });
  var el1 = document.getElementById('total-' + sid);
  var el2 = document.getElementById('subtotal-' + sid);
  var fmt = function(n) { return n.toLocaleString('de-DE', {minimumFractionDigits:0}) + ' €'; };
  if (el1) el1.textContent = fmt(total);
  if (el2) el2.textContent = fmt(total);
  saveCostValues();
  updateCostSummary();
}

function getAllSectionTotals() {
  var totals = {};
  COST_SECTIONS.forEach(function(sec) {
    var t = 0;
    document.querySelectorAll('input[data-section="' + sec.id + '"]').forEach(function(inp) {
      t += parseFloat(inp.value) || 0;
    });
    totals[sec.id] = t;
  });
  return totals;
}

function updateCostSummary() {
  var fmt = function(n) { return n.toLocaleString('de-DE') + ' €'; };
  var totals = getAllSectionTotals();
  var sectionsSum = Object.values(totals).reduce(function(a,b) { return a+b; }, 0);
  // Bestellungen-Summe (zusätzlich zu den Sektions-Schätzungen)
  var ordersTotal = 0, ordersConfirmed = 0;
  (window.boOrders || []).forEach(function(o){
    var v = o.betrag || 0;
    ordersTotal += v;
    if (window.BO_CONFIRMED_STATES && window.BO_CONFIRMED_STATES.indexOf(o.status) !== -1) ordersConfirmed += v;
  });
  // Eigene Budget-Positionen mit aufnehmen
  var customTotal = 0;
  (window.budgetCustom || []).forEach(function(it){ customTotal += (it.betrag || 0); });
  var netto = sectionsSum + ordersTotal + customTotal;
  var reserve = Math.round(netto * 0.08);
  var mwst = Math.round((netto + reserve) * 0.19);
  var brutto = netto + reserve + mwst;

  // Summary Cards
  var cards = document.getElementById('cost-summary-cards');
  if (cards) {
    var cardDefs = [
      ['💶','Netto Gesamt',fmt(netto),'#1e40af','ohne MwSt. + Reserve'],
      ['🛡','+ Reserve 8%',fmt(reserve),'#7c3aed','Unvorhergesehenes'],
      ['📊','+ MwSt. 19%',fmt(mwst),'#0891b2','auf Netto+Reserve'],
      ['🏦','≈ Brutto Gesamt',fmt(brutto),'#dc2626','Gesamtinvestition'],
    ];
    cards.innerHTML = cardDefs.map(function(c) {
      return '<div style="background:#fff;border:1.5px solid ' + c[3] + '30;border-radius:10px;padding:12px 16px;flex:1;min-width:130px">'
        + '<div style="font-size:18px">' + c[0] + '</div>'
        + '<div style="font-size:17px;font-weight:800;color:' + c[3] + '">' + c[2] + '</div>'
        + '<div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-top:2px">' + c[1] + '</div>'
        + '<div style="font-size:10px;color:#94a3b8">' + c[4] + '</div></div>';
    }).join('');
  }

  // Gesamtrechnung
  var tbody = document.getElementById('cost-totals-body');
  if (tbody) {
    var rows = COST_SECTIONS.map(function(sec) {
      var t = totals[sec.id] || 0;
      return '<tr style="border-bottom:1px solid #f1f5f9">'
        + '<td style="padding:6px 10px;font-size:11px;color:#1e293b">' + sec.title + '</td>'
        + '<td style="padding:6px 10px;text-align:right;font-weight:600;color:#2563eb;font-size:11px">' + fmt(t) + '</td>'
        + '</tr>';
    }).join('');
    if (ordersTotal > 0) {
      rows += '<tr style="background:#f0fdf4"><td style="padding:6px 10px;font-size:11px;color:#15803d">+ 📦 Bestellungen (' + fmt(ordersConfirmed) + ' verbindlich)</td>'
        + '<td style="padding:6px 10px;text-align:right;font-size:11px;color:#15803d;font-weight:700">' + fmt(ordersTotal) + '</td></tr>';
    }
    if (customTotal > 0) {
      rows += '<tr style="background:#eff6ff"><td style="padding:6px 10px;font-size:11px;color:#1e40af">+ 📝 Eigene Positionen</td>'
        + '<td style="padding:6px 10px;text-align:right;font-size:11px;color:#1e40af;font-weight:700">' + fmt(customTotal) + '</td></tr>';
    }
    rows += '<tr style="background:#f8fafc"><td style="padding:7px 10px;font-weight:700;font-size:12px;color:#1e293b">Netto-Summe (inkl. Bestellungen)</td>'
      + '<td style="padding:7px 10px;text-align:right;font-weight:700;font-size:12px;color:#2563eb">' + fmt(netto) + '</td></tr>';
    rows += '<tr><td style="padding:6px 10px;font-size:11px;color:#7c3aed">+ Reserve 8%</td>'
      + '<td style="padding:6px 10px;text-align:right;font-size:11px;color:#7c3aed">' + fmt(reserve) + '</td></tr>';
    rows += '<tr><td style="padding:6px 10px;font-size:11px;color:#0891b2">+ MwSt. 19%</td>'
      + '<td style="padding:6px 10px;text-align:right;font-size:11px;color:#0891b2">' + fmt(mwst) + '</td></tr>';
    rows += '<tr style="background:#1e293b"><td style="padding:8px 10px;font-size:13px;font-weight:800;color:#fff">≈ GESAMT BRUTTO</td>'
      + '<td style="padding:8px 10px;text-align:right;font-size:14px;font-weight:800;color:#fbbf24">' + fmt(brutto) + '</td></tr>';
    tbody.innerHTML = rows;
  }

  // Plausibilitätsprüfung
  updatePlausibility(netto, totals);
}

function updatePlausibility(netto, totals) {
  var checks = [
    ['Haustechnik/m² BGF (ca. 2.800m²)', Math.round((totals.haustechnik||0)/2800), '150–200', function(v){return v>=130&&v<=220;}, '€/m²'],
    ['Apartments Ausbau/m² (831m²)', Math.round((((totals.apt_ph1b||0)+(totals.apt_ph2||0))/831)), '1.000–1.600', function(v){return v>=800&&v<=1800;}, '€/m²'],
    ['Wellness/m² (ca. 300m²)', Math.round((totals.wellness||0)/300), '600–900', function(v){return v>=400&&v<=1100;}, '€/m²'],
    ['Anteil Hochbau/Gesamt', (netto>0?Math.round((totals.hochbau||0)/netto*100):0), '3–6%', function(v){return v>=2&&v<=9;}, '%'],
  ];
  var body = document.getElementById('cost-plaus-body');
  if (!body) return;
  body.innerHTML = '<table style="width:100%;border-collapse:collapse">'
    + '<thead><tr style="background:#f8fafc"><th style="padding:5px 10px;text-align:left;font-size:10px;color:#94a3b8">Kennzahl</th>'
    + '<th style="padding:5px 10px;text-align:center;font-size:10px;color:#94a3b8">Aktuell</th>'
    + '<th style="padding:5px 10px;text-align:left;font-size:10px;color:#94a3b8">Benchmark</th>'
    + '<th style="padding:5px 10px;text-align:center;font-size:10px;color:#94a3b8">Bewertung</th></tr></thead><tbody>'
    + checks.map(function(c) {
      var ok = c[3](c[1]);
      var col = ok ? '#15803d' : '#dc2626';
      var bg  = ok ? '#dcfce7' : '#fee2e2';
      return '<tr style="border-bottom:1px solid #f1f5f9">'
        + '<td style="padding:5px 10px;font-size:11px;color:#1e293b">' + c[0] + '</td>'
        + '<td style="padding:5px 10px;text-align:center;font-size:11px;font-weight:700;color:' + col + '">' + c[1] + ' ' + c[4] + '</td>'
        + '<td style="padding:5px 10px;font-size:10px;color:#64748b">' + c[2] + ' ' + c[4] + '</td>'
        + '<td style="padding:5px 10px;text-align:center"><span style="background:' + bg + ';color:' + col + ';border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700">'
        + (ok ? '✓ Plausibel' : '⚠ Prüfen') + '</span></td></tr>';
    }).join('') + '</tbody></table>';
}

function exportCostCSV() {
  var lines = ['"Bereich","Position","Betrag (Netto €)"'];
  COST_SECTIONS.forEach(function(sec) {
    var i = 0;
    document.querySelectorAll('input[data-section="' + sec.id + '"]').forEach(function(inp) {
      var name = sec.items[i] ? sec.items[i].name : '—';
      lines.push('"' + sec.title.replace(/"/g,'') + '","' + name + '",' + (parseFloat(inp.value)||0));
      i++;
    });
  });
  var blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8;'});
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'Budgetplanung_Archivita.csv';
  a.click();
}

// ════════════ EIGENE BUDGET-POSITIONEN ════════════
window.budgetCustom = JSON.parse(localStorage.getItem('budget-custom-v1') || '[]');

function saveBudgetCustom() {
  var json = JSON.stringify(window.budgetCustom);
  localStorage.setItem('budget-custom-v1', json);
  if (window.__syncKV) window.__syncKV('budget-custom-v1', json);
}

window.addBudgetCustom = function () {
  window.budgetCustom.push({
    id: 'bc' + Date.now(),
    name: 'Neue Position',
    gewerk: '',
    verantwortlicher: 'offen',
    betrag: 0,
    hinweis: ''
  });
  saveBudgetCustom();
  renderBudgetCustom();
  if (typeof updateCostSummary === 'function') updateCostSummary();
};

window.updateBudgetCustom = function (id, field, value) {
  var idx = window.budgetCustom.findIndex(function(x){ return x.id === id; });
  if (idx < 0) return;
  if (field === 'betrag') value = parseFloat(value) || 0;
  window.budgetCustom[idx][field] = value;
  saveBudgetCustom();
  // Nur Gesamtsumme aktualisieren, kein Re-Render (sonst verliert Input den Fokus)
  if (typeof updateCostSummary === 'function') updateCostSummary();
};

window.deleteBudgetCustom = function (id) {
  var idx = window.budgetCustom.findIndex(function(x){ return x.id === id; });
  if (idx < 0) return;
  if (!confirm('Position "' + (window.budgetCustom[idx].name || '?') + '" löschen?')) return;
  window.budgetCustom.splice(idx, 1);
  saveBudgetCustom();
  renderBudgetCustom();
  if (typeof updateCostSummary === 'function') updateCostSummary();
};

window.renderBudgetCustom = function () {
  var body = document.getElementById('budget-custom-body');
  if (!body) return;
  var items = window.budgetCustom || [];
  if (!items.length) {
    body.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:12px">Noch keine eigenen Positionen. Klick auf "+ Position".</div>';
    return;
  }
  // Nach Gewerk gruppieren
  var groups = {};
  items.forEach(function(it){
    var g = it.gewerk || '— Ohne Gewerk —';
    if (!groups[g]) groups[g] = { sum: 0, items: [] };
    groups[g].sum += it.betrag || 0;
    groups[g].items.push(it);
  });
  var gMap = {}; (window.GEWERKE || []).forEach(function(g){ gMap[g.name] = g; });
  var gewerkOpts = '<option value="">— Ohne Gewerk —</option>'
    + (window.GEWERKE || []).map(function(g){ return '<option value="' + g.name + '">' + g.name + '</option>'; }).join('');
  var verantOpts = ['offen','DIB','HEG','EGA','Architronik'].map(function(v){ return '<option value="' + v + '">' + v + '</option>'; }).join('');
  var fmtE = function(n){ return (n||0).toLocaleString('de-DE') + ' €'; };

  var html = '';
  Object.keys(groups).sort(function(a,b){ return a.localeCompare(b, 'de'); }).forEach(function(g){
    var gMeta = gMap[g] || { bg:'#f1f5f9', fg:'#64748b' };
    html += '<div style="border-bottom:1px solid #f1f5f9">'
      + '<div style="padding:8px 16px;background:#fafafa;display:flex;align-items:center;justify-content:space-between">'
      +   '<span style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:#1e293b">'
      +     '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + gMeta.fg + '"></span>' + g
      +   '</span>'
      +   '<span style="font-size:11px;color:#1e40af;font-weight:700">' + fmtE(groups[g].sum) + '</span>'
      + '</div>'
      + '<table style="width:100%;border-collapse:collapse;font-size:11px">'
      +   '<thead><tr style="background:#fff">'
      +     '<th style="padding:5px 16px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Position</th>'
      +     '<th style="padding:5px 8px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Gewerk</th>'
      +     '<th style="padding:5px 8px;text-align:left;font-size:10px;color:#94a3b8;font-weight:600">Hinweis</th>'
      +     '<th style="padding:5px 16px;text-align:right;font-size:10px;color:#94a3b8;font-weight:600">Betrag</th>'
      +     '<th style="padding:5px 8px;width:36px"></th>'
      +   '</tr></thead><tbody>';
    groups[g].items.forEach(function(it){
      html += '<tr style="border-top:1px solid #f8fafc">'
        + '<td style="padding:5px 16px"><input value="' + (it.name||'').replace(/"/g,'&quot;') + '" oninput="window.updateBudgetCustom(\'' + it.id + '\',\'name\',this.value)" placeholder="Position" style="width:100%;border:none;background:transparent;font-size:11px;font-weight:600;color:#1e293b;outline:none;padding:2px 4px;border-bottom:1px dashed transparent" onfocus="this.style.borderBottomColor=\'#cbd5e1\'" onblur="this.style.borderBottomColor=\'transparent\'"></td>'
        + '<td style="padding:5px 8px"><select onchange="window.updateBudgetCustom(\'' + it.id + '\',\'gewerk\',this.value); setTimeout(window.renderBudgetCustom,30)" style="width:130px;border:1px solid #e2e8f0;border-radius:4px;padding:3px 5px;font-size:11px">' + gewerkOpts.replace('value="' + (it.gewerk||'') + '"', 'value="' + (it.gewerk||'') + '" selected') + '</select></td>'
        + '<td style="padding:5px 8px"><input value="' + (it.hinweis||'').replace(/"/g,'&quot;') + '" oninput="window.updateBudgetCustom(\'' + it.id + '\',\'hinweis\',this.value)" placeholder="Hinweis" style="width:100%;border:none;background:transparent;font-size:10px;color:#64748b;outline:none;padding:2px 4px"></td>'
        + '<td style="padding:5px 16px;text-align:right"><input type="number" min="0" step="100" value="' + (it.betrag||0) + '" oninput="window.updateBudgetCustom(\'' + it.id + '\',\'betrag\',this.value)" style="width:110px;text-align:right;border:1.5px solid #e2e8f0;border-radius:5px;padding:3px 6px;font-size:11px;font-weight:600;color:#2563eb"></td>'
        + '<td style="padding:5px 8px;text-align:center"><button onclick="window.deleteBudgetCustom(\'' + it.id + '\')" title="Löschen" style="background:#fee2e2;color:#dc2626;border:none;border-radius:4px;padding:3px 7px;cursor:pointer;font-size:10px">🗑</button></td>'
      + '</tr>';
    });
    html += '</tbody></table></div>';
  });
  body.innerHTML = html;
};

// ════════════ BESTELLUNGEN ENGINE ════════════

// Status-Liste in Workflow-Reihenfolge (Angebot → Auftrag → Bestellung → Lieferung)
window.BO_STATUS_LIST = [
  { v: 'geplant',              bg: '#f1f5f9', fg: '#64748b' },
  { v: 'Angebot angefordert',  bg: '#fefce8', fg: '#a16207' },
  { v: 'Angebot erhalten',     bg: '#fef3c7', fg: '#b45309' },
  { v: 'Angebot geprüft',      bg: '#e0e7ff', fg: '#4338ca' },
  { v: 'Angebot freigegeben',  bg: '#dbeafe', fg: '#1d4ed8' },
  { v: 'AB erhalten',          bg: '#ede9fe', fg: '#6d28d9' },
  { v: 'bestellt',             bg: '#dbeafe', fg: '#2563eb' },
  { v: 'Lieferung ausstehend', bg: '#fed7aa', fg: '#ea580c' },
  { v: 'geliefert',            bg: '#dcfce7', fg: '#15803d' },
  { v: 'laufend',              bg: '#fef3c7', fg: '#b45309' },
  { v: 'ausstehend',           bg: '#fee2e2', fg: '#dc2626' },
];
// Auswahlliste Verantwortliche (frei erweiterbar)
window.BO_VERANT_LIST = [
  { v: 'offen',       bg: '#94a3b8' },
  { v: 'DIB',         bg: '#2563eb' },
  { v: 'HEG',         bg: '#16a34a' },
  { v: 'EGA',         bg: '#d97706' },
  { v: 'Architronik', bg: '#7c3aed' },
];

// Datum (YYYY-MM-DD) → fortlaufende KW relativ zum Projektstart 2026
window.dateToContKW = function (dateStr) {
  if (!dateStr) return null;
  var d = new Date(dateStr + 'T12:00:00');
  if (isNaN(d.getTime())) return null;
  var target = new Date(d.valueOf());
  var dayNr = (d.getDay() + 6) % 7;
  target.setDate(target.getDate() - dayNr + 3);
  var jan4 = new Date(target.getFullYear(), 0, 4);
  var dayDiff = (target - jan4) / 86400000;
  var iso = 1 + Math.ceil(dayDiff / 7);
  return iso + (target.getFullYear() - 2026) * 52;
};

// Order-Status → Hauptzeitplan-Task-Status spiegeln (Keyword-Match wie umgekehrte Richtung)
window.syncOrderToTasks = function (o) {
  if (!o || !o.name) return;
  var s = o.status;
  var taskStatus = null;
  if (s === 'geliefert') taskStatus = 'abgeschlossen';
  else if (s === 'laufend' || s === 'bestellt' || s === 'AB erhalten' || s === 'Angebot freigegeben') taskStatus = 'laufend';
  else if (s === 'ausstehend' || s === 'Lieferung ausstehend') taskStatus = 'verzögert';
  if (!taskStatus) return;  // geplant/Angebot* → Hauptplan unverändert lassen
  var keyWords = o.name.toLowerCase().split(/[\s\(\)\-–_,/]+/).filter(function(w){ return w.length > 4; });
  if (keyWords.length < 1) return;
  var rows = document.querySelectorAll('tr.task-row');
  var hits = 0;
  rows.forEach(function (tr) {
    var nameCell = tr.querySelector('.task-name-cell');
    if (!nameCell) return;
    var name = (nameCell.textContent || '').toLowerCase();
    var mc = keyWords.filter(function(w){ return name.indexOf(w) >= 0; }).length;
    if (mc < 2) return;
    if (tr.getAttribute('data-status') === taskStatus) return;
    // Setzt data-status → MutationObserver in changes.js pusht über PlanSync
    tr.setAttribute('data-status', taskStatus);
    hits++;
  });
  if (hits > 0) console.log('Order→Hauptplan: ' + hits + ' Aufgabe(n) → ' + taskStatus);
};

// Inline-Edit einer Bestellung — schreibt + synct + re-rendert
window.setOrderField = function (id, key, value) {
  var idx = boOrders.findIndex(function (x) { return x.id === id; });
  if (idx < 0) return;
  if (key === 'kw' || key === 'kw_lief') value = value === '' ? null : parseInt(value, 10);
  if (key === 'betrag') value = parseFloat(value) || 0;
  // Bei Lieferdatum auch die KW automatisch ableiten
  if (key === 'kw_lief_date') {
    boOrders[idx].kw_lief_date = value || null;
    boOrders[idx].kw_lief = value ? window.dateToContKW(value) : null;
  } else {
    boOrders[idx][key] = value;
  }
  var json = JSON.stringify(boOrders);
  localStorage.setItem('bo-orders-v3', json);
  if (window.__syncKV) window.__syncKV('bo-orders-v3', json);
  if (typeof renderOrders === 'function') renderOrders();
  if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
  // Status-Änderung an passende Hauptplan-Aufgaben weitergeben
  if (key === 'status' && typeof window.syncOrderToTasks === 'function') {
    window.syncOrderToTasks(boOrders[idx]);
  }
};

var boOrders = JSON.parse(localStorage.getItem('bo-orders-v3') || 'null');
if (!boOrders) {
  boOrders = [{"id":"B001","name":"NSHV Niederspannungshauptverteilung","gewerk":"Elektro","kategorie":"Material","verantwortlicher":"offen","firma":"DIB Elektro","kw":27,"status":"bestellt","betrag":32000,"hinweis":"Lieferung KW27 – Einbau danach"},{"id":"B002","name":"Batteriespeicher – Einbau","gewerk":"Elektro","kategorie":"Montage","verantwortlicher":"offen","firma":"DIB Elektro","kw":25,"status":"geliefert","betrag":8000,"hinweis":"Ist da, Einbau planen"},{"id":"B003","name":"Kaltwassersatz Installation Restarbeiten","gewerk":"Klima","kategorie":"Montage","verantwortlicher":"offen","firma":"extern","kw":26,"status":"laufend","betrag":12000,"hinweis":"75% fertig"},{"id":"B004","name":"Sole-Wasser-Wärmepumpe Brigach","gewerk":"Heizung","kategorie":"Material","verantwortlicher":"offen","firma":"Heizungsbauer","kw":29,"status":"ausstehend","betrag":58000,"hinweis":"Verzögert – Angebot einholen"},{"id":"B005","name":"Lüftungsanlagen 2+3 Shedhalle – Lieferung","gewerk":"Lüftung","kategorie":"Material","verantwortlicher":"offen","firma":"TGA-Fachbetrieb","kw":28,"status":"ausstehend","betrag":44000,"hinweis":"Beginnt nach Lieferung"},{"id":"B006","name":"Lüftungsanlage 4 HBO – Einhausung","gewerk":"Lüftung","kategorie":"Montage","verantwortlicher":"offen","firma":"TGA-Fachbetrieb","kw":30,"status":"geplant","betrag":35000,"hinweis":"Einhausung Wände+Dach offen"},{"id":"B007","name":"Vakuumdämmung Dach HBO","gewerk":"Dämmung","kategorie":"Material","verantwortlicher":"offen","firma":"Spezialist","kw":51,"status":"geliefert","betrag":28800,"hinweis":"Bestellt KW46 / geliefert KW51"},{"id":"B008","name":"Aufzüge – Demontage + Neubau Kabinen (2 Stk.)","gewerk":"Aufzug","kategorie":"Montage","verantwortlicher":"offen","firma":"CBS Aufzüge","kw":30,"status":"geplant","betrag":145000,"hinweis":"Schachtentrauchung inkl."},{"id":"B009","name":"Regen- Schmutzwasserleitungen Tiefbau","gewerk":"Sanitär","kategorie":"Montage","verantwortlicher":"offen","firma":"Sanitärfirma","kw":26,"status":"ausstehend","betrag":24000,"hinweis":"Priorität – sofort vergeben"},{"id":"B010","name":"Gerüst Haushahn Außen (2. Einsatz)","gewerk":"Gerüst","kategorie":"Montage","verantwortlicher":"offen","firma":"Haushahn","kw":28,"status":"geplant","betrag":18000,"hinweis":"Terminabstimmung ausstehend"},{"id":"B011","name":"Estrich Ausgleich Dach","gewerk":"Estrich","kategorie":"Montage","verantwortlicher":"offen","firma":"Chini","kw":29,"status":"ausstehend","betrag":15000,"hinweis":"Termin offen – nach UK"},{"id":"B012","name":"Glasgeländer Terrasse","gewerk":"Schlosser","kategorie":"Material","verantwortlicher":"offen","firma":"Schlosserei","kw":32,"status":"ausstehend","betrag":11107,"hinweis":"Maße erst nach Estrich"},{"id":"B013","name":"Unterkonstruktion Terrasse 4.OG","gewerk":"Metall","kategorie":"Material","verantwortlicher":"offen","firma":"Stahlbau","kw":31,"status":"ausstehend","betrag":20000,"hinweis":"Planung läuft"},{"id":"B014","name":"Flüssigkunststoff Dach Kaltwassersatz","gewerk":"Abdichtung","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":27,"status":"geliefert","betrag":3500,"hinweis":"Vorhanden laut Checkliste"},{"id":"B015","name":"Brandschutz Abschottungen (Allgemein)","gewerk":"Brandschutz","kategorie":"Montage","verantwortlicher":"offen","firma":"Brandschutz-Fachbetrieb","kw":30,"status":"geplant","betrag":12000,"hinweis":"Planung noch offen"},{"id":"B016","name":"Trockenbau Phase 2 (9 WE)","gewerk":"Trockenbau","kategorie":"Montage","verantwortlicher":"offen","firma":"Trockenbauer","kw":28,"status":"geplant","betrag":82800,"hinweis":"9 WE × 9.200 €"},{"id":"B017","name":"Elektro Phase 2 (9 WE) 1.Fix","gewerk":"Elektro","kategorie":"Montage","verantwortlicher":"offen","firma":"Elektrofirma","kw":28,"status":"geplant","betrag":70200,"hinweis":"9 WE × 7.800 €"},{"id":"B018","name":"Sanitär Phase 2 (9 WE) 1.Fix","gewerk":"Sanitär","kategorie":"Montage","verantwortlicher":"offen","firma":"Sanitärfirma","kw":28,"status":"geplant","betrag":85500,"hinweis":"9 WE × 9.500 €"},{"id":"B019","name":"FBHZ Rohrleitungen Phase 2 (9 WE)","gewerk":"Heizung","kategorie":"Montage","verantwortlicher":"offen","firma":"Heizungsbauer","kw":28,"status":"geplant","betrag":49500,"hinweis":"9 WE × 5.500 €"},{"id":"B020","name":"Estrich Phase 2 (9 WE)","gewerk":"Estrich","kategorie":"Montage","verantwortlicher":"offen","firma":"Chini","kw":30,"status":"geplant","betrag":41400,"hinweis":"9 WE × 4.600 € – KW30 start"},{"id":"B021","name":"Bodenbelag Vinyl Phase 2 (9 WE)","gewerk":"Bodenbelag","kategorie":"Material","verantwortlicher":"offen","firma":"Bodenbelag-Firma","kw":34,"status":"geplant","betrag":37800,"hinweis":"9 WE × 4.200 €"},{"id":"B022","name":"Türen + Zargen Phase 2 (9 WE)","gewerk":"Schreiner","kategorie":"Material","verantwortlicher":"offen","firma":"Schreinerei","kw":34,"status":"geplant","betrag":52200,"hinweis":"9 WE × 5.800 € – Maße nach Trockenbau"},{"id":"B023","name":"Fensterbänke Außen HBO","gewerk":"Schlosser","kategorie":"Material","verantwortlicher":"offen","firma":"Schlosserei","kw":27,"status":"ausstehend","betrag":6000,"hinweis":"Bestellen – Material prüfen"},{"id":"B024","name":"Kran Dach TH Nord + Brückenbau","gewerk":"Gerüst","kategorie":"Miete","verantwortlicher":"offen","firma":"Haushahn","kw":29,"status":"geplant","betrag":6000,"hinweis":"Termin koordinieren"},{"id":"B025","name":"Fliesen Wellnessbereich T5.1","gewerk":"Fliesen","kategorie":"Material","verantwortlicher":"offen","firma":"Fliesenfirma","kw":33,"status":"geplant","betrag":32000,"hinweis":"Großformat – früh bestellen"},{"id":"B026","name":"Glaswände Wellnessbereich","gewerk":"Glas","kategorie":"Material","verantwortlicher":"offen","firma":"Glaserei","kw":35,"status":"geplant","betrag":22000,"hinweis":"Maße nach Trockenbau"},{"id":"B027","name":"PV-Anlage Dach HBO","gewerk":"Elektro","kategorie":"Material","verantwortlicher":"offen","firma":"Elektro / Solar","kw":35,"status":"geplant","betrag":45000,"hinweis":"Termin nach Dacharbeiten"},{"id":"B028","name":"Blitzschutz Dach HBO","gewerk":"Elektro","kategorie":"Montage","verantwortlicher":"offen","firma":"Blitzschutz-Süd","kw":35,"status":"geplant","betrag":8000,"hinweis":"Firma Blitzableiterbau Süd"},{"id":"B029","name":"Regenrohre Brückenbau","gewerk":"Sanitär","kategorie":"Material","verantwortlicher":"offen","firma":"Staiger","kw":31,"status":"ausstehend","betrag":4500,"hinweis":"Bestellen – fehlt laut Checkliste"},{"id":"B030","name":"ICF Kidsräume Materialpaket","gewerk":"Trockenbau","kategorie":"Material","verantwortlicher":"offen","firma":"Lieferant","kw":30,"status":"geplant","betrag":18000,"hinweis":"Vollständiger Ausbau 3.OG"},{"id":"GH001","name":"Rinnen Ostseite – Dach HBO","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":1800,"hinweis":"✓ Vorhanden – Dach HBO"},{"id":"GH002","name":"Kantteile – Dach HBO","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":950,"hinweis":"✓ Vorhanden – Dach HBO"},{"id":"GH003","name":"Gefälledämmung Kaltwassersatz","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Isolierung","kw":25,"status":"ausstehend","betrag":3200,"hinweis":"Angebot einholen – Dach KWS"},{"id":"GH004","name":"Flüssigkunststoff Dach KWS","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":1100,"hinweis":"✓ Vorhanden – Dach KWS"},{"id":"GH005","name":"Wanne Kaltwassersatz","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":27,"status":"geplant","betrag":2400,"hinweis":"noch nicht geplant – Dach KWS"},{"id":"GH006","name":"Sandwich + UK Dach Brückenbau","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":8500,"hinweis":"✓ Vorhanden – Dach Brücke"},{"id":"GH007","name":"Rinnen Dach Brückenbau","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":1600,"hinweis":"✓ Vorhanden – Dach Brücke"},{"id":"GH008","name":"Regenrohr Dach Brückenbau","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":25,"status":"ausstehend","betrag":780,"hinweis":"Fehlt – Bestellen – Dach Brücke"},{"id":"GH009","name":"Kantteile Dach Brückenbau","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":650,"hinweis":"✓ Vorhanden – Dach Brücke"},{"id":"GH010","name":"Decke öffnen Aufzug / Abbruch","gewerk":"Gebäudehülle","kategorie":"Leistung","verantwortlicher":"offen","firma":"Abbruch","kw":26,"status":"geplant","betrag":4500,"hinweis":"Termin + Kran klären – Dach TH Nord"},{"id":"GH011","name":"Sandwich + UK Dach TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":6200,"hinweis":"✓ Vorhanden – Dach TH Nord"},{"id":"GH012","name":"Rinnen Dach TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":1400,"hinweis":"✓ Vorhanden – Dach TH Nord"},{"id":"GH013","name":"Abwasserrohr Dach TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":25,"status":"ausstehend","betrag":620,"hinweis":"Fehlt – Bestellen – Dach TH Nord"},{"id":"GH014","name":"Kantteile Dach TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":580,"hinweis":"✓ Vorhanden – Dach TH Nord"},{"id":"GH015","name":"Fenster versetzen 1+2.OG Ostseite (Siga)","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":25,"status":"ausstehend","betrag":12400,"hinweis":"NEIN – Siga fehlt, Bestellen prüfen – Ostseite"},{"id":"GH016","name":"Fensterbänke Ostseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":26,"status":"geplant","betrag":2100,"hinweis":"Prüfen erforderlich – Ostseite"},{"id":"GH017","name":"WDVS Ostseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"WDVS","kw":23,"status":"geliefert","betrag":9800,"hinweis":"✓ Vorhanden – Material prüfen – Ostseite"},{"id":"GH018","name":"Regenrohr Ostseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":25,"status":"ausstehend","betrag":840,"hinweis":"Fehlt – Bestellen – Ostseite"},{"id":"GH019","name":"Jalousien 1+2.OG Ostseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Sonnenschutz","kw":27,"status":"geplant","betrag":6800,"hinweis":"Zuständigkeit + Termin klären – Ostseite"},{"id":"GH020","name":"Beleuchtung Ostseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Elektro","kw":27,"status":"ausstehend","betrag":2200,"hinweis":"Fehlt – Besprechen + planen – Ostseite"},{"id":"GH021","name":"UK Kante Süd-Ost","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":25,"status":"ausstehend","betrag":1800,"hinweis":"UK fehlt – Detail zeichnen – Ostseite"},{"id":"GH022","name":"UK Kante Nord-Ost","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":25,"status":"ausstehend","betrag":1800,"hinweis":"UK fehlt – Detail zeichnen – Ostseite"},{"id":"GH023","name":"Ausgleich Estrich 4.OG Terrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Estrich","kw":26,"status":"ausstehend","betrag":2800,"hinweis":"Fehlt – 4.OG Terrasse"},{"id":"GH024","name":"Vakuumdämmung 4.OG Terrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Isolierung","kw":26,"status":"ausstehend","betrag":4200,"hinweis":"Fehlt – 4.OG Terrasse"},{"id":"GH025","name":"UK Erweiterungsterrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":3100,"hinweis":"✓ Vorhanden – 4.OG Terrasse"},{"id":"GH026","name":"Holzplatten 4.OG Terrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Schreiner","kw":27,"status":"ausstehend","betrag":5600,"hinweis":"Fehlt – Bestellen – 4.OG Terrasse"},{"id":"GH027","name":"Flüssigkunststoff 4.OG Terrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Dachdecker","kw":23,"status":"geliefert","betrag":1200,"hinweis":"✓ Vorhanden – 4.OG Terrasse"},{"id":"GH028","name":"Granitplatten 4.OG Terrasse","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Steinmetz","kw":28,"status":"ausstehend","betrag":8900,"hinweis":"Fehlt – Angebot + Bestellen – 4.OG Terrasse"},{"id":"GH029","name":"Beleuchtung Westseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Elektro","kw":27,"status":"geplant","betrag":1900,"hinweis":"Besprechen + planen – Westseite"},{"id":"GH030","name":"WDVS Westseite (Restfläche)","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"WDVS","kw":23,"status":"geliefert","betrag":7200,"hinweis":"✓ Vorhanden – Putz + Farbe – Westseite"},{"id":"GH031","name":"Sandwich + UK TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":5800,"hinweis":"✓ Vorhanden – TH Nord"},{"id":"GH032","name":"Kantteile TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":520,"hinweis":"✓ Vorhanden – TH Nord"},{"id":"GH033","name":"Fenster + Lamellenfenster TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":23,"status":"geliefert","betrag":6400,"hinweis":"✓ Vorhanden – TH Nord"},{"id":"GH034","name":"Beleuchtung Südseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Elektro","kw":27,"status":"geplant","betrag":1900,"hinweis":"Besprechen + planen – Südseite"},{"id":"GH035","name":"Fenster 3.OG Südseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":23,"status":"geliefert","betrag":4800,"hinweis":"✓ Vorhanden – Wand sägen + Sandwich"},{"id":"GH036","name":"Sandwich + UK TH Nord (2)","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":4200,"hinweis":"✓ Vorhanden – TH Nord 2"},{"id":"GH037","name":"Kantteile TH Nord (2)","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":430,"hinweis":"✓ Vorhanden – TH Nord 2"},{"id":"GH038","name":"Beleuchtung Nordseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Elektro","kw":27,"status":"geplant","betrag":1900,"hinweis":"Besprechen + planen – Nordseite"},{"id":"GH039","name":"WDVS 4.OG Nordseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"WDVS","kw":26,"status":"ausstehend","betrag":5400,"hinweis":"Fehlt – Nordseite"},{"id":"GH040","name":"Sandwich + UK Nordseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":7100,"hinweis":"✓ Vorhanden – Nordseite"},{"id":"GH041","name":"Kantteile Nordseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":610,"hinweis":"✓ Vorhanden – Nordseite"},{"id":"GH042","name":"Fenster + Türen 4.OG W5.3 Nordseite","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":23,"status":"geliefert","betrag":8200,"hinweis":"✓ Vorhanden – Wand sägen – Nordseite"},{"id":"GH043","name":"Fenster 5.OG TH Nord","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fensterbau","kw":23,"status":"geliefert","betrag":5600,"hinweis":"✓ Vorhanden – TH Nord 5.OG"},{"id":"GH044","name":"Sandwich + UK TH Nord 5.OG","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":4900,"hinweis":"✓ Vorhanden – TH Nord 5.OG"},{"id":"GH045","name":"Kantteile TH Nord 5.OG","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":390,"hinweis":"✓ Vorhanden – TH Nord 5.OG"},{"id":"GH046","name":"PR Fassade TH Nord 5.OG","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":25,"status":"ausstehend","betrag":3800,"hinweis":"Fehlt – Bestellen – TH Nord 5.OG"},{"id":"GH047","name":"Sandwich + UK Brückenbau Decke","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":6300,"hinweis":"✓ Vorhanden – Brückenbau"},{"id":"GH048","name":"Kantteile Brückenbau Decke","gewerk":"Gebäudehülle","kategorie":"Material","verantwortlicher":"offen","firma":"Fassadenbau","kw":23,"status":"geliefert","betrag":490,"hinweis":"✓ Vorhanden – Brückenbau"}];
  localStorage.setItem('bo-orders-v3', JSON.stringify(boOrders));
}
// Auto-Remap entfernter Status-Werte
(function migrateOrderStatuses(){
  var changed = 0;
  boOrders.forEach(function(o){
    if (o.status === 'kommt in KW') { o.status = 'Lieferung ausstehend'; changed++; }
  });
  if (changed > 0) localStorage.setItem('bo-orders-v3', JSON.stringify(boOrders));
})();

var boFilters = { verant: '', status: '', gewerk: '', search: '', overdue: false };

function setFilter(group, val, btn) {
  boFilters[group] = val;
  document.querySelectorAll('.bo-filter[data-group="' + group + '"]').forEach(function(b) {
    b.classList.remove('active');
    b.style.outline = 'none';
  });
  if (btn) { btn.classList.add('active'); btn.style.outline = '2px solid currentColor'; }
  renderOrders();
}

function renderOrders() {
  // Status-Pills initial befüllen (idempotent)
  var spCont = document.getElementById('bo-status-pills');
  if (spCont && !spCont.firstChild && typeof window.renderStatusPills === 'function') window.renderStatusPills();
  // Status wird per Pills gesetzt (setStatusFilter) → boFilters.status bleibt persistent
  boFilters.gewerk = (document.getElementById('bo-gewerk-filter')||{}).value || '';
  boFilters.search = ((document.getElementById('bo-search')||{}).value || '').toLowerCase();
  boFilters.ghkat  = ((document.getElementById('bo-gh-filter')||{}).value || '');

  var nowKW = (window.dateToContKW && boFilters.overdue) ? window.dateToContKW(new Date().toISOString().slice(0,10)) : 0;
  var filtered = boOrders.filter(function(o) {
    if (boFilters.verant && o.verantwortlicher !== boFilters.verant) return false;
    if (boFilters.status && o.status !== boFilters.status) return false;
    if (boFilters.overdue) {
      if (!(o.kw && nowKW && o.kw < nowKW && o.status !== 'geliefert')) return false;
    }
    if (boFilters.gewerk && o.gewerk !== boFilters.gewerk) return false;
    if (boFilters.ghkat) {
      var isGH = o.id && o.id.startsWith('GH');
      if (boFilters.ghkat === '__gh__') { if (!isGH) return false; }
      else { if (o.hinweis.indexOf(boFilters.ghkat) < 0) return false; }
    }
    var fw = (o.firma||'').toLowerCase(); var hw = (o.hinweis||'').toLowerCase();
    if (boFilters.search && !o.name.toLowerCase().includes(boFilters.search) &&
        !fw.includes(boFilters.search) && !hw.includes(boFilters.search)) return false;
    return true;
  });

  var tbody = document.getElementById('bo-tbody');
  if (!tbody) return;

  var sMap = {}; (window.BO_STATUS_LIST || []).forEach(function(s){ sMap[s.v] = s; });
  var vMap = {}; (window.BO_VERANT_LIST || []).forEach(function(v){ vMap[v.v] = v; });
  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);}); }

  tbody.innerHTML = filtered.map(function(o) {
    var sMeta = sMap[o.status] || { bg:'#f1f5f9', fg:'#64748b' };
    var vMeta = vMap[o.verantwortlicher] || { bg:'#64748b' };
    var isUrgent = (o.status === 'ausstehend' || o.status === 'Lieferung ausstehend');
    var urgentStyle = isUrgent ? 'background:#fff8f0;' : '';
    var ghBadge = (o.id && o.id.startsWith('GH')) ? '<span style="background:#0891b218;color:#0891b2;border:1px solid #0891b230;padding:1px 4px;border-radius:3px;font-size:9px;font-weight:700;margin-left:4px">GH</span>' : '';

    // Gewerk-Dropdown (live aus window.GEWERKE, mit "+ Neues Gewerk…")
    var gNames = (window.GEWERKE || []).map(function(g){ return g.name; }).filter(function(n){ return n; });
    if (o.gewerk && gNames.indexOf(o.gewerk) === -1) gNames.unshift(o.gewerk);
    var gMeta = (window.GEWERKE || []).find(function(g){ return g.name === o.gewerk; }) || { bg:'#f1f5f9', fg:'#475569' };
    var gewerkSel = '<select onchange="if(this.value===\'__add__\'){var nm=prompt(\'Neues Gewerk:\'); if(nm){ window.addGewerk(nm); setOrderField(\'' + o.id + '\',\'gewerk\',nm);} else { renderOrders(); } } else { setOrderField(\'' + o.id + '\',\'gewerk\',this.value); }" '
      + 'style="background:' + gMeta.bg + ';color:' + gMeta.fg + ';border:1px solid ' + gMeta.fg + '30;border-radius:4px;padding:2px 4px;font-size:10px;font-weight:700;cursor:pointer;max-width:130px">'
      + gNames.map(function(n){ return '<option value="' + esc(n) + '"' + (n === o.gewerk ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('')
      + '<option value="__add__" style="font-style:italic;color:#2563eb">+ Neues Gewerk…</option>'
      + '</select>';

    // Status-Dropdown
    var statusSel = '<select onchange="setOrderField(\'' + o.id + '\',\'status\',this.value)" style="background:' + sMeta.bg + ';color:' + sMeta.fg + ';border:1px solid ' + sMeta.fg + '30;border-radius:4px;padding:2px 4px;font-size:10px;font-weight:700;cursor:pointer">'
      + (window.BO_STATUS_LIST || []).map(function(s){
          return '<option value="' + esc(s.v) + '"' + (s.v === o.status ? ' selected' : '') + '>' + esc(s.v) + '</option>';
        }).join('')
      + '</select>';

    // Verantwortlicher-Dropdown
    var verantSel = '<select onchange="setOrderField(\'' + o.id + '\',\'verantwortlicher\',this.value)" style="background:' + vMeta.bg + '18;color:' + vMeta.bg + ';border:1px solid ' + vMeta.bg + '30;border-radius:10px;padding:2px 6px;font-size:10px;font-weight:700;cursor:pointer">'
      + (window.BO_VERANT_LIST || []).map(function(vv){
          return '<option value="' + esc(vv.v) + '"' + (vv.v === o.verantwortlicher ? ' selected' : '') + '>' + esc(vv.v) + '</option>';
        }).join('')
      + '</select>';

    // bis KW — nur Anzeige, Änderung im Modal
    var kwBadge = o.kw
      ? '<span style="display:inline-block;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700">KW ' + o.kw + '</span>'
      : '<span style="color:#cbd5e1;font-size:11px">—</span>';
    // Lieferung als Datums-Picker, KW wird automatisch berechnet + angezeigt
    var kwLiefDate = o.kw_lief_date || '';
    var kwLiefDisplay = o.kw_lief ? 'KW ' + o.kw_lief : '';
    var kwLiefInput =
      '<div style="display:inline-flex;align-items:center;gap:4px">' +
        '<input type="date" value="' + kwLiefDate + '" onchange="setOrderField(\'' + o.id + '\',\'kw_lief_date\',this.value)" ' +
          'style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;padding:2px 4px;font-size:10px;font-weight:700;font-family:inherit">' +
        (kwLiefDisplay ? '<span style="font-size:10px;color:#15803d;font-weight:700">' + kwLiefDisplay + '</span>' : '') +
      '</div>';

    return '<tr style="border-bottom:1px solid #f1f5f9;' + urgentStyle + '">'
      + '<td style="padding:5px 8px;font-size:10px;color:#94a3b8">' + o.id + '</td>'
      + '<td style="padding:5px 10px;font-size:11px;color:#1e293b;font-weight:' + (isUrgent?'700':'400') + '">' + esc(o.name) + ghBadge + '</td>'
      + '<td style="padding:5px 8px">' + gewerkSel + '</td>'
      + '<td style="padding:5px 8px">' + verantSel + '</td>'
      + '<td style="padding:5px 8px;font-size:10px;color:#64748b">' + esc(o.firma) + '</td>'
      + '<td style="padding:5px 8px;text-align:center">' + kwBadge + '</td>'
      + '<td style="padding:5px 8px">' + statusSel + '</td>'
      + '<td style="padding:5px 8px;text-align:center">' + kwLiefInput + '</td>'
      + '<td style="padding:5px 10px;text-align:right;font-weight:600;color:#2563eb;font-size:11px">' + (o.betrag ? o.betrag.toLocaleString('de-DE') + ' €' : '—') + '</td>'
      + '<td style="padding:5px 8px;font-size:10px;color:#94a3b8;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (o.hinweis||'') + '</td>'
      + '<td style="padding:5px 8px;text-align:center;white-space:nowrap">'
      +   '<button onclick="editOrder(\'' + o.id + '\')" title="Bearbeiten" style="padding:2px 8px;font-size:10px;border:1px solid #e2e8f0;border-radius:4px;cursor:pointer;background:#f8fafc;margin-right:2px">✏</button>'
      +   '<button onclick="deleteOrder(\'' + o.id + '\')" title="Löschen" style="padding:2px 8px;font-size:10px;border:1px solid #fecaca;border-radius:4px;cursor:pointer;background:#fff;color:#dc2626">✕</button>'
      + '</td>'
      + '</tr>';
  }).join('') || '<tr><td colspan="10" style="padding:20px;text-align:center;color:#94a3b8;font-size:12px">Keine Einträge gefunden</td></tr>';

  // Total
  var total = filtered.reduce(function(s,o) { return s + (o.betrag||0); }, 0);
  var totEl = document.getElementById('bo-total');
  if (totEl) totEl.textContent = total.toLocaleString('de-DE') + ' €';

  // Summary Badges
  renderSummaryBadges(filtered);

  // Gewerk-Dropdown füllen
  fillGewerkDropdown();

  // TOD-Integration aktualisieren
  syncOrdersToTODs();
}

function renderSummaryBadges(filtered) {
  var el = document.getElementById('bo-summary');
  if (!el) return;
  var html = '<span style="font-size:11px;color:#64748b;align-self:center">' + filtered.length + ' Einträge</span>';
  // Klickbarer Schnellfilter "X verzögert" — Bestellungen, deren bis-KW überschritten ist und noch nicht geliefert
  var nowKW = window.dateToContKW ? window.dateToContKW(new Date().toISOString().slice(0,10)) : 0;
  var lateCount = boOrders.filter(function(o){
    return o.kw && nowKW && o.kw < nowKW && o.status !== 'geliefert';
  }).length;
  var active = !!boFilters.overdue;
  var greyed = (lateCount === 0);
  html += '<button onclick="setOverdueFilter()" title="Bestellungen, deren bis-KW (' + (nowKW ? 'aktuell KW ' + nowKW : '?') + ') überschritten ist und die noch nicht geliefert sind" '
    + 'style="background:' + (active ? '#dc2626' : (greyed ? '#f1f5f9' : '#fee2e2')) + ';'
    + 'color:' + (active ? '#fff' : (greyed ? '#64748b' : '#dc2626')) + ';'
    + 'border:1px solid ' + (greyed ? '#cbd5e1' : '#dc262630') + ';'
    + 'border-radius:10px;padding:2px 10px;font-size:11px;font-weight:700;cursor:pointer">'
    + (greyed ? '' : '⚠ ') + lateCount + ' verzögert</button>';
  el.innerHTML = html;
}

// Toggle "verzögert"-Schnellfilter
window.setOverdueFilter = function () {
  boFilters.overdue = !boFilters.overdue;
  renderOrders();
};

// Status-Filter per Pill
window.setStatusFilter = function (val) {
  boFilters.status = (boFilters.status === val) ? '' : val;
  renderStatusPills();
  renderOrders();
};

// Status-Filter-Pills rendern (aus BO_STATUS_LIST)
window.renderStatusPills = function () {
  var cont = document.getElementById('bo-status-pills');
  if (!cont) return;
  var cur = boFilters.status || '';
  var html = '<button onclick="setStatusFilter(\'\')" class="bo-status-pill" '
    + 'style="padding:3px 10px;border-radius:14px;border:1.5px solid ' + (cur === '' ? '#2563eb' : '#e2e8f0') + ';'
    + 'background:' + (cur === '' ? '#2563eb' : '#fff') + ';color:' + (cur === '' ? '#fff' : '#64748b') + ';'
    + 'font-size:11px;font-weight:600;cursor:pointer">Alle</button>';
  (window.BO_STATUS_LIST || []).forEach(function (s) {
    var active = (cur === s.v);
    html += '<button onclick="setStatusFilter(\'' + s.v.replace(/'/g, "\\'") + '\')" class="bo-status-pill" data-val="' + s.v + '" '
      + 'style="padding:3px 10px;border-radius:14px;border:1.5px solid ' + s.fg + (active ? '' : '40') + ';'
      + 'background:' + (active ? s.fg : s.bg) + ';color:' + (active ? '#fff' : s.fg) + ';'
      + 'font-size:11px;font-weight:600;cursor:pointer">' + s.v + '</button>';
  });
  cont.innerHTML = html;
};

// Verbindliche Bestellungen in der Budgetplanung darstellen
// Verbindlich = ab Status "AB erhalten" (Vertrag fixiert) bis "geliefert"; davor = Schätzung
var BO_CONFIRMED_STATES = ['AB erhalten', 'bestellt', 'Lieferung ausstehend', 'geliefert', 'laufend'];
window.BO_CONFIRMED_STATES = BO_CONFIRMED_STATES;
function fmtEUR(v) { return (v || 0).toLocaleString('de-DE', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' €'; }

window.renderCostOrders = function () {
  var el = document.getElementById('cost-orders-overview');
  if (!el) return;
  var orders = (window.boOrders || []).slice();
  if (!orders.length) { el.innerHTML = ''; el.style.display = 'none'; return; }
  el.style.display = '';

  var groups = {};
  orders.forEach(function (o) {
    var g = o.gewerk || 'Ohne Gewerk';
    if (!groups[g]) groups[g] = { confirmed: 0, estimated: 0, rows: [] };
    var confirmed = BO_CONFIRMED_STATES.indexOf(o.status) !== -1;
    groups[g][confirmed ? 'confirmed' : 'estimated'] += (o.betrag || 0);
    groups[g].rows.push({ o: o, confirmed: confirmed });
  });

  var totalConfirmed = 0, totalEstimated = 0;
  orders.forEach(function (o) {
    var c = BO_CONFIRMED_STATES.indexOf(o.status) !== -1;
    if (c) totalConfirmed += (o.betrag || 0);
    else totalEstimated += (o.betrag || 0);
  });
  var grand = totalConfirmed + totalEstimated;
  var pctConfirmed = grand > 0 ? Math.round(totalConfirmed / grand * 100) : 0;

  var gMap = {}; (window.GEWERKE || []).forEach(function(g){ gMap[g.name] = g; });

  var html = ''
    + '<div style="padding:12px 16px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">'
    +   '<div>'
    +     '<div style="font-size:13px;font-weight:800;color:#14532d">📦 Verbindliche Bestellungen — automatisch aus Bestelltab</div>'
    +     '<div style="font-size:10px;color:#15803d;margin-top:2px">Plausible Beträge (basieren auf Auftragsbestätigung) vs. Schätzungen</div>'
    +   '</div>'
    +   '<div style="display:flex;gap:6px;align-items:center;font-size:11px">'
    +     '<span style="background:#dcfce7;color:#15803d;border:1px solid #16a34a40;border-radius:8px;padding:3px 10px;font-weight:700">✓ verbindlich: ' + fmtEUR(totalConfirmed) + '</span>'
    +     '<span style="background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;border-radius:8px;padding:3px 10px;font-weight:700">~ Schätzung: ' + fmtEUR(totalEstimated) + '</span>'
    +     '<span style="background:#1e293b;color:#fff;border-radius:8px;padding:3px 10px;font-weight:800">Σ ' + fmtEUR(grand) + '</span>'
    +   '</div>'
    + '</div>'
    + '<div style="padding:8px 16px;background:#fff;border-bottom:1px solid #f0fdf4">'
    +   '<div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;display:flex">'
    +     '<div style="background:#16a34a;width:' + pctConfirmed + '%"></div>'
    +     '<div style="background:#cbd5e1;width:' + (100-pctConfirmed) + '%"></div>'
    +   '</div>'
    +   '<div style="font-size:9px;color:#64748b;margin-top:3px;text-align:right">' + pctConfirmed + '% verbindlich</div>'
    + '</div>';

  Object.keys(groups).sort().forEach(function (gName) {
    var g = groups[gName];
    var meta = gMap[gName] || { bg: '#f1f5f9', fg: '#64748b' };
    html += '<div style="border-bottom:1px solid #f1f5f9">'
      + '<div style="padding:8px 16px;background:#fafafa;display:flex;align-items:center;justify-content:space-between">'
      +   '<span style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:#1e293b">'
      +     '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + meta.fg + '"></span>' + gName
      +   '</span>'
      +   '<span style="font-size:11px;color:#64748b">'
      +     '<span style="color:#15803d;font-weight:700">✓ ' + fmtEUR(g.confirmed) + '</span>'
      +     ' &nbsp;·&nbsp; ~ ' + fmtEUR(g.estimated)
      +   '</span>'
      + '</div>'
      + '<table style="width:100%;border-collapse:collapse;font-size:11px">';
    g.rows.sort(function(a,b){ return (b.o.betrag||0) - (a.o.betrag||0); });
    g.rows.forEach(function (r) {
      var o = r.o, conf = r.confirmed;
      var badge = conf
        ? '<span style="background:#dcfce7;color:#15803d;border:1px solid #16a34a40;border-radius:6px;padding:1px 6px;font-size:9px;font-weight:700">✓ verbindlich</span>'
        : '<span style="background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;border-radius:6px;padding:1px 6px;font-size:9px;font-weight:700">~ Schätzung</span>';
      html += '<tr style="border-top:1px solid #f8fafc">'
        + '<td style="padding:5px 16px;color:#94a3b8;font-size:10px;width:54px">' + o.id + '</td>'
        + '<td style="padding:5px 8px;color:#1e293b">' + (o.name || '') + '</td>'
        + '<td style="padding:5px 8px">' + badge + '</td>'
        + '<td style="padding:5px 8px;color:#64748b;font-size:10px;white-space:nowrap">' + (o.status || '') + '</td>'
        + '<td style="padding:5px 16px;text-align:right;font-weight:700;color:' + (conf ? '#15803d' : '#64748b') + '">' + fmtEUR(o.betrag) + '</td>'
        + '</tr>';
    });
    html += '</table></div>';
  });
  el.innerHTML = html;
  // Top-Gesamtsummen mit aktualisieren
  if (typeof updateCostSummary === 'function') updateCostSummary();
};

function fillGewerkDropdown() {
  var sel = document.getElementById('bo-gewerk-filter');
  if (!sel || sel.dataset.filled === '1') return;
  var gewerke = [...new Set(boOrders.map(function(o){return o.gewerk;}).filter(Boolean))].sort();
  gewerke.forEach(function(g) {
    var opt = document.createElement('option'); opt.value = g; opt.textContent = g; sel.appendChild(opt);
  });
  sel.dataset.filled = '1';
}

// ── ADD / EDIT MODAL ──────────────────────────────────────────────────────────
function openAddOrder() {
  document.getElementById('bo-modal-title').textContent = 'Neue Bestellung';
  ['bo-m-id','bo-m-name','bo-m-firma','bo-m-kw','bo-m-kw-lief-date','bo-m-betrag','bo-m-hinweis'].forEach(function(id) {
    var el = document.getElementById(id); if(el) el.value='';
  });
  var prev = document.getElementById('bo-m-kw-lief-preview'); if (prev) prev.textContent = '';
  document.getElementById('bo-m-tod').checked = false;
  document.getElementById('bo-modal').style.display = 'flex';
}

function deleteOrder(id) {
  var idx = boOrders.findIndex(function(x){ return x.id === id; });
  if (idx < 0) return;
  var o = boOrders[idx];
  if (!confirm('Bestellung "' + (o.name || o.id) + '" wirklich löschen?')) return;
  boOrders.splice(idx, 1);
  var json = JSON.stringify(boOrders);
  localStorage.setItem('bo-orders-v3', json);
  if (window.__syncKV) window.__syncKV('bo-orders-v3', json);
  renderOrders();
  if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
}

function editOrder(id) {
  var o = boOrders.find(function(x){return x.id===id;});
  if (!o) return;
  document.getElementById('bo-modal-title').textContent = 'Bestellung bearbeiten';
  document.getElementById('bo-m-id').value = o.id;
  document.getElementById('bo-m-name').value = o.name;
  document.getElementById('bo-m-gewerk').value = o.gewerk;
  document.getElementById('bo-m-kat').value = o.kategorie;
  document.getElementById('bo-m-verant').value = o.verantwortlicher;
  document.getElementById('bo-m-firma').value = o.firma;
  document.getElementById('bo-m-kw').value = o.kw || '';
  document.getElementById('bo-m-kw-lief-date').value = o.kw_lief_date || '';
  document.getElementById('bo-m-kw-lief-preview').textContent = o.kw_lief ? ('→ KW ' + o.kw_lief) : '';
  document.getElementById('bo-m-status').value = o.status;
  document.getElementById('bo-m-betrag').value = o.betrag || '';
  document.getElementById('bo-m-hinweis').value = o.hinweis || '';
  document.getElementById('bo-modal').style.display = 'flex';
}

function cycleVerant(id) {
  var idx = boOrders.findIndex(function(o){ return o.id === id; });
  if (idx < 0) return;
  var order = ['offen','DIB','HEG','EGA','Architronik'];
  var cur = order.indexOf(boOrders[idx].verantwortlicher);
  boOrders[idx].verantwortlicher = order[(cur + 1) % order.length];
  var boJson = JSON.stringify(boOrders);
  localStorage.setItem('bo-orders-v3', boJson);
  if (window.__syncKV) window.__syncKV('bo-orders-v3', boJson);
  renderOrders();
  if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
  syncOrdersToTODs();
}

function closeModal() {
  document.getElementById('bo-modal').style.display = 'none';
}

function saveOrder() {
  var name = document.getElementById('bo-m-name').value.trim();
  if (!name) { alert('Bitte Position eingeben'); return; }
  var id = document.getElementById('bo-m-id').value;
  var newO = {
    id: id || 'B' + String(Date.now()).slice(-4),
    name: name,
    gewerk: document.getElementById('bo-m-gewerk').value.trim(),
    kategorie: document.getElementById('bo-m-kat').value,
    verantwortlicher: document.getElementById('bo-m-verant').value,
    firma: document.getElementById('bo-m-firma').value.trim(),
    kw: parseInt(document.getElementById('bo-m-kw').value) || null,
    kw_lief_date: document.getElementById('bo-m-kw-lief-date').value || null,
    kw_lief: window.dateToContKW(document.getElementById('bo-m-kw-lief-date').value),
    status: document.getElementById('bo-m-status').value,
    betrag: parseFloat(document.getElementById('bo-m-betrag').value) || 0,
    hinweis: document.getElementById('bo-m-hinweis').value.trim(),
    todLinked: document.getElementById('bo-m-tod').checked
  };
  if (id) {
    var idx = boOrders.findIndex(function(o){return o.id===id;});
    if (idx >= 0) boOrders[idx] = newO;
  } else {
    boOrders.push(newO);
  }
  var boJson2 = JSON.stringify(boOrders);
  localStorage.setItem('bo-orders-v3', boJson2);
  if (window.__syncKV) window.__syncKV('bo-orders-v3', boJson2);
  closeModal();
  renderOrders();
  if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
  syncOrdersToTODs();
}

// ── TOD-INTEGRATION ───────────────────────────────────────────────────────────
function syncOrdersToTODs() {
  // Bestehende Order-Badges aus TOD-Karten entfernen
  document.querySelectorAll('.bo-tod-badge').forEach(function(el) { el.remove(); });

  var vColors = {"DIB": "#2563eb", "HEG": "#16a34a", "EGA": "#d97706", "Architronik": "#7c3aed", "offen": "#94a3b8"};
  boOrders.forEach(function(o) {
    if (!o.kw) return;
    if (o.id && o.id.startsWith('GH') && o.status !== 'ausstehend') return;
    var card = document.querySelector('[data-kw="' + o.kw + '"][contenteditable]');
    if (!card) return;
    var vc = vColors[o.verantwortlicher] || '#64748b';
    // Status-Farbe
    var sMap = {"geliefert": ["#dcfce7", "#15803d"], "bestellt": ["#dbeafe", "#2563eb"], "laufend": ["#fef3c7", "#b45309"], "geplant": ["#f1f5f9", "#64748b"], "ausstehend": ["#fee2e2", "#dc2626"]};
    var sc = sMap[o.status] || ['#f1f5f9','#64748b'];
    var badge = document.createElement('div');
    badge.className = 'bo-tod-badge';
    badge.style.cssText = 'display:flex;align-items:center;gap:6px;padding:3px 8px;margin:2px 0;'
      + 'background:#f8fafc;border-left:3px solid ' + vc + ';border-radius:0 4px 4px 0;font-size:10px;';
    badge.innerHTML = '📦 <span style="color:#1e293b;font-weight:600">' + o.name + '</span>'
      + ' <span style="background:' + vc + '18;color:' + vc + ';padding:1px 5px;border-radius:8px;font-size:9px;font-weight:700">' + o.verantwortlicher + '</span>'
      + ' <span style="background:' + sc[0] + ';color:' + sc[1] + ';padding:1px 5px;border-radius:4px;font-size:9px;font-weight:700">' + o.status + '</span>';
    // Vor dem editierbaren Bereich einfügen
    var parent = card.parentElement;
    if (parent) parent.insertBefore(badge, card);
  });
}

// ── INIT ──────────────────────────────────────────────────────────────────────
(function init() {
  loadCostValues();
  renderOrders();
  updateCostSummary();
})();
</script>


<script id="unit-cost-progress-engine">
// ════════ EINHEITEN-KOSTEN ENGINE ════════

var ALL_UNITS = ["T_4_01", "T_4_02", "T_4_03", "T_4_04", "T_4_05", "T_4_06", "T_4_07", "T_4_08", "T_4_09", "T_4_10", "T_4_11", "T_4_12", "T_4_13", "T_4_14", "T_4_16", "T_4_18", "T_4_20", "T_4_22", "W_5_3", "T_5_1"];
var PH1B_UNITS = ["T_4_01", "T_4_02", "T_4_03", "T_4_04", "T_4_05", "T_4_06", "T_4_07", "T_4_08", "T_4_09"];
var PH2_UNITS  = ["T_4_10", "T_4_11", "T_4_12", "T_4_13", "T_4_14", "T_4_16", "T_4_18", "T_4_20", "T_4_22", "W_5_3"];

// ── Kosten pro Einheit laden / speichern ──────────────────────────────────────
function loadUnitCosts() {
  var saved = JSON.parse(localStorage.getItem('unit-costs') || '{}');
  Object.keys(saved).forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = saved[id];
  });
  ALL_UNITS.forEach(function(uid) { updateUnitRow(uid); });
  updatePhaseTotals();
  updateUnitGrandTotal();
}

function saveUnitCosts() {
  var vals = {};
  document.querySelectorAll('input[data-unit][data-trade]').forEach(function(inp) {
    vals[inp.id] = parseFloat(inp.value) || 0;
  });
  var json = JSON.stringify(vals);
  localStorage.setItem('unit-costs', json);
  if (window.__syncKV) window.__syncKV('unit-costs', json);
}

function resetUnitDefaults() {
  if (!confirm('Alle Einheiten-Kosten auf Standardwerte zurücksetzen?')) return;
  localStorage.removeItem('unit-costs');
  document.querySelectorAll('input[data-unit][data-trade]').forEach(function(inp) {
    inp.value = inp.dataset.default;
  });
  ALL_UNITS.forEach(function(uid) { updateUnitRow(uid); });
  updatePhaseTotals();
  updateUnitGrandTotal();
}

function updateUnitRow(uid) {
  var total = 0;
  document.querySelectorAll('input[data-unit="' + uid + '"]').forEach(function(inp) {
    total += parseFloat(inp.value) || 0;
  });
  var el = document.getElementById('cost-unit-total-' + uid);
  if (el) el.textContent = total.toLocaleString('de-DE') + ' €';
  saveUnitCosts();
  updatePhaseTotals();
  updateUnitGrandTotal();
  // Auch Hauptsumme in den Summary-Cards aktualisieren
  if (typeof updateCostSummary === 'function') updateCostSummary();
}

function updatePhaseTotals() {
  // Phase 2
  var tot1 = 0;
  PH1B_UNITS.forEach(function(uid) {
    document.querySelectorAll('input[data-unit="' + uid + '"]').forEach(function(inp) {
      tot1 += parseFloat(inp.value) || 0;
    });
  });
  ['phase-grand-total-T_4_0-hdr','phase-grand-total-T_4_0'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.textContent = tot1.toLocaleString('de-DE') + ' €';
  });
  // Phase 3
  var tot2 = 0;
  PH2_UNITS.forEach(function(uid) {
    document.querySelectorAll('input[data-unit="' + uid + '"]').forEach(function(inp) {
      tot2 += parseFloat(inp.value) || 0;
    });
  });
  ['phase-grand-total-T_4_1-hdr','phase-grand-total-T_4_1',
   'phase-grand-total-W_5_3-hdr','phase-grand-total-W_5_3'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.textContent = tot2.toLocaleString('de-DE') + ' €';
  });
  // T5.1
  var tot3 = 0;
  document.querySelectorAll('input[data-unit="T_5_1"]').forEach(function(inp) {
    tot3 += parseFloat(inp.value) || 0;
  });
  ['phase-grand-total-T_5_1-hdr','phase-grand-total-T_5_1'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.textContent = tot3.toLocaleString('de-DE') + ' €';
  });
}

function updateUnitGrandTotal() {
  var grand = 0;
  document.querySelectorAll('input[data-unit][data-trade]').forEach(function(inp) {
    grand += parseFloat(inp.value) || 0;
  });
  var el = document.getElementById('unit-grand-total');
  if (el) el.textContent = grand.toLocaleString('de-DE') + ' €';
}

function exportUnitCSV() {
  var lines = ['"Einheit","Gewerk","Betrag (Netto €)"'];
  document.querySelectorAll('input[data-unit][data-trade]').forEach(function(inp) {
    lines.push('"' + inp.dataset.unit + '","' + inp.dataset.trade + '",' + (parseFloat(inp.value)||0));
  });
  var blob = new Blob([lines.join('\n')], {type:'text/csv'});
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'Einheiten_Kosten.csv';
  a.click();
}

// ════════ FORTSCHRITT ENGINE ════════

// Status-Mapping: data-status → Kategorie
function classifyStatus(status) {
  if (!status) return 'planned';
  if (status === 'abgeschlossen') return 'done';
  if (status.startsWith('fortschritt_') || status === 'laufend') return 'wip';
  if (status === 'verzögert') return 'delayed';
  if (status === 'priorität') return 'wip';
  return 'planned';
}

// Alle Task-Zeilen scannen und Fortschritt berechnen
function calcProgress() {
  var counts = {done:0, wip:0, planned:0, delayed:0};
  document.querySelectorAll('tr.task-row[data-status]').forEach(function(tr) {
    var cat = classifyStatus(tr.dataset.status);
    counts[cat] = (counts[cat]||0) + 1;
  });
  return counts;
}

// Unit-Level Fortschritt (für Kosten-Tab)
function calcUnitProgress(uid) {
  var counts = {done:0, wip:0, planned:0, delayed:0, total:0};
  document.querySelectorAll('tr.task-row[data-unit="' + uid + '"]').forEach(function(tr) {
    counts.total++;
    var cat = classifyStatus(tr.dataset.status);
    counts[cat]++;
  });
  return counts;
}

// Header-Balken aktualisieren
function updateHeaderProgress() {
  var c = calcProgress();
  var total = c.done + c.wip + c.planned + c.delayed;
  if (total === 0) return;
  var pct = Math.round(c.done / total * 100);

  var set = function(id, val) { var el=document.getElementById(id); if(el) el.textContent=val; };
  set('hdr-done',  c.done);
  set('hdr-wip',   c.wip);
  set('hdr-plan',  c.planned);
  set('hdr-delay', c.delayed);
  set('hdr-prog-pct', pct + ' %');
  set('hdr-prog-txt', 'Fertigstellung\n(' + c.done + ' / ' + total + ' Aufgaben)');

  var fill = document.getElementById('hdr-prog-fill');
  if (fill) fill.style.width = pct + '%';

  // Tab-Label aktualisieren
  var tab = document.querySelector('[onclick*="hauptwerk"]');
  if (tab) tab.textContent = '📊 Hauptzeitplan (' + total + ' Aufgaben)';
}

// Einheiten-Fortschritt in Kosten-Tab
function updateAllUnitProgress() {
  var allDone=0, allWip=0, allPlan=0, allDelay=0, allTotal=0;
  ALL_UNITS.forEach(function(uid) {
    var c = calcUnitProgress(uid);
    allDone  += c.done;
    allWip   += c.wip;
    allPlan  += c.planned;
    allDelay += c.delayed;
    allTotal += c.total;
    if (c.total === 0) return;

    // Mini-Fortschrittsbalken pro Einheit
    var pct = Math.round(c.done / c.total * 100);
    var fill = document.getElementById('unit-prog-fill-' + uid);
    if (fill) {
      // Gestapelter Balken
      var donePct  = Math.round(c.done  / c.total * 100);
      var wipPct   = Math.round(c.wip   / c.total * 100);
      var delayPct = Math.round(c.delayed / c.total * 100);
      fill.style.width = donePct + '%';
      // Status-Badge
      var statusEl = document.getElementById('unit-status-' + uid);
      if (statusEl) {
        var label, bg, col;
        if (c.done === c.total) {
          label='✅ Fertig'; bg='#dcfce7'; col='#15803d';
        } else if (c.delayed > 0) {
          label='⚠ Verzögerung'; bg='#fee2e2'; col='#dc2626';
        } else if (c.wip > 0) {
          label='🔨 In Arbeit'; bg='#fef3c7'; col='#b45309';
        } else {
          label='📋 Geplant'; bg='#f1f5f9'; col='#64748b';
        }
        statusEl.textContent = label;
        statusEl.style.background = bg;
        statusEl.style.color = col;
      }
      var txt = document.getElementById('unit-prog-txt-' + uid);
      if (txt) txt.textContent = c.done + ' / ' + c.total;
    }
  });
  // Grand total progress
  if (allTotal > 0) {
    var donePct  = Math.round(allDone  / allTotal * 100);
    var wipPct   = Math.round(allWip   / allTotal * 100);
    var delayPct = Math.round(allDelay / allTotal * 100);
    ['done','wip','plan','delay'].forEach(function(k) {
      var el = document.getElementById('ugp-' + k);
      if (!el) return;
      var v = {done:allDone,wip:allWip,plan:allPlan,delay:allDelay}[k];
      var icons = {done:'✅ erledigt',wip:'🔨 in Arbeit',plan:'📋 geplant',delay:'⚠ verzögert'};
      el.textContent = v + ' ' + icons[k];
    });
    var bd = document.getElementById('ugp-bar-done');
    var bw = document.getElementById('ugp-bar-wip');
    var by = document.getElementById('ugp-bar-delay');
    if(bd) bd.style.width = donePct + '%';
    if(bw) bw.style.width = wipPct  + '%';
    if(by) by.style.width = delayPct + '%';
  }
}

// ── Alles live verknüpfen (MutationObserver) ──────────────────────────────────
function startProgressObserver() {
  var target = document.getElementById('tab-hauptwerk');
  if (!target) return;
  var mo = new MutationObserver(function(mutations) {
    // Nur bei Status-Änderungen reagieren
    var relevant = mutations.some(function(m) {
      return m.type === 'attributes' && m.attributeName === 'data-status';
    });
    if (relevant) {
      updateHeaderProgress();
      updateAllUnitProgress();
    }
  });
  mo.observe(target, {subtree: true, attributes: true, attributeFilter: ['data-status']});
}

// ── Status-Badge klickbar machen (Toggle) ────────────────────────────────────
// Status-Optionen für das Dropdown (Hauptzeitplan). Reihenfolge = Workflow.
var STATUS_OPTIONS = [
  { v: 'geplant',         l: '—',              c: 'status-planned',   dot: '#94a3b8' },
  { v: 'priorität',       l: '⭐ Priorität',    c: 'status-prio',      dot: '#ea580c' },
  { v: 'vorbereitung',    l: 'Vorbereitung',   c: 'status-planned',   dot: '#94a3b8' },
  { v: 'begonnen',        l: 'begonnen',       c: 'status-wip',       dot: '#f59e0b' },
  { v: 'fortschritt_25',  l: '25 %',           c: 'status-wip',       dot: '#f59e0b' },
  { v: 'fortschritt_50',  l: '50 %',           c: 'status-wip',       dot: '#f59e0b' },
  { v: 'fortschritt_75',  l: '75 %',           c: 'status-wip',       dot: '#f59e0b' },
  { v: 'fortschritt_90',  l: '90 %',           c: 'status-wip',       dot: '#f59e0b' },
  { v: 'abnahme',         l: 'Abnahme',        c: 'status-wip',       dot: '#f59e0b' },
  { v: 'abgeschlossen',   l: '✓ fertig',       c: 'status-done',      dot: '#16a34a' },
  { v: 'pausiert',        l: '⏸ Pause',        c: 'status-delayed',   dot: '#dc2626' },
  { v: 'verzögert',       l: '⚠ verzögert',    c: 'status-delayed',   dot: '#dc2626' },
  { v: 'abgebrochen',     l: '✕ abgebrochen',  c: 'status-cancelled', dot: '#64748b' },
];
var STATUS_LABELS = {};
var STATUS_CSS = {};
STATUS_OPTIONS.forEach(function(s){ STATUS_LABELS[s.v] = s.l; STATUS_CSS[s.v] = s.c; });
// Altlasten — alte Statuswerte abwärtskompatibel mappen
STATUS_LABELS['laufend'] = 'laufend';   STATUS_CSS['laufend'] = 'status-wip';
STATUS_LABELS['fertig']  = '✓ fertig';  STATUS_CSS['fertig']  = 'status-done';
STATUS_LABELS['priorität']= 'Priorität'; STATUS_CSS['priorität']= 'status-prio';
window.STATUS_OPTIONS = STATUS_OPTIONS;
window.STATUS_LABELS = STATUS_LABELS;
window.STATUS_CSS = STATUS_CSS;

function applyStatus(row, next) {
  if (!row || !next) return;
  var badge = row.querySelector('.status-badge');
  row.dataset.status = next;
  if (badge) {
    badge.textContent = STATUS_LABELS[next] || next;
    badge.className = 'status-badge ' + (STATUS_CSS[next] || 'status-planned');
  }
  var bar = row.querySelector('.gantt-bar');
  if (bar) {
    bar.className = bar.className.replace(/\bstatus-\S+/g, '').trim() + ' ' + (STATUS_CSS[next] || 'status-planned');
  }
  var tid = row.dataset.tid;
  if (tid) {
    var saved = JSON.parse(localStorage.getItem('task-statuses') || '{}');
    saved[tid] = next;
    localStorage.setItem('task-statuses', JSON.stringify(saved));
  }
  if (typeof updateHeaderProgress === 'function') updateHeaderProgress();
  if (typeof updateAllUnitProgress === 'function') updateAllUnitProgress();
}
window.applyStatus = applyStatus;

function showStatusDropdown(badge, row) {
  // Existierende schließen
  document.querySelectorAll('.status-dropdown').forEach(function(d){ d.remove(); });
  var cur = row.dataset.status || 'geplant';
  var dd = document.createElement('div');
  dd.className = 'status-dropdown';
  dd.style.cssText = 'position:absolute;z-index:99999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 28px rgba(15,23,42,.18);padding:4px;min-width:170px;font-size:11px';
  STATUS_OPTIONS.forEach(function(s) {
    var item = document.createElement('div');
    item.style.cssText = 'padding:6px 8px;border-radius:5px;cursor:pointer;display:flex;align-items:center;gap:8px;' + (s.v === cur ? 'background:#f1f5f9;font-weight:700' : '');
    item.innerHTML = '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + s.dot + '"></span>'
      + '<span>' + s.l + '</span>';
    item.addEventListener('mouseenter', function(){ if (s.v !== cur) item.style.background = '#f8fafc'; });
    item.addEventListener('mouseleave', function(){ if (s.v !== cur) item.style.background = ''; });
    item.addEventListener('click', function(e){
      e.stopPropagation();
      applyStatus(row, s.v);
      dd.remove();
    });
    dd.appendChild(item);
  });
  document.body.appendChild(dd);
  var rect = badge.getBoundingClientRect();
  dd.style.left = (rect.left + window.scrollX) + 'px';
  dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  setTimeout(function(){
    document.addEventListener('click', function close(){ dd.remove(); document.removeEventListener('click', close); });
  }, 10);
}
window.showStatusDropdown = showStatusDropdown;

document.addEventListener('click', function(e) {
  var badge = e.target.closest('.status-badge');
  if (!badge) return;
  var row = badge.closest('tr.task-row');
  if (!row) return;
  e.stopPropagation();
  e.preventDefault();
  showStatusDropdown(badge, row);
});

// Gespeicherte Status wiederherstellen
function restoreTaskStatuses() {
  var saved = JSON.parse(localStorage.getItem('task-statuses') || '{}');
  Object.keys(saved).forEach(function(tid) {
    var row = document.querySelector('tr[data-tid="' + tid + '"]');
    if (!row) return;
    var status = saved[tid];
    row.dataset.status = status;
    var badge = row.querySelector('.status-badge');
    if (badge) {
      badge.textContent = STATUS_LABELS[status] || status;
      badge.className = 'status-badge ' + (STATUS_CSS[status] || 'status-planned');
    }
  });
}

// ── INIT ─────────────────────────────────────────────────────────────────────
(function initProgress() {
  setTimeout(function() {
    restoreTaskStatuses();
    updateHeaderProgress();
    updateAllUnitProgress();
    loadUnitCosts();
    startProgressObserver();
    // Auch nach Kosten-Tab-Öffnung neu berechnen
    var origShow = window.showTab;
    window.showTab = function(name, el) {
      if (origShow) origShow(name, el);
      if (name === 'kosten') {
        setTimeout(function() {
          updateAllUnitProgress();
          updateUnitGrandTotal();
        }, 50);
      }
    };
  }, 300);
})();
</script>


<script id="status-sync-helper">
(function() {
  document.addEventListener('mouseover', function(e) {
    var b = e.target.closest('.status-badge');
    if (b && !b.dataset.tipset) {
      b.title = 'Klick: geplant → laufend → 50% → 75% → 90% → fertig → verzögert';
      b.style.cursor = 'pointer';
      b.dataset.tipset = '1';
    }
  });
  var obs = new MutationObserver(function(muts) {
    muts.forEach(function(m) {
      if (m.type !== 'attributes' || m.attributeName !== 'data-status') return;
      var row = m.target;
      var nameCell = row.querySelector('.task-name-cell');
      if (!nameCell) return;
      var taskName = nameCell.textContent.trim();
      var newStatus = row.getAttribute('data-status') || '';
      var orderStatus = null;
      if (newStatus === 'abgeschlossen' || newStatus === 'fertig') orderStatus = 'geliefert';
      else if (newStatus === 'begonnen' || newStatus === 'laufend' || newStatus === 'abnahme' || newStatus.indexOf('fortschritt_') === 0) orderStatus = 'laufend';
      else if (newStatus === 'verzögert' || newStatus === 'pausiert') orderStatus = 'ausstehend';
      if (!orderStatus) return;
      var orders = JSON.parse(localStorage.getItem('bo-orders-v3') || '[]');
      var changed = 0;
      var keyWords = taskName.toLowerCase().split(/[\s\(\)\-–_,/]+/).filter(function(w){return w.length > 4;});
      orders.forEach(function(o) {
        if (!o.name) return;
        var ol = o.name.toLowerCase();
        var mc = keyWords.filter(function(w){return ol.indexOf(w)>=0;}).length;
        if (mc >= 2 && o.status !== orderStatus) { o.status = orderStatus; changed++; }
      });
      if (changed > 0) {
        var boJson3 = JSON.stringify(orders);
        // window.boOrders aktuell halten, damit renderCostOrders die neuen Status sieht
        try { window.boOrders = JSON.parse(boJson3); } catch (e) {}
        localStorage.setItem('bo-orders-v3', boJson3);
        if (window.__syncKV) window.__syncKV('bo-orders-v3', boJson3);
        if (typeof renderOrders === 'function') renderOrders();
        if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
        console.log('Status-Sync: ' + changed + ' Bestellung(en) → ' + orderStatus);
      }
    });
  });
  setTimeout(function(){
    document.querySelectorAll('tr.task-row').forEach(function(r){ obs.observe(r, {attributes:true, attributeFilter:['data-status']}); });
  }, 500);
})();
</script>

<script id="unit-registry">
// ── ZENTRALE UNIT-REGISTRY (Single Source of Truth) ──────────────────
// Wird von allen Tabs gelesen. Änderungen via updateUnit() synchronisieren.
window.UNIT_REGISTRY = [
  {
    "id": "T 4.01",
    "typ": "3-Zimmer",
    "m2": 72.35,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": "Wohnen/Essen + separate Küche"
  },
  {
    "id": "T 4.02",
    "typ": "2-Zimmer",
    "m2": 60.2,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.03",
    "typ": "2-Zimmer",
    "m2": 54.79,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.04",
    "typ": "2-Zimmer",
    "m2": 54.79,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.05",
    "typ": "Studio",
    "m2": 31.55,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.06",
    "typ": "Studio",
    "m2": 31.62,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.07",
    "typ": "Studio",
    "m2": 31.67,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.08",
    "typ": "2-Zimmer",
    "m2": 61.02,
    "phase": "Phase 2",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.09",
    "typ": "Maisonette",
    "m2": 177.01,
    "phase": "Phase 3/4",
    "og": "3.OG+4.OG",
    "bem": "2-stöckig · 3.OG bis Estrich (P2) + 4.OG (P4)"
  },
  {
    "id": "T 4.10",
    "typ": "Studio",
    "m2": 31.98,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.11",
    "typ": "2-Zimmer",
    "m2": 61.01,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.12",
    "typ": "Studio",
    "m2": 31.18,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.13",
    "typ": "Studio",
    "m2": 32.73,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.14",
    "typ": "Studio",
    "m2": 31.57,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.16",
    "typ": "Studio",
    "m2": 31.6,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.18",
    "typ": "Studio",
    "m2": 31.55,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.20",
    "typ": "Studio",
    "m2": 31.63,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": ""
  },
  {
    "id": "T 4.22",
    "typ": "Büro",
    "m2": 102.73,
    "phase": "Phase 3",
    "og": "3.OG",
    "bem": "Büro-/Praxis-Einheit (Gewerbe)"
  },
  {
    "id": "W 5.3",
    "typ": "Penthouse",
    "m2": 288.14,
    "phase": "Phase 4",
    "og": "4.OG",
    "bem": ""
  },
  {
    "id": "T 5.1",
    "typ": "Studio",
    "m2": 54.04,
    "phase": "Phase 1",
    "og": "4.OG",
    "bem": "Wellnessbereich · keiner Phase zugeordnet"
  },
  {
    "id": "W 5.2",
    "typ": "Penthouse",
    "m2": 89.77,
    "phase": "Phase 1",
    "og": "4.OG",
    "bem": "Penthouse · nur Kleinigkeiten als Aufgaben"
  }

,
  {
    "id": "W 5.1",
    "typ": "2-Zimmer",
    "m2": 74.44,
    "phase": "Phase 4",
    "og": "4.OG",
    "bem": "kleine Wohnung 4.OG · nur Kleinigkeiten"
  }
];

// Auto-Sync Helper: Aktualisiert alle Stellen wenn eine Unit geändert wird
window.updateUnit = function(uid, key, value) {
  var unit = window.UNIT_REGISTRY.find(function(u){return u.id === uid;});
  if (!unit) return false;
  unit[key] = value;
  var urJson = JSON.stringify(window.UNIT_REGISTRY);
  localStorage.setItem('unit-registry', urJson);
  if (window.__syncKV) window.__syncKV('unit-registry', urJson);
  // Section-Titles im Hauptzeitplan aktualisieren
  document.querySelectorAll('span.section-arrow + *').forEach(function(span){
    var txt = span.textContent || '';
    if (txt.indexOf(uid) === 0) {
      // Format: "T 4.01 — Studio (31.5 m²) Phase 2"
      span.textContent = uid + ' — ' + unit.typ + ' (' + unit.m2.toFixed(2) + ' m²) ' + unit.phase;
    }
  });
  return true;
};

// Lade Anpassungen aus localStorage
(function loadRegistry(){
  try {
    var saved = JSON.parse(localStorage.getItem('unit-registry') || 'null');
    if (saved && Array.isArray(saved)) {
      saved.forEach(function(s){
        var u = window.UNIT_REGISTRY.find(function(x){return x.id === s.id;});
        if (u) Object.assign(u, s);
      });
    }
  } catch(e) { console.warn('Registry load:', e); }
})();

// Terrassen-Status zyklisch ändern
window.cycleTerrStatus = function(badge) {
  var states = [
    {key:'geplant',  label:'○ geplant',   col:'#94a3b8'},
    {key:'arbeit',   label:'⚙ in Arbeit', col:'#f59e0b'},
    {key:'fertig',   label:'✓ fertig',    col:'#16a34a'},
  ];
  var current = badge.textContent.trim();
  var idx = states.findIndex(function(s){return s.label === current;});
  var next = states[(idx + 1) % states.length];
  badge.textContent = next.label;
  badge.style.background = next.col + '18';
  badge.style.color = next.col;
  badge.style.borderColor = next.col + '40';
  // Persist
  var key = 'terr-' + badge.dataset.uid + '-' + badge.dataset.rid;
  localStorage.setItem(key, next.key);
};

// Restore Terrassen-Status aus localStorage
window.addEventListener('DOMContentLoaded', function(){
  setTimeout(function(){
    document.querySelectorAll('.terr-status').forEach(function(b){
      var key = 'terr-' + b.dataset.uid + '-' + b.dataset.rid;
      var saved = localStorage.getItem(key);
      if (saved) {
        var states = {
          'geplant':  ['○ geplant',   '#94a3b8'],
          'arbeit':   ['⚙ in Arbeit', '#f59e0b'],
          'fertig':   ['✓ fertig',    '#16a34a'],
        };
        var s = states[saved];
        if (s) {
          b.textContent = s[0];
          b.style.background = s[1] + '18';
          b.style.color = s[1];
          b.style.borderColor = s[1] + '40';
        }
      }
    });
  }, 200);
});
</script>

<!-- ══════════════ BAR-EDITOR MODAL ══════════════ -->
<div id="bar-editor-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:420px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h3 style="margin:0;font-size:15px;font-weight:700;color:#1e293b">📅 Aufgabe planen</h3>
      <button onclick="closeBarEditor()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">×</button>
    </div>
    <div id="be-task-name" style="font-size:13px;color:#475569;font-weight:600;margin-bottom:14px;padding:8px 12px;background:#f8fafc;border-radius:6px;border-left:3px solid #2563eb"></div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Beginn (Datum)</label>
        <input id="be-von" type="date" oninput="beRecalc()" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Ende (Datum, inkl.)</label>
        <input id="be-bis" type="date" oninput="beRecalc()" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
    </div>

    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;margin-bottom:14px">
      <label style="font-size:11px;font-weight:700;color:#14532d;display:flex;align-items:center;gap:6px;margin-bottom:6px">
        👷 Mannstunden für diese Aufgabe
        <span style="font-weight:400;color:#15803d;font-size:10px">— wirken auf Kapazitäts-Auslastung</span>
      </label>
      <div style="display:flex;gap:8px;align-items:center">
        <input id="be-mh" type="number" min="0" max="9999" step="1" placeholder="z.B. 40" style="width:100px;padding:5px 8px;border:1.5px solid #bbf7d0;border-radius:5px;font-size:12px;background:#fff">
        <span style="font-size:11px;color:#15803d">Std</span>
        <span id="be-mh-hint" style="font-size:10px;color:#15803d;margin-left:8px"></span>
      </div>
    </div>
    <div id="be-info" style="font-size:11px;color:#64748b;padding:8px 12px;background:#eff6ff;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:14px"></div>

    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button onclick="beReset()" style="padding:8px 14px;background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600">↺ Auf Plan zurück</button>
      <button onclick="closeBarEditor()" style="padding:8px 14px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12px">Abbrechen</button>
      <button onclick="beApply()" style="padding:8px 14px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600">✓ Anwenden</button>
    </div>
  </div>
</div>

<script id="bar-editor-script">
(function(){
  // Plan-Koordinaten: left:0 = KW23 = 1. Juni 2026
  var ORIGIN_KW = 23;
  var PX_PER_WEEK = 126;
  var PX_PER_DAY = PX_PER_WEEK / 7;             // 6 px = 1 Tag (tag-genaue Planung)
  var ORIGIN_DATE = new Date(2026, 5, 1, 12);   // 1. Juni 2026 (KW23 Mo) = left:0

  var currentBar = null;
  var currentTid = null;
  var planLeft = 0, planWidth = 0;

  function pxToKw(px) { return ORIGIN_KW + Math.round(px / PX_PER_WEEK); }
  function kwToPx(kw) { return (kw - ORIGIN_KW) * PX_PER_WEEK; }
  // Datum ↔ px (1 Tag = 6 px). left:0 = 1. Juni 2026.
  function pxFromDate(str) { if (!str) return null; var d = new Date(str + 'T12:00:00'); if (isNaN(d.getTime())) return null; return Math.round((d - ORIGIN_DATE) / 86400000) * PX_PER_DAY; }
  function dateFromPx(px) { var days = Math.round(px / PX_PER_DAY); var d = new Date(ORIGIN_DATE.getTime() + days * 86400000); var m = ('0' + (d.getMonth() + 1)).slice(-2), dd = ('0' + d.getDate()).slice(-2); return d.getFullYear() + '-' + m + '-' + dd; }
  function fmtDate(str) { if (!str) return '?'; var p = str.split('-'); return p[2] + '.' + p[1] + '.' + p[0].slice(2); }
  window.ganttDayLabel = function (px) { return fmtDate(dateFromPx(px)); };  // px → "dd.mm.yy" (für Drag-Tooltip)
  // Balken setzen + persistieren + (optional) syncen — gemeinsam für Editor, Drag & Undo
  window.ganttSetBar = function (bar, left, width, doSync) {
    if (!bar) return;
    left = Math.round(left); width = Math.round(width);
    bar.style.left = left + 'px';
    bar.style.width = width + 'px';
    var pl = parseInt(bar.dataset.planLeft, 10), pw = parseInt(bar.dataset.planWidth, 10);
    if (!isNaN(pl) && !isNaN(pw)) bar.classList.toggle('shifted', left !== pl || width !== pw);
    var tr = bar.closest('tr.task-row'); if (!tr) return;
    var tid = tr.getAttribute('data-tid') || '';
    if (tid) localStorage.setItem('bar-pos-' + tid, JSON.stringify({ left: left, width: width }));
    if (doSync && window.PlanSync && !window.PlanSync.isApplyingRemote()) {
      var cid = tr.getAttribute('data-client-id');
      if (cid || tr.getAttribute('data-custom') === '1') window.PlanSync.pushCustomUpdate(cid || tid, { bar_left: left, bar_width: width });
      else { window.PlanSync.pushOverride('task', tid, 'bar_left', String(left)); window.PlanSync.pushOverride('task', tid, 'bar_width', String(width)); }
    }
    if (typeof window.renderKapaHeatStrip === 'function') window.renderKapaHeatStrip();
  };

  // Klick auf gantt-bar öffnet Editor
  document.addEventListener('click', function(e){
    var bar = e.target.closest('.gantt-bar');
    if (!bar) return;
    if (!bar.style.width || bar.style.width === '0px') return;  // ignore empty bars
    // Skip if it was a drag (drag engine sets data-just-dragged)
    if (bar.dataset.justDragged === '1') { delete bar.dataset.justDragged; return; }
    // Skip if click was on a resize handle
    if (e.target.closest('.resize-handle')) return;
    e.preventDefault();
    e.stopPropagation();
    openBarEditor(bar);
  }, true);

  function openBarEditor(bar) {
    currentBar = bar;
    // Tid + Name aus parent <tr>
    var tr = bar.closest('tr.task-row');
    if (!tr) return;
    currentTid = tr.getAttribute('data-tid') || '';
    var nameCell = tr.querySelector('.task-name-cell');
    var name = nameCell ? nameCell.textContent.trim() : '?';

    // Aktuelle Position
    var left = parseInt(bar.style.left, 10) || 0;
    var width = parseInt(bar.style.width, 10) || 0;
    planLeft = parseInt(bar.dataset.planLeft || left, 10);
    planWidth = parseInt(bar.dataset.planWidth || width, 10);
    // Speichere Plan-Default beim ersten Mal
    if (!bar.dataset.planLeft) bar.dataset.planLeft = left;
    if (!bar.dataset.planWidth) bar.dataset.planWidth = width;

    var durWeeks = Math.max(1, Math.round(width / PX_PER_WEEK));  // nur für MH-Hinweis

    document.getElementById('be-task-name').textContent = name;
    document.getElementById('be-von').value = dateFromPx(left);
    document.getElementById('be-bis').value = dateFromPx(left + Math.max(0, width - PX_PER_DAY));  // letzter Tag (inkl.)

    // Mannstunden laden
    var mhInput = document.getElementById('be-mh');
    if (mhInput) {
      var savedMh = localStorage.getItem('task-mh-' + currentTid);
      mhInput.value = savedMh !== null ? savedMh : '40';
      var hint = document.getElementById('be-mh-hint');
      if (hint) {
        var perWeek = Math.round((parseInt(mhInput.value, 10) || 0) / Math.max(1, durWeeks));
        hint.textContent = '≈ ' + perWeek + ' Std/Woche bei ' + durWeeks + ' Wochen Dauer';
      }
      mhInput.oninput = function () {
        var vpx = pxFromDate(document.getElementById('be-von').value);
        var bpx = pxFromDate(document.getElementById('be-bis').value);
        var days = (vpx != null && bpx != null) ? (Math.round((bpx - vpx) / PX_PER_DAY) + 1) : 7;
        var wks = Math.max(1, Math.round(days / 7));
        var v = parseInt(this.value, 10) || 0;
        var h2 = document.getElementById('be-mh-hint');
        if (h2) h2.textContent = '≈ ' + Math.round(v / wks) + ' Std/Woche (' + days + ' Tage)';
      };
    }

    beRecalc();
    document.getElementById('bar-editor-modal').style.display = 'flex';
  }

  window.openBarEditor = openBarEditor;
  window.closeBarEditor = function(){
    document.getElementById('bar-editor-modal').style.display = 'none';
    currentBar = null;
  };

  window.beRecalc = function() {
    var von = document.getElementById('be-von').value;
    var bis = document.getElementById('be-bis').value;
    var vpx = pxFromDate(von), bpx = pxFromDate(bis);
    var info = document.getElementById('be-info');
    if (vpx == null || bpx == null) { info.textContent = ''; return; }
    var days = Math.round((bpx - vpx) / PX_PER_DAY) + 1;
    if (days < 1) { info.innerHTML = '<span style="color:#dc2626">⚠ Ende muss am/nach dem Beginn liegen</span>'; return; }
    info.innerHTML = 'Beginn: <b>' + fmtDate(von) + '</b> &nbsp;&nbsp;Ende: <b>' + fmtDate(bis) + '</b> &nbsp;&nbsp;Dauer: <b>' + days + ' Tag' + (days === 1 ? '' : 'e') + '</b>';
  };

  window.beApply = function() {
    if (!currentBar) return;
    var vpx = pxFromDate(document.getElementById('be-von').value);
    var bpx = pxFromDate(document.getElementById('be-bis').value);
    if (vpx == null || bpx == null || bpx < vpx) { alert('Bitte Beginn und Ende wählen (Ende am/nach Beginn).'); return; }
    var newLeft = Math.max(0, vpx);
    var newWidth = (bpx - newLeft) + PX_PER_DAY;   // inkl. letztem Tag

    // Undo: vorherigen Zustand sichern (vor dem Setzen)
    var oldLeft = parseInt(currentBar.style.left, 10) || 0;
    var oldWidth = parseInt(currentBar.style.width, 10) || 0;
    var barRef = currentBar;
    if (window.pushUndo && (oldLeft !== newLeft || oldWidth !== newWidth)) {
      window.pushUndo({ label: 'Balken-Datum geändert', undo: function () { window.ganttSetBar(barRef, oldLeft, oldWidth, true); } });
    }

    window.ganttSetBar(currentBar, newLeft, newWidth, true);

    // Mannstunden speichern + KV-Sync → Kapa-Kalender aktualisiert sich
    if (currentTid) {
      var mhEl = document.getElementById('be-mh');
      if (mhEl) {
        var mhVal = String(parseInt(mhEl.value, 10) || 0);
        var mhKey = 'task-mh-' + currentTid;
        localStorage.setItem(mhKey, mhVal);
        if (window.__syncKV) window.__syncKV(mhKey, mhVal);
        if (typeof window.kapReload === 'function') window.kapReload();
        else if (typeof window.renderKalender === 'function') window.renderKalender();
      }
    }
    closeBarEditor();
  };

  window.beReset = function() {
    if (!currentBar) return;
    var l = currentBar.dataset.planLeft;
    var w = currentBar.dataset.planWidth;
    if (l && w) {
      currentBar.style.left = l + 'px';
      currentBar.style.width = w + 'px';
      currentBar.classList.remove('shifted');
    }
    if (currentTid) {
      localStorage.removeItem('bar-pos-' + currentTid);
      localStorage.removeItem('bar-deadline-' + currentTid);
    }
    closeBarEditor();
  };

  // ESC schließt Modal
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && document.getElementById('bar-editor-modal').style.display === 'flex') {
      closeBarEditor();
    }
  });

  // Beim Laden: gespeicherte Bar-Positionen wiederherstellen
  window.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){
      document.querySelectorAll('tr.task-row[data-tid]').forEach(function(tr){
        var tid = tr.getAttribute('data-tid');
        var saved = localStorage.getItem('bar-pos-' + tid);
        if (!saved) return;
        try {
          var p = JSON.parse(saved);
          var bar = tr.querySelector('.gantt-bar');
          if (bar) {
            // Save plan default first
            if (!bar.dataset.planLeft) bar.dataset.planLeft = parseInt(bar.style.left, 10) || 0;
            if (!bar.dataset.planWidth) bar.dataset.planWidth = parseInt(bar.style.width, 10) || 0;
            bar.style.left = p.left + 'px';
            bar.style.width = p.width + 'px';
            if (p.left != bar.dataset.planLeft || p.width != bar.dataset.planWidth) {
              bar.classList.add('shifted');
            }
          }
        } catch(e) {}
      });
    }, 300);
  });

  // Hover-Effekt: zeige dass Bar klickbar ist
  document.addEventListener('mouseover', function(e){
    var bar = e.target.closest('.gantt-bar');
    if (bar && !bar.dataset.editTip) {
      bar.title = '📅 Klick zum Bearbeiten von Start, Dauer und Deadline';
      bar.style.cursor = 'pointer';
      bar.dataset.editTip = '1';
    }
  });
})();
</script>


<script id="gantt-drag-resize-engine">
(function(){
  var ORIGIN_KW = 23;
  var PX_PER_WEEK = 126;

  function pxToKw(px) { return ORIGIN_KW + Math.round(px / PX_PER_WEEK); }
  // In Tagesansicht auf Tage (6px) rasten, sonst auf Wochen (42px)
  function snapUnit() { return PX_PER_WEEK / 7; }   // tag-genau (18px), fester Tages-Maßstab
  function snapToWeek(px) { var u = snapUnit(); return Math.round(px / u) * u; }

  var dragState = null; // {mode: 'drag'|'resize-l'|'resize-r', bar, startX, startLeft, startWidth, planLeft, planWidth}
  var infoDiv = null;

  function showInfo(x, y, text) {
    if (!infoDiv) {
      infoDiv = document.createElement('div');
      infoDiv.className = 'gantt-drag-info';
      document.body.appendChild(infoDiv);
    }
    infoDiv.style.left = (x + 12) + 'px';
    infoDiv.style.top = (y + 12) + 'px';
    infoDiv.innerHTML = text;
    infoDiv.style.display = 'block';
  }
  function hideInfo() { if (infoDiv) infoDiv.style.display = 'none'; }

  // Add resize handles to all bars on load
  function addHandles() {
    document.querySelectorAll('.gantt-bar').forEach(function(bar){
      if (bar.dataset.handlesAdded) return;
      if (!bar.style.width || parseInt(bar.style.width,10) === 0) return;
      var hL = document.createElement('div'); hL.className = 'resize-handle left';
      var hR = document.createElement('div'); hR.className = 'resize-handle right';
      bar.appendChild(hL);
      bar.appendChild(hR);
      bar.dataset.handlesAdded = '1';
      // Save plan defaults
      if (!bar.dataset.planLeft) bar.dataset.planLeft = parseInt(bar.style.left, 10) || 0;
      if (!bar.dataset.planWidth) bar.dataset.planWidth = parseInt(bar.style.width, 10) || 0;
    });
  }
  setTimeout(addHandles, 300);
  // Auch nach möglichem späteren Render
  var addObserver = new MutationObserver(function(){ addHandles(); });
  addObserver.observe(document.body, {childList: true, subtree: true});

  // mousedown auf bar oder handle
  document.addEventListener('mousedown', function(e){
    var bar = e.target.closest('.gantt-bar');
    if (!bar) return;
    if (!bar.style.width || parseInt(bar.style.width,10) === 0) return;

    // ggf. resize handle?
    var handle = e.target.closest('.resize-handle');
    var mode = 'drag';
    if (handle) mode = handle.classList.contains('left') ? 'resize-l' : 'resize-r';

    e.preventDefault();
    e.stopPropagation();

    dragState = {
      mode: mode,
      bar: bar,
      startX: e.clientX,
      startLeft: parseInt(bar.style.left, 10) || 0,
      startWidth: parseInt(bar.style.width, 10) || 0,
      planLeft: parseInt(bar.dataset.planLeft, 10) || 0,
      planWidth: parseInt(bar.dataset.planWidth, 10) || 0,
      moved: false
    };
    bar.classList.add('drag-active');
  }, true);

  document.addEventListener('mousemove', function(e){
    if (!dragState) return;
    e.preventDefault();
    // In Tagesansicht ist der Inhalt visuell um GANTT_Z gestreckt → Bildschirm-Delta
    // auf Basis-Koordinaten (42px/Woche) zurückrechnen, sonst springt der Balken.
    var dx = (e.clientX - dragState.startX) / (window.GANTT_Z || 1);
    var bar = dragState.bar;
    var newLeft = dragState.startLeft;
    var newWidth = dragState.startWidth;

    if (dragState.mode === 'drag') {
      newLeft = snapToWeek(dragState.startLeft + dx);
      if (newLeft < 0) newLeft = 0;
    } else if (dragState.mode === 'resize-l') {
      newLeft = snapToWeek(dragState.startLeft + dx);
      if (newLeft < 0) newLeft = 0;
      newWidth = dragState.startWidth - (newLeft - dragState.startLeft);
      var minWl = snapUnit();
      if (newWidth < minWl) { newWidth = minWl; newLeft = dragState.startLeft + dragState.startWidth - minWl; }
    } else if (dragState.mode === 'resize-r') {
      newWidth = snapToWeek(dragState.startWidth + dx);
      if (newWidth < snapUnit()) newWidth = snapUnit();
    }

    bar.style.left = newLeft + 'px';
    bar.style.width = newWidth + 'px';
    if (Math.abs(newLeft - dragState.startLeft) >= snapUnit()/2 ||
        Math.abs(newWidth - dragState.startWidth) >= snapUnit()/2) {
      dragState.moved = true;
    }
    // Info-Tooltip: in Tagesansicht datums-/tag-genau, sonst KW/Wochen
    var infoHtml;
    if (typeof window.ganttDayLabel === 'function') {
      var pd = PX_PER_WEEK / 7;
      var days = Math.max(1, Math.round(newWidth / pd));
      infoHtml = '<b>' + window.ganttDayLabel(newLeft) + ' – ' + window.ganttDayLabel(newLeft + newWidth - pd) + '</b><br>Dauer: ' + days + ' Tag' + (days === 1 ? '' : 'e');
    } else {
      var startKw = pxToKw(newLeft);
      var endKw = pxToKw(newLeft + newWidth) - 1;
      var dur = Math.round(newWidth / PX_PER_WEEK);
      infoHtml = '<b>KW ' + startKw + '–' + endKw + '</b><br>Dauer: ' + dur + ' Wochen';
    }
    showInfo(e.clientX, e.clientY, infoHtml +
      (dragState.mode === 'resize-l' ? ' &nbsp;|&nbsp; ←Start' :
       dragState.mode === 'resize-r' ? ' &nbsp;|&nbsp; Ende→' : ' &nbsp;|&nbsp; ↔ Verschieben'));
  });

  document.addEventListener('mouseup', function(e){
    if (!dragState) return;
    var bar = dragState.bar;
    bar.classList.remove('drag-active');
    hideInfo();

    if (dragState.moved) {
      bar.dataset.justDragged = '1';
      setTimeout(function(){ delete bar.dataset.justDragged; }, 200);
      var newLeft = parseInt(bar.style.left, 10);
      var newWidth = parseInt(bar.style.width, 10);

      // shifted-Klasse je nach Plan-Default
      if (newLeft !== dragState.planLeft || newWidth !== dragState.planWidth) {
        bar.classList.add('shifted');
      } else {
        bar.classList.remove('shifted');
      }

      // Persist
      var tr = bar.closest('tr.task-row');
      if (tr) {
        var tid = tr.getAttribute('data-tid');
        if (tid) {
          localStorage.setItem('bar-pos-' + tid, JSON.stringify({left: newLeft, width: newWidth}));
        }
        // An den Live-Sync weitergeben
        if (window.PlanSync && !window.PlanSync.isApplyingRemote()) {
          var cid = tr.getAttribute('data-client-id');
          if (cid || tr.getAttribute('data-custom') === '1') {
            window.PlanSync.pushCustomUpdate(cid || tid, { bar_left: newLeft, bar_width: newWidth });
          } else if (tid) {
            window.PlanSync.pushOverride('task', tid, 'bar_left', String(newLeft));
            window.PlanSync.pushOverride('task', tid, 'bar_width', String(newWidth));
          }
        }
      }

      // Undo: Drag/Resize rückgängig machbar (⌘Z / ↺-FAB)
      (function () {
        var oldL = dragState.startLeft, oldW = dragState.startWidth, barRef = bar;
        if (window.pushUndo && (newLeft !== oldL || newWidth !== oldW)) {
          window.pushUndo({ label: 'Balken verschoben', undo: function () { window.ganttSetBar(barRef, oldL, oldW, true); } });
        }
      })();

      // VERKETTUNG: Folge-Tasks innerhalb gleicher Einheit zeigen anbieten
      if (dragState.mode !== 'resize-r' && tr) {
        var deltaLeft = newLeft - dragState.startLeft;
        if (deltaLeft !== 0) {
          maybeChainShift(tr, bar, deltaLeft);
        }
      } else if (dragState.mode === 'resize-r') {
        // Bei Resize rechts wird Ende verschoben — nachfolgende Tasks könnten betroffen sein
        var newEndPx = newLeft + newWidth;
        var oldEndPx = dragState.startLeft + dragState.startWidth;
        var deltaEnd = newEndPx - oldEndPx;
        if (deltaEnd > 0 && tr) {
          // Folge-Tasks schieben falls überlappend
          maybeChainShift(tr, bar, deltaEnd, true);
        }
      }
    }
    dragState = null;
  });

  function maybeChainShift(tr, bar, delta, isFromResize) {
    // Folge-Tasks ermitteln: zuerst data-unit, sonst Gewerk-Kette
    var unit = tr.getAttribute('data-unit');
    var gewerk = tr.getAttribute('data-gewerk');
    var allRows, scopeLbl;
    if (unit) {
      allRows = Array.from(document.querySelectorAll('tr.task-row[data-unit="' + unit + '"]'));
      scopeLbl = 'Einheit ' + unit;
    } else if (gewerk) {
      // Gewerk-Chain: alle Tasks mit gleichem (normiertem) Gewerk, deren Bar zeitlich nach der aktuellen liegt
      var nrm = (typeof window.mapGewerk === 'function') ? window.mapGewerk(gewerk) : gewerk;
      var movedLeft = parseInt(bar.style.left, 10) || 0;
      allRows = Array.from(document.querySelectorAll('tr.task-row[data-gewerk]')).filter(function (other) {
        var og = other.getAttribute('data-gewerk');
        var ogN = (typeof window.mapGewerk === 'function') ? window.mapGewerk(og) : og;
        if (ogN !== nrm) return false;
        var ob = other.querySelector('.gantt-bar');
        if (!ob || !ob.style.width) return false;
        var ol = parseInt(ob.style.left, 10) || 0;
        // Nur nachfolgende Tasks (Bar startet später) — und nicht die aktuelle Zeile
        return other !== tr && ol >= movedLeft;
      });
      // Sortieren nach left aufsteigend
      allRows.sort(function(a, b){
        return (parseInt(a.querySelector('.gantt-bar').style.left, 10)||0)
             - (parseInt(b.querySelector('.gantt-bar').style.left, 10)||0);
      });
      // tr selbst noch davor einfügen, damit der idx-Logik unten funktioniert
      allRows.unshift(tr);
      scopeLbl = 'Gewerk ' + nrm;
    } else { return; }
    var idx = allRows.indexOf(tr);
    if (idx < 0 || idx === allRows.length - 1) return;
    var followers = allRows.slice(idx + 1);
    if (!followers.length) return;

    // Frage Nutzer
    var msg = followers.length + ' Folge-Aufgabe' + (followers.length>1?'n':'') + ' für ' + scopeLbl +
              ' um ' + (delta > 0 ? '+' : '') + Math.round(delta/PX_PER_WEEK) + ' Wochen verschieben?';
    if (!confirm(msg)) return;

    followers.forEach(function(ftr){
      var fbar = ftr.querySelector('.gantt-bar');
      if (!fbar || !fbar.style.width) return;
      if (!fbar.dataset.planLeft) fbar.dataset.planLeft = parseInt(fbar.style.left,10) || 0;
      if (!fbar.dataset.planWidth) fbar.dataset.planWidth = parseInt(fbar.style.width,10) || 0;
      var fl = (parseInt(fbar.style.left,10) || 0) + delta;
      fbar.style.left = fl + 'px';
      fbar.classList.add('shifted');
      // Persist
      var ftid = ftr.getAttribute('data-tid');
      var fw = parseInt(fbar.style.width,10) || 0;
      if (ftid) {
        localStorage.setItem('bar-pos-' + ftid, JSON.stringify({left: fl, width: fw}));
      }
      // An den Live-Sync weitergeben
      if (window.PlanSync && !window.PlanSync.isApplyingRemote()) {
        var fcid = ftr.getAttribute('data-client-id');
        if (fcid || ftr.getAttribute('data-custom') === '1') {
          window.PlanSync.pushCustomUpdate(fcid || ftid, { bar_left: fl, bar_width: fw });
        } else if (ftid) {
          window.PlanSync.pushOverride('task', ftid, 'bar_left', String(fl));
          window.PlanSync.pushOverride('task', ftid, 'bar_width', String(fw));
        }
      }
    });
  }

  // Hover-Effekt: zeige Ränder + Cursor
  document.addEventListener('mouseover', function(e){
    var bar = e.target.closest('.gantt-bar');
    if (bar && !bar.dataset.hoverTip) {
      bar.title = '🖱 Klick = Editor · Ziehen = Verschieben · Ränder = Dauer ändern';
      bar.style.cursor = 'grab';
      bar.dataset.hoverTip = '1';
    }
  });

  // Verhindere dass click-to-edit feuert nach drag
  var lastDragEnd = 0;
  document.addEventListener('mouseup', function(){ lastDragEnd = Date.now(); }, true);
  document.addEventListener('click', function(e){
    // If we just finished a drag (within last 200ms), block click handler from firing modal
    var bar = e.target.closest('.gantt-bar');
    if (!bar) return;
    if (Date.now() - lastDragEnd < 100 && dragState === null) {
      // a click immediately after drag - check if bar was moved
      // We let modal open only if no movement happened
    }
  }, false);

})();
</script>


<!-- ═══════════════ NEW TASK BUTTON + MODAL ═══════════════ -->
<button class="btn-new-task" onclick="openNewTaskModal()" title="Neue Aufgabe">+</button>

<div id="new-task-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:480px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h3 style="margin:0;font-size:15px;font-weight:700;color:#1e293b">➕ Neue Aufgabe hinzufügen</h3>
      <button onclick="closeNewTaskModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">×</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div style="grid-column:1/-1">
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Aufgabe *</label>
        <input id="nt-name" type="text" placeholder="z.B. Sanitär Endmontage" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Einheit (data-unit)</label>
        <select id="nt-unit" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
          <option value="">— keine —</option>
          <option>T_4_01</option><option>T_4_02</option><option>T_4_03</option><option>T_4_04</option>
          <option>T_4_05</option><option>T_4_06</option><option>T_4_07</option><option>T_4_08</option>
          <option>T_4_09</option><option>T_4_10</option><option>T_4_11</option><option>T_4_12</option>
          <option>T_4_13</option><option>T_4_14</option><option>T_4_16</option><option>T_4_18</option>
          <option>T_4_20</option><option>T_4_22</option>
          <option>W_5_1</option><option>W_5_2</option><option>W_5_3</option><option>T_5_1</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Gewerk</label>
        <select id="nt-gewerk" onchange="if(this.value==='__add__'){ var nm=prompt('Neues Gewerk:'); if(nm){ window.addGewerk(nm); this.value=nm; } else { this.value=''; } }" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
          <option value="">—</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Firma</label>
        <input id="nt-firma" type="text" placeholder="optional" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Status</label>
        <select id="nt-status" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
          <option value="geplant">geplant</option>
          <option value="laufend">laufend</option>
          <option value="abgeschlossen">abgeschlossen</option>
          <option value="verzögert">verzögert</option>
        </select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Start KW</label>
        <input id="nt-start" type="number" min="1" max="80" placeholder="z.B. 26" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Dauer (Wochen)</label>
        <input id="nt-duration" type="number" min="1" max="52" value="2" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
      </div>
      <div style="grid-column:1/-1">
        <label style="font-size:11px;font-weight:600;color:#64748b;display:block;margin-bottom:4px">Einfügen nach Sektion (optional)</label>
        <select id="nt-section" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px">
          <option value="end">— am Ende der Liste —</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
      <button onclick="closeNewTaskModal()" style="padding:8px 14px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12px">Abbrechen</button>
      <button onclick="ntCreate()" style="padding:8px 14px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600">✓ Aufgabe anlegen</button>
    </div>
  </div>
</div>

<script id="inline-edit-engine">
(function(){
  // ── Gewerk-Liste mit Farben (gemeinsam für Hauptplan + Bestellungen + Kapazität) ──
  var DEFAULT_GEWERKE = [
    {name:'Sanitär/Heizung', bg:'#dbeafe', fg:'#2563eb'},
    {name:'Elektro',         bg:'#fed7aa', fg:'#d97706'},
    {name:'Maler/Gipser',    bg:'#fed7aa', fg:'#ea580c'},
    {name:'Trockenbau',      bg:'#dbeafe', fg:'#6366f1'},
    {name:'Bodenbelag',      bg:'#fef3c7', fg:'#92400e'},
    {name:'Fliesen',         bg:'#ccfbf1', fg:'#0f766e'},
    {name:'Estrich',         bg:'#e9d5ff', fg:'#7c3aed'},
    {name:'Schreiner/Endmontage',bg:'#fde68a', fg:'#78350f'},
    {name:'Brandschutz',     bg:'#fee2e2', fg:'#991b1b'},
    {name:'Dach/Fassade',    bg:'#d1fae5', fg:'#065f46'},
    {name:'Planung/Architekt',bg:'#e0e7ff', fg:'#4338ca'},
    {name:'Sonstige',        bg:'#f1f5f9', fg:'#64748b'},
  ];
  function harvestExtraGewerke(arr) {
    var have = {};
    arr.forEach(function(g){ have[g.name] = true; });
    // aus Bestellungen
    (window.boOrders || []).forEach(function(o){
      if (o.gewerk && !have[o.gewerk]) {
        arr.push({ name: o.gewerk, bg: '#f1f5f9', fg: '#475569' });
        have[o.gewerk] = true;
      }
    });
    // aus Hauptplan-Zeilen
    document.querySelectorAll('tr.task-row').forEach(function(tr){
      var g = tr.getAttribute('data-gewerk');
      if (g && !have[g]) {
        arr.push({ name: g, bg: '#f1f5f9', fg: '#475569' });
        have[g] = true;
      }
    });
  }
  function loadGewerke() {
    var arr;
    try {
      var saved = JSON.parse(localStorage.getItem('gewerke-list-v1') || 'null');
      arr = (Array.isArray(saved) && saved.length) ? saved.slice() : DEFAULT_GEWERKE.slice();
    } catch (e) { arr = DEFAULT_GEWERKE.slice(); }
    harvestExtraGewerke(arr);
    return arr;
  }
  var GEWERKE = loadGewerke();
  window.GEWERKE = GEWERKE;
  // Liste alphabetisch sortieren (deutsch, ignore-case)
  function sortGewerke() {
    GEWERKE.sort(function(a, b){
      return (a.name || '').localeCompare(b.name || '', 'de', { sensitivity: 'base' });
    });
  }
  // Liste persistieren + synchronisieren
  window.saveGewerkeList = function () {
    sortGewerke();
    var json = JSON.stringify(GEWERKE);
    localStorage.setItem('gewerke-list-v1', json);
    if (window.__syncKV) window.__syncKV('gewerke-list-v1', json);
  };
  // Initial sortieren
  sortGewerke();
  // Neues Gewerk hinzufügen (überall verfügbar)
  window.addGewerk = function (name, bg, fg) {
    name = (name || '').trim();
    if (!name) return null;
    var ex = GEWERKE.find(function(g){ return g.name === name; });
    if (ex) return ex;
    var g = { name: name, bg: bg || '#f1f5f9', fg: fg || '#475569' };
    GEWERKE.push(g);
    window.saveGewerkeList();
    window.populateGewerkSelects();
    return g;
  };
  // Gewerk löschen (Datensätze mit dem Namen bleiben, verlieren aber die Farbe)
  window.removeGewerkByName = function (name) {
    var idx = GEWERKE.findIndex(function(g){ return g.name === name; });
    if (idx < 0) return false;
    GEWERKE.splice(idx, 1);
    window.saveGewerkeList();
    window.populateGewerkSelects();
    return true;
  };
  // Helfer: aus Farb-Hex (#rrggbb) leichten Hintergrund ableiten
  function softenHex(hex) {
    hex = (hex || '#475569').replace('#','');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    var r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
    // mit Weiß mischen (85% Weiß)
    r = Math.round(r*0.18 + 255*0.82);
    g = Math.round(g*0.18 + 255*0.82);
    b = Math.round(b*0.18 + 255*0.82);
    return '#' + [r,g,b].map(function(x){var h=x.toString(16); return h.length===1?'0'+h:h;}).join('');
  }
  // Wenn ein Gewerk gelöscht wird: alle Referenzen "leeren" (Aufgaben, Bestellungen, Mitarbeiter)
  function cascadeClearGewerk(oldName) {
    if (!oldName) return;
    var empty = { name: '', bg: '#f1f5f9', fg: '#475569' };
    document.querySelectorAll('tr.task-row[data-gewerk="' + (window.CSS && CSS.escape ? CSS.escape(oldName) : oldName.replace(/"/g,'\\"')) + '"]').forEach(function(tr){
      var span = tr.querySelector('.gewerk-badge') || (tr.children[2] && tr.children[2].querySelector('span'));
      if (span && typeof window.applyGewerk === 'function') window.applyGewerk(span, tr, empty);
      else tr.setAttribute('data-gewerk', '');
    });
    if (window.boOrders && window.boOrders.length) {
      var anyOrder = false;
      window.boOrders.forEach(function(o){ if (o.gewerk === oldName) { o.gewerk = ''; anyOrder = true; } });
      if (anyOrder) {
        var json = JSON.stringify(window.boOrders);
        localStorage.setItem('bo-orders-v3', json);
        if (window.__syncKV) window.__syncKV('bo-orders-v3', json);
        if (typeof window.renderOrders === 'function') window.renderOrders();
        if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
      }
    }
    if (typeof window.cascadeClearKapEmployees === 'function') {
      window.cascadeClearKapEmployees(oldName);
    }
  }

  // Wenn ein Gewerk umbenannt wird: alle Referenzen mitschleppen (Aufgaben, Bestellungen, Mitarbeiter)
  function cascadeRenameGewerk(oldName, newName) {
    if (!oldName || !newName || oldName === newName) return;
    // 1) Hauptzeitplan-Aufgaben mit data-gewerk=oldName → applyGewerk(newName) → updates DOM + Sync
    var tmpG = { name: newName, bg: '#f1f5f9', fg: '#475569' };
    var found = GEWERKE.find(function(g){ return g.name === newName; });
    if (found) tmpG = found;
    document.querySelectorAll('tr.task-row[data-gewerk="' + (window.CSS && CSS.escape ? CSS.escape(oldName) : oldName.replace(/"/g,'\\"')) + '"]').forEach(function(tr){
      var span = tr.querySelector('.gewerk-badge') || (tr.children[2] && tr.children[2].querySelector('span'));
      if (span && typeof window.applyGewerk === 'function') window.applyGewerk(span, tr, tmpG);
      else tr.setAttribute('data-gewerk', newName);
    });
    // 2) Bestellungen
    if (window.boOrders && window.boOrders.length) {
      var anyOrder = false;
      window.boOrders.forEach(function(o){ if (o.gewerk === oldName) { o.gewerk = newName; anyOrder = true; } });
      if (anyOrder) {
        var json = JSON.stringify(window.boOrders);
        localStorage.setItem('bo-orders-v3', json);
        if (window.__syncKV) window.__syncKV('bo-orders-v3', json);
        if (typeof window.renderOrders === 'function') window.renderOrders();
        if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
      }
    }
    // 3) Mitarbeiter (Closure-Variable in der Kapa-IIFE)
    if (typeof window.cascadeRenameKapEmployees === 'function') {
      window.cascadeRenameKapEmployees(oldName, newName);
    }
  }

  // Gewerke-Verwaltungs-Modal
  window.openGewerkeManager = function () {
    var existing = document.getElementById('gewerke-manager-overlay');
    if (existing) existing.remove();
    var bd = document.createElement('div');
    bd.id = 'gewerke-manager-overlay';
    bd.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);z-index:99998;display:flex;align-items:center;justify-content:center;font-family:Inter,sans-serif';
    var md = document.createElement('div');
    md.style.cssText = 'background:#fff;border-radius:14px;padding:0;width:560px;max-width:90vw;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden';
    md.innerHTML =
        '<div style="padding:14px 20px;border-bottom:1px solid #e8e9ed;display:flex;justify-content:space-between;align-items:center">'
      +   '<div><h2 style="margin:0;font-size:15px;font-weight:800">🔧 Gewerke verwalten</h2>'
      +   '<div style="font-size:11px;color:#64748b;margin-top:2px">Name + Farbe ändern · Löschen · neu anlegen — wirkt überall</div></div>'
      +   '<button id="gv-close-x" style="background:#f1f5f9;border:none;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">✕</button>'
      + '</div>'
      + '<div id="gv-list" style="padding:8px 16px;overflow-y:auto;flex:1"></div>'
      + '<div style="padding:12px 20px;border-top:1px solid #e8e9ed;display:flex;gap:8px;justify-content:space-between;align-items:center;background:#f8fafc">'
      +   '<button id="gv-add" style="padding:7px 14px;background:#16a34a;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">+ Neues Gewerk</button>'
      +   '<button id="gv-done" style="padding:7px 14px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">Fertig</button>'
      + '</div>';
    bd.appendChild(md);
    document.body.appendChild(bd);

    function refresh() {
      var lst = md.querySelector('#gv-list');
      if (!GEWERKE.length) { lst.innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:12px">Noch keine Gewerke. Klick auf "+ Neues Gewerk".</div>'; return; }
      lst.innerHTML = GEWERKE.map(function(g, i){
        return '<div data-i="' + i + '" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9">'
          + '<input type="color" value="' + g.fg + '" data-i="' + i + '" data-f="fg" title="Farbe ändern" style="width:34px;height:34px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;padding:0;background:#fff">'
          + '<span class="gv-preview" style="display:inline-block;width:14px;height:14px;border-radius:50%;background:' + g.fg + ';flex-shrink:0"></span>'
          + '<input value="' + (g.name || '').replace(/"/g,'&quot;') + '" data-i="' + i + '" data-f="name" placeholder="Name" style="flex:1;border:1px solid #e2e8f0;border-radius:6px;padding:6px 8px;font-size:12px;font-weight:600">'
          + '<button data-i="' + i + '" class="gv-del" title="Löschen" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:11px;font-weight:700">🗑</button>'
        + '</div>';
      }).join('');
      lst.querySelectorAll('input').forEach(function(inp){
        inp.addEventListener('change', function(){
          var i = +this.dataset.i, f = this.dataset.f;
          if (!GEWERKE[i]) return;
          var v = this.value;
          if (f === 'name') {
            var oldName = GEWERKE[i].name;
            var newName = v.trim();
            if (newName && newName !== oldName) {
              // Alle Referenzen mit umbenennen (Aufgaben, Bestellungen, Mitarbeiter)
              cascadeRenameGewerk(oldName, newName);
              GEWERKE[i].name = newName;
            }
          } else if (f === 'fg') { GEWERKE[i].fg = v; GEWERKE[i].bg = softenHex(v); }
          window.saveGewerkeList();
          window.populateGewerkSelects();
          refresh();
        });
      });
      lst.querySelectorAll('.gv-del').forEach(function(btn){
        btn.addEventListener('click', function(){
          var i = +this.dataset.i;
          if (!GEWERKE[i]) return;
          var delName = GEWERKE[i].name;
          if (!confirm('Gewerk "' + delName + '" löschen?\n\nZugewiesene Aufgaben, Bestellungen und Mitarbeiter behalten ihren Eintrag, das Gewerk wird aber GELEERT — du musst dann ein neues Gewerk zuweisen.')) return;
          // Erst alle Referenzen leeren, dann aus der Liste entfernen
          cascadeClearGewerk(delName);
          GEWERKE.splice(i, 1);
          window.saveGewerkeList();
          window.populateGewerkSelects();
          refresh();
        });
      });
    }
    md.querySelector('#gv-add').onclick = function(){
      var nm = prompt('Name des neuen Gewerks:');
      if (!nm) return;
      window.addGewerk(nm);
      refresh();
    };
    md.querySelector('#gv-close-x').onclick = function(){ bd.remove(); };
    md.querySelector('#gv-done').onclick = function(){ bd.remove(); };
    bd.addEventListener('click', function(e){ if (e.target === bd) bd.remove(); });
    refresh();
  };
  // Hauptzeitplan-Filter-Pills neu rendern
  window.renderGewerkFilter = function () {
    var cont = document.getElementById('gewerk-filter-pills');
    if (!cont) return;
    cont.innerHTML = GEWERKE.filter(function(g){ return g.name; }).map(function(g){
      var name = g.name.replace(/'/g, "\\'");
      return '<button class="filter-btn gw-filter-btn" '
        + 'style="border-color:' + g.fg + '40;--gw-color:' + g.fg + ';--gw-bg:' + g.bg + '" '
        + 'onclick="filterGewerk(\'' + name + '\')">'
        + '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + g.fg + ';margin-right:4px;vertical-align:middle"></span>'
        + g.name + '</button>';
    }).join('');
  };

  // Alle Gewerk-Dropdowns/Re-Render auf einen Stand bringen
  window.populateGewerkSelects = function () {
    window.renderGewerkFilter();
    var names = GEWERKE.map(function(g){return g.name;}).filter(function(n){return n;});
    var opts = names.map(function(n){ return '<option value="'+n+'">'+n+'</option>'; }).join('');
    var addOpt = '<option value="__add__" style="font-style:italic;color:#2563eb">+ Neues Gewerk…</option>';
    // Bestellungen-Filter
    var f = document.getElementById('bo-gewerk-filter');
    if (f) {
      var cur = f.value;
      f.innerHTML = '<option value="">Alle Gewerke</option>' + opts;
      if (cur && names.indexOf(cur) !== -1) f.value = cur;
    }
    // Bestellungen-Modal
    var m = document.getElementById('bo-m-gewerk');
    if (m && m.tagName === 'SELECT') {
      var cur2 = m.value;
      m.innerHTML = opts + addOpt;
      if (cur2 && names.indexOf(cur2) !== -1) m.value = cur2;
    }
    // Neue-Aufgabe-Modal
    var n = document.getElementById('nt-gewerk');
    if (n) {
      var cur3 = n.value;
      n.innerHTML = '<option value="">—</option>' + opts + addOpt;
      if (cur3 && names.indexOf(cur3) !== -1) n.value = cur3;
    }
    // Bestellungen-Inline-Dropdowns aktualisieren
    if (typeof renderOrders === 'function') renderOrders();
    // Kapazitäts-Ansicht: Mitarbeiter-Badges + Kalender + Cockpit nachziehen
    if (typeof window.renderMA === 'function') window.renderMA();
    if (typeof window.renderKalender === 'function') window.renderKalender();
    if (typeof window.renderKapaCockpit === 'function') window.renderKapaCockpit();
  };
  // Eingehender KV-Update auf gewerke-list-v1 → Liste tauschen + überall neu rendern
  window.__applyGewerkeKV = function (value) {
    try {
      var arr = JSON.parse(value || '[]');
      if (!Array.isArray(arr)) return;
      GEWERKE.length = 0;
      arr.forEach(function(g){ GEWERKE.push(g); });
      sortGewerke();
      window.populateGewerkSelects();
    } catch (e) {}
  };
  // Beim Laden alle Selects initial befüllen
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ window.populateGewerkSelects(); });
  } else {
    setTimeout(window.populateGewerkSelects, 50);
  }

  // ── 1. Task-Name Cells contenteditable machen ──────────────────────
  function makeNameEditable() {
    document.querySelectorAll('.task-name-cell:not([data-edit-init])').forEach(function(td){
      if (!td.closest('tr.task-row')) return;  // nur Task-Rows, nicht Headers
      td.setAttribute('contenteditable', 'true');
      td.dataset.editInit = '1';
      td.dataset.originalText = td.textContent.trim();
      td.addEventListener('blur', function(){
        var tid = (this.closest('tr.task-row')||{}).getAttribute && this.closest('tr.task-row').getAttribute('data-tid');
        if (tid) {
          localStorage.setItem('task-name-' + tid, this.textContent.trim());
        }
      });
      td.addEventListener('keydown', function(e){
        if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
      });
    });
  }

  // ── 2. Firma Cell contenteditable machen ───────────────────────────
  // Firma-Cell = 4. td in task-row mit max-width:100px style (war Bearbeiter aber das ist weg)
  function makeFirmaEditable() {
    document.querySelectorAll('tr.task-row:not([data-firma-init])').forEach(function(tr){
      var tds = tr.querySelectorAll('td');
      // Reihenfolge nach Bearbeiter-Entfernung: 0=name, 1=status, 2=gewerk, 3=firma, 4=gantt
      if (tds.length < 5) return;
      var firmaTd = tds[3];
      if (!firmaTd.classList.contains('task-firma-cell')) {
        firmaTd.classList.add('task-firma-cell');
        firmaTd.setAttribute('contenteditable', 'true');
        firmaTd.dataset.editInit = '1';
        firmaTd.addEventListener('blur', function(){
          var tid = tr.getAttribute('data-tid');
          if (tid) localStorage.setItem('task-firma-' + tid, this.textContent.trim());
        });
        firmaTd.addEventListener('keydown', function(e){
          if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
        });
      }
      tr.dataset.firmaInit = '1';
    });
  }

  // ── 3. Gewerk-Badge: Dropdown bei Klick (Event-Delegation) ─────────
  function makeGewerkClickable() {
    // Füge "+ Gewerk" placeholder in leere Gewerk-Zellen
    document.querySelectorAll('tr.task-row:not([data-gw-placeholder])').forEach(function(tr){
      var tds = tr.querySelectorAll('td');
      if (tds.length < 5) return;
      var gewerkTd = tds[2];
      var hasSpan = gewerkTd.querySelector('span');
      if (!hasSpan) {
        var emptySpan = document.createElement('span');
        emptySpan.style.cssText = 'display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px dashed #94a3b8;cursor:pointer';
        emptySpan.textContent = '+ Gewerk';
        emptySpan.classList.add('gewerk-badge');
        gewerkTd.appendChild(emptySpan);
      } else {
        hasSpan.classList.add('gewerk-badge');
        hasSpan.style.cursor = 'pointer';
      }
      tr.dataset.gwPlaceholder = '1';
    });
  }

  // EINMALIGER Click-Handler — funktioniert auf ALLEN Zeilen via Spalten-Position
  document.addEventListener('click', function(e){
    // Skip bei resize-handle Klicks
    if (e.target.closest('.resize-handle')) return;
    // Skip wenn auf status-badge (anderes System)
    if (e.target.closest('.status-badge')) return;
    var tr = e.target.closest('tr.task-row');
    if (!tr) return;
    var td = e.target.closest('td');
    if (!td) return;
    var tds = tr.querySelectorAll('td');
    // Spalte 3 = Gewerk (0-indexed: tds[2])
    if (tds[2] !== td) return;
    e.stopPropagation();
    e.preventDefault();
    // Span suchen oder erstellen
    var span = td.querySelector('span');
    if (!span) {
      span = document.createElement('span');
      span.style.cssText = 'display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px dashed #94a3b8;cursor:pointer';
      span.textContent = '+ Gewerk';
      td.appendChild(span);
    }
    showGewerkDropdown(span, tr);
  }, true);

  function showGewerkDropdown(span, tr) {
    // Entferne andere offene Dropdowns
    document.querySelectorAll('.gewerk-dropdown').forEach(function(d){ d.remove(); });
    var dd = document.createElement('div');
    dd.className = 'gewerk-dropdown';
    (window.GEWERKE || GEWERKE).forEach(function(g){
      var item = document.createElement('div');
      item.textContent = g.name || '(kein)';
      item.style.color = g.fg;
      item.addEventListener('click', function(e){
        e.stopPropagation();
        applyGewerk(span, tr, g);
        dd.remove();
      });
      dd.appendChild(item);
    });
    // + Neues Gewerk…
    var addItem = document.createElement('div');
    addItem.textContent = '+ Neues Gewerk…';
    addItem.style.color = '#2563eb';
    addItem.style.fontStyle = 'italic';
    addItem.style.borderTop = '1px solid #e2e8f0';
    addItem.style.marginTop = '4px';
    addItem.style.paddingTop = '6px';
    addItem.addEventListener('click', function(e){
      e.stopPropagation();
      var nm = prompt('Neues Gewerk:');
      if (nm) {
        var g = window.addGewerk(nm);
        if (g) applyGewerk(span, tr, g);
      }
      dd.remove();
    });
    dd.appendChild(addItem);
    document.body.appendChild(dd);
    var rect = span.getBoundingClientRect();
    dd.style.left = rect.left + 'px';
    dd.style.top  = (rect.bottom + 4) + 'px';
    // Außerhalb klicken schließt
    setTimeout(function(){
      document.addEventListener('click', function close(){
        dd.remove();
        document.removeEventListener('click', close);
      });
    }, 10);
  }

  function applyGewerk(span, tr, g) {
    span.textContent = g.name || '+ Gewerk';
    if (g.name) {
      span.style.cssText = 'display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:' + g.bg + ';color:' + g.fg + ';border:1px solid ' + g.fg + '40;cursor:pointer';
    } else {
      span.style.cssText = 'display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;white-space:nowrap;background:#f1f5f9;color:#64748b;border:1px dashed #94a3b8;cursor:pointer';
    }
    span.classList.add('gewerk-badge');
    tr.setAttribute('data-gewerk', g.name);
    var tid = tr.getAttribute('data-tid');
    if (tid) localStorage.setItem('task-gewerk-' + tid, g.name);
    // An den Live-Sync weitergeben (außer wir wenden gerade eine Remote-Änderung an)
    if (window.PlanSync && !window.PlanSync.isApplyingRemote()) {
      var cid = tr.getAttribute('data-client-id');
      if (cid || tr.getAttribute('data-custom') === '1') {
        window.PlanSync.pushCustomUpdate(cid || tid, { gewerk: g.name });
      } else if (tid) {
        window.PlanSync.pushOverride('task', tid, 'gewerk', g.name);
      }
    }
  }
  window.applyGewerk = applyGewerk;

  // ── 4. New Task Modal ────────────────────────────────────────────
  window.openNewTaskModal = function() {
    // Populate section dropdown
    var sel = document.getElementById('nt-section');
    sel.innerHTML = '<option value="end">— am Ende der Liste —</option>';
    document.querySelectorAll('tr.section-row').forEach(function(sr){
      var lbl = sr.querySelector('.section-name');
      if (lbl) {
        var txt = lbl.textContent.trim().replace(/▶/g,'').trim().substring(0, 60);
        var opt = document.createElement('option');
        opt.value = lbl.textContent.trim();
        opt.textContent = txt;
        sel.appendChild(opt);
      }
    });
    document.getElementById('nt-name').value = '';
    document.getElementById('nt-firma').value = '';
    document.getElementById('new-task-modal').style.display = 'flex';
  };
  window.closeNewTaskModal = function() {
    document.getElementById('new-task-modal').style.display = 'none';
  };

  window.ntCreate = function() {
    var name = document.getElementById('nt-name').value.trim();
    if (!name) { alert('Bitte Aufgabennamen eingeben'); return; }
    var unit   = document.getElementById('nt-unit').value;
    var gwName = document.getElementById('nt-gewerk').value;
    var firma  = document.getElementById('nt-firma').value.trim();
    var status = document.getElementById('nt-status').value;
    var startK = parseInt(document.getElementById('nt-start').value, 10);
    var dur    = parseInt(document.getElementById('nt-duration').value, 10);

    var gw = GEWERKE.find(function(g){return g.name === gwName;}) || {name:'', bg:'#f1f5f9', fg:'#64748b'};

    // TID
    var tid = 'custom-' + Date.now();

    // Status-Klasse
    var statCls = {'geplant':'status-planned','laufend':'status-wip','abgeschlossen':'status-done','verzögert':'status-delayed'}[status] || 'status-planned';
    var statTxt = {'geplant':'—','laufend':'•','abgeschlossen':'✓','verzögert':'!'}[status] || '—';

    // Gantt-Position
    var ORIGIN_KW = 23, PX = 42;
    var left = (isNaN(startK) ? 0 : (startK - ORIGIN_KW) * PX);
    var width = (isNaN(dur) ? PX*2 : dur * PX);
    var barCls = 'status-' + (status === 'abgeschlossen' ? 'done' : status === 'laufend' ? 'wip' : status === 'verzögert' ? 'delayed' : 'planned');

    // Gewerk-Span
    var gwSpan = gw.name ? ('<span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:9px;font-weight:600;background:' + gw.bg + ';color:' + gw.fg + ';border:1px solid ' + gw.fg + '40">' + gw.name + '</span>') : '';

    var rowHtml = '<tr class="task-row" data-status="'+status+'" data-gewerk="'+gw.name+'" data-phase="haustechnik" data-unit="'+unit+'" data-task-type="custom" data-tid="'+tid+'">' +
      '<td class="task-name-cell">'+name+'</td>' +
      '<td><span class="status-badge '+statCls+'">'+statTxt+'</span></td>' +
      '<td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">'+gwSpan+'</td>' +
      '<td style="padding:2px 5px;font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px">'+firma+'</td>' +
      '<td><div class="gantt-row-inner" style="width:10800px"><div class="gantt-bar '+barCls+'" style="left:'+left+'px;width:'+width+'px" title="'+name+'"></div></div></td>' +
      '</tr>';

    // Einfügen
    var section = document.getElementById('nt-section').value;
    var tbody = document.querySelector('#tab-hauptwerk tbody');
    if (!tbody) tbody = document.querySelector('#tab-hauptwerk table tbody');
    if (!tbody) { alert('Hauptzeitplan-tbody nicht gefunden'); return; }

    if (section === 'end') {
      tbody.insertAdjacentHTML('beforeend', rowHtml);
    } else {
      // Nach Section einfügen
      var sectionRows = tbody.querySelectorAll('tr.section-row');
      var matchSr = null;
      sectionRows.forEach(function(sr){
        var lbl = sr.querySelector('.section-name');
        if (lbl && lbl.textContent.trim() === section) matchSr = sr;
      });
      if (matchSr) {
        matchSr.insertAdjacentHTML('afterend', rowHtml);
      } else {
        tbody.insertAdjacentHTML('beforeend', rowHtml);
      }
    }

    // Persist als custom-task
    var customTasks = JSON.parse(localStorage.getItem('custom-tasks') || '[]');
    customTasks.push({tid, name, unit, gewerk: gw.name, firma, status, start: startK, duration: dur, section});
    localStorage.setItem('custom-tasks', JSON.stringify(customTasks));

    // Re-init editing on new row
    makeNameEditable();
    makeFirmaEditable();
    makeGewerkClickable();

    closeNewTaskModal();
  };

  // ── 5. Restore inline edits aus localStorage ──────────────────────
  function restoreEdits() {
    document.querySelectorAll('tr.task-row[data-tid]').forEach(function(tr){
      var tid = tr.getAttribute('data-tid');
      var nameTd = tr.querySelector('.task-name-cell');
      var firmaTd = tr.querySelector('.task-firma-cell');
      var name = localStorage.getItem('task-name-' + tid);
      var firma = localStorage.getItem('task-firma-' + tid);
      var gewerk = localStorage.getItem('task-gewerk-' + tid);
      if (nameTd && name) nameTd.textContent = name;
      if (firmaTd && firma) firmaTd.textContent = firma;
      if (gewerk) {
        var gwSpan = (tr.querySelectorAll('td')[2] || {}).querySelector ? tr.querySelectorAll('td')[2].querySelector('span') : null;
        var g = GEWERKE.find(function(x){return x.name === gewerk;}) || {name:gewerk,bg:'#f1f5f9',fg:'#64748b'};
        if (gwSpan) applyGewerk(gwSpan, tr, g);
      }
    });
  }

  // Init
  function initAll() {
    makeNameEditable();
    makeFirmaEditable();
    makeGewerkClickable();
    restoreEdits();
  }
  setTimeout(initAll, 200);
  // Stelle sicher dass alle vorhandenen Gewerk-Badges die Klasse haben
  function ensureGewerkBadgeClass() {
    document.querySelectorAll('tr.task-row').forEach(function(tr){
      var tds = tr.querySelectorAll('td');
      if (tds.length < 5) return;
      var gewerkTd = tds[2];
      // Cursor + Hover-Style für die ganze Zelle
      gewerkTd.style.cursor = 'pointer';
      gewerkTd.title = 'Klick: Gewerk wählen';
      gewerkTd.querySelectorAll('span').forEach(function(sp){
        sp.classList.add('gewerk-badge');
        sp.style.cursor = 'pointer';
      });
    });
  }
  setTimeout(ensureGewerkBadgeClass, 300);
  setTimeout(ensureGewerkBadgeClass, 1000);
  setTimeout(ensureGewerkBadgeClass, 2000);


  // Beobachte neue Rows (nach Add Task)
  var obs = new MutationObserver(function(){ initAll(); });
  setTimeout(function(){
    var tbody = document.querySelector('#tab-hauptwerk table tbody');
    if (tbody) obs.observe(tbody, {childList: true});
  }, 500);
})();
</script>


<script id="toggle-panel-fix">
window.togglePanel = function() {
  var p = document.getElementById('delay-panel');
  var b = document.getElementById('btn-toggle-panel');
  if (!p) { console.warn('delay-panel not found'); return; }
  var hidden = (p.style.display === 'none' || !p.style.display);
  p.style.display = hidden ? 'block' : 'none';
  if (b) b.style.display = hidden ? 'none' : 'flex';
};
</script>


<script id="today-line-dynamic">
(function(){
  // Today-Line v3: nutzt das exakt-gleiche Koordinatensystem wie die Gantt-Bars.
  // Bars sind <div class="gantt-bar" style="left:Xpx"> innerhalb der ersten task-row.
  // Daher: Today-Line wird als position:absolute Kind des TABLE (nicht der wrap)
  // bei left = (X-Offset der ersten task-row gantt-row-inner) + today_px gesetzt.
  // Beim Horizontal-Scroll wandert sie mit dem Table-Content mit (richtige Bewegung).

  var PX_PER_WEEK = 126;
  var ORIGIN = new Date(2026, 5, 1); // 1. Juni 2026 = KW23 Mo (Projektstart)
  ORIGIN.setHours(0,0,0,0);

  // Berechnet Heute-Position. SNAP auf Montag der aktuellen ISO-Woche,
  // damit die Linie immer exakt auf einer KW-Grenze sitzt.
  function todayPx() {
    var t = new Date();
    t.setHours(0,0,0,0);
    var dow = t.getDay();              // 0=So, 1=Mo, ..., 6=Sa
    var offsetToMon = (dow === 0) ? -6 : (1 - dow);
    t.setDate(t.getDate() + offsetToMon);   // jetzt = Montag dieser Woche
    var days = Math.round((t - ORIGIN) / 86400000);
    return (days / 7) * PX_PER_WEEK;
  }
  function todayKW() {
    var t = new Date();
    t.setHours(0,0,0,0);
    var dow = t.getDay();
    var offsetToMon = (dow === 0) ? -6 : (1 - dow);
    t.setDate(t.getDate() + offsetToMon);
    var days = Math.round((t - ORIGIN) / 86400000);
    return 23 + Math.round(days / 7);  // ORIGIN_KW = 23
  }

  function clearOldLines() {
    document.querySelectorAll('.today-line, .today-line-row, #today-line-global, #today-line-gh')
      .forEach(function(el){ el.remove(); });
  }

  function updateTodayLine() {
    clearOldLines();

    var table = document.getElementById('main-gantt');
    if (!table) return;
    // Referenz: erste SICHTBARE task-row (Filter könnte vorhergehende verstecken)
    var refRow = null;
    var allRows = table.querySelectorAll('tbody tr.task-row');
    for (var i = 0; i < allRows.length; i++) {
      var r = allRows[i];
      var hidden = r.style.display === 'none' || r.offsetParent === null;
      if (!hidden) { refRow = r; break; }
    }
    if (!refRow) refRow = allRows[0];
    if (!refRow) return;
    var refInner = refRow.querySelector('.gantt-row-inner');
    if (!refInner) return;

    // Position der gantt-row-inner relativ zum TABLE (nicht zur viewport, nicht zur wrap)
    var tRect = table.getBoundingClientRect();
    var rRect = refInner.getBoundingClientRect();
    var timelineX = rRect.left - tRect.left;  // Px-Offset im Tabellen-Content

    var px = todayPx();
    var kw = todayKW();
    var line = document.createElement('div');
    line.id = 'today-line-global';
    line.className = 'today-line';
    line.style.cssText = [
      'position:absolute',
      'top:0',
      'bottom:0',
      'height:' + table.offsetHeight + 'px',
      'left:' + (timelineX + px * (window.GANTT_Z || 1)) + 'px',
      'width:2px',
      'background:#ef4444',
      'z-index:25',
      'pointer-events:none',
      'box-shadow:0 0 8px rgba(239,68,68,0.5)'
    ].join(';');

    // Heute-Label oben an die Linie
    var label = document.createElement('div');
    label.textContent = 'HEUTE · KW' + kw;
    label.style.cssText = [
      'position:absolute',
      'top:-2px',
      'left:4px',
      'background:#ef4444',
      'color:#fff',
      'font-size:9px',
      'font-weight:700',
      'padding:2px 6px',
      'border-radius:4px',
      'white-space:nowrap',
      'letter-spacing:0.04em'
    ].join(';');
    line.appendChild(label);

    // table braucht position:relative damit absolutes Kind im Tabellen-Koordinatensystem ankert
    if (getComputedStyle(table).position === 'static') {
      table.style.position = 'relative';
    }
    table.appendChild(line);
  }

  // Resize / Tab-Wechsel: neu zeichnen
  window.addEventListener('resize', function(){ setTimeout(updateTodayLine, 100); });
  document.addEventListener('click', function(){ setTimeout(updateTodayLine, 200); }, true);

  // Für externen Aufruf (z.B. nach Filterwechsel) exportieren
  window.updateTodayLine = updateTodayLine;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(updateTodayLine, 200); });
  } else {
    setTimeout(updateTodayLine, 200);
  }
  setTimeout(updateTodayLine, 1500);
  setInterval(updateTodayLine, 5 * 60 * 1000);
})();
</script>

<script src="assets/sync.js"></script>
<script src="assets/admin.js"></script>
<script src="assets/search.js"></script>
<script src="assets/sticky.js"></script>
<script src="assets/mobile.js"></script>
<script src="assets/bar-labels.js"></script>
<script src="assets/section-edit.js"></script>
<script src="assets/changes.js"></script>
<script src="assets/history-modal.js"></script>
<script src="assets/sync2.js"></script>
<script>
/* ===== Kapa-Heatmap-Strip im Hauptzeitplan: zeigt Auslastung pro KW für aktiven Gewerk-Filter ===== */
(function () {
  var PX_PER_WEEK = 126;
  var ORIGIN_KW   = 23;
  function getEmployees() {
    try { return JSON.parse(localStorage.getItem('kap-mitarbeiter-v10') || '[]') || []; } catch (e) { return []; }
  }
  function gnormHP(g) {
    if (!g) return '';
    return (typeof window.mapGewerk === 'function') ? window.mapGewerk(g) : g;
  }
  // Pro Gewerk → KW → Mannstunden-Bedarf
  function demandByGewerkKw() {
    var map = {};
    document.querySelectorAll('#tab-hauptwerk tr.task-row').forEach(function (tr) {
      var g = gnormHP(tr.getAttribute('data-gewerk') || '');
      if (!g) return;
      var bar = tr.querySelector('.gantt-bar');
      if (!bar || !bar.style.width) return;
      var left = parseInt(bar.style.left, 10) || 0;
      var width = parseInt(bar.style.width, 10) || 0;
      if (!width) return;
      var startKw = ORIGIN_KW + Math.round(left / PX_PER_WEEK);
      var weeks = Math.max(1, Math.round(width / PX_PER_WEEK));
      var tid = tr.getAttribute('data-tid');
      var mh = parseInt(localStorage.getItem('task-mh-' + tid) || '40', 10);
      var perWeek = mh / weeks;
      if (!map[g]) map[g] = {};
      for (var k = 0; k < weeks; k++) map[g][startKw + k] = (map[g][startKw + k] || 0) + perWeek;
    });
    return map;
  }
  // Pro Gewerk → KW → Kapazität
  function supplyByGewerkKw() {
    var map = {};
    getEmployees().forEach(function (emp) {
      (emp.gewerke || []).forEach(function (gw0) {
        var gw = gnormHP(gw0);
        if (!gw) return;
        if (!map[gw]) map[gw] = {};
        var per = (emp.std || 0) / Math.max(1, (emp.gewerke || []).length);
        for (var kw = emp.von || 23; kw <= (emp.bis || 52); kw++) map[gw][kw] = (map[gw][kw] || 0) + per;
      });
    });
    return map;
  }
  // Eine Zellen-Reihe (KW23..104) für EIN Gewerk bauen
  function buildHeatCells(gName, demandG, supplyG) {
    var cells = '';
    var PXW = PX_PER_WEEK * (window.GANTT_Z || 1);   // Tagesansicht-Zoom berücksichtigen
    for (var kw = 23; kw <= 104; kw++) {
      var d = (demandG[gName]||{})[kw] || 0;
      var s = (supplyG[gName]||{})[kw] || 0;
      var pct = s > 0 ? (d/s*100) : 0;
      var col = '#f8fafc', fg = '#94a3b8', txt = '';
      if (s === 0 && d === 0) { col = '#f8fafc'; }
      else if (s === 0 && d > 0) { col = '#fecaca'; fg = '#991b1b'; txt = '!'; }
      else if (d === 0) { col = '#f0fdf4'; }
      else if (pct > 100) { col = '#dc2626'; fg = '#fff'; txt = Math.round(pct) + '%'; }
      else if (pct > 80) { col = '#f59e0b'; fg = '#7c2d12'; txt = Math.round(pct) + '%'; }
      else { col = '#86efac'; fg = '#14532d'; txt = Math.round(pct) + '%'; }
      var left = (kw - ORIGIN_KW) * PXW;
      var title = 'KW ' + kw + ' · ' + gName + '\n' + Math.round(d) + 'h / ' + Math.round(s) + 'h (' + Math.round(pct) + '%)';
      cells += '<div title="' + title + '" '
        + 'style="position:absolute;left:' + left + 'px;top:0;width:' + (PXW - 1) + 'px;height:100%;background:' + col + ';color:' + fg + ';display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;border-right:1px solid #fff;cursor:default">' + txt + '</div>';
    }
    return cells;
  }
  function renderHeatStrip() {
    var table = document.getElementById('main-gantt');
    var tbody = table && table.querySelector('tbody');
    if (!tbody) return;
    // Alte Heat-Zeilen + ggf. alten Header-Streifen (frühere Version) entfernen
    tbody.querySelectorAll('tr.kapa-heat-row').forEach(function (r) { r.remove(); });
    var oldStrip = document.getElementById('kapa-heat-strip');
    if (oldStrip) oldStrip.remove();
    var sel = (typeof window.selectedGewerke !== 'undefined' && window.selectedGewerke) ? window.selectedGewerke : [];
    // Ohne Gewerk-Auswahl keine Streifen — sinnlos ohne Fokus
    if (!sel.length) return;
    var demandG = demandByGewerkKw();
    var supplyG = supplyByGewerkKw();
    // Ein Streifen PRO gewähltem Gewerk als echte Tabellenzeile, ganz oben gestapelt.
    // Label-Zelle (colspan=4) liegt im FESTEN Spaltenbereich (Aufgabe…Firma); die
    // Auslastungs-Zellen sitzen in der Gantt-Spalte → fluchten exakt mit den Balken,
    // und KW23+ ist voll sichtbar (kein Label über den ersten KWs mehr).
    var rows = '';
    sel.forEach(function (gName) {
      rows += '<tr class="kapa-heat-row">'
        + '<td colspan="4" style="padding:3px 10px;font-size:9px;font-weight:800;letter-spacing:.4px;color:#0f172a;background:#eef2f7;border-bottom:1px solid #e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">📊 ' + gName.toUpperCase() + ' · Auslastung</td>'
        + '<td style="padding:0;background:#eef2f7;border-bottom:1px solid #e2e8f0"><div style="position:relative;width:10800px;height:18px">' + buildHeatCells(gName, demandG, supplyG) + '</div></td>'
        + '</tr>';
    });
    tbody.insertAdjacentHTML('afterbegin', rows);
  }
  window.renderKapaHeatStrip = renderHeatStrip;
  // Auf Filter-/Status-/Bar-Änderungen reagieren
  ['DOMContentLoaded'].forEach(function(ev){ document.addEventListener(ev, function(){ setTimeout(renderHeatStrip, 400); }); });
  window.addEventListener('load', function(){ setTimeout(renderHeatStrip, 400); });
  // Klick auf Filter-Pills (window.filterGewerk wird genutzt) → refresh
  var origFilter = window.filterGewerk;
  if (typeof origFilter === 'function') {
    window.filterGewerk = function(g) { origFilter(g); renderHeatStrip(); };
  } else {
    // erst später definiert — verzögert wrappen
    setTimeout(function(){
      if (typeof window.filterGewerk === 'function') {
        var orig = window.filterGewerk;
        window.filterGewerk = function(g) { orig(g); renderHeatStrip(); };
      }
    }, 800);
  }
  // Bar-Drag-End löst Mutation auf Bar-style → MutationObserver
  setTimeout(function() {
    var ganttArea = document.querySelector('#tab-hauptwerk .gantt-wrap');
    if (!ganttArea) return;
    var t;
    var obs = new MutationObserver(function() {
      clearTimeout(t); t = setTimeout(renderHeatStrip, 250);
    });
    obs.observe(ganttArea, { subtree: true, attributes: true, attributeFilter: ['style', 'data-status'] });
  }, 1000);
})();
</script>
<script>
/* ===== Generischer KV-Sync für Neben-Tabs (Bestellungen, Budget, Kapazität, TODs, Einheiten) ===== */
(function () {
  // Welche localStorage-Keys über die DB synchronisiert werden (Kern-Plan läuft separat über overrides!)
  var EXACT = ['bo-orders-v3', 'cost-values', 'unit-costs', 'kap-mitarbeiter-v10', 'unit-registry', 'gewerke-list-v1', 'task-times-v1', 'budget-custom-v1'];
  var PREFIX = ['task-mh-', 'todo-kw-', 'cost-name-'];
  function isSynced(key) {
    if (!key) return false;
    if (EXACT.indexOf(key) !== -1) return true;
    return PREFIX.some(function (p) { return key.indexOf(p) === 0; });
  }

  // localStorage abfangen → an DB pushen (außer während Remote-Anwendung).
  // WICHTIG: am Storage-PROTOTYP überschreiben — Instanz-Überschreibung greift in Safari oft nicht.
  var proto = (window.Storage && Storage.prototype) ? Storage.prototype : Object.getPrototypeOf(localStorage);
  var _set = proto.setItem;
  var _rem = proto.removeItem;
  proto.setItem = function (k, v) {
    _set.call(this, k, v);
    if (this === window.localStorage && isSynced(k) && window.PlanSync && !window.PlanSync.isApplyingRemote()) {
      window.PlanSync.pushKV(k, v);
    }
  };
  proto.removeItem = function (k) {
    _rem.call(this, k);
    if (this === window.localStorage && isSynced(k) && window.PlanSync && !window.PlanSync.isApplyingRemote()) {
      window.PlanSync.pushKV(k, null);
    }
  };
  // Zusätzlich explizite Hook-Funktion (falls einzelne Save-Stellen sie direkt aufrufen)
  window.__syncKV = function (k, v) {
    if (isSynced(k) && window.PlanSync && !window.PlanSync.isApplyingRemote()) window.PlanSync.pushKV(k, v);
  };

  // Eingehende KV-Änderung anwenden → passendes Re-Render auslösen
  window.__applyKVUpdate = function (key, value) {
    try {
      if (key === 'bo-orders-v3') {
        window.boOrders = JSON.parse(value || 'null') || window.boOrders;
        if (typeof window.renderOrders === 'function') window.renderOrders();
        if (typeof window.renderCostOrders === 'function') window.renderCostOrders();
      } else if (key === 'budget-custom-v1') {
        try { window.budgetCustom = JSON.parse(value || '[]') || []; } catch (e) {}
        if (typeof window.renderBudgetCustom === 'function') window.renderBudgetCustom();
        if (typeof window.updateCostSummary === 'function') window.updateCostSummary();
      } else if (key === 'cost-values') {
        if (typeof window.loadCostValues === 'function') window.loadCostValues();
      } else if (key === 'unit-costs') {
        if (typeof window.loadUnitCosts === 'function') window.loadUnitCosts();
      } else if (key === 'kap-mitarbeiter-v10' || key.indexOf('task-mh-') === 0) {
        if (typeof window.kapReload === 'function') window.kapReload();
        else if (typeof window.renderKalender === 'function') window.renderKalender();
      } else if (key === 'unit-registry') {
        try { window.UNIT_REGISTRY = JSON.parse(value || '[]'); } catch (e) {}
        if (typeof window.kapReload === 'function') window.kapReload();
        else if (typeof window.renderKalender === 'function') window.renderKalender();
      } else if (key === 'gewerke-list-v1') {
        if (typeof window.__applyGewerkeKV === 'function') window.__applyGewerkeKV(value);
      } else if (key === 'task-times-v1') {
        if (typeof window.__applyTaskTimesKV === 'function') window.__applyTaskTimesKV(value);
      } else if (key.indexOf('cost-name-') === 0) {
        var posId = key.replace('cost-name-', '');
        if (typeof window.__applyBudgetPositionName === 'function') window.__applyBudgetPositionName(posId, value);
      } else if (key.indexOf('todo-kw-') === 0) {
        var kw = key.replace('todo-kw-', '');
        var el = document.getElementById('manual-kw' + kw);
        if (el) el.innerHTML = value || '';
      }
    } catch (e) { /* defensiv: niemals den Sync crashen lassen */ }
  };

  // Globale Referenzen sicherstellen (renderOrders/loadCostValues/loadUnitCosts sind bereits global)
  if (typeof renderOrders === 'function') window.renderOrders = renderOrders;
  if (typeof loadCostValues === 'function') window.loadCostValues = loadCostValues;
  if (typeof loadUnitCosts === 'function') window.loadUnitCosts = loadUnitCosts;
})();
</script>
<script>
/* ===== TODs-Tab: Inline-Status + Zeit-Erfassung + Gewerke-Filter + Sync zum Hauptplan ===== */
(function () {
  var TIMES_KEY = 'task-times-v1';
  function loadTimes() { try { return JSON.parse(localStorage.getItem(TIMES_KEY) || '{}'); } catch (e) { return {}; } }
  function saveTimes(t) { localStorage.setItem(TIMES_KEY, JSON.stringify(t)); }
  var times = loadTimes();
  var activeGw = '';

  // Status-Zyklus für Klick auf Dot (kurz, nur Meilensteine — Feinabstufung über Dropdown im Hauptplan)
  var CYCLE = ['geplant', 'begonnen', 'fortschritt_50', 'fortschritt_75', 'abgeschlossen'];
  var DOT_BG = {
    geplant:'#94a3b8', vorbereitung:'#94a3b8',
    laufend:'#f59e0b', begonnen:'#f59e0b',
    fortschritt_25:'#f59e0b', fortschritt_50:'#f59e0b',
    fortschritt_75:'#f59e0b', fortschritt_90:'#f59e0b',
    abnahme:'#f59e0b',
    abgeschlossen:'#16a34a', fertig:'#16a34a',
    pausiert:'#dc2626', verzögert:'#dc2626',
    abgebrochen:'#64748b',
  };
  var DOT_LBL = {
    geplant:'·', vorbereitung:'·',
    laufend:'▶', begonnen:'▶',
    fortschritt_25:'¼', fortschritt_50:'½',
    fortschritt_75:'¾', fortschritt_90:'9',
    abnahme:'A',
    abgeschlossen:'✓', fertig:'✓',
    pausiert:'⏸', verzögert:'⚠',
    abgebrochen:'✕',
  };

  function fmt(ts) { if (!ts) return ''; try { return new Date(ts).toLocaleDateString('de-DE') + ' ' + new Date(ts).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'}); } catch(e){return ts;} }

  function findTaskRow(name) {
    name = (name || '').trim().toLowerCase();
    if (!name) return null;
    var cells = document.querySelectorAll('tr.task-row .task-name-cell');
    for (var i=0; i<cells.length; i++) {
      var t = (cells[i].textContent || '').replace(/[🕐✕]/g,'').replace(/\s+/g,' ').trim().toLowerCase();
      if (t === name) return cells[i].closest('tr.task-row');
    }
    for (var j=0; j<cells.length; j++) {
      var u = (cells[j].textContent || '').replace(/[🕐✕]/g,'').replace(/\s+/g,' ').trim().toLowerCase();
      if (u && (u.indexOf(name) >= 0 || name.indexOf(u) >= 0)) return cells[j].closest('tr.task-row');
    }
    return null;
  }

  function setStatus(taskRow, tid, next) {
    if (!taskRow || taskRow.getAttribute('data-status') === next) return;
    // setAttribute → MutationObserver in changes.js pusht über PlanSync an die DB
    taskRow.setAttribute('data-status', next);
    // Zeitstempel
    if (next === 'laufend') {
      times[tid] = times[tid] || {};
      if (!times[tid].started) { times[tid].started = new Date().toISOString(); saveTimes(times); }
    }
    if (next === 'abgeschlossen') {
      times[tid] = times[tid] || {};
      times[tid].done = new Date().toISOString();
      saveTimes(times);
    }
  }

  function renderControls(li) {
    var tid = li.dataset.tid;
    if (!tid) return;
    var ctrl = li.querySelector('.todo-ctrl');
    if (!ctrl) {
      ctrl = document.createElement('div');
      ctrl.className = 'todo-ctrl';
      ctrl.style.cssText = 'display:flex;gap:4px;align-items:center;margin-left:auto';
      li.appendChild(ctrl);
    }
    var taskRow = document.querySelector('tr.task-row[data-tid="' + CSS.escape(tid) + '"]');
    var curS = taskRow ? (taskRow.getAttribute('data-status') || 'geplant') : 'geplant';
    var t = times[tid] || {};
    ctrl.innerHTML =
      '<button class="todo-begin" title="Projekt begonnen — setzt Status laufend + erfasst Startzeit" '
      +   'style="padding:2px 6px;border-radius:8px;border:1px solid ' + (curS!=='geplant'?'#f59e0b':'#e2e8f0') + ';'
      +   'background:' + (curS==='laufend'||/^fortschritt_/.test(curS)?'#fef3c7':'#fff') + ';color:#b45309;font-size:9px;font-weight:700;cursor:pointer">▶ begonnen</button>'
      + '<button class="todo-done" title="Erledigt — setzt abgeschlossen + erfasst Endzeit" '
      +   'style="padding:2px 6px;border-radius:8px;border:1px solid ' + (curS==='abgeschlossen'?'#16a34a':'#e2e8f0') + ';'
      +   'background:' + (curS==='abgeschlossen'?'#dcfce7':'#fff') + ';color:#15803d;font-size:9px;font-weight:700;cursor:pointer">✓ erledigt</button>';
    // Zeitstempel-Anzeige ist vorerst ausgeblendet (Erfassung läuft im Hintergrund weiter)
    // Zur Reaktivierung später wieder einkommentieren / refaktorieren.
  }

  function refreshDot(li) {
    var tid = li.dataset.tid;
    if (!tid) return;
    var taskRow = document.querySelector('tr.task-row[data-tid="' + CSS.escape(tid) + '"]');
    if (!taskRow) return;
    var s = taskRow.getAttribute('data-status') || 'geplant';
    var dot = li.querySelector('.todo-dot');
    if (!dot) return;
    dot.style.background = DOT_BG[s] || '#94a3b8';
    dot.textContent = DOT_LBL[s] || '·';
  }

  function setupLi(li) {
    if (li.dataset.todoInit) return;
    var nameEl = li.querySelector('span[style*="flex:1"]');
    if (!nameEl) return;
    var name = nameEl.textContent.trim();
    var taskRow = findTaskRow(name);
    if (!taskRow) return;
    var tid = taskRow.getAttribute('data-tid');
    if (!tid) return;
    li.dataset.tid = tid;
    li.dataset.gewerk = taskRow.getAttribute('data-gewerk') || '';
    li.dataset.todoInit = '1';

    // Erstes <span> ist der Status-Dot → klickbar machen + Klasse setzen
    var dot = li.firstElementChild;
    if (dot && dot.style && dot.style.borderRadius && dot.style.borderRadius.indexOf('50%') >= 0) {
      dot.classList.add('todo-dot');
      dot.style.cursor = 'pointer';
      dot.style.width = '14px';
      dot.style.height = '14px';
      dot.style.display = 'inline-flex';
      dot.style.alignItems = 'center';
      dot.style.justifyContent = 'center';
      dot.style.color = '#fff';
      dot.style.fontSize = '10px';
      dot.style.fontWeight = '700';
      dot.title = 'Klick: Status weiterschalten';
      dot.addEventListener('click', function (e) {
        e.stopPropagation();
        var s = taskRow.getAttribute('data-status') || 'geplant';
        var idx = CYCLE.indexOf(s);
        var next = CYCLE[(idx + 1) % CYCLE.length];
        setStatus(taskRow, tid, next);
        refreshDot(li);
        renderControls(li);
      });
      refreshDot(li);
    }

    // Strecker, damit Buttons rechts landen
    var strut = document.createElement('span');
    strut.style.cssText = 'flex:1 0 0';
    li.appendChild(strut);

    renderControls(li);

    // Click-Delegation für ▶ begonnen / ✓ erledigt
    li.addEventListener('click', function (e) {
      var b = e.target.closest('.todo-begin, .todo-done');
      if (!b) return;
      e.stopPropagation();
      var target = b.classList.contains('todo-begin') ? 'laufend' : 'abgeschlossen';
      setStatus(taskRow, tid, target);
      refreshDot(li);
      renderControls(li);
    });
  }

  function buildFilterRow() {
    var wrap = document.querySelector('#tab-todos .todos-wrap');
    if (!wrap || document.getElementById('todos-filter-row')) return;
    var row = document.createElement('div');
    row.id = 'todos-filter-row';
    row.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:0 0 14px;padding:6px 0;border-bottom:1px solid #f1f5f9';
    var lbl = document.createElement('span');
    lbl.style.cssText = 'font-size:11px;font-weight:600;color:#64748b;margin-right:4px';
    lbl.textContent = 'Filter Gewerk:';
    row.appendChild(lbl);
    var btnAll = document.createElement('button');
    btnAll.textContent = 'Alle';
    btnAll.dataset.gw = '';
    btnAll.className = 'todos-gw-pill';
    row.appendChild(btnAll);
    (window.GEWERKE || []).forEach(function (g) {
      if (!g.name) return;
      var b = document.createElement('button');
      b.textContent = g.name;
      b.dataset.gw = g.name;
      b.dataset.fg = g.fg;
      b.className = 'todos-gw-pill';
      row.appendChild(b);
    });
    row.querySelectorAll('.todos-gw-pill').forEach(function (b) {
      b.style.cssText = 'padding:3px 11px;border-radius:14px;border:1.5px solid '
        + (b.dataset.fg ? b.dataset.fg + '40' : '#e2e8f0')
        + ';background:#fff;color:' + (b.dataset.fg || '#64748b')
        + ';font-size:11px;font-weight:600;cursor:pointer';
    });
    var ref = wrap.children[1] || wrap.firstChild;
    wrap.insertBefore(row, ref);
    row.addEventListener('click', function (e) {
      var b = e.target.closest('.todos-gw-pill');
      if (!b) return;
      activeGw = b.dataset.gw || '';
      row.querySelectorAll('.todos-gw-pill').forEach(function (p) {
        var on = (p === b);
        p.style.background = on ? (p.dataset.fg || '#2563eb') : '#fff';
        p.style.color = on ? '#fff' : (p.dataset.fg || '#64748b');
      });
      applyFilter();
    });
    // Initial "Alle" aktiv
    btnAll.style.background = '#2563eb';
    btnAll.style.color = '#fff';
    btnAll.style.borderColor = '#2563eb';
  }

  function applyFilter() {
    document.querySelectorAll('#tab-todos .kw-card ul li').forEach(function (li) {
      if (!activeGw) { li.style.display = ''; return; }
      li.style.display = (li.dataset.gewerk === activeGw) ? '' : 'none';
    });
    // Leere KW-Karten ggf. dezent stumpf
    document.querySelectorAll('#tab-todos .kw-card').forEach(function (c) {
      var visible = Array.from(c.querySelectorAll('ul li')).some(function (li) { return li.style.display !== 'none'; });
      c.style.opacity = visible ? '1' : '0.45';
    });
  }

  function init() {
    buildFilterRow();
    document.querySelectorAll('#tab-todos .kw-card ul li').forEach(setupLi);
    applyFilter();
  }

  // Init nach Page-Load + bei Tab-Wechsel zu TODs
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(init, 400); });
  } else { setTimeout(init, 400); }
  var origShow = window.showTab;
  if (origShow) {
    window.showTab = function (name, el) { origShow(name, el); if (name === 'todos') setTimeout(init, 100); };
  }

  // Bei eingehendem KV-Update (z.B. andere Person hat erledigt geklickt)
  window.__applyTaskTimesKV = function (value) {
    try { times = JSON.parse(value || '{}'); } catch (e) {}
    document.querySelectorAll('#tab-todos .kw-card ul li[data-tid]').forEach(function (li) { renderControls(li); });
  };

  // Wenn Hauptplan-Status sich ändert (auch via Sync) → TOD-Bullets nachziehen
  var statusObs = new MutationObserver(function (muts) {
    var dirty = false;
    for (var i = 0; i < muts.length; i++) {
      if (muts[i].type === 'attributes' && muts[i].attributeName === 'data-status') { dirty = true; break; }
    }
    if (!dirty) return;
    document.querySelectorAll('#tab-todos .kw-card ul li[data-tid]').forEach(function (li) {
      refreshDot(li);
      renderControls(li);
    });
  });
  setTimeout(function () {
    document.querySelectorAll('tr.task-row').forEach(function (r) {
      statusObs.observe(r, { attributes: true, attributeFilter: ['data-status'] });
    });
  }, 800);
})();
</script>
<script>
(function () {
  // Live-Update für "Stand DD.MM.YYYY, HH:MM Uhr" jede Minute
  function updateStandTime() {
    const el = document.getElementById('stand-time');
    if (!el) return;
    const now = new Date();
    const d = now.getDate();
    const m = now.getMonth() + 1;
    const y = now.getFullYear();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    el.textContent = `${d}.${m}.${y}, ${hh}:${mm} Uhr`;
  }
  updateStandTime();
  // Minütlich aktualisieren (an Minuten-Wechsel andocken)
  const now = new Date();
  const msToNextMinute = (60 - now.getSeconds()) * 1000 - now.getMilliseconds();
  setTimeout(() => {
    updateStandTime();
    setInterval(updateStandTime, 60 * 1000);
  }, msToNextMinute);
})();
</script>
</body>
</html>
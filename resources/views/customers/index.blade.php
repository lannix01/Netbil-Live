@extends('layouts.app')

@section('content')

{{-- jQuery + DataTables --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<style>
:root{
    --bg:#f6f7fb;
    --card:#ffffff;
    --border:#e6e9f2;
    --text:#0f172a;
    --muted:#667085;
    --primary:#2f5cf0;
    --primary2:#4e73df;
    --danger:#e11d48;
    --shadow2:0 6px 14px rgba(16,24,40,.10);
    --r:14px;
}

body{ background:var(--bg); }
.nm-wrap{ max-width:1400px; margin:0 auto; padding:18px 10px 44px; }

.nm-head{
    display:flex; align-items:flex-end; justify-content:space-between;
    gap:12px; flex-wrap:wrap; margin-bottom:14px;
}
.nm-title{ margin:0; font-weight:950; letter-spacing:.2px; color:#1d4ed8; }
.nm-sub{ margin:0; color:var(--muted); font-size:.95rem; }
.nm-actions{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }

.btn-soft{
    border:1px solid var(--border);
    background:#fff;
    border-radius:12px;
    font-weight:900;
    padding:.45rem .75rem;
    box-shadow:0 1px 0 rgba(16,24,40,.04);
}
.btn-soft:hover{ box-shadow:var(--shadow2); transform:translateY(-1px); }

.kpi-grid{ margin:10px 0 14px; }
.kpi{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--r);
    padding:12px 12px;
    box-shadow:0 2px 10px rgba(16,24,40,.05);
    transition:transform .16s ease, box-shadow .16s ease;
    min-height:84px;
}
.kpi:hover{ transform:translateY(-2px); box-shadow:var(--shadow2); }
.kpi .k{ font-size:.72rem; color:var(--muted); font-weight:900; letter-spacing:.7px; text-transform:uppercase; }
.kpi .v{ font-size:1.5rem; font-weight:950; color:var(--text); line-height:1.1; margin-top:3px; }
.kpi .ico{
    width:40px; height:40px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    border:1px solid var(--border);
    background:linear-gradient(180deg, #ffffff, #f3f6ff);
    color:#3558e6;
}
.kpi-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; }

.nm-tabs{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--r);
    box-shadow:0 2px 10px rgba(16,24,40,.05);
    overflow:hidden;
}
.nm-tabs-head{
    padding:12px 14px;
    border-bottom:1px solid var(--border);
    background:linear-gradient(135deg, #c7d6ff 0%, #b6c8ff 55%, #aebfff 100%);
}

.nav-pills{
    background:rgba(255,255,255,.65);
    border:1px solid rgba(255,255,255,.65);
    border-radius:14px;
    padding:6px;
    box-shadow: inset 0 0 0 1px rgba(15,23,42,.06);
    gap:6px;
}
.nav-pills .nav-link{
    border-radius:12px;
    padding:.55rem .9rem;
    font-weight:950;
    font-size:.88rem;
    color:#475569;
    transition:transform .12s ease, background .12s ease;
}
.nav-pills .nav-link:hover{ background:#f3f6ff; transform:translateY(-1px); }
.nav-pills .nav-link.active{
    background:linear-gradient(135deg, #2f5cf0 0%, #4e73df 100%);
    color:#fff;
    box-shadow:0 8px 18px rgba(47,92,240,.25);
}

.section-card{ border-top:1px solid var(--border); }
.section-head{
    padding:10px 14px;
    display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
    background:#fbfcff;
    border-bottom:1px solid var(--border);
}
.section-head .left{
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    font-weight:950; color:#0f172a;
}
.badge-soft{
    border:1px solid var(--border);
    background:#fff;
    color:#334155;
    font-weight:950;
    border-radius:999px;
    padding:.25rem .55rem;
    font-size:.72rem;
}
.refresh-btn{
    border-radius:12px;
    padding:.35rem .6rem;
    font-size:.8rem;
    border:1px solid var(--border);
    background:#fff;
    font-weight:950;
}
.refresh-btn:hover{ transform:translateY(-1px); box-shadow:var(--shadow2); }

.fade-section{ position:relative; min-height:120px; }
.loading-overlay{
    position:absolute; inset:0;
    background:rgba(255,255,255,.88);
    display:flex; align-items:center; justify-content:center;
    border-radius:0 0 var(--r) var(--r);
    z-index:999;
    backdrop-filter: blur(2px);
}
.spinner{
    width:22px; height:22px;
    border:2px solid #eef2ff;
    border-top:2px solid var(--primary2);
    border-radius:999px;
    animation:spin 1s linear infinite;
}
@keyframes spin{ to { transform:rotate(360deg);} }

/* DataTables polish */
table.dataTable{
    border-radius:14px;
    font-size:.88rem;
    border:1px solid var(--border) !important;
}
table.dataTable thead th{
    background:#e9eef6 !important;
    color:#2f3f57 !important;
    font-weight:950;
    padding:.85rem .75rem !important;
    border-bottom:1px solid #d6deea !important;
    font-size:.78rem;
    text-transform:uppercase;
    letter-spacing:.6px;
    white-space:nowrap;
}
table.dataTable tbody td{
    padding:.7rem .75rem !important;
    border-color:#f1f3f4;
    vertical-align:middle;
    font-size:.88rem;
}
table.dataTable tbody tr:hover{ background:#fafcff !important; }
/* Keep selected state extremely subtle and readable across all cells/actions */
table.dataTable tbody tr.selected{
    outline:1px solid #aec1fb;
}
table.dataTable tbody tr.selected > td{
    background:#aec1fb !important;
    color:#0f172a !important;
}
/* DataTables select extension paints selected rows via strong box-shadow; override it */
table.dataTable > tbody > tr.selected > *,
table.dataTable.table-striped > tbody > tr.selected > *,
table.dataTable.table-hover > tbody > tr.selected > *{
    box-shadow: inset 0 0 0 9999px #aec1fb !important;
    background-color: #aec1fb !important;
    color:#0f172a !important;
}
table.dataTable tbody tr.selected .btn,
table.dataTable tbody tr.selected .btn-outline-secondary,
table.dataTable tbody tr.selected .dropdown-toggle{
    background:#fff !important;
    color:#475569 !important;
    border-color:var(--border) !important;
}

.dataTables_filter input{
    border-radius:12px !important;
    border:1px solid var(--border) !important;
    padding:.45rem .65rem !important;
    font-size:.9rem !important;
}
.dataTables_length select{
    border-radius:12px !important;
    border:1px solid var(--border) !important;
    padding:.35rem .55rem !important;
    font-size:.9rem !important;
}

.nm-toast{
    position:fixed; top:12px; right:12px; z-index:99999;
    min-width:260px;
    border-radius:14px;
    border:1px solid var(--border);
    box-shadow:0 12px 30px rgba(16,24,40,.18);
    overflow:hidden;
}
.nm-toast .bar{ height:3px; background:linear-gradient(135deg, #2f5cf0 0%, #4e73df 100%); }
.nm-toast .body{
    padding:10px 12px; background:#fff;
    display:flex; gap:10px; align-items:flex-start;
}
.nm-toast .body .t{ font-weight:950; color:#0f172a; font-size:.92rem; }
.nm-toast .body .s{ color:#64748b; font-size:.85rem; margin-top:2px; }
.nm-toast .x{
    margin-left:auto;
    border:1px solid var(--border);
    background:#fff;
    border-radius:12px;
    width:28px; height:28px;
    display:flex; align-items:center; justify-content:center;
}
.nm-toast .x:hover{ background:#f8fafc; }

.nm-error{
    border:1px solid #fecaca;
    background:#fff1f2;
    color:#7f1d1d;
    border-radius:14px;
    padding:12px 14px;
    display:flex; align-items:flex-start; gap:10px;
    box-shadow:0 10px 24px rgba(225,29,72,.10);
}
.nm-error .t{ font-weight:950; }
.nm-error .s{ color:#991b1b; font-size:.9rem; margin-top:2px; }

.user-mini-card{
    border:1px solid #dbe4f3;
    border-radius:16px;
    background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
    padding:12px 13px;
    min-height:84px;
    box-shadow:0 10px 24px rgba(15,23,42,.05);
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.user-mini-card .k{
    font-size:.74rem;
    color:#64748b;
    text-transform:uppercase;
    font-weight:800;
    letter-spacing:.55px;
}
.user-mini-card .v{
    margin-top:4px;
    font-size:1rem;
    font-weight:900;
    color:#0f172a;
    line-height:1.25;
    overflow-wrap:anywhere;
}
#trafficModal .modal-dialog{
    max-width:min(1120px, calc(100vw - 1.5rem));
}
#trafficModal .modal-content{
    border:0;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 30px 70px rgba(15,23,42,.26);
}
#trafficModal .modal-header{
    border-bottom:1px solid rgba(148,163,184,.18);
    background:
        linear-gradient(135deg, rgba(8,145,178,.18), transparent 42%),
        linear-gradient(120deg, #0f172a 0%, #111827 45%, #1d4ed8 100%);
    padding:1rem 1.25rem;
}
#trafficModal .modal-title{
    margin:0;
    color:#fff;
    font-weight:950;
    letter-spacing:-.02em;
}
#trafficModal .modal-body{
    padding:1rem 1.25rem 1.1rem;
    background:linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%);
}
#trafficModal .modal-footer{
    border-top:1px solid #e2e8f0;
    background:#f8fafc;
}
#trafficModal .traffic-chart-shell{
    border:1px solid rgba(15,118,110,.16);
    border-radius:20px;
    padding:14px;
    background:
        radial-gradient(circle at top right, rgba(14,165,233,.18), transparent 30%),
        radial-gradient(circle at bottom left, rgba(34,197,94,.14), transparent 36%),
        linear-gradient(180deg, #08121d 0%, #0f172a 100%);
    box-shadow:0 18px 42px rgba(15,23,42,.18);
}
#trafficModal .traffic-chart-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
}
#trafficModal .traffic-chart-kicker{
    font-size:.68rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:#7dd3fc;
}
#trafficModal .traffic-chart-title{
    margin-top:2px;
    font-size:1rem;
    font-weight:900;
    color:#e2e8f0;
}
#trafficModal .traffic-source-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    border-radius:999px;
    border:1px solid rgba(148,163,184,.18);
    padding:5px 10px;
    font-size:.75rem;
    font-weight:900;
    letter-spacing:.03em;
    text-transform:uppercase;
    background:rgba(15,23,42,.22);
    color:#bfdbfe;
}
#trafficModal .traffic-source-badge .dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:currentColor;
    box-shadow:0 0 0 3px rgba(255,255,255,.06);
    animation:trafficPulse 1.4s ease-in-out infinite;
}
#trafficModal .traffic-source-badge[data-state="live"]{
    color:#86efac;
    border-color:rgba(34,197,94,.22);
    background:rgba(20,83,45,.26);
}
#trafficModal .traffic-source-badge[data-state="fallback"]{
    color:#7dd3fc;
    border-color:rgba(14,165,233,.22);
    background:rgba(12,74,110,.22);
}
#trafficModal .traffic-source-badge[data-state="idle"]{
    color:#fde68a;
    border-color:rgba(234,179,8,.22);
    background:rgba(113,63,18,.22);
}
#trafficModal .traffic-source-badge[data-state="offline"]{
    color:#cbd5e1;
    border-color:rgba(148,163,184,.2);
    background:rgba(30,41,59,.24);
}
@keyframes trafficPulse{
    50%{
        opacity:.45;
        transform:scale(.82);
    }
}
#trafficModal .traffic-chart-wrap{
    height:240px;
    border-radius:16px;
    padding:8px 12px 6px;
    background:
        linear-gradient(180deg, rgba(15,23,42,.22), rgba(15,23,42,.04)),
        repeating-linear-gradient(
            180deg,
            rgba(148,163,184,.05) 0,
            rgba(148,163,184,.05) 1px,
            transparent 1px,
            transparent 36px
        ),
        repeating-linear-gradient(
            90deg,
            rgba(148,163,184,.02) 0,
            rgba(148,163,184,.02) 1px,
            transparent 1px,
            transparent 48px
        );
    border:1px solid rgba(148,163,184,.14);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04),
        inset 0 0 0 1px rgba(2,6,23,.14);
}
#trafficModal .traffic-chart-foot{
    margin-top:10px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
}
#trafficModal #trafficChartHint{
    color:#94a3b8;
}
#trafficModal .traffic-live-stats{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
#trafficModal .traffic-live-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    border:1px solid rgba(148,163,184,.16);
    background:rgba(15,23,42,.28);
    padding:5px 10px;
    font-size:.75rem;
    font-weight:900;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:#cbd5e1;
}
#trafficModal .traffic-live-pill strong{
    font-size:.76rem;
    font-weight:900;
}
#trafficModal .traffic-live-pill.tx strong{
    color:#fbbf24;
}
#trafficModal .traffic-live-pill.rx strong{
    color:#86efac;
}
#trafficModal .traffic-live-pill.avg strong{
    color:#7dd3fc;
}
#trafficModal .traffic-live-pill.peak strong{
    color:#fda4af;
}
#trafficModal .traffic-stat-card{
    min-height:82px;
    border:1px solid #dbe4f3;
    border-radius:16px;
    background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
    padding:11px 12px;
    box-shadow:0 10px 24px rgba(15,23,42,.05);
}
#trafficModal .table{
    margin-bottom:0;
}
#trafficModal .table thead th{
    background:linear-gradient(180deg,#f8faff 0%,#eef2ff 100%);
    color:#334155;
    font-size:.72rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.55px;
    border-bottom:1px solid #dbe3f3;
    white-space:nowrap;
}
#trafficModal .table tbody td{
    vertical-align:middle;
    color:#0f172a;
}
.ud-loader{
    min-height:190px;
    display:flex;
    align-items:center;
    justify-content:center;
}
#userDetailsModal .modal-dialog{
    max-width:min(1280px, calc(100vw - 1.5rem));
}
#userDetailsModal .modal-content{
    border:0;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 28px 68px rgba(15,23,42,.20);
}
#userDetailsModal .modal-header{
    border-bottom:1px solid #e2e8f0;
    background:linear-gradient(135deg,#f8fbff 0%,#eef4ff 52%,#f8fafc 100%);
    padding:1.1rem 1.25rem 1rem;
}
#userDetailsModal .modal-title{
    margin:0;
    color:#0f172a;
    font-weight:950;
    letter-spacing:-.02em;
}
#userDetailsModal .ud-head-copy{
    display:flex;
    flex-direction:column;
    gap:2px;
}
#userDetailsModal .ud-head-kicker{
    font-size:.72rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:#2563eb;
}
#userDetailsModal .ud-head-subtitle{
    margin:0;
    font-size:.88rem;
    color:#64748b;
}
#userDetailsModal .modal-body{
    padding:1.15rem 1.25rem 1.35rem;
    background:linear-gradient(180deg,#fcfdff 0%,#f8fafc 100%);
}
#userDetailsModal .form-label{
    font-size:.72rem;
    font-weight:900 !important;
    text-transform:uppercase;
    letter-spacing:.6px;
    color:#475569;
    margin-bottom:.42rem;
}
#userDetailsModal .form-control,
#userDetailsModal .form-select{
    min-height:44px;
    border-radius:12px;
    border:1px solid #d7e0ee;
    background:#fff;
    box-shadow:inset 0 1px 2px rgba(15,23,42,.03);
}
#userDetailsModal textarea.form-control{
    min-height:84px;
}
#userDetailsModal .form-control:focus,
#userDetailsModal .form-select:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 .18rem rgba(59,130,246,.12);
}
#userDetailsModal .ud-tab-shell{
    border:0;
    border-bottom:1px solid #dbe4f3;
    border-radius:0;
    background:transparent;
    box-shadow:none;
    padding:0;
}
#userDetailsModal .ud-nav-tabs{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:-1px;
}
#userDetailsModal .ud-nav-tabs .nav-item{
    flex:1 1 0;
    min-width:0;
}
#userDetailsModal .ud-nav-tabs .nav-link{
    width:100%;
    min-height:auto;
    border:1px solid transparent;
    border-bottom-color:transparent;
    border-radius:16px 16px 0 0;
    background:transparent;
    color:#64748b;
    padding:12px 14px 11px;
    display:flex;
    flex-direction:row;
    align-items:baseline;
    justify-content:flex-start;
    gap:9px;
    font-weight:900;
    text-align:left;
    transition:background-color .18s ease, color .18s ease, border-color .18s ease;
}
#userDetailsModal .ud-nav-tabs .nav-link:hover{
    background:rgba(255,255,255,.78);
    color:#1e293b;
    border-color:#e2e8f0 #e2e8f0 transparent;
}
#userDetailsModal .ud-nav-tabs .nav-link.active{
    background:#fff;
    color:#1d4ed8;
    border-color:#dbe4f3 #dbe4f3 #fff;
    box-shadow:none;
}
#userDetailsModal .ud-tab-kicker{
    font-size:.64rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.55px;
    color:#64748b;
    opacity:1;
}
#userDetailsModal .ud-nav-tabs .nav-link.active .ud-tab-kicker{
    color:#2563eb;
}
#userDetailsModal .ud-tab-label{
    font-size:.98rem;
    font-weight:900;
    line-height:1.1;
}
#userDetailsModal .tab-content{
    margin-top:.95rem;
}
#userDetailsModal .ud-section-card{
    border:1px solid #dbe4f3;
    border-radius:18px;
    background:rgba(255,255,255,.94);
    box-shadow:0 14px 28px rgba(15,23,42,.05);
    padding:14px;
}
#userDetailsModal .ud-section-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
}
#userDetailsModal .ud-section-title{
    margin:0;
    font-size:.8rem;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:#0f172a;
}
#userDetailsModal .ud-section-subtitle{
    margin:3px 0 0;
    font-size:.84rem;
    color:#64748b;
}
#userDetailsModal .ud-section-note{
    font-size:.76rem;
    font-weight:800;
    color:#1d4ed8;
    background:#eff6ff;
    border:1px solid #dbeafe;
    border-radius:999px;
    padding:4px 10px;
    white-space:nowrap;
}
.invoice-highlight{
    border:1px solid #dbeafe;
    background:linear-gradient(135deg,#eff6ff 0%,#f8fbff 100%);
    border-radius:16px;
    padding:12px 14px;
    box-shadow:0 10px 24px rgba(59,130,246,.08);
}
.icon-action{
    border:1px solid var(--border);
    background:#fff;
    color:#1e293b;
    border-radius:8px;
    width:30px;
    height:30px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
}
.icon-action:hover{
    background:#f8fafc;
    color:#0f172a;
}
#userDetailsModal .ud-table-shell{
    border:1px solid var(--border);
    border-radius:14px;
    overflow-x:auto;
    overflow-y:hidden;
    background:#fff;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.75);
    -webkit-overflow-scrolling:touch;
    scrollbar-width:thin;
}
#userDetailsModal .ud-table{
    margin:0;
    width:100%;
    min-width:760px;
}
#userDetailsModal .ud-table thead th{
    background:linear-gradient(180deg,#f8faff 0%,#eef2ff 100%);
    color:#334155;
    font-size:.72rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.55px;
    border-bottom:1px solid #dbe3f3;
    white-space:nowrap;
    padding:.72rem .72rem;
    vertical-align:middle;
}
#userDetailsModal .ud-table tbody td{
    border-top:1px solid #eef2f7;
    padding:.66rem .72rem;
    vertical-align:middle;
    color:#0f172a;
    font-size:.84rem;
}
#userDetailsModal .ud-table tbody tr:nth-child(even){
    background:#fbfdff;
}
#userDetailsModal .ud-table tbody tr:hover{
    background:#f5f9ff;
}
#userDetailsModal .ud-table td.text-nowrap{
    white-space:nowrap;
}
#userDetailsModal .ud-extend-panel{
    border:1px solid #dbe4f3;
    border-radius:18px;
    background:linear-gradient(180deg,#f8fbff 0%,#f1f6ff 100%);
    padding:14px;
    box-shadow:0 14px 28px rgba(37,99,235,.06);
}
#userDetailsModal .ud-panel-title{
    margin:0;
    font-size:.82rem;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:#0f172a;
}
#userDetailsModal .ud-panel-subtitle{
    margin:3px 0 0;
    font-size:.84rem;
    color:#64748b;
}
#userDetailsModal .ud-action-row{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
}
#userDetailsModal .ud-table-actions{
    display:inline-flex;
    align-items:center;
    gap:6px;
    flex-wrap:nowrap;
}
#createUserModal .modal-content{
    border:0;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 28px 68px rgba(15,23,42,.18);
}
#createUserModal .modal-header{
    border-bottom:1px solid #e2e8f0;
    background:linear-gradient(135deg,#f8fbff 0%,#eef4ff 54%,#f8fafc 100%);
    padding:1.1rem 1.25rem 1rem;
}
#createUserModal .modal-title{
    margin:0;
    color:#0f172a;
    font-weight:950;
}
#createUserModal .modal-body{
    background:linear-gradient(180deg,#fcfdff 0%,#f8fafc 100%);
    padding:1.1rem 1.25rem 1.2rem;
}
#createUserModal .cu-kicker{
    font-size:.72rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.68px;
    color:#2563eb;
}
#createUserModal .cu-subtitle{
    margin:.2rem 0 0;
    font-size:.88rem;
    color:#64748b;
}
#createUserModal .cu-shell{
    border:1px solid #dbe4f3;
    border-radius:18px;
    background:rgba(255,255,255,.94);
    box-shadow:0 14px 28px rgba(15,23,42,.05);
    padding:14px;
}
#createUserModal .cu-summary{
    border:1px solid #dbeafe;
    border-radius:16px;
    background:linear-gradient(135deg,#eff6ff 0%,#f8fbff 100%);
    padding:12px 13px;
}
#createUserModal .cu-summary-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:10px;
}
#createUserModal .cu-summary-label{
    font-size:.68rem;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.6px;
    color:#64748b;
}
#createUserModal .cu-summary-value{
    margin-top:3px;
    font-size:.95rem;
    font-weight:900;
    color:#0f172a;
    line-height:1.2;
    overflow-wrap:anywhere;
}
#createUserModal .cu-inline-note{
    font-size:.8rem;
    color:#64748b;
}
@media (max-width: 767.98px){
    #userDetailsModal .ud-nav-tabs{
        flex-wrap:nowrap;
        gap:8px;
        overflow-x:auto;
        padding-bottom:2px;
    }
    #userDetailsModal .ud-nav-tabs .nav-item{
        flex:0 0 auto;
    }
    #userDetailsModal .ud-nav-tabs .nav-link{
        width:auto;
        min-height:auto;
        padding:10px 12px;
        white-space:nowrap;
    }
    #userDetailsModal .ud-head-subtitle{
        display:none;
    }
    #userDetailsModal .ud-section-card{
        padding:12px;
    }
    #userDetailsModal .ud-table{
        min-width:680px;
    }
    #createUserModal .cu-summary-grid{
        grid-template-columns:1fr;
    }
}
</style>

<div class="container-fluid">
    <div class="nm-wrap">

        @if(!empty($error))
            <div class="nm-error mb-3">
                <i class="bi bi-exclamation-triangle" style="font-size:1.25rem;"></i>
                <div>
                    <div class="t">MikroTik Connection Error</div>
                    <div class="s">{{ $error }}</div>
                </div>
            </div>
        @endif

        <div class="nm-head">
            <div>
                <h2 class="nm-title">Network Users Management</h2>
                <p class="nm-sub">Hotspot customers, sessions, hosts & cookies — fast refresh, better tables, less chaos.</p>
            </div>
            <div class="nm-actions">
                <button class="btn btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus me-1"></i>Create Metered User
                </button>
                <button class="btn btn-soft" id="toggleAutoRefresh" type="button">
                    <i class="bi bi-clock-history me-1"></i><span id="autoRefreshLabel">Auto-refresh: OFF</span>
                </button>
                <button class="btn btn-primary btn-sm" type="button" onclick="refreshAllSections(false)">
                    <i class="bi bi-arrow-repeat me-1"></i>Refresh All
                </button>
            </div>
        </div>

        <div class="row g-3 kpi-grid">
            <div class="col-xl-3 col-md-6">
                <div class="kpi">
                    <div class="kpi-row">
                        <div>
                            <div class="k">Total Users</div>
                            <div class="v">{{ count($users) }}</div>
                        </div>
                        <div class="ico"><i class="bi bi-people fs-5"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi">
                    <div class="kpi-row">
                        <div>
                            <div class="k">Active Sessions</div>
                            <div class="v">{{ count($activeSessions) }}</div>
                        </div>
                        <div class="ico"><i class="bi bi-wifi fs-5"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi">
                    <div class="kpi-row">
                        <div>
                            <div class="k">Common Package</div>
                            <div class="v text-primary">
                                {{ collect($users)->pluck('profile')->countBy()->sortDesc()->keys()->first() ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="ico"><i class="bi bi-box-seam fs-5"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi">
                    <div class="kpi-row">
                        <div>
                            <div class="k">Unique Hosts</div>
                            <div class="v text-success">
                                {{ collect($hosts)->pluck('mac-address')->unique()->count() }}
                            </div>
                        </div>
                        <div class="ico"><i class="bi bi-laptop fs-5"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="nm-tabs mt-3">
            <div class="nm-tabs-head">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="mb-0" style="font-weight:950; color:#0b1220;">
                        <i class="bi bi-wifi me-1"></i>Hotspot Customers
                    </h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge-soft"><i class="bi bi-lightning-charge me-1"></i>Fast refresh</span>
                        <span class="badge-soft"><i class="bi bi-table me-1"></i>Smart tables</span>
                    </div>
                </div>

                <div class="mt-2">
                    {{-- NO refresh on tab click --}}
                    <ul class="nav nav-pills mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#users-pane" type="button" role="tab">
                                <i class="bi bi-people me-1"></i>Users
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#sessions-pane" type="button" role="tab">
                                <i class="bi bi-wifi me-1"></i>Sessions
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hosts-pane" type="button" role="tab">
                                <i class="bi bi-laptop me-1"></i>Hosts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#cookies-pane" type="button" role="tab">
                                <i class="bi bi-shield-check me-1"></i>Cookies
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="users-pane" role="tabpanel">
                    <div class="section-card">
                        <div class="section-head">
                            <div class="left">
                                <i class="bi bi-people me-1"></i> Hotspot Users
                                <span class="badge-soft">{{ count($users) }}</span>
                                <span class="badge-soft">Online {{ collect($users)->where('status', 'active')->count() }}</span>
                                <span class="badge-soft">Temporary {{ collect($users)->where('account_type', 'hotspot_temporary')->count() }}</span>
                                <span class="badge-soft">Metered {{ collect($users)->where('account_type', 'metered_static')->count() }}</span>
                            </div>
                            <button class="refresh-btn" type="button" onclick="refreshSection('users', false)" title="Refresh users">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="p-0 fade-section" id="users-section">
                            @include('customers.partials.users', ['users' => $users])
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="sessions-pane" role="tabpanel">
                    <div class="section-card">
                        <div class="section-head">
                            <div class="left">
                                <i class="bi bi-wifi me-1"></i> Active Sessions
                                <span class="badge-soft">{{ count($activeSessions) }}</span>
                            </div>
                            <button class="refresh-btn" type="button" onclick="refreshSection('sessions', false)" title="Refresh sessions">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="p-0 fade-section" id="sessions-section">
                            @include('customers.partials.sessions', ['activeSessions' => $activeSessions])
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="hosts-pane" role="tabpanel">
                    <div class="section-card">
                        <div class="section-head">
                            <div class="left">
                                <i class="bi bi-laptop me-1"></i> Network Hosts
                                <span class="badge-soft">{{ collect($hosts)->pluck('mac-address')->unique()->count() }}</span>
                            </div>
                            <button class="refresh-btn" type="button" onclick="refreshSection('hosts', false)" title="Refresh hosts">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="p-0 fade-section" id="hosts-section">
                            @include('customers.partials.hosts', ['hosts' => $hosts])
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="cookies-pane" role="tabpanel">
                    <div class="section-card">
                        <div class="section-head">
                            <div class="left">
                                <i class="bi bi-shield-check me-1"></i> Cookies
                                <span class="badge-soft">{{ count($cookies) }}</span>
                            </div>
                            <button class="refresh-btn" type="button" onclick="refreshSection('cookies', false)" title="Refresh cookies">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="p-0 fade-section" id="cookies-section">
                            @include('customers.partials.cookies', ['cookies' => $cookies])
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="ud-head-copy">
                    <div class="ud-head-kicker">Customer Workspace</div>
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details & Billing</h5>
                    <p class="ud-head-subtitle">Profile, usage, package control and billing history in one place.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="userDetailsLoader" class="ud-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div id="userDetailsContent" style="display:none;">
                    <div class="ud-tab-shell">
                        <ul class="nav ud-nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#user-general-tab" type="button" role="tab">
                                    <span class="ud-tab-kicker">Profile</span>
                                    <span class="ud-tab-label">General</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#user-usage-tab" type="button" role="tab">
                                    <span class="ud-tab-kicker">Activity</span>
                                    <span class="ud-tab-label">Usage</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#user-package-tab" type="button" role="tab">
                                    <span class="ud-tab-kicker">Service</span>
                                    <span class="ud-tab-label">Package</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="userBillingTabBtn" data-bs-toggle="tab" data-bs-target="#user-billing-tab" type="button" role="tab">
                                    <span class="ud-tab-kicker">Records</span>
                                    <span class="ud-tab-label" id="userBillingTabLabel">History</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="user-general-tab" role="tabpanel">
                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Username</div>
                                        <div class="v" id="udUsername">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Profile</div>
                                        <div class="v" id="udProfile">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">MAC Address</div>
                                        <div class="v" id="udMac">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Status</div>
                                        <div class="v" id="udStatus">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input type="text" class="form-control form-control-sm" id="generalName" placeholder="Customer name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Phone</label>
                                    <input type="text" class="form-control form-control-sm" id="generalPhone" placeholder="07XXXXXXXX">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" class="form-control form-control-sm" id="generalEmail" placeholder="name@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Address</label>
                                    <input type="text" class="form-control form-control-sm" id="generalAddress" placeholder="Physical address">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <select class="form-select form-select-sm" id="generalStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Plan</label>
                                    <select class="form-select form-select-sm" id="generalPlanSelect">
                                        <option value="">No plan</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="saveCustomerProfileBtn">
                                        <i class="bi bi-person-check me-1"></i>Save Profile
                                    </button>
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Total Billed</div>
                                        <div class="v" id="udBalanceBilled">KES 0.00</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Total Paid</div>
                                        <div class="v" id="udBalancePaid">KES 0.00</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Total Due</div>
                                        <div class="v" id="udBalanceDue">KES 0.00</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Open Invoices</div>
                                        <div class="v" id="udOpenInvoices">0</div>
                                    </div>
                                </div>
                            </div>

                            <div class="ud-section-card mt-3">
                                <div class="ud-section-head">
                                    <div>
                                        <h6 class="ud-section-title">Recent Payments</h6>
                                        <p class="ud-section-subtitle">Latest successful and pending payment activity for this user.</p>
                                    </div>
                                    <span class="ud-section-note">Finance</span>
                                </div>
                                <div class="table-responsive ud-table-shell">
                                    <table class="table table-sm mb-0 ud-table">
                                        <thead>
                                            <tr>
                                                <th>Paid At</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Reference</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userPaymentsTbody">
                                            <tr><td colspan="5" class="text-muted text-center">No payments yet.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="user-usage-tab" role="tabpanel">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Uptime</div>
                                        <div class="v" id="udUptime">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Limit Uptime</div>
                                        <div class="v" id="udLimitUptime">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Last Seen</div>
                                        <div class="v" id="udLastSeen">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="user-mini-card">
                                        <div class="k">Total Online</div>
                                        <div class="v" id="udTotalOnline">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <div class="user-mini-card">
                                        <div class="k">Session Address</div>
                                        <div class="v" id="udSessionAddress">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="user-mini-card">
                                        <div class="k">Session Time</div>
                                        <div class="v" id="udSessionUptime">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 invoice-highlight">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div><strong>Total Usage:</strong> <span id="udUsageTotal">0 B</span></div>
                                    <div><strong>Router Total:</strong> <span id="udUsageRouter">0 B</span></div>
                                    <div><strong>DB Total:</strong> <span id="udUsageDb">0 B</span></div>
                                </div>
                            </div>

                            <div class="ud-section-card mt-3">
                                <div class="ud-section-head">
                                    <div>
                                        <h6 class="ud-section-title">Session History</h6>
                                        <p class="ud-section-subtitle">Each reconnect opens a new traceable session with its uptime and usage.</p>
                                    </div>
                                    <span class="ud-section-note">Trace</span>
                                </div>
                                <div class="table-responsive ud-table-shell">
                                    <table class="table table-sm mb-0 ud-table">
                                        <thead>
                                            <tr>
                                                <th>Started</th>
                                                <th>Ended</th>
                                                <th>Package</th>
                                                <th>IP</th>
                                                <th>Uptime</th>
                                                <th>Usage</th>
                                                <th>Status</th>
                                                <th>View</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userSessionHistoryTbody">
                                            <tr><td colspan="8" class="text-muted text-center">No hotspot session history yet.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="user-package-tab" role="tabpanel">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="user-mini-card">
                                        <div class="k">Package</div>
                                        <div class="v" id="udPkgName">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Type</div>
                                        <div class="v" id="udPkgCategory">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Amount</div>
                                        <div class="v" id="udPkgPrice">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Duration</div>
                                        <div class="v" id="udPkgDuration">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Remaining</div>
                                        <div class="v" id="udPkgRemaining">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div class="user-mini-card">
                                        <div class="k">Subscribed At</div>
                                        <div class="v" id="udPkgSubscribedAt">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="user-mini-card">
                                        <div class="k">Expires At</div>
                                        <div class="v" id="udPkgExpiresAt">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Uptime</div>
                                        <div class="v" id="udPkgUptime">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="user-mini-card">
                                        <div class="k">Status</div>
                                        <div class="v" id="udPkgConnStatus">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 ud-extend-panel" id="packageExtendPanel">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <h6 class="ud-panel-title">Manage Subscription</h6>
                                        <p class="ud-panel-subtitle">Extend with a saved package, or expire the active access immediately.</p>
                                    </div>
                                </div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Extend Using Package</label>
                                        <select class="form-select" id="packageExtendPlanSelect">
                                            <option value="">Select package</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Minutes (optional)</label>
                                        <input type="number" class="form-control" id="packageExtendMinutes" min="1" step="1" placeholder="Use package duration">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check mt-4 pt-1">
                                            <input class="form-check-input" type="checkbox" id="packageExtendForce">
                                            <label class="form-check-label fw-bold" for="packageExtendForce">Force</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="ud-action-row">
                                            <button type="button" class="btn btn-success" id="extendPackageBtn">
                                                <i class="bi bi-clock-history me-1"></i>Extend Package
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" id="expirePackageBtn">
                                                <i class="bi bi-slash-circle me-1"></i>Expire Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2" id="packageExtendHelp">
                                    Leave minutes blank to use selected package duration.
                                </div>
                                <div class="mt-2" id="packageExtendResult" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="user-billing-tab" role="tabpanel">
                            <input type="hidden" id="billingUsername" value="">
                            <input type="hidden" id="billingUsageBytes" value="0">

                            <div id="hotspotHistoryPanel" style="display:none;">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="ud-section-card h-100">
                                            <div class="ud-section-head">
                                                <div>
                                                    <h6 class="ud-section-title">Subscription History</h6>
                                                    <p class="ud-section-subtitle">Track package starts, expiry times, remaining time and direct actions.</p>
                                                </div>
                                                <span class="ud-section-note">Hotspot</span>
                                            </div>
                                            <div class="table-responsive ud-table-shell">
                                                <table class="table table-sm mb-0 ud-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Package</th>
                                                            <th>Started</th>
                                                            <th>Expires</th>
                                                            <th>Duration</th>
                                                            <th>Status</th>
                                                            <th>Remaining</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="hotspotSubscriptionsTbody">
                                                        <tr><td colspan="7" class="text-muted text-center">No hotspot subscriptions yet.</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ud-section-card h-100">
                                            <div class="ud-section-head">
                                                <div>
                                                    <h6 class="ud-section-title">Payment Attempts</h6>
                                                    <p class="ud-section-subtitle">Every payment request and outcome tied to hotspot access for this user.</p>
                                                </div>
                                                <span class="ud-section-note">MegaPay</span>
                                            </div>
                                            <div class="table-responsive ud-table-shell">
                                                <table class="table table-sm mb-0 ud-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Time</th>
                                                            <th>Ref</th>
                                                            <th>Package</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                            <th>Receipt</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="hotspotTransactionsTbody">
                                                        <tr><td colspan="6" class="text-muted text-center">No hotspot payment attempts yet.</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="meteredBillingPanel" class="ud-section-card">
                                <div class="ud-section-head">
                                    <div>
                                        <h6 class="ud-section-title">Metered Billing</h6>
                                        <p class="ud-section-subtitle">Set billing rate, usage window and generate an invoice from current usage.</p>
                                    </div>
                                    <span class="ud-section-note">Billing</span>
                                </div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Plan</label>
                                        <select class="form-select" id="billingPlanSelect">
                                            <option value="">No plan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold" id="billingRateLabel">Rate per MB</label>
                                        <input type="number" class="form-control" id="billingRateInput" min="0" step="0.01" placeholder="0.00">
                                        <div class="small text-muted mt-1" id="billingRateHint">Metered plans are billed per MB.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Currency</label>
                                        <input type="text" class="form-control" id="billingCurrencyInput" value="KES" maxlength="8">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="billingNotifyCustomer">
                                            <label class="form-check-label fw-bold" for="billingNotifyCustomer">Notify SMS</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="small text-muted mb-1">Usage to bill</div>
                                        <div class="fw-bold" id="billingUsageLabel">0 B</div>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Issued Date</label>
                                        <input type="date" class="form-control form-control-sm" id="billingIssuedAt">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Due Date</label>
                                        <input type="date" class="form-control form-control-sm" id="billingDueDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Tax %</label>
                                        <input type="number" class="form-control form-control-sm" id="billingTaxPercent" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Penalty %</label>
                                        <input type="number" class="form-control form-control-sm" id="billingPenaltyPercent" min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Notes</label>
                                        <textarea class="form-control form-control-sm" id="billingNotes" rows="2" placeholder="Optional notes for invoice"></textarea>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="saveBillingRateBtn">
                                        <i class="bi bi-save me-1"></i>Save Rate
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" id="generateInvoiceBtn">
                                        <i class="bi bi-receipt me-1"></i>Generate Invoice
                                    </button>
                                </div>

                                <div class="mt-3" id="invoiceGeneratedBox" style="display:none;"></div>

                                <div class="mt-3">
                                    <h6 class="mb-2">Recent Invoices</h6>
                                    <div class="table-responsive ud-table-shell">
                                        <table class="table table-sm mb-0 ud-table">
                                            <thead>
                                                <tr>
                                                    <th>Invoice #</th>
                                                    <th>Total</th>
                                                    <th>Balance</th>
                                                    <th>Status</th>
                                                    <th>Due</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="userInvoicesTbody">
                                                <tr><td colspan="7" class="text-muted text-center">No invoices yet.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="cu-kicker">Static Account Setup</div>
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Create Metered User</h5>
                    <p class="cu-subtitle">Create a permanent login account using saved metered plans and router profiles.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @php
                    $meteredPlans = collect($plans ?? [])->filter(function ($plan) {
                        return strtolower((string)(is_array($plan) ? ($plan['category'] ?? 'hotspot') : ($plan->category ?? 'hotspot'))) === 'metered';
                    })->values();
                @endphp
                <div class="cu-shell">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" class="form-control" id="createUsername" placeholder="e.g. client001">
                            <div class="cu-inline-note mt-1">Use a permanent login name for this customer.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" class="form-control" id="createPhone" placeholder="07XXXXXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" class="form-control" id="createName" placeholder="Customer name">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Password Setup</label>
                            <select class="form-select" id="createPasswordMode">
                                <option value="auto">Auto generate</option>
                                <option value="phone_last6">Use phone last 6 digits</option>
                                <option value="custom">Enter custom password</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Password</label>
                            <input type="text" class="form-control" id="createPassword" placeholder="Generated automatically">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Metered Plan</label>
                            <select class="form-select" id="createPlan">
                                @if($meteredPlans->isEmpty())
                                    <option value="">No metered plans available</option>
                                @else
                                    <option value="">Select metered plan</option>
                                    @foreach($meteredPlans as $plan)
                                        <option
                                            value="{{ $plan['id'] ?? $plan->id }}"
                                            data-profile="{{ $plan['profile'] ?? $plan->profile ?? '' }}"
                                            data-price="{{ (float)($plan['price'] ?? $plan->price ?? 0) }}"
                                            data-name="{{ $plan['name'] ?? $plan->name }}"
                                        >
                                            {{ $plan['name'] ?? $plan->name }} - KES {{ number_format((float)($plan['price'] ?? $plan->price ?? 0), 2) }} /MB
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Router Profile</label>
                            <select class="form-select" id="createProfile">
                                <option value="">Use selected plan profile</option>
                                @foreach(($profileOptions ?? []) as $profileOption)
                                    <option value="{{ $profileOption }}">{{ $profileOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Account Status</label>
                            <select class="form-select" id="createStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Limit Uptime</label>
                            <select class="form-select" id="createLimitUptime">
                                <option value="">No uptime cap (recommended)</option>
                                <option value="1h">1 hour</option>
                                <option value="12h">12 hours</option>
                                <option value="1d">1 day</option>
                                <option value="7d">7 days</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" id="createEmail" placeholder="name@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Address</label>
                            <input type="text" class="form-control" id="createAddress" placeholder="Physical address">
                        </div>

                        <div class="col-md-12">
                            <div class="cu-summary">
                                <div class="cu-summary-grid">
                                    <div>
                                        <div class="cu-summary-label">Plan</div>
                                        <div class="cu-summary-value" id="createPlanSummaryName">No plan selected</div>
                                    </div>
                                    <div>
                                        <div class="cu-summary-label">Rate</div>
                                        <div class="cu-summary-value" id="createPlanSummaryRate">KES 0.00 /MB</div>
                                    </div>
                                    <div>
                                        <div class="cu-summary-label">Router Profile</div>
                                        <div class="cu-summary-value" id="createPlanSummaryProfile">Pending plan selection</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="createNotify">
                                <label class="form-check-label fw-bold" for="createNotify">Send SMS with login details</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Comment</label>
                            <input type="text" class="form-control" id="createComment" placeholder="Optional note for this account">
                        </div>
                    </div>
                </div>
                <div id="createUserAlert" class="mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="createUserBtn">
                    <i class="bi bi-check2-circle me-1"></i>Create Metered User
                </button>
            </div>
        </div>
    </div>
</div>

@include('customers.partials.traffic-modal')

<script>
let AUTO_REFRESH_ON = false;
let AUTO_REFRESH_TIMER = null;
let DT_REINIT_TIMER = null;
let CURRENT_USER_DETAILS = null;
let CURRENT_USER_USERNAME = '';
let USER_DETAILS_REFRESH_TIMER = null;
let CURRENT_USER_FILTER = 'all';
let CURRENT_DETAIL_MODE = 'hotspot';
let CURRENT_EXTENSION_OPTIONS = [];
let CURRENT_FORCE_EXTENSION_OPTIONS = [];
let TRAFFIC_CHART = null;
let TRAFFIC_POLL_TIMER = null;
let TRAFFIC_ACTIVE_TARGET = { username: '', mac: '', connectionId: null };
let TRAFFIC_HISTORY = [];
let TRAFFIC_LAST_SNAPSHOT = null;

const SECTION_ROUTE_TEMPLATE = @json(route('customers.section', ['section' => '__SECTION__'], false));
const VALID_TYPES = new Set(['users','sessions','hosts','cookies']);
const CSRF_TOKEN = '{{ csrf_token() }}';
const ROUTES = {
    disconnect: @json(route('customers.disconnect', [], false)),
    monitorTraffic: @json(route('customers.monitor', [], false)),
    userDetails: @json(route('customers.user.details', [], false)),
    updateUser: @json(route('customers.user.update', [], false)),
    createUser: @json(route('customers.user.create', [], false)),
    saveCustomerProfile: @json(route('customers.user.profile', [], false)),
    disableUser: @json(route('customers.user.disable', [], false)),
    enableUser: @json(route('customers.user.enable', [], false)),
    extendPackage: @json(route('customers.user.extend-package', [], false)),
    expirePackage: @json(route('customers.user.expire-package', [], false)),
    blockHost: @json(route('customers.host.block', [], false)),
    deleteCookie: @json(route('customers.cookie.delete', [], false)),
    saveBillingRate: @json(route('customers.user.billing-rate', [], false)),
    generateInvoice: @json(route('customers.user.generate-invoice', [], false)),
    invoiceView: @json(route('revenue.index', [], false)),
    invoicePrint: @json(url('/invoices')),
};

function setAutoRefresh(on){
    AUTO_REFRESH_ON = !!on;
    $('#autoRefreshLabel').text(`Auto-refresh: ${AUTO_REFRESH_ON ? 'ON' : 'OFF'}`);
    if (AUTO_REFRESH_ON){
        startAutoRefresh();
        toast('Auto-refresh enabled', 'Will refresh sections every 30s.', 'success');
    } else {
        stopAutoRefresh();
        toast('Auto-refresh paused', 'Manual refresh still works.', 'info');
    }
}

function startAutoRefresh(){
    stopAutoRefresh();
    AUTO_REFRESH_TIMER = setInterval(() => {
        if (!AUTO_REFRESH_ON) return;
        refreshAllSections(true);
    }, 30000);
}
function stopAutoRefresh(){
    if (AUTO_REFRESH_TIMER) clearInterval(AUTO_REFRESH_TIMER);
    AUTO_REFRESH_TIMER = null;
}

function addOverlay($section){
    const overlay = $('<div class="loading-overlay"><div class="spinner"></div></div>');
    $section.append(overlay);
}
function removeOverlay($section){
    $section.find('.loading-overlay').remove();
}

function refreshSection(type, silent=false) {
    return new Promise((resolve) => {
        type = (type || '').toString().toLowerCase().trim();
        if (!VALID_TYPES.has(type)) {
            resolve(false);
            return;
        }

        const section = $(`#${type}-section`);
        if (!section.length) {
            resolve(false);
            return;
        }

        const existingRows = section.find('tbody tr').length;
        addOverlay(section);

        const url = SECTION_ROUTE_TEMPLATE.replace('__SECTION__', encodeURIComponent(type));

        $.get(url, function(html) {
            const raw = (html || '').toString().trim();
            if (raw === '') {
                removeOverlay(section);
                if (!silent) {
                    toast('Refresh skipped', `No ${type} data returned. Keeping existing rows.`, 'warning');
                }
                resolve(false);
                return;
            }

            const incoming = $('<div>').html(raw);
            const incomingRows = incoming.find('tbody tr').length;
            const hasErrorAlert = incoming.find('.alert, .nm-error').length > 0;
            const hasIncomingTable = incoming.find('table').length > 0;

            // Prevent wiping already loaded rows when backend returns section-error fragment.
            if (existingRows > 0 && hasErrorAlert) {
                removeOverlay(section);
                if (!silent) {
                    toast('Refresh skipped', `Could not refresh ${type} right now. Keeping existing rows.`, 'warning');
                }
                resolve(false);
                return;
            }

            // Defensive: if response has no table markup while we already had rows, keep existing content.
            if (existingRows > 0 && !hasIncomingTable) {
                removeOverlay(section);
                if (!silent) {
                    toast('Refresh skipped', `Unexpected ${type} response. Keeping existing rows.`, 'warning');
                }
                resolve(false);
                return;
            }

            section.html(raw);

            // re-init after DOM swap; debounce avoids double triggers
            scheduleReinit();

            if (!silent) toast(`${cap(type)} refreshed`, 'Data updated successfully.', 'success');
            resolve(true);
        }).fail(function(xhr) {
            removeOverlay(section);
            if (!silent) {
                const code = xhr?.status ?? '??';
                toast('Refresh failed', `Could not refresh ${type} (HTTP ${code}).`, 'danger');
            }
            resolve(false);
        });
    });
}

async function refreshAllSections(silent=false) {
    for (const t of ['users', 'sessions', 'hosts', 'cookies']) {
        await refreshSection(t, silent);
    }
}

function scheduleReinit(){
    if (DT_REINIT_TIMER) clearTimeout(DT_REINIT_TIMER);
    DT_REINIT_TIMER = setTimeout(() => {
        safeInitDataTables();
        initializeDropdowns();
    }, 80);
}

/**
 * This is the important bit:
 * - If a table is already initialized, we destroy it safely.
 * - We also remove DataTables wrappers left behind (common after ajax swaps).
 */
function safeInitDataTables() {
    $('table.display').each(function() {
        if ($(this).closest('#trafficModal').length) return;
        const $tbl = $(this);

        if ($.fn.dataTable.isDataTable(this)) {
            try {
                $tbl.DataTable().clear();
                $tbl.DataTable().destroy(true);
            } catch (e) {
                try { $tbl.DataTable().destroy(); } catch (_) {}
            }
        }

        const id = $tbl.attr('id');
        if (id) {
            const $wrap = $('#' + id + '_wrapper');
            if ($wrap.length && !$wrap.find('table').length) {
                $wrap.remove();
            }
        }
    });

    $('table.display').each(function() {
        if ($(this).closest('#trafficModal').length) return;
        if ($.fn.dataTable.isDataTable(this)) return;

        $(this).DataTable({
            responsive: true,
            pageLength: 25,
            order: [],
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            deferRender: true,
            stateSave: false,
            retrieve: true,
            destroy: true,

            dom:
                '<"row align-items-center mb-2"' +
                    '<"col-sm-12 col-md-6 d-flex align-items-center gap-2"l>' +
                    '<"col-sm-12 col-md-6 d-flex justify-content-md-end mt-2 mt-md-0"f>' +
                '>' +
                'rt' +
                '<"row align-items-center mt-2"' +
                    '<"col-sm-12 col-md-6"i>' +
                    '<"col-sm-12 col-md-6 d-flex justify-content-md-end"p>' +
                '>',

            language: {
                search: "",
                searchPlaceholder: "Search in table…",
                lengthMenu: "Rows: _MENU_",
                info: "Showing _START_–_END_ of _TOTAL_",
                infoEmpty: "No records",
                infoFiltered: "(filtered from _MAX_)",
                paginate: { previous: "‹", next: "›" }
            }
        });
    });

    // row highlight
    $(document).off('click', 'table.display tbody tr')
        .on('click', 'table.display tbody tr', function(){
            $(this).closest('tbody').find('tr').removeClass('selected');
            $(this).addClass('selected');
        });

    applyUsersFilter();
}

function initializeDropdowns() {
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(el => new bootstrap.Dropdown(el));

    $('.dropdown-toggle').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        bootstrap.Dropdown.getOrCreateInstance(this).toggle();
    });

    $(document).off('click.dt.dropdown').on('click.dt.dropdown', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
}

function syncUserFilterButtons() {
    const filter = (CURRENT_USER_FILTER || 'all').toLowerCase();
    $('.user-filter-btn').removeClass('active');
    $(`.user-filter-btn[data-filter="${filter}"]`).addClass('active');
}

function applyUsersFilter() {
    syncUserFilterButtons();
    const tableEl = document.querySelector('#users-section table.display');
    if (!tableEl || !$.fn.dataTable.isDataTable(tableEl)) {
        return;
    }

    const dt = $(tableEl).DataTable();
    dt.column(1).search('');
    dt.column(2).search('');

    switch ((CURRENT_USER_FILTER || 'all').toLowerCase()) {
        case 'online':
            dt.column(2).search('^Online$', true, false);
            break;
        case 'offline':
            dt.column(2).search('^(Offline|Disabled)$', true, false);
            break;
        case 'temporary':
            dt.column(1).search('^Temporary$', true, false);
            break;
        case 'hotspot':
            dt.column(1).search('^Hotspot$', true, false);
            break;
        case 'metered':
            dt.column(1).search('^Metered$', true, false);
            break;
        default:
            break;
    }

    dt.draw();
}

function stopTrafficPolling() {
    if (TRAFFIC_POLL_TIMER) {
        clearInterval(TRAFFIC_POLL_TIMER);
        TRAFFIC_POLL_TIMER = null;
    }
}

function buildTrafficGradient(context, topColor, bottomColor) {
    const chart = context?.chart;
    const chartArea = chart?.chartArea;
    const ctx = chart?.ctx;

    if (!chartArea || !ctx) {
        return bottomColor;
    }

    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    gradient.addColorStop(0, topColor);
    gradient.addColorStop(1, bottomColor);

    return gradient;
}

function getTrafficModeMeta(mode) {
    switch ((mode || '').toLowerCase()) {
        case 'session_delta':
            return {
                tableLabel: 'Exact sample',
                hint: 'Per-user throughput derived from session byte changes over the last 1 second.',
                badgeState: 'live',
                badgeLabel: 'Per-user live',
                warmup: false,
            };
        case 'router_assist':
            return {
                tableLabel: 'Interface assist',
                hint: 'Session counters were flat in this interval, so the graph is using live interface activity as a guide.',
                badgeState: 'fallback',
                badgeLabel: 'Interface assist',
                warmup: false,
            };
        case 'router_interface':
            return {
                tableLabel: 'Router fallback',
                hint: 'Showing router interface throughput until a user-specific delta sample is ready.',
                badgeState: 'fallback',
                badgeLabel: 'Router fallback',
                warmup: false,
            };
        case 'session_idle':
            return {
                tableLabel: 'Idle sample',
                hint: 'Session is online, but no byte movement was detected in the last 1-second sample.',
                badgeState: 'idle',
                badgeLabel: 'Idle sample',
                warmup: false,
            };
        case 'session_warmup':
            return {
                tableLabel: 'Calibrating',
                hint: 'Collecting the baseline sample. The first accurate per-user rate appears on the next poll.',
                badgeState: 'fallback',
                badgeLabel: 'Calibrating',
                warmup: true,
            };
        case 'stored_history':
            return {
                tableLabel: 'Stored sample',
                hint: 'Showing saved usage samples for this session. This graph stays available after the session ends.',
                badgeState: 'offline',
                badgeLabel: 'Stored session',
                warmup: false,
            };
        default:
            return {
                tableLabel: 'Offline',
                hint: 'User is offline. Last session counters are shown.',
                badgeState: 'offline',
                badgeLabel: 'Offline',
                warmup: false,
            };
    }
}

function updateTrafficFootStats() {
    const totals = TRAFFIC_HISTORY.map((point) => Number(point.tx || 0) + Number(point.rx || 0));
    const average = totals.length
        ? Math.round(totals.reduce((sum, value) => sum + value, 0) / totals.length)
        : 0;
    const peak = totals.length ? Math.max(...totals) : 0;

    $('#trafficCurrentAvg').text(formatBitsPerSecond(average));
    $('#trafficCurrentPeak').text(formatBitsPerSecond(peak));
}

function setTrafficPresentation(isHistorical = false) {
    $('#trafficModalSubtitle').text(
        isHistorical
            ? 'Saved usage samples for this exact session, available even after the user goes offline.'
            : 'Session delta with router assist, sampled every 1 second'
    );
    $('#trafficChartKicker').text(isHistorical ? 'Saved Session Telemetry' : 'Live Session Telemetry');
    $('#trafficChartTitle').text(isHistorical ? 'Session Usage History' : 'Live Throughput');
}

function formatTrafficSampleSource(source = '') {
    const key = (source || '').toString().trim().toLowerCase();
    switch (key) {
        case 'session_start':
            return 'Session start';
        case 'session_end':
            return 'Session end';
        case 'session_expired':
            return 'Expired end';
        case 'traffic_poll':
            return 'Traffic poll';
        case 'customers_index':
            return 'Customers scan';
        case 'users_panel':
            return 'Users scan';
        case 'sessions_panel':
            return 'Sessions scan';
        case 'user_details':
            return 'Details scan';
        default:
            return cap(key.replace(/_/g, ' ') || 'stored sample');
    }
}

function resetTrafficChart() {
    stopTrafficPolling();
    TRAFFIC_HISTORY = [];
    TRAFFIC_LAST_SNAPSHOT = null;
    if (TRAFFIC_CHART) {
        TRAFFIC_CHART.destroy();
        TRAFFIC_CHART = null;
    }

    const canvas = document.getElementById('trafficLiveChart');
    if (!canvas || !window.Chart) return;

    const ctx = canvas.getContext('2d');
    TRAFFIC_CHART = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'TX',
                    data: [],
                    borderColor: '#f59e0b',
                    backgroundColor(context) {
                        return buildTrafficGradient(
                            context,
                            'rgba(245,158,11,0.34)',
                            'rgba(245,158,11,0.02)'
                        );
                    },
                    cubicInterpolationMode: 'monotone',
                    tension: 0.28,
                    fill: true,
                    borderWidth: 1.9,
                    pointRadius: 0,
                    pointHoverRadius: 2,
                    pointHitRadius: 10,
                },
                {
                    label: 'RX',
                    data: [],
                    borderColor: '#22c55e',
                    backgroundColor(context) {
                        return buildTrafficGradient(
                            context,
                            'rgba(34,197,94,0.28)',
                            'rgba(34,197,94,0.02)'
                        );
                    },
                    cubicInterpolationMode: 'monotone',
                    tension: 0.28,
                    fill: true,
                    borderWidth: 1.9,
                    pointRadius: 0,
                    pointHoverRadius: 2,
                    pointHitRadius: 10,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            normalized: true,
            spanGaps: true,
            layout: {
                padding: {
                    top: 6,
                    right: 6,
                    left: 4,
                    bottom: 2,
                }
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(2,6,23,0.92)',
                    borderColor: 'rgba(148,163,184,0.18)',
                    borderWidth: 1,
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    callbacks: {
                        title(items) {
                            const label = items?.[0]?.label || '';
                            return label ? `Sample ${label}` : 'Sample';
                        },
                        label(context) {
                            return `${context.dataset.label}: ${formatBitsPerSecond(context.parsed.y)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: 'rgba(226,232,240,0.78)',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 5,
                    },
                    grid: {
                        color: 'rgba(148,163,184,0.06)',
                        tickColor: 'rgba(148,163,184,0.06)',
                    },
                    border: {
                        color: 'rgba(148,163,184,0.14)',
                    }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 1000,
                    ticks: {
                        color: 'rgba(226,232,240,0.78)',
                        maxTicksLimit: 5,
                        callback(value) {
                            return formatBitsPerSecond(value);
                        }
                    },
                    grid: {
                        color: 'rgba(148,163,184,0.08)',
                    },
                    border: {
                        color: 'rgba(148,163,184,0.14)',
                    }
                }
            }
        }
    });
}

function calculateTrafficCeiling(maxBps) {
    const peak = Number(maxBps || 0);
    if (!Number.isFinite(peak) || peak <= 0) {
        return 1000;
    }

    const padded = peak * 1.18;
    const magnitude = 10 ** Math.max(0, Math.floor(Math.log10(padded)));
    const normalized = padded / magnitude;

    if (normalized <= 1) {
        return magnitude;
    }
    if (normalized <= 2) {
        return 2 * magnitude;
    }
    if (normalized <= 5) {
        return 5 * magnitude;
    }

    return 10 * magnitude;
}

function renderStoredTrafficHistory(data) {
    stopTrafficPolling();
    TRAFFIC_LAST_SNAPSHOT = null;
    TRAFFIC_HISTORY = [];

    const history = Array.isArray(data?.history) ? data.history : [];
    const content = $('#trafficModalContent tbody');
    content.empty();

    if (!TRAFFIC_CHART) {
        resetTrafficChart();
    }

    const labels = [];
    const txSeries = [];
    const rxSeries = [];

    history.forEach((point) => {
        const tx = Number(point?.tx || 0);
        const rx = Number(point?.rx || 0);

        labels.push((point?.label || '').toString() || formatDate(point?.recorded_at || null));
        txSeries.push(tx);
        rxSeries.push(rx);
        TRAFFIC_HISTORY.push({ tx, rx });

        content.prepend(`
            <tr>
                <td>${escapeHtml(formatDate(point?.recorded_at || null))}</td>
                <td>${escapeHtml(data?.username || TRAFFIC_ACTIVE_TARGET.username || '-')}</td>
                <td>${escapeHtml(data?.connection_id ? `Session #${data.connection_id}` : '-')}</td>
                <td>${escapeHtml(formatTrafficSampleSource(point?.source || 'stored_history'))}</td>
                <td title="${escapeHtml(`${tx} bps`)}">${escapeHtml(formatBitsPerSecond(tx))}</td>
                <td title="${escapeHtml(`${rx} bps`)}">${escapeHtml(formatBitsPerSecond(rx))}</td>
            </tr>
        `);
    });

    if (TRAFFIC_CHART) {
        TRAFFIC_CHART.data.labels = labels;
        TRAFFIC_CHART.data.datasets[0].data = txSeries;
        TRAFFIC_CHART.data.datasets[1].data = rxSeries;

        const peakBps = TRAFFIC_HISTORY.reduce((max, point) => {
            return Math.max(max, Number(point.tx || 0), Number(point.rx || 0));
        }, 0);
        TRAFFIC_CHART.options.scales.y.suggestedMax = calculateTrafficCeiling(peakBps);
        TRAFFIC_CHART.update();
    }

    const latestPoint = history[history.length - 1] || {};
    const snapshot = {
        ...(data || {}),
        tx: Number(latestPoint?.tx || 0),
        rx: Number(latestPoint?.rx || 0),
        live_mode: 'stored_history',
    };

    updateTrafficSummary(snapshot);
    updateTrafficFootStats();

    if (history.length === 0) {
        $('#trafficChartHint').text('No stored samples yet for this session.');
        content.html(`
            <tr>
                <td class="text-muted">No samples</td>
                <td>${escapeHtml(data?.username || '-')}</td>
                <td>${escapeHtml(data?.connection_id ? `Session #${data.connection_id}` : '-')}</td>
                <td>Stored session</td>
                <td>0 bps</td>
                <td>0 bps</td>
            </tr>
        `);
        return;
    }

    $('#trafficChartHint').text(`Loaded ${history.length} stored sample${history.length === 1 ? '' : 's'} for this session.`);
}

function pushTrafficPoint(data) {
    if (!TRAFFIC_CHART) return;

    const label = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
    const mode = (data.live_mode || '').toLowerCase();
    const txBps = mode === 'session_warmup' ? null : Number(data.tx || 0);
    const rxBps = mode === 'session_warmup' ? null : Number(data.rx || 0);

    TRAFFIC_CHART.data.labels.push(label);
    TRAFFIC_CHART.data.datasets[0].data.push(txBps);
    TRAFFIC_CHART.data.datasets[1].data.push(rxBps);
    TRAFFIC_HISTORY.push({
        tx: txBps ?? 0,
        rx: rxBps ?? 0,
    });

    const maxPoints = 24;
    if (TRAFFIC_CHART.data.labels.length > maxPoints) {
        TRAFFIC_CHART.data.labels.shift();
        TRAFFIC_CHART.data.datasets[0].data.shift();
        TRAFFIC_CHART.data.datasets[1].data.shift();
        TRAFFIC_HISTORY.shift();
    }

    const peakBps = TRAFFIC_HISTORY.reduce((max, point) => {
        return Math.max(max, Number(point.tx || 0), Number(point.rx || 0));
    }, 0);
    TRAFFIC_CHART.options.scales.y.suggestedMax = calculateTrafficCeiling(peakBps);
    updateTrafficFootStats();
    TRAFFIC_CHART.update();
}

function enrichTrafficSnapshot(data) {
    const snapshot = { ...(data || {}) };
    snapshot.status = (snapshot.status || '').toString().toLowerCase();
    snapshot.router_tx = Number(snapshot.tx || 0);
    snapshot.router_rx = Number(snapshot.rx || 0);
    snapshot.tx = snapshot.router_tx;
    snapshot.rx = snapshot.router_rx;
    snapshot.session_bytes_in = Number(snapshot.session_bytes_in || 0);
    snapshot.session_bytes_out = Number(snapshot.session_bytes_out || 0);
    snapshot.session_total_bytes = Number(snapshot.session_total_bytes || (snapshot.session_bytes_in + snapshot.session_bytes_out));
    snapshot.user_total_bytes = Number(snapshot.user_total_bytes || 0);
    snapshot.live_mode = 'offline';

    if (snapshot.status !== 'online') {
        TRAFFIC_LAST_SNAPSHOT = null;
        return snapshot;
    }

    const nowMs = Date.now();
    const hasRouterRate = snapshot.router_tx > 0 || snapshot.router_rx > 0;
    let hasDeltaSample = false;
    let deltaTx = 0;
    let deltaRx = 0;

    if (TRAFFIC_LAST_SNAPSHOT && nowMs > Number(TRAFFIC_LAST_SNAPSHOT.at || 0)) {
        const elapsedSeconds = (nowMs - Number(TRAFFIC_LAST_SNAPSHOT.at || 0)) / 1000;
        const deltaIn = snapshot.session_bytes_in - Number(TRAFFIC_LAST_SNAPSHOT.in || 0);
        const deltaOut = snapshot.session_bytes_out - Number(TRAFFIC_LAST_SNAPSHOT.out || 0);

        if (elapsedSeconds > 0 && deltaIn >= 0 && deltaOut >= 0) {
            deltaRx = Math.round((deltaIn * 8) / elapsedSeconds);
            deltaTx = Math.round((deltaOut * 8) / elapsedSeconds);
            hasDeltaSample = true;
        }
    }

    TRAFFIC_LAST_SNAPSHOT = {
        at: nowMs,
        in: snapshot.session_bytes_in,
        out: snapshot.session_bytes_out,
    };

    if (hasDeltaSample && (deltaTx > 0 || deltaRx > 0)) {
        snapshot.tx = deltaTx;
        snapshot.rx = deltaRx;
        snapshot.live_available = true;
        snapshot.live_mode = 'session_delta';
        return snapshot;
    }

    if (hasRouterRate) {
        snapshot.tx = snapshot.router_tx;
        snapshot.rx = snapshot.router_rx;
        snapshot.live_available = true;
        snapshot.live_mode = hasDeltaSample ? 'router_assist' : 'router_interface';
        return snapshot;
    }

    if (hasDeltaSample) {
        snapshot.tx = 0;
        snapshot.rx = 0;
        snapshot.live_available = true;
        snapshot.live_mode = 'session_idle';
        return snapshot;
    }

    snapshot.tx = 0;
    snapshot.rx = 0;
    snapshot.live_available = false;
    snapshot.live_mode = 'session_warmup';

    return snapshot;
}

function appendTrafficRow(data) {
    const content = $('#trafficModalContent tbody');
    const nowLabel = new Date().toLocaleString();
    const mode = (data.live_mode || '').toLowerCase();
    const meta = getTrafficModeMeta(mode);
    const txLabel = meta.warmup ? 'Sampling...' : formatBitsPerSecond(data.tx);
    const rxLabel = meta.warmup ? 'Sampling...' : formatBitsPerSecond(data.rx);
    content.prepend(`
        <tr>
            <td>${escapeHtml(nowLabel)}</td>
            <td>${escapeHtml(data.username || TRAFFIC_ACTIVE_TARGET.username || TRAFFIC_ACTIVE_TARGET.mac || '-')}</td>
            <td>${escapeHtml(data.interface ?? '-')}</td>
            <td>${escapeHtml(meta.tableLabel)}</td>
            <td title="${escapeHtml(`${Number(data.tx || 0)} bps`)}">${escapeHtml(txLabel)}</td>
            <td title="${escapeHtml(`${Number(data.rx || 0)} bps`)}">${escapeHtml(rxLabel)}</td>
        </tr>
    `);

    const rows = content.find('tr');
    if (rows.length > 25) {
        rows.slice(25).remove();
    }
}

function updateTrafficSummary(data) {
    $('#trafficStatusText').text(cap(data.status || (data.live_available ? 'online' : 'offline')));
    $('#trafficSessionUsage').text(formatBytes(data.session_total_bytes || 0));
    $('#trafficTotalUsage').text(formatBytes(data.user_total_bytes || 0));
    $('#trafficIdentity').text(`${data.ip || '-'} | ${data.mac || '-'}`);
    const badge = $('#trafficSourceBadge');
    const mode = (data.live_mode || '').toLowerCase();
    const meta = getTrafficModeMeta(mode);
    let hint = meta.hint;
    let badgeState = meta.badgeState;
    let badgeLabel = meta.badgeLabel;
    let txLabel = formatBitsPerSecond(data.tx);
    let rxLabel = formatBitsPerSecond(data.rx);

    if (meta.warmup) {
        txLabel = 'Sampling...';
        rxLabel = 'Sampling...';
    }

    badge.attr('data-state', badgeState);
    badge.find('.label').text(badgeLabel);
    $('#trafficChartHint').text(hint);
    $('#trafficCurrentTx').text(txLabel);
    $('#trafficCurrentRx').text(rxLabel);
}

function fetchTrafficSnapshot({ username, mac, connectionId = null, silent = false }) {
    return $.ajax({
        url: ROUTES.monitorTraffic,
        method: 'POST',
        data: {
            _token: CSRF_TOKEN,
            username,
            mac,
            connection_id: connectionId || '',
        },
    }).done(function(data) {
        if (data?.historical) {
            renderStoredTrafficHistory(data);
            return;
        }

        const snapshot = enrichTrafficSnapshot(data);
        if (!silent) {
            $('#trafficModalContent tbody').empty();
        }
        updateTrafficSummary(snapshot);
        appendTrafficRow(snapshot);
        pushTrafficPoint(snapshot);
    });
}

function startTrafficPolling() {
    stopTrafficPolling();
    TRAFFIC_POLL_TIMER = setInterval(() => {
        const modalEl = document.getElementById('trafficModal');
        if (!modalEl || !modalEl.classList.contains('show')) {
            stopTrafficPolling();
            return;
        }
        if (TRAFFIC_ACTIVE_TARGET.connectionId) {
            stopTrafficPolling();
            return;
        }
        if (!TRAFFIC_ACTIVE_TARGET.username && !TRAFFIC_ACTIVE_TARGET.mac) {
            return;
        }

        fetchTrafficSnapshot({
            username: TRAFFIC_ACTIVE_TARGET.username,
            mac: TRAFFIC_ACTIVE_TARGET.mac,
            connectionId: TRAFFIC_ACTIVE_TARGET.connectionId,
            silent: true,
        }).fail(() => {
            stopTrafficPolling();
            $('#trafficChartHint').text('Live polling stopped due to request failure.');
        });
    }, 1000);
}

function viewTrafficModal(username, mac = '', connectionId = null) {
    if(!username && !mac && !connectionId) return toast('No user selected', 'Pick a user/session and try again.', 'danger');

    const resolvedConnectionId = Number(connectionId || 0) || null;
    const isHistorical = !!resolvedConnectionId;

    TRAFFIC_ACTIVE_TARGET = {
        username: (username || '').trim(),
        mac: (mac || '').trim(),
        connectionId: resolvedConnectionId,
    };

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('trafficModal'));
    const loader = $('#trafficModalLoader');
    const content = $('#trafficModalContent tbody');

    loader.show();
    $('#trafficModalContent').hide();
    content.empty();
    $('#trafficStatusText').text('Loading...');
    $('#trafficSessionUsage').text('0 B');
    $('#trafficTotalUsage').text('0 B');
    $('#trafficIdentity').text('-');
    $('#trafficSourceBadge').attr('data-state', isHistorical ? 'offline' : 'fallback').find('.label').text(isHistorical ? 'Loading history' : 'Calibrating');
    $('#trafficCurrentTx').text(isHistorical ? '0 bps' : 'Sampling...');
    $('#trafficCurrentRx').text(isHistorical ? '0 bps' : 'Sampling...');
    $('#trafficCurrentAvg').text('0 bps');
    $('#trafficCurrentPeak').text('0 bps');
    $('#trafficChartHint').text(isHistorical ? 'Loading saved session history...' : 'Requesting throughput...');
    setTrafficPresentation(isHistorical);
    resetTrafficChart();

    modal.show();

    fetchTrafficSnapshot({
        username: TRAFFIC_ACTIVE_TARGET.username,
        mac: TRAFFIC_ACTIVE_TARGET.mac,
        connectionId: TRAFFIC_ACTIVE_TARGET.connectionId,
        silent: false,
    }).done(function(data) {
        loader.hide();
        $('#trafficModalContent').show();

        if (data?.historical) {
            stopTrafficPolling();
            return;
        }

        const status = (data?.status || '').toString().toLowerCase();
        if (data.live_available || status === 'online') {
            startTrafficPolling();
        } else {
            stopTrafficPolling();
        }
    }).fail(function(xhr) {
        loader.hide();
        const msg = xhr?.responseJSON?.error || xhr?.responseJSON?.message || `Failed to load traffic (HTTP ${xhr.status || '??'}).`;
        $('#trafficStatusText').text('Unavailable');
        $('#trafficSessionUsage').text('0 B');
        $('#trafficTotalUsage').text('0 B');
        $('#trafficIdentity').text('-');
        $('#trafficSourceBadge').attr('data-state', 'offline').find('.label').text('Unavailable');
        $('#trafficCurrentTx').text('-');
        $('#trafficCurrentRx').text('-');
        $('#trafficCurrentAvg').text('-');
        $('#trafficCurrentPeak').text('-');
        $('#trafficChartHint').text('No data available.');
        content.html(`
            <tr>
                <td class="text-danger">${escapeHtml(msg)}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        `);
        $('#trafficModalContent').show();
    });
}

function viewUserDetails(username) {
    if (!username) {
        toast('Missing username', 'Could not open details modal.', 'danger');
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('userDetailsModal'));
    modal.show();
    CURRENT_USER_USERNAME = username;

    $('#userDetailsLoader').show();
    $('#userDetailsContent').hide();
    $('#invoiceGeneratedBox').hide().empty();
    $('#packageExtendResult').hide().empty();
    $('#packageExtendMinutes').val('');

    $.get(ROUTES.userDetails, { username })
        .done(function(payload) {
            CURRENT_USER_DETAILS = payload;
            renderUserDetails(payload);
            $('#userDetailsLoader').hide();
            $('#userDetailsContent').show();
            startUserDetailsAutoRefresh();
        })
        .fail(function(xhr) {
            $('#userDetailsLoader').hide();
            const msg = xhr?.responseJSON?.message
                || xhr?.responseJSON?.error
                || `Could not load user details (HTTP ${xhr.status || '??'}).`;
            toast('Details failed', msg, 'danger');
        });
}

function stopUserDetailsAutoRefresh() {
    if (USER_DETAILS_REFRESH_TIMER) {
        clearInterval(USER_DETAILS_REFRESH_TIMER);
        USER_DETAILS_REFRESH_TIMER = null;
    }
}

function startUserDetailsAutoRefresh() {
    stopUserDetailsAutoRefresh();
    USER_DETAILS_REFRESH_TIMER = setInterval(() => {
        const modalEl = document.getElementById('userDetailsModal');
        if (!modalEl || !modalEl.classList.contains('show')) {
            stopUserDetailsAutoRefresh();
            return;
        }

        if (!CURRENT_USER_USERNAME) {
            return;
        }

        $.get(ROUTES.userDetails, { username: CURRENT_USER_USERNAME })
            .done(function(payload) {
                CURRENT_USER_DETAILS = payload;
                renderUserDetails(payload);
            });
    }, 15000);
}

function normalizePlanCategory(value) {
    const category = (value || '').toString().trim().toLowerCase();
    return category === 'metered' ? 'metered' : 'hotspot';
}

function knownPlans() {
    const plans = CURRENT_USER_DETAILS?.plans || [];
    return Array.isArray(plans) ? plans : [];
}

function findPlanById(planId) {
    const selected = String(planId ?? '').trim();
    if (selected === '') return null;
    return knownPlans().find((plan) => String(plan?.id ?? '') === selected) || null;
}

function updateBillingUiForPlan(planId = null, preferPlanPrice = false) {
    const selectedId = String(planId ?? $('#billingPlanSelect').val() ?? '').trim();
    const plan = findPlanById(selectedId);
    const fallbackMode = normalizePlanCategory(CURRENT_USER_DETAILS?.billing?.billing_mode || 'metered');
    const category = normalizePlanCategory(plan?.category || fallbackMode);
    const isHotspot = category === 'hotspot';
    const planPrice = Number(plan?.price ?? 0);
    const currentRate = Number($('#billingRateInput').val() || 0);

    $('#billingRateLabel').text(isHotspot ? 'Package Price' : 'Rate per MB');
    $('#billingRateHint').text(
        isHotspot
            ? 'Hotspot billing uses a flat package amount for the selected duration/data package.'
            : 'Metered billing uses usage MB multiplied by this rate.'
    );
    $('#billingRateInput').attr('data-billing-mode', isHotspot ? 'hotspot' : 'metered');

    if (isHotspot && Number.isFinite(planPrice) && planPrice > 0) {
        if (preferPlanPrice || !Number.isFinite(currentRate) || currentRate <= 0) {
            $('#billingRateInput').val(planPrice.toFixed(2));
        }
    }
}

function currentBillingMode() {
    return ($('#billingRateInput').attr('data-billing-mode') || 'metered').toLowerCase() === 'hotspot'
        ? 'hotspot'
        : 'metered';
}

function resolveDetailMode(payload = {}, packageInfo = {}) {
    const direct = (payload?.detail_mode || '').toString().toLowerCase();
    if (direct === 'metered' || direct === 'hotspot') {
        return direct;
    }

    const accountType = (payload?.user?.account_type || '').toString().toLowerCase();
    if (accountType === 'metered_static') {
        return 'metered';
    }

    return normalizePlanCategory(packageInfo?.package_category || 'hotspot') === 'metered'
        ? 'metered'
        : 'hotspot';
}

function applyDetailMode(mode = 'hotspot') {
    CURRENT_DETAIL_MODE = mode === 'metered' ? 'metered' : 'hotspot';
    const isMetered = CURRENT_DETAIL_MODE === 'metered';

    $('#meteredBillingPanel').toggle(isMetered);
    $('#hotspotHistoryPanel').toggle(!isMetered);
    $('#packageExtendPanel').toggle(!isMetered);
    $('#userBillingTabLabel').text(isMetered ? 'Billing' : 'History');

    if (!isMetered) {
        $('#invoiceGeneratedBox').hide().empty();
    }
}

function renderHotspotSubscriptions(subscriptions = []) {
    const $tbody = $('#hotspotSubscriptionsTbody');
    $tbody.empty();

    if (!Array.isArray(subscriptions) || subscriptions.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-muted text-center">No hotspot subscriptions yet.</td></tr>');
        return;
    }

    subscriptions.forEach((subscription) => {
        const rawStatus = (subscription?.status || 'active').toString().toLowerCase();
        const badgeClass = rawStatus === 'active'
            ? 'bg-success-subtle text-success border border-success-subtle'
            : (rawStatus === 'expired'
                ? 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                : 'bg-warning-subtle text-warning border border-warning-subtle');
        const packageId = Number(subscription?.package_id || 0);
        const forceOnly = !(Boolean(subscription?.eligible_extension));
        const isExpired = Boolean(subscription?.is_expired);
        const isActive = Boolean(subscription?.is_active) || (rawStatus === 'active' && !isExpired);
        const source = (subscription?.source || '').toString().toLowerCase();
        const subscriptionId = source === 'subscription' ? Number(subscription?.id || 0) : Number(subscription?.subscription_id || 0);
        const connectionId = Number(subscription?.connection_id || (source === 'connection' ? subscription?.id || 0 : 0));

        const viewUsageBtn = `
            <a href="javascript:void(0);" class="icon-action action-hotspot-sub-view-usage" title="View usage"
               data-username="${escapeHtml(CURRENT_USER_USERNAME || '')}">
                <i class="bi bi-eye"></i>
            </a>
        `;

        const modifyBtn = packageId > 0
            ? `
                <a href="javascript:void(0);" class="icon-action action-hotspot-sub-modify" title="Modify / Extend package"
                   data-package-id="${packageId}"
                   data-force-only="${forceOnly ? '1' : '0'}">
                    <i class="bi bi-pen"></i>
                </a>
            `
            : `
                <span class="icon-action text-muted" title="No package to modify" style="pointer-events:none;opacity:.55;">
                    <i class="bi bi-pen"></i>
                </span>
            `;

        const expireBtn = isActive
            ? `
                <a href="javascript:void(0);" class="icon-action action-hotspot-sub-expire text-danger" title="Expire subscription now"
                   data-subscription-id="${subscriptionId > 0 ? subscriptionId : ''}"
                   data-connection-id="${connectionId > 0 ? connectionId : ''}">
                    <i class="bi bi-slash-circle"></i>
                </a>
            `
            : `
                <span class="icon-action text-muted" title="Subscription already inactive" style="pointer-events:none;opacity:.55;">
                    <i class="bi bi-slash-circle"></i>
                </span>
            `;

        $tbody.append(`
            <tr>
                <td>${escapeHtml(subscription?.package_name || '-')}</td>
                <td>${escapeHtml(formatDate(subscription?.starts_at || null))}</td>
                <td>${escapeHtml(formatDate(subscription?.expires_at || null))}</td>
                <td>${escapeHtml(subscription?.duration_label || '-')}</td>
                <td><span class="badge rounded-pill ${badgeClass}">${escapeHtml(cap(rawStatus || 'unknown'))}</span></td>
                <td>${escapeHtml(subscription?.time_remaining || '-')}</td>
                <td class="text-nowrap"><span class="ud-table-actions">${viewUsageBtn}${modifyBtn}${expireBtn}</span></td>
            </tr>
        `);
    });
}

function renderHotspotTransactions(transactions = [], currency = 'KES') {
    const $tbody = $('#hotspotTransactionsTbody');
    $tbody.empty();

    if (!Array.isArray(transactions) || transactions.length === 0) {
        $tbody.html('<tr><td colspan="6" class="text-muted text-center">No hotspot payment attempts yet.</td></tr>');
        return;
    }

    transactions.forEach((transaction) => {
        const rawStatus = (transaction?.status || 'pending').toString().toLowerCase();
        const badgeClass = rawStatus === 'completed'
            ? 'bg-success-subtle text-success border border-success-subtle'
            : (['failed', 'cancelled', 'timeout', 'expired'].includes(rawStatus)
                ? 'bg-danger-subtle text-danger border border-danger-subtle'
                : 'bg-warning-subtle text-warning border border-warning-subtle');

        const amount = Number(transaction?.amount || 0);
        const curr = (transaction?.currency || currency || 'KES').toUpperCase();
        const attemptedAt = transaction?.attempted_at || transaction?.created_at || null;

        $tbody.append(`
            <tr>
                <td>${escapeHtml(formatDate(attemptedAt))}</td>
                <td>${escapeHtml(transaction?.reference || '-')}</td>
                <td>${escapeHtml(transaction?.package_name || '-')}</td>
                <td>${escapeHtml(formatCurrency(amount, curr))}</td>
                <td><span class="badge rounded-pill ${badgeClass}">${escapeHtml(cap(rawStatus))}</span></td>
                <td>${escapeHtml(transaction?.transaction_code || transaction?.receipt || '-')}</td>
            </tr>
        `);
    });
}

function renderHotspotSessions(sessions = []) {
    const $tbody = $('#userSessionHistoryTbody');
    $tbody.empty();

    if (!Array.isArray(sessions) || sessions.length === 0) {
        $tbody.html('<tr><td colspan="8" class="text-muted text-center">No hotspot session history yet.</td></tr>');
        return;
    }

    sessions.forEach((session) => {
        const rawStatus = (session?.status || 'active').toString().toLowerCase();
        const badgeClass = rawStatus === 'active'
            ? 'bg-success-subtle text-success border border-success-subtle'
            : (['expired', 'ended', 'terminated'].includes(rawStatus)
                ? 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                : 'bg-warning-subtle text-warning border border-warning-subtle');
        const endedAt = session?.ended_at || (session?.is_active ? null : session?.expires_at) || null;
        const connectionId = Number(session?.connection_id || 0);
        const sessionMac = (session?.mac_address || '').toString();
        const viewAction = connectionId > 0
            ? `
                <button type="button"
                        class="btn btn-sm btn-outline-primary action-view-session-traffic"
                        data-connection-id="${connectionId}"
                        data-username="${escapeHtml((CURRENT_USER_USERNAME || '').toString())}"
                        data-mac="${escapeHtml(sessionMac)}"
                        title="View stored session graph">
                    <i class="bi bi-graph-up"></i>
                </button>
            `
            : '<span class="text-muted">-</span>';

        $tbody.append(`
            <tr>
                <td>${escapeHtml(formatDate(session?.starts_at || null))}</td>
                <td>${escapeHtml(session?.is_active ? 'Live' : formatDate(endedAt))}</td>
                <td>${escapeHtml(session?.package_name || '-')}</td>
                <td>${escapeHtml(session?.ip_address || '-')}</td>
                <td>${escapeHtml(session?.online_duration || '-')}</td>
                <td>${escapeHtml(session?.usage_total || formatBytes(session?.usage_total_bytes || 0))}</td>
                <td><span class="badge rounded-pill ${badgeClass}">${escapeHtml(cap(rawStatus))}</span></td>
                <td class="text-center">${viewAction}</td>
            </tr>
        `);
    });
}

function renderHotspotHistory(payload = {}, currency = 'KES') {
    const hotspot = payload?.hotspot || {};
    renderHotspotSubscriptions(hotspot?.subscriptions || []);
    renderHotspotSessions(hotspot?.sessions || []);
    renderHotspotTransactions(hotspot?.transactions || [], currency);
}

function renderUserDetails(payload) {
    const user = payload?.user || {};
    const usage = payload?.usage || {};
    const billing = payload?.billing || {};
    const packageInfo = payload?.package_info || {};
    const general = payload?.general || {};
    const customer = general?.customer || {};
    const balances = general?.balances || {};
    const recentPayments = general?.recent_payments || [];
    const plans = payload?.plans || [];
    const activeSession = payload?.active_session || {};
    const hotspot = payload?.hotspot || {};
    const detailMode = resolveDetailMode(payload, packageInfo);
    const extensionMeta = hotspot?.extension || {};
    const extensionOptions = Array.isArray(hotspot?.extension_options) ? hotspot.extension_options : [];
    const forceExtensionOptions = Array.isArray(hotspot?.force_extension_options) ? hotspot.force_extension_options : [];
    const hotspotSummary = hotspot?.summary || {};

    $('#udUsername').text(user.username || '-');
    $('#udProfile').text(user.profile || '-');
    $('#udMac').text(user['mac-address'] || '-');
    $('#udStatus').text(cap(user.status || customer.status || 'unknown'));
    $('#udUptime').text(packageInfo.uptime || user.uptime || '-');
    $('#udLimitUptime').text(packageInfo.limit_uptime || user['limit-uptime'] || '-');
    $('#udLastSeen').text(hotspotSummary?.last_seen || user['last-seen'] || '-');
    $('#udTotalOnline').text(hotspotSummary?.total_online || '-');
    $('#udSessionAddress').text(activeSession.address || activeSession['host-ip'] || '-');
    $('#udSessionUptime').text(activeSession.uptime || packageInfo.uptime || '-');

    $('#udUsageTotal').text(usage.total_human || formatBytes(usage.total_bytes || 0));
    $('#udUsageRouter').text(formatBytes(usage.router_total_bytes || 0));
    $('#udUsageDb').text(formatBytes(usage.db_total_bytes || 0));

    const username = user.username || customer.username || '';
    const totalBytes = Number(usage.total_bytes || 0);
    const currency = (billing.currency || 'KES').toUpperCase();
    const rate = Number(billing.rate_per_mb || billing.rate_per_gb || 0);
    const selectedPlanId = packageInfo.package_id || customer.package_id || billing.package_id || '';

    $('#billingUsername').val(username);
    $('#billingUsageBytes').val(totalBytes);
    $('#billingUsageLabel').text(`${formatBytes(totalBytes)} (${Number(totalBytes / (1024 ** 2)).toFixed(2)} MB)`);
    $('#billingCurrencyInput').val(currency);
    $('#billingRateInput').val(Number.isFinite(rate) ? rate : 0);
    $('#billingNotifyCustomer').prop('checked', Boolean(billing.notify_customer));

    const issuedAt = toInputDate(new Date());
    const dueAt = toInputDate(new Date(Date.now() + (7 * 24 * 60 * 60 * 1000)));
    $('#billingIssuedAt').val(issuedAt);
    $('#billingDueDate').val(dueAt);
    $('#billingTaxPercent').val(0);
    $('#billingPenaltyPercent').val(0);
    $('#billingNotes').val('');

    $('#generalName').val(customer.name || user.username || '');
    $('#generalPhone').val(customer.phone || '');
    $('#generalEmail').val(customer.email || '');
    $('#generalAddress').val(customer.address || '');
    $('#generalStatus').val(customer.status || 'active');

    const extensionDefaultPlanId = extensionMeta?.default_package_id || packageInfo.package_id || selectedPlanId || '';
    $('#packageExtendForce').prop('checked', false);
    populatePlans(plans, selectedPlanId, extensionOptions, forceExtensionOptions, extensionDefaultPlanId, detailMode);
    if (detailMode === 'metered') {
        updateBillingUiForPlan(selectedPlanId, rate <= 0);
    }
    renderPackageDetails(packageInfo, currency);
    applyDetailMode(detailMode);
    renderHotspotHistory(payload, currency);

    $('#udBalanceBilled').text(formatCurrency(balances.total_billed || 0, currency));
    $('#udBalancePaid').text(formatCurrency(balances.total_paid || 0, currency));
    $('#udBalanceDue').text(formatCurrency(balances.total_due || 0, currency));
    $('#udOpenInvoices').text(Number(balances.open_invoices || 0));

    const hotspotRecentPayments = Array.isArray(hotspot?.recent_payments) ? hotspot.recent_payments : [];
    populatePayments(
        detailMode === 'hotspot' && hotspotRecentPayments.length > 0 ? hotspotRecentPayments : recentPayments,
        currency
    );

    if (detailMode === 'metered') {
        populateInvoices(payload?.invoices || [], currency);
        $('#packageExtendHelp').text('Package extension is available for hotspot users only.');
    } else {
        $('#userInvoicesTbody').html('<tr><td colspan="7" class="text-muted text-center">Invoicing is available for metered users only.</td></tr>');
        $('#packageExtendHelp').text(
            extensionOptions.length > 0
                ? 'Eligible packages are preloaded. Enable Force to extend non-eligible packages.'
                : (forceExtensionOptions.length > 0
                    ? 'No eligible package right now. Enable Force to extend previous package.'
                    : 'No hotspot package history available to extend.')
        );
    }
}

function renderPackageDetails(packageInfo = {}, currency = 'KES') {
    const packageName = packageInfo.package_name || 'No package';
    const category = cap((packageInfo.package_category || '-').toString());
    const price = Number(packageInfo.package_price || 0);
    const duration = packageInfo.duration_label || '-';
    const subscribedAt = packageInfo.subscribed_at ? formatDate(packageInfo.subscribed_at) : '-';
    const expiresAt = packageInfo.expires_at ? formatDate(packageInfo.expires_at) : '-';
    const remaining = packageInfo.time_remaining || '-';
    const uptime = packageInfo.uptime || '-';
    const status = cap(packageInfo.connection_status || 'offline');

    $('#udPkgName').text(packageName);
    $('#udPkgCategory').text(category);
    $('#udPkgPrice').text(price > 0 ? formatCurrency(price, currency) : '-');
    $('#udPkgDuration').text(duration);
    $('#udPkgRemaining').text(remaining);
    $('#udPkgSubscribedAt').text(subscribedAt);
    $('#udPkgExpiresAt').text(expiresAt);
    $('#udPkgUptime').text(uptime);
    $('#udPkgConnStatus').text(status);

    const subscriptionStatus = (packageInfo.subscription_status || '').toString().toLowerCase();
    const remainingSecondsRaw = packageInfo?.time_remaining_seconds;
    const remainingSeconds = remainingSecondsRaw === null || remainingSecondsRaw === undefined || remainingSecondsRaw === ''
        ? null
        : Number(remainingSecondsRaw);
    const canExpire = subscriptionStatus === 'active'
        && (remainingSeconds === null || (Number.isFinite(remainingSeconds) && remainingSeconds > 0));
    $('#expirePackageBtn').prop('disabled', !canExpire);
}

function toInputDate(value) {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function renderExtensionPlanOptions(forceMode = false, preferredId = '') {
    const detailMode = CURRENT_DETAIL_MODE === 'metered' ? 'metered' : 'hotspot';
    const source = forceMode
        ? (Array.isArray(CURRENT_FORCE_EXTENSION_OPTIONS) ? CURRENT_FORCE_EXTENSION_OPTIONS : [])
        : (Array.isArray(CURRENT_EXTENSION_OPTIONS) ? CURRENT_EXTENSION_OPTIONS : []);

    const extensionOpts = ['<option value="">Select package</option>'];
    source.forEach((option) => {
        const id = String(option?.id ?? '');
        if (id === '') return;

        const categoryRaw = normalizePlanCategory(option?.category || 'hotspot');
        if (detailMode === 'hotspot' && categoryRaw === 'metered') {
            return;
        }

        const amount = Number(option?.price || 0).toFixed(2);
        const name = option?.name || 'Package';
        const durationLabel = (option?.duration_label || option?.time_label || '').toString().trim();
        const forceOnly = Boolean(option?.force_only);
        const label = durationLabel !== '' && durationLabel !== '-'
            ? `${name} - ${durationLabel} - KES ${amount}${forceOnly ? ' (force)' : ''}`
            : `${name} - KES ${amount}${forceOnly ? ' (force)' : ''}`;

        extensionOpts.push(
            `<option value="${escapeHtml(id)}" data-category="${escapeHtml(categoryRaw)}" data-price="${escapeHtml(amount)}" data-force-only="${forceOnly ? '1' : '0'}">${escapeHtml(label)}</option>`
        );
    });

    if (detailMode === 'hotspot' && extensionOpts.length === 1) {
        extensionOpts.push('<option value="">No package available</option>');
    }

    $('#packageExtendPlanSelect').html(extensionOpts.join(''));

    const preferred = String(preferredId || '').trim();
    if (preferred !== '') {
        $('#packageExtendPlanSelect').val(preferred);
    }

    if (!$('#packageExtendPlanSelect').val()) {
        const firstEligible = $('#packageExtendPlanSelect option[value!=""]').first().val() || '';
        if (firstEligible !== '') {
            $('#packageExtendPlanSelect').val(firstEligible);
        }
    }
}

function populatePlans(plans, selectedPlanId = '', extensionOptions = [], forceExtensionOptions = [], extensionSelectedId = '', detailMode = CURRENT_DETAIL_MODE) {
    const planList = Array.isArray(plans) ? plans : [];
    const opts = ['<option value="">No plan</option>'];

    planList.forEach((plan) => {
        const id = String(plan?.id ?? '');
        if (id === '') return;

        const amount = Number(plan?.price || 0).toFixed(2);
        const categoryRaw = normalizePlanCategory(plan?.category || 'hotspot');
        const category = categoryRaw.toUpperCase();
        const unit = categoryRaw === 'metered' ? '/MB' : '/package';
        const title = `${plan?.name || 'Plan'} [${category}] - KES ${amount}${unit}`;
        opts.push(`<option value="${escapeHtml(id)}" data-category="${escapeHtml(categoryRaw)}" data-price="${escapeHtml(amount)}">${escapeHtml(title)}</option>`);
    });

    $('#generalPlanSelect').html(opts.join(''));
    $('#billingPlanSelect').html(opts.join(''));
    CURRENT_EXTENSION_OPTIONS = Array.isArray(extensionOptions) ? extensionOptions : [];
    const forceOpts = Array.isArray(forceExtensionOptions) ? forceExtensionOptions : [];
    CURRENT_FORCE_EXTENSION_OPTIONS = forceOpts.length > 0
        ? forceOpts
        : (Array.isArray(extensionOptions) ? extensionOptions : []);

    const selected = String(selectedPlanId || '');
    if (selected !== '') {
        $('#generalPlanSelect').val(selected);
        $('#billingPlanSelect').val(selected);
    }

    const extensionSelected = String(extensionSelectedId || selectedPlanId || '');
    renderExtensionPlanOptions(false, extensionSelected);
}

function syncExtensionOptionsFromForce() {
    const forceMode = $('#packageExtendForce').is(':checked');
    const currentSelected = $('#packageExtendPlanSelect').val() || '';
    renderExtensionPlanOptions(forceMode, currentSelected);

    if (CURRENT_DETAIL_MODE === 'hotspot') {
        if (forceMode) {
            $('#packageExtendHelp').text('Force mode enabled. You can extend using non-eligible packages.');
        } else {
            const hasEligible = Array.isArray(CURRENT_EXTENSION_OPTIONS) && CURRENT_EXTENSION_OPTIONS.length > 0;
            $('#packageExtendHelp').text(
                hasEligible
                    ? 'Eligible packages are preloaded. Enable Force to extend non-eligible packages.'
                    : 'No eligible package right now. Enable Force to extend previous package.'
            );
        }
    }
}

function selectExtensionPackage(packageId, forceMode = false) {
    const targetId = String(packageId || '').trim();
    if (targetId === '') return;

    $('#packageExtendForce').prop('checked', !!forceMode);
    syncExtensionOptionsFromForce();
    $('#packageExtendPlanSelect').val(targetId);
    if (!$('#packageExtendPlanSelect').val()) {
        $('#packageExtendForce').prop('checked', true);
        syncExtensionOptionsFromForce();
        $('#packageExtendPlanSelect').val(targetId);
    }
}

function populatePayments(payments, currency = 'KES') {
    const $tbody = $('#userPaymentsTbody');
    $tbody.empty();

    if (!Array.isArray(payments) || payments.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-muted text-center">No payments yet.</td></tr>');
        return;
    }

    payments.forEach((payment) => {
        const amount = Number(payment.amount || 0);
        const curr = (payment.currency || currency || 'KES').toUpperCase();
        $tbody.append(`
            <tr>
                <td>${escapeHtml(formatDate(payment.paid_at || payment.created_at || null))}</td>
                <td>${escapeHtml(formatCurrency(amount, curr))}</td>
                <td>${escapeHtml((payment.method || 'mpesa').toUpperCase())}</td>
                <td>${escapeHtml(payment.transaction_code || payment.reference || '-')}</td>
                <td>${escapeHtml(cap(payment.status || 'completed'))}</td>
            </tr>
        `);
    });
}

function populateInvoices(invoices, currency = 'KES') {
    const $tbody = $('#userInvoicesTbody');
    $tbody.empty();

    if (!Array.isArray(invoices) || invoices.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-muted text-center">No invoices yet.</td></tr>');
        return;
    }

    invoices.forEach((invoice) => {
        appendInvoiceRow(invoice, currency, false);
    });
}

function appendInvoiceRow(invoice, currency = 'KES', prepend = true) {
    const $tbody = $('#userInvoicesTbody');
    const total = Number(invoice.total_amount || invoice.amount || 0);
    const balance = Number(invoice.balance_amount ?? total);
    const status = invoice.invoice_status || invoice.status || 'unpaid';
    const dueDate = invoice.due_date || null;
    const invoiceId = invoice.id || 0;
    const viewQuery = encodeURIComponent(invoice.invoice_number || invoiceId);
    const viewUrl = `${ROUTES.invoiceView}?invoice_q=${viewQuery}`;
    const printUrl = `${ROUTES.invoicePrint}/${invoiceId}/print`;

    const html = `
        <tr>
            <td>${escapeHtml(invoice.invoice_number || '-')}</td>
            <td>${escapeHtml(formatCurrency(total, currency))}</td>
            <td>${escapeHtml(formatCurrency(balance, currency))}</td>
            <td>${escapeHtml(cap(status))}</td>
            <td>${escapeHtml(dueDate ? formatDate(dueDate) : '-')}</td>
            <td>${escapeHtml(formatDate(invoice.created_at || null))}</td>
            <td>
                <a href="${escapeHtml(viewUrl)}" class="btn btn-link btn-sm p-0 me-2" target="_blank">View</a>
                <a href="${escapeHtml(printUrl)}" class="btn btn-link btn-sm p-0" target="_blank">Print</a>
            </td>
        </tr>
    `;

    if ($tbody.find('td[colspan="7"]').length) {
        $tbody.empty();
    }

    if (prepend) {
        $tbody.prepend(html);
    } else {
        $tbody.append(html);
    }
}

function editUser(username, defaults = {}) {
    if (!username) return;

    const current = CURRENT_USER_DETAILS?.user || {};
    const profile = window.prompt(
        'Set profile:',
        defaults.profile ?? current.profile ?? ''
    );
    if (profile === null) return;

    const limitUptime = window.prompt(
        'Set limit uptime (e.g. 1h, 30m):',
        defaults.limitUptime ?? current['limit-uptime'] ?? ''
    );
    if (limitUptime === null) return;

    const comment = window.prompt(
        'Set comment (optional):',
        defaults.comment ?? current.comment ?? ''
    );
    if (comment === null) return;

    postJson(ROUTES.updateUser, {
        username,
        profile,
        limit_uptime: limitUptime,
        comment,
    }).done(function(resp) {
        toast('User updated', resp.message || 'Changes saved.', 'success');
        refreshSection('users', true);
        refreshSection('sessions', true);
    }).fail(function(xhr) {
        toast('Update failed', xhr?.responseJSON?.message || 'Could not update user.', 'danger');
    });
}

function disableUser(username, mac = '') {
    if (!username && !mac) return;
    if (!window.confirm(`Disable user ${username || mac}?`)) return;

    postJson(ROUTES.disableUser, { username, mac })
        .done(function(resp) {
            toast('User disabled', resp.message || 'Done.', 'success');
            refreshSection('users', true);
            refreshSection('sessions', true);
        })
        .fail(function(xhr) {
            toast('Disable failed', xhr?.responseJSON?.message || 'Could not disable user.', 'danger');
        });
}

function enableUser(username, mac = '') {
    if (!username && !mac) return;
    if (!window.confirm(`Enable user ${username || mac}?`)) return;

    postJson(ROUTES.enableUser, { username, mac })
        .done(function(resp) {
            toast('User enabled', resp.message || 'Done.', 'success');
            refreshSection('users', true);
            refreshSection('sessions', true);
            if (CURRENT_USER_USERNAME && CURRENT_USER_USERNAME === username) {
                viewUserDetails(username);
            }
        })
        .fail(function(xhr) {
            toast('Enable failed', xhr?.responseJSON?.message || 'Could not enable user.', 'danger');
        });
}

function extendUserPackage() {
    const username = $('#billingUsername').val() || CURRENT_USER_USERNAME || '';
    const packageId = $('#packageExtendPlanSelect').val() || '';
    const extendMinutesRaw = $('#packageExtendMinutes').val();
    const extendMinutes = extendMinutesRaw === '' ? '' : Number(extendMinutesRaw);
    const force = $('#packageExtendForce').is(':checked');

    if (!username) {
        toast('Missing user', 'Open user details first.', 'danger');
        return;
    }
    if (!packageId) {
        toast('Missing package', 'Select package to extend.', 'danger');
        return;
    }
    if (extendMinutesRaw !== '' && (!Number.isFinite(extendMinutes) || extendMinutes <= 0)) {
        toast('Invalid minutes', 'Enter a valid positive number of minutes.', 'danger');
        return;
    }

    const payload = {
        username,
        package_id: Number(packageId),
        force: force ? 1 : 0,
    };
    if (extendMinutesRaw !== '') {
        payload.extend_minutes = Math.round(extendMinutes);
    }

    postJson(ROUTES.extendPackage, payload)
        .done(function(resp) {
            const info = resp?.package_info || {};
            const currency = ($('#billingCurrencyInput').val() || 'KES').toUpperCase();
            renderPackageDetails(info, currency);
            $('#packageExtendResult')
                .html(`<div class="alert alert-success py-2 mb-0">${escapeHtml(resp?.message || 'Package extended.')}</div>`)
                .show();
            toast('Package extended', resp?.message || 'Done.', 'success');
            refreshSection('users', true);
            refreshSection('sessions', true);
            if (username) {
                viewUserDetails(username);
            }
        })
        .fail(function(xhr) {
            const msg = xhr?.responseJSON?.message || 'Could not extend package.';
            $('#packageExtendResult')
                .html(`<div class="alert alert-danger py-2 mb-0">${escapeHtml(msg)}</div>`)
                .show();
            toast('Extend failed', msg, 'danger');
        });
}

function expireUserPackage(subscriptionId = 0, connectionId = 0) {
    const username = ($('#billingUsername').val() || CURRENT_USER_USERNAME || '').toString().trim();

    if (!username) {
        toast('Missing user', 'Open user details first.', 'danger');
        return;
    }

    if (!window.confirm(`Expire the active subscription for ${username}? This will end current hotspot access.`)) {
        return;
    }

    const payload = {
        username,
        disconnect: 1,
    };

    const parsedSubscriptionId = Number(subscriptionId || 0);
    const parsedConnectionId = Number(connectionId || 0);
    if (parsedSubscriptionId > 0) {
        payload.subscription_id = parsedSubscriptionId;
    }
    if (parsedConnectionId > 0) {
        payload.connection_id = parsedConnectionId;
    }

    postJson(ROUTES.expirePackage, payload)
        .done(function(resp) {
            const info = resp?.package_info || {};
            const currency = ($('#billingCurrencyInput').val() || 'KES').toUpperCase();
            const message = resp?.message || 'Subscription expired.';

            renderPackageDetails(info, currency);
            $('#packageExtendResult')
                .html(`<div class="alert alert-success py-2 mb-0">${escapeHtml(message)}</div>`)
                .show();
            toast('Subscription expired', message, 'success');
            refreshSection('users', true);
            refreshSection('sessions', true);
            if (username) {
                viewUserDetails(username);
            }
        })
        .fail(function(xhr) {
            const msg = xhr?.responseJSON?.message || 'Could not expire subscription.';
            $('#packageExtendResult')
                .html(`<div class="alert alert-danger py-2 mb-0">${escapeHtml(msg)}</div>`)
                .show();
            toast('Expire failed', msg, 'danger');
        });
}

function disconnectSession(username, mac = '') {
    if (!username && !mac) return;
    if (!window.confirm(`Disconnect active session for ${username || mac}?`)) return;

    postJson(ROUTES.disconnect, { username, mac })
        .done(function(resp) {
            toast('Disconnected', resp.message || 'Session disconnected.', 'success');
            refreshSection('sessions', true);
            refreshSection('users', true);
        })
        .fail(function(xhr) {
            toast('Disconnect failed', xhr?.responseJSON?.message || 'Could not disconnect.', 'danger');
        });
}

function blockHost(mac) {
    if (!mac) return;
    if (!window.confirm(`Block host ${mac}?`)) return;

    postJson(ROUTES.blockHost, { mac })
        .done(function(resp) {
            toast('Host updated', resp.message || 'Host blocked.', 'success');
            refreshSection('hosts', true);
            refreshSection('sessions', true);
        })
        .fail(function(xhr) {
            toast('Block failed', xhr?.responseJSON?.message || 'Could not block host.', 'danger');
        });
}

function filterSessionsByMac(mac) {
    if (!mac) return;

    const tabButton = document.querySelector('button[data-bs-target="#sessions-pane"]');
    if (tabButton) {
        bootstrap.Tab.getOrCreateInstance(tabButton).show();
    }

    refreshSection('sessions', true);
    setTimeout(() => {
        const $table = $('#sessions-section table.display');
        if ($table.length && $.fn.dataTable.isDataTable($table[0])) {
            $table.DataTable().search(mac).draw();
            toast('Sessions filtered', `Showing sessions for ${mac}.`, 'info');
        }
    }, 450);
}

function deleteCookie(username, mac = '') {
    if (!username && !mac) return;
    if (!window.confirm(`Delete cookie for ${username || mac}?`)) return;

    postJson(ROUTES.deleteCookie, { username, mac })
        .done(function(resp) {
            toast('Cookie deleted', resp.message || 'Cookie removed.', 'success');
            refreshSection('cookies', true);
        })
        .fail(function(xhr) {
            toast('Delete failed', xhr?.responseJSON?.message || 'Could not delete cookie.', 'danger');
        });
}

function createPasswordFromPhone(phone) {
    const digits = String(phone || '').replace(/\D+/g, '');
    if (digits.length === 0) {
        return '';
    }

    const source = digits.length >= 6 ? digits.slice(-6) : digits;
    return source.padStart(6, '0');
}

function generateCreatePassword() {
    const seed = Math.random().toString(36).slice(2, 8).toUpperCase();
    return `NB${seed}`;
}

function syncCreatePassword(forceRefresh = false) {
    const mode = ($('#createPasswordMode').val() || 'auto').toString();
    const $password = $('#createPassword');

    if (mode === 'custom') {
        $password.prop('readonly', false).attr('placeholder', 'Enter password');
        if (forceRefresh && !$password.val()?.trim()) {
            $password.val('');
        }
        return;
    }

    let nextPassword = '';
    if (mode === 'phone_last6') {
        nextPassword = createPasswordFromPhone($('#createPhone').val() || '');
        if (!nextPassword) {
            nextPassword = generateCreatePassword();
        }
    } else {
        nextPassword = generateCreatePassword();
    }

    const currentValue = $password.val()?.trim() || '';
    if (forceRefresh || currentValue === '' || $password.prop('readonly')) {
        $password.val(nextPassword);
    }

    $password.prop('readonly', true).attr('placeholder', 'Generated automatically');
}

function syncCreatePlanSelection() {
    const selected = document.querySelector('#createPlan option:checked');
    const planName = selected?.dataset?.name || 'No plan selected';
    const planRate = Number(selected?.dataset?.price || 0);
    const planProfile = (selected?.dataset?.profile || '').toString().trim();

    $('#createPlanSummaryName').text(planName);
    $('#createPlanSummaryRate').text(selected?.value ? `KES ${planRate.toFixed(2)} /MB` : 'KES 0.00 /MB');
    $('#createPlanSummaryProfile').text(planProfile || 'Use default / choose profile');

    if (planProfile) {
        $('#createProfile').val(planProfile);
        if (!$('#createProfile').val()) {
            $('#createProfile').val('');
        }
    }
}

function resetCreateUserForm() {
    $('#createUsername').val('');
    $('#createPhone').val('');
    $('#createName').val('');
    $('#createEmail').val('');
    $('#createAddress').val('');
    $('#createComment').val('');
    $('#createNotify').prop('checked', false);
    $('#createStatus').val('active');
    $('#createLimitUptime').val('');
    $('#createPlan').val('');
    $('#createProfile').val('');
    $('#createPasswordMode').val('auto');
    $('#createUserAlert').hide().empty();
    syncCreatePassword(true);
    syncCreatePlanSelection();
}

function createMeteredUser() {
    const username = $('#createUsername').val()?.trim() || '';
    const password = $('#createPassword').val()?.trim() || '';
    const profile = $('#createProfile').val()?.trim() || '';
    const packageId = $('#createPlan').val();

    if (!username || !password) {
        toast('Validation', 'Username and password are required.', 'danger');
        return;
    }
    if (!packageId) {
        toast('Validation', 'Select a metered plan before creating the user.', 'danger');
        return;
    }

    const payload = {
        username,
        password,
        profile,
        package_id: packageId ? Number(packageId) : '',
        phone: $('#createPhone').val()?.trim() || '',
        email: $('#createEmail').val()?.trim() || '',
        name: $('#createName').val()?.trim() || '',
        address: $('#createAddress').val()?.trim() || '',
        status: $('#createStatus').val() || 'active',
        limit_uptime: $('#createLimitUptime').val()?.trim() || '',
        comment: $('#createComment').val()?.trim() || '',
        notify_customer: $('#createNotify').is(':checked') ? 1 : 0,
    };

    postJson(ROUTES.createUser, payload)
        .done(function(resp) {
            toast('User created', resp?.message || 'Metered user created.', 'success');
            const notify = resp?.notification || null;
            const accountUrl = resp?.public_account_url || '';
            if (notify && notify.success === false) {
                toast('SMS not sent', notify.message || 'User created but notification failed.', 'danger');
            } else if (notify && notify.success === true) {
                toast('SMS sent', 'Customer received login details and account link.', 'success');
            }
            $('#createUserAlert')
                .html(`<div class="alert alert-success py-2 mb-0">User created successfully.${accountUrl ? ` <a href="${escapeHtml(accountUrl)}" target="_blank">View public account</a>` : ''}</div>`)
                .show();

            const modal = bootstrap.Modal.getInstance(document.getElementById('createUserModal'));
            setTimeout(() => {
                if (modal) modal.hide();
                resetCreateUserForm();
            }, 600);

            refreshSection('users', true);
            refreshSection('sessions', true);
        })
        .fail(function(xhr) {
            const msg = xhr?.responseJSON?.message || 'Could not create metered user.';
            toast('Create failed', msg, 'danger');
            $('#createUserAlert')
                .html(`<div class="alert alert-danger py-2 mb-0">${escapeHtml(msg)}</div>`)
                .show();
        });
}

function saveCustomerProfile() {
    const username = $('#billingUsername').val();
    if (!username) {
        toast('Missing user', 'Open user details first.', 'danger');
        return;
    }

    const packageId = $('#generalPlanSelect').val();
    const email = $('#generalEmail').val()?.trim() || '';
    const payload = {
        username,
        name: $('#generalName').val()?.trim() || '',
        phone: $('#generalPhone').val()?.trim() || '',
        address: $('#generalAddress').val()?.trim() || '',
        status: $('#generalStatus').val() || 'active',
        package_id: packageId ? Number(packageId) : '',
        notify_customer: $('#billingNotifyCustomer').is(':checked') ? 1 : 0,
    };

    if (email) {
        payload.email = email;
    }

    postJson(ROUTES.saveCustomerProfile, payload)
        .done(function(resp) {
            toast('Profile saved', resp?.message || 'Customer profile updated.', 'success');

            if (packageId) {
                $('#billingPlanSelect').val(packageId);
            }

            if (username) {
                viewUserDetails(username);
            }
        })
        .fail(function(xhr) {
            toast('Save failed', xhr?.responseJSON?.message || 'Could not save customer profile.', 'danger');
        });
}

function saveBillingRate() {
    if (CURRENT_DETAIL_MODE !== 'metered') {
        toast('Not available', 'Billing rate applies to metered users only.', 'warning');
        return;
    }

    const username = $('#billingUsername').val();
    const rate = Number($('#billingRateInput').val());
    const currency = ($('#billingCurrencyInput').val() || 'KES').toUpperCase();
    const packageId = $('#billingPlanSelect').val();
    const notifyCustomer = $('#billingNotifyCustomer').is(':checked') ? 1 : 0;
    const isHotspotBilling = currentBillingMode() === 'hotspot';

    if (!username) {
        toast('Missing user', 'Open user details first.', 'danger');
        return;
    }
    if (!Number.isFinite(rate) || rate < 0) {
        toast('Invalid amount', isHotspotBilling ? 'Package price must be a valid number.' : 'Rate per MB must be a valid number.', 'danger');
        return;
    }

    postJson(ROUTES.saveBillingRate, {
        username,
        rate_per_mb: rate,
        currency,
        package_id: packageId ? Number(packageId) : '',
        notify_customer: notifyCustomer,
    }).done(function(resp) {
        toast('Billing saved', resp.message || 'Billing rate saved.', 'success');
    }).fail(function(xhr) {
        toast('Billing failed', xhr?.responseJSON?.message || 'Could not save billing rate.', 'danger');
    });
}

function generateInvoice() {
    if (CURRENT_DETAIL_MODE !== 'metered') {
        toast('Not available', 'Invoice generation is available for metered users only.', 'warning');
        return;
    }

    const username = $('#billingUsername').val();
    const usageBytes = Number($('#billingUsageBytes').val() || 0);
    const rate = Number($('#billingRateInput').val());
    const currency = ($('#billingCurrencyInput').val() || 'KES').toUpperCase();
    const packageId = $('#billingPlanSelect').val();
    const notifyCustomer = $('#billingNotifyCustomer').is(':checked') ? 1 : 0;
    const issuedAt = $('#billingIssuedAt').val() || '';
    const dueDate = $('#billingDueDate').val() || '';
    const taxPercent = Number($('#billingTaxPercent').val() || 0);
    const penaltyPercent = Number($('#billingPenaltyPercent').val() || 0);
    const notes = $('#billingNotes').val()?.trim() || '';
    const isHotspotBilling = currentBillingMode() === 'hotspot';

    if (!username) {
        toast('Missing user', 'Open user details first.', 'danger');
        return;
    }
    if (!Number.isFinite(rate) || rate < 0) {
        toast('Invalid amount', isHotspotBilling ? 'Package price must be a valid number.' : 'Rate per MB must be a valid number.', 'danger');
        return;
    }

    postJson(ROUTES.generateInvoice, {
        username,
        usage_bytes: Math.max(0, Math.round(usageBytes)),
        rate_per_mb: rate,
        currency,
        package_id: packageId ? Number(packageId) : '',
        issued_at: issuedAt || null,
        due_date: dueDate || null,
        tax_percent: Number.isFinite(taxPercent) ? taxPercent : 0,
        penalty_percent: Number.isFinite(penaltyPercent) ? penaltyPercent : 0,
        notes,
        notify_customer: notifyCustomer,
    }).done(function(resp) {
        const inv = resp.invoice || {};
        const bill = resp.billing || { currency };
        const publicUrl = inv.public_url ? `<a href="${escapeHtml(inv.public_url)}" target="_blank">Public payment link</a>` : '';

        $('#invoiceGeneratedBox')
            .html(`<div class="alert alert-success py-2 mb-0"><strong>${escapeHtml(inv.invoice_number || '-')}</strong> generated for ${escapeHtml(formatCurrency(inv.amount || 0, bill.currency || currency))}. ${publicUrl}</div>`)
            .show();

        appendInvoiceRow(inv, bill.currency || currency, true);
        $('#billingNotes').val('');
        toast('Invoice generated', resp.message || 'Invoice created.', 'success');
    }).fail(function(xhr) {
        toast('Invoice failed', xhr?.responseJSON?.message || 'Could not generate invoice.', 'danger');
    });
}

function bindActionHandlers() {
    $(document).off('click.actionViewUser').on('click.actionViewUser', '.action-view-user', function(e) {
        e.preventDefault();
        viewUserDetails($(this).data('username') || '');
    });

    $(document).off('click.actionEditUser').on('click.actionEditUser', '.action-edit-user', function(e) {
        e.preventDefault();
        editUser($(this).data('username') || '', {
            profile: $(this).data('profile') || '',
            limitUptime: $(this).data('limit-uptime') || '',
            comment: $(this).data('comment') || '',
        });
    });

    $(document).off('click.actionDisableUser').on('click.actionDisableUser', '.action-disable-user', function(e) {
        e.preventDefault();
        disableUser($(this).data('username') || '', $(this).data('mac') || '');
    });

    $(document).off('click.actionEnableUser').on('click.actionEnableUser', '.action-enable-user', function(e) {
        e.preventDefault();
        enableUser($(this).data('username') || '', $(this).data('mac') || '');
    });

    $(document).off('click.actionViewTraffic').on('click.actionViewTraffic', '.action-view-traffic', function(e) {
        e.preventDefault();
        viewTrafficModal($(this).data('username') || '', $(this).data('mac') || '');
    });

    $(document).off('click.actionViewSessionTraffic').on('click.actionViewSessionTraffic', '.action-view-session-traffic', function(e) {
        e.preventDefault();
        viewTrafficModal(
            ($(this).data('username') || CURRENT_USER_USERNAME || '').toString().trim(),
            ($(this).data('mac') || '').toString().trim(),
            $(this).data('connection-id') || null
        );
    });

    $(document).off('click.actionHotspotSubUsage').on('click.actionHotspotSubUsage', '.action-hotspot-sub-view-usage', function(e) {
        e.preventDefault();
        const username = ($(this).data('username') || CURRENT_USER_USERNAME || '').toString().trim();
        if (!username) {
            toast('Missing user', 'Open user details first.', 'danger');
            return;
        }
        viewTrafficModal(username, '');
    });

    $(document).off('click.actionHotspotSubModify').on('click.actionHotspotSubModify', '.action-hotspot-sub-modify', function(e) {
        e.preventDefault();
        const packageId = ($(this).data('package-id') || '').toString().trim();
        const forceOnly = Number($(this).data('force-only') || 0) === 1;
        if (!packageId) {
            return;
        }
        selectExtensionPackage(packageId, forceOnly);
        const packageTabBtn = document.querySelector('button[data-bs-target="#user-package-tab"]');
        if (packageTabBtn) {
            bootstrap.Tab.getOrCreateInstance(packageTabBtn).show();
        }
        toast('Package selected', 'Review extension settings and apply.', 'info');
    });

    $(document).off('click.actionHotspotSubExpire').on('click.actionHotspotSubExpire', '.action-hotspot-sub-expire', function(e) {
        e.preventDefault();
        expireUserPackage(
            Number($(this).data('subscription-id') || 0),
            Number($(this).data('connection-id') || 0)
        );
    });

    $(document).off('click.actionDisconnect').on('click.actionDisconnect', '.action-disconnect-session', function(e) {
        e.preventDefault();
        disconnectSession($(this).data('username') || '', $(this).data('mac') || '');
    });

    $(document).off('click.actionBlockHost').on('click.actionBlockHost', '.action-block-host', function(e) {
        e.preventDefault();
        blockHost($(this).data('mac') || '');
    });

    $(document).off('click.actionFilterByMac').on('click.actionFilterByMac', '.action-filter-sessions-by-mac', function(e) {
        e.preventDefault();
        filterSessionsByMac($(this).data('mac') || '');
    });

    $(document).off('click.actionDeleteCookie').on('click.actionDeleteCookie', '.action-delete-cookie', function(e) {
        e.preventDefault();
        deleteCookie($(this).data('username') || '', $(this).data('mac') || '');
    });

    $(document).off('click.createUser').on('click.createUser', '#createUserBtn', function() {
        createMeteredUser();
    });

    $(document).off('change.createPlan').on('change.createPlan', '#createPlan', function() {
        syncCreatePlanSelection();
    });

    $(document).off('change.createProfile').on('change.createProfile', '#createProfile', function() {
        const selectedProfile = ($(this).val() || '').toString().trim();
        $('#createPlanSummaryProfile').text(selectedProfile || 'Use default / choose profile');
    });

    $(document).off('change.createPasswordMode').on('change.createPasswordMode', '#createPasswordMode', function() {
        syncCreatePassword(true);
    });

    $(document).off('input.createPhone').on('input.createPhone', '#createPhone', function() {
        if (!$('#createUsername').val()?.trim()) {
            const digits = ($(this).val() || '').replace(/\D+/g, '');
            if (digits.length >= 9) {
                $('#createUsername').val(`mtr${digits.slice(-9)}`);
            }
        }
        syncCreatePassword(false);
    });

    $(document).off('shown.bs.modal.createUser').on('shown.bs.modal.createUser', '#createUserModal', function() {
        syncCreatePlanSelection();
        syncCreatePassword(false);
    });

    $(document).off('click.saveCustomerProfile').on('click.saveCustomerProfile', '#saveCustomerProfileBtn', function() {
        saveCustomerProfile();
    });

    $(document).off('click.saveBilling').on('click.saveBilling', '#saveBillingRateBtn', function() {
        saveBillingRate();
    });

    $(document).off('click.generateInvoice').on('click.generateInvoice', '#generateInvoiceBtn', function() {
        generateInvoice();
    });

    $(document).off('change.generalPlan').on('change.generalPlan', '#generalPlanSelect', function() {
        const val = $(this).val();
        if (val !== undefined) {
            $('#billingPlanSelect').val(val);
            updateBillingUiForPlan(val, true);
        }
    });

    $(document).off('change.billingPlan').on('change.billingPlan', '#billingPlanSelect', function() {
        const val = $(this).val();
        if (val !== undefined) {
            $('#generalPlanSelect').val(val);
            updateBillingUiForPlan(val, true);
            $('#packageExtendPlanSelect').val(val);
        }
    });

    $(document).off('change.packagePlan').on('change.packagePlan', '#packageExtendPlanSelect', function() {
        const val = $(this).val();
        if (val !== undefined) {
            $('#generalPlanSelect').val(val);
            $('#billingPlanSelect').val(val);
            updateBillingUiForPlan(val, true);
        }
    });

    $(document).off('change.packageForce').on('change.packageForce', '#packageExtendForce', function() {
        syncExtensionOptionsFromForce();
    });

    $(document).off('click.extendPackage').on('click.extendPackage', '#extendPackageBtn', function() {
        extendUserPackage();
    });

    $(document).off('click.expirePackage').on('click.expirePackage', '#expirePackageBtn', function() {
        expireUserPackage();
    });

    $(document).off('click.userFilter').on('click.userFilter', '.user-filter-btn', function() {
        CURRENT_USER_FILTER = ($(this).data('filter') || 'all').toString().toLowerCase();
        applyUsersFilter();
    });
}

function postJson(url, data = {}) {
    return $.ajax({
        url,
        method: 'POST',
        data: { _token: CSRF_TOKEN, ...data },
    });
}

function formatCurrency(value, currency = 'KES') {
    const amount = Number(value || 0);
    return `${(currency || 'KES').toUpperCase()} ${amount.toFixed(2)}`;
}

function formatDate(value) {
    if (!value) return '-';
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString();
}

function formatBytes(bytes) {
    const b = Number(bytes || 0);
    if (!Number.isFinite(b) || b <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = b;
    let i = 0;

    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }

    return `${size.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function formatBitsPerSecond(bits) {
    const value = Number(bits || 0);
    if (!Number.isFinite(value) || value <= 0) return '0 bps';

    const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    let size = value;
    let i = 0;

    while (size >= 1000 && i < units.length - 1) {
        size /= 1000;
        i++;
    }

    const decimals = i === 0 ? 0 : (size >= 100 ? 0 : (size >= 10 ? 1 : 2));
    return `${size.toFixed(decimals)} ${units[i]}`;
}

function toast(title, subtitle='', variant='info'){
    const icon = variant === 'success' ? 'check-circle' :
                 variant === 'danger' ? 'exclamation-triangle' :
                 'info-circle';

    const el = $(`
        <div class="nm-toast">
            <div class="bar"></div>
            <div class="body">
                <div><i class="bi bi-${icon}"></i></div>
                <div>
                    <div class="t">${escapeHtml(title)}</div>
                    ${subtitle ? `<div class="s">${escapeHtml(subtitle)}</div>` : ``}
                </div>
                <button class="x" type="button" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        </div>
    `);

    $('body').append(el);
    el.find('.x').on('click', () => el.remove());
    setTimeout(() => el.fadeOut(180, () => el.remove()), 2000);
}

function cap(s){ return (s || '').charAt(0).toUpperCase() + (s || '').slice(1); }
function escapeHtml(str){
    return (str ?? '').toString()
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

function openUserFromQueryParams() {
    const params = new URLSearchParams(window.location.search || '');
    const username = (params.get('open_user') || '').toString().trim();
    if (!username) {
        return;
    }

    const focus = (params.get('focus') || '').toString().trim().toLowerCase();
    const focusTargets = {
        general: '#user-general-tab',
        usage: '#user-usage-tab',
        package: '#user-package-tab',
        billing: '#user-billing-tab',
    };
    const targetTab = focusTargets[focus] || '';
    const detailsModal = document.getElementById('userDetailsModal');

    if (detailsModal && targetTab) {
        detailsModal.addEventListener('shown.bs.modal', function onShown() {
            const btn = document.querySelector(`button[data-bs-target="${targetTab}"]`);
            if (btn) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        }, { once: true });
    }

    setTimeout(() => viewUserDetails(username), 150);
}

$(document).ready(function() {
    bindActionHandlers();
    safeInitDataTables();
    initializeDropdowns();
    setAutoRefresh(false);
    resetCreateUserForm();

    // Initial server render already has data; avoid immediate ajax overwrite.
    const hasInitialRows = ['users', 'sessions', 'hosts', 'cookies']
        .some((type) => $(`#${type}-section tbody tr`).length > 0);

    if (!hasInitialRows) {
        refreshAllSections(true);
    }

    const detailsModal = document.getElementById('userDetailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('hidden.bs.modal', function() {
            stopUserDetailsAutoRefresh();
            CURRENT_USER_USERNAME = '';
        });
    }

    const trafficModal = document.getElementById('trafficModal');
    if (trafficModal) {
        trafficModal.addEventListener('hidden.bs.modal', function() {
            stopTrafficPolling();
            if (TRAFFIC_CHART) {
                TRAFFIC_CHART.destroy();
                TRAFFIC_CHART = null;
            }
            TRAFFIC_HISTORY = [];
            TRAFFIC_LAST_SNAPSHOT = null;
            TRAFFIC_ACTIVE_TARGET = { username: '', mac: '', connectionId: null };
            setTrafficPresentation(false);
        });
    }

    const createUserModal = document.getElementById('createUserModal');
    if (createUserModal) {
        createUserModal.addEventListener('hidden.bs.modal', function() {
            resetCreateUserForm();
        });
    }

    openUserFromQueryParams();
});

$(document).on('click', '#toggleAutoRefresh', function(){
    setAutoRefresh(!AUTO_REFRESH_ON);
});

$(document).on('keydown', function(e) {
    if (e.ctrlKey && e.key.toLowerCase() === 'r') {
        e.preventDefault();
        refreshAllSections(false);
        toast('Refresh', 'All sections refreshed.', 'success');
    }
});
</script>

@endsection

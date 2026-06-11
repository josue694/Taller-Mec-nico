/* ============================================================
   assets/css/style.css — Estilos principales Garage GT
   ============================================================ */

:root {
    --color-primary:    #1a1a2e;
    --color-secondary:  #16213e;
    --color-accent:     #e94560;
    --color-accent-2:   #0f3460;
    --color-bg:         #f0f2f5;
    --color-card:       #ffffff;
    --color-text:       #2d3436;
    --color-text-light: #636e72;
    --color-border:     #dfe6e9;
    --sidebar-width:    260px;
    --topbar-height:    60px;
    --font-main:        'Segoe UI', system-ui, -apple-system, sans-serif;
    --radius:           10px;
    --shadow:           0 2px 12px rgba(0,0,0,.08);
    --transition:       .25s ease;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--font-main);
    background: var(--color-bg);
    color: var(--color-text);
    font-size: 14px;
}

/* ── SIDEBAR ───────────────────────────────────────────────── */
.sidebar {
    width: var(--sidebar-width);
    min-height: 100vh;
    background: var(--color-primary);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    z-index: 1000;
    transition: width var(--transition);
    overflow: hidden;
}

.sidebar.collapsed { width: 64px; }
.sidebar.collapsed .sidebar-brand span,
.sidebar.collapsed .nav-section,
.sidebar.collapsed li a span,
.sidebar.collapsed .user-info div,
.sidebar.collapsed .user-name { display: none; }

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px 16px;
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: .5px;
    border-bottom: 1px solid rgba(255,255,255,.1);
}
.sidebar-brand i { font-size: 1.5rem; color: var(--color-accent); flex-shrink: 0; }

.sidebar-nav {
    flex: 1;
    padding: 16px 0;
    overflow-y: auto;
}

.nav-section {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,.35);
    padding: 14px 20px 6px;
}

.sidebar-nav li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: rgba(255,255,255,.75);
    text-decoration: none;
    border-radius: 0;
    transition: background var(--transition), color var(--transition);
    white-space: nowrap;
}
.sidebar-nav li a i { font-size: 1.1rem; flex-shrink: 0; }
.sidebar-nav li a:hover,
.sidebar-nav li a.active {
    background: rgba(255,255,255,.08);
    color: #fff;
    border-left: 3px solid var(--color-accent);
}

.sidebar-footer {
    padding: 14px 20px;
    border-top: 1px solid rgba(255,255,255,.1);
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    overflow: hidden;
}
.user-info > i { font-size: 1.6rem; color: rgba(255,255,255,.6); flex-shrink: 0; }
.user-name {
    display: block;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-rol {
    font-size: 10px;
    background: var(--color-accent);
    color: #fff;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: capitalize;
}
.btn-logout {
    color: rgba(255,255,255,.5);
    font-size: 1.2rem;
    transition: color var(--transition);
    flex-shrink: 0;
}
.btn-logout:hover { color: var(--color-accent); }

/* ── MAIN CONTENT ──────────────────────────────────────────── */
.main-content {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    transition: margin-left var(--transition);
}
.sidebar.collapsed ~ .main-content { margin-left: 64px; }

.topbar {
    height: var(--topbar-height);
    background: var(--color-card);
    border-bottom: 1px solid var(--color-border);
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 100;
}
.topbar h5 { font-weight: 700; color: var(--color-primary); }

.btn-icon {
    background: none;
    border: none;
    font-size: 1.4rem;
    color: var(--color-text);
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: background var(--transition);
}
.btn-icon:hover { background: var(--color-bg); }

.content-area { flex: 1; }

/* ── CARDS ─────────────────────────────────────────────────── */
.card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid var(--color-border);
    padding: 16px 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-header i { color: var(--color-accent); }
.card-body { padding: 20px; }

/* ── STAT CARDS ────────────────────────────────────────────── */
.stat-card {
    background: var(--color-card);
    border-radius: var(--radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--color-accent);
}
.stat-icon {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: rgba(233,69,96,.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--color-accent);
    flex-shrink: 0;
}
.stat-card.blue  { border-left-color: #0984e3; }
.stat-card.blue  .stat-icon { background: rgba(9,132,227,.1); color: #0984e3; }
.stat-card.green { border-left-color: #00b894; }
.stat-card.green .stat-icon { background: rgba(0,184,148,.1); color: #00b894; }
.stat-card.orange{ border-left-color: #e17055; }
.stat-card.orange .stat-icon{ background: rgba(225,112,85,.1); color: #e17055; }

.stat-value { font-size: 1.8rem; font-weight: 800; color: var(--color-primary); }
.stat-label { font-size: 12px; color: var(--color-text-light); text-transform: uppercase; letter-spacing: .5px; }

/* ── BUTTONS ───────────────────────────────────────────────── */
.btn-primary {
    background: var(--color-accent) !important;
    border-color: var(--color-accent) !important;
}
.btn-primary:hover {
    background: #c73652 !important;
    border-color: #c73652 !important;
}

/* ── TABLES ────────────────────────────────────────────────── */
.table th {
    background: var(--color-primary);
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: .5px;
    text-transform: uppercase;
    border: none;
}
.table td { vertical-align: middle; }
.table-hover tbody tr:hover { background: rgba(233,69,96,.04); }

/* ── BADGES DE ESTADO ──────────────────────────────────────── */
.badge-pendiente  { background: #fdcb6e; color: #2d3436; }
.badge-en_proceso { background: #0984e3; color: #fff; }
.badge-finalizado { background: #00b894; color: #fff; }
.badge-cancelado  { background: #d63031; color: #fff; }

/* ── LOGIN ─────────────────────────────────────────────────── */
.login-wrapper {
    min-height: 100vh;
    background: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.login-card {
    background: var(--color-card);
    border-radius: 16px;
    padding: 40px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.login-logo {
    text-align: center;
    margin-bottom: 28px;
}
.login-logo i { font-size: 3rem; color: var(--color-accent); }
.login-logo h2 { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); margin-top: 8px; }
.login-logo p  { color: var(--color-text-light); font-size: 13px; }

/* ── FORMS ─────────────────────────────────────────────────── */
.form-label { font-weight: 600; font-size: 13px; }
.form-control:focus, .form-select:focus {
    border-color: var(--color-accent);
    box-shadow: 0 0 0 3px rgba(233,69,96,.15);
}

/* ── ALERTS ────────────────────────────────────────────────── */
.alert { border-radius: var(--radius); border: none; }

/* ── RESPONSIVE ────────────────────────────────────────────── */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0; }
}

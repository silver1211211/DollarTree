<?php
if (!isset($page_title))  $page_title  = 'Admin';
if (!isset($active_nav))  $active_nav  = '';
if (!isset($topbar_actions)) $topbar_actions = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title><?php echo htmlspecialchars($page_title); ?> — DollarTree Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --gp:#1a7a1a;--gb:#22a322;--gl:#2db82d;--gg:rgba(26,122,26,.12);
  --bg:#090d09;--panel:#0f150f;--card:#131a13;--border:#1a261a;--border2:#243024;
  --tp:#e4f5e4;--ts:#9dbf9d;--tm:#5a7a5a;--tl:#324832;
  --red:#c0392b;--amber:#d4700a;--blue:#2980b9;
  --nav-w:230px;--topbar-h:52px;--bottom-nav-h:60px;
  --r:8px;--r2:12px;--touch:44px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-tap-highlight-color:transparent;}
body{
  font-family:'Barlow',sans-serif;
  background:var(--bg);color:var(--ts);
  min-height:100vh;display:flex;
  -webkit-font-smoothing:antialiased;
  overscroll-behavior:none;
}

/* ── SIDEBAR ── */
.sidebar{
  position:fixed;top:0;left:0;bottom:0;width:var(--nav-w);
  background:var(--panel);border-right:1px solid var(--border);
  display:flex;flex-direction:column;z-index:200;
  transition:transform .25s cubic-bezier(.4,0,.2,1);
}
.sidebar::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:repeating-linear-gradient(0deg,transparent,transparent 31px,rgba(26,122,26,.05) 31px,rgba(26,122,26,.05) 32px);
}
.sb-logo{position:relative;z-index:2;padding:16px 14px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
.sb-logo-icon{width:32px;height:32px;border-radius:8px;background:var(--gp);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 14px rgba(26,122,26,.5);}
.sb-logo-icon i{color:#fff;font-size:14px;}
.sb-logo-text{font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:900;color:var(--tp);}
.sb-logo-sub{font-size:9px;color:var(--tl);font-weight:700;text-transform:uppercase;letter-spacing:.12em;}
.sb-nav{position:relative;z-index:2;flex:1;overflow-y:auto;padding:6px 8px;}
.sb-nav::-webkit-scrollbar{width:2px;}
.sb-nav::-webkit-scrollbar-thumb{background:var(--border2);}
.sb-section{padding:10px 8px 3px;font-size:9px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.14em;}
.nav-link{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:var(--r);color:var(--tm);font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;margin-bottom:1px;min-height:var(--touch);}
.nav-link i{width:14px;text-align:center;font-size:12px;flex-shrink:0;}
.nav-link:hover,.nav-link:active{background:var(--gg);color:var(--ts);}
.nav-link.active{background:var(--gg);color:var(--gl);border-left:2px solid var(--gp);padding-left:8px;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;border-radius:100px;font-size:9px;font-weight:800;padding:2px 6px;}
.sb-footer{position:relative;z-index:2;padding:10px;border-top:1px solid var(--border);}
.sb-admin{display:flex;align-items:center;gap:8px;}
.sb-avatar{width:28px;height:28px;border-radius:50%;background:var(--gg);border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--gl);flex-shrink:0;}
.sb-name{font-size:12px;font-weight:700;color:var(--ts);}
.sb-role{font-size:9px;color:var(--tl);}
.sb-logout{margin-left:auto;color:var(--tl);font-size:13px;text-decoration:none;padding:8px;transition:color .15s;display:flex;align-items:center;justify-content:center;min-width:var(--touch);min-height:var(--touch);}
.sb-logout:hover{color:var(--red);}

/* ── DRAWER OVERLAY ── */
.drawer-overlay{
  display:none;
  position:fixed;inset:0;
  background:rgba(0,0,0,.6);
  z-index:199;
  opacity:0;
  transition:opacity .25s;
  pointer-events:none;
}
.drawer-overlay.open{
  opacity:1;
  pointer-events:auto;
}

/* ── MAIN ── */
.main{
  margin-left:var(--nav-w);
  flex:1;
  display:flex;
  flex-direction:column;
  min-height:100vh;
  /* Push content down so it starts below the fixed topbar */
  padding-top:var(--topbar-h);
}

/* ── TOPBAR (FIXED) ── */
.topbar{
  background:var(--panel);
  border-bottom:1px solid var(--border);
  padding:0 16px;
  height:var(--topbar-h);
  display:flex;
  align-items:center;
  justify-content:space-between;
  /* Fixed to viewport, spanning only the main column */
  position:fixed;
  top:0;
  left:var(--nav-w);   /* starts after the sidebar */
  right:0;
  z-index:100;
  /* Smooth transition for mobile (sidebar slides in/out) */
  transition:left .25s cubic-bezier(.4,0,.2,1);
}
.tb-left{display:flex;align-items:center;gap:10px;}

/* ── HAMBURGER ── */
.tb-hamburger{
  display:none;
  align-items:center;justify-content:center;
  width:36px;height:36px;
  background:var(--gg);
  border:1px solid var(--border2);
  border-radius:var(--r);
  color:var(--ts);
  cursor:pointer;
  font-size:15px;
  flex-shrink:0;
  transition:all .15s;
  touch-action:manipulation;
  -webkit-tap-highlight-color:transparent;
}
.tb-hamburger:hover,.tb-hamburger:active{
  background:rgba(26,122,26,.2);
  color:var(--gl);
}

.tb-title{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:900;color:var(--tp);}
.tb-right{display:flex;align-items:center;gap:6px;}
.tb-chip{display:flex;align-items:center;gap:6px;background:var(--gg);border:1px solid var(--border2);border-radius:var(--r);padding:6px 10px;font-size:11px;font-weight:700;color:var(--ts);text-decoration:none;cursor:pointer;transition:all .15s;font-family:'Barlow',sans-serif;border:none;white-space:nowrap;min-height:36px;touch-action:manipulation;}
.tb-chip:hover,.tb-chip:active{background:rgba(26,122,26,.2);color:var(--gl);}
.tb-chip.primary{background:var(--gp);color:#fff;box-shadow:0 2px 10px rgba(26,122,26,.35);}
.tb-chip.primary:hover{background:var(--gb);}
.tb-clock{}

/* ── CONTENT ── */
.content{padding:16px;flex:1;background:var(--bg);}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r2);padding:15px 17px;position:relative;overflow:hidden;transition:border-color .15s;}
.stat-card:hover{border-color:var(--border2);}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gp),var(--gl));opacity:0;transition:opacity .15s;}
.stat-card:hover::after{opacity:1;}
.sc-bg-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:26px;opacity:.05;color:var(--gp);}
.sc-lbl{font-size:9px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.12em;margin-bottom:7px;}
.sc-val{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;color:var(--tp);line-height:1;}
.sc-sub{font-size:11px;color:var(--tm);margin-top:4px;display:flex;align-items:center;gap:4px;}
.sc-sub.up{color:#2ecc71;}.sc-sub.dn{color:var(--red);}.sc-sub.warn{color:var(--amber);}

/* ── CARDS ── */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--r2);padding:14px;}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px;flex-wrap:wrap;gap:8px;}
.card-title{font-family:'Barlow Condensed',sans-serif;font-size:13px;font-weight:900;color:var(--tp);text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;gap:7px;}
.card-title i{color:var(--gp);}

/* ── TABLE ── */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead th{background:rgba(26,122,26,.06);padding:8px 10px;text-align:left;font-size:9px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.1em;border-bottom:1px solid var(--border);white-space:nowrap;}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(26,122,26,.04);}
tbody td{padding:9px 10px;color:var(--ts);vertical-align:middle;}
.mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--tm);}
td strong{color:var(--tp);font-weight:700;}
.empty-row td{text-align:center;padding:30px;color:var(--tl);font-size:13px;}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:100px;font-size:10px;font-weight:700;white-space:nowrap;}
.b-green{background:rgba(26,122,26,.18);color:#3dd63d;border:1px solid rgba(26,122,26,.3);}
.b-red{background:rgba(192,57,43,.15);color:#e74c3c;border:1px solid rgba(192,57,43,.3);}
.b-amber{background:rgba(212,112,10,.15);color:#f39c12;border:1px solid rgba(212,112,10,.3);}
.b-blue{background:rgba(41,128,185,.12);color:#5dade2;border:1px solid rgba(41,128,185,.3);}
.b-gray{background:rgba(90,122,90,.1);color:var(--tm);border:1px solid var(--border);}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:5px;
  padding:8px 14px;border:none;border-radius:var(--r);
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;
  cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;
  min-height:36px;
  -webkit-tap-highlight-color:transparent;
  touch-action:manipulation;
  position:relative;z-index:1;
}
.btn-green{background:var(--gp);color:#fff;box-shadow:0 2px 8px rgba(26,122,26,.3);}
.btn-green:hover,.btn-green:active{background:var(--gb);}
.btn-red{background:var(--red);color:#fff;}
.btn-red:hover,.btn-red:active{background:#a93226;}
.btn-amber{background:var(--amber);color:#fff;}
.btn-amber:hover{background:#b8600a;}
.btn-blue{background:var(--blue);color:#fff;}
.btn-blue:hover{background:#2471a3;}
.btn-ghost{background:var(--gg);color:var(--ts);border:1px solid var(--border2);}
.btn-ghost:hover,.btn-ghost:active{background:rgba(26,122,26,.2);color:var(--gl);}
.btn-sm{padding:7px 12px;font-size:11px;min-height:34px;}
.btn-xs{padding:5px 10px;font-size:11px;min-height:32px;}

/* ── FORMS ── */
.form-group{margin-bottom:13px;}
.form-label{display:block;font-size:10px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.1em;margin-bottom:5px;}
.form-control{
  width:100%;
  background:#0a100a;
  border:1px solid var(--border2);
  border-radius:var(--r);
  padding:11px 12px;
  font-family:'Barlow',sans-serif;
  font-size:16px;
  color:var(--tp);
  transition:border-color .15s;
  outline:none;
  -webkit-appearance:none;
  appearance:none;
  min-height:var(--touch);
  position:relative;z-index:1;
}
.form-control:focus{border-color:var(--gp);box-shadow:0 0 0 3px rgba(26,122,26,.1);}
.form-control::placeholder{color:var(--tl);}
select.form-control{cursor:pointer;color:var(--tp);}
select.form-control option{background:var(--panel);color:var(--tp);}
textarea.form-control{resize:vertical;min-height:85px;font-size:14px;}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:13px;}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:13px;}
.form-hint{font-size:11px;color:var(--tl);line-height:1.5;display:flex;align-items:flex-start;gap:5px;}
.form-hint i{margin-top:1px;flex-shrink:0;}

/* ── MODAL ── */
.modal-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.78);
  z-index:500;
  display:none;
  align-items:flex-end;
  justify-content:center;
  padding:0;
}
.modal-overlay.open{display:flex;animation:fin .15s ease;}
@keyframes fin{from{opacity:0}to{opacity:1}}
.modal{
  background:var(--panel);
  border:1px solid var(--border2);
  border-radius:var(--r2) var(--r2) 0 0;
  width:100%;max-width:100%;
  box-shadow:0 -10px 40px rgba(0,0,0,.7);
  animation:mup .2s cubic-bezier(.34,1.56,.64,1);
  max-height:92vh;
  display:flex;flex-direction:column;
}
.modal-lg{max-width:100%;}
@keyframes mup{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.modal-title{font-family:'Barlow Condensed',sans-serif;font-size:14px;font-weight:900;color:var(--tp);text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:7px;}
.modal-title i{color:var(--gp);}
.modal-close{background:none;border:none;color:var(--tl);cursor:pointer;font-size:18px;transition:color .15s;min-width:var(--touch);min-height:var(--touch);display:flex;align-items:center;justify-content:center;touch-action:manipulation;}
.modal-close:hover{color:var(--tp);}
.modal-body{padding:16px;overflow-y:auto;-webkit-overflow-scrolling:touch;flex:1;}
.modal-foot{padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;padding-bottom:max(12px,env(safe-area-inset-bottom));}

/* ── ALERT / TOAST ── */
.alert{padding:10px 13px;border-radius:var(--r);font-size:12px;font-weight:600;margin-bottom:13px;display:flex;align-items:center;gap:7px;}
.alert-green{background:rgba(26,122,26,.15);border:1px solid rgba(26,122,26,.3);color:#3dd63d;}
.alert-red{background:rgba(192,57,43,.15);border:1px solid rgba(192,57,43,.3);color:#e74c3c;}
.alert-amber{background:rgba(212,112,10,.15);border:1px solid rgba(212,112,10,.3);color:#f39c12;}
.toast{position:fixed;bottom:calc(var(--bottom-nav-h) + 12px);left:50%;transform:translateX(-50%) translateY(10px);z-index:9999;padding:12px 18px;border-radius:var(--r);font-size:13px;font-weight:700;display:flex;align-items:center;gap:7px;box-shadow:0 8px 30px rgba(0,0,0,.6);opacity:0;transition:all .22s;pointer-events:none;min-width:220px;max-width:90vw;white-space:nowrap;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast-green{background:var(--gp);color:#fff;}
.toast-red{background:var(--red);color:#fff;}
.toast-amber{background:var(--amber);color:#fff;}

/* ── TOGGLE ── */
.toggle{position:relative;width:40px;height:22px;flex-shrink:0;display:inline-block;}
.toggle input{opacity:0;width:0;height:0;position:absolute;}
.toggle-track{position:absolute;inset:0;background:var(--border2);border-radius:100px;cursor:pointer;transition:background .2s;}
.toggle-track::before{content:'';position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.4);}
.toggle input:checked~.toggle-track{background:var(--gp);}
.toggle input:checked~.toggle-track::before{transform:translateX(18px);}

/* ── BOTTOM NAV ── */
.bottom-nav{
  display:none;
  position:fixed;bottom:0;left:0;right:0;
  background:var(--panel);border-top:1px solid var(--border);
  z-index:150;
  padding-bottom:env(safe-area-inset-bottom);
}
.bn-inner{display:flex;justify-content:space-around;align-items:center;height:var(--bottom-nav-h);}
.bn-item{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;flex:1;text-decoration:none;color:var(--tm);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;position:relative;min-height:var(--bottom-nav-h);-webkit-tap-highlight-color:transparent;transition:color .15s;background:none;border:none;cursor:pointer;font-family:'Barlow',sans-serif;touch-action:manipulation;}
.bn-item i{font-size:18px;}
.bn-item.active{color:var(--gl);}
.bn-badge{position:absolute;top:6px;right:calc(50% - 14px);background:var(--red);color:#fff;border-radius:100px;font-size:8px;font-weight:800;padding:1px 5px;min-width:14px;text-align:center;}

/* ── UTILS ── */
.flex{display:flex;}.fc{align-items:center;}.fb{justify-content:space-between;}
.gap-8{gap:8px;}.gap-10{gap:10px;}.gap-12{gap:12px;}
.mb-12{margin-bottom:12px;}.mb-16{margin-bottom:16px;}.mb-20{margin-bottom:20px;}
.text-green{color:var(--gl);}.text-red{color:var(--red);}.text-amber{color:var(--amber);}.muted{color:var(--tl);}
.w-full{width:100%;}
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px;}

/* ════════════════════════════
   MOBILE BREAKPOINTS
════════════════════════════ */
@media (max-width: 768px) {
  /* Sidebar slides off-screen; topbar goes full-width */
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .drawer-overlay{display:block;}

  /* On mobile the sidebar is hidden, so topbar spans full width */
  .main{margin-left:0;padding-top:var(--topbar-h);}
  .topbar{left:0;}

  .bottom-nav{display:block;}
  .tb-clock{display:none;}
  .tb-hamburger{display:flex;}

  .content{
    padding:12px;
    padding-bottom:calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 16px);
  }
  .stats-grid{grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;}
  .stat-card{padding:12px 13px;}
  .sc-val{font-size:22px;}
  .card{padding:12px;}
  .card-head{flex-direction:column;align-items:flex-start;gap:8px;}
  .form-grid-2,.form-grid-3{grid-template-columns:1fr;}
  table{font-size:12px;}
  thead th{font-size:8px;padding:7px 8px;}
  tbody td{padding:8px 8px;}
  .modal-overlay{align-items:flex-end;padding:0;}
  .modal,.modal-lg{border-radius:16px 16px 0 0;max-height:88vh;width:100%;max-width:100%;}
  .modal-head{position:relative;padding-top:20px;}
  .modal-head::before{content:'';display:block;width:36px;height:4px;background:var(--border2);border-radius:2px;position:absolute;top:8px;left:50%;transform:translateX(-50%);}
  .tb-chip{padding:6px 10px;font-size:11px;}
  .topbar{padding:0 12px;}
  .tb-title{font-size:16px;}
}

@media (max-width: 400px){
  .stats-grid{grid-template-columns:1fr 1fr;gap:6px;}
  .sc-val{font-size:20px;}
  .content{padding:10px;padding-bottom:calc(var(--bottom-nav-h) + env(safe-area-inset-bottom) + 16px);}
  .stat-card{padding:10px 11px;}
}
</style>
</head>
<body>

<div class="drawer-overlay" id="drawerOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon"><i class="fa-solid fa-seedling"></i></div>
    <div><div class="sb-logo-text">DollarTree</div><div class="sb-logo-sub">Admin Panel</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Overview</div>
    <a href="dashboard.php" class="nav-link <?php echo $active_nav==='dashboard'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <div class="sb-section">Users</div>
    <a href="users.php" class="nav-link <?php echo $active_nav==='users'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-users"></i> All Users</a>
    <a href="crawl_control.php" class="nav-link <?php echo $active_nav==='crawl'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-spider"></i> Crawl Control</a>
    <div class="sb-section">Finance</div>
    <a href="deposits.php" class="nav-link <?php echo $active_nav==='deposits'?'active':''; ?>" onclick="closeSidebar()">
      <i class="fa-solid fa-download"></i> Deposits
      <?php global $pdo; try{$pd=(int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status='pending'")->fetchColumn();if($pd>0)echo "<span class='nav-badge'>{$pd}</span>";}catch(Exception $e){} ?>
    </a>
    <a href="withdrawals.php" class="nav-link <?php echo $active_nav==='withdrawals'?'active':''; ?>" onclick="closeSidebar()">
      <i class="fa-solid fa-upload"></i> Withdrawals
      <?php try{$pw=(int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();if($pw>0)echo "<span class='nav-badge'>{$pw}</span>";}catch(Exception $e){} ?>
    </a>
    <a href="commissions.php" class="nav-link <?php echo $active_nav==='commissions'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-percent"></i> Commissions</a>
    <div class="sb-section">VIP System</div>
    <a href="svip_tiers.php" class="nav-link <?php echo $active_nav==='svip'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-crown"></i> SVIP Tiers</a>
    <div class="sb-section">Communication</div>
    <a href="announcements.php" class="nav-link <?php echo $active_nav==='announcements'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <a href="messages.php" class="nav-link <?php echo $active_nav==='messages'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-envelope"></i> User Messages</a>
    <div class="sb-section">System</div>
    <a href="activity_logs.php" class="nav-link <?php echo $active_nav==='logs'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</a>
    <a href="settings.php" class="nav-link <?php echo $active_nav==='settings'?'active':''; ?>" onclick="closeSidebar()"><i class="fa-solid fa-gear"></i> Settings</a>
  </nav>
  <div class="sb-footer">
    <div class="sb-admin">
      <div class="sb-avatar"><i class="fa-solid fa-shield-halved"></i></div>
      <div><div class="sb-name"><?php echo htmlspecialchars($_SESSION['admin_username']??'Admin'); ?></div><div class="sb-role">Super Admin</div></div>
      <a href="logout.php" class="sb-logout" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
</aside>

<!-- Bottom nav (mobile only) -->
<nav class="bottom-nav">
  <div class="bn-inner">
    <a href="dashboard.php" class="bn-item <?php echo $active_nav==='dashboard'?'active':''; ?>"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
    <a href="deposits.php" class="bn-item <?php echo $active_nav==='deposits'?'active':''; ?>">
      <i class="fa-solid fa-download"></i><span>Deposits</span>
      <?php try{$pd=(int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status='pending'")->fetchColumn();if($pd>0)echo "<span class='bn-badge'>{$pd}</span>";}catch(Exception $e){} ?>
    </a>
    <a href="withdrawals.php" class="bn-item <?php echo $active_nav==='withdrawals'?'active':''; ?>">
      <i class="fa-solid fa-upload"></i><span>Withdraw</span>
      <?php try{$pw=(int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();if($pw>0)echo "<span class='bn-badge'>{$pw}</span>";}catch(Exception $e){} ?>
    </a>
    <a href="users.php" class="bn-item <?php echo $active_nav==='users'?'active':''; ?>"><i class="fa-solid fa-users"></i><span>Users</span></a>
  </div>
</nav>

<div class="main">
  <header class="topbar">
    <div class="tb-left">
      <button class="tb-hamburger" onclick="openSidebar()" aria-label="Open menu">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="tb-title"><?php echo htmlspecialchars($page_title); ?></div>
    </div>
    <div class="tb-right">
      <?php echo $topbar_actions; ?>
      <span class="tb-chip tb-clock"><i class="fa-regular fa-clock"></i> <?php echo date('M d, H:i'); ?></span>
    </div>
  </header>
  <div class="content">

<div class="toast" id="__toast"></div>
<script>
function showToast(msg,type='green'){
  const t=document.getElementById('__toast');
  t.className='toast toast-'+type+' show';
  const ic=type==='green'?'circle-check':type==='red'?'circle-xmark':'triangle-exclamation';
  t.innerHTML=`<i class="fa-solid fa-${ic}"></i> ${msg}`;
  clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('show'),3400);
}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

function openSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
  document.body.style.overflow='';
}

/* Swipe left on sidebar to close */
(function(){
  let sx=0,sy=0;
  const sb=document.getElementById('sidebar');
  sb.addEventListener('touchstart',e=>{sx=e.touches[0].clientX;sy=e.touches[0].clientY;},{passive:true});
  sb.addEventListener('touchend',e=>{
    const dx=e.changedTouches[0].clientX-sx;
    const dy=Math.abs(e.changedTouches[0].clientY-sy);
    if(dx<-40&&dy<30)closeSidebar();
  },{passive:true});
})();

document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){
    document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'));
    closeSidebar();
  }
});
</script>

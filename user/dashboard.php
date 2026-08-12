<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
if (!$user || !is_array($user)) { session_destroy(); header('Location: login.php'); exit; }
$page_title = t('home','Home');
$svip_tier = get_svip_tier($user['svip_level']);

global $pdo;
$stmt = $pdo->query("SELECT * FROM svip_tiers ORDER BY svip_level ASC");
$tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$spin_chances = (int)($user['spin_chances'] ?? 0);
include __DIR__ . '/spin_wheel.php';
?>
<!DOCTYPE html>
<html lang="<?php echo get_language();?>">
<head>
<style>html.fonts-loading body{visibility:hidden}</style><script>document.documentElement.classList.add('fonts-loading');(function(){var r=function(){document.documentElement.classList.remove('fonts-loading')};if(document.fonts&&document.fonts.ready){Promise.race([document.fonts.ready,new Promise(function(x){setTimeout(x,1800)})]).then(r,r)}else{window.addEventListener('load',r,{once:true})}})();</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title><?php echo $page_title;?> — Dollar Tree</title>
<link rel="icon" type="image/jpeg" href="/user/images/tree.jpg?v=20260812">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --gp:#1a7a1a;--gb:#22a322;--gl:#2db82d;
  --gg:rgba(26,122,26,0.10);--gg2:rgba(26,122,26,0.06);
  --bg:#f0f5f0;--white:#fff;
  --border:#d4ebd4;--border2:#b8d8b8;
  --tp:#0d1a0d;--ts:#2d4a2d;--tm:#5a7a5a;--tl:#8aaa8a;
  --gold:#c8860a;--gold-bg:#fef9ed;--gold-bdr:#f0c060;
  --blue:#1a6ab8;
  --red:#e02020;
  --amber:#d4700a;--amber2:#f0a030;
  --card-active:#edfaed;
  --nav-h:64px;--top-h:60px;
  --r:14px;--r2:18px;
  --sh:0 2px 10px rgba(0,0,0,0.06);
  --shm:0 4px 20px rgba(0,0,0,0.09);
  --shl:0 8px 32px rgba(0,0,0,0.12);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{
  font-family:'Barlow',sans-serif;
  background:var(--bg);color:var(--tp);
  min-height:100vh;
  padding-top:var(--top-h);
  padding-bottom:calc(var(--nav-h)+24px);
  overflow-x:hidden;width:100%;
  -webkit-font-smoothing:antialiased;
}

/* ─── TOPBAR ─── */
.topbar{
  position:fixed;top:0;left:0;right:0;z-index:400;
  height:var(--top-h);background:var(--white);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;box-shadow:var(--sh);
}
.tb-left{display:flex;align-items:center;gap:10px;min-width:0;flex:1;}
.tb-logo{height:20px;width:auto;object-fit:contain;flex-shrink:0; scale:1.2;margin:0px 5px;}
.tb-div{width:1px;height:20px;background:var(--border2);flex-shrink:0;}
.tb-greet{line-height:1.2;min-width:0;}
.tb-top{font-size:10px;color:var(--tl);font-weight:600;text-transform:uppercase;letter-spacing:.06em;}
.tb-name{font-size:13px;font-weight:800;color:var(--gp);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px;}
.tb-right{display:flex;align-items:center;gap:7px;flex-shrink:0;}
.tb-btn{
  width:36px;height:36px;background:var(--bg);
  border:1px solid var(--border);border-radius:10px;
  color:var(--tm);font-size:14px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;text-decoration:none;
  transition:all .18s;position:relative;flex-shrink:0;
}
.tb-btn:hover{border-color:var(--border2);color:var(--gp);background:var(--gg);}

.notif-badge{
  display:none;
  position:absolute;
  top:-6px; right:-6px;
  min-width:18px; height:18px;
  padding:0 4px;
  background:var(--red);
  color:#fff;
  border-radius:100px;
  font-size:10px; font-weight:800; line-height:1;
  align-items:center; justify-content:center;
  border:2px solid var(--white);
  pointer-events:none;
  animation:badgePop .3s cubic-bezier(.34,1.56,.64,1);
  z-index:2;
}
@keyframes badgePop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}

/* ─── NOTIFICATION MODAL ─── */
.n-overlay{position:fixed;inset:0;z-index:600;background:rgba(0,0,0,0.45);display:none;align-items:flex-start;justify-content:center;padding-top:var(--top-h);}
.n-overlay.open{display:flex;animation:fadein .18s ease;}
@keyframes fadein{from{opacity:0}to{opacity:1}}
.n-modal{width:100%;max-width:480px;background:var(--white);border-radius:0 0 var(--r2) var(--r2);overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,.20);animation:slidedown .22s cubic-bezier(.34,1.3,.64,1);max-height:calc(100vh - var(--top-h) - 20px);display:flex;flex-direction:column;}
@keyframes slidedown{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)}}
.nm-head{padding:14px 16px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.nm-title{font-size:15px;font-weight:800;color:var(--tp);display:flex;align-items:center;gap:8px;}
.nm-title i{color:var(--gp);}

.nm-total-badge{
  display:none;
  background:var(--red);
  color:#fff;
  border-radius:100px;
  font-size:10px; font-weight:800;
  padding:2px 7px; line-height:1.4;
  margin-left:2px;
  animation:badgePop .3s cubic-bezier(.34,1.56,.64,1);
}

.nm-head-actions{display:flex;align-items:center;gap:8px;}
.nm-x{width:32px;height:32px;border-radius:8px;background:var(--bg);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--tm);transition:all .18s;}
.nm-x:hover{background:var(--gg);color:var(--gp);}
.nm-mark-all-btn{
  height:32px;padding:0 11px;
  background:var(--bg);border:1px solid var(--border);border-radius:8px;
  font-family:'Barlow',sans-serif;font-size:11px;font-weight:700;color:var(--tl);
  cursor:pointer;display:flex;align-items:center;gap:5px;
  transition:all .18s;white-space:nowrap;
}
.nm-mark-all-btn:hover{border-color:var(--border2);color:var(--ts);}

.nm-tabs{display:flex;border-bottom:1px solid var(--border);flex-shrink:0;overflow-x:auto;scrollbar-width:none;}
.nm-tabs::-webkit-scrollbar{display:none;}

.nm-tab{
  flex:1;min-width:80px;
  padding:10px 6px 9px;
  border:none;border-bottom:2px solid transparent;
  background:transparent;
  font-family:'Barlow',sans-serif;font-size:10px;font-weight:700;
  color:var(--tl);cursor:pointer;
  text-transform:uppercase;letter-spacing:.04em;
  transition:all .18s;white-space:nowrap;
  display:flex;align-items:center;justify-content:center;gap:5px;
}
.nm-tab.active{color:var(--gp);border-bottom-color:var(--gp);}
.nm-tab:hover:not(.active){color:var(--ts);}

.tab-badge{
  display:none;
  align-items:center;justify-content:center;
  min-width:16px; height:16px;
  padding:0 4px;
  background:var(--red);
  color:#fff;
  border-radius:100px;
  font-size:9px; font-weight:800; line-height:1;
  flex-shrink:0;
  animation:badgePop .25s cubic-bezier(.34,1.56,.64,1);
}

.nm-body{overflow-y:auto;flex:1;}
.nm-pane{display:none;}

/* ─── NOTIFICATION ITEMS ─── */
.nm-item{
  display:block;
  padding:12px 16px;
  border-bottom:1px solid var(--border);
  transition:background .15s;
  border-left:3px solid transparent;
  cursor:default;
}
.nm-item:nth-child(odd){background:#f7fbf7;}
.nm-item:nth-child(even){background:var(--white);}
.nm-item:hover{background:#eef6ee !important;}
.nm-item:last-child{border-bottom:none;}
.nm-item.is-unread{border-left-color:var(--red);}
.nm-item.is-unread:nth-child(odd){background:#fff5f5;}
.nm-item.is-unread:nth-child(even){background:#fff8f8;}
.nm-item.is-unread:hover{background:#fff0f0 !important;}
.nm-item-head{display:flex;align-items:center;gap:9px;margin-bottom:5px;}
.nm-ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
.nm-ico.g{background:rgba(26,122,26,.10);color:var(--gp);}
.nm-ico.o{background:rgba(200,134,10,.10);color:var(--gold);}
.nm-ico.b{background:rgba(26,106,184,.09);color:var(--blue);}
.nm-ico.r{background:rgba(192,57,43,.09);color:#c0392b;}
.nm-ico.teal{background:rgba(0,150,136,.10);color:#00897b;}
.nm-item-title{font-size:13px;font-weight:700;color:var(--tp);display:flex;align-items:center;gap:6px;flex:1;min-width:0;flex-wrap:wrap;}
.nm-item-body{font-size:12px;color:var(--tm);line-height:1.55;padding-left:0;margin-bottom:4px;}
.nm-item-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;}
.nm-time{font-size:10px;color:var(--tl);font-weight:600;}
.nm-status{display:inline-flex;align-items:center;gap:3px;border-radius:100px;padding:2px 8px;font-size:10px;font-weight:700;}
.nm-status.pending  {background:rgba(200,134,10,.12);color:var(--gold);}
.nm-status.completed{background:rgba(26,122,26,.12);color:var(--gp);}
.nm-status.failed   {background:rgba(192,57,43,.10);color:#c0392b;}
.nm-status.rejected {background:rgba(192,57,43,.10);color:#c0392b;}
.nm-amount{font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:800;color:var(--tp);}
.nm-amount.credit{color:var(--gp);}
.nm-amount.debit  {color:#c0392b;}
.nm-new-pill{display:inline-flex;align-items:center;background:var(--red);color:#fff;border-radius:100px;font-size:9px;font-weight:800;padding:1px 6px;letter-spacing:.04em;flex-shrink:0;}
.nm-empty{padding:36px 20px;text-align:center;color:var(--tl);font-size:13px;}
.nm-empty i{font-size:30px;display:block;margin-bottom:10px;opacity:.35;}

/* ─── HERO ─── */
.hero{margin:16px 16px 0;border-radius:var(--r2);background:linear-gradient(145deg,var(--gp) 0%,#0f5c0f 100%);box-shadow:var(--shl);overflow:hidden;position:relative;}
.hero::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 80% 60% at 88% 108%,rgba(255,255,255,.07) 0%,transparent 55%),radial-gradient(ellipse 60% 50% at 10% -10%,rgba(255,255,255,.10) 0%,transparent 55%);}
.hero-dots{position:absolute;inset:0;pointer-events:none;opacity:.13;background-image:radial-gradient(circle,rgba(255,255,255,.6) 1px,transparent 1px);background-size:22px 22px;}
.hero-in{position:relative;z-index:2;padding:20px 18px 18px;}
.hero-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.hero-lbl{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.08em;}
.hero-lbl i{color:rgba(255,255,255,.75);}
.hero-pill{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:4px 11px;font-size:11px;font-weight:700;color:rgba(255,255,255,.9);}
.hero-pill i{color:#7be87b;font-size:10px;}
.hero-bal{font-family:'Barlow Condensed',sans-serif;font-size:50px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1;margin-bottom:3px;}
.hero-bal .dec{font-size:28px;color:rgba(255,255,255,.55);}
.hero-curr{font-size:11px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:15px;}
.hero-stats{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;}
.hst{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.10);border-radius:11px;padding:11px 13px;}
.hst-l{font-size:10px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;}
.hst-v{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:800;color:#fff;line-height:1;}
.hst-v .u{font-size:11px;color:rgba(255,255,255,.38);font-weight:600;margin-left:2px;}
.hero-vip{display:inline-flex;align-items:center;gap:7px;background:rgba(240,192,64,.18);border:1px solid rgba(240,192,64,.35);border-radius:100px;padding:6px 14px;font-size:11px;font-weight:800;color:#f5d060;text-transform:uppercase;letter-spacing:.06em;}
.hero-vip i{font-size:11px;}

/* ─── SECTIONS ─── */
.sec{padding:16px 16px 0;}
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.sec-ttl{font-size:11px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.09em;display:flex;align-items:center;gap:6px;}
.sec-ttl i{color:var(--gp);font-size:11px;}

/* ─── QUICK ACTIONS ─── */
.act-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.act-item{background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:14px 6px 12px;display:flex;flex-direction:column;align-items:center;gap:8px;text-decoration:none;color:inherit;box-shadow:var(--sh);transition:all .18s;cursor:pointer;}
.act-item:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:var(--shm);}
.act-item:active{transform:none;}
.act-ic{width:46px;height:46px;background:var(--gg2);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--gp);transition:all .18s;}
.act-item:hover .act-ic{background:var(--gg);border-color:var(--border2);}
.act-lbl{font-size:10px;font-weight:700;color:var(--ts);text-align:center;text-transform:uppercase;letter-spacing:.04em;}

/* ─── REFERRAL ─── */
.ref-card{margin:16px 16px 0;background:var(--white);border:1px solid var(--gold-bdr);border-radius:var(--r2);padding:18px 16px;box-shadow:var(--shm);position:relative;overflow:hidden;}
.ref-card::before{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(200,134,10,.07) 0%,transparent 70%);pointer-events:none;}
.ref-top{display:flex;align-items:center;gap:10px;margin-bottom:4px;}
.ref-ic{width:34px;height:34px;flex-shrink:0;background:var(--gold-bg);border:1px solid var(--gold-bdr);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--gold);}
.ref-ttl{font-size:14px;font-weight:800;color:var(--tp);}
.ref-sub{font-size:12px;color:var(--tm);margin-bottom:13px;line-height:1.5;}
.ref-row{display:flex;gap:9px;align-items:stretch;}
.ref-box{flex:1;background:var(--gold-bg);border:1px solid var(--gold-bdr);border-radius:10px;padding:10px 13px;font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:800;color:var(--gold);letter-spacing:1px;display:flex;align-items:center;min-width:0;}
.ref-box span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ref-copy{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:0 15px;font-family:'Barlow',sans-serif;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;transition:all .18s;box-shadow:0 3px 12px rgba(200,134,10,.25);}
.ref-copy:hover{background:#b5780a;transform:translateY(-1px);}
.ref-copy:active{transform:none;}
.ref-copy.copied{background:var(--gp);}
.ref-copy i{font-size:12px;}

/* ─── TOAST ─── */
.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:500;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.err{background:#c0392b;}

/* ─── BOTTOM NAV ─── */
.bnav{position:fixed;bottom:0;left:0;right:0;z-index:300;height:var(--nav-h);background:var(--white);border-top:1px solid var(--border);display:flex;align-items:stretch;box-shadow:0 -2px 16px rgba(0,0,0,.07);}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0 4px;text-decoration:none;color:var(--tl);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;transition:all .18s;position:relative;cursor:pointer;}
.nav-item::before{content:'';position:absolute;top:0;left:25%;right:25%;height:2px;border-radius:0 0 3px 3px;background:var(--gp);transform:scaleX(0);transform-origin:center;transition:transform .2s;}
.nav-item.active{color:var(--gp);}
.nav-item.active::before{transform:scaleX(1);}
.nav-item:hover{color:var(--ts);}
.nav-item.active:hover{color:var(--gp);}
.ni{font-size:20px;transition:transform .18s;}
.nav-item.active .ni{transform:scale(1.1);}

.spacer{height:10px;}
.foot{padding-bottom:100px;}

/* ─── FLOATING SIDE MENU ─── */
.float-menu{position:fixed;right:0;top:50%;z-index:350;transform:translateY(-50%);display:flex;flex-direction:column;background:var(--white);border:1px solid var(--border);border-right:none;border-radius:12px 0 0 12px;box-shadow:-4px 0 20px rgba(0,0,0,.10);overflow:hidden;}
.float-btn{width:46px;height:46px;display:flex;align-items:center;justify-content:center;background:var(--white);border:none;color:var(--tm);font-size:18px;cursor:pointer;transition:all .18s;position:relative;border-bottom:1px solid var(--border);}
.float-btn:last-child{border-bottom:none;}
.float-btn:hover{background:var(--gg);color:var(--gp);}
.float-btn .fbadge{position:absolute;top:6px;right:6px;min-width:16px;height:16px;padding:0 4px;background:var(--gold);color:#fff;border-radius:8px;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--white);line-height:1;}
.float-btn .fbadge.zero{background:var(--tl);}

/* ─── SPINNER MODAL ─── */
.fm-overlay{position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:20px;}
.fm-overlay.open{display:flex;animation:fadein .2s ease;}
.spin-modal{width:100%;max-width:360px;background:linear-gradient(160deg,#1a1a2e 0%,#0d3b0d 60%,#1a3a1a 100%);border-radius:24px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.45);animation:popIn .25s cubic-bezier(.34,1.56,.64,1);position:relative;}
.spin-sparkles{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
.spark{position:absolute;border-radius:50%;animation:sparkle 3s infinite;}
.spark:nth-child(1){width:4px;height:4px;background:#f5d060;top:15%;left:20%;animation-delay:0s;}
.spark:nth-child(2){width:3px;height:3px;background:#7be87b;top:25%;right:18%;animation-delay:.4s;}
.spark:nth-child(3){width:5px;height:5px;background:#f5d060;bottom:30%;left:15%;animation-delay:.8s;}
.spark:nth-child(4){width:3px;height:3px;background:#fff;top:60%;right:22%;animation-delay:1.2s;}
.spark:nth-child(5){width:4px;height:4px;background:#7be87b;bottom:20%;right:12%;animation-delay:1.6s;}
@keyframes sparkle{0%,100%{opacity:0;transform:scale(0)}50%{opacity:1;transform:scale(1)}}
.spin-head{padding:18px 18px 0;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:2;}
.spin-title{font-size:15px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;}
.spin-title i{color:var(--gold);}
.spin-x{width:32px;height:32px;border-radius:9px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.7);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .18s;}
.spin-x:hover{background:rgba(255,255,255,.2);color:#fff;}
.spin-chances-row{text-align:center;padding:10px 18px 0;position:relative;z-index:2;}
.spin-chances-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(240,192,64,.15);border:1px solid rgba(240,192,64,.3);border-radius:100px;padding:5px 14px;font-size:12px;font-weight:700;color:#f5d060;}
.spin-chances-pill i{font-size:11px;}
.wheel-wrap{display:flex;align-items:center;justify-content:center;padding:20px 18px 10px;position:relative;z-index:2;}
.wheel-outer{position:relative;width:240px;height:240px;flex-shrink:0;}
.wheel-pointer{position:absolute;top:-12px;left:50%;transform:translateX(-50%);width:0;height:0;z-index:10;border-left:10px solid transparent;border-right:10px solid transparent;border-top:22px solid var(--gold);filter:drop-shadow(0 2px 6px rgba(200,134,10,.6));}
.wheel-ring{position:absolute;inset:-6px;border-radius:50%;background:conic-gradient(#f5d060 0deg,#e8b830 45deg,#f5d060 45deg,#e8b830 90deg,#f5d060 90deg,#e8b830 135deg,#f5d060 135deg,#e8b830 180deg,#f5d060 180deg,#e8b830 225deg,#f5d060 225deg,#e8b830 270deg,#f5d060 270deg,#e8b830 315deg,#f5d060 315deg,#e8b830 360deg);box-shadow:0 0 20px rgba(240,192,64,.5),0 0 40px rgba(240,192,64,.2);animation:ringPulse 2s ease-in-out infinite;}
@keyframes ringPulse{0%,100%{box-shadow:0 0 18px rgba(240,192,64,.5),0 0 36px rgba(240,192,64,.2)}50%{box-shadow:0 0 28px rgba(240,192,64,.7),0 0 56px rgba(240,192,64,.35)}}
canvas#spinWheel{position:relative;z-index:2;width:240px;height:240px;border-radius:50%;}
.wheel-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;width:56px;height:56px;border-radius:50%;background:radial-gradient(135deg,#f5d060 0%,var(--gold) 60%,#8a5c00 100%);border:3px solid rgba(255,255,255,.3);box-shadow:0 4px 16px rgba(0,0,0,.4),inset 0 1px 2px rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .18s,box-shadow .18s;font-family:'Barlow Condensed',sans-serif;font-size:11px;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:.04em;text-align:center;line-height:1.2;}
.wheel-center:hover{transform:translate(-50%,-50%) scale(1.06);box-shadow:0 6px 22px rgba(0,0,0,.5);}
.wheel-center:active{transform:translate(-50%,-50%) scale(.97);}
.wheel-center.spinning{pointer-events:none;opacity:.8;}
.spin-footer{background:rgba(0,0,0,.25);border-top:1px solid rgba(255,255,255,.07);padding:12px 18px;position:relative;z-index:2;}
.spin-invite-note{font-size:11px;color:rgba(255,255,255,.45);text-align:center;line-height:1.6;display:flex;align-items:flex-start;gap:6px;}
.spin-invite-note i{color:#f5d060;flex-shrink:0;margin-top:1px;}
.win-overlay{position:absolute;inset:0;z-index:20;background:rgba(0,0,0,.85);display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;border-radius:24px;animation:fadein .3s ease;}
.win-overlay.show{display:flex;}
.win-emoji{font-size:48px;animation:bounce .5s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-10px)}}
.win-title{font-size:22px;font-weight:900;color:#f5d060;font-family:'Barlow Condensed',sans-serif;letter-spacing:.04em;}
.win-amount{font-family:'Barlow Condensed',sans-serif;font-size:48px;font-weight:900;color:#fff;line-height:1;}
.win-sub{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}
.win-claim{margin-top:8px;background:linear-gradient(135deg,#f5d060 0%,var(--gold) 100%);color:#fff;border:none;border-radius:12px;padding:13px 32px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .18s;box-shadow:0 4px 16px rgba(200,134,10,.4);}
.win-claim:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(200,134,10,.5);}
.sp-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-align:center;padding:0 18px 4px;position:relative;z-index:2;}

/* ─── SUPPORT MODAL ─── */
.sup-modal{width:100%;max-width:380px;background:var(--white);border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.2);animation:popIn .25s cubic-bezier(.34,1.56,.64,1);}
.sup-head{background:linear-gradient(135deg,var(--gp) 0%,#0f5c0f 100%);padding:20px 18px 18px;position:relative;overflow:hidden;}
.sup-head::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 80% 80% at 90% 120%,rgba(255,255,255,.08) 0%,transparent 55%);}
.sup-head-row{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:2;}
.sup-ttl{font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;}
.sup-ttl i{color:rgba(255,255,255,.7);}
.sup-x{width:32px;height:32px;border-radius:9px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .18s;}
.sup-x:hover{background:rgba(255,255,255,.25);color:#fff;}
.sup-sub{font-size:12px;color:rgba(255,255,255,.55);margin-top:6px;position:relative;z-index:2;}
.sup-status{display:inline-flex;align-items:center;gap:5px;background:rgba(123,232,123,.15);border:1px solid rgba(123,232,123,.3);border-radius:100px;padding:4px 11px;font-size:11px;font-weight:700;color:#7be87b;margin-top:10px;position:relative;z-index:2;}
.sup-dot{width:7px;height:7px;border-radius:50%;background:#7be87b;animation:pulse 1.5s ease-in-out infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)}}
.sup-body{padding:20px 18px;}
.sup-desc{font-size:13px;color:var(--tm);line-height:1.7;margin-bottom:18px;}
.sup-channel{display:flex;align-items:center;gap:13px;background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:14px 16px;text-decoration:none;color:inherit;transition:all .2s;cursor:pointer;margin-bottom:10px;}
.sup-channel:hover{border-color:var(--border2);background:#f0f8f0;transform:translateX(3px);}
.sup-ch-ic{width:44px;height:44px;border-radius:12px;flex-shrink:0;background:linear-gradient(135deg,#2aabee 0%,#229ed9 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(42,171,238,.3);}
.sup-ch-ic img,.sup-ch-ic svg{width:24px;height:24px;}
.sup-ch-info{flex:1;min-width:0;}
.sup-ch-name{font-size:14px;font-weight:800;color:var(--tp);margin-bottom:2px;}
.sup-ch-sub{font-size:11px;color:var(--tl);}
.sup-ch-arr{color:var(--tl);font-size:14px;flex-shrink:0;}
.sup-channel:hover .sup-ch-arr{color:var(--gp);}
.sup-hours{display:flex;align-items:center;gap:8px;background:rgba(26,122,26,.06);border:1px solid var(--border);border-radius:10px;padding:10px 14px;font-size:11px;color:var(--tm);font-weight:600;margin-top:4px;}
.sup-hours i{color:var(--gp);font-size:13px;flex-shrink:0;}

/* ─── VIP TIERS ON DASHBOARD ─── */
.tiers-sec{padding:16px 16px 0;}
.tiers-list{
  display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;
  padding:2px 2px 10px;scrollbar-width:thin;scrollbar-color:var(--border2) transparent;
}
.tier-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r2);margin-bottom:0;flex:0 0 min(380px,calc(100vw - 38px));
  scroll-snap-align:start;
  box-shadow:var(--sh);overflow:hidden;position:relative;
}
.tier-card.is-active{border-color:var(--gl);background:var(--card-active);}
.tier-card.is-current{border-color:var(--gb);border-width:2px;}
.step-ribbon{position:absolute;top:0;right:0;z-index:3;width:52px;height:52px;overflow:hidden;pointer-events:none;}
.step-ribbon span{
  position:absolute;top:10px;right:-14px;width:68px;text-align:center;
  background:var(--amber);color:#fff;font-size:10px;font-weight:800;
  letter-spacing:.04em;padding:3px 0;transform:rotate(45deg);
  box-shadow:0 2px 4px rgba(0,0,0,.15);
}
.tier-card.is-active .step-ribbon span{background:var(--gp);}
.tc-top{display:flex;align-items:center;gap:12px;padding:14px 14px 0;}
.tc-img{width:52px;height:52px;flex-shrink:0;border-radius:12px;overflow:hidden;background:#e8f5e8;border:1px solid var(--border);}
.tc-img img{width:100%;height:100%;object-fit:contain;}
.tc-meta{flex:1;min-width:0;}
.tc-name{font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:800;color:var(--tp);margin-bottom:2px;}
.tc-eff{font-size:11px;color:var(--tl);display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
.tc-eff i{font-size:10px;color:var(--gl);}
.tc-eff .eff-val{color:var(--ts);font-weight:600;}
.tc-status{flex-shrink:0;}
.badge-active{display:inline-flex;align-items:center;gap:5px;background:rgba(26,122,26,.12);border:1px solid rgba(26,122,26,.25);border-radius:100px;padding:4px 10px;font-size:11px;font-weight:700;color:var(--gp);}
.badge-active i{font-size:10px;}
.tc-stats{display:grid;grid-template-columns:1fr 1fr;gap:1px;margin:12px 14px 0;background:var(--border);border-radius:10px;overflow:hidden;}
.tc-stat{background:var(--white);padding:10px 12px;}
.tier-card.is-active .tc-stat{background:var(--card-active);}
.tc-stat-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;}
.tc-stat-val{font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:800;color:var(--tp);line-height:1;}
.tc-stat-val.green{color:var(--gp);}
.tc-stat-val .u{font-size:11px;color:var(--tl);font-weight:600;margin-left:1px;}
.tc-foot{padding:12px 14px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.tc-need{font-size:12px;font-weight:700;color:var(--tm);}
.tc-need strong{color:var(--gp);}
.tc-need.amber-need strong{color:var(--amber);}
.btn-unlock{
  display:flex;align-items:center;gap:7px;background:var(--gp);color:#fff;border:none;
  border-radius:100px;padding:9px 18px;font-family:'Barlow',sans-serif;
  font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;
  cursor:pointer;transition:all .18s;white-space:nowrap;
  box-shadow:0 3px 12px rgba(26,122,26,.28);
}
.btn-unlock:hover{background:var(--gb);transform:translateY(-1px);box-shadow:0 5px 16px rgba(26,122,26,.35);}
.btn-unlock:active{transform:none;}
.btn-unlock:disabled{background:#b8d8b8;cursor:not-allowed;box-shadow:none;transform:none;}
.btn-current{
  display:flex;align-items:center;gap:7px;background:rgba(26,122,26,.10);
  color:var(--gp);border:1px solid rgba(26,122,26,.22);border-radius:100px;
  padding:9px 16px;font-family:'Barlow',sans-serif;font-size:12px;font-weight:800;
  text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;
}
.btn-current i{font-size:12px;}
.btn-done{
  display:flex;align-items:center;gap:7px;background:#f0f5f0;color:var(--tl);
  border:1px solid var(--border);border-radius:100px;padding:9px 16px;
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;
  text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;
}

/* ─── INSUFFICIENT BALANCE SHEET ─── */
.recharge-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.5);display:none;align-items:flex-end;justify-content:center;padding:0;}
.recharge-overlay.open{display:flex;animation:fadein .18s ease;}
.recharge-sheet{background:var(--white);border-radius:20px 20px 0 0;width:100%;max-width:480px;overflow:hidden;box-shadow:0 -10px 40px rgba(0,0,0,.2);animation:slideup .25s cubic-bezier(.34,1.56,.64,1);}
@keyframes slideup{from{transform:translateY(60px);opacity:0}to{transform:translateY(0);opacity:1}}
.rs-handle{width:40px;height:4px;background:var(--border2);border-radius:2px;margin:12px auto 0;}
.rs-head{padding:20px 20px 0;text-align:center;}
.rs-icon{width:64px;height:64px;background:linear-gradient(135deg,rgba(212,112,10,.15),rgba(212,112,10,.05));border:2px solid rgba(212,112,10,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--amber);margin:0 auto 14px;}
.rs-title{font-size:18px;font-weight:800;color:var(--tp);margin-bottom:6px;}
.rs-body{padding:12px 20px 0;font-size:13px;color:var(--tm);text-align:center;line-height:1.65;}
.rs-amounts{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 20px 0;padding:14px;background:var(--bg);border-radius:12px;border:1px solid var(--border);}
.rs-amt-row{display:flex;flex-direction:column;gap:2px;}
.rs-amt-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.06em;}
.rs-amt-val{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:900;}
.rs-amt-val.green{color:var(--gp);}
.rs-need{margin:10px 20px 0;background:rgba(212,112,10,.08);border:1px solid rgba(212,112,10,.2);border-radius:10px;padding:10px 14px;font-size:12px;color:var(--amber);font-weight:600;text-align:center;}
.rs-need strong{font-weight:800;}
.rs-foot{display:flex;gap:10px;padding:18px 20px 28px;}
.rs-cancel{flex:1;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:12px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:var(--tm);cursor:pointer;transition:all .18s;}
.rs-cancel:hover{border-color:var(--border2);}
.rs-recharge{flex:2;padding:12px;background:var(--gp);border:none;border-radius:12px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;color:#fff;cursor:pointer;transition:all .18s;box-shadow:0 3px 12px rgba(26,122,26,.3);display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;}
.rs-recharge:hover{background:var(--gb);}

/* ─── CONFIRM MODAL ─── */
.confirm-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;padding:20px;}
.confirm-overlay.open{display:flex;}
.confirm-modal{background:var(--white);border-radius:var(--r2);width:100%;max-width:360px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.cm-head{padding:20px 20px 0;text-align:center;}
.cm-icon{width:56px;height:56px;background:var(--gg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--gp);margin:0 auto 12px;}
.cm-title{font-size:17px;font-weight:800;color:var(--tp);margin-bottom:6px;}
.cm-body{padding:14px 20px 0;font-size:13px;color:var(--tm);text-align:center;line-height:1.6;}
.cm-amount{font-family:'Barlow Condensed',sans-serif;font-size:24px;font-weight:900;color:var(--gp);display:block;margin:8px 0 4px;}
.cm-foot{display:flex;gap:10px;padding:18px 20px 20px;}
.cm-cancel{flex:1;padding:11px;background:var(--bg);border:1px solid var(--border);border-radius:10px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:var(--tm);cursor:pointer;transition:all .18s;}
.cm-cancel:hover{border-color:var(--border2);}
.cm-confirm{flex:1;padding:11px;background:var(--gp);border:none;border-radius:10px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;color:#fff;cursor:pointer;transition:all .18s;box-shadow:0 3px 12px rgba(26,122,26,.3);}
.cm-confirm:hover{background:var(--gb);}

/* ── Daily Announcement Modal ── */
.da-overlay{
  position:fixed;inset:0;z-index:800;
  background:rgba(0,0,0,.6);
  display:flex;align-items:center;justify-content:center;
  padding:20px;
  opacity:0;pointer-events:none;
  transition:opacity .28s ease;
}
.da-overlay.open{opacity:1;pointer-events:all;}

.da-modal{
  background:var(--white);
  border-radius:20px;
  width:100%;max-width:400px;
  overflow:hidden;
  box-shadow:0 28px 72px rgba(0,0,0,.28),0 8px 24px rgba(0,0,0,.14);
  transform:translateY(20px) scale(.97);
  opacity:0;
  transition:transform .35s cubic-bezier(.34,1.3,.64,1), opacity .28s ease;
  position:relative;
}
.da-overlay.open .da-modal{transform:translateY(0) scale(1);opacity:1;}

/* green top bar — matches brand */
.da-topbar{
  height:4px;
  background:linear-gradient(90deg, var(--gp) 0%, var(--gl) 50%, #7be87b 100%);
}

/* header */
.da-head{
  padding:18px 18px 0;
  display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
}
.da-head-inner{display:flex;align-items:flex-start;gap:11px;}
.da-ic{
  width:42px;height:42px;flex-shrink:0;
  border-radius:12px;
  background:var(--gg);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--gp);
}
.da-label{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--gg2);border:1px solid var(--border);
  border-radius:100px;padding:2px 9px;
  font-size:9px;font-weight:800;color:var(--gp);
  text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;
}
.da-title{
  font-family:'Barlow Condensed',sans-serif;
  font-size:20px;font-weight:800;
  color:var(--tp);line-height:1.2;
}
.da-x{
  width:30px;height:30px;flex-shrink:0;
  border-radius:8px;
  background:var(--bg);border:1px solid var(--border);
  color:var(--tl);font-size:13px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:all .18s;margin-top:2px;
}
.da-x:hover{background:var(--gg);border-color:var(--border2);color:var(--gp);}

/* divider */
.da-hr{height:1px;background:var(--border);margin:14px 18px 0;}

/* body */
.da-body{
  padding:14px 18px 0;
  font-size:13px;color:var(--ts);
  line-height:1.7;
  max-height:240px;overflow-y:auto;
  scrollbar-width:thin;scrollbar-color:var(--border2) transparent;
}
.da-body::-webkit-scrollbar{width:3px;}
.da-body::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px;}

/* footer */
.da-footer{padding:16px 18px 22px;display:flex;flex-direction:column;gap:9px;}

/* CTA button — green brand */
.da-cta{
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:var(--gp);color:#fff;
  border:none;border-radius:100px;padding:13px 22px;
  font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;
  text-transform:uppercase;letter-spacing:.04em;
  text-decoration:none;cursor:pointer;width:100%;
  box-shadow:0 4px 14px rgba(26,122,26,.3);
  transition:all .18s;
}
.da-cta:hover{background:var(--gb);transform:translateY(-1px);box-shadow:0 6px 18px rgba(26,122,26,.38);}
.da-cta i{font-size:11px;}

/* dismiss */
.da-dismiss{
  background:transparent;border:none;
  font-family:'Barlow',sans-serif;font-size:11px;font-weight:700;
  color:var(--tl);cursor:pointer;padding:4px;
  text-align:center;transition:color .15s;width:100%;
}
.da-dismiss:hover{color:var(--tm);}

.da-date{font-size:10px;color:var(--tl);font-weight:600;text-align:center;}
</style>
</head>
<body>

<div class="topbar">
  <div class="tb-left">
    <img class="tb-logo" src="/user/images/logotree.png?v=20260812" alt="Dollar Tree">
    <div class="tb-div"></div>
    <div class="tb-greet">
      <div class="tb-top"><?php echo t('hello','Hello');?> 👋</div>
      <div class="tb-name"><?php echo htmlspecialchars(substr($user['username'],0,16));?></div>
    </div>
  </div>
  <div class="tb-right">
    <button class="tb-btn" onclick="openNotif()" title="Notifications">
      <i class="fa-regular fa-bell"></i>
      <span class="notif-badge" id="bell-badge"></span>
    </button>
    <button class="tb-btn" onclick="showToast('Language change coming soon!')" title="Language">
      <i class="fa-solid fa-globe"></i>
    </button>
  </div>
</div>

<!-- ─── NOTIFICATION MODAL ─── -->
<div class="n-overlay" id="nOverlay" onclick="if(event.target===this)closeNotif()">
  <div class="n-modal">

    <div class="nm-head">
      <span class="nm-title">
        <i class="fa-regular fa-bell"></i>
        Notifications
        <span class="nm-total-badge" id="nm-total-badge"></span>
      </span>
      <div class="nm-head-actions">
        <button class="nm-mark-all-btn" onclick="markAllRead()">
          <i class="fa-solid fa-check-double"></i>Mark all read
        </button>
        <button class="nm-x" onclick="closeNotif()"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>

    <!-- TABS -->
    <div class="nm-tabs">
      <button class="nm-tab active" id="tab-ann" onclick="switchTab('ann',this)">
        <i class="fa-solid fa-bullhorn"></i>
        Announcements
        <span class="tab-badge" id="badge-ann"></span>
      </button>
      <button class="nm-tab" id="tab-msg" onclick="switchTab('msg',this)">
        <i class="fa-solid fa-envelope"></i>
        Messages
        <span class="tab-badge" id="badge-msg"></span>
      </button>
      <button class="nm-tab" id="tab-dep" onclick="switchTab('dep',this)">
        <i class="fa-solid fa-circle-arrow-down"></i>
        Deposits
        <span class="tab-badge" id="badge-dep"></span>
      </button>
      <button class="nm-tab" id="tab-with" onclick="switchTab('with',this)">
        <i class="fa-solid fa-circle-arrow-up"></i>
        Withdrawals
        <span class="tab-badge" id="badge-with"></span>
      </button>
    </div>

    <div class="nm-body">
      <div id="nm-loading" style="padding:40px;text-align:center;color:var(--tl);">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;display:block;margin-bottom:10px;opacity:.5;"></i>
        <span style="font-size:13px;">Loading…</span>
      </div>
      <div class="nm-pane" id="np-ann"></div>
      <div class="nm-pane" id="np-msg"></div>
      <div class="nm-pane" id="np-dep"></div>
      <div class="nm-pane" id="np-with"></div>
    </div>

  </div>
</div>

<!-- HERO -->
<div class="hero">
  <div class="hero-dots"></div>
  <div class="hero-in">
    <div class="hero-top">
      <div class="hero-lbl"><i class="fa-solid fa-wallet"></i><?php echo t('total_balance','Total Balance');?></div>
      <div class="hero-pill"><i class="fa-solid fa-arrow-trend-up"></i>+4.5% <?php echo t('daily','Daily');?></div>
    </div>
    <div class="hero-bal"><span id="bi">0</span><span class="dec">.<span id="bd">00</span></span></div>
    <div class="hero-curr">USDT — Investment Balance</div>
    <div class="hero-stats">
      <div class="hst">
        <div class="hst-l"><?php echo t('commission_balance','Commission');?></div>
        <div class="hst-v"><span id="cv">0.00</span><span class="u">USDT</span></div>
      </div>
      <div class="hst">
        <div class="hst-l"><?php echo t('total_deposited','Deposited');?></div>
        <div class="hst-v"><span id="dv">0.00</span><span class="u">USDT</span></div>
      </div>
    </div>
    <div class="hero-vip"><i class="fa-solid fa-crown"></i>SVIP <?php echo htmlspecialchars($user['svip_level']);?> — <?php echo htmlspecialchars($svip_tier['name']??'Standard');?></div>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div class="sec">
  <div class="sec-hd"><div class="sec-ttl"><i class="fa-solid fa-bolt"></i><?php echo t('quick_actions','Quick Actions');?></div></div>
  <div class="act-grid">
    <a href="recharge.php" class="act-item">
      <div class="act-ic"><i class="fa-solid fa-dollar-sign"></i></div>
      <span class="act-lbl"><?php echo t('recharge','Deposit');?></span>
    </a>
    <a href="withdraw.php" class="act-item">
      <div class="act-ic"><i class="fa-solid fa-money-bill-transfer"></i></div>
      <span class="act-lbl"><?php echo t('withdraw','Withdraw');?></span>
    </a>
    <a href="tasks.php" class="act-item">
      <div class="act-ic"><i class="fa-solid fa-list-check"></i></div>
      <span class="act-lbl"><?php echo t('task','Tasks');?></span>
    </a>
    <a href="profile.php?open=financial" class="act-item">
      <div class="act-ic"><i class="fa-solid fa-chart-line"></i></div>
      <span class="act-lbl"><?php echo t('financial_records','Records');?></span>
    </a>
  </div>
</div>

<!-- ─── SVIP TIERS ─── -->
<div class="tiers-sec">
  <div class="sec-hd">
    <div class="sec-ttl"><i class="fa-solid fa-crown"></i><?php echo t('vip','VIP Levels');?></div>
    <a href="vip.php" style="font-size:11px;font-weight:700;color:var(--gp);text-decoration:none;">View all →</a>
  </div>
  <div class="tiers-list">
  <?php foreach($tiers as $tier):
    $total_income = $tier['daily_tasks_limit'] * $tier['task_profit_per_completion'] * $tier['contract_duration_days'];
    $is_current = $tier['svip_level'] == $user['svip_level'];
    $is_below   = $tier['svip_level'] < $user['svip_level'];
    $has_balance = $user['total_deposited'] >= $tier['unlock_amount'];
    $card_class = $is_current ? 'tier-card is-active is-current' : ($is_below ? 'tier-card is-active' : 'tier-card');
    $eff_start  = $is_current ? date('d/m/Y H:i') : '—';
    $eff_end    = $is_current ? date('d/m/Y H:i', strtotime('+'.$tier['contract_duration_days'].' days')) : '—';
    $shortfall  = max(0, $tier['unlock_amount'] - $user['total_deposited']);
  ?>
  <div class="<?php echo $card_class;?>" id="dash-tier-<?php echo $tier['svip_level'];?>">
    <div class="step-ribbon"><span>Step <?php echo $tier['svip_level'];?></span></div>

    <div class="tc-top">
      <div class="tc-img"><img src="/user/images/tree.jpg?v=20260812" alt="SVIP <?php echo $tier['svip_level'];?>"></div>
      <div class="tc-meta">
        <div class="tc-name">SVIP <?php echo $tier['svip_level'];?></div>
        <?php if($is_current || $is_below):?>
        <div class="tc-eff">
          <i class="fa-solid fa-clock"></i>
          Effective:
          <span class="eff-val"><?php echo $eff_start;?></span>
          <span style="color:var(--tl)">—</span>
          <span class="eff-val"><?php echo $eff_end;?></span>
        </div>
        <?php endif;?>
      </div>
      <?php if($is_current):?>
      <div class="tc-status"><div class="badge-active"><i class="fa-solid fa-circle-check"></i>Active</div></div>
      <?php endif;?>
    </div>

    <div class="tc-stats">
      <div class="tc-stat">
        <div class="tc-stat-lbl">Unlock amount</div>
        <div class="tc-stat-val"><?php echo number_format($tier['unlock_amount'],2);?><span class="u">USDT</span></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">Maximum daily profit</div>
        <div class="tc-stat-val green"><?php echo number_format($tier['max_daily_profit'],2);?><span class="u">USDT</span></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">Duration</div>
        <div class="tc-stat-val"><?php echo (int)$tier['contract_duration_days'];?><span class="u">days</span></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">Availability</div>
        <div class="tc-stat-val green">Display only</div>
      </div>
    </div>

    <div class="tc-foot">
      <?php if($is_current):?>
        <div class="tc-need">Currently <strong>active</strong></div>
        <div class="btn-current"><i class="fa-solid fa-circle-check"></i>Unlock Effective</div>
      <?php elseif($is_below):?>
        <div class="tc-need">Already <strong>unlocked</strong></div>
        <div class="btn-done"><i class="fa-solid fa-check"></i>Completed</div>
      <?php else:?>
        <div class="tc-need">Cost: <strong><?php echo number_format($tier['unlock_amount'],2);?> USDT</strong></div>
        <button class="btn-unlock" type="button" disabled title="VIP unlocking is currently unavailable">
          <i class="fa-solid fa-lock"></i>Currently unavailable
        </button>
      <?php endif;?>
    </div>
  </div>
  <?php endforeach;?>
  </div>
</div>

<!-- REFERRAL -->
<div class="ref-card">
  <div class="ref-top">
    <div class="ref-ic"><i class="fa-solid fa-gift"></i></div>
    <div class="ref-ttl"><?php echo t('invitation_code_label','Invite & Earn');?></div>
  </div>
  <div class="ref-sub"><?php echo t('share_referral_link','Invite investors and earn commission on every deposit they make.');?></div>
  <div class="ref-row">
    <div class="ref-box"><span id="refCode"><?php echo htmlspecialchars($user['referral_code']);?></span></div>
    <button class="ref-copy" id="copyBtn" onclick="copyRef()"><i class="fa-regular fa-copy"></i><?php echo t('copy','Copy');?></button>
  </div>
</div>
<div class="spacer"></div>

<div class="toast" id="toast"></div>

<!-- ─── INSUFFICIENT BALANCE BOTTOM SHEET ─── -->
<div class="recharge-overlay" id="dashRechargeOverlay" onclick="if(event.target===this)dashCloseRecharge()">
  <div class="recharge-sheet">
    <div class="rs-handle"></div>
    <div class="rs-head">
      <div class="rs-icon"><i class="fa-solid fa-wallet"></i></div>
      <div class="rs-title">Insufficient Balance</div>
    </div>
    <div class="rs-body">
      Your current balance isn't enough to unlock <strong>SVIP <span id="dash-rs-level"></span></strong>. Please recharge your account to continue.
    </div>
    <div class="rs-amounts">
      <div class="rs-amt-row">
        <div class="rs-amt-lbl">Your Balance</div>
        <div class="rs-amt-val green"><?php echo number_format($user['total_deposited'],2);?> <small style="font-size:11px;color:var(--tl)">USDT</small></div>
      </div>
      <div class="rs-amt-row">
        <div class="rs-amt-lbl">Required</div>
        <div class="rs-amt-val" id="dash-rs-required" style="color:var(--tp)"></div>
      </div>
    </div>
    <div class="rs-need">You need <strong id="dash-rs-shortfall"></strong> more USDT to unlock this tier</div>
    <div class="rs-foot">
      <button class="rs-cancel" onclick="dashCloseRecharge()">Cancel</button>
      <a href="recharge.php" class="rs-recharge"><i class="fa-solid fa-circle-plus"></i>Recharge Now</a>
    </div>
  </div>
</div>

<!-- ─── CONFIRM UNLOCK MODAL ─── -->
<div class="confirm-overlay" id="dashCOverlay" onclick="if(event.target===this)dashCloseConfirm()">
  <div class="confirm-modal">
    <div class="cm-head">
      <div class="cm-icon"><i class="fa-solid fa-crown"></i></div>
      <div class="cm-title">Unlock SVIP <span id="dash-cm-level"></span></div>
    </div>
    <div class="cm-body">
      This will deduct<span class="cm-amount" id="dash-cm-amount"></span>from your balance to unlock this tier.
    </div>
    <div class="cm-foot">
      <button class="cm-cancel" onclick="dashCloseConfirm()">Cancel</button>
      <button class="cm-confirm" id="dash-cm-confirm-btn" onclick="dashDoUnlock()">Confirm</button>
    </div>
  </div>
</div>

<div class="bnav">
  <a href="dashboard.php" class="nav-item active"><i class="fa-solid fa-house ni"></i><span><?php echo t('home','Home');?></span></a>
  <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span><?php echo t('task','Tasks');?></span></a>
  <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span><?php echo t('team','Team');?></span></a>
  <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span><?php echo t('vip','VIP');?></span></a>
  <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span><?php echo t('me','Me');?></span></a>
</div>

<!-- Daily Announcement Modal -->
<div class="da-overlay" id="daOverlay">
  <div class="da-modal">
    <div class="da-topbar"></div>

    <div class="da-head">
      <div class="da-head-inner">
        <div class="da-ic"><i class="fa-solid fa-bullhorn"></i></div>
        <div>
          <div class="da-label"><i class="fa-solid fa-circle-info"></i> Announcement</div>
          <div class="da-title" id="da-title">…</div>
        </div>
      </div>
      <button class="da-x" onclick="closeDailyModal()" title="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="da-hr"></div>

    <div class="da-body" id="da-body"></div>

    <div class="da-footer">
      <!-- CTA button — shown only when link_url is set -->
      <a href="#" class="da-cta" id="da-cta" target="_blank" rel="noopener" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i>
        <span id="da-cta-text">Learn More</span>
      </a>
      <button class="da-dismiss" onclick="closeDailyModal()">
        Got it — don't show again today
      </button>
      <div class="da-date" id="da-date"></div>
    </div>
  </div>
</div>

<div class="foot"></div>

<script>
/* ═══════════════════════════════════════════════════════════════
   BALANCE ANIMATIONS
   ═══════════════════════════════════════════════════════════════ */
function animBal(i,d,t,dur){var e1=document.getElementById(i),e2=document.getElementById(d),t0=Date.now();(function tick(){var p=Math.min((Date.now()-t0)/dur,1),ease=1-Math.pow(1-p,3),v=t*ease;e1.textContent=Math.floor(v).toLocaleString();e2.textContent=(v%1).toFixed(2).substring(2);if(p<1)requestAnimationFrame(tick);})();}
function animNum(id,t,dur){var el=document.getElementById(id);if(!el)return;var t0=Date.now();(function tick(){var p=Math.min((Date.now()-t0)/dur,1),ease=1-Math.pow(1-p,3);el.textContent=(t*ease).toFixed(2);if(p<1)requestAnimationFrame(tick);})();}
document.addEventListener('DOMContentLoaded',function(){
  animBal('bi','bd',<?php echo floatval($user['balance']);?>,1800);
  animNum('cv',<?php echo floatval($user['commission_balance']);?>,1500);
  animNum('dv',<?php echo floatval($user['total_deposited']);?>,1400);
});

/* ═══════════════════════════════════════════════════════════════
   NOTIFICATION BADGE SYSTEM
   ═══════════════════════════════════════════════════════════════ */
var _notifData=null, _notifLoaded=false, _activeTab='ann';

function applyBadges(unreadAnn, unreadMsg, pendingDep, pendingWith) {
  var total = unreadAnn + unreadMsg + pendingDep + pendingWith;
  var bell = document.getElementById('bell-badge');
  if (total > 0) { bell.textContent = total > 99 ? '99+' : String(total); bell.style.display = 'flex'; }
  else           { bell.style.display = 'none'; }
  var hd = document.getElementById('nm-total-badge');
  if (total > 0) { hd.textContent = total; hd.style.display = 'inline'; }
  else           { hd.style.display = 'none'; }
  var defs = { ann: unreadAnn, msg: unreadMsg, dep: pendingDep, with: pendingWith };
  Object.keys(defs).forEach(function(k) {
    var b = document.getElementById('badge-' + k);
    if (defs[k] > 0) { b.textContent = defs[k]; b.style.display = 'inline-flex'; }
    else             { b.style.display = 'none'; }
  });
}

function decrementBadge(badgeId) {
  var el = document.getElementById(badgeId);
  var cur = Math.max(0, parseInt(el.textContent || '0') - 1);
  if (cur > 0) { el.textContent = cur; }
  else         { el.style.display = 'none'; }
  ['bell-badge','nm-total-badge'].forEach(function(id) {
    var b = document.getElementById(id);
    var v = Math.max(0, parseInt(b.textContent || '0') - 1);
    if (v > 0) { b.textContent = v; b.style.display = id==='bell-badge'?'flex':'inline'; }
    else       { b.style.display = 'none'; }
  });
}

function escHtml(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function timeAgo(ds){
  if(!ds)return'—';
  var d=new Date(ds.replace(' ','T')),now=new Date(),s=Math.floor((now-d)/1000);
  if(s<60)return'Just now';if(s<3600)return Math.floor(s/60)+'m ago';
  if(s<86400)return Math.floor(s/3600)+'h ago';
  var days=Math.floor(s/86400);if(days<7)return days+'d ago';
  return d.toLocaleDateString('en',{month:'short',day:'numeric'});
}
function emptyPane(icon,txt){return'<div class="nm-empty"><i class="fa-solid fa-'+icon+'"></i>'+txt+'</div>';}
function annIco(type){return({general:['g','bullhorn'],promotion:['o','gift'],maintenance:['r','wrench'],warning:['r','triangle-exclamation']})[type]||['b','circle-info'];}
function statusPill(status){
  var map={pending:'pending',completed:'completed',approved:'completed',failed:'failed',rejected:'rejected'};
  var cls=map[status]||'pending';
  var icon={pending:'clock',completed:'circle-check',approved:'circle-check',failed:'circle-xmark',rejected:'ban'}[status]||'clock';
  return '<span class="nm-status '+cls+'"><i class="fa-solid fa-'+icon+'"></i> '+escHtml(status)+'</span>';
}
function buildItem(icoClass, icoIcon, title, body, meta, isUnread, clickAttr) {
  return '<div class="nm-item'+(isUnread?' is-unread':'')+'" '+(clickAttr||'')+'>'
       +   '<div class="nm-item-head">'
       +     '<div class="nm-ico '+icoClass+'"><i class="fa-solid fa-'+icoIcon+'"></i></div>'
       +     '<div class="nm-item-title">'+title+(isUnread?'<span class="nm-new-pill">NEW</span>':'')+'</div>'
       +   '</div>'
       +   '<div class="nm-item-body">'+body+'</div>'
       +   '<div class="nm-item-meta">'+meta+'</div>'
       + '</div>';
}
function renderAnn(data) {
  if (!data||!data.length) return emptyPane('bullhorn','No announcements right now');
  return data.map(function(a) {
    var ic=annIco(a.announcement_type);
    var unread = a.hasOwnProperty('is_read') && !a.is_read;
    var click  = unread ? 'onclick="markAnnRead('+a.id+',this)" style="cursor:pointer;"' : '';
    return buildItem(ic[0], ic[1], escHtml(a.title), escHtml(a.content),
      '<span class="nm-time"><i class="fa-regular fa-clock"></i> '+timeAgo(a.created_at)+'</span>', unread, click);
  }).join('');
}
function renderMsg(data) {
  if (!data||!data.length) return emptyPane('envelope','No messages from admin yet');
  return data.map(function(m) {
    var unread=!m.is_read;
    var ico=m.msg_type==='all'?'bullhorn':(m.msg_type==='svip'?'crown':'headset');
    var click = 'onclick="markMsgRead('+m.id+',this)" style="cursor:pointer;"';
    var meta = '<span class="nm-time"><i class="fa-solid fa-shield-halved"></i> Admin'
             + (m.from_admin?' · '+escHtml(m.from_admin):'')
             + ' &nbsp;·&nbsp; <i class="fa-regular fa-clock"></i> '+timeAgo(m.created_at)+'</span>';
    return buildItem('o', ico, escHtml(m.title), escHtml(m.content), meta, unread, click);
  }).join('');
}
function renderDep(data) {
  if (!data||!data.length) return emptyPane('circle-arrow-down','No deposit records found');
  return data.map(function(d) {
    var isPending = d.status === 'pending';
    var detected  = d.detected_amount && d.detected_amount != d.amount
                  ? ' <small style="color:var(--tl);font-size:10px;">(detected: '+parseFloat(d.detected_amount).toFixed(2)+')</small>' : '';
    var body = '<span class="nm-amount credit">+'+parseFloat(d.amount).toFixed(2)+' '+(d.currency||'USDT')+'</span>'+detected;
    var meta = statusPill(d.status)
             + '<span class="nm-time"><i class="fa-regular fa-clock"></i> '+timeAgo(d.created_at)+'</span>'
             + (d.completed_at ? '<span class="nm-time">Completed: '+timeAgo(d.completed_at)+'</span>' : '');
    return buildItem('teal','circle-arrow-down','Deposit #'+d.id, body, meta, isPending, '');
  }).join('');
}
function renderWith(data) {
  if (!data||!data.length) return emptyPane('circle-arrow-up','No withdrawal records found');
  return data.map(function(w) {
    var net = w.net_amount ? parseFloat(w.net_amount).toFixed(2) : parseFloat(w.amount).toFixed(2);
    var body = '<span class="nm-amount debit">−'+net+' '+(w.currency||'USDT')+'</span>'
             + (w.network ? ' <small style="color:var(--tl);font-size:10px;">via '+escHtml(w.network)+'</small>' : '');
    var meta = statusPill(w.status)
             + '<span class="nm-time"><i class="fa-regular fa-clock"></i> '+timeAgo(w.requested_at)+'</span>'
             + (w.processed_at ? '<span class="nm-time">Processed: '+timeAgo(w.processed_at)+'</span>' : '')
             + (w.rejection_reason ? '<span class="nm-time" style="color:#c0392b;">'+escHtml(w.rejection_reason)+'</span>' : '');
    return buildItem('r','circle-arrow-up','Withdrawal #'+w.id, body, meta, false, '');
  }).join('');
}
function loadNotif(force) {
  if (_notifLoaded && !force) { showPane(); return; }
  document.getElementById('nm-loading').style.display = 'block';
  document.querySelectorAll('.nm-pane').forEach(function(p){p.style.display='none';});
  fetch('../api/notifications.php', {credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(d){
      document.getElementById('nm-loading').style.display = 'none';
      if (!d.success) return;
      _notifData=d; _notifLoaded=true;
      document.getElementById('np-ann').innerHTML  = renderAnn(d.announcements);
      document.getElementById('np-msg').innerHTML  = renderMsg(d.messages);
      document.getElementById('np-dep').innerHTML  = renderDep(d.deposits);
      document.getElementById('np-with').innerHTML = renderWith(d.withdrawals);
      var unreadAnn  = (d.announcements||[]).filter(function(a){return a.hasOwnProperty('is_read') && !a.is_read;}).length;
      var unreadMsg  = (d.messages||[]).filter(function(m){return !m.is_read;}).length;
      var pendingDep = (d.deposits||[]).filter(function(x){return x.status==='pending';}).length;
      var pendingWith= (d.withdrawals||[]).filter(function(x){return x.status==='pending';}).length;
      applyBadges(unreadAnn, unreadMsg, pendingDep, pendingWith);
      showPane();
    })
    .catch(function(){
      document.getElementById('nm-loading').style.display='none';
      document.getElementById('np-'+_activeTab).innerHTML=emptyPane('wifi','Could not load. Check your connection.');
      showPane();
    });
}
function showPane() {
  document.querySelectorAll('.nm-pane').forEach(function(p){p.style.display='none';});
  var el=document.getElementById('np-'+_activeTab);
  if (el) el.style.display='block';
}
function markAnnRead(annId, el) {
  fetch('../api/mark_message_read.php',{
    method:'POST',credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({announcement_id:annId})
  }).then(function(r){return r.json();}).then(function(d){
    if (d.success) {
      if (el) { el.classList.remove('is-unread'); var p=el.querySelector('.nm-new-pill'); if(p)p.remove(); }
      decrementBadge('badge-ann');
    }
  }).catch(function(){});
}
function markMsgRead(msgId, el) {
  if (el && !el.classList.contains('is-unread')) return;
  fetch('../api/mark_message_read.php',{
    method:'POST',credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({message_id:msgId})
  }).then(function(r){return r.json();}).then(function(d){
    if (d.success) {
      if (el) { el.classList.remove('is-unread'); var p=el.querySelector('.nm-new-pill'); if(p)p.remove(); }
      decrementBadge('badge-msg');
    }
  }).catch(function(){});
}
function markAllRead() {
  fetch('../api/mark_message_read.php',{
    method:'POST',credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({mark_all:true})
  }).then(function(r){return r.json();}).then(function(d){
    if (d.success) { _notifLoaded=false; loadNotif(true); showToast('All notifications marked as read'); }
  });
}
function openNotif(){document.getElementById('nOverlay').classList.add('open');document.body.style.overflow='hidden';loadNotif(false);}
function closeNotif(){document.getElementById('nOverlay').classList.remove('open');document.body.style.overflow='';}
function switchTab(id,btn){
  document.querySelectorAll('.nm-tab').forEach(function(b){b.classList.remove('active');});
  btn.classList.add('active');
  _activeTab=id;
  _notifLoaded ? showPane() : loadNotif(false);
}
document.addEventListener('DOMContentLoaded',function(){
  fetch('../api/notifications.php',{credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(d){
      if (!d.success) return;
      _notifData=d; _notifLoaded=true;
      document.getElementById('np-ann').innerHTML  = renderAnn(d.announcements);
      document.getElementById('np-msg').innerHTML  = renderMsg(d.messages);
      document.getElementById('np-dep').innerHTML  = renderDep(d.deposits);
      document.getElementById('np-with').innerHTML = renderWith(d.withdrawals);
      document.getElementById('nm-loading').style.display='none';
      var unreadAnn  = (d.announcements||[]).filter(function(a){return a.hasOwnProperty('is_read') && !a.is_read;}).length;
      var unreadMsg  = (d.messages||[]).filter(function(m){return !m.is_read;}).length;
      var pendingDep = (d.deposits||[]).filter(function(x){return x.status==='pending';}).length;
      var pendingWith= (d.withdrawals||[]).filter(function(x){return x.status==='pending';}).length;
      applyBadges(unreadAnn, unreadMsg, pendingDep, pendingWith);
    })
    .catch(function(){});
});

/* ═══════════════════════════════════════════════════════════════
   TOAST
   ═══════════════════════════════════════════════════════════════ */
let _tt;
function showToast(msg,isErr=false){
  const el=document.getElementById('toast');clearTimeout(_tt);
  el.innerHTML=`<i class="fa-solid fa-${isErr?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  el.className='toast show'+(isErr?' err':'');
  _tt=setTimeout(()=>el.classList.remove('show'),3500);
}

/* ═══════════════════════════════════════════════════════════════
   REFERRAL COPY
   ═══════════════════════════════════════════════════════════════ */
function copyRef(){var code=document.getElementById('refCode').textContent.trim();var link=location.origin+'/user/register.php?ref='+code;var btn=document.getElementById('copyBtn');(navigator.clipboard?navigator.clipboard.writeText(link):Promise.reject()).catch(function(){var inp=Object.assign(document.createElement('input'),{value:link});Object.assign(inp.style,{position:'fixed',opacity:'0'});document.body.appendChild(inp);inp.select();document.execCommand('copy');inp.remove();}).finally(function(){btn.classList.add('copied');btn.innerHTML='<i class="fa-solid fa-check"></i>Copied!';showToast('Referral link copied!');setTimeout(function(){btn.classList.remove('copied');btn.innerHTML='<i class="fa-regular fa-copy"></i><?php echo t("copy","Copy");?>';},2500);});}

/* ═══════════════════════════════════════════════════════════════
   DASHBOARD VIP UNLOCK
   ═══════════════════════════════════════════════════════════════ */
let _dashPendingLevel=null, _dashPendingAmount=null;

function dashShowRecharge(level, required, shortfall){
  document.getElementById('dash-rs-level').textContent = level;
  document.getElementById('dash-rs-required').textContent = required.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' USDT';
  document.getElementById('dash-rs-shortfall').textContent = shortfall.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('dashRechargeOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function dashCloseRecharge(){
  document.getElementById('dashRechargeOverlay').classList.remove('open');
  document.body.style.overflow='';
}
function dashConfirmUnlock(level, amount){
  _dashPendingLevel=level; _dashPendingAmount=amount;
  document.getElementById('dash-cm-level').textContent=level;
  document.getElementById('dash-cm-amount').textContent=amount.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' USDT';
  document.getElementById('dashCOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function dashCloseConfirm(){
  document.getElementById('dashCOverlay').classList.remove('open');
  document.body.style.overflow='';
  _dashPendingLevel=null; _dashPendingAmount=null;
}
async function dashDoUnlock(){
  if(!_dashPendingLevel)return;
  const btn=document.getElementById('dash-cm-confirm-btn');
  btn.disabled=true; btn.textContent='Processing…';
  try{
    const r=await fetch('../api/unlock_svip.php',{
      method:'POST',credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({svip_level:_dashPendingLevel})
    });
    const d=await r.json();
    dashCloseConfirm();
    if(d.success){
      showToast('SVIP '+_dashPendingLevel+' unlocked! New balance: '+d.data.new_balance.toFixed(2)+' USDT');
      setTimeout(()=>location.reload(),1800);
    } else {
      showToast(d.message||'Failed to unlock.',true);
    }
  } catch(e){
    dashCloseConfirm(); showToast('Network error. Please try again.',true);
  }
  btn.disabled=false; btn.textContent='Confirm';
}

document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){ closeNotif(); dashCloseConfirm(); dashCloseRecharge(); }
});

(function(){
  var _daId = null;

  function openDailyModal(ann) {
    _daId = ann.id;

    document.getElementById('da-title').textContent = ann.title || 'Announcement';
    document.getElementById('da-body').innerHTML    = (ann.content || '').replace(/\n/g, '<br>');

    var cta = document.getElementById('da-cta');
    if (ann.link_url) {
      cta.href = ann.link_url;
      document.getElementById('da-cta-text').textContent = ann.link_text || 'Learn More';
      cta.style.display = 'flex';
    } else {
      cta.style.display = 'none';
    }

    if (ann.created_at) {
      var d = new Date(ann.created_at.replace(' ','T'));
      document.getElementById('da-date').textContent =
        d.toLocaleDateString('en', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
    }

    document.getElementById('daOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  window.closeDailyModal = function() {
    document.getElementById('daOverlay').classList.remove('open');
    document.body.style.overflow = '';

    // Tell server — won't show again today
    if (_daId) {
      fetch('../api/daily_modal.php', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ann_id: _daId})
      }).catch(function(){});
      _daId = null;
    }
  };

  // Fetch on page load — 900ms delay so page renders first
  document.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){
      fetch('../api/daily_modal.php', {credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (d.success && d.show && d.ann) openDailyModal(d.ann);
        })
        .catch(function(){});
    }, 900);
  });

  // Close on backdrop click
  document.getElementById('daOverlay').addEventListener('click', function(e){
    if (e.target === this) window.closeDailyModal();
  });

  // Close on Escape (merged with your existing keydown listener)
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') window.closeDailyModal();
  });
})();
</script>
</body>
</html>

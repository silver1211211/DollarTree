<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
$page_title = t('me','Me');
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
  --gg:rgba(26,122,26,0.10);--gg2:rgba(26,122,26,0.05);
  --bg:#f0f5f0;--white:#fff;
  --border:#d4ebd4;--border2:#b8d8b8;
  --tp:#0d1a0d;--ts:#2d4a2d;--tm:#5a7a5a;--tl:#8aaa8a;
  --gold:#c8860a;--gold-bg:#fef9ed;--gold-bdr:#f0c060;
  --nav-h:64px;--top-h:60px;
  --r:14px;--r2:18px;
  --sh:0 2px 10px rgba(0,0,0,0.06);
  --shm:0 4px 20px rgba(0,0,0,0.09);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{
  font-family:'Barlow',sans-serif;background:var(--bg);color:var(--tp);
  min-height:100vh;padding-top:var(--top-h);
  padding-bottom:calc(var(--nav-h)+24px);
  overflow-x:hidden;width:100%;-webkit-font-smoothing:antialiased;
}

/* ─── TOPBAR ─── */
.topbar{
  position:fixed;top:0;left:0;right:0;z-index:400;
  height:var(--top-h);background:var(--gp);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;box-shadow:0 2px 12px rgba(0,0,0,0.15);
}
.tb-left{display:flex;align-items:center;gap:10px;}
.tb-logo{height:20px;width:auto;object-fit:contain;}
.tb-title{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:800;color:#fff;letter-spacing:.02em;}
.tb-right{display:flex;align-items:center;gap:8px;}
.tb-lang{
  display:flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.25);
  border-radius:100px;padding:5px 12px;
  font-size:11px;font-weight:700;color:#fff;cursor:pointer;
  transition:all .18s;
}
.tb-lang:hover{background:rgba(255,255,255,.28);}
.tb-lang i{font-size:12px;}

/* ─── PROFILE HEADER ─── */
.profile-header{
  background:linear-gradient(145deg,var(--gp) 0%,#0f5c0f 100%);
  padding:24px 20px 20px;
  display:flex;align-items:center;gap:16px;
  position:relative;overflow:hidden;
}
.profile-header::before{content:'';position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(255,255,255,.4) 1px,transparent 1px);
  background-size:20px 20px;opacity:.07;}
.ph-inner{position:relative;z-index:2;display:flex;align-items:center;gap:16px;width:100%;}
.ph-avatar{
  width:64px;height:64px;flex-shrink:0;
  background:rgba(255,255,255,0.15);border:2px solid rgba(255,255,255,.25);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:26px;color:rgba(255,255,255,.9);
}
.ph-info{flex:1;min-width:0;}
.ph-name{font-size:16px;font-weight:800;color:#fff;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ph-sub{font-size:12px;color:rgba(255,255,255,.6);}
.ph-vip{display:inline-flex;align-items:center;gap:5px;background:rgba(240,192,64,.2);border:1px solid rgba(240,192,64,.35);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:800;color:#f5d060;text-transform:uppercase;letter-spacing:.05em;margin-top:6px;}
.ph-vip i{font-size:10px;}

/* ─── BALANCE CARDS STRIP ─── */
.bal-strip{
  display:grid;grid-template-columns:1fr 1fr;
  gap:0;margin:16px 16px 0;
  background:var(--white);border:1px solid var(--border);border-radius:var(--r2);
  overflow:hidden;box-shadow:var(--sh);
}
.bal-card{padding:16px 14px;position:relative;}
.bal-card:first-child{border-right:1px solid var(--border);}
.bal-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
.bal-val{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:var(--tp);line-height:1;}
.bal-unit{font-size:13px;font-weight:700;color:var(--gp);margin-left:4px;}

/* ─── BLACK ACTION BUTTONS ─── */
.action-bar{
  margin:14px 16px 0;
  background:#111;border-radius:var(--r2);
  display:grid;grid-template-columns:repeat(4,1fr);
  overflow:hidden;
  box-shadow:var(--shm);
}
.ab-item{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:8px;padding:16px 6px;
  text-decoration:none;cursor:pointer;border:none;
  background:transparent;color:#fff;
  transition:background .18s;
  font-family:'Barlow',sans-serif;
}
.ab-item:hover{background:rgba(255,255,255,.06);}
.ab-ic{
  width:44px;height:44px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;
}
.ab-ic.green{background:#00c875;}
.ab-ic.red{background:#e74c3c;}
.ab-ic.teal{background:#1abc9c;}
.ab-ic.gold{background:#f39c12;}
.ab-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:rgba(255,255,255,.85);}

/* ─── 2-COLUMN ACTION BUTTONS ─── */
.two-col{
  display:grid;grid-template-columns:1fr 1fr;
  gap:12px;margin:14px 16px 0;
}
.tc-btn{
  background:var(--white);border:1px solid var(--border);border-radius:var(--r);
  padding:18px 14px;
  display:flex;flex-direction:column;align-items:center;gap:10px;
  cursor:pointer;transition:all .18s;text-decoration:none;
  box-shadow:var(--sh);
}
.tc-btn:hover{border-color:var(--border2);box-shadow:var(--shm);}
.tc-btn.privacy-link{grid-column:1/-1;flex-direction:row;justify-content:center;padding:14px;}
.tc-ic{
  width:48px;height:48px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;
}
.tc-ic.blue{background:#e8f0fd;color:#1a6ab8;}
.tc-ic.indigo{background:#edeffa;color:#5b67d8;}
.tc-lbl{font-size:12px;font-weight:700;color:var(--ts);text-align:center;letter-spacing:.01em;}

/* ─── SLIDE PAGE ─── */
.slide-page{
  position:fixed;inset:0;z-index:600;
  background:var(--white);
  transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
  overflow:hidden;
}
.slide-page.open{transform:translateX(0);}

.sp-topbar{
  height:var(--top-h);background:var(--gp);
  display:flex;align-items:center;gap:12px;
  padding:0 16px;box-shadow:0 2px 10px rgba(0,0,0,.15);
  flex-shrink:0;
}
.sp-back{width:36px;height:36px;border:none;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;cursor:pointer;transition:all .18s;flex-shrink:0;}
.sp-back:hover{background:rgba(255,255,255,.25);}
.sp-title{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:800;color:#fff;letter-spacing:.02em;flex:1;}

.sp-body{flex:1;overflow-y:auto;background:var(--bg);}

/* ACCOUNT PAGE BODY */
.acct-hero{
  background:#e8f5e8;border:1px solid var(--border);
  margin:16px;border-radius:var(--r2);
  padding:20px 16px 20px;
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;position:relative;overflow:hidden;
}
.acct-hero::before{content:'';position:absolute;right:-10px;bottom:-10px;width:100px;height:100px;background:radial-gradient(circle,rgba(26,122,26,.08) 0%,transparent 70%);pointer-events:none;}
.acct-fields{flex:1;display:flex;flex-direction:column;gap:14px;}
.acct-field-lbl{font-size:11px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;}
.acct-field-val{font-family:'Barlow Condensed',sans-serif;font-size:24px;font-weight:900;color:var(--tp);line-height:1;}
.acct-field-val .unit{font-size:15px;font-weight:800;color:var(--gp);margin-left:4px;}
.acct-illus{width:90px;height:90px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.bank-svg{width:90px;height:90px;}

/* FINANCIAL RECORDS */
.fr-toggle{
  display:flex;align-items:center;
  background:var(--white);border:1px solid var(--border);
  border-radius:100px;margin:16px;
  padding:4px;overflow:hidden;
  box-shadow:var(--sh);
}
.fr-tab{
  flex:1;padding:9px 12px;border:none;border-radius:100px;
  background:transparent;
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;
  color:var(--tl);cursor:pointer;transition:all .2s;
  text-align:center;
}
.fr-tab.active{background:var(--gg);color:var(--gp);}

.fr-filter{display:flex;justify-content:flex-end;padding:0 16px 10px;}
.fr-filter-btn{background:transparent;border:none;color:var(--tl);font-size:16px;cursor:pointer;padding:4px;}
.fr-filter-btn:hover{color:var(--gp);}

.fr-list{padding:0 16px;}
.fr-item{
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r);padding:14px 16px;margin-bottom:10px;
  display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  box-shadow:var(--sh);
}
.fr-item-info{}
.fr-item-type{font-size:13px;font-weight:700;color:var(--tp);margin-bottom:4px;}
.fr-item-date{font-size:11px;color:var(--tl);font-weight:600;}
.fr-item-amt{font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:800;color:var(--gp);white-space:nowrap;}

.fr-empty{
  padding:48px 20px;text-align:center;
  background:#e8f5e8;border:1px solid var(--border);border-radius:var(--r2);margin:0 16px;
}
.fr-empty-icon{
  width:72px;height:72px;margin:0 auto 16px;
  opacity:.25;
}
.fr-empty-icon svg{width:72px;height:72px;}
.fr-empty-txt{font-size:13px;color:var(--tl);font-weight:600;}

.fr-no-more{text-align:center;padding:16px;font-size:12px;color:var(--tl);font-weight:700;text-transform:uppercase;letter-spacing:.06em;}

/* ─── SECTION LABEL ─── */
.fr-section-lbl{
  font-size:11px;font-weight:800;color:var(--tl);
  text-transform:uppercase;letter-spacing:.07em;
  margin:4px 0 10px;
}

/* ─── TOAST ─── */
.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:700;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.err{background:#c0392b;}

/* ─── CHANGE PASSWORD MODAL ─── */
.cpw-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.45);display:none;align-items:flex-end;justify-content:center;}
.cpw-overlay.open{display:flex;animation:fadein .18s ease;}
@keyframes fadein{from{opacity:0}to{opacity:1}}
.cpw-sheet{
  background:var(--white);border-radius:var(--r2) var(--r2) 0 0;
  width:100%;max-width:480px;padding:24px 20px 32px;
  box-shadow:0 -8px 32px rgba(0,0,0,.14);
  animation:slideup .25s cubic-bezier(.34,1.3,.64,1);
}
@keyframes slideup{from{transform:translateY(40px);opacity:0}to{transform:translateY(0);opacity:1}}
.cpw-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.cpw-title{font-size:16px;font-weight:800;color:var(--tp);}
.cpw-close{width:32px;height:32px;border-radius:8px;background:var(--bg);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--tm);transition:all .18s;}
.cpw-close:hover{background:var(--gg);color:var(--gp);}
.cpw-field{margin-bottom:14px;}
.cpw-label{font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
.cpw-input{
  width:100%;padding:12px 14px;
  background:var(--bg);border:1px solid var(--border);border-radius:10px;
  font-family:'Barlow',sans-serif;font-size:14px;color:var(--tp);
  transition:all .18s;outline:none;
}
.cpw-input:focus{border-color:var(--border2);}
.cpw-btn{
  width:100%;padding:13px;margin-top:6px;
  background:var(--gp);color:#fff;border:none;border-radius:12px;
  font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;
  cursor:pointer;transition:all .18s;box-shadow:0 3px 12px rgba(26,122,26,.3);
}
.cpw-btn:hover{background:var(--gb);}

/* ─── CONFIRM MODAL ─── */
.cf-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:20px;}
.cf-overlay.open{display:flex;animation:fadein .18s ease;}
.cf-modal{background:var(--white);border-radius:var(--r2);width:100%;max-width:320px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.cf-head{padding:20px 20px 0;text-align:center;}
.cf-icon{width:52px;height:52px;background:#fef0ef;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:#c0392b;margin:0 auto 12px;}
.cf-title{font-size:16px;font-weight:800;color:var(--tp);margin-bottom:6px;}
.cf-sub{font-size:13px;color:var(--tm);line-height:1.5;padding:0 10px;}
.cf-foot{display:flex;gap:10px;padding:18px 20px 20px;}
.cf-cancel{flex:1;padding:11px;background:var(--bg);border:1px solid var(--border);border-radius:10px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:var(--tm);cursor:pointer;transition:all .18s;}
.cf-cancel:hover{border-color:var(--border2);}
.cf-confirm{flex:1;padding:11px;background:#c0392b;border:none;border-radius:10px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;color:#fff;cursor:pointer;transition:all .18s;}
.cf-confirm:hover{background:#a93226;}

/* ─── BOTTOM NAV ─── */
.bnav{position:fixed;bottom:0;left:0;right:0;z-index:300;height:var(--nav-h);background:var(--white);border-top:1px solid var(--border);display:flex;align-items:stretch;box-shadow:0 -2px 16px rgba(0,0,0,.07);}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0 4px;text-decoration:none;color:var(--tl);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;transition:all .18s;position:relative;cursor:pointer;}
.nav-item::before{content:'';position:absolute;top:0;left:25%;right:25%;height:2px;border-radius:0 0 3px 3px;background:var(--gp);transform:scaleX(0);transform-origin:center;transition:transform .2s;}
.nav-item.active{color:var(--gp);}
.nav-item.active::before{transform:scaleX(1);}
.ni{font-size:20px;transition:transform .18s;}
.nav-item.active .ni{transform:scale(1.1);}

.spacer{height:10px;}
.foot{padding-bottom:100px;}

/* ─── CPW FEEDBACK OVERLAY ─── */
.cpw-feedback{
  position:fixed;inset:0;z-index:900;
  background:rgba(0,0,0,.55);
  display:none;
  align-items:center;justify-content:center;
  backdrop-filter:blur(2px);
}
.cpw-feedback.show{display:flex;}
.cpw-feedback-msg{
  color:#fff;font-family:'Barlow',sans-serif;font-size:15px;font-weight:700;
  text-align:center;letter-spacing:.02em;
  display:flex;align-items:center;gap:10px;
}
.cpw-feedback-msg i{font-size:18px;}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="tb-left">
    <img class="tb-logo" src="/user/images/logotree.png?v=20260812" alt="Dollar Tree">
    <span class="tb-title">DollarTree</span>
  </div>
  <div class="tb-right">
    <button class="tb-lang" onclick="showToast('Language change coming soon!')">
      <i class="fa-solid fa-globe"></i> English
    </button>
  </div>
</div>

<!-- PROFILE HEADER -->
<div class="profile-header">
  <div class="ph-inner">
    <div class="ph-avatar"><i class="fa-solid fa-circle-user"></i></div>
    <div class="ph-info">
      <div class="ph-name"><?php echo htmlspecialchars($user['username']);?></div>
      <div class="ph-sub">ID: <?php echo $user['id'];?></div>
      <div class="ph-vip"><i class="fa-solid fa-crown"></i>SVIP <?php echo $user['svip_level'];?></div>
    </div>
  </div>
</div>

<!-- BALANCE CARDS -->
<div class="bal-strip">
  <div class="bal-card">
    <div class="bal-lbl">Total Balance</div>
    <div class="bal-val"><?php echo number_format($user['balance'],2);?><span class="bal-unit">USDT</span></div>
  </div>
  <div class="bal-card">
    <div class="bal-lbl">Recharge Amount</div>
    <div class="bal-val"><?php echo number_format($user['total_deposited'] ?? 0,2);?><span class="bal-unit">USDT</span></div>
  </div>
</div>

<!-- BLACK ACTION BAR -->
<div class="action-bar">
  <a href="recharge.php" class="ab-item">
    <div class="ab-ic green"><i class="fa-solid fa-dollar-sign"></i></div>
    <span class="ab-lbl">Recharge</span>
  </a>
  <button class="ab-item" onclick="openAccount()">
    <div class="ab-ic red"><i class="fa-solid fa-credit-card"></i></div>
    <span class="ab-lbl">Account</span>
  </button>
  <a href="withdraw.php" class="ab-item">
    <div class="ab-ic teal"><i class="fa-solid fa-money-bill-transfer"></i></div>
    <span class="ab-lbl">Withdraw</span>
  </a>
  <button class="ab-item" onclick="openFinancial()">
    <div class="ab-ic gold"><i class="fa-solid fa-chart-bar"></i></div>
    <span class="ab-lbl">Financial records</span>
  </button>
</div>

<!-- TWO COLUMN: Change Password / Sign Out -->
<div class="two-col">
  <button class="tc-btn" onclick="openChangePassword()">
    <div class="tc-ic blue"><i class="fa-solid fa-lock"></i></div>
    <span class="tc-lbl">Change Password</span>
  </button>
  <button class="tc-btn" onclick="openSignOut()">
    <div class="tc-ic indigo"><i class="fa-solid fa-right-from-bracket"></i></div>
    <span class="tc-lbl">Sign Out</span>
  </button>
</div>

<div class="spacer"></div>

<!-- ══════ ACCOUNT SLIDE PAGE ══════ -->
<div class="slide-page" id="accountPage">
  <div class="sp-topbar">
    <button class="sp-back" onclick="closeSlide('accountPage')"><i class="fa-solid fa-chevron-left"></i></button>
    <span class="sp-title">Account</span>
  </div>
  <div class="sp-body">
    <div class="acct-hero">
      <div class="acct-fields">
        <div>
          <div class="acct-field-lbl">Basic account</div>
          <div class="acct-field-val"><?php echo number_format($user['total_deposited'] ?? 0,2);?><span class="unit">USDT</span></div>
        </div>
        <div>
          <div class="acct-field-lbl">Withdrawal account</div>
          <div class="acct-field-val"><?php echo number_format($user['balance'],2);?><span class="unit">USDT</span></div>
        </div>
      </div>
      <div class="acct-illus">
        <svg class="bank-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="55" cy="55" r="38" fill="#e8f5e8" opacity="0.8"/>
          <circle cx="72" cy="28" r="10" fill="#f5c842" stroke="#e6b800" stroke-width="1.5"/>
          <text x="72" y="33" text-anchor="middle" font-size="11" font-weight="bold" fill="#c8860a">$</text>
          <circle cx="82" cy="42" r="8" fill="#f5c842" stroke="#e6b800" stroke-width="1.5"/>
          <text x="82" y="46" text-anchor="middle" font-size="9" font-weight="bold" fill="#c8860a">$</text>
          <circle cx="65" cy="18" r="6" fill="#f5c842" stroke="#e6b800" stroke-width="1.5"/>
          <text x="65" y="22" text-anchor="middle" font-size="7" font-weight="bold" fill="#c8860a">$</text>
          <rect x="20" y="65" width="52" height="5" rx="1" fill="#7f8c8d"/>
          <rect x="16" y="70" width="60" height="6" rx="2" fill="#95a5a6"/>
          <rect x="24" y="48" width="5" height="18" rx="1" fill="#bdc3c7"/>
          <rect x="34" y="48" width="5" height="18" rx="1" fill="#bdc3c7"/>
          <rect x="44" y="48" width="5" height="18" rx="1" fill="#bdc3c7"/>
          <rect x="54" y="48" width="5" height="18" rx="1" fill="#bdc3c7"/>
          <rect x="64" y="48" width="5" height="18" rx="1" fill="#bdc3c7"/>
          <polygon points="18,48 74,48 46,36" fill="#95a5a6"/>
          <rect x="8" y="48" width="18" height="12" rx="3" fill="#e74c3c"/>
          <rect x="9" y="50" width="12" height="2" rx="1" fill="rgba(255,255,255,.7)"/>
          <rect x="9" y="54" width="8" height="2" rx="1" fill="rgba(255,255,255,.7)"/>
          <rect x="14" y="60" width="14" height="10" rx="3" fill="#9b59b6" opacity=".9"/>
          <rect x="15" y="62" width="8" height="2" rx="1" fill="rgba(255,255,255,.7)"/>
          <rect x="15" y="66" width="5" height="2" rx="1" fill="rgba(255,255,255,.7)"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<!-- ══════ FINANCIAL RECORDS SLIDE PAGE ══════ -->
<div class="slide-page" id="financialPage">
  <div class="sp-topbar">
    <button class="sp-back" onclick="closeSlide('financialPage')"><i class="fa-solid fa-chevron-left"></i></button>
    <span class="sp-title">Financial Records</span>
  </div>
  <div class="sp-body">

    <!-- Toggle tabs -->
    <div class="fr-toggle">
      <button class="fr-tab active" id="frTab-basic" onclick="switchFR('basic',this)">Basic account</button>
      <button class="fr-tab" id="frTab-withdrawal" onclick="switchFR('withdrawal',this)">Withdrawal account</button>
    </div>

    <!-- Filter icon -->
    <div class="fr-filter"><button class="fr-filter-btn" title="Filter"><i class="fa-solid fa-filter"></i></button></div>

    <!-- Basic account pane -->
    <div id="frPane-basic" class="fr-pane">
      <div class="fr-empty">
        <div class="fr-empty-icon">
          <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="12" y="8" width="56" height="64" rx="6" fill="#d0dfd0"/>
            <rect x="20" y="24" width="40" height="4" rx="2" fill="#b0c8b0"/>
            <rect x="20" y="34" width="32" height="4" rx="2" fill="#b0c8b0"/>
            <rect x="20" y="44" width="36" height="4" rx="2" fill="#b0c8b0"/>
            <rect x="20" y="54" width="24" height="4" rx="2" fill="#b0c8b0"/>
            <rect x="22" y="14" width="10" height="2" rx="1" fill="#90b090"/>
          </svg>
        </div>
        <div class="fr-empty-txt">No data</div>
      </div>
    </div>

    <!-- Withdrawal account pane -->
    <div id="frPane-withdrawal" class="fr-pane" style="display:none;">

      <!-- Sub-toggle: Task Earnings vs Referral Commissions -->
      <div style="display:flex;gap:8px;padding:0 16px 12px;">
        <button id="wSubTab-task" onclick="switchWSub('task',this)"
          style="flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:var(--gg);color:var(--gp);font-family:'Barlow',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .18s;">
          Task Earnings
        </button>
        <button id="wSubTab-referral" onclick="switchWSub('referral',this)"
          style="flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--tl);font-family:'Barlow',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .18s;">
          Referral Earnings
        </button>
      </div>

      <!-- Task Earnings sub-pane -->
      <div id="wSubPane-task">

        <!-- Successful Withdrawals section -->
        <div style="padding:0 16px 6px;">
          <div class="fr-section-lbl">Successful Withdrawals</div>
          <div id="frWithdrawalsContent">
            <div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>
          </div>
        </div>

        <!-- Task Earnings section -->
        <div style="padding:0 16px 6px;margin-top:10px;">
          <div class="fr-section-lbl">Task Earnings</div>
          <div id="frWithdrawalContent">
            <div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>
          </div>
        </div>

      </div>

      <!-- Referral Earnings sub-pane -->
      <div id="wSubPane-referral" style="display:none;">
        <div id="frCommissionContent">
          <div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>
        </div>
      </div>

    </div><!-- end frPane-withdrawal -->

  </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="cpw-overlay" id="cpwOverlay" onclick="if(event.target===this)closeCPW()">
  <div class="cpw-sheet">
    <div class="cpw-head">
      <span class="cpw-title">Change Password</span>
      <button class="cpw-close" onclick="closeCPW()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cpw-field">
      <div class="cpw-label">Current Password</div>
      <input type="password" class="cpw-input" id="cpwCurrent" placeholder="Enter current password">
    </div>
    <div class="cpw-field">
      <div class="cpw-label">New Password</div>
      <input type="password" class="cpw-input" id="cpwNew" placeholder="Enter new password">
    </div>
    <div class="cpw-field">
      <div class="cpw-label">Confirm New Password</div>
      <input type="password" class="cpw-input" id="cpwConfirm" placeholder="Re-enter new password">
    </div>
    <button class="cpw-btn" onclick="submitCPW()">Update Password</button>
  </div>
</div>

<!-- SIGN OUT CONFIRM -->
<div class="cf-overlay" id="cfOverlay" onclick="if(event.target===this)closeCF()">
  <div class="cf-modal">
    <div class="cf-head">
      <div class="cf-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
      <div class="cf-title">Sign Out</div>
      <div class="cf-sub">Are you sure you want to sign out of your account?</div>
    </div>
    <div class="cf-foot">
      <button class="cf-cancel" onclick="closeCF()">Cancel</button>
      <button class="cf-confirm" onclick="doSignOut()">Sign Out</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<!-- BOTTOM NAV -->
<div class="bnav">
  <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span>Home</span></a>
  <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span>Tasks</span></a>
  <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span>Team</span></a>
  <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span>VIP</span></a>
  <a href="profile.php" class="nav-item active"><i class="fa-regular fa-circle-user ni"></i><span>Me</span></a>
</div>

<!-- CPW FEEDBACK OVERLAY -->
<div class="cpw-feedback" id="cpwFeedback">
  <div class="cpw-feedback-msg" id="cpwFeedbackMsg"></div>
</div>

<div class="foot"></div>

<script>
// ── SLIDE PAGES ──
function openSlide(id){
  document.getElementById(id).classList.add('open');
  document.body.style.overflow='hidden';
}
function closeSlide(id){
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{
  if(e.key==='Escape'){closeSlide('accountPage');closeSlide('financialPage');closeCPW();closeCF();}
});

function openAccount(){ openSlide('accountPage'); }

function openFinancial(){
  openSlide('financialPage');
  loadDeposits();
  loadWithdrawals();
  loadEarnings();
  loadCommissions();
}

// ── FINANCIAL RECORDS TABS ──
function switchFR(tab, btn){
  document.querySelectorAll('.fr-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.fr-pane').forEach(p=>p.style.display='none');
  document.getElementById('frPane-'+tab).style.display='block';
  if(tab === 'basic') loadDeposits();
}

// ── EMPTY STATE HTML ──
function emptyHTML(msg='No data'){
  return `<div class="fr-empty">
    <div class="fr-empty-icon">
      <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="12" y="8" width="56" height="64" rx="6" fill="#d0dfd0"/>
        <rect x="20" y="24" width="40" height="4" rx="2" fill="#b0c8b0"/>
        <rect x="20" y="34" width="32" height="4" rx="2" fill="#b0c8b0"/>
        <rect x="20" y="44" width="36" height="4" rx="2" fill="#b0c8b0"/>
        <rect x="20" y="54" width="24" height="4" rx="2" fill="#b0c8b0"/>
      </svg>
    </div>
    <div class="fr-empty-txt">${msg}</div>
  </div>`;
}

// ── LOAD DEPOSITS (Basic account tab) ──
async function loadDeposits(){
  const pane = document.getElementById('frPane-basic');
  pane.innerHTML = `<div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>`;
  try{
    const r = await fetch('../api/get_deposits.php', {credentials:'same-origin'});
    const d = await r.json();
    if(d.success && d.data && d.data.length > 0){
      const statusColors = { completed:'#22a322', pending:'#f39c12', failed:'#e74c3c' };
      pane.innerHTML = d.data.map(item => {
        const displayAmount = parseFloat(item.detected_amount || item.amount || 0).toFixed(2);
        const currency = item.currency || 'USDT';
        const date = item.completed_at || item.created_at || '—';
        const statusColor = statusColors[item.status] || '#8aaa8a';
        return `<div class="fr-item">
          <div class="fr-item-info">
            <div class="fr-item-type">
              Deposit
              <span style="display:inline-block;margin-left:6px;padding:1px 8px;border-radius:100px;font-size:10px;font-weight:700;background:${statusColor}22;color:${statusColor};text-transform:capitalize;">
                ${item.status}
              </span>
            </div>
            <div class="fr-item-date">${date}</div>
          </div>
          <div class="fr-item-amt" style="color:${item.status==='completed'?'var(--gp)':'var(--tm)'}">
            +${displayAmount} ${currency}
          </div>
        </div>`;
      }).join('') + `<div class="fr-no-more">No more</div>`;
    } else {
      pane.innerHTML = emptyHTML('No deposits yet');
    }
  } catch(e){
    console.error('deposits error', e);
    pane.innerHTML = emptyHTML('Failed to load deposits');
  }
}

// ── LOAD COMPLETED WITHDRAWALS ──
async function loadWithdrawals(){
  const cont = document.getElementById('frWithdrawalsContent');
  cont.innerHTML = `<div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>`;
  try{
    const r = await fetch('../api/get_withdrawals.php', {credentials:'same-origin'});
    const d = await r.json();
    if(d.success && d.data && d.data.length > 0){
      cont.innerHTML = d.data.map(item => `
        <div class="fr-item">
          <div class="fr-item-info">
            <div class="fr-item-type">
              Withdrawal
              <span style="display:inline-block;margin-left:6px;padding:1px 8px;border-radius:100px;font-size:10px;font-weight:700;background:#22a32222;color:#22a322;text-transform:capitalize;">
                Completed
              </span>
            </div>
            <div class="fr-item-date" style="margin-top:2px;">
              ${item.withdrawal_date || '—'}
              &nbsp;·&nbsp;
              <strong>${item.withdrawal_time || ''}</strong>
            </div>
          </div>
          <div class="fr-item-amt" style="color:#e74c3c;">
            -${parseFloat(item.amount || 0).toFixed(2)} ${item.currency || 'USDT'}
          </div>
        </div>`).join('') + `<div class="fr-no-more">No more</div>`;
    } else {
      cont.innerHTML = emptyHTML('No completed withdrawals yet');
    }
  } catch(e){
    console.error('withdrawals error', e);
    cont.innerHTML = emptyHTML('Failed to load withdrawals');
  }
}

// ── LOAD TASK EARNINGS ──
async function loadEarnings(){
  const cont = document.getElementById('frWithdrawalContent');
  cont.innerHTML = `<div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>`;
  try{
    const r = await fetch('../api/get_completed_tasks.php', {credentials:'same-origin'});
    const d = await r.json();
    if(d.success && d.data && d.data.length > 0){
      cont.innerHTML = d.data.map(item => `
        <div class="fr-item">
          <div class="fr-item-info">
            <div class="fr-item-type">${item.task_type || 'Task Earnings'}</div>
            <div class="fr-item-date" style="margin-top:2px;">
              ${item.completion_date || '—'}
              &nbsp;·&nbsp;
              <strong>${item.completed_at || ''}</strong>
            </div>
          </div>
          <div class="fr-item-amt">+${parseFloat(item.earnings || 0).toFixed(2)} USDT</div>
        </div>`).join('') + `<div class="fr-no-more">No more</div>`;
    } else {
      cont.innerHTML = emptyHTML('No task earnings yet');
    }
  } catch(e){
    console.error('earnings error', e);
    cont.innerHTML = emptyHTML('Failed to load earnings');
  }
}

// ── LOAD REFERRAL COMMISSIONS ──
async function loadCommissions(){
  const cont = document.getElementById('frCommissionContent');
  cont.innerHTML = `<div class="fr-empty"><div class="fr-empty-txt">Loading…</div></div>`;
  try{
    const r = await fetch('../api/get_commission_records.php', {credentials:'same-origin'});
    const d = await r.json();
    const sourceLabel = {deposit:'Deposit Commission', task_earning:'Task Commission'};
    const levelLabel  = {1:'Level 1 (14%)', 2:'Level 2 (2%)', 3:'Level 3 (1%)'};
    if(d.success && d.data && d.data.length > 0){
      cont.innerHTML = d.data.map(item => `
        <div class="fr-item">
          <div class="fr-item-info">
            <div class="fr-item-type">
              ${sourceLabel[item.source_type] || 'Referral Commission'}
              <span style="font-size:10px;color:var(--tl);font-weight:600;margin-left:4px;">
                ${levelLabel[item.commission_level] || ''}
              </span>
            </div>
            <div class="fr-item-date" style="margin-top:2px;">
              From: <strong>${item.referee_username || 'User'}</strong>
              &nbsp;·&nbsp; Source: $${parseFloat(item.source_amount || 0).toFixed(2)}
            </div>
            <div class="fr-item-date">${item.created_at || '—'}</div>
          </div>
          <div class="fr-item-amt" style="color:var(--gold);">
            +${parseFloat(item.commission_amount || 0).toFixed(2)} USDT
          </div>
        </div>`).join('') + `<div class="fr-no-more">No more</div>`;
    } else {
      cont.innerHTML = emptyHTML('No referral earnings yet');
    }
  } catch(e){
    console.error('commissions error', e);
    cont.innerHTML = emptyHTML('Failed to load commissions');
  }
}

// ── SUB-TAB SWITCHER ──
function switchWSub(tab, btn){
  document.querySelectorAll('[id^="wSubTab-"]').forEach(b=>{
    b.style.background='transparent';
    b.style.color='var(--tl)';
    b.style.borderColor='var(--border)';
  });
  btn.style.background='var(--gg)';
  btn.style.color='var(--gp)';
  btn.style.borderColor='var(--border2)';
  document.querySelectorAll('[id^="wSubPane-"]').forEach(p=>p.style.display='none');
  document.getElementById('wSubPane-'+tab).style.display='block';
}

// ── CHANGE PASSWORD ──
function openChangePassword(){ document.getElementById('cpwOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeCPW(){ document.getElementById('cpwOverlay').classList.remove('open'); document.body.style.overflow=''; }
// ── CHANGE PASSWORD ──
function showCPWFeedback(msg, isError = false) {
  const fb    = document.getElementById('cpwFeedback');
  const fbMsg = document.getElementById('cpwFeedbackMsg');
  const icon  = isError ? 'fa-circle-xmark' : 'fa-circle-check';
  fbMsg.style.color = isError ? '#ff7f7f' : '#fff';
  fbMsg.innerHTML = `<i class="fa-solid ${icon}"></i> ${msg}`;
  fb.classList.add('show');
  setTimeout(() => { fb.classList.remove('show'); }, isError ? 2400 : 1800);
}

async function submitCPW(){
  const cur = document.getElementById('cpwCurrent').value;
  const nw  = document.getElementById('cpwNew').value;
  const cf  = document.getElementById('cpwConfirm').value;

  // ── Client-side validation — shown via overlay ──
  if(!cur || !nw || !cf) {
    showCPWFeedback('Please fill in all fields.', true);
    return;
  }
  if(nw !== cf) {
    showCPWFeedback('New passwords do not match.', true);
    return;
  }
  if(nw.length < 6) {
    showCPWFeedback('Password must be at least 6 characters.', true);
    return;
  }

  // ── Show loading ──
  const fb    = document.getElementById('cpwFeedback');
  const fbMsg = document.getElementById('cpwFeedbackMsg');
  fbMsg.style.color = '#fff';
  fbMsg.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Updating password…`;
  fb.classList.add('show');

  try{
    const r = await fetch('../api/change_password.php', {
      method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({current_password: cur, new_password: nw})
    });
    const d = await r.json();
    if(d.success){
      fbMsg.style.color = '#fff';
      fbMsg.innerHTML = `<i class="fa-solid fa-circle-check"></i> Password updated successfully!`;
      setTimeout(() => {
        fb.classList.remove('show');
        closeCPW();
        ['cpwCurrent','cpwNew','cpwConfirm'].forEach(id => document.getElementById(id).value = '');
      }, 1800);
    } else {
      // ── Server errors (wrong current password, etc.) ──
      fb.classList.remove('show');
      showCPWFeedback(d.message || 'Failed to update password.', true);
    }
  } catch(e){
    fb.classList.remove('show');
    showCPWFeedback('Network error. Please try again.', true);
  }
}
// ── SIGN OUT ──
function openSignOut(){ document.getElementById('cfOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeCF(){ document.getElementById('cfOverlay').classList.remove('open'); document.body.style.overflow=''; }
function doSignOut(){
  document.cookie.split(';').forEach(c=>{document.cookie=c.replace(/^ +/,'').replace(/=.*/,'=;expires='+new Date().toUTCString()+';path=/');});
  window.location.href='login.php';
}

// ── TOAST ──
let _tt;
function showToast(msg,isErr=false){
  const el=document.getElementById('toast');clearTimeout(_tt);
  el.innerHTML=`<i class="fa-solid fa-${isErr?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  el.className='toast show'+(isErr?' err':'');
  _tt=setTimeout(()=>el.classList.remove('show'),3500);
}
// ── AUTO-OPEN FROM URL PARAM ──
document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('open') === 'financial') {
    // Small delay so the page renders first
    setTimeout(function() {
      openFinancial();
    }, 120);
    // Clean the URL without reloading
    history.replaceState(null, '', window.location.pathname);
  }
});
</script>
</body>
</html>

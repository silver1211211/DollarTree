<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
$page_title = t('team', 'Team');
?>
<?php
$spin_chances = (int)($user['spin_chances'] ?? 0);
include __DIR__ . '/spin_wheel.php';
include 'reviews_component.php'; 
?>
<!DOCTYPE html>
<html lang="<?php echo get_language(); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title><?php echo $page_title; ?> — Dollar Tree</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --gp:#1a7a1a; --gb:#22a322; --gl:#2db82d;
  --gg:rgba(26,122,26,.10); --gg2:rgba(26,122,26,.06);
  --bg:#f0f5f0; --white:#fff;
  --border:#d4ebd4; --border2:#b8d8b8;
  --tp:#0d1a0d; --ts:#2d4a2d; --tm:#5a7a5a; --tl:#8aaa8a;
  --nav-h:64px; --r:14px; --r2:18px;
  --sh:0 2px 10px rgba(0,0,0,.06); --shm:0 4px 20px rgba(0,0,0,.09);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{font-family:'Barlow',sans-serif;background:var(--bg);color:var(--tp);min-height:100vh;padding-bottom:calc(var(--nav-h)+24px);overflow-x:hidden;-webkit-font-smoothing:antialiased;}

/* ─── DARK HERO ─── */
.hero-wrap{background:#0e1610;position:relative;overflow:hidden;}
.hero-wrap::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(26,122,26,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(26,122,26,.07) 1px,transparent 1px);background-size:40px 40px;z-index:0;pointer-events:none;}

.htopbar{position:relative;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:16px 18px 0;}
.htb-brand{display:flex;align-items:center;gap:9px;}
.htb-dot{width:10px;height:10px;border-radius:50%;background:var(--gp);box-shadow:0 0 8px rgba(26,122,26,.6);}
.htb-name{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:900;color:#fff;letter-spacing:.04em;}
.lang-btn{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:100px;padding:5px 12px;color:rgba(255,255,255,.75);font-size:12px;font-weight:600;cursor:pointer;border:none;}

/* coin scene */
.coin-stage{position:relative;height:254px;z-index:2;}
.ring{position:absolute;border-radius:50%;border:1px solid rgba(26,122,26,.18);pointer-events:none;}
.ring-1{width:300px;height:300px;top:50%;left:60%;transform:translate(-50%,-52%);animation:rp 4s ease-in-out infinite;}
.ring-2{width:200px;height:200px;top:50%;left:60%;transform:translate(-50%,-52%);animation:rp 4s ease-in-out infinite .9s;}
@keyframes rp{0%,100%{opacity:.3;transform:translate(-50%,-52%) scale(1);}50%{opacity:.7;transform:translate(-50%,-52%) scale(1.05);}}

.coin-main{position:absolute;top:18px;right:48px;width:132px;height:132px;border-radius:50%;background:conic-gradient(from 120deg,#7cf07c,#1a7a1a 35%,#0a420a 65%,#1a7a1a 80%,#7cf07c);box-shadow:0 12px 52px rgba(26,122,26,.6),0 0 0 3px rgba(120,240,120,.18);display:flex;align-items:center;justify-content:center;animation:fa 4s ease-in-out infinite;z-index:4;}
.coin-inner{width:92px;height:92px;border-radius:50%;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;}
.coin-inner .cs{font-family:'Barlow Condensed',sans-serif;font-size:46px;font-weight:900;color:rgba(0,0,0,.6);line-height:1;}
@keyframes fa{0%,100%{transform:translateY(0) rotate(-4deg);}50%{transform:translateY(-14px) rotate(3deg);}}

.coin-sec{position:absolute;bottom:40px;left:42px;width:86px;height:86px;border-radius:50%;background:conic-gradient(from 100deg,#686868,#282828 40%,#0e0e0e 65%,#282828 80%,#686868);box-shadow:0 6px 22px rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;animation:fb 4s ease-in-out infinite 1.6s;z-index:3;}
.coin-sec-in{width:60px;height:60px;border-radius:50%;border:1.5px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;}
.coin-sec-in .cs{font-family:'Barlow Condensed',sans-serif;font-size:26px;font-weight:900;color:rgba(255,255,255,.25);line-height:1;}
@keyframes fb{0%,100%{transform:translateY(0) rotate(3deg);}50%{transform:translateY(-10px) rotate(-2deg);}}

.ticket{position:absolute;bottom:46px;right:20px;z-index:5;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.13);border-radius:8px;padding:7px 11px;font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:800;color:rgba(255,255,255,.38);letter-spacing:.08em;transform:rotate(7deg);}

.fdot{position:absolute;border-radius:50%;pointer-events:none;}
.fd1{width:7px;height:7px;background:#a8e63a;top:36px;left:40%;z-index:3;animation:tw 2s infinite;}
.fd2{width:4px;height:4px;background:#fff;top:72px;right:26%;z-index:3;animation:tw 2.5s infinite .5s;opacity:.35;}
.fd3{width:5px;height:5px;background:#4ade4a;bottom:56px;right:20%;z-index:3;animation:tw 3s infinite 1s;opacity:.6;}
.fd4{width:4px;height:4px;background:rgba(255,255,255,.4);bottom:36px;left:32%;z-index:3;animation:tw 2.2s infinite 1.5s;opacity:.4;}
@keyframes tw{0%,100%{opacity:.12;transform:scale(1);}50%{opacity:.95;transform:scale(1.7);}}

.float-btns{position:absolute;right:0;top:50%;transform:translateY(-50%);z-index:20;display:flex;flex-direction:column;gap:2px;}
.float-btn{width:40px;height:40px;background:rgba(255,255,255,.95);border-radius:8px 0 0 8px;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;box-shadow:-2px 2px 10px rgba(0,0,0,.25);color:#333;}

/* invite */
.invite-section{position:relative;z-index:5;padding:0 18px 22px;background:#0e1610;}
.invite-inner{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:18px;}
.inv-label{font-size:12px;color:rgba(255,255,255,.44);margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.inv-label i{color:#ffc107;font-size:13px;}
.inv-code-row{display:flex;align-items:center;gap:12px;margin-bottom:11px;}
.inv-code{font-family:'Barlow Condensed',sans-serif;font-size:30px;font-weight:900;color:#fff;letter-spacing:.04em;}
.inv-copy-pill{background:var(--gp);color:#fff;border:none;padding:5px 16px;border-radius:100px;font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:all .18s;}
.inv-copy-pill:hover{background:var(--gb);}
.inv-copy-pill.copied{background:#0d4d0d;}
.inv-link-row{display:flex;align-items:center;gap:10px;}
.inv-link{font-size:12px;color:rgba(255,255,255,.38);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
.inv-link-copy{background:rgba(26,122,26,.3);color:#7be87b;border:1px solid rgba(26,122,26,.48);padding:5px 13px;border-radius:100px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0;transition:all .18s;}
.inv-link-copy:hover{background:rgba(26,122,26,.5);}

/* ─── LIGHT BODY ─── */
.period-bar{display:flex;align-items:center;gap:8px;padding:14px 16px 10px;font-size:13px;font-weight:700;color:var(--ts);}
.period-bar i{font-size:13px;}

.stats-card{margin:0 16px;background:var(--white);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);}
.stats-row+.stats-row{border-top:1px solid var(--border);}
.stat-cell{padding:14px 8px;text-align:center;border-right:1px solid var(--border);}
.stat-cell:last-child{border-right:none;}
.stat-label{font-size:10px;font-weight:600;color:var(--tl);margin-bottom:5px;line-height:1.3;}
.stat-value{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:800;color:var(--tp);line-height:1;}
.stat-value.g{color:var(--gp);}

.levels-wrap{padding:12px 16px 0;display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.lvl-card{border-radius:16px;overflow:hidden;position:relative;box-shadow:var(--shm);display:flex;flex-direction:column;}
.lvl-1{background:linear-gradient(160deg,#3a2d8f 0%,#1a6ab8 100%);}
.lvl-2{background:linear-gradient(160deg,#0da89c 0%,#0ecf7a 100%);}
.lvl-3{background:linear-gradient(160deg,#c858a8 0%,#9060e0 100%);}

.lvl-gem{padding:10px 10px 0;}
.gem-svg{width:42px;height:42px;filter:drop-shadow(0 3px 8px rgba(0,0,0,.35));}
.lvl-content{padding:8px 10px 2px;flex:1;}
.lvl-name{font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:900;color:#fff;letter-spacing:.05em;margin-bottom:9px;}
.lvl-row{margin-bottom:7px;}
.lvl-rl{font-size:9px;font-weight:700;color:rgba(255,255,255,.52);text-transform:uppercase;letter-spacing:.07em;margin-bottom:1px;}
.lvl-rv{font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:800;color:#fff;}
.lvl-rv.accent{color:rgba(255,255,200,.9);}

.lvl-btn{margin:10px 10px 10px;background:#000;color:#fff;border:none;border-radius:8px;padding:9px 0;width:calc(100% - 20px);font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:all .18s;letter-spacing:.03em;}
.lvl-btn:hover{background:#1a1a1a;}

/* ─── NAV ─── */
.bnav{position:fixed;bottom:0;left:0;right:0;z-index:300;height:var(--nav-h);background:var(--white);border-top:1px solid var(--border);display:flex;align-items:stretch;box-shadow:0 -2px 16px rgba(0,0,0,.07);}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0 4px;text-decoration:none;color:var(--tl);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;transition:all .18s;position:relative;cursor:pointer;}
.nav-item::before{content:'';position:absolute;top:0;left:25%;right:25%;height:2px;border-radius:0 0 3px 3px;background:var(--gp);transform:scaleX(0);transform-origin:center;transition:transform .2s;}
.nav-item.active{color:var(--gp);}
.nav-item.active::before{transform:scaleX(1);}
.ni{font-size:20px;transition:transform .18s;}
.nav-item.active .ni{transform:scale(1.1);}

.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:500;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.spacer{height:10px;}
.foot{
    padding-bottom: 100px;
}
.tb-logo{height:20px;width:auto;object-fit:contain;flex-shrink:0; scale:1.2;margin:0px 5px;}
</style>
</head>
<body>

<!-- DARK HERO -->
<div class="hero-wrap">
  <div class="htopbar">
    <div class="htb-brand">
      <div class="htb-dot"></div>
     <img class="tb-logo" src="images/logotree.png" alt="Dollar Tree">
    </div>
    <!--<button class="lang-btn"><i class="fa-solid fa-globe"></i> English <i class="fa-solid fa-chevron-down"></i></button>-->
  </div>

  <div class="coin-stage">
    <div class="ring ring-1"></div>
    <div class="ring ring-2"></div>
    <div class="coin-main"><div class="coin-inner"><span class="cs">$</span></div></div>
    <div class="coin-sec"><div class="coin-sec-in"><span class="cs">$</span></div></div>
    <div class="ticket">:26:</div>
    <div class="fdot fd1"></div>
    <div class="fdot fd2"></div>
    <div class="fdot fd3"></div>
    <div class="fdot fd4"></div>
  </div>

  <!--<div class="float-btns">-->
  <!--  <div class="float-btn"><i class="fa-solid fa-headset"></i></div>-->
  <!--  <div class="float-btn"><i class="fa-regular fa-envelope"></i></div>-->
  <!--</div>-->

  <div class="invite-section">
    <div class="invite-inner">
      <div class="inv-label"><i class="fa-solid fa-star"></i> Invitation code：</div>
      <div class="inv-code-row">
        <span class="inv-code" id="invCode"><?php echo htmlspecialchars($user['referral_code']); ?></span>
        <button class="inv-copy-pill" id="codeBtn" onclick="copyCode()">Copy</button>
      </div>
      <div class="inv-link-row">
        <span class="inv-link" id="refLink">Share your referral link and start earning</span>
        <button class="inv-link-copy" onclick="copyLink()">Copy</button>
      </div>
    </div>
  </div>
</div>

<!-- LIGHT BODY -->
<div class="period-bar">
  <i class="fa-regular fa-calendar-check"></i> Selection period
</div>

<div class="stats-card">
  <div class="stats-row">
    <div class="stat-cell">
      <div class="stat-label">Team size</div>
      <div class="stat-value" id="teamSize">0</div>
    </div>
    <div class="stat-cell">
      <div class="stat-label">Team recharge</div>
      <div class="stat-value g" id="teamRecharge">$0.00</div>
    </div>
    <div class="stat-cell">
      <div class="stat-label">Team Withdrawal</div>
      <div class="stat-value g" id="teamWithdrawal">$0.00</div>
    </div>
  </div>
  <div class="stats-row">
    <div class="stat-cell">
      <div class="stat-label">New team</div>
      <div class="stat-value" id="newTeam">0</div>
    </div>
    <div class="stat-cell">
      <div class="stat-label">First time recharge</div>
      <div class="stat-value" id="firstRecharge">0</div>
    </div>
    <div class="stat-cell">
      <div class="stat-label">First withdrawal</div>
      <div class="stat-value" id="firstWithdrawal">0</div>
    </div>
  </div>
</div>

<div class="levels-wrap">
  <!-- LEVEL 1 -->
  <div class="lvl-card lvl-1">
    <div class="lvl-gem">
      <svg class="gem-svg" viewBox="0 0 48 48" fill="none">
        <polygon points="24,4 40,16 40,32 24,44 8,32 8,16" fill="url(#g1)"/>
        <polygon points="24,4 40,16 24,20 8,16" fill="rgba(255,255,255,.28)"/>
        <polygon points="8,16 24,20 8,32" fill="rgba(255,255,255,.09)"/>
        <polygon points="40,16 40,32 24,20" fill="rgba(0,0,0,.14)"/>
        <defs><linearGradient id="g1" x1="8" y1="4" x2="40" y2="44" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#b0b8ff"/><stop offset="100%" stop-color="#5555cc"/></linearGradient></defs>
      </svg>
    </div>
    <div class="lvl-content">
      <div class="lvl-name">LEVEL 1</div>
      <div class="lvl-row"><div class="lvl-rl">Register/Valid</div><div class="lvl-rv" id="l1reg">0/0</div></div>
      <div class="lvl-row"><div class="lvl-rl">Commission Percentage</div><div class="lvl-rv accent">14%</div></div>
      <div class="lvl-row"><div class="lvl-rl">Total Income</div><div class="lvl-rv" id="l1inc">0</div></div>
    </div>
    <!--<button class="lvl-btn" onclick="viewLevel(1)">Details</button>-->
  </div>

  <!-- LEVEL 2 -->
  <div class="lvl-card lvl-2">
    <div class="lvl-gem">
      <svg class="gem-svg" viewBox="0 0 48 48" fill="none">
        <polygon points="24,4 40,16 40,32 24,44 8,32 8,16" fill="url(#g2)"/>
        <polygon points="24,4 40,16 24,20 8,16" fill="rgba(255,255,255,.28)"/>
        <polygon points="8,16 24,20 8,32" fill="rgba(255,255,255,.09)"/>
        <polygon points="40,16 40,32 24,20" fill="rgba(0,0,0,.14)"/>
        <defs><linearGradient id="g2" x1="8" y1="4" x2="40" y2="44" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#60e8d8"/><stop offset="100%" stop-color="#0da878"/></linearGradient></defs>
      </svg>
    </div>
    <div class="lvl-content">
      <div class="lvl-name">LEVEL 2</div>
      <div class="lvl-row"><div class="lvl-rl">Register/Valid</div><div class="lvl-rv" id="l2reg">0/0</div></div>
      <div class="lvl-row"><div class="lvl-rl">Commission Percentage</div><div class="lvl-rv accent">2%</div></div>
      <div class="lvl-row"><div class="lvl-rl">Total Income</div><div class="lvl-rv" id="l2inc">0</div></div>
    </div>
    <!--<button class="lvl-btn" onclick="viewLevel(2)">Details</button>-->
  </div>

  <!-- LEVEL 3 -->
  <div class="lvl-card lvl-3">
    <div class="lvl-gem">
      <svg class="gem-svg" viewBox="0 0 48 48" fill="none">
        <polygon points="24,4 40,16 40,32 24,44 8,32 8,16" fill="url(#g3)"/>
        <polygon points="24,4 40,16 24,20 8,16" fill="rgba(255,255,255,.28)"/>
        <polygon points="8,16 24,20 8,32" fill="rgba(255,255,255,.09)"/>
        <polygon points="40,16 40,32 24,20" fill="rgba(0,0,0,.14)"/>
        <defs><linearGradient id="g3" x1="8" y1="4" x2="40" y2="44" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#f0a0d8"/><stop offset="100%" stop-color="#8850e0"/></linearGradient></defs>
      </svg>
    </div>
    <div class="lvl-content">
      <div class="lvl-name">LEVEL 3</div>
      <div class="lvl-row"><div class="lvl-rl">Register/Valid</div><div class="lvl-rv" id="l3reg">0/0</div></div>
      <div class="lvl-row"><div class="lvl-rl">Commission Percentage</div><div class="lvl-rv accent">1%</div></div>
      <div class="lvl-row"><div class="lvl-rl">Total Income</div><div class="lvl-rv" id="l3inc">0</div></div>
    </div>
    <!--<button class="lvl-btn" onclick="viewLevel(3)">Details</button>-->
  </div>
</div>
<div class="spacer"></div>

<div class="toast" id="toast"></div>

<div class="bnav">
  <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span>Home</span></a>
  <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span>Task</span></a>
  <a href="team.php" class="nav-item active"><i class="fa-solid fa-users ni"></i><span>Team</span></a>
  <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span>VIP</span></a>
  <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span>Me</span></a>
</div>
<div class="foot"></div>
<script>
const token = localStorage.getItem('auth_token') || '';
const baseUrl = location.origin;
document.getElementById('refLink').textContent = baseUrl + '/dollar-tree-platform/user/register.php?ref=' + document.getElementById('invCode').textContent.trim(); 
async function loadTeam() {
  try {
   const r = await fetch('../api/get_team_info.php', { 
  credentials: 'include',
  headers: { 'Authorization': 'Bearer ' + token } 
});
    const d = await r.json();
    if (!d.success) return;
    const td = d.data;
    document.getElementById('teamSize').textContent = td.team_size ?? 0;
    document.getElementById('teamRecharge').textContent = '$' + parseFloat(td.team_recharge ?? 0).toFixed(2);
    document.getElementById('teamWithdrawal').textContent = '$' + parseFloat(td.team_withdrawal ?? 0).toFixed(2);
    document.getElementById('newTeam').textContent = td.new_team ?? 0;
    document.getElementById('firstRecharge').textContent = td.first_recharge ?? 0;
    document.getElementById('firstWithdrawal').textContent = td.first_withdrawal ?? 0;
    ['1','2','3'].forEach(n => {
      const lv = td['level'+n] ?? {};
      document.getElementById('l'+n+'reg').textContent = (lv.registered??0)+'/'+(lv.valid??0);
      document.getElementById('l'+n+'inc').textContent = parseFloat(lv.income??0).toFixed(2);
    });
  } catch(e) { console.error(e); }
}
function copyCode() {
  copy(document.getElementById('invCode').textContent.trim(), 'codeBtn', 'Code copied!');
}
function copyLink() {
  copy(document.getElementById('refLink').textContent.trim(), null, 'Link copied!');
}
function copy(text, btnId, msg) {
  try {
    const i = document.createElement('input');
    i.value = text;
    i.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
    document.body.appendChild(i);
    i.focus(); i.select();
    document.execCommand('copy');
    i.remove();
    if (btnId) {
      const b = document.getElementById(btnId);
      const o = b.textContent;
      b.textContent = '✓ Copied';
      b.classList.add('copied');
      setTimeout(() => { b.textContent = o; b.classList.remove('copied'); }, 2500);
    }
    showToast(msg);
  } catch(e) {
    showToast('Copy failed — please copy manually.', true);
  }
}
function viewLevel(n) { showToast('Level '+n+' details coming soon!'); }
let _tt;
function showToast(msg, err=false) {
  const el=document.getElementById('toast');clearTimeout(_tt);
  el.innerHTML=`<i class="fa-solid fa-${err?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  el.className='toast show'+(err?' err':'');
  _tt=setTimeout(()=>el.classList.remove('show'),3000);
}
document.addEventListener('DOMContentLoaded', loadTeam);
</script>
</body>
</html>
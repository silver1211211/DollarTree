<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Dashboard'; $active_nav = 'dashboard';
$total_users     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetchColumn();
$new_today       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE() AND is_admin=0")->fetchColumn();
$total_balance   = (float)($pdo->query("SELECT SUM(balance) FROM users WHERE is_admin=0")->fetchColumn()??0);
$total_deposited = (float)($pdo->query("SELECT SUM(amount) FROM pending_deposits WHERE status='completed'")->fetchColumn()??0);
$total_withdrawn = (float)($pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='completed'")->fetchColumn()??0);
$pending_w_cnt   = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();
$pending_w_amt   = (float)($pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='pending'")->fetchColumn()??0);
$pending_d_cnt   = (int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status='pending'")->fetchColumn();
$crawl_active    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE craw_mode=1 AND is_admin=0")->fetchColumn();
$recent_w = $pdo->query("
    SELECT w.*, u.username 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'completed'
    ORDER BY w.requested_at DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
$recent_d = $pdo->query("
    SELECT d.*, u.username 
    FROM pending_deposits d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'completed'
    ORDER BY d.created_at DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
$svip_dist= $pdo->query("SELECT svip_level,COUNT(*) as cnt FROM users WHERE is_admin=0 GROUP BY svip_level ORDER BY svip_level")->fetchAll(PDO::FETCH_ASSOC);
$top_users= $pdo->query("SELECT username,balance,total_deposited,svip_level FROM users WHERE is_admin=0 ORDER BY total_deposited DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$topbar_actions = '<a href="announcements.php" class="tb-chip primary"><i class="fa-solid fa-bullhorn"></i> <span class="hide-xs">New </span>Announce</a>';
include '_layout.php';
function status_badge($s){
  $map=['pending'=>['b-amber','clock'],'completed'=>['b-green','circle-check'],'approved'=>['b-blue','thumbs-up'],'rejected'=>['b-red','ban'],'active'=>['b-green','circle-check'],'inactive'=>['b-gray','circle-xmark']];
  $d=$map[$s]??['b-gray','circle'];
  return "<span class='badge {$d[0]}'><i class='fa-solid fa-{$d[1]}'></i> ".ucfirst($s)."</span>";
}
?>

<style>
/* Dashboard mobile extras */
.dash-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.dash-grid-2-b{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:640px){
  .dash-grid-2,.dash-grid-2-b{grid-template-columns:1fr;}
}
/* Compact table for mobile: hide less-critical columns */
@media(max-width:480px){
  .hide-mobile{display:none;}
  .hide-xs{display:none;}
}
</style>

<div class="stats-grid">
  <div class="stat-card">
    <div class="sc-lbl">Total Users</div>
    <div class="sc-val"><?php echo number_format($total_users);?></div>
    <div class="sc-sub up"><i class="fa-solid fa-arrow-up"></i> <?php echo $new_today;?> today</div>
    <i class="fa-solid fa-users sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Platform Bal</div>
    <div class="sc-val">$<?php echo number_format($total_balance,0);?></div>
    <div class="sc-sub"><i class="fa-solid fa-wallet"></i> All users</div>
    <i class="fa-solid fa-dollar-sign sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Deposited</div>
    <div class="sc-val">$<?php echo number_format($total_deposited,0);?></div>
    <div class="sc-sub <?php echo $pending_d_cnt>0?'warn':'';?>">
      <i class="fa-solid fa-clock"></i> <?php echo $pending_d_cnt;?> pending
    </div>
    <i class="fa-solid fa-arrow-down-to-line sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Withdrawn</div>
    <div class="sc-val">$<?php echo number_format($total_withdrawn,0);?></div>
    <div class="sc-sub <?php echo $pending_w_cnt>0?'warn':'';?>">
      <i class="fa-solid fa-clock"></i> $<?php echo number_format($pending_w_amt,0);?> pending
    </div>
    <i class="fa-solid fa-arrow-up-from-line sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Crawl Active</div>
    <div class="sc-val"><?php echo $crawl_active;?></div>
    <div class="sc-sub"><i class="fa-solid fa-spider"></i> In crawl</div>
    <i class="fa-solid fa-spider sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Pending W/D</div>
    <div class="sc-val <?php echo $pending_w_cnt>0?'text-amber':'';?>"><?php echo $pending_w_cnt;?></div>
    <div class="sc-sub warn"><i class="fa-solid fa-triangle-exclamation"></i> Review</div>
    <i class="fa-solid fa-hourglass-half sc-bg-icon"></i>
  </div>
</div>

<div class="dash-grid-2">
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-arrow-down-to-line"></i> Recent Deposits</div>
      <a href="deposits.php" class="btn btn-ghost btn-xs">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Amount</th>
            <th class="hide-mobile">Status</th>
            <th class="hide-mobile">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recent_d as $d): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($d['username']);?></strong>
              <div class="hide-mobile" style="font-size:11px;color:var(--tl);"><?php echo date('M d H:i',strtotime($d['created_at']));?></div>
            </td>
            <td class="text-green">$<?php echo number_format($d['detected_amount'],2);?></td>
            <td class="hide-mobile"><?php echo status_badge($d['status']);?></td>
            <td class="muted hide-mobile" style="font-size:11px;"><?php echo date('M d H:i',strtotime($d['created_at']));?></td>
          </tr>
          <?php endforeach; if(empty($recent_d)):?>
          <tr class="empty-row"><td colspan="4"><i class="fa-solid fa-inbox"></i> No deposits</td></tr>
          <?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-arrow-up-from-line"></i> Recent Withdrawals</div>
      <a href="withdrawals.php" class="btn btn-ghost btn-xs">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Amount</th>
            <th class="hide-mobile">Status</th>
            <th class="hide-mobile">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recent_w as $w): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($w['username']);?></strong>
              <div class="hide-mobile" style="font-size:11px;color:var(--tl);"><?php echo date('M d H:i',strtotime($w['requested_at']));?></div>
            </td>
            <td class="text-red">$<?php echo number_format($w['amount'],2);?></td>
            <td class="hide-mobile"><?php echo status_badge($w['status']);?></td>
            <td class="muted hide-mobile" style="font-size:11px;"><?php echo date('M d H:i',strtotime($w['requested_at']));?></td>
          </tr>
          <?php endforeach; if(empty($recent_w)):?>
          <tr class="empty-row"><td colspan="4"><i class="fa-solid fa-inbox"></i> No withdrawals</td></tr>
          <?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="dash-grid-2-b">
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-crown"></i> SVIP Distribution</div></div>
    <?php foreach($svip_dist as $row):
      $pct = $total_users > 0 ? round($row['cnt']/$total_users*100) : 0; ?>
    <div style="margin-bottom:10px;">
      <div class="flex fc fb" style="margin-bottom:4px;">
        <span style="font-size:12px;font-weight:700;color:var(--ts);">SVIP <?php echo $row['svip_level'];?></span>
        <span style="font-size:11px;color:var(--tl);"><?php echo $row['cnt'];?> (<?php echo $pct;?>%)</span>
      </div>
      <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
        <div style="height:100%;width:<?php echo $pct;?>%;background:var(--gp);border-radius:3px;transition:width .4s;"></div>
      </div>
    </div>
    <?php endforeach; if(empty($svip_dist))echo '<p class="muted" style="font-size:13px;text-align:center;padding:20px;">No data</p>'; ?>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-trophy"></i> Top Depositors</div></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Deposited</th>
            <th class="hide-mobile">SVIP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($top_users as $i=>$u): ?>
          <tr>
            <td class="muted"><?php echo $i+1;?></td>
            <td><strong><?php echo htmlspecialchars($u['username']);?></strong></td>
            <td class="text-green">$<?php echo number_format($u['total_deposited'],0);?></td>
            <td class="hide-mobile"><span class="badge b-amber"><i class="fa-solid fa-crown"></i> <?php echo $u['svip_level'];?></span></td>
          </tr>
          <?php endforeach; if(empty($top_users)):?>
          <tr class="empty-row"><td colspan="4">No data</td></tr>
          <?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '_layout_end.php'; ?>
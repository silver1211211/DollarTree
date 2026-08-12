<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Commissions'; $active_nav = 'commissions';

$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&$_POST['action']==='update_rates'){
  for($i=1;$i<=3;$i++){
    $rate=(float)$_POST["rate_$i"];
    $pdo->prepare("UPDATE referral_settings SET commission_rate=?,updated_at=NOW() WHERE level=?")->execute([$rate,$i]);
  }
  $msg="Commission rates updated";
}

$commissions=$pdo->query("SELECT c.*,r.username as referrer,rf.username as referred FROM commissions c JOIN users r ON c.referrer_user_id=r.id JOIN users rf ON c.referred_user_id=rf.id ORDER BY c.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$ref_settings=$pdo->query("SELECT * FROM referral_settings ORDER BY level ASC")->fetchAll(PDO::FETCH_ASSOC);
$stats=[
  'total'     => $pdo->query("SELECT SUM(commission_amount) FROM commissions WHERE status='paid'")->fetchColumn()??0,
  'pending'   => $pdo->query("SELECT COUNT(*) FROM commissions WHERE status='pending'")->fetchColumn(),
  'total_cnt' => $pdo->query("SELECT COUNT(*) FROM commissions")->fetchColumn()
];
include '_layout.php';
?>

<style>
.comm-layout{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;}
/* Mobile: stack log above rates panel */
@media(max-width:680px){
  .comm-layout{grid-template-columns:1fr;}
  /* rates panel moves to top on mobile via order */
  .comm-rates-panel{order:-1;}
  /* Compact commission cards instead of table */
  .comm-table-wrap{display:none;}
  .comm-card-list{display:block;}
}
.comm-card-list{display:none;}
.comm-card-item{
  background:var(--bg);border:1px solid var(--border);border-radius:var(--r);
  padding:12px 13px;margin-bottom:8px;
}
.comm-card-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
.comm-card-users{font-size:13px;color:var(--ts);}
.comm-card-users strong{color:var(--tp);}
.comm-card-amount{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:900;color:#3dd63d;}
.comm-card-meta{display:flex;flex-wrap:wrap;gap:6px;align-items:center;}
</style>

<?php if($msg):?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div>
<?php endif;?>

<div class="comm-layout">

  <!-- Left: stats + log -->
  <div>
    <div class="stats-grid" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:14px;">
      <div class="stat-card">
        <div class="sc-lbl">Total Paid Out</div>
        <div class="sc-val text-green">$<?php echo number_format($stats['total'],2);?></div>
        <i class="fa-solid fa-hand-holding-dollar sc-bg-icon"></i>
      </div>
      <div class="stat-card">
        <div class="sc-lbl">Pending</div>
        <div class="sc-val text-amber"><?php echo $stats['pending'];?></div>
        <i class="fa-solid fa-clock sc-bg-icon"></i>
      </div>
      <div class="stat-card">
        <div class="sc-lbl">Total Records</div>
        <div class="sc-val"><?php echo number_format($stats['total_cnt']);?></div>
        <i class="fa-solid fa-percent sc-bg-icon"></i>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-percent"></i> Commission Log</div></div>

      <!-- Desktop table -->
      <div class="table-wrap comm-table-wrap"><table>
        <thead><tr><th>Referrer</th><th>Referred</th><th>Level</th><th>Rate</th><th>Source</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($commissions as $c):
          $sm=['paid'=>'b-green','pending'=>'b-amber'];$sc=$sm[$c['status']]??'b-gray';
        ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($c['referrer']);?></strong></td>
          <td class="muted"><?php echo htmlspecialchars($c['referred']);?></td>
          <td><span class="badge b-blue">L<?php echo $c['commission_level'];?></span></td>
          <td class="mono"><?php echo $c['commission_rate'];?>%</td>
          <td><span class="badge b-gray"><?php echo htmlspecialchars($c['source_type']);?></span></td>
          <td class="text-green"><strong>$<?php echo number_format($c['commission_amount'],4);?></strong></td>
          <td><span class="badge <?php echo $sc;?>"><?php echo ucfirst($c['status']);?></span></td>
          <td class="muted" style="font-size:11px;"><?php echo date('M d H:i',strtotime($c['created_at']));?></td>
        </tr>
        <?php endforeach; if(empty($commissions)):?>
        <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-inbox"></i> No commissions yet</td></tr>
        <?php endif;?>
        </tbody>
      </table></div>

      <!-- Mobile card list -->
      <div class="comm-card-list">
        <?php if(empty($commissions)):?>
        <div style="text-align:center;padding:30px;color:var(--tl);">
          <i class="fa-solid fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>No commissions yet
        </div>
        <?php endif;?>
        <?php foreach($commissions as $c):
          $sm=['paid'=>'b-green','pending'=>'b-amber'];$sc=$sm[$c['status']]??'b-gray';
        ?>
        <div class="comm-card-item">
          <div class="comm-card-row">
            <div class="comm-card-users">
              <strong><?php echo htmlspecialchars($c['referrer']);?></strong>
              <span style="color:var(--tl);margin:0 5px;">→</span>
              <?php echo htmlspecialchars($c['referred']);?>
            </div>
            <div class="comm-card-amount">$<?php echo number_format($c['commission_amount'],4);?></div>
          </div>
          <div class="comm-card-meta">
            <span class="badge <?php echo $sc;?>"><?php echo ucfirst($c['status']);?></span>
            <span class="badge b-blue">L<?php echo $c['commission_level'];?></span>
            <span class="mono" style="font-size:11px;"><?php echo $c['commission_rate'];?>%</span>
            <span class="badge b-gray"><?php echo htmlspecialchars($c['source_type']);?></span>
            <span class="muted" style="font-size:11px;margin-left:auto;"><?php echo date('M d H:i',strtotime($c['created_at']));?></span>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- Right: rates panel -->
  <div class="card comm-rates-panel" style="height:fit-content;">
    <div class="card-head"><div class="card-title"><i class="fa-solid fa-sliders"></i> Referral Rates</div></div>
    <form method="POST">
      <input type="hidden" name="action" value="update_rates">
      <?php for($i=1;$i<=3;$i++): $rate=0; foreach($ref_settings as $rs){if($rs['level']==$i)$rate=$rs['commission_rate'];} ?>
      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-user-group"></i> Level <?php echo $i;?> Rate (%)</label>
        <input type="number" name="rate_<?php echo $i;?>" value="<?php echo $rate;?>" step="0.1" min="0" max="100" class="form-control">
      </div>
      <?php endfor;?>
      <button type="submit" class="btn btn-green w-full" style="justify-content:center;">
        <i class="fa-solid fa-check"></i> Update Rates
      </button>
    </form>
  </div>

</div>

<?php include '_layout_end.php'; ?>
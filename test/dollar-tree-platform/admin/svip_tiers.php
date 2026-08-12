<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'SVIP Tiers'; $active_nav = 'svip';

$msg=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='save_tier'){
    $id=(int)($_POST['tier_id']??0);
    $data=[
      'svip_level'=>(int)$_POST['svip_level'],
      'unlock_amount'=>(float)$_POST['unlock_amount'],
      'max_daily_profit'=>(float)$_POST['max_daily_profit'],
      'daily_tasks_limit'=>(int)$_POST['daily_tasks_limit'],
      'task_profit_per_completion'=>(float)$_POST['task_profit_per_completion'],
      'contract_duration_days'=>(int)$_POST['contract_duration_days'],
      'status'=>$_POST['status']
    ];
    if($id>0){
      $pdo->prepare("UPDATE svip_tiers SET svip_level=?,unlock_amount=?,max_daily_profit=?,daily_tasks_limit=?,task_profit_per_completion=?,contract_duration_days=?,status=?,updated_at=NOW() WHERE id=?")->execute(array_merge(array_values($data),[$id]));
      log_activity($_SESSION['admin_id'],'svip_tier_update',"Updated SVIP tier ID $id level ".$data['svip_level']);
      $msg="SVIP tier updated successfully";
    }else{
      $pdo->prepare("INSERT INTO svip_tiers (svip_level,unlock_amount,max_daily_profit,daily_tasks_limit,task_profit_per_completion,contract_duration_days,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())")->execute(array_values($data));
      log_activity($_SESSION['admin_id'],'svip_tier_create',"Created SVIP tier level ".$data['svip_level']);
      $msg="SVIP tier created successfully";
    }
  }elseif($act==='delete_tier'){
    $id=(int)$_POST['tier_id'];
    $pdo->prepare("DELETE FROM svip_tiers WHERE id=?")->execute([$id]);
    log_activity($_SESSION['admin_id'],'svip_tier_delete',"Deleted SVIP tier ID $id");
    $msg="SVIP tier deleted";
  }elseif($act==='toggle_status'){
    $id=(int)$_POST['tier_id'];
    $cur=$pdo->query("SELECT status FROM svip_tiers WHERE id=$id")->fetchColumn();
    $new=$cur==='active'?'inactive':'active';
    $pdo->prepare("UPDATE svip_tiers SET status=?,updated_at=NOW() WHERE id=?")->execute([$new,$id]);
    $msg="Tier status set to $new";
  }
}

$tiers=$pdo->query("SELECT * FROM svip_tiers ORDER BY svip_level ASC")->fetchAll(PDO::FETCH_ASSOC);
$topbar_actions='<button class="tb-chip primary" onclick="openTierModal(0)"><i class="fa-solid fa-plus"></i> <span class="hide-xs">Add Tier</span></button>';
include '_layout.php';
?>

<style>
.hide-xs{}
/* SVIP tier cards for mobile */
.svip-card-list{display:none;}
.svip-card-item{
  background:var(--card);border:1px solid var(--border);border-radius:var(--r2);
  padding:14px;margin-bottom:10px;
}
.svip-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.svip-level-badge{
  display:flex;align-items:center;gap:10px;
}
.svip-level-num{
  width:40px;height:40px;border-radius:10px;background:var(--gg);
  border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;
  font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:900;color:var(--gl);
}
.svip-card-name{font-size:16px;font-weight:800;color:var(--tp);}
.svip-stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;}
.svip-stat{background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:var(--r);padding:8px 10px;}
.svip-stat-label{font-size:9px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.1em;margin-bottom:3px;}
.svip-stat-val{font-size:14px;font-weight:700;color:var(--tp);}
.svip-card-actions{display:flex;gap:8px;}
.svip-card-actions .btn{flex:1;justify-content:center;}

@media(max-width:640px){
  .svip-table-wrap{display:none;}
  .svip-card-list{display:block;}
  .hide-xs{display:none;}
}
</style>

<?php if($msg):?><div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div><?php endif;?>

<!-- Desktop table -->
<div class="card svip-table-wrap">
  <div class="card-head">
    <div class="card-title"><i class="fa-solid fa-crown"></i> SVIP Tier Configuration</div>
    <button class="btn btn-green btn-sm" onclick="openTierModal(0)"><i class="fa-solid fa-plus"></i> Add New Tier</button>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Level</th><th>Unlock Cost</th><th>Daily Tasks</th><th>Profit/Task</th><th>Max Daily</th><th>Duration</th><th>Total Profit</th><th>Status</th><th>Users</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($tiers as $t):
      $total=$t['daily_tasks_limit']*$t['task_profit_per_completion']*$t['contract_duration_days'];
      $user_cnt=$pdo->query("SELECT COUNT(*) FROM users WHERE svip_level=".(int)$t['svip_level'])->fetchColumn();
    ?>
    <tr>
      <td><div style="display:flex;align-items:center;gap:8px;"><div style="width:32px;height:32px;border-radius:8px;background:var(--gg);border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:900;color:var(--gl);"><?php echo $t['svip_level'];?></div><strong>SVIP <?php echo $t['svip_level'];?></strong></div></td>
      <td class="text-amber"><strong>$<?php echo number_format($t['unlock_amount'],2);?></strong></td>
      <td><?php echo $t['daily_tasks_limit'];?> tasks</td>
      <td class="text-green">$<?php echo number_format($t['task_profit_per_completion'],4);?></td>
      <td class="text-green">$<?php echo number_format($t['max_daily_profit'],2);?>/day</td>
      <td><?php echo $t['contract_duration_days'];?> days</td>
      <td class="text-green"><strong>$<?php echo number_format($total,2);?></strong></td>
      <td>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="tier_id" value="<?php echo $t['id'];?>">
          <input type="hidden" name="action" value="toggle_status">
          <button type="submit" class="badge <?php echo $t['status']==='active'?'b-green':'b-red';?>" style="cursor:pointer;border:none;font-family:'Barlow',sans-serif;">
            <i class="fa-solid fa-<?php echo $t['status']==='active'?'circle-check':'circle-xmark';?>"></i> <?php echo ucfirst($t['status']);?>
          </button>
        </form>
      </td>
      <td><?php echo number_format($user_cnt);?> users</td>
      <td>
        <div class="flex gap-8">
          <button class="btn btn-ghost btn-xs" onclick='openTierModal(<?php echo $t["id"];?>,<?php echo htmlspecialchars(json_encode($t),ENT_QUOTES);?>)'><i class="fa-solid fa-pen"></i> Edit</button>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete SVIP Tier <?php echo $t['svip_level'];?>?')">
            <input type="hidden" name="tier_id" value="<?php echo $t['id'];?>">
            <input type="hidden" name="action" value="delete_tier">
            <button class="btn btn-red btn-xs"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; if(empty($tiers)):?><tr class="empty-row"><td colspan="10"><i class="fa-solid fa-crown"></i> No SVIP tiers configured yet</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>

<!-- Mobile card list -->
<div class="svip-card-list">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <span style="font-size:13px;font-weight:700;color:var(--ts);"><?php echo count($tiers);?> tiers configured</span>
    <button class="btn btn-green btn-sm" onclick="openTierModal(0)"><i class="fa-solid fa-plus"></i> Add Tier</button>
  </div>
  <?php if(empty($tiers)):?>
  <div class="card" style="text-align:center;padding:30px;color:var(--tl);">
    <i class="fa-solid fa-crown" style="font-size:24px;margin-bottom:8px;display:block;"></i>No tiers configured yet
  </div>
  <?php endif;?>
  <?php foreach($tiers as $t):
    $total=$t['daily_tasks_limit']*$t['task_profit_per_completion']*$t['contract_duration_days'];
    $user_cnt=$pdo->query("SELECT COUNT(*) FROM users WHERE svip_level=".(int)$t['svip_level'])->fetchColumn();
  ?>
  <div class="svip-card-item">
    <div class="svip-card-header">
      <div class="svip-level-badge">
        <div class="svip-level-num"><?php echo $t['svip_level'];?></div>
        <div>
          <div class="svip-card-name">SVIP <?php echo $t['svip_level'];?></div>
          <div style="font-size:11px;color:var(--tl);"><?php echo number_format($user_cnt);?> users</div>
        </div>
      </div>
      <form method="POST">
        <input type="hidden" name="tier_id" value="<?php echo $t['id'];?>">
        <input type="hidden" name="action" value="toggle_status">
        <button type="submit" class="badge <?php echo $t['status']==='active'?'b-green':'b-red';?>" style="cursor:pointer;border:none;font-family:'Barlow',sans-serif;min-height:30px;padding:4px 10px;">
          <i class="fa-solid fa-<?php echo $t['status']==='active'?'circle-check':'circle-xmark';?>"></i> <?php echo ucfirst($t['status']);?>
        </button>
      </form>
    </div>
    <div class="svip-stat-grid">
      <div class="svip-stat">
        <div class="svip-stat-label">Unlock Cost</div>
        <div class="svip-stat-val text-amber">$<?php echo number_format($t['unlock_amount'],2);?></div>
      </div>
      <div class="svip-stat">
        <div class="svip-stat-label">Daily Tasks</div>
        <div class="svip-stat-val"><?php echo $t['daily_tasks_limit'];?></div>
      </div>
      <div class="svip-stat">
        <div class="svip-stat-label">Profit/Task</div>
        <div class="svip-stat-val text-green">$<?php echo number_format($t['task_profit_per_completion'],4);?></div>
      </div>
      <div class="svip-stat">
        <div class="svip-stat-label">Duration</div>
        <div class="svip-stat-val"><?php echo $t['contract_duration_days'];?> days</div>
      </div>
      <div class="svip-stat">
        <div class="svip-stat-label">Max Daily</div>
        <div class="svip-stat-val text-green">$<?php echo number_format($t['max_daily_profit'],2);?></div>
      </div>
      <div class="svip-stat">
        <div class="svip-stat-label">Total Profit</div>
        <div class="svip-stat-val text-green">$<?php echo number_format($total,2);?></div>
      </div>
    </div>
    <div class="svip-card-actions">
      <button class="btn btn-ghost btn-sm" onclick='openTierModal(<?php echo $t["id"];?>,<?php echo htmlspecialchars(json_encode($t),ENT_QUOTES);?>)'>
        <i class="fa-solid fa-pen"></i> Edit
      </button>
      <form method="POST" onsubmit="return confirm('Delete this tier?')" style="flex:1;">
        <input type="hidden" name="tier_id" value="<?php echo $t['id'];?>">
        <input type="hidden" name="action" value="delete_tier">
        <button class="btn btn-red btn-sm w-full"><i class="fa-solid fa-trash"></i> Delete</button>
      </form>
    </div>
  </div>
  <?php endforeach;?>
</div>

<!-- Tier Modal -->
<div class="modal-overlay" id="tierModal" onclick="if(event.target===this)closeModal('tierModal')">
  <div class="modal modal-lg">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-crown"></i> <span id="tm-title">Add SVIP Tier</span></div>
      <button class="modal-close" onclick="closeModal('tierModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="save_tier">
        <input type="hidden" name="tier_id" id="tm-id" value="0">
        <div class="form-grid-2">
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-crown"></i> SVIP Level</label><input type="number" name="svip_level" id="tm-level" class="form-control" min="1" placeholder="1" required></div>
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-lock-open"></i> Unlock Amount (USDT)</label><input type="number" name="unlock_amount" id="tm-unlock" class="form-control" step="0.01" placeholder="0.00" required></div>
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-list-check"></i> Daily Tasks Limit</label><input type="number" name="daily_tasks_limit" id="tm-tasks" class="form-control" placeholder="10" required></div>
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-coins"></i> Profit Per Task (USDT)</label><input type="number" name="task_profit_per_completion" id="tm-profit" class="form-control" step="0.0001" placeholder="0.0000" required></div>
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-chart-line"></i> Max Daily Profit</label><input type="number" name="max_daily_profit" id="tm-maxprofit" class="form-control" step="0.01" placeholder="0.00" required></div>
          <div class="form-group"><label class="form-label"><i class="fa-solid fa-calendar-days"></i> Duration (days)</label><input type="number" name="contract_duration_days" id="tm-days" class="form-control" placeholder="30" required></div>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" id="tm-status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div id="tm-calc" style="background:var(--gg);border:1px solid var(--border2);border-radius:var(--r);padding:12px;font-size:12px;color:var(--ts);display:none;">
          <strong style="color:var(--tp);">Projected Total:</strong>
          <span id="tm-total-profit" style="color:var(--gl);font-weight:800;font-size:15px;margin-left:8px;"></span>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('tierModal')">Cancel</button>
        <button type="submit" class="btn btn-green"><i class="fa-solid fa-check"></i> Save Tier</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTierModal(id,data){
  document.getElementById('tm-title').textContent=id>0?'Edit SVIP Tier':'Add New SVIP Tier';
  document.getElementById('tm-id').value=id||0;
  const fields={level:'svip_level',unlock:'unlock_amount',tasks:'daily_tasks_limit',profit:'task_profit_per_completion',maxprofit:'max_daily_profit',days:'contract_duration_days',status:'status'};
  Object.entries(fields).forEach(([k,f])=>{const el=document.getElementById('tm-'+k);if(el)el.value=data?data[f]:'';});
  calcProfit();
  openModal('tierModal');
}
function calcProfit(){
  const tasks=parseFloat(document.getElementById('tm-tasks').value)||0;
  const profit=parseFloat(document.getElementById('tm-profit').value)||0;
  const days=parseFloat(document.getElementById('tm-days').value)||0;
  const total=tasks*profit*days;
  const el=document.getElementById('tm-calc');
  const tp=document.getElementById('tm-total-profit');
  if(total>0){el.style.display='block';tp.textContent='$'+total.toFixed(2)+' USDT';}
  else{el.style.display='none';}
}
['tm-tasks','tm-profit','tm-days'].forEach(id=>{const el=document.getElementById(id);if(el)el.addEventListener('input',calcProfit);});
</script>
<?php include '_layout_end.php'; ?>
<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Settings'; $active_nav = 'settings';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&$_POST['action']==='save_settings'){
  foreach($_POST as $k=>$v){
    if($k==='action'||$k==='csrf') continue;
    $exists=$pdo->query("SELECT COUNT(*) FROM admin_settings WHERE setting_key='$k'")->fetchColumn();
    if($exists) $pdo->prepare("UPDATE admin_settings SET setting_value=?,updated_at=NOW() WHERE setting_key=?")->execute([$v,$k]);
    else $pdo->prepare("INSERT INTO admin_settings (setting_key,setting_value,created_at,updated_at) VALUES (?,?,NOW(),NOW())")->execute([$k,$v]);
  }
  log_activity($_SESSION['admin_id'],'settings_update','Updated platform settings');
  $msg="Settings saved";
}
$rows=$pdo->query("SELECT * FROM admin_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
$settings=[]; foreach($rows as $r) $settings[$r['setting_key']]=$r['setting_value'];
$get=fn($k,$d='')=>$settings[$k]??$d;
include '_layout.php';
?>

<style>
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.save-bar{margin-top:14px;text-align:right;}
@media(max-width:640px){
  .settings-grid{grid-template-columns:1fr;}
  .save-bar{text-align:stretch;}
  .save-bar .btn{width:100%;justify-content:center;}
}
.toggle-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 0;border-bottom:1px solid var(--border);
  gap:12px;
}
.toggle-row:last-child{border-bottom:none;}
.toggle-label{font-size:13px;font-weight:600;color:var(--tp);}
.toggle-desc{font-size:11px;color:var(--tl);margin-top:2px;}
.db-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:8px 0;border-bottom:1px solid var(--border);font-size:12px;
  gap:8px;
}
.db-row:last-child{border-bottom:none;}
.db-key{font-family:'JetBrains Mono',monospace;color:var(--tl);font-size:11px;word-break:break-all;}
.db-val{color:var(--ts);text-align:right;word-break:break-all;max-width:55%;}
</style>

<?php if($msg):?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div>
<?php endif;?>

<form method="POST">
  <input type="hidden" name="action" value="save_settings">
  <div class="settings-grid">

    <!-- Platform -->
    <div class="card">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-building"></i> Platform</div></div>
      <div class="form-group">
        <label class="form-label">Platform Name</label>
        <input type="text" name="platform_name" value="<?php echo htmlspecialchars($get('platform_name','DollarTree'));?>" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Withdrawal Min (USDT)</label>
        <input type="number" name="min_withdrawal" value="<?php echo $get('min_withdrawal','10');?>" step="0.01" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Withdrawal Max (USDT)</label>
        <input type="number" name="max_withdrawal" value="<?php echo $get('max_withdrawal','10000');?>" step="0.01" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Withdrawal Fee (%)</label>
        <input type="number" name="withdrawal_fee_pct" value="<?php echo $get('withdrawal_fee_pct','2');?>" step="0.1" class="form-control">
      </div>
    </div>

    <!-- Toggles -->
    <div class="card">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-toggle-on"></i> Toggles</div></div>
      <?php
      $toggles=[
        ['registration_enabled','User Registration','Enable new user signups'],
        ['withdrawals_enabled','Withdrawals','Allow withdrawal requests'],
        ['deposits_enabled','Deposits','Allow deposit submissions'],
        ['maintenance_mode','Maintenance Mode','Show maintenance page to users'],
        ['crawl_enabled_global','Crawl Mode (Global)','Master switch for crawl feature'],
      ];
      foreach($toggles as [$key,$label,$desc]):
        $on=$get($key,'1')==='1';
      ?>
      <div class="toggle-row">
        <div>
          <div class="toggle-label"><?php echo $label;?></div>
          <div class="toggle-desc"><?php echo $desc;?></div>
        </div>
        <label class="toggle">
          <input type="checkbox" name="<?php echo $key;?>" value="1" <?php echo $on?'checked':'';?>>
          <span class="toggle-track"></span>
        </label>
      </div>
      <?php endforeach;?>
    </div>

    <!-- Support -->
    <div class="card">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-envelope"></i> Support</div></div>
      <div class="form-group">
        <label class="form-label">Support Email</label>
        <input type="email" name="support_email" value="<?php echo htmlspecialchars($get('support_email','support@dollartree.com'));?>" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Telegram Support URL</label>
        <input type="text" name="telegram_url" value="<?php echo htmlspecialchars($get('telegram_url',''));?>" class="form-control" placeholder="https://t.me/…">
      </div>
      <div class="form-group">
        <label class="form-label">Deposit Wallet Address</label>
        <input type="text" name="deposit_address" value="<?php echo htmlspecialchars($get('deposit_address',''));?>" class="form-control" placeholder="TRC20 address…">
      </div>
    </div>

    <!-- DB Settings viewer -->
    <div class="card">
      <div class="card-head"><div class="card-title"><i class="fa-solid fa-database"></i> Stored Settings</div></div>
      <div style="max-height:260px;overflow-y:auto;">
        <?php foreach($rows as $r):?>
        <div class="db-row">
          <span class="db-key"><?php echo htmlspecialchars($r['setting_key']);?></span>
          <span class="db-val"><?php echo htmlspecialchars(substr($r['setting_value'],0,30));?></span>
        </div>
        <?php endforeach; if(empty($rows))echo '<p class="muted" style="padding:12px;font-size:12px;text-align:center;">No settings stored yet</p>';?>
      </div>
    </div>

  </div>

  <div class="save-bar">
    <button type="submit" class="btn btn-green" style="padding:12px 24px;font-size:14px;">
      <i class="fa-solid fa-floppy-disk"></i> Save All Settings
    </button>
  </div>
</form>

<script>
document.querySelector('form').addEventListener('submit',function(){
  document.querySelectorAll('input[type=checkbox]').forEach(cb=>{
    if(!cb.checked){
      const h=document.createElement('input');h.type='hidden';h.name=cb.name;h.value='0';cb.parentNode.insertBefore(h,cb);
    }
  });
});
</script>
<?php include '_layout_end.php'; ?>
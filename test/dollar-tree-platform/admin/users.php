<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Users'; $active_nav = 'users';

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];
    $act = $_POST['action'] ?? '';

    if ($act === 'toggle_status') {
        $cur = $pdo->query("SELECT account_status FROM users WHERE id=$uid")->fetchColumn();
        $new = $cur === 'active' ? 'suspended' : 'active';
        $pdo->prepare("UPDATE users SET account_status=? WHERE id=?")->execute([$new, $uid]);
        log_activity($_SESSION['admin_id'], 'admin_action', "Set user $uid account_status=$new");
        $msg = "User status updated to $new";

    } elseif ($act === 'adjust_balance') {
        $amount = (float)$_POST['amount'];
        $type   = $_POST['adj_type'];
        if ($type === 'add') {
            $pdo->prepare("UPDATE users SET balance=balance+? WHERE id=?")->execute([$amount, $uid]);
            $msg = "Added $$amount to user #$uid";
        } else {
            $pdo->prepare("UPDATE users SET balance=GREATEST(0,balance-?) WHERE id=?")->execute([$amount, $uid]);
            $msg = "Deducted $$amount from user #$uid";
        }
        log_activity($_SESSION['admin_id'], 'admin_action', "Balance $type $amount for user $uid");

    } elseif ($act === 'reset_crawl') {
        $pdo->prepare("
            UPDATE users
            SET craw_mode=0, craw_task_step=0, craw_completed_today=0,
                craw_recharge_paid=0, craw_gap=0, craw_snapshot_balance=0,
                craw_balance_after_t1=0, craw_balance_after_t2=0, craw_t3_price=0
            WHERE id=?
        ")->execute([$uid]);
        log_activity($_SESSION['admin_id'], 'admin_action', "Reset crawl for user $uid");
        $msg = "Crawl data reset for user #$uid";

    } elseif ($act === 'enable_crawl') {
        // Enable crawl: set mode, record entry time, start at step 1, reset completed flag
        $pdo->prepare("
            UPDATE users
            SET craw_mode=1, craw_entered_at=NOW(), craw_task_step=1, craw_completed_today=0
            WHERE id=?
        ")->execute([$uid]);
        log_activity($_SESSION['admin_id'], 'admin_action', "Enabled crawl for user $uid");
        $msg = "Crawl ENABLED for user #$uid";

    }  elseif ($act === 'disable_crawl') {
    $pdo->prepare("
        UPDATE users
        SET craw_mode=0, craw_task_step=0, craw_completed_today=0
        WHERE id=?
    ")->execute([$uid]);
        log_activity($_SESSION['admin_id'], 'admin_action', "Disabled crawl for user $uid");
        $msg = "Crawl DISABLED for user #$uid";
    }
}

$search        = trim($_GET['s'] ?? '');
$status_filter = $_GET['status'] ?? '';
$where  = "is_admin=0";
$params = [];
if ($search) {
    $where  .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($status_filter) {
    $where  .= " AND account_status=?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topbar_actions = '<a href="messages.php" class="tb-chip primary"><i class="fa-solid fa-envelope"></i> <span class="hide-xs">Message User</span></a>';
include '_layout.php';
function sb($s) {
    $m = ['active'=>['b-green','circle-check'],'suspended'=>['b-red','ban'],'pending'=>['b-amber','clock']];
    $d = $m[$s] ?? ['b-gray','circle'];
    return "<span class='badge {$d[0]}'><i class='fa-solid fa-{$d[1]}'></i> ".ucfirst($s)."</span>";
}
?>

<style>
.hide-xs{}
.user-card-list{display:none;}
.user-card-item{background:var(--card);border:1px solid var(--border);border-radius:var(--r2);padding:14px;margin-bottom:10px;}
.user-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.user-card-name{font-weight:700;color:var(--tp);font-size:14px;}
.user-card-email{font-size:11px;color:var(--tl);margin-top:2px;}
.user-card-bal{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#3dd63d;}
.user-card-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;align-items:center;}
.user-card-actions{display:flex;gap:8px;flex-wrap:wrap;}
.user-card-actions .btn{flex:1;justify-content:center;min-width:80px;}
.search-form{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.search-form .form-control{flex:1;min-width:140px;}
@media(max-width:640px){
  .users-table-wrap{display:none;}
  .user-card-list{display:block;}
  .hide-xs{display:none;}
  .search-form .result-count{display:none;}
}
/* Crawl enable/disable button colours */
.btn-spider{background:linear-gradient(135deg,#1565c0,#1976d2);color:#fff;border:none;}
.btn-spider:hover{background:linear-gradient(135deg,#0d47a1,#1565c0);}
.btn-spider-off{background:linear-gradient(135deg,#b71c1c,#c62828);color:#fff;border:none;}
.btn-spider-off:hover{background:linear-gradient(135deg,#7f0000,#b71c1c);}
</style>

<?php if ($msg): ?><div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-red"><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<!-- Search bar -->
<div class="card" style="margin-bottom:14px;">
  <form method="GET" class="search-form">
    <div style="position:relative;flex:1;min-width:140px;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--tl);font-size:13px;pointer-events:none;"></i>
      <input type="text" name="s" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users…" class="form-control" style="padding-left:34px;">
    </div>
    <select name="status" class="form-control" style="max-width:140px;">
      <option value="">All Status</option>
      <option value="active"    <?php echo $status_filter === 'active'    ? 'selected' : ''; ?>>Active</option>
      <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
    </select>
    <button class="btn btn-green btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <a href="users.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
    <span class="muted result-count" style="font-size:12px;"><?php echo count($users); ?> results</span>
  </form>
</div>

<!-- Desktop table -->
<div class="card users-table-wrap">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Balance</th>
          <th>SVIP</th>
          <th>Deposited</th>
          <th>Crawl</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="mono">#<?php echo $u['id']; ?></td>
          <td>
            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
            <div style="font-size:11px;color:var(--tl);"><?php echo htmlspecialchars($u['email']); ?></div>
          </td>
          <td class="text-green"><strong>$<?php echo number_format($u['balance'], 2); ?></strong></td>
          <td><span class="badge b-amber"><i class="fa-solid fa-crown"></i> <?php echo $u['svip_level']; ?></span></td>
          <td class="mono">$<?php echo number_format($u['total_deposited'], 2); ?></td>
          <td>
            <?php if ($u['craw_mode']): ?>
              <span class="badge b-blue"><i class="fa-solid fa-spider"></i> Active</span>
            <?php else: ?>
              <span class="badge b-gray">Off</span>
            <?php endif; ?>
          </td>
          <td><?php echo sb($u['account_status']); ?></td>
          <td class="muted" style="font-size:11px;"><?php echo date('M d Y', strtotime($u['created_at'])); ?></td>
          <td>
            <div class="flex gap-8" style="flex-wrap:nowrap;">

              <!-- Balance adjust -->
              <button class="btn btn-ghost btn-xs"
                onclick="openUserModal(<?php echo $u['id']; ?>,'<?php echo addslashes($u['username']); ?>',<?php echo $u['balance']; ?>,'<?php echo $u['account_status']; ?>')"
                title="Adjust Balance">
                <i class="fa-solid fa-pen"></i>
              </button>

              <!-- Suspend / Activate -->
              <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle status?')">
                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn btn-xs <?php echo $u['account_status'] === 'active' ? 'btn-red' : 'btn-green'; ?>"
                  title="<?php echo $u['account_status'] === 'active' ? 'Suspend' : 'Activate'; ?>">
                  <i class="fa-solid fa-<?php echo $u['account_status'] === 'active' ? 'ban' : 'circle-check'; ?>"></i>
                </button>
              </form>

              <!-- ── CRAWL TOGGLE ── -->
              <?php if ($u['craw_mode']): ?>
                <!-- Currently ON → show disable button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Disable crawl for <?php echo htmlspecialchars($u['username']); ?>?')">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="action" value="disable_crawl">
                  <button type="submit" class="btn btn-xs btn-spider-off" title="Stop Crawl">
                    <i class="fa-solid fa-spider"></i> <span class="hide-xs">Stop</span>
                  </button>
                </form>
                <!-- Reset crawl data -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Reset all crawl data for this user?')">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="action" value="reset_crawl">
                  <button type="submit" class="btn btn-amber btn-xs" title="Reset Crawl Data">
                    <i class="fa-solid fa-rotate"></i>
                  </button>
                </form>
              <?php else: ?>
                <!-- Currently OFF → show enable button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Enable crawl mode for <?php echo htmlspecialchars($u['username']); ?>?')">
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="hidden" name="action" value="enable_crawl">
                  <button type="submit" class="btn btn-xs btn-spider" title="Enable Crawl">
                    <i class="fa-solid fa-spider"></i> <span class="hide-xs">Crawl</span>
                  </button>
                </form>
              <?php endif; ?>

            </div>
          </td>
        </tr>
        <?php endforeach;
        if (empty($users)): ?>
        <tr class="empty-row"><td colspan="9"><i class="fa-solid fa-users-slash"></i> No users found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Mobile card list -->
<div class="user-card-list">
  <div style="font-size:12px;color:var(--tl);margin-bottom:10px;"><?php echo count($users); ?> results</div>
  <?php if (empty($users)): ?>
  <div class="card" style="text-align:center;padding:30px;color:var(--tl);">
    <i class="fa-solid fa-users-slash" style="font-size:24px;margin-bottom:8px;display:block;"></i>No users found
  </div>
  <?php endif; ?>
  <?php foreach ($users as $u): ?>
  <div class="user-card-item">
    <div class="user-card-top">
      <div>
        <div class="user-card-name"><?php echo htmlspecialchars($u['username']); ?></div>
        <div class="user-card-email"><?php echo htmlspecialchars($u['email']); ?></div>
      </div>
      <div class="user-card-bal">$<?php echo number_format($u['balance'], 2); ?></div>
    </div>
    <div class="user-card-meta">
      <?php echo sb($u['account_status']); ?>
      <span class="badge b-amber"><i class="fa-solid fa-crown"></i> SVIP <?php echo $u['svip_level']; ?></span>
      <?php echo $u['craw_mode'] ? "<span class='badge b-blue'><i class='fa-solid fa-spider'></i> Crawl Active</span>" : ''; ?>
      <span class="mono" style="font-size:11px;">#<?php echo $u['id']; ?></span>
    </div>
    <div style="font-size:12px;color:var(--tl);margin-bottom:12px;">
      Deposited: <strong style="color:var(--ts);">$<?php echo number_format($u['total_deposited'], 2); ?></strong>
      &nbsp;·&nbsp; Joined: <span><?php echo date('M d Y', strtotime($u['created_at'])); ?></span>
    </div>
    <div class="user-card-actions">
      <!-- Balance -->
      <button class="btn btn-ghost btn-sm"
        onclick="openUserModal(<?php echo $u['id']; ?>,'<?php echo addslashes($u['username']); ?>',<?php echo $u['balance']; ?>,'<?php echo $u['account_status']; ?>')">
        <i class="fa-solid fa-pen"></i> Balance
      </button>

      <!-- Status toggle -->
      <form method="POST" onsubmit="return confirm('Toggle status?')" style="flex:1;">
        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
        <input type="hidden" name="action" value="toggle_status">
        <button type="submit" class="btn btn-sm w-full <?php echo $u['account_status'] === 'active' ? 'btn-red' : 'btn-green'; ?>">
          <i class="fa-solid fa-<?php echo $u['account_status'] === 'active' ? 'ban' : 'circle-check'; ?>"></i>
          <?php echo $u['account_status'] === 'active' ? 'Suspend' : 'Activate'; ?>
        </button>
      </form>

      <!-- Crawl toggle -->
      <?php if ($u['craw_mode']): ?>
        <form method="POST" onsubmit="return confirm('Disable crawl?')" style="flex:1;">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <input type="hidden" name="action" value="disable_crawl">
          <button type="submit" class="btn btn-sm w-full btn-spider-off">
            <i class="fa-solid fa-spider"></i> Stop Crawl
          </button>
        </form>
        <form method="POST" onsubmit="return confirm('Reset all crawl data?')" style="flex:1;">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <input type="hidden" name="action" value="reset_crawl">
          <button type="submit" class="btn btn-amber btn-sm w-full">
            <i class="fa-solid fa-rotate"></i> Reset Crawl
          </button>
        </form>
      <?php else: ?>
        <form method="POST" onsubmit="return confirm('Enable crawl for this user?')" style="flex:1;">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <input type="hidden" name="action" value="enable_crawl">
          <button type="submit" class="btn btn-sm w-full btn-spider">
            <i class="fa-solid fa-spider"></i> Enable Crawl
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Balance Adjust Modal -->
<div class="modal-overlay" id="userModal" onclick="if(event.target===this)closeModal('userModal')">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Edit — <span id="mu-name"></span></div>
      <button class="modal-close" onclick="closeModal('userModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="user_id" id="mu-id">
        <input type="hidden" name="action" value="adjust_balance">
        <div class="form-group">
          <div class="form-label">Current Balance</div>
          <div style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;color:var(--gl);" id="mu-bal"></div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Adjustment Type</label>
            <select name="adj_type" class="form-control">
              <option value="add">➕ Add Balance</option>
              <option value="subtract">➖ Subtract Balance</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (USDT)</label>
            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" placeholder="0.00" required> 
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('userModal')">Cancel</button>
        <button type="submit" class="btn btn-green"><i class="fa-solid fa-check"></i> Apply</button>
      </div>
    </form>
  </div>
</div>

<script>
function openUserModal(id, name, bal, status) {
  document.getElementById('mu-id').value  = id;
  document.getElementById('mu-name').textContent = name;
  document.getElementById('mu-bal').textContent  = '$' + parseFloat(bal).toFixed(2) + ' USDT';
  openModal('userModal');
}
</script>

<?php include '_layout_end.php'; ?>
<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'User Messages';
$active_nav = 'messages';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act      = $_POST['action'] ?? '';
    $admin_id = (int)$_SESSION['admin_id'];

    if ($act === 'send_message') {
        $type    = $_POST['msg_type']    ?? 'all';
        $title   = trim($_POST['title']  ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!$title || !$content) {
            $msg = 'ERROR:Title and content are required.';
        } else {
            if ($type === 'all') {
                $pdo->prepare("INSERT INTO user_messages (user_id, admin_id, title, content, msg_type, created_at) VALUES (NULL, ?, ?, ?, 'all', NOW())")->execute([$admin_id, $title, $content]);
                log_activity($admin_id, 'broadcast_message', "Broadcast: $title");
                $msg = 'Message broadcast to ALL users.';
            } elseif ($type === 'svip') {
                $svip_lvl = (int)$_POST['svip_level'];
                $users    = $pdo->query("SELECT id FROM users WHERE svip_level = $svip_lvl AND is_admin = 0")->fetchAll(PDO::FETCH_COLUMN);
                $stmt = $pdo->prepare("INSERT INTO user_messages (user_id, admin_id, title, content, msg_type, svip_level, created_at) VALUES (?, ?, ?, ?, 'svip', ?, NOW())");
                foreach ($users as $uid) $stmt->execute([$uid, $admin_id, $title, $content, $svip_lvl]);
                log_activity($admin_id, 'svip_message', "SVIP $svip_lvl message: $title (" . count($users) . " users)");
                $msg = 'Message sent to ' . count($users) . ' SVIP ' . $svip_lvl . ' user(s).';
            } elseif ($type === 'specific') {
                $uids = array_map('intval', $_POST['user_ids'] ?? []);
                if (empty($uids)) {
                    $msg = 'ERROR:Please select at least one user.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO user_messages (user_id, admin_id, title, content, msg_type, created_at) VALUES (?, ?, ?, ?, 'specific', NOW())");
                    foreach ($uids as $uid) $stmt->execute([$uid, $admin_id, $title, $content]);
                    log_activity($admin_id, 'targeted_message', "Targeted message to " . count($uids) . " users: $title");
                    $msg = 'Message sent to ' . count($uids) . ' user(s).';
                }
            }
        }
    } elseif ($act === 'delete_message') {
        $mid = (int)$_POST['message_id'];
        $pdo->prepare("DELETE FROM user_messages WHERE id = ?")->execute([$mid]);
        $msg = 'Message deleted.';
    }
}

$recent_msgs = $pdo->query(
    "SELECT um.*, a.username AS admin_name,
            CASE WHEN um.user_id IS NULL THEN 'All Users'
                 ELSE (SELECT username FROM users WHERE id = um.user_id LIMIT 1)
            END AS recipient_name,
            (SELECT COUNT(*) FROM user_messages um2
             WHERE (um2.user_id = um.user_id OR um2.id = um.id)
               AND um2.title = um.title AND um2.is_read = 1) as read_count
     FROM user_messages um
     LEFT JOIN users a ON um.admin_id = a.id
     ORDER BY um.created_at DESC LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

$total_sent      = $pdo->query("SELECT COUNT(*) FROM user_messages")->fetchColumn();
$total_broadcast = $pdo->query("SELECT COUNT(*) FROM user_messages WHERE user_id IS NULL")->fetchColumn();
$total_unread    = $pdo->query("SELECT COUNT(*) FROM user_messages WHERE is_read = 0")->fetchColumn();
$users           = $pdo->query("SELECT id, username, email, svip_level FROM users WHERE is_admin = 0 ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$svip_levels     = $pdo->query("SELECT DISTINCT svip_level FROM users WHERE is_admin = 0 ORDER BY svip_level")->fetchAll(PDO::FETCH_COLUMN);

include '_layout.php';
?>

<style>
.msg-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
/* Mobile: stack compose above log */
@media(max-width:680px){
  .msg-grid{grid-template-columns:1fr;}
}
.urow:hover{background:var(--gg);}
.msg-log-item{
  background:var(--bg);border:1px solid var(--border);border-radius:var(--r);
  padding:12px 13px;position:relative;
}
.msg-log-header{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;}
.msg-log-title{font-size:13px;font-weight:700;color:var(--tp);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
/* Tighter user list on mobile */
@media(max-width:480px){
  .user-list-item span.email-label{display:none;}
}
</style>

<?php if ($msg): $is_err = str_starts_with($msg, 'ERROR:'); ?>
<div class="alert <?php echo $is_err ? 'alert-red' : 'alert-green'; ?>">
  <i class="fa-solid fa-<?php echo $is_err ? 'circle-xmark' : 'circle-check'; ?>"></i>
  <?php echo htmlspecialchars($is_err ? substr($msg, 6) : $msg); ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:14px;">
  <div class="stat-card">
    <div class="sc-lbl">Total Sent</div>
    <div class="sc-val"><?php echo number_format($total_sent); ?></div>
    <div class="sc-sub"><i class="fa-solid fa-paper-plane"></i> All time</div>
    <i class="fa-solid fa-envelope sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Broadcasts</div>
    <div class="sc-val text-green"><?php echo number_format($total_broadcast); ?></div>
    <div class="sc-sub"><i class="fa-solid fa-bullhorn"></i> All-user</div>
    <i class="fa-solid fa-bullhorn sc-bg-icon"></i>
  </div>
  <div class="stat-card">
    <div class="sc-lbl">Unread</div>
    <div class="sc-val text-amber"><?php echo number_format($total_unread); ?></div>
    <div class="sc-sub warn"><i class="fa-solid fa-eye-slash"></i> Unseen</div>
    <i class="fa-solid fa-eye-slash sc-bg-icon"></i>
  </div>
</div>

<div class="msg-grid">

  <!-- ── COMPOSE ── -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-paper-plane"></i> Compose Message</div>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="send_message">

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-users"></i> Send To</label>
        <select name="msg_type" id="msg-type" class="form-control" onchange="toggleRecipient(this.value)">
          <option value="all">📢 All Users (Broadcast)</option>
          <option value="svip">👑 Specific SVIP Level</option>
          <option value="specific">👤 Specific Users</option>
        </select>
      </div>

      <!-- SVIP selector -->
      <div id="svip-selector" style="display:none;" class="form-group">
        <label class="form-label">SVIP Level</label>
        <select name="svip_level" class="form-control">
          <?php foreach ($svip_levels as $lvl): ?>
            <option value="<?php echo $lvl; ?>">SVIP <?php echo $lvl; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- User multi-select -->
      <div id="user-selector" style="display:none;" class="form-group">
        <label class="form-label">Select Users</label>
        <div style="position:relative;margin-bottom:6px;">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--tl);font-size:12px;pointer-events:none;"></i>
          <input type="text" id="user-search" placeholder="Search users…" class="form-control"
                 style="padding-left:30px;" oninput="filterUsers(this.value)">
        </div>
        <div style="max-height:190px;overflow-y:auto;-webkit-overflow-scrolling:touch;background:var(--bg);border:1px solid var(--border);border-radius:var(--r);padding:6px;" id="user-list">
          <?php foreach ($users as $u): ?>
          <label class="urow user-list-item" data-name="<?php echo strtolower($u['username']); ?>"
                 data-email="<?php echo strtolower($u['email']); ?>"
                 style="display:flex;align-items:center;gap:8px;padding:9px 8px;border-radius:6px;cursor:pointer;transition:background .1s;min-height:42px;">
            <input type="checkbox" name="user_ids[]" value="<?php echo $u['id']; ?>" style="accent-color:var(--gp);width:16px;height:16px;flex-shrink:0;">
            <span style="font-weight:600;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;"><?php echo htmlspecialchars($u['username']); ?></span>
            <span class="email-label" style="font-size:11px;color:var(--tl);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;"><?php echo htmlspecialchars($u['email']); ?></span>
            <span class="badge b-amber" style="font-size:9px;flex-shrink:0;">S<?php echo $u['svip_level']; ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:8px;display:flex;gap:8px;">
          <button type="button" class="btn btn-ghost btn-xs"
                  onclick="document.querySelectorAll('#user-list input').forEach(c=>c.checked=true)">
            <i class="fa-solid fa-check-double"></i> All
          </button>
          <button type="button" class="btn btn-ghost btn-xs"
                  onclick="document.querySelectorAll('#user-list input').forEach(c=>c.checked=false)">
            <i class="fa-solid fa-xmark"></i> None
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-heading"></i> Title</label>
        <input type="text" name="title" class="form-control" placeholder="Subject / Title…" required>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-align-left"></i> Content</label>
        <textarea name="content" class="form-control" rows="5" placeholder="Write your message…" required></textarea>
      </div>

      <div style="background:rgba(26,122,26,.08);border:1px solid var(--border2);border-radius:var(--r);padding:10px 12px;font-size:12px;color:var(--tm);margin-bottom:13px;">
        <i class="fa-solid fa-circle-info text-green"></i>
        Messages appear in the user's <strong>Notifications → Messages</strong> tab in real-time.
      </div>

      <button type="submit" class="btn btn-green w-full" style="justify-content:center;">
        <i class="fa-solid fa-paper-plane"></i> Send Message
      </button>
    </form>
  </div>

  <!-- ── SENT LOG ── -->
  <div class="card">
    <div class="card-head">
      <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Sent Messages</div>
      <span style="font-size:11px;color:var(--tl);"><?php echo count($recent_msgs); ?> records</span>
    </div>

    <?php if (empty($recent_msgs)): ?>
    <div style="text-align:center;padding:40px 20px;color:var(--tl);">
      <i class="fa-solid fa-envelope-open" style="font-size:28px;display:block;margin-bottom:10px;opacity:.3;"></i>
      <p style="font-size:13px;">No messages sent yet.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:8px;max-height:560px;overflow-y:auto;-webkit-overflow-scrolling:touch;padding-right:2px;">
      <?php foreach ($recent_msgs as $m):
        $type_icons  = ['all'=>'bullhorn','svip'=>'crown','specific'=>'user'];
        $type_colors = ['all'=>'b-green','svip'=>'b-amber','specific'=>'b-blue'];
        $ic  = $type_icons[$m['msg_type']]  ?? 'envelope';
        $cls = $type_colors[$m['msg_type']] ?? 'b-gray';
        $recipient = $m['user_id'] === null
          ? '<span class="badge b-green" style="font-size:9px;"><i class="fa-solid fa-users"></i> All Users</span>'
          : '<span class="badge b-blue" style="font-size:9px;"><i class="fa-solid fa-user"></i> '.htmlspecialchars($m['recipient_name']).'</span>';
      ?>
      <div class="msg-log-item">
        <div class="msg-log-header">
          <div style="display:flex;align-items:center;gap:7px;flex:1;min-width:0;">
            <span class="badge <?php echo $cls; ?>" style="font-size:9px;flex-shrink:0;">
              <i class="fa-solid fa-<?php echo $ic; ?>"></i> <?php echo ucfirst($m['msg_type']); ?>
            </span>
            <span class="msg-log-title"><?php echo htmlspecialchars($m['title']); ?></span>
          </div>
          <form method="POST" style="flex-shrink:0;">
            <input type="hidden" name="action" value="delete_message">
            <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
            <button class="btn btn-red btn-xs" title="Delete"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
        <div style="margin-bottom:6px;"><?php echo $recipient; ?></div>
        <p style="font-size:12px;color:var(--tm);line-height:1.5;margin-bottom:6px;">
          <?php echo htmlspecialchars(mb_substr($m['content'], 0, 100)) . (mb_strlen($m['content']) > 100 ? '…' : ''); ?>
        </p>
        <div style="font-size:10px;color:var(--tl);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></span>
          <span><i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($m['admin_name'] ?? 'Admin'); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleRecipient(val) {
  document.getElementById('svip-selector').style.display = val === 'svip'     ? 'block' : 'none';
  document.getElementById('user-selector').style.display = val === 'specific' ? 'block' : 'none';
}
function filterUsers(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.urow').forEach(row => {
    const match = row.dataset.name.includes(q) || row.dataset.email.includes(q);
    row.style.display = match ? '' : 'none';
  });
}
</script>

<?php include '_layout_end.php'; ?>
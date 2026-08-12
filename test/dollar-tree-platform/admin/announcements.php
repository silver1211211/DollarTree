<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Announcements'; $active_nav = 'announcements';

// Safe migration — add link columns if missing
try { $pdo->query("SELECT link_url FROM announcements LIMIT 1"); }
catch (Exception $e) { $pdo->exec("ALTER TABLE announcements ADD COLUMN link_url VARCHAR(500) NULL DEFAULT NULL AFTER content"); }
try { $pdo->query("SELECT link_text FROM announcements LIMIT 1"); }
catch (Exception $e) { $pdo->exec("ALTER TABLE announcements ADD COLUMN link_text VARCHAR(200) NULL DEFAULT NULL AFTER link_url"); }

$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $act       = $_POST['action'] ?? '';
  $link_url  = trim($_POST['link_url']  ?? '');
  $link_text = trim($_POST['link_text'] ?? '');

  if($act==='create'){
    $pdo->prepare(
      "INSERT INTO announcements (title,content,announcement_type,is_active,link_url,link_text,created_by_admin_id,created_at,updated_at)
       VALUES (?,?,?,?,?,?,?,NOW(),NOW())"
    )->execute([
      $_POST['title'], $_POST['content'], $_POST['announcement_type'],
      (int)($_POST['is_active']??1),
      $link_url  ?: null,
      $link_text ?: null,
      $_SESSION['admin_id']
    ]);
    log_activity($_SESSION['admin_id'],'announcement_create',"Created: ".$_POST['title']);
    $msg="Announcement published successfully";

  }elseif($act==='update'){
    $id=(int)$_POST['ann_id'];
    $pdo->prepare(
      "UPDATE announcements SET title=?,content=?,announcement_type=?,is_active=?,link_url=?,link_text=?,updated_at=NOW() WHERE id=?"
    )->execute([
      $_POST['title'], $_POST['content'], $_POST['announcement_type'],
      (int)($_POST['is_active']??1),
      $link_url  ?: null,
      $link_text ?: null,
      $id
    ]);
    $msg="Announcement updated";

  }elseif($act==='toggle'){
    $id=(int)$_POST['ann_id'];
    $pdo->prepare("UPDATE announcements SET is_active=NOT is_active,updated_at=NOW() WHERE id=?")->execute([$id]);
    $msg="Announcement toggled";

  }elseif($act==='delete'){
    $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([(int)$_POST['ann_id']]);
    $msg="Announcement deleted";
  }
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$topbar_actions = '<button class="tb-chip primary" onclick="openModal(\'createModal\')"><i class="fa-solid fa-plus"></i> <span class="hide-xs">New Announcement</span><span class="show-xs">New</span></button>';
include '_layout.php';
?>

<style>
.hide-xs{}
.show-xs{display:none;}
@media(max-width:480px){.hide-xs{display:none;}.show-xs{display:inline;}}

.ann-card{background:var(--bg);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;position:relative;border-left:3px solid var(--border2);transition:border-color .15s;}
.ann-card.live{border-left-color:var(--gp);}
.ann-card.is-daily{border-left-color:#8b5cf6 !important;}
.ann-card-header{display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;flex-wrap:wrap;}
.ann-card-title{font-size:14px;font-weight:700;color:var(--tp);flex:1;min-width:0;word-break:break-word;}
.ann-card-badges{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.ann-card-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
.ann-card-actions .btn{flex:1;min-width:70px;justify-content:center;}
.ann-card-link{font-size:11px;color:var(--tl);margin-top:6px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
.ann-card-link a{color:var(--blue);text-decoration:none;word-break:break-all;}
.ann-card-link a:hover{text-decoration:underline;}
@media(max-width:400px){.ann-card-actions{gap:6px;}.ann-card-actions .btn{font-size:11px;padding:6px 8px;}}

/* ── MODAL SCROLL FIX ──────────────────────────────────────────────────
   The modal itself is flex column. The head + foot are fixed height;
   modal-body fills the remaining space and scrolls independently.
   This means the Publish button is always visible at the bottom.
   ─────────────────────────────────────────────────────────────────── */
#createModal .modal {
  display: flex;
  flex-direction: column;
  max-height: 92vh;          /* never taller than the viewport */
  overflow: hidden;          /* clip corners, let body scroll */
}
#createModal .modal-head {
  flex-shrink: 0;            /* always visible */
}
#createModal .modal-body {
  flex: 1 1 auto;            /* fills available space */
  overflow-y: auto;          /* ← THE KEY FIX: body scrolls */
  overscroll-behavior: contain;
  /* thin scrollbar */
  scrollbar-width: thin;
  scrollbar-color: var(--border2) transparent;
}
#createModal .modal-body::-webkit-scrollbar { width: 4px; }
#createModal .modal-body::-webkit-scrollbar-track { background: transparent; }
#createModal .modal-body::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 4px; }
#createModal .modal-foot {
  flex-shrink: 0;            /* Publish button always visible */
  border-top: 1px solid var(--border);
}

/* link section in modal */
.link-section{background:rgba(139,92,246,.06);border:1px solid rgba(139,92,246,.2);border-radius:9px;padding:13px 15px;margin-top:6px;display:none;}
.link-section.show{display:block;}
.link-section-hd{font-size:10px;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;display:flex;align-items:center;gap:5px;}
.char-hint{font-size:10px;color:var(--tl);text-align:right;margin-top:2px;}

.b-purple{background:rgba(139,92,246,.12)!important;color:#7c3aed!important;border:1px solid rgba(139,92,246,.25)!important;}
</style>

<?php if($msg):?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div>
<?php endif;?>

<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="fa-solid fa-bullhorn"></i> Announcements (<?php echo count($announcements);?>)</div>
    <button class="btn btn-green btn-sm" onclick="openModal('createModal')"><i class="fa-solid fa-plus"></i> New</button>
  </div>

  <?php if(empty($announcements)):?>
  <div style="text-align:center;padding:40px 20px;color:var(--tl);">
    <i class="fa-solid fa-bullhorn" style="font-size:32px;display:block;margin-bottom:12px;opacity:.3;"></i>
    <p style="font-size:13px;">No announcements yet.</p>
    <p style="font-size:11px;margin-top:6px;color:var(--tl);">Create one to notify all users.</p>
    <button class="btn btn-green btn-sm" style="margin-top:14px;" onclick="openModal('createModal')">
      <i class="fa-solid fa-plus"></i> Create First Announcement
    </button>
  </div>
  <?php else:?>
  <div style="display:flex;flex-direction:column;gap:10px;">
  <?php foreach($announcements as $a):
    $type_colors = [
      'info'        => 'b-blue',
      'success'     => 'b-green',
      'warning'     => 'b-amber',
      'danger'      => 'b-red',
      'daily_modal' => 'b-purple',
    ];
    $type_labels = [
      'info'        => 'ℹ️ Info',
      'success'     => '✅ Success',
      'warning'     => '⚠️ Warning',
      'danger'      => '🚨 Danger',
      'daily_modal' => '📅 Daily Modal',
    ];
    $tc        = $type_colors[$a['announcement_type']] ?? 'b-gray';
    $tlabel    = $type_labels[$a['announcement_type']] ?? ucfirst($a['announcement_type']);
    $is_daily  = ($a['announcement_type'] === 'daily_modal');
  ?>
  <div class="ann-card <?php echo $a['is_active']?'live':''; ?> <?php echo $is_daily?'is-daily':''; ?>">
    <div class="ann-card-header">
      <div class="ann-card-badges">
        <span class="badge <?php echo $tc;?>"><?php echo $tlabel;?></span>
        <?php echo $a['is_active']
          ? "<span class='badge b-green'><i class='fa-solid fa-eye'></i> Live</span>"
          : "<span class='badge b-gray'><i class='fa-solid fa-eye-slash'></i> Hidden</span>";?>
      </div>
      <span class="ann-card-title"><?php echo htmlspecialchars($a['title']);?></span>
    </div>

    <p style="font-size:13px;color:var(--tm);line-height:1.6;word-break:break-word;">
      <?php echo nl2br(htmlspecialchars($a['content']));?>
    </p>

    <?php if(!empty($a['link_url'])): ?>
    <div class="ann-card-link">
      <i class="fa-solid fa-link" style="color:#8b5cf6;flex-shrink:0;"></i>
      <strong><?php echo htmlspecialchars($a['link_text'] ?: 'Link'); ?>:</strong>
      <a href="<?php echo htmlspecialchars($a['link_url']); ?>" target="_blank" rel="noopener">
        <?php echo htmlspecialchars($a['link_url']); ?>
      </a>
    </div>
    <?php endif; ?>

    <div style="font-size:11px;color:var(--tl);margin-top:8px;">
      <i class="fa-regular fa-clock"></i> <?php echo date('M d, Y H:i',strtotime($a['created_at']));?>
    </div>

    <div class="ann-card-actions">
      <button class="btn btn-ghost btn-sm" onclick='editAnn(<?php echo htmlspecialchars(json_encode($a),ENT_QUOTES);?>)'>
        <i class="fa-solid fa-pen"></i> Edit
      </button>
      <form method="POST" style="flex:1;">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="ann_id" value="<?php echo $a['id'];?>">
        <button class="btn btn-ghost btn-sm w-full">
          <?php echo $a['is_active']
            ? "<i class='fa-solid fa-eye-slash'></i> Hide"
            : "<i class='fa-solid fa-eye'></i> Show";?>
        </button>
      </form>
      <form method="POST" onsubmit="return confirm('Delete this announcement?')" style="flex:1;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="ann_id" value="<?php echo $a['id'];?>">
        <button class="btn btn-red btn-sm w-full"><i class="fa-solid fa-trash"></i> Delete</button>
      </form>
    </div>
  </div>
  <?php endforeach;?>
  </div>
  <?php endif;?>
</div>

<!-- ═══ Create / Edit Modal ═══ -->
<div class="modal-overlay" id="createModal" onclick="if(event.target===this)closeModal('createModal')">
  <div class="modal modal-lg">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-bullhorn"></i> <span id="ann-modal-title">New Announcement</span></div>
      <button class="modal-close" onclick="closeModal('createModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form method="POST" style="display:contents;">
      <div class="modal-body">
        <input type="hidden" name="action" id="ann-action" value="create">
        <input type="hidden" name="ann_id"  id="ann-id"    value="0">

        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" id="ann-title" class="form-control"
            placeholder="Announcement title…" required maxlength="200">
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="announcement_type" id="ann-type" class="form-control" onchange="onTypeChange(this.value)">
              <option value="info">ℹ️ Info</option>
              <option value="success">✅ Success</option>
              <option value="warning">⚠️ Warning</option>
              <option value="danger">🚨 Danger</option>
              <option value="daily_modal">📅 Daily Modal (login popup)</option>
            </select>
            <p id="daily-hint" style="display:none;font-size:11px;color:#7c3aed;margin-top:4px;">
              <i class="fa-solid fa-circle-info"></i> Shows as a popup once per day when users visit the dashboard.
            </p>
          </div>
          <div class="form-group">
            <label class="form-label">Visibility</label>
            <select name="is_active" id="ann-active" class="form-control">
              <option value="1">✅ Active (visible)</option>
              <option value="0">👁️ Draft (hidden)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Content</label>
          <textarea name="content" id="ann-content" class="form-control" rows="5"
            placeholder="Write your announcement…" required
            oninput="updateCC(this,'ann-cc',800)"></textarea>
          <div class="char-hint"><span id="ann-cc">0</span> / 800 characters</div>
        </div>

        <!-- Shown only for daily_modal type -->
        <div class="link-section" id="link-section">
          <div class="link-section-hd"><i class="fa-solid fa-link"></i> Optional CTA Button</div>
          <div class="form-grid-2">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Button Label</label>
              <input type="text" name="link_text" id="ann-link-text" class="form-control"
                placeholder="e.g. Deposit Now, Learn More…" maxlength="80">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">URL</label>
              <input type="url" name="link_url" id="ann-link-url" class="form-control"
                placeholder="https://…">
            </div>
          </div>
          <p style="font-size:11px;color:var(--tl);margin-top:8px;">
            Leave blank to show no button. When filled, a green CTA button appears at the bottom of the popup.
          </p>
        </div>
      </div><!-- /modal-body -->

      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('createModal')">Cancel</button>
        <button type="submit" class="btn btn-green"><i class="fa-solid fa-bullhorn"></i> Publish</button>
      </div>
    </form>

  </div>
</div>

<script>
function onTypeChange(val) {
  var ls   = document.getElementById('link-section');
  var hint = document.getElementById('daily-hint');
  var show = (val === 'daily_modal');
  ls.classList[show ? 'add' : 'remove']('show');
  hint.style.display = show ? 'block' : 'none';
  if (!show) {
    document.getElementById('ann-link-url').value  = '';
    document.getElementById('ann-link-text').value = '';
  }
}
function updateCC(el, cid, max) {
  var c = document.getElementById(cid);
  if (!c) return;
  c.textContent = el.value.length;
  c.style.color = el.value.length > max * 0.9 ? '#e02020' : '';
}
function editAnn(a) {
  document.getElementById('ann-action').value            = 'update';
  document.getElementById('ann-modal-title').textContent = 'Edit Announcement';
  document.getElementById('ann-id').value                = a.id;
  document.getElementById('ann-title').value             = a.title;
  document.getElementById('ann-content').value           = a.content;
  document.getElementById('ann-type').value              = a.announcement_type;
  document.getElementById('ann-active').value            = a.is_active;
  document.getElementById('ann-link-url').value          = a.link_url  || '';
  document.getElementById('ann-link-text').value         = a.link_text || '';
  updateCC(document.getElementById('ann-content'), 'ann-cc', 800);
  onTypeChange(a.announcement_type);
  openModal('createModal');
  // Scroll body back to top when re-opening for edit
  setTimeout(function(){
    var mb = document.querySelector('#createModal .modal-body');
    if (mb) mb.scrollTop = 0;
  }, 50);
}
</script>

<?php include '_layout_end.php'; ?>
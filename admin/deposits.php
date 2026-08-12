<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Deposits'; $active_nav = 'deposits';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $did = (int)$_POST['deposit_id'];
    $act = $_POST['action'] ?? '';

    if ($act === 'approve') {
        $pdo->prepare("UPDATE pending_deposits SET status='completed', completed_at=NOW() WHERE id=?")
            ->execute([$did]);
        $d = $pdo->prepare("SELECT user_id, COALESCE(detected_amount, amount) AS amt FROM pending_deposits WHERE id=?");
        $d->execute([$did]);
        $d = $d->fetch(PDO::FETCH_ASSOC);
        if ($d) {
            $pdo->prepare("UPDATE users SET balance=balance+?, total_deposited=total_deposited+? WHERE id=?")
                ->execute([$d['amt'], $d['amt'], $d['user_id']]);
            log_activity($_SESSION['admin_id'], 'deposit_approve', "Approved deposit $did, amount " . $d['amt']);
        }
        $msg = "Deposit #$did approved and credited";
    } elseif ($act === 'reject') {
        $pdo->prepare("UPDATE pending_deposits SET status='rejected' WHERE id=?")
            ->execute([$did]);
        log_activity($_SESSION['admin_id'], 'deposit_reject', "Rejected deposit $did");
        $msg = "Deposit #$did rejected";
    }
}

$status_f = $_GET['status'] ?? '';

$conds = [];
if ($status_f) $conds[] = "d.status = " . $pdo->quote($status_f);
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$deposits = $pdo->query("
    SELECT d.*, u.username, u.email
    FROM pending_deposits d
    JOIN users u ON d.user_id = u.id
    $where
    ORDER BY d.created_at DESC
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

$counts = [
    'pending'   => $pdo->query("SELECT COUNT(*) FROM pending_deposits WHERE status='pending'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM pending_deposits WHERE status='completed'")->fetchColumn(),
    'rejected'  => $pdo->query("SELECT COUNT(*) FROM pending_deposits WHERE status='rejected'")->fetchColumn(),
];

include '_layout.php';
?>

<style>
/* ─── Deposits Page ─────────────────────────────────────────────── */

/* ── Search bar ── */
.dep-search-wrap {
  position: relative;
  margin-bottom: 12px;
}
.dep-search-wrap i.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--tl);
  font-size: 12px;
  pointer-events: none;
}
.dep-search-input {
  width: 100%;
  padding: 10px 36px 10px 34px;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: 10px;
  color: var(--tp);
  font-family: var(--fb, 'DM Sans', sans-serif);
  font-size: 13px;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
}
.dep-search-input:focus {
  border-color: var(--green, #1a7a1a);
  box-shadow: 0 0 0 3px rgba(26,122,26,.12);
}
.dep-search-input::placeholder { color: var(--tl); font-size: 12px; }

/* Clear ×  */
.dep-search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--surface3, #1c2b1c);
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: none;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--tl);
  font-size: 9px;
  transition: all .15s;
}
.dep-search-clear.visible { display: flex; }
.dep-search-clear:hover { background: var(--green, #1a7a1a); color: #fff; }

/* Result count */
.dep-result-count {
  font-size: 11px;
  color: var(--tl);
  margin-bottom: 10px;
  min-height: 16px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.dep-result-count strong { color: var(--green, #1a7a1a); }

/* ── Filter tabs ── */
.dep-filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
}
.ftab {
  padding: 5px 13px;
  border-radius: 99px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid var(--border2);
  background: var(--surface2);
  color: var(--tl);
  transition: all .15s;
  white-space: nowrap;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
}
.ftab:hover { border-color: var(--green, #1a7a1a); color: var(--green, #1a7a1a); }
.ftab.on {
  background: var(--green, #1a7a1a);
  border-color: var(--green, #1a7a1a);
  color: #fff;
  box-shadow: 0 2px 10px rgba(26,122,26,.25);
}

/* ── Table ── */
.card { width: 100%; box-sizing: border-box; }
.table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; overflow-y: visible; }
.table-wrap table { min-width: 680px; width: 100%; border-collapse: collapse; }
.table-wrap td.tx-cell { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.action-btns { display: flex; gap: 6px; flex-wrap: nowrap; }
.alert { box-sizing: border-box; width: 100%; }

/* Highlight */
.search-hl {
  background: rgba(26,122,26,.2);
  color: var(--tp);
  border-radius: 3px;
  padding: 0 2px;
  font-weight: 700;
}

/* Hidden row */
tr.dep-hidden { display: none; }

/* No results row */
.no-results-row { display: none; }
.no-results-row.visible { display: table-row; }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  display: none; align-items: center; justify-content: center;
  background: rgba(0,0,0,.55); padding: 16px; box-sizing: border-box;
}
.modal-overlay.open { display: flex; }
.modal { width: 100%; max-width: 480px; box-sizing: border-box; border-radius: 10px; overflow: hidden; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .dep-filter-bar .ftab { font-size: 11px; padding: 4px 10px; }
  .table-wrap table th,
  .table-wrap table td { padding: 8px 10px; font-size: 12px; }
  .action-btns .btn { font-size: 11px; padding: 4px 8px; }
}
@media (max-width: 480px) {
  .dep-filter-bar .ftab { font-size: 10px; padding: 4px 9px; }
  .table-wrap table th,
  .table-wrap table td { padding: 7px 8px; font-size: 11px; }
  .badge { font-size: 10px; padding: 2px 6px; }
  .modal { max-width: 100%; border-radius: 14px 14px 0 0; position: fixed; bottom: 0; left: 0; right: 0; }
  .modal-overlay { align-items: flex-end; padding: 0; }
  .action-btns { flex-direction: column; gap: 4px; }
  .action-btns .btn { width: 100%; justify-content: center; }
}
@media (max-width: 360px) {
  .table-wrap table th,
  .table-wrap table td { padding: 6px; font-size: 10.5px; }
}
</style>

<?php if ($msg): ?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- ══ SEARCH ══ -->
<div class="dep-search-wrap">
  <i class="fa-solid fa-magnifying-glass search-icon"></i>
  <input
    type="text"
    id="depSearchInput"
    class="dep-search-input"
    placeholder="Search user, email, TX hash, ID, network…"
    autocomplete="off"
    spellcheck="false"
  >
  <button class="dep-search-clear" id="depSearchClear" title="Clear">
    <i class="fa-solid fa-xmark"></i>
  </button>
</div>

<!-- Result count -->
<div class="dep-result-count" id="depResultCount"></div>

<!-- ══ FILTER TABS ══ -->
<div class="dep-filter-bar">
  <a href="deposits.php"                    class="ftab <?php echo !$status_f ? 'on' : ''; ?>">All <span style="opacity:.65;font-weight:500;">(<?php echo array_sum($counts); ?>)</span></a>
  <a href="deposits.php?status=pending"     class="ftab <?php echo $status_f === 'pending'   ? 'on' : ''; ?>"><i class="fa-solid fa-clock"></i> Pending <span style="opacity:.65;font-weight:500;">(<?php echo $counts['pending']; ?>)</span></a>
  <a href="deposits.php?status=completed"   class="ftab <?php echo $status_f === 'completed' ? 'on' : ''; ?>"><i class="fa-solid fa-circle-check"></i> Completed <span style="opacity:.65;font-weight:500;">(<?php echo $counts['completed']; ?>)</span></a>
  <a href="deposits.php?status=rejected"    class="ftab <?php echo $status_f === 'rejected'  ? 'on' : ''; ?>"><i class="fa-solid fa-ban"></i> Rejected <span style="opacity:.65;font-weight:500;">(<?php echo $counts['rejected']; ?>)</span></a>
</div>

<!-- ══ TABLE ══ -->
<div class="card">
  <div class="table-wrap">
    <table id="depTable">
      <thead>
        <tr>
          <th>ID</th><th>User</th><th>Amount</th><th>Network</th>
          <th>TX Hash</th><th>Status</th><th>Created</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($deposits as $d):
        $sm  = ['pending' => 'b-amber', 'completed' => 'b-green', 'rejected' => 'b-red'];
        $sc  = $sm[$d['status']] ?? 'b-gray';
        $amt = $d['detected_amount'] ?? $d['amount'] ?? 0;
        // Searchable text stored in data-search attr (lowercase, all fields)
        $searchable = strtolower(implode(' ', [
          $d['id'], $d['username'], $d['email'],
          $d['tx_hash'] ?? '', $d['currency'] ?? '', $d['status']
        ]));
      ?>
      <tr data-search="<?php echo htmlspecialchars($searchable); ?>">
        <td class="mono" style="font-size:11px;color:var(--tl);">#<?php echo $d['id']; ?></td>
        <td>
          <strong style="color:var(--tp);"><?php echo htmlspecialchars($d['username']); ?></strong>
          <div style="font-size:11px;color:var(--tl);"><?php echo htmlspecialchars($d['email']); ?></div>
        </td>
        <td class="text-green"><strong>$<?php echo number_format($amt, 2); ?></strong></td>
        <td style="font-size:12px;"><?php echo htmlspecialchars($d['currency'] ?? '—'); ?></td>
        <td class="mono tx-cell" title="<?php echo htmlspecialchars($d['tx_hash'] ?? ''); ?>">
          <?php echo $d['tx_hash'] ? htmlspecialchars(substr($d['tx_hash'], 0, 16)) . '…' : '—'; ?>
        </td>
        <td><span class="badge <?php echo $sc; ?>"><i class="fa-solid fa-circle" style="font-size:6px;"></i> <?php echo ucfirst($d['status']); ?></span></td>
        <td class="muted" style="font-size:11px;white-space:nowrap;"><?php echo date('M d, H:i', strtotime($d['created_at'])); ?></td>
        <td>
          <?php if ($d['status'] === 'pending'): ?>
          <div class="action-btns">
            <button class="btn btn-green btn-xs" onclick="openDepModal(<?php echo $d['id']; ?>,'approve')"><i class="fa-solid fa-check"></i> Approve</button>
            <button class="btn btn-red btn-xs"   onclick="openDepModal(<?php echo $d['id']; ?>,'reject')"><i class="fa-solid fa-ban"></i> Reject</button>
          </div>
          <?php else: ?>
            <span class="muted" style="font-size:11px;">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($deposits)): ?>
      <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-inbox"></i> No deposits found</td></tr>
      <?php endif; ?>
      <!-- JS injects this when search yields zero matches -->
      <tr class="no-results-row" id="noResultsRow">
        <td colspan="8"><i class="fa-solid fa-magnifying-glass"></i> No results found</td>
      </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ MODAL: Approve / Reject ══ -->
<div class="modal-overlay" id="depModal" onclick="if(event.target===this)closeModal('depModal')">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title">
        <i class="fa-solid fa-download"></i>
        <span id="dep-modal-title">Process Deposit</span>
      </div>
      <button class="modal-close" onclick="closeModal('depModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="deposit_id" id="dep-id">
        <input type="hidden" name="action"     id="dep-action">
        <div class="form-group">
          <label class="form-label">Admin Notes (optional)</label>
          <textarea name="notes" class="form-control" placeholder="Internal notes…"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('depModal')">Cancel</button>
        <button type="submit" class="btn btn-green" id="dep-submit-btn"><i class="fa-solid fa-check"></i> Confirm</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Real-time search ──────────────────────────────────────────── */
(function() {
  var input     = document.getElementById('depSearchInput');
  var clearBtn  = document.getElementById('depSearchClear');
  var countEl   = document.getElementById('depResultCount');
  var noResults = document.getElementById('noResultsRow');
  var rows      = document.querySelectorAll('#depTable tbody tr[data-search]');
  var total     = rows.length;
  var timer     = null;

  function doFilter() {
    var q = input.value.trim().toLowerCase();

    // Toggle clear button
    clearBtn.classList.toggle('visible', q.length > 0);

    if (!q) {
      // Show everything
      rows.forEach(function(r) { r.classList.remove('dep-hidden'); });
      noResults.classList.remove('visible');
      countEl.innerHTML = '';
      return;
    }

    var shown = 0;
    rows.forEach(function(r) {
      var match = r.getAttribute('data-search').indexOf(q) !== -1;
      r.classList.toggle('dep-hidden', !match);
      if (match) shown++;
    });

    // No results row
    noResults.classList.toggle('visible', shown === 0);

    // Count label
    countEl.innerHTML = shown === total
      ? ''
      : '<i class="fa-solid fa-filter" style="color:var(--green,#1a7a1a);font-size:10px;"></i> <strong>' + shown + '</strong> of ' + total + ' deposit' + (total !== 1 ? 's' : '') + ' match';
  }

  input.addEventListener('input', function() {
    clearTimeout(timer);
    timer = setTimeout(doFilter, 120); // tiny debounce — feels instant
  });

  clearBtn.addEventListener('click', function() {
    input.value = '';
    doFilter();
    input.focus();
  });
})();

/* ── Modal helpers ─────────────────────────────────────────────── */
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  if (!document.querySelector('.modal-overlay.open')) {
    document.body.style.overflow = '';
  }
}
function openDepModal(id, action) {
  document.getElementById('dep-id').value     = id;
  document.getElementById('dep-action').value = action;
  document.getElementById('dep-modal-title').textContent =
    (action === 'approve' ? 'Approve' : 'Reject') + ' Deposit #' + id;
  var btn = document.getElementById('dep-submit-btn');
  if (action === 'reject') {
    btn.className = 'btn btn-red';
    btn.innerHTML = '<i class="fa-solid fa-ban"></i> Reject';
  } else {
    btn.className = 'btn btn-green';
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Approve & Credit';
  }
  openModal('depModal');
}
</script>

<?php include '_layout_end.php'; ?>

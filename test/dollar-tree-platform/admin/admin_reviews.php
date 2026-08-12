<?php
/**
 * admin_reviews.php — Admin Reviews Management
 *
 * Features:
 *  - Add / Edit / Delete public reviews
 *  - Avatar upload per review
 *  - Average rating is the only editable setting
 *  - Total review count is auto-synced from the DB (not manually editable)
 *  - Logo upload removed
 */
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;

$page_title = 'Reviews';
$active_nav = 'reviews';

$upload_dir = __DIR__ . '/uploads/avatars/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

/* Auto-sync total_reviews in settings to the real row count */
function rv_sync_count($pdo) {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE visibility='public'")->fetchColumn();
    $pdo->prepare("UPDATE review_settings SET total_reviews=? WHERE id=1")->execute([$n]);
    return $n;
}

$msg = $err = '';

/* ─────────────────────────────────────
   POST handlers
───────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Update average rating only */
    if ($action === 'update_settings') {
        $avg = min(5.0, max(0.0, (float)($_POST['average_rating'] ?? 4.6)));
        $pdo->prepare("UPDATE review_settings SET average_rating=? WHERE id=1")->execute([$avg]);
        $msg = 'Average rating updated.';
    }

    /* Add or edit a public review */
    elseif (in_array($action, ['add_review', 'edit_review'])) {
        $rid      = (int)($_POST['review_id']     ?? 0);
        $uname    = htmlspecialchars(trim($_POST['username']    ?? ''), ENT_QUOTES, 'UTF-8');
        $rtext    = htmlspecialchars(trim($_POST['review_text'] ?? ''), ENT_QUOTES, 'UTF-8');
        $rating   = min(5, max(1, (int)($_POST['rating']       ?? 5)));
        $rcnt     = max(1, (int)($_POST['reviews_count']       ?? 1));
        $verified = isset($_POST['verified']) ? 1 : 0;
        $created  = $_POST['created_at'] ?? date('Y-m-d H:i:s');

        /* Avatar */
        $avatar = $_POST['existing_avatar'] ?? null;
        if (!empty($_FILES['avatar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $fname = 'av_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $fname)) {
                    $avatar = $fname;
                }
            }
        }

        if (!$uname || !$rtext) {
            $err = 'Display name and review text are required.';
        } else {
            if ($action === 'add_review') {
                $pdo->prepare("
                    INSERT INTO reviews
                        (username, review_text, rating, avatar, reviews_count, verified, visibility, created_at)
                    VALUES (?,?,?,?,?,?,'public',?)
                ")->execute([$uname, $rtext, $rating, $avatar, $rcnt, $verified, $created]);
                $msg = 'Review added.';
            } else {
                $sql    = "UPDATE reviews SET username=?,review_text=?,rating=?,reviews_count=?,verified=?,created_at=?";
                $params = [$uname, $rtext, $rating, $rcnt, $verified, $created];
                if ($avatar) { $sql .= ',avatar=?'; $params[] = $avatar; }
                $sql .= " WHERE id=? AND visibility='public'";
                $params[] = $rid;
                $pdo->prepare($sql)->execute($params);
                $msg = 'Review updated.';
            }
            rv_sync_count($pdo);
        }
    }

    /* Delete */
    elseif ($action === 'delete_review') {
        $rid = (int)($_POST['review_id'] ?? 0);
        $row = $pdo->prepare("SELECT avatar FROM reviews WHERE id=? AND visibility='public'");
        $row->execute([$rid]);
        $r = $row->fetch();
        if ($r && $r['avatar']) @unlink($upload_dir . $r['avatar']);
        $pdo->prepare("DELETE FROM reviews WHERE id=? AND visibility='public'")->execute([$rid]);
        rv_sync_count($pdo);
        $msg = 'Review deleted.';
    }
}

/* ─────────────────────────────────────
   Fetch data
───────────────────────────────────── */
$settings     = $pdo->query("SELECT * FROM review_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$reviews      = $pdo->query("SELECT * FROM reviews WHERE visibility='public' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$total_public = count($reviews);

/* Keep total_reviews in sync on every page load */
if ((int)($settings['total_reviews'] ?? 0) !== $total_public) {
    $pdo->prepare("UPDATE review_settings SET total_reviews=? WHERE id=1")->execute([$total_public]);
    $settings['total_reviews'] = $total_public;
}

/* Edit mode */
$edit_review = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM reviews WHERE id=? AND visibility='public'");
    $s->execute([(int)$_GET['edit']]);
    $edit_review = $s->fetch(PDO::FETCH_ASSOC);
}

/* 5-star count for stats */
$five_star = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE visibility='public' AND rating=5")->fetchColumn();
$pct5      = $total_public > 0 ? round($five_star / $total_public * 100) : 0;

$topbar_actions = '';
include '_layout.php';
?>

<style>
/* ── Page-specific styles ── */
.rev-settings-card { margin-bottom: 20px; }
.rev-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media(max-width:700px){ .rev-grid { grid-template-columns: 1fr; } }

.rev-table-user  { display: flex; align-items: center; gap: 10px; }
.rev-table-avatar{
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; background:var(--gg);
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:800; color:var(--gl);
    border:2px solid var(--border2); flex-shrink:0;
}
.star-display { color:#f5c518; letter-spacing:1px; font-size:14px; }

/* Auto-count pill */
.rv-auto-pill{
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(26,122,26,.12); border:1px solid rgba(26,122,26,.25);
    border-radius:100px; padding:4px 12px;
    font-size:11px; font-weight:700; color:var(--gl);
}

/* Current avatar thumbnail */
.rev-av-thumb{
    width:34px; height:34px; border-radius:50%;
    object-fit:cover; border:1px solid var(--border2);
    vertical-align:middle;
}
</style>

<?php if ($msg): ?>
<div class="alert alert-green"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-red"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     SETTINGS — average rating only
══════════════════════════════════════ -->
<div class="card rev-settings-card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-sliders"></i> Review Settings</div>
        <span class="rv-auto-pill">
            <i class="fa-solid fa-arrows-rotate"></i>
            Auto count: <strong><?= $total_public ?></strong>
        </span>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="update_settings">
        <div style="max-width:280px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Average Rating (0.0 – 5.0)</label>
                <input type="number" name="average_rating" class="form-control"
                    step="0.1" min="0" max="5"
                    value="<?= htmlspecialchars($settings['average_rating']) ?>">
            </div>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" class="btn btn-green btn-sm">
                <i class="fa-solid fa-floppy-disk"></i> Save Rating
            </button>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════
     ADD / EDIT FORM  +  QUICK STATS
══════════════════════════════════════ -->
<div class="rev-grid">

    <!-- Form -->
    <div class="card">
        <div class="card-head">
            <div class="card-title">
                <i class="fa-solid fa-<?= $edit_review ? 'pen' : 'plus' ?>"></i>
                <?= $edit_review ? 'Edit Review' : 'Add Public Review' ?>
            </div>
            <?php if ($edit_review): ?>
            <a href="admin_reviews.php" class="btn btn-ghost btn-xs">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_review ? 'edit_review' : 'add_review' ?>">
            <?php if ($edit_review): ?>
            <input type="hidden" name="review_id"       value="<?= $edit_review['id'] ?>">
            <input type="hidden" name="existing_avatar" value="<?= htmlspecialchars($edit_review['avatar'] ?? '') ?>">
            <?php endif; ?>

            <!-- Display name -->
            <div class="form-group">
                <label class="form-label">Display Name</label>
                <input type="text" name="username" class="form-control"
                    placeholder="e.g. Michael H."
                    value="<?= htmlspecialchars($edit_review['username'] ?? '') ?>" required>
            </div>

            <!-- Rating + Review count -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Star Rating</label>
                    <select name="rating" class="form-control">
                        <?php for ($i=5; $i>=1; $i--): ?>
                        <option value="<?= $i ?>"
                            <?= (int)($edit_review['rating'] ?? 5) === $i ? 'selected' : '' ?>>
                            <?= $i ?> Star<?= $i>1?'s':'' ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">User's Review Count</label>
                    <input type="number" name="reviews_count" class="form-control"
                        min="1" value="<?= (int)($edit_review['reviews_count'] ?? 1) ?>">
                </div>
            </div>

            <!-- Review body -->
            <div class="form-group">
                <label class="form-label">Review Text</label>
                <textarea name="review_text" class="form-control" rows="4"
                    placeholder="Write the review…" required><?= htmlspecialchars($edit_review['review_text'] ?? '') ?></textarea>
            </div>

            <!-- Date + Avatar -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="datetime-local" name="created_at" class="form-control"
                        value="<?= date('Y-m-d\TH:i', strtotime($edit_review['created_at'] ?? 'now')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Avatar <span class="muted">(optional)</span></label>
                    <input type="file" name="avatar" class="form-control"
                        accept="image/*" style="font-size:13px;padding:9px;">
                    <?php if (!empty($edit_review['avatar'])): ?>
                    <div style="margin-top:7px;display:flex;align-items:center;gap:8px;">
                        <img src="uploads/avatars/<?= htmlspecialchars($edit_review['avatar']) ?>"
                            class="rev-av-thumb" alt=""
                            onerror="this.style.display='none'">
                        <span style="font-size:11px;color:var(--tl);">Current</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Verified toggle -->
            <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
                <label class="toggle">
                    <input type="checkbox" name="verified"
                        <?= (int)($edit_review['verified'] ?? 1) ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
                <span style="font-size:13px;color:var(--ts);">Show "Verified User" badge</span>
            </div>

            <button type="submit" class="btn btn-green w-full">
                <i class="fa-solid fa-<?= $edit_review ? 'floppy-disk' : 'plus' ?>"></i>
                <?= $edit_review ? 'Update Review' : 'Add Review' ?>
            </button>
        </form>
    </div>

    <!-- Quick stats -->
    <div class="card" style="align-self:start;">
        <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-chart-bar"></i> Quick Stats</div>
        </div>
        <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:10px;">
            <div class="stat-card">
                <div class="sc-lbl">Avg Rating</div>
                <div class="sc-val"><?= htmlspecialchars($settings['average_rating']) ?></div>
                <div class="sc-sub"><i class="fa-solid fa-star" style="color:#f5c518;"></i> Stars</div>
                <i class="fa-solid fa-star sc-bg-icon"></i>
            </div>
            <div class="stat-card">
                <div class="sc-lbl">Public Reviews</div>
                <div class="sc-val"><?= $total_public ?></div>
                <div class="sc-sub"><i class="fa-solid fa-eye"></i> Live</div>
                <i class="fa-solid fa-comments sc-bg-icon"></i>
            </div>
            <div class="stat-card">
                <div class="sc-lbl">5-Star</div>
                <div class="sc-val"><?= $five_star ?></div>
                <div class="sc-sub up"><i class="fa-solid fa-arrow-up"></i> <?= $pct5 ?>%</div>
                <i class="fa-solid fa-trophy sc-bg-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     REVIEWS TABLE
══════════════════════════════════════ -->
<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fa-solid fa-list"></i> Public Reviews</div>
        <span style="font-size:12px;color:var(--tl);"><?= $total_public ?> total</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th class="hide-mobile">Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $rev): ?>
                <tr>
                    <td>
                        <div class="rev-table-user">
                            <?php if ($rev['avatar']): ?>
                            <img src="uploads/avatars/<?= htmlspecialchars($rev['avatar']) ?>"
                                class="rev-table-avatar" alt=""
                                onerror="this.style.display='none'">
                            <?php else: ?>
                            <div class="rev-table-avatar">
                                <?= mb_strtoupper(mb_substr($rev['username'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($rev['username']) ?></strong>
                                <div style="font-size:11px;color:var(--tl);"><?= $rev['reviews_count'] ?> reviews</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="star-display">
                            <?= str_repeat('★', (int)$rev['rating']) ?><?= str_repeat('☆', 5-(int)$rev['rating']) ?>
                        </span>
                    </td>
                    <td style="max-width:260px;font-size:12px;color:var(--ts);">
                        <?= htmlspecialchars(mb_substr($rev['review_text'], 0, 90)) ?><?= mb_strlen($rev['review_text'])>90?'…':'' ?>
                        <?php if ($rev['verified']): ?>
                        <div style="margin-top:4px;">
                            <span class="badge b-green" style="font-size:9px;">
                                <i class="fa-solid fa-circle-check"></i> Verified
                            </span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="muted hide-mobile" style="font-size:11px;white-space:nowrap;">
                        <?= date('M d, Y', strtotime($rev['created_at'])) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="?edit=<?= $rev['id'] ?>" class="btn btn-ghost btn-xs">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Delete this review?')">
                                <input type="hidden" name="action"    value="delete_review">
                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                <button type="submit" class="btn btn-red btn-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                <tr class="empty-row">
                    <td colspan="5"><i class="fa-solid fa-inbox"></i> No reviews yet</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '_layout_end.php'; ?>
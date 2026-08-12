<?php
/**
 * reviews_component.php  v6
 *
 * Changes:
 *  – Review cards: white/light background, dark text (fully readable)
 *  – Total review count is the LIVE count from DB (no manual override)
 *  – Logo upload removed (use admin panel separately)
 *  – Pagination: 10 per page with smooth scroll
 *  – Average rating still comes from review_settings (admin can tune it)
 *
 * Usage:
 *   <?php include 'spin_wheel.php'; ?>
 *   <?php include 'reviews_component.php'; ?>
 */

if (!defined('RV_SUBMIT_URL')) define('RV_SUBMIT_URL', 'submit_review.php');

global $pdo;

/* ── Settings (only average_rating matters now) ── */
$rv_settings = $pdo->query("SELECT * FROM review_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$rv_avg      = (float)($rv_settings['average_rating'] ?? 4.6);

/* ── Live counts ── */
$rv_public = $pdo->query(
    "SELECT * FROM reviews WHERE visibility='public' ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$rv_total  = count($rv_public); // LIVE count — never manual

/* ── Logged-in user's private reviews ── */
$rv_user  = [];
$rv_uid   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($rv_uid) {
    $s = $pdo->prepare(
        "SELECT * FROM reviews WHERE user_id=? AND visibility='private' ORDER BY created_at DESC"
    );
    $s->execute([$rv_uid]);
    $rv_user = $s->fetchAll(PDO::FETCH_ASSOC);
}

/* ── Badge: how many public reviews since last visit ── */
$seen_key     = 'rv_seen_' . ($rv_uid ?: 'guest');
$last_seen    = isset($_COOKIE[$seen_key]) ? (int)$_COOKIE[$seen_key] : 0;
$rv_new_count = 0;
foreach ($rv_public as $r) {
    if (strtotime($r['created_at']) > $last_seen) $rv_new_count++;
}

/* ── Resolve upload URL relative to THIS file's location ── */
$_rv_docroot  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$_rv_thisdir  = rtrim(dirname(str_replace('\\', '/', (string)__FILE__)), '/');
$_rv_rel      = str_replace($_rv_docroot, '', $_rv_thisdir);
$RV_UPLOAD_URL = (strpos($_rv_rel, '/') === 0 ? $_rv_rel : '/' . $_rv_rel) . '/uploads/avatars/';
/* Fallback for edge cases */
if (substr_count($RV_UPLOAD_URL, '/') < 2) $RV_UPLOAD_URL = 'uploads/avatars/';

/* ── Helpers ── */
function rv_time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60)       return 'just now';
    if ($d < 3600)     return floor($d/60)     . ' min ago';
    if ($d < 86400)    return floor($d/3600)   . ' hour'  . (floor($d/3600)   >1?'s':'') . ' ago';
    if ($d < 2592000)  return floor($d/86400)  . ' day'   . (floor($d/86400)  >1?'s':'') . ' ago';
    if ($d < 31536000) return floor($d/2592000). ' month' . (floor($d/2592000)>1?'s':'') . ' ago';
    return floor($d/31536000) . ' year' . (floor($d/31536000)>1?'s':'') . ' ago';
}
function rv_stars_html($n, $sz = '14px') {
    $o = '';
    for ($i = 1; $i <= 5; $i++)
        $o .= '<i class="fa-' . ($i<=$n?'solid':'regular') . ' fa-star"'
            . ' style="color:' . ($i<=$n?'#f5a623':'#ddd') . ';font-size:' . $sz . ';"></i>';
    return $o;
}
function rv_color($name) {
    $c = ['#1a7a1a','#22a322','#0f5c0f','#2db82d','#3d8b3d','#145e14'];
    return $c[abs(crc32($name)) % count($c)];
}

/* ── Rating distribution ── */
$rv_dist     = array_fill(1, 5, 0);
foreach ($rv_public as $r) $rv_dist[(int)$r['rating']]++;
$rv_dist_max = max(array_values($rv_dist)) ?: 1;

/* ── JSON payload for JS pagination ── */
$rv_js_reviews = [];
foreach ($rv_public as $r) {
    $rv_js_reviews[] = [
        'username'      => htmlspecialchars($r['username'],    ENT_QUOTES, 'UTF-8'),
        'rating'        => (int)$r['rating'],
        'reviews_count' => (int)$r['reviews_count'],
        'verified'      => (bool)$r['verified'],
        'review_text'   => htmlspecialchars($r['review_text'], ENT_QUOTES, 'UTF-8'),
        'time_ago'      => rv_time_ago($r['created_at']),
        'avatar'        => $r['avatar'] ? $RV_UPLOAD_URL . $r['avatar'] : null,
        'color'         => rv_color($r['username']),
        'initial'       => mb_strtoupper(mb_substr($r['username'], 0, 1)),
    ];
}
?>
<style>
/* ════════════════════════════════════════════════════
   REVIEWS SYSTEM  v6
   Cards = white bg + dark text
   Hero/topbar = dark (matches existing site shell)
════════════════════════════════════════════════════ */

/* Keyframes */
@keyframes rv-pulse  { 0%,100%{box-shadow:0 2px 8px rgba(200,160,16,.55);transform:scale(1)} 50%{box-shadow:0 4px 18px rgba(200,160,16,.9);transform:scale(1.18)} }
@keyframes rv-in     { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
@keyframes rv-fadein { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
@keyframes rv-spin   { to{transform:rotate(360deg)} }
@keyframes rv-cardIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

/* ── Float button ── */
#rv-float-trigger {
    width:46px; height:46px;
    display:flex; align-items:center; justify-content:center;
    background:#fff; border:none; border-top:1px solid #d4ebd4;
    color:#5a7a5a; font-size:18px; cursor:pointer;
    transition:all .18s; position:relative; font-family:inherit;
}
#rv-float-trigger:hover { background:#f0f5f0; color:#1a7a1a; }
#rv-gold-badge {
    position:absolute; top:5px; right:5px;
    min-width:17px; height:17px; padding:0 3px;
    background:radial-gradient(135deg,#d4a010 0%,#8a5c00 100%);
    color:#fff; border-radius:50%; font-size:9px; font-weight:900;
    display:flex; align-items:center; justify-content:center;
    border:2px solid #fff; line-height:1;
    animation:rv-pulse 2s ease-in-out infinite;
}

/* ── Full-page overlay ── */
.rv-overlay {
    position:fixed; inset:0; z-index:700;
    display:none; flex-direction:column;
    background:#f5f7f5; /* light page bg */
    overflow:hidden;
}
.rv-overlay.open { display:flex; animation:rv-in .26s cubic-bezier(.4,0,.2,1); }

/* ── Topbar (keeps dark brand look) ── */
.rv-topbar {
    flex-shrink:0;
    background:#0f150f;
    border-bottom:1px solid #1a261a;
    display:flex; align-items:center; gap:12px;
    padding:0 18px; height:56px; position:relative;
}
.rv-topbar::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg,#1a7a1a,#2db82d,transparent);
}
.rv-back-btn {
    flex-shrink:0; width:38px; height:38px; border-radius:10px;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12);
    color:#c8e6c8; display:flex; align-items:center; justify-content:center;
    font-size:15px; cursor:pointer; transition:all .18s;
}
.rv-back-btn:hover { background:rgba(26,122,26,.3); color:#7fff7f; }
.rv-topbar-title {
    font-family:'Barlow Condensed',Barlow,sans-serif;
    font-size:19px; font-weight:900; color:#e8f5e8;
    text-transform:uppercase; letter-spacing:.06em;
}
.rv-topbar-close {
    margin-left:auto; background:none; border:none;
    color:#6a9a6a; font-size:20px; cursor:pointer;
    padding:8px; transition:color .18s; line-height:1;
}
.rv-topbar-close:hover { color:#c8e6c8; }

/* ── Scrollable body ── */
.rv-body { flex:1; overflow-y:auto; -webkit-overflow-scrolling:touch; }
.rv-body::-webkit-scrollbar { width:4px; }
.rv-body::-webkit-scrollbar-thumb { background:#ccc; border-radius:2px; }

/* ── Hero (dark panel) ── */
.rv-hero {
    background:linear-gradient(160deg,#0d180d 0%,#1a2e1a 100%);
    padding:36px 20px 28px; text-align:center; position:relative; overflow:hidden;
}
.rv-hero::before {
    content:''; position:absolute; inset:0; pointer-events:none;
    background:repeating-linear-gradient(0deg,transparent,transparent 31px,rgba(26,122,26,.04) 31px,rgba(26,122,26,.04) 32px);
}
.rv-hero-ring {
    position:absolute; top:-60px; right:-60px; width:220px; height:220px;
    border-radius:50%; border:1px solid rgba(26,122,26,.12); pointer-events:none;
}
.rv-avg-big {
    font-family:'Barlow Condensed',Barlow,sans-serif;
    font-size:76px; font-weight:900; color:#e8f5e8;
    line-height:1; position:relative; z-index:1;
}
.rv-avg-big sup { color:#1a7a1a; font-size:34px; vertical-align:super; }
.rv-hero-stars-row {
    display:flex; align-items:center; justify-content:center;
    gap:5px; margin:10px 0 6px; position:relative; z-index:1;
}
.rv-hero-sub { font-size:13px; color:#8ab88a; font-weight:600; position:relative; z-index:1; }

/* Distribution bars */
.rv-dist { max-width:260px; margin:20px auto 0; position:relative; z-index:1; }
.rv-dist-row { display:flex; align-items:center; gap:9px; margin-bottom:7px; font-size:11px; color:#9dc89d; font-weight:700; }
.rv-dist-track { flex:1; height:6px; background:rgba(255,255,255,.08); border-radius:3px; overflow:hidden; }
.rv-dist-fill  { height:100%; background:#1a7a1a; border-radius:3px; }
.rv-dist-cnt   { width:22px; text-align:right; font-size:10px; color:#6a8a6a; }

/* ── Section header ── */
.rv-sec-hd { display:flex; align-items:center; justify-content:space-between; padding:22px 18px 12px; }
.rv-sec-title {
    font-family:'Barlow Condensed',Barlow,sans-serif;
    font-size:14px; font-weight:900; color:#1a2a1a;
    text-transform:uppercase; letter-spacing:.08em;
    display:flex; align-items:center; gap:8px;
}
.rv-sec-title i { color:#1a7a1a; }
.rv-sec-count { font-size:11px; color:#6a8a6a; font-weight:700; }

/* ── Cards grid ── */
.rv-cards-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(290px,1fr));
    gap:14px; padding:0 16px 4px; min-height:60px;
}

/* ── Single card — WHITE background, DARK text ── */
.rv-card {
    background:#ffffff;
    border:1px solid #e8f0e8;
    border-radius:14px; padding:20px;
    position:relative; overflow:hidden;
    transition:border-color .2s, transform .2s, box-shadow .2s;
    animation:rv-cardIn .22s ease both;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.rv-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#1a7a1a,#2db82d,transparent);
    opacity:0; transition:opacity .2s;
}
.rv-card:hover {
    border-color:#c0dcc0;
    transform:translateY(-2px);
    box-shadow:0 8px 24px rgba(26,122,26,.12);
}
.rv-card:hover::before { opacity:1; }

.rv-card-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:13px; }
.rv-av-img {
    width:46px; height:46px; border-radius:50%; object-fit:cover;
    flex-shrink:0; border:2px solid #e0ede0;
}
.rv-av-letter {
    width:46px; height:46px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-family:'Barlow Condensed',Barlow,sans-serif;
    font-size:19px; font-weight:900; color:#fff; flex-shrink:0;
}

/* Dark text on white card */
.rv-card-name { font-size:14px; font-weight:800; color:#1a2a1a; line-height:1.2; margin-bottom:3px; }
.rv-card-cnt  { font-size:11px; color:#6a8a6a; font-weight:600; }
.rv-card-stars { display:flex; align-items:center; gap:5px; margin-bottom:10px; flex-wrap:wrap; }
.rv-card-time  { font-size:11px; color:#8aaa8a; font-weight:600; margin-left:auto; }

.rv-verified-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:#f0faf0; border:1px solid #b8dbb8;
    border-radius:100px; padding:3px 10px;
    font-size:10px; font-weight:800; color:#1a6a1a;
    margin-bottom:11px;
}

/* Dark body text on white bg */
.rv-card-text {
    font-size:13px; color:#333; /* solid dark for max readability */
    line-height:1.75; font-style:italic;
}
.rv-card-text::before { content:'\201C'; color:#1a7a1a; font-size:20px; font-style:normal; line-height:0; vertical-align:-6px; margin-right:2px; }
.rv-card-text::after  { content:'\201D'; color:#1a7a1a; font-size:20px; font-style:normal; line-height:0; vertical-align:-6px; margin-left:2px;  }

.rv-empty { grid-column:1/-1; text-align:center; padding:48px 20px; color:#8aaa8a; font-size:14px; }
.rv-empty i { font-size:28px; color:#ccc; margin-bottom:12px; display:block; }

/* ── Pagination ── */
.rv-pagination { display:flex; align-items:center; justify-content:center; gap:6px; padding:20px 16px 8px; flex-wrap:wrap; }
.rv-page-info  { font-size:11px; color:#8aaa8a; font-weight:600; text-align:center; padding:2px 16px 20px; }
.rv-pg-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:4px;
    height:36px; padding:0 14px;
    background:#fff; border:1px solid #d0e4d0;
    border-radius:8px; font-family:'Barlow',sans-serif;
    font-size:12px; font-weight:700; color:#3a6a3a;
    cursor:pointer; transition:all .18s; white-space:nowrap;
}
.rv-pg-btn:hover         { background:#f0faf0; border-color:#1a7a1a; color:#1a7a1a; }
.rv-pg-btn:disabled      { opacity:.35; cursor:not-allowed; pointer-events:none; }
.rv-pg-btn.active        { background:#1a7a1a; border-color:#1a7a1a; color:#fff; }
.rv-pg-btn i             { font-size:10px; }
.rv-pg-dots              { color:#aaa; font-size:13px; padding:0 3px; align-self:center; }

/* ── Divider ── */
.rv-divider { height:1px; margin:6px 18px; background:#e8ede8; }

/* ── User's own reviews (private) ── */
.rv-my-wrap { padding:0 16px 6px; }
.rv-my-card {
    background:#fff; border:1px solid #e0ede0;
    border-left:3px solid #1a7a1a;
    border-radius:10px; padding:14px 16px; margin-bottom:10px;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
}
.rv-my-name  { font-size:12px; font-weight:800; color:#1a4a1a; margin-bottom:5px; }
.rv-my-stars { display:flex; gap:3px; margin-bottom:6px; }
.rv-my-text  { font-size:13px; color:#333; line-height:1.65; }
.rv-my-time  { font-size:10px; color:#8aaa8a; margin-top:5px; font-weight:600; }

/* ── Write-review form — light card ── */
.rv-form-wrap { padding:4px 16px 40px; }
.rv-form-card {
    background:#fff;
    border:1px solid #dceadc;
    border-radius:16px; padding:24px 20px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
}
.rv-form-lbl {
    display:block; font-size:10px; font-weight:800;
    color:#2a5a2a; text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px;
}

/* Name input */
.rv-name-input {
    display:block; width:100%; box-sizing:border-box;
    background:#fff; border:1px solid #c8dcc8; border-radius:10px;
    padding:12px 14px; font-family:'Barlow',sans-serif;
    font-size:15px; color:#1a1a1a; outline:none;
    transition:border-color .18s, box-shadow .18s;
    -webkit-appearance:none; appearance:none;
}
.rv-name-input::placeholder { color:#aac8aa; }
.rv-name-input:focus { border-color:#1a7a1a; box-shadow:0 0 0 3px rgba(26,122,26,.1); }

/* Star picker */
.rv-star-picker { display:flex; gap:4px; margin-bottom:4px; cursor:pointer; user-select:none; }
.rv-star-picker span { font-size:38px; line-height:1; color:#ddd; transition:color .12s, transform .12s; }
.rv-star-picker span.lit   { color:#f5a623; }
.rv-star-picker span:hover { transform:scale(1.18); }

/* Textarea */
.rv-textarea {
    display:block; width:100%; box-sizing:border-box;
    background:#fff; border:1px solid #c8dcc8; border-radius:10px;
    padding:14px 16px; font-family:'Barlow',sans-serif;
    font-size:14px; line-height:1.65; color:#1a1a1a;
    resize:vertical; min-height:120px; outline:none;
    transition:border-color .18s, box-shadow .18s;
    -webkit-appearance:none; appearance:none;
}
.rv-textarea::placeholder { color:#aac8aa; }
.rv-textarea:focus { border-color:#1a7a1a; box-shadow:0 0 0 3px rgba(26,122,26,.1); }

.rv-char-row { display:flex; justify-content:flex-end; font-size:10px; color:#8aaa8a; margin-top:4px; margin-bottom:16px; }

/* Errors */
.rv-field-err { display:block; font-size:11px; color:#c0392b; margin-top:4px; min-height:16px; font-weight:600; }

/* Success box */
.rv-ok-box {
    display:none;
    background:#f0faf0; border:1px solid #a0d8a0;
    border-radius:10px; padding:13px 16px;
    font-size:13px; font-weight:700; color:#1a5a1a;
    align-items:center; gap:9px; margin-bottom:16px;
}
.rv-ok-box.show { display:flex; animation:rv-fadein .25s ease; }

/* Submit button */
.rv-submit-btn {
    width:100%; padding:14px;
    background:#1a7a1a; color:#fff; border:none; border-radius:10px;
    font-family:'Barlow',sans-serif; font-size:14px; font-weight:800;
    text-transform:uppercase; letter-spacing:.06em;
    cursor:pointer; transition:all .2s;
    box-shadow:0 4px 16px rgba(26,122,26,.25);
    display:flex; align-items:center; justify-content:center; gap:8px;
    position:relative; overflow:hidden;
}
.rv-submit-btn:hover  { background:#22a322; transform:translateY(-1px); box-shadow:0 6px 22px rgba(26,122,26,.35); }
.rv-submit-btn:active { transform:translateY(0); }
.rv-submit-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.rv-spinner { display:none; width:16px; height:16px; border-radius:50%; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; animation:rv-spin .7s linear infinite; flex-shrink:0; }
.rv-submit-btn.loading .rv-spinner { display:inline-block; }

/* Login note */
.rv-login-note {
    text-align:center; padding:28px 18px;
    background:#fff; border:1px solid #dceadc; border-radius:14px;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.rv-login-note strong { color:#1a2a1a; display:block; margin-bottom:6px; font-size:15px; }
.rv-login-note p { font-size:13px; color:#4a6a4a; }
.rv-login-note a { color:#1a7a1a; font-weight:800; text-decoration:none; }
.rv-login-note a:hover { text-decoration:underline; }

@media(max-width:540px){
    .rv-cards-grid { grid-template-columns:1fr; padding:0 12px 4px; }
    .rv-hero { padding:28px 14px 22px; }
    .rv-avg-big { font-size:58px; }
    .rv-form-card { padding:18px 14px; }
    .rv-pagination { gap:4px; }
    .rv-pg-btn { height:34px; padding:0 10px; font-size:11px; }
}
</style>

<?php /* Inject Reviews float button into .sw-float-menu */ ?>
<script>
(function(){
    function inject(){
        var menu = document.querySelector('.sw-float-menu');
        if(!menu || document.getElementById('rv-float-trigger')) return;
        var n = <?=(int)$rv_new_count?>;
        var badge = n > 0
            ? '<span id="rv-gold-badge">'+n+'</span>'
            : '<span id="rv-gold-badge" style="display:none;"></span>';
        menu.insertAdjacentHTML('beforeend',
            '<button id="rv-float-trigger" title="Customer Reviews" onclick="rvOpen()">'+
            '<i class="fa-solid fa-star"></i>'+badge+'</button>');
    }
    if(document.querySelector('.sw-float-menu')){ inject(); }
    else { document.addEventListener('DOMContentLoaded', inject); }
})();
</script>

<!-- ════════════════════════════════════════
     FULL-PAGE OVERLAY
════════════════════════════════════════ -->
<div class="rv-overlay" id="rv-overlay">

    <!-- Topbar -->
    <div class="rv-topbar">
        <button class="rv-back-btn" onclick="rvClose()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="rv-topbar-title">Customer Reviews</div>
        <button class="rv-topbar-close" onclick="rvClose()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Scrollable body -->
    <div class="rv-body" id="rv-body">

        <!-- Hero -->
        <div class="rv-hero">
            <div class="rv-hero-ring"></div>
            <div class="rv-avg-big"><?=number_format($rv_avg,1)?><sup>/5</sup></div>
            <div class="rv-hero-stars-row">
                <?php
                $full  = floor($rv_avg);
                $half  = ($rv_avg - $full) >= 0.5;
                $empty = 5 - $full - ($half?1:0);
                for($i=0;$i<$full;$i++)  echo '<i class="fa-solid fa-star" style="color:#f5a623;font-size:26px;"></i>';
                if($half)                echo '<i class="fa-solid fa-star-half-stroke" style="color:#f5a623;font-size:26px;"></i>';
                for($i=0;$i<$empty;$i++) echo '<i class="fa-regular fa-star" style="color:rgba(255,255,255,.2);font-size:26px;"></i>';
                ?>
            </div>
            <div class="rv-hero-sub">Based on <?=number_format($rv_total)?> verified reviews</div>
            <!-- Distribution -->
            <div class="rv-dist">
                <?php for($s=5;$s>=1;$s--):
                    $pct = round($rv_dist[$s] / $rv_dist_max * 100);
                ?>
                <div class="rv-dist-row">
                    <span><?=$s?><i class="fa-solid fa-star" style="color:#f5a623;font-size:9px;margin-left:1px;"></i></span>
                    <div class="rv-dist-track"><div class="rv-dist-fill" style="width:<?=$pct?>%"></div></div>
                    <span class="rv-dist-cnt"><?=$rv_dist[$s]?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Section header -->
        <div class="rv-sec-hd">
            <div class="rv-sec-title"><i class="fa-solid fa-quote-left"></i> What People Are Saying</div>
            <span class="rv-sec-count" id="rv-sec-count"><?=$rv_total?> reviews</span>
        </div>

        <!-- Cards rendered by JS (paginated) -->
        <div class="rv-cards-grid" id="rv-cards-grid"></div>
        <div class="rv-pagination"  id="rv-pagination"></div>
        <div class="rv-page-info"   id="rv-page-info"></div>

        <div class="rv-divider" style="margin:24px 16px 0;"></div>

        <!-- User's private reviews -->
        <?php if($rv_uid): ?>
        <div class="rv-sec-hd">
            <div class="rv-sec-title"><i class="fa-solid fa-user-pen"></i> Your Reviews</div>
        </div>
        <div class="rv-my-wrap" id="rv-my-wrap">
            <?php if(empty($rv_user)): ?>
            <p id="rv-no-reviews-yet" style="font-size:13px;color:#8aaa8a;padding:0 2px 12px;">You haven't written a review yet.</p>
            <?php else: foreach($rv_user as $r): ?>
            <div class="rv-my-card">
                <div class="rv-my-name"><?=htmlspecialchars($r['username'])?></div>
                <div class="rv-my-stars"><?=rv_stars_html($r['rating'],'13px')?></div>
                <div class="rv-my-text"><?=htmlspecialchars($r['review_text'])?></div>
                <div class="rv-my-time"><?=rv_time_ago($r['created_at'])?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="rv-divider" style="margin:8px 16px 0;"></div>
        <?php endif; ?>

        <!-- Write a review -->
        <div class="rv-sec-hd" style="padding-bottom:8px;">
            <div class="rv-sec-title"><i class="fa-solid fa-pen-to-square"></i> Share Your Experience</div>
        </div>
        <div class="rv-form-wrap">
            <?php if($rv_uid): ?>
            <div class="rv-form-card">
                <div class="rv-ok-box" id="rv-ok">
                    <i class="fa-solid fa-circle-check"></i> Review submitted successfully!
                </div>
                <form id="rv-form" onsubmit="rvSubmit(event)">

                    <label class="rv-form-lbl" for="rv-name-input">Your Name or Title</label>
                    <input type="text" id="rv-name-input" class="rv-name-input"
                           placeholder="e.g. Sarah K., Happy Investor…"
                           maxlength="60" autocomplete="name">
                    <span class="rv-field-err" id="rv-name-err"></span>

                    <label class="rv-form-lbl" style="margin-top:20px;">Your Rating</label>
                    <div class="rv-star-picker" id="rv-picker">
                        <span data-v="1">★</span><span data-v="2">★</span>
                        <span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
                    </div>
                    <input type="hidden" id="rv-rating-val" value="0">
                    <span class="rv-field-err" id="rv-rating-err"></span>

                    <label class="rv-form-lbl" style="margin-top:20px;">Your Review</label>
                    <textarea id="rv-txt" class="rv-textarea"
                              placeholder="Tell others about your experience with the platform…"
                              maxlength="1000" rows="5"></textarea>
                    <div class="rv-char-row" id="rv-cnt">0 / 1000</div>

                    <button type="submit" class="rv-submit-btn" id="rv-btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Submit Review</span>
                        <span class="rv-spinner"></span>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="rv-login-note">
                <i class="fa-solid fa-lock" style="color:#1a7a1a;font-size:26px;margin-bottom:14px;display:block;"></i>
                <strong>Login to leave a review</strong>
                <p>Please <a href="login.php">sign in</a> or <a href="register.php">create an account</a> to share your experience.</p>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /rv-body -->
</div><!-- /rv-overlay -->

<script>
(function(){
'use strict';

/* ── Review data from PHP ── */
var RV_DATA = <?=json_encode($rv_js_reviews, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
var RV_PER  = 10;
var rvPage  = 1;

/* ── Stars HTML helper ── */
function starsHtml(n, sz){
    sz = sz || '14px'; var h = '';
    for(var i=1;i<=5;i++)
        h += '<i class="fa-'+(i<=n?'solid':'regular')+' fa-star" style="color:'+(i<=n?'#f5a623':'#ddd')+';font-size:'+sz+';"></i>';
    return h;
}

/* ── Render one page ── */
function rvRender(page){
    var grid   = document.getElementById('rv-cards-grid');
    var pgWrap = document.getElementById('rv-pagination');
    var pgInfo = document.getElementById('rv-page-info');
    if(!grid) return;

    var total      = RV_DATA.length;
    var totalPages = Math.max(1, Math.ceil(total / RV_PER));
    page  = Math.max(1, Math.min(page, totalPages));
    rvPage = page;

    var start = (page - 1) * RV_PER;
    var slice = RV_DATA.slice(start, start + RV_PER);

    if(total === 0){
        grid.innerHTML = '<div class="rv-empty"><i class="fa-regular fa-star"></i>No public reviews yet.</div>';
        if(pgWrap) pgWrap.innerHTML = '';
        if(pgInfo) pgInfo.textContent = '';
        return;
    }

    /* Build cards */
    var html = '';
    slice.forEach(function(r, idx){
        var av = r.avatar
            ? '<img src="'+r.avatar+'" class="rv-av-img" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
              + '<div class="rv-av-letter" style="background:'+r.color+';display:none;">'+r.initial+'</div>'
            : '<div class="rv-av-letter" style="background:'+r.color+'">'+r.initial+'</div>';

        var vb = r.verified
            ? '<div class="rv-verified-badge"><i class="fa-solid fa-circle-check"></i> Verified User</div>' : '';

        html +=
            '<div class="rv-card" style="animation-delay:'+(idx*0.04)+'s">'+
                '<div class="rv-card-header">'+av+
                    '<div><div class="rv-card-name">'+r.username+'</div>'+
                    '<div class="rv-card-cnt">'+r.reviews_count+' review'+(r.reviews_count!==1?'s':'')+'</div></div>'+
                '</div>'+
                '<div class="rv-card-stars">'+starsHtml(r.rating)+
                    '<span class="rv-card-time">'+r.time_ago+'</span>'+
                '</div>'+
                vb+
                '<div class="rv-card-text">'+r.review_text+'</div>'+
            '</div>';
    });
    grid.innerHTML = html;

    /* Pagination controls */
    if(totalPages <= 1){
        if(pgWrap) pgWrap.innerHTML = '';
        if(pgInfo) pgInfo.textContent = (total > 0 ? 'Showing all '+total+' reviews' : '');
        return;
    }

    var p = '';
    p += '<button class="rv-pg-btn"'+(page<=1?' disabled':'')+' onclick="rvGoPage('+(page-1)+')">'+
         '<i class="fa-solid fa-chevron-left"></i> Prev</button>';

    var rs = Math.max(1, page-2), re = Math.min(totalPages, page+2);
    if(rs > 1){ p += '<button class="rv-pg-btn" onclick="rvGoPage(1)">1</button>'; if(rs>2) p+='<span class="rv-pg-dots">…</span>'; }
    for(var pg = rs; pg <= re; pg++)
        p += '<button class="rv-pg-btn'+(pg===page?' active':'')+'" onclick="rvGoPage('+pg+')">'+pg+'</button>';
    if(re < totalPages){ if(re < totalPages-1) p+='<span class="rv-pg-dots">…</span>'; p+='<button class="rv-pg-btn" onclick="rvGoPage('+totalPages+')">'+totalPages+'</button>'; }

    p += '<button class="rv-pg-btn"'+(page>=totalPages?' disabled':'')+' onclick="rvGoPage('+(page+1)+')">'+
         'Next <i class="fa-solid fa-chevron-right"></i></button>';

    if(pgWrap) pgWrap.innerHTML = p;
    var from = start+1, to = Math.min(start+RV_PER, total);
    if(pgInfo) pgInfo.textContent = 'Showing '+from+'–'+to+' of '+total+' reviews';
}

/* ── Go to page + smooth scroll back to grid top ── */
window.rvGoPage = function(p){
    rvRender(p);
    var body = document.getElementById('rv-body');
    var grid = document.getElementById('rv-cards-grid');
    if(body && grid) body.scrollTo({ top: grid.offsetTop - 60, behavior:'smooth' });
};

/* ── Open / Close ── */
window.rvOpen = function(){
    document.getElementById('rv-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    var b = document.getElementById('rv-gold-badge');
    if(b) b.style.display = 'none';
    /* Update seen cookie */
    var d = new Date(); d.setFullYear(d.getFullYear()+1);
    document.cookie = 'rv_seen_<?=($rv_uid ?: 'guest')?>=' + Math.floor(Date.now()/1000)
        + ';expires=' + d.toUTCString() + ';path=/';
    var body = document.getElementById('rv-body');
    if(body) body.scrollTop = 0;
    /* Render first page if grid is empty */
    var grid = document.getElementById('rv-cards-grid');
    if(grid && grid.innerHTML.trim() === '') rvRender(1);
};
window.rvClose = function(){
    document.getElementById('rv-overlay').classList.remove('open');
    document.body.style.overflow = '';
};

/* Swipe right → close */
var ov = document.getElementById('rv-overlay'), sx = 0;
ov.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; }, {passive:true});
ov.addEventListener('touchend',   function(e){ if(e.changedTouches[0].clientX - sx > 60) rvClose(); }, {passive:true});
document.addEventListener('keydown', function(e){ if(e.key==='Escape') rvClose(); });
/* Pre-render on load so first open is instant */
document.addEventListener('DOMContentLoaded', function(){ rvRender(1); });

/* ── Star picker ── */
var picker      = document.getElementById('rv-picker');
var ratingInput = document.getElementById('rv-rating-val');
if(picker && ratingInput){
    var pstars = picker.querySelectorAll('span');
    function litStars(n){ pstars.forEach(function(s,i){ s.classList.toggle('lit', i<n); }); }
    picker.addEventListener('mouseover',  function(e){ if(e.target.dataset.v) litStars(parseInt(e.target.dataset.v)); });
    picker.addEventListener('mouseleave', function(){ litStars(parseInt(ratingInput.value)||0); });
    picker.addEventListener('click', function(e){
        if(e.target.dataset.v){
            ratingInput.value = parseInt(e.target.dataset.v);
            litStars(parseInt(ratingInput.value));
            var er = document.getElementById('rv-rating-err'); if(er) er.textContent='';
        }
    });
}

/* ── Char counter ── */
var ta = document.getElementById('rv-txt'), cnt = document.getElementById('rv-cnt');
if(ta && cnt) ta.addEventListener('input', function(){ cnt.textContent = ta.value.length+' / 1000'; });

/* ── Submit ── */
window.rvSubmit = function(e){
    e.preventDefault();
    var nameEl    = document.getElementById('rv-name-input');
    var nameErr   = document.getElementById('rv-name-err');
    var ratingErr = document.getElementById('rv-rating-err');
    var name   = (nameEl  ? nameEl.value  : '').trim();
    var rating = parseInt((ratingInput||{}).value||'0');
    var text   = ((ta||{}).value||'').trim();
    var ok = true;

    if(nameErr) nameErr.textContent = '';
    if(ratingErr) ratingErr.textContent = '';

    if(name.length < 2){ if(nameErr) nameErr.textContent='Please enter your name or title.'; ok=false; }
    else if(name.length > 60){ if(nameErr) nameErr.textContent='Name too long (max 60 chars).'; ok=false; }
    if(rating < 1){ if(ratingErr) ratingErr.textContent='Please select a star rating.'; ok=false; }
    if(text.length < 10){ if(ratingErr && !ratingErr.textContent) ratingErr.textContent='Review must be at least 10 characters.'; ok=false; }
    if(!ok) return;

    var btn = document.getElementById('rv-btn');
    if(btn){ btn.classList.add('loading'); btn.disabled=true; }

    var fd = new FormData();
    fd.append('display_name', name);
    fd.append('rating',       rating);
    fd.append('review_text',  text);

    fetch('<?=RV_SUBMIT_URL?>', {method:'POST', credentials:'same-origin', body:fd})
    .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(function(d){
        if(btn){ btn.classList.remove('loading'); btn.disabled=false; }
        if(d.success){
            /* Success banner */
            var okBox = document.getElementById('rv-ok');
            if(okBox){ okBox.classList.add('show'); setTimeout(function(){ okBox.classList.remove('show'); }, 4500); }

            /* Reset form */
            if(nameEl)      nameEl.value = '';
            if(ta)          ta.value = '';
            if(cnt)         cnt.textContent = '0 / 1000';
            if(ratingInput) ratingInput.value = '0';
            if(picker)      picker.querySelectorAll('span').forEach(function(s){ s.classList.remove('lit'); });

            /* Inject into "Your Reviews" section (private, only this user sees it) */
            var wrap = document.getElementById('rv-my-wrap');
            if(wrap){
                var ph = document.getElementById('rv-no-reviews-yet'); if(ph) ph.remove();
                var sh = starsHtml(rating, '13px');
                var safeName = name.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                var safeText = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                var card = document.createElement('div');
                card.className = 'rv-my-card';
                card.style.animation = 'rv-fadein .3s ease';
                card.innerHTML =
                    '<div class="rv-my-name">'+safeName+'</div>'+
                    '<div class="rv-my-stars">'+sh+'</div>'+
                    '<div class="rv-my-text">'+safeText+'</div>'+
                    '<div class="rv-my-time">just now</div>';
                wrap.insertBefore(card, wrap.firstChild);
            }
        } else {
            if(ratingErr) ratingErr.textContent = d.error || 'Something went wrong. Please try again.';
        }
    })
    .catch(function(err){
        if(btn){ btn.classList.remove('loading'); btn.disabled=false; }
        if(ratingErr) ratingErr.textContent = 'Network error — please try again. ('+err.message+')';
    });
};

})();
</script>
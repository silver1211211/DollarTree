<?php
// user/login.php
require_once __DIR__ . '/../config.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_method = $_POST['login_method'] ?? 'email';
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    global $pdo;
    $stmt = $login_method === 'email'
        ? $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1")
        : $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch();
    if ($user && verify_password($password, $user['password_hash'])) {
        if ($user['account_status'] !== 'active') {
            $error = 'Your account is ' . $user['account_status'] . '.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
            $stmt->execute([get_client_ip(), $user['id']]);
            $today = date('Y-m-d');
            if ($user['last_withdrawal_date'] !== $today) {
                $stmt = $pdo->prepare("UPDATE users SET daily_withdrawal_count = 0, last_withdrawal_date = ? WHERE id = ?");
                $stmt->execute([$today, $user['id']]);
            }
            log_activity($user['id'], 'login', 'User logged in');
            header('Location: dashboard.php'); exit;
        }
    } else { $error = 'Invalid credentials. Please try again.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Dollar Tree Investment</title>
<script>
document.documentElement.classList.add('fonts-loading');
(function () {
    var reveal = function () { document.documentElement.classList.remove('fonts-loading'); };
    if (document.fonts && document.fonts.ready) {
        Promise.race([document.fonts.ready, new Promise(function (resolve) { setTimeout(resolve, 1800); })]).then(reveal, reveal);
    } else {
        window.addEventListener('load', reveal, { once: true });
    }
}());
</script>
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
html.fonts-loading body { visibility: hidden; }
:root {
    --gp: #1a7a1a;
    --gb: #22a322;
    --gl: #34d058;
    --gg: rgba(34,163,34,0.15);
    --gs: #f0faf0;
    --dark: #071007;
    --dark2: #0d1a0d;
    --tp: #0d1a0d;
    --ts: #3d5c3d;
    --tm: #7a9a7a;
    --border: #d0e8d0;
    --bg: #f5faf5;
    --white: #ffffff;
    --sh: 0 1px 4px rgba(26,122,26,0.08);
    --r: 12px;
}
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

html, body { height: 100%; }

body {
    font-family: 'Barlow', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
}

/* ===== LEFT PANEL ===== */
.lp {
    flex: 1;
    background: var(--dark);
    display: flex; flex-direction: column;
    justify-content: space-between;
    padding: 52px 48px;
    position: relative; overflow: hidden;
    min-height: 100vh;
}
.lp::before {
    content:''; position:absolute; inset:0; pointer-events:none;
    background:
        radial-gradient(ellipse 80% 55% at 15% 10%, rgba(34,163,34,0.22) 0%, transparent 65%),
        radial-gradient(ellipse 50% 65% at 88% 88%, rgba(26,122,26,0.16) 0%, transparent 60%);
}
.grid-bg {
    position:absolute; inset:0; pointer-events:none;
    background-image:
        linear-gradient(rgba(34,163,34,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(34,163,34,0.05) 1px, transparent 1px);
    background-size: 52px 52px;
}
.ring { position:absolute; border-radius:50%; border:1px solid rgba(34,163,34,0.09); pointer-events:none; }
.r1 { width:500px; height:500px; top:-200px; right:-200px; }
.r2 { width:290px; height:290px; top:-90px; right:-90px; }
.r3 { width:320px; height:320px; bottom:-130px; left:-110px; }

.lp-top { position:relative; z-index:2; }
.brand {
    display:flex; align-items:center; gap:14px;
}
.bran {
    text-align:center;
}
.brand-logo-img {
    height: 34px; width: auto;
    object-fit: contain;
    filter: brightness(0) invert(1) sepia(1) saturate(4) hue-rotate(85deg) brightness(1.1);
}

.lp-mid { position:relative; z-index:2; }
.badge {
    display:inline-flex; align-items:center; gap:8px;
    background: rgba(52,208,88,0.12);
    border: 1px solid rgba(52,208,88,0.22);
    color: var(--gl);
    font-size:11px; font-weight:700;
    letter-spacing:0.09em; text-transform:uppercase;
    padding:6px 14px; border-radius:100px; margin-bottom:28px;
}
.headline {
    font-family:'Barlow Condensed', sans-serif;
    font-size: clamp(36px, 4vw, 58px);
    font-weight:800; color:white;
    line-height:1.02; letter-spacing:-0.01em; margin-bottom:18px;
}
.headline .ac { color: var(--gl); }
.desc { color:rgba(255,255,255,0.42); font-size:15px; line-height:1.7; max-width:320px; }

.stats {
    position:relative; z-index:2;
    display:grid; grid-template-columns:1fr 1fr; gap:12px;
}
.stat {
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(52,208,88,0.12);
    border-radius:var(--r); padding:18px 20px;
}
.sv {
    font-family:'Barlow Condensed', sans-serif;
    font-size:26px; font-weight:800; color:var(--gl);
    line-height:1; margin-bottom:3px;
}
.sl { font-size:11px; color:rgba(255,255,255,0.32); font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }

/* ===== RIGHT PANEL ===== */
.rp {
    width:480px; flex-shrink:0;
    background:var(--white);
    display:flex; flex-direction:column; justify-content:center;
    padding:0;
    box-shadow: -16px 0 48px rgba(0,0,0,0.07);
    overflow-y: auto;
    min-height: 100vh;
}

/* mobile header shown only on small screens */
.mobile-header {
    display:none;
    flex-direction:column; align-items:center;
    padding: 32px 24px 24px;
    background: var(--dark);
    position:relative; overflow:hidden;
}
.mobile-header::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse 100% 80% at 50% 0%, rgba(34,163,34,0.25) 0%, transparent 70%);
    pointer-events:none;
}
.mobile-header .grid-bg { background-size:40px 40px; }
.mh-inner { position:relative; z-index:2; text-align:center; }
.mh-logo-img {
    height:28px; width:auto; object-fit:contain;
    filter: brightness(0) invert(1) sepia(1) saturate(4) hue-rotate(85deg) brightness(1.1);
    margin-bottom:14px;
}
.mh-title {
    font-family:'Barlow Condensed', sans-serif;
    font-size:26px; font-weight:800; color:white;
    letter-spacing:-0.01em; margin-bottom:6px;
}
.mh-title .ac { color:var(--gl); }
.mh-sub { font-size:13px; color:rgba(255,255,255,0.5); }

.fh { margin-bottom:28px; }
.fh h2 {
    font-family:'Barlow Condensed', sans-serif;
    font-size:32px; font-weight:800; color:var(--tp);
    letter-spacing:-0.02em; margin-bottom:6px;
}
.fh p { color:var(--tm); font-size:14px; }

/* TABS */
.tabs {
    display:flex; gap:3px;
    background:var(--bg); border-radius:10px;
    padding:4px; margin-bottom:24px;
}
.tab-btn {
    flex:1; padding:10px 6px;
    border:none; background:transparent; border-radius:7px;
    font-family:'Barlow', sans-serif;
    font-size:13px; font-weight:600; color:var(--tm);
    cursor:pointer; transition:all 0.18s;
    display:flex; align-items:center; justify-content:center; gap:6px;
}
.tab-btn.active { background:var(--white); color:var(--gp); box-shadow:var(--sh); }
.tab-btn i { font-size:12px; }

.tp-pane { display:none; }
.tp-pane.active { display:block; animation:fadeUp 0.2s ease; }
@keyframes fadeUp { from{opacity:0;transform:translateY(7px)} to{opacity:1;transform:translateY(0)} }

/* FIELDS */
.fg { margin-bottom:16px; }
.fl {
    display:block; font-size:11px; font-weight:700;
    color:var(--ts); letter-spacing:0.07em;
    text-transform:uppercase; margin-bottom:7px;
}
.fw { position:relative; }
.fi {
    position:absolute; left:14px; top:50%;
    transform:translateY(-50%);
    color:#b8d0b8; font-size:14px;
    pointer-events:none; transition:color 0.18s; z-index:1;
}
.fw:focus-within .fi { color:var(--gp); }
.fw input {
    width:100%; height:48px;
    padding:0 44px 0 42px;
    border:1.5px solid var(--border); border-radius:var(--r);
    font-family:'Barlow', sans-serif;
    font-size:15px; font-weight:500; color:var(--tp);
    background:var(--white); transition:all 0.18s; outline:none;
    -webkit-appearance:none;
}
.fw input:focus { border-color:var(--gp); box-shadow:0 0 0 3px var(--gg); }
.fw input::placeholder { color:#c4d8c4; font-weight:400; }
.tv {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    background:none; border:none; cursor:pointer;
    color:#c4d8c4; font-size:14px; padding:4px; transition:color 0.18s;
}
.tv:hover { color:var(--gp); }

/* ALERT */
.alert {
    display:none; align-items:flex-start; gap:10px;
    padding:12px 14px; border-radius:var(--r);
    font-size:13px; font-weight:500; margin-bottom:16px;
}
.alert.error { background:#fff2f2; border:1px solid #ffd0d0; color:#c0392b; }
.alert.success { background:var(--gs); border:1px solid #c8e6c9; color:var(--gp); }
.alert.show { display:flex; animation:fadeUp 0.2s ease; }
.alert i { flex-shrink:0; margin-top:1px; }

/* COMING SOON */
.cs { text-align:center; padding:32px 20px; }
.cs-icon {
    width:64px; height:64px; background:#f0f8ff;
    border-radius:50%; display:flex; align-items:center;
    justify-content:center; margin:0 auto 16px;
    font-size:28px; color:#229ed9;
}
.cs h4 { font-size:16px; font-weight:700; color:var(--tp); margin-bottom:6px; }
.cs p { font-size:13px; color:var(--tm); line-height:1.6; }

/* BUTTON */
.btn-sub {
    width:100%; height:50px;
    background:var(--gp); color:white; border:none;
    border-radius:var(--r);
    font-family:'Barlow', sans-serif;
    font-size:15px; font-weight:700; letter-spacing:0.02em;
    cursor:pointer; display:flex; align-items:center;
    justify-content:center; gap:10px;
    transition:all 0.2s; margin-top:8px;
    position:relative; overflow:hidden;
}
.btn-sub::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(255,255,255,0.07), transparent);
    pointer-events:none;
}
.btn-sub:hover { background:var(--gb); transform:translateY(-1px); box-shadow:0 8px 24px rgba(26,122,26,0.35); }
.btn-sub:active { transform:none; }

/* DIVIDER */
.div {
    display:flex; align-items:center; gap:12px; margin:20px 0;
    color:var(--tm); font-size:11px; font-weight:700;
    letter-spacing:0.07em; text-transform:uppercase;
}
.div::before, .div::after { content:''; flex:1; height:1px; background:var(--border); }

/* FOOTER */
.ff { text-align:center; font-size:13px; color:var(--tm); }
.ff a { color:var(--gp); font-weight:700; text-decoration:none; }
.ff a:hover { text-decoration:underline; }

/* PAGE FOOTER (mobile) */
.page-footer {
    display:block;
    background:var(--dark2);
    padding:20px 24px;
    text-align:center;
}
.page-footer p { font-size:12px; color:rgba(255,255,255,0.3); line-height:1.6; }
.page-footer a { color:var(--gl); text-decoration:none; font-weight:600; }

/* ===== RESPONSIVE ===== */
@media (max-width:900px) {
    body { flex-direction:column; min-height:100vh; min-height:100dvh; }
    .lp { display:none; }
    .rp {
        width:100%; min-height:100vh; min-height:100dvh;
        padding:0 0 0 0;
        box-shadow:none;
        display:flex;
        flex-direction:column;
    }
    .mobile-header { display:flex; }
    .rp-inner {
        flex:1 0 auto;
        padding: 28px 20px 32px;
    }
    .fh { margin-bottom:22px; }
    .fh h2 { font-size:26px; }
    .page-footer { display:block; flex-shrink:0; margin-top:auto; }
}
@media (min-width:901px) {
    .rp-inner { flex:1; display:flex; flex-direction:column; justify-content:safe center; padding:56px 44px; min-height:0; }
    .page-footer { flex-shrink:0; }
}
@media (min-width:901px) and (max-height:750px) {
    .rp-inner { justify-content:flex-start; padding:22px 44px; }
    .fh { margin-bottom:14px; }
    .fh h2 { font-size:28px; margin-bottom:2px; }
    .tabs { margin-bottom:14px; }
    .tab-btn { padding:8px 6px; }
    .fg { margin-bottom:10px; }
    .fw input { height:42px; }
    .btn-sub { height:44px; margin-top:4px; }
    .div { margin:12px 0; }
    .page-footer { padding:12px 20px; }
}
</style>
</head>
<body>

<!-- LEFT PANEL (desktop) -->
<div class="lp">
    <div class="grid-bg"></div>
    <div class="ring r1"></div><div class="ring r2"></div><div class="ring r3"></div>
    <div class="lp-top">
        <div class="brand">
            <img class="brand-logo-img" src="/user/images/logotree.png?v=20260812" alt="Dollar Tree">
        </div>
    </div>
    <div class="lp-mid">
        <div class="badge"><i class="fa-solid fa-chart-line"></i> Investor Portal</div>
        <div class="headline">Grow your wealth<br>with <span class="ac">daily returns.</span></div>
        <p class="desc">Join thousands of investors earning consistent daily profits. Secure, transparent, and built for long-term financial growth.</p>
    </div>
    <div class="stats">
        <div class="stat"><div class="sv">$2.4M+</div><div class="sl">Paid out daily</div></div>
        <div class="stat"><div class="sv">98.6%</div><div class="sl">On-time returns</div></div>
        <div class="stat"><div class="sv">50K+</div><div class="sl">Active investors</div></div>
        <div class="stat"><div class="sv">4.5%</div><div class="sl">Avg. daily yield</div></div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="rp">
    <!-- Mobile header -->
    <div class="mobile-header">
        <div class="grid-bg"></div>
        <div class="bran">
            <img class="brand-logo-img" src="/user/images/logotree.png?v=20260812" alt="Dollar Tree">
            <div class="mh-title">Grow your wealth<br>with <span class="ac">daily returns.</span></div>
            <p class="mh-sub">Investor portal — secure & transparent</p>
        </div>
    </div>

    <div class="rp-inner">
        <div class="fh">
            <h2>Welcome back</h2>
            <p>Sign in to your investment account</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert error show">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('email',this)"><i class="fa-regular fa-envelope"></i> Email</button>
            <button class="tab-btn" onclick="switchTab('phone',this)"><i class="fa-solid fa-mobile-screen-button"></i> Phone</button>
            <button class="tab-btn" onclick="switchTab('telegram',this)"><i class="fa-brands fa-telegram"></i> Telegram</button>
        </div>

        <!-- EMAIL -->
        <div id="pane-email" class="tp-pane active">
            <form method="POST" action="">
                <input type="hidden" name="login_method" value="email">
                <div class="fg">
                    <label class="fl">Email address</label>
                    <div class="fw">
                        <i class="fa-regular fa-envelope fi"></i>
                        <input type="email" name="identifier" required placeholder="your@email.com" autocomplete="email">
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Password</label>
                    <div class="fw">
                        <i class="fa-solid fa-lock fi"></i>
                        <input type="password" name="password" id="pw-e" required placeholder="••••••••" autocomplete="current-password">
                        <button type="button" class="tv" onclick="togglePw('pw-e',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-sub"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</button>
            </form>
        </div>

        <!-- PHONE -->
        <div id="pane-phone" class="tp-pane">
            <form method="POST" action="">
                <input type="hidden" name="login_method" value="phone">
                <div class="fg">
                    <label class="fl">Phone number</label>
                    <div class="fw">
                        <i class="fa-solid fa-phone fi"></i>
                        <input type="tel" name="identifier" required placeholder="+1 234 567 8900">
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Password</label>
                    <div class="fw">
                        <i class="fa-solid fa-lock fi"></i>
                        <input type="password" name="password" id="pw-p" required placeholder="••••••••" autocomplete="current-password">
                        <button type="button" class="tv" onclick="togglePw('pw-p',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-sub"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</button>
            </form>
        </div>

        <!-- TELEGRAM -->
        <div id="pane-telegram" class="tp-pane">
            <div class="cs">
                <div class="cs-icon"><i class="fa-brands fa-telegram"></i></div>
                <h4>Telegram Login — Coming Soon</h4>
                <p>We're working on this feature.<br>Use Email or Phone to sign in for now.</p>
            </div>
        </div>

        <div class="div">or</div>
        <div class="ff">No account yet? <a href="register.php">Create one free</a></div>
    </div>

    <!-- Mobile footer -->
    <div class="page-footer">
        <p>
            &copy; <?php echo date('Y'); ?> Dollar Tree Investment Platform<br>
            <a href="privacy.php">Privacy Policy</a> &nbsp;&middot;&nbsp; <a href="terms.php">Terms of Service</a> &nbsp;&middot;&nbsp; <a href="https://t.me/dollartreesupport" target="_blank" rel="noopener">Support</a>
        </p>
    </div>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tp-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('pane-' + id).classList.add('active');
}
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
}
</script>
</body>
</html>

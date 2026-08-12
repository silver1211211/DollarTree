<?php
// user/register.php
require_once __DIR__ . '/../config.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
$invitation_code = $_GET['ref'] ?? '';
$page_title = 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Account — Dollar Tree Investment</title>
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
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
    -webkit-font-smoothing: antialiased;
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
.lp-tops {text-align:center; }
.brand {
    display:flex; align-items:center; gap:14px;
    text-decoration:none;
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

.perks {
    position:relative; z-index:2;
    display:flex; flex-direction:column; gap:10px;
}
.perk {
    display:flex; align-items:center; gap:14px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(52,208,88,0.10);
    border-radius:var(--r); padding:14px 16px;
}
.perk-icon {
    width:38px; height:38px; flex-shrink:0;
    background:rgba(26,122,26,0.18);
    border:1px solid rgba(52,208,88,0.18);
    border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; color:var(--gl);
}
.perk-text h5 { font-size:13px; font-weight:700; color:#fff; margin-bottom:2px; }
.perk-text p  { font-size:12px; color:rgba(255,255,255,0.30); line-height:1.45; }

/* ===== RIGHT PANEL ===== */
.rp {
    width:500px; flex-shrink:0;
    background:var(--white);
    display:flex; flex-direction:column; justify-content:center;
    padding:52px 44px;
    box-shadow: -16px 0 48px rgba(0,0,0,0.07);
    overflow-y: auto;
    min-height: 100vh;
}

/* mobile header */
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

/* FORM HEAD */
.fh { margin-bottom:24px; }
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
    padding:4px; margin-bottom:20px;
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

/* ALERT */
.alert {
    display:none; align-items:flex-start; gap:10px;
    padding:12px 14px; border-radius:var(--r);
    font-size:13px; font-weight:500; margin-bottom:16px;
}
.alert.error   { background:#fff2f2; border:1px solid #ffd0d0; color:#c0392b; }
.alert.success { background:var(--gs); border:1px solid #c8e6c9; color:var(--gp); }
.alert.show    { display:flex; animation:fadeUp 0.2s ease; }
.alert i       { flex-shrink:0; margin-top:1px; }

/* SPINNER */
.spinner-wrap { display:none; flex-direction:column; align-items:center; justify-content:center; padding:36px; }
.spinner-wrap.show { display:flex; }
.spinner-ring {
    width:40px; height:40px;
    border:3px solid var(--border);
    border-top-color:var(--gp);
    border-radius:50%;
    animation:spin .7s linear infinite;
    margin-bottom:12px;
}
@keyframes spin { to{transform:rotate(360deg)} }
.spinner-wrap p { font-size:14px; color:var(--tm); font-weight:500; }

/* FIELDS */
.fg { margin-bottom:14px; }
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

/* PHONE ROW */
.phone-row { display:flex; gap:8px; }
.country-wrap { position:relative; flex-shrink:0; }
.country-trigger {
    height:48px; padding:0 10px 0 12px;
    border:1.5px solid var(--border); border-radius:var(--r);
    background:var(--white);
    font-family:'Barlow', sans-serif;
    font-size:13px; font-weight:600; color:var(--tp);
    cursor:pointer; transition:all 0.18s;
    display:flex; align-items:center; gap:5px;
    white-space:nowrap; min-width:92px; max-width:92px;
    outline:none; user-select:none;
}
.country-trigger:focus,
.country-trigger.open { border-color:var(--gp); box-shadow:0 0 0 3px var(--gg); }
.ct-chevron { font-size:9px; color:var(--tm); margin-left:auto; transition:transform 0.2s; }
.country-trigger.open .ct-chevron { transform:rotate(180deg); }

.country-dropdown {
    position:absolute; top:calc(100% + 6px); left:0; width:300px;
    background:var(--white);
    border:1.5px solid var(--border); border-radius:var(--r);
    box-shadow:0 16px 48px rgba(7,16,7,0.12);
    z-index:2000; display:none; overflow:hidden;
}
.country-dropdown.open { display:block; animation:fadeUp 0.18s ease; }
.cd-search {
    padding:10px 12px; border-bottom:1px solid var(--border);
    display:flex; align-items:center; gap:8px;
}
.cd-search i { color:var(--tm); font-size:12px; }
.cd-search input {
    width:100%; border:none; outline:none;
    font-family:'Barlow', sans-serif;
    font-size:13px; color:var(--tp); background:transparent;
}
.cd-search input::placeholder { color:var(--tm); }
.cd-list { max-height:220px; overflow-y:auto; }
.cd-list::-webkit-scrollbar { width:4px; }
.cd-list::-webkit-scrollbar-thumb { background:var(--border); border-radius:2px; }
.cd-item {
    display:flex; align-items:center; gap:10px;
    padding:9px 14px; cursor:pointer;
    font-size:13px; font-weight:500; color:var(--tp);
    transition:background 0.12s;
}
.cd-item:hover { background:var(--gs); }
.cd-item.sel   { background:rgba(26,122,26,0.07); color:var(--gp); }
.cd-item-name  { flex:1; }
.cd-item-dial  { font-size:12px; color:var(--tm); }

.phone-input-wrap { flex:1; position:relative; min-width:0; }
.phone-input-wrap .fi { left:12px; }
.phone-field {
    width:100%; height:48px;
    padding:0 14px 0 38px;
    border:1.5px solid var(--border); border-radius:var(--r);
    font-family:'Barlow', sans-serif;
    font-size:15px; font-weight:500; color:var(--tp);
    background:var(--white); transition:all 0.18s; outline:none;
    -webkit-appearance:none;
}
.phone-field:focus { border-color:var(--gp); box-shadow:0 0 0 3px var(--gg); }
.phone-field::placeholder { color:#c4d8c4; font-weight:400; }
.phone-input-wrap:focus-within .fi { color:var(--gp); }

.detect-hint {
    font-size:11px; color:var(--gp); font-weight:600;
    margin-top:5px; display:none; align-items:center; gap:4px;
}
.detect-hint.show { display:flex; }

/* INVITE BOX */
.invite-box {
    background:var(--bg);
    border:1.5px dashed var(--border); border-radius:var(--r);
    padding:12px 14px; margin-bottom:14px;
    display:flex; align-items:center; gap:10px;
}
.invite-box i { color:var(--gp); font-size:13px; flex-shrink:0; }
.invite-box input {
    flex:1; border:none; background:transparent;
    font-family:'Barlow', sans-serif;
    font-size:14px; font-weight:600; color:var(--tp); outline:none;
}
.invite-box input::placeholder { color:var(--tm); font-weight:400; }

/* BUTTON */
.btn-sub {
    width:100%; height:50px;
    background:var(--gp); color:white; border:none;
    border-radius:var(--r);
    font-family:'Barlow', sans-serif;
    font-size:15px; font-weight:700; letter-spacing:0.02em;
    cursor:pointer; display:flex; align-items:center;
    justify-content:center; gap:10px;
    transition:all 0.2s; margin-top:6px;
    position:relative; overflow:hidden;
}
.btn-sub::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(255,255,255,0.07), transparent);
    pointer-events:none;
}
.btn-sub:hover { background:var(--gb); transform:translateY(-1px); box-shadow:0 8px 24px rgba(26,122,26,0.35); }
.btn-sub:active { transform:none; }
.btn-sub:disabled { opacity:.55; cursor:not-allowed; transform:none; box-shadow:none; }

/* DIVIDER */
.div {
    display:flex; align-items:center; gap:12px; margin:18px 0;
    color:var(--tm); font-size:11px; font-weight:700;
    letter-spacing:0.07em; text-transform:uppercase;
}
.div::before, .div::after { content:''; flex:1; height:1px; background:var(--border); }

/* FOOTER LINK */
.ff { text-align:center; font-size:13px; color:var(--tm); }
.ff a { color:var(--gp); font-weight:700; text-decoration:none; }
.ff a:hover { text-decoration:underline; }

/* PAGE FOOTER (mobile) */
.page-footer {
    display:none;
    background:var(--dark2);
    padding:20px 24px;
    text-align:center;
}
.page-footer p { font-size:12px; color:rgba(255,255,255,0.3); line-height:1.6; }
.page-footer a { color:var(--gl); text-decoration:none; font-weight:600; }

/* ===== RESPONSIVE ===== */
@media (max-width:900px) {
    body { flex-direction:column; }
    .lp { display:none; }
    .rp {
        width:100%; min-height:auto;
        padding:0; box-shadow:none;
        display:block;
    }
    .mobile-header { display:flex; }
    .rp-inner { padding:28px 20px 32px; }
    .fh { margin-bottom:20px; }
    .fh h2 { font-size:26px; }
    .page-footer { display:block; }
    .country-dropdown { width:calc(100vw - 40px); max-width:290px; }
}
@media (min-width:901px) {
    .rp-inner { flex:1; display:flex; flex-direction:column; justify-content:center; padding:0; }
}
</style>
</head>
<body>

<!-- LEFT PANEL (desktop) -->
<div class="lp">
    <div class="grid-bg"></div>
    <div class="ring r1"></div><div class="ring r2"></div><div class="ring r3"></div>

    <div class="lp-top">
        <a href="login.php" class="brand">
            <img class="brand-logo-img" src="images/logotree.png" alt="Dollar Tree">
        </a>
    </div>

    <div class="lp-mid">
        <div class="badge"><i class="fa-solid fa-seedling"></i> Start Investing Today</div>
        <div class="headline">Unlock your wealth<br>with <span class="ac">daily returns.</span></div>
        <p class="desc">Join a platform where your money works around the clock. Transparent yields, daily payouts, and a secure environment built for you.</p>
    </div>

    <div class="perks">
        <div class="perk">
            <div class="perk-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div class="perk-text">
                <h5>Daily Returns</h5>
                <p>Earn up to 4.5% daily yield on your investment portfolio</p>
            </div>
        </div>
        <div class="perk">
            <div class="perk-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="perk-text">
                <h5>Secure &amp; Transparent</h5>
                <p>Fully audited platform with bank-grade encryption</p>
            </div>
        </div>
        <div class="perk">
            <div class="perk-icon"><i class="fa-solid fa-bolt"></i></div>
            <div class="perk-text">
                <h5>Instant Withdrawals</h5>
                <p>Access your earnings anytime with zero withdrawal delays</p>
            </div>
        </div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="rp">

    <!-- Mobile header -->
    <div class="mobile-header">
        <div class="grid-bg"></div>
        <div class="lp-tops">
            <img class="brand-logo-img" src="images/logotree.png" alt="Dollar Tree">
            <div class="mh-title">Start earning <span class="ac">daily returns.</span></div>
            <p class="mh-sub">Create your free investor account</p>
        </div>
    </div>

    <div class="rp-inner">

        <div class="fh">
            <h2>Create account</h2>
            <p>Join Dollar Tree — free to get started</p>
        </div>

        <div id="alert-box" class="alert"></div>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('email',this)">
                <i class="fa-regular fa-envelope"></i> Email
            </button>
            <button class="tab-btn" onclick="switchTab('phone',this)">
                <i class="fa-solid fa-mobile-screen-button"></i> Phone
            </button>
        </div>

        <!-- EMAIL PANE -->
        <div id="pane-email" class="tp-pane active">
            <div id="spin-email" class="spinner-wrap">
                <div class="spinner-ring"></div>
                <p>Creating your account…</p>
            </div>
            <form id="form-email" onsubmit="handleReg(event,'email')">
                <div class="fg">
                    <label class="fl">Email address</label>
                    <div class="fw">
                        <i class="fa-regular fa-envelope fi"></i>
                        <input type="email" name="email" required placeholder="you@example.com" autocomplete="email">
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Password</label>
                    <div class="fw">
                        <i class="fa-solid fa-lock fi"></i>
                        <input type="password" name="password" id="pw-e1" required placeholder="••••••••" minlength="6">
                        <button type="button" class="tv" onclick="togglePw('pw-e1',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Confirm password</label>
                    <div class="fw">
                        <i class="fa-solid fa-shield-halved fi"></i>
                        <input type="password" name="confirm_password" id="pw-e2" required placeholder="••••••••">
                        <button type="button" class="tv" onclick="togglePw('pw-e2',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="invite-box">
                    <i class="fa-solid fa-ticket"></i>
                    <input type="text" name="invitation_code" value="<?php echo htmlspecialchars($invitation_code); ?>" placeholder="Referral / invitation code (optional)">
                </div>
                <button type="submit" class="btn-sub" id="sub-email">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </form>
        </div>

        <!-- PHONE PANE -->
        <div id="pane-phone" class="tp-pane">
            <div id="spin-phone" class="spinner-wrap">
                <div class="spinner-ring"></div>
                <p>Creating your account…</p>
            </div>
            <form id="form-phone" onsubmit="handleReg(event,'phone')">
                <div class="fg">
                    <label class="fl">Phone number</label>
                    <div class="phone-row">
                        <div class="country-wrap">
                            <div class="country-trigger" id="ct" onclick="toggleDD()" tabindex="0" onkeydown="ctKey(event)">
                                <span id="sel-flag">🇺🇸</span>
                                <span id="sel-dial" style="font-size:12px;color:var(--tm)">+1</span>
                                <i class="fa-solid fa-chevron-down ct-chevron"></i>
                            </div>
                            <div class="country-dropdown" id="cd">
                                <div class="cd-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="cd-search-inp" placeholder="Search country…" oninput="filterC(this.value)">
                                </div>
                                <div class="cd-list" id="cd-list"></div>
                            </div>
                            <input type="hidden" name="country_code" id="cc-val" value="+1">
                            <input type="hidden" name="country_iso" id="iso-val" value="US">
                        </div>
                        <div class="phone-input-wrap">
                            <i class="fa-solid fa-mobile-screen-button fi"></i>
                            <input class="phone-field" type="tel" name="phone_number" id="ph-num" required placeholder="234 567 8900" oninput="autoDetect(this.value)">
                        </div>
                    </div>
                    <div class="detect-hint" id="det"><i class="fa-solid fa-location-dot"></i><span id="det-txt"></span></div>
                </div>
                <div class="fg">
                    <label class="fl">Password</label>
                    <div class="fw">
                        <i class="fa-solid fa-lock fi"></i>
                        <input type="password" name="password" id="pw-p1" required placeholder="••••••••" minlength="6">
                        <button type="button" class="tv" onclick="togglePw('pw-p1',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="fg">
                    <label class="fl">Confirm password</label>
                    <div class="fw">
                        <i class="fa-solid fa-shield-halved fi"></i>
                        <input type="password" name="confirm_password" id="pw-p2" required placeholder="••••••••">
                        <button type="button" class="tv" onclick="togglePw('pw-p2',this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="invite-box">
                    <i class="fa-solid fa-ticket"></i>
                    <input type="text" name="invitation_code" value="<?php echo htmlspecialchars($invitation_code); ?>" placeholder="Referral / invitation code (optional)">
                </div>
                <button type="submit" class="btn-sub" id="sub-phone">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </form>
        </div>

        <div class="div">or</div>
        <div class="ff">Already have an account? <a href="login.php">Sign in</a></div>

    </div><!-- /rp-inner -->

    <!-- Mobile footer -->
    <div class="page-footer">
        <p>
            &copy; <?php echo date('Y'); ?> Dollar Tree Investment Platform<br>
            <a href="#">Privacy Policy</a> &nbsp;&middot;&nbsp; <a href="#">Terms of Service</a> &nbsp;&middot;&nbsp; <a href="#">Support</a>
        </p>
    </div>

</div><!-- /rp -->

<script>
const COUNTRIES=[
  {i:'AF',f:'🇦🇫',n:'Afghanistan',d:'+93'},{i:'AL',f:'🇦🇱',n:'Albania',d:'+355'},{i:'DZ',f:'🇩🇿',n:'Algeria',d:'+213'},
  {i:'AR',f:'🇦🇷',n:'Argentina',d:'+54'},{i:'AU',f:'🇦🇺',n:'Australia',d:'+61'},{i:'AT',f:'🇦🇹',n:'Austria',d:'+43'},
  {i:'AZ',f:'🇦🇿',n:'Azerbaijan',d:'+994'},{i:'BH',f:'🇧🇭',n:'Bahrain',d:'+973'},{i:'BD',f:'🇧🇩',n:'Bangladesh',d:'+880'},
  {i:'BE',f:'🇧🇪',n:'Belgium',d:'+32'},{i:'BR',f:'🇧🇷',n:'Brazil',d:'+55'},{i:'CA',f:'🇨🇦',n:'Canada',d:'+1'},
  {i:'CL',f:'🇨🇱',n:'Chile',d:'+56'},{i:'CN',f:'🇨🇳',n:'China',d:'+86'},{i:'CO',f:'🇨🇴',n:'Colombia',d:'+57'},
  {i:'EG',f:'🇪🇬',n:'Egypt',d:'+20'},{i:'ET',f:'🇪🇹',n:'Ethiopia',d:'+251'},{i:'FR',f:'🇫🇷',n:'France',d:'+33'},{i:'DE',f:'🇩🇪',n:'Germany',d:'+49'},
  {i:'GH',f:'🇬🇭',n:'Ghana',d:'+233'},{i:'GR',f:'🇬🇷',n:'Greece',d:'+30'},{i:'HK',f:'🇭🇰',n:'Hong Kong',d:'+852'},
  {i:'IN',f:'🇮🇳',n:'India',d:'+91'},{i:'ID',f:'🇮🇩',n:'Indonesia',d:'+62'},{i:'IR',f:'🇮🇷',n:'Iran',d:'+98'},
  {i:'IQ',f:'🇮🇶',n:'Iraq',d:'+964'},{i:'IE',f:'🇮🇪',n:'Ireland',d:'+353'},{i:'IL',f:'🇮🇱',n:'Israel',d:'+972'},
  {i:'IT',f:'🇮🇹',n:'Italy',d:'+39'},{i:'JP',f:'🇯🇵',n:'Japan',d:'+81'},{i:'JO',f:'🇯🇴',n:'Jordan',d:'+962'},
  {i:'KE',f:'🇰🇪',n:'Kenya',d:'+254'},{i:'KW',f:'🇰🇼',n:'Kuwait',d:'+965'},{i:'LB',f:'🇱🇧',n:'Lebanon',d:'+961'},
  {i:'MY',f:'🇲🇾',n:'Malaysia',d:'+60'},{i:'MX',f:'🇲🇽',n:'Mexico',d:'+52'},{i:'MA',f:'🇲🇦',n:'Morocco',d:'+212'},
  {i:'NP',f:'🇳🇵',n:'Nepal',d:'+977'},{i:'NL',f:'🇳🇱',n:'Netherlands',d:'+31'},{i:'NZ',f:'🇳🇿',n:'New Zealand',d:'+64'},
  {i:'NG',f:'🇳🇬',n:'Nigeria',d:'+234'},{i:'NO',f:'🇳🇴',n:'Norway',d:'+47'},{i:'PK',f:'🇵🇰',n:'Pakistan',d:'+92'},
  {i:'PE',f:'🇵🇪',n:'Peru',d:'+51'},{i:'PH',f:'🇵🇭',n:'Philippines',d:'+63'},{i:'PL',f:'🇵🇱',n:'Poland',d:'+48'},
  {i:'PT',f:'🇵🇹',n:'Portugal',d:'+351'},{i:'QA',f:'🇶🇦',n:'Qatar',d:'+974'},{i:'RO',f:'🇷🇴',n:'Romania',d:'+40'},
  {i:'RU',f:'🇷🇺',n:'Russia',d:'+7'},{i:'SA',f:'🇸🇦',n:'Saudi Arabia',d:'+966'},{i:'SN',f:'🇸🇳',n:'Senegal',d:'+221'},
  {i:'SG',f:'🇸🇬',n:'Singapore',d:'+65'},{i:'ZA',f:'🇿🇦',n:'South Africa',d:'+27'},{i:'KR',f:'🇰🇷',n:'South Korea',d:'+82'},
  {i:'ES',f:'🇪🇸',n:'Spain',d:'+34'},{i:'LK',f:'🇱🇰',n:'Sri Lanka',d:'+94'},{i:'SE',f:'🇸🇪',n:'Sweden',d:'+46'},
  {i:'CH',f:'🇨🇭',n:'Switzerland',d:'+41'},{i:'TW',f:'🇹🇼',n:'Taiwan',d:'+886'},{i:'TZ',f:'🇹🇿',n:'Tanzania',d:'+255'},
  {i:'TH',f:'🇹🇭',n:'Thailand',d:'+66'},{i:'TN',f:'🇹🇳',n:'Tunisia',d:'+216'},{i:'TR',f:'🇹🇷',n:'Turkey',d:'+90'},
  {i:'UG',f:'🇺🇬',n:'Uganda',d:'+256'},{i:'UA',f:'🇺🇦',n:'Ukraine',d:'+380'},{i:'AE',f:'🇦🇪',n:'United Arab Emirates',d:'+971'},
  {i:'GB',f:'🇬🇧',n:'United Kingdom',d:'+44'},{i:'US',f:'🇺🇸',n:'United States',d:'+1'},{i:'UY',f:'🇺🇾',n:'Uruguay',d:'+598'},
  {i:'VE',f:'🇻🇪',n:'Venezuela',d:'+58'},{i:'VN',f:'🇻🇳',n:'Vietnam',d:'+84'},{i:'YE',f:'🇾🇪',n:'Yemen',d:'+967'},
  {i:'ZM',f:'🇿🇲',n:'Zambia',d:'+260'},{i:'ZW',f:'🇿🇼',n:'Zimbabwe',d:'+263'}
];

const DM={};
COUNTRIES.forEach(c=>{const k=c.d.replace(/-\d+$/,'');if(!DM[k])DM[k]=c;});
let SC=COUNTRIES.find(c=>c.i==='US');

function renderList(list){
  document.getElementById('cd-list').innerHTML=list.map(c=>`
    <div class="cd-item${c.i===SC.i?' sel':''}" onclick="selC('${c.i}')">
      <span>${c.f}</span><span class="cd-item-name">${c.n}</span><span class="cd-item-dial">${c.d}</span>
    </div>`).join('');
}

function filterC(q){
  const lq=q.toLowerCase();
  renderList(q?COUNTRIES.filter(c=>c.n.toLowerCase().includes(lq)||c.d.includes(q)||c.i.toLowerCase().includes(lq)):COUNTRIES);
}

function selC(iso){
  SC=COUNTRIES.find(c=>c.i===iso);
  document.getElementById('sel-flag').textContent=SC.f;
  document.getElementById('sel-dial').textContent=SC.d.replace(/-\d+$/,'');
  document.getElementById('cc-val').value=SC.d;
  document.getElementById('iso-val').value=SC.i;
  closeDD();
}

function toggleDD(){
  const dd=document.getElementById('cd'),tr=document.getElementById('ct');
  if(dd.classList.contains('open')){closeDD();}
  else{
    dd.classList.add('open');tr.classList.add('open');
    renderList(COUNTRIES);
    document.getElementById('cd-search-inp').value='';
    setTimeout(()=>document.getElementById('cd-search-inp').focus(),50);
  }
}

function closeDD(){
  document.getElementById('cd').classList.remove('open');
  document.getElementById('ct').classList.remove('open');
}

function ctKey(e){
  if(e.key==='Enter'||e.key===' '){e.preventDefault();toggleDD();}
  if(e.key==='Escape')closeDD();
}

document.addEventListener('click',e=>{
  const w=document.querySelector('.country-wrap');
  if(w&&!w.contains(e.target))closeDD();
});

function autoDetect(v){
  const c=v.replace(/[^\d+]/g,'');
  if(!c.startsWith('+')){document.getElementById('det').classList.remove('show');return;}
  let found=null;
  for(let l=5;l>=1;l--){const p=c.substring(0,l+1);if(DM[p]){found=DM[p];break;}}
  if(found&&found.i!==SC.i){
    selC(found.i);
    document.getElementById('det-txt').textContent='Detected: '+found.f+' '+found.n;
    document.getElementById('det').classList.add('show');
  }else{document.getElementById('det').classList.remove('show');}
}

function switchTab(id,btn){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tp-pane').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('pane-'+id).classList.add('active');
}

function togglePw(id,btn){
  const inp=document.getElementById(id);
  const icon=btn.querySelector('i');
  inp.type=inp.type==='password'?'text':'password';
  icon.className=inp.type==='password'?'fa-regular fa-eye':'fa-regular fa-eye-slash';
}

function showAlert(msg,type){
  const b=document.getElementById('alert-box');
  b.innerHTML=`<i class="fa-solid fa-${type==='error'?'circle-exclamation':'circle-check'}"></i> ${msg}`;
  b.className='alert '+type+' show';
  b.scrollIntoView({behavior:'smooth',block:'nearest'});
  if(type==='success')return;
  setTimeout(()=>b.classList.remove('show'),5000);
}

async function handleReg(e,m){
  e.preventDefault();
  const form=document.getElementById('form-'+m);
  const spin=document.getElementById('spin-'+m);
  const sub=document.getElementById('sub-'+m);
  const pw=form.querySelector('[name="password"]').value;
  const cpw=form.querySelector('[name="confirm_password"]').value;
  if(pw!==cpw){showAlert('Passwords do not match.','error');return;}
  if(pw.length<6){showAlert('Password must be at least 6 characters.','error');return;}
  let payload={registration_method:m};
  if(m==='email'){
    payload.email=form.querySelector('[name="email"]').value.trim();
    payload.password=pw;payload.confirm_password=cpw;
    payload.invitation_code=form.querySelector('[name="invitation_code"]').value.trim();
  }else{
    const dc=document.getElementById('cc-val').value.replace(/-\d+$/,'');
    const num=document.getElementById('ph-num').value.trim().replace(/^0+/,'').replace(/[^\d]/g,'');
    payload.phone=dc+num;payload.password=pw;payload.confirm_password=cpw;
    payload.invitation_code=form.querySelector('[name="invitation_code"]').value.trim();
    payload.country_iso=document.getElementById('iso-val').value;
  }
  form.style.display='none';spin.classList.add('show');sub.disabled=true;
  try{
    const r=await fetch('../api/register_user.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const d=await r.json();
    if(d.success){showAlert(d.message||'Account created! Redirecting…','success');setTimeout(()=>{window.location.href='login.php';},2200);}
    else throw new Error(d.message||'Registration failed.');
  }catch(err){
    showAlert(err.message,'error');
    form.style.display='block';spin.classList.remove('show');sub.disabled=false;
  }
}

renderList(COUNTRIES);
</script>
</body>
</html>
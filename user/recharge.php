<?php
// user/recharge.php - Deposit/Recharge page
require_once __DIR__ . '/../config.php';
require_login();

$user = get_logged_in_user();
$page_title = t('recharge', 'Recharge');
$api_token = $user['auth_token'];
$depositAssets = $pdo->query("SELECT canonical_code,symbol,asset_name,network_name FROM deposit_asset_catalog WHERE provider_enabled=1 AND deposits_enabled=1 ORDER BY sort_priority,symbol,network_name")->fetchAll(PDO::FETCH_ASSOC);
$coinIcons = [
    'USDT'=>'tether-usdt','BTC'=>'bitcoin-btc','ETH'=>'ethereum-eth','BNB'=>'bnb-bnb',
    'TRX'=>'tron-trx','LTC'=>'litecoin-ltc','DOGE'=>'dogecoin-doge','USDC'=>'usd-coin-usdc',
    'XRP'=>'xrp-xrp','TON'=>'toncoin-ton','SOL'=>'solana-sol','DGB'=>'digibyte-dgb',
    'BCH'=>'bitcoin-cash-bch','DAI'=>'multi-collateral-dai-dai','DOGS'=>'dogs-dogs',
    'GRAM'=>'toncoin-ton','NOT'=>'notcoin-not','POL'=>'polygon-matic',
    'SHIB'=>'shiba-inu-shib','XMR'=>'monero-xmr'
];
?>
<?php
$spin_chances = (int)($user['spin_chances'] ?? 0);
include __DIR__ . '/spin_wheel.php';
?>
<!DOCTYPE html>
<html lang="<?php echo get_language(); ?>">
<head>
<style>html.fonts-loading body{visibility:hidden}</style><script>document.documentElement.classList.add('fonts-loading');(function(){var r=function(){document.documentElement.classList.remove('fonts-loading')};if(document.fonts&&document.fonts.ready){Promise.race([document.fonts.ready,new Promise(function(x){setTimeout(x,1800)})]).then(r,r)}else{window.addEventListener('load',r,{once:true})}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - <?php echo PLATFORM_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700;800&family=Barlow+Condensed:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --gp:#1a7a1a; --gb:#22a322; --gl:#2db82d;
            --green:#1a7a1a; --green-light:#22a022;
            --green-dim:rgba(26,122,26,0.15); --green-glow:rgba(26,122,26,0.4);
            --surface:#161d17; --surface-2:#1c261d; --surface-3:#223024;
            --border:rgba(26,122,26,0.2);
            --text:#e8f0e8; --text-muted:#6b8a6d; --text-dim:#4a634c;
            --white:#fff;
            --nav-h:64px;
            --tl:#8aaa8a;
        }
        *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
        html { -webkit-text-size-adjust:100%; }
        body {
            font-family:'Barlow',sans-serif;
            background:#f0f5f0;
            min-height:100vh;
            padding-bottom:calc(var(--nav-h) + 24px);
            color:var(--text);
            overflow-x:hidden;
            -webkit-font-smoothing:antialiased;
        }

        /* ── HEADER ── */
        .header {
            position:relative;
            background:var(--gp);
            padding:18px 20px;
            display:flex; align-items:center; gap:14px;
            z-index:10; overflow:hidden;
            box-shadow:0 2px 12px rgba(0,0,0,.15);
        }
        .header::before {
            content:''; position:absolute;
            right:-40px; top:-40px;
            width:140px; height:140px;
            border-radius:50%;
            background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);
            pointer-events:none;
        }
        .back-btn {
            width:36px; height:36px;
            background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.2);
            border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; color:#fff; font-size:16px;
            transition:all .2s; flex-shrink:0; text-decoration:none;
        }
        .back-btn:hover { background:rgba(255,255,255,.25); }
        .header-title {
            font-family:'Barlow Condensed',sans-serif;
            font-size:20px; font-weight:800;
            color:#fff; letter-spacing:.05em; flex:1;
        }
        .header-tag {
            font-size:10px; font-weight:800;
            letter-spacing:.12em; text-transform:uppercase;
            color:#f5d060; background:rgba(245,208,96,.15);
            border:1px solid rgba(245,208,96,.3);
            padding:4px 10px; border-radius:20px;
            display:flex; align-items:center; gap:5px;
        }

        /* ── NETWORK SELECTOR ── */
        .section {
            margin:16px 16px 0;
            background:#fff;
            border:1px solid #d4ebd4;
            border-radius:16px; padding:18px;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        .section-label {
            font-size:10px; font-weight:800;
            letter-spacing:.1em; text-transform:uppercase;
            color:#8aaa8a; margin-bottom:14px;
            display:flex; align-items:center; gap:8px;
        }
        .section-label i { color:var(--gp); font-size:12px; }
        .network-grid {
            display:grid; grid-template-columns:repeat(2,1fr); gap:8px;
        }
        .legacy-network-grid { display:none; }
        .network-btn {
            padding:12px 22px;
            background:#f7fbf7;
            border:1px solid #d4ebd4;
            border-radius:10px;
            color:#5a7a5a; cursor:pointer;
            font-family:'Barlow',sans-serif;
            font-size:12px; font-weight:700;
            letter-spacing:.03em; transition:all .2s;
            text-align:left;
            display:flex; align-items:center; justify-content:flex-start; gap:10px;
        }
        .network-btn:hover { border-color:#b8d8b8; color:#1a7a1a; background:#edf7ed; }
        .network-btn.active {
            background:rgba(26,122,26,.1);
            border-color:var(--green);
            color:var(--green);
        }
        .network-btn i { font-size:13px; }
        .coin-icon {
            width:24px; height:24px; flex:0 0 24px; object-fit:contain;
            filter:drop-shadow(0 1px 2px rgba(0,0,0,.14));
        }
        @media (max-width:700px) {
            .network-btn { padding:10px 10px; gap:8px; }
            .coin-icon { width:22px; height:22px; flex-basis:22px; }
        }
        .deposit-backdrop {
            position:fixed; inset:0; z-index:450; background:rgba(7,16,7,.48);
            opacity:0; visibility:hidden; transition:opacity .25s ease,visibility .25s ease;
        }
        .deposit-backdrop.active { opacity:1; visibility:visible; }

        /* ── LOADING ── */
        .loading {
            margin:16px 16px 0;
            background:#fff; border:1px solid #d4ebd4;
            border-radius:16px; padding:48px 20px;
            text-align:center;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        .spinner {
            width:36px; height:36px;
            border:3px solid #d4ebd4;
            border-top:3px solid var(--green);
            border-radius:50%;
            animation:spin .7s linear infinite;
            margin:0 auto 14px;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        .loading-text { font-size:13px; color:#8aaa8a; font-weight:600; }

        /* ── QR SECTION ── */
        .qr-section {
            margin:16px 16px 0;
            background:#fff; border:1px solid #d4ebd4;
            border-radius:16px; padding:28px 20px 20px;
            text-align:center;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        .qr-label {
            font-size:10px; font-weight:800;
            letter-spacing:.1em; text-transform:uppercase;
            color:#8aaa8a; margin-bottom:18px;
            display:flex; align-items:center; justify-content:center; gap:6px;
        }
        .qr-label i { color:var(--gp); }
        #qrcode {
            display:inline-block;
            padding:12px; background:#fff;
            border:1px solid #d4ebd4;
            border-radius:12px;
        }

        /* ── ADDRESS SECTION ── */
        .address-section {
            margin:0 16px;
            background:#fff; border:1px solid #d4ebd4;
            border-top:none; border-radius:0 0 16px 16px;
            padding:0 18px 20px;
            box-shadow:0 4px 10px rgba(0,0,0,.06);
        }
        .divider { height:1px; background:#d4ebd4; margin-bottom:16px; }
        .address-label {
            font-size:10px; font-weight:800;
            letter-spacing:.1em; text-transform:uppercase;
            color:#8aaa8a; margin-bottom:8px;
            display:flex; align-items:center; gap:6px;
        }
        .address-label i { color:var(--gp); font-size:12px; }
        .address-text {
            font-family:'Barlow Condensed',sans-serif;
            font-size:13px; font-weight:600;
            color:#2d4a2d; word-break:break-all;
            background:#f7fbf7; border:1px solid #d4ebd4;
            border-radius:10px; padding:12px 14px;
            margin-bottom:12px; letter-spacing:.03em;
            line-height:1.5;
        }
        .memo-section { display:none; margin-bottom:12px; }
        .copy-btn {
            width:100%; padding:14px;
            background:var(--green); color:#fff;
            border:none; border-radius:10px;
            font-family:'Barlow',sans-serif;
            font-size:13px; font-weight:800;
            letter-spacing:.08em; text-transform:uppercase;
            cursor:pointer; transition:all .2s;
            display:flex; align-items:center; justify-content:center; gap:8px;
            box-shadow:0 3px 12px rgba(26,122,26,.3);
        }
        .copy-btn:hover { background:var(--green-light); transform:translateY(-1px); }
        .copy-btn:active { transform:none; }
        .copy-btn.copied { background:#0f5c0f; }

        /* ── INSTRUCTIONS ── */
        .instructions {
            margin:12px 16px 0;
            background:#fff; border:1px solid #d4ebd4;
            border-left:3px solid var(--green);
            border-radius:0 12px 12px 0;
            padding:16px 18px;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        .instructions-title {
            font-size:11px; font-weight:800;
            letter-spacing:.1em; text-transform:uppercase;
            color:var(--green); margin-bottom:12px;
            display:flex; align-items:center; gap:7px;
        }
        .instruction-item {
            display:flex; gap:10px;
            margin-bottom:9px; font-size:12px;
            color:#5a7a5a; line-height:1.55;
            align-items:flex-start;
        }
        .instruction-item:last-child { margin-bottom:0; }
        .instruction-item i {
            color:var(--green); font-size:11px;
            margin-top:2px; flex-shrink:0; width:14px;
        }

        /* ── DEPOSIT CONTAINER ── */
        .deposit-container {
            display:block; position:fixed; z-index:500; left:50%; bottom:0;
            width:100%; max-width:520px; max-height:calc(100dvh - 72px); overflow-y:auto;
            padding:0 0 calc(18px + env(safe-area-inset-bottom)); background:#f0f5f0;
            border-radius:20px 20px 0 0; box-shadow:0 -14px 45px rgba(0,0,0,.24);
            transform:translate(-50%,105%); transition:transform .3s cubic-bezier(.22,.75,.25,1);
        }
        .deposit-container.active { transform:translate(-50%,0); }
        .sheet-head { position:sticky;top:0;z-index:2;height:28px;background:#f0f5f0;display:flex;align-items:center;justify-content:center;cursor:pointer; }
        .sheet-handle { width:42px;height:4px;border-radius:4px;background:#b8c9b8; }
        .deposit-info { display:none; }

        /* ── TOAST ── */
        .rc-toast {
            position:fixed; top:50%; left:50%;
            transform:translate(-50%,-50%) scale(.9);
            z-index:9999;
            background:rgba(0,0,0,.78);
            color:#fff; padding:16px 24px;
            border-radius:14px;
            font-size:14px; font-weight:700;
            text-align:center; line-height:1.5;
            display:flex; flex-direction:column;
            align-items:center; gap:10px;
            max-width:280px; width:90%;
            opacity:0; pointer-events:none;
            transition:all .25s cubic-bezier(.34,1.56,.64,1);
            box-shadow:0 8px 32px rgba(0,0,0,.35);
        }
        .rc-toast.show {
            opacity:1; transform:translate(-50%,-50%) scale(1);
            pointer-events:auto;
        }
        .rc-toast i { font-size:26px; }
        .rc-toast.success i { color:#7be87b; }
        .rc-toast.error   i { color:#e74c3c; }
        .rc-toast.info    i { color:#f5d060; }
        .rc-toast-msg { font-size:13px; color:rgba(255,255,255,.85); font-weight:600; }

        /* ── BOTTOM NAV ── */
        .bnav {
            position:fixed; bottom:0; left:0; right:0; z-index:300;
            height:var(--nav-h); background:#fff;
            border-top:1px solid #d4ebd4;
            display:flex; align-items:stretch;
            box-shadow:0 -2px 16px rgba(0,0,0,.07);
        }
        .nav-item {
            flex:1; display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            gap:4px; padding:0 4px; text-decoration:none;
            color:var(--tl); font-size:9px; font-weight:800;
            text-transform:uppercase; letter-spacing:.06em;
            transition:all .18s; position:relative; cursor:pointer;
        }
        .nav-item::before {
            content:''; position:absolute; top:0; left:25%; right:25%;
            height:2px; border-radius:0 0 3px 3px;
            background:var(--gp); transform:scaleX(0);
            transform-origin:center; transition:transform .2s;
        }
        .nav-item.active { color:var(--gp); }
        .nav-item.active::before { transform:scaleX(1); }
        .ni { font-size:20px; transition:transform .18s; }
        .nav-item.active .ni { transform:scale(1.1); }
        .foot { padding-bottom:100px; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <a class="back-btn" onclick="window.history.back()" href="#">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <div class="header-title"><?php echo t('recharge','Recharge'); ?></div>
</div>

<!-- Network Selector -->
<div class="section">
    <div class="section-label"><i class="fa-solid fa-network-wired"></i><?php echo t('select_network','Select Network'); ?></div>
    <div class="network-grid legacy-network-grid">
        <button class="network-btn" onclick="selectNetwork('TRON',this)">
            <i class="fa-solid fa-circle-nodes"></i> TRC20 · USDT
        </button>
        <button class="network-btn" onclick="selectNetwork('BSC',this)">
            <i class="fa-solid fa-circle-nodes"></i> BEP20 · USDT
        </button>
        <button class="network-btn" onclick="selectNetwork('ETH',this)">
            <i class="fa-brands fa-ethereum"></i> ERC20 · USDT
        </button>
        <button class="network-btn" onclick="selectNetwork('POLYGON',this)">
            <i class="fa-solid fa-circle-nodes"></i> POLYGON · USDT
        </button>
        <button class="network-btn" onclick="selectNetwork('TON',this)">
            <i class="fa-solid fa-gem"></i> TON &middot; USDT
        </button>
    </div>
    <div class="network-grid" id="assetNetworkGrid">
        <?php foreach ($depositAssets as $asset): ?>
        <button class="network-btn" onclick="selectAsset(<?php echo htmlspecialchars(json_encode($asset['canonical_code']), ENT_QUOTES, 'UTF-8'); ?>,this)">
            <?php $iconSlug = $coinIcons[$asset['symbol']] ?? strtolower($asset['symbol']) . '-' . strtolower($asset['symbol']); ?>
            <img class="coin-icon" src="<?php echo $asset['symbol'] === 'NOT' ? '/user/images/coins/not.png?v=20260812' : 'https://cryptologos.cc/logos/' . htmlspecialchars($iconSlug) . '-logo.png?v=040'; ?>" alt="<?php echo htmlspecialchars($asset['symbol']); ?> logo" loading="lazy" onerror="this.onerror=null;this.src='/user/images/tree.jpg?v=20260812'">
            <?php echo htmlspecialchars($asset['symbol'] . ' · ' . $asset['network_name']); ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Deposit Container -->
<div id="depositBackdrop" class="deposit-backdrop" onclick="closeDepositSheet()"></div>
<div id="depositContainer" class="deposit-container">
    <div class="sheet-head" onclick="closeDepositSheet()"><div class="sheet-handle"></div></div>

    <div id="loading" class="loading">
        <div class="spinner"></div>
        <div class="loading-text">Generating deposit address…</div>
    </div>

    <div id="depositInfo" class="deposit-info">
        <!-- QR Code -->
        <div class="qr-section">
            <div class="qr-label"><i class="fa-solid fa-qrcode"></i>Scan to Deposit</div>
            <div id="qrcode"></div>
        </div>

        <!-- Address -->
        <div class="address-section">
            <div class="divider"></div>
            <div class="address-label"><i class="fa-solid fa-wallet"></i><?php echo t('address','Wallet Address'); ?></div>
            <div class="address-text" id="depositAddress">—</div>
            <div class="memo-section" id="memoSection">
                <div class="address-label"><i class="fa-solid fa-tag"></i>Memo / Tag</div>
                <div class="address-text" id="depositMemo"></div>
            </div>
            <button class="copy-btn" id="copyBtn" onclick="copyAddress()">
                <i class="fa-regular fa-copy"></i><?php echo t('copy','Copy Address'); ?>
            </button>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <div class="instructions-title">
                <i class="fa-solid fa-circle-info"></i>
                <?php echo t('recharge_instructions','Deposit Instructions'); ?>
            </div>
            <div class="instruction-item">
                <i class="fa-solid fa-qrcode"></i>
                <div><?php echo t('scan_code_or_copy','Scan the QR code or copy the address to complete the deposit.'); ?></div>
            </div>
            <div class="instruction-item">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>Only send the selected coin through its displayed network. A different coin or network may be lost permanently.</div>
            </div>
            <div class="instruction-item">
                <i class="fa-solid fa-download"></i>
                <div><?php echo t('minimum_deposit','The converted amount must be at least 2 USDT. Smaller amounts will not be credited.'); ?></div>
            </div>
            <div class="instruction-item">
                <i class="fa-solid fa-bolt"></i>
                <div><?php echo t('auto_upgrade','System will automatically match the product level and complete the upgrade.'); ?></div>
            </div>
        </div>
    </div>

</div>

<!-- Bottom Nav -->
<div class="bnav">
    <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house ni"></i><span>Home</span></a>
    <a href="tasks.php" class="nav-item"><i class="fa-solid fa-list-check ni"></i><span>Tasks</span></a>
    <a href="team.php" class="nav-item"><i class="fa-solid fa-users ni"></i><span>Team</span></a>
    <a href="vip.php" class="nav-item"><i class="fa-solid fa-crown ni"></i><span>VIP</span></a>
    <a href="profile.php" class="nav-item"><i class="fa-regular fa-circle-user ni"></i><span>Me</span></a>
</div>

<!-- Toast -->
<div class="rc-toast" id="rcToast">
    <i id="rcToastIcon" class="fa-solid fa-circle-check"></i>
    <div id="rcToastTitle"></div>
    <div class="rc-toast-msg" id="rcToastMsg"></div>
</div>

<div class="foot"></div>

<script>
    const authToken = <?php echo json_encode($api_token); ?>;
    let currentAddress   = null;
    let currentAddressId = null;
    let pollingInterval  = null;
    let toastTimer       = null;

    /* ── Toast ── */
    function rcToast(title, msg, type, duration) {
        duration = duration || 3000;
        var el    = document.getElementById('rcToast');
        var ico   = document.getElementById('rcToastIcon');
        var ttl   = document.getElementById('rcToastTitle');
        var mtxt  = document.getElementById('rcToastMsg');

        var icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info', loading:'fa-spinner fa-spin' };
        ico.className  = 'fa-solid ' + (icons[type] || icons.info);
        el.className   = 'rc-toast ' + (type || 'info');
        ttl.textContent = title || '';
        mtxt.textContent = msg || '';
        el.classList.add('show');
        clearTimeout(toastTimer);
        if (duration > 0) {
            toastTimer = setTimeout(function () { el.classList.remove('show'); }, duration);
        }
    }
    function rcToastHide() {
        clearTimeout(toastTimer);
        document.getElementById('rcToast').classList.remove('show');
    }

    /* ── Network select ── */
    function selectNetwork(network, btn) {
        selectAsset(network, btn);
    }

    function selectAsset(assetCode, btn) {
        document.querySelectorAll('.network-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('depositBackdrop').classList.add('active');
        document.getElementById('depositContainer').classList.add('active');
        document.body.style.overflow = 'hidden';
        getDepositAddress(assetCode);
    }

    function closeDepositSheet() {
        document.getElementById('depositBackdrop').classList.remove('active');
        document.getElementById('depositContainer').classList.remove('active');
        document.body.style.overflow = '';
        document.querySelectorAll('.network-btn').forEach(function(b){ b.classList.remove('active'); });
        if (pollingInterval) clearInterval(pollingInterval);
    }

    /* ── Get deposit address ── */
    async function getDepositAddress(assetCode) {
        document.getElementById('loading').style.display = 'block';
        document.getElementById('depositInfo').style.display = 'none';
        if (pollingInterval) clearInterval(pollingInterval);

        try {
            const response = await fetch('../api/get_deposit_address.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-User-Token': authToken },
                body: JSON.stringify({ asset_code: assetCode })
            });
            const data = await response.json();
            if (data.success && data.data.address) {
                currentAddress   = data.data.address;
                currentAddressId = data.data.address_id;
                document.getElementById('depositAddress').textContent = currentAddress;
                var memo = data.data.memo || '';
                document.getElementById('depositMemo').textContent = memo;
                document.getElementById('memoSection').style.display = memo ? 'block' : 'none';
                const qrContainer = document.getElementById('qrcode');
                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: currentAddress, width: 180, height: 180,
                    colorDark: '#000000', colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
                document.getElementById('loading').style.display = 'none';
                document.getElementById('depositInfo').style.display = 'block';
                startDepositPolling();
            } else {
                throw new Error(data.message || 'Failed to get deposit address');
            }
        } catch(error) {
            document.getElementById('loading').style.display = 'none';
            rcToast('Error', error.message, 'error', 5000);
        }
    }

    /* ── Copy address ── */
    function copyAddress() {
        if (!currentAddress) return;
        var btn = document.getElementById('copyBtn');
        var promise = navigator.clipboard && navigator.clipboard.writeText
            ? navigator.clipboard.writeText(currentAddress)
            : Promise.resolve().then(function () {
                var inp = document.createElement('input');
                inp.value = currentAddress;
                Object.assign(inp.style, { position: 'fixed', opacity: '0' });
                document.body.appendChild(inp); inp.select();
                document.execCommand('copy'); document.body.removeChild(inp);
            });
        promise.then(function () {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
            rcToast('Copied!', 'Wallet address copied to clipboard.', 'success', 2500);
            setTimeout(function () {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy Address';
            }, 2500);
        }).catch(function () {
            rcToast('Error', 'Could not copy. Please copy manually.', 'error', 3000);
        });
    }

    /* ── Deposit polling ── */
    function startDepositPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(async function () {
            if (!currentAddressId) return;
            try {
                const response = await fetch('../api/check_new_deposits.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-User-Token': authToken },
                    body: JSON.stringify({ address_id: currentAddressId })
                });
                const data = await response.json();
                if (data.success && data.data.new_deposits > 0) {
                    clearInterval(pollingInterval);
                    rcToast(
                        'Recharge Received!',
                        data.data.total_amount + ' USDT has been credited to your account.',
                        'success',
                        0   /* keep open until redirect */
                    );
                    setTimeout(function () { window.location.href = 'dashboard.php'; }, 2800);
                }
            } catch(e) { console.error(e); }
        }, 5000);
    }

    /* ── Init ── */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeDepositSheet();
    });
    window.addEventListener('beforeunload', function () {
        if (pollingInterval) clearInterval(pollingInterval);
    });
</script>
</body>
</html>

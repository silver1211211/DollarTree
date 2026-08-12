<?php
require_once __DIR__ . '/../config.php';
$back = is_logged_in() ? 'profile.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Terms of Service — DollarTree</title>
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--green:#1a7a1a;--light:#f4faf4;--dark:#071007;--text:#132013;--muted:#688068;--border:#d8e9d8;--white:#fff}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Barlow,sans-serif;background:var(--light);color:var(--text);line-height:1.65;min-height:100vh}
.top{background:var(--dark);color:#fff;padding:18px 20px}.top-inner{max-width:760px;margin:auto;display:flex;align-items:center;gap:14px}.back{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.08);color:#fff;display:grid;place-items:center;text-decoration:none}.top h1{font:800 24px 'Barlow Condensed',sans-serif}.wrap{max-width:760px;margin:28px auto;padding:0 18px 40px}.intro,.card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:14px}.intro{border-left:4px solid var(--green)}.updated{font-size:12px;color:var(--muted);margin-top:5px}.card h2{font:800 20px 'Barlow Condensed',sans-serif;color:var(--green);margin-bottom:7px}.card p,.card li{font-size:14px;color:#405540}.card ul{padding-left:20px;margin-top:7px}.card li{margin:5px 0}.links a{color:var(--green);font-weight:700}.foot{text-align:center;color:var(--muted);font-size:12px;padding:6px 0 24px}@media(max-width:600px){.wrap{margin-top:18px}.intro,.card{padding:18px}.top h1{font-size:22px}}
</style>
</head>
<body>
<header class="top"><div class="top-inner"><a class="back" href="<?php echo $back; ?>" aria-label="Go back"><i class="fa-solid fa-chevron-left"></i></a><h1>Terms of Service</h1></div></header>
<main class="wrap">
  <section class="intro"><strong>Please read these terms before using DollarTree.</strong><p>By creating an account or using the platform, you agree to these Terms of Service and our Privacy Policy.</p><div class="updated">Last updated: August 12, 2026</div></section>
  <section class="card"><h2>Account eligibility and security</h2><ul><li>You must be legally permitted to use the platform in your location and provide accurate registration information.</li><li>You are responsible for your password, account activity and the security of your devices.</li><li>One person may not create accounts to abuse tasks, referrals, promotions or platform rewards.</li></ul></section>
  <section class="card"><h2>Platform services</h2><p>DollarTree provides account, SVIP tier, task, referral, deposit and withdrawal features. Available features, limits, processing times and eligibility requirements may vary by account and may be updated when reasonably necessary.</p></section>
  <section class="card"><h2>Deposits and withdrawals</h2><ul><li>You are responsible for checking the correct asset, blockchain network, wallet address and amount before submitting a transaction.</li><li>Blockchain transactions may be irreversible and may be delayed by networks or third-party providers.</li><li>Withdrawals may be reviewed for account security, fraud prevention and compliance before completion.</li></ul></section>
  <section class="card"><h2>SVIP, tasks and referrals</h2><p>SVIP access, task rewards and referral commissions are governed by the amounts, limits and conditions displayed in the platform at the time of use. Rewards may be withheld or reversed where activity is duplicated, manipulated, fraudulent or otherwise violates these terms.</p></section>
  <section class="card"><h2>Prohibited use</h2><p>You may not use the platform for unlawful activity, impersonation, fraud, money laundering, unauthorized access, automated abuse, interference with the service, or attempts to exploit errors. Accounts involved in prohibited activity may be restricted or suspended.</p></section>
  <section class="card"><h2>Risk and availability</h2><p>Digital assets and online financial services involve risk. Values, networks and processing times may change. You are responsible for your decisions and should not use funds you cannot afford to lose. The service may occasionally be unavailable for maintenance, security or circumstances outside our control.</p></section>
  <section class="card"><h2>Changes and termination</h2><p>We may update these terms and will publish the revised date on this page. You may stop using the service at any time. We may restrict access when needed to protect users, enforce these terms or comply with legal requirements.</p></section>
  <section class="card links"><h2>Privacy and support</h2><p>Read our <a href="privacy.php">Privacy Policy</a> for information about personal data. For questions about these terms, contact the official support channel shown inside your account.</p></section>
  <div class="foot">&copy; <?php echo date('Y'); ?> DollarTree. All rights reserved.</div>
</main>
</body>
</html>

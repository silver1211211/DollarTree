<?php
require_once __DIR__ . '/../config.php';
$back = is_logged_in() ? 'profile.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Privacy Policy — DollarTree</title>
<link rel="icon" type="image/jpeg" href="images/tree.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--green:#1a7a1a;--light:#f4faf4;--dark:#071007;--text:#132013;--muted:#688068;--border:#d8e9d8;--white:#fff}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Barlow,sans-serif;background:var(--light);color:var(--text);line-height:1.65;min-height:100vh}
.top{background:var(--dark);color:#fff;padding:18px 20px}.top-inner{max-width:760px;margin:auto;display:flex;align-items:center;gap:14px}.back{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.08);color:#fff;display:grid;place-items:center;text-decoration:none}.top h1{font:800 24px 'Barlow Condensed',sans-serif}.wrap{max-width:760px;margin:28px auto;padding:0 18px 40px}.intro,.card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:14px}.intro{border-left:4px solid var(--green)}.updated{font-size:12px;color:var(--muted);margin-top:5px}.card h2{font:800 20px 'Barlow Condensed',sans-serif;color:var(--green);margin-bottom:7px}.card p,.card li{font-size:14px;color:#405540}.card ul{padding-left:20px;margin-top:7px}.card li{margin:5px 0}.contact a{color:var(--green);font-weight:700}.foot{text-align:center;color:var(--muted);font-size:12px;padding:6px 0 24px}@media(max-width:600px){.wrap{margin-top:18px}.intro,.card{padding:18px}.top h1{font-size:22px}}
</style>
</head>
<body>
<header class="top"><div class="top-inner"><a class="back" href="<?php echo $back; ?>" aria-label="Go back"><i class="fa-solid fa-chevron-left"></i></a><h1>Privacy Policy</h1></div></header>
<main class="wrap">
  <section class="intro"><strong>Your privacy matters to us.</strong><p>This policy explains the basic information DollarTree collects and how it is used when you access the platform.</p><div class="updated">Last updated: August 12, 2026</div></section>
  <section class="card"><h2>Information we collect</h2><ul><li>Account details such as your username, email address, phone number and encrypted password.</li><li>Transaction information including deposits, withdrawals, balances, SVIP activity, task earnings and referral records.</li><li>Technical information such as IP address, login time, device or browser details, session data and activity logs.</li><li>Messages or information you send to platform support.</li></ul></section>
  <section class="card"><h2>How we use information</h2><p>We use this information to create and secure accounts, provide platform features, process and record transactions, prevent fraud, provide support, improve the service and meet applicable legal obligations.</p></section>
  <section class="card"><h2>Sharing and service providers</h2><p>We do not sell your personal information. Information may be shared only with service providers needed to operate the platform, such as payment or blockchain-processing providers, hosting and security services, or when disclosure is required by law.</p></section>
  <section class="card"><h2>Cookies and security</h2><p>The platform uses essential cookies and sessions to keep you signed in and protect your account. We apply reasonable security measures, but no online system can guarantee complete security. Keep your password private and sign out on shared devices.</p></section>
  <section class="card"><h2>Your choices</h2><p>You may request access to or correction of your account information. You may also request account closure or deletion where permitted, subject to records we must retain for security, transaction or legal purposes.</p></section>
  <section class="card contact"><h2>Contact us</h2><p>For privacy questions or requests, contact platform support through the official support channel shown inside your account. Your use of the platform is also governed by our <a href="terms.php">Terms of Service</a>.</p></section>
  <div class="foot">&copy; <?php echo date('Y'); ?> DollarTree. All rights reserved.</div>
</main>
</body>
</html>

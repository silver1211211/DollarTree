<?php
require_once __DIR__ . '/../config.php';
if(isset($_SESSION['admin_id'])&&$_SESSION['is_admin']){header('Location: dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  http_response_code(403);
  $error='Admin operations are currently unavailable.';
} elseif (false) {
  $username=$_POST['username']??''; $password=$_POST['password']??'';
  global $pdo;
  $stmt=$pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND is_admin=1 LIMIT 1");
  $stmt->execute([$username,$username]);
  $admin=$stmt->fetch(PDO::FETCH_ASSOC);
  if($admin&&verify_password($password,$admin['password_hash'])){
    $_SESSION['admin_id']=$admin['id'];$_SESSION['is_admin']=true;$_SESSION['admin_username']=$admin['username'];
    log_activity($admin['id'],'admin_login','Admin logged in from '.$_SERVER['REMOTE_ADDR']);
    header('Location: dashboard.php');exit;
  }else{$error='Invalid credentials';app_log("Admin login failed: $username",'WARNING');}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — DollarTree</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&family=Barlow+Condensed:wght@800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Barlow',sans-serif;background:#090d09;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;-webkit-font-smoothing:antialiased;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(26,122,26,.18) 0%,transparent 70%);pointer-events:none;}
.box{background:#0f150f;border:1px solid #1a261a;border-radius:16px;width:100%;max-width:380px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.7);}
.box-top{padding:32px 28px 24px;text-align:center;border-bottom:1px solid #1a261a;position:relative;}
.box-top::before{content:'';position:absolute;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 31px,rgba(26,122,26,.05) 31px,rgba(26,122,26,.05) 32px);}
.logo{position:relative;z-index:1;width:52px;height:52px;background:#1a7a1a;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 0 24px rgba(26,122,26,.5);}
.logo i{color:#fff;font-size:22px;}
.brand{position:relative;z-index:1;font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:900;color:#e4f5e4;letter-spacing:.02em;}
.sub{position:relative;z-index:1;font-size:11px;color:#324832;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-top:3px;}
.form-area{padding:24px 28px 28px;}
.error{background:rgba(192,57,43,.15);border:1px solid rgba(192,57,43,.3);border-radius:8px;padding:10px 13px;font-size:13px;font-weight:600;color:#e74c3c;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.label{display:block;font-size:10px;font-weight:800;color:#324832;text-transform:uppercase;letter-spacing:.1em;margin-bottom:5px;}
.inp{width:100%;background:rgba(0,0,0,.4);border:1px solid #1a261a;border-radius:8px;padding:11px 14px;font-family:'Barlow',sans-serif;font-size:14px;color:#e4f5e4;outline:none;transition:border-color .15s;margin-bottom:14px;}
.inp:focus{border-color:#1a7a1a;box-shadow:0 0 0 3px rgba(26,122,26,.12);}
.inp::placeholder{color:#324832;}
.btn-login{width:100%;padding:13px;background:#1a7a1a;border:none;border-radius:8px;font-family:'Barlow',sans-serif;font-size:14px;font-weight:800;color:#fff;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 16px rgba(26,122,26,.4);}
.btn-login:hover{background:#22a322;transform:translateY(-1px);}
.warn{margin-top:16px;background:rgba(212,112,10,.08);border:1px solid rgba(212,112,10,.2);border-radius:8px;padding:10px 13px;font-size:12px;color:rgba(212,112,10,.8);display:flex;align-items:center;gap:7px;}
</style>
</head>
<body>
<div class="box">
  <div class="box-top">
    <div class="logo"><i class="fa-solid fa-seedling"></i></div>
    <div class="brand">DollarTree</div>
    <div class="sub">Admin Panel</div>
  </div>
  <div class="form-area">
    <?php if($error):?><div class="error"><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST">
      <label class="label">Username or Email</label>
      <input type="text" name="username" class="inp" placeholder="admin@dollartree.com" required autofocus>
      <label class="label">Password</label>
      <input type="password" name="password" class="inp" placeholder="••••••••" required>
      <button type="submit" class="btn-login" disabled title="Admin operations are currently unavailable"><i class="fa-solid fa-lock"></i> Currently Unavailable</button>
    </form>
    <div class="warn"><i class="fa-solid fa-lock"></i> Restricted access. Unauthorized entry is prohibited.</div>
  </div>
</div>
</body>
</html>

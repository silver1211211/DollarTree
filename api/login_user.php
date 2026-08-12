<?php
// api/login_user.php - User login endpoint
// Supports login by email or phone number
// Returns auth token for subsequent requests

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Get request data
    $body = file_get_contents('php://input');
    $input = json_decode($body, true);
    
    if (!$input) {
        json_error('Invalid request data');
    }
    
    // ===== EXTRACT LOGIN DATA =====
    $login_method = $input['login_method'] ?? 'email'; // 'email' or 'phone'
    $identifier = isset($input['identifier']) ? trim($input['identifier']) : '';
    $password = $input['password'] ?? '';
    
    app_log("Login attempt: method={$login_method}, identifier={$identifier}");
    
    // ===== VALIDATION =====
    if (empty($identifier)) {
        json_error('Email/Phone is required');
    }
    
    if (empty($password)) {
        json_error('Password is required');
    }
    
    // ===== FIND USER =====
    if ($login_method === 'email') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    }
    
    $stmt->execute([$identifier]);
    $user = $stmt->fetch();
    
    if (!$user) {
        app_log("Login failed: User not found - {$identifier}");
        json_error(t('invalid_credentials', 'Invalid email/phone or password'), 401);
    }
    
    // ===== VERIFY PASSWORD =====
    if (!verify_password($password, $user['password_hash'])) {
        app_log("Login failed: Invalid password for user {$user['id']}");
        log_activity($user['id'], 'login_failed', 'Invalid password attempt');
        json_error(t('invalid_credentials', 'Invalid email/phone or password'), 401);
    }
    
    // ===== CHECK ACCOUNT STATUS =====
    if ($user['account_status'] !== 'active') {
        app_log("Login failed: Account suspended/banned - User {$user['id']}");
        json_error('Account is ' . $user['account_status'] . '. Please contact support.', 403);
    }
    
    // ===== GENERATE NEW AUTH TOKEN =====
    $new_auth_token = generate_random_string(64);
    
    // Update user's last login and auth token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET auth_token = ?, 
            last_login_at = NOW(), 
            last_login_ip = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_auth_token, get_client_ip(), $user['id']]);
    
    // Reset daily withdrawal count if it's a new day
    $today = date('Y-m-d');
    if ($user['last_withdrawal_date'] !== $today) {
        $stmt = $pdo->prepare("UPDATE users SET daily_withdrawal_count = 0, last_withdrawal_date = ? WHERE id = ?");
        $stmt->execute([$today, $user['id']]);
    }
    
    app_log("✅ Login successful: User {$user['id']} ({$user['username']})");
    log_activity($user['id'], 'login', 'User logged in successfully');
    
    // ===== GET UPDATED USER INFO =====
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $user = $stmt->fetch();
    
    // Get SVIP tier info
    $svip_tier = get_svip_tier($user['svip_level']);
    
    // ===== PREPARE RESPONSE =====
    json_success([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_token' => $new_auth_token,
        'balance' => (float)$user['balance'],
        'commission_balance' => (float)$user['commission_balance'],
        'svip_level' => $user['svip_level'],
        'svip_expires_at' => $user['svip_expires_at'],
        'svip_tier' => $svip_tier,
        'referral_code' => $user['referral_code'],
        'is_admin' => (bool)$user['is_admin'],
        'language' => $user['language'],
        'total_deposited' => (float)$user['total_deposited'],
        'total_withdrawn' => (float)$user['total_withdrawn']
    ], t('login_successful', 'Login successful!'));
    
} catch (Exception $e) {
    app_log("Login EXCEPTION: " . $e->getMessage(), 'ERROR');
    json_error('Login failed: ' . $e->getMessage(), 500);
}
?>

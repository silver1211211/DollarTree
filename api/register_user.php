<?php
// api/register_user.php - User registration endpoint
// Supports registration by email or phone number
// Handles referral tracking and initial account setup

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
    
    // ===== EXTRACT REGISTRATION DATA =====
    $registration_method = $input['registration_method'] ?? 'email'; // 'email' or 'phone'
    $email = isset($input['email']) ? trim($input['email']) : null;
    $phone = isset($input['phone']) ? trim($input['phone']) : null;
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    $invitation_code = isset($input['invitation_code']) ? trim($input['invitation_code']) : null;
    
    app_log("Registration attempt: method={$registration_method}, email={$email}, phone={$phone}");
    
    // ===== VALIDATION =====
    
    // Validate registration method
    if (!in_array($registration_method, ['email', 'phone'])) {
        json_error('Invalid registration method');
    }
    
    // Validate email or phone based on method
    if ($registration_method === 'email') {
        if (empty($email)) {
            json_error(t('field_required', 'Email is required'));
        }
        if (!is_valid_email($email)) {
            json_error(t('invalid_email', 'Invalid email address'));
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            json_error(t('email_already_exists', 'Email already registered'));
        }
        
        $username = $email;
        
    } else { // phone registration
        if (empty($phone)) {
            json_error(t('field_required', 'Phone number is required'));
        }
        if (!is_valid_phone($phone)) {
            json_error(t('invalid_phone', 'Invalid phone number'));
        }
        
        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            json_error(t('phone_already_exists', 'Phone already registered'));
        }
        
        $username = 'user_' . $phone;
    }
    
    // Validate password
    if (empty($password)) {
        json_error(t('field_required', 'Password is required'));
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        json_error(t('password_too_short', 'Password must be at least 6 characters'));
    }
    if ($password !== $confirm_password) {
        json_error(t('passwords_not_match', 'Passwords do not match'));
    }
    
    // Validate and get referrer
    $referrer_id = null;
    if (!empty($invitation_code)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$invitation_code]);
        $referrer = $stmt->fetch();
        
        if (!$referrer) {
            json_error(t('invalid_invitation_code', 'Invalid invitation code'));
        }
        
        $referrer_id = $referrer['id'];
        app_log("Valid referrer found: ID {$referrer_id}");
    }
    
    // ===== CREATE USER ACCOUNT =====
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique referral code for new user
        $new_referral_code = generate_referral_code();
        
        // Generate auth token
        $auth_token = generate_random_string(64);
        
        // Hash password
        $password_hash = hash_password($password);
        
        // Get registration bonus if configured
        $registration_bonus = (float)get_setting('registration_bonus', 0);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, email, phone, password_hash, auth_token, referrer_id, referral_code, 
             registration_method, balance, svip_level, created_at, last_login_at, last_login_ip)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)
        ");
        
        $stmt->execute([
            $username,
            $email,
            $phone,
            $password_hash,
            $auth_token,
            $referrer_id,
            $new_referral_code,
            $registration_method,
            $registration_bonus,
            get_client_ip()
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        app_log("✅ User created successfully: ID={$user_id}, username={$username}, referral_code={$new_referral_code}");
        
        // Log activity
        log_activity($user_id, 'registration', "New user registered via {$registration_method}");
        
        // If there's a referrer, log the relationship only (no spin awarded at registration)
        if ($referrer_id) {
            log_activity($referrer_id, 'new_referral', "New Level 1 referral: User ID {$user_id}");
            app_log("ℹ️  Referrer ID {$referrer_id} logged for new user {$user_id} — spin awarded on SVIP 3+ unlock, not registration");
        }
        
        $pdo->commit();
        
        // ===== PREPARE RESPONSE =====
        json_success([
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'referral_code' => $new_referral_code,
            'auth_token' => $auth_token,
            'balance' => $registration_bonus,
            'svip_level' => 0,
            'registration_bonus' => $registration_bonus
        ], t('registration_successful', 'Registration successful! Please login.'));
        
    } catch (Exception $e) {
        $pdo->rollBack();
        app_log("Registration DB error: " . $e->getMessage(), 'ERROR');
        json_error('Registration failed: ' . $e->getMessage(), 500);
    }
    
} catch (Exception $e) {
    app_log("Registration EXCEPTION: " . $e->getMessage(), 'ERROR');
    json_error('Registration failed: ' . $e->getMessage(), 500);
}
?>
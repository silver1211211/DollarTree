<?php
// config.php - Main configuration file for Dollar Tree platform
// FIXED: Renamed get_current_user() to get_logged_in_user()

// ============================================
// ERROR REPORTING SETTINGS
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ============================================
// TIMEZONE SETTINGS
// ============================================
date_default_timezone_set('UTC');

// ============================================
// SESSION CONFIGURATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'dollartr_dollar_tree_db');
define('DB_USER', 'dollartr_dollar_tree');
define('DB_PASS', 'dollar_tree_db');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// PLATFORM CONFIGURATION
// ============================================
define('PLATFORM_NAME', 'DollarTree');
define('PLATFORM_URL', 'http://127.0.0.1:8000/');
define('ADMIN_EMAIL', 'admin@dollartree.local');

// ============================================
// SECURITY SETTINGS
// ============================================
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_LIFETIME', 86400);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900);

// ============================================
// FILE PATHS
// ============================================
define('BASE_PATH', __DIR__);
define('ASSETS_PATH', BASE_PATH . '/assets');
define('LOGS_PATH', BASE_PATH . '/logs');

if (!file_exists(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact support.");
}

// ============================================
// LOGGING FUNCTION
// ============================================
function app_log($message, $level = 'INFO') {
    $logFile = LOGS_PATH . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// ============================================
// LANGUAGE SUPPORT
// ============================================
function get_language() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT language FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                return $user['language'];
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    if (isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }
    
    return 'en';
}

function load_language($lang = null) {
    if ($lang === null) {
        $lang = get_language();
    }
    
    $langFile = BASE_PATH . "/languages/{$lang}.php";
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    $defaultLang = BASE_PATH . "/languages/en.php";
    if (file_exists($defaultLang)) {
        return include $defaultLang;
    }
    
    return [];
}

$translations = load_language();

function t($key, $default = '') {
    global $translations;
    return $translations[$key] ?? $default ?? $key;
}

// ============================================
// AUTHENTICATION HELPERS
// ============================================
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . PLATFORM_URL . 'user/login.php');
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . PLATFORM_URL . 'admin/login.php');
        exit;
    }
}

// FIXED: Renamed from get_current_user() to get_logged_in_user()
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function generate_referral_code() {
    global $pdo;
    
    do {
        $code = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referral_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn() > 0;
        
    } while ($exists);
    
    return $code;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function format_currency($amount, $currency = 'USDT') {
    return number_format($amount, 2, '.', ',') . ' ' . $currency;
}

function format_date($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_phone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

function calculate_svip_level($amount) {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT svip_level 
            FROM svip_tiers 
            WHERE unlock_amount <= {$amount} 
            AND status = 'active'
            ORDER BY unlock_amount DESC 
            LIMIT 1
        ");
        
        $tier = $stmt->fetch();
        return $tier ? $tier['svip_level'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

function get_svip_tier($level) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM svip_tiers WHERE svip_level = ? LIMIT 1");
        $stmt->execute([$level]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function log_activity($user_id, $activity_type, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $activity_type,
            $description,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        app_log("Failed to log activity: " . $e->getMessage(), 'ERROR');
    }
}

function get_setting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function update_setting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (setting_key, setting_value, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (Exception $e) {
        app_log("Failed to update setting: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// ============================================
// REFERRAL SYSTEM HELPERS
// ============================================

function get_user_upline($user_id) {
    global $pdo;
    
    $upline = [];
    $current_referrer_id = $user_id;
    
    for ($level = 1; $level <= 3; $level++) {
        try {
            $stmt = $pdo->prepare("SELECT referrer_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$current_referrer_id]);
            $user = $stmt->fetch();
            
            if (!$user || !$user['referrer_id']) {
                break;
            }
            
            $upline[$level] = $user['referrer_id'];
            $current_referrer_id = $user['referrer_id'];
        } catch (Exception $e) {
            break;
        }
    }
    
    return $upline;
}

function distribute_referral_commissions($referred_user_id, $amount, $source_type = 'deposit') {
    global $pdo;
    
    $upline = get_user_upline($referred_user_id);
    
    if (empty($upline)) {
        app_log("No upline found for user {$referred_user_id}");
        return;
    }
    
    try {
        $stmt = $pdo->query("SELECT level, commission_rate FROM referral_settings WHERE status = 'active' ORDER BY level");
        $rates = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($upline as $level => $referrer_id) {
            if (!isset($rates[$level])) {
                continue;
            }
            
            $rate = $rates[$level];
            $commission = $amount * $rate;
            
           $stmt = $pdo->prepare("UPDATE users SET balance = balance + ?, commission_balance = commission_balance + ? WHERE id = ?");
$stmt->execute([$commission, $commission, $referrer_id]);

            $stmt = $pdo->prepare("
                INSERT INTO commissions 
                (referrer_user_id, referred_user_id, commission_level, commission_rate, source_type, source_amount, commission_amount, status, paid_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', NOW())
            ");
            $stmt->execute([$referrer_id, $referred_user_id, $level, $rate, $source_type, $amount, $commission]);
            
            app_log("Commission paid: Level {$level}, Referrer {$referrer_id}, Amount: {$commission}");
        }
    } catch (Exception $e) {
        app_log("Commission distribution error: " . $e->getMessage(), 'ERROR');
    }
}

// ============================================
// JSON RESPONSE HELPERS
// ============================================
function json_success($data = [], $message = 'Success') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message = 'Error', $code = 400, $data = []) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'ok' => false,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

app_log("Config loaded successfully");
?>

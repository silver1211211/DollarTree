<?php
// api/check_new_deposits.php - Check for new deposits
// Used for polling to detect completed deposits in real-time
// Returns count and total of recent completed deposits

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    global $pdo;
    
    // ===== AUTHENTICATE USER =====
    $authToken = $_SERVER['HTTP_X_USER_TOKEN'] ?? '';
    $user = null;
    
    if (!empty($authToken)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE auth_token = ? LIMIT 1");
        $stmt->execute([$authToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        json_error('Authentication required', 401);
    }
    
    // ===== GET REQUEST DATA =====
    $body = file_get_contents('php://input');
    $input = json_decode($body, true);
    $addressId = (int)($input['address_id'] ?? 0);
    
    if ($addressId <= 0) {
        json_success([
            'new_deposits' => 0,
            'total_amount' => 0
        ]);
    }
    
    // ===== CHECK FOR RECENT DEPOSITS =====
    // Check for deposits in last 30 seconds that are completed
    // Use COALESCE to prioritize detected_amount over amount
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as count, 
            SUM(COALESCE(detected_amount, amount, 0)) as total 
        FROM pending_deposits 
        WHERE deposit_address_id = ? 
        AND user_id = ?
        AND status = 'completed' 
        AND completed_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ");
    $stmt->execute([$addressId, $user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $count = (int)$result['count'];
    $total = (float)($result['total'] ?? 0);
    
    if ($count > 0) {
        app_log("check_new_deposits: Found {$count} new deposits totaling \${$total} for user {$user['id']}");
    }
    
    json_success([
        'new_deposits' => $count,
        'total_amount' => $total
    ]);
    
} catch (Exception $e) {
    app_log("check_new_deposits ERROR: " . $e->getMessage());
    json_success([
        'new_deposits' => 0,
        'total_amount' => 0
    ]);
}
?>

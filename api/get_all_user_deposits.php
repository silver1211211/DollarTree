<?php
// api/get_all_user_deposits.php - Get all user deposits across all addresses
// Returns complete deposit history with status and amounts

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
    
    // ===== GET ALL DEPOSITS FOR THIS USER =====
    // Join with address details
    // Use COALESCE to get the actual amount (detected_amount first, then amount)
    $stmt = $pdo->prepare("
        SELECT 
            pd.id,
            pd.user_id,
            pd.deposit_address_id,
            pd.track_id,
            pd.amount,
            pd.detected_amount,
            COALESCE(pd.detected_amount, pd.amount, 0) as final_amount,
            pd.currency,
            pd.tx_hash,
            pd.status,
            pd.created_at,
            pd.completed_at,
            uda.network,
            uda.address
        FROM pending_deposits pd
        LEFT JOIN user_deposit_addresses uda ON pd.deposit_address_id = uda.id
        WHERE pd.user_id = ?
        ORDER BY pd.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$user['id']]);
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== PROCESS DEPOSITS =====
    foreach ($deposits as &$deposit) {
        // Convert to proper types
        $deposit['final_amount'] = (float)$deposit['final_amount'];
        $deposit['detected_amount'] = (float)($deposit['detected_amount'] ?? 0);
        $deposit['amount'] = (float)($deposit['amount'] ?? 0);
        
        // Ensure currency is set
        if (empty($deposit['currency'])) {
            $deposit['currency'] = 'USDT';
        }
        
        // Ensure network is set
        if (empty($deposit['network'])) {
            $deposit['network'] = 'Unknown';
        }
        
        // Format dates
        if ($deposit['created_at']) {
            $deposit['created_at_formatted'] = date('M d, Y H:i', strtotime($deposit['created_at']));
        }
        
        if ($deposit['completed_at']) {
            $deposit['completed_at_formatted'] = date('M d, Y H:i', strtotime($deposit['completed_at']));
        } else {
            $deposit['completed_at_formatted'] = null;
        }
        
        // Status text
        switch ($deposit['status']) {
            case 'completed':
                $deposit['status_text'] = 'Completed';
                $deposit['status_class'] = 'success';
                break;
            case 'pending':
                $deposit['status_text'] = 'Pending';
                $deposit['status_class'] = 'warning';
                break;
            case 'failed':
                $deposit['status_text'] = 'Failed';
                $deposit['status_class'] = 'danger';
                break;
            case 'expired':
                $deposit['status_text'] = 'Expired';
                $deposit['status_class'] = 'secondary';
                break;
            default:
                $deposit['status_text'] = ucfirst($deposit['status']);
                $deposit['status_class'] = 'info';
        }
    }
    
    app_log("get_all_user_deposits: Loaded " . count($deposits) . " deposits for user {$user['id']}");
    
    json_success([
        'deposits' => $deposits,
        'total' => count($deposits)
    ]);
    
} catch (Exception $e) {
    app_log("get_all_user_deposits ERROR: " . $e->getMessage());
    json_error('Failed to load deposit history', 500);
}
?>

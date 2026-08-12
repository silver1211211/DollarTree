<?php
// api/get_withdrawals.php - Get completed withdrawals for the logged-in user
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
if (!is_logged_in()) {
    json_error('Please login first', 401);
}
global $pdo;
try {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            amount,
            currency,
            status,
            processed_at,
            DATE(processed_at) AS withdrawal_date,
            DATE_FORMAT(processed_at, '%h:%i %p') AS withdrawal_time
        FROM withdrawals
        WHERE user_id = ? AND status = 'completed'
        ORDER BY processed_at DESC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($rows as $row) {
        $formatted[] = [
            'id'              => $row['id'],
            'amount'          => floatval($row['amount']),
            'currency'        => $row['currency'] ?? 'USDT',
            'status'          => $row['status'],
            'withdrawal_date' => $row['withdrawal_date'],
            'withdrawal_time' => $row['withdrawal_time'],
            'raw_timestamp'   => $row['processed_at'],
        ];
    }

    json_success($formatted, 'Withdrawals retrieved');

} catch (Exception $e) {
    app_log('Get withdrawals error: ' . $e->getMessage(), 'ERROR');
    json_error('Failed to load withdrawals: ' . $e->getMessage(), 500);
}
?>
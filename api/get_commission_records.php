<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');
$user = get_logged_in_user();
$uid = $user['id'];

try {
    $s = $pdo->prepare("
        SELECT 
            c.commission_amount,
            c.commission_level,
            c.commission_rate,
            c.source_type,
            c.source_amount,
            c.created_at,
            u.username AS referee_username
        FROM commissions c
        LEFT JOIN users u ON u.id = c.referred_user_id
        WHERE c.referrer_user_id = ? AND c.status = 'paid'
        ORDER BY c.created_at DESC
        LIMIT 100
    ");
    $s->execute([$uid]);
    $records = $s->fetchAll();

    json_success($records);
} catch (Exception $e) {
    json_error('Failed: ' . $e->getMessage());
}
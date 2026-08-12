<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();

$stmt = $pdo->prepare("
    SELECT id, amount, detected_amount, currency, status, created_at, completed_at
    FROM pending_deposits
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$user['id']]);
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $deposits]);
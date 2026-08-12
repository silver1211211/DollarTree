<?php
// api/mark_message_read.php — Mark user_messages as read
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_login();

$user = get_logged_in_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

global $pdo;
$uid  = (int)$user['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

if (!empty($body['mark_all'])) {
    // Mark ALL messages for this user as read (targeted + broadcasts)
    $pdo->prepare(
        "UPDATE user_messages SET is_read = 1
         WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0"
    )->execute([$uid]);
    echo json_encode(['success' => true]);
    exit;
}

$mid = (int)($body['message_id'] ?? 0);
if (!$mid) {
    echo json_encode(['success' => false, 'message' => 'No message_id']);
    exit;
}

// Only mark if this message belongs to this user (or is a broadcast)
$pdo->prepare(
    "UPDATE user_messages SET is_read = 1
     WHERE id = ? AND (user_id = ? OR user_id IS NULL)"
)->execute([$mid, $uid]);

echo json_encode(['success' => true]);
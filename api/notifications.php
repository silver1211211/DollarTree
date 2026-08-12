<?php
// api/notifications.php — Returns all notification data for the logged-in user
// Called by dashboard.php via fetch() to populate the notification modal tabs
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}
global $pdo;
$uid = (int)$user['id'];

// ── 1. ANNOUNCEMENTS (active platform-wide) ────────────────────────────
$announcements = $pdo->query(
    "SELECT id, title, content, announcement_type, created_at
     FROM announcements
     WHERE is_active = 1
     ORDER BY created_at DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

// ── 2. DEPOSITS ───────────────────────────────────────────────────────
// FIX: query pending_deposits (matches get_deposits.php and profile financial records)
$dep_stmt = $pdo->prepare(
    "SELECT id,
            amount,
            COALESCE(detected_amount, amount) AS detected_amount,
            currency,
            network,
            status,
            created_at,
            completed_at
     FROM pending_deposits
     WHERE user_id = ? AND status = 'completed'
     ORDER BY created_at DESC
     LIMIT 20"
);
$dep_stmt->execute([$uid]);
$deposits = $dep_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 3. WITHDRAWALS (this user's completed) ───────────────────────────
$with_stmt = $pdo->prepare(
    "SELECT id, amount, net_amount, currency, network, status,
            requested_at, processed_at, rejection_reason
     FROM withdrawals
     WHERE status ='completed' AND user_id = ?
     ORDER BY processed_at DESC
     LIMIT 20"
);
$with_stmt->execute([$uid]);
$withdrawals = $with_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 4. MESSAGES (from admin → this user OR broadcast) ─────────────────
$msg_stmt = $pdo->prepare(
    "SELECT um.id, um.title, um.content, um.msg_type, um.is_read, um.created_at,
            u.username AS from_admin
     FROM user_messages um
     LEFT JOIN users u ON um.admin_id = u.id
     WHERE um.user_id = ?
        OR um.user_id IS NULL
     ORDER BY um.created_at DESC
     LIMIT 30"
);
$msg_stmt->execute([$uid]);
$messages = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 5. Unread counts for badge ─────────────────────────────────────────
$unread_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM user_messages
     WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0"
);
$unread_stmt->execute([$uid]);
$unread_msg_count = (int)$unread_stmt->fetchColumn();

$pending_deposits    = 0;
$pending_withdrawals = count(array_filter($withdrawals, fn($w) => $w['status'] === 'pending'));

echo json_encode([
    'success'       => true,
    'unread_count'  => $unread_msg_count + $pending_deposits + $pending_withdrawals,
    'announcements' => $announcements,
    'deposits'      => $deposits,
    'withdrawals'   => $withdrawals,
    'messages'      => $messages,
]);

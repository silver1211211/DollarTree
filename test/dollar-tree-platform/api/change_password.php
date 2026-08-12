<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';   // ← fixed: added the /
require_login();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$current = trim($data['current_password'] ?? '');
$new     = trim($data['new_password'] ?? '');

if (!$current || !$new) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    exit;
}

// ── Fetch current hash ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

// ── Verify current password ─────────────────────────────────
if (!password_verify($current, $row['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

// ── Prevent reuse ───────────────────────────────────────────
if (password_verify($new, $row['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from current password.']);
    exit;
}

// ── Hash and update ─────────────────────────────────────────
$new_hash = password_hash($new, PASSWORD_BCRYPT);

try {
    $upd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$new_hash, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} catch (PDOException $e) {
    error_log('change_password error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
<?php
// api/daily_modal.php
// GET  — returns the active daily_modal announcement if user hasn't seen it today
// POST — records that user has dismissed/seen the modal today
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config.php';
require_login();
$user = get_logged_in_user();
if (!$user) { http_response_code(401); echo json_encode(['success'=>false]); exit; }

global $pdo;
$uid   = (int)$user['id'];
$today = date('Y-m-d');

// ── Auto-create tracking table if it doesn't exist ───────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS daily_modal_seen (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id   INT UNSIGNED NOT NULL,
    seen_date DATE NOT NULL,
    ann_id    INT UNSIGNED NOT NULL,
    seen_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_date (user_id, seen_date)
)");

// ── POST: mark seen for today ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $ann_id = (int)($body['ann_id'] ?? 0);
    if ($ann_id) {
        // INSERT IGNORE respects the UNIQUE KEY — safe to call multiple times
        $pdo->prepare(
            "INSERT IGNORE INTO daily_modal_seen (user_id, seen_date, ann_id) VALUES (?,?,?)"
        )->execute([$uid, $today, $ann_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── GET: check if already seen today ─────────────────────────────────────────
$seen = $pdo->prepare("SELECT id FROM daily_modal_seen WHERE user_id=? AND seen_date=? LIMIT 1");
$seen->execute([$uid, $today]);
if ($seen->fetch()) {
    echo json_encode(['success' => true, 'show' => false]);
    exit;
}

// ── Fetch the most recent active daily_modal ──────────────────────────────────
$ann = $pdo->query(
    "SELECT id, title, content, link_url, link_text, created_at
     FROM announcements
     WHERE announcement_type = 'daily_modal'
       AND is_active = 1
     ORDER BY created_at DESC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$ann) {
    echo json_encode(['success' => true, 'show' => false]);
    exit;
}

echo json_encode(['success' => true, 'show' => true, 'ann' => $ann]);
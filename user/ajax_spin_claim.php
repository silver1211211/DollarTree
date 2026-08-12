<?php
require_once __DIR__ . '/../config.php';
require_login();
header('Content-Type: application/json');

// ── 1. Authenticate ───────────────────────────────────────────────────
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ── 2. Validate prize ────────────────────────────────────────────────
$data  = json_decode(file_get_contents('php://input'), true);
$prize = round(floatval($data['prize'] ?? 0), 2);
if (!in_array($prize, [0.66, 1.66], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid prize: ' . $prize]);
    exit;
}

// ── 3. Get $pdo — try every common pattern ───────────────────────────
// Pattern A: global $pdo (most common)
global $pdo;

// Pattern B: if config uses a function like get_db() or db()
if (!$pdo && function_exists('get_db'))   { $pdo = get_db(); }
if (!$pdo && function_exists('db'))       { $pdo = db(); }
if (!$pdo && function_exists('getDb'))    { $pdo = getDb(); }

// Pattern C: if config stores it in a superglobal
if (!$pdo && isset($GLOBALS['pdo']))      { $pdo = $GLOBALS['pdo']; }
if (!$pdo && isset($GLOBALS['db']))       { $pdo = $GLOBALS['db']; }

// Pattern D: if config uses a Database class
if (!$pdo && class_exists('Database')) {
    try { $pdo = Database::getInstance(); } catch (Exception $e) {}
}

if (!$pdo || !($pdo instanceof PDO)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection unavailable. $pdo is ' . gettype($pdo),
        'hint'    => 'Check config.php — $pdo may be defined inside a function scope'
    ]);
    exit;
}

// ── 4. Enable PDO exceptions so errors aren't silent ─────────────────
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── 5. Fresh spin_chances from DB ────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT spin_chances, balance FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB read failed: ' . $e->getMessage()]);
    exit;
}

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'User not found: id=' . $user_id]);
    exit;
}

$chances = (int)$row['spin_chances'];
if ($chances <= 0) {
    echo json_encode([
        'success'        => false,
        'error'          => 'No spins available',
        'spin_chances'   => $chances,
        'current_balance'=> (float)$row['balance']
    ]);
    exit;
}

// ── 6. Atomic update ─────────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("
        UPDATE users
           SET spin_chances = spin_chances - 1,
               balance      = balance + ?
         WHERE id            = ?
           AND spin_chances  > 0
    ");
    $upd->execute([$prize, $user_id]);
    $affected = $upd->rowCount();

    if ($affected === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Race condition — no rows updated']);
        exit;
    }

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'DB update failed: ' . $e->getMessage()]);
    exit;
}

// ── 7. Return fresh values ───────────────────────────────────────────
try {
    $stmt2 = $pdo->prepare("SELECT balance, spin_chances FROM users WHERE id = ? LIMIT 1");
    $stmt2->execute([$user_id]);
    $updated = $stmt2->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Update succeeded — return estimated values
    echo json_encode([
        'success'      => true,
        'prize'        => $prize,
        'new_balance'  => (float)$row['balance'] + $prize,
        'spin_chances' => $chances - 1
    ]);
    exit;
}

echo json_encode([
    'success'      => true,
    'prize'        => $prize,
    'new_balance'  => (float)$updated['balance'],
    'spin_chances' => (int)$updated['spin_chances'],
]);
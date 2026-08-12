<?php
// api/enter_craw_mode.php
// Called by withdraw.php when the user tries to withdraw more than 55%
// of their total_deposited. Sets craw_mode=1 and initialises step 1.
// Idempotent — safe to call multiple times (won't reset an in-progress craw).

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

global $pdo;

try {
    $user  = get_logged_in_user();
    $uid   = (int)$user['id'];
    $today = date('Y-m-d');

    $crawMode = (int)$user['craw_mode'];
    $crawStep = (int)$user['craw_task_step'];

    // If already in an active craw (step 1-3), don't reset progress
    if ($crawMode && $crawStep >= 1 && $crawStep <= 3) {
        json_success([
            'already_active' => true,
            'craw_step'      => $crawStep,
            'message'        => 'CRAW mode already active. Please complete your tasks.',
        ]);
    }

    // Calculate total balance for the snapshot
    $totalBalance = (float)$user['balance'] + (float)$user['total_deposited'];

    // Enter craw: set mode on, step=1, store snapshot, keep gap intact
    $gap = (float)($user['craw_gap'] ?: 6.00);

    $stmt = $pdo->prepare("
        UPDATE users
        SET craw_mode             = 1,
            craw_task_step        = 1,
            craw_completed_today  = 0,
            craw_entered_at       = ?,
            craw_snapshot_balance = ?,
            craw_balance_after_t1 = 0,
            craw_balance_after_t2 = 0,
            craw_t3_price         = 0,
            craw_recharge_paid    = 0
        WHERE id = ?
    ");
    $stmt->execute([$today, $totalBalance, $uid]);

    app_log("CRAW MODE ENTERED: user={$uid}, snapshot={$totalBalance}, gap={$gap}, date={$today}");
    log_activity($uid, 'craw_entered', "CRAW mode entered. Snapshot balance: {$totalBalance}");

    json_success([
        'already_active'   => false,
        'craw_step'        => 1,
        'snapshot_balance' => $totalBalance,
        'gap'              => $gap,
        'message'          => 'CRAW mode activated. Please complete your withdrawal tasks.',
    ]);

} catch (Exception $e) {
    app_log("enter_craw_mode ERROR: " . $e->getMessage());
    json_error('Failed to enter CRAW mode', 500);
}
?>
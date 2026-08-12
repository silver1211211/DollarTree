<?php
// api/complete_craw_task.php
//
// KEY DESIGN RULE — prices are locked once and never move:
//   T1 completion  → saves craw_t2_price  (locked target for entering T2)
//   T2 completion  → saves craw_t3_price  (locked target for entering T3)
//   Display/gate   → always reads the stored price, NEVER recalculates from live balance
//
// This prevents the moving-target problem where depositing raises the required price.

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

    if ((int)$user['craw_mode'] !== 1) {
        json_error('You are not in CRAW mode.');
    }

    $crawStep = (int)$user['craw_task_step'];

    if ($crawStep < 1 || $crawStep > 3) {
        json_error('Invalid CRAW task step.');
    }

    srand(crc32($uid . $today . (string)$crawStep));

    $snapshot   = (float)$user['craw_snapshot_balance'];
    $balAfterT1 = (float)$user['craw_balance_after_t1'];
    $balAfterT2 = (float)$user['craw_balance_after_t2'];
    $gap        = (float)($user['craw_gap'] ?: 6.00);
    $totalDep   = (float)$user['total_deposited'];

    // Stored locked prices — set once, never touched again after that step
    $storedT2Price = (float)($user['craw_t2_price'] ?? 0);
    $storedT3Price = (float)($user['craw_t3_price'] ?? 0);

    // Current live balance
    $liveBalance = (float)$user['balance'] + $totalDep;

    // ── Safe audit log — runs AFTER commit, never kills payment ──
    function safe_audit($pdo, $params) {
        try {
            $pdo->prepare("
                INSERT INTO craw_sessions
                    (user_id, step, balance_before, task_price, earning, balance_after, note)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute($params);
        } catch (Exception $e) {
            error_log("craw_sessions insert failed (non-fatal): " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // STEP 1 — no deposit gate
    // Locks in craw_t2_price immediately so it can never move
    // ─────────────────────────────────────────────────────────────
    if ($crawStep === 1) {

        $base          = $snapshot > 0 ? $snapshot : $liveBalance;
        $earn          = round($base * 0.30, 2);
        $newBalAfterT1 = round($liveBalance + $earn, 2);
        $taskPrice     = round($base * (rand(70, 100) / 100), 2);

        // Lock T2 price NOW based on current snapshot — will never change again
        // T2 required = balance after T1 earns + 50% of total ever deposited
        $lockedT2Price = round($newBalAfterT1 + ($totalDep * 0.50), 2);

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
            ->execute([$earn, $uid]);

        $pdo->prepare("
            UPDATE users
            SET craw_task_step        = 2,
                craw_balance_after_t1 = ?,
                craw_t2_price         = ?
            WHERE id = ?
        ")->execute([$newBalAfterT1, $lockedT2Price, $uid]);

        $pdo->commit();

        safe_audit($pdo, [$uid, 1, $liveBalance, $taskPrice, $earn, $newBalAfterT1, 'Task 1 completed']);
        app_log("CRAW T1 DONE: user={$uid}, earn={$earn}, new_bal={$newBalAfterT1}, locked_t2_price={$lockedT2Price}");
        log_activity($uid, 'craw_task1', "CRAW Task 1 completed. Earned: {$earn}. T2 locked at: {$lockedT2Price}");

        json_success([
            'step_completed' => 1,
            'next_step'      => 2,
            'earning'        => $earn,
            'new_balance'    => $newBalAfterT1,
            'message'        => "Task 1 complete! You earned \${$earn} USDT.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // STEP 2 — deposit gate against LOCKED craw_t2_price
    // Locks in craw_t3_price on success so it can never move
    // ─────────────────────────────────────────────────────────────
    elseif ($crawStep === 2) {

        // Always use the price that was locked when T1 completed.
        // If for some reason it was never stored (old session), calculate once
        // and treat it as locked — do NOT recalculate on every check.
        if ($storedT2Price <= 0) {
            // One-time fallback: calculate from T1 snapshot and lock it now
            $t2Base        = $balAfterT1 > 0 ? $balAfterT1 : $liveBalance;
            $storedT2Price = round($t2Base + ($totalDep * 0.50), 2);
            // Save immediately so future refreshes use this fixed value
            $pdo->prepare("UPDATE users SET craw_t2_price = ? WHERE id = ?")
                ->execute([$storedT2Price, $uid]);
        }

        $t2MakeUp = round(max(0, $storedT2Price - $liveBalance), 2);

        if ($liveBalance < $storedT2Price) {
            json_error("Please top up \${$t2MakeUp} USDT to proceed with Task 2.", 402);
        }

        $t2Base        = $balAfterT1 > 0 ? $balAfterT1 : $liveBalance;
        $earn          = round($t2Base * 0.30, 2);
        $newBalAfterT2 = round($liveBalance + $earn, 2);
        $taskPrice     = round($t2Base * (rand(70, 100) / 100), 2);

        // Lock T3 price NOW — will never change again regardless of future deposits
        $lockedT3Price = round($newBalAfterT2 + ($totalDep * 0.80), 2);

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
            ->execute([$earn, $uid]);

        $pdo->prepare("
            UPDATE users
            SET craw_task_step        = 3,
                craw_balance_after_t2 = ?,
                craw_t3_price         = ?
            WHERE id = ?
        ")->execute([$newBalAfterT2, $lockedT3Price, $uid]);

        $pdo->commit();

        safe_audit($pdo, [$uid, 2, $liveBalance, $taskPrice, $earn, $newBalAfterT2, 'Task 2 completed']);
        app_log("CRAW T2 DONE: user={$uid}, earn={$earn}, new_bal={$newBalAfterT2}, locked_t3_price={$lockedT3Price}");
        log_activity($uid, 'craw_task2', "CRAW Task 2 completed. Earned: {$earn}. T3 locked at: {$lockedT3Price}");

        json_success([
            'step_completed' => 2,
            'next_step'      => 3,
            'earning'        => $earn,
            'new_balance'    => $newBalAfterT2,
            't3_price'       => $lockedT3Price,
            'makeup'         => round(max(0, $lockedT3Price - $newBalAfterT2), 2),
            'message'        => "Task 2 complete! You earned \${$earn} USDT.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // STEP 3 — deposit gate against LOCKED craw_t3_price
    // ─────────────────────────────────────────────────────────────
    elseif ($crawStep === 3) {

        if ($storedT3Price <= 0) {
            // One-time fallback: lock it now and save
            $t3Base        = $balAfterT2 > 0 ? $balAfterT2 : $liveBalance;
            $storedT3Price = round($t3Base + ($totalDep * 0.80), 2);
            $pdo->prepare("UPDATE users SET craw_t3_price = ? WHERE id = ?")
                ->execute([$storedT3Price, $uid]);
        }

        $makeUp = round(max(0, $storedT3Price - $liveBalance), 2);

        if ($liveBalance < $storedT3Price) {
            json_error("Please top up \${$makeUp} USDT to proceed with Task 3.", 402);
        }

        $t3Base     = $balAfterT2 > 0 ? $balAfterT2 : $liveBalance;
        $earn       = round($t3Base * 0.50, 2);
        $newBalance = round($liveBalance + $earn, 2);
        $newGap     = round($gap * 1.50, 2);

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
            ->execute([$earn, $uid]);

        // craw_mode stays 1 — admin resets to 0 after confirming withdrawal
        $pdo->prepare("
            UPDATE users
            SET craw_task_step       = 4,
                craw_completed_today = 1,
                craw_gap             = ?
            WHERE id = ?
        ")->execute([$newGap, $uid]);

        $pdo->commit();

        safe_audit($pdo, [$uid, 3, $liveBalance, $storedT3Price, $earn, $newBalance, 'Task 3 completed - CRAW finished']);
        app_log("CRAW T3 DONE: user={$uid}, earn={$earn}, new_bal={$newBalance}, new_gap={$newGap}");
        log_activity($uid, 'craw_task3', "CRAW Task 3 completed. Earned: {$earn}. CRAW session done.");

        json_success([
            'step_completed' => 3,
            'next_step'      => 'done',
            'earning'        => $earn,
            'new_balance'    => $newBalance,
            'new_gap'        => $newGap,
            'craw_done'      => true,
            'message'        => "All CRAW tasks complete! You earned \${$earn} USDT. Please contact support to complete your withdrawal.",
        ]);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    app_log("complete_craw_task ERROR: " . $e->getMessage());
    json_error('Failed to complete CRAW task: ' . $e->getMessage(), 500);
}
?>
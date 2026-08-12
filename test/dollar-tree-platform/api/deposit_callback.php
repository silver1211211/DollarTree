<?php
// api/deposit_callback.php - OxaPay deposit callback handler
// FIXED: Correctly parses OxaPay static-address webhook payload structure
// OxaPay sends: amount=0 at top level, real data is inside txs[] array

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // ===== GET CALLBACK DATA =====
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    app_log("===== DEPOSIT CALLBACK RECEIVED =====");
    app_log("Callback data: " . substr($body, 0, 3000));

    if (!$data) {
        app_log("ERROR: Invalid JSON in callback", 'ERROR');
        json_error('Invalid callback data');
    }

    // ===== EXTRACT TOP-LEVEL FIELDS =====
    $trackId  = $data['track_id']  ?? null;
    $orderId  = $data['order_id']  ?? null;
    $status   = $data['status']    ?? null;   // "Paid" at top level
    $currency = $data['currency']  ?? 'USDT';
    $network  = $data['network']   ?? null;

    app_log("Top-level — Track ID: {$trackId}, Order ID: {$orderId}, Status: {$status}");

    // ===== EXTRACT REAL DATA FROM txs[] ARRAY =====
    // OxaPay static-address webhooks always set top-level amount=0.
    // The actual tx details live in txs[0].
    $txHash         = null;
    $detectedAmount = 0;
    $txAddress      = null;
    $txNetwork      = null;

    if (!empty($data['txs']) && is_array($data['txs'])) {
        // Use the first confirmed transaction
        foreach ($data['txs'] as $tx) {
            if (isset($tx['tx_hash'])) {
                $txHash         = $tx['tx_hash']          ?? null;
                $detectedAmount = (float)($tx['received_amount'] ?? $tx['value'] ?? 0);
                $txAddress      = $tx['address']           ?? null;
                $txNetwork      = $tx['network']           ?? null;
                app_log("Found TX in txs[] — Hash: {$txHash}, Amount: {$detectedAmount}, Network: {$txNetwork}, Address: {$txAddress}");
                break; // take the first tx
            }
        }
    }

    // Fallback: try top-level fields (for non-static invoice flows)
    if (!$txHash) {
        $txHash = $data['tx_hash'] ?? null;
    }
    if ($detectedAmount == 0) {
        // value = amount after fees, sent_value = what sender actually sent
        $detectedAmount = (float)($data['value'] ?? $data['amount'] ?? 0);
    }
    if (!$txAddress) {
        $txAddress = $data['address'] ?? null;
    }
    if (!$txNetwork) {
        $txNetwork = $network;
    }

    app_log("Extracted — Track: {$trackId}, Status: {$status}, Amount: {$detectedAmount} {$currency}, Tx: {$txHash}, Address: {$txAddress}, Network: {$txNetwork}");

    if (!$trackId && !$orderId) {
        app_log("ERROR: Missing track_id and order_id", 'ERROR');
        json_error('Missing transaction identifiers');
    }

    // ===== FIND DEPOSIT ADDRESS =====
    // Try track_id first (most reliable), then fall back to wallet address
    $depositAddress = null;

    if ($trackId) {
        $stmt = $pdo->prepare("SELECT * FROM user_deposit_addresses WHERE track_id = ? LIMIT 1");
        $stmt->execute([$trackId]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($depositAddress) {
            app_log("Found deposit address by track_id: {$trackId}");
        }
    }

    if (!$depositAddress && $txAddress) {
        $stmt = $pdo->prepare("SELECT * FROM user_deposit_addresses WHERE address = ? LIMIT 1");
        $stmt->execute([$txAddress]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($depositAddress) {
            app_log("Found deposit address by wallet address: {$txAddress}");
        }
    }

    // Last resort: parse user ID from order_id (format: USER_722_BSC_1767971030)
    if (!$depositAddress && $orderId && preg_match('/^USER_(\d+)_/', $orderId, $m)) {
        $parsedUserId = (int)$m[1];
        $stmt = $pdo->prepare("SELECT * FROM user_deposit_addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$parsedUserId]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($depositAddress) {
            app_log("Found deposit address by order_id user parse: user_id={$parsedUserId}");
        }
    }

    if (!$depositAddress) {
        app_log("WARNING: Deposit address not found. track_id={$trackId}, address={$txAddress}, order_id={$orderId}", 'WARNING');
        json_error('Deposit address not found', 404);
    }

    $userId = $depositAddress['user_id'];
    app_log("Deposit for user_id: {$userId}");

    // ===== GET USER INFO =====
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        app_log("ERROR: User not found: {$userId}", 'ERROR');
        json_error('User not found', 404);
    }

   // ===== CHECK IF ALREADY PROCESSED (deduplicate by tx_hash ONLY) =====
// track_id is reused across all deposits to the same static address,
// so deduplicating by it would block every deposit after the first.
// tx_hash is unique per blockchain transaction — safe to deduplicate on.
$existingDeposit = null;

if ($txHash) {
    $stmt = $pdo->prepare("SELECT * FROM pending_deposits WHERE tx_hash = ? LIMIT 1");
    $stmt->execute([$txHash]);
    $existingDeposit = $stmt->fetch(PDO::FETCH_ASSOC);
}
    // ===== ADD BACK OXAPAY 2% FEE =====
    // OxaPay deducts 2% before delivering the amount. We reverse this so the
    // user is credited their full deposit. Formula: detectedAmount / 0.98
    // e.g. OxaPay delivers 0.98 → 0.98 / 0.98 = 1.00 (what the user actually sent)
    $creditAmount = $detectedAmount > 0 ? round($detectedAmount / 0.98, 8) : 0;
    app_log("Fee compensation — OxaPay delivered: {$detectedAmount}, Crediting: {$creditAmount}");

    // ===== MAP OxaPay STATUS → valid DB ENUM =====
    // pending_deposits.status ENUM is: pending, completed, failed, below_minimum
    // OxaPay sends "Paid" which maps to 'completed'. "paid" is NOT a valid ENUM value.
    $dbStatus = 'pending';
    if ($status === 'Paid' || strtolower($status) === 'paid' || strtolower($status) === 'completed') {
        $dbStatus = 'completed';
    } elseif (in_array(strtolower($status), ['expired', 'canceled', 'failed'])) {
        $dbStatus = 'failed';
    }

    if ($existingDeposit) {
        if ($existingDeposit['status'] === 'completed') {
            app_log("Deposit already processed: {$existingDeposit['id']}");
            json_success(['message' => 'Deposit already processed']);
        }

        // Update existing pending record
        $depositId = $existingDeposit['id'];
        app_log("Updating existing deposit record: {$depositId}");

        $stmt = $pdo->prepare("
            UPDATE pending_deposits
            SET detected_amount = ?,
                tx_hash         = ?,
                status          = ?,
                completed_at    = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$creditAmount, $txHash, $dbStatus, $depositId]);

    } else {
        // Insert new deposit record
        $stmt = $pdo->prepare("
            INSERT INTO pending_deposits
              (user_id, deposit_address_id, track_id, amount, detected_amount,
               currency, tx_hash, status, created_at, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $userId,
            $depositAddress['id'],
            $trackId,
            $creditAmount,
            $creditAmount,
            $currency,
            $txHash,
            $dbStatus,
        ]);

        $depositId = $pdo->lastInsertId();
        app_log("Created new deposit record: {$depositId}");
    }

    // ===== PROCESS COMPLETED DEPOSITS =====
    // OxaPay sends "Paid" (capital P) — check both for safety
    if ($status === 'Paid' || strtolower($status) === 'paid' || strtolower($status) === 'completed') {

        $minDeposit = (float)get_setting('min_deposit_amount', 2);

        if ($creditAmount <= 0) {
            app_log("WARNING: Zero or negative amount received: {$detectedAmount}. Check txs[] parsing.");
            json_error('Invalid deposit amount', 400);
        }

        if ($creditAmount < $minDeposit) {
            app_log("WARNING: Deposit {$creditAmount} below minimum {$minDeposit}. Deposit ID: {$depositId}");
            // Mark as below_minimum so admin can review
            $pdo->prepare("UPDATE pending_deposits SET status = 'below_minimum' WHERE id = ?")
                ->execute([$depositId]);
            json_success(['message' => 'Deposit below minimum amount']);
        }

        app_log("Processing completed deposit: {$depositId}, Amount: {$creditAmount}");

        try {
            $pdo->beginTransaction();

            // ===== UPDATE USER BALANCE =====
            $stmt = $pdo->prepare("
                UPDATE users
                SET 
                    total_deposited = total_deposited + ?
                WHERE id = ?
            ");
            $stmt->execute([$creditAmount, $userId]);

            app_log("Added {$creditAmount} to user {$userId} balance");

            // ===== MARK DEPOSIT AS COMPLETED =====
            $pdo->prepare("
                UPDATE pending_deposits
                SET status       = 'completed',
                    completed_at = NOW()
                WHERE id = ?
            ")->execute([$depositId]);

            // ===== SVIP LEVEL UPGRADE =====
            $newTotalDeposited = $user['total_deposited'] + $creditAmount;
            $newSvipLevel      = calculate_svip_level($newTotalDeposited);

            if ($newSvipLevel > $user['svip_level']) {
                $svipTier     = get_svip_tier($newSvipLevel);
                $contractDays = $svipTier['contract_duration_days'] ?? 90;

                $pdo->prepare("
                    UPDATE users
                    SET svip_level         = ?,
                        svip_unlock_amount = ?,
                        svip_activated_at  = NOW(),
                        svip_expires_at    = DATE_ADD(NOW(), INTERVAL ? DAY)
                    WHERE id = ?
                ")->execute([$newSvipLevel, $svipTier['unlock_amount'], $contractDays, $userId]);

                app_log("SVIP level upgraded: {$user['svip_level']} → {$newSvipLevel}");
                log_activity($userId, 'svip_upgrade', "SVIP level upgraded to {$newSvipLevel}");
            } else {
                $newSvipLevel = $user['svip_level'];
            }

            // ===== REFERRAL COMMISSIONS =====
            distribute_referral_commissions($userId, $creditAmount, 'deposit');

            // ===== CRAW RECHARGE CHECK =====
            if ((int)$user['craw_mode'] === 1 && (int)$user['craw_task_step'] === 3) {
                $t3Price = (float)$user['craw_t3_price'];

                $stmt2 = $pdo->prepare("SELECT balance, commission_balance FROM users WHERE id = ? LIMIT 1");
                $stmt2->execute([$userId]);
                $freshUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                $newTotal  = (float)$freshUser['balance'] + (float)$freshUser['commission_balance'];

                if ($t3Price > 0 && $newTotal >= $t3Price) {
                    $pdo->prepare("UPDATE users SET craw_recharge_paid = 1 WHERE id = ?")
                        ->execute([$userId]);
                    app_log("CRAW: Task-3 recharge satisfied for user {$userId}. Balance={$newTotal}, T3 price={$t3Price}");
                    log_activity($userId, 'craw_t3_recharge', "Task-3 recharge paid. Balance {$newTotal} >= price {$t3Price}");
                }
            }

            // ===== LOG ACTIVITY =====
            log_activity($userId, 'deposit_completed', "Deposit completed: {$creditAmount} {$currency} via {$txNetwork}");

            $pdo->commit();

            // Re-fetch final balance
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $finalUser = $stmt->fetch(PDO::FETCH_ASSOC);

            app_log("✅ DEPOSIT PROCESSED SUCCESSFULLY — User: {$userId}, Amount: {$creditAmount}, New Balance: {$finalUser['balance']}");

            json_success([
                'deposit_id'  => $depositId,
                'user_id'     => $userId,
                'amount'      => $creditAmount,
                'new_balance' => (float)$finalUser['balance'],
                'svip_level'  => $newSvipLevel,
                'message'     => 'Deposit processed successfully',
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            app_log("ERROR processing deposit: " . $e->getMessage(), 'ERROR');
            json_error('Failed to process deposit: ' . $e->getMessage(), 500);
        }
    }

    // Non-paid statuses (waiting, confirming, etc.)
    json_success(['message' => 'Callback received', 'status' => $status]);

} catch (Exception $e) {
    app_log("CALLBACK EXCEPTION: " . $e->getMessage(), 'ERROR');
    json_error('Callback processing failed: ' . $e->getMessage(), 500);
}
?>
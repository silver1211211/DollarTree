<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/oxapay_helpers.php';

function callback_error(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function callback_ok(): never
{
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

try {
    global $pdo;
    $rawBody = file_get_contents('php://input') ?: '';
    $data = json_decode($rawBody, true);

    if (!is_array($data)) {
        app_log('OxaPay callback rejected: invalid JSON', 'WARNING');
        callback_error('invalid json', 400);
    }

    $merchantKey = (string)get_setting('oxapay_api_key', '');
    $receivedHmac = (string)($_SERVER['HTTP_HMAC'] ?? '');
    if (!oxapay_verify_webhook($rawBody, $receivedHmac, $merchantKey)) {
        app_log('OxaPay callback rejected: invalid HMAC', 'WARNING');
        callback_error('invalid signature', 401);
    }

    $status = strtolower((string)($data['status'] ?? ''));

    // OxaPay first sends Paying. It must produce no user-visible record or
    // notification; only the final Paid callback is eligible for crediting.
    if ($status !== 'paid') {
        app_log('OxaPay callback acknowledged without user notification. Status: ' . $status);
        callback_ok();
    }

    $trackId = trim((string)($data['track_id'] ?? ''));
    $orderId = trim((string)($data['order_id'] ?? ''));

    $transaction = null;
    foreach (($data['txs'] ?? []) as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }
        if (strtolower((string)($candidate['status'] ?? 'confirmed')) === 'confirmed') {
            $transaction = $candidate;
            break;
        }
    }
    if ($transaction === null && !empty($data['tx_hash'])) {
        $transaction = $data;
    }
    if ($transaction === null) {
        app_log('OxaPay Paid callback rejected: no confirmed transaction', 'WARNING');
        callback_error('confirmed transaction required', 422);
    }

    $txHash = trim((string)($transaction['tx_hash'] ?? ''));
    $txAddress = trim((string)($transaction['address'] ?? $data['address'] ?? ''));
    $txNetwork = (string)($transaction['network'] ?? $data['network'] ?? '');
    $receivedAmount = (float)($transaction['received_amount'] ?? $data['value'] ?? 0);
    $originalAsset = strtoupper((string)($transaction['currency'] ?? $data['pay_currency'] ?? $data['currency'] ?? ''));

    if ($txHash === '' || $receivedAmount <= 0) {
        app_log('OxaPay Paid callback rejected: missing tx hash or amount', 'WARNING');
        callback_error('invalid transaction', 422);
    }

    $depositAddress = null;
    if ($trackId !== '') {
        $stmt = $pdo->prepare('SELECT * FROM user_deposit_addresses WHERE track_id = ? LIMIT 1');
        $stmt->execute([$trackId]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$depositAddress && $txAddress !== '') {
        $stmt = $pdo->prepare('SELECT * FROM user_deposit_addresses WHERE address = ? LIMIT 1');
        $stmt->execute([$txAddress]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$depositAddress && preg_match('/^USER_(\d+)_([A-Z0-9-]+)_/', $orderId, $matches)) {
        $stmt = $pdo->prepare("SELECT uda.* FROM user_deposit_addresses uda JOIN deposit_asset_catalog dac ON dac.symbol=uda.currency AND dac.network_key=uda.network WHERE uda.user_id=? AND dac.canonical_code=? LIMIT 1");
        $stmt->execute([(int)$matches[1], strtolower($matches[2])]);
        $depositAddress = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$depositAddress) {
        app_log("OxaPay Paid callback: address assignment not found for track {$trackId}", 'ERROR');
        callback_error('address not found', 404);
    }

    if ($originalAsset === '') {
        $originalAsset = strtoupper((string)$depositAddress['currency']);
    }

    // Prefer the conversion supplied by OxaPay. If it is absent, use its
    // transaction rate. Only then fall back to a fresh worker-cached rate.
    $convertedUsdt = 0.0;
    $conversionRate = 0.0;
    $conversionSource = 'none';
    $autoCurrency = strtoupper((string)($transaction['auto_convert_currency'] ?? $data['auto_convert_currency'] ?? ''));
    $autoAmount = (float)($transaction['auto_convert_amount'] ?? $data['auto_convert_amount'] ?? 0);
    if ($autoCurrency === 'USDT' && $autoAmount > 0) {
        $convertedUsdt = $autoAmount;
        $conversionRate = $receivedAmount > 0 ? $convertedUsdt / $receivedAmount : 0;
        $conversionSource = 'oxapay_amount';
    } elseif ($originalAsset === 'USDT') {
        $convertedUsdt = $receivedAmount;
        $conversionRate = 1.0;
        $conversionSource = 'same_asset';
    } else {
        $providerRate = (float)($transaction['rate'] ?? $data['rate'] ?? 0);
        if ($providerRate > 0) {
            $conversionRate = $providerRate;
            $convertedUsdt = $receivedAmount * $conversionRate;
            $conversionSource = 'oxapay_rate';
        } else {
            $stmt = $pdo->prepare("SELECT usdt_rate FROM crypto_conversion_rates WHERE symbol=? AND fetched_at >= DATE_SUB(NOW(),INTERVAL 5 MINUTE) LIMIT 1");
            $stmt->execute([$originalAsset]);
            $conversionRate = (float)$stmt->fetchColumn();
            if ($conversionRate > 0) {
                $convertedUsdt = $receivedAmount * $conversionRate;
                $conversionSource = 'cached_rate';
            }
        }
    }
    if ($convertedUsdt <= 0) {
        app_log("OxaPay Paid callback awaiting conversion rate for {$originalAsset}", 'WARNING');
        callback_error('conversion rate unavailable', 422);
    }
    $creditAmount = oxapay_credit_amount($convertedUsdt);
    app_log("Deposit conversion: {$receivedAmount} {$originalAsset} -> {$convertedUsdt} USDT ({$conversionSource}), credit={$creditAmount}");

    $userId = (int)$depositAddress['user_id'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        callback_error('user not found', 404);
    }

    $stmt = $pdo->prepare('SELECT * FROM pending_deposits WHERE tx_hash = ? LIMIT 1');
    $stmt->execute([$txHash]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing && in_array($existing['status'], ['completed', 'below_minimum'], true)) {
        callback_ok();
    }

    $minimum = max(2.0, (float)get_setting('min_deposit_amount', 2));
    $isEligible = $creditAmount >= $minimum;
    $depositStatus = $isEligible ? 'completed' : 'below_minimum';
    $platformStatus = $isEligible ? 'credited' : 'below_minimum';
    $creditStatus = $isEligible ? 'credited' : 'not_eligible';

    $pdo->beginTransaction();
    try {
        if ($existing) {
            $depositId = (int)$existing['id'];
            $stmt = $pdo->prepare("UPDATE pending_deposits SET amount=?,detected_amount=?,received_amount=?,original_amount=?,original_asset=?,converted_usdt_amount=?,provider_conversion_rate=?,gross_up_amount=?,credited_amount=?,currency='USDT',asset='USDT',network=?,transaction_hash=?,status=?,provider='oxapay',provider_status='Paid',platform_status=?,credit_status=?,completed_at=NOW(),confirmed_at=NOW(),credited_at=IF(?='credited',NOW(),NULL),updated_at=NOW() WHERE id=?");
            $stmt->execute([$creditAmount,$creditAmount,$receivedAmount,$receivedAmount,$originalAsset,$convertedUsdt,$conversionRate,$creditAmount,$isEligible?$creditAmount:0,$depositAddress['network'],$txHash,$depositStatus,$platformStatus,$creditStatus,$creditStatus,$depositId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pending_deposits (user_id,deposit_address_id,address_assignment_id,track_id,amount,detected_amount,currency,tx_hash,status,created_at,completed_at,provider,transaction_hash,asset,original_asset,network,received_amount,original_amount,converted_usdt_amount,provider_conversion_rate,provider_fee_rate,gross_up_amount,credited_amount,provider_status,platform_status,credit_status,confirmed_at,credited_at) VALUES (?,?,?,?,?,?,'USDT',?,?,NOW(),NOW(),'oxapay',?,'USDT',?,?,?,?,?,?,0.02,?,?,'Paid',?,?,NOW(),IF(?='credited',NOW(),NULL))");
            $stmt->execute([$userId,$depositAddress['id'],$depositAddress['id'],$trackId,$creditAmount,$creditAmount,$txHash,$depositStatus,$txHash,$originalAsset,$depositAddress['network'],$receivedAmount,$receivedAmount,$convertedUsdt,$conversionRate,$creditAmount,$isEligible?$creditAmount:0,$platformStatus,$creditStatus,$creditStatus]);
            $depositId = (int)$pdo->lastInsertId();
        }

        if (!$isEligible) {
            app_log("OxaPay deposit {$txHash} below 2 USDT after fee adjustment; no credit and no notification.");
            $pdo->commit();
            callback_ok();
        }

        $stmt = $pdo->prepare('UPDATE users SET total_deposited = total_deposited + ? WHERE id = ?');
        $stmt->execute([$creditAmount, $userId]);

        $newTotalDeposited = (float)$user['total_deposited'] + $creditAmount;
        $newSvipLevel = calculate_svip_level($newTotalDeposited);
        if ($newSvipLevel > (int)$user['svip_level']) {
            $tier = get_svip_tier($newSvipLevel);
            $days = (int)($tier['contract_duration_days'] ?? 90);
            $stmt = $pdo->prepare('UPDATE users SET svip_level=?,svip_unlock_amount=?,svip_activated_at=NOW(),svip_expires_at=DATE_ADD(NOW(),INTERVAL ? DAY) WHERE id=?');
            $stmt->execute([$newSvipLevel, $tier['unlock_amount'], $days, $userId]);
            log_activity($userId, 'svip_upgrade', "SVIP level upgraded to {$newSvipLevel}");
        }

        distribute_referral_commissions($userId, $creditAmount, 'deposit');
        log_activity($userId, 'deposit_completed', "Deposit completed: {$creditAmount} USDT via {$txNetwork}");
        $pdo->commit();
        app_log("OxaPay deposit credited: user={$userId}, tx={$txHash}, received={$receivedAmount}, credited={$creditAmount}");
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    callback_ok();
} catch (Throwable $error) {
    app_log('OxaPay callback exception: ' . $error->getMessage(), 'ERROR');
    callback_error('processing failed', 500);
}

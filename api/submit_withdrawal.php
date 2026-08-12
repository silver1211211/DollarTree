<?php
// api/submit_withdrawal.php - Automatic Withdrawal Processing
// Integrates with OxaPay Payout API for instant automated withdrawals
// SECURITY FIXES APPLIED:
//   1. API key fallback hardcode removed
//   2. CRAW mode enforced server-side (was frontend-only bypass)
//   3. FOR UPDATE row lock prevents race condition on balance deduction
//   4. Rate limiting — max 3 withdrawal attempts per user per minute
//   5. CSRF token validation on every request
//   6. last_withdrawal_date-based daily limit reset (replaces unreliable count)

require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ─────────────────────────────────────────────
// CONFIG
// ── FIX 1: Hardcoded fallback key removed ────
// A previous revision used a hard-coded fallback credential.
// If there is no key in
// the DB we now fail loudly instead of silently using the hardcoded one.
// ─────────────────────────────────────────────
$OXAPAY_PAYOUT_API_KEY = get_setting('oxapay_payout_api_key', '');
$OXAPAY_PAYOUT_URL     = 'https://api.oxapay.com/v1/payout';

if (empty($OXAPAY_PAYOUT_API_KEY)) {
    error_log("submit_withdrawal: oxapay_payout_api_key not configured in admin_settings");
    echo json_encode(['success' => false, 'message' => 'Withdrawals are temporarily unavailable. Please contact support.']);
    exit;
}

// ─────────────────────────────────────────────
// 1. Parse input
// ─────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

$amount      = isset($input['amount'])              ? (float)$input['amount']              : 0;
$destination = isset($input['wallet_address'])      ? trim($input['wallet_address'])       :
              (isset($input['destination_address']) ? trim($input['destination_address'])  : '');
$currency    = isset($input['currency'])            ? strtoupper(trim($input['currency']))  : 'USDT';
$network_in  = isset($input['network'])             ? strtoupper(trim($input['network']))   : 'TRC20';

// ── FIX 5: CSRF token validation ─────────────────────────────────────────
// The frontend must send the CSRF token that was embedded in the page.
// This prevents Cross-Site Request Forgery where another website tricks
// a logged-in user's browser into submitting a withdrawal.
// See withdraw.php for where the token is generated and embedded.
$incoming_csrf = trim($input['csrf_token'] ?? '');
$session_csrf  = $_SESSION['withdrawal_csrf'] ?? '';

if (empty($session_csrf) || empty($incoming_csrf) || !hash_equals($session_csrf, $incoming_csrf)) {
    error_log("submit_withdrawal: CSRF mismatch for user " . ($_SESSION['user_id'] ?? 'unknown'));
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
    exit;
}

// Rotate the token after each use so it cannot be replayed
$_SESSION['withdrawal_csrf'] = bin2hex(random_bytes(32));
// ─────────────────────────────────────────────────────────────────────────

$network_map = [
    'TRC20'   => 'TRC20',
    'BEP20'   => 'BSC',
    'ERC20'   => 'ERC20',
    'POLYGON' => 'POLYGON',
    'TRX'     => 'TRC20',
    'BSC'     => 'BSC',
    'ETH'     => 'ERC20',
];
$oxapay_network = $network_map[$network_in] ?? 'TRC20';

// ─────────────────────────────────────────────
// ADDRESS FORMAT VALIDATOR
// Returns null if valid, or a specific error string
// ─────────────────────────────────────────────
function validate_address_for_network(string $address, string $network): ?string {
    if (strlen($address) < 10) {
        return 'Please enter a valid wallet address.';
    }
    switch ($network) {
        case 'TRC20':
            if (!preg_match('/^T[A-Za-z0-9]{33}$/', $address)) {
                return 'Wrong address format for TRC20 (Tron). TRON addresses start with "T" and are exactly 34 characters. Example: TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE';
            }
            break;
        case 'BEP20':
            if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
                return 'Wrong address format for BEP20 (BSC). BSC addresses start with "0x" followed by 40 hex characters. Example: 0x71C7656EC7ab88b098defB751B7401B5f6d8976F';
            }
            break;
        case 'ERC20':
            if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
                return 'Wrong address format for ERC20 (Ethereum). Ethereum addresses start with "0x" followed by 40 hex characters.';
            }
            break;
        case 'POLYGON':
            if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
                return 'Wrong address format for Polygon. Polygon addresses start with "0x" followed by 40 hex characters.';
            }
            break;
        default:
            if (strlen($address) < 20) {
                return 'Invalid wallet address. Please double-check and re-enter.';
            }
    }
    return null;
}

// ─────────────────────────────────────────────
// OxaPay error code → human-readable message
// ─────────────────────────────────────────────
function oxapay_error_message(int $code, string $raw_message, string $error_key = ''): string {
    $key_map = [
        'amount_transaction_limit' => 'The withdrawal amount exceeds the maximum allowed per transaction on this gateway. Please withdraw a smaller amount.',
        'insufficient_balance'     => 'Gateway has insufficient balance to process this withdrawal. Please contact support.',
        'invalid_address'          => 'The wallet address does not match the selected network. Please verify your address and network.',
        'network_unavailable'      => 'The selected network is currently unavailable. Please try TRC20 instead.',
        'currency_not_supported'   => 'Currency not supported. Please use USDT.',
        'duplicate_request'        => 'Duplicate request — this withdrawal was already submitted.',
        'daily_limit_exceeded'     => 'Daily payout limit reached on the gateway. Please try again tomorrow.',
    ];
    if (!empty($error_key) && isset($key_map[$error_key])) {
        return $key_map[$error_key];
    }
    $map = [
        101 => 'Invalid API key. Please contact support.',
        102 => 'Gateway has insufficient balance to process this withdrawal. Please contact support.',
        103 => 'The withdrawal amount is below the gateway minimum. Try a larger amount.',
        104 => 'The wallet address does not match the selected network. Please check your address and network, then try again.',
        105 => 'The selected network is currently unavailable. Please try TRC20 instead.',
        106 => 'Currency not supported. Please use USDT.',
        107 => 'Payouts are disabled on this account. Please contact support.',
        108 => 'Duplicate request — this withdrawal was already submitted.',
        109 => 'Invalid withdrawal amount. Please enter a valid number.',
        110 => 'Daily payout limit reached on the gateway. Please try again tomorrow.',
    ];
    if (isset($map[$code])) {
        return $map[$code];
    }
    if (!empty($raw_message) && strlen($raw_message) < 200) {
        if (stripos($raw_message, 'address') !== false) {
            return 'The wallet address does not match the selected network. Please verify your address and network.';
        }
        if (stripos($raw_message, 'balance') !== false || stripos($raw_message, 'insufficient') !== false) {
            return 'Withdrawal failed due to insufficient gateway balance. Please contact support.';
        }
        return 'Withdrawal failed: ' . $raw_message;
    }
    return 'Withdrawal could not be processed (code: ' . $code . '). Please contact support.';
}

// ─────────────────────────────────────────────
// Helper: refund balance on failure
// ─────────────────────────────────────────────
function refund_withdrawal(PDO $db, int $withdrawal_id, int $user_id, float $amount): void
{
    try {
        $db->prepare("
            UPDATE users
            SET balance                = balance + ?,
                daily_withdrawal_count = GREATEST(0, daily_withdrawal_count - 1)
            WHERE id = ?
        ")->execute([$amount, $user_id]);

        $db->prepare("UPDATE withdrawals SET status='failed' WHERE id=?")
           ->execute([$withdrawal_id]);

    } catch (Exception $e) {
        error_log("CRITICAL: Failed to refund withdrawal #{$withdrawal_id} for user #{$user_id}: " . $e->getMessage());
    }
}

// ─────────────────────────────────────────────
// 2. Load user & settings
// ─────────────────────────────────────────────
$user             = get_logged_in_user();
$user_id          = (int)$user['id'];
// ── MINIMUM WITHDRAWAL FLOOR ─────────────────────────────────────────────
// The DB setting is read but we enforce a hard floor of $9 so that even if
// an admin accidentally sets min_withdrawal_amount to 1 (or the DB row is
// missing), withdrawals below $9 are always rejected at the backend.
// The frontend also shows $9 minimum — both must agree at all times.
$HARD_MIN_WITHDRAWAL = 9.0;
$db_min              = (float)get_setting('min_withdrawal_amount', 9);
$min_withdrawal      = max($HARD_MIN_WITHDRAWAL, $db_min);
// ─────────────────────────────────────────────────────────────────────────
$daily_limit      = (int)get_setting('daily_withdrawal_limit', 1);
$platform_fee_pct = 0.16;

global $pdo;
$db = $pdo;

// ── FIX 4: Rate limiting ──────────────────────────────────────────────────
// Prevents automated scripts from hammering the endpoint.
// Allows max 3 attempts per user per 60 seconds.
// Uses the withdrawals table so no extra table is needed.
$stmt = $db->prepare("
    SELECT COUNT(*) FROM withdrawals
    WHERE user_id = ? AND requested_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
");
$stmt->execute([$user_id]);
$recentAttempts = (int)$stmt->fetchColumn();

if ($recentAttempts >= 3) {
    echo json_encode(['success' => false, 'message' => 'Too many withdrawal attempts. Please wait a moment before trying again.']);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────

// ── FIX 2: CRAW mode enforced SERVER-SIDE ────────────────────────────────
// The old code only checked CRAW in JavaScript. Any user could open
// DevTools, set CRAW_MODE=false and bypass the task requirement entirely.
// This server-side check cannot be bypassed from the browser.
$crawMode = (int)($user['craw_mode']            ?? 0);
$crawDone = (int)($user['craw_completed_today'] ?? 0);

if ($crawMode === 1 && $crawDone !== 1) {
    echo json_encode(['success' => false, 'message' => 'You must complete your withdrawal tasks before withdrawing. Please visit the Tasks page.']);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────
// 3. Validations
// ─────────────────────────────────────────────
if ($amount < $min_withdrawal) {
    echo json_encode(['success' => false, 'message' => "Minimum withdrawal is {$min_withdrawal} USDT"]);
    exit;
}

$user_balance = (float)$user['balance'];
if ($amount > $user_balance) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance. Your available balance is ' . number_format($user_balance, 2) . ' USDT.']);
    exit;
}

$address_error = validate_address_for_network($destination, $network_in);
if ($address_error) {
    echo json_encode(['success' => false, 'message' => $address_error]);
    exit;
}

// ── FIX 6: Date-based daily limit reset ──────────────────────────────────
// The old code used a raw daily_withdrawal_count that was never reliably
// reset at midnight — if you forgot to run a cron job the counter would
// accumulate and permanently block users.
// We now compare last_withdrawal_date (Y-m-d) against today's date.
// If they differ, the counter is reset for the new day before checking.
$today = date('Y-m-d');
$lastWithdrawalDate = $user['last_withdrawal_date'] ?? '';

if ($lastWithdrawalDate !== $today) {
    // New day — reset count atomically
    $db->prepare("
        UPDATE users
        SET daily_withdrawal_count = 0,
            last_withdrawal_date   = ?
        WHERE id = ?
    ")->execute([$today, $user_id]);

    // Re-fetch so the checks below see the reset value
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ((int)$user['daily_withdrawal_count'] >= $daily_limit) {
    echo json_encode(['success' => false, 'message' => 'You have already made a withdrawal today. Your limit resets at midnight UTC.']);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────

$platform_fee = round($amount * $platform_fee_pct, 2);
$net_to_send  = round($amount - $platform_fee, 2);

if ($net_to_send <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount too low after the 16% platform fee is deducted.']);
    exit;
}

// ─────────────────────────────────────────────
// 4. Deduct balance & create withdrawal record
// ── FIX 3: FOR UPDATE row lock ───────────────
// Without this lock, two simultaneous requests can both pass the
// balance >= amount check before either deducts — allowing a user
// to withdraw the same funds twice (double-spend).
// SELECT ... FOR UPDATE acquires an exclusive row lock so only one
// transaction can proceed at a time. The second request will wait,
// then see the already-deducted balance and fail cleanly.
// ─────────────────────────────────────────────
$db->beginTransaction();

try {
    // Lock the user row exclusively for this transaction
    $lockStmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $lockStmt->execute([$user_id]);
    $lockedRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

    // Re-check balance inside the lock — the values may have changed
    // between the initial validation above and now.
    if (!$lockedRow || (float)$lockedRow['balance'] < $amount) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Insufficient balance. Please refresh and try again.']);
        exit;
    }

    // Positional params: [amount_to_deduct, today, user_id, balance_check]
    $stmt = $db->prepare("
        UPDATE users
        SET balance                = balance - ?,
            daily_withdrawal_count = daily_withdrawal_count + 1,
            last_withdrawal_date   = ?
        WHERE id      = ?
          AND balance >= ?
    ");
    $stmt->execute([$amount, $today, $user_id, $amount]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Balance update failed — your balance may have changed. Please refresh and try again.');
    }

    $stmt2 = $db->prepare("
        INSERT INTO withdrawals
            (user_id, amount, fee, net_amount, currency, network,
             destination_address, status, requested_at)
        VALUES
            (:user_id, :amount, :fee, :net_amount, :currency, :network,
             :address, 'processing', NOW())
    ");
    $stmt2->execute([
        ':user_id'    => $user_id,
        ':amount'     => $amount,
        ':fee'        => $platform_fee,
        ':net_amount' => $net_to_send,
        ':currency'   => $currency,
        ':network'    => $network_in,
        ':address'    => $destination,
    ]);

    $withdrawal_id = $db->lastInsertId();
    $db->commit();

} catch (Exception $e) {
    $db->rollBack();
    error_log("Withdrawal DB error (user {$user_id}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// ─────────────────────────────────────────────
// 5. Call OxaPay Payout API
// ─────────────────────────────────────────────
$payout_payload = [
    'address'     => $destination,
    'amount'      => $net_to_send,
    'currency'    => $currency,
    'network'     => $oxapay_network,
    'description' => "Withdrawal #{$withdrawal_id} for user #{$user_id}",
];

$headers = [
    'Content-Type: application/json',
    'payout_api_key: ' . $OXAPAY_PAYOUT_API_KEY,
];

$ch = curl_init($OXAPAY_PAYOUT_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payout_payload),
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$raw_response = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error   = curl_error($ch);
curl_close($ch);

error_log("OxaPay payout request  (withdrawal #{$withdrawal_id}): " . json_encode($payout_payload));
error_log("OxaPay payout response (withdrawal #{$withdrawal_id}, HTTP {$http_code}): " . $raw_response);

// ─────────────────────────────────────────────
// 6. Handle OxaPay response
// ─────────────────────────────────────────────
if ($curl_error) {
    refund_withdrawal($db, $withdrawal_id, $user_id, $amount);
    error_log("OxaPay cURL error (withdrawal #{$withdrawal_id}): {$curl_error}");
    echo json_encode(['success' => false, 'message' => 'Could not reach the payment gateway. Your balance has been refunded. Please try again.']);
    exit;
}

$oxapay_data = json_decode($raw_response, true);

$http_success   = ($http_code === 200);
$msg_success    = (stripos($oxapay_data['message'] ?? '', 'successfully') !== false);
$legacy_success = ((int)($oxapay_data['result']   ?? 0) === 100);
$oxapay_success = ($http_success && $msg_success) || $legacy_success;

$result_code = (int)($oxapay_data['result'] ?? $oxapay_data['status'] ?? 0);

if ($oxapay_success) {
    $data_block    = $oxapay_data['data'] ?? $oxapay_data;
    $track_id      = $data_block['track_id'] ?? $data_block['trackId'] ?? null;
    $txid          = $data_block['txid']     ?? $data_block['txID']    ?? null;
    $gateway_fee   = (float)($data_block['fee'] ?? 0);

    $internal_status = 'completed';
    $success_note    = "OxaPay accepted: track_id={$track_id}, gateway_status=" . ($data_block['status'] ?? 'processing');

    $stmt3 = $db->prepare("
        UPDATE withdrawals
        SET status       = :status,
            fee          = :fee,
            net_amount   = :net_amount,
            txid         = :txid,
            track_id     = :track_id,
            note         = :note,
            processed_at = NOW(),
            completed_at = NOW()
        WHERE id = :id
    ");
    $stmt3->execute([
        ':status'     => $internal_status,
        ':fee'        => $platform_fee + $gateway_fee,
        ':net_amount' => $net_to_send - $gateway_fee,
        ':txid'       => $txid,
        ':track_id'   => $track_id,
        ':note'       => $success_note,
        ':id'         => $withdrawal_id,
    ]);

    log_activity($user_id, 'withdrawal_submitted',
        "Withdrawal #{$withdrawal_id}: {$amount} {$currency} to {$destination} via {$network_in}");

    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal submitted successfully! Funds will arrive within a few minutes.',
        'data'    => [
            'withdrawal_id' => $withdrawal_id,
            'amount'        => $amount,
            'platform_fee'  => $platform_fee,
            'net_amount'    => round($net_to_send - $gateway_fee, 2),
            'currency'      => $currency,
            'network'       => $network_in,
            'status'        => 'completed',
            'txid'          => $txid,
            'track_id'      => $track_id,
        ],
    ]);

} else {
    $raw_msg       = $oxapay_data['message'] ?? '';
    $error_key     = $oxapay_data['error']['key'] ?? '';
    $error_message = oxapay_error_message($result_code, $raw_msg, $error_key);

    refund_withdrawal($db, $withdrawal_id, $user_id, $amount);

    $stmt4 = $db->prepare("UPDATE withdrawals SET status='failed', note=:note WHERE id=:id");
    $stmt4->execute([':note' => "OxaPay rejected: http={$http_code}, result={$result_code}, msg={$raw_msg}", ':id' => $withdrawal_id]);

    error_log("OxaPay payout failed (withdrawal #{$withdrawal_id}): result={$result_code}, msg={$raw_msg}");

    echo json_encode(['success' => false, 'message' => $error_message]);
}
?>

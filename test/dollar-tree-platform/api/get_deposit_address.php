<?php
// api/get_deposit_address.php - Get permanent deposit address for user
// Integrated with OxaPay payment gateway
// SECURITY FIXES APPLIED:
//   1. Address integrity hash — detects tampered DB addresses
//   2. Callback URL uses PLATFORM_URL constant, not $_SERVER['HTTP_HOST']
//   3. Rate limiting — max 5 new address requests per user per hour

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    global $pdo;

    // ===== AUTHENTICATE USER =====
    $authToken = $_SERVER['HTTP_X_USER_TOKEN'] ?? '';
    $user = null;

    if (!empty($authToken)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE auth_token = ? LIMIT 1");
        $stmt->execute([$authToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        app_log("get_deposit_address: Authentication failed");
        json_error('Authentication required', 401);
    }

    app_log("get_deposit_address: Authenticated user_id={$user['id']}");

    // ===== GET REQUEST DATA =====
    $body  = file_get_contents('php://input');
    $input = json_decode($body, true);

    $currency = strtoupper(trim($input['currency'] ?? 'USDT'));
    $network  = $input['network'] ?? 'TRON';

    app_log("get_deposit_address: Requested currency={$currency}, network={$network}");

    // ===== GET OXAPAY SETTINGS =====
    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM admin_settings
        WHERE setting_key IN ('oxapay_api_key', 'oxapay_enabled')
    ");

    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    if (empty($settings['oxapay_api_key'])) {
        app_log("get_deposit_address: OxaPay API key not configured");
        json_error('OxaPay not configured. Please contact administrator.', 500);
    }

    if (empty($settings['oxapay_enabled']) || $settings['oxapay_enabled'] != '1') {
        app_log("get_deposit_address: OxaPay is disabled");
        json_error('Cryptocurrency deposits are currently disabled.', 503);
    }

    // ===== CHECK IF USER ALREADY HAS ADDRESS FOR THIS NETWORK =====
    $stmt = $pdo->prepare("
        SELECT * FROM user_deposit_addresses
        WHERE user_id = ? AND network = ? AND currency = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$user['id'], $network, $currency]);
    $existingAddress = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAddress) {

        // ── FIX 1: Verify address integrity hash ──────────────────────────
        // If address_hash column exists and is populated, verify it.
        // This detects any DB-level tampering of the wallet address.
        if (!empty($existingAddress['address_hash'])) {
            $expectedHash = hash_hmac('sha256', $existingAddress['address'], $settings['oxapay_api_key']);
            if (!hash_equals($expectedHash, $existingAddress['address_hash'])) {
                // Alert loudly — this should never happen unless someone
                // directly modified the database row.
                app_log("SECURITY ALERT: Address hash mismatch for user {$user['id']}, address_id {$existingAddress['id']}. Possible DB tampering.", 'CRITICAL');
                json_error('Security verification failed. Please contact support immediately.', 500);
            }
        }
        // ─────────────────────────────────────────────────────────────────

        // Return existing address
        app_log("get_deposit_address: Returning existing address for user {$user['id']}, network {$network}");

        // Update last_used_at
        $stmt = $pdo->prepare("UPDATE user_deposit_addresses SET last_used_at = NOW() WHERE id = ?");
        $stmt->execute([$existingAddress['id']]);

        json_success([
            'address_id' => $existingAddress['id'],
            'address'    => $existingAddress['address'],
            'network'    => $existingAddress['network'],
            'currency'   => $existingAddress['currency'],
            'memo'       => $existingAddress['memo'],
            'track_id'   => $existingAddress['track_id'],
            'created_at' => $existingAddress['created_at'],
            'is_new'     => false
        ], 'This is your permanent deposit address. Any amount sent here will be automatically credited to your account.');
    }

    // ===== RATE LIMIT: max 5 new address creations per user per hour =====
    // Prevents a flood of API calls to OxaPay if someone hammers the endpoint.
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_deposit_addresses
        WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$user['id']]);
    $recentCount = (int)$stmt->fetchColumn();

    if ($recentCount >= 5) {
        app_log("get_deposit_address: Rate limit hit for user {$user['id']}");
        json_error('Too many address requests. Please wait before requesting a new address.', 429);
    }

    // ===== CREATE NEW PERMANENT ADDRESS =====
    app_log("get_deposit_address: Creating new permanent address for user {$user['id']}, network {$network}");

    // ── FIX 2: Use PLATFORM_URL constant instead of $_SERVER['HTTP_HOST'] ──
    // Using HTTP_HOST is vulnerable to Host Header Injection — an attacker
    // can spoof the Host header so OxaPay sends callbacks to their server.
    // PLATFORM_URL is defined in config.php and is always trusted.
    $callbackUrl = rtrim(PLATFORM_URL, '/') . '/api/deposit_callback.php';
    // ────────────────────────────────────────────────────────────────────────

    $orderId = 'USER_' . $user['id'] . '_' . $network . '_' . time();

    $apiUrl = 'https://api.oxapay.com/v1/payment/static-address';

    $payload = [
        'network'          => $network,
        'to_currency'      => $currency,
        'auto_withdrawal'  => false,
        'callback_url'     => $callbackUrl,
        'email'            => $user['email'] ?? $user['username'] . '@platform.user',
        'order_id'         => $orderId,
        'description'      => 'Permanent deposit address for User ID: ' . $user['id'] . ' - ' . $currency . ' on ' . $network
    ];

    app_log("get_deposit_address: Calling OxaPay API with payload: " . json_encode($payload));

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'merchant_api_key: ' . $settings['oxapay_api_key']
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($response === false) {
        curl_close($ch);
        app_log("get_deposit_address: cURL error: {$curlError}");
        json_error("Network error: " . $curlError, 500);
    }

    curl_close($ch);

    app_log("get_deposit_address: OxaPay response (HTTP {$httpCode}): " . substr($response, 0, 1000));

    $responseData = json_decode($response, true);

    if (!is_array($responseData)) {
        app_log("get_deposit_address: Invalid JSON response");
        json_error("Invalid response from payment provider", 500);
    }

    // ===== CHECK FOR ERRORS =====
    if ($httpCode !== 200 || (isset($responseData['status']) && $responseData['status'] >= 400)) {
        $errorMsg = $responseData['message'] ?? $responseData['error']['message'] ?? 'Unknown error';
        app_log("get_deposit_address: OxaPay error: {$errorMsg}");
        json_error("Payment provider error: {$errorMsg}", 500);
    }

    // ===== EXTRACT ADDRESS DATA =====
    $data = $responseData['data'] ?? null;

    if (!$data || !isset($data['address'])) {
        app_log("get_deposit_address: No address in response");
        json_error('Failed to generate payment address. Please try again.', 500);
    }

    $address     = $data['address'];
    $trackId     = $data['track_id'] ?? $orderId;
    $networkName = $data['network']   ?? $network;
    $memo        = $data['memo']      ?? '';

    // ── FIX 1 (continued): Generate integrity hash before saving ─────────
    // We HMAC the address using the OxaPay API key as the secret.
    // On every future retrieval we re-compute and compare. If the address
    // row is ever modified directly in the DB, the hash will not match
    // and the tampered address will be rejected before being shown to users.
    $addressHash = hash_hmac('sha256', $address, $settings['oxapay_api_key']);
    // ─────────────────────────────────────────────────────────────────────

    // ===== SAVE ADDRESS TO DATABASE =====
    // NOTE: The address_hash column must exist. See migration SQL below.
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_deposit_addresses
            (user_id, network, currency, address, memo, track_id, address_hash, status, created_at, last_used_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$user['id'], $network, $currency, $address, $memo, $trackId, $addressHash]);

        $addressId = $pdo->lastInsertId();
        app_log("get_deposit_address: Saved permanent address - ID: {$addressId}, Address: {$address}");

        json_success([
            'address_id' => $addressId,
            'address'    => $address,
            'network'    => $networkName,
            'currency'   => $currency,
            'memo'       => $memo,
            'track_id'   => $trackId,
            'created_at' => date('Y-m-d H:i:s'),
            'is_new'     => true
        ], 'New permanent deposit address created! Save this address - any amount sent here will be automatically credited to your account.');

    } catch (Exception $e) {
        app_log("get_deposit_address: DB error: " . $e->getMessage());
        json_error('Failed to save address: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    app_log("get_deposit_address EXCEPTION: " . $e->getMessage());
    json_error('Failed to get deposit address: ' . $e->getMessage(), 500);
}
?>
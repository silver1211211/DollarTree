<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/oxapay_helpers.php';

$baseUrl = 'http://127.0.0.1:8000/api/deposit_callback.php';
$dummyKey = 'local-test-' . bin2hex(random_bytes(24));
$marker = bin2hex(random_bytes(6));
$username = 'oxapay_test_' . $marker;
$address = 'TEST_ADDRESS_' . $marker;
$trackId = 'TEST_TRACK_' . $marker;
$orderId = 'USER_PENDING_TRON_' . time();
$userId = 0;
$addressId = 0;
$originalKey = (string)get_setting('oxapay_api_key', '');
$originalMinimum = (string)get_setting('min_deposit_amount', '2');

function postWebhook(string $url, array $payload, string $key): array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'HMAC: ' . hash_hmac('sha512', $body, $key),
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($response === false) {
        throw new RuntimeException($error);
    }
    return ['status' => $status, 'body' => $response];
}

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("{$label}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
    echo "PASS: {$label}\n";
}

try {
    $pdo->prepare("UPDATE admin_settings SET setting_value=? WHERE setting_key='oxapay_api_key'")->execute([$dummyKey]);
    $pdo->prepare("UPDATE admin_settings SET setting_value='2' WHERE setting_key='min_deposit_amount'")->execute();
    $pdo->prepare("INSERT INTO users (username,email,password_hash,referral_code,account_status,total_deposited) VALUES (?,?,?,?, 'active',0)")
        ->execute([$username, $username . '@example.test', password_hash('local-test-only', PASSWORD_DEFAULT), 'T' . strtoupper($marker)]);
    $userId = (int)$pdo->lastInsertId();
    $orderId = "USER_{$userId}_TRON_" . time();
    $pdo->prepare("INSERT INTO user_deposit_addresses (user_id,network,currency,address,track_id,status) VALUES (?,'TRON','USDT',?,?,'active')")
        ->execute([$userId, $address, $trackId]);
    $addressId = (int)$pdo->lastInsertId();

    $base = [
        'track_id' => $trackId,
        'type' => 'static_address',
        'currency' => 'USDT',
        'order_id' => $orderId,
    ];

    $paying = $base + ['status' => 'Paying', 'txs' => [[
        'status' => 'confirming', 'tx_hash' => 'PAYING_' . $marker,
        'received_amount' => 1.96, 'currency' => 'USDT',
        'network' => 'Tron Network', 'address' => $address,
    ]]];
    $result = postWebhook($baseUrl, $paying, $dummyKey);
    assertSameValue(200, $result['status'], 'Paying callback acknowledged');
    assertSameValue('ok', $result['body'], 'Paying callback response body');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM pending_deposits WHERE user_id={$userId}")->fetchColumn();
    assertSameValue(0, $count, 'Paying creates no deposit or notification record');

    $below = $base + ['status' => 'Paid', 'txs' => [[
        'status' => 'confirmed', 'tx_hash' => 'BELOW_' . $marker,
        'received_amount' => 0.98, 'currency' => 'USDT',
        'network' => 'Tron Network', 'address' => $address,
    ]]];
    $result = postWebhook($baseUrl, $below, $dummyKey);
    assertSameValue(200, $result['status'], 'Below-minimum Paid callback acknowledged');
    $row = $pdo->query("SELECT status,credited_amount FROM pending_deposits WHERE tx_hash='BELOW_{$marker}'")->fetch(PDO::FETCH_ASSOC);
    assertSameValue('below_minimum', $row['status'], '1 USDT adjusted amount is below minimum');
    assertSameValue('0.00000000', $row['credited_amount'], 'Below-minimum deposit credits zero');

    $paid = $base + ['status' => 'Paid', 'txs' => [[
        'status' => 'confirmed', 'tx_hash' => 'PAID_' . $marker,
        'received_amount' => 1.96, 'currency' => 'USDT',
        'network' => 'Tron Network', 'address' => $address,
    ]]];
    $result = postWebhook($baseUrl, $paid, $dummyKey);
    assertSameValue(200, $result['status'], 'Eligible Paid callback acknowledged');
    $total = (float)$pdo->query("SELECT total_deposited FROM users WHERE id={$userId}")->fetchColumn();
    assertSameValue(2.0, $total, '1.96 received is credited as 2.00 using division by 0.98');

    postWebhook($baseUrl, $paid, $dummyKey);
    $total = (float)$pdo->query("SELECT total_deposited FROM users WHERE id={$userId}")->fetchColumn();
    assertSameValue(2.0, $total, 'Duplicate Paid callback does not double-credit');

    $pdo->prepare("INSERT INTO crypto_conversion_rates(symbol,usdt_rate,source,fetched_at) VALUES ('TEST',2000,'local_test',NOW()) AS new ON DUPLICATE KEY UPDATE usdt_rate=new.usdt_rate,source=new.source,fetched_at=new.fetched_at")
        ->execute();
    $converted = $base + ['status' => 'Paid', 'txs' => [[
        'status' => 'confirmed', 'tx_hash' => 'CONVERTED_' . $marker,
        'received_amount' => 0.00098, 'currency' => 'TEST',
        'network' => 'Tron Network', 'address' => $address,
    ]]];
    $result = postWebhook($baseUrl, $converted, $dummyKey);
    assertSameValue(200, $result['status'], 'Cached-rate conversion callback acknowledged');
    $total = (float)$pdo->query("SELECT total_deposited FROM users WHERE id={$userId}")->fetchColumn();
    assertSameValue(4.0, $total, 'Cached rate converts coin to 1.96 USDT then credits 2.00');

    $providerConverted = $base + ['status' => 'Paid', 'txs' => [[
        'status' => 'confirmed', 'tx_hash' => 'PROVIDER_' . $marker,
        'received_amount' => 0.00001, 'currency' => 'BTC',
        'auto_convert_amount' => 1.96, 'auto_convert_currency' => 'USDT',
        'network' => 'Bitcoin Network', 'address' => $address,
    ]]];
    postWebhook($baseUrl, $providerConverted, $dummyKey);
    $total = (float)$pdo->query("SELECT total_deposited FROM users WHERE id={$userId}")->fetchColumn();
    assertSameValue(6.0, $total, 'OxaPay callback conversion amount takes priority over cached rates');

    echo "OXAPAY LOCAL E2E PASSED\n";
} finally {
    if ($userId > 0) {
        $pdo->prepare('DELETE FROM activity_logs WHERE user_id=?')->execute([$userId]);
        $pdo->prepare('DELETE FROM pending_deposits WHERE user_id=?')->execute([$userId]);
        $pdo->prepare('DELETE FROM user_deposit_addresses WHERE user_id=?')->execute([$userId]);
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
    }
    $pdo->prepare("UPDATE admin_settings SET setting_value=? WHERE setting_key='oxapay_api_key'")->execute([$originalKey]);
    $pdo->prepare("UPDATE admin_settings SET setting_value=? WHERE setting_key='min_deposit_amount'")->execute([$originalMinimum]);
    $pdo->exec("DELETE FROM crypto_conversion_rates WHERE symbol='TEST' AND source='local_test'");
}

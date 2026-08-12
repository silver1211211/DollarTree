<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function fetchJson(string $url): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        throw new RuntimeException($error !== '' ? $error : "HTTP {$status}");
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON response');
    }
    return $decoded;
}

function syncRates(PDO $pdo): int
{
    $response = fetchJson('https://api.oxapay.com/v1/common/prices');
    $prices = $response['data'] ?? [];
    if (!is_array($prices) || !$prices) {
        throw new RuntimeException('OxaPay returned no prices');
    }
    $usdtUsd = (float)($prices['USDT'] ?? 1);
    if ($usdtUsd <= 0) {
        $usdtUsd = 1;
    }
    $enabled = $pdo->query("SELECT DISTINCT symbol FROM deposit_asset_catalog WHERE provider_enabled=1 AND deposits_enabled=1")->fetchAll(PDO::FETCH_COLUMN);
    $upsert = $pdo->prepare("INSERT INTO crypto_conversion_rates (symbol,usdt_rate,source,fetched_at) VALUES (?,?,'oxapay',NOW()) AS new ON DUPLICATE KEY UPDATE usdt_rate=new.usdt_rate,source=new.source,fetched_at=new.fetched_at,updated_at=NOW()");
    $count = 0;
    foreach ($enabled as $symbol) {
        $symbol = strtoupper((string)$symbol);
        $priceUsd = $symbol === 'USDT' ? $usdtUsd : (float)($prices[$symbol] ?? 0);
        if ($priceUsd <= 0) {
            continue;
        }
        $upsert->execute([$symbol, $priceUsd / $usdtUsd]);
        $count++;
    }
    $heartbeat = $pdo->prepare("INSERT INTO worker_heartbeats (worker_name,worker_id,status,last_seen_at,safe_metadata) VALUES ('oxapay_rate_worker',?,'healthy',NOW(),?) AS new ON DUPLICATE KEY UPDATE worker_id=new.worker_id,status=new.status,last_seen_at=new.last_seen_at,safe_metadata=new.safe_metadata");
    $heartbeat->execute([gethostname() . ':' . getmypid(), json_encode(['rates_updated' => $count])]);
    return $count;
}

function syncAssetCatalog(PDO $pdo): int
{
    $response = fetchJson('https://api.oxapay.com/v1/common/currencies');
    $currencies = $response['data'] ?? [];
    if (!is_array($currencies) || !$currencies) {
        throw new RuntimeException('OxaPay returned no asset catalog');
    }
    $upsert = $pdo->prepare("INSERT INTO deposit_asset_catalog (canonical_code,symbol,asset_name,network_key,network_name,provider_network,provider_minimum,required_confirmations,provider_enabled,deposits_enabled,metadata_json,synced_at) VALUES (?,?,?,?,?,?,?,?,1,1,?,NOW()) AS new ON DUPLICATE KEY UPDATE symbol=new.symbol,asset_name=new.asset_name,network_key=new.network_key,network_name=new.network_name,provider_network=new.provider_network,provider_minimum=new.provider_minimum,required_confirmations=new.required_confirmations,provider_enabled=1,metadata_json=new.metadata_json,synced_at=NOW(),updated_at=NOW()");
    $count = 0;
    foreach ($currencies as $symbol => $currency) {
        if (empty($currency['status']) || !is_array($currency['networks'] ?? null)) {
            continue;
        }
        foreach ($currency['networks'] as $network) {
            $providerNetwork = (string)($network['network'] ?? '');
            if ($providerNetwork === '') {
                continue;
            }
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $symbol . '-' . $providerNetwork), '-'));
            $upsert->execute([
                $slug,
                strtoupper((string)$symbol),
                (string)($currency['name'] ?? $symbol),
                $providerNetwork,
                (string)($network['name'] ?? $providerNetwork),
                $providerNetwork,
                isset($network['deposit_min']) ? (float)$network['deposit_min'] : null,
                isset($network['required_confirmations']) ? (int)$network['required_confirmations'] : null,
                json_encode(['keys' => $network['keys'] ?? []]),
            ]);
            $count++;
        }
    }
    return $count;
}

$once = in_array('--once', $argv, true);
do {
    try {
        $count = syncRates($pdo);
        $assets = syncAssetCatalog($pdo);
        $heartbeat = $pdo->prepare("UPDATE worker_heartbeats SET safe_metadata=? WHERE worker_name='oxapay_rate_worker'");
        $heartbeat->execute([json_encode(['rates_updated' => $count, 'assets_synced' => $assets])]);
        echo '[' . date('c') . "] updated {$count} OxaPay rates and {$assets} asset/network pairs\n";
    } catch (Throwable $error) {
        $stmt = $pdo->prepare("INSERT INTO worker_heartbeats (worker_name,worker_id,status,last_seen_at,safe_metadata) VALUES ('oxapay_rate_worker',?,'degraded',NOW(),?) AS new ON DUPLICATE KEY UPDATE worker_id=new.worker_id,status=new.status,last_seen_at=new.last_seen_at,safe_metadata=new.safe_metadata");
        $stmt->execute([gethostname() . ':' . getmypid(), json_encode(['error' => substr($error->getMessage(), 0, 180)])]);
        echo '[' . date('c') . '] rate sync failed: ' . $error->getMessage() . "\n";
    }
    if (!$once) {
        sleep(60);
    }
} while (!$once);

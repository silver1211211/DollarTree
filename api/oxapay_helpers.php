<?php

declare(strict_types=1);

function oxapay_usdt_networks(): array
{
    return [
        'TRON'    => ['api' => 'Tron',             'label' => 'TRC20'],
        'BSC'     => ['api' => 'BSC',              'label' => 'BEP20'],
        'ETH'     => ['api' => 'Ethereum',         'label' => 'ERC20'],
        'POLYGON' => ['api' => 'Polygon',          'label' => 'POLYGON'],
        'TON'     => ['api' => 'The Open Network', 'label' => 'TON'],
    ];
}

function oxapay_normalize_usdt_network(string $network): ?array
{
    $value = strtoupper(trim($network));
    $aliases = [
        'TRON' => 'TRON', 'TRC20' => 'TRON', 'TRON NETWORK' => 'TRON',
        'BSC' => 'BSC', 'BEP20' => 'BSC', 'BINANCE SMART CHAIN' => 'BSC',
        'ETH' => 'ETH', 'ERC20' => 'ETH', 'ETHEREUM' => 'ETH', 'ETHEREUM NETWORK' => 'ETH',
        'POLYGON' => 'POLYGON', 'POL' => 'POLYGON', 'POLYGON NETWORK' => 'POLYGON',
        'TON' => 'TON', 'THE OPEN NETWORK' => 'TON', 'TON NETWORK' => 'TON',
    ];
    $code = $aliases[$value] ?? null;
    if ($code === null) {
        return null;
    }
    $networkData = oxapay_usdt_networks()[$code];
    return ['code' => $code] + $networkData;
}

function oxapay_verify_webhook(string $rawBody, string $receivedHmac, string $merchantKey): bool
{
    if ($rawBody === '' || $receivedHmac === '' || $merchantKey === '') {
        return false;
    }
    return hash_equals(hash_hmac('sha512', $rawBody, $merchantKey), trim($receivedHmac));
}

function oxapay_credit_amount(float $receivedAmount): float
{
    return $receivedAmount > 0 ? round($receivedAmount / 0.98, 8) : 0.0;
}


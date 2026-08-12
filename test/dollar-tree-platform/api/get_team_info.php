<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');
$user = get_logged_in_user();
$uid = $user['id'];

try {
    // ── LEVEL 1 (direct referrals) ──
    $s = $pdo->prepare("SELECT id, created_at FROM users WHERE referrer_id = ?");
    $s->execute([$uid]);
    $l1_users = $s->fetchAll();
    $l1_ids = array_column($l1_users, 'id');

    // ── LEVEL 2 ──
    $l2_ids = [];
    if ($l1_ids) {
        $in = implode(',', array_fill(0, count($l1_ids), '?'));
        $s = $pdo->prepare("SELECT id FROM users WHERE referrer_id IN ($in)");
        $s->execute($l1_ids);
        $l2_ids = array_column($s->fetchAll(), 'id');
    }

    // ── LEVEL 3 ──
    $l3_ids = [];
    if ($l2_ids) {
        $in = implode(',', array_fill(0, count($l2_ids), '?'));
        $s = $pdo->prepare("SELECT id FROM users WHERE referrer_id IN ($in)");
        $s->execute($l2_ids);
        $l3_ids = array_column($s->fetchAll(), 'id');
    }

    function count_valid($pdo, $ids) {
        if (!$ids) return 0;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id IN ($in) AND total_deposited > 0");
        $s->execute($ids);
        return (int)$s->fetchColumn();
    }

  function sum_recharge($pdo, $ids) {
    if (!$ids) return 0;
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = $pdo->prepare("SELECT COALESCE(SUM(total_deposited),0) FROM users WHERE id IN ($in)");
    $s->execute($ids);
    return (float)$s->fetchColumn();
}

function sum_withdrawal($pdo, $ids) {
    if (!$ids) return 0;
    $in = implode(',', array_fill(0, count($ids), '?'));
    $s = $pdo->prepare("SELECT COALESCE(SUM(total_withdrawn),0) FROM users WHERE id IN ($in)");
    $s->execute($ids);
    return (float)$s->fetchColumn();
}
    // Uses YOUR actual column names: referrer_user_id, commission_level, commission_amount
    function sum_commission($pdo, $referrer_id, $level) {
        $s = $pdo->prepare("
            SELECT COALESCE(SUM(commission_amount), 0) 
            FROM commissions 
            WHERE referrer_user_id = ? AND commission_level = ? AND status = 'paid'
        ");
        $s->execute([$referrer_id, $level]);
        return (float)$s->fetchColumn();
    }

    $all_ids = array_merge($l1_ids, $l2_ids, $l3_ids);

    // New team today
    $today_new = 0;
    foreach ($l1_users as $u) {
        if (date('Y-m-d', strtotime($u['created_at'])) === date('Y-m-d')) $today_new++;
    }

    // First-time recharged
    $first_recharge = 0;
    if ($l1_ids) {
        $in = implode(',', array_fill(0, count($l1_ids), '?'));
        $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id IN ($in) AND total_deposited > 0");
        $s->execute($l1_ids);
        $first_recharge = (int)$s->fetchColumn();
    }

    // First-time withdrawn
    $first_withdrawal = 0;
    if ($l1_ids) {
        $in = implode(',', array_fill(0, count($l1_ids), '?'));
        $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id IN ($in) AND total_withdrawn > 0");
        $s->execute($l1_ids);
        $first_withdrawal = (int)$s->fetchColumn();
    }

    json_success([
        'team_size'        => count($all_ids),
        'team_recharge'    => sum_recharge($pdo, $all_ids),
        'team_withdrawal'  => sum_withdrawal($pdo, $all_ids),
        'new_team'         => $today_new,
        'first_recharge'   => $first_recharge,
        'first_withdrawal' => $first_withdrawal,
        'level1' => [
            'registered' => count($l1_ids),
            'valid'      => count_valid($pdo, $l1_ids),
            'income'     => sum_commission($pdo, $uid, 1),
        ],
        'level2' => [
            'registered' => count($l2_ids),
            'valid'      => count_valid($pdo, $l2_ids),
            'income'     => sum_commission($pdo, $uid, 2),
        ],
        'level3' => [
            'registered' => count($l3_ids),
            'valid'      => count_valid($pdo, $l3_ids),
            'income'     => sum_commission($pdo, $uid, 3),
        ],
    ]);

}  catch (Exception $e) {
    app_log("get_team_info error: " . $e->getMessage(), 'ERROR');
    json_error('Failed: ' . $e->getMessage());
}
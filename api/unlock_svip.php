<?php
// api/unlock_svip.php - Unlock SVIP level (manual unlock)
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// ─────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS — declared FIRST so PHP can find them when called below
// ─────────────────────────────────────────────────────────────────────────────

function perform_svip_unlock(PDO $pdo, int $user_id, array $user, array $tier, int $level): array
{
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . (int)$tier['contract_duration_days'] . ' days'));

    $pdo->beginTransaction();
    try {
        // 1. Deduct balance and upgrade SVIP level
        $stmt = $pdo->prepare("
            UPDATE users
            SET total_deposited   = total_deposited - ?,
                svip_level        = ?,
                svip_expires_at   = ?,
                svip_activated_at = NOW(),
                svip_unlock_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([
            (float)$tier['unlock_amount'],
            $level,
            $expires_at,
            (float)$tier['unlock_amount'],
            $user_id
        ]);

        // 2. Distribute referral commissions
        distribute_referral_commissions($user_id, (float)$tier['unlock_amount'], 'svip_unlock');

        // 3. Award 1 free spin to the referrer if this user unlocks SVIP 3 or above
        if ($level >= 3 && !empty($user['referrer_id'])) {
            $pdo->prepare("
                UPDATE users
                SET spin_chances = spin_chances + 1
                WHERE id = ?
            ")->execute([$user['referrer_id']]);

            log_activity(
                (int)$user['referrer_id'],
                'spin_awarded',
                "1 free spin awarded: referred user ID {$user_id} unlocked SVIP {$level}"
            );
            app_log("✅ Spin chance awarded to referrer ID {$user['referrer_id']} — user {$user_id} unlocked SVIP {$level}");
        }

        // 4. Log to activity_logs
        log_activity($user_id, 'svip_unlock', "Unlocked SVIP {$level} for {$tier['unlock_amount']} USDT");

        $pdo->commit();
        app_log("✅ User {$user_id} unlocked SVIP {$level}");

        return [
            'new_level'   => $level,
            'new_balance' => (float)$user['total_deposited'] - (float)$tier['unlock_amount'],
            'expires_at'  => $expires_at,
            'amount_paid' => (float)$tier['unlock_amount']
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function auto_upgrade_svip(PDO $pdo, int $user_id, float $deposited_amount): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return null;

    $stmt = $pdo->prepare("
        SELECT * FROM svip_tiers
        WHERE status = 'active'
          AND unlock_amount <= ?
          AND svip_level > ?
        ORDER BY svip_level DESC
        LIMIT 1
    ");
    $stmt->execute([$deposited_amount, (int)$user['svip_level']]);
    $best_tier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$best_tier) {
        app_log("ℹ️  No auto-upgrade for user {$user_id} — deposit {$deposited_amount} USDT doesn't qualify for a higher tier");
        return null;
    }

    $new_level = (int)$best_tier['svip_level'];
    app_log("🔄 Auto-upgrading user {$user_id} → SVIP {$new_level} (deposit: {$deposited_amount} USDT)");

    try {
        $result = perform_svip_unlock($pdo, $user_id, $user, $best_tier, $new_level);
        app_log("✅ Auto-upgrade complete: user {$user_id} → SVIP {$new_level}");
        return $result;
    } catch (Exception $e) {
        app_log("❌ Auto-upgrade failed for user {$user_id}: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN REQUEST HANDLER
// ─────────────────────────────────────────────────────────────────────────────

if (!is_logged_in()) {
    json_error('Please login first', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

global $pdo;

try {
    $user_id = (int)$_SESSION['user_id'];

    $raw_input       = file_get_contents('php://input');
    $input           = json_decode($raw_input, true);
    $requested_level = $input['svip_level'] ?? ($_POST['svip_level'] ?? null);

    if ($requested_level === null || !is_numeric($requested_level)) {
        json_error('Invalid SVIP level', 400);
    }

    $requested_level = (int)$requested_level;

    // Fresh user row (includes referrer_id needed for spin award)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_error('User not found', 404);
    }

    $current_level = (int)$user['svip_level'];
    $balance       = (float)$user['total_deposited'];

    // Get tier
    $stmt = $pdo->prepare("SELECT * FROM svip_tiers WHERE svip_level = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$requested_level]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tier) {
        json_error('SVIP tier not found or inactive', 404);
    }

    $unlock_amount = (float)$tier['unlock_amount'];

    // Already at this level or higher?
    if ($requested_level <= $current_level) {
        json_error('You already have this level or higher', 400);
    }

    // Insufficient balance?
    if ($balance < $unlock_amount) {
        $shortfall = number_format($unlock_amount - $balance, 2);
        json_error("Insufficient balance. You need {$shortfall} more USDT. Please recharge your account.", 400);
    }

    // All checks passed — unlock
    $result = perform_svip_unlock($pdo, $user_id, $user, $tier, $requested_level);

    json_success($result, "Successfully unlocked SVIP {$requested_level}!");

} catch (Exception $e) {
    app_log('SVIP unlock error: ' . $e->getMessage(), 'ERROR');
    json_error('Failed to unlock SVIP level: ' . $e->getMessage(), 500);
}
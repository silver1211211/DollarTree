<?php
// api/complete_task.php - Complete daily task
// Tasks reset at midnight (00:00:00) each day
// Earnings based on SVIP level

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// ===== AUTHENTICATE USER VIA SESSION =====
if (!is_logged_in()) {
    json_error('Authentication required. Please login.', 401);
}

try {
    global $pdo;
    
    $user_id = $_SESSION['user_id'];
    
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_error('User not found', 404);
    }
    
    // Reset daily tasks if new day
    $today = date('Y-m-d');
    if ($user['last_task_date'] !== $today) {
        $stmt = $pdo->prepare("UPDATE users SET daily_tasks_completed = 0, last_task_date = ? WHERE id = ?");
        $stmt->execute([$today, $user['id']]);
        $user['daily_tasks_completed'] = 0;
    }
    
    app_log("complete_task: User {$user['id']} attempting to complete task");
    
    // ===== CHECK SVIP LEVEL =====
    // Get SVIP tier - even level 0 should have tasks
    $svipTier = get_svip_tier($user['svip_level']);

    if (!$svipTier) {
        json_error('VIP tier configuration error. Please contact support.', 500);
    }

    // Only check expiration if user has a paid VIP level (> 0)
    if ($user['svip_level'] > 0 && $user['svip_expires_at'] && strtotime($user['svip_expires_at']) < time()) {
        json_error('Your VIP level has expired. Please recharge to continue.', 403);
    }
    
    $dailyTasksLimit = $svipTier['daily_tasks_limit'];
    $taskProfit = $svipTier['task_profit_per_completion'];
    $maxDailyProfit = $svipTier['max_daily_profit'];
    
    // Check if user has reached daily limit
    if ($user['daily_tasks_completed'] >= $dailyTasksLimit) {
        json_error('All tasks completed for today. Come back tomorrow!', 400);
    }
    
    // ===== GET OR CREATE TODAY'S TASK RECORD =====
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $resetTime = $tomorrow . ' 00:00:00'; // Next midnight
    
    $stmt = $pdo->prepare("
        SELECT * FROM daily_tasks 
        WHERE user_id = ? AND task_date = ?
        LIMIT 1
    ");
    $stmt->execute([$user['id'], $today]);
    $taskRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$taskRecord) {
        // Create new task record for today
        $stmt = $pdo->prepare("
            INSERT INTO daily_tasks 
            (user_id, task_date, svip_level, tasks_available, tasks_completed, earnings_today, max_daily_earnings, reset_time)
            VALUES (?, ?, ?, ?, 0, 0, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $today,
            $user['svip_level'],
            $dailyTasksLimit,
            $maxDailyProfit,
            $resetTime
        ]);
        
        $taskRecordId = $pdo->lastInsertId();
        
        // Fetch the new record
        $stmt = $pdo->prepare("SELECT * FROM daily_tasks WHERE id = ?");
        $stmt->execute([$taskRecordId]);
        $taskRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        app_log("Created new task record for user {$user['id']}, date {$today}");
    }
    
    // ===== VALIDATE TASK CAN BE COMPLETED =====
    
    // Check if tasks available in daily_tasks table
    if ($taskRecord['tasks_completed'] >= $taskRecord['tasks_available']) {
        json_error('All tasks completed for today. Come back tomorrow!', 400);
    }
    
    // Check if daily profit limit reached
    if ($taskRecord['earnings_today'] >= $taskRecord['max_daily_earnings']) {
        json_error('Daily earnings limit reached', 400);
    }
    
    // ===== COMPLETE TASK =====
    try {
        $pdo->beginTransaction();
        
        // Update task record in daily_tasks table
        $stmt = $pdo->prepare("
            UPDATE daily_tasks 
            SET tasks_completed = tasks_completed + 1,
                earnings_today = earnings_today + ?
            WHERE id = ?
        ");
        $stmt->execute([$taskProfit, $taskRecord['id']]);
        
        // Update user balance AND daily_tasks_completed
        $stmt = $pdo->prepare("
            UPDATE users 
            SET balance = balance + ?,
                daily_tasks_completed = daily_tasks_completed + 1,
                last_task_date = ?
            WHERE id = ?
        ");
        $stmt->execute([$taskProfit, $today, $user['id']]);
        
        // Record task completion
        $stmt = $pdo->prepare("
            INSERT INTO task_completions 
            (user_id, daily_task_id, task_type, earnings, completed_at)
            VALUES (?, ?, 'DollarTree items', ?, NOW())
        ");
        $stmt->execute([$user['id'], $taskRecord['id'], $taskProfit]);
        
        $completionId = $pdo->lastInsertId();
        
        // // Distribute referral commissions on task earnings
        // distribute_referral_commissions($user['id'], $taskProfit, 'task_earning');
        
        // Log activity
        log_activity($user['id'], 'task_completed', "Task completed: Earned {$taskProfit} USDT");
        
        $pdo->commit();
        
        app_log("✅ Task completed: User {$user['id']}, Earnings: {$taskProfit}");
        
        // Get updated info
        $newTasksCompleted = $taskRecord['tasks_completed'] + 1;
        $newEarningsToday = $taskRecord['earnings_today'] + $taskProfit;
        $tasksRemaining = $taskRecord['tasks_available'] - $newTasksCompleted;
        
        // Calculate time until reset
        $resetTimestamp = strtotime($taskRecord['reset_time']);
        $currentTimestamp = time();
        $secondsUntilReset = $resetTimestamp - $currentTimestamp;
        
        json_success([
            'completion_id' => $completionId,
            'earnings' => $taskProfit,
            'new_balance' => $user['balance'] + $taskProfit,
            'tasks_completed_today' => $newTasksCompleted,
            'tasks_remaining_today' => max(0, $tasksRemaining),
            'total_earnings_today' => $newEarningsToday,
            'max_daily_earnings' => $maxDailyProfit,
            'reset_time' => $taskRecord['reset_time'],
            'seconds_until_reset' => $secondsUntilReset,
            'completed_at' => date('Y-m-d H:i:s')
        ], 'Task completed! Earnings credited to your account.');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        app_log("Task completion error: " . $e->getMessage(), 'ERROR');
        json_error('Failed to complete task: ' . $e->getMessage(), 500);
    }
    
} catch (Exception $e) {
    app_log("complete_task EXCEPTION: " . $e->getMessage(), 'ERROR');
    json_error('Task completion failed: ' . $e->getMessage(), 500);
}
?>
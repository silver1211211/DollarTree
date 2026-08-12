<?php
// api/get_completed_tasks.php - Get completed tasks for today
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_error('Please login first', 401);
}

global $pdo;

try {
    $user_id = $_SESSION['user_id'];
    
    // Debug: log the query
    app_log("Fetching completed tasks for user {$user_id}");
    
    // Use the exact query that worked in your database
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            user_id, 
            task_type, 
            earnings, 
            completed_at, 
            DATE(completed_at) AS completion_date,
            DATE_FORMAT(completed_at, '%h:%i %p') as completed_time
        FROM task_completions 
        WHERE user_id = ? 
        ORDER BY completed_at DESC
    ");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: log how many tasks found
    app_log("Found " . count($tasks) . " completed tasks for user {$user_id} today");
    
    if (count($tasks) > 0) {
        app_log("Task details: " . json_encode($tasks));
    }
    
    // Format the response properly
    $formatted_tasks = [];
    foreach ($tasks as $task) {
        $formatted_tasks[] = [
            'id' => $task['id'],
            'task_type' => $task['task_type'],
            'earnings' => floatval($task['earnings']),
            'completed_at' => $task['completed_time'],
            'completion_date' => $task['completion_date'],
            'raw_timestamp' => $task['completed_at']
        ];
    }
    
    json_success($formatted_tasks, 'Completed tasks retrieved');
    
} catch (Exception $e) {
    app_log('Get completed tasks error: ' . $e->getMessage(), 'ERROR');
    json_error('Failed to load completed tasks: ' . $e->getMessage(), 500);
}
?>
<?php
require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM commisions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 commisions TABLE SCHEMA:\n";
    echo "====================\n\n";
    
    foreach ($columns as $col) {
        echo "• {$col['Field']} ({$col['Type']}) - {$col['Null']} - Default: {$col['Default']}\n";
    }
    
    echo "\n\n";
    
    // Also check ad_deliveries table
    $stmt = $pdo->query("SHOW COLUMNS FROM daily_tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 daily_tasks TABLE SCHEMA:\n";
    echo "================================\n\n";
    
    foreach ($columns as $col) {
        echo "• {$col['Field']} ({$col['Type']}) - {$col['Null']} - Default: {$col['Default']}\n";
    }
    
    
    
      echo "\n\n";
    
    // Also check ad_deliveries table
    $stmt = $pdo->query("SHOW COLUMNS FROM referral_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
     
    echo "📊 referral_settings TABLE SCHEMA:\n";
    echo "================================\n\n";
     
    foreach ($columns as $col) {
        echo "• {$col['Field']} ({$col['Type']}) - {$col['Null']} - Default: {$col['Default']}\n";
    }
    
     echo "\n\n";
    
    // Also check ad_deliveries table
    $stmt = $pdo->query("SHOW COLUMNS FROM task_completions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 task_completions TABLE SCHEMA:\n";
    echo "================================\n\n";
    
    foreach ($columns as $col) {
        echo "• {$col['Field']} ({$col['Type']}) - {$col['Null']} - Default: {$col['Default']}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

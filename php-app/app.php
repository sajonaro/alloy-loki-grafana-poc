<?php

// Simple PHP application that generates various types of logs
function writeLog($level, $message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    
    // Write to stdout (which Docker will capture)
    echo "[$timestamp] $level: $message$contextStr" . PHP_EOL;
    
    // Also write to error log for ERROR level
    if ($level === 'ERROR') {
        error_log("[$timestamp] $level: $message$contextStr");
    }
}

function simulateUserActivity() {
    $users = ['alice', 'bob', 'charlie', 'diana', 'eve'];
    $actions = ['login', 'logout', 'view_page', 'create_post', 'delete_post', 'update_profile'];
    $pages = ['/home', '/profile', '/dashboard', '/settings', '/posts', '/admin'];
    
    $user = $users[array_rand($users)];
    $action = $actions[array_rand($actions)];
    $page = $pages[array_rand($pages)];
    
    return [
        'user' => $user,
        'action' => $action,
        'page' => $page,
        'ip' => '192.168.1.' . rand(1, 254),
        'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)'
    ];
}

function simulateError() {
    $errors = [
        'Database connection failed',
        'Invalid user credentials',
        'File not found',
        'Permission denied',
        'Timeout occurred',
        'Memory limit exceeded'
    ];
    
    return $errors[array_rand($errors)];
}

writeLog('INFO', 'PHP logging application started');

$counter = 0;
while (true) {
    $counter++;
    
    // Generate different types of logs
    $logType = rand(1, 10);
    
    if ($logType <= 6) {
        // 60% - Normal user activity
        $activity = simulateUserActivity();
        writeLog('INFO', "User activity: {$activity['action']}", $activity);
    } elseif ($logType <= 8) {
        // 20% - Warning logs
        writeLog('WARNING', 'High memory usage detected', [
            'memory_usage' => rand(70, 90) . '%',
            'threshold' => '85%'
        ]);
    } elseif ($logType <= 9) {
        // 10% - Error logs
        $error = simulateError();
        writeLog('ERROR', $error, [
            'error_code' => rand(500, 599),
            'request_id' => uniqid()
        ]);
    } else {
        // 10% - Debug logs
        writeLog('DEBUG', 'Processing request', [
            'request_count' => $counter,
            'processing_time' => rand(10, 500) . 'ms'
        ]);
    }
    
    // Sleep for 2-5 seconds between logs
    sleep(rand(2, 5));
}
?>

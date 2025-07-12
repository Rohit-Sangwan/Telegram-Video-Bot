<?php
/**
 * Telegram Bot Configuration
 * Clean Production Version
 */

// Bot Configuration
define('BOT_TOKEN', '5380666957:AAHHRw2PZrvBo0wgEo32qbRJUSIPdVE5egw');
define('BOT_API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', 'https://fastme.cloud/bot/webhook.php');

// Settings
define('DELETE_AFTER_MINUTES', 30);

// File Paths
define('FILE_IDS_PATH', __DIR__ . '/data/file_ids.json');
define('PROGRESS_PATH', __DIR__ . '/data/progress.json');
define('LOGS_PATH', __DIR__ . '/data/logs.json');
define('DELETION_QUEUE_PATH', __DIR__ . '/data/deletion_queue.json');

// Admin Credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'Haryanvi@123');

// Timezone
date_default_timezone_set('UTC');

// Logging function
function logEvent($message, $type = 'info') {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'message' => $message
    ];
    
    $logs = [];
    if (file_exists(LOGS_PATH)) {
        $logs = json_decode(file_get_contents(LOGS_PATH), true) ?: [];
    }
    
    $logs[] = $log;
    
    // Keep only last 500 logs
    if (count($logs) > 500) {
        $logs = array_slice($logs, -500);
    }
    
    file_put_contents(LOGS_PATH, json_encode($logs, JSON_PRETTY_PRINT));
}

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Initialize data files if they don't exist
$dataFiles = [
    'file_ids.json' => '[]',
    'progress.json' => '[]',
    'logs.json' => '[]',
    'deletion_queue.json' => '[]'
];

foreach ($dataFiles as $file => $content) {
    $filePath = __DIR__ . '/data/' . $file;
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $content);
    }
}
?>

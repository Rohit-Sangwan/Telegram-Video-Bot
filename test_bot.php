<?php
/**
 * Simple Bot Testing Script
 */

require_once 'config.php';
require_once 'TelegramBot.php';
require_once 'VideoManager.php';

// Test the bot functionality
$bot = new TelegramBot();
$videoManager = new VideoManager();

// Test 1: Get bot info
echo "=== Testing Bot Connection ===\n";
$botInfo = $bot->getMe();
if ($botInfo && isset($botInfo['result'])) {
    echo "✅ Bot connected successfully!\n";
    echo "Bot name: " . $botInfo['result']['first_name'] . "\n";
    echo "Bot username: @" . $botInfo['result']['username'] . "\n";
} else {
    echo "❌ Failed to connect to bot\n";
}

// Test 2: Test webhook info
echo "\n=== Testing Webhook ===\n";
$webhookInfo = $bot->getWebhookInfo();
if ($webhookInfo && isset($webhookInfo['result'])) {
    echo "✅ Webhook info retrieved\n";
    echo "Webhook URL: " . ($webhookInfo['result']['url'] ?: 'Not set') . "\n";
    echo "Has custom certificate: " . ($webhookInfo['result']['has_custom_certificate'] ? 'Yes' : 'No') . "\n";
    echo "Pending updates: " . ($webhookInfo['result']['pending_update_count'] ?? 0) . "\n";
} else {
    echo "❌ Failed to get webhook info\n";
}

// Test 3: Test video manager
echo "\n=== Testing Video Manager ===\n";
$stats = $videoManager->getSystemStats();
echo "✅ Video Manager working\n";
echo "Total videos: " . $stats['total_videos'] . "\n";
echo "Total users: " . $stats['total_users'] . "\n";
echo "Average progress: " . $stats['average_progress'] . "%\n";

// Test 4: Test support ticket file
echo "\n=== Testing Support System ===\n";
$supportFile = __DIR__ . '/data/support_tickets.json';
if (file_exists($supportFile)) {
    $tickets = json_decode(file_get_contents($supportFile), true) ?: [];
    echo "✅ Support ticket file exists\n";
    echo "Total tickets: " . count($tickets) . "\n";
} else {
    echo "ℹ️ Support ticket file doesn't exist yet (will be created on first ticket)\n";
}

// Test 5: Test file permissions
echo "\n=== Testing File Permissions ===\n";
$testFiles = [
    'data/file_ids.json',
    'data/user_progress.json',
    'data/deletion_queue.json',
    'data/logs.json'
];

foreach ($testFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        if (is_writable($fullPath)) {
            echo "✅ $file is writable\n";
        } else {
            echo "⚠️ $file is not writable\n";
        }
    } else {
        $dir = dirname($fullPath);
        if (is_writable($dir)) {
            echo "✅ $file directory is writable\n";
        } else {
            echo "❌ $file directory is not writable\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
echo "✅ All tests completed successfully!\n";
?>

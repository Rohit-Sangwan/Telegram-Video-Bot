<?php
/**
 * Video Send Test Script
 * Diagnose video sending issues
 */

require_once 'config.php';
require_once 'TelegramBot.php';
require_once 'VideoManager.php';

// Initialize classes
$bot = new TelegramBot();
$videoManager = new VideoManager();

echo "=== Video Send Diagnostic Test ===\n\n";

// Test 1: Check if bot API is working
echo "Test 1: Bot API Connection\n";
$botInfo = $bot->getMe();
if ($botInfo && isset($botInfo['ok']) && $botInfo['ok']) {
    echo "✅ Bot API connection: OK\n";
    echo "Bot name: " . $botInfo['result']['first_name'] . "\n";
    echo "Bot username: @" . $botInfo['result']['username'] . "\n";
} else {
    echo "❌ Bot API connection: FAILED\n";
    echo "Error: " . json_encode($botInfo) . "\n";
}

echo "\n";

// Test 2: Check file IDs
echo "Test 2: Video File IDs\n";
$totalVideos = $videoManager->getTotalVideos();
echo "Total videos available: $totalVideos\n";

if ($totalVideos > 0) {
    echo "✅ Video file IDs: OK\n";
    
    // Get first video to test
    $firstVideo = $videoManager->getNextVideo(999999); // Use high user ID for testing
    if ($firstVideo) {
        echo "First video file_id: " . substr($firstVideo['file_id'], 0, 30) . "...\n";
    } else {
        echo "❌ Could not get first video\n";
    }
} else {
    echo "❌ No videos found in file_ids.json\n";
}

echo "\n";

// Test 3: Test sending video to admin (replace with your user ID)
echo "Test 3: Video Send Test\n";
$adminUserId = 1089928728; // Replace with your Telegram user ID

if ($totalVideos > 0) {
    $testVideo = $videoManager->getNextVideo($adminUserId);
    if ($testVideo) {
        echo "Attempting to send video to admin user ($adminUserId)...\n";
        
        $result = $bot->sendVideo($adminUserId, $testVideo['file_id'], $testVideo['index'], 1);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            echo "✅ Video send test: SUCCESS\n";
            echo "Message ID: " . $result['result']['message_id'] . "\n";
        } else {
            echo "❌ Video send test: FAILED\n";
            echo "Error details: " . json_encode($result) . "\n";
        }
    } else {
        echo "❌ Could not get test video\n";
    }
} else {
    echo "❌ No videos to test with\n";
}

echo "\n";

// Test 4: Check webhook URL
echo "Test 4: Webhook Status\n";
$webhookInfo = $bot->getWebhookInfo();
if ($webhookInfo && isset($webhookInfo['ok']) && $webhookInfo['ok']) {
    echo "✅ Webhook info retrieved\n";
    echo "Webhook URL: " . ($webhookInfo['result']['url'] ?? 'Not set') . "\n";
    echo "Pending updates: " . ($webhookInfo['result']['pending_update_count'] ?? 0) . "\n";
} else {
    echo "❌ Could not get webhook info\n";
}

echo "\n=== Diagnostic Complete ===\n";
?>

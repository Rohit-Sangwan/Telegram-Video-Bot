<?php
/**
 * Clean Setup Script
 * Production Version
 */

require_once 'config.php';
require_once 'TelegramBot.php';

echo "🚀 Setting up your Telegram Bot...\n";
echo "==================================\n\n";

$bot = new TelegramBot();

// Set webhook
echo "1. Setting webhook...\n";
$result = $bot->setWebhook(WEBHOOK_URL);
if ($result) {
    echo "✅ Webhook set successfully!\n";
} else {
    echo "❌ Failed to set webhook\n";
}

// Check bot info
echo "\n2. Checking bot info...\n";
$botInfo = $bot->getMe();
if ($botInfo) {
    echo "✅ Bot is working: @" . $botInfo['result']['username'] . "\n";
    echo "   Name: " . $botInfo['result']['first_name'] . "\n";
} else {
    echo "❌ Bot is not responding\n";
}

// Check webhook info
echo "\n3. Checking webhook status...\n";
$webhookInfo = $bot->getWebhookInfo();
if ($webhookInfo) {
    echo "✅ Webhook is active: " . $webhookInfo['result']['url'] . "\n";
    echo "   Pending updates: " . $webhookInfo['result']['pending_update_count'] . "\n";
} else {
    echo "❌ Webhook is not working\n";
}

echo "\n🎉 Setup complete!\n";
echo "==================\n";
echo "Your bot is ready to use!\n";
echo "Commands: /start, /next, /random, /stats, /reset, /help\n";
?>

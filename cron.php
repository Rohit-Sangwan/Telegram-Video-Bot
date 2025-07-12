<?php
/**
 * Enhanced Cron Job System
 * Production Version with Multiple Tasks
 */

require_once 'config.php';
require_once 'TelegramBot.php';
require_once 'VideoManager.php';

// Set execution time limit
set_time_limit(0);

// Start execution logging
$startTime = microtime(true);
logEvent("Cron job started at " . date('Y-m-d H:i:s'));

try {
    // Initialize classes
    $bot = new TelegramBot();
    $videoManager = new VideoManager();
    
    // Task 1: Process deletion queue
    logEvent("Starting deletion queue processing...");
    $bot->processDeletionQueue();
    logEvent("Deletion queue processed successfully");
    
    // Task 2: Clean up old logs (keep last 1000 entries)
    logEvent("Starting log cleanup...");
    $logsCleaned = cleanupOldLogs();
    logEvent("Log cleanup completed - $logsCleaned old entries removed");
    
    // Task 3: Update system statistics
    logEvent("Starting system statistics update...");
    $stats = updateSystemStats($videoManager);
    logEvent("System statistics updated - Total users: {$stats['total_users']}, Total videos: {$stats['total_videos']}");
    
    // Task 4: Clean up orphaned user progress
    logEvent("Starting progress cleanup...");
    $progressCleaned = cleanupOrphanedProgress();
    logEvent("Progress cleanup completed - $progressCleaned orphaned entries removed");
    
    // Task 5: Send daily statistics to admin (at 9 AM)
    if (date('H') === '09') {
        logEvent("Sending daily statistics to admin...");
        sendDailyStatsToAdmin($bot, $stats);
        logEvent("Daily statistics sent to admin");
    }
    
    // Task 6: Backup important data
    logEvent("Starting data backup...");
    $backupResult = backupImportantData();
    logEvent("Data backup completed - " . ($backupResult ? "Success" : "Failed"));
    
} catch (Exception $e) {
    logEvent("Cron job error: " . $e->getMessage(), 'error');
    
    // Send error notification to admin
    try {
        $adminId = 1089928728; // Your admin ID
        $bot->sendMessage($adminId, "🚨 <b>Cron Job Error</b>\n\n" . 
                         "⚠️ <b>Error:</b> " . $e->getMessage() . "\n" . 
                         "🕒 <b>Time:</b> " . date('Y-m-d H:i:s'));
    } catch (Exception $notificationError) {
        logEvent("Failed to send error notification: " . $notificationError->getMessage(), 'error');
    }
}

// Calculate execution time
$executionTime = round(microtime(true) - $startTime, 2);
logEvent("Cron job completed in {$executionTime} seconds");

/**
 * Clean up old log entries
 */
function cleanupOldLogs() {
    $logFile = __DIR__ . '/data/logs.json';
    if (!file_exists($logFile)) {
        return 0;
    }
    
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
    $originalCount = count($logs);
    
    if ($originalCount > 1000) {
        // Keep only the last 1000 entries
        $logs = array_slice($logs, -1000);
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
        return $originalCount - 1000;
    }
    
    return 0;
}

/**
 * Update system statistics
 */
function updateSystemStats($videoManager) {
    $stats = $videoManager->getSystemStats();
    
    // Get user count
    $userRequestsFile = __DIR__ . '/data/user_requests.json';
    $userCount = 0;
    if (file_exists($userRequestsFile)) {
        $userRequests = json_decode(file_get_contents($userRequestsFile), true) ?: [];
        $userCount = count($userRequests);
    }
    
    $stats['total_users'] = $userCount;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    // Save updated stats
    $statsFile = __DIR__ . '/data/system_stats.json';
    if (!file_exists(dirname($statsFile))) {
        mkdir(dirname($statsFile), 0755, true);
    }
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    
    return $stats;
}

/**
 * Clean up orphaned user progress
 */
function cleanupOrphanedProgress() {
    $progressFile = __DIR__ . '/data/user_progress.json';
    $requestsFile = __DIR__ . '/data/user_requests.json';
    
    if (!file_exists($progressFile) || !file_exists($requestsFile)) {
        return 0;
    }
    
    $progress = json_decode(file_get_contents($progressFile), true) ?: [];
    $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
    
    $originalCount = count($progress);
    $cleanedProgress = [];
    
    foreach ($progress as $userId => $userProgress) {
        if (isset($requests[$userId])) {
            $cleanedProgress[$userId] = $userProgress;
        }
    }
    
    if (count($cleanedProgress) !== $originalCount) {
        file_put_contents($progressFile, json_encode($cleanedProgress, JSON_PRETTY_PRINT));
        return $originalCount - count($cleanedProgress);
    }
    
    return 0;
}

/**
 * Send daily statistics to admin
 */
function sendDailyStatsToAdmin($bot, $stats) {
    $adminId = 1089928728; // Your admin ID
    
    $message = "📊 <b>Daily Bot Statistics</b>\n\n";
    $message .= "👥 <b>Total Users:</b> {$stats['total_users']}\n";
    $message .= "🎬 <b>Total Videos:</b> {$stats['total_videos']}\n";
    $message .= "📈 <b>Videos Sent Today:</b> " . getTodayVideoCount() . "\n";
    $message .= "🗓️ <b>Date:</b> " . date('Y-m-d') . "\n";
    $message .= "🕒 <b>Time:</b> " . date('H:i:s') . "\n\n";
    $message .= "✅ <b>System Status:</b> Healthy";
    
    $bot->sendMessage($adminId, $message);
}

/**
 * Get today's video count
 */
function getTodayVideoCount() {
    $logFile = __DIR__ . '/data/logs.json';
    if (!file_exists($logFile)) {
        return 0;
    }
    
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
    $today = date('Y-m-d');
    $count = 0;
    
    foreach ($logs as $log) {
        if (strpos($log['timestamp'], $today) === 0 && 
            strpos($log['message'], 'requested next video') !== false) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Backup important data
 */
function backupImportantData() {
    $backupDir = __DIR__ . '/backups/' . date('Y-m-d');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filesToBackup = [
        'data/user_progress.json',
        'data/user_requests.json',
        'data/file_ids.json',
        'data/support_tickets.json'
    ];
    
    $success = true;
    foreach ($filesToBackup as $file) {
        $sourcePath = __DIR__ . '/' . $file;
        $backupPath = $backupDir . '/' . basename($file);
        
        if (file_exists($sourcePath)) {
            if (!copy($sourcePath, $backupPath)) {
                $success = false;
            }
        }
    }
    
    // Keep only last 7 days of backups
    cleanupOldBackups();
    
    return $success;
}

/**
 * Clean up old backups
 */
function cleanupOldBackups() {
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        return;
    }
    
    $directories = glob($backupDir . '/*', GLOB_ONLYDIR);
    $cutoffDate = date('Y-m-d', strtotime('-7 days'));
    
    foreach ($directories as $dir) {
        $dirDate = basename($dir);
        if ($dirDate < $cutoffDate) {
            // Remove old backup directory
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }
}
?>

<?php
/**
 * Admin Panel - Main Entry Point
 * Modern Production Version
 */

// Include required files
require_once '../config.php';
require_once '../TelegramBot.php';
require_once '../VideoManager.php';
require_once 'includes/auth.php';
require_once 'includes/ajax_handlers.php';

// Initialize classes
$bot = new TelegramBot();
$videoManager = new VideoManager();

// Get initial data
$systemStats = $videoManager->getSystemStats();
$userProgress = [];
if (file_exists(PROGRESS_PATH)) {
    $userProgress = json_decode(file_get_contents(PROGRESS_PATH), true) ?: [];
}
$deletionQueue = [];
if (file_exists(DELETION_QUEUE_PATH)) {
    $deletionQueue = json_decode(file_get_contents(DELETION_QUEUE_PATH), true) ?: [];
}

// Include the main dashboard
include 'includes/dashboard.php';
?>

<?php
/**
 * Clean Webhook Handler
 * Production Version
 */

require_once 'config.php';
require_once 'TelegramBot.php';
require_once 'VideoManager.php';

// Get webhook input
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    exit;
}

// Initialize classes
$bot = new TelegramBot();
$videoManager = new VideoManager();

// Process message
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $userId = $message['from']['id'];
    $text = $message['text'] ?? '';
    $firstName = $message['from']['first_name'] ?? 'User';
    
    // Handle video capture (admin functionality)
    if (isset($message['video'])) {
        $workingFileId = $message['video']['file_id'];
        
        // Check if sender is admin
        if ($bot->isAdmin($userId)) {
            $fileIds = [];
            if (file_exists(FILE_IDS_PATH)) {
                $fileIds = json_decode(file_get_contents(FILE_IDS_PATH), true) ?: [];
            }
            
            if (!in_array($workingFileId, $fileIds)) {
                $fileIds[] = $workingFileId;
                file_put_contents(FILE_IDS_PATH, json_encode($fileIds, JSON_PRETTY_PRINT));
                
                $bot->sendAdminVideoConfirmation($chatId, count($fileIds));
                logEvent("Admin $userId added video to library. Total: " . count($fileIds));
            } else {
                $bot->sendAdminDuplicateMessage($chatId, count($fileIds));
                logEvent("Admin $userId tried to add duplicate video");
            }
        } else {
            // Non-admin users get a different message
            $bot->sendMessage($chatId, "❌ <b>Access Denied</b>\n\nOnly admins can add videos to the library.\n\nUse /start to access your videos!");
        }
        
        http_response_code(200);
        echo "OK";
        exit;
    }
    
    // Handle commands
    switch (strtolower(trim($text))) {
        case '/start':
            // Check channel membership first
            if (!$bot->testChannelMembership($userId)) {
                $bot->sendChannelJoinRequired($chatId, $firstName);
                logEvent("User $userId tried to start without channel membership");
            } else {
                handleStart($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
            
        case '/next':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleNext($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
            
        case '/stats':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleStats($bot, $chatId, $userId, $videoManager);
            }
            break;
            
        case '/reset':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleReset($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
            
        case '/help':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleHelp($bot, $chatId);
            }
            break;
            
        case '/random':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleRandom($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
            
        case '/support':
            $telegramId = $userId;
            $userName = $firstName;
            $supportUrl = "https://fastme.cloud/bot/support.php?user_id=$telegramId&name=" . urlencode($userName);
            
            $text = "🎫 <b>Support Ticket System</b>\n\n";
            $text .= "Hello $firstName! 👋\n\n";
            $text .= "🛠️ <b>Need Help?</b>\n";
            $text .= "Our support team is here to assist you!\n\n";
            $text .= "📋 <b>We can help with:</b>\n";
            $text .= "• Channel joining problems\n";
            $text .= "• Video playback issues\n";
            $text .= "• Progress tracking problems\n";
            $text .= "• Technical difficulties\n\n";
            $text .= "🌐 <b>Click below to create a support ticket:</b>\n";
            $text .= "Your information will be automatically filled!";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎫 Create Support Ticket', 'web_app' => ['url' => $supportUrl]]
                    ],
                    [
                        ['text' => '🔙 Back to Main', 'callback_data' => 'start']
                    ]
                ]
            ];
            
            $bot->sendMessage($chatId, $text, $keyboard);
            break;
            
        default:
            if (!$bot->isChannelMember($userId)) {
                $bot->sendChannelJoinRequired($chatId, $firstName);
            } else {
                $bot->sendMessage($chatId, "❓ Unknown command. Use /help to see available commands.");
            }
            break;
    }
}

// Command handlers
function handleStart($bot, $chatId, $userId, $firstName, $videoManager) {
    $userStats = $videoManager->getUserStats($userId);
    $bot->sendWelcomeMessage($chatId, $firstName, $userStats);
    logEvent("User $userId started bot");
}

function handleNext($bot, $chatId, $userId, $firstName, $videoManager) {
    $video = $videoManager->getNextVideo($userId);
    
    if (!$video) {
        $requestCount = $bot->getUserRequestCount($userId);
        $bot->sendCompletionMessage($chatId, $firstName, $requestCount);
        return;
    }
    
    $requestCount = $bot->incrementUserRequestCount($userId);
    $result = $bot->sendVideo($chatId, $video['file_id'], $video['index'], $requestCount);
    
    if ($result) {
        // Only send a simple confirmation, video already has caption
        $text = "✅ <b>Next Video Delivered!</b>\n\n";
        $text .= "🎬 <b>Request #$requestCount</b>\n";
        $text .= "📊 <b>Position:</b> {$video['index']} of {$video['total']}\n\n";
        $text .= "🎯 <b>Continue watching?</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                    ['text' => '🎲 Random', 'callback_data' => 'random']
                ],
                [
                    ['text' => '📊 My Stats', 'callback_data' => 'stats'],
                    ['text' => '🏠 Main Menu', 'callback_data' => 'start']
                ]
            ]
        ];
        
        $bot->sendMessage($chatId, $text, $keyboard);
        logEvent("User $userId requested next video (Request #$requestCount)");
    } else {
        $bot->sendErrorMessage($chatId, 'video_send_failed');
    }
}

function handleStats($bot, $chatId, $userId, $videoManager) {
    $stats = $videoManager->getUserStats($userId);
    $requestCount = $bot->getUserRequestCount($userId);
    $bot->sendStatsMessage($chatId, $stats, $requestCount);
    logEvent("User $userId viewed stats");
}

function handleReset($bot, $chatId, $userId, $firstName, $videoManager) {
    $videoManager->resetUserProgress($userId);
    
    $text = "🔄 <b>Progress Reset Complete!</b>\n\n";
    $text .= "Hi $firstName! Your progress has been reset.\n\n";
    $text .= "� <b>Starting Fresh:</b>\n";
    $text .= "• Position: Back to beginning\n";
    $text .= "• Progress: 0%\n";
    $text .= "• Status: Ready to watch\n\n";
    $text .= "🎯 <i>Ready for your video journey?</i>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '▶️ Start Watching', 'callback_data' => 'next'],
                ['text' => '🎲 Random Video', 'callback_data' => 'random']
            ]
        ]
    ];
    
    $bot->sendMessage($chatId, $text, $keyboard);
    logEvent("User $userId reset progress");
}

function handleHelp($bot, $chatId) {
    $bot->sendHelpMessage($chatId);
}

function handleRandom($bot, $chatId, $userId, $firstName, $videoManager) {
    $video = $videoManager->getRandomVideo();
    
    if (!$video) {
        $bot->sendErrorMessage($chatId, 'no_videos');
        return;
    }
    
    $requestCount = $bot->incrementUserRequestCount($userId);
    $result = $bot->sendVideo($chatId, $video['file_id'], $video['index'], $requestCount);
    
    if ($result) {
        // Send a simple confirmation message without duplicating the video success message
        $text = "🎲 <b>Random Video Selected!</b>\n\n";
        $text .= "🎬 <b>Request #$requestCount</b>\n";
        $text .= "📊 <b>Video:</b> #{$video['index']}\n\n";
        $text .= "🎯 <b>What's next?</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎲 Another Random', 'callback_data' => 'random'],
                    ['text' => '▶️ Next Video', 'callback_data' => 'next']
                ],
                [
                    ['text' => '📊 My Stats', 'callback_data' => 'stats'],
                    ['text' => '🏠 Main Menu', 'callback_data' => 'start']
                ]
            ]
        ];
        
        $bot->sendMessage($chatId, $text, $keyboard);
        logEvent("User $userId requested random video (Request #$requestCount)");
    } else {
        $bot->sendErrorMessage($chatId, 'video_send_failed');
    }
}

// Handle callback queries (inline buttons)
if (isset($update['callback_query'])) {
    $callbackQuery = $update['callback_query'];
    $chatId = $callbackQuery['message']['chat']['id'];
    $userId = $callbackQuery['from']['id'];
    $firstName = $callbackQuery['from']['first_name'] ?? 'User';
    $data = $callbackQuery['data'];
    
    switch ($data) {
        case 'check_membership':
            // Use comprehensive membership test
            if ($bot->testChannelMembership($userId)) {
                $bot->sendMembershipVerified($chatId, $firstName);
                logEvent("User $userId verified channel membership successfully");
            } else {
                $bot->sendMembershipVerificationFailed($chatId, $firstName);
                logEvent("User $userId failed all membership verification checks");
            }
            break;
            
        case 'start_verified':
            if ($bot->testChannelMembership($userId)) {
                handleStart($bot, $chatId, $userId, $firstName, $videoManager);
            } else {
                $bot->sendAccessDenied($chatId, $firstName);
            }
            break;
            
        case 'support_ticket':
            $telegramId = $userId;
            $userName = $firstName;
            $supportUrl = "https://fastme.cloud/bot/support.php?user_id=$telegramId&name=" . urlencode($userName);
            
            $text = "🎫 <b>Support Ticket System</b>\n\n";
            $text .= "Hello $firstName! 👋\n\n";
            $text .= "🛠️ <b>Need Help?</b>\n";
            $text .= "Our support team is here to assist you!\n\n";
            $text .= "📋 <b>We can help with:</b>\n";
            $text .= "• Channel joining problems\n";
            $text .= "• Video playback issues\n";
            $text .= "• Progress tracking problems\n";
            $text .= "• Technical difficulties\n\n";
            $text .= "🌐 <b>Click below to create a support ticket:</b>\n";
            $text .= "Your information will be automatically filled!";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎫 Create Support Ticket', 'web_app' => ['url' => $supportUrl]]
                    ],
                    [
                        ['text' => '🔙 Back to Main', 'callback_data' => 'start']
                    ]
                ]
            ];
            
            $bot->sendMessage($chatId, $text, $keyboard);
            break;
            
        case 'next':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleNext($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
        case 'random':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleRandom($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
        case 'stats':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleStats($bot, $chatId, $userId, $videoManager);
            }
            break;
        case 'reset':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleReset($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
        case 'reset_confirm':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                $text = "⚠️ <b>Confirm Reset</b>\n\n";
                $text .= "Are you sure you want to reset your progress?\n";
                $text .= "This action cannot be undone.\n\n";
                $text .= "🔄 <i>All your watching progress will be lost!</i>";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Yes, Reset', 'callback_data' => 'reset'],
                            ['text' => '❌ Cancel', 'callback_data' => 'stats']
                        ]
                    ]
                ];
                
                $bot->sendMessage($chatId, $text, $keyboard);
            }
            break;
        case 'help':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                handleHelp($bot, $chatId);
            }
            break;
        case 'start':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendChannelJoinRequired($chatId, $firstName);
            } else {
                handleStart($bot, $chatId, $userId, $firstName, $videoManager);
            }
            break;
        case 'retry':
            if (!$bot->isChannelMember($userId)) {
                $bot->sendAccessDenied($chatId, $firstName);
            } else {
                $text = "🔄 <b>Ready to try again?</b>\n\n";
                $text .= "What would you like to do?";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                            ['text' => '🎲 Random', 'callback_data' => 'random']
                        ],
                        [
                            ['text' => '📊 My Stats', 'callback_data' => 'stats'],
                            ['text' => '🏠 Main Menu', 'callback_data' => 'start']
                        ]
                    ]
                ];
                
                $bot->sendMessage($chatId, $text, $keyboard);
            }
            break;
    }
    
    // Answer callback query
    $bot->makeRequest('answerCallbackQuery', ['callback_query_id' => $callbackQuery['id']]);
}

http_response_code(200);
echo "OK";
?>

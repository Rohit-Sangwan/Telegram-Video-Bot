<?php
/**
 * Clean Telegram Bot API Class
 * Production Version
 */

require_once 'config.php';

class TelegramBot {
    private $botToken;
    private $apiUrl;
    
    public function __construct() {
        $this->botToken = BOT_TOKEN;
        $this->apiUrl = BOT_API_URL;
    }
    
    /**
     * Make API request to Telegram
     */
    public function makeRequest($method, $params = []) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if (isset($params['video']) || isset($params['photo']) || isset($params['document'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            logEvent("cURL Error in $method: " . $error, 'error');
            curl_close($ch);
            return ['ok' => false, 'error' => $error];
        }
        
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['ok']) && $result['ok']) {
                return $result;
            } else {
                $errorMsg = $result['description'] ?? 'Unknown API error';
                logEvent("API Error in $method: " . $errorMsg, 'error');
                return ['ok' => false, 'error' => $errorMsg];
            }
        } else {
            logEvent("HTTP Error $httpCode in $method: $response", 'error');
            return ['ok' => false, 'error' => "HTTP Error $httpCode"];
        }
    }
    
    /**
     * Get user information from Telegram
     */
    public function getUserInfo($userId) {
        try {
            $result = $this->makeRequest('getChat', ['chat_id' => $userId]);
            if ($result && isset($result['ok']) && $result['ok']) {
                return $result['result'];
            }
            return false;
        } catch (Exception $e) {
            logEvent("Error getting user info for $userId: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send message with better error handling
     */
    public function sendMessage($chatId, $text, $replyMarkup = null) {
        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];
            
            if ($replyMarkup) {
                $params['reply_markup'] = json_encode($replyMarkup);
            }
            
            $result = $this->makeRequest('sendMessage', $params);
            
            if ($result && isset($result['ok']) && $result['ok']) {
                return $result;
            } else {
                logEvent("Failed to send message to $chatId: " . ($result['error'] ?? 'Unknown error'), 'error');
                return false;
            }
        } catch (Exception $e) {
            logEvent("Exception in sendMessage to $chatId: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Send video with enhanced protection and auto-deletion after 15 minutes
     */
    public function sendVideo($chatId, $fileId, $videoNumber = null, $userRequestCount = null) {
        if (empty($fileId)) {
            logEvent("Empty file_id provided for chat $chatId", 'error');
            return false;
        }
        
        // Log the attempt
        logEvent("Attempting to send video to chat $chatId with file_id: " . substr($fileId, 0, 20) . "...");
        
        // Enhanced video caption
        $caption = "🎬 <b>Video Delivered!</b>\n\n";
        $caption .= "⏱️ <b>Auto-Delete:</b> 15 minutes\n";
        $caption .= "🎯 <i>Enjoy your video!</i>";
        
        $params = [
            'chat_id' => $chatId,
            'video' => $fileId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'protect_content' => true  // Prevent forwarding and downloading
        ];
        
        $result = $this->makeRequest('sendVideo', $params);
        
        if ($result && isset($result['result']['message_id'])) {
            // Schedule VIDEO deletion after 15 minutes
            $this->scheduleDelete($result['result']['message_id'], $chatId, 15, 'video');
            logEvent("Protected video sent successfully to chat $chatId (Request #$userRequestCount) - Will auto-delete in 15 minutes");
            return $result;
        } else {
            // Enhanced error logging
            $errorMsg = "Failed to send protected video to chat $chatId. ";
            if (isset($result['error'])) {
                $errorMsg .= "Error: " . $result['error'];
            } else {
                $errorMsg .= "API response: " . json_encode($result);
            }
            logEvent($errorMsg, 'error');
            return false;
        }
    }
    
    /**
     * Delete message
     */
    public function deleteMessage($chatId, $messageId) {
        return $this->makeRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }
    
    /**
     * Schedule message deletion with different types
     */
    private function scheduleDelete($messageId, $chatId, $deleteAfterMinutes = null, $messageType = 'message') {
        // Use custom delay or default from config
        $minutes = $deleteAfterMinutes ?? DELETE_AFTER_MINUTES;
        $deleteTime = time() + ($minutes * 60);
        
        $deletion = [
            'message_id' => $messageId,
            'chat_id' => $chatId,
            'delete_time' => $deleteTime,
            'type' => $messageType // 'message', 'video', 'confirmation_message'
        ];
        
        $queue = [];
        if (file_exists(DELETION_QUEUE_PATH)) {
            $queue = json_decode(file_get_contents(DELETION_QUEUE_PATH), true) ?: [];
        }
        
        $queue[] = $deletion;
        file_put_contents(DELETION_QUEUE_PATH, json_encode($queue, JSON_PRETTY_PRINT));
        
        logEvent("Scheduled deletion of $messageType $messageId in $minutes minutes");
    }
    
    /**
     * Process deletion queue (messages = 1 min, videos = 15 min)
     */
    public function processDeletionQueue() {
        if (!file_exists(DELETION_QUEUE_PATH)) {
            return;
        }
        
        $queue = json_decode(file_get_contents(DELETION_QUEUE_PATH), true) ?: [];
        $currentTime = time();
        $remaining = [];
        $deletedCount = 0;
        $deletedMessages = 0;
        $deletedVideos = 0;
        
        foreach ($queue as $deletion) {
            if ($currentTime >= $deletion['delete_time']) {
                $messageType = $deletion['type'] ?? 'unknown';
                
                if ($this->deleteMessage($deletion['chat_id'], $deletion['message_id'])) {
                    $deletedCount++;
                    
                    if ($messageType === 'video') {
                        $deletedVideos++;
                    } else {
                        $deletedMessages++;
                    }
                    
                    logEvent("Deleted $messageType {$deletion['message_id']} from chat {$deletion['chat_id']}");
                } else {
                    logEvent("Failed to delete $messageType {$deletion['message_id']} from chat {$deletion['chat_id']}", 'error');
                }
            } else {
                $remaining[] = $deletion;
            }
        }
        
        file_put_contents(DELETION_QUEUE_PATH, json_encode($remaining, JSON_PRETTY_PRINT));
        
        if ($deletedCount > 0) {
            logEvent("Deleted $deletedCount items: $deletedMessages messages, $deletedVideos videos");
        }
    }
    
    /**
     * Set webhook
     */
    public function setWebhook($url) {
        return $this->makeRequest('setWebhook', ['url' => $url]);
    }
    
    /**
     * Get webhook info
     */
    public function getWebhookInfo() {
        return $this->makeRequest('getWebhookInfo');
    }
    
    /**
     * Get bot info
     */
    public function getMe() {
        return $this->makeRequest('getMe');
    }
    
    /**
     * Send modern welcome message (auto-delete after 1 minute)
     */
    public function sendWelcomeMessage($chatId, $firstName, $userStats) {
        $text = "🎬 <b>Welcome to Video Bot, $firstName!</b>\n\n";
        $text .= "✨ <b>Your Personal Video Library</b>\n\n";
        $text .= "📊 <b>Your Progress:</b>\n";
        $text .= "• Videos watched: {$userStats['current_index']}\n";
        $text .= "• Progress: {$userStats['percentage']}%\n\n";
        $text .= "🎯 <b>What would you like to do?</b>\n";
        $text .= "⏱️ <i>This message will auto-delete in 1 minute</i>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                    ['text' => '📊 My Stats', 'callback_data' => 'stats']
                ],
                [
                    ['text' => '🆘 Help', 'callback_data' => 'help'],
                    ['text' => '🎫 Support', 'callback_data' => 'support_ticket']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of welcome message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send modern stats message (auto-delete after 1 minute)
     */
    public function sendStatsMessage($chatId, $userStats, $requestCount) {
        $progressBar = $this->createProgressBar($userStats['percentage']);
        
        $text = "📊 <b>Your Video Statistics</b>\n\n";
        $text .= "🎬 <b>Progress Overview:</b>\n";
        $text .= "• Total requests: $requestCount\n";
        $text .= "• Progress: {$userStats['percentage']}%\n\n";
        $text .= "📈 <b>Progress Bar:</b>\n";
        $text .= $progressBar . "\n\n";
        $text .= "🎯 <i>Keep watching to complete your collection!</i>\n";
        $text .= "⏱️ <i>This message will auto-delete in 1 minute</i>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Continue Watching', 'callback_data' => 'next'],
                    ['text' => '📊 View Stats', 'callback_data' => 'stats']
                ],
                [
                    ['text' => '🔄 Reset Progress', 'callback_data' => 'reset_confirm']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of stats message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send modern help message
     */
    public function sendHelpMessage($chatId) {
        $text = "🆘 <b>Video Bot - Help Center</b>\n\n";
        $text .= "🎬 <b>Main Commands:</b>\n";
        $text .= "• /start - Welcome & status\n";
        $text .= "• /next - Get next video in sequence\n";
        $text .= "• /stats - View your progress\n";
        $text .= "• /reset - Reset your progress\n";
        $text .= "• /support - Get support help\n\n";
        $text .= "⚡ <b>Quick Actions:</b>\n";
        $text .= "Use the buttons below for faster navigation!\n\n";
        $text .= "⚠️ <b>Auto-Delete Times:</b>\n";
        $text .= "• Messages: 1 minute\n";
        $text .= "• Videos: 15 minutes";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                    ['text' => '📊 My Stats', 'callback_data' => 'stats']
                ],
                [
                    ['text' => '🎫 Support', 'callback_data' => 'support_ticket'],
                    ['text' => '🏠 Back to Main', 'callback_data' => 'start']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of help message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send video success message (auto-delete after 1 minute)
     */
    public function sendVideoSuccessMessage($chatId, $videoIndex, $totalVideos, $requestCount) {
        $text = "✅ <b>Video Delivered!</b>\n\n";
        $text .= "🎬 <b>Request #$requestCount</b>\n";
        $text .= "⏱️ <b>This message will auto-delete in 1 minute</b>\n\n";
        $text .= "🎯 <b>What's next?</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                    ['text' => '📊 My Stats', 'callback_data' => 'stats']
                ],
                [
                    ['text' => '🏠 Main Menu', 'callback_data' => 'start']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of this confirmation message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send completion message (auto-delete after 1 minute)
     */
    public function sendCompletionMessage($chatId, $firstName, $requestCount) {
        $text = "🎉 <b>Congratulations, $firstName!</b>\n\n";
        $text .= "🏆 <b>Collection Complete!</b>\n";
        $text .= "You've watched all available videos! 🍿\n\n";
        $text .= "📊 <b>Your Achievement:</b>\n";
        $text .= "• Total requests: $requestCount\n";
        $text .= "• Status: Master Viewer 🌟\n\n";
        $text .= "🎯 <i>What would you like to do next?</i>\n";
        $text .= "⏱️ <i>This message will auto-delete in 1 minute</i>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Next Video', 'callback_data' => 'next'],
                    ['text' => '🔄 Start Over', 'callback_data' => 'reset']
                ],
                [
                    ['text' => '📊 View Stats', 'callback_data' => 'stats']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of completion message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send error message (auto-delete after 1 minute)
     */
    public function sendErrorMessage($chatId, $errorType = 'general') {
        $messages = [
            'general' => "❌ <b>Oops! Something went wrong.</b>\n\nPlease try again in a moment.",
            'no_videos' => "📭 <b>No videos available.</b>\n\nVideos will be added soon!",
            'video_send_failed' => "❌ <b>Failed to send video.</b>\n\nPlease try again or contact support."
        ];
        
        $text = $messages[$errorType] ?? $messages['general'];
        $text .= "\n\n⏱️ <i>This message will auto-delete in 1 minute</i>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Try Again', 'callback_data' => 'retry'],
                    ['text' => '🏠 Main Menu', 'callback_data' => 'start']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of error message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send admin video confirmation
     */
    public function sendAdminVideoConfirmation($chatId, $videoCount) {
        $text = "✅ <b>Admin: Video Added!</b>\n\n";
        $text .= "🎬 <b>Video Library Updated</b>\n";
        $text .= "• Total videos: $videoCount\n";
        $text .= "• Status: Active\n\n";
        $text .= "🎯 <i>Video is now available for users!</i>";
        
        return $this->sendMessage($chatId, $text);
    }

    /**
     * Send admin duplicate video message
     */
    public function sendAdminDuplicateMessage($chatId, $videoCount) {
        $text = "ℹ️ <b>Admin: Duplicate Video</b>\n\n";
        $text .= "🎬 <b>Video Already Exists</b>\n";
        $text .= "• Total videos: $videoCount\n";
        $text .= "• Status: No changes made\n\n";
        $text .= "🎯 <i>This video is already in the library!</i>";
        
        return $this->sendMessage($chatId, $text);
    }

    /**
     * Create progress bar visualization
     */
    private function createProgressBar($percentage) {
        $filled = floor($percentage / 10);
        $empty = 10 - $filled;
        
        $bar = str_repeat('🟩', $filled) . str_repeat('⬜', $empty);
        return $bar . " {$percentage}%";
    }

    /**
     * Check if user is admin
     */
    public function isAdmin($userId) {
        return $userId == 1089928728; // Admin ID
    }

    /**
     * Get user request count
     */
    public function getUserRequestCount($userId) {
        $requestsFile = __DIR__ . '/data/user_requests.json';
        $requests = [];
        
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
        }
        
        if (!isset($requests[$userId])) {
            $requests[$userId] = 0;
        }
        
        return $requests[$userId];
    }

    /**
     * Increment user request count
     */
    public function incrementUserRequestCount($userId) {
        $requestsFile = __DIR__ . '/data/user_requests.json';
        $requests = [];
        
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
        }
        
        if (!isset($requests[$userId])) {
            $requests[$userId] = 0;
        }
        
        $requests[$userId]++;
        
        if (!file_exists(dirname($requestsFile))) {
            mkdir(dirname($requestsFile), 0755, true);
        }
        
        file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT));
        
        return $requests[$userId];
    }

    /**
     * Send broadcast message to all users
     */
    public function sendBroadcastMessage($message) {
        $requestsFile = __DIR__ . '/data/user_requests.json';
        $broadcastCount = 0;
        
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
            
            foreach (array_keys($requests) as $userId) {
                if ($this->sendMessage($userId, $message)) {
                    $broadcastCount++;
                }
                usleep(100000); // 0.1 second delay to avoid rate limiting
            }
        }
        
        return $broadcastCount;
    }

    /**
     * Check if user is member of required channel
     */
    public function isChannelMember($userId, $channelId = '-1002792618294') {
        try {
            $result = $this->makeRequest('getChatMember', [
                'chat_id' => $channelId,
                'user_id' => $userId
            ]);
            
            if ($result && isset($result['result']['status'])) {
                $status = $result['result']['status'];
                // Include 'left' status as non-member but don't include 'kicked' or 'restricted'
                $validStatuses = ['creator', 'administrator', 'member'];
                logEvent("User $userId membership check: status = $status", 'debug');
                return in_array($status, $validStatuses);
            }
            
            logEvent("User $userId membership check failed: " . json_encode($result), 'error');
            return false;
        } catch (Exception $e) {
            logEvent("Channel membership check error for user $userId: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send channel join required message (auto-delete after 1 minute)
     */
    public function sendChannelJoinRequired($chatId, $firstName) {
        $text = "🔒 <b>Channel Membership Required</b>\n\n";
        $text .= "Hello $firstName! 👋\n\n";
        $text .= "🎬 <b>To access our video library, you must join our channel first!</b>\n\n";
        $text .= "✅ <b>Why join?</b>\n";
        $text .= "• Get latest updates\n";
        $text .= "• Access exclusive content\n";
        $text .= "• Stay connected with community\n\n";
        $text .= "📢 <b>After joining, click 'Check Membership' to continue!</b>\n";
        $text .= "⏱️ <i>This message will auto-delete in 1 minute</i>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📢 Join Channel', 'url' => 'https://t.me/+-8X0J3qI-XJkYjll']
                ],
                [
                    ['text' => '✅ Check Membership', 'callback_data' => 'check_membership']
                ],
                [
                    ['text' => '🎫 Support Ticket', 'callback_data' => 'support_ticket']
                ]
            ]
        ];
        
        $result = $this->sendMessage($chatId, $text, $keyboard);
        
        // Schedule deletion of channel join message (1 minute)
        if ($result && isset($result['result']['message_id'])) {
            $this->scheduleDelete($result['result']['message_id'], $chatId, 1, 'message');
        }
        
        return $result;
    }

    /**
     * Send membership verification success
     */
    public function sendMembershipVerified($chatId, $firstName) {
        $text = "✅ <b>Membership Verified!</b>\n\n";
        $text .= "Welcome to the Video Bot, $firstName! 🎉\n\n";
        $text .= "🎬 <b>You now have access to:</b>\n";
        $text .= "• Premium video library\n";
        $text .= "• Personal progress tracking\n";
        $text .= "• Sequential video experience\n";
        $text .= "• Priority support\n\n";
        $text .= "🚀 <b>Ready to start watching?</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '▶️ Start Watching', 'callback_data' => 'start_verified'],
                    ['text' => '📊 My Stats', 'callback_data' => 'stats']
                ],
                [
                    ['text' => '🆘 Help', 'callback_data' => 'help']
                ]
            ]
        ];
        
        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send membership verification failed message
     */
    public function sendMembershipVerificationFailed($chatId, $firstName) {
        $text = "⚠️ <b>Membership Verification Failed</b>\n\n";
        $text .= "Hello $firstName! 👋\n\n";
        $text .= "🔍 <b>We couldn't verify your membership.</b>\n\n";
        $text .= "📋 <b>Please ensure:</b>\n";
        $text .= "• You've actually joined the channel\n";
        $text .= "• You didn't immediately leave after joining\n";
        $text .= "• Your privacy settings allow bots to see membership\n\n";
        $text .= "🔄 <b>Try again in a few seconds, or contact support if the issue persists.</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📢 Join Channel', 'url' => 'https://t.me/+-8X0J3qI-XJkYjll']
                ],
                [
                    ['text' => '✅ Check Again', 'callback_data' => 'check_membership'],
                    ['text' => '🎫 Support', 'callback_data' => 'support_ticket']
                ]
            ]
        ];
        
        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Alternative channel membership check (less restrictive for testing)
     */
    public function isChannelMemberAlternative($userId, $channelId = '-1002150515413') {
        try {
            // First try the normal check
            $result = $this->makeRequest('getChatMember', [
                'chat_id' => $channelId,
                'user_id' => $userId
            ]);
            
            if ($result && isset($result['result'])) {
                $status = $result['result']['status'];
                logEvent("Alternative membership check for user $userId: status = $status", 'debug');
                
                // More inclusive check - exclude only definitely blocked users
                $blockedStatuses = ['kicked', 'banned'];
                return !in_array($status, $blockedStatuses);
            }
            
            // If API call fails, allow access but log it
            logEvent("Channel membership API call failed for user $userId, allowing access", 'info');
            return true;
        } catch (Exception $e) {
            logEvent("Alternative membership check error for user $userId: " . $e->getMessage(), 'error');
            return true; // Allow access if check fails
        }
    }

    /**
     * Send support ticket information
     */
    public function sendSupportTicket($chatId, $firstName) {
        $text = "🎫 <b>Support Ticket System</b>\n\n";
        $text .= "Hello $firstName! 👋\n\n";
        $text .= "🛠️ <b>Need Help?</b>\n";
        $text .= "Our support team is here to assist you!\n\n";
        $text .= "📋 <b>Common Issues:</b>\n";
        $text .= "• Channel joining problems\n";
        $text .= "• Video playback issues\n";
        $text .= "• Progress tracking problems\n";
        $text .= "• Technical difficulties\n\n";
        $text .= "🌐 <b>Click below to create a support ticket:</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎫 Create Support Ticket', 'web_app' => ['url' => 'https://fastme.cloud/bot/support.php']]
                ],
                [
                    ['text' => '🔙 Back to Main', 'callback_data' => 'start']
                ]
            ]
        ];
        
        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send access denied message
     */
    public function sendAccessDenied($chatId, $firstName) {
        $text = "❌ <b>Access Denied</b>\n\n";
        $text .= "Sorry $firstName, you must be a member of our channel to use this bot.\n\n";
        $text .= "🔒 <b>Membership Required</b>\n";
        $text .= "Please join our channel and try again.\n\n";
        $text .= "💡 <b>Need help?</b> Use the support ticket system below.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📢 Join Channel', 'url' => 'https://t.me/+-8X0J3qI-XJkYjll']
                ],
                [
                    ['text' => '✅ Check Membership', 'callback_data' => 'check_membership']
                ],
                [
                    ['text' => '🎫 Support Ticket', 'callback_data' => 'support_ticket']
                ]
            ]
        ];
        
        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Debug channel membership - for testing only
     */
    public function debugChannelMembership($userId, $channelId = '-1002150515413') {
        $result = $this->makeRequest('getChatMember', [
            'chat_id' => $channelId,
            'user_id' => $userId
        ]);
        
        logEvent("Debug membership check for user $userId: " . json_encode($result), 'debug');
        
        return $result;
    }

    /**
     * Test channel membership with fallback
     */
    public function testChannelMembership($userId) {
        // Check if user should bypass the channel check
        if ($this->shouldBypassChannelCheck($userId)) {
            logEvent("User $userId bypassed channel membership check", 'info');
            return true;
        }
        
        // First get debug info
        $debugInfo = $this->debugChannelMembership($userId);
        
        // Try normal check
        $normalCheck = $this->isChannelMember($userId);
        
        // Try alternative check  
        $alternativeCheck = $this->isChannelMemberAlternative($userId);
        
        // For now, if any check passes, allow access
        $finalResult = $normalCheck || $alternativeCheck;
        
        logEvent("Membership test for user $userId: normal=$normalCheck, alternative=$alternativeCheck, final=$finalResult", 'info');
        
        return $finalResult;
    }

    /**
     * Check if user should bypass channel membership (for testing)
     */
    public function shouldBypassChannelCheck($userId) {
        // Allow admin to bypass for testing
        if ($this->isAdmin($userId)) {
            return true;
        }
        
        // Add any other test user IDs here if needed
        $testUsers = []; // Add test user IDs here
        
        return in_array($userId, $testUsers);
    }
}
?>

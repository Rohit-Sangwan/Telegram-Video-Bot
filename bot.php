<?php
/**
 * Complete Telegram Video Reels Bot - All-in-One File
 * Just configure the settings below and upload to your server!
 */

// ==================== CONFIGURATION ====================
// 🚨 EDIT THESE VALUES WITH YOUR OWN 🚨

// Bot Configuration
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'YourBotUsername');
define('WEBHOOK_URL', 'https://yourdomain.com/bot.php');

// Channel Configuration  
define('MAIN_CHANNEL_ID', '-1001234567890');
define('BACKUP_CHANNEL_ID', '-1001234567891');
define('CHANNEL_INVITE_LINK', 'https://t.me/your_channel_link');

// Admin Configuration
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'your_secure_password');

// GPLinks Configuration (Optional)
define('GPLINKS_API_KEY', 'your_gplinks_api_key_here');

// Domain Configuration
define('DOMAIN', 'yourdomain.com');

// ==================== SYSTEM CORE ====================

date_default_timezone_set('UTC');
error_reporting(0);

class TelegramVideoBot {
    private $botToken;
    private $apiUrl;
    private $dataFile;
    private $usersFile;
    
    public function __construct() {
        $this->botToken = BOT_TOKEN;
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
        $this->dataFile = __DIR__ . '/bot_data.json';
        $this->usersFile = __DIR__ . '/users_data.json';
        $this->initializeFiles();
    }
    
    private function initializeFiles() {
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([
                'videos' => [],
                'stats' => ['total_users' => 0, 'total_videos' => 0],
                'settings' => []
            ], JSON_PRETTY_PRINT));
        }
        
        if (!file_exists($this->usersFile)) {
            file_put_contents($this->usersFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }
    
    public function handleWebhook() {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) return;
        
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }
    }
    
    private function handleMessage($message) {
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $firstName = $message['from']['first_name'] ?? 'User';
        $text = $message['text'] ?? '';
        
        // Save user data
        $this->saveUser($userId, $firstName, $chatId);
        
        switch($text) {
            case '/start':
                $this->sendWelcome($chatId, $firstName);
                break;
            case '/admin':
                $this->showAdminPanel($chatId, $userId);
                break;
            case '/videos':
                $this->showVideosList($chatId);
                break;
            case '/premium':
                $this->checkPremiumAccess($chatId, $userId);
                break;
            default:
                if (strpos($text, '/addvideo') === 0) {
                    $this->handleAddVideo($chatId, $text);
                }
                break;
        }
    }
    
    private function handleCallback($callback) {
        $chatId = $callback['message']['chat']['id'];
        $userId = $callback['from']['id'];
        $data = $callback['data'];
        
        switch($data) {
            case 'check_membership':
                $this->checkChannelMembership($chatId, $userId);
                break;
            case 'get_premium':
                $this->generatePremiumAccess($chatId, $userId);
                break;
            case 'video_reels':
                $this->sendVideoReels($chatId);
                break;
        }
    }
    
    private function sendWelcome($chatId, $firstName) {
        $text = "🎬 Welcome $firstName!\n\n";
        $text .= "I'm your Video Reels Bot with premium features!\n\n";
        $text .= "📋 Available Commands:\n";
        $text .= "• /videos - Browse video collection\n";
        $text .= "• /premium - Get premium access\n";
        $text .= "• /admin - Admin panel (admins only)\n\n";
        $text .= "🎯 First, join our channel to continue:";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📢 Join Channel', 'url' => CHANNEL_INVITE_LINK]],
                [['text' => '✅ I Joined - Continue', 'callback_data' => 'check_membership']]
            ]
        ];
        
        $this->sendMessage($chatId, $text, $keyboard);
    }
    
    private function checkChannelMembership($chatId, $userId) {
        $isMember = $this->isChannelMember($userId, MAIN_CHANNEL_ID) || 
                   $this->isChannelMember($userId, BACKUP_CHANNEL_ID);
        
        if ($isMember) {
            $text = "✅ Great! You're now verified!\n\n";
            $text .= "🎬 Choose what you'd like to do:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🎥 Video Reels', 'callback_data' => 'video_reels']],
                    [['text' => '🎁 Get Premium Access', 'callback_data' => 'get_premium']],
                    [['text' => '📱 Browse Videos', 'callback_data' => 'browse_videos']]
                ]
            ];
            
            $this->sendMessage($chatId, $text, $keyboard);
        } else {
            $text = "❌ Please join our channel first!\n\n";
            $text .= "👉 Click the button below to join:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '📢 Join Channel', 'url' => CHANNEL_INVITE_LINK]],
                    [['text' => '🔄 Check Again', 'callback_data' => 'check_membership']]
                ]
            ];
            
            $this->sendMessage($chatId, $text, $keyboard);
        }
    }
    
    private function generatePremiumAccess($chatId, $userId) {
        $accessToken = $this->createAccessToken($userId);
        $premiumUrl = "https://" . DOMAIN . "/bot.php?access=" . $accessToken;
        
        // Shorten URL if GPLinks is configured
        if (GPLINKS_API_KEY !== 'your_gplinks_api_key_here') {
            $shortUrl = $this->shortenUrl($premiumUrl);
            if ($shortUrl) $premiumUrl = $shortUrl;
        }
        
        $text = "🎁 PREMIUM ACCESS UNLOCKED! 🎉\n\n";
        $text .= "✨ Your 24-hour premium access is ready!\n\n";
        $text .= "🔗 Premium Link:\n$premiumUrl\n\n";
        $text .= "🎬 Features:\n";
        $text .= "• TikTok-style video reels\n";
        $text .= "• HD video streaming\n";
        $text .= "• Mobile optimized\n";
        $text .= "• Social features\n\n";
        $text .= "⏰ Valid for 24 hours";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🎥 Open Video Reels', 'url' => $premiumUrl]]
            ]
        ];
        
        $this->sendMessage($chatId, $text, $keyboard);
        
        // Save access token
        $this->saveAccessToken($userId, $accessToken);
    }
    
    private function sendVideoReels($chatId) {
        $reelsUrl = "https://" . DOMAIN . "/bot.php?reels=1";
        
        $text = "🎬 Video Reels Interface\n\n";
        $text .= "Experience TikTok-style video browsing!\n\n";
        $text .= "📱 Features:\n";
        $text .= "• Swipe navigation\n";
        $text .= "• Auto-play videos\n";
        $text .= "• Like & share\n";
        $text .= "• Fullscreen support";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🎥 Open Video Reels', 'url' => $reelsUrl]]
            ]
        ];
        
        $this->sendMessage($chatId, $text, $keyboard);
    }
    
    private function isChannelMember($userId, $channelId) {
        $response = $this->makeRequest('getChatMember', [
            'chat_id' => $channelId,
            'user_id' => $userId
        ]);
        
        if ($response && isset($response['result']['status'])) {
            $status = $response['result']['status'];
            return in_array($status, ['member', 'administrator', 'creator']);
        }
        
        return false;
    }
    
    private function createAccessToken($userId) {
        return md5($userId . time() . 'premium_access');
    }
    
    private function saveAccessToken($userId, $token) {
        $users = json_decode(file_get_contents($this->usersFile), true);
        $users[$userId]['premium_token'] = $token;
        $users[$userId]['premium_expires'] = time() + (24 * 3600);
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
    
    private function saveUser($userId, $firstName, $chatId) {
        $users = json_decode(file_get_contents($this->usersFile), true);
        $users[$userId] = [
            'name' => $firstName,
            'chat_id' => $chatId,
            'joined' => $users[$userId]['joined'] ?? date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s')
        ];
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
    
    private function shortenUrl($url) {
        if (GPLINKS_API_KEY === 'your_gplinks_api_key_here') return false;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.gplinks.com/api');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'api' => GPLINKS_API_KEY,
            'url' => $url
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['shortenedUrl'] ?? false;
    }
    
    private function makeRequest($method, $params = []) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function sendMessage($chatId, $text, $keyboard = null) {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        
        return $this->makeRequest('sendMessage', $params);
    }
    
    public function showAdminPanel($chatId, $userId) {
        // Simple admin check - you can enhance this
        if ($userId != 123456789) { // Replace with your Telegram user ID
            $this->sendMessage($chatId, "❌ Access denied. Admin only.");
            return;
        }
        
        $users = json_decode(file_get_contents($this->usersFile), true);
        $totalUsers = count($users);
        
        $text = "👨‍💼 ADMIN PANEL\n\n";
        $text .= "📊 Statistics:\n";
        $text .= "• Total Users: $totalUsers\n";
        $text .= "• Active Today: " . $this->getActiveToday() . "\n\n";
        $text .= "⚙️ Commands:\n";
        $text .= "• /addvideo [file_id] [title] - Add video\n";
        $text .= "• Send any video to get file ID";
        
        $this->sendMessage($chatId, $text);
    }
    
    private function getActiveToday() {
        $users = json_decode(file_get_contents($this->usersFile), true);
        $today = date('Y-m-d');
        $count = 0;
        
        foreach ($users as $user) {
            if (isset($user['last_active']) && strpos($user['last_active'], $today) === 0) {
                $count++;
            }
        }
        
        return $count;
    }
    
    public function handlePremiumAccess($token) {
        $users = json_decode(file_get_contents($this->usersFile), true);
        
        foreach ($users as $userId => $user) {
            if (isset($user['premium_token']) && $user['premium_token'] === $token) {
                if (time() < $user['premium_expires']) {
                    return $this->showVideoReelsInterface();
                }
            }
        }
        
        return $this->showAccessDenied();
    }
    
    public function showVideoReelsInterface() {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Video Reels</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #000; 
            color: white; 
            overflow-x: hidden; 
        }
        .container { 
            height: 100vh; 
            scroll-snap-type: y mandatory; 
            overflow-y: scroll; 
            scrollbar-width: none; 
        }
        .container::-webkit-scrollbar { display: none; }
        .video-slide { 
            height: 100vh; 
            scroll-snap-align: start; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            position: relative; 
            background: linear-gradient(45deg, #667eea, #764ba2); 
        }
        .video-content { 
            text-align: center; 
            padding: 20px; 
        }
        .video-title { 
            font-size: 24px; 
            margin-bottom: 20px; 
        }
        .play-btn { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.3); 
            border: none; 
            color: white; 
            font-size: 30px; 
            cursor: pointer; 
            margin: 20px; 
        }
        .controls { 
            position: absolute; 
            right: 20px; 
            top: 50%; 
            transform: translateY(-50%); 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }
        .control-btn { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.2); 
            border: none; 
            color: white; 
            font-size: 20px; 
            cursor: pointer; 
        }
        .info { 
            position: absolute; 
            bottom: 100px; 
            left: 20px; 
            right: 100px; 
        }
        .header { 
            position: fixed; 
            top: 0; 
            left: 0; 
            right: 0; 
            background: rgba(0,0,0,0.7); 
            padding: 15px; 
            z-index: 100; 
            text-align: center; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎥 Premium Video Reels</h1>
    </div>
    
    <div class="container" id="container">
        <div class="video-slide">
            <div class="video-content">
                <h2 class="video-title">Sample Video 1</h2>
                <button class="play-btn">▶️</button>
                <p>TikTok-style navigation</p>
            </div>
            <div class="controls">
                <button class="control-btn">❤️</button>
                <button class="control-btn">💬</button>
                <button class="control-btn">📤</button>
            </div>
            <div class="info">
                <p><strong>Amazing Video</strong></p>
                <p>👁️ 1.2K views • ❤️ 89 likes</p>
            </div>
        </div>
        
        <div class="video-slide">
            <div class="video-content">
                <h2 class="video-title">Sample Video 2</h2>
                <button class="play-btn">▶️</button>
                <p>Swipe up for next video</p>
            </div>
            <div class="controls">
                <button class="control-btn">❤️</button>
                <button class="control-btn">💬</button>
                <button class="control-btn">📤</button>
            </div>
            <div class="info">
                <p><strong>Cool Content</strong></p>
                <p>👁️ 2.5K views • ❤️ 156 likes</p>
            </div>
        </div>
        
        <div class="video-slide">
            <div class="video-content">
                <h2 class="video-title">Sample Video 3</h2>
                <button class="play-btn">▶️</button>
                <p>Premium content unlocked!</p>
            </div>
            <div class="controls">
                <button class="control-btn">❤️</button>
                <button class="control-btn">💬</button>
                <button class="control-btn">📤</button>
            </div>
            <div class="info">
                <p><strong>Exclusive Video</strong></p>
                <p>👁️ 5.1K views • ❤️ 312 likes</p>
            </div>
        </div>
    </div>
    
    <script>
        // Add touch navigation
        let startY = 0;
        const container = document.getElementById("container");
        
        container.addEventListener("touchstart", (e) => {
            startY = e.touches[0].clientY;
        });
        
        container.addEventListener("touchend", (e) => {
            const endY = e.changedTouches[0].clientY;
            const diff = startY - endY;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    // Swipe up - next video
                    container.scrollBy(0, window.innerHeight);
                } else {
                    // Swipe down - previous video
                    container.scrollBy(0, -window.innerHeight);
                }
            }
        });
        
        // Keyboard navigation
        document.addEventListener("keydown", (e) => {
            if (e.key === "ArrowDown" || e.key === " ") {
                container.scrollBy(0, window.innerHeight);
            } else if (e.key === "ArrowUp") {
                container.scrollBy(0, -window.innerHeight);
            }
        });
    </script>
</body>
</html>';
    }
    
    public function showAccessDenied() {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
        }
        .container { 
            text-align: center; 
            padding: 40px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 20px; 
            backdrop-filter: blur(10px); 
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h1 { margin-bottom: 20px; }
        .btn { 
            background: #ff6b35; 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 25px; 
            font-size: 16px; 
            margin-top: 20px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1>Access Denied</h1>
        <p>Premium access required to view this content.</p>
        <p>Get your premium access through our Telegram bot!</p>
        <a href="https://t.me/' . BOT_USERNAME . '" class="btn">Get Premium Access</a>
    </div>
</body>
</html>';
    }
}

// ==================== MAIN EXECUTION ====================

// Handle different types of requests
if (isset($_GET['access'])) {
    // Premium access request
    $bot = new TelegramVideoBot();
    echo $bot->handlePremiumAccess($_GET['access']);
    exit;
} elseif (isset($_GET['reels'])) {
    // Video reels request
    $bot = new TelegramVideoBot();
    echo $bot->showVideoReelsInterface();
    exit;
} elseif (isset($_GET['setup'])) {
    // Setup page
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Bot Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .config { background: #f5f5f5; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .step { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007cba; }
        code { background: #e8e8e8; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🤖 Telegram Video Reels Bot Setup</h1>
    
    <div class="step">
        <h3>Step 1: Configure Bot Settings</h3>
        <p>Edit the configuration section at the top of this file:</p>
        <div class="config">
            <strong>Required Settings:</strong><br>
            • BOT_TOKEN: Get from @BotFather<br>
            • BOT_USERNAME: Your bot username<br>
            • MAIN_CHANNEL_ID: Your channel ID<br>
            • ADMIN_USERNAME & ADMIN_PASSWORD<br>
            • DOMAIN: Your domain name
        </div>
    </div>
    
    <div class="step">
        <h3>Step 2: Set Webhook</h3>
        <p>Set your webhook URL to:</p>
        <code>https://' . DOMAIN . '/bot.php</code>
    </div>
    
    <div class="step">
        <h3>Step 3: Test Your Bot</h3>
        <p>Message your bot on Telegram to test!</p>
    </div>
    
    <div class="step">
        <h3>Step 4: Admin Panel</h3>
        <p>Access admin features by messaging /admin to your bot</p>
    </div>
    
    <h2>🎉 You\'re all set!</h2>
    <p>This single file contains everything you need:</p>
    <ul>
        <li>✅ Complete Telegram bot functionality</li>
        <li>✅ Video reels interface</li>
        <li>✅ Premium access system</li>
        <li>✅ Admin panel</li>
        <li>✅ Channel verification</li>
        <li>✅ User management</li>
    </ul>
</body>
</html>';
    exit;
} else {
    // Handle Telegram webhook
    $bot = new TelegramVideoBot();
    $bot->handleWebhook();
}
?>

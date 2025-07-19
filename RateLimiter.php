<?php
/**
 * Clean Rate Limiter Class
 * Production Version
 */

class RateLimiter {
    private $limitsPath;
    private $defaultLimits;
    
    public function __construct() {
        $this->limitsPath = RATE_LIMITS_PATH;
        $this->defaultLimits = [
            'requests_per_minute' => RATE_LIMIT_REQUESTS_PER_MINUTE,
            'requests_per_hour' => RATE_LIMIT_REQUESTS_PER_HOUR,
            'requests_per_day' => RATE_LIMIT_REQUESTS_PER_DAY
        ];
    }
    
    /**
     * Check if user is allowed to make a request
     */
    public function isAllowed($userId, $action = 'general') {
        $userData = $this->getUserData($userId);
        $currentTime = time();
        
        // Clean old entries
        $this->cleanOldEntries($userData, $currentTime);
        
        // Check rate limits
        if (!$this->checkMinuteLimit($userData, $currentTime)) {
            logEvent("Rate limit exceeded (minute) for user: $userId, action: $action", 'warning');
            return false;
        }
        
        if (!$this->checkHourLimit($userData, $currentTime)) {
            logEvent("Rate limit exceeded (hour) for user: $userId, action: $action", 'warning');
            return false;
        }
        
        if (!$this->checkDayLimit($userData, $currentTime)) {
            logEvent("Rate limit exceeded (day) for user: $userId, action: $action", 'warning');
            return false;
        }
        
        // Record the request
        $this->recordRequest($userId, $action, $currentTime);
        
        return true;
    }
    
    /**
     * Get user rate limit data
     */
    private function getUserData($userId) {
        if (!file_exists($this->limitsPath)) {
            return $this->createUserData($userId);
        }
        
        $allData = json_decode(file_get_contents($this->limitsPath), true) ?: [];
        
        if (isset($allData[$userId])) {
            return $allData[$userId];
        }
        
        return $this->createUserData($userId);
    }
    
    /**
     * Create new user rate limit data
     */
    private function createUserData($userId) {
        return [
            'user_id' => $userId,
            'requests' => [],
            'created_at' => time(),
            'last_request' => null,
            'total_requests' => 0,
            'blocked_count' => 0
        ];
    }
    
    /**
     * Save user rate limit data
     */
    private function saveUserData($userId, $userData) {
        // Load all data
        $allData = [];
        if (file_exists($this->limitsPath)) {
            $content = file_get_contents($this->limitsPath);
            if ($content) {
                $allData = json_decode($content, true) ?: [];
            }
        }
        
        // Update user data
        $allData[$userId] = $userData;
        
        // Save updated data
        file_put_contents($this->limitsPath, json_encode($allData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Record a request
     */
    private function recordRequest($userId, $action, $timestamp) {
        $userData = $this->getUserData($userId);
        
        $userData['requests'][] = [
            'action' => $action,
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s', $timestamp)
        ];
        
        $userData['last_request'] = $timestamp;
        $userData['total_requests']++;
        
        // Keep only recent requests (last 24 hours)
        $dayAgo = $timestamp - (24 * 60 * 60);
        $userData['requests'] = array_filter($userData['requests'], function($request) use ($dayAgo) {
            return $request['timestamp'] > $dayAgo;
        });
        
        $this->saveUserData($userId, $userData);
    }
    
    /**
     * Check minute-based rate limit
     */
    private function checkMinuteLimit($userData, $currentTime) {
        $minuteAgo = $currentTime - 60;
        $recentRequests = array_filter($userData['requests'], function($request) use ($minuteAgo) {
            return $request['timestamp'] > $minuteAgo;
        });
        
        return count($recentRequests) < $this->defaultLimits['requests_per_minute'];
    }
    
    /**
     * Check hour-based rate limit
     */
    private function checkHourLimit($userData, $currentTime) {
        $hourAgo = $currentTime - (60 * 60);
        $recentRequests = array_filter($userData['requests'], function($request) use ($hourAgo) {
            return $request['timestamp'] > $hourAgo;
        });
        
        return count($recentRequests) < $this->defaultLimits['requests_per_hour'];
    }
    
    /**
     * Check day-based rate limit
     */
    private function checkDayLimit($userData, $currentTime) {
        $dayAgo = $currentTime - (24 * 60 * 60);
        $recentRequests = array_filter($userData['requests'], function($request) use ($dayAgo) {
            return $request['timestamp'] > $dayAgo;
        });
        
        return count($recentRequests) < $this->defaultLimits['requests_per_day'];
    }
    
    /**
     * Clean old entries from user data
     */
    private function cleanOldEntries($userData, $currentTime) {
        $dayAgo = $currentTime - (24 * 60 * 60);
        
        $userData['requests'] = array_filter($userData['requests'], function($request) use ($dayAgo) {
            return $request['timestamp'] > $dayAgo;
        });
        
        return $userData;
    }
    
    /**
     * Get wait time for user (in seconds)
     */
    public function getWaitTime($userId) {
        $userData = $this->getUserData($userId);
        $currentTime = time();
        
        // Check what limit was hit
        $minuteAgo = $currentTime - 60;
        $recentMinuteRequests = array_filter($userData['requests'], function($request) use ($minuteAgo) {
            return $request['timestamp'] > $minuteAgo;
        });
        
        if (count($recentMinuteRequests) >= $this->defaultLimits['requests_per_minute']) {
            // Find the oldest request in the last minute
            $oldestRequest = min(array_column($recentMinuteRequests, 'timestamp'));
            return 60 - ($currentTime - $oldestRequest);
        }
        
        $hourAgo = $currentTime - (60 * 60);
        $recentHourRequests = array_filter($userData['requests'], function($request) use ($hourAgo) {
            return $request['timestamp'] > $hourAgo;
        });
        
        if (count($recentHourRequests) >= $this->defaultLimits['requests_per_hour']) {
            // Find the oldest request in the last hour
            $oldestRequest = min(array_column($recentHourRequests, 'timestamp'));
            return 3600 - ($currentTime - $oldestRequest);
        }
        
        return 0; // No wait time needed
    }
    
    /**
     * Get user rate limit status
     */
    public function getUserStatus($userId) {
        $userData = $this->getUserData($userId);
        $currentTime = time();
        
        $minuteAgo = $currentTime - 60;
        $hourAgo = $currentTime - (60 * 60);
        $dayAgo = $currentTime - (24 * 60 * 60);
        
        $minuteRequests = array_filter($userData['requests'], function($request) use ($minuteAgo) {
            return $request['timestamp'] > $minuteAgo;
        });
        
        $hourRequests = array_filter($userData['requests'], function($request) use ($hourAgo) {
            return $request['timestamp'] > $hourAgo;
        });
        
        $dayRequests = array_filter($userData['requests'], function($request) use ($dayAgo) {
            return $request['timestamp'] > $dayAgo;
        });
        
        return [
            'user_id' => $userId,
            'requests_last_minute' => count($minuteRequests),
            'requests_last_hour' => count($hourRequests),
            'requests_last_day' => count($dayRequests),
            'limits' => $this->defaultLimits,
            'is_allowed' => $this->isAllowed($userId),
            'wait_time' => $this->getWaitTime($userId),
            'total_requests' => $userData['total_requests'],
            'blocked_count' => $userData['blocked_count'],
            'last_request' => $userData['last_request'] ? date('Y-m-d H:i:s', $userData['last_request']) : null
        ];
    }
    
    /**
     * Reset user rate limits (admin function)
     */
    public function resetUserLimits($userId) {
        $userData = $this->createUserData($userId);
        $this->saveUserData($userId, $userData);
        
        logEvent("Rate limits reset for user: $userId", 'info');
        return true;
    }
    
    /**
     * Set custom limits for a user (premium users, etc.)
     */
    public function setCustomLimits($userId, $limits) {
        $userData = $this->getUserData($userId);
        $userData['custom_limits'] = $limits;
        $userData['custom_limits_set_at'] = time();
        
        $this->saveUserData($userId, $userData);
        
        logEvent("Custom rate limits set for user: $userId", 'info');
        return true;
    }
    
    /**
     * Get rate limiting statistics
     */
    public function getStatistics() {
        if (!file_exists($this->limitsPath)) {
            return [
                'total_users' => 0,
                'total_requests' => 0,
                'blocked_requests' => 0,
                'active_users_last_hour' => 0
            ];
        }
        
        $allData = json_decode(file_get_contents($this->limitsPath), true) ?: [];
        $currentTime = time();
        $hourAgo = $currentTime - (60 * 60);
        
        $stats = [
            'total_users' => count($allData),
            'total_requests' => 0,
            'blocked_requests' => 0,
            'active_users_last_hour' => 0,
            'users_by_activity' => []
        ];
        
        foreach ($allData as $userId => $userData) {
            $stats['total_requests'] += $userData['total_requests'];
            $stats['blocked_requests'] += $userData['blocked_count'];
            
            // Check if user was active in the last hour
            if ($userData['last_request'] && $userData['last_request'] > $hourAgo) {
                $stats['active_users_last_hour']++;
            }
            
            // Categorize users by activity level
            $recentRequests = array_filter($userData['requests'], function($request) use ($hourAgo) {
                return $request['timestamp'] > $hourAgo;
            });
            
            $requestCount = count($recentRequests);
            if ($requestCount == 0) {
                $category = 'inactive';
            } elseif ($requestCount <= 5) {
                $category = 'low';
            } elseif ($requestCount <= 20) {
                $category = 'medium';
            } else {
                $category = 'high';
            }
            
            if (!isset($stats['users_by_activity'][$category])) {
                $stats['users_by_activity'][$category] = 0;
            }
            $stats['users_by_activity'][$category]++;
        }
        
        return $stats;
    }
    
    /**
     * Clean up old rate limit data
     */
    public function cleanup() {
        if (!file_exists($this->limitsPath)) {
            return 0;
        }
        
        $allData = json_decode(file_get_contents($this->limitsPath), true) ?: [];
        $currentTime = time();
        $weekAgo = $currentTime - (7 * 24 * 60 * 60);
        $cleaned = 0;
        
        foreach ($allData as $userId => $userData) {
            // Remove users with no recent activity (last 7 days)
            if (!$userData['last_request'] || $userData['last_request'] < $weekAgo) {
                unset($allData[$userId]);
                $cleaned++;
            } else {
                // Clean old requests for active users
                $dayAgo = $currentTime - (24 * 60 * 60);
                $userData['requests'] = array_filter($userData['requests'], function($request) use ($dayAgo) {
                    return $request['timestamp'] > $dayAgo;
                });
                $allData[$userId] = $userData;
            }
        }
        
        if ($cleaned > 0 || $allData != json_decode(file_get_contents($this->limitsPath), true)) {
            file_put_contents($this->limitsPath, json_encode($allData, JSON_PRETTY_PRINT));
            logEvent("Rate limiter cleanup completed: $cleaned users removed", 'info');
        }
        
        return $cleaned;
    }
    
    /**
     * Block user temporarily (admin function)
     */
    public function blockUser($userId, $duration = 3600) {
        $userData = $this->getUserData($userId);
        $userData['blocked_until'] = time() + $duration;
        $userData['blocked_count']++;
        
        $this->saveUserData($userId, $userData);
        
        logEvent("User $userId blocked for $duration seconds", 'warning');
        return true;
    }
    
    /**
     * Check if user is currently blocked
     */
    public function isUserBlocked($userId) {
        $userData = $this->getUserData($userId);
        
        if (isset($userData['blocked_until']) && $userData['blocked_until'] > time()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get remaining block time for user
     */
    public function getBlockTime($userId) {
        $userData = $this->getUserData($userId);
        
        if (isset($userData['blocked_until']) && $userData['blocked_until'] > time()) {
            return $userData['blocked_until'] - time();
        }
        
        return 0;
    }
}
?>

<?php
/**
 * Simple Rate Limiter for Telegram Bot
 * Prevents API flooding from rapid button clicks
 */

class RateLimiter {
    private $dataFile;
    private $limits;
    
    public function __construct($dataFile = 'data/rate_limits.json') {
        $this->dataFile = $dataFile;
        $this->limits = [];
        $this->loadData();
    }
    
    private function loadData() {
        if (file_exists($this->dataFile)) {
            $data = json_decode(file_get_contents($this->dataFile), true);
            $this->limits = $data ?: [];
        }
    }
    
    private function saveData() {
        $dir = dirname($this->dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->dataFile, json_encode($this->limits, JSON_PRETTY_PRINT));
    }
    
    /**
     * Check if user is rate limited
     * @param int $userId User ID
     * @param string $action Action type (random, next, etc.)
     * @param int $windowSeconds Time window in seconds
     * @param int $maxAttempts Maximum attempts in window
     * @return bool True if rate limited, false if allowed
     */
    public function isRateLimited($userId, $action, $windowSeconds = 3, $maxAttempts = 1) {
        $key = $userId . '_' . $action;
        $now = time();
        
        // Clean old entries
        $this->cleanOldEntries($now - $windowSeconds);
        
        if (!isset($this->limits[$key])) {
            $this->limits[$key] = [];
        }
        
        // Count attempts in current window
        $attempts = array_filter($this->limits[$key], function($timestamp) use ($now, $windowSeconds) {
            return $timestamp > ($now - $windowSeconds);
        });
        
        if (count($attempts) >= $maxAttempts) {
            return true; // Rate limited
        }
        
        // Record this attempt
        $this->limits[$key][] = $now;
        $this->saveData();
        
        return false; // Not rate limited
    }
    
    private function cleanOldEntries($cutoff) {
        foreach ($this->limits as $key => $timestamps) {
            $this->limits[$key] = array_filter($timestamps, function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            });
            
            if (empty($this->limits[$key])) {
                unset($this->limits[$key]);
            }
        }
    }
    
    /**
     * Get remaining time until next allowed attempt
     */
    public function getRemainingTime($userId, $action, $windowSeconds = 3) {
        $key = $userId . '_' . $action;
        $now = time();
        
        if (!isset($this->limits[$key]) || empty($this->limits[$key])) {
            return 0;
        }
        
        $lastAttempt = max($this->limits[$key]);
        $nextAllowed = $lastAttempt + $windowSeconds;
        
        return max(0, $nextAllowed - $now);
    }
}

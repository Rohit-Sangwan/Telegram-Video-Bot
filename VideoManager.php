<?php
/**
 * Clean Video Manager Class
 * Production Version
 */

require_once 'config.php';

class VideoManager {
    private $fileIds;
    private $userProgress;
    
    public function __construct() {
        $this->loadFileIds();
        $this->loadUserProgress();
    }
    
    /**
     * Load file IDs from JSON
     */
    private function loadFileIds() {
        if (file_exists(FILE_IDS_PATH)) {
            $this->fileIds = json_decode(file_get_contents(FILE_IDS_PATH), true) ?: [];
        } else {
            $this->fileIds = [];
        }
    }
    
    /**
     * Load user progress from JSON
     */
    private function loadUserProgress() {
        if (file_exists(PROGRESS_PATH)) {
            $this->userProgress = json_decode(file_get_contents(PROGRESS_PATH), true) ?: [];
        } else {
            $this->userProgress = [];
        }
    }
    
    /**
     * Save user progress to JSON
     */
    private function saveUserProgress() {
        file_put_contents(PROGRESS_PATH, json_encode($this->userProgress, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get total number of videos
     */
    public function getTotalVideos() {
        return count($this->fileIds);
    }
    
    /**
     * Get user's current progress
     */
    public function getUserProgress($userId) {
        return isset($this->userProgress[$userId]) ? $this->userProgress[$userId] : 0;
    }
    
    /**
     * Set user's progress
     */
    public function setUserProgress($userId, $progress) {
        $this->userProgress[$userId] = $progress;
        $this->saveUserProgress();
    }
    
    /**
     * Get next video for user
     */
    public function getNextVideo($userId) {
        $currentIndex = $this->getUserProgress($userId);
        
        if ($currentIndex >= count($this->fileIds)) {
            return null;
        }
        
        $fileId = $this->fileIds[$currentIndex];
        $this->setUserProgress($userId, $currentIndex + 1);
        
        return [
            'file_id' => $fileId,
            'index' => $currentIndex + 1,
            'total' => count($this->fileIds)
        ];
    }
    
    /**
     * Get random video
     */
    public function getRandomVideo() {
        if (empty($this->fileIds)) {
            return null;
        }
        
        // Ensure we have a valid array
        $videos = array_values($this->fileIds);
        if (count($videos) === 0) {
            return null;
        }
        
        // Get a random index
        $randomIndex = array_rand($videos);
        
        // Ensure we have a valid file_id
        if (!isset($videos[$randomIndex]) || empty($videos[$randomIndex])) {
            return null;
        }
        
        return [
            'file_id' => $videos[$randomIndex],
            'index' => $randomIndex + 1,
            'total' => count($videos)
        ];
    }
    
    /**
     * Reset user progress
     */
    public function resetUserProgress($userId) {
        $this->setUserProgress($userId, 0);
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        $progress = $this->getUserProgress($userId);
        $total = $this->getTotalVideos();
        
        return [
            'current_index' => $progress,
            'total_videos' => $total,
            'remaining' => $total - $progress,
            'percentage' => $total > 0 ? round(($progress / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Add new file ID (for webhook capture)
     */
    public function addFileId($fileId) {
        if (!in_array($fileId, $this->fileIds)) {
            $this->fileIds[] = $fileId;
            file_put_contents(FILE_IDS_PATH, json_encode($this->fileIds, JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        return [
            'total_videos' => count($this->fileIds),
            'total_users' => count($this->userProgress),
            'average_progress' => count($this->userProgress) > 0 ? 
                round(array_sum($this->userProgress) / count($this->userProgress), 2) : 0
        ];
    }
}
?>

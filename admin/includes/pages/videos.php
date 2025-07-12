<!-- Videos Page -->
<div id="videos" class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-video"></i> Video Library</h3>
            <button class="btn btn-primary" onclick="openModal('videoModal')">
                <i class="fas fa-plus"></i> Add Video
            </button>
        </div>
        <div class="card-subtitle">
            <p>Total videos: <strong><?php echo $systemStats['total_videos']; ?></strong></p>
            <p>Auto-deletion after: <strong><?php echo DELETE_AFTER_MINUTES; ?> minutes</strong></p>
        </div>
        <div id="videoList" class="loading">
            <div class="spinner"></div>
            Loading video library...
        </div>
    </div>
</div>

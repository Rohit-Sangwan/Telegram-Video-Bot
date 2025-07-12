<!-- Dashboard Page -->
<div id="dashboard" class="page-content active">
    <div class="dashboard-cards">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Total Videos</div>
                <div class="card-icon blue">
                    <i class="fas fa-video"></i>
                </div>
            </div>
            <div class="card-value" id="totalVideos"><?php echo $systemStats['total_videos']; ?></div>
            <div class="card-subtitle">Videos in library</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Total Users</div>
                <div class="card-icon green">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="card-value" id="totalUsers"><?php echo $systemStats['total_users']; ?></div>
            <div class="card-subtitle">Registered users</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Avg Progress</div>
                <div class="card-icon orange">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="card-value" id="avgProgress"><?php echo $systemStats['average_progress']; ?>%</div>
            <div class="card-subtitle">User completion rate</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Queue Size</div>
                <div class="card-icon purple">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="card-value" id="queueSize"><?php echo count($deletionQueue); ?></div>
            <div class="card-subtitle">Pending deletions</div>
        </div>
    </div>

    <div class="logs-container">
        <div class="logs-header">
            <h3><i class="fas fa-chart-bar"></i> Recent Activity</h3>
            <button class="btn btn-primary" onclick="refreshData()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
        <div id="recentLogs" class="loading">
            <div class="spinner"></div>
            Loading recent activity...
        </div>
    </div>
</div>

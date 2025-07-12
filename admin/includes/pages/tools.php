<!-- Tools Page -->
<div id="tools" class="page-content">
    <div class="dashboard-cards">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-broadcast-tower"></i> Broadcast Message</h3>
            </div>
            <div class="form-group">
                <textarea id="broadcastMessage" placeholder="Enter your message..." rows="4"></textarea>
            </div>
            <button class="btn btn-primary" onclick="sendBroadcast()">
                <i class="fas fa-paper-plane"></i> Send Broadcast
            </button>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database"></i> Database Actions</h3>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn btn-danger" onclick="clearAllProgress()">
                    <i class="fas fa-trash"></i> Clear All Progress
                </button>
                <button class="btn btn-danger" onclick="clearAllLogs()">
                    <i class="fas fa-file-alt"></i> Clear All Logs
                </button>
                <button class="btn btn-primary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Statistics</h3>
            </div>
            <canvas id="statsChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

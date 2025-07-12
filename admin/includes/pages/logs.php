<!-- Logs Page -->
<div id="logs" class="page-content">
    <div class="logs-container">
        <div class="logs-header">
            <h3><i class="fas fa-file-alt"></i> System Logs</h3>
            <div>
                <button class="btn btn-primary" onclick="refreshLogs()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
                <button class="btn btn-danger" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </div>
        </div>
        <div id="systemLogs" class="loading">
            <div class="spinner"></div>
            Loading system logs...
        </div>
    </div>
</div>

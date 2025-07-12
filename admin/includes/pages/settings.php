<!-- Settings Page -->
<div id="settings" class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-cog"></i> Bot Settings</h3>
        </div>
        <div class="form-group">
            <label>Bot Token</label>
            <input type="text" value="<?php echo substr(BOT_TOKEN, 0, 20) . '...'; ?>" disabled>
        </div>
        <div class="form-group">
            <label>Webhook URL</label>
            <input type="text" value="<?php echo WEBHOOK_URL; ?>" disabled>
        </div>
        <div class="form-group">
            <label>Delete After (minutes)</label>
            <input type="number" value="<?php echo DELETE_AFTER_MINUTES; ?>" id="deleteAfter">
        </div>
        <div class="form-group">
            <label>Admin Username</label>
            <input type="text" value="<?php echo ADMIN_USERNAME; ?>" disabled>
        </div>
        <button class="btn btn-primary" onclick="saveSettings()">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </div>
</div>

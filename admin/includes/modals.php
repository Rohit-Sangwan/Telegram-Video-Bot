<!-- Modals -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('userModal')">&times;</span>
        <h2>Add User</h2>
        <div class="form-group">
            <label>User ID</label>
            <input type="text" id="newUserId" placeholder="Enter user ID">
        </div>
        <div class="form-group">
            <label>Initial Progress</label>
            <input type="number" id="newUserProgress" value="0" min="0">
        </div>
        <button class="btn btn-primary" onclick="addUser()">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
</div>

<div id="videoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('videoModal')">&times;</span>
        <h2>Add Video</h2>
        <div class="form-group">
            <label>Video File ID</label>
            <input type="text" id="newVideoId" placeholder="Enter Telegram file ID">
        </div>
        <button class="btn btn-primary" onclick="addVideo()">
            <i class="fas fa-plus"></i> Add Video
        </button>
    </div>
</div>

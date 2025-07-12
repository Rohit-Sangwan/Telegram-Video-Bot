<!-- Users Page -->
<div id="users" class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> User Management</h3>
            <button class="btn btn-primary" onclick="openModal('userModal')">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
        <table class="users-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Progress</th>
                    <th>Percentage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($userProgress as $userId => $progress): ?>
                    <?php 
                        $percentage = $systemStats['total_videos'] > 0 ? 
                            round(($progress / $systemStats['total_videos']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($userId); ?></td>
                        <td><?php echo $progress; ?>/<?php echo $systemStats['total_videos']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <?php echo $percentage; ?>%
                        </td>
                        <td>
                            <button class="btn btn-danger" onclick="resetUserProgress('<?php echo $userId; ?>')">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

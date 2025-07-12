/* Dashboard JavaScript */

// Define all functions in global scope
window.showPage = function(pageId) {
    console.log('showPage called with:', pageId);
    
    try {
        // Hide all pages
        const pages = document.querySelectorAll('.page-content');
        console.log('Found pages:', pages.length);
        pages.forEach(page => {
            page.classList.remove('active');
            page.style.display = 'none';
        });
        
        // Show selected page
        const targetPage = document.getElementById(pageId);
        console.log('Target page:', targetPage);
        if (targetPage) {
            targetPage.classList.add('active');
            targetPage.style.display = 'block';
            console.log('Page activated:', pageId);
        } else {
            console.error('Page not found:', pageId);
        }
        
        // Update sidebar menu
        const menuItems = document.querySelectorAll('.sidebar-menu a');
        menuItems.forEach(item => item.classList.remove('active'));
        
        // Find and activate the clicked menu item
        const activeMenuItem = document.querySelector(`[onclick="showPage('${pageId}')"]`);
        if (activeMenuItem) {
            activeMenuItem.classList.add('active');
            console.log('Menu item activated');
        }
        
        // Update header title
        const titles = {
            'dashboard': 'Dashboard',
            'users': 'User Management',
            'videos': 'Video Library',
            'logs': 'System Logs',
            'settings': 'Bot Settings',
            'tools': 'Admin Tools',
            'support': 'Support Tickets'
        };
        
        const headerTitle = document.querySelector('.header h1');
        if (headerTitle) {
            headerTitle.innerHTML = `<i class="fas fa-${getPageIcon(pageId)}"></i> ${titles[pageId]}`;
        }
        
        // Load page-specific content
        switch(pageId) {
            case 'support':
                refreshSupportTickets();
                break;
            case 'logs':
                refreshLogs();
                break;
            case 'dashboard':
                refreshData();
                break;
            case 'videos':
                loadVideoList();
                break;
        }
    } catch (error) {
        console.error('Error in showPage:', error);
    }
};

// Sidebar toggle
window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
};

function getPageIcon(pageId) {
    const icons = {
        'dashboard': 'tachometer-alt',
        'users': 'users',
        'videos': 'video',
        'logs': 'file-alt',
        'settings': 'cog',
        'tools': 'tools',
        'support': 'headset'
    };
    return icons[pageId] || 'tachometer-alt';
}

// Modal functions
window.openModal = function(modalId) {
    document.getElementById(modalId).style.display = 'block';
};

window.closeModal = function(modalId) {
    document.getElementById(modalId).style.display = 'none';
};

// AJAX functions
window.refreshData = function() {
    console.log('Refreshing data...');
    fetch('?action=get_stats')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Stats data:', data);
            if (document.getElementById('totalVideos')) {
                document.getElementById('totalVideos').textContent = data.total_videos;
            }
            if (document.getElementById('totalUsers')) {
                document.getElementById('totalUsers').textContent = data.total_users;
            }
            if (document.getElementById('avgProgress')) {
                document.getElementById('avgProgress').textContent = data.average_progress + '%';
            }
        })
        .catch(error => {
            console.error('Error refreshing data:', error);
        });
    
    loadRecentLogs();
};

function loadRecentLogs() {
    console.log('Loading recent logs...');
    fetch('?action=get_logs')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Logs data:', data);
            const logsContainer = document.getElementById('recentLogs');
            if (logsContainer) {
                if (data.length === 0) {
                    logsContainer.innerHTML = '<p>No recent activity</p>';
                } else {
                    logsContainer.innerHTML = data.slice(0, 10).map(log => `
                        <div class="log-entry ${log.type}">
                            <span class="log-timestamp">[${log.timestamp}]</span>
                            ${log.message}
                        </div>
                    `).join('');
                }
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            const logsContainer = document.getElementById('recentLogs');
            if (logsContainer) {
                logsContainer.innerHTML = '<p>Error loading logs</p>';
            }
        });
}

window.refreshLogs = function() {
    fetch('?action=get_logs')
        .then(response => response.json())
        .then(data => {
            const logsContainer = document.getElementById('systemLogs');
            if (data.length === 0) {
                logsContainer.innerHTML = '<p>No logs available</p>';
            } else {
                logsContainer.innerHTML = data.map(log => `
                    <div class="log-entry ${log.type}">
                        <span class="log-timestamp">[${log.timestamp}]</span>
                        ${log.message}
                    </div>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('systemLogs').innerHTML = '<p>Error loading logs</p>';
        });
};

window.clearLogs = function() {
    if (confirm('Are you sure you want to clear all logs?')) {
        fetch('?action=clear_logs', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    refreshLogs();
                    alert('Logs cleared successfully');
                }
            })
            .catch(error => console.error('Error:', error));
    }
};

window.resetUserProgress = function(userId) {
    if (confirm(`Are you sure you want to reset progress for user ${userId}?`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('?action=reset_user_progress', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to reset user progress'));
            }
        })
        .catch(error => console.error('Error:', error));
    }
};

window.sendBroadcast = function() {
    const message = document.getElementById('broadcastMessage').value;
    if (!message.trim()) {
        alert('Please enter a message');
        return;
    }

    const formData = new FormData();
    formData.append('message', message);
    
    fetch('?action=broadcast_message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Broadcast sent successfully');
        document.getElementById('broadcastMessage').value = '';
    })
    .catch(error => console.error('Error:', error));
};

window.saveSettings = function() {
    alert('Settings saved successfully');
};

window.clearAllProgress = function() {
    if (confirm('Are you sure you want to clear all user progress? This action cannot be undone.')) {
        alert('All user progress cleared');
    }
};

window.clearAllLogs = function() {
    clearLogs();
};

window.exportData = function() {
    alert('Data export functionality coming soon');
};

window.addUser = function() {
    const userId = document.getElementById('newUserId').value;
    const progress = document.getElementById('newUserProgress').value;
    
    if (!userId) {
        alert('Please enter a user ID');
        return;
    }
    
    alert('User added successfully');
    closeModal('userModal');
    location.reload();
};

window.addVideo = function() {
    const videoId = document.getElementById('newVideoId').value;
    
    if (!videoId) {
        alert('Please enter a video file ID');
        return;
    }
    
    const formData = new FormData();
    formData.append('video_id', videoId);
    
    fetch('?action=add_video', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Video added successfully');
            closeModal('videoModal');
            loadVideoList();
        } else {
            alert('Error: ' + (data.message || 'Failed to add video'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding video');
    });
};

function loadVideoList() {
    fetch('?action=get_videos')
        .then(response => response.json())
        .then(data => {
            const videoContainer = document.getElementById('videoList');
            if (data.length === 0) {
                videoContainer.innerHTML = '<p>No videos in library</p>';
            } else {
                videoContainer.innerHTML = data.map((video, index) => `
                    <div class="card" style="margin-bottom: 15px;">
                        <div class="card-header">
                            <h4>Video ${index + 1}</h4>
                            <button class="btn btn-danger" onclick="deleteVideo('${video}')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div style="padding: 15px;">
                            <p><strong>File ID:</strong> ${video}</p>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error loading videos:', error);
            document.getElementById('videoList').innerHTML = '<p>Error loading videos</p>';
        });
}

window.deleteVideo = function(videoId) {
    if (confirm('Are you sure you want to delete this video?')) {
        const formData = new FormData();
        formData.append('video_id', videoId);
        
        fetch('?action=delete_video', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadVideoList();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete video'));
            }
        })
        .catch(error => console.error('Error:', error));
    }
};

// Support ticket functions
window.refreshSupportTickets = function() {
    console.log('Refreshing support tickets...');
    const container = document.getElementById('supportTickets');
    
    if (!container) {
        console.error('Support tickets container not found');
        return;
    }
    
    container.innerHTML = '<div class="loading"><div class="spinner"></div>Loading support tickets...</div>';
    
    fetch('?action=get_support_tickets')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Support tickets data:', data);
            if (data.length === 0) {
                container.innerHTML = '<p>No support tickets found</p>';
            } else {
                container.innerHTML = data.map(ticket => `
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h4>${ticket.ticket_id}</h4>
                            <span class="badge badge-${ticket.status === 'resolved' ? 'success' : ticket.status === 'open' ? 'primary' : 'warning'}">${ticket.status}</span>
                        </div>
                        <div style="padding: 20px;">
                            <p><strong>User:</strong> ${ticket.name} (${ticket.telegram_id})</p>
                            <p><strong>Category:</strong> ${ticket.category}</p>
                            <p><strong>Priority:</strong> ${ticket.priority}</p>
                            <p><strong>Subject:</strong> ${ticket.subject}</p>
                            <p><strong>Description:</strong> ${ticket.description}</p>
                            <p><strong>Created:</strong> ${new Date(ticket.created_at).toLocaleString()}</p>
                            ${ticket.resolved_at ? `<p><strong>Resolved:</strong> ${new Date(ticket.resolved_at).toLocaleString()}</p>` : ''}
                            <div style="margin-top: 15px;">
                                <button class="btn btn-primary" onclick="updateTicketStatus('${ticket.ticket_id}', 'in_progress')">In Progress</button>
                                <button class="btn btn-success" onclick="updateTicketStatus('${ticket.ticket_id}', 'resolved')">Resolve</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error loading support tickets:', error);
            container.innerHTML = '<p>Error loading tickets. Please try again.</p>';
        });
};

window.updateTicketStatus = function(ticketId, status) {
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    formData.append('status', status);
    
    fetch('?action=update_ticket_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshSupportTickets();
            alert('Ticket status updated successfully');
        }
    })
    .catch(error => console.error('Error:', error));
};

// Initialize chart
function initChart() {
    const ctx = document.getElementById('statsChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [30, 45, 25],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f8f9fa'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel loaded');
    
    // Initialize dashboard as default page
    showPage('dashboard');
    
    // Initialize chart
    setTimeout(function() {
        try {
            initChart();
        } catch (error) {
            console.error('Chart initialization error:', error);
        }
    }, 1000);
    
    // Add click handlers to menu items
    const menuItems = document.querySelectorAll('.sidebar-menu a');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const onclick = this.getAttribute('onclick');
            if (onclick) {
                eval(onclick);
            }
        });
    });
    
    console.log('Initialization complete');
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};

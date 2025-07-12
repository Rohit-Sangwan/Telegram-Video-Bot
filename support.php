<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Bot - Support Ticket</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .priority-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .priority-option {
            background: #f8f9fa;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .priority-option:hover {
            border-color: #667eea;
            background: white;
        }

        .priority-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .priority-option .icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .priority-option .text {
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            display: none;
        }

        .ticket-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }

        .ticket-info h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .ticket-info ul {
            list-style: none;
            padding: 0;
        }

        .ticket-info li {
            padding: 5px 0;
            color: #555;
        }

        .ticket-info li i {
            width: 20px;
            color: #2196f3;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e1e1e1;
            color: #666;
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .priority-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-headset"></i> Support Center</h1>
            <p>We're here to help you with any issues or questions</p>
        </div>

        <div class="content">
            <div class="ticket-info">
                <h3><i class="fas fa-info-circle"></i> Before You Submit</h3>
                <ul>
                    <li><i class="fas fa-clock"></i> Response time: 24-48 hours</li>
                    <li><i class="fas fa-shield-alt"></i> All tickets are handled confidentially</li>
                    <li><i class="fas fa-user-check"></i> Please provide detailed information</li>
                    <li><i class="fas fa-bell"></i> You'll receive updates via the bot</li>
                </ul>
            </div>

            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i> <strong>Ticket Submitted Successfully!</strong><br>
                Your support ticket has been created. You'll receive a confirmation with your ticket ID shortly.
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <span id="errorText"></span>
            </div>

            <form id="supportForm">
                <div class="form-group">
                    <label for="subject"><i class="fas fa-heading"></i> Subject</label>
                    <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
                </div>

                <div class="form-group">
                    <label for="category"><i class="fas fa-tags"></i> Issue Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="channel_join">Channel Join Issues</option>
                        <option value="video_playback">Video Playback Problems</option>
                        <option value="progress_tracking">Progress Tracking Issues</option>
                        <option value="bot_commands">Bot Commands Not Working</option>
                        <option value="technical_issues">Technical Issues</option>
                        <option value="feature_request">Feature Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exclamation-circle"></i> Priority Level</label>
                    <div class="priority-selector">
                        <div class="priority-option" data-priority="low">
                            <div class="icon">🟢</div>
                            <div class="text">Low</div>
                        </div>
                        <div class="priority-option" data-priority="medium">
                            <div class="icon">🟡</div>
                            <div class="text">Medium</div>
                        </div>
                        <div class="priority-option" data-priority="high">
                            <div class="icon">🔴</div>
                            <div class="text">High</div>
                        </div>
                        <div class="priority-option" data-priority="urgent">
                            <div class="icon">🚨</div>
                            <div class="text">Urgent</div>
                        </div>
                    </div>
                    <input type="hidden" id="priority" name="priority" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-comment"></i> Detailed Description</label>
                    <textarea id="description" name="description" required placeholder="Please provide detailed information about your issue, including steps to reproduce if applicable..."></textarea>
                </div>

                <!-- Hidden fields for auto-fetched data -->
                <input type="hidden" id="name" name="name">
                <input type="hidden" id="telegramId" name="telegramId">
                <input type="hidden" id="email" name="email">
                <input type="hidden" id="userAgent" name="userAgent">
                <input type="hidden" id="timestamp" name="timestamp">
                <input type="hidden" id="referrer" name="referrer">

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Support Ticket
                </button>
            </form>
        </div>

        <div class="footer">
            <p><i class="fas fa-robot"></i> Video Bot Support System | <i class="fas fa-clock"></i> 24/7 Available</p>
        </div>
    </div>

    <script>
        // Auto-fetch user information when page loads
        document.addEventListener('DOMContentLoaded', function() {
            autoFetchUserInfo();
        });

        function autoFetchUserInfo() {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const telegramId = urlParams.get('user_id');
            const userName = urlParams.get('name') || urlParams.get('user_name');
            
            // Auto-fill Telegram ID
            if (telegramId) {
                document.getElementById('telegramId').value = telegramId;
            }
            
            // Auto-fill name
            if (userName) {
                document.getElementById('name').value = decodeURIComponent(userName);
            }
            
            // Auto-fill user agent
            document.getElementById('userAgent').value = navigator.userAgent;
            
            // Auto-fill timestamp
            document.getElementById('timestamp').value = new Date().toISOString();
            
            // Auto-fill referrer
            document.getElementById('referrer').value = document.referrer || 'Direct Access';
            
            // Try to get user info from Telegram Web App if available
            if (window.Telegram && window.Telegram.WebApp) {
                const webApp = window.Telegram.WebApp;
                const user = webApp.initDataUnsafe.user;
                
                if (user) {
                    document.getElementById('telegramId').value = user.id;
                    document.getElementById('name').value = user.first_name + (user.last_name ? ' ' + user.last_name : '');
                    if (user.username) {
                        document.getElementById('email').value = user.username + '@telegram.user';
                    }
                }
            }
            
            // If no name is found, try to get from localStorage or set a default
            if (!document.getElementById('name').value) {
                const savedName = localStorage.getItem('supportUserName');
                if (savedName) {
                    document.getElementById('name').value = savedName;
                } else {
                    document.getElementById('name').value = 'Telegram User';
                }
            }
            
            // If no Telegram ID is found, generate a temporary one
            if (!document.getElementById('telegramId').value) {
                const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                document.getElementById('telegramId').value = tempId;
            }
            
            console.log('Auto-fetched user info:', {
                name: document.getElementById('name').value,
                telegramId: document.getElementById('telegramId').value,
                userAgent: document.getElementById('userAgent').value,
                timestamp: document.getElementById('timestamp').value,
                referrer: document.getElementById('referrer').value
            });
        }

        // Handle priority selection
        document.querySelectorAll('.priority-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.priority-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('priority').value = this.dataset.priority;
            });
        });

        // Handle form submission
        document.getElementById('supportForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            // Hide previous messages
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // Validate priority selection
            if (!document.getElementById('priority').value) {
                showError('Please select a priority level');
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            try {
                // Save user name to localStorage for future use
                localStorage.setItem('supportUserName', document.getElementById('name').value);
                
                // Collect form data
                const formData = new FormData(this);
                
                // Add timestamp and ticket ID
                const ticketId = 'TKT-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5).toUpperCase();
                formData.append('ticket_id', ticketId);
                formData.append('created_at', new Date().toISOString());
                formData.append('status', 'open');
                
                // Send to backend
                const response = await fetch('support_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successMessage.style.display = 'block';
                    successMessage.innerHTML = `
                        <i class="fas fa-check-circle"></i> <strong>Ticket Submitted Successfully!</strong><br>
                        Your ticket ID is: <strong>${ticketId}</strong><br>
                        You'll receive updates via the bot within 24-48 hours.
                    `;
                    
                    // Reset only the user-input fields
                    document.getElementById('subject').value = '';
                    document.getElementById('category').value = '';
                    document.getElementById('description').value = '';
                    document.getElementById('priority').value = '';
                    document.querySelectorAll('.priority-option').forEach(opt => opt.classList.remove('selected'));
                    
                    // Scroll to success message
                    successMessage.scrollIntoView({ behavior: 'smooth' });
                } else {
                    showError(result.message || 'Failed to submit ticket. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Network error. Please check your connection and try again.');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Support Ticket';
            }
        });
        
        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            errorText.textContent = message;
            errorMessage.style.display = 'block';
            errorMessage.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>

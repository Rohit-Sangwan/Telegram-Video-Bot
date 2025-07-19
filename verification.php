<?php
/**
 * Clean Verification Handler
 * Production Version
 */

require_once 'config.php';

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');

// Get verification parameters
$token = $_GET['token'] ?? '';
$userId = $_GET['user'] ?? '';
$action = $_GET['action'] ?? 'verify';

if (empty($token) || empty($userId)) {
    http_response_code(400);
    showError('Missing verification parameters');
    exit();
}

// Handle verification actions
switch ($action) {
    case 'verify':
        handleVerification($token, $userId);
        break;
    case 'complete':
        handleCompletion($token, $userId);
        break;
    default:
        http_response_code(400);
        showError('Invalid action');
        break;
}

/**
 * Handle verification process
 */
function handleVerification($token, $userId) {
    // Validate token
    if (!validateVerificationToken($token, $userId)) {
        showError('Invalid or expired verification token');
        return;
    }
    
    // Show verification interface
    showVerificationInterface($token, $userId);
}

/**
 * Handle verification completion
 */
function handleCompletion($token, $userId) {
    // Validate token
    if (!validateVerificationToken($token, $userId)) {
        showError('Invalid or expired verification token');
        return;
    }
    
    // Mark verification as complete
    completeVerification($token, $userId);
    
    // Show success page
    showSuccessPage($userId);
}

/**
 * Validate verification token
 */
function validateVerificationToken($token, $userId) {
    try {
        // Load verification data
        if (!file_exists(USER_VERIFICATION_PATH)) {
            return false;
        }
        
        $verificationData = json_decode(file_get_contents(USER_VERIFICATION_PATH), true) ?: [];
        
        foreach ($verificationData as $verification) {
            if ($verification['token'] === $token && 
                $verification['user_id'] == $userId && 
                !$verification['completed'] &&
                time() < $verification['expires_at']) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        logEvent("Error validating verification token: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Complete verification process
 */
function completeVerification($token, $userId) {
    try {
        // Load verification data
        $verificationData = json_decode(file_get_contents(USER_VERIFICATION_PATH), true) ?: [];
        
        // Update verification status
        foreach ($verificationData as &$verification) {
            if ($verification['token'] === $token && $verification['user_id'] == $userId) {
                $verification['completed'] = true;
                $verification['completed_at'] = time();
                break;
            }
        }
        
        // Save updated data
        file_put_contents(USER_VERIFICATION_PATH, json_encode($verificationData, JSON_PRETTY_PRINT));
        
        // Log completion
        logEvent("Verification completed for user: $userId", 'info');
        
        return true;
        
    } catch (Exception $e) {
        logEvent("Error completing verification: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Show verification interface
 */
function showVerificationInterface($token, $userId) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Required</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .verification-container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }

            .verification-icon {
                font-size: 64px;
                margin-bottom: 20px;
                display: block;
            }

            .verification-title {
                font-size: 28px;
                font-weight: 700;
                color: #2d3748;
                margin-bottom: 15px;
            }

            .verification-subtitle {
                font-size: 16px;
                color: #718096;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .verification-steps {
                text-align: left;
                background: #f7fafc;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 30px;
            }

            .verification-step {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
                font-size: 14px;
                color: #4a5568;
            }

            .verification-step:last-child {
                margin-bottom: 0;
            }

            .step-number {
                background: #667eea;
                color: white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 600;
                margin-right: 15px;
                flex-shrink: 0;
            }

            .verify-button {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 50px;
                padding: 15px 40px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
                margin-bottom: 20px;
            }

            .verify-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }

            .security-notice {
                font-size: 12px;
                color: #a0aec0;
                border-top: 1px solid #e2e8f0;
                padding-top: 20px;
                margin-top: 20px;
            }

            .progress-indicator {
                display: flex;
                justify-content: center;
                margin-bottom: 30px;
            }

            .progress-step {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 10px;
                font-size: 14px;
                font-weight: 600;
                color: #a0aec0;
            }

            .progress-step.active {
                background: #667eea;
                color: white;
            }

            .progress-step.completed {
                background: #48bb78;
                color: white;
            }

            @media (max-width: 768px) {
                .verification-container {
                    padding: 30px 20px;
                    margin: 20px;
                }

                .verification-title {
                    font-size: 24px;
                }

                .verification-icon {
                    font-size: 48px;
                }
            }
        </style>
    </head>
    <body>
        <div class="verification-container">
            <div class="progress-indicator">
                <div class="progress-step completed">✓</div>
                <div class="progress-step active">2</div>
                <div class="progress-step">3</div>
            </div>

            <div class="verification-icon">🔐</div>
            
            <h1 class="verification-title">Verification Required</h1>
            <p class="verification-subtitle">
                Complete the verification process to access premium features and continue using our service.
            </p>

            <div class="verification-steps">
                <div class="verification-step">
                    <div class="step-number">1</div>
                    <div>Click the verification button below</div>
                </div>
                <div class="verification-step">
                    <div class="step-number">2</div>
                    <div>Complete the security challenge</div>
                </div>
                <div class="verification-step">
                    <div class="step-number">3</div>
                    <div>Return to the bot for full access</div>
                </div>
            </div>

            <a href="?action=complete&token=<?php echo urlencode($token); ?>&user=<?php echo urlencode($userId); ?>" 
               class="verify-button" 
               onclick="return handleVerification()">
                🚀 Start Verification
            </a>

            <div class="security-notice">
                🔒 This verification helps protect your account and ensures secure access to our premium features.
                Your privacy and security are our top priorities.
            </div>
        </div>

        <script>
            function handleVerification() {
                // Show loading state
                const button = document.querySelector('.verify-button');
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Verifying...';
                button.style.pointerEvents = 'none';
                
                // Update progress
                const steps = document.querySelectorAll('.progress-step');
                steps[1].classList.remove('active');
                steps[1].classList.add('completed');
                steps[1].innerHTML = '✓';
                steps[2].classList.add('active');
                
                // Allow the navigation to continue
                setTimeout(() => {
                    return true;
                }, 500);
                
                return true;
            }

            // Auto-refresh token validation
            setInterval(() => {
                fetch(`?action=validate&token=<?php echo urlencode($token); ?>&user=<?php echo urlencode($userId); ?>`)
                    .then(response => {
                        if (!response.ok) {
                            window.location.href = '/error?message=Token expired';
                        }
                    })
                    .catch(error => {
                        console.error('Token validation failed:', error);
                    });
            }, 30000); // Check every 30 seconds
        </script>
    </body>
    </html>
    <?php
}

/**
 * Show success page
 */
function showSuccessPage($userId) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Complete</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .success-container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }

            .success-icon {
                font-size: 80px;
                margin-bottom: 20px;
                display: block;
                animation: bounce 1s ease-in-out;
            }

            .success-title {
                font-size: 28px;
                font-weight: 700;
                color: #2d3748;
                margin-bottom: 15px;
            }

            .success-subtitle {
                font-size: 16px;
                color: #718096;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .success-message {
                background: #f0fff4;
                border: 1px solid #9ae6b4;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 30px;
                color: #22543d;
            }

            .return-button {
                background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                color: white;
                border: none;
                border-radius: 50px;
                padding: 15px 40px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
                margin-bottom: 20px;
            }

            .return-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(72, 187, 120, 0.3);
            }

            .auto-close-notice {
                font-size: 12px;
                color: #a0aec0;
                border-top: 1px solid #e2e8f0;
                padding-top: 20px;
                margin-top: 20px;
            }

            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                60% {
                    transform: translateY(-5px);
                }
            }

            @media (max-width: 768px) {
                .success-container {
                    padding: 30px 20px;
                    margin: 20px;
                }

                .success-title {
                    font-size: 24px;
                }

                .success-icon {
                    font-size: 60px;
                }
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">✅</div>
            
            <h1 class="success-title">Verification Complete!</h1>
            <p class="success-subtitle">
                Your account has been successfully verified. You now have access to all premium features.
            </p>

            <div class="success-message">
                <h3 style="margin-bottom: 10px;">🎉 What's Next?</h3>
                <p>Return to the Telegram bot to enjoy:</p>
                <ul style="text-align: left; margin-top: 10px; padding-left: 20px;">
                    <li>Premium video access</li>
                    <li>Enhanced features</li>
                    <li>Priority support</li>
                </ul>
            </div>

            <button class="return-button" onclick="closeWindow()">
                🔙 Return to Bot
            </button>

            <div class="auto-close-notice">
                This window will close automatically in <span id="countdown">10</span> seconds.
            </div>
        </div>

        <script>
            // Countdown timer
            let countdown = 10;
            const countdownElement = document.getElementById('countdown');
            
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    closeWindow();
                }
            }, 1000);

            function closeWindow() {
                // Try to close the window
                if (window.opener) {
                    window.close();
                } else {
                    // If can't close, redirect to telegram
                    window.location.href = 'https://t.me/<?php echo BOT_USERNAME; ?>';
                }
            }

            // Log verification completion
            fetch('/api/log_verification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: '<?php echo $userId; ?>',
                    timestamp: Date.now(),
                    status: 'completed'
                })
            }).catch(e => {
                // Silent fail for logging
            });
        </script>
    </body>
    </html>
    <?php
}

/**
 * Show error page
 */
function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Error</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .error-container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }

            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
                display: block;
            }

            .error-title {
                font-size: 28px;
                font-weight: 700;
                color: #2d3748;
                margin-bottom: 15px;
            }

            .error-message {
                font-size: 16px;
                color: #718096;
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .retry-button {
                background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
                color: white;
                border: none;
                border-radius: 50px;
                padding: 15px 40px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }

            .retry-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(229, 62, 62, 0.3);
            }

            @media (max-width: 768px) {
                .error-container {
                    padding: 30px 20px;
                    margin: 20px;
                }

                .error-title {
                    font-size: 24px;
                }

                .error-icon {
                    font-size: 48px;
                }
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            
            <h1 class="error-title">Verification Error</h1>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>

            <button class="retry-button" onclick="history.back()">
                🔄 Try Again
            </button>
        </div>
    </body>
    </html>
    <?php
}
?>

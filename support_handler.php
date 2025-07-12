<?php
/**
 * Support Ticket Handler
 * Handles support ticket submissions and notifications
 */

require_once 'config.php';
require_once 'TelegramBot.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['name', 'telegramId', 'category', 'priority', 'subject', 'description'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }
    
    // Sanitize and validate input
    $ticket_data = [
        'ticket_id' => $_POST['ticket_id'] ?? 'TKT-' . time() . '-' . rand(1000, 9999),
        'name' => trim($_POST['name']),
        'telegram_id' => trim($_POST['telegramId']),
        'email' => trim($_POST['email'] ?? ''),
        'category' => $_POST['category'],
        'priority' => $_POST['priority'],
        'subject' => trim($_POST['subject']),
        'description' => trim($_POST['description']),
        'created_at' => $_POST['created_at'] ?? date('Y-m-d H:i:s'),
        'status' => 'open',
        'admin_notes' => '',
        'resolved_at' => null
    ];
    
    // Validate Telegram ID format (allow temporary IDs)
    if (!is_numeric($ticket_data['telegram_id']) && !preg_match('/^temp_/', $ticket_data['telegram_id'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid Telegram ID format'
        ]);
        exit;
    }
    
    // Validate email if provided
    if (!empty($ticket_data['email']) && !filter_var($ticket_data['email'], FILTER_VALIDATE_EMAIL)) {
        // If it's a telegram username format, allow it
        if (!preg_match('/^[a-zA-Z0-9_]+@telegram\.user$/', $ticket_data['email'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid email address format'
            ]);
            exit;
        }
    }
    
    // Load existing tickets
    $tickets_file = __DIR__ . '/data/support_tickets.json';
    $tickets = [];
    
    if (file_exists($tickets_file)) {
        $tickets = json_decode(file_get_contents($tickets_file), true) ?: [];
    }
    
    // Add new ticket
    $tickets[] = $ticket_data;
    
    // Ensure data directory exists
    if (!file_exists(dirname($tickets_file))) {
        mkdir(dirname($tickets_file), 0755, true);
    }
    
    // Save tickets
    if (file_put_contents($tickets_file, json_encode($tickets, JSON_PRETTY_PRINT))) {
        // Send confirmation to user (only if it's a real Telegram ID)
        if (is_numeric($ticket_data['telegram_id'])) {
            $bot = new TelegramBot();
            $user_message = "🎫 <b>Support Ticket Created</b>\n\n";
            $user_message .= "Hello {$ticket_data['name']}! 👋\n\n";
            $user_message .= "📋 <b>Ticket Details:</b>\n";
            $user_message .= "• ID: <code>{$ticket_data['ticket_id']}</code>\n";
            $user_message .= "• Category: {$ticket_data['category']}\n";
            $user_message .= "• Priority: " . ucfirst($ticket_data['priority']) . "\n";
            $user_message .= "• Subject: {$ticket_data['subject']}\n\n";
            $user_message .= "⏰ <b>What's Next?</b>\n";
            $user_message .= "• Our team will review your ticket\n";
            $user_message .= "• Response time: 24-48 hours\n";
            $user_message .= "• Updates will be sent here\n\n";
            $user_message .= "🆘 <b>Status:</b> Open\n";
            $user_message .= "📞 <b>Need urgent help?</b> Contact admin directly.";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🏠 Back to Bot', 'callback_data' => 'start']
                    ]
                ]
            ];
            
            $bot->sendMessage($ticket_data['telegram_id'], $user_message, $keyboard);
        }
        
        // Always notify admin
        $bot = new TelegramBot();
        $admin_message = "🆘 <b>New Support Ticket</b>\n\n";
        $admin_message .= "📋 <b>Ticket:</b> {$ticket_data['ticket_id']}\n";
        $admin_message .= "👤 <b>User:</b> {$ticket_data['name']}\n";
        $admin_message .= "🆔 <b>Telegram ID:</b> {$ticket_data['telegram_id']}\n";
        $admin_message .= "📧 <b>Email:</b> " . ($ticket_data['email'] ?: 'Not provided') . "\n";
        $admin_message .= "🏷️ <b>Category:</b> {$ticket_data['category']}\n";
        $admin_message .= "⚠️ <b>Priority:</b> " . ucfirst($ticket_data['priority']) . "\n";
        $admin_message .= "📝 <b>Subject:</b> {$ticket_data['subject']}\n\n";
        $admin_message .= "💬 <b>Description:</b>\n{$ticket_data['description']}\n\n";
        $admin_message .= "🕐 <b>Created:</b> " . date('Y-m-d H:i:s', strtotime($ticket_data['created_at']));
        
        $bot->sendMessage(1089928728, $admin_message); // Send to admin
        
        // Log the ticket creation
        logEvent("Support ticket created: {$ticket_data['ticket_id']} by user {$ticket_data['telegram_id']}", 'info');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Support ticket created successfully',
            'ticket_id' => $ticket_data['ticket_id']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save ticket. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    logEvent("Support ticket error: " . $e->getMessage(), 'error');
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request'
    ]);
}
?>

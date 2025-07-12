<?php
/**
 * Debug Support Tickets
 * Quick test to verify the support ticket system
 */

require_once '../config.php';

// Check if support tickets file exists
$ticketsPath = __DIR__ . '/../data/support_tickets.json';
echo "Checking path: $ticketsPath\n";

if (file_exists($ticketsPath)) {
    echo "✅ Support tickets file exists\n";
    
    $tickets = json_decode(file_get_contents($ticketsPath), true);
    echo "✅ File loaded successfully\n";
    echo "📊 Total tickets: " . count($tickets) . "\n";
    
    if (count($tickets) > 0) {
        echo "📋 First ticket:\n";
        print_r($tickets[0]);
    }
} else {
    echo "❌ Support tickets file does not exist\n";
    echo "Creating test file...\n";
    
    // Create the directory if it doesn't exist
    $dir = dirname($ticketsPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Create a test ticket
    $testTicket = [
        "ticket_id" => "TKT-TEST-001",
        "name" => "Test User",
        "telegram_id" => "123456789",
        "email" => "test@example.com",
        "category" => "technical_issues",
        "priority" => "medium",
        "subject" => "Test Support Ticket",
        "description" => "This is a test support ticket to verify the system is working properly.",
        "created_at" => date('Y-m-d H:i:s'),
        "status" => "open",
        "admin_notes" => "",
        "resolved_at" => null
    ];
    
    file_put_contents($ticketsPath, json_encode([$testTicket], JSON_PRETTY_PRINT));
    echo "✅ Test ticket created\n";
}

// Test the AJAX endpoint
echo "\n🔄 Testing AJAX endpoint...\n";
$_GET['action'] = 'get_support_tickets';
ob_start();
include 'includes/ajax_handlers.php';
$output = ob_get_clean();
echo "📡 AJAX Response: $output\n";
?>

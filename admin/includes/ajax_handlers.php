<?php
/**
 * Admin AJAX Handlers
 */

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_stats':
            echo json_encode($videoManager->getSystemStats());
            break;
            
        case 'get_logs':
            $logs = [];
            if (file_exists(LOGS_PATH)) {
                $logs = array_reverse(array_slice(json_decode(file_get_contents(LOGS_PATH), true) ?: [], -100));
            }
            echo json_encode($logs);
            break;
            
        case 'clear_logs':
            file_put_contents(LOGS_PATH, json_encode([]));
            echo json_encode(['success' => true]);
            break;
            
        case 'get_support_tickets':
            $tickets = [];
            $ticketsPath = __DIR__ . '/../../data/support_tickets.json';
            if (file_exists($ticketsPath)) {
                $tickets = json_decode(file_get_contents($ticketsPath), true) ?: [];
            }
            echo json_encode(array_reverse($tickets)); // Show newest first
            break;
            
        case 'update_ticket_status':
            if (isset($_POST['ticket_id']) && isset($_POST['status'])) {
                $tickets = [];
                $ticketsPath = __DIR__ . '/../../data/support_tickets.json';
                if (file_exists($ticketsPath)) {
                    $tickets = json_decode(file_get_contents($ticketsPath), true) ?: [];
                }
                
                foreach ($tickets as &$ticket) {
                    if ($ticket['ticket_id'] === $_POST['ticket_id']) {
                        $ticket['status'] = $_POST['status'];
                        if ($_POST['status'] === 'resolved') {
                            $ticket['resolved_at'] = date('Y-m-d H:i:s');
                        }
                        break;
                    }
                }
                
                file_put_contents($ticketsPath, json_encode($tickets, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'broadcast_message':
            if (isset($_POST['message'])) {
                $message = trim($_POST['message']);
                if (!empty($message)) {
                    $broadcastCount = $bot->sendBroadcastMessage($message);
                    echo json_encode(['success' => true, 'message' => "Broadcast sent to $broadcastCount users"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
                }
            }
            break;
            
        case 'reset_user_progress':
            if (isset($_POST['user_id'])) {
                $userId = $_POST['user_id'];
                $userProgress = [];
                if (file_exists(PROGRESS_PATH)) {
                    $userProgress = json_decode(file_get_contents(PROGRESS_PATH), true) ?: [];
                }
                
                if (isset($userProgress[$userId])) {
                    unset($userProgress[$userId]);
                    file_put_contents(PROGRESS_PATH, json_encode($userProgress, JSON_PRETTY_PRINT));
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            }
            break;
            
        case 'get_videos':
            $videos = [];
            if (file_exists(FILE_IDS_PATH)) {
                $videos = json_decode(file_get_contents(FILE_IDS_PATH), true) ?: [];
            }
            echo json_encode($videos);
            break;
            
        case 'add_video':
            if (isset($_POST['video_id'])) {
                $videoId = trim($_POST['video_id']);
                if (!empty($videoId)) {
                    $videos = [];
                    if (file_exists(FILE_IDS_PATH)) {
                        $videos = json_decode(file_get_contents(FILE_IDS_PATH), true) ?: [];
                    }
                    
                    if (!in_array($videoId, $videos)) {
                        $videos[] = $videoId;
                        file_put_contents(FILE_IDS_PATH, json_encode($videos, JSON_PRETTY_PRINT));
                        echo json_encode(['success' => true, 'message' => 'Video added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Video already exists']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Video ID cannot be empty']);
                }
            }
            break;
            
        case 'delete_video':
            if (isset($_POST['video_id'])) {
                $videoId = $_POST['video_id'];
                $videos = [];
                if (file_exists(FILE_IDS_PATH)) {
                    $videos = json_decode(file_get_contents(FILE_IDS_PATH), true) ?: [];
                }
                
                $key = array_search($videoId, $videos);
                if ($key !== false) {
                    unset($videos[$key]);
                    $videos = array_values($videos); // Reindex array
                    file_put_contents(FILE_IDS_PATH, json_encode($videos, JSON_PRETTY_PRINT));
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Video not found']);
                }
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
    exit;
}
?>

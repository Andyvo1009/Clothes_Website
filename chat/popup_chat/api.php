<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__) . '/chat_functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'get_user_info':
                // Get user information
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'User not found'
                    ]);
                }
                break;

            case 'send_message':
                $conversationId = getOrCreateConversation($pdo, $userId);
                $otherUserId = getOtherUserId($pdo, $conversationId, $userId);
                $message = $_POST['message'] ?? '';

                // Handle image upload if present
                $imageFile = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageFile = $_FILES['image'];
                    if (empty($message)) {
                        $message = '[Hình ảnh]'; // Default message for image-only messages
                    }
                }

                try {
                    $result = sendMessageWithImage($pdo, $conversationId, $userId, $otherUserId, $message, $imageFile);
                    echo json_encode([
                        'success' => true,
                        'message_id' => $result['message_id'],
                        'timestamp' => date('Y-m-d H:i:s'),
                        'image' => $result['image_path']
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                }
                break;

            case 'get_messages':
                $conversationId = getOrCreateConversation($pdo, $userId);
                markMessagesAsRead($pdo, $conversationId, $userId);
                $messages = getConversationMessages($pdo, $conversationId, $userId);

                echo json_encode([
                    'success' => true,
                    'messages' => $messages
                ]);
                break;

            case 'check_new_messages':
                $conversationId = getOrCreateConversation($pdo, $userId);
                $lastMessageId = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;

                $stmt = $pdo->prepare("
                    SELECT m.*, u.username as sender_username
                    FROM messages m
                    LEFT JOIN users u ON u.id = m.sender_id
                    WHERE m.conversation_id = ? AND m.id > ?
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$conversationId, $lastMessageId]);
                $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Mark new messages as read
                if (!empty($newMessages)) {
                    markMessagesAsRead($pdo, $conversationId, $userId);
                }

                echo json_encode([
                    'success' => true,
                    'messages' => $newMessages
                ]);
                break;

            case 'get_unread_count':
                $conversationId = getOrCreateConversation($pdo, $userId);

                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as unread_count 
                    FROM messages 
                    WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
                ");
                $stmt->execute([$conversationId, $userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'unread_count' => $result['unread_count']
                ]);
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action'
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}

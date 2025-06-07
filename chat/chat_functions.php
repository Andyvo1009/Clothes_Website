<?php
require_once dirname(__DIR__) . '/includes/db.php';

// Get or create conversation between user and admin
function getOrCreateConversation($pdo, $userId)
{
    error_log("Getting/creating conversation for user ID: $userId");

    // Find admin user (assuming first admin in database)
    $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        error_log("No admin user found");
        throw new Exception("No admin user found");
    }

    $adminId = $admin['id'];
    error_log("Found admin ID: $adminId");

    // Check if conversation already exists
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE 
        (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$userId, $adminId, $adminId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        error_log("Found existing conversation ID: " . $conversation['id']);
        return $conversation['id'];
    }

    // Create new conversation
    $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
    $stmt->execute([$userId, $adminId]);
    $newConvId = $pdo->lastInsertId();
    error_log("Created new conversation ID: $newConvId");
    return $newConvId;
}

// Get all conversations for admin
function getAdminConversations($pdo, $adminId)
{
    $stmt = $pdo->prepare("
        SELECT c.id, c.user1_id, c.user2_id, c.created_at,
               u.username as client_username,
               (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM conversations c
        LEFT JOIN users u ON (u.id = c.user1_id AND u.id != ?) OR (u.id = c.user2_id AND u.id != ?)
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get messages for a conversation
function getConversationMessages($pdo, $conversationId, $userId = null)
{
    error_log("Getting messages for conversation ID: $conversationId, User ID: $userId");

    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_username
        FROM messages m
        LEFT JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($messages) . " messages");

    return $messages;
}

// Send message
function sendMessage($pdo, $conversationId, $senderId, $receiverId, $message)
{
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, receiver_id, message) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$conversationId, $senderId, $receiverId, $message]);
    return $pdo->lastInsertId();
}

// Mark messages as read
function markMessagesAsRead($pdo, $conversationId, $userId)
{
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE conversation_id = ? AND receiver_id = ?
    ");
    $stmt->execute([$conversationId, $userId]);
}

// Get user role
function getUserRole($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['role'] : null;
}

// Get other user in conversation
function getOtherUserId($pdo, $conversationId, $currentUserId)
{
    $stmt = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conv) return null;

    return ($conv['user1_id'] == $currentUserId) ? $conv['user2_id'] : $conv['user1_id'];
}

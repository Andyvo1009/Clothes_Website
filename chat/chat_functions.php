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
               u.email as client_email,
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
        SELECT m.*, u.email as sender_email
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
function sendMessage($pdo, $conversationId, $senderId, $receiverId, $message, $imagePath = null)
{
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, receiver_id, message, image) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$conversationId, $senderId, $receiverId, $message, $imagePath]);
    return $pdo->lastInsertId();
}

// Send message with image (two-step process for custom image path)
function sendMessageWithImage($pdo, $conversationId, $senderId, $receiverId, $message, $imageFile = null)
{
    // First, insert the message without image to get the message ID
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, receiver_id, message, image) 
        VALUES (?, ?, ?, ?, NULL)
    ");
    $stmt->execute([$conversationId, $senderId, $receiverId, $message]);
    $messageId = $pdo->lastInsertId();

    $imagePath = null;

    // If there's an image, upload it and update the message
    if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadChatImageWithId($imageFile, $messageId);

        // Update the message with the image path
        $updateStmt = $pdo->prepare("UPDATE messages SET image = ? WHERE id = ?");
        $updateStmt->execute([$imagePath, $messageId]);
    }

    return ['message_id' => $messageId, 'image_path' => $imagePath];
}

// Handle image upload with message ID
function uploadChatImageWithId($file, $messageId)
{
    $uploadDir = __DIR__ . '/uploads/';

    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Chỉ cho phép upload file ảnh (JPEG, PNG, GIF)');
    }

    if ($file['size'] > $maxFileSize) {
        throw new Exception('File ảnh quá lớn. Vui lòng chọn file nhỏ hơn 5MB');
    }

    // Generate filename using message ID
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $messageId . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return 'uploads/' . $fileName; // Return relative path for database
    } else {
        throw new Exception('Không thể upload file');
    }
}

// Handle image upload
function uploadChatImage($file)
{
    $uploadDir = __DIR__ . '/uploads/';

    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Chỉ cho phép upload file ảnh (JPEG, PNG, GIF)');
    }

    if ($file['size'] > $maxFileSize) {
        throw new Exception('File ảnh quá lớn. Vui lòng chọn file nhỏ hơn 5MB');
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'chat_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return 'uploads/' . $fileName; // Return relative path for database
    } else {
        throw new Exception('Không thể upload file');
    }
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

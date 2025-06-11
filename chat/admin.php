<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once 'chat_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole($pdo, $userId);

if ($userRole !== 'admin') {
    header('Location: /FirstWebsite/index.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'get_conversations':
                $conversations = getAdminConversations($pdo, $userId);
                echo json_encode([
                    'success' => true,
                    'conversations' => $conversations
                ]);
                break;

            case 'get_messages':
                $conversationId = (int)$_POST['conversation_id'];
                markMessagesAsRead($pdo, $conversationId, $userId);
                $messages = getConversationMessages($pdo, $conversationId);

                echo json_encode([
                    'success' => true,
                    'messages' => $messages
                ]);
                break;

            case 'send_message':
                $conversationId = (int)$_POST['conversation_id'];
                $otherUserId = getOtherUserId($pdo, $conversationId, $userId);

                // Handle image upload if present
                $imageFile = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageFile = $_FILES['image'];
                    if (empty($_POST['message'])) {
                        $message = '[Hình ảnh]'; // Default message for image-only messages
                    } else {
                        $message = $_POST['message'];
                    }

                    $result = sendMessageWithImage($pdo, $conversationId, $userId, $otherUserId, $message, $imageFile);

                    echo json_encode([
                        'success' => true,
                        'message_id' => $result['message_id'],
                        'timestamp' => date('Y-m-d H:i:s'),
                        'image' => $result['image_path']
                    ]);
                } else {
                    $message = $_POST['message'];
                    $messageId = sendMessage($pdo, $conversationId, $userId, $otherUserId, $message);

                    echo json_encode([
                        'success' => true,
                        'message_id' => $messageId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
                break;

            case 'check_new_messages':
                $conversationId = (int)$_POST['conversation_id'];
                $lastMessageId = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;

                $stmt = $pdo->prepare("
                    SELECT m.*, u.email as sender_email
                    FROM messages m
                    LEFT JOIN users u ON u.id = m.sender_id
                    WHERE m.conversation_id = ? AND m.id > ?
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$conversationId, $lastMessageId]);
                $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'messages' => $newMessages
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get all conversations
$conversations = getAdminConversations($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .admin-chat-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: #f8f9fa;
        }


        .conversations-panel {
            width: 350px;
            background: white;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            background: #c62828;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.3s;
            position: relative;
        }

        .conversation-item:hover {
            background: #f8f9fa;
        }

        .conversation-item.active {
            background: #e3f2fd;
            border-left: 4px solid #c62828;
        }

        .conversation-email {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .conversation-last-message {
            color: #666;
            font-size: 0.9rem;
            max-height: 2.4em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .conversation-time {
            color: #999;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .unread-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #c62828;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message.sent {
            align-items: flex-end;
        }

        .message.received {
            align-items: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: #c62828;
            color: white;
        }

        .message.received .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
        }

        .message-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            padding: 0 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input input[type="text"] {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
        }

        .chat-input input[type="file"] {
            display: none;
        }

        .image-upload-btn {
            background: #6c757d;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .image-upload-btn:hover {
            background: #5a6268;
        }

        .message-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        .message-bubble.image-message {
            padding: 4px;
            background: transparent;
            border: none;
        }

        .message.sent .message-bubble.image-message {
            background: transparent;
        }

        .chat-input button {
            background: #c62828;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-input button:hover {
            background: #b71c1c;
        }

        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            font-size: 1.2rem;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            cursor: pointer;
        }

        .image-modal img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .image-modal .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="admin-chat-container">
        <div class="conversations-panel">
            <div class="panel-header">
                <a href="/FirstWebsite/admin/products.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h3><i class="fas fa-comments"></i> Cuộc trò chuyện</h3>
                    <small>Quản lý chat khách hàng</small>
                </div>
            </div>

            <div class="conversations-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: #666;">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px; color: #ddd;"></i>
                        <p>Chưa có cuộc trò chuyện nào</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item" data-id="<?= $conv['id'] ?>" onclick="selectConversation(<?= $conv['id'] ?>, '<?= htmlspecialchars($conv['client_email']) ?>')">
                            <div class="conversation-email">
                                <?= htmlspecialchars($conv['client_email']) ?>
                            </div>
                            <?php if ($conv['last_message']): ?>
                                <div class="conversation-last-message">
                                    <?= htmlspecialchars($conv['last_message']) ?>
                                </div>
                                <div class="conversation-time">
                                    <?= $conv['last_message_time'] ? date('d/m H:i', strtotime($conv['last_message_time'])) : '' ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <div class="unread-badge"><?= $conv['unread_count'] ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-panel">
            <div class="chat-header" id="chatHeader" style="display: none;">
                <div>
                    <h3 id="chatWithemail"></h3>
                    <small>Đang trò chuyện với khách hàng</small>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="no-conversation">
                    <div>
                        <i class="fas fa-comment-dots" style="font-size: 4rem; margin-bottom: 20px; color: #ddd;"></i>
                        <p>Chọn một cuộc trò chuyện để bắt đầu</p>
                    </div>
                </div>
            </div>

            <div class="chat-input" id="chatInput" style="display: none;">
                <input type="text" id="messageInput" placeholder="Nhập tin nhắn..." maxlength="1000">
                <input type="file" id="imageInput" accept="image/*">
                <button type="button" class="image-upload-btn" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-image"></i>
                </button>
                <button onclick="sendMessage()" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <span class="close" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="Hình ảnh lớn">
    </div>

    <script>
        let currentConversationId = null;
        let lastMessageId = 0;
        let checkMessagesInterval = null;

        function selectConversation(conversationId, email) {
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-id="${conversationId}"]`).classList.add('active');

            // Clear previous interval
            if (checkMessagesInterval) {
                clearInterval(checkMessagesInterval);
            }

            currentConversationId = conversationId;
            lastMessageId = 0;

            // Update header
            document.getElementById('chatWithemail').textContent = email;
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('chatInput').style.display = 'flex';

            // Load messages
            loadMessages();

            // Start checking for new messages
            checkMessagesInterval = setInterval(checkNewMessages, 2000);

            // Clear unread badge
            const badge = document.querySelector(`[data-id="${conversationId}"] .unread-badge`);
            if (badge) badge.remove();
        }

        function loadMessages() {
            if (!currentConversationId) return;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_messages&conversation_id=${currentConversationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const messagesDiv = document.getElementById('chatMessages');
                        messagesDiv.innerHTML = '';

                        data.messages.forEach(msg => {
                            addMessage(msg.message, msg.sender_id == <?= $userId ?> ? 'sent' : 'received', msg.created_at, msg.sender_email, msg.image);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });

                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function sendMessage() {
            if (!currentConversationId) return;

            const input = document.getElementById('messageInput');
            const imageInput = document.getElementById('imageInput');
            const message = input.value.trim();
            const hasImage = imageInput.files.length > 0;

            if (!message && !hasImage) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            input.disabled = true;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('conversation_id', currentConversationId);
            formData.append('message', message);

            if (hasImage) {
                formData.append('image', imageInput.files[0]);
            }

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        imageInput.value = '';
                        addMessage(message || '[Hình ảnh]', 'sent', data.timestamp, 'Admin', data.image);
                        lastMessageId = data.message_id;
                    } else {
                        alert('Lỗi gửi tin nhắn: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi kết nối');
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    input.disabled = false;
                    input.focus();
                });
        }

        function addMessage(text, type, timestamp, senderName, imagePath = null) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;

            // Debug logging
            if (imagePath) {
                console.log('Image path received:', imagePath);
            }

            const now = timestamp ? new Date(timestamp) : new Date();
            const timeStr = now.toLocaleDateString('vi-VN') + ' ' +
                now.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

            let messageContent = '';

            if (imagePath) {
                // Remove 'uploads/' prefix if it exists since we're adding it in the src
                const cleanImagePath = imagePath.startsWith('uploads/') ? imagePath.substring(8) : imagePath;
                messageContent = `
                    <div class="message-bubble image-message">
                        <img src="/FirstWebsite/chat/uploads/${cleanImagePath}" alt="Hình ảnh" class="message-image" onclick="openImageModal(this.src)">
                        ${text && text !== '[Hình ảnh]' ? `<div style="margin-top: 8px; padding: 8px 12px; background: ${type === 'sent' ? '#c62828' : 'white'}; border-radius: 12px; ${type === 'sent' ? 'color: white;' : 'color: #333; border: 1px solid #e0e0e0;'}">${text.replace(/\n/g, '<br>')}</div>` : ''}
                    </div>
                `;
            } else {
                messageContent = `
                    <div class="message-bubble">
                        ${text.replace(/\n/g, '<br>')}
                    </div>
                `;
            }

            messageDiv.innerHTML = `
                ${messageContent}
                <div class="message-info">
                    <span>${senderName || (type === 'sent' ? 'Admin' : 'Khách hàng')}</span>
                    <span>${timeStr}</span>
                </div>
            `;

            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function checkNewMessages() {
            if (!currentConversationId) return;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_new_messages&conversation_id=${currentConversationId}&last_message_id=${lastMessageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            addMessage(msg.message, msg.sender_id == <?= $userId ?> ? 'sent' : 'received', msg.created_at, msg.sender_email, msg.image);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                    }
                })
                .catch(error => console.error('Error checking messages:', error));
        }

        // Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Image upload handling
        document.getElementById('imageInput').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert('Kích thước file không được vượt quá 5MB');
                    e.target.value = '';
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF)');
                    e.target.value = '';
                    return;
                }
            }
        });

        // Image modal functions
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = imageSrc;
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Refresh conversations every 10 seconds
        setInterval(function() {
            if (!currentConversationId) {
                // Only refresh the conversations list if no conversation is selected
                // This prevents disrupting the current chat
                location.reload();
            }
        }, 10000);
    </script>
</body>

</html>
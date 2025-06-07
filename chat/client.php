<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once 'chat_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole($pdo, $userId);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'send_message':
                $conversationId = getOrCreateConversation($pdo, $userId);
                $otherUserId = getOtherUserId($pdo, $conversationId, $userId);
                $messageId = sendMessage($pdo, $conversationId, $userId, $otherUserId, $_POST['message']);

                echo json_encode([
                    'success' => true,
                    'message_id' => $messageId,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
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
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get conversation and messages for display
try {
    $conversationId = getOrCreateConversation($pdo, $userId);
    markMessagesAsRead($pdo, $conversationId, $userId);
    $messages = getConversationMessages($pdo, $conversationId, $userId);
} catch (Exception $e) {
    $messages = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .chat-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 700px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .header-info h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .header-info small {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .chat-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4CAF50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .chat-messages {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background: #fafafa;
            background-image:
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.2) 2px, transparent 0),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.2) 2px, transparent 0);
            background-size: 100px 100px;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 15px;
            border-left: 4px solid #2196F3;
        }

        .welcome-message h4 {
            color: #1976d2;
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }

        .welcome-message p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            animation: messageSlide 0.3s ease-out;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            align-items: flex-end;
        }

        .message.received {
            align-items: flex-start;
        }

        .message-bubble {
            max-width: 75%;
            padding: 15px 20px;
            border-radius: 20px;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.received .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 5px;
        }

        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 8px;
            padding: 0 5px;
        }

        .chat-input {
            padding: 25px 30px;
            border-top: 1px solid #e0e0e0;
            background: white;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            outline: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .chat-input input:focus {
            border-color: #c62828;
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        .chat-input button {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
            color: white;
            border: none;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .chat-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .chat-input button:active {
            transform: translateY(0);
        }

        .chat-input button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .chat-container {
                height: calc(100vh - 20px);
                max-width: 100%;
                border-radius: 10px;
            }

            .chat-header {
                padding: 15px 20px;
            }

            .header-info h3 {
                font-size: 1.1rem;
            }

            .chat-messages {
                padding: 15px;
            }

            .chat-input {
                padding: 15px 20px;
            }

            .message-bubble {
                max-width: 85%;
                padding: 12px 16px;
            }
        }

        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="header-left">
                <a href="/FirstWebsite/index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Quay lại</span>
                </a>
                <div class="header-info">
                    <h3><i class="fas fa-headset"></i> Hỗ trợ khách hàng VPF</h3>
                    <small>Chúng tôi luôn sẵn sàng hỗ trợ bạn</small>
                </div>
            </div>
            <div class="chat-status">
                <div class="status-dot"></div>
                <span>Đang hoạt động</span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php if (isset($error)): ?>
                <div class="message received">
                    <div class="message-bubble">
                        <strong>Lỗi:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="welcome-message">
                    <h4><i class="fas fa-heart"></i> Chào mừng bạn đến với dịch vụ hỗ trợ VPF Fashion!</h4>
                    <p>Chúng tôi sẽ trả lời tin nhắn của bạn trong thời gian sớm nhất. Hãy mô tả vấn đề bạn đang gặp phải.</p>
                </div>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?= $message['sender_id'] == $userId ? 'sent' : 'received' ?>">
                        <div class="message-bubble">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                        </div>
                        <div class="message-time">
                            <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input">
            <div class="input-wrapper">
                <input type="text" id="messageInput" placeholder="Nhập tin nhắn của bạn..." maxlength="1000">
                <button onclick="sendMessage()" id="sendBtn" title="Gửi tin nhắn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (!message) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            input.disabled = true;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        addMessage(message, 'sent', data.timestamp);
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

        function addMessage(text, type, timestamp) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;

            const now = timestamp ? new Date(timestamp) : new Date();
            const timeStr = now.toLocaleDateString('vi-VN') + ' ' +
                now.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

            messageDiv.innerHTML = `
                <div class="message-bubble">
                    ${text.replace(/\n/g, '<br>')}
                </div>
                <div class="message-time">${timeStr}</div>
            `;

            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function checkNewMessages() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_new_messages&last_message_id=${lastMessageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            addMessage(msg.message, 'received', msg.created_at);
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

        // Auto-scroll to bottom
        const messagesDiv = document.getElementById('chatMessages');
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        // Check for new messages every 3 seconds
        setInterval(checkNewMessages, 3000);

        // Focus on input
        document.getElementById('messageInput').focus();
    </script>
</body>

</html>
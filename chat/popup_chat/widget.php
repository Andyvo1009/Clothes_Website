<?php
// Chat Popup Widget Include
// This file should be included at the bottom of pages where you want the chat widget

// Only show chat widget if user is on the main site (not in admin or auth areas)
$current_path = $_SERVER['REQUEST_URI'];

// Default rule: hide on /admin/, /auth/, or /chat/ directory paths in the URL
$show_chat_by_path = !preg_match('/\/(admin|auth|chat)\//', $current_path);

$requesting_script_filename = basename($_SERVER['SCRIPT_FILENAME']);

// Override for specific debug/test scripts
if ($requesting_script_filename === 'debug.php' || $requesting_script_filename === 'test_chat.php') {
    $show_chat = true; // Always show for these scripts
} else {
    $show_chat = $show_chat_by_path; // Otherwise, follow the path rule
}

if ($show_chat): ?>
    <!-- Chat Popup Widget -->
    <div id="chatWidget" class="chat-widget">
        <!-- Chat Toggle Button -->
        <div id="chatToggle" class="chat-toggle">
            <i class="fas fa-comments"></i>
            <span id="chatBadge" class="chat-badge" style="display: none;">0</span>
        </div>

        <!-- Chat Popup -->
        <div id="chatPopup" class="chat-popup">
            <div class="chat-popup-header">
                <div class="chat-popup-title">
                    <i class="fas fa-headset"></i>
                    <div>
                        <h4>Hỗ trợ khách hàng</h4>
                        <small>VPF Fashion</small>
                    </div>
                </div>
                <div class="chat-popup-controls">
                    <button id="chatMinimize" class="chat-control-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button id="chatClose" class="chat-control-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="chat-popup-body">
                <div id="chatMessages" class="chat-popup-messages">
                    <div class="welcome-message">
                        <div class="welcome-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Chào mừng đến VPF Fashion!</h5>
                        <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn. Hãy để lại tin nhắn và chúng tôi sẽ phản hồi sớm nhất!</p>
                    </div>
                </div>

                <div class="chat-popup-input">
                    <div class="input-group">
                        <input type="text" id="chatMessageInput" placeholder="Nhập tin nhắn..." maxlength="1000">
                        <input type="file" id="chatImageInput" accept="image/*" style="display: none;">
                        <button id="chatImageBtn" class="chat-image-btn" title="Gửi hình ảnh">
                            <i class="fas fa-image"></i>
                        </button>
                        <button id="chatSendBtn" class="chat-send-btn" title="Gửi tin nhắn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="/FirstWebsite/chat/popup_chat/widget.css">
    <script src="/FirstWebsite/chat/popup_chat/widget.js"></script>

    <script>
        console.log('Chat widget scripts loaded');
        console.log('FontAwesome test:', document.querySelector('.fas'));
    </script>

<?php endif; ?>
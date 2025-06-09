// Chat Widget JavaScript
class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.isLoggedIn = false;
        this.currentUserId = null;
        this.lastMessageId = 0;
        this.checkInterval = null;
        this.apiUrl = '/FirstWebsite/chat/popup_chat/api.php';
        
        this.init();
    }    
    init() {
    this.bindEvents();
    this.checkLoginStatus();
    // Re-check and bind events if elements are loaded later
    setTimeout(() => this.bindEvents(), 100); // Fallback in case DOM updates
    if (this.isLoggedIn) {
        this.loadMessages();
        this.startMessageCheck();
        this.checkUnreadMessages();
    }
}

    bindEvents() {
        // Toggle chat popup
        const chatToggle = document.getElementById('chatToggle');
        console.log('Chat toggle element found:', chatToggle);
        
        if (chatToggle) {
            chatToggle.addEventListener('click', () => {
                console.log('Chat toggle clicked!');
                this.toggleChat();
            });        } else {
            console.error('Chat toggle element not found!');
        }

        // Minimize chat
        const chatMinimize = document.getElementById('chatMinimize');
        if (chatMinimize) {
            chatMinimize.addEventListener('click', () => {
                this.toggleChat();
            });
        }

        // Close chat
        const chatClose = document.getElementById('chatClose');
        if (chatClose) {
            chatClose.addEventListener('click', () => {
                this.closeChat();
            });
        }

        // Send message
        const chatSendBtn = document.getElementById('chatSendBtn');
        if (chatSendBtn) {
            chatSendBtn.addEventListener('click', () => {
                this.sendMessage();
            });
        }

        // Enter key to send
        const chatMessageInput = document.getElementById('chatMessageInput');
        if (chatMessageInput) {
            chatMessageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });
        }

        // Image upload
        const chatImageBtn = document.getElementById('chatImageBtn');
        if (chatImageBtn) {
            chatImageBtn.addEventListener('click', () => {
                if (this.isLoggedIn) {
                    document.getElementById('chatImageInput').click();
                } else {
                    this.showLoginPrompt();
                }
            });
        }

        // Handle image selection
        const chatImageInput = document.getElementById('chatImageInput');
        if (chatImageInput) {
            chatImageInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.sendMessage();
                }
            });
        }
    }

    checkLoginStatus() {
        // Check if user session exists by getting user info
        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_user_info'
        })
        .then(response => response.json())
        .then(data => {
            this.isLoggedIn = data.success;
            if (this.isLoggedIn) {
                this.currentUserId = data.user.id;
                this.loadMessages();
                this.startMessageCheck();
                this.checkUnreadMessages();
            } else {
                this.showLoginPrompt();
            }
        })
        .catch(() => {
            this.isLoggedIn = false;
            this.showLoginPrompt();        });
    }

    showLoginPrompt() {
        const messagesDiv = document.getElementById('chatMessages');
        if (messagesDiv) {
            messagesDiv.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h5>Đăng nhập để chat</h5>
                    <p>Vui lòng đăng nhập để sử dụng tính năng chat hỗ trợ khách hàng.</p>
                    <div style="margin-top: 15px;">
                        <a href="/FirstWebsite/auth/login.php" 
                           style="background: #c62828; color: white; padding: 8px 20px; 
                                  border-radius: 20px; text-decoration: none; font-size: 0.9rem;">
                            Đăng nhập
                        </a>
                    </div>
                </div>            `;
        }
    }

   toggleChat() {
    console.log('toggleChat called, current isOpen:', this.isOpen);
    this.isOpen = !this.isOpen;
    const popup = document.getElementById('chatPopup');
    console.log('Popup element found:', popup, 'Classes:', popup.className);
    
    if (this.isOpen) {
        popup.classList.add('show');
        console.log('Added show class to popup');
        if (this.isLoggedIn) {
            document.getElementById('chatMessageInput').focus();
            this.markMessagesAsRead();
        }
    } else {
        popup.classList.remove('show');
        console.log('Removed show class from popup');
    }
}

    closeChat() {
        this.isOpen = false;
        document.getElementById('chatPopup').classList.remove('show');
    }

    loadMessages() {
        if (!this.isLoggedIn) return;

        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_messages'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayMessages(data.messages);
                if (data.messages.length > 0) {
                    this.lastMessageId = Math.max(...data.messages.map(msg => msg.id));
                }
            }
        })
        .catch(error => console.error('Error loading messages:', error));
    }

    displayMessages(messages) {
        const messagesDiv = document.getElementById('chatMessages');
        messagesDiv.innerHTML = '';

        if (messages.length === 0) {
            messagesDiv.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h5>Chào mừng bạn!</h5>
                    <p>Hãy gửi tin nhắn đầu tiên để bắt đầu cuộc trò chuyện với chúng tôi.</p>
                </div>
            `;
            return;
        }

        messages.forEach(message => {
            this.addMessageToChat(message);
        });        this.scrollToBottom();
    }

    addMessageToChat(message) {
        const messagesDiv = document.getElementById('chatMessages');
        const messageElement = document.createElement('div');
        
        const isOwnMessage = parseInt(message.user_id) === parseInt(this.currentUserId);
        messageElement.className = `chat-message ${isOwnMessage ? 'sent' : 'received'}`;

        const bubbleElement = document.createElement('div');
        bubbleElement.className = 'chat-message-bubble';

        let messageContent = '';
        
        if (message.image_path) {
            bubbleElement.className += ' image-message';
            messageContent = `
                <img src="/FirstWebsite/${message.image_path}" 
                     alt="Shared image" 
                     class="chat-message-image" 
                     onclick="this.classList.toggle('expanded')">
            `;
        }
        
        if (message.message) {
            messageContent += this.escapeHtml(message.message);
        }

        bubbleElement.innerHTML = messageContent;
        messageElement.appendChild(bubbleElement);

        const timeElement = document.createElement('div');
        timeElement.className = 'chat-message-time';
        timeElement.innerHTML = `${this.escapeHtml(message.fullname || 'User')} - ${this.formatTime(message.timestamp)}`;
        messageElement.appendChild(timeElement);

        messagesDiv.appendChild(messageElement);
    }

    sendMessage() {
        if (!this.isLoggedIn) {
            this.showLoginPrompt();
            return;
        }

        const messageInput = document.getElementById('chatMessageInput');
        const imageInput = document.getElementById('chatImageInput');
        const message = messageInput.value.trim();

        if (!message && !imageInput.files.length) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'send_message');
        
        if (message) {
            formData.append('message', message);
        }
        
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }

        // Show sending indicator
        const sendBtn = document.getElementById('chatSendBtn');
        const originalText = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;

        fetch(this.apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                imageInput.value = '';
                this.loadMessages(); // Reload to show new message
            } else {
                alert('Lỗi gửi tin nhắn: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        })
        .finally(() => {
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
        });
    }

    startMessageCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }

        this.checkInterval = setInterval(() => {
            this.checkForNewMessages();
        }, 3000); // Check every 3 seconds
    }

    checkForNewMessages() {
        if (!this.isLoggedIn) return;

        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=check_new_messages&last_message_id=${this.lastMessageId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_new_messages) {
                this.loadMessages();
            }
            
            // Update unread count if chat is closed
            if (!this.isOpen) {
                this.checkUnreadMessages();
            }
        })
        .catch(error => console.error('Error checking new messages:', error));
    }

    checkUnreadMessages() {
        if (!this.isLoggedIn) return;

        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_unread_count'
        })
        .then(response => response.json())        .then(data => {
            if (data.success) {
                this.updateUnreadBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error checking unread messages:', error));
    }

    updateUnreadBadge(count) {
        const badge = document.getElementById('chatBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    markMessagesAsRead() {
        if (!this.isLoggedIn) return;

        fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_as_read'
        })
        .then(() => {
            this.updateUnreadBadge(0);
        })
        .catch(error => console.error('Error marking messages as read:', error));
    }

    scrollToBottom() {
        const messagesDiv = document.getElementById('chatMessages');
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 1) {
            return `Hôm qua ${date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`;
        } else if (diffDays <= 7) {
            return date.toLocaleDateString('vi-VN', { 
                weekday: 'short', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        } else {
            return date.toLocaleDateString('vi-VN', { 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
    }

    destroy() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Initialize chat widget when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.chatWidget === 'undefined') {
        window.chatWidget = new ChatWidget();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.chatWidget) {
        window.chatWidget.destroy();
    }
});

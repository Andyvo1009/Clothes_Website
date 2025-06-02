document.addEventListener('DOMContentLoaded', function() {
    let ws;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;
      const chatTrigger = document.querySelector('.chat-trigger');
    const chatPopup = document.querySelector('.chat-popup');
    const closeChat = document.querySelector('.close-chat');
    const messageInput = document.querySelector('.chat-input input[type="text"]');
    const sendButton = document.querySelector('.chat-input button');
    const imageUpload = document.querySelector('#imageUpload');
    const messagesContainer = document.querySelector('.chat-messages');

    // Connect to WebSocket server
    function connect() {
        try {
            ws = new WebSocket('ws://localhost:8080');
            
            ws.onopen = function() {
                console.log('Connected to chat server');
                reconnectAttempts = 0;
                showConnectionStatus('Connected', 'success');
            };
              ws.onmessage = function(e) {
                try {
                    const data = JSON.parse(e.data);
                    
                    if (data.type === 'error') {
                        console.error('Server error:', data.message);
                        showConnectionStatus('Server Error', 'error');
                        return;
                    }
                      if (data.type === 'auto-reply' && data.content && data.content.trim()) {
                        // Add a small delay to make it feel more natural
                        setTimeout(() => {
                            appendMessage(data.content, 'received');
                        }, 500 + Math.random() * 1500);
                    } else if (data.type === 'image-confirmation') {
                        setTimeout(() => {
                            appendMessage(data.content, 'received');
                        }, 500);
                    }
                } catch (error) {
                    console.error('Error parsing message:', error);
                }
            };
            
            ws.onclose = function(e) {
                console.log('Disconnected from chat server. Code:', e.code);
                showConnectionStatus('Disconnected', 'error');
                
                // Attempt to reconnect with exponential backoff
                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    const delay = reconnectDelay * Math.pow(2, reconnectAttempts - 1);
                    console.log(`Attempting to reconnect in ${delay}ms... (Attempt ${reconnectAttempts}/${maxReconnectAttempts})`);
                    setTimeout(connect, delay);
                } else {
                    showConnectionStatus('Connection Failed', 'error');
                }
            };
            
            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
                showConnectionStatus('Connection Error', 'error');
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            showConnectionStatus('Connection Failed', 'error');
        }
    }

    // Show connection status
    function showConnectionStatus(message, type) {
        // You can implement a status indicator here if needed
        console.log(`Status: ${message} (${type})`);
    }

    // Toggle chat popup
    if (chatTrigger) {
        chatTrigger.addEventListener('click', function() {
            chatPopup.classList.add('active');
            chatTrigger.style.display = 'none';
            
            // Connect to WebSocket when chat is opened if not already connected
            if (!ws || ws.readyState === WebSocket.CLOSED) {
                connect();
            }
        });
    }

    if (closeChat) {
        closeChat.addEventListener('click', function() {
            chatPopup.classList.remove('active');
            chatTrigger.style.display = 'flex';
        });
    }

    // Send message function
    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) {
            return;
        }

        if (!ws || ws.readyState !== WebSocket.OPEN) {
            appendMessage('Kết nối không khả dụng. Đang thử kết nối lại...', 'system');
            connect();
            return;        }        try {
            const data = {
                type: 'text',
                content: message,
                timestamp: new Date().toISOString()
            };
            
            ws.send(JSON.stringify(data));
            appendMessage(message, 'sent');
            messageInput.value = '';
            
            // Remove auto reply - server will handle it
        } catch (error) {
            console.error('Error sending message:', error);
            appendMessage('Lỗi khi gửi tin nhắn. Vui lòng thử lại.', 'system');
        }
    }

    // Send image function
    function sendImage(file) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            appendMessage('Kết nối không khả dụng. Đang thử kết nối lại...', 'system');
            connect();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                // Display image immediately in chat
                appendImageMessage(e.target.result, 'sent');
                
                // Send to server
                const data = {
                    type: 'image',
                    imageData: e.target.result,
                    filename: file.name,
                    timestamp: new Date().toISOString()
                };
                
                ws.send(JSON.stringify(data));
            } catch (error) {
                console.error('Error sending image:', error);
                appendMessage('Lỗi khi gửi hình ảnh. Vui lòng thử lại.', 'system');
            }
        };
        reader.readAsDataURL(file);
    }

    // Send image function
    function sendImage(file) {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            appendMessage('Kết nối không khả dụng. Đang thử kết nối lại...', 'system');
            connect();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                // Display image immediately in chat
                appendImageMessage(e.target.result, 'sent');
                
                // Send to server
                const data = {
                    type: 'image',
                    imageData: e.target.result,
                    filename: file.name,
                    timestamp: new Date().toISOString()
                };
                
                ws.send(JSON.stringify(data));
            } catch (error) {
                console.error('Error sending image:', error);
                appendMessage('Lỗi khi gửi hình ảnh. Vui lòng thử lại.', 'system');
            }
        };
        reader.readAsDataURL(file);
    }    // Append text message to chat
    function appendMessage(message, type) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', type);
        
        const timestamp = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (type === 'system') {
            messageElement.innerHTML = `<em>${message}</em>`;
        } else {
            messageElement.innerHTML = `
                <div class="message-content">${message}</div>
                <div class="message-time">${timestamp}</div>
            `;
        }
        
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Append image message to chat
    function appendImageMessage(imageSrc, type) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', 'image', type);
        
        const timestamp = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        messageElement.innerHTML = `
            <img src="${imageSrc}" alt="Sent image">
            <div class="message-time">${timestamp}</div>
        `;
        
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }    // Event listeners
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Image upload event listener
    if (imageUpload) {
        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (limit to 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    appendMessage('File quá lớn. Vui lòng chọn hình ảnh nhỏ hơn 5MB.', 'system');
                    return;
                }

                // Check file type
                if (!file.type.startsWith('image/')) {
                    appendMessage('Vui lòng chọn file hình ảnh.', 'system');
                    return;
                }

                sendImage(file);
                e.target.value = ''; // Reset input
            }
        });
    }

    // Initialize connection when page loads
    // connect(); // Comment out to only connect when chat is opened
});

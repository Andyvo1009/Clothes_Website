document.addEventListener('DOMContentLoaded', function() {
    let ws;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;
    
    // Wait a bit to ensure all DOM elements are loaded
    setTimeout(() => {
        const chatTrigger = document.querySelector('.chat-trigger');
        const chatPopup = document.querySelector('.chat-popup');
        const closeChat = document.querySelector('.close-chat');
        const messageInput = document.querySelector('.chat-input input[type="text"]');
        const sendButton = document.querySelector('.chat-input button');
        const imageUpload = document.querySelector('#imageUpload');
        const messagesContainer = document.querySelector('.chat-messages');

        // Debug: Check if elements are found
        console.log('Chat elements found:', {
            chatTrigger: !!chatTrigger,
            chatPopup: !!chatPopup,
            closeChat: !!closeChat,
            messageInput: !!messageInput,
            sendButton: !!sendButton,
            imageUpload: !!imageUpload,
            messagesContainer: !!messagesContainer
        });

        // Check server status and start if needed
        // Check server status and start if needed
        async function ensureServerRunning() {
            try {
                const response = await fetch('/FirstWebsite/chat/start_server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });
                
                const result = await response.json();
                console.log('Server check result:', result);
                
                if (result.success) {
                    showConnectionStatus(result.message, 'success');
                    if (result.message.includes('started')) {
                        await new Promise(resolve => setTimeout(resolve, 3000));
                    }
                    return true;
                } else {
                    showConnectionStatus(result.message, 'error');
                    return false;
                }
            } catch (error) {
                console.error('Error checking server status:', error);
                showConnectionStatus('Failed to check server status', 'error');
                return false;
            }
        }

        // Connect to WebSocket server
        async function connect() {
            try {
                const serverReady = await ensureServerRunning();
                if (!serverReady) {
                    showConnectionStatus('Server not available', 'error');
                    return;
                }

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
            console.log(`Status: ${message} (${type})`);
            
            if (chatPopup && chatPopup.classList.contains('active')) {
                const statusColors = {
                    'success': '#4CAF50',
                    'error': '#f44336',
                    'info': '#2196F3'
                };
                
                let statusIndicator = document.querySelector('.chat-status');
                if (!statusIndicator) {
                    statusIndicator = document.createElement('div');
                    statusIndicator.className = 'chat-status';
                    statusIndicator.style.cssText = `
                        position: absolute;
                        top: 10px;
                        left: 50%;
                        transform: translateX(-50%);
                        padding: 5px 10px;
                        border-radius: 15px;
                        font-size: 12px;
                        color: white;
                        z-index: 1000;
                        transition: opacity 0.3s;
                    `;
                    chatPopup.style.position = 'relative';
                    chatPopup.appendChild(statusIndicator);
                }
                
                statusIndicator.style.backgroundColor = statusColors[type] || '#666';
                statusIndicator.textContent = message;
                statusIndicator.style.opacity = '1';
                
                if (type === 'success' || type === 'info') {
                    setTimeout(() => {
                        statusIndicator.style.opacity = '0';
                    }, 3000);
                }
            }
        }

        // Send message function
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            if (!ws || ws.readyState !== WebSocket.OPEN) {
                appendMessage('Kết nối không khả dụng. Đang thử kết nối lại...', 'system');
                await connect();
                return;
            }

            try {
                const data = {
                    type: 'text',
                    content: message,
                    timestamp: new Date().toISOString()
                };
                
                ws.send(JSON.stringify(data));
                appendMessage(message, 'sent');
                messageInput.value = '';
            } catch (error) {
                console.error('Error sending message:', error);
                appendMessage('Lỗi khi gửi tin nhắn. Vui lòng thử lại.', 'system');
            }
        }

        // Send image function
        async function sendImage(file) {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                appendMessage('Kết nối không khả dụng. Đang thử kết nối lại...', 'system');
                await connect();
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    appendImageMessage(e.target.result, 'sent');
                    
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

        // Append text message to chat
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
        }

        // Toggle chat popup
        if (chatTrigger) {
            chatTrigger.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Chat trigger clicked');
                
                if (chatPopup) {
                    console.log('Before toggle - chatPopup display:', window.getComputedStyle(chatPopup).display);
                    
                    // Force positioning
                    chatPopup.style.position = 'fixed';
                    chatPopup.style.bottom = '20px';
                    chatPopup.style.right = '20px';
                    chatPopup.style.left = 'auto';
                    chatPopup.style.top = 'auto';
                    chatPopup.style.transform = 'none';
                    chatPopup.style.zIndex = '9999';
                    
                    chatPopup.classList.add('active');
                    chatTrigger.style.display = 'none';
                    
                    console.log('After toggle - chatPopup display:', window.getComputedStyle(chatPopup).display);
                    console.log('Chat popup positioned and shown', {
                        position: chatPopup.style.position,
                        bottom: chatPopup.style.bottom,
                        right: chatPopup.style.right,
                        classList: chatPopup.classList.toString()
                    });
                    
                    // Connect to WebSocket when chat is opened
                    if (!ws || ws.readyState === WebSocket.CLOSED) {
                        showConnectionStatus('Starting chat server...', 'info');
                        await connect();
                    }
                } else {
                    console.error('Chat popup element not found');
                }
            });
        } else {
            console.error('Chat trigger element not found');
        }

        // Close chat
        if (closeChat) {
            closeChat.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Close chat clicked');
                
                if (chatPopup) {
                    chatPopup.classList.remove('active');
                    
                    chatTrigger.style.position = 'fixed';
                    chatTrigger.style.bottom = '20px';
                    chatTrigger.style.right = '20px';
                    chatTrigger.style.display = 'flex';
                    
                    console.log('Chat closed and trigger repositioned');
                }
            });
        } else {
            console.error('Close chat element not found');
        }

        // Event listeners for sending messages
        if (sendButton) {
            sendButton.addEventListener('click', async function() {
                await sendMessage();
            });
        }
        
        if (messageInput) {
            messageInput.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    await sendMessage();
                }
            });
        }

        // Image upload event listener
        if (imageUpload) {
            imageUpload.addEventListener('change', async function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        appendMessage('File quá lớn. Vui lòng chọn hình ảnh nhỏ hơn 5MB.', 'system');
                        return;
                    }

                    if (!file.type.startsWith('image/')) {
                        appendMessage('Vui lòng chọn file hình ảnh.', 'system');
                        return;
                    }

                    await sendImage(file);
                    e.target.value = '';
                }
            });
        }

    }, 100); // Reduced timeout for faster loading
});

// 📁 script.js
const socket = new WebSocket('ws://localhost:8080');

const toggleBtn = document.getElementById('chat-toggle');
const chatbox = document.getElementById('chatbox');
const closeBtn = document.getElementById('chat-close');
const messages = document.getElementById('chat-messages');
const input = document.getElementById('messageInput');
const emojiBtn = document.getElementById('emoji-btn');
const emojiBox = document.getElementById('emoji-box');
const fileInput = document.getElementById('fileInput');

// Ẩn emoji box mặc định
emojiBox.classList.add('hidden');

// Toggle hiển thị chatbox
toggleBtn.onclick = () => chatbox.classList.remove('hidden');
closeBtn.onclick = () => chatbox.classList.add('hidden');

// Emoji loader
emojiBtn.onclick = () => emojiBox.classList.toggle('hidden');

fetch('emoji.json')
  .then(res => res.json())
  .then(data => {
    emojiBox.innerHTML = '';
    data.forEach(e => {
      const span = document.createElement('span');
      span.textContent = e;
      span.onclick = () => {
        input.value += e;
        emojiBox.classList.add('hidden');
      };
      emojiBox.appendChild(span);
    });
  });

// Gửi file
fileInput.onchange = () => {
  const file = fileInput.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = () => {
      const message = {
        type: 'file',
        name: file.name,
        mime: file.type,
        content: reader.result
      };
      socket.send(JSON.stringify(message));

      const msg = document.createElement('div');
      msg.classList.add('msg-wrapper');
      msg.innerHTML = `<div class="msg user">
        <div class="bubble">
          📎 ${file.name}<br>
          <a href="${reader.result}" target="_blank">Tải xuống</a>
        </div>
      </div>`;
      messages.appendChild(msg);
      messages.scrollTop = messages.scrollHeight;

      // Trả lời mặc định
      addAutoReply();
    };
    reader.readAsDataURL(file);
  }
};

// Nhận tin nhắn từ server
socket.onmessage = (event) => {
  const data = JSON.parse(event.data);
  const msg = document.createElement('div');
  msg.classList.add('msg-wrapper');

  if (data.type === 'file') {
    msg.innerHTML = `<div class="msg sv">
      <div class="bubble">
        <strong>📎 ${data.name}</strong><br>
        <a href="${data.content}" target="_blank">Tải xuống</a>
      </div>
    </div>`;
  } else {
    msg.innerHTML = `<div class="msg sv">
      <div class="bubble">${data.content}</div>
    </div>`;
  }
  messages.appendChild(msg);
  messages.scrollTop = messages.scrollHeight;
};

// Gửi tin nhắn văn bản
function sendMessage() {
  if (input.value.trim()) {
    const message = {
      type: 'text',
      content: input.value.trim()
    };
    socket.send(JSON.stringify(message));

    const msg = document.createElement('div');
    msg.classList.add('msg-wrapper');
    msg.innerHTML = `<div class="msg user">
      <div class="bubble">${message.content}</div>
    </div>`;
    messages.appendChild(msg);
    messages.scrollTop = messages.scrollHeight;

    input.value = '';

    // Trả lời mặc định
    addAutoReply();
  }
}

// Tự động trả lời từ shop
function addAutoReply() {
  setTimeout(() => {
    const autoMsg = document.createElement('div');
    autoMsg.classList.add('msg-wrapper');
    autoMsg.innerHTML = `<div class="msg sv">
      <div class="bubble">Cảm ơn! Shop sẽ sớm phản hồi.</div>
    </div>`;
    messages.appendChild(autoMsg);
    messages.scrollTop = messages.scrollHeight;
  }, 600);
}

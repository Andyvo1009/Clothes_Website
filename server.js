const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 8080 });

const clients = new Map();

wss.on('connection', function connection(ws) {
  ws.on('message', function incoming(message) {
    try {
      const data = JSON.parse(message);
      if (data.type === 'register') {
        clients.set(data.id, ws);
        console.log(`🟢 Client registered: ${data.id}`);
      } else if (data.type === 'message') {
        console.log(`💬 ${data.id}: ${data.text}`);

        // Broadcast to all other clients
        for (const [id, client] of clients.entries()) {
          if (client !== ws && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify({ from: data.id, text: data.text }));
          }
        }
      }
    } catch (err) {
      console.error('❌ Error:', err);
    }
  });

  ws.on('close', () => {
    for (const [id, client] of clients.entries()) {
      if (client === ws) {
        clients.delete(id);
        console.log(`🔴 Client disconnected: ${id}`);
        break;
      }
    }
  });
});

console.log('🚀 WebSocket server running at ws://localhost:8080');

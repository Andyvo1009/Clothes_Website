import { useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Smile, Send, X, Paperclip } from "lucide-react";
import Picker from "@emoji-mart/react";
import data from "@emoji-mart/data";

const ChatWidget = () => {
  const [open, setOpen] = useState(false);
  const [input, setInput] = useState("");
  const [messages, setMessages] = useState(() => {
    const saved = localStorage.getItem("chat-messages");
    return saved ? JSON.parse(saved) : [];
  });
  const [showEmoji, setShowEmoji] = useState(false);
  const socketRef = useRef(null);
  const bottomRef = useRef(null);

  useEffect(() => {
    const socket = new WebSocket("ws://localhost:8080");
    socketRef.current = socket;

    socket.onopen = () => {
      socket.send(JSON.stringify({ type: "register", id: "customer1" }));
    };

    socket.onmessage = (event) => {
      const data = JSON.parse(event.data);
      setMessages((prev) => {
        const updated = [...prev, { from: data.from || "shop", text: data.text }];
        localStorage.setItem("chat-messages", JSON.stringify(updated));
        return updated;
      });
    };

    return () => socket.close();
  }, []);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const sendMessage = () => {
    if (!input.trim()) return;
    const msg = { type: "message", id: "customer1", text: input };
    socketRef.current?.send(JSON.stringify(msg));
    const updated = [...messages, { from: "you", text: input }];
    setMessages(updated);
    localStorage.setItem("chat-messages", JSON.stringify(updated));
    setInput("");
    setShowEmoji(false);
  };

  const handleKeyDown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  return (
    <div className="fixed bottom-4 left-4 z-50">
      {!open && (
        <button
          onClick={() => setOpen(true)}
          className="bg-blue-600 text-white px-4 py-2 rounded-full shadow-lg"
        >
          Chat
        </button>
      )}

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 50 }}
            className="w-80 h-[500px] bg-white rounded-lg shadow-xl flex flex-col overflow-hidden border"
          >
            {/* Header */}
            <div className="bg-black text-white px-4 py-2 flex items-center justify-between">
              <div className="flex flex-col">
                <div className="flex items-center gap-1">
                  <span className="text-sm font-semibold">Hỗ trợ</span>
                  <span className="w-2 h-2 bg-green-400 rounded-full" />
                </div>
                <span className="text-xs text-gray-300">
                  Chúng tôi sẽ trả lời sớm nhất có thể
                </span>
              </div>
              <button onClick={() => setOpen(false)}>
                <X size={16} />
              </button>
            </div>

            {/* Tin nhắn */}
            <div className="flex-1 px-3 py-2 overflow-y-auto bg-white space-y-2 text-sm">
              {messages.map((msg, idx) => (
                <div
                  key={idx}
                  className={`max-w-[75%] p-2 rounded-lg ${
                    msg.from === "you"
                      ? "bg-blue-100 self-end ml-auto"
                      : "bg-gray-100 self-start mr-auto"
                  }`}
                >
                  {msg.text}
                </div>
              ))}
              <div ref={bottomRef} />
            </div>

            {/* Emoji Picker */}
            {showEmoji && (
              <div className="absolute bottom-28 left-6 z-50">
                <Picker
                  data={data}
                  onEmojiSelect={(emoji) =>
                    setInput((prev) => prev + emoji.native)
                  }
                  theme="light"
                />
              </div>
            )}

            {/* Thanh nhập */}
            <div className="border-t p-2 bg-gray-50 flex items-center gap-2">
              <button onClick={() => setShowEmoji(!showEmoji)}>
                <Smile className="text-gray-500" size={20} />
              </button>

              <label htmlFor="file-upload">
                <Paperclip className="text-gray-500 cursor-pointer" size={20} />
              </label>
              <input id="file-upload" type="file" className="hidden" />

              <textarea
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                className="flex-1 resize-none px-2 py-1 rounded-md border text-sm"
                placeholder="Gõ tin nhắn của bạn..."
                rows={1}
              />

              <button onClick={sendMessage}>
                <Send className="text-blue-600" size={20} />
              </button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default ChatWidget;

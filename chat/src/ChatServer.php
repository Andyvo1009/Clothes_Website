<?php

namespace Chat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    protected $users = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }
    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON received');
            }

            if (!isset($data['type'])) {
                throw new \Exception('Message type not specified');
            }

            // Add timestamp to message
            $data['timestamp'] = date('Y-m-d H:i:s');
            $data['fromId'] = $from->resourceId;

            // Handle different message types
            if ($data['type'] === 'text' && isset($data['content'])) {
                $this->handleTextMessage($from, $data);
            } elseif ($data['type'] === 'image' && isset($data['imageData'])) {
                $this->handleImageMessage($from, $data);
            }

            // Don't echo messages back to prevent duplicates

        } catch (\Exception $e) {
            echo "Error handling message: {$e->getMessage()}\n";
            // Send error back to sender
            $errorResponse = json_encode([
                'type' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ]);
            if ($errorResponse !== false) {
                $from->send($errorResponse);
            }
        }
    }

    private function handleTextMessage(ConnectionInterface $from, $data)
    {
        $autoReplies = [
            'Cảm ơn bạn đã liên hệ! Shop sẽ phản hồi sớm nhất.',
            'Chúng tôi đã nhận được tin nhắn của bạn.',
            'Shop sẽ hỗ trợ bạn ngay!',
            'Cảm ơn! Chúng tôi sẽ liên hệ lại với bạn.',
            'Xin chào! Bạn cần hỗ trợ gì ạ?',

        ];

        $replyData = [
            'type' => 'auto-reply',
            'content' => $autoReplies[array_rand($autoReplies)],
            'timestamp' => date('Y-m-d H:i:s'),
            'fromId' => 'system'
        ];

        $replyResponse = json_encode($replyData);
        if ($replyResponse !== false) {
            $from->send($replyResponse);
        }
    }

    private function handleImageMessage(ConnectionInterface $from, $data)
    {
        // Save the image and send confirmation
        $imageReplies = [
            'Hình ảnh rất đẹp! Cảm ơn bạn đã chia sẻ.',
            'Đã nhận được hình ảnh. Shop sẽ phản hồi sớm!',
            'Cảm ơn bạn đã gửi hình ảnh, chúng tôi sẽ xem và phản hồi.',
            'Hình ảnh đã được gửi thành công!'
        ];

        $replyData = [
            'type' => 'auto-reply',
            'content' => $imageReplies[array_rand($imageReplies)],
            'timestamp' => date('Y-m-d H:i:s'),
            'fromId' => 'system'
        ];
        $replyResponse = json_encode($replyData);
        if ($replyResponse !== false) {
            $from->send($replyResponse);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error occurred for connection {$conn->resourceId}: {$e->getMessage()}\n";

        try {
            // Notify client of error
            $errorResponse = json_encode([
                'type' => 'error',
                'message' => 'Connection error occurred'
            ]);
            if ($errorResponse !== false) {
                $conn->send($errorResponse);
            }
        } catch (\Exception $sendError) {
            echo "Failed to send error message: {$sendError->getMessage()}\n";
        }

        // Clean up connection
        $this->clients->detach($conn);
        $conn->close();
    }
}

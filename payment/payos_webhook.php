<?php
require_once '../includes/db.php';
require_once 'payos_handler.php';

// Webhook endpoint for PayOS payment notifications
// This file should be configured in PayOS dashboard as webhook URL

header('Content-Type: application/json');

// Log webhook data for debugging (optional)
$logFile = __DIR__ . '/logs/webhook.log';
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'get' => $_GET,
    'post' => $_POST
];

// // Create logs directory if it doesn't exist
// if (!is_dir(__DIR__ . '/logs')) {
//     mkdir(__DIR__ . '/logs', 0755, true);
// }

file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND | LOCK_EX);

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }

    // Get webhook data
    $rawInput = file_get_contents('php://input');
    $webhookData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit();
    }

    // Validate webhook signature (if PayOS provides signature verification)
    $webhookSignature = $_SERVER['HTTP_X_PAYOS_SIGNATURE'] ?? '';    // Initialize PayOS handler
    $payosHandler = new PayOSHandler($pdo);

    // Add debugging log for webhook data structure
    error_log("PayOS Webhook Data: " . json_encode($webhookData));

    // Process webhook
    $result = $payosHandler->handleWebhook($webhookData, $webhookSignature);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'order_code' => $result['order_code'] ?? null
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} catch (Exception $e) {
    // Log error
    error_log("PayOS Webhook Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

<?php
session_start();
require_once '../includes/db.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.',
        'redirect' => '/FirstWebsite/auth/login.php'
    ]);
    exit;
}

$response = [
    'success' => false,
    'message' => 'Không thể thêm sản phẩm vào giỏ hàng.',
    'cart_count' => 0
];

// Process add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? trim($_POST['size']) : null;
    $color = isset($_POST['color']) ? trim($_POST['color']) : null;

    // Validate product ID
    if ($product_id <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Validate quantity
    if ($quantity <= 0) {
        $quantity = 1;
    }

    // Add to cart
    if (addToCart($pdo, $product_id, $quantity, $size, $color)) {
        $cartSummary = getCartSummary($pdo);

        $response = [
            'success' => true,
            'message' => 'Sản phẩm đã được thêm vào giỏ hàng.',
            'cart_count' => $cartSummary['count']
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

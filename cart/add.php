<?php
session_start();
require_once '../includes/db.php';
require_once 'functions.php';

$response = [
    'success' => false,
    'message' => 'Không thể thêm sản phẩm vào giỏ hàng.',
    'cart_count' => 0
];

// Generate or get session ID for cart
if (!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
$cart_id = $_SESSION['cart_id'];

// Process add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? trim($_POST['size']) : null;
    $color = isset($_POST['color']) ? trim($_POST['color']) : null;
    
    // Validate product ID
    if ($product_id <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ.';
        echo json_encode($response);
        exit;
    }
    
    // Validate quantity
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Add to cart
    if (addToCart($pdo, $cart_id, $product_id, $quantity, $size, $color)) {
        $cartSummary = getCartSummary($pdo, $cart_id);
        
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
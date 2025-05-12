<?php
session_start();
require_once '../includes/db.php';
require_once 'functions.php';

// Generate or get session ID for cart
if (!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
$cart_id = $_SESSION['cart_id'];

// Get cart summary
$cartSummary = getCartSummary($pdo, $cart_id);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => $cartSummary['count']
]); 
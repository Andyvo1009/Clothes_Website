<?php
session_start();
require_once '../includes/db.php';
require_once 'functions.php';

// Get cart summary
$cartSummary = getCartSummary($pdo);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => $cartSummary['count']
]);

<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../cart/functions.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    $_SESSION['error_message'] = 'Không tìm thấy đơn hàng.';
    header('Location: history.php');
    exit;
}

// Verify order belongs to user
$orderStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.';
    header('Location: history.php');
    exit;
}

try {
    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.variant_id, oi.quantity, p.name, pv.size, pv.color, pv.stock
        FROM order_items oi
        JOIN product_variants pv ON oi.variant_id = pv.id
        JOIN products p ON pv.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();

    if (empty($items)) {
        $_SESSION['error_message'] = 'Không tìm thấy sản phẩm trong đơn hàng này.';
        header('Location: history.php');
        exit;
    }

    $addedCount = 0;
    $skippedItems = [];

    // Add each item to cart
    foreach ($items as $item) {
        // Check if variant still exists and has stock
        if ($item['stock'] >= $item['quantity']) {
            $result = addToCart($pdo, $item['variant_id'], $item['quantity']);
            if ($result) {
                $addedCount++;
            } else {
                $skippedItems[] = $item['name'] . ' (' . $item['size'] . ', ' . $item['color'] . ')';
            }
        } else {
            $skippedItems[] = $item['name'] . ' (' . $item['size'] . ', ' . $item['color'] . ') - Hết hàng';
        }
    }

    // Set success/warning message
    if ($addedCount > 0) {
        $message = "Đã thêm $addedCount sản phẩm vào giỏ hàng.";
        if (!empty($skippedItems)) {
            $message .= " Một số sản phẩm không thể thêm: " . implode(', ', $skippedItems);
        }
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = 'Không thể thêm sản phẩm nào vào giỏ hàng. Kiểm tra tình trạng hàng tồn kho.';
    }

    // Redirect to cart
    header('Location: ../cart/index.php');
    exit;
} catch (Exception $e) {
    error_log("Reorder Error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng.';
    header('Location: history.php');
    exit;
}

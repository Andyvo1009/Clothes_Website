<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    $_SESSION['error_message'] = 'Không tìm thấy đơn hàng.';
    header('Location: history.php');
    exit;
}

// Get order details and verify user owns it
$orderStmt = $pdo->prepare("
    SELECT id, order_status, payment_status, order_code 
    FROM orders 
    WHERE id = ? AND user_id = ?
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.';
    header('Location: history.php');
    exit;
}

// Check if order can be cancelled
if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
    $_SESSION['error_message'] = 'Không thể hủy đơn hàng này. Trạng thái hiện tại: ' . ucfirst($order['order_status']);
    header('Location: detail.php?id=' . $order_id);
    exit;
}

if ($order['payment_status'] === 'completed') {
    $_SESSION['error_message'] = 'Không thể hủy đơn hàng đã thanh toán. Vui lòng liên hệ hỗ trợ khách hàng.';
    header('Location: detail.php?id=' . $order_id);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update order status
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET order_status = 'cancelled', 
            payment_status = 'cancelled',
            updated_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$order_id]);

    // If there were any pending payment links, they should be invalidated
    // (This would typically involve calling the payment provider's API)

    $pdo->commit();

    $_SESSION['success_message'] = 'Đơn hàng #' . ($order['order_code'] ?? 'ORD-' . $order['id']) . ' đã được hủy thành công.';
    header('Location: history.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Cancel Order Error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại.';
    header('Location: detail.php?id=' . $order_id);
    exit;
}

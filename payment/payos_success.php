<?php
session_start();
require_once '../includes/db.php';
require_once 'payos_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/auth/login.php');
    exit;
}

$error = '';
$orderInfo = null;
$payosHandler = new PayOSHandler($pdo);

// Get payment result parameters
$orderCode = $_GET['orderCode'] ?? '';
$status = $_GET['status'] ?? '';

if (!empty($orderCode)) {
    // Verify payment status with PayOS
    $verificationResult = $payosHandler->verifyPayment($orderCode);

    if ($verificationResult['success']) {
        $paymentData = $verificationResult['data'];

        // Update order status based on payment result
        if ($paymentData['status'] === 'PAID') {
            $payosHandler->updateOrderStatus($orderCode, 'paid', $paymentData);
            $payosHandler->clearUserCart($_SESSION['user_id']);

            // Get order information
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       COUNT(oi.id) as item_count,
                       SUM(oi.quantity) as total_quantity
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.order_code = ? AND o.user_id = ?
                GROUP BY o.id
            ");
            $stmt->execute([$orderCode, $_SESSION['user_id']]);
            $orderInfo = $stmt->fetch();

            // Clear session order data
            unset($_SESSION['pending_order_id']);
            unset($_SESSION['pending_order_code']);
        } else {
            $payosHandler->updateOrderStatus($orderCode, 'failed', $paymentData);
            $error = 'Thanh toán không thành công. Vui lòng thử lại.';
        }
    } else {
        $error = 'Không thể xác minh trạng thái thanh toán: ' . $verificationResult['error'];
    }
} else {
    $error = 'Không tìm thấy thông tin đơn hàng.';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .payment-result {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .result-title {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #333;
        }

        .result-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #666;
            line-height: 1.6;
        }

        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .order-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .order-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }

        .action-buttons {
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #dc3545;
            color: white;
        }

        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .payment-result {
                margin: 20px 10px;
                padding: 20px;
            }

            .result-title {
                font-size: 1.5rem;
            }

            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="payment-result">
            <?php if ($orderInfo && empty($error)): ?>
                <!-- Success State -->
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h1 class="result-title">Thanh Toán Thành Công!</h1>

                <p class="result-message">
                    Cảm ơn bạn đã mua sắm tại VPF Fashion. Đơn hàng của bạn đã được xác nhận và sẽ được xử lý trong thời gian sớm nhất.
                </p>

                <div class="order-details">
                    <div class="order-detail-row">
                        <span class="detail-label">Mã đơn hàng:</span>
                        <span class="detail-value">#<?= htmlspecialchars($orderInfo['order_code']) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Ngày đặt hàng:</span>
                        <span class="detail-value"><?= date('d/m/Y H:i', strtotime($orderInfo['created_at'])) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Khách hàng:</span>
                        <span class="detail-value"><?= htmlspecialchars($orderInfo['customer_name']) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?= htmlspecialchars($orderInfo['customer_email']) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Số điện thoại:</span>
                        <span class="detail-value"><?= htmlspecialchars($orderInfo['customer_phone']) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Địa chỉ giao hàng:</span>
                        <span class="detail-value"><?= htmlspecialchars($orderInfo['customer_address']) ?></span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Số lượng sản phẩm:</span>
                        <span class="detail-value"><?= $orderInfo['total_quantity'] ?> sản phẩm</span>
                    </div>

                    <div class="order-detail-row">
                        <span class="detail-label">Tổng tiền:</span>
                        <span class="detail-value total-amount">
                            <?= number_format($orderInfo['total_amount'], 0, ',', '.') ?>đ
                        </span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="/FirstWebsite/orders/history.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Xem Đơn Hàng
                    </a>
                    <a href="/FirstWebsite/index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Về Trang Chủ
                    </a>
                </div>

            <?php else: ?>
                <!-- Error State -->
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>

                <h1 class="result-title">Thanh Toán Không Thành Công</h1>

                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>

                <p class="result-message">
                    Đã có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại hoặc liên hệ với chúng tôi để được hỗ trợ.
                </p>

                <div class="action-buttons">
                    <a href="/FirstWebsite/cart/index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Quay Lại Giỏ Hàng
                    </a>
                    <a href="/FirstWebsite/index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Về Trang Chủ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.html'; ?>
</body>

</html>
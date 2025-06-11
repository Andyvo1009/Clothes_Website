<?php
session_start();
require_once '../includes/db.php';
require_once 'payos_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/auth/login.php');
    exit;
}

$orderCode = $_GET['orderCode'] ?? '';
$reason = $_GET['reason'] ?? 'cancelled';

// Update order status if we have order code
if (!empty($orderCode)) {
    $payosHandler = new PayOSHandler($pdo);
    $payosHandler->updateOrderStatus($orderCode, 'cancelled', [
        'reason' => $reason,
        'cancelled_at' => date('Y-m-d H:i:s')
    ]);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Bị Hủy - VPF Fashion</title>
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

        .cancel-icon {
            font-size: 4rem;
            color: #ffc107;
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

        .info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-box ul {
            color: #856404;
            margin: 0;
            padding-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
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

        .btn-outline {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
        }

        .btn-outline:hover {
            background: #dc3545;
            color: white;
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
            <div class="cancel-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h1 class="result-title">Thanh Toán Bị Hủy</h1>

            <p class="result-message">
                Bạn đã hủy quá trình thanh toán. Đơn hàng của bạn chưa được hoàn tất và sản phẩm vẫn còn trong giỏ hàng.
            </p>

            <?php if (!empty($orderCode)): ?>
                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h3>
                    <p><strong>Mã đơn hàng:</strong> #<?= htmlspecialchars($orderCode) ?></p>
                    <p><strong>Trạng thái:</strong> Đã hủy</p>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3><i class="fas fa-lightbulb"></i> Bạn có thể:</h3>
                <ul>
                    <li>Quay lại giỏ hàng để xem lại sản phẩm</li>
                    <li>Thử thanh toán lại bằng phương thức khác</li>
                    <li>Liên hệ với chúng tôi nếu gặp vấn đề kỹ thuật</li>
                    <li>Tiếp tục mua sắm các sản phẩm khác</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="/FirstWebsite/cart/index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Quay Lại Giỏ Hàng
                </a>
                <a href="/FirstWebsite/payment/checkout.php" class="btn btn-outline">
                    <i class="fas fa-credit-card"></i> Thử Thanh Toán Lại
                </a>
                <a href="/FirstWebsite/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Về Trang Chủ
                </a>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <p style="color: #6c757d; font-size: 0.9rem;">
                    <i class="fas fa-phone"></i> Cần hỗ trợ? Liên hệ:
                    <a href="tel:1900-0000" style="color: #dc3545;">1900-0000</a> |
                    <a href="mailto:support@vpffashion.com" style="color: #dc3545;">support@vpffashion.com</a>
                </p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.html'; ?>
</body>

</html>
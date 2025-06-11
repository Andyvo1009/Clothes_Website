<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: history.php');
    exit;
}

// Get order details
$orderStmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.id = ? AND o.user_id = ?
    GROUP BY o.id
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: history.php');
    exit;
}

// Get order items with product details
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, p.category, p.brand,
           pv.size, pv.color
    FROM order_items oi
    JOIN product_variants pv ON oi.variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY p.name
");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đơn Hàng #<?= htmlspecialchars($order['order_code'] ?? 'ORD-' . $order['id']) ?> - VPF Fashion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../payment/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-detail-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }

        .order-detail-header {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .order-detail-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .order-detail-header .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #a71e2a;
            transform: translateX(-5px);
        }

        .order-summary-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.3rem;
        }

        .card-body {
            padding: 20px;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .info-section h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1rem;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-status {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }

        .payment-completed {
            background: #d4edda;
            color: #155724;
        }

        .payment-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .items-list {
            display: grid;
            gap: 15px;
        }

        .item-card {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .item-variant {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .item-price {
            font-weight: 600;
            color: #dc3545;
            font-size: 1.1rem;
        }

        .order-totals {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .total-row:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
            border-top: 2px solid #dc3545;
            margin-top: 10px;
            padding-top: 15px;
        }

        .order-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dc3545;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dc3545;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #dc3545;
        }

        .timeline-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-date {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .timeline-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .timeline-description {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .order-detail-container {
                padding: 10px;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .item-card {
                flex-direction: column;
                text-align: center;
            }

            .item-meta {
                justify-content: center;
                gap: 20px;
            }

            .order-actions {
                flex-direction: column;
            }

            .card-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        @media print {

            .back-link,
            .order-actions {
                display: none;
            }

            .order-detail-container {
                max-width: none;
                margin: 0;
                padding: 20px;
            }

            .order-summary-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="order-detail-container">
        <!-- Back Link -->
        <a href="history.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại lịch sử đơn hàng
        </a>

        <!-- Header -->
        <div class="order-detail-header">
            <h1>Chi Tiết Đơn Hàng #<?= htmlspecialchars($order['order_code'] ?? 'ORD-' . $order['id']) ?></h1>
            <p class="subtitle">Đặt hàng ngày <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Thông Tin Đơn Hàng</h2>
                <div>
                    <span class="order-status status-<?= $order['order_status'] ?>">
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                    <?php if ($order['payment_status']): ?>
                        <span class="payment-status payment-<?= $order['payment_status'] ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <div class="order-info-grid">
                    <div class="info-section">
                        <h4><i class="fas fa-receipt"></i> Thông Tin Đơn Hàng</h4>
                        <div class="info-item">
                            <span class="info-label">Mã đơn hàng:</span>
                            <span class="info-value">#<?= htmlspecialchars($order['order_code'] ?? 'ORD-' . $order['id']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ngày đặt:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Số lượng sản phẩm:</span>
                            <span class="info-value"><?= $order['total_quantity'] ?> sản phẩm</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phương thức thanh toán:</span>
                            <span class="info-value"><?= $order['payment_method'] ? ucfirst($order['payment_method']) : 'Chưa xác định' ?></span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-user"></i> Thông Tin Khách Hàng</h4>
                        <div class="info-item">
                            <span class="info-label">Họ tên:</span>
                            <span class="info-value"><?= htmlspecialchars($order['customer_name'] ?? 'Chưa cập nhật') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= htmlspecialchars($order['customer_email'] ?? 'Chưa cập nhật') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Số điện thoại:</span>
                            <span class="info-value"><?= htmlspecialchars($order['customer_phone'] ?? 'Chưa cập nhật') ?></span>
                        </div>
                    </div>

                    <?php if ($order['customer_address']): ?>
                        <div class="info-section">
                            <h4><i class="fas fa-map-marker-alt"></i> Địa Chỉ Giao Hàng</h4>
                            <p style="margin: 0; color: #333; line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($order['customer_address'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-summary-card">
            <div class="card-header">
                <h2><i class="fas fa-shopping-bag"></i> Sản Phẩm Đã Đặt</h2>
                <span style="color: #666;"><?= count($items) ?> sản phẩm</span>
            </div>

            <div class="card-body">
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <div class="item-image">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    <i class="fas fa-image"></i>
                                <?php endif; ?>
                            </div>

                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-variant">
                                    <?php
                                    $variants = [];
                                    if (!empty($item['size'])) $variants[] = "Size: " . htmlspecialchars($item['size']);
                                    if (!empty($item['color'])) $variants[] = "Màu: " . htmlspecialchars($item['color']);
                                    if (!empty($item['category'])) $variants[] = "Danh mục: " . htmlspecialchars($item['category']);
                                    if (!empty($item['brand'])) $variants[] = "Thương hiệu: " . htmlspecialchars($item['brand']);
                                    echo implode(' | ', $variants);
                                    ?>
                                </div>

                                <div class="item-meta">
                                    <span class="item-quantity">Số lượng: <?= $item['quantity'] ?></span>
                                    <span class="item-price"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Totals -->
                <div class="order-totals">
                    <div class="total-row">
                        <span>Tạm tính:</span>
                        <span><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="total-row">
                        <span>Phí vận chuyển:</span>
                        <span>Miễn phí</span>
                    </div>
                    <div class="total-row">
                        <span>Tổng cộng:</span>
                        <span><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="order-summary-card">
            <div class="card-header">
                <h2><i class="fas fa-clock"></i> Trạng Thái Đơn Hàng</h2>
            </div>

            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                            <div class="timeline-title">Đơn hàng được tạo</div>
                            <div class="timeline-description">Đơn hàng đã được tạo và đang chờ xử lý</div>
                        </div>
                    </div>

                    <?php if ($order['order_status'] !== 'pending'): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                                <div class="timeline-title">Trạng thái cập nhật</div>
                                <div class="timeline-description">
                                    Đơn hàng đã được cập nhật thành: <strong><?= ucfirst($order['order_status']) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['payment_status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                                <div class="timeline-title">Thanh toán thành công</div>
                                <div class="timeline-description">
                                    Đã thanh toán <?= number_format($order['paid_amount'] ?? $order['total_amount'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['order_status'] === 'delivered'): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                                <div class="timeline-title">Giao hàng thành công</div>
                                <div class="timeline-description">Đơn hàng đã được giao thành công</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="order-actions">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> In Đơn Hàng
            </button>

            <?php if ($order['order_status'] === 'pending' && $order['payment_status'] === 'pending'): ?>
                <a href="../payment/checkout.php?reorder=<?= $order['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i> Thanh Toán Lại
                </a>
            <?php endif; ?>

            <?php if ($order['order_status'] === 'delivered'): ?>
                <a href="reorder.php?id=<?= $order['id'] ?>" class="btn btn-success">
                    <i class="fas fa-redo"></i> Mua Lại
                </a>
            <?php endif; ?>

            <?php if (in_array($order['order_status'], ['pending', 'confirmed']) && $order['payment_status'] !== 'completed'): ?>
                <a href="cancel.php?id=<?= $order['id'] ?>" class="btn btn-danger"
                    onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                    <i class="fas fa-times"></i> Hủy Đơn Hàng
                </a>
            <?php endif; ?>

            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Tiếp Tục Mua Sắm
            </a>
        </div>
    </div>

    <?php include '../includes/footer.html'; ?>

    <script>
        // Auto-refresh for pending orders
        <?php if (in_array($order['order_status'], ['pending', 'processing']) || $order['payment_status'] === 'pending'): ?>
            setTimeout(() => {
                window.location.reload();
            }, 60000); // Refresh every minute for pending orders
        <?php endif; ?>

        // Print functionality
        function printOrder() {
            window.print();
        }

        // Add loading states to action buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.href && !this.onclick && !this.href.includes('javascript:')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                    this.style.pointerEvents = 'none';

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                }
            });
        });
    </script>
</body>

</html>
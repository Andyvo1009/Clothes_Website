<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10; // Orders per page
$offset = ($page - 1) * $limit;

// Get status filter
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build the query
$whereClause = "WHERE o.user_id = ?";
$params = [$user_id];

if (!empty($statusFilter)) {
    $whereClause .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM orders o $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Get orders with items count
$query = "
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    $whereClause
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statuses for filter dropdown
$statusQuery = "SELECT DISTINCT order_status FROM orders WHERE user_id = ? ORDER BY order_status";
$statusStmt = $pdo->prepare($statusQuery);
$statusStmt->execute([$user_id]);
$availableStatuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - VPF Fashion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../payment/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .orders-header {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .orders-header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .orders-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .orders-controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .orders-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #dc3545;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #dc3545;
        }

        .orders-list {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
        }

        .order-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.2rem;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .order-total {
            font-size: 1.4rem;
            font-weight: bold;
            color: #dc3545;
        }

        .order-body {
            padding: 20px;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #f8f9fa;
            border-color: #dc3545;
        }

        .pagination .current {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .payment-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
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

        .payment-expired {
            background: #e2e3e5;
            color: #383d41;
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: 10px;
            }

            .orders-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .orders-stats {
                justify-content: center;
            }

            .filter-controls {
                justify-content: center;
            }

            .order-header {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-actions {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="orders-container">
        <!-- Header -->
        <div class="orders-header">
            <h1><i class="fas fa-shopping-bag"></i> Lịch Sử Đơn Hàng</h1>
            <p class="subtitle">Theo dõi và quản lý tất cả đơn hàng của bạn</p>
        </div>

        <!-- Controls and Stats -->
        <div class="orders-controls">
            <div class="orders-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalOrders ?></div>
                    <div class="stat-label">Tổng đơn hàng</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count(array_filter($orders, function ($o) {
                                                    return $o['order_status'] === 'delivered';
                                                })) ?></div>
                    <div class="stat-label">Đã giao</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count(array_filter($orders, function ($o) {
                                                    return $o['order_status'] === 'pending';
                                                })) ?></div>
                    <div class="stat-label">Đang xử lý</div>
                </div>
            </div>

            <div class="filter-controls">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <label for="status" style="font-weight: 600;">Lọc theo trạng thái:</label>
                    <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($availableStatuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>"
                                <?= $statusFilter === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                    <a href="../index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-shopping-cart"></i> Mua Sắm Ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>#<?= htmlspecialchars($order['order_code'] ?? 'ORD-' . $order['id']) ?></h3>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <div>
                                <div class="order-status status-<?= $order['order_status'] ?>">
                                    <?= ucfirst($order['order_status']) ?>
                                </div>
                                <?php if ($order['payment_status']): ?>
                                    <div class="payment-status payment-<?= $order['payment_status'] ?>" style="margin-top: 5px;">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="order-total">
                                <?= number_format($order['total_amount'], 0, ',', '.') ?>đ
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">Số lượng sản phẩm:</span>
                                    <span class="detail-value"><?= $order['total_quantity'] ?> sản phẩm</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phương thức thanh toán:</span>
                                    <span class="detail-value"><?= $order['payment_method'] ? ucfirst($order['payment_method']) : 'Chưa xác định' ?></span>
                                </div>
                                <?php if ($order['customer_name']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Người nhận:</span>
                                        <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['customer_phone']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Số điện thoại:</span>
                                        <span class="detail-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($order['customer_address']): ?>
                                <div style="margin-bottom: 15px;">
                                    <strong>Địa chỉ giao hàng:</strong><br>
                                    <span style="color: #666;"><?= htmlspecialchars($order['customer_address']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="order-actions">
                                <a href="detail.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Xem Chi Tiết
                                </a>

                                <?php if ($order['order_status'] === 'pending' && $order['payment_status'] === 'pending'): ?>
                                    <a href="../payment/checkout.php?reorder=<?= $order['id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Thanh Toán Lại
                                    </a>
                                <?php endif; ?>

                                <?php if ($order['order_status'] === 'delivered'): ?>
                                    <a href="reorder.php?id=<?= $order['id'] ?>" class="btn btn-success">
                                        <i class="fas fa-shopping-cart"></i> Mua Lại
                                    </a>
                                <?php endif; ?>

                                <?php if (in_array($order['order_status'], ['pending', 'confirmed']) && $order['payment_status'] !== 'completed'): ?>
                                    <a href="cancel.php?id=<?= $order['id'] ?>" class="btn btn-danger"
                                        onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                        <i class="fas fa-times"></i> Hủy Đơn
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>">
                        Sau <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Back to Profile -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="../profile/index.php" class="btn btn-secondary">
                <i class="fas fa-user"></i> Quay Lại Hồ Sơ
            </a>
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Về Trang Chủ
            </a>
        </div>
    </div>

    <?php include '../includes/footer.html'; ?>

    <script>
        // Auto-refresh for pending orders
        function refreshPendingOrders() {
            const pendingOrders = document.querySelectorAll('.status-pending, .status-processing');
            if (pendingOrders.length > 0) {
                setTimeout(() => {
                    window.location.reload();
                }, 30000); // Refresh every 30 seconds
            }
        }

        // Initialize auto-refresh
        document.addEventListener('DOMContentLoaded', refreshPendingOrders);

        // Add loading states to buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('javascript:') && !this.onclick) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
                    this.style.pointerEvents = 'none';

                    // Restore button after 3 seconds if page doesn't change
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                }
            });
        });

        // Filter change animation
        document.getElementById('status').addEventListener('change', function() {
            document.querySelector('.orders-list').style.opacity = '0.5';
        });
    </script>
</body>

</html>
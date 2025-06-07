<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/discount_functions.php';

// Require admin role to access this page
requireAdmin();

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create discount
    if (isset($_POST['action']) && $_POST['action'] === 'create_discount') {
        $discountData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'discount_percent' => !empty($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : null,
            'discount_amount' => !empty($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'active' => 1
        ];

        $discountId = createDiscount($pdo, $discountData);
        if ($discountId) {
            $message = "Discount created successfully with ID: $discountId";
        } else {
            $message = "Failed to create discount";
        }
    }

    // Apply discount to product
    if (isset($_POST['action']) && $_POST['action'] === 'apply_discount') {
        $productId = (int)$_POST['product_id'];
        $discountId = (int)$_POST['discount_id'];

        if (applyDiscountToProduct($pdo, $productId, $discountId)) {
            $message = "Discount applied successfully";
        } else {
            $message = "Failed to apply discount";
        }
    }

    // Remove discount from product
    if (isset($_POST['action']) && $_POST['action'] === 'remove_discount') {
        $productId = (int)$_POST['product_id'];
        $discountId = (int)$_POST['discount_id'];

        if (removeDiscountFromProduct($pdo, $productId, $discountId)) {
            $message = "Discount removed successfully";
        } else {
            $message = "Failed to remove discount";
        }
    }

    // Toggle discount status
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $discountId = (int)$_POST['discount_id'];
        $newStatus = (int)$_POST['new_status'];

        if (updateDiscountStatus($pdo, $discountId, $newStatus)) {
            $status = $newStatus ? 'activated' : 'deactivated';
            $message = "Discount $status successfully";
        } else {
            $message = "Failed to update discount status";
        }
    }
}

// Get all discounts
$discounts = getAllDiscounts($pdo);

// Get all products for the apply discount form
$productsStmt = $pdo->prepare("SELECT id, name, category FROM products ORDER BY name");
$productsStmt->execute();
$products = $productsStmt->fetchAll();

// Get current product-discount relationships
$relationshipsStmt = $pdo->prepare("
    SELECT pd.*, p.name as product_name, d.name as discount_name 
    FROM product_discounts pd 
    JOIN products p ON pd.product_id = p.id 
    JOIN discounts d ON pd.discount_id = d.id
    ORDER BY p.name, d.name
");
$relationshipsStmt->execute();
$relationships = $relationshipsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giảm giá - VPF Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .discount-form,
        .apply-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .discount-table,
        .relationships-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .discount-table th,
        .discount-table td,
        .relationships-table th,
        .relationships-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .discount-table th,
        .relationships-table th {
            background-color: #f5f5f5;
        }

        .button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }

        .button-primary {
            background-color: #007cba;
            color: white;
        }

        .button-success {
            background-color: #4CAF50;
            color: white;
        }

        .button-danger {
            background-color: #f44336;
            color: white;
        }

        .button-warning {
            background-color: #ff9800;
            color: white;
        }

        .status-active {
            color: #4CAF50;
            font-weight: bold;
        }

        .status-inactive {
            color: #f44336;
            font-weight: bold;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Quản lý Giảm giá</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="products.php" class="button button-primary">Quản lý Sản phẩm</a>
                <a href="../chat/admin_box_chat.php" class="button" style="background-color: #4CAF50;">
                    <i class="fas fa-comments"></i> Chat Admin
                </a>
                <a href="../index.php" class="button">Quay lại Trang chủ</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="admin-content">
            <!-- Create Discount Form -->
            <div class="discount-form">
                <h2>Tạo Giảm giá Mới</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_discount">

                    <div class="form-group">
                        <label for="name">Tên giảm giá</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="discount_percent">Giảm theo phần trăm (%)</label>
                        <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" step="0.01">
                    </div>

                    <div class="form-group">
                        <label for="discount_amount">Giảm theo số tiền (đ)</label>
                        <input type="number" id="discount_amount" name="discount_amount" min="0" step="1000">
                    </div>

                    <div class="form-group">
                        <label for="start_date">Ngày bắt đầu</label>
                        <input type="datetime-local" id="start_date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Ngày kết thúc</label>
                        <input type="datetime-local" id="end_date" name="end_date" required>
                    </div>

                    <button type="submit" class="button button-success">Tạo Giảm giá</button>
                </form>
            </div>

            <!-- Apply Discount Form -->
            <div class="apply-form">
                <h2>Áp dụng Giảm giá cho Sản phẩm</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="apply_discount">

                    <div class="form-group">
                        <label for="product_id">Chọn sản phẩm</label>
                        <select id="product_id" name="product_id" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['category']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="discount_id">Chọn giảm giá</label>
                        <select id="discount_id" name="discount_id" required>
                            <option value="">-- Chọn giảm giá --</option>
                            <?php foreach ($discounts as $discount): ?>
                                <?php if ($discount['active']): ?>
                                    <option value="<?= $discount['id'] ?>">
                                        <?= htmlspecialchars($discount['name']) ?>
                                        (<?= $discount['discount_percent'] ? $discount['discount_percent'] . '%' : number_format($discount['discount_amount']) . 'đ' ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="button button-primary">Áp dụng Giảm giá</button>
                </form>
            </div>
        </div>

        <!-- Discounts List -->
        <div class="discounts-list">
            <h2>Danh sách Giảm giá</h2>
            <table class="discount-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Mô tả</th>
                        <th>Loại giảm giá</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td><?= $discount['id'] ?></td>
                            <td><?= htmlspecialchars($discount['name']) ?></td>
                            <td><?= htmlspecialchars($discount['description'] ?? '') ?></td>
                            <td>
                                <?php if ($discount['discount_percent']): ?>
                                    <?= $discount['discount_percent'] ?>%
                                <?php else: ?>
                                    <?= number_format($discount['discount_amount']) ?>đ
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($discount['start_date'])) ?><br>
                                đến<br>
                                <?= date('d/m/Y H:i', strtotime($discount['end_date'])) ?>
                            </td>
                            <td>
                                <span class="<?= $discount['active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $discount['active'] ? 'Hoạt động' : 'Không hoạt động' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="discount_id" value="<?= $discount['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $discount['active'] ? 0 : 1 ?>">
                                    <button type="submit" class="button <?= $discount['active'] ? 'button-warning' : 'button-success' ?>">
                                        <?= $discount['active'] ? 'Tắt' : 'Bật' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Applied Discounts -->
        <div class="applied-discounts">
            <h2>Giảm giá đã áp dụng</h2>
            <table class="relationships-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giảm giá</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relationships as $rel): ?>
                        <tr>
                            <td><?= htmlspecialchars($rel['product_name']) ?></td>
                            <td><?= htmlspecialchars($rel['discount_name']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa giảm giá này?');">
                                    <input type="hidden" name="action" value="remove_discount">
                                    <input type="hidden" name="product_id" value="<?= $rel['product_id'] ?>">
                                    <input type="hidden" name="discount_id" value="<?= $rel['discount_id'] ?>">
                                    <button type="submit" class="button button-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Prevent both percentage and amount from being filled
        document.getElementById('discount_percent').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('discount_amount').value = '';
            }
        });

        document.getElementById('discount_amount').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('discount_percent').value = '';
            }
        });
    </script>
</body>

</html>
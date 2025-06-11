<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];

// Get current user information
$stmt = $pdo->prepare("SELECT email, email, first_name, last_name, phone, address, role, timestamp FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}

// Format join date
$join_date = new DateTime($user['timestamp']);
$formatted_date = $join_date->format('d/m/Y');

// Get user's recent orders for order history section
$ordersStmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ?
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$ordersStmt->execute([$user_id]);
$recentOrders = $ordersStmt->fetchAll();

// Get total orders count
$totalOrdersStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$totalOrdersStmt->execute([$user_id]);
$totalOrdersCount = $totalOrdersStmt->fetchColumn();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($email)) {
        $error = 'Tên đăng nhập và email là bắt buộc.';
    } else {
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            // Check if email or email already exists (excluding current user)
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR email = ?) AND id != ?");
            $checkStmt->execute([$email, $email, $user_id]);

            if ($checkStmt->fetch()) {
                $error = 'Tên đăng nhập hoặc email đã tồn tại.';
            } else {
                $update_password = false;
                $password_hash = null;

                // Handle password change
                if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $error = 'Vui lòng điền đầy đủ thông tin mật khẩu.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Mật khẩu mới không khớp.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
                    } else {
                        // Verify current password
                        $passStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                        $passStmt->execute([$user_id]);
                        $current_hash = $passStmt->fetchColumn();

                        if (!password_verify($current_password, $current_hash)) {
                            $error = 'Mật khẩu hiện tại không đúng.';
                        } else {
                            $update_password = true;
                            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        }
                    }
                }

                // Update profile if no errors
                if (empty($error)) {
                    try {
                        if ($update_password) {
                            $updateStmt = $pdo->prepare("UPDATE users SET email = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                            $updateStmt->execute([$email, $email, $first_name, $last_name, $phone, $address, $password_hash, $user_id]);
                        } else {
                            $updateStmt = $pdo->prepare("UPDATE users SET email = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
                            $updateStmt->execute([$email, $email, $first_name, $last_name, $phone, $address, $user_id]);
                        }

                        // Update session email if changed
                        $_SESSION['email'] = $email;

                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT email, email, first_name, last_name, phone, address FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();

                        $message = 'Hồ sơ đã được cập nhật thành công!';
                        if ($update_password) {
                            $message .= ' Mật khẩu đã được thay đổi.';
                        }
                    } catch (PDOException $e) {
                        $error = 'Có lỗi xảy ra khi cập nhật hồ sơ. Vui lòng thử lại.';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Cá Nhân - VPF Fashion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.2em;
        }

        .profile-header p {
            color: #666;
            font-size: 1.1em;
        }

        .profile-form {
            display: grid;
            gap: 20px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        .form-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.3em;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc3545;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .required {
            color: #dc3545;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-section {
            margin-top: 10px;
        }

        .password-toggle {
            background: #e9ecef;
            color: #495057;
            font-size: 14px;
            padding: 8px 15px;
            margin-bottom: 15px;
        }

        .password-toggle:hover {
            background: #dee2e6;
        }

        .password-fields {
            display: none;
        }

        .password-fields.show {
            display: block;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Order History Section Styles */
        .order-history-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }

        .view-all-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .view-all-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-orders i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-orders h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .empty-orders p {
            margin-bottom: 20px;
        }

        .orders-grid {
            display: grid;
            gap: 15px;
            padding: 20px;
        }

        .order-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.3s;
        }

        .order-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-info h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.1rem;
        }

        .order-date {
            font-size: 0.9rem;
            color: #666;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
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

        .order-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .order-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }

        .order-items {
            color: #666;
            font-size: 0.9rem;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn-outline {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
        }

        .btn-outline:hover {
            background: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-details {
                flex-direction: column;
                gap: 5px;
            }

            .order-actions {
                justify-content: stretch;
            }

            .order-actions .btn {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <a href="../index.php" class="back-link">← Quay lại trang chủ</a>

        <div class="profile-header">
            <h1>Hồ Sơ Cá Nhân</h1>
            <p>Cập nhật thông tin cá nhân của bạn</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?> <!-- Order History Section -->
        <div class="order-history-section">
            <div class="section-header">
                <h2><i class="fas fa-shopping-bag"></i> Lịch Sử Đơn Hàng</h2>
                <a href="../orders/history.php" class="view-all-btn">Xem tất cả (<?= $totalOrdersCount ?>)</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Bạn chưa thực hiện đơn hàng nào. Hãy khám phá các sản phẩm của chúng tôi!</p>
                    <a href="../index.php" class="btn btn-primary">Mua sắm ngay</a>
                </div>
            <?php else: ?>
                <div class="orders-grid">
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <div class="order-info">
                                    <h4>#<?= htmlspecialchars($order['order_code'] ?? 'ORD-' . $order['id']) ?></h4>
                                    <span class="order-date"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?= $order['order_status'] ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="order-amount"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</div>
                                <div class="order-items"><?= $order['item_count'] ?> sản phẩm</div>
                            </div>
                            <div class="order-actions">
                                <a href="../orders/detail.php?id=<?= $order['id'] ?>" class="btn btn-outline">Chi tiết</a>
                                <?php if ($order['order_status'] === 'delivered'): ?>
                                    <a href="../orders/reorder.php?id=<?= $order['id'] ?>" class="btn btn-primary">Mua lại</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="profile-form">
            <!-- Account Information -->
            <div class="form-section">
                <h3>Thông tin tài khoản</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Tên đăng nhập <span class="required">*</span></label>
                        <input type="text" id="email" name="email"
                            value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="form-section">
                <h3>Thông tin cá nhân</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Họ</label>
                        <input type="text" id="first_name" name="first_name"
                            value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Tên</label>
                        <input type="text" id="last_name" name="last_name"
                            value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <textarea id="address" name="address"
                        placeholder="Nhập địa chỉ đầy đủ của bạn"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Password Change -->
            <div class="form-section">
                <h3>Đổi mật khẩu</h3>
                <button type="button" class="btn password-toggle" onclick="togglePasswordFields()">
                    Thay đổi mật khẩu
                </button>
                <div class="password-fields" id="passwordFields">
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <input type="password" id="new_password" name="new_password" minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Cập nhật hồ sơ</button>
                <a href="../index.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordFields() {
            const fields = document.getElementById('passwordFields');
            const button = document.querySelector('.password-toggle');

            if (fields.classList.contains('show')) {
                fields.classList.remove('show');
                button.textContent = 'Thay đổi mật khẩu';
                // Clear password fields when hiding
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
            } else {
                fields.classList.add('show');
                button.textContent = 'Ẩn thay đổi mật khẩu';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            // If any password field is filled, all must be filled
            if (newPassword || confirmPassword || currentPassword) {
                if (!newPassword || !confirmPassword || !currentPassword) {
                    e.preventDefault();
                    alert('Vui lòng điền đầy đủ thông tin mật khẩu.');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Mật khẩu mới không khớp.');
                    return;
                }

                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Mật khẩu mới phải có ít nhất 6 ký tự.');
                    return;
                }
            }
        });
    </script>
</body>

</html>
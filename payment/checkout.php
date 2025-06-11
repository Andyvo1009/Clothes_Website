<?php
session_start();
require_once '../includes/db.php';
require_once '../cart/functions.php';
require_once 'payos_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$error = '';
$success = '';

// Get cart items
$cartItems = getCartItems($pdo);

if (empty($cartItems)) {
    $_SESSION['cart_message'] = 'Giỏ hàng của bạn đang trống.';
    header('Location: /FirstWebsite/cart/index.php');
    exit;
}

// Calculate totals
$subtotal = 0;
$totalDiscount = 0;
foreach ($cartItems as $item) {
    $originalPrice = $item['price'] * $item['quantity'];
    $discountedPrice = $item['final_price'] * $item['quantity'];
    $subtotal += $originalPrice;
    $totalDiscount += ($originalPrice - $discountedPrice);
}
$total = $subtotal - $totalDiscount;

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');

    // Validation
    if (empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($customerAddress)) {
        $error = 'Vui lòng điền đầy đủ thông tin giao hàng';
    } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        // Create payment
        $payosHandler = new PayOSHandler($pdo);

        $customerInfo = [
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'address' => $customerAddress
        ];

        $result = $payosHandler->createPaymentFromCart($customerInfo);

        if ($result['success']) {
            // Store order info in session for reference
            $_SESSION['pending_order_id'] = $result['order_id'];
            $_SESSION['pending_order_code'] = $result['order_code'];

            // Redirect to PayOS payment page
            header('Location: ' . $result['payment_url']);
            exit();
        } else {
            $error = 'Có lỗi xảy ra khi tạo đơn hàng: ' . $result['error'];
        }
    }
}

// Get user information for pre-filling form
$stmt = $pdo->prepare("SELECT email, first_name, last_name, phone, address FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                margin: 10px;
                padding: 15px;
            }
        }

        .checkout-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.4em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 10px;
        }

        .order-summary {
            max-height: 400px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-variant {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .item-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }

        .discounted-price {
            color: #dc3545;
            font-weight: 600;
        }

        .pricing-summary {
            border-top: 2px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }

        .pricing-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-row {
            font-size: 1.2em;
            font-weight: bold;
            color: #dc3545;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
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
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc3545;
        }

        .required {
            color: #dc3545;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.3);
        }

        .back-to-cart {
            display: inline-block;
            margin-bottom: 20px;
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-cart:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .payos-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            text-align: center;
        }

        .payos-logo {
            width: 100px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="/FirstWebsite/cart/index.php" class="back-to-cart">
            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
        </a>

        <div class="checkout-container">
            <!-- Order Summary -->
            <div class="checkout-section">
                <h2 class="section-title">
                    <i class="fas fa-shopping-cart"></i> Đơn Hàng Của Bạn
                </h2>

                <div class="order-summary">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <?php if (!empty($item['image'])): ?>
                                <img src="/FirstWebsite/assets/images/<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            <?php else: ?>
                                <div class="item-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>

                                <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                                    <div class="item-variant">
                                        <?php if (!empty($item['variant_size'])): ?>
                                            Size: <?= htmlspecialchars($item['variant_size']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['variant_color'])): ?>
                                            <?= !empty($item['variant_size']) ? ' | ' : '' ?>
                                            Màu: <?= htmlspecialchars($item['variant_color']) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="item-pricing">
                                    <span>Số lượng: <?= $item['quantity'] ?></span>
                                    <div>
                                        <?php if ($item['discount_info']['has_discount']): ?>
                                            <span class="original-price">
                                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                                            </span>
                                            <span class="discounted-price">
                                                <?= number_format($item['final_price'] * $item['quantity'], 0, ',', '.') ?>đ
                                            </span>
                                        <?php else: ?>
                                            <span class="discounted-price">
                                                <?= number_format($item['final_price'] * $item['quantity'], 0, ',', '.') ?>đ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pricing-summary">
                    <div class="pricing-row">
                        <span>Tạm tính:</span>
                        <span><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                    </div>

                    <?php if ($totalDiscount > 0): ?>
                        <div class="pricing-row" style="color: #28a745;">
                            <span>Giảm giá:</span>
                            <span>-<?= number_format($totalDiscount, 0, ',', '.') ?>đ</span>
                        </div>
                    <?php endif; ?>

                    <div class="pricing-row">
                        <span>Phí vận chuyển:</span>
                        <span style="color: #28a745;">Miễn phí</span>
                    </div>

                    <div class="pricing-row total-row">
                        <span>Tổng cộng:</span>
                        <span><?= number_format($total, 0, ',', '.') ?>đ</span>
                    </div>
                </div>

                <div class="payos-info">
                    <p><strong>Thanh toán an toàn với PayOS</strong></p>
                    <small>Hỗ trợ thanh toán qua QR Code, thẻ ATM, Visa/MasterCard</small>
                </div>
            </div>

            <!-- Customer Information Form -->
            <div class="checkout-section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i> Thông Tin Giao Hàng
                </h2>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="customer_name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" required
                            value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_email">Email <span class="required">*</span></label>
                        <input type="email" id="customer_email" name="customer_email" required
                            value="<?= htmlspecialchars($user['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Số điện thoại <span class="required">*</span></label>
                        <input type="tel" id="customer_phone" name="customer_phone" required
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_address">Địa chỉ giao hàng <span class="required">*</span></label>
                        <textarea id="customer_address" name="customer_address" rows="3" required
                            placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="checkout-btn">
                        <i class="fas fa-credit-card"></i> Thanh Toán với PayOS
                        <br><small style="font-weight: normal;">Tổng: <?= number_format($total, 0, ',', '.') ?>đ</small>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.html'; ?>
</body>

</html>
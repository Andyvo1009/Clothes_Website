<?php
session_start();
require_once '../includes/db.php';
require_once 'functions.php';

$message = '';
$messageType = '';

// Generate or get session ID for cart
if (!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
$cart_id = $_SESSION['cart_id'];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Update quantity
        if ($_POST['action'] === 'update' && isset($_POST['item_id']) && isset($_POST['quantity'])) {
            $item_id = (int)$_POST['item_id'];
            $quantity = (int)$_POST['quantity'];
            
            if (updateCartItem($pdo, $item_id, $quantity)) {
                $message = 'Giỏ hàng đã được cập nhật.';
                $messageType = 'success';
            } else {
                $message = 'Không thể cập nhật giỏ hàng.';
                $messageType = 'error';
            }
        }
        
        // Remove item
        if ($_POST['action'] === 'remove' && isset($_POST['item_id'])) {
            $item_id = (int)$_POST['item_id'];
            
            if (removeCartItem($pdo, $item_id)) {
                $message = 'Sản phẩm đã được xóa khỏi giỏ hàng.';
                $messageType = 'success';
            } else {
                $message = 'Không thể xóa sản phẩm khỏi giỏ hàng.';
                $messageType = 'error';
            }
        }
        
        // Clear cart
        if ($_POST['action'] === 'clear') {
            if (clearCart($pdo, $cart_id)) {
                $message = 'Giỏ hàng đã được làm trống.';
                $messageType = 'success';
            } else {
                $message = 'Không thể làm trống giỏ hàng.';
                $messageType = 'error';
            }
        }
    }
}

// Get cart items
$cartItems = getCartItems($pdo, $cart_id);
$cartSummary = getCartSummary($pdo, $cart_id);

// Include header
include '../includes/header.html';
?>

<div class="cart-container">
    <div class="section-header">
        <h2>Giỏ hàng của bạn</h2>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (empty($cartItems)): ?>
    <div class="empty-cart">
        <p>Giỏ hàng của bạn đang trống.</p>
        <a href="../index.php" class="button">Tiếp tục mua sắm</a>
    </div>
    <?php else: ?>
    <div class="cart-content">
        <div class="cart-items">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Kích cỡ</th>
                        <th>Màu sắc</th>
                        <th>Số lượng</th>
                        <th>Tổng</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td class="product-info">
                            <div class="product-thumb">
                                <?php if (!empty($item['image']) && file_exists('../assets/images/' . $item['image'])): ?>
                                <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php else: ?>
                                <?php 
                                    // Generate a placeholder based on category and product name
                                    $placeholderText = urlencode($item['name']);
                                    $bgColor = '';
                                    $textColor = '555555';
                                    
                                    switch($item['category']) {
                                        case 'Đồ Nam':
                                            $bgColor = '87CEEB'; // Light blue
                                            break;
                                        case 'Đồ Nữ':
                                            $bgColor = 'FFB6C1'; // Light pink
                                            break;
                                        case 'Đồ Bé Trai':
                                            $bgColor = '90EE90'; // Light green
                                            break;
                                        case 'Đồ Bé Gái':
                                            $bgColor = 'FFFFE0'; // Light yellow
                                            break;
                                        default:
                                            $bgColor = 'DEDEDE'; // Light gray
                                    }
                                ?>
                                <img src="https://placeholder.pics/svg/100x100/<?= $bgColor ?>/<?= $textColor ?>/<?= $placeholderText ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="product-name">
                                <a href="../product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                            </div>
                        </td>
                        <td class="price"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                        <td><?= htmlspecialchars($item['size'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($item['color'] ?? '-') ?></td>
                        <td>
                            <form method="post" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="10" onchange="this.form.submit()">
                            </form>
                        </td>
                        <td class="total"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                        <td>
                            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="remove-button">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="cart-actions">
            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn làm trống giỏ hàng?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="clear-button">Làm trống giỏ hàng</button>
            </form>
            <a href="../index.php" class="button">Tiếp tục mua sắm</a>
        </div>
        
        <div class="cart-summary">
            <h3>Tổng giỏ hàng</h3>
            <div class="summary-row">
                <span>Số lượng sản phẩm:</span>
                <span><?= $cartSummary['count'] ?></span>
            </div>
            <div class="summary-row">
                <span>Tổng tiền:</span>
                <span class="total-price"><?= number_format($cartSummary['total'], 0, ',', '.') ?>đ</span>
            </div>
            <a href="../checkout.php" class="checkout-button">Tiến hành thanh toán</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.cart-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

.message {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.message.success {
    background-color: #e8f5e9;
    border-left: 5px solid #4CAF50;
}

.message.error {
    background-color: #ffebee;
    border-left: 5px solid #f44336;
}

.empty-cart {
    text-align: center;
    padding: 50px 0;
}

.cart-content {
    display: grid;
    grid-template-columns: 3fr 1fr;
    gap: 20px;
}

.cart-items {
    grid-column: 1 / 3;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th, .cart-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-thumb img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.quantity-form input {
    width: 60px;
    padding: 5px;
    text-align: center;
}

.remove-button {
    background: none;
    border: none;
    color: #f44336;
    font-size: 18px;
    cursor: pointer;
}

.cart-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.clear-button, .button {
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
}

.clear-button {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.button {
    background-color: #333;
    color: #fff;
    border: 1px solid #333;
}

.cart-summary {
    grid-column: 2;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.total-price {
    font-weight: bold;
    color: #c62828;
    font-size: 1.2rem;
}

.checkout-button {
    display: block;
    background-color: #c62828;
    color: #fff;
    text-align: center;
    padding: 12px;
    border-radius: 4px;
    margin-top: 20px;
    font-weight: bold;
}

@media (max-width: 768px) {
    .cart-content {
        grid-template-columns: 1fr;
    }
    
    .cart-items, .cart-summary {
        grid-column: 1;
    }
    
    .cart-table {
        font-size: 0.9rem;
    }
    
    .product-thumb img {
        width: 60px;
        height: 60px;
    }
}
</style>

<?php include '../includes/footer.html'; ?> 
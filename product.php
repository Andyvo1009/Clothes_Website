<?php
session_start();
require_once 'includes/db.php';
require_once 'cart/functions.php';
require_once 'includes/discount_functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Generate or get session ID for cart
if (!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
$cart_id = $_SESSION['cart_id'];

// Handle form submission (add to cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $message = 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.';
        $messageType = 'error';

        // Redirect to login page after a short delay
        echo "<script>
            setTimeout(function() {
                window.location.href = '/FirstWebsite/auth/login.php';
            }, 2000);
        </script>";
    } else {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $size = isset($_POST['size']) ? trim($_POST['size']) : null;
        $color = isset($_POST['color']) ? trim($_POST['color']) : null;

        if ($quantity <= 0) {
            $quantity = 1;
        }
        if (addToCart($pdo, $product_id, $quantity, $size, $color)) {
            $message = 'Sản phẩm đã được thêm vào giỏ hàng.';
            $messageType = 'success';

            // Trigger cart count update
            echo "<script>
                window.dispatchEvent(new CustomEvent('cartUpdated', { 
                    detail: { count: " . getCartSummary($pdo)['count'] . " } 
                }));
            </script>";
        } else {
            $message = 'Không thể thêm sản phẩm vào giỏ hàng.';
            $messageType = 'error';
        }
    }
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Redirect to home if product not found
if (!$product) {
    header('Location: index.php');
    exit;
}

// Get product variants (sizes, colors, stock)
$variantsStmt = $pdo->prepare("
    SELECT size, color, stock 
    FROM product_variants 
    WHERE product_id = ? AND stock > 0 
    ORDER BY size, color
");
$variantsStmt->execute([$product_id]);
$variants = $variantsStmt->fetchAll();

// Get available sizes and colors
$availableSizes = [];
$availableColors = [];
$totalStock = 0;

foreach ($variants as $variant) {
    if ($variant['size'] && !in_array($variant['size'], $availableSizes)) {
        $availableSizes[] = $variant['size'];
    }
    if ($variant['color'] && !in_array($variant['color'], $availableColors)) {
        $availableColors[] = $variant['color'];
    }
    $totalStock += $variant['stock'];
}

// Set product stock to total available stock
$product['stock'] = $totalStock;
$product['variants'] = $variants;
$product['available_sizes'] = $availableSizes;
$product['available_colors'] = $availableColors;

// Get discount information for this product
$discountInfo = calculateDiscountedPrice($pdo, $product['id'], $product['price']);
$product['discount_info'] = $discountInfo;

// Get related products from same category
$relatedStmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category = ? AND id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$relatedStmt->execute([$product['category'], $product_id]);
$relatedProducts = $relatedStmt->fetchAll();

// Add discount information to related products
$relatedProducts = addDiscountInfoToProducts($pdo, $relatedProducts);

// Include header
include 'includes/header.php';
?>

<div class="product-detail-container">
    <?php if (!empty($message)): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="product-detail">
        <div class="product-images">
            <?php if (!empty($product['image']) && file_exists('assets/images/' . $product['image'])): ?>
                <img src="assets/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image">
            <?php else: ?>
                <?php
                // Generate a placeholder based on category and product name
                $placeholderText = urlencode($product['name']);
                $bgColor = '';
                $textColor = '555555';

                switch ($product['category']) {
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
                <img src="https://placeholder.pics/svg/500x600/<?= $bgColor ?>/<?= $textColor ?>/<?= $placeholderText ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image">
            <?php endif; ?>
        </div>

        <div class="product-info">
            <h1 class="product-name"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="product-category">Danh mục: <?= htmlspecialchars($product['category']) ?></p>
            <div class="product-price">
                <?php if (isset($product['discount_info']) && $product['discount_info']['has_discount']): ?>
                    <div class="price-container">
                        <span class="original-price"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                        <span class="current-price"><?= number_format($product['discount_info']['discounted_price'], 0, ',', '.') ?>đ</span>
                        <span class="discount-badge">-<?= round($product['discount_info']['discount_percent']) ?>%</span>
                    </div>
                    <?php if (isset($product['discount_info']['discount_info']['name'])): ?>
                        <div class="discount-name">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($product['discount_info']['discount_info']['name']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="savings-info">
                        Tiết kiệm: <?= number_format($product['discount_info']['discount_amount'], 0, ',', '.') ?>đ
                    </div>
                <?php else: ?>
                    <span class="current-price"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['description'])): ?>
                <div class="product-description">
                    <h3>Mô tả sản phẩm</h3>
                    <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
            <?php endif; ?> <form method="post" class="add-to-cart-form">
                <?php if (!empty($availableSizes)): ?>
                    <div class="form-group">
                        <label for="size">Kích cỡ:</label>
                        <div class="size-options">
                            <?php foreach ($availableSizes as $index => $size): ?>
                                <div class="size-option">
                                    <input type="radio" name="size" id="size-<?= $index ?>" value="<?= htmlspecialchars(trim($size)) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <label for="size-<?= $index ?>"><?= htmlspecialchars(trim($size)) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($availableColors)): ?>
                    <div class="form-group">
                        <label for="color">Màu sắc:</label>
                        <div class="color-options">
                            <?php foreach ($availableColors as $index => $color): ?>
                                <div class="color-option">
                                    <input type="radio" name="color" id="color-<?= $index ?>" value="<?= htmlspecialchars(trim($color)) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <label for="color-<?= $index ?>"><?= htmlspecialchars(trim($color)) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="quantity">Số lượng:</label>
                    <div class="quantity-control">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                    <p class="stock-info"><?= $product['stock'] ?> sản phẩm có sẵn</p>
                </div>
                <div class="form-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="submit" name="add_to_cart" class="add-to-cart-button">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                        </button>
                    <?php else: ?>
                        <button type="button" class="login-required-button" onclick="redirectToLogin()">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập để mua hàng
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <div class="product-meta">
                <?php if (!empty($product['brand'])): ?>
                    <p><strong>Thương hiệu:</strong> <?= htmlspecialchars($product['brand']) ?></p>
                <?php endif; ?>
                <p><strong>Mã sản phẩm:</strong> VPF-<?= $product['id'] ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
        <div class="related-products">
            <h2>Sản phẩm liên quan</h2>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <a href="product.php?id=<?= $relatedProduct['id'] ?>" class="product-link">
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($relatedProduct['image']) && file_exists('assets/images/' . $relatedProduct['image'])): ?>
                                    <img src="assets/images/<?= htmlspecialchars($relatedProduct['image']) ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?>">
                                <?php else: ?>
                                    <?php
                                    // Generate a placeholder based on category and product name
                                    $placeholderText = urlencode($relatedProduct['name']);
                                    $bgColor = '';
                                    $textColor = '555555';

                                    switch ($relatedProduct['category']) {
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
                                    <img src="https://placeholder.pics/svg/300x400/<?= $bgColor ?>/<?= $textColor ?>/<?= $placeholderText ?>" alt="<?= htmlspecialchars($relatedProduct['name']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4><?= htmlspecialchars($relatedProduct['name']) ?></h4>
                                <p class="price">Giá: <?= number_format($relatedProduct['price'], 0, ',', '.') ?>đ</p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .product-detail-container {
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

    .product-detail {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    .product-images {
        position: relative;
    }

    .main-image {
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .product-name {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .product-category {
        color: #777;
        margin-bottom: 15px;
    }

    .product-price {
        margin-bottom: 20px;
    }

    .current-price {
        font-size: 1.5rem;
        color: #c62828;
        font-weight: bold;
    }

    .product-description {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .product-description h3 {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .size-options,
    .color-options {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .size-option,
    .color-option {
        position: relative;
    }

    .size-option input,
    .color-option input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .size-option label,
    .color-option label {
        display: inline-block;
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .size-option input:checked+label,
    .color-option input:checked+label {
        background-color: #333;
        color: #fff;
        border-color: #333;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        max-width: 120px;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .quantity-control input {
        width: 60px;
        height: 30px;
        text-align: center;
        border: 1px solid #ddd;
        border-left: none;
        border-right: none;
    }

    .stock-info {
        margin-top: 5px;
        font-size: 0.9rem;
        color: #777;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .add-to-cart-button {
        padding: 12px 20px;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        background-color: #c62828;
        color: #fff;
        border: none;
        justify-content: center;
        width: 100%;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .add-to-cart-button:hover {
        background-color: #a01e1e;
        color: #fff;
    }

    .add-to-cart-button.login-required {
        background-color: #2196F3;
    }

    .add-to-cart-button.login-required:hover {
        background-color: #1976D2;
    }

    .product-meta {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        font-size: 0.9rem;
        color: #777;
    }

    .related-products h2 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    @media (max-width: 768px) {
        .product-detail {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .add-to-cart-button,
        .wishlist-button {
            width: 100%;
        }
    }

    /* Login required button styling */
    .login-required-button {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        border: none;
        padding: 15px 25px;
        font-size: 1.1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        justify-content: center;
    }

    .login-required-button:hover {
        background: linear-gradient(135deg, #45a049, #3d8b40);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity controls
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const quantityInput = document.getElementById('quantity');
        const maxQuantity = <?= $product['stock'] ?>;

        minusBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < maxQuantity) {
                quantityInput.value = currentValue + 1;
            }
        }); // Prevent form submission when clicking quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
            });
        });
    });

    // Function to redirect to login page
    function redirectToLogin() {
        const currentUrl = window.location.href;
        window.location.href = '/FirstWebsite/auth/login.php?redirect=' + encodeURIComponent(currentUrl);
    }
</script>


<?php include 'includes/footer.html'; ?>
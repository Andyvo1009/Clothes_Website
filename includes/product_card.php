<a href="product.php?id=<?= $product['id'] ?>" class="product-link">
    <div class="product-card">
        <?php if ($product['stock'] < 5): ?>
            <div class="product-badge">Sale</div>
        <?php endif; ?>
        <div class="product-image">
            <?php if (!empty($product['image']) && file_exists('assets/images/' . $product['image'])): ?>
                <img src="assets/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <?php
                $placeholderText = urlencode($product['name']);
                $bgColor = '';
                $textColor = '555555';
                switch ($product['category']) {
                    case 'Đồ Nam':
                        $bgColor = '87CEEB';
                        break;
                    case 'Đồ Nữ':
                        $bgColor = 'FFB6C1';
                        break;
                    case 'Đồ Bé Trai':
                        $bgColor = '90EE90';
                        break;
                    case 'Đồ Bé Gái':
                        $bgColor = 'FFFFE0';
                        break;
                    default:
                        $bgColor = 'DEDEDE';
                }
                ?>
                <img src="https://placeholder.pics/svg/300x400/<?= $bgColor ?>/<?= $textColor ?>/<?= $placeholderText ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php endif; ?>
            <button class="wishlist-button"><i class="far fa-heart"></i></button>
        </div>
        <div class="product-info">
            <h4><?= htmlspecialchars($product['name']) ?></h4>
            <p class="price">Giá: <?= number_format($product['price'], 0, ',', '.') ?>đ</p>
            <?php if (!empty($product['size'])): ?>
                <p class="size">Size: <?= htmlspecialchars($product['size']) ?></p>
            <?php endif; ?>
            <?php if (!empty($product['color'])): ?>
                <p class="colors">Màu: <?= htmlspecialchars($product['color']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</a>
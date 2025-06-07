<a href="product.php?id=<?= $product['id'] ?>" class="product-link">
    <div class="product-card">
        <?php if ($product['stock'] < 5 || (isset($product['discount_info']) && $product['discount_info']['has_discount'])): ?>
            <div class="product-badge">
                <?php if (isset($product['discount_info']) && $product['discount_info']['has_discount']): ?>
                    -<?= round($product['discount_info']['discount_percent']) ?>%
                <?php else: ?>
                    Sale
                <?php endif; ?>
            </div>
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
                <img src="https://placeholder.pics/svg/300x400/<?= $bgColor ?>/<?= $textColor ?>/<?= $placeholderText ?>" alt="<?= htmlspecialchars($product['name']) ?>"> <?php endif; ?>

        </div>
        <div class="product-info">
            <h4><?= htmlspecialchars($product['name']) ?></h4>
            <?php if (isset($product['discount_info']) && $product['discount_info']['has_discount']): ?>
                <div class="price-container">
                    <div class="original-price"><?= number_format($product['price'], 0, ',', '.') ?>đ</div>
                    <div class="discounted-price"><?= number_format($product['discount_info']['discounted_price'], 0, ',', '.') ?>đ</div>
                </div>
                <?php if (isset($product['discount_info']['discount_info']['name'])): ?>
                    <div class="discount-name"><?= htmlspecialchars($product['discount_info']['discount_info']['name']) ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="price-container">
                    <div class="regular-price"><?= number_format($product['price'], 0, ',', '.') ?>đ</div>
                </div>
            <?php endif; ?>

            <?php if (!empty($product['size'])): ?>
                <p class="size">Size: <?= htmlspecialchars($product['size']) ?></p>
            <?php endif; ?>
            <?php if (!empty($product['color'])): ?>
                <p class="colors">Màu: <?= htmlspecialchars($product['color']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</a>
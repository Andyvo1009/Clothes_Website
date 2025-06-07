<?php

/**
 * Discount functions for managing product discounts
 */

/**
 * Get active discounts for a specific product
 *
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @return array Array of active discounts for the product
 */
function getProductDiscounts($pdo, $product_id)
{
    $stmt = $pdo->prepare("
        SELECT d.* 
        FROM discounts d
        JOIN product_discounts pd ON d.id = pd.discount_id
        WHERE pd.product_id = ? 
        AND d.active = 1 
        AND d.start_date <= NOW() 
        AND d.end_date >= NOW()
        ORDER BY d.discount_percent DESC, d.discount_amount DESC
    ");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/**
 * Calculate discounted price for a product
 *
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @param float $original_price Original price
 * @return array ['discounted_price' => float, 'discount_amount' => float, 'discount_percent' => float, 'has_discount' => bool]
 */
function calculateDiscountedPrice($pdo, $product_id, $original_price)
{
    $discounts = getProductDiscounts($pdo, $product_id);

    if (empty($discounts)) {
        return [
            'discounted_price' => $original_price,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'has_discount' => false,
            'discount_info' => null
        ];
    }

    // Use the first (best) discount
    $discount = $discounts[0];
    $discountedPrice = $original_price;
    $discountAmount = 0;
    $discountPercent = 0;

    if ($discount['discount_percent']) {
        // Percentage discount
        $discountPercent = $discount['discount_percent'];
        $discountAmount = ($original_price * $discountPercent) / 100;
        $discountedPrice = $original_price - $discountAmount;
    } elseif ($discount['discount_amount']) {
        // Fixed amount discount
        $discountAmount = min($discount['discount_amount'], $original_price);
        $discountedPrice = $original_price - $discountAmount;
        $discountPercent = ($discountAmount / $original_price) * 100;
    }

    return [
        'discounted_price' => max(0, $discountedPrice),
        'discount_amount' => $discountAmount,
        'discount_percent' => $discountPercent,
        'has_discount' => true,
        'discount_info' => $discount
    ];
}

/**
 * Add discount information to an array of products
 *
 * @param PDO $pdo Database connection
 * @param array $products Array of products
 * @return array Products with discount information added
 */
function addDiscountInfoToProducts($pdo, $products)
{
    foreach ($products as &$product) {
        $discountInfo = calculateDiscountedPrice($pdo, $product['id'], $product['price']);
        $product['discount_info'] = $discountInfo;
        $product['final_price'] = $discountInfo['discounted_price'];
    }
    unset($product); // Clean up reference

    return $products;
}

/**
 * Create a new discount
 *
 * @param PDO $pdo Database connection
 * @param array $discountData Discount data
 * @return int|false Discount ID or false on failure
 */
function createDiscount($pdo, $discountData)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO discounts (name, description, discount_percent, discount_amount, start_date, end_date, active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $discountData['name'],
            $discountData['description'],
            $discountData['discount_percent'],
            $discountData['discount_amount'],
            $discountData['start_date'],
            $discountData['end_date'],
            $discountData['active']
        ]);

        return $result ? $pdo->lastInsertId() : false;
    } catch (Exception $e) {
        error_log("Error creating discount: " . $e->getMessage());
        return false;
    }
}

/**
 * Apply discount to a product
 *
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @param int $discount_id Discount ID
 * @return bool Success status
 */
function applyDiscountToProduct($pdo, $product_id, $discount_id)
{
    try {
        // Check if discount is already applied
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_discounts WHERE product_id = ? AND discount_id = ?");
        $checkStmt->execute([$product_id, $discount_id]);

        if ($checkStmt->fetchColumn() > 0) {
            return true; // Already applied
        }

        $stmt = $pdo->prepare("INSERT INTO product_discounts (product_id, discount_id) VALUES (?, ?)");
        return $stmt->execute([$product_id, $discount_id]);
    } catch (Exception $e) {
        error_log("Error applying discount to product: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove discount from a product
 *
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @param int $discount_id Discount ID
 * @return bool Success status
 */
function removeDiscountFromProduct($pdo, $product_id, $discount_id)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM product_discounts WHERE product_id = ? AND discount_id = ?");
        return $stmt->execute([$product_id, $discount_id]);
    } catch (Exception $e) {
        error_log("Error removing discount from product: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all discounts
 *
 * @param PDO $pdo Database connection
 * @return array All discounts
 */
function getAllDiscounts($pdo)
{
    $stmt = $pdo->prepare("SELECT * FROM discounts ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get discount by ID
 *
 * @param PDO $pdo Database connection
 * @param int $discount_id Discount ID
 * @return array|false Discount data or false if not found
 */
function getDiscountById($pdo, $discount_id)
{
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->execute([$discount_id]);
    return $stmt->fetch();
}

/**
 * Update discount status
 *
 * @param PDO $pdo Database connection
 * @param int $discount_id Discount ID
 * @param int $active Active status (0 or 1)
 * @return bool Success status
 */
function updateDiscountStatus($pdo, $discount_id, $active)
{
    try {
        $stmt = $pdo->prepare("UPDATE discounts SET active = ? WHERE id = ?");
        return $stmt->execute([$active, $discount_id]);
    } catch (Exception $e) {
        error_log("Error updating discount status: " . $e->getMessage());
        return false;
    }
}

/**
 * Format price with discount display
 *
 * @param float $originalPrice Original price
 * @param array $discountInfo Discount information
 * @return string Formatted price HTML
 */
function formatPriceWithDiscount($originalPrice, $discountInfo)
{
    if (!$discountInfo['has_discount']) {
        return '<span class="price">' . number_format($originalPrice, 0, ',', '.') . 'đ</span>';
    }

    $html = '<div class="price-container">';
    $html .= '<span class="original-price">' . number_format($originalPrice, 0, ',', '.') . 'đ</span>';
    $html .= '<span class="discounted-price">' . number_format($discountInfo['discounted_price'], 0, ',', '.') . 'đ</span>';
    $html .= '</div>';

    return $html;
}

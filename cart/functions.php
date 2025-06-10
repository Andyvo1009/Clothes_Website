<?php

/**
 * Cart functions for managing shopping cart
 */

require_once __DIR__ . '/../includes/discount_functions.php';

/**
 * Get cart identifier (user_id for logged in users, session_id for guests)
 *
 * @return array ['type' => 'user'|'session', 'id' => string|int]
 */
function getCartIdentifier()
{
    if (isset($_SESSION['user_id'])) {
        return ['type' => 'user', 'id' => $_SESSION['user_id']];
    } else {
        if (!isset($_SESSION['cart_id'])) {
            $_SESSION['cart_id'] = session_id();
        }
        return ['type' => 'session', 'id' => $_SESSION['cart_id']];
    }
}

/**
 * Get the current cart items
 *
 * @param PDO $pdo Database connection
 * @return array Cart items with product details
 */
function getCartItems($pdo)
{
    $cartId = getCartIdentifier();
    if ($cartId['type'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.id as product_id, p.name, p.price, p.image, p.category, p.brand,
                   pv.stock, pv.size as variant_size, pv.color as variant_color
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN product_variants pv ON ci.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY ci.added_at DESC
        ");
        $stmt->execute([$cartId['id']]);
    } else {
        // For session-based carts, we'll store session_id as a temporary solution
        // You might want to extend your schema to handle this better
        $stmt = $pdo->prepare("
            SELECT ci.*, p.id as product_id, p.name, p.price, p.image, p.category, p.brand,
                   pv.stock, pv.size as variant_size, pv.color as variant_color
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN product_variants pv ON ci.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE c.user_id = 0 AND c.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY ci.added_at DESC
        ");
        $stmt->execute();
    }

    $cartItems = $stmt->fetchAll();    // Add discount information to cart items
    foreach ($cartItems as &$item) {
        $discountInfo = calculateDiscountedPrice($pdo, $item['product_id'], $item['price']);
        $item['discount_info'] = $discountInfo;
        $item['final_price'] = $discountInfo['discounted_price'];
    }
    unset($item); // Clean up reference

    return $cartItems;
}

/**
 * Add item to cart
 *
 * @param PDO $pdo Database connection
 * @param int $product_id Product ID
 * @param int $quantity Quantity to add
 * @param string $size Selected size
 * @param string $color Selected color
 * @return bool Success status
 */
function addToCart($pdo, $product_id, $quantity = 1, $size = null, $color = null)
{
    $cartId = getCartIdentifier();

    // Check if product exists
    $productStmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch();

    if (!$product) {
        return false; // Product doesn't exist
    }

    // Find the specific variant
    $variantStmt = $pdo->prepare("
        SELECT id, stock FROM product_variants 
        WHERE product_id = ? AND size = ? AND color = ?
    ");
    $variantStmt->execute([$product_id, $size, $color]);
    $variant = $variantStmt->fetch();

    if (!$variant || $variant['stock'] < $quantity) {
        return false; // Variant doesn't exist or not enough stock
    }

    $variant_id = $variant['id'];
    $availableStock = $variant['stock'];

    // Get or create cart for user
    if ($cartId['type'] === 'user') {
        // Get or create user cart
        $cartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $cartStmt->execute([$cartId['id']]);
        $cart = $cartStmt->fetch();

        if (!$cart) {
            // Create new cart for user
            $createCartStmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $createCartStmt->execute([$cartId['id']]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if item already exists in cart
        $stmt = $pdo->prepare("
            SELECT id, quantity FROM cart_items 
            WHERE cart_id = ? AND variant_id = ?
        ");
        $stmt->execute([$cart_id, $variant_id]);
    } else {
        // For guest users, create a temporary cart with user_id = 0
        // This is a workaround - you might want to extend your schema for better guest cart handling
        $cartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1");
        $cartStmt->execute();
        $cart = $cartStmt->fetch();

        if (!$cart) {
            // Create new temporary cart
            $createCartStmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (0)");
            $createCartStmt->execute();
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if item already exists in cart
        $stmt = $pdo->prepare("
            SELECT id, quantity FROM cart_items 
            WHERE cart_id = ? AND variant_id = ?
        ");
        $stmt->execute([$cart_id, $variant_id]);
    }

    $existingItem = $stmt->fetch();

    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;

        // Check if new quantity exceeds available stock
        if ($newQuantity > $availableStock) {
            $newQuantity = $availableStock;
        }

        $updateStmt = $pdo->prepare("
            UPDATE cart_items SET quantity = ? WHERE id = ?
        ");
        return $updateStmt->execute([$newQuantity, $existingItem['id']]);
    } else {
        // Insert new item
        $insertStmt = $pdo->prepare("
            INSERT INTO cart_items (cart_id, variant_id, quantity)
            VALUES (?, ?, ?)
        ");
        return $insertStmt->execute([$cart_id, $variant_id, $quantity]);
    }
}

/**
 * Update cart item quantity
 *
 * @param PDO $pdo Database connection
 * @param int $item_id Cart item ID
 * @param int $quantity New quantity
 * @return bool Success status
 */
function updateCartItem($pdo, $item_id, $quantity)
{
    // Get cart item details with variant_id
    $getStmt = $pdo->prepare("SELECT variant_id FROM cart_items WHERE id = ?");
    $getStmt->execute([$item_id]);
    $cartItem = $getStmt->fetch();

    if (!$cartItem) {
        return false;
    }

    // Check available stock for this variant
    $stockStmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ?");
    $stockStmt->execute([$cartItem['variant_id']]);
    $variant = $stockStmt->fetch();
    $stock = $variant ? $variant['stock'] : 0;

    // Ensure quantity doesn't exceed stock
    if ($quantity > $stock) {
        $quantity = $stock;
    }

    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        return removeCartItem($pdo, $item_id);
    } else {
        // Update quantity
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $item_id]);
    }
}

/**
 * Remove item from cart
 *
 * @param PDO $pdo Database connection
 * @param int $item_id Cart item ID
 * @return bool Success status
 */
function removeCartItem($pdo, $item_id)
{
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    return $stmt->execute([$item_id]);
}

/**
 * Clear all items from cart
 *
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function clearCart($pdo)
{
    $cartId = getCartIdentifier();

    if ($cartId['type'] === 'user') {
        $stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = ?
        ");
        return $stmt->execute([$cartId['id']]);
    } else {
        $stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = 0 AND c.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        return $stmt->execute();
    }
}

/**
 * Get cart summary (count and total)
 *
 * @param PDO $pdo Database connection
 * @return array Cart summary with count and total
 */
function getCartSummary($pdo)
{
    $cartId = getCartIdentifier();

    if ($cartId['type'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.id as product_id, p.price, ci.quantity
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN product_variants pv ON ci.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$cartId['id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ci.*, p.id as product_id, p.price, ci.quantity
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN product_variants pv ON ci.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE c.user_id = 0 AND c.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute();
    }

    $items = $stmt->fetchAll();
    $count = 0;
    $total = 0;
    $originalTotal = 0;

    foreach ($items as $item) {
        $count++;
        $discountInfo = calculateDiscountedPrice($pdo, $item['product_id'], $item['price']);
        $originalTotal += $item['price'] * $item['quantity'];
        $total += $discountInfo['discounted_price'] * $item['quantity'];
    }

    return [
        'count' => $count,
        'total' => $total,
        'original_total' => $originalTotal,
        'total_savings' => $originalTotal - $total
    ];
}

/**
 * Transfer session cart to user cart when user logs in
 *
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $session_id Session ID
 * @return bool Success status
 */
function transferSessionCartToUser($pdo, $user_id, $session_id)
{
    try {
        $pdo->beginTransaction();

        // Get session cart (user_id = 0) items
        $sessionCartStmt = $pdo->prepare("
            SELECT ci.* FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = 0 AND c.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $sessionCartStmt->execute();

        // Get or create user cart
        $userCartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $userCartStmt->execute([$user_id]);
        $userCart = $userCartStmt->fetch();

        if (!$userCart) {
            // Create new cart for user
            $createCartStmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $createCartStmt->execute([$user_id]);
            $user_cart_id = $pdo->lastInsertId();
        } else {
            $user_cart_id = $userCart['id'];
        }

        while ($item = $sessionCartStmt->fetch()) {
            // Check if item already exists in user cart
            $existingStmt = $pdo->prepare("
                SELECT id, quantity FROM cart_items 
                WHERE cart_id = ? AND variant_id = ?
            ");
            $existingStmt->execute([$user_cart_id, $item['variant_id']]);
            $existing = $existingStmt->fetch();

            if ($existing) {
                // Update quantity
                $updateStmt = $pdo->prepare("
                    UPDATE cart_items SET quantity = quantity + ? WHERE id = ?
                ");
                $updateStmt->execute([$item['quantity'], $existing['id']]);
            } else {
                // Insert new item
                $insertStmt = $pdo->prepare("
                    INSERT INTO cart_items (cart_id, variant_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $insertStmt->execute([
                    $user_cart_id,
                    $item['variant_id'],
                    $item['quantity']
                ]);
            }
        }

        // Delete session cart items
        $deleteStmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = 0 AND c.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $deleteStmt->execute();

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

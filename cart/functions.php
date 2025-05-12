<?php
/**
 * Cart functions for managing shopping cart
 */

/**
 * Get the current cart items for the session
 *
 * @param PDO $pdo Database connection
 * @param string $session_id The session ID
 * @return array Cart items with product details
 */
function getCartItems($pdo, $session_id) {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image, p.category
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.session_id = ?
        ORDER BY ci.created_at DESC
    ");
    $stmt->execute([$session_id]);
    return $stmt->fetchAll();
}

/**
 * Add item to cart
 *
 * @param PDO $pdo Database connection
 * @param string $session_id The session ID
 * @param int $product_id Product ID
 * @param int $quantity Quantity to add
 * @param string $size Selected size
 * @param string $color Selected color
 * @return bool Success status
 */
function addToCart($pdo, $session_id, $product_id, $quantity = 1, $size = null, $color = null) {
    // Check if product exists and is in stock
    $productStmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    $product = $productStmt->fetch();
    
    if (!$product || $product['stock'] < $quantity) {
        return false; // Product doesn't exist or not enough stock
    }
    
    // Check if item already exists in cart
    $stmt = $pdo->prepare("
        SELECT id, quantity FROM cart_items 
        WHERE session_id = ? AND product_id = ? AND size = ? AND color = ?
    ");
    $stmt->execute([$session_id, $product_id, $size, $color]);
    $existingItem = $stmt->fetch();
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock']) {
            $newQuantity = $product['stock'];
        }
        
        $updateStmt = $pdo->prepare("
            UPDATE cart_items SET quantity = ? WHERE id = ?
        ");
        return $updateStmt->execute([$newQuantity, $existingItem['id']]);
    } else {
        // Insert new item
        $insertStmt = $pdo->prepare("
            INSERT INTO cart_items (session_id, product_id, quantity, size, color)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $insertStmt->execute([$session_id, $product_id, $quantity, $size, $color]);
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
function updateCartItem($pdo, $item_id, $quantity) {
    // Get product ID from cart item
    $getStmt = $pdo->prepare("SELECT product_id FROM cart_items WHERE id = ?");
    $getStmt->execute([$item_id]);
    $product_id = $getStmt->fetchColumn();
    
    // Check stock level
    $stockStmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stockStmt->execute([$product_id]);
    $stock = $stockStmt->fetchColumn();
    
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
function removeCartItem($pdo, $item_id) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    return $stmt->execute([$item_id]);
}

/**
 * Clear all items from cart
 *
 * @param PDO $pdo Database connection
 * @param string $session_id The session ID
 * @return bool Success status
 */
function clearCart($pdo, $session_id) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?");
    return $stmt->execute([$session_id]);
}

/**
 * Get cart summary (count and total)
 *
 * @param PDO $pdo Database connection
 * @param string $session_id The session ID
 * @return array Cart summary with count and total
 */
function getCartSummary($pdo, $session_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(ci.id) as count, SUM(ci.quantity * p.price) as total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.session_id = ?
    ");
    $stmt->execute([$session_id]);
    $summary = $stmt->fetch();
    
    return [
        'count' => (int)($summary['count'] ?? 0),
        'total' => (float)($summary['total'] ?? 0)
    ];
} 
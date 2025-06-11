<?php

/**
 * PayOS Payment Handler for VPF Fashion
 */

require_once __DIR__ . '/../includes/payos_config.php';
require_once __DIR__ . '/../cart/functions.php';

class PayOSHandler
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create payment for cart checkout
     */
    public function createPaymentFromCart($customerInfo)
    {
        try {
            // Get cart items
            $cartItems = getCartItems($this->pdo);

            if (empty($cartItems)) {
                throw new Exception('Giỏ hàng trống');
            }

            // Calculate total
            $total = $this->calculateCartTotal($cartItems);

            // Create order in database
            $orderId = $this->createOrder($customerInfo, $cartItems, $total);            // Prepare PayOS payment data
            $orderCode = PayOSConfig::generateOrderCode();
            $paymentData = [
                "orderCode" => $orderCode,
                "amount" => PayOSConfig::formatAmount($total),
                "description" => "VPF Fashion Order #" . $orderId,
                "items" => $this->formatItemsForPayOS($cartItems),
                "returnUrl" => PayOSConfig::getReturnUrl() . "?orderCode=" . $orderCode,
                "cancelUrl" => PayOSConfig::getCancelUrl() . "?orderCode=" . $orderCode,
                "buyerName" => $customerInfo['name'],
                "buyerEmail" => $customerInfo['email'],
                "buyerPhone" => $customerInfo['phone'],
                "buyerAddress" => $customerInfo['address']
            ];
            // Create PayOS payment link
            $response = PayOSConfig::createPaymentLink($paymentData);

            if (!$response['success']) {
                throw new Exception($response['error']);
            }

            // Update order with PayOS information
            $this->updateOrderPaymentInfo($orderId, $orderCode, $response['paymentLinkId']);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_code' => $orderCode,
                'payment_url' => $response['checkoutUrl'],
                'payment_link_id' => $response['paymentLinkId']
            ];
        } catch (Exception $e) {
            error_log("PayOS Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate total amount from cart items
     */
    private function calculateCartTotal($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['final_price'] * $item['quantity'];
        }
        return $total;
    }
    /**
     * Create order in database
     */
    private function createOrder($customerInfo, $cartItems, $total)
    {
        try {
            $this->pdo->beginTransaction();

            // Insert order with new schema
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    user_id, customer_name, customer_email, customer_phone, 
                    customer_address, total_amount, payment_method, 
                    payment_status, order_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'payos', 'pending', 'pending', NOW())
            ");

            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $customerInfo['name'],
                $customerInfo['email'],
                $customerInfo['phone'],
                $customerInfo['address'],
                $total
            ]);

            $orderId = $this->pdo->lastInsertId();            // Insert order items with existing schema
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (
                    order_id, variant_id, quantity, price
                ) VALUES (?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['variant_id'],
                    $item['quantity'],
                    $item['final_price']
                ]);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    /**
     * Format cart items for PayOS API
     */
    private function formatItemsForPayOS($cartItems)
    {
        $formattedItems = [];

        foreach ($cartItems as $item) {
            // Simplify item name to avoid encoding issues
            $itemName = preg_replace('/[^\w\s-]/', '', $item['name']); // Remove special characters
            if (strlen($itemName) > 50) {
                $itemName = substr($itemName, 0, 47) . '...';
            }

            // Add variant info if available
            if (!empty($item['variant_size']) || !empty($item['variant_color'])) {
                $variants = [];
                if (!empty($item['variant_size'])) $variants[] = $item['variant_size'];
                if (!empty($item['variant_color'])) $variants[] = $item['variant_color'];
                $itemName .= ' (' . implode(', ', $variants) . ')';
            }

            $formattedItems[] = [
                "name" => $itemName,
                "quantity" => (int)$item['quantity'],
                "price" => PayOSConfig::formatAmount($item['final_price'])
            ];
        }

        return $formattedItems;
    }

    /**
     * Update order with payment information
     */
    private function updateOrderPaymentInfo($orderId, $orderCode, $paymentLinkId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET order_code = ?, payment_link_id = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$orderCode, $paymentLinkId, $orderId]);
    }
    /**
     * Verify payment status from PayOS
     */
    public function verifyPayment($orderCode)
    {
        try {
            $response = PayOSConfig::getPaymentInfo($orderCode);
            return $response;
        } catch (Exception $e) {
            error_log("PayOS Verify Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle payment webhook from PayOS
     */
    public function handleWebhook($webhookData)
    {
        try {
            // Verify webhook signature
            $signature = $_SERVER['HTTP_X_PAYOS_SIGNATURE'] ?? '';

            if (!$this->verifyWebhookSignature($webhookData, $signature)) {
                throw new Exception('Invalid webhook signature');
            }

            $orderCode = $webhookData['data']['orderCode'];
            $status = $webhookData['data']['status'];
            $amount = $webhookData['data']['amount'];

            // Update order status
            $this->updateOrderStatus($orderCode, $status, $amount);

            return ['success' => true];
        } catch (Exception $e) {
            error_log("PayOS Webhook Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($data, $signature)
    {
        $dataStr = json_encode($data, JSON_UNESCAPED_UNICODE);
        $expectedSignature = hash_hmac('sha256', $dataStr, PayOSConfig::getChecksumKey());
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Update order status based on payment status
     */
    public function updateOrderStatus($orderCode, $paymentStatus, $amount)
    {
        $orderStatus = 'pending';
        $paymentStatusDB = 'pending';

        switch ($paymentStatus) {
            case 'PAID':
                $orderStatus = 'confirmed';
                $paymentStatusDB = 'completed';
                break;
            case 'CANCELLED':
                $orderStatus = 'cancelled';
                $paymentStatusDB = 'cancelled';
                break;
            case 'EXPIRED':
                $orderStatus = 'cancelled';
                $paymentStatusDB = 'expired';
                break;
        }

        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = ?, order_status = ?, 
                paid_amount = ?, updated_at = NOW() 
            WHERE order_code = ?
        ");
        $stmt->execute([$paymentStatusDB, $orderStatus, $amount, $orderCode]);

        // Clear cart if payment successful
        if ($paymentStatus === 'PAID') {
            $this->clearUserCart($orderCode);
        }
    }

    /**
     * Clear user cart after successful payment
     */
    public function clearUserCart($orderCode)
    {
        // Get user_id from order
        $stmt = $this->pdo->prepare("SELECT user_id FROM orders WHERE order_code = ?");
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch();

        if ($order && $order['user_id']) {
            // Clear cart items for this user
            $clearStmt = $this->pdo->prepare("
                DELETE ci FROM cart_items ci 
                JOIN cart c ON ci.cart_id = c.id 
                WHERE c.user_id = ?
            ");
            $clearStmt->execute([$order['user_id']]);
        }
    }
}

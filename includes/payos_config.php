<?php

/**
 * PayOS Configuration for VPF Fashion
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';

class PayOSConfig
{
    // Configuration loaded from environment variables
    private static $config = null;

    /**
     * Initialize configuration from environment variables
     */
    private static function initConfig()
    {
        if (self::$config === null) {
            // Load .env file
            EnvLoader::load(__DIR__ . '/../.env');

            // Get base URL for constructing URLs
            $baseUrl = EnvLoader::get('APP_BASE_URL', 'http://localhost/FirstWebsite');

            self::$config = [
                'CLIENT_ID' => EnvLoader::get('PAYOS_CLIENT_ID'),
                'API_KEY' => EnvLoader::get('PAYOS_API_KEY'),
                'CHECKSUM_KEY' => EnvLoader::get('PAYOS_CHECKSUM_KEY'),
                'IS_SANDBOX' => EnvLoader::get('PAYOS_IS_SANDBOX', 'true') === 'true',
                'RETURN_URL' => $baseUrl . '/payment/payos_success.php',
                'CANCEL_URL' => $baseUrl . '/payment/payos_cancel.php',
                'WEBHOOK_URL' => $baseUrl . '/payment/payos_webhook.php'
            ];

            // Validate required configuration
            if (empty(self::$config['CLIENT_ID']) || empty(self::$config['API_KEY']) || empty(self::$config['CHECKSUM_KEY'])) {
                throw new Exception('PayOS credentials not found in environment variables');
            }
        }
    }

    /**
     * Get configuration value
     */
    private static function getConfig($key)
    {
        self::initConfig();
        return self::$config[$key];
    }

    /**
     * Get PayOS Client ID
     */
    public static function getClientId()
    {
        return self::getConfig('CLIENT_ID');
    }

    /**
     * Get PayOS API Key
     */
    public static function getApiKey()
    {
        return self::getConfig('API_KEY');
    }

    /**
     * Get PayOS Checksum Key
     */
    public static function getChecksumKey()
    {
        return self::getConfig('CHECKSUM_KEY');
    }

    /**
     * Check if running in sandbox mode
     */
    public static function isSandbox()
    {
        return self::getConfig('IS_SANDBOX');
    }

    /**
     * Get return URL
     */
    public static function getReturnUrl()
    {
        return self::getConfig('RETURN_URL');
    }

    /**
     * Get cancel URL
     */
    public static function getCancelUrl()
    {
        return self::getConfig('CANCEL_URL');
    }
    /**
     * Get webhook URL
     */
    public static function getWebhookUrl()
    {
        return self::getConfig('WEBHOOK_URL');
    }

    /**
     * Get PayOS API base URL
     */
    public static function getApiUrl()
    {
        return self::isSandbox() ? 'https://api-merchant.payos.vn' : 'https://api-merchant.payos.vn';
    }

    /**
     * Create payment link using PayOS API
     */
    public static function createPaymentLink($data)
    {
        $url = self::getApiUrl() . '/v2/payment-requests';

        // Ensure orderCode is within valid range (must be positive integer)
        $orderCode = abs($data['orderCode']);

        // Set expiration time (30 minutes from now)
        $expiredAt = time() + (30 * 60);

        // Create base payload for signature
        $baseData = [
            'amount' => $data['amount'],
            'cancelUrl' => $data['cancelUrl'],
            'description' => $data['description'],
            'orderCode' => $orderCode,
            'returnUrl' => $data['returnUrl']
        ];

        // Generate signature
        $signature = self::createSignature($baseData);

        // Format payload according to PayOS API documentation
        $payload = [
            'orderCode' => $orderCode,
            'amount' => $data['amount'],
            'description' => $data['description'],
            'buyerName' => $data['buyerName'],
            'buyerEmail' => $data['buyerEmail'],
            'buyerPhone' => $data['buyerPhone'],
            'buyerAddress' => $data['buyerAddress'],
            'items' => $data['items'],
            'cancelUrl' => $data['cancelUrl'],
            'returnUrl' => $data['returnUrl'],
            'expiredAt' => $expiredAt,
            'signature' => $signature
        ];

        $headers = [
            'x-client-id: ' . self::getClientId(),
            'x-api-key: ' . self::getApiKey(),
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log the request and response for debugging
        error_log("PayOS Request: " . json_encode($payload, JSON_UNESCAPED_UNICODE));
        error_log("PayOS Response (HTTP $httpCode): " . $response);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $curlError
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "PayOS API error (HTTP $httpCode): " . $response
            ];
        }

        $result = json_decode($response, true);

        if ($result === null) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from PayOS'
            ];
        }
        // Check if request was successful
        if (isset($result['code']) && $result['code'] === '00' && isset($result['data']['checkoutUrl'])) {
            return [
                'success' => true,
                'checkoutUrl' => $result['data']['checkoutUrl'],
                'paymentLinkId' => $result['data']['paymentLinkId'] ?? $result['data']['id'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'error' => 'PayOS API error: ' . ($result['desc'] ?? 'Unknown error') . ' (Code: ' . ($result['code'] ?? 'N/A') . ')'
            ];
        }
    }
    /**
     * Create signature for PayOS API
     */
    public static function createSignature($data)
    {
        // PayOS signature requires specific fields in specific order
        // Format: amount=$amount&cancelUrl=$cancelUrl&description=$description&orderCode=$orderCode&returnUrl=$returnUrl
        $signatureData = [
            'amount' => (string)$data['amount'],
            'cancelUrl' => (string)$data['cancelUrl'],
            'description' => (string)$data['description'],
            'orderCode' => (string)$data['orderCode'],
            'returnUrl' => (string)$data['returnUrl']
        ];

        // Sort by key to ensure consistent ordering (already alphabetical)
        ksort($signatureData);

        // Create the signature string in the exact PayOS format
        $signatureString = 'amount=' . $signatureData['amount'] .
            '&cancelUrl=' . $signatureData['cancelUrl'] .
            '&description=' . $signatureData['description'] .
            '&orderCode=' . $signatureData['orderCode'] .
            '&returnUrl=' . $signatureData['returnUrl'];
        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $signatureString, self::getChecksumKey());

        // Log for debugging
        error_log("PayOS Signature String: " . $signatureString);
        error_log("PayOS Generated Signature: " . $signature);

        return $signature;
    }

    /**
     * Get payment information
     */
    public static function getPaymentInfo($orderCode)
    {
        $url = self::getApiUrl() . '/v2/payment-requests/' . $orderCode;

        $headers = [
            'x-client-id: ' . self::getClientId(),
            'x-api-key: ' . self::getApiKey()
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'PayOS API error: ' . $response
            ];
        }

        $result = json_decode($response, true);

        if (isset($result['data'])) {
            return [
                'success' => true,
                'data' => $result['data']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Invalid PayOS response: ' . $response
            ];
        }
    }
    /**
     * Generate unique order code
     */
    public static function generateOrderCode()
    {
        // PayOS requires orderCode to be a positive integer
        // Generate a simple unique code using timestamp
        $timestamp = time();
        $random = rand(100, 999);

        // Create a shorter, simpler order code
        $orderCode = (int)($timestamp . $random);

        // Ensure it's positive and reasonable size
        return abs($orderCode % 2147483647); // Max int32 value
    }

    /**
     * Format amount for PayOS (VND, no decimals)
     */
    public static function formatAmount($amount)
    {
        // Ensure amount is positive integer for PayOS
        $formattedAmount = (int)round(abs($amount));

        // PayOS requires minimum amount (usually 1000 VND)
        return max($formattedAmount, 1000);
    }
}

<?php

/**
 * Email configuration for VPF Fashion
 * Configure your email settings here
 */

// Email configuration options
return [
    // Choose email method: 'phpmailer', 'mail', or 'mock'
    'method' => 'phpmailer', // Change to 'mock' for testing without sending real emails

    // PHPMailer SMTP settings (for Gmail example)
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls', // 'tls' or 'ssl'
        'auth' => true,
        'username' => 'your-email@gmail.com', // Replace with your Gmail address
        'password' => 'your-app-password',    // Replace with your Gmail app password
    ],

    // Sender information
    'from' => [
        'email' => 'noreply@vpffashion.com',
        'name' => 'VPF Fashion'
    ],

    // Reply-to information
    'reply_to' => [
        'email' => 'support@vpffashion.com',
        'name' => 'VPF Fashion Support'
    ]
];

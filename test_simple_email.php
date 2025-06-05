<?php

/**
 * Simple Gmail Test - Clean Implementation
 */

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Simple Gmail Email Test</h1>";

// Configuration
$gmail_user = 'testmail10092004@gmail.com';
$gmail_password = 'blsmrcdxdzrottxo'; // App password without spaces
$recipient = 'vokhoinguyen2017@gmail.com';

// Test email sending
echo "<div class='info'><strong>Testing email sending...</strong></div>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $gmail_user;
    $mail->Password = $gmail_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Disable SSL verification for testing
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom($gmail_user, 'VPF Fashion');
    $mail->addAddress($recipient);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from VPF Fashion';
    $mail->Body = '
    <html>
    <body style="font-family: Arial, sans-serif;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #333;">Test Email</h2>
            <p>This is a test email from VPF Fashion system.</p>
            <p>If you receive this email, the SMTP configuration is working correctly!</p>
            <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';

    $mail->send();
    echo "<div class='success'>✅ <strong>Email sent successfully!</strong><br>Check your inbox at: $recipient</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Email failed to send.</strong><br>";
    echo "Error: " . $mail->ErrorInfo . "</div>";

    // Show debug information
    echo "<div class='info'>";
    echo "<strong>Debug Information:</strong><br>";
    echo "Gmail User: " . htmlspecialchars($gmail_user) . "<br>";
    echo "SMTP Host: smtp.gmail.com<br>";
    echo "Port: 587<br>";
    echo "Encryption: STARTTLS<br>";
    echo "</div>";
}

echo "</body></html>";

<?php

/**
 * Gmail SMTP Troubleshooting Script
 * This script helps diagnose and fix Gmail authentication issues
 */

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Test credentials
$email = 'testmail10092004@gmail.com';
$appPassword = 'blsm rcdx dzro ttxo';
$testRecipient = 'vokhoinguyen2017@gmail.com';

echo "<h2>Gmail SMTP Troubleshooting Tool</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; }
</style>";

// Test 1: Basic connectivity
echo "<div class='test info'>";
echo "<h3>Test 1: Basic SMTP Connectivity</h3>";
$socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Can connect to Gmail SMTP server (smtp.gmail.com:587)<br>";
    fclose($socket);
} else {
    echo "❌ Cannot connect to Gmail SMTP server: $errstr ($errno)<br>";
}
echo "</div>";

// Test 2: Test different app password formats
echo "<div class='test'>";
echo "<h3>Test 2: App Password Format Testing</h3>";

$passwordVariations = [
    'blsm rcdx dzro ttxo',      // With spaces
    'blsmrcdxdzrottxo',         // Without spaces
    'blsm-rcdx-dzro-ttxo',      // With dashes
];

foreach ($passwordVariations as $i => $password) {
    echo "<h4>Testing password format " . ($i + 1) . ": " . htmlspecialchars($password) . "</h4>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Disable debug for this test

        // Just test authentication, don't send
        if ($mail->smtpConnect()) {
            echo "✅ Authentication successful with this format!<br>";
            $mail->smtpClose();
            break;
        } else {
            echo "❌ Authentication failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
echo "</div>";

// Test 3: Try different ports and encryption
echo "<div class='test'>";
echo "<h3>Test 3: Different SMTP Configurations</h3>";

$configs = [
    ['port' => 587, 'encryption' => PHPMailer::ENCRYPTION_STARTTLS, 'name' => 'TLS on port 587'],
    ['port' => 465, 'encryption' => PHPMailer::ENCRYPTION_SMTPS, 'name' => 'SSL on port 465'],
    ['port' => 25, 'encryption' => PHPMailer::ENCRYPTION_STARTTLS, 'name' => 'TLS on port 25'],
];

foreach ($configs as $config) {
    echo "<h4>Testing: " . $config['name'] . "</h4>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email;
        $mail->Password = 'blsmrcdxdzrottxo'; // Use format without spaces
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->SMTPDebug = 0;

        // Test connection
        if ($mail->smtpConnect()) {
            echo "✅ Connection successful!<br>";
            $mail->smtpClose();
        } else {
            echo "❌ Connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
echo "</div>";

// Test 4: Full email sending test
echo "<div class='test'>";
echo "<h3>Test 4: Full Email Sending Test</h3>";

$mail = new PHPMailer(true);
try {
    // Use the best configuration found
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $email;
    $mail->Password = 'blsmrcdxdzrottxo'; // Without spaces
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPDebug = 2; // Enable debug
    $mail->Debugoutput = function ($str, $level) {
        echo "Debug: " . htmlspecialchars($str) . "<br>";
    };

    // Disable SSL verification
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom($email, 'VPF Fashion Test');
    $mail->addAddress($testRecipient);
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from VPF Fashion';
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from the VPF Fashion system.</p>';

    if ($mail->send()) {
        echo "<div class='success'>✅ Email sent successfully!</div>";
    } else {
        echo "<div class='error'>❌ Failed to send email</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 5: Gmail Account Security Check
echo "<div class='test info'>";
echo "<h3>Test 5: Gmail Account Security Checklist</h3>";
echo "<p>Please verify the following Gmail account settings:</p>";
echo "<ul>";
echo "<li>✓ 2-Step Verification is enabled</li>";
echo "<li>✓ App Password is generated (not regular password)</li>";
echo "<li>✓ 'Less secure app access' is disabled (use App Password instead)</li>";
echo "<li>✓ Account is not locked or suspended</li>";
echo "<li>✓ Recent security activity doesn't show blocked sign-in attempts</li>";
echo "</ul>";
echo "<p><strong>Gmail Settings Links:</strong></p>";
echo "<ul>";
echo "<li><a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
echo "<li><a href='https://myaccount.google.com/apppasswords' target='_blank'>App Passwords</a></li>";
echo "<li><a href='https://myaccount.google.com/device-activity' target='_blank'>Device Activity</a></li>";
echo "</ul>";
echo "</div>";

// Test 6: Alternative solutions
echo "<div class='test info'>";
echo "<h3>Test 6: Alternative Solutions</h3>";
echo "<p>If Gmail SMTP continues to fail, consider these alternatives:</p>";
echo "<ol>";
echo "<li><strong>Use a different email service:</strong> Outlook, Yahoo, or dedicated SMTP services</li>";
echo "<li><strong>Use a transactional email service:</strong> SendGrid, Mailgun, or Amazon SES</li>";
echo "<li><strong>Use PHP's built-in mail() function:</strong> Configure XAMPP's sendmail</li>";
echo "<li><strong>Use a different Gmail account:</strong> Create a new account specifically for this application</li>";
echo "</ol>";
echo "</div>";

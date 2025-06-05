<?php

/**
 * Simple OTP email test script
 */

require_once 'includes/email_utils.php';

// Test email address (you can change this to your email for testing)
$testEmail = 'vokhoinguyen2017@gmail.com'; // Send to the same Gmail for testing
$testOTP = generateOTP();

echo "<h2>Testing OTP Email</h2>";
echo "<p>Generating OTP: <strong>{$testOTP}</strong></p>";
echo "<p>Sending to: <strong>{$testEmail}</strong></p>";

if (sendOTPEmail($testEmail, $testOTP)) {
    echo "<p style='color: green;'>✅ Email sent successfully!</p>";
    echo "<p>Check your email inbox for the OTP code.</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send email.</p>";
    echo "<p>Check the PHP error log for details.</p>";
}

echo "<hr>";
echo "<p><a href='auth/forgot_password.php'>Test Forgot Password Form</a></p>";

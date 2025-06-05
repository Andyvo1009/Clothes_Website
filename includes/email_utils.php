<?php

/**
 * Email utilities for OTP verification and password reset
 */

// Load PHPMailer
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Generate a 6-digit OTP
 */
function generateOTP()
{
    return sprintf('%06d', mt_rand(100000, 999999));
}

/**
 * Validate email format
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Store OTP in session with timestamp
 */
function storeOTP($email, $otp)
{
    $_SESSION['otp_data'] = [
        'email' => $email,
        'otp' => $otp,
        'timestamp' => time(),
        'attempts' => 0
    ];
}

/**
 * Verify OTP
 */
function verifyOTP($inputOTP)
{
    if (!isset($_SESSION['otp_data'])) {
        return [
            'valid' => false,
            'message' => 'Không tìm thấy mã xác thực. Vui lòng yêu cầu mã mới.'
        ];
    }

    $otpData = $_SESSION['otp_data'];

    // Check if OTP has expired (10 minutes)
    if (time() - $otpData['timestamp'] > 600) {
        clearOTP();
        return [
            'valid' => false,
            'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu mã mới.'
        ];
    }

    // Increment attempts
    $_SESSION['otp_data']['attempts']++;

    // Check if too many attempts (max 5)
    if ($_SESSION['otp_data']['attempts'] > 5) {
        clearOTP();
        return [
            'valid' => false,
            'message' => 'Đã nhập sai quá nhiều lần. Vui lòng yêu cầu mã mới.'
        ];
    }

    // Verify OTP
    if ($inputOTP === $otpData['otp']) {
        return [
            'valid' => true,
            'message' => 'Mã xác thực chính xác! Bạn có thể đặt lại mật khẩu.'
        ];
    } else {
        $remainingAttempts = 5 - $_SESSION['otp_data']['attempts'];
        return [
            'valid' => false,
            'message' => "Mã xác thực không chính xác. Còn {$remainingAttempts} lần thử."
        ];
    }
}

/**
 * Clear OTP data from session
 */
function clearOTP()
{
    unset($_SESSION['otp_data']);
}

/**
 * Send OTP email using PHPMailer with Gmail SMTP
 */
function sendOTPEmail($email, $otp)
{
    $mail = new PHPMailer(true);

    try {
        // Gmail SMTP settings - Direct configuration for reliability
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'testmail10092004@gmail.com';
        $mail->Password   = 'blsm rcdx dzro ttxo'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Security settings for Gmail
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );        // Email content
        $mail->setFrom('testmail10092004@gmail.com', 'VPF Fashion');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'VPF Fashion - Ma xac thuc dat lai mat khau';
        $mail->Body = generateOTPEmailHTML($otp);

        $mail->send();
        error_log("OTP email sent successfully to: {$email}");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());

        // Try SSL fallback
        return sendOTPEmailFallback($email, $otp);
    }
}

/**
 * Fallback email method with SSL
 */
function sendOTPEmailFallback($email, $otp)
{
    $mail = new PHPMailer(true);

    try {
        // Try SSL connection (port 465)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'testmail10092004@gmail.com';
        $mail->Password   = 'blsm rcdx dzro ttxo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;

        // Disable SSL verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->setFrom('testmail10092004@gmail.com', 'VPF Fashion');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'VPF Fashion - Ma xac thuc dat lai mat khau';
        $mail->Body = generateOTPEmailHTML($otp);

        $mail->send();
        error_log("OTP email sent via SSL fallback to: {$email}");
        return true;
    } catch (Exception $e) {
        error_log("SSL Fallback Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML email template for OTP
 */
function generateOTPEmailHTML($otp)
{
    return "
    <html>
    <head>
        <title>Mã xác thực đặt lại mật khẩu</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
            <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #C5172E; text-align: center; margin-bottom: 30px;'>VPF Fashion</h2>
                <h3 style='color: #333; text-align: center;'>Đặt lại mật khẩu</h3>
                <p style='color: #666; line-height: 1.6;'>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
                
                <div style='background-color: #f8f9fa; padding: 30px; text-align: center; margin: 30px 0; border-radius: 8px; border: 2px dashed #C5172E;'>
                    <h1 style='color: #C5172E; font-size: 48px; margin: 0; letter-spacing: 8px; font-weight: bold;'>{$otp}</h1>
                    <p style='margin: 15px 0 0 0; color: #666; font-size: 16px;'>Mã xác thực của bạn</p>
                </div>
                
                <div style='background-color: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404;'><strong>⚠️ Lưu ý quan trọng:</strong></p>
                    <ul style='color: #856404; margin: 10px 0; padding-left: 20px;'>
                        <li>Mã này sẽ hết hạn sau <strong>10 phút</strong></li>
                        <li>Không chia sẻ mã này với bất kỳ ai</li>
                        <li>Chỉ sử dụng mã này trên website chính thức của VPF Fashion</li>
                        <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='color: #666;'>Cần hỗ trợ? Liên hệ với chúng tôi:</p>
                    <p style='color: #C5172E; font-weight: bold;'>support@vpffashion.com</p>
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='color: #999; font-size: 12px; text-align: center; margin: 0;'>
                    Email này được gửi tự động từ hệ thống VPF Fashion.<br>
                    Vui lòng không trả lời email này.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

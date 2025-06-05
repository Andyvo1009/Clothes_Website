<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/email_utils.php';

$error = '';
$success = '';

// Handle reset parameter to clear session BEFORE any output
if (isset($_GET['reset'])) {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['otp_verified']);
    clearOTP();
    header('Location: /FirstWebsite/auth/forgot_password.php');
    exit();
}

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'find_user';

    if ($action === 'find_user') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Vui lòng nhập địa chỉ email của bạn';
        } elseif (!isValidEmail($email)) {
            $error = 'Địa chỉ email không hợp lệ';
        } else {
            try {
                // Check if email exists in database (username field stores email)
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Generate and send OTP
                    $otp = generateOTP();

                    if (sendOTPEmail($email, $otp)) {
                        // Store OTP in session
                        storeOTP($email, $otp);
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_user_id'] = $user['id'];
                        $success = 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.';
                    } else {
                        $error = 'Không thể gửi email. Vui lòng thử lại sau.';
                    }
                } else {
                    $error = 'Email không tồn tại trong hệ thống.';
                }
            } catch (PDOException $e) {
                error_log("Forgot Password Error: " . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    } elseif ($action === 'verify_otp') {
        $inputOTP = trim($_POST['otp'] ?? '');

        if (empty($inputOTP)) {
            $error = 'Vui lòng nhập mã xác thực';
        } else {
            $otpResult = verifyOTP($inputOTP);
            if ($otpResult['valid']) {
                // OTP is valid, proceed to password reset
                $_SESSION['otp_verified'] = true;
                $success = $otpResult['message'];
            } else {
                $error = $otpResult['message'];
            }
        }
    } elseif ($action === 'resend_otp') {
        if (isset($_SESSION['reset_email'])) {
            $email = $_SESSION['reset_email'];
            $otp = generateOTP();

            if (sendOTPEmail($email, $otp)) {
                storeOTP($email, $otp);
                $success = 'Mã xác thực mới đã được gửi đến email của bạn.';
            } else {
                $error = 'Không thể gửi email. Vui lòng thử lại sau.';
            }
        } else {
            $error = 'Phiên làm việc đã hết hạn. Vui lòng bắt đầu lại.';
        }
    } elseif ($action === 'reset_password') {
        if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
            $error = 'Phiên làm việc đã hết hạn hoặc chưa xác thực OTP. Vui lòng thử lại.';
        } else {
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($new_password) || empty($confirm_password)) {
                $error = 'Vui lòng nhập đầy đủ thông tin mật khẩu.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } elseif (strlen($new_password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
            } else {
                try {
                    // Update password in database
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);

                    // Clear all reset session data
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['otp_verified']);
                    clearOTP();

                    // Set success message and redirect to login
                    $_SESSION['password_reset_success'] = 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.';
                    header('Location: /FirstWebsite/auth/login.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Password Reset Error: " . $e->getMessage());
                    $error = 'Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại sau.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <?php if (!isset($_SESSION['reset_email'])): ?>
                <!-- Step 1: Enter Email -->
                <div class="step-indicator">
                    <div class="step active">1</div>
                    <div class="step">2</div>
                    <div class="step">3</div>
                </div>

                <h2>Forgot Password</h2>
                <p>Enter your email address to receive a verification code</p>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="find_user">
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <button type="submit">Send Verification Code</button>
                </form>

            <?php elseif (!isset($_SESSION['otp_verified'])): ?>
                <!-- Step 2: Verify OTP -->
                <div class="step-indicator">
                    <div class="step completed">✓</div>
                    <div class="step active">2</div>
                    <div class="step">3</div>
                </div>

                <h2>Verify Email</h2>
                <p>Enter the 6-digit code sent to <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong></p>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="form-group">
                        <label for="otp">Verification Code:</label>
                        <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}"
                            placeholder="000000" style="text-align: center; font-size: 1.2em; letter-spacing: 0.2em;">
                    </div>

                    <button type="submit">Verify Code</button>
                </form>

                <div class="otp-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="resend_otp">
                        <button type="submit" class="btn-link">Resend Code</button>
                    </form>
                    <span> | </span>
                    <a href="/FirstWebsite/auth/forgot_password.php?reset=1" class="btn-link">← Change Email</a>
                </div>

            <?php else: ?>
                <!-- Step 3: Reset Password -->
                <div class="step-indicator">
                    <div class="step completed">✓</div>
                    <div class="step completed">✓</div>
                    <div class="step active">3</div>
                </div>

                <h2>Reset Password</h2>
                <p>Enter your new password for <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong></p>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="auth-links">
                <a class="auth-link" href="/FirstWebsite/auth/login.php">Remember your password? Login</a>
                <div class="divider">•</div>
                <a class="auth-link secondary-link" href="/FirstWebsite/index.php">← Back to Homepage</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-format OTP input
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/\D/g, '');

                    // Limit to 6 digits
                    if (this.value.length > 6) {
                        this.value = this.value.substring(0, 6);
                    }
                });

                // Auto-submit when 6 digits are entered
                otpInput.addEventListener('input', function(e) {
                    if (this.value.length === 6) {
                        // Small delay to show the complete number
                        setTimeout(() => {
                            this.form.submit();
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>

</html>
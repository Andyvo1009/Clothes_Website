<?php
session_start();
require_once '../includes/db.php';

$error = '';
$success = '';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'find_user';

    if ($action === 'find_user') {
        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            $error = 'Vui lòng nhập tên đăng nhập của bạn';
        } else {
            try {
                // Check if username exists in database
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);                if ($user) {
                    $_SESSION['reset_username'] = $username;
                    $_SESSION['reset_user_id'] = $user['id'];
                    // Don't set success message, just let the form proceed to step 2
                } else {
                    $error = 'Tên đăng nhập không tồn tại trong hệ thống.';
                }
            } catch (PDOException $e) {
                error_log("Forgot Password Error: " . $e->getMessage());
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    } elseif ($action === 'reset_password') {
        if (!isset($_SESSION['reset_username']) || !isset($_SESSION['reset_user_id'])) {
            $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
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
                    $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);                    // Clear reset session data
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_user_id']);

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
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - VPF Fashion</title>
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forgot-container {
            max-width: 400px;
            width: 90%;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .forgot-header h2 {
            color: #c62828;
            margin-bottom: 0.5rem;
        }

        .forgot-header p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .forgot-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c62828;
        }

        .btn {
            background: #c62828;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background: #a61e1e;
        }

        .btn-secondary {
            background: #666;
            margin-top: 0.5rem;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #2e7d32;
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .auth-links a {
            color: #c62828;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 1rem 0;
            color: #666;
        }

        .step-indicator {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #2196f3;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="forgot-container">        <?php if (!isset($_SESSION['reset_username'])): ?>
            <!-- Step 1: Find User -->
            <div class="forgot-header">
                <h2>Quên mật khẩu</h2>
                <p>Bước 1: Nhập tên đăng nhập để tìm tài khoản</p>
            </div>

            <div class="step-indicator">
                <strong>Bước 1/2:</strong> Xác minh tài khoản của bạn
            </div><?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="forgot-form" method="POST">
                <input type="hidden" name="action" value="find_user">
                <div class="form-group">
                    <label for="username">Tên đăng nhập:</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <button type="submit" class="btn">Tìm tài khoản</button>
            </form>

        <?php else: ?>
            <!-- Step 2: Reset Password -->
            <div class="forgot-header">
                <h2>Đặt lại mật khẩu</h2>
                <p>Nhập mật khẩu mới cho tài khoản: <strong><?= htmlspecialchars($_SESSION['reset_username']) ?></strong></p>
            </div>            <div class="step-indicator">
                <strong>Bước 2/2:</strong> Tài khoản đã được xác nhận. Vui lòng nhập mật khẩu mới.
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php else: ?>
                <form class="forgot-form" method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn">Đặt lại mật khẩu</button>
                    <button type="button" class="btn btn-secondary" onclick="location.href='/FirstWebsite/auth/forgot_password.php?reset=1'">← Quay lại</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <div class="auth-links">
            <p>Nhớ mật khẩu? <a href="/FirstWebsite/auth/login.php">Đăng nhập</a></p>
            <div class="divider">•</div>
            <p><a href="/FirstWebsite/index.php">← Quay về trang chủ</a></p>
        </div>
    </div>

    <?php
    // Handle reset parameter to clear session
    if (isset($_GET['reset'])) {
        unset($_SESSION['reset_username']);
        unset($_SESSION['reset_user_id']);
        header('Location: /FirstWebsite/auth/forgot_password.php');
        exit();
    }
    ?>
</body>

</html>
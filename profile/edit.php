<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require user to be logged in
requireLogin();

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        try {
            // Check if email or email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR email = ?) AND id != ?");
            $stmt->execute([$email, $email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email hoặc tên đăng nhập đã được sử dụng bởi người khác.';
            } else {
                // If password change is requested
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = 'Vui lòng nhập mật khẩu hiện tại để thay đổi mật khẩu.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Mật khẩu mới không khớp.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
                    } else {
                        // Verify current password
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $current_hash = $stmt->fetchColumn();

                        if (!password_verify($current_password, $current_hash)) {
                            $error = 'Mật khẩu hiện tại không đúng.';
                        } else {
                            // Update with new password
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET email = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                            $stmt->execute([$email, $email, $first_name, $last_name, $phone, $address, $new_hash, $user_id]);
                            $message = 'Thông tin cá nhân và mật khẩu đã được cập nhật thành công.';
                        }
                    }
                } else {
                    // Update without password change
                    $stmt = $pdo->prepare("UPDATE users SET email = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$email, $email, $first_name, $last_name, $phone, $address, $user_id]);
                    $message = 'Thông tin cá nhân đã được cập nhật thành công.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Có lỗi xảy ra khi cập nhật thông tin.';
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Hồ sơ - VPF Fashion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }

        .profile-header p {
            color: #666;
            margin: 0;
            font-size: 1.1em;
        }

        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4e54c8;
            box-shadow: 0 0 0 3px rgba(78, 84, 200, 0.1);
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .password-section {
            grid-column: 1 / -1;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .password-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
        }

        .password-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        .button-group {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3d43a8, #7b80e8);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 84, 200, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            color: #495057;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f1b0b7);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .info-note {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }

            .password-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Chỉnh sửa Hồ sơ</h1>
            <p>Cập nhật thông tin cá nhân của bạn</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-note">
            <strong>Lưu ý:</strong> Các trường có dấu <span class="required">*</span> là bắt buộc.
            Nếu không muốn thay đổi mật khẩu, hãy để trống các trường mật khẩu.
        </div>

        <form method="POST" class="profile-form">
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Tên đăng nhập <span class="required">*</span></label>
                <input type="text" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="first_name">Họ <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Tên <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="VD: 0123456789">
            </div>

            <div class="form-group full-width">
                <label for="address">Địa chỉ</label>
                <textarea id="address" name="address" placeholder="Nhập địa chỉ đầy đủ của bạn"><?= htmlspecialchars($user['address']) ?></textarea>
            </div>

            <div class="password-section">
                <h3>Thay đổi Mật khẩu</h3>
                <div class="password-grid">
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Cập nhật Hồ sơ</button>
                <a href="../profile/index.php" class="btn btn-secondary">Hủy bỏ</a>
            </div>
        </form>
    </div>

    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const newPassword = this.value;
            const currentPassword = document.getElementById('current_password');

            if (newPassword.length > 0) {
                currentPassword.setAttribute('required', 'required');
                document.getElementById('confirm_password').setAttribute('required', 'required');
            } else {
                currentPassword.removeAttribute('required');
                document.getElementById('confirm_password').removeAttribute('required');
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Mật khẩu không khớp');
            } else {
                this.setCustomValidity('');
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            this.value = value;
        });
    </script>
</body>

</html>
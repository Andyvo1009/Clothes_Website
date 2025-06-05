<?php
session_start();
require_once '../includes/db.php';

$notification = '';
$error = '';
$success = '';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: /FirstWebsite/index.php');
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = 'client'; // Default role is client

        // Basic validation
        if (empty($username) || empty($password) || empty($confirm_password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } elseif ($password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp';
        } elseif (strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự';
        } else {
            // Check if username already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);

            if ($check->rowCount() > 0) {
                $error = 'Tên đăng nhập đã tồn tại';
            } else {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database with role
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, timestamp) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$username, $hashedPassword, $role]);

                $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                header("refresh:2;url=login.php"); // Redirect to login page after 2 seconds
            }
        }
    }
} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    $error = 'Lỗi đăng ký: ' . $e->getMessage();
}

// Add JavaScript to show notifications
if (!empty($error)) {
    echo "<script>
        window.onload = function() {
            var notificationDiv = document.getElementById('notification');
            if (notificationDiv) {
                notificationDiv.textContent = '" . addslashes($error) . "';
                notificationDiv.classList.add('show');
                notificationDiv.style.color = '#ff0000';
                notificationDiv.style.backgroundColor = '#ffebee';
                notificationDiv.style.padding = '10px';
                notificationDiv.style.borderRadius = '5px';
                notificationDiv.style.marginBottom = '15px';
            }
        };
    </script>";
}

if (!empty($success)) {
    echo "<script>
        window.onload = function() {
            var notificationDiv = document.getElementById('notification');
            if (notificationDiv) {
                notificationDiv.textContent = '" . addslashes($success) . "';
                notificationDiv.classList.add('show');
                notificationDiv.style.color = '#008000';
                notificationDiv.style.backgroundColor = '#e8f5e8';
                notificationDiv.style.padding = '10px';
                notificationDiv.style.borderRadius = '5px';
                notificationDiv.style.marginBottom = '15px';
            }
        };
    </script>";
}

include 'register.html';

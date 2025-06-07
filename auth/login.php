<?php
session_start();
require_once '../includes/db.php';

$notification = '';
$error = '';
$success = '';

// Check for password reset success message
if (isset($_SESSION['password_reset_success'])) {
    $success = $_SESSION['password_reset_success'];
    unset($_SESSION['password_reset_success']);
}

// If already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /FirstWebsite/admin/products.php');
    } else {
        header('Location: /FirstWebsite/index.php');
    }
    exit();
}

// Handle redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/FirstWebsite/index.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Transfer session cart to user cart if exists
                if (isset($_SESSION['cart_id'])) {
                    require_once '../cart/functions.php';
                    transferSessionCartToUser($pdo, $user['id'], $_SESSION['cart_id']);
                    unset($_SESSION['cart_id']); // Clear session cart ID
                }

                // Redirect based on role or to requested page
                if ($user['role'] === 'admin' && strpos($redirect, 'admin') !== false) {
                    header('Location: ' . $redirect);
                } elseif ($user['role'] === 'admin') {
                    header('Location: /FirstWebsite/admin/products.php');
                } else {
                    header('Location: ' . $redirect);
                }
                exit();
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        }
    }
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $error = 'Lỗi đăng nhập: ' . $e->getMessage();
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

include 'login.html';

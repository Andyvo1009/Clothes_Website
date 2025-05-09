<?php
session_start();
require_once '../includes/db.php';

$notification = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password matches!
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $notification = "Login successful! Welcome, " . htmlspecialchars($user['username']) . ".";
            header("refresh:2;url=../index.php"); // Redirect to home page after 2 seconds
        } else {
            $notification = "Invalid username or password.";
        }
    }
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $notification = "An error occurred during login. Please try again later.";
}

// Add JavaScript to show the notification
if (!empty($notification)) {
    echo "<script>
        window.onload = function() {
            var notificationDiv = document.getElementById('notification');
            notificationDiv.textContent = '" . addslashes($notification) . "';
            notificationDiv.classList.add('show');
            notificationDiv.style.color = '" . (strpos($notification, 'successful') !== false ? '#008000' : '#ff0000') . "';
        };
    </script>";
}

include 'login.html';
?>

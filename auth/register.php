<?php
require_once '../includes/db.php';
$notification = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Check if user already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute(['username' => $username]);

        if ($check->rowCount() > 0) {
            $notification = "Username already taken. Try another.";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO users (username, password, timestamp) VALUES (:username, :password, NOW())");
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword
            ]);

            $notification = "Registration successful! You can now log in.";
            header("refresh:2;url=login.html"); // Redirect to login page after 2 seconds
        }
    }
} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    $notification = "An error occurred during registration. Please try again later.";
}

// Add JavaScript to show the notification
if (!empty($notification)) {
    echo "<script>
        window.onload = function() {
            var notificationDiv = document.getElementById('notification');
            notificationDiv.textContent = '" . addslashes($notification) . "';
            notificationDiv.classList.add('show');
            notificationDiv.style.color = '" . ($notification === "Username already taken. Try another." ? '#ff0000' : '#008000') . "';
        };
    </script>";
}

include 'register.html';
?>

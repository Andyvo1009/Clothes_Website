<?php
// auth_check.php - Session management and role-based authorization

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin()
{
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /FirstWebsite/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        if (!isLoggedIn()) {
            header('Location: /FirstWebsite/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        } else {
            header('Location: /FirstWebsite/index.php?error=unauthorized');
        }
        exit();
    }
}

function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

function logout()
{
    session_start();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header('Location: /FirstWebsite/index.php');
    exit();
}

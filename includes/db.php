<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'first_web';
$db_user = 'root';
$db_pass = '';
$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

// PDO options for better error handling and security
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Log error (to a file in a real application)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show generic error message to user
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}
?>

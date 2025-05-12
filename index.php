<?php
session_start();
require_once 'includes/db.php';

// Fetch products from the database
$query = "SELECT * FROM products ORDER BY category, name";
$stmt = $pdo->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll();

// Group products by category
$categories = [];
foreach ($products as $product) {
    $categories[$product['category']][] = $product;
}

// Include header
include("includes/header.html");
include("index.html");

// Include footer
include("includes/footer.html");
?>
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/discount_functions.php';

// Helper: Convert category name to slug
function category_to_slug($name)
{
    $slug = strtolower(trim($name));
    $slug = str_replace(
        [' ', 'đ', 'Đ', 'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
        ['-', 'd', 'd', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y'],
        $slug
    );
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Fetch all categories for navigation
$allCategories = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get search and category filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';

// Initialize variables for the template
$selectedCategory = '';
$categoryName = null;

// Build query
// Initialize template variables
$templateVars = [
    'searchQuery' => $searchQuery,
    'resultCount' => 0,
    'selectedCategory' => null
];

$query = "SELECT * FROM products";
$params = [];
$where = [];

if (!empty($searchQuery)) {
    $where[] = "name LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}

if (!empty($categorySlug)) {
    // Find the real category name from slug
    foreach ($allCategories as $cat) {
        if (category_to_slug($cat) === $categorySlug) {
            $categoryName = $cat;

            $selectedCategory = $cat;

            break;
        }
    }
    if ($categoryName) {
        $where[] = "category = :category";
        $params[':category'] = $categoryName;
    }
}

if ($where) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}

$query .= " ORDER BY category, name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Add stock information from variants for each product
foreach ($products as &$product) {
    $stockStmt = $pdo->prepare("SELECT SUM(stock) as total_stock FROM product_variants WHERE product_id = ?");
    $stockStmt->execute([$product['id']]);
    $stockResult = $stockStmt->fetch();
    $product['stock'] = $stockResult['total_stock'] ?? 0;
}
unset($product); // Clean up reference

// Add discount information to products
$products = addDiscountInfoToProducts($pdo, $products);

// Count results
$resultCount = count($products);


// Group products by category (for normal view)
$categories = [];
foreach ($products as $product) {
    $categories[$product['category']][] = $product;
}




include("includes/header.php");
include("index.html");

// Include footer
include("includes/footer.html");

// Include chat popup widget
//include("chat/popup_chat/widget.php");

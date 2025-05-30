<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/utils.php';

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
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build query
// Initialize template variables
$templateVars = [
    'searchQuery' => $search,
    'resultCount' => 0,
    'selectedCategory' => null
];

$query = "SELECT * FROM products";
$params = [];
$where = [];

if (!empty($search)) {
    $where[] = "name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($categorySlug)) {
    // Find the real category name from slug
    $categoryName = null;
    foreach ($allCategories as $cat) {
        if (category_to_slug($cat) === $categorySlug) {
            $categoryName = $cat;
            $templateVars['selectedCategory'] = $categoryName;
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

// Set the result count for the template
$templateVars['resultCount'] = count($products);

// Make template variables available in HTML
extract($templateVars);

// Group products by category (for normal view)
$categories = [];
foreach ($products as $product) {
    $categories[$product['category']][] = $product;
}

// Set variables for the view
$searchMessage = '';
if (!empty($search)) {
    $searchMessage = 'Kết quả tìm kiếm cho: "' . htmlspecialchars($search) . '" (' . count($products) . ' sản phẩm)';
} elseif (!empty($categorySlug) && isset($categoryName)) {
    $searchMessage = 'Danh mục: ' . htmlspecialchars($categoryName) . ' (' . count($products) . ' sản phẩm)';
}

include("includes/header.php");
include("index.html");

// Include footer
include("includes/footer.html");

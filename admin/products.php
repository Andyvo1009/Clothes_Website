<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Require admin role to access this page
requireAdmin();

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Product
    if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $brand = trim($_POST['brand']);
        $size = trim($_POST['size']);
        $color = trim($_POST['color']);
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

        // Handle image upload
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $image;

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // If editing and there's an existing image, get it
                if ($_POST['action'] === 'edit' && $product_id) {
                    $oldImageStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                    $oldImageStmt->execute([$product_id]);
                    $oldImage = $oldImageStmt->fetchColumn();

                    // Delete old image if it exists
                    if ($oldImage && file_exists($upload_dir . $oldImage)) {
                        unlink($upload_dir . $oldImage);
                    }
                }
            } else {
                $message = 'Lỗi khi tải lên hình ảnh.';
                $image = null;
            }
        } elseif ($_POST['action'] === 'edit' && $product_id) {
            // Keep existing image if editing and no new image uploaded
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $image = $stmt->fetchColumn();
        }

        // Add or update product in database
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, description, price, image, stock, brand, size, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $description, $price, $image, $stock, $brand, $size, $color]);
            $message = 'Sản phẩm đã được thêm thành công.';
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, description = ?, price = ?, image = ?, stock = ?, brand = ?, size = ?, color = ? WHERE id = ?");
            $stmt->execute([$name, $category, $description, $price, $image, $stock, $brand, $size, $color, $product_id]);
            $message = 'Sản phẩm đã được cập nhật thành công.';
        }
    }

    // Delete Product
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];

        // Get image filename before deleting
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $image = $stmt->fetchColumn();

        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        // Delete the image file if it exists
        if ($image && file_exists('../assets/images/' . $image)) {
            unlink('../assets/images/' . $image);
        }

        $message = 'Sản phẩm đã được xóa thành công.';
    }
}

// Fetch all products for the list
$stmt = $pdo->query("SELECT * FROM products ORDER BY category, name");
$products = $stmt->fetchAll();

// Get unique categories for the dropdown
$categories = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - VPF Fashion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .admin-header h1 {
            margin: 0;
            color: #333;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
        }

        .product-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 100px;
        }

        .product-list {
            background-color: #fff;
            border-radius: 8px;
            overflow: auto;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .product-table th {
            background-color: #f5f5f5;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .button {
            padding: 8px 16px;
            background-color: #4e54c8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .button-edit {
            background-color: #4CAF50;
        }

        .button-delete {
            background-color: #f44336;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            background-color: #e8f5e9;
            border-left: 5px solid #4CAF50;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Quản lý Sản phẩm</h1>
            <a href="../index.php" class="button">Quay lại Trang chủ</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="admin-content">
            <div class="product-form">
                <h2>Thêm/Sửa Sản phẩm</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="product_id" id="product-id">

                    <div class="form-group">
                        <label for="name">Tên sản phẩm</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Danh mục</label>
                        <select id="category" name="category" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="new">Danh mục mới...</option>
                        </select>
                    </div>

                    <div class="form-group" id="new-category-group" style="display: none;">
                        <label for="new-category">Tên danh mục mới</label>
                        <input type="text" id="new-category" name="new_category">
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Giá (VNĐ)</label>
                        <input type="number" id="price" name="price" min="0" step="1000" required>
                    </div>

                    <div class="form-group">
                        <label for="image">Hình ảnh</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <div id="current-image"></div>
                    </div>

                    <div class="form-group">
                        <label for="stock">Số lượng tồn kho</label>
                        <input type="number" id="stock" name="stock" min="0" value="10">
                    </div>

                    <div class="form-group">
                        <label for="brand">Thương hiệu</label>
                        <input type="text" id="brand" name="brand">
                    </div>

                    <div class="form-group">
                        <label for="size">Kích cỡ (phân cách bằng dấu phẩy)</label>
                        <input type="text" id="size" name="size" placeholder="VD: S,M,L,XL">
                    </div>

                    <div class="form-group">
                        <label for="color">Màu sắc (phân cách bằng dấu phẩy)</label>
                        <input type="text" id="color" name="color" placeholder="VD: Đen,Trắng,Xanh">
                    </div>

                    <button type="submit" class="button">Lưu sản phẩm</button>
                    <button type="button" class="button" onclick="resetForm()">Làm mới</button>
                </form>
            </div>

            <div class="product-list">
                <h2>Danh sách Sản phẩm</h2>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên</th>
                            <th>Danh mục</th>
                            <th>Giá (VNĐ)</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['id']) ?></td>
                                <td>
                                    <?php if (!empty($product['image']) && file_exists('../assets/images/' . $product['image'])): ?>
                                        <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                            <span>No Image</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= number_format($product['price'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($product['stock']) ?></td>
                                <td>
                                    <button type="button" class="button button-edit" onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">Sửa</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <button type="submit" class="button button-delete">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('category').addEventListener('change', function() {
            const newCategoryGroup = document.getElementById('new-category-group');
            if (this.value === 'new') {
                newCategoryGroup.style.display = 'block';
                document.getElementById('new-category').setAttribute('required', 'required');
            } else {
                newCategoryGroup.style.display = 'none';
                document.getElementById('new-category').removeAttribute('required');
            }
        });

        function editProduct(product) {
            document.getElementById('form-action').value = 'edit';
            document.getElementById('product-id').value = product.id;
            document.getElementById('name').value = product.name;

            const categorySelect = document.getElementById('category');
            let categoryExists = false;

            for (let i = 0; i < categorySelect.options.length; i++) {
                if (categorySelect.options[i].value === product.category) {
                    categorySelect.selectedIndex = i;
                    categoryExists = true;
                    break;
                }
            }

            if (!categoryExists && product.category) {
                const newOption = new Option(product.category, product.category);
                categorySelect.add(newOption, categorySelect.options[categorySelect.options.length - 1]);
                newOption.selected = true;
            }

            document.getElementById('description').value = product.description || '';
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('brand').value = product.brand || '';
            document.getElementById('size').value = product.size || '';
            document.getElementById('color').value = product.color || '';

            const currentImage = document.getElementById('current-image');
            if (product.image) {
                currentImage.innerHTML = `<p>Hình ảnh hiện tại: <img src="../assets/images/${product.image}" alt="${product.name}" style="max-width: 100px; max-height: 100px; margin-top: 5px;"></p>`;
            } else {
                currentImage.innerHTML = '<p>Chưa có hình ảnh</p>';
            }

            document.querySelector('button[type="submit"]').textContent = 'Cập nhật';
        }

        function resetForm() {
            document.getElementById('form-action').value = 'add';
            document.getElementById('product-id').value = '';
            document.getElementById('current-image').innerHTML = '';
            document.querySelector('form').reset();
            document.querySelector('button[type="submit"]').textContent = 'Lưu sản phẩm';
        }
    </script>
</body>

</html>
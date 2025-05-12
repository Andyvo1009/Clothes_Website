<?php
require_once '../includes/db.php';

// Create products table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    stock INT DEFAULT 10,
    brand VARCHAR(100),
    size VARCHAR(50),
    color VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$pdo->exec($createTableSQL);

// Create cart_items table if it doesn't exist
$createCartTableSQL = "CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    size VARCHAR(10),
    color VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";
$pdo->exec($createCartTableSQL);

// Check if products table is empty
$checkProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Insert sample products if table is empty
if ($checkProducts == 0) {
    $insertSQL = "INSERT INTO products (name, category, description, price, image, stock, brand, size, color) VALUES 
    ('Áo Nam Tay Ngắn', 'Đồ Nam', 'Áo nam tay ngắn chất liệu cotton cao cấp', 199000, NULL, 20, 'VPF', 'M,L,XL', 'Đen,Trắng,Xanh'),
    ('Áo Len Lông Cừu Ức', 'Đồ Nam', 'Áo len lông cừu ức ấm áp cho mùa đông', 499000, NULL, 15, 'VPF', 'L,XL', 'Nâu,Xám'),
    ('Áo Len Nam Gile', 'Đồ Nam', 'Áo len nam gile thanh lịch, phù hợp mặc công sở', 399000, NULL, 25, 'VPF', 'M,L,XL', 'Đen,Xanh đậm'),
    ('Áo Len Nam', 'Đồ Nam', 'Áo len nam dệt kim chất lượng cao', 299000, NULL, 30, 'VPF', 'S,M,L,XL', 'Xanh,Đỏ,Đen'),
    
    ('Áo Tay Dài Nữ', 'Đồ Nữ', 'Áo tay dài nữ chất liệu mềm mại, thoáng mát', 299000, NULL, 18, 'VPF', 'S,M,L', 'Hồng,Trắng,Đen'),
    ('Áo Len Cổ Tim', 'Đồ Nữ', 'Áo len cổ tim thời trang cho phái nữ', 399000, NULL, 22, 'VPF', 'S,M,L', 'Đỏ,Xanh,Tím'),
    ('Áo Len Tay Lửng Nữ', 'Đồ Nữ', 'Áo len tay lửng nữ phong cách Hàn Quốc', 299000, NULL, 15, 'VPF', 'S,M', 'Đen,Trắng'),
    ('Áo Len Nữ', 'Đồ Nữ', 'Áo len nữ dáng rộng thời trang', 399000, NULL, 20, 'VPF', 'S,M,L', 'Xanh nhạt,Hồng nhạt'),
    
    ('Áo Nam Tay Dài', 'Đồ Bé Trai', 'Áo tay dài cho bé trai từ 5-10 tuổi', 199000, NULL, 25, 'VPF Kids', 'S,M,L', 'Xanh,Đỏ,Vàng'),
    ('Áo Nữ Tay Ngắn', 'Đồ Bé Gái', 'Áo tay ngắn cho bé gái từ 5-10 tuổi', 199000, NULL, 25, 'VPF Kids', 'S,M,L', 'Hồng,Tím,Trắng'),
    ('Áo Nam Tay Ngắn', 'Đồ Bé Trai', 'Áo tay ngắn cho bé trai từ 3-7 tuổi', 199000, NULL, 30, 'VPF Kids', 'XS,S,M', 'Xanh,Đỏ'),
    ('Áo Len Nữ Tay Dài', 'Đồ Bé Gái', 'Áo len tay dài cho bé gái từ 3-7 tuổi', 199000, NULL, 30, 'VPF Kids', 'XS,S,M', 'Hồng,Tím')";
    
    $pdo->exec($insertSQL);
    echo "Sample products have been inserted successfully.";
} else {
    echo "Database already has products.";
}

echo "<p>Database setup completed. <a href='../index.php'>Go to homepage</a></p>";
?> 
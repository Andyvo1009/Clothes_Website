<?php
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPF Fashion - Thời trang Việt Nam</title>
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="/FirstWebsite/index.php">
                    <h1 style="color: #c62828; font-size: 1.8rem;">VPF FASHION</h1>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="/FirstWebsite/index.php" class="active">Home</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-nam">Đồ Nam</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-nu">Đồ Nữ</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-be-trai">Đồ Bé Trai</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-be-gai">Đồ Bé Gái</a></li>
                </ul>
            </nav>
            <form class="search-bar" method="get" action="/FirstWebsite/index.php">
                <input type="text" name="search" placeholder="Tìm kiếm Tên gì...">
                <button type="submit" name="submiter">Tìm kiếm</button>
            </form>
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <a href="#" class="icon-link user-menu-trigger">
                            <i class="fas fa-user"></i>
                            <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                        </a>
                        <div class="dropdown-content">
                            <a href="/FirstWebsite/user/profile.php">Hồ sơ</a>
                            <a href="/FirstWebsite/user/orders.php">Đơn hàng</a>
                            <?php if (getCurrentUsername() === 'admin'): ?>
                                <a href="/FirstWebsite/admin/products.php">Quản lý</a>
                            <?php endif; ?>
                            <a href="/FirstWebsite/auth/logout.php">Đăng xuất</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/FirstWebsite/auth/login.php" class="icon-link"><i class="fas fa-user"></i></a>
                <?php endif; ?>
                <a href="#" class="icon-link"><i class="fas fa-heart"></i></a>
                <a href="/FirstWebsite/cart/index.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .username {
            margin-left: 5px;
            font-size: 0.9em;
        }

        .user-menu-trigger {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch cart count
            fetchCartCount();
        });

        function fetchCartCount() {
            fetch('/FirstWebsite/cart/count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count;
                })
                .catch(error => console.error('Error:', error));
        }

        // Listen for cart updates
        window.addEventListener('cartUpdated', function(e) {
            if (e.detail && typeof e.detail.count !== 'undefined') {
                document.getElementById('cart-count').textContent = e.detail.count;
            }
        });
    </script>
</body>

</html>
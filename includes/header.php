<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine current page and category for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_category = $_GET['category'] ?? '';
$current_search = $_GET['search'] ?? '';

// Function to determine if a nav item should be active
function isActive($page, $category = '')
{
    global $current_page, $current_category, $current_search;

    // If we're searching, no category should be active
    if (!empty($current_search)) {
        return false;
    }

    if ($page === 'home') {
        return ($current_page === 'index.php' && empty($current_category));
    }

    return ($current_page === 'index.php' && $current_category === $category);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPF Fashion - Thời trang Việt Nam</title>
    <link
      rel="icon"
      type="image/x-icon"
      href="/FirstWebsite/assets/images/favicon.ico"
    />
    <link rel="stylesheet" href="/FirstWebsite/assets/css/style.css?v=<?= time() ?>">
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
                    <li><a href="/FirstWebsite/index.php" class="<?= isActive('home') ? 'active' : '' ?>">Home</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-nam" class="<?= isActive('category', 'do-nam') ? 'active' : '' ?>">Đồ Nam</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-nu" class="<?= isActive('category', 'do-nu') ? 'active' : '' ?>">Đồ Nữ</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-be-trai" class="<?= isActive('category', 'do-be-trai') ? 'active' : '' ?>">Đồ Bé Trai</a></li>
                    <li><a href="/FirstWebsite/index.php?category=do-be-gai" class="<?= isActive('category', 'do-be-gai') ? 'active' : '' ?>">Đồ Bé Gái</a></li>
                </ul>
            </nav>
            <form class="search-bar" method="get" action="/FirstWebsite/index.php">
                <input type="text" name="search" placeholder="Tìm kiếm Tên gì...">
                <button type="submit" name="submiter">Tìm kiếm</button>
            </form>
            <div class="user-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="/FirstWebsite/admin/products.php" class="icon-link admin-link" title="Admin Panel">
                            <i class="fas fa-cog"></i>
                        </a>
                        <a href="/FirstWebsite/chat/admin.php" class="icon-link chat-link" title="Chat Admin">
                            <i class="fas fa-comments"></i>
                        </a>
                    <?php else: ?>
                        <a href="/FirstWebsite/chat/client.php" class="icon-link chat-link" title="Hỗ trợ khách hàng">
                            <i class="fas fa-headset"></i>
                        </a>
                    <?php endif; ?> <span class="user-greeting" style="margin-right: 10px; color: #333; font-weight: 500;">
                        Xin chào, <?= htmlspecialchars($_SESSION['email']) ?>
                    </span>
                    <a href="/FirstWebsite/profile/index.php" class="icon-link profile-link" title="Hồ sơ cá nhân">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="/FirstWebsite/auth/logout.php" class="icon-link logout-link" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="/FirstWebsite/auth/login.php" class="icon-link login-link" title="Đăng nhập">
                        <i class="fas fa-user"></i>
                    </a>
                    <!-- <a href="/FirstWebsite/auth/register.php" class="icon-link register-link" title="Đăng ký">
                        <i class="fas fa-user-plus"></i>
                    </a> --> <?php endif; ?>
                <a href="/FirstWebsite/cart/index.php" class="icon-link cart-icon" title="Giỏ hàng">

                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch cart count
            fetchCartCount();

            // Function to fetch cart count
            function fetchCartCount() {
                fetch('/FirstWebsite/cart/count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCartCount(data.count);
                        }
                    })
                    .catch(error => console.error('Error fetching cart count:', error));
            }

            // Function to update cart count in UI
            function updateCartCount(count) {
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = count;

                    if (count > 0) {
                        cartCountElement.classList.add('active');
                    } else {
                        cartCountElement.classList.remove('active');
                    }
                }
            }

            // Custom event listener for cart updates
            window.addEventListener('cartUpdated', function(e) {
                if (e.detail && e.detail.count !== undefined) {
                    updateCartCount(e.detail.count);
                } else {
                    fetchCartCount();
                }
            });
        });
    </script>

    <style>
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-greeting {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
            white-space: nowrap;
        }

        .icon-link {
            position: relative;
            color: #333;
            text-decoration: none;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .icon-link:hover {
            background-color: #f5f5f5;
            color: #c62828;
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #c62828;
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .cart-count.active {
            opacity: 1;
        }

        /* Specific styling for different icon types */
        .login-link {
            color: #4CAF50;
        }

        .login-link:hover {
            background-color: #e8f5e8;
            color: #2e7d32;
        }

        .register-link {
            color: #2196F3;
        }

        .register-link:hover {
            background-color: #e3f2fd;
            color: #1565C0;
        }

        .logout-link {
            color: #f44336;
        }

        .logout-link:hover {
            background-color: #ffebee;
            color: #c62828;
        }

        .admin-link {
            color: #FF9800;
        }

        .admin-link:hover {
            background-color: #fff3e0;
            color: #e65100;
        }

        .chat-link {
            color: #4CAF50;
        }

        .chat-link:hover {
            background-color: #e8f5e8;
            color: #2e7d32;
        }

        .wishlist-link:hover {
            color: #e91e63;
        }
    </style>

</body>

</html>
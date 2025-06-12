# VPF Fashion - Website Bán Quần Áo

Website bán hàng thời trang được xây dựng với PHP, tích hợp thanh toán PayOS và hệ thống chat real-time.

## Tính Năng Chính

- **Quản lý sản phẩm**: Catalog đầy đủ với variants (size, màu sắc)

- **Giỏ hàng thông minh**: Hỗ trợ cả khách và thành viên

- **Thanh toán PayOS**: Tích hợp cổng thanh toán Việt Nam

- **Hệ thống chat**: Chat giữa admin và khách hàng

- **Quản lý đơn hàng**: Theo dõi trạng thái đơn hàng

- **Panel admin**: Quản lý sản phẩm, đơn hàng, khuyến mãi

- **Xác thực người dùng**: Đăng ký/đăng nhập với reset password qua email

## Công Nghệ Sử Dụng

- **Backend**: PHP 8.1+, MySQL 8.0

- **Dependencies**: Composer, PHPMailer, PayOS SDK, Ratchet/ReactPHP

- **Frontend**: HTML5/CSS3, JavaScript

## Hướng Dẫn Cài Đặt

### **Yêu Cầu Hệ Thống**

- PHP >= 8.1 (với extensions: pdo_mysql, mbstring, curl)

- MySQL >= 8.0

- Composer

- XAMPP

### **Cài Đặt với XAMPP**

1.  **Download và cài đặt dependencies**

```powershell

cd C:\xampp\htdocs\FirstWebsite

composer install

```

2.  **Tạo database**

- Mở phpMyAdmin: `http://localhost/phpmyadmin`

- Tạo database tên `first_web`

- Import file `init.sql`

3.  **Cấu hình môi trường**

```powershell

# Copy file cấu hình

copy .env.example .env

# Chỉnh sửa file .env với thông tin database và email

```

4.  **Chạy website**

- Khởi động Apache và MySQL trong XAMPP

- Truy cập: `http://localhost/FirstWebsite`

## 🔑 Tài Khoản Đăng Nhập

- **Admin**: `vokhoinguyen2017@gmail.com` / `Nguyenvo123`

- **Test User**: `Nguyenvo10092004@gmail.com` / `Nguyenvo123`

## 📁 Cấu Trúc Project

```

FirstWebsite/

├── index.php # Trang chủ

├── admin/ # Quản trị

├── auth/ # Đăng ký/đăng nhập

├── cart/ # Giỏ hàng

├── payment/ # Thanh toán

├── chat/ # Hệ thống chat

└── init.sql # Database schema

```

## Truy Cập

- **Website**: `http://localhost/FirstWebsite`

- **Admin Panel**: `http://localhost/FirstWebsite/admin/products.php`

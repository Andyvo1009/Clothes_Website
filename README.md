
# FirstWebsite - Hướng dẫn cài đặt và sử dụng

Dự án website PHP với tính năng gửi email sử dụng PHPMailer và chat popup.

## Yêu cầu hệ thống

### PHP Extensions (Bắt buộc)
- **PHP >= 5.5.0**
- **ext-ctype** - Xử lý kiểu ký tự
- **ext-filter** - Lọc và xác thực dữ liệu
- **ext-hash** - Hàm băm

### PHP Extensions (Khuyến nghị)
- **ext-mbstring** - Hỗ trợ mã hóa đa byte (UTF-8)
- **ext-openssl** - Gửi email SMTP bảo mật và ký DKIM

### Phần mềm cần thiết
- [Composer](https://getcomposer.org/download/) - Quản lý thư viện PHP
- [XAMPP](https://www.apachefriends.org/) hoặc web server tương tự

## Cài đặt nhanh

### Bước 1: Tải mã nguồn
```bash
git clone <repository-url>
cd FirstWebsite
```

### Bước 2: Cài đặt thư viện PHP
```bash
composer install
```
Lệnh này sẽ cài đặt:
- **PHPMailer v6.10.0** - Thư viện gửi email chuyên nghiệp

### Bước 3: Cấu hình web server
- Đặt thư mục dự án vào `c:\xampp\htdocs\FirstWebsite`
- Khởi động Apache và MySQL trong XAMPP
- Truy cập: `http://localhost/FirstWebsite`

## Cấu trúc thư mục

```
FirstWebsite/
├── vendor/              # Thư viện PHP (tự động tạo)
├── chat/               # Hệ thống chat popup
│   └── popup_chat/
├── composer.json       # Cấu hình Composer
├── composer.lock       # Khóa phiên bản thư viện
└── README.md          # File này
```

## Sử dụng PHPMailer

### Cách import và sử dụng:
```php
<?php
// Import autoloader của Composer
require_once __DIR__ . '/vendor/autoload.php';

// Import các class cần thiết
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // Cấu hình SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@gmail.com';
    $mail->Password   = 'your-app-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Người gửi và nhận
    $mail->setFrom('your-email@gmail.com', 'Your Name');
    $mail->addAddress('recipient@example.com');
    
    // Nội dung email
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<h1>Hello from PHPMailer!</h1>';
    
    $mail->send();
    echo 'Email đã gửi thành công!';
} catch (Exception $e) {
    echo "Lỗi gửi email: {$mail->ErrorInfo}";
}
?>
```

## 🔧 Khắc phục sự cố

### Lỗi thiếu PHP Extensions
```bash
# Ubuntu/Debian
sudo apt-get install php-ctype php-filter php-hash php-mbstring php-openssl

# Windows XAMPP - thường đã có sẵn
# Kiểm tra trong php.ini, bỏ comment các dòng:
# extension=ctype
# extension=filter
# extension=hash
# extension=mbstring
# extension=openssl
```

### Lỗi Composer
```bash
# Cập nhật Composer
composer self-update

# Xóa cache và cài lại
composer clear-cache
composer install --no-cache
```

### Lỗi PHPMailer SMTP
- Kiểm tra thông tin SMTP server
- Sử dụng App Password cho Gmail
- Bật Less Secure Apps (nếu cần)

##  Tài liệu tham khảo

- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
- [Composer Documentation](https://getcomposer.org/doc/)

##  Cập nhật dự án

```bash
# Kéo code mới nhất
git pull origin main

# Cập nhật thư viện (nếu có thay đổi)
composer install
```

## Lưu ý quan trọng

1. **Không commit file cấu hình email** chứa mật khẩu
2. **File `composer.lock`** phải được commit để đảm bảo tất cả cùng sử dụng phiên bản thư viện giống nhau
3. **Thư mục `vendor/`** không nên commit vào Git (đã có trong .gitignore)

## Hỗ trợ

Nếu gặp vấn đề, hãy:
1. Kiểm tra PHP version: `php -v`
2. Kiểm tra extensions: `php -m`
3. Xem log lỗi trong XAMPP Control Panel

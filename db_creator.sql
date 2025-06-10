-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 08:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `first_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-06-06 15:59:41', '2025-06-06 15:59:41'),
(2, 12, '2025-06-07 01:23:37', '2025-06-07 01:23:37'),
(4, 13, '2025-06-08 01:05:38', '2025-06-08 01:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `variant_id`, `quantity`, `added_at`) VALUES
(4, 2, 82, 8, '2025-06-07 01:23:46'),
(5, 2, 58, 1, '2025-06-07 02:13:22'),
(6, 2, 66, 1, '2025-06-07 02:13:26'),
(8, 4, 15, 1, '2025-06-08 01:51:55'),
(12, 2, 15, 1, '2025-06-09 15:55:59'),
(14, 1, 96, 1, '2025-06-10 23:52:38');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`) VALUES
(1, 1, 1, '2025-06-08 02:29:35'),
(2, 12, 1, '2025-06-08 02:33:02'),
(3, 13, 1, '2025-06-08 23:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `name`, `description`, `discount_percent`, `discount_amount`, `start_date`, `end_date`, `active`, `created_at`) VALUES
(1, 'Giảm giá mùa hè 2025', 'Giảm giá 20% cho tất cả sản phẩm trong mùa hè', 20.00, NULL, '2025-06-01 00:00:00', '2025-08-31 23:59:59', 1, '2025-06-08 01:25:00'),
(2, 'Flash Sale', 'Giảm 50,000đ cho đơn hàng', NULL, 50000.00, '2025-06-08 00:00:00', '2025-06-15 23:59:59', 1, '2025-06-08 01:25:00'),
(3, 'Khuyến mãi cuối tuần', 'Giảm 15% cho sản phẩm cuối tuần', 15.00, NULL, '2025-06-07 00:00:00', '2025-06-08 23:59:59', 1, '2025-06-08 01:25:00'),
(4, 'Sale áo nam', 'Giảm 30% cho tất cả áo nam', 30.00, NULL, '2025-06-01 00:00:00', '2025-12-31 23:59:59', 1, '2025-06-08 01:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `receiver_id`, `message`, `image`, `is_read`, `created_at`) VALUES
(1, 1, 1, 1, 'qweqwe', NULL, 1, '2025-06-08 02:31:32'),
(2, 1, 1, 1, 'qweasdzxc', NULL, 1, '2025-06-08 02:31:35'),
(3, 2, 12, 1, 'concac', NULL, 1, '2025-06-08 02:34:04'),
(4, 2, 12, 1, 'asdaosdjasd', NULL, 1, '2025-06-08 02:34:06'),
(5, 2, 12, 1, 'qewq', NULL, 1, '2025-06-08 02:34:09'),
(6, 2, 1, 12, 'm ngu vcl', NULL, 1, '2025-06-08 02:34:45'),
(7, 2, 12, 1, 'asdqwe', NULL, 1, '2025-06-08 02:36:13'),
(8, 2, 12, 1, 'qweqes', NULL, 1, '2025-06-08 02:36:15'),
(9, 2, 12, 1, 'zxczxc', NULL, 1, '2025-06-08 02:36:16'),
(10, 2, 1, 12, 'asdads', NULL, 1, '2025-06-08 02:49:54'),
(11, 2, 1, 12, 'qweqwef wqtwk', NULL, 1, '2025-06-08 02:49:56'),
(12, 2, 12, 1, 'bot di ia lai', NULL, 1, '2025-06-08 03:08:09'),
(13, 2, 12, 1, 'con cac', NULL, 1, '2025-06-08 04:20:37'),
(14, 2, 12, 1, 'clm', NULL, 1, '2025-06-08 04:20:38'),
(15, 2, 1, 12, 'vcl', NULL, 1, '2025-06-08 04:20:55'),
(16, 2, 1, 12, 'why you do that', NULL, 1, '2025-06-08 04:20:59'),
(17, 2, 1, 12, 'ia it thoi', NULL, 1, '2025-06-08 04:21:05'),
(18, 2, 1, 12, 'bot di dai lai', NULL, 1, '2025-06-08 04:23:22'),
(19, 2, 1, 12, 'ccccccc', NULL, 1, '2025-06-08 14:58:35'),
(20, 2, 1, 12, 'hello', NULL, 1, '2025-06-08 15:35:20'),
(21, 2, 12, 1, 'Cho minh hoi gia cua don hang nay', NULL, 1, '2025-06-08 15:36:06'),
(22, 2, 12, 1, 'qewq', NULL, 1, '2025-06-08 23:12:20'),
(23, 2, 12, 1, 'anh ko muon la so 2', 'uploads/chat_1749399161_7705.png', 1, '2025-06-08 23:12:41'),
(24, 2, 12, 1, '[Hình ảnh]', 'uploads/chat_1749399176_9933.jpg', 1, '2025-06-08 23:12:56'),
(25, 2, 12, 1, 'iuashd', NULL, 1, '2025-06-08 23:28:36'),
(26, 2, 12, 1, 'qiuweqh', NULL, 1, '2025-06-08 23:28:37'),
(27, 2, 12, 1, '[Hình ảnh]', 'uploads/chat_1749400131_5222.jpg', 1, '2025-06-08 23:28:51'),
(28, 3, 13, 1, 'Chao cc', NULL, 1, '2025-06-08 23:39:39'),
(29, 3, 13, 1, '[Hình ảnh]', 'uploads/chat_1749400801_1538.jpg', 1, '2025-06-08 23:40:01'),
(30, 2, 1, 12, 'ok', NULL, 1, '2025-06-08 23:41:36'),
(31, 2, 1, 12, 'you bad', NULL, 1, '2025-06-08 23:41:37'),
(32, 2, 1, 12, 'af', NULL, 1, '2025-06-08 23:41:39'),
(33, 2, 1, 12, 'tbh i dont think u gonna make it', NULL, 1, '2025-06-08 23:41:46'),
(34, 2, 1, 12, 'jaksd', NULL, 1, '2025-06-08 23:50:17'),
(35, 2, 1, 12, 'kjasd', NULL, 1, '2025-06-08 23:53:57'),
(36, 2, 12, 1, '[Hình ảnh]', 'uploads/chat_1749402513_2085.jfif', 1, '2025-06-09 00:08:33'),
(37, 3, 1, 13, '[Hình ảnh]', 'uploads/37.png', 0, '2025-06-09 00:21:56'),
(38, 2, 1, 12, '[Hình ảnh]', 'uploads/38.jfif', 1, '2025-06-09 00:22:52'),
(39, 2, 1, 12, 'andy la toi', NULL, 1, '2025-06-09 00:23:23'),
(40, 2, 12, 1, 'Nguyenvo123', NULL, 1, '2025-06-09 01:42:15'),
(41, 2, 12, 1, '[Hình ảnh]', 'uploads/41.png', 1, '2025-06-09 01:42:58'),
(42, 2, 12, 1, 'fg', NULL, 1, '2025-06-09 01:43:33'),
(43, 2, 12, 1, 'qwe', NULL, 1, '2025-06-09 01:43:35'),
(44, 2, 12, 1, 'andy is good', NULL, 1, '2025-06-09 01:43:38'),
(45, 2, 1, 12, 'shut the fuck up', NULL, 1, '2025-06-09 01:43:57'),
(46, 2, 12, 1, 'this is client', NULL, 1, '2025-06-09 01:44:25'),
(47, 2, 12, 1, 'hello', NULL, 1, '2025-06-09 15:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `price`, `brand`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Áo Sơ Mi Nam Công Sở', 'Đồ Nam', 'Áo sơ mi nam chất liệu cotton cao cấp, phù hợp cho môi trường công sở. Thiết kế thanh lịch, thoải mái.', 350000.00, 'VPF Fashion', 'product/product_1.jpeg', '2025-06-06 14:32:32', '2025-06-11 00:40:37'),
(2, 'Quần Âu Nam Slim Fit', 'Đồ Nam', 'Quần âu nam form slim fit, chất liệu vải cao cấp, co giãn nhẹ. Phù hợp đi làm và dự tiệc.', 450000.00, 'VPF Fashion', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(3, 'Áo Polo Nam Premium', 'Đồ Nam', 'Áo polo nam chất liệu pique cotton, thoáng mát, phù hợp cho cả môi trường công sở và dạo phố.', 280000.00, 'VPF Fashion', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(4, 'Váy Công Sở Nữ Thanh Lịch', 'Đồ Nữ', 'Váy công sở nữ thiết kế thanh lịch, chất liệu vải tốt, form dáng tôn dáng. Phù hợp cho môi trường công sở.', 520000.00, 'VPF Fashion', 'product/product_4.jpg', '2025-06-06 14:32:32', '2025-06-11 00:40:50'),
(5, 'Áo Blouse Nữ Tay Dài', 'Đồ Nữ', 'Áo blouse nữ tay dài, chất liệu silk mềm mại, thiết kế nữ tính và thanh lịch.', 380000.00, 'VPF Fashion', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(6, 'Quần Jeans Nữ Skinny', 'Đồ Nữ', 'Quần jeans nữ form skinny, chất liệu denim cao cấp, co giãn tốt, tôn dáng.', 420000.00, 'VPF Fashion', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(7, 'Áo Thun Bé Trai Hoạt Hình', 'Đồ Bé Trai', 'Áo thun bé trai với họa tiết hoạt hình dễ thương, chất liệu cotton mềm mại, an toàn cho trẻ em.', 150000.00, 'VPF Kids', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(8, 'Quần Short Bé Trai', 'Đồ Bé Trai', 'Quần short bé trai chất liệu thể thao, thoáng mát, phù hợp cho các hoạt động vận động.', 180000.00, 'VPF Kids', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(9, 'Váy Công Chúa Bé Gái', 'Đồ Bé Gái', 'Váy công chúa bé gái thiết kế xinh xắn, chất liệu mềm mại, phù hợp cho các dịp đặc biệt.', 320000.00, 'VPF Kids', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(10, 'Áo Len Bé Gái', 'Đồ Bé Gái', 'Áo len bé gái ấm áp, mềm mại, thiết kế dễ thương với họa tiết tim nhỏ.', 250000.00, 'VPF Kids', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32'),
(11, 'Áo Khoác Nam Bomber', 'Đồ Nam', 'Áo khoác bomber nam phong cách streetwear, chất liệu polyester cao cấp, chống gió nhẹ.', 680000.00, 'VPF Fashion', 'product/product_11.jpeg', '2025-06-06 14:32:32', '2025-06-11 00:40:59'),
(12, 'Đầm Maxi Nữ Bohemian', 'Đồ Nữ', 'Đầm maxi nữ phong cách bohemian, chất liệu voan nhẹ, thiết kế thoải mái cho mùa hè.', 580000.00, 'VPF Fashion', '', '2025-06-06 14:32:32', '2025-06-06 14:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `product_discounts`
--

CREATE TABLE `product_discounts` (
  `product_id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_discounts`
--

INSERT INTO `product_discounts` (`product_id`, `discount_id`) VALUES
(1, 1),
(1, 4),
(2, 1),
(2, 4),
(3, 1),
(4, 2),
(10, 2);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 10,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `image`, `stock`, `size`, `color`, `created_at`, `updated_at`, `product_id`) VALUES
(13, NULL, 5, 'S', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(14, NULL, 8, 'M', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(15, NULL, 6, 'L', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(16, NULL, 4, 'S', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(17, NULL, 7, 'M', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(18, NULL, 5, 'L', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(19, NULL, 6, 'M', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(20, NULL, 8, 'L', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 1),
(21, NULL, 10, '30', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(22, NULL, 8, '31', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(23, NULL, 12, '32', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(24, NULL, 6, '30', 'Xám Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(25, NULL, 9, '31', 'Xám Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(26, NULL, 7, '32', 'Xám Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(27, NULL, 5, '31', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(28, NULL, 8, '32', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 2),
(29, NULL, 10, 'S', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(30, NULL, 15, 'M', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(31, NULL, 12, 'L', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(32, NULL, 8, 'M', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(33, NULL, 10, 'L', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(34, NULL, 6, 'M', 'Đỏ Đô', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(35, NULL, 5, 'L', 'Xanh Lá', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 3),
(36, NULL, 6, 'S', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(37, NULL, 8, 'M', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(38, NULL, 5, 'L', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(39, NULL, 4, 'S', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(40, NULL, 7, 'M', 'Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(41, NULL, 6, 'M', 'Xám', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(42, NULL, 4, 'L', 'Xám', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 4),
(43, NULL, 12, 'S', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(44, NULL, 15, 'M', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(45, NULL, 8, 'L', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(46, NULL, 6, 'S', 'Hồng Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(47, NULL, 9, 'M', 'Hồng Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(48, NULL, 7, 'M', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(49, NULL, 5, 'L', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 5),
(50, NULL, 8, '25', 'Xanh Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(51, NULL, 10, '26', 'Xanh Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(52, NULL, 9, '27', 'Xanh Đậm', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(53, NULL, 5, '25', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(54, NULL, 7, '26', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(55, NULL, 6, '27', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(56, NULL, 8, '26', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(57, NULL, 7, '27', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 6),
(58, NULL, 12, '2-3T', 'Xanh Dương', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(59, NULL, 15, '4-5T', 'Xanh Dương', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(60, NULL, 10, '6-7T', 'Xanh Dương', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(61, NULL, 8, '2-3T', 'Đỏ', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(62, NULL, 12, '4-5T', 'Đỏ', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(63, NULL, 9, '6-7T', 'Đỏ', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(64, NULL, 6, '4-5T', 'Vàng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(65, NULL, 8, '6-7T', 'Xanh Lá', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 7),
(66, NULL, 10, '2-3T', 'Xanh Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(67, NULL, 15, '4-5T', 'Xanh Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(68, NULL, 12, '6-7T', 'Xanh Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(69, NULL, 8, '8-9T', 'Xanh Navy', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(70, NULL, 10, '4-5T', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(71, NULL, 9, '6-7T', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(72, NULL, 7, '6-7T', 'Xám', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(73, NULL, 6, '8-9T', 'Xám', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 8),
(74, NULL, 8, '2-3T', 'Hồng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(75, NULL, 12, '4-5T', 'Hồng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(76, NULL, 10, '6-7T', 'Hồng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(77, NULL, 6, '2-3T', 'Tím', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(78, NULL, 9, '4-5T', 'Tím', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(79, NULL, 7, '6-7T', 'Tím', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(80, NULL, 5, '4-5T', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(81, NULL, 6, '6-7T', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 9),
(82, NULL, 10, '2-3T', 'Hồng Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(83, NULL, 12, '4-5T', 'Hồng Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(84, NULL, 8, '6-7T', 'Hồng Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(85, NULL, 7, '2-3T', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(86, NULL, 10, '4-5T', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(87, NULL, 9, '6-7T', 'Trắng', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(88, NULL, 6, '4-5T', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(89, NULL, 5, '6-7T', 'Xanh Nhạt', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 10),
(90, NULL, 5, 'M', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(91, NULL, 8, 'L', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(92, NULL, 6, 'XL', 'Đen', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(93, NULL, 4, 'M', 'Xanh Rêu', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(94, NULL, 7, 'L', 'Xanh Rêu', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(95, NULL, 3, 'L', 'Nâu', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(96, NULL, 4, 'XL', 'Nâu', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 11),
(97, NULL, 6, 'S', 'Hoa Nhí', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(98, NULL, 8, 'M', 'Hoa Nhí', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(99, NULL, 4, 'L', 'Hoa Nhí', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(100, NULL, 5, 'S', 'Xanh Biển', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(101, NULL, 7, 'M', 'Xanh Biển', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(102, NULL, 3, 'M', 'Cam Đất', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12),
(103, NULL, 4, 'L', 'Cam Đất', '2025-06-06 14:32:32', '2025-06-06 14:32:32', 12);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `role` enum('admin','client') NOT NULL DEFAULT 'client',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `timestamp`, `role`, `first_name`, `last_name`, `phone`, `address`) VALUES
(1, 'vokhoinguyen2017@gmail.com', '$2y$10$XKWa2R1Jr1M.h.wTmscEEu4SA/nuqbgBuh9//Pj77AACApUlouUfC', '2025-05-09 04:21:57', 'admin', 'Nguyên', 'Võ', '0915538518', '22a/6 Đ. Thống Nhất, Đông Hoà, Dĩ An, Bình Dương'),
(9, 'admin@vpf.com', '$2y$10$Q3Q0nvZtFnvDL6W9.pOOPuSVHrnTLReSoTWE1X/71nTxWHpgwacq2', '2025-06-06 07:32:32', 'admin', NULL, NULL, NULL, NULL),
(10, 'customer1@test.com', '$2y$10$dU6ktKz8399MQnI3tDKgDumO.LL806qxW38tgmhNyemBkEPfwPcQi', '2025-06-06 07:32:32', 'client', NULL, NULL, NULL, NULL),
(11, 'customer2@test.com', '$2y$10$XAmQA2PO7nLuA1oWyv4kqOOOFMsfUFCbADCbMBBEsyxGpio4ez/1i', '2025-06-06 07:32:32', 'client', NULL, NULL, NULL, NULL),
(12, 'Nguyenvo10092004@gmail.com', '$2y$10$HYZBFGxz8y3xkG4wWIajzeXs1VcKwB9Vx6L7hv/JNjdR8tKgxjAym', '2025-06-06 18:23:31', 'client', NULL, NULL, NULL, NULL),
(13, 'danghophuonganh@gmail.com', '$2y$10$qfngz9VDRPj2xsgsu/P5Cu4pBD.9GzsiyFtqb3jNJksucAqXSVgp6', '2025-06-07 18:05:18', 'client', NULL, NULL, NULL, NULL),
(14, '12i3hni@gmail.com', '$2y$10$5J9mVMKlT3wWBWtn9yAtUelj6f/AI59SLYFrKTB2WwTAc6S4Al1am', '2025-06-10 15:49:13', 'client', 'Nguyen', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD PRIMARY KEY (`product_id`,`discount_id`),
  ADD KEY `discount_id` (`discount_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD CONSTRAINT `product_discounts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_discounts_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

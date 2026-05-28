-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 03, 2026 at 09:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webp`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'T-Shirts', 'Various styles of t-shirts', '2026-01-29 17:20:26'),
(2, 'Pants', 'Jeans, trousers, and shorts', '2026-01-29 17:20:26'),
(3, 'Shoes', 'Footwear for all occasions', '2026-01-29 17:20:26'),
(4, 'Accessories', 'Hats, belts, and other accessories', '2026-01-29 17:20:26'),
(5, 'hat', 'ysaguiushduishd', '2026-02-02 08:44:21'),
(6, 'ffds', 'vvf', '2026-02-03 07:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `confirmed_received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `confirmed_received_at`, `created_at`) VALUES
(1, 2, 150000.00, 'cancelled', NULL, '2026-01-29 17:55:52'),
(2, 2, 150000.00, 'processing', NULL, '2026-01-29 18:09:28'),
(3, 2, 150000.00, 'cancelled', NULL, '2026-01-29 18:10:42'),
(4, 2, 150000.00, 'processing', NULL, '2026-01-29 18:12:34'),
(5, 2, 150000.00, 'delivered', '2026-02-02 06:53:36', '2026-02-02 06:46:01'),
(6, 5, 2000000.00, 'delivered', '2026-02-02 08:50:51', '2026-02-02 08:46:38'),
(7, 5, 4000000.00, 'processing', NULL, '2026-02-02 09:13:09'),
(8, 5, 2000000.00, 'processing', NULL, '2026-02-02 09:13:51'),
(9, 5, 800000.00, 'processing', NULL, '2026-02-02 09:17:57'),
(10, 5, 2000000.00, 'processing', NULL, '2026-02-02 09:19:55'),
(11, 5, 2000000.00, 'processing', NULL, '2026-02-02 09:21:16'),
(12, 5, 2000000.00, 'processing', NULL, '2026-02-02 09:27:15'),
(13, 5, 150000.00, 'processing', NULL, '2026-02-02 09:27:57'),
(14, 5, 150000.00, 'processing', NULL, '2026-02-02 09:28:19'),
(15, 5, 150000.00, 'processing', NULL, '2026-02-02 09:30:12'),
(16, 5, 800000.00, 'processing', NULL, '2026-02-02 09:33:34'),
(17, 5, 150000.00, 'processing', NULL, '2026-02-02 09:50:15'),
(18, 5, 150000.00, 'processing', NULL, '2026-02-02 09:53:37'),
(19, 5, 800000.00, 'processing', NULL, '2026-02-02 10:02:20'),
(20, 5, 800000.00, 'processing', NULL, '2026-02-02 10:20:25'),
(21, 5, 800000.00, 'processing', NULL, '2026-02-02 11:21:02'),
(22, 5, 800000.00, 'processing', NULL, '2026-02-02 11:38:56'),
(23, 5, 6000000.00, 'delivered', '2026-02-03 07:11:28', '2026-02-03 07:09:19'),
(24, 5, 84.00, 'pending', NULL, '2026-02-03 07:14:53'),
(25, 5, 42.00, 'processing', NULL, '2026-02-03 07:18:41'),
(26, 5, 2800042.00, 'cancelled', NULL, '2026-02-03 07:29:13'),
(27, 5, 2000000.00, 'cancelled', NULL, '2026-02-03 07:36:17'),
(28, 5, 150000.00, 'shipped', NULL, '2026-02-03 07:51:47'),
(29, 5, 800000.00, 'delivered', '2026-02-03 07:58:27', '2026-02-03 07:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 150000.00),
(2, 2, 1, 1, 150000.00),
(3, 3, 1, 1, 150000.00),
(4, 4, 1, 1, 150000.00),
(5, 5, 1, 1, 150000.00),
(6, 6, 2, 1, 2000000.00),
(7, 7, 2, 2, 2000000.00),
(8, 8, 2, 1, 2000000.00),
(9, 9, 3, 1, 800000.00),
(10, 10, 2, 1, 2000000.00),
(11, 11, 2, 1, 2000000.00),
(12, 12, 2, 1, 2000000.00),
(13, 13, 1, 1, 150000.00),
(14, 14, 1, 1, 150000.00),
(15, 15, 1, 1, 150000.00),
(16, 16, 3, 1, 800000.00),
(17, 17, 1, 1, 150000.00),
(18, 18, 1, 1, 150000.00),
(19, 19, 3, 1, 800000.00),
(20, 20, 3, 1, 800000.00),
(21, 21, 3, 1, 800000.00),
(22, 22, 3, 1, 800000.00),
(23, 23, 2, 3, 2000000.00),
(24, 24, 4, 2, 42.00),
(25, 25, 4, 1, 42.00),
(26, 26, 2, 1, 2000000.00),
(27, 26, 4, 1, 42.00),
(28, 26, 3, 1, 800000.00),
(29, 27, 2, 1, 2000000.00),
(30, 28, 1, 1, 150000.00),
(31, 29, 3, 1, 800000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `proof_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `user_id`, `amount`, `payment_method`, `status`, `transaction_id`, `notes`, `created_at`, `updated_at`, `proof_image`) VALUES
(1, 2, 2, 150000.00, 'e_wallet', 'completed', 'TRX-20260129190935-2', NULL, '2026-01-29 18:09:35', '2026-01-29 18:09:35', ''),
(2, 4, 2, 150000.00, 'transfer_bank', 'pending', 'TRX-20260129191955-4', NULL, '2026-01-29 18:19:55', '2026-01-29 18:19:55', ''),
(3, 5, 2, 150000.00, 'e_wallet', 'completed', 'TRX-20260202074614-5', NULL, '2026-02-02 06:46:14', '2026-02-02 06:46:14', ''),
(4, 6, 5, 2000000.00, 'e_wallet', 'completed', 'TRX-20260202094703-6', NULL, '2026-02-02 08:47:03', '2026-02-02 08:47:03', ''),
(5, 7, 5, 4000000.00, 'e_wallet', 'completed', 'TRX-20260202101315-7', NULL, '2026-02-02 09:13:15', '2026-02-02 09:13:15', ''),
(6, 8, 5, 2000000.00, 'e_wallet', 'completed', 'TRX-20260202101357-8', NULL, '2026-02-02 09:13:57', '2026-02-02 09:13:57', ''),
(7, 9, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202101803-9', NULL, '2026-02-02 09:18:03', '2026-02-02 09:18:03', ''),
(8, 11, 5, 2000000.00, 'e_wallet', 'completed', 'TRX-20260202102400-11', NULL, '2026-02-02 09:24:00', '2026-02-02 09:24:00', ''),
(9, 10, 5, 2000000.00, 'e_wallet', 'completed', 'TRX-20260202102649-10', NULL, '2026-02-02 09:26:49', '2026-02-02 09:26:49', ''),
(10, 12, 5, 2000000.00, 'e_wallet', 'completed', 'TRX-20260202102725-12', NULL, '2026-02-02 09:27:25', '2026-02-02 09:27:25', ''),
(11, 13, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260202102801-13', NULL, '2026-02-02 09:28:01', '2026-02-02 09:28:01', ''),
(12, 14, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260202102827-14', NULL, '2026-02-02 09:28:27', '2026-02-02 09:28:27', ''),
(13, 15, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260202103020-15', NULL, '2026-02-02 09:30:20', '2026-02-02 09:30:20', ''),
(14, 16, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202103337-16', NULL, '2026-02-02 09:33:37', '2026-02-02 09:33:37', ''),
(15, 17, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260202105018-17', NULL, '2026-02-02 09:50:18', '2026-02-02 09:50:18', ''),
(16, 18, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260202105344-18', NULL, '2026-02-02 09:53:44', '2026-02-02 09:53:44', ''),
(17, 19, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202110224-19', NULL, '2026-02-02 10:02:24', '2026-02-02 10:10:45', 'proof_order_19_1770027045.jpg'),
(18, 20, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202112029-20', NULL, '2026-02-02 10:20:29', '2026-02-02 11:20:45', 'proof_order_20_1770031245.jpg'),
(19, 21, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202122106-21', NULL, '2026-02-02 11:21:06', '2026-02-02 11:21:17', 'proof_order_21_1770031277.jpg'),
(20, 22, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260202123902-22', NULL, '2026-02-02 11:39:02', '2026-02-02 11:39:17', 'proof_order_22_1770032357.jpg'),
(21, 23, 5, 6000000.00, 'e_wallet', 'completed', 'TRX-20260203080923-23', NULL, '2026-02-03 07:09:23', '2026-02-03 07:09:54', 'proof_order_23_1770102594.jpg'),
(22, 24, 5, 84.00, 'transfer_bank', 'pending', 'TRX-20260203081545-24', NULL, '2026-02-03 07:15:45', '2026-02-03 07:15:45', ''),
(23, 25, 5, 42.00, 'e_wallet', 'completed', 'TRX-20260203081910-25', NULL, '2026-02-03 07:19:10', '2026-02-03 07:19:50', 'proof_order_25_1770103190.jpg'),
(24, 27, 5, 2000000.00, 'e_wallet', 'pending', 'TRX-20260203083623-27', NULL, '2026-02-03 07:36:23', '2026-02-03 07:36:23', ''),
(25, 28, 5, 150000.00, 'e_wallet', 'completed', 'TRX-20260203085151-28', NULL, '2026-02-03 07:51:51', '2026-02-03 07:52:19', 'proof_order_28_1770105139.jpg'),
(26, 29, 5, 800000.00, 'e_wallet', 'completed', 'TRX-20260203085707-29', NULL, '2026-02-03 07:57:07', '2026-02-03 07:57:24', 'proof_order_29_1770105444.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category_id`, `image`, `created_at`) VALUES
(1, 'heels mahal', 'dress pokonya mah', 150000.00, 14, 3, 'pict_1769708120_263e676d.jpg', '2026-01-29 17:35:20'),
(2, 'topi kerenuhuwshdyuhdh', 'uhdwdgwjnduhsaghas', 2000000.00, 2, 5, 'pict_1770021906_4087a3fd.jpg', '2026-02-02 08:45:06'),
(3, 'jhwsuHEIUHSIGS', 'HS8UqhsuwHY', 800000.00, 68, 2, 'pict_1770021946_ce3b4fd9.jpg', '2026-02-02 08:45:46'),
(4, 'gfgb', 'bdgbd', 42.00, 0, 6, 'pict_1770102817_196cec3d.jpg', '2026-02-03 07:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(2, 'kesa', '$2y$10$pxIRtqVFsmnNUkCI7Vu0luAJAyCcixhuCgZwzkPKXMYpzqihPHAwi', 'orahayuningroro@gmail.com', 'user', '2026-01-29 17:21:13'),
(4, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@store.com', 'admin', '2026-01-29 17:32:35'),
(5, 'pak rizal', '$2y$10$mI00Hr0J7jni2ApRYcooN.txTRCgLcQXXzo8up4RSqOB5Wv/LS9qG', 'rizal@gmail.com', 'user', '2026-02-02 08:41:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_payment` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

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
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

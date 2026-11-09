-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2026 at 09:36 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chating`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_one` int(10) UNSIGNED NOT NULL,
  `user_two` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user_one`, `user_two`, `created_at`) VALUES
(1, 1, 2, '2026-11-02 22:06:50'),
(2, 1, 3, '2026-11-02 22:30:16'),
(3, 1, 4, '2026-11-02 23:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `friendships`
--

CREATE TABLE `friendships` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_one` int(10) UNSIGNED NOT NULL,
  `user_two` int(10) UNSIGNED NOT NULL,
  `requested_by` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friendships`
--

INSERT INTO `friendships` (`id`, `user_one`, `user_two`, `requested_by`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 3, 1, 'accepted', '2026-11-02 23:34:42', '2026-11-02 23:34:42'),
(4, 1, 2, 2, 'accepted', '2026-11-02 23:58:52', '2026-11-02 23:59:12'),
(6, 2, 3, 2, 'pending', '2026-11-03 00:05:49', '2026-11-03 00:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `genders`
--

CREATE TABLE `genders` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(40) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genders`
--

INSERT INTO `genders` (`id`, `name`, `sort_order`, `is_active`) VALUES
(1, 'Male', 1, 1),
(2, 'Female', 2, 1),
(3, 'Other', 3, 1),
(4, 'Prefer not to say', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(120) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `body`, `file_name`, `file_path`, `file_type`, `file_size`, `location_lat`, `location_lng`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Hello Bob!', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-11-02 22:06:50'),
(2, 2, 1, 'h', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-11-02 22:30:33'),
(3, 1, 1, 'hi', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-11-02 22:55:29'),
(4, 1, 1, 'h', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-11-02 22:55:51'),
(5, 1, 1, 'aa', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-11-02 22:56:34'),
(6, 1, 2, '😁', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-11-02 23:07:20'),
(7, 1, 1, 'see file', '_test.txt', 'uploads/chat/20261102191050_29dbc86ff948.txt', 'text/plain', 12, NULL, NULL, 1, '2026-11-02 23:10:50'),
(8, 1, 1, '📍 Shared current location', NULL, NULL, NULL, NULL, 25.2048000, 55.2708000, 1, '2026-11-02 23:15:48'),
(9, 1, 1, '', 'WhatsApp Image 2026-10-13 at 21.48.42.jpeg', 'uploads/chat/20261102191617_dfceaf83ad8f.jpeg', 'image/jpeg', 254910, NULL, NULL, 1, '2026-11-02 23:16:17'),
(10, 1, 2, '📍 Shared current location', NULL, NULL, NULL, NULL, 31.4152234, 74.2543997, 1, '2026-11-02 23:16:48'),
(11, 1, 2, '', 'front.png', 'uploads/chat/20261102191913_8dcc997bf558.png', 'image/png', 434338, NULL, NULL, 1, '2026-11-02 23:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `typing_status`
--

CREATE TABLE `typing_status` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('idle','typing','erasing') NOT NULL DEFAULT 'idle',
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_status`
--

INSERT INTO `typing_status` (`conversation_id`, `user_id`, `status`, `updated_at`) VALUES
(1, 1, 'idle', '2026-11-02 19:16:17'),
(1, 2, 'idle', '2026-11-02 19:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `country_code` varchar(8) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `gender_id` int(10) UNSIGNED NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(80) NOT NULL,
  `avatar_color` varchar(7) NOT NULL DEFAULT '#0d6efd',
  `avatar_path` varchar(255) DEFAULT NULL,
  `user_type` enum('system','normal') NOT NULL DEFAULT 'normal',
  `last_seen` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `country_code`, `phone`, `gender_id`, `password`, `display_name`, `avatar_color`, `avatar_path`, `user_type`, `last_seen`, `created_at`) VALUES
(1, 'alice', 'alice@example.com', '+92', '3001111111', 4, '$2y$12$CT0qz.CxffF8hrx8VNMdkOXmv6GZsU/SmjLTOVDxAU2.i2ejdhuMy', 'Alice Johnson', '#0d6efd', NULL, 'system', '2026-11-02 21:26:59', '2026-11-02 22:06:10'),
(2, 'bob', 'bob@example.com', '+92', '9000000002', 4, '$2y$12$GlynbiyRdsWNZoVle4l0IeAxoFyWgj4fM6oE6RY2dyMzLVHOPIOHC', 'Bob Smith', '#198754', 'uploads/avatars/20261102211502_eb5813bd088c.jpg', 'normal', '2026-11-02 21:27:38', '2026-11-02 22:06:10'),
(3, 'carol', 'carol@example.com', '+92', '9000000003', 4, '$2y$12$8tJjg1/Vk/Y5UYmO2wkrQOq9SttIZ4pKkvziKhI92XlgI1I9H7gOi', 'Carol Lee', '#dc3545', NULL, 'normal', NULL, '2026-11-02 22:06:10'),
(4, 'dave', 'dave@example.com', '+92', '9000000004', 4, '$2y$12$8tJjg1/Vk/Y5UYmO2wkrQOq9SttIZ4pKkvziKhI92XlgI1I9H7gOi', 'Dave Updated', '#198754', NULL, 'normal', NULL, '2026-11-02 22:39:25'),
(5, 'norm108', 'norm108@ex.com', '+92', '9000000005', 4, '$2y$12$i2q2tNao5y7aKF2LKEjtNO/1Z8EmqwuY04DCgtncYORePD9Tp.2aO', 'Norm User', '#fd7e14', NULL, 'normal', NULL, '2026-11-02 23:44:03'),
(6, 'bybob7936', 'bybob7936@ex.com', '+92', '9000000006', 4, '$2y$12$LjBrpnO0vZaGYFnm7Qxxd.PSlZCB/XxcqfUPx1QVjQ0UIjYIvdSWy', 'By Bob', '#0d6efd', NULL, 'normal', NULL, '2026-11-03 00:11:56'),
(7, 'ph4005', 'ph4005@ex.com', '+92', '3001234567', 4, '$2y$12$FGPF0IZf4mWASM31fEs8F.z87ptEY8KXvoXrgsCVcxHxRiZQAsywq', 'Phone User', '#0d6efd', NULL, 'normal', NULL, '2026-11-03 00:30:43'),
(8, 'g4284', 'g4284@ex.com', '+92', '3012345678', 1, '$2y$12$Idt/ZDGNYZtSA4Nr6U2jW..w7fXjwPOP1H5Z1g43GPr4guQpEsFlW', 'Gender User', '#0d6efd', NULL, 'normal', NULL, '2026-11-03 01:02:43'),
(9, 'av3494', 'av3494@ex.com', '+92', '3099999999', 1, '$2y$12$N7N5m8jPTpStdHfJ2OHa9.OZXNFos0FXfLGEJvVOJ0yJiaFjAVlOi', 'Avatar User', '#0d6efd', 'uploads/avatars/20261102211327_dde1bb3ebe86.png', 'normal', NULL, '2026-11-03 01:13:27'),
(10, 'lt5432', 'lt5432@ex.com', '+92', '3088888888', 2, '$2y$12$xfIETsxE6O4JMM9Sb/NLEeFto0ZuHiB7JvSo3SmHI7kma9Am7gyYW', 'Letter Only', '#0d6efd', NULL, 'normal', NULL, '2026-11-03 01:13:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pair` (`user_one`,`user_two`),
  ADD KEY `fk_conv_user_two` (`user_two`);

--
-- Indexes for table `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pair` (`user_one`,`user_two`),
  ADD KEY `fk_friend_two` (`user_two`),
  ADD KEY `fk_friend_req` (`requested_by`);

--
-- Indexes for table `genders`
--
ALTER TABLE `genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_sender` (`sender_id`),
  ADD KEY `idx_conversation_created` (`conversation_id`,`created_at`),
  ADD KEY `idx_conversation_unread` (`conversation_id`,`is_read`,`sender_id`);

--
-- Indexes for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `fk_typing_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_phone_pair` (`country_code`,`phone`),
  ADD KEY `fk_users_gender` (`gender_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `genders`
--
ALTER TABLE `genders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_user_one` FOREIGN KEY (`user_one`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_user_two` FOREIGN KEY (`user_two`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `fk_friend_one` FOREIGN KEY (`user_one`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_friend_req` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_friend_two` FOREIGN KEY (`user_two`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `fk_typing_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_typing_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_gender` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 14, 2026 at 01:30 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cricket_tournament_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `award_type`
--

CREATE TABLE `award_type` (
  `award_type_id` int NOT NULL,
  `award_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `award_type`
--

INSERT INTO `award_type` (`award_type_id`, `award_name`, `description`, `is_active`) VALUES
(1, 'Man of the Match', 'Best overall performer of the match', 1),
(2, 'Best Bowler', 'Best bowling performer of the match', 1),
(3, 'Fastest Bowler', 'Player who delivered the fastest ball', 1);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int NOT NULL,
  `team1_id` int NOT NULL,
  `team2_id` int NOT NULL,
  `match_date` date NOT NULL,
  `team1_score` int DEFAULT NULL,
  `team1_wickets` tinyint UNSIGNED DEFAULT NULL,
  `team1_overs` decimal(5,1) DEFAULT NULL,
  `team2_score` int DEFAULT NULL,
  `team2_wickets` tinyint UNSIGNED DEFAULT NULL,
  `team2_overs` decimal(5,1) DEFAULT NULL,
  `result_status` enum('pending','live','completed','draw') NOT NULL DEFAULT 'pending',
  `winner_team_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`match_id`, `team1_id`, `team2_id`, `match_date`, `team1_score`, `team1_wickets`, `team1_overs`, `team2_score`, `team2_wickets`, `team2_overs`, `result_status`, `winner_team_id`) VALUES
(1, 1, 2, '2026-07-21', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 1),
(2, 3, 4, '2026-07-21', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 3),
(3, 1, 3, '2026-07-22', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 1),
(4, 2, 4, '2026-07-22', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 2),
(5, 1, 4, '2026-07-23', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 1),
(6, 2, 3, '2026-07-23', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 2),
(7, 1, 2, '2026-07-24', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 1),
(9, 2, 1, '2026-07-31', NULL, NULL, NULL, NULL, NULL, NULL, 'completed', 1),
(10, 2, 3, '2026-07-30', 158, 8, '20.0', 156, 10, '19.5', 'completed', 2),
(11, 1, 3, '2026-07-30', 78, 0, '0.0', 15, 0, '0.0', 'completed', 1),
(12, 4, 3, '2026-07-30', 12, 1, '12.0', 12, 2, '13.0', 'draw', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `match_award`
--

CREATE TABLE `match_award` (
  `match_award_id` int NOT NULL,
  `match_id` int NOT NULL,
  `player_id` int NOT NULL,
  `award_type_id` int NOT NULL,
  `performance_value` decimal(10,2) DEFAULT NULL,
  `performance_unit` varchar(30) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE `player` (
  `player_id` int NOT NULL,
  `player_name` varchar(100) NOT NULL,
  `role` enum('Batsman','Bowler','All-Rounder','Wicket Keeper') NOT NULL,
  `team_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `player`
--

INSERT INTO `player` (`player_id`, `player_name`, `role`, `team_id`) VALUES
(1, 'Rezwanul Haque Rifat', 'All-Rounder', 1),
(2, 'Ashiq', 'All-Rounder', 1),
(3, 'Ayon', 'All-Rounder', 1),
(4, 'Fahim', 'Wicket Keeper', 1),
(5, 'Md. Samiul Anan', 'Wicket Keeper', 1),
(6, 'Shifat', 'All-Rounder', 2),
(7, 'Kausar Mahmud', 'All-Rounder', 2),
(8, 'Sabbir Hasan Ahad', 'Batsman', 2),
(9, 'Nabil', 'All-Rounder', 2),
(10, 'Abid', 'All-Rounder', 2),
(11, 'Shah Poran', 'All-Rounder', 3),
(12, 'Junayed Ahmed Ashik', 'All-Rounder', 3),
(13, 'Masud', 'All-Rounder', 3),
(14, 'Ahsan Siam', 'All-Rounder', 3),
(15, 'Raad Bin Rafiq', 'All-Rounder', 3),
(16, 'Shamim Rezwan Hridoy', 'Batsman', 4),
(17, 'Mesbahul Alam Toha', 'Wicket Keeper', 4),
(18, 'Prosenjit Kumar Halder', 'All-Rounder', 4),
(19, 'Jotirmoy Debnath', 'All-Rounder', 4),
(20, 'Naeem', 'Batsman', 4),
(21, 'Muaz', 'Wicket Keeper', 2),
(22, 'Muktadir', 'All-Rounder', 2),
(23, 'Shadman Sakib Noor', 'All-Rounder', 2),
(24, 'Fahad Khan', 'Batsman', 2),
(25, 'Mim', 'Bowler', 2),
(26, 'Shah Poran', 'All-Rounder', 3),
(27, 'Junayed Ahmed Ashik', 'All-Rounder', 3),
(28, 'Masud', 'All-Rounder', 3),
(29, 'Ahsan Siam', 'All-Rounder', 3),
(30, 'Raad Bin Rafiq', 'All-Rounder', 3),
(31, 'Shadman Rahman', 'Bowler', 3),
(32, 'Fahim Hossain', 'Bowler', 3),
(33, 'Arif', 'Batsman', 3),
(34, 'Mahadi Hasan', 'All-Rounder', 3),
(35, 'Rakib Hasan', 'Wicket Keeper', 3),
(36, 'Siam Ahmed', 'Bowler', 3),
(37, 'Robin', 'All-Rounder', 3),
(38, 'Tanvir', 'Batsman', 3),
(39, 'Nahid', 'Bowler', 3),
(40, 'Sakib Hasan', 'All-Rounder', 3),
(41, 'Shamim Rezwan Hridoy', 'Batsman', 4),
(42, 'Mesbahul Alam Toha', 'Wicket Keeper', 4),
(43, 'Prosenjit Kumar Halder', 'All-Rounder', 4),
(44, 'Jotirmoy Debnath', 'All-Rounder', 4),
(45, 'Naeem', 'Batsman', 4),
(46, 'Arafat', 'Bowler', 4),
(47, 'Arifur Rahman', 'All-Rounder', 4),
(48, 'Sajid', 'Bowler', 4),
(49, 'Sohan', 'Batsman', 4),
(50, 'Rafi', 'All-Rounder', 4),
(51, 'Tamim', 'Bowler', 4),
(52, 'Mahmud', 'All-Rounder', 4),
(53, 'Imran', 'Batsman', 4),
(54, 'Riyad', 'Bowler', 4),
(55, 'Sabbir', 'All-Rounder', 4),
(56, 'Mahmudul Hasan', 'Bowler', 1),
(57, 'Tanvir Ahmed', 'All-Rounder', 1),
(58, 'Rakib', 'Batsman', 1),
(59, 'Sajid Hossain', 'Bowler', 1),
(60, 'Nafis', 'All-Rounder', 1);

-- --------------------------------------------------------

--
-- Table structure for table `points_table`
--

CREATE TABLE `points_table` (
  `team_id` int NOT NULL,
  `matches_played` int DEFAULT '0',
  `wins` int DEFAULT '0',
  `losses` int DEFAULT '0',
  `draws` int NOT NULL DEFAULT '0',
  `points` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `points_table`
--

INSERT INTO `points_table` (`team_id`, `matches_played`, `wins`, `losses`, `draws`, `points`) VALUES
(1, 6, 6, 0, 0, 12),
(2, 6, 3, 3, 0, 6),
(3, 6, 1, 4, 1, 3),
(4, 4, 0, 3, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `score`
--

CREATE TABLE `score` (
  `score_id` int NOT NULL,
  `match_id` int NOT NULL,
  `player_id` int NOT NULL,
  `runs` int DEFAULT '0',
  `wickets` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `score`
--

INSERT INTO `score` (`score_id`, `match_id`, `player_id`, `runs`, `wickets`) VALUES
(1, 1, 47, 68, 1),
(2, 1, 48, 42, 2),
(3, 1, 49, 15, 3),
(4, 1, 3, 54, 0),
(5, 1, 2, 36, 1),
(6, 1, 11, 8, 2),
(7, 2, 16, 75, 1),
(8, 2, 17, 33, 2),
(9, 2, 21, 18, 1),
(10, 2, 31, 48, 0),
(11, 2, 33, 27, 2),
(12, 2, 42, 6, 1),
(13, 3, 46, 81, 0),
(14, 3, 47, 39, 2),
(15, 3, 50, 12, 3),
(16, 3, 18, 41, 1),
(17, 3, 22, 29, 1),
(18, 3, 30, 10, 2),
(19, 4, 1, 65, 1),
(20, 4, 8, 38, 2),
(21, 4, 14, 20, 2),
(22, 4, 35, 44, 0),
(23, 4, 36, 18, 1),
(24, 4, 41, 7, 2),
(25, 5, 48, 93, 1),
(26, 5, 55, 46, 2),
(27, 5, 59, 11, 4),
(28, 5, 34, 37, 1),
(29, 5, 39, 21, 0),
(30, 5, 45, 16, 1),
(31, 6, 2, 72, 1),
(32, 6, 6, 28, 3),
(33, 6, 10, 33, 0),
(34, 6, 19, 57, 1),
(35, 6, 24, 31, 1),
(36, 6, 29, 12, 2),
(37, 7, 46, 84, 0),
(38, 7, 47, 40, 2),
(39, 7, 49, 18, 3),
(40, 7, 1, 61, 0),
(41, 7, 3, 29, 1),
(42, 7, 11, 9, 2),
(43, 9, 1, 12, 2),
(44, 9, 24, 121, 7);

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `team_id` int NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `captain_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`team_id`, `team_name`, `captain_name`) VALUES
(1, 'Storm Breakers', 'Ashiq'),
(2, 'Byte Blasters', 'Ahad'),
(3, 'Infinity Strikers', 'Poran'),
(4, 'Quantum Hitters', 'Prosenjit');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','inactive','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `bio`, `status`, `approved_by`, `approved_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Mehedi Hasan', 'mehedi241-50-001@diu.edu.bd', '$2y$10$7gDs.0OUMEsue1QTKt0etODPB4ueZ7d/Q75v/R.JIvg2049FmqQbe', 'super_admin', '01766359263', NULL, 'active', NULL, '2026-07-27 20:08:38', '2026-07-30 23:25:50', '2026-07-27 14:08:38', '2026-07-30 17:25:50'),
(2, 'Tauhid Ayon', 'gateman@gmail.com', '$2y$10$TSKuUYSBS8at6G5hHH8gWel5hhj1YOCFAeJPU/h6shh4DMF.JVa6e', 'user', '123123', NULL, 'active', NULL, '2026-07-27 20:10:17', '2026-07-30 21:46:00', '2026-07-27 14:10:17', '2026-07-30 15:46:00'),
(3, 'Mehedi Hossain', 'mehediadmin@gmail.com', '$2y$10$5iwg14lSc6w5B7CrKk3qd.qr8ibgI59pvDSZv.h0LZyg4aECkrwe6', 'admin', '01944055947', NULL, 'active', 1, '2026-07-30 22:36:10', '2026-07-30 22:40:32', '2026-07-30 16:36:10', '2026-07-30 16:40:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `award_type`
--
ALTER TABLE `award_type`
  ADD PRIMARY KEY (`award_type_id`),
  ADD UNIQUE KEY `uq_award_name` (`award_name`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `winner_team_id` (`winner_team_id`);

--
-- Indexes for table `match_award`
--
ALTER TABLE `match_award`
  ADD PRIMARY KEY (`match_award_id`),
  ADD UNIQUE KEY `uq_match_award` (`match_id`,`award_type_id`),
  ADD KEY `idx_match_award_match` (`match_id`),
  ADD KEY `idx_match_award_player` (`player_id`),
  ADD KEY `idx_match_award_type` (`award_type_id`);

--
-- Indexes for table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `points_table`
--
ALTER TABLE `points_table`
  ADD PRIMARY KEY (`team_id`);

--
-- Indexes for table `score`
--
ALTER TABLE `score`
  ADD PRIMARY KEY (`score_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `award_type`
--
ALTER TABLE `award_type`
  MODIFY `award_type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `match_award`
--
ALTER TABLE `match_award`
  MODIFY `match_award_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `score`
--
ALTER TABLE `score`
  MODIFY `score_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `team_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`team1_id`) REFERENCES `team` (`team_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team2_id`) REFERENCES `team` (`team_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`winner_team_id`) REFERENCES `team` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `match_award`
--
ALTER TABLE `match_award`
  ADD CONSTRAINT `fk_match_award_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_award_player` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_award_type` FOREIGN KEY (`award_type_id`) REFERENCES `award_type` (`award_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `points_table`
--
ALTER TABLE `points_table`
  ADD CONSTRAINT `points_table_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `score`
--
ALTER TABLE `score`
  ADD CONSTRAINT `score_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `score_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

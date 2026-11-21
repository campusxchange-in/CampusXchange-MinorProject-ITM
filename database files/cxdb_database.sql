-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2026 at 07:16 PM
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
-- Database: `cxdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pending_email` varchar(150) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `admin_role` enum('Main Admin','Sub Admin') NOT NULL,
  `status` enum('Active','Suspended','Banned') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `email`, `pending_email`, `email_verified_at`, `password`, `admin_role`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Amit Sharma', 'nikhil@gmail.com', NULL, '2026-08-02 08:30:00', '$2y$10$hclHfe.H8T2BxCmXWCkrtO.U4k.GPws9hmEnHBKCUsYvwaTwzk.za', 'Main Admin', 'Active', '2026-09-19 21:38:51', '2026-10-29 13:52:37', NULL),
(2, 'Neha Verma', 'a@g.c', NULL, '2026-08-02 08:45:00', '$2y$10$XZgiKGE4TS1uuzJftYkUseQwORu/heRiRFfxAslk3XiLhix2GPPKW', 'Sub Admin', 'Suspended', '2026-09-19 21:38:51', '2026-11-18 12:29:40', NULL),
(9, 'Amit Sharma', 'nikhilr@gmail.com', '', '2026-11-14 22:34:30', '$2y$10$EFdv92JQ/vmqVLf66GlTCOQbtv2lAqEPp/s0IJR2yqrUYAhqSWjny', 'Sub Admin', 'Active', '2026-11-11 14:15:57', '2026-11-21 18:12:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_email_verification_tokens`
--

CREATE TABLE `admin_email_verification_tokens` (
  `verification_token_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_email_verification_tokens`
--

INSERT INTO `admin_email_verification_tokens` (`verification_token_id`, `admin_id`, `email`, `token`, `expires_at`, `verified_at`, `created_at`) VALUES
(1, 1, 'admin@campusportal.edu', 'test_admin_email_verify_1_a83f92', '2026-09-07 20:30:00', '2026-08-02 08:35:00', '2026-09-19 21:38:51'),
(2, 2, 'subadmin@campusportal.edu', 'test_admin_email_verify_2_b72c41', '2026-09-07 20:30:00', '2026-08-02 08:50:00', '2026-09-19 21:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` enum('Login','Logout','Create','Update','Delete','Restore','Verify User','Manage User','Manage Product','Review Report','Issue Ban','Revoke Ban','Manage Category','Change Settings','Manage Admin','Other') NOT NULL,
  `action_description` text NOT NULL,
  `target_type` enum('User','Admin','Product','Category','Report','Ban','System Setting','Other') DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action_type`, `action_description`, `target_type`, `target_id`, `ip_address`, `user_agent`, `created_at`, `deleted_at`) VALUES
(1, 1, 'Verify User', 'College ID verification approved for Rahul Sharma.', 'User', 1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(2, 1, 'Verify User', 'College ID verification approved for Priya Verma.', 'User', 2, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(3, 1, 'Review Report', 'Report regarding inappropriate user behavior was reviewed.', 'Report', 1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(4, 1, 'Issue Ban', 'Temporary ban issued to Rahul Sharma after report review.', 'Ban', 1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(5, 1, 'Review Report', 'Product report regarding misleading information was reviewed.', 'Report', 2, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(6, 1, 'Issue Ban', 'Warning issued to Priya Verma regarding misleading product information.', 'Ban', 2, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-19 21:38:51', NULL),
(7, 1, 'Manage User', 'Changed user #1 account status from Suspended to Banned.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:19:41', NULL),
(8, 1, 'Manage User', 'Changed user #1 account status from Banned to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:19:59', NULL),
(9, 1, 'Manage User', 'Logged user #1 out from all devices.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:20:09', NULL),
(10, 1, 'Manage User', 'Changed user #1 account status from Active to Pending.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:23:38', NULL),
(11, 1, 'Verify User', 'Approved verification for user #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:24:39', NULL),
(12, 1, 'Manage User', 'Changed user #1 account status from Pending to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 22:25:06', NULL),
(13, 1, 'Delete', 'Soft deleted user account #2.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 09:53:46', NULL),
(14, 1, '', 'Restored soft-deleted user account #2 (Priya Verma). Account status set to Pending.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:11:33', NULL),
(15, 1, 'Manage User', 'Changed user #2 account status from Pending to Active.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:12:42', NULL),
(16, 1, 'Manage User', 'Changed user #2 account status from Active to Suspended.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:18', NULL),
(17, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:41', NULL),
(18, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:45', NULL),
(19, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:49', NULL),
(20, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:53', NULL),
(21, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:23:59', NULL),
(22, 1, 'Manage User', 'Logged user #2 out from all devices.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:24:21', NULL),
(23, 1, 'Verify User', 'Approved verification for user #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:26:23', NULL),
(24, 1, 'Manage User', 'Changed user #1 account status from Active to Pending.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:37:16', NULL),
(25, 1, 'Manage User', 'Changed user #1 account status from Pending to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:37:24', NULL),
(26, 1, 'Verify User', 'Approved verification for user #2.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:39:30', NULL),
(27, 1, 'Manage User', 'Changed user #2 account status from Suspended to Active.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 10:45:18', NULL),
(28, 1, 'Manage Category', 'Changed category \'Electronics\' status to Inactive.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:03:29', NULL),
(29, 1, 'Manage Category', 'Changed category \'Electronics\' status to Active.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:03:32', NULL),
(30, 1, 'Manage Category', 'Changed category \'Books & Study Materials\' status from \'Active\' to \'Inactive\'.', 'Category', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:11:22', NULL),
(31, 1, 'Manage Category', 'Changed category \'Books & Study Materials\' status from \'Inactive\' to \'Active\'.', 'Category', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:11:26', NULL),
(32, 1, 'Manage Category', 'Updated category from \'Books & Study Materials\' to \'Books & Study Materials\'. Status set to Active.', 'Category', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:08', NULL),
(33, 1, 'Manage Category', 'Updated category from \'Books & Study Materials\' to \'Books & Study Materials\'. Status set to Inactive.', 'Category', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:17', NULL),
(34, 1, 'Manage Category', 'Updated category from \'Books & Study Materials\' to \'Books & Study Materials\'. Status set to Active.', 'Category', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:25', NULL),
(35, 1, 'Manage Category', 'Deleted category \'Furniture\'.', 'Category', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:43', NULL),
(36, 1, 'Manage Category', 'Changed category \'Electronics\' status from \'Active\' to \'Inactive\'.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:49', NULL),
(37, 1, 'Manage Category', 'Changed category \'Electronics\' status from \'Inactive\' to \'Active\'.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:12:53', NULL),
(38, 1, 'Manage Category', 'Changed category \'Electronics\' status from \'Active\' to \'Inactive\'.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:26:25', NULL),
(39, 1, 'Manage Category', 'Changed category \'Electronics\' status from \'Inactive\' to \'Active\'.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:27:03', NULL),
(40, 1, 'Manage Category', 'Deleted category \'Clothing & Accessories\'.', 'Category', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:27:31', NULL),
(41, 1, 'Manage Category', 'Created category \'clothing\'. Invoice required.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 11:27:47', NULL),
(42, 1, 'Issue Ban', 'Temporarily suspended user #1 for 2 day(s) after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:24:38', NULL),
(43, 1, 'Review Report', 'Resolved report #1 by issuing a temporary ban to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:24:38', NULL),
(44, 1, 'Review Report', 'Rejected report #2.', 'Report', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:30:25', NULL),
(45, 1, 'Review Report', 'Started reviewing report #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:37:55', NULL),
(46, 1, 'Review Report', 'Started reviewing report #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:45:38', NULL),
(47, 1, 'Delete', 'Soft deleted user account #2.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:49:39', NULL),
(48, 1, '', 'Restored soft-deleted user account #2 (Priya Verma). Account status set to Pending.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:49:48', NULL),
(49, 1, 'Manage User', 'Changed user #2 account status from Pending to Active.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 15:50:02', NULL),
(50, 1, 'Other', 'Hidden seller review #2 from \"Visible\" to \"Hidden\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:12:54', NULL),
(51, 1, 'Other', 'Restored seller review #2 from \"Hidden\" to \"Visible\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:12:57', NULL),
(52, 1, 'Other', 'Hidden seller review #2 from \"Visible\" to \"Hidden\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:13:01', NULL),
(53, 1, 'Other', 'Restored seller review #2 from \"Hidden\" to \"Visible\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:13:04', NULL),
(54, 1, 'Other', 'Hidden seller review #2 from \"Visible\" to \"Hidden\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:16:00', NULL),
(55, 1, 'Other', 'Restored seller review #2 from \"Hidden\" to \"Visible\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:16:45', NULL),
(56, 1, 'Other', 'Hidden seller review #2 from \"Visible\" to \"Hidden\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:16:51', NULL),
(57, 1, 'Other', 'Restored seller review #2 from \"Hidden\" to \"Visible\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:16:55', NULL),
(58, 1, 'Other', 'Hidden seller review #2 from \"Visible\" to \"Hidden\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:43:14', NULL),
(59, 1, 'Other', 'Restored seller review #2 from \"Hidden\" to \"Visible\".', 'Other', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 16:43:18', NULL),
(60, 1, 'Manage User', 'Logged user #1 out from all devices.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:26:38', NULL),
(61, 1, 'Verify User', 'Approved verification and activated account for user #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:26:49', NULL),
(62, 1, 'Review Report', 'Rejected report #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:32:54', NULL),
(63, 1, 'Issue Ban', 'Changed user #1 from Active to Suspended. Reason: jhbjhb', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:37:25', NULL),
(64, 1, 'Manage User', 'Reinstated user #1 from Suspended to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:37:37', NULL),
(65, 1, 'Manage User', 'Logged user #1 out from all devices.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:38:02', NULL),
(66, 1, 'Delete', 'Soft deleted user account #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:38:21', NULL),
(67, 1, '', 'Restored soft-deleted user account #1 (Rahul Sharma). Account status set to Pending.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:38:28', NULL),
(68, 1, 'Delete', 'Soft deleted user account #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:44:58', NULL),
(69, 1, '', 'Restored soft-deleted user account #1 (Rahul Sharma). Account status set to Pending.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:45:03', NULL),
(70, 1, 'Manage User', 'Reinstated user #1 from Banned to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:48:13', NULL),
(71, 1, 'Review Report', 'Started reviewing report #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:49:59', NULL),
(72, 1, 'Issue Ban', 'Temporarily suspended user #1 for 2 day(s) after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:51:40', NULL),
(73, 1, 'Review Report', 'Resolved report #1 by issuing a temporary ban to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:51:40', NULL),
(74, 1, 'Manage User', 'Reinstated user #1 from Suspended to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:52:21', NULL),
(75, 1, 'Issue Ban', 'Issued a warning to user #1 after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:54:00', NULL),
(76, 1, 'Review Report', 'Resolved report #1 by issuing a warning to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:54:00', NULL),
(77, 1, 'Issue Ban', 'Changed user #1 from Active to Suspended. Reason: jhgvjvjhjhb', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:03:01', NULL),
(78, 1, 'Manage User', 'Reinstated user #1 from Suspended to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:03:08', NULL),
(79, 1, 'Issue Ban', 'Issued a warning to user #1 after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:03:26', NULL),
(80, 1, 'Review Report', 'Resolved report #1 by issuing a warning to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:03:26', NULL),
(81, 1, 'Issue Ban', 'Changed user #1 from Active to Suspended. Reason: jhhjhh', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:04:08', NULL),
(82, 1, 'Manage User', 'Reinstated user #1 from Suspended to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:04:12', NULL),
(83, 1, 'Issue Ban', 'Permanently banned user #1 after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:04:56', NULL),
(84, 1, 'Review Report', 'Resolved report #1 by permanently banning user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:04:56', NULL),
(85, 1, 'Manage User', 'Reinstated user #1 from Banned to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:06:02', NULL),
(86, 1, 'Verify User', 'Approved verification for user #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:37:24', NULL),
(87, 1, 'Verify User', 'Approved verification for user #1.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 19:39:00', NULL),
(88, 1, 'Verify User', 'Approved verification request #1 for user \"Rahul Sharma\".', 'User', 1, NULL, NULL, '2026-09-20 20:22:13', NULL),
(89, 1, 'Verify User', 'Approved verification request #1 for user \"Rahul Sharma\".', 'User', 1, NULL, NULL, '2026-09-20 20:27:06', NULL),
(90, 1, 'Issue Ban', 'Changed user #1 from Active to Banned. Reason: hjhjh', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:42:17', NULL),
(91, 1, 'Manage User', 'Reinstated user #1 from Banned to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:42:23', NULL),
(92, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:46:17', NULL),
(93, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:46:23', NULL),
(94, 1, 'Delete', 'Soft deleted user account #2.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:51:52', NULL),
(95, 1, 'Restore', 'Restored soft-deleted user account #2 (Priya Verma). Account status set to Banned.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 12:51:57', NULL),
(96, 1, 'Issue Ban', 'Changed user #1 from Active to Banned. Reason: hgjhlhj', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:23:24', NULL),
(97, 1, 'Revoke Ban', 'Revoked Permanent ban #12 for user #1. Account status changed from Banned to Active. Reason: Account reinstated by administrator.', 'Ban', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:23:28', NULL),
(98, 1, 'Manage User', 'Reinstated user #1 from Banned to Active.', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:23:28', NULL),
(99, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:47:26', NULL),
(100, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 14:37:16', NULL),
(101, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 18:32:49', NULL),
(102, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-28 12:32:11', NULL),
(103, 1, 'Manage Category', 'Changed category \'clothing\' status from \'Active\' to \'Inactive\'.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-28 12:32:51', NULL),
(104, 1, 'Manage Category', 'Changed category \'clothing\' status from \'Inactive\' to \'Active\'.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-28 12:32:55', NULL),
(105, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:07:02', NULL),
(106, 1, 'Verify User', 'Rejected verification request #2 for user \"Priya Verma\". Reason: hghfhjhfjgjh', 'User', 2, NULL, NULL, '2026-10-29 13:38:57', NULL),
(107, 1, 'Verify User', 'Approved verification request #2 for user \"Priya Verma\".', 'User', 2, NULL, NULL, '2026-10-29 13:39:39', NULL),
(108, 1, 'Revoke Ban', 'Revoked Warning ban #2 for user #2. Account status changed from Banned to Active. Reason: Account reinstated by administrator.', 'Ban', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:39:58', NULL),
(109, 1, 'Manage User', 'Reinstated user #2 from Banned to Active.', 'User', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:39:58', NULL),
(110, 1, 'Change Settings', 'Updated global settings: platform_name, support_email, verification_enabled, require_id_front, require_id_back, verification_max_file_mb, notify_new_reports, notify_verification_requests, notify_new_users', 'System Setting', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:58:29', NULL),
(111, 1, 'Change Settings', 'Updated global settings: platform_name, support_email, verification_enabled, require_id_front, require_id_back, verification_max_file_mb, notify_new_reports, notify_verification_requests, notify_new_users', 'System Setting', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:58:36', NULL),
(112, 1, 'Change Settings', 'Updated global settings: platform_name, support_email, verification_enabled, require_id_front, require_id_back, verification_max_file_mb, notify_new_reports, notify_verification_requests, notify_new_users', 'System Setting', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 13:58:49', NULL),
(113, 1, 'Change Settings', 'Updated global settings: platform_name, support_email, verification_enabled, require_id_front, require_id_back, verification_max_file_mb, notify_new_reports, notify_verification_requests, notify_new_users', 'System Setting', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:31:09', NULL),
(114, 1, 'Review Report', 'Started reviewing report #2.', 'Report', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:49:03', NULL),
(115, 1, 'Review Report', 'Rejected report #2.', 'Report', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:49:56', NULL),
(116, 1, 'Issue Ban', 'Issued a warning to user #1 after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:50:41', NULL),
(117, 1, 'Review Report', 'Resolved report #1 by issuing a warning to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:50:41', NULL),
(118, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 16:52:26', NULL),
(119, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 11:09:40', NULL),
(120, 1, 'Create', 'Created a Sub Admin account for Amit Sharma (nikhilraykwar1998@gmail.com). Invitation email sent successfully.', 'Admin', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 13:31:54', NULL),
(124, 1, 'Create', 'Created a Sub Admin account for Amit Sharman (nikhilraykwar1998@gmail.com). Invitation email sent successfully.', 'Admin', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:00:33', NULL),
(125, 1, 'Create', 'Created a Sub Admin account for Amit Sharma (nikhilraykwar19985436536546@gmail.com). Invitation email sent successfully.', 'Admin', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:05:04', NULL),
(126, 1, 'Create', 'Created a Sub Admin account for Amit Sharma (nikhilraykwar1998@gmail.com). Invitation email sent successfully.', 'Admin', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:16:04', NULL),
(127, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:18:48', NULL),
(128, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:19:02', NULL),
(129, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 14:19:09', NULL),
(130, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 15:22:53', NULL),
(131, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 15:36:02', NULL),
(132, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 15:45:25', NULL),
(133, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 15:45:31', NULL),
(134, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:00:48', NULL),
(135, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:00:57', NULL),
(136, 1, 'Create', 'Created a Sub Admin account for Amit Sharman (campusxchange0@gmail.com). Invitation email could not be sent.', 'Admin', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:01:31', NULL),
(137, 1, 'Create', 'Created a Sub Admin account for Amit Sharman (nikhilraykwar@gmail.com). Invitation email could not be sent.', 'Admin', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:19:11', NULL),
(138, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:25:17', NULL),
(139, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:25:28', NULL),
(140, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:32:15', NULL),
(141, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:33:11', NULL),
(142, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:33:18', NULL),
(143, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 16:36:50', NULL),
(144, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 17:05:12', NULL),
(145, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 17:06:03', NULL),
(146, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 17:28:35', NULL),
(147, 9, 'Update', 'Administrator password was reset using the email recovery process.', 'Admin', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 17:48:49', NULL),
(148, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 17:48:59', NULL),
(149, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 18:08:04', NULL),
(150, 9, 'Issue Ban', 'Issued a warning to user #1 after reviewing report #1. Reason: Inappropriate behave', 'User', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 18:14:08', NULL),
(151, 9, 'Review Report', 'Resolved report #1 by issuing a warning to user #1.', 'Report', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 18:14:08', NULL),
(152, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 18:57:18', NULL),
(153, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 19:23:59', NULL),
(154, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-14 19:24:02', NULL),
(155, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 20:29:06', NULL),
(156, 9, 'Issue Ban', 'Changed user #3 from Active to Suspended. Reason: gg', 'User', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 20:29:45', NULL),
(157, 9, 'Revoke Ban', 'Revoked Temporary ban #15 for user #3. Account status changed from Suspended to Active. Reason: Account reinstated by administrator.', 'Ban', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 20:30:13', NULL),
(158, 9, 'Manage User', 'Reinstated user #3 from Suspended to Active.', 'User', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 20:30:13', NULL),
(159, 9, 'Verify User', 'Approved verification request #3 for user \"RNick7\".', 'User', 3, NULL, NULL, '2026-11-16 20:36:17', NULL),
(160, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 20:48:27', NULL),
(161, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 21:51:43', NULL),
(162, 9, 'Verify User', 'Approved verification request #11 for user \"RNick7\".', 'User', 3, NULL, NULL, '2026-11-16 21:51:51', NULL),
(163, 9, 'Verify User', 'Approved verification request #14 for user \"Nikhil Raykwar\".', 'User', 4, NULL, NULL, '2026-11-16 22:26:30', NULL),
(164, 9, 'Issue Ban', 'Issued a warning to user #3 after reviewing report #3. Reason: Inappropriate behave towards a fellow user', 'User', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 22:52:45', NULL),
(165, 9, 'Review Report', 'Resolved report #3 by issuing a warning to user #3.', 'Report', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 22:52:45', NULL),
(166, 9, 'Manage Category', 'Changed category \'clothing\' status from \'Active\' to \'Inactive\'.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:15:49', NULL),
(167, 9, 'Manage Category', 'Changed category \'clothing\' status from \'Inactive\' to \'Active\'.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:16:11', NULL),
(168, 9, 'Manage Category', 'Deleted category \'Electronics\'. Category had 2 active product(s) associated with it.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:17:14', NULL),
(169, 9, 'Manage Category', 'Restored category \'Electronics\'. Category restored as Inactive.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:51:03', NULL),
(170, 9, 'Manage Category', 'Changed category \'Electronics\' status from \'Inactive\' to \'Active\'.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:52:14', NULL),
(171, 9, 'Manage Category', 'Restored category \'Furniture\'. Category restored as Inactive.', 'Category', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:52:35', NULL),
(172, 9, 'Manage Category', 'Changed category \'Furniture\' status from \'Inactive\' to \'Active\'.', 'Category', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:52:44', NULL),
(173, 9, 'Manage Category', 'Updated category from \'clothing\' to \'clothing\'. Status set to Active. Invoice not required.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-16 23:54:11', NULL),
(174, 9, 'Manage Category', 'Restored category \'Clothing & Accessories\'. Category restored as Inactive.', 'Category', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-17 00:11:02', NULL),
(175, 9, 'Manage Category', 'Deleted category \'Clothing & Accessories\'.', 'Category', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-17 00:11:09', NULL),
(176, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-17 00:31:57', NULL),
(177, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-17 08:34:42', NULL),
(178, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 10:19:05', NULL),
(179, 9, 'Manage Category', 'Deleted category \'clothing\'.', 'Category', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 10:22:49', NULL),
(180, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:17:55', NULL),
(181, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:18:15', NULL),
(182, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:18:29', NULL),
(183, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:25:13', NULL),
(184, 1, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:25:20', NULL),
(185, 1, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 12:30:21', NULL),
(186, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 18:33:03', NULL),
(187, 9, 'Review Report', 'Started reviewing report #10.', 'Report', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-18 18:57:20', NULL),
(188, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-19 04:23:20', NULL),
(189, 9, 'Logout', 'Admin \"Amit Sharma\" logged out successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-19 04:39:29', NULL),
(190, 9, 'Login', 'Admin \"Amit Sharma\" logged in successfully.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-21 17:31:51', NULL),
(191, 9, 'Verify User', 'Approved verification request #121 for user \"Anuj Kushwaha\".', 'User', 122, NULL, NULL, '2026-11-21 17:32:02', NULL),
(192, 9, 'Manage Category', 'Updated category from \'Electronics\' to \'Electronics\'. Status set to Active. Invoice required.', 'Category', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.0.0 Safari/537.36', '2026-11-21 17:47:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_reset_tokens`
--

CREATE TABLE `admin_password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_password_reset_tokens`
--

INSERT INTO `admin_password_reset_tokens` (`token_id`, `admin_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'test_reset_token_admin_1_a83f92', '2026-09-07 21:00:00', NULL, '2026-09-19 21:38:51'),
(2, 2, 'test_reset_token_admin_2_b72c41', '2026-09-07 21:15:00', NULL, '2026-09-19 21:38:51'),
(4, 9, '3f0a4a7732ec8df5b6c6f2fdad312341bcb779d9757cb4bedd1b7f2da92d8331', '2026-11-15 00:16:30', '2026-11-14 23:18:49', '2026-11-14 17:46:30');

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--

CREATE TABLE `admin_profiles` (
  `admin_profile_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_profiles`
--

INSERT INTO `admin_profiles` (`admin_profile_id`, `admin_id`, `profile_picture`, `phone`, `designation`, `department`, `address`, `city`, `state`, `postal_code`, `bio`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'admin_1_5de58dc3fee37507.jpg', '9876500001', 'System Administrator', 'Information Technology', 'Admin Block, Campus Portal Office', 'Gwalior', 'Madhya Pradesh', '474001', 'Main administrator responsible for overall system management and administration.', '2026-09-19 21:38:51', '2026-09-20 09:42:27', NULL),
(2, 2, 'admin_1_5de58dc3fee37507.jpg', '9876500002', 'Assistant Administrator', 'Information Technology', 'Admin Block, Campus Portal Office', 'Gwalior', 'Madhya Pradesh', '474001', 'Sub administrator responsible for routine platform management and user support.', '2026-09-19 21:38:51', '2026-10-29 16:05:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_restrictions`
--

CREATE TABLE `admin_restrictions` (
  `restriction_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` enum('Suspended','Unsuspended','Banned','Unbanned') NOT NULL,
  `reason` text NOT NULL,
  `performed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_restrictions`
--

INSERT INTO `admin_restrictions` (`restriction_id`, `admin_id`, `action_type`, `reason`, `performed_by`, `created_at`) VALUES
(1, 2, 'Unsuspended', 'hggjhgj', 1, '2026-10-29 16:01:32'),
(2, 2, 'Suspended', 'jhgjhgk,hkhjkj', 1, '2026-11-18 12:29:40');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `invoice_required` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `invoice_required`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Books & Study Materials', 'Academic books, reference books, notes, and study materials.', 0, 'Active', '2026-09-19 21:38:51', '2026-09-20 11:12:25', NULL),
(2, 'Electronics', 'Laptops, calculators, headphones, accessories, and other electronic items.', 1, 'Active', '2026-09-19 21:38:51', '2026-11-21 17:47:35', NULL),
(3, 'Furniture', 'Tables, chairs, study desks, shelves, and other furniture items.', 0, 'Active', '2026-09-19 21:38:51', '2026-11-16 23:52:44', NULL),
(4, 'Clothing & Accessories', 'Clothing, bags, watches, shoes, and other personal accessories.', 0, 'Active', '2026-09-19 21:38:51', '2026-11-18 09:42:56', NULL),
(5, 'clothing', 'ghhgfhg', 0, 'Inactive', '2026-09-20 11:27:47', '2026-11-18 10:22:49', '2026-11-18 15:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `chat_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`chat_id`, `product_id`, `buyer_id`, `seller_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, 1, '2026-10-19 09:42:56', '2026-10-19 09:42:56', NULL),
(2, 8, 9, 8, '2026-11-08 09:42:56', '2026-11-08 09:42:56', NULL),
(3, 12, 3, 12, '2026-11-12 09:42:56', '2026-11-12 09:42:56', NULL),
(4, 4, 17, 4, '2026-11-16 09:42:56', '2026-11-16 09:42:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `verification_token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_security_activity`
--

CREATE TABLE `login_security_activity` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('Login','New Device','Remembered Login','Unusual Login','Logout All Devices','Token Revoked','Password Changed','Email Changed','Account Recovery') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_security_activity`
--

INSERT INTO `login_security_activity` (`activity_id`, `user_id`, `activity_type`, `title`, `description`, `created_at`) VALUES
(3, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 09:51:09'),
(4, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 09:54:15'),
(7, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 12:08:02'),
(8, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 12:17:37'),
(9, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 18:45:37'),
(10, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 19:51:11'),
(11, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 19:53:17'),
(12, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 19:54:58'),
(13, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 19:55:15'),
(14, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 19:56:11'),
(15, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 19:57:00'),
(16, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 19:57:46'),
(17, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 19:58:05'),
(18, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 19:58:46'),
(19, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-18 20:00:40'),
(20, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-18 20:00:46'),
(21, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 04:21:31'),
(22, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 04:23:04'),
(23, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 04:35:57'),
(24, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 04:37:14'),
(25, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 04:41:38'),
(26, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 04:47:35'),
(27, 122, 'Login', 'Successful login', 'User logged in with Remember Me enabled.', '2026-11-19 04:49:00'),
(28, 122, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 04:59:05'),
(29, 122, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 04:59:14'),
(30, 122, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 04:59:25'),
(31, 1, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 05:04:42'),
(32, 1, 'Token Revoked', 'User logged out', 'User logged out and the current remember token was revoked.', '2026-11-19 05:08:10'),
(33, 122, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-19 05:10:47'),
(34, 122, 'Login', 'Successful login', 'User logged in without Remember Me.', '2026-11-21 17:29:48');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text DEFAULT NULL,
  `message_type` enum('Text','Image') DEFAULT 'Text',
  `attachment_path` varchar(255) DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `chat_id`, `sender_id`, `message_text`, `message_type`, `attachment_path`, `sent_at`, `delivered_at`, `read_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, 'Hi, is the laptop still available?', 'Text', NULL, '2026-10-19 15:12:56', '2026-10-19 15:12:56', '2026-10-19 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(2, 1, 1, 'Yes it is. Battery gives around 3 to 4 hours.', 'Text', NULL, '2026-10-19 15:12:56', '2026-10-19 15:12:56', '2026-10-19 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(3, 1, 2, 'Can you do 26000? I can pick it up from the hostel.', 'Text', NULL, '2026-10-20 15:12:56', '2026-10-20 15:12:56', '2026-10-20 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(4, 1, 1, 'I can do 27000, final. Meet near the library?', 'Text', NULL, '2026-10-20 15:12:56', '2026-10-20 15:12:56', '2026-10-20 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(5, 2, 9, 'Is the keyboard hot-swappable with the switches included?', 'Text', NULL, '2026-11-08 15:12:56', '2026-11-08 15:12:56', '2026-11-08 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(6, 2, 8, 'Yes, comes with the stock switches and a keycap puller.', 'Text', NULL, '2026-11-08 15:12:56', '2026-11-08 15:12:56', '2026-11-08 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(7, 2, 9, 'Great, I will take it. Can we meet at the canteen tomorrow?', 'Text', NULL, '2026-11-09 15:12:56', '2026-11-09 15:12:56', '2026-11-09 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(8, 3, 3, 'Hello, is the chair height adjustable?', 'Text', NULL, '2026-11-12 15:12:56', '2026-11-12 15:12:56', '2026-11-12 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(9, 3, 12, 'Yes, gas lift works fine. Slight wear on the armrest.', 'Text', NULL, '2026-11-12 15:12:56', '2026-11-12 15:12:56', '2026-11-12 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(10, 3, 3, 'Okay, I will come to see it this evening.', 'Text', NULL, '2026-11-13 15:12:56', '2026-11-13 15:12:56', '2026-11-13 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(11, 4, 17, 'Hi, I am interested in the drawing kit.', 'Text', NULL, '2026-11-16 15:12:56', '2026-11-16 15:12:56', '2026-11-16 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(12, 4, 4, 'Sure, it is available. When can you meet?', 'Text', NULL, '2026-11-16 15:12:56', '2026-11-16 15:12:56', '2026-11-16 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(13, 4, 17, 'I can meet on campus tomorrow after lectures.', 'Text', NULL, '2026-11-17 15:12:56', '2026-11-17 15:12:56', '2026-11-17 15:12:56', '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `notification_type` enum('Verification','Message','Product','Transaction','Wishlist','Review','Report','Ban','Security','System') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` enum('User','Product','Transaction','Chat','Review','Report','Ban','Security','System') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `admin_id`, `notification_type`, `title`, `message`, `reference_type`, `reference_id`, `is_read`, `created_at`, `read_at`, `deleted_at`) VALUES
(1, 1, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 1, 1, '2026-08-21 09:42:56', NULL, NULL),
(2, 2, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 2, 1, '2026-08-25 09:42:56', NULL, NULL),
(3, 3, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 3, 1, '2026-08-29 09:42:56', NULL, NULL),
(4, 4, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 4, 1, '2026-09-02 09:42:56', NULL, NULL),
(5, 5, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 5, 1, '2026-09-06 09:42:56', NULL, NULL),
(6, 6, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 6, 1, '2026-09-10 09:42:56', NULL, NULL),
(7, 7, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 7, 1, '2026-09-14 09:42:56', NULL, NULL),
(8, 8, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 8, 1, '2026-09-18 09:42:56', NULL, NULL),
(9, 9, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 9, 1, '2026-09-22 09:42:56', NULL, NULL),
(10, 10, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 10, 1, '2026-09-26 09:42:56', NULL, NULL),
(11, 11, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 11, 1, '2026-09-30 09:42:56', NULL, NULL),
(12, 12, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 12, 1, '2026-10-04 09:42:56', NULL, NULL),
(13, 13, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 13, 1, '2026-10-08 09:42:56', NULL, NULL),
(14, 14, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 14, 1, '2026-10-12 09:42:56', NULL, NULL),
(15, 15, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 15, 1, '2026-10-16 09:42:56', NULL, NULL),
(16, 16, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 16, 1, '2026-10-20 09:42:56', NULL, NULL),
(17, 17, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 17, 1, '2026-10-24 09:42:56', NULL, NULL),
(18, 18, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 18, 1, '2026-10-28 09:42:56', NULL, NULL),
(19, 19, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 19, 1, '2026-11-01 09:42:56', NULL, NULL),
(20, 20, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 20, 1, '2026-11-05 09:42:56', NULL, NULL),
(21, 7, NULL, 'Ban', 'Account Banned', 'Your account has been permanently banned for Scam/Fraud.', 'Ban', 1, 0, '2026-10-30 09:42:56', NULL, NULL),
(22, 13, NULL, 'Ban', 'Account Banned', 'Your account has been permanently banned for Harassment.', 'Ban', 2, 0, '2026-11-01 09:42:56', NULL, NULL),
(23, 11, NULL, 'Ban', 'Account Suspended', 'Your account has been suspended for 14 days for Spam.', 'Ban', 3, 0, '2026-11-07 09:42:56', NULL, NULL),
(24, 18, NULL, 'Ban', 'Account Suspended', 'Your account has been suspended for 7 days for inappropriate behaviour.', 'Ban', 4, 0, '2026-11-16 09:42:56', NULL, NULL),
(25, 5, NULL, 'Ban', 'Warning Issued', 'You have received a warning for inappropriate behaviour.', 'Ban', 5, 1, '2026-10-25 09:42:56', NULL, NULL),
(26, 8, NULL, 'Product', 'Listing Assigned to You', 'A listing from a banned account (Power Bank 20000 mAh) has been assigned to your account.', 'Product', 7, 0, '2026-10-31 09:42:56', NULL, NULL),
(27, 12, NULL, 'Product', 'Listing Assigned to You', 'A listing from a banned account (Wooden Bookshelf with 4 Shelves) has been assigned to your account.', 'Product', 13, 0, '2026-11-02 09:42:56', NULL, NULL),
(28, NULL, 1, 'Report', 'New Report Submitted', 'A new report has been submitted and requires administrative review.', 'Report', 7, 0, '2026-11-15 09:42:56', NULL, NULL),
(29, NULL, 1, 'Report', 'New Report Submitted', 'A new report has been submitted and requires administrative review.', 'Report', 8, 0, '2026-11-16 09:42:56', NULL, NULL),
(30, NULL, 1, 'Report', 'New Report Submitted', 'A new report has been submitted and requires administrative review.', 'Report', 9, 0, '2026-11-17 09:42:56', NULL, NULL),
(31, NULL, 1, 'Report', 'New Report Submitted', 'A new report has been submitted and requires administrative review.', 'Report', 10, 0, '2026-11-17 09:42:56', NULL, NULL),
(32, NULL, 1, 'Report', 'New Report Submitted', 'A new report has been submitted and requires administrative review.', 'Report', 11, 0, '2026-11-16 09:42:56', NULL, NULL),
(33, 21, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 21, 1, '2026-08-17 10:03:39', NULL, NULL),
(34, 22, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 22, 1, '2026-09-05 10:03:39', NULL, NULL),
(35, 23, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 23, 1, '2026-08-06 10:03:39', NULL, NULL),
(36, 24, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 24, 1, '2026-10-05 10:03:39', NULL, NULL),
(37, 25, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 25, 1, '2026-09-12 10:03:39', NULL, NULL),
(38, 26, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 26, 1, '2026-08-26 10:03:39', NULL, NULL),
(39, 27, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 27, 1, '2026-11-01 10:03:39', NULL, NULL),
(40, 28, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 28, 1, '2026-09-08 10:03:39', NULL, NULL),
(41, 29, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 29, 1, '2026-09-07 10:03:39', NULL, NULL),
(42, 30, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 30, 1, '2026-08-28 10:03:39', NULL, NULL),
(43, 31, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 31, 1, '2026-10-02 10:03:39', NULL, NULL),
(44, 32, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 32, 1, '2026-09-11 10:03:39', NULL, NULL),
(45, 33, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 33, 1, '2026-10-03 10:03:39', NULL, NULL),
(46, 34, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 34, 1, '2026-08-20 10:03:39', NULL, NULL),
(47, 35, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 35, 1, '2026-09-30 10:03:39', NULL, NULL),
(48, 36, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 36, 1, '2026-09-27 10:03:39', NULL, NULL),
(49, 37, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 37, 1, '2026-10-20 10:03:39', NULL, NULL),
(50, 38, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 38, 1, '2026-11-03 10:03:39', NULL, NULL),
(51, 39, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 39, 1, '2026-09-02 10:03:39', NULL, NULL),
(52, 40, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 40, 1, '2026-08-02 10:03:39', NULL, NULL),
(53, 41, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 41, 1, '2026-10-21 10:03:39', NULL, NULL),
(54, 42, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 42, 1, '2026-08-07 10:03:39', NULL, NULL),
(55, 43, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 43, 1, '2026-10-11 10:03:39', NULL, NULL),
(56, 44, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 44, 1, '2026-09-06 10:03:39', NULL, NULL),
(57, 45, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 45, 1, '2026-09-07 10:03:39', NULL, NULL),
(58, 46, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 46, 1, '2026-09-24 10:03:39', NULL, NULL),
(59, 47, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 47, 1, '2026-08-24 10:03:39', NULL, NULL),
(60, 48, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 48, 1, '2026-10-30 10:03:39', NULL, NULL),
(61, 49, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 49, 1, '2026-08-27 10:03:39', NULL, NULL),
(62, 50, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 50, 1, '2026-10-16 10:03:39', NULL, NULL),
(63, 51, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 51, 1, '2026-11-09 10:03:39', NULL, NULL),
(64, 52, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 52, 1, '2026-09-14 10:03:39', NULL, NULL),
(65, 53, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 53, 1, '2026-10-13 10:03:39', NULL, NULL),
(66, 54, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 54, 1, '2026-10-17 10:03:39', NULL, NULL),
(67, 55, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 55, 1, '2026-09-19 10:03:39', NULL, NULL),
(68, 56, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 56, 1, '2026-09-08 10:03:39', NULL, NULL),
(69, 57, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 57, 1, '2026-09-06 10:03:39', NULL, NULL),
(70, 58, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 58, 1, '2026-10-27 10:03:39', NULL, NULL),
(71, 59, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 59, 1, '2026-09-08 10:03:39', NULL, NULL),
(72, 60, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 60, 1, '2026-10-16 10:03:39', NULL, NULL),
(73, 61, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 61, 1, '2026-08-03 10:03:39', NULL, NULL),
(74, 62, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 62, 1, '2026-10-09 10:03:39', NULL, NULL),
(75, 63, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 63, 1, '2026-08-05 10:03:39', NULL, NULL),
(76, 64, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 64, 1, '2026-08-13 10:03:39', NULL, NULL),
(77, 65, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 65, 1, '2026-09-11 10:03:39', NULL, NULL),
(78, 66, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 66, 1, '2026-10-26 10:03:39', NULL, NULL),
(79, 67, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 67, 1, '2026-09-02 10:03:39', NULL, NULL),
(80, 68, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 68, 1, '2026-08-18 10:03:39', NULL, NULL),
(81, 69, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 69, 1, '2026-07-28 10:03:39', NULL, NULL),
(82, 70, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 70, 1, '2026-10-02 10:03:39', NULL, NULL),
(83, 71, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 71, 1, '2026-09-27 10:03:39', NULL, NULL),
(84, 72, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 72, 1, '2026-08-21 10:03:39', NULL, NULL),
(85, 73, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 73, 1, '2026-09-23 10:03:39', NULL, NULL),
(86, 74, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 74, 1, '2026-09-05 10:03:39', NULL, NULL),
(87, 75, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 75, 1, '2026-08-26 10:03:39', NULL, NULL),
(88, 76, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 76, 1, '2026-08-12 10:03:39', NULL, NULL),
(89, 77, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 77, 1, '2026-09-04 10:03:39', NULL, NULL),
(90, 78, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 78, 1, '2026-10-19 10:03:39', NULL, NULL),
(91, 79, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 79, 1, '2026-10-24 10:03:39', NULL, NULL),
(92, 80, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 80, 1, '2026-08-27 10:03:39', NULL, NULL),
(93, 81, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 81, 1, '2026-09-03 10:03:39', NULL, NULL),
(94, 82, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 82, 1, '2026-08-14 10:03:39', NULL, NULL),
(95, 83, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 83, 1, '2026-10-24 10:03:39', NULL, NULL),
(96, 84, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 84, 1, '2026-08-11 10:03:39', NULL, NULL),
(97, 85, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 85, 1, '2026-10-25 10:03:39', NULL, NULL),
(98, 86, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 86, 1, '2026-10-27 10:03:39', NULL, NULL),
(99, 87, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 87, 1, '2026-08-02 10:03:39', NULL, NULL),
(100, 88, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 88, 1, '2026-07-31 10:03:39', NULL, NULL),
(101, 89, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 89, 1, '2026-08-02 10:03:39', NULL, NULL),
(102, 90, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 90, 1, '2026-08-20 10:03:39', NULL, NULL),
(103, 91, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 91, 1, '2026-08-31 10:03:39', NULL, NULL),
(104, 92, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 92, 1, '2026-11-05 10:03:39', NULL, NULL),
(105, 93, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 93, 1, '2026-08-02 10:03:39', NULL, NULL),
(106, 94, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 94, 1, '2026-08-19 10:03:39', NULL, NULL),
(107, 95, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 95, 1, '2026-11-08 10:03:39', NULL, NULL),
(108, 96, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 96, 1, '2026-10-08 10:03:39', NULL, NULL),
(109, 97, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 97, 1, '2026-08-03 10:03:39', NULL, NULL),
(110, 98, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 98, 1, '2026-10-05 10:03:39', NULL, NULL),
(111, 99, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 99, 1, '2026-07-31 10:03:39', NULL, NULL),
(112, 100, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 100, 1, '2026-10-21 10:03:39', NULL, NULL),
(113, 101, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 101, 1, '2026-10-26 10:03:39', NULL, NULL),
(114, 102, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 102, 1, '2026-10-09 10:03:39', NULL, NULL),
(115, 103, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 103, 1, '2026-09-04 10:03:39', NULL, NULL),
(116, 104, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 104, 1, '2026-08-16 10:03:39', NULL, NULL),
(117, 105, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 105, 1, '2026-09-28 10:03:39', NULL, NULL),
(118, 106, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 106, 1, '2026-10-27 10:03:39', NULL, NULL),
(119, 107, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 107, 1, '2026-09-15 10:03:39', NULL, NULL),
(120, 108, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 108, 1, '2026-10-22 10:03:39', NULL, NULL),
(121, 109, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 109, 1, '2026-10-20 10:03:39', NULL, NULL),
(122, 110, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 110, 1, '2026-08-16 10:03:39', NULL, NULL),
(123, 111, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 111, 1, '2026-11-06 10:03:39', NULL, NULL),
(124, 112, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 112, 1, '2026-08-08 10:03:39', NULL, NULL),
(125, 113, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 113, 1, '2026-10-20 10:03:39', NULL, NULL),
(126, 114, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 114, 1, '2026-08-17 10:03:39', NULL, NULL),
(127, 115, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 115, 1, '2026-10-24 10:03:39', NULL, NULL),
(128, 116, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 116, 1, '2026-09-21 10:03:39', NULL, NULL),
(129, 117, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 117, 1, '2026-08-22 10:03:39', NULL, NULL),
(130, 118, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 118, 1, '2026-08-25 10:03:39', NULL, NULL),
(131, 119, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 119, 1, '2026-09-11 10:03:39', NULL, NULL),
(132, 120, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. You can now list items for sale.', 'User', 120, 1, '2026-09-04 10:03:39', NULL, NULL),
(133, 33, NULL, 'Ban', 'Account Restricted', 'Your account has been permanently banned for Scam/Fraud.', 'Ban', 6, 0, '2026-10-19 10:03:39', NULL, NULL),
(134, 58, NULL, 'Ban', 'Account Restricted', 'Your account has been permanently banned for Harassment.', 'Ban', 7, 0, '2026-10-23 10:03:39', NULL, NULL),
(135, 84, NULL, 'Ban', 'Account Restricted', 'Your account has been permanently banned for Scam/Fraud.', 'Ban', 8, 0, '2026-10-27 10:03:39', NULL, NULL),
(136, 41, NULL, 'Ban', 'Account Restricted', 'Your account has been suspended for 10 days for Spam.', 'Ban', 9, 0, '2026-11-10 10:03:39', NULL, NULL),
(137, 67, NULL, 'Ban', 'Account Restricted', 'Your account has been suspended for 7 days for inappropriate behaviour.', 'Ban', 10, 0, '2026-11-14 10:03:39', NULL, NULL),
(138, 92, NULL, 'Ban', 'Account Restricted', 'Your account has been suspended for 14 days for Harassment.', 'Ban', 11, 0, '2026-11-08 10:03:39', NULL, NULL),
(139, 24, NULL, 'Product', 'Product Sold', 'Your Engineering Chemistry Notes and Textbook Bundle has been sold.', 'Product', 24, 1, '2026-10-08 10:03:39', NULL, NULL),
(140, 28, NULL, 'Product', 'Product Sold', 'Your Scientific Calculator Pink Edition, 417 Functions has been sold.', 'Product', 28, 1, '2026-09-11 10:03:39', NULL, NULL),
(141, 32, NULL, 'Product', 'Product Sold', 'Your Water-Resistant Backpack with USB Port, Red has been sold.', 'Product', 32, 1, '2026-09-14 10:03:39', NULL, NULL),
(142, 36, NULL, 'Product', 'Product Sold', 'Your Green Cotton Hoodie (Size S) has been sold.', 'Product', 36, 1, '2026-09-30 10:03:39', NULL, NULL),
(143, 40, NULL, 'Product', 'Product Sold', 'Your Casual Canvas Shoes UK Size 8, Red has been sold.', 'Product', 40, 1, '2026-08-05 10:03:39', NULL, NULL),
(144, 44, NULL, 'Product', 'Product Sold', 'Your Classic Leather Strap Watch, Brown has been sold.', 'Product', 44, 1, '2026-09-09 10:03:39', NULL, NULL),
(145, 48, NULL, 'Product', 'Product Sold', 'Your Varsity Jacket Maroon (Size S) has been sold.', 'Product', 48, 1, '2026-11-02 10:03:39', NULL, NULL),
(146, 52, NULL, 'Product', 'Product Sold', 'Your Gym Shaker Bottle 700 ml has been sold.', 'Product', 52, 1, '2026-09-17 10:03:39', NULL, NULL),
(147, 56, NULL, 'Product', 'Product Sold', 'Your Travel Adapter with Extension Board has been sold.', 'Product', 56, 1, '2026-09-11 10:03:39', NULL, NULL),
(148, 60, NULL, 'Product', 'Product Sold', 'Your Foldable On-Ear Headphones, Pink has been sold.', 'Product', 60, 1, '2026-10-19 10:03:39', NULL, NULL),
(149, 64, NULL, 'Product', 'Product Sold', 'Your Wired USB Mouse, Black has been sold.', 'Product', 64, 1, '2026-08-16 10:03:39', NULL, NULL),
(150, 68, NULL, 'Product', 'Product Sold', 'Your RGB Gaming Keyboard with Blue Switches has been sold.', 'Product', 68, 1, '2026-08-21 10:03:39', NULL, NULL),
(151, 72, NULL, 'Product', 'Product Sold', 'Your Power Bank 15000 mAh with Type-C Fast Charge has been sold.', 'Product', 72, 1, '2026-08-24 10:03:39', NULL, NULL),
(152, 76, NULL, 'Product', 'Product Sold', 'Your 7-inch Wi-Fi Tablet for Study Use has been sold.', 'Product', 76, 1, '2026-08-15 10:03:39', NULL, NULL),
(153, 80, NULL, 'Product', 'Product Sold', 'Your Desktop Speakers 2.0 USB has been sold.', 'Product', 80, 1, '2026-08-30 10:03:39', NULL, NULL),
(154, 88, NULL, 'Product', 'Product Sold', 'Your Wi-Fi Range Extender has been sold.', 'Product', 88, 1, '2026-08-03 10:03:39', NULL, NULL),
(155, 96, NULL, 'Product', 'Product Sold', 'Your 24-inch Curved Monitor has been sold.', 'Product', 96, 1, '2026-10-11 10:03:39', NULL, NULL),
(156, 100, NULL, 'Product', 'Product Sold', 'Your Revolving Chair, Black has been sold.', 'Product', 100, 1, '2026-10-24 10:03:39', NULL, NULL),
(157, 104, NULL, 'Product', 'Product Sold', 'Your Compact Writing Desk, Walnut Finish has been sold.', 'Product', 104, 1, '2026-08-19 10:03:39', NULL, NULL),
(158, 108, NULL, 'Product', 'Product Sold', 'Your Reading Lamp with Flexible Neck has been sold.', 'Product', 108, 1, '2026-10-25 10:03:39', NULL, NULL),
(159, 112, NULL, 'Product', 'Product Sold', 'Your Desktop Organizer Shelf has been sold.', 'Product', 112, 1, '2026-08-11 10:03:39', NULL, NULL),
(160, 116, NULL, 'Product', 'Product Sold', 'Your Wall-Mount Fan 400 mm has been sold.', 'Product', 116, 1, '2026-09-24 10:03:39', NULL, NULL),
(161, 120, NULL, 'Product', 'Product Sold', 'Your Mini Rice Cooker 1.2 L has been sold.', 'Product', 120, 1, '2026-09-07 10:03:39', NULL, NULL),
(162, NULL, 1, 'Report', 'New product report', 'A product \"Sipper Bottle 900 ml with Straw, Green\" was reported.', 'Report', 18, 0, '2026-11-18 18:51:06', NULL, NULL),
(163, NULL, 9, 'Report', 'New product report', 'A product \"Sipper Bottle 900 ml with Straw, Green\" was reported.', 'Report', 18, 1, '2026-11-18 18:51:06', '2026-11-19 00:26:51', NULL),
(164, NULL, 1, 'Report', 'New product report', 'A product \"Cube Storage Shelf, 6 Compartments\" was reported.', 'Report', 19, 0, '2026-11-18 18:56:08', NULL, NULL),
(165, NULL, 9, 'Report', 'New product report', 'A product \"Cube Storage Shelf, 6 Compartments\" was reported.', 'Report', 19, 1, '2026-11-18 18:56:08', '2026-11-19 00:26:38', NULL),
(166, NULL, 1, 'Verification', 'New verification request', 'Anuj Kushwaha submitted a profile for verification.', 'User', 122, 0, '2026-11-21 17:31:15', NULL, NULL),
(167, NULL, 9, 'Verification', 'New verification request', 'Anuj Kushwaha submitted a profile for verification.', 'User', 122, 0, '2026-11-21 17:31:15', NULL, NULL),
(168, 122, NULL, 'Verification', 'Verification submitted', 'Your profile was submitted. An admin will review it soon.', 'User', 122, 1, '2026-11-21 17:31:15', '2026-11-21 23:01:32', NULL),
(169, 122, NULL, 'Verification', 'Verification Approved', 'Your identity verification has been approved. Your CampusXchange account is now active and you can access the full marketplace.', NULL, NULL, 1, '2026-11-21 17:32:02', '2026-11-21 23:38:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_type` varchar(50) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `product_condition` enum('New','Like New','Good','Used') DEFAULT NULL,
  `status` enum('Draft','Active','Sold','Removed','Inactive') DEFAULT 'Draft',
  `removed_by_admin_id` int(11) DEFAULT NULL,
  `removal_reason` varchar(255) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `invoice_document` varchar(255) DEFAULT NULL,
  `product_box_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `category_id`, `product_type`, `title`, `description`, `price`, `product_condition`, `status`, `removed_by_admin_id`, `removal_reason`, `removed_at`, `created_at`, `updated_at`, `deleted_at`, `invoice_document`, `product_box_image`) VALUES
(1, 1, 2, NULL, 'Laptop 14-inch Core i5, 8GB RAM, 512GB SSD', 'Well-maintained 14-inch laptop with an Intel Core i5 processor, 8 GB RAM and a 512 GB SSD. It has been used for three years for coding and assignments and runs Windows 11 smoothly. Battery backup is around 3 to 4 hours. Light scratches on the lid, screen and keyboard are in perfect condition. Original charger and a padded laptop sleeve are included. Selling because I am moving to a new machine for my final-year project.', 28000.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-22 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(2, 2, 1, NULL, 'Higher Engineering Mathematics (Revised Edition) - Dr. R. K. Sharma', 'Core textbook for first-year and second-year engineering mathematics covering calculus, differential equations, Laplace transforms, Fourier series, vector calculus and complex analysis. All chapters are intact, a few important formulas are highlighted in pencil and there is no torn page. The cover has minor corner wear. An ideal book for exam preparation and daily practice.', 350.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-26 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(3, 3, 1, NULL, 'Computer Science Book Bundle: Data Structures in C, Algorithms, Operating Systems', 'Set of three semester books: Data Structures in C, Algorithms: Design & Analysis and Operating System Concepts. They were bought last year and used only for one semester; there is no writing inside and the spines are not broken. Very useful for placement preparation and viva. Selling the full bundle together as I have completed these subjects.', 500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-30 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(4, 4, 1, NULL, 'Engineering Drawing Kit: Set Squares, Protractor, Compass, 30 cm Ruler', 'Complete drawing kit for the first-year Engineering Graphics course: 45-degree and 30-60-degree set squares, a 180-degree protractor, a metal compass with pencil adapter and a 30 cm scale. All instruments are in working condition and the markings are clearly visible. Compass joint is smooth and the ruler is straight. Perfect for new students joining this session.', 400.00, 'Used', 'Active', NULL, NULL, NULL, '2026-09-03 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(5, 5, 2, NULL, 'Scientific Calculator CX-991 Classwiz (552 functions)', 'Non-programmable scientific calculator accepted in university examinations. It supports equation solving, matrices, vectors, statistics, base-n calculations and natural textbook display. Works on solar power with battery backup and the display is crisp with no dead segments. Comes with the hard slide cover. Used for only two semesters and always kept in the cover.', 650.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-07 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(6, 6, 2, NULL, 'Wireless Bluetooth Over-Ear Headphones with Mic', 'Foldable over-ear Bluetooth headphones with deep bass, soft ear cushions and a built-in microphone for online lectures and calls. Battery lasts around 20 hours on a single charge, and a 3.5 mm cable is included for wired use. Pairing is quick and stable up to 10 metres. Cushions are clean and there are no cracks. Charging cable and carry pouch included.', 1200.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-11 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(7, 8, 2, NULL, 'Power Bank 20000 mAh, 22.5W Fast Charging, Dual Output', '20000 mAh power bank with USB-C input and output, one USB-A port and a four-LED battery indicator. Charges a typical smartphone about four times and supports 22.5W fast charging. Perfect for long days at college and travel. Works exactly as it should and holds charge well. Minor scuffs on the back. Charging cable included.', 700.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-15 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(8, 8, 2, NULL, 'Mechanical Keyboard, 75% Layout, Hot-Swappable, Blue Switches', 'Compact 75 percent mechanical keyboard with hot-swappable blue switches, PBT-style keycaps with orange accent keys and a detachable USB-C cable. Very satisfying to type on and great for coding. All keys register properly and the keycaps are clean without any shine. Original keycap puller and a spare switch set are included. Selling because I shifted to a quieter keyboard for the hostel.', 1800.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-19 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(9, 9, 2, NULL, 'Arduino Uno R3 Starter Kit with Breadboard, Sensors and LEDs', 'Unused starter kit made for embedded-systems labs and mini projects. It contains an Arduino Uno R3 compatible board, USB cable, half-size breadboard, jumper wires, LEDs, resistors, an ultrasonic sensor, a 16x2 LCD display and a few push buttons. All parts are sealed and tested. A great way to start learning electronics. I received a duplicate kit during a workshop, hence selling this one.', 900.00, 'New', 'Active', NULL, NULL, NULL, '2026-09-23 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(10, 10, 2, NULL, '24-inch Full HD IPS Monitor with HDMI and VGA', '24-inch Full HD (1920 x 1080) IPS monitor with HDMI and VGA inputs, tilt-adjustable stand and thin bezels. Colours are accurate and bright, there are no dead pixels and no screen burn. Used in my office as a second display for two years. Power cable and HDMI cable are included. Collection from the CSE department office on working days.', 4500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-27 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(11, 11, 3, NULL, 'Wooden Study Table with 3 Drawers and Side Cabinet', 'Sturdy wooden study table with a large work surface, three drawers on the left and a side cabinet on the right. The surface is clean and the drawers slide smoothly. Suitable for a hostel room or a rented flat, easily fits a laptop and monitor. The legs have been stabilised with screws and there is no wobble. Buyer must arrange pickup; I can help with moving it across campus.', 1800.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-01 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(12, 12, 3, NULL, 'Ergonomic Mesh Office Chair with Adjustable Height and Armrests', 'Comfortable ergonomic chair with a breathable mesh back, lumbar support, adjustable height, fixed armrests and smooth rolling wheels. Great for long study sessions. The gas lift works properly and the wheels rotate without noise. Seat cushion shows slight wear but is still firm. Easy to carry in a car or auto.', 1500.00, 'Used', 'Active', NULL, NULL, NULL, '2026-10-05 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(13, 12, 3, NULL, 'Wooden Bookshelf with 4 Shelves', 'Four-tier wooden bookshelf with a plain back panel, ideal for textbooks, files and decorative items. Each shelf is strong enough for heavy books and the finish is clean with only minor scratches on the sides. Dimensions are about 70 cm wide, 120 cm tall and 30 cm deep. Selling as I am moving to a smaller room. Pickup from the main gate area only.', 1200.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-09 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(14, 14, 3, NULL, 'Adjustable LED Study Lamp with Dimmer and USB Charging', 'Flexible LED desk lamp with three brightness levels, a warm and cool light mode and a USB port for charging a phone. The arm can be moved and locked at any angle and the base is heavy enough not to tip over. It gives glare-free light and is perfect for night study. Used for a few months only. Power adapter included.', 350.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-13 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(15, 15, 3, NULL, 'Table Fan 400 mm, 3 Speed Settings', 'Powerful 400 mm table fan with three speed settings and a sturdy metal guard. It oscillates smoothly and moves a lot of air, making it perfect for summer in hostel rooms. The blades are clean and balanced and make little noise. Cleaned and serviced recently. Selling because I am shifting into an air-cooled room.', 600.00, 'Used', 'Active', NULL, NULL, NULL, '2026-10-17 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(16, 16, 4, NULL, 'Water-Resistant College Backpack, 30L with Laptop Compartment', 'Spacious 30-litre backpack with a padded laptop compartment that fits up to a 15.6-inch laptop, a large front pocket, two side bottle pockets and a front organiser. It is water resistant and the zips run smoothly. Shoulder straps are well padded and comfortable. Small stitching mark at one corner but fully functional. A great all-day college bag.', 600.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-21 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(17, 17, 4, NULL, 'White Lab Coat (Size M), Full Sleeves', 'Standard white lab coat in medium size with full sleeves, two front pockets and a chest pocket. Worn for only two semesters in the pharmacy lab and always washed and ironed. There are no stains or tears and all buttons are in place. Chest around 38 inches, length around 36 inches. Required for practical classes.', 250.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-25 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(18, 18, 4, NULL, 'Maroon Cotton Fleece Hoodie (Size L)', 'Warm maroon fleece hoodie with a kangaroo pocket, drawstring hood and ribbed cuffs. Fabric is soft and has not pilled. Perfect for Gwalior winters and early morning classes. Size L, fits a chest of about 40 to 42 inches. Washed and ready to use. Selling because it is slightly big for me.', 450.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-29 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(19, 19, 4, NULL, 'Running Shoes UK Size 8, Blue and Neon Green', 'Lightweight running shoes in UK size 8 with breathable mesh, cushioned midsole and a grippy outsole. Used for about five months of morning runs on campus. The sole still has plenty of tread and the cushioning is good. Cleaned and deodorised. Not suitable for anyone with a different size, so please check before messaging.', 800.00, 'Used', 'Active', NULL, NULL, NULL, '2026-11-02 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(20, 20, 4, NULL, 'Classic Analog Quartz Wrist Watch with Leather Strap', 'Elegant analog quartz watch with a white dial, steel case and a genuine brown leather strap. Keeps accurate time and the battery was replaced recently. Water-resistant for splashes, not for swimming. No scratches on the glass. Selling as I received a similar watch as a gift. Original box available on request.', 1100.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-11-06 09:42:56', '2026-11-18 09:42:56', NULL, NULL, NULL),
(21, 21, 1, NULL, 'Engineering Physics Textbook (Revised Edition)', 'Standard first-year textbook covering optics, lasers and semiconductors. All pages intact with light pencil notes on a few chapters.', 280.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-17 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(22, 22, 1, NULL, 'Data Structures Using C Book Set (2 Books)', 'Two books for the data structures course, barely used. Clean pages, no highlighting, ideal for lab and exam preparation.', 420.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(23, 23, 1, NULL, 'Digital Electronics Textbook with Solved Examples', 'Clear explanations with plenty of solved problems. Cover has minor wear, inside is neat and complete.', 300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-06 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(24, 24, 1, NULL, 'Engineering Chemistry Notes and Textbook Bundle', 'Textbook plus handwritten chapter-wise notes from a topper. Some pages are folded but everything is readable.', 250.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-10-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(25, 25, 2, NULL, 'Scientific Calculator fx-991EX ClassWiz', 'Allowed in most university exams. Works perfectly, fresh batteries and the slide-on cover are included.', 700.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-12 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(26, 26, 2, NULL, 'Casio FX-82MS Scientific Calculator', 'Reliable calculator with 240 functions. Light scratches on the body, display and keys are perfect.', 350.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-26 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(27, 27, 2, NULL, 'Scientific Calculator 570 Functions with Cover', 'Solar and battery powered. Used for two semesters, comes with a hard protective case.', 500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-11-01 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(28, 28, 2, NULL, 'Scientific Calculator Pink Edition, 417 Functions', 'Fully working and handy for hostel study sessions. Selling because I bought a newer model.', 450.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(29, 29, 4, NULL, 'Laptop Backpack 35L Black with Rain Cover', 'Spacious bag with padded laptop compartment, multiple pockets and a rain cover. Zips work smoothly.', 700.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-07 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(30, 30, 4, NULL, 'College Sling Bag, Navy Blue', 'Compact sling bag for daily essentials, used only a few times. No stains or tears.', 300.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-28 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(31, 31, 4, NULL, 'Trekking Backpack 45L Grey', 'Sturdy backpack with strong stitching and a chest strap. Great for trips and weekend treks.', 900.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(32, 32, 4, NULL, 'Water-Resistant Backpack with USB Port, Red', 'Has an external USB charging port and anti-theft back pocket. Looks almost new.', 650.00, 'Like New', 'Sold', NULL, NULL, NULL, '2026-09-11 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(33, 22, 4, NULL, 'Black Zip Hoodie (Size M)', 'Warm fleece-lined hoodie with a full zip and two pockets. Washed and ready to wear.', 400.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-03 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(34, 34, 4, NULL, 'Grey Round-Neck Sweatshirt (Size L)', 'Soft cotton blend sweatshirt, no fading or pilling. Perfect for winter mornings.', 300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-20 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(35, 35, 4, NULL, 'Navy Blue Hoodie with Front Pocket (Size XL)', 'Worn only a couple of times. Thick material that keeps you warm in hostel winters.', 450.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-30 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(36, 36, 4, NULL, 'Green Cotton Hoodie (Size S)', 'Comfortable everyday hoodie with a small mark on the sleeve that is barely visible.', 350.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(37, 37, 4, NULL, 'Running Shoes UK Size 9, Black and Orange', 'Lightweight running shoes with good grip. Used for morning jogs on campus, sole still in good shape.', 900.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-20 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(38, 38, 4, NULL, 'White Sneakers UK Size 7', 'Clean white sneakers worn only a few times. Comes with a spare set of laces.', 700.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-11-03 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(39, 39, 4, NULL, 'Sports Shoes UK Size 10, Blue', 'Comfortable cushioned sports shoes. Light wear on the sole, no tears.', 800.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(40, 40, 4, NULL, 'Casual Canvas Shoes UK Size 8, Red', 'Everyday canvas shoes in decent condition. Washed and sun-dried.', 450.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-08-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(41, 41, 4, NULL, 'Digital Sports Watch, Black', 'Water-resistant digital watch with stopwatch, alarm and backlight. Battery recently changed.', 600.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-21 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(42, 42, 4, NULL, 'Analog Wrist Watch with Steel Strap', 'Elegant formal watch with a scratch-free dial. Strap length can be adjusted.', 900.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-07 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(43, 43, 4, NULL, 'Smart Fitness Band with Heart Rate Monitor', 'Tracks steps, sleep and heart rate. Charger included, battery lasts about a week.', 700.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-11 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(44, 44, 4, NULL, 'Classic Leather Strap Watch, Brown', 'Simple analog watch with a genuine leather strap. Keeps good time.', 500.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-06 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(45, 45, 4, NULL, 'Denim Jacket (Size M)', 'Classic blue denim jacket in a regular fit. No tears and all buttons are intact.', 650.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-07 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(46, 46, 4, NULL, 'Black Windcheater Jacket (Size L)', 'Light windproof jacket with zip pockets. Good for rainy days and night rides.', 500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-24 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(47, 47, 4, NULL, 'Puffer Jacket for Winter, Blue (Size M)', 'Very warm quilted jacket, worn for a single winter. Zip works perfectly.', 1100.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-24 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(48, 48, 4, NULL, 'Varsity Jacket Maroon (Size S)', 'Stylish varsity jacket with contrast sleeves. Small fading near the cuffs.', 800.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-10-30 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(49, 49, 4, NULL, 'Stainless Steel Water Bottle 1 L', 'Leak-proof steel bottle with a sturdy cap. A small dent at the bottom, works perfectly.', 250.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(50, 50, 4, NULL, 'Insulated Flask 750 ml, Keeps Cold 12 Hours', 'Double-wall insulated flask for hot or cold drinks. No smell, cleaned regularly.', 400.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-16 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(51, 51, 4, NULL, 'Sipper Bottle 900 ml with Straw, Green', 'BPA-free sipper bottle with a flip straw. Great for lectures and the gym.', 150.00, 'Good', 'Active', NULL, NULL, NULL, '2026-11-09 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(52, 52, 4, NULL, 'Gym Shaker Bottle 700 ml', 'Shaker with a mixing ball and measurement marks. Cleaned and ready to use.', 120.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-14 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(53, 53, 2, NULL, '6-Socket Extension Board with Surge Protector', 'Six sockets with individual switch and 3 m cable. Ideal for hostel rooms and study desks.', 350.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-13 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(54, 54, 2, NULL, '4-Socket Extension Board, 3 Metre Cable', 'Sturdy extension board with overload protection. No burn marks, works perfectly.', 250.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-17 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(55, 55, 2, NULL, 'Multi-Plug Extension with USB Ports', 'Three sockets and two USB charging ports. Barely used.', 400.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-19 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(56, 56, 2, NULL, 'Travel Adapter with Extension Board', 'Handy adapter with multiple sockets. Light scratches but fully working.', 300.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(57, 57, 2, NULL, 'Wired Over-Ear Headphones with Mic', 'Comfortable ear cushions and clear sound. Works well for classes and calls.', 450.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-06 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(58, 26, 2, NULL, 'Wireless Bluetooth Headphones, 30 Hour Battery', 'Long battery life with deep bass and a built-in mic. Charging cable included.', 1300.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(59, 59, 2, NULL, 'Gaming Headset with Noise-Cancelling Mic', 'Adjustable headband, soft padding and a flexible mic. Works with PC and mobile.', 1100.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(60, 60, 2, NULL, 'Foldable On-Ear Headphones, Pink', 'Lightweight foldable headphones with good sound. Padding shows slight wear.', 600.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-10-16 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(61, 61, 2, NULL, 'Wireless Optical Mouse 2.4 GHz', 'Smooth tracking and quiet clicks. USB receiver and fresh battery included.', 250.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-03 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(62, 62, 2, NULL, 'Gaming Mouse with 6 Buttons and RGB', 'Adjustable DPI and colourful lighting. Barely used, no double-click issues.', 500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-09 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(63, 63, 2, NULL, 'Bluetooth Silent Click Mouse', 'Works with laptops and tablets. Silent buttons are great for the library.', 400.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(64, 64, 2, NULL, 'Wired USB Mouse, Black', 'Basic wired mouse that still works perfectly, a little worn on the surface.', 150.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-08-13 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(65, 65, 2, NULL, 'Wireless Keyboard and Mouse Combo', 'Slim combo with one USB receiver. Works up to 10 m. Batteries included.', 700.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-11 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(66, 66, 2, NULL, 'Full-Size USB Keyboard with Number Pad', 'Comfortable keys and a spill-resistant design. Everything works as it should.', 300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-26 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(67, 67, 2, NULL, 'Compact Bluetooth Keyboard for Tablet', 'Pairs with phones, tablets and laptops. Rechargeable with a long battery life.', 600.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(68, 68, 2, NULL, 'RGB Gaming Keyboard with Blue Switches', 'Clicky mechanical switches with multi-colour backlight. Detachable cable.', 1100.00, 'Like New', 'Sold', NULL, NULL, NULL, '2026-08-18 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(69, 69, 2, NULL, 'Power Bank 10000 mAh Fast Charging', 'Charges a phone about 2.5 times. Both USB ports and Type-C work fine.', 500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-07-28 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(70, 70, 2, NULL, 'Power Bank 20000 mAh Dual USB', 'High-capacity power bank for long days on campus. Holds charge very well.', 800.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(71, 71, 2, NULL, 'Slim Power Bank 5000 mAh', 'Pocket-sized and light. Used rarely, with an LED battery indicator.', 300.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(72, 72, 2, NULL, 'Power Bank 15000 mAh with Type-C Fast Charge', 'Supports fast charging in and out. Minor scratches on the casing.', 650.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-08-21 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(73, 73, 2, NULL, '10-inch Android Tablet 4GB RAM 64GB', 'Great for reading PDFs and online classes. Charger and a flip cover are included.', 6500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-23 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(74, 74, 2, NULL, '8-inch Tablet with Stylus', 'Perfect for taking handwritten notes. Screen has a guard from day one.', 4500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(75, 75, 2, NULL, 'Drawing Tablet for Notes and Design', 'Pressure-sensitive pen, works with Windows and Mac. Driver CD and cable are included.', 3500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-26 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(76, 76, 2, NULL, '7-inch Wi-Fi Tablet for Study Use', 'Simple tablet for e-books and videos. Battery lasts around 5 hours.', 2500.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-08-12 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(77, 77, 2, NULL, 'Portable Bluetooth Speaker, Waterproof', 'Loud and clear sound, battery lasts nearly 8 hours. Charging cable included.', 800.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-04 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(78, 78, 2, NULL, 'Mini Bluetooth Speaker with FM Radio', 'Pocket speaker with memory card slot and radio. Sound is crisp at medium volume.', 450.00, 'Used', 'Active', NULL, NULL, NULL, '2026-10-19 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(79, 79, 2, NULL, 'Party Speaker 20W with Mic Input', 'Powerful bass with an AUX and mic input. Great for hostel get-togethers.', 1500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-24 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(80, 80, 2, NULL, 'Desktop Speakers 2.0 USB', 'Compact desktop pair powered through USB. Clean stereo sound for movies and music.', 600.00, 'Good', 'Sold', NULL, NULL, NULL, '2026-08-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(81, 81, 2, NULL, 'Laptop 15.6 inch Core i3, 8GB RAM, 256GB SSD', 'Smooth for coding and assignments. Battery gives about 3 hours. Charger included.', 22000.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-03 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(82, 82, 2, NULL, 'Laptop 14 inch Ryzen 5, 16GB RAM, 512GB SSD', 'Fast machine for programming and light editing. Bought last year, with warranty remaining.', 32000.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-14 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(83, 83, 2, NULL, 'Refurbished Business Laptop Core i5, 8GB RAM', 'Sturdy business laptop with a good keyboard. New SSD installed recently.', 18000.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-24 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(84, 29, 2, NULL, 'Lightweight Laptop 13.3 inch, 8GB RAM, 256GB SSD', 'Easy to carry to class. Occasional light use, no dead pixels.', 20000.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-11 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(85, 85, 2, NULL, 'Wi-Fi Router 300 Mbps with 3 Antennas', 'Stable connection for a hostel room. Power adapter and LAN cable included.', 500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-25 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(86, 86, 2, NULL, 'Dual-Band Wi-Fi Router AC1200', 'Fast dual-band router with easy mobile app setup. Used for only a few months.', 900.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(87, 87, 2, NULL, 'Portable 4G Wi-Fi Hotspot Device', 'Works with any SIM and connects up to 10 devices. Battery lasts around 6 hours.', 1200.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(88, 88, 2, NULL, 'Wi-Fi Range Extender', 'Extends your signal to far corners. Simple plug-and-play setup.', 400.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-07-31 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(89, 89, 1, NULL, 'Engineering Drawing Board with T-Square', 'Smooth board with a sliding T-square. Used for one year of drawing practicals.', 600.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(90, 90, 1, NULL, 'Clipboard Set with A3 Sheets', 'Sturdy clipboard with 20 blank A3 sheets. Handy for sketching.', 250.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-20 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(91, 91, 1, NULL, 'Drafter Mini Set with Scale', 'Mini drafter with a scale for engineering graphics. Moves smoothly.', 350.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-31 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(92, 92, 1, NULL, 'Sketching Board A3 Size', 'Light wooden board with edge clips. Small scratches, perfectly usable.', 300.00, 'Used', 'Active', NULL, NULL, NULL, '2026-11-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(93, 93, 2, NULL, '22-inch Full HD LED Monitor', 'Bright, sharp screen with HDMI and VGA ports. No dead pixels or lines.', 3500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-02 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(94, 94, 2, NULL, '27-inch IPS Monitor 75 Hz', 'Wide viewing angles and thin bezels. Perfect for coding and design work.', 7500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-19 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(95, 95, 2, NULL, '19-inch Monitor for Basic Use', 'Reliable monitor for assignments and browsing. Stand included.', 2000.00, 'Used', 'Active', NULL, NULL, NULL, '2026-11-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(96, 96, 2, NULL, '24-inch Curved Monitor', 'Immersive curved display, good for movies and gaming. VGA and HDMI cables included.', 6000.00, 'Good', 'Sold', NULL, NULL, NULL, '2026-10-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(97, 97, 3, NULL, 'Study Chair with Cushioned Seat', 'Comfortable chair with a firm back. Wheels roll smoothly.', 1200.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-03 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(98, 98, 3, NULL, 'Gaming Chair with Lumbar Support', 'Reclining chair with adjustable armrests. Used for a few months.', 3500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-05 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(99, 99, 3, NULL, 'Plastic Armchair for Hostel Use', 'Simple lightweight chair, easy to clean. Slight colour fading.', 500.00, 'Used', 'Active', NULL, NULL, NULL, '2026-07-31 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(100, 100, 3, NULL, 'Revolving Chair, Black', 'Height-adjustable revolving chair with a padded seat. Gas lift works fine.', 1400.00, 'Good', 'Sold', NULL, NULL, NULL, '2026-10-21 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(101, 101, 3, NULL, 'Foldable Laptop Table, Adjustable Height', 'Lightweight table for bed or floor use. Folds flat for storage.', 600.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-26 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(102, 102, 3, NULL, 'Computer Desk 4 ft with Keyboard Tray', 'Solid desk with space for monitor and CPU. Assembly guide included.', 2200.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-09 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(103, 103, 3, NULL, 'Study Table with Drawer', 'Sturdy table with one lockable drawer. Minor surface scratches.', 1800.00, 'Used', 'Active', NULL, NULL, NULL, '2026-09-04 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(104, 104, 3, NULL, 'Compact Writing Desk, Walnut Finish', 'Neat desk for small rooms. Used for one semester.', 1500.00, 'Like New', 'Sold', NULL, NULL, NULL, '2026-08-16 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(105, 105, 3, NULL, 'LED Desk Lamp with Touch Dimmer', 'Three brightness levels and a flexible neck. Easy on the eyes during late-night study.', 400.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-09-28 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(106, 106, 3, NULL, 'Rechargeable Study Lamp with Clamp', 'Clamps to any desk edge. Battery lasts about 6 hours per charge.', 450.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-27 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(107, 107, 3, NULL, 'Foldable Table Lamp, Warm Light', 'Compact lamp that folds for easy carrying. Warm white light.', 300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-15 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(108, 108, 3, NULL, 'Reading Lamp with Flexible Neck', 'Bendable neck and a stable base. Bulb replaced recently.', 350.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-10-22 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(109, 109, 3, NULL, 'Wall-Mounted Floating Shelf Set of 3', 'Three shelves with screws and brackets. Great for books and decor.', 500.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-20 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(110, 110, 3, NULL, '5-Tier Book Rack, Metal Frame', 'Strong rack that holds plenty of books. Easy to disassemble for moving.', 1300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-16 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(111, 111, 3, NULL, 'Cube Storage Shelf, 6 Compartments', 'Neat cubes for books, bags and boxes. Minor edge scratches.', 1700.00, 'Good', 'Active', NULL, NULL, NULL, '2026-11-06 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(112, 112, 3, NULL, 'Desktop Organizer Shelf', 'Keeps stationery and files in order. Sturdy and compact.', 400.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-08-08 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(113, 113, 3, NULL, 'Table Fan 300 mm, Oscillating', 'Quiet motor and three speed settings. Oscillation works smoothly.', 550.00, 'Good', 'Active', NULL, NULL, NULL, '2026-10-20 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(114, 114, 3, NULL, 'Pedestal Fan 400 mm', 'Height-adjustable stand with strong airflow. Cleaned and serviced.', 1300.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-17 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(115, 115, 3, NULL, 'USB Mini Desk Fan', 'Small fan powered via USB. Quiet and great for study desks.', 200.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-10-24 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(116, 116, 3, NULL, 'Wall-Mount Fan 400 mm', 'High-speed fan with a sturdy wall bracket. Works perfectly.', 1000.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-21 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(117, 117, 3, NULL, 'Electric Kettle 1.5 L, Stainless Steel', 'Boils water in minutes with auto shut-off. Clean interior, no limescale.', 550.00, 'Good', 'Active', NULL, NULL, NULL, '2026-08-22 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(118, 118, 3, NULL, 'Mini Electric Kettle 1 L', 'Compact kettle for hostel use. Used for a few months.', 400.00, 'Like New', 'Active', NULL, NULL, NULL, '2026-08-25 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(119, 119, 3, NULL, 'Induction Cooktop 2000 W', 'Touch panel with preset cooking modes. Works with any induction-friendly pot.', 1500.00, 'Good', 'Active', NULL, NULL, NULL, '2026-09-11 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL),
(120, 120, 3, NULL, 'Mini Rice Cooker 1.2 L', 'Cooks rice and veggies. Non-stick pot has minor wear.', 800.00, 'Used', 'Sold', NULL, NULL, NULL, '2026-09-04 10:03:39', '2026-11-18 10:03:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `is_primary`, `created_at`, `deleted_at`) VALUES
(1, 1, 'cx_p01_1.jpg', 1, '2026-08-22 09:42:56', NULL),
(2, 1, 'cx_p01_2.jpg', 0, '2026-08-22 09:42:56', NULL),
(3, 1, 'cx_p01_3.jpg', 0, '2026-08-22 09:42:56', NULL),
(4, 2, 'cx_p02_1.jpg', 1, '2026-08-26 09:42:56', NULL),
(5, 2, 'cx_p02_2.jpg', 0, '2026-08-26 09:42:56', NULL),
(6, 2, 'cx_p02_3.jpg', 0, '2026-08-26 09:42:56', NULL),
(7, 3, 'cx_p03_1.jpg', 1, '2026-08-30 09:42:56', NULL),
(8, 3, 'cx_p03_2.jpg', 0, '2026-08-30 09:42:56', NULL),
(9, 3, 'cx_p03_3.jpg', 0, '2026-08-30 09:42:56', NULL),
(10, 4, 'cx_p04_1.jpg', 1, '2026-09-03 09:42:56', NULL),
(11, 4, 'cx_p04_2.jpg', 0, '2026-09-03 09:42:56', NULL),
(12, 4, 'cx_p04_3.jpg', 0, '2026-09-03 09:42:56', NULL),
(13, 5, 'cx_p05_1.jpg', 1, '2026-09-07 09:42:56', NULL),
(14, 5, 'cx_p05_2.jpg', 0, '2026-09-07 09:42:56', NULL),
(15, 5, 'cx_p05_3.jpg', 0, '2026-09-07 09:42:56', NULL),
(16, 6, 'cx_p06_1.jpg', 1, '2026-09-11 09:42:56', NULL),
(17, 6, 'cx_p06_2.jpg', 0, '2026-09-11 09:42:56', NULL),
(18, 6, 'cx_p06_3.jpg', 0, '2026-09-11 09:42:56', NULL),
(19, 7, 'cx_p07_1.jpg', 1, '2026-09-15 09:42:56', NULL),
(20, 7, 'cx_p07_2.jpg', 0, '2026-09-15 09:42:56', NULL),
(21, 7, 'cx_p07_3.jpg', 0, '2026-09-15 09:42:56', NULL),
(22, 8, 'cx_p08_1.jpg', 1, '2026-09-19 09:42:56', NULL),
(23, 8, 'cx_p08_2.jpg', 0, '2026-09-19 09:42:56', NULL),
(24, 8, 'cx_p08_3.jpg', 0, '2026-09-19 09:42:56', NULL),
(25, 9, 'cx_p09_1.jpg', 1, '2026-09-23 09:42:56', NULL),
(26, 9, 'cx_p09_2.jpg', 0, '2026-09-23 09:42:56', NULL),
(27, 9, 'cx_p09_3.jpg', 0, '2026-09-23 09:42:56', NULL),
(28, 10, 'cx_p10_1.jpg', 1, '2026-09-27 09:42:56', NULL),
(29, 10, 'cx_p10_2.jpg', 0, '2026-09-27 09:42:56', NULL),
(30, 10, 'cx_p10_3.jpg', 0, '2026-09-27 09:42:56', NULL),
(31, 11, 'cx_p11_1.jpg', 1, '2026-10-01 09:42:56', NULL),
(32, 11, 'cx_p11_2.jpg', 0, '2026-10-01 09:42:56', NULL),
(33, 11, 'cx_p11_3.jpg', 0, '2026-10-01 09:42:56', NULL),
(34, 12, 'cx_p12_1.jpg', 1, '2026-10-05 09:42:56', NULL),
(35, 12, 'cx_p12_2.jpg', 0, '2026-10-05 09:42:56', NULL),
(36, 12, 'cx_p12_3.jpg', 0, '2026-10-05 09:42:56', NULL),
(37, 13, 'cx_p13_1.jpg', 1, '2026-10-09 09:42:56', NULL),
(38, 13, 'cx_p13_2.jpg', 0, '2026-10-09 09:42:56', NULL),
(39, 13, 'cx_p13_3.jpg', 0, '2026-10-09 09:42:56', NULL),
(40, 14, 'cx_p14_1.jpg', 1, '2026-10-13 09:42:56', NULL),
(41, 14, 'cx_p14_2.jpg', 0, '2026-10-13 09:42:56', NULL),
(42, 14, 'cx_p14_3.jpg', 0, '2026-10-13 09:42:56', NULL),
(43, 15, 'cx_p15_1.jpg', 1, '2026-10-17 09:42:56', NULL),
(44, 15, 'cx_p15_2.jpg', 0, '2026-10-17 09:42:56', NULL),
(45, 15, 'cx_p15_3.jpg', 0, '2026-10-17 09:42:56', NULL),
(46, 16, 'cx_p16_1.jpg', 1, '2026-10-21 09:42:56', NULL),
(47, 16, 'cx_p16_2.jpg', 0, '2026-10-21 09:42:56', NULL),
(48, 16, 'cx_p16_3.jpg', 0, '2026-10-21 09:42:56', NULL),
(49, 17, 'cx_p17_1.jpg', 1, '2026-10-25 09:42:56', NULL),
(50, 17, 'cx_p17_2.jpg', 0, '2026-10-25 09:42:56', NULL),
(51, 17, 'cx_p17_3.jpg', 0, '2026-10-25 09:42:56', NULL),
(52, 18, 'cx_p18_1.jpg', 1, '2026-10-29 09:42:56', NULL),
(53, 18, 'cx_p18_2.jpg', 0, '2026-10-29 09:42:56', NULL),
(54, 18, 'cx_p18_3.jpg', 0, '2026-10-29 09:42:56', NULL),
(55, 19, 'cx_p19_1.jpg', 1, '2026-11-02 09:42:56', NULL),
(56, 19, 'cx_p19_2.jpg', 0, '2026-11-02 09:42:56', NULL),
(57, 19, 'cx_p19_3.jpg', 0, '2026-11-02 09:42:56', NULL),
(58, 20, 'cx_p20_1.jpg', 1, '2026-11-06 09:42:56', NULL),
(59, 20, 'cx_p20_2.jpg', 0, '2026-11-06 09:42:56', NULL),
(60, 20, 'cx_p20_3.jpg', 0, '2026-11-06 09:42:56', NULL),
(61, 21, 'cx_p021_1.jpg', 1, '2026-08-17 10:03:39', NULL),
(62, 21, 'cx_p021_2.jpg', 0, '2026-08-17 10:03:39', NULL),
(63, 21, 'cx_p021_3.jpg', 0, '2026-08-17 10:03:39', NULL),
(64, 22, 'cx_p022_1.jpg', 1, '2026-09-05 10:03:39', NULL),
(65, 22, 'cx_p022_2.jpg', 0, '2026-09-05 10:03:39', NULL),
(66, 22, 'cx_p022_3.jpg', 0, '2026-09-05 10:03:39', NULL),
(67, 23, 'cx_p023_1.jpg', 1, '2026-08-06 10:03:39', NULL),
(68, 23, 'cx_p023_2.jpg', 0, '2026-08-06 10:03:39', NULL),
(69, 23, 'cx_p023_3.jpg', 0, '2026-08-06 10:03:39', NULL),
(70, 24, 'cx_p024_1.jpg', 1, '2026-10-05 10:03:39', NULL),
(71, 24, 'cx_p024_2.jpg', 0, '2026-10-05 10:03:39', NULL),
(72, 24, 'cx_p024_3.jpg', 0, '2026-10-05 10:03:39', NULL),
(73, 25, 'cx_p025_1.jpg', 1, '2026-09-12 10:03:39', NULL),
(74, 25, 'cx_p025_2.jpg', 0, '2026-09-12 10:03:39', NULL),
(75, 25, 'cx_p025_3.jpg', 0, '2026-09-12 10:03:39', NULL),
(76, 26, 'cx_p026_1.jpg', 1, '2026-08-26 10:03:39', NULL),
(77, 26, 'cx_p026_2.jpg', 0, '2026-08-26 10:03:39', NULL),
(78, 26, 'cx_p026_3.jpg', 0, '2026-08-26 10:03:39', NULL),
(79, 27, 'cx_p027_1.jpg', 1, '2026-11-01 10:03:39', NULL),
(80, 27, 'cx_p027_2.jpg', 0, '2026-11-01 10:03:39', NULL),
(81, 27, 'cx_p027_3.jpg', 0, '2026-11-01 10:03:39', NULL),
(82, 28, 'cx_p028_1.jpg', 1, '2026-09-08 10:03:39', NULL),
(83, 28, 'cx_p028_2.jpg', 0, '2026-09-08 10:03:39', NULL),
(84, 28, 'cx_p028_3.jpg', 0, '2026-09-08 10:03:39', NULL),
(85, 29, 'cx_p029_1.jpg', 1, '2026-09-07 10:03:39', NULL),
(86, 29, 'cx_p029_2.jpg', 0, '2026-09-07 10:03:39', NULL),
(87, 29, 'cx_p029_3.jpg', 0, '2026-09-07 10:03:39', NULL),
(88, 30, 'cx_p030_1.jpg', 1, '2026-08-28 10:03:39', NULL),
(89, 30, 'cx_p030_2.jpg', 0, '2026-08-28 10:03:39', NULL),
(90, 30, 'cx_p030_3.jpg', 0, '2026-08-28 10:03:39', NULL),
(91, 31, 'cx_p031_1.jpg', 1, '2026-10-02 10:03:39', NULL),
(92, 31, 'cx_p031_2.jpg', 0, '2026-10-02 10:03:39', NULL),
(93, 31, 'cx_p031_3.jpg', 0, '2026-10-02 10:03:39', NULL),
(94, 32, 'cx_p032_1.jpg', 1, '2026-09-11 10:03:39', NULL),
(95, 32, 'cx_p032_2.jpg', 0, '2026-09-11 10:03:39', NULL),
(96, 32, 'cx_p032_3.jpg', 0, '2026-09-11 10:03:39', NULL),
(97, 33, 'cx_p033_1.jpg', 1, '2026-10-03 10:03:39', NULL),
(98, 33, 'cx_p033_2.jpg', 0, '2026-10-03 10:03:39', NULL),
(99, 33, 'cx_p033_3.jpg', 0, '2026-10-03 10:03:39', NULL),
(100, 34, 'cx_p034_1.jpg', 1, '2026-08-20 10:03:39', NULL),
(101, 34, 'cx_p034_2.jpg', 0, '2026-08-20 10:03:39', NULL),
(102, 34, 'cx_p034_3.jpg', 0, '2026-08-20 10:03:39', NULL),
(103, 35, 'cx_p035_1.jpg', 1, '2026-09-30 10:03:39', NULL),
(104, 35, 'cx_p035_2.jpg', 0, '2026-09-30 10:03:39', NULL),
(105, 35, 'cx_p035_3.jpg', 0, '2026-09-30 10:03:39', NULL),
(106, 36, 'cx_p036_1.jpg', 1, '2026-09-27 10:03:39', NULL),
(107, 36, 'cx_p036_2.jpg', 0, '2026-09-27 10:03:39', NULL),
(108, 36, 'cx_p036_3.jpg', 0, '2026-09-27 10:03:39', NULL),
(109, 37, 'cx_p037_1.jpg', 1, '2026-10-20 10:03:39', NULL),
(110, 37, 'cx_p037_2.jpg', 0, '2026-10-20 10:03:39', NULL),
(111, 37, 'cx_p037_3.jpg', 0, '2026-10-20 10:03:39', NULL),
(112, 38, 'cx_p038_1.jpg', 1, '2026-11-03 10:03:39', NULL),
(113, 38, 'cx_p038_2.jpg', 0, '2026-11-03 10:03:39', NULL),
(114, 38, 'cx_p038_3.jpg', 0, '2026-11-03 10:03:39', NULL),
(115, 39, 'cx_p039_1.jpg', 1, '2026-09-02 10:03:39', NULL),
(116, 39, 'cx_p039_2.jpg', 0, '2026-09-02 10:03:39', NULL),
(117, 39, 'cx_p039_3.jpg', 0, '2026-09-02 10:03:39', NULL),
(118, 40, 'cx_p040_1.jpg', 1, '2026-08-02 10:03:39', NULL),
(119, 40, 'cx_p040_2.jpg', 0, '2026-08-02 10:03:39', NULL),
(120, 40, 'cx_p040_3.jpg', 0, '2026-08-02 10:03:39', NULL),
(121, 41, 'cx_p041_1.jpg', 1, '2026-10-21 10:03:39', NULL),
(122, 41, 'cx_p041_2.jpg', 0, '2026-10-21 10:03:39', NULL),
(123, 41, 'cx_p041_3.jpg', 0, '2026-10-21 10:03:39', NULL),
(124, 42, 'cx_p042_1.jpg', 1, '2026-08-07 10:03:39', NULL),
(125, 42, 'cx_p042_2.jpg', 0, '2026-08-07 10:03:39', NULL),
(126, 42, 'cx_p042_3.jpg', 0, '2026-08-07 10:03:39', NULL),
(127, 43, 'cx_p043_1.jpg', 1, '2026-10-11 10:03:39', NULL),
(128, 43, 'cx_p043_2.jpg', 0, '2026-10-11 10:03:39', NULL),
(129, 43, 'cx_p043_3.jpg', 0, '2026-10-11 10:03:39', NULL),
(130, 44, 'cx_p044_1.jpg', 1, '2026-09-06 10:03:39', NULL),
(131, 44, 'cx_p044_2.jpg', 0, '2026-09-06 10:03:39', NULL),
(132, 44, 'cx_p044_3.jpg', 0, '2026-09-06 10:03:39', NULL),
(133, 45, 'cx_p045_1.jpg', 1, '2026-09-07 10:03:39', NULL),
(134, 45, 'cx_p045_2.jpg', 0, '2026-09-07 10:03:39', NULL),
(135, 45, 'cx_p045_3.jpg', 0, '2026-09-07 10:03:39', NULL),
(136, 46, 'cx_p046_1.jpg', 1, '2026-09-24 10:03:39', NULL),
(137, 46, 'cx_p046_2.jpg', 0, '2026-09-24 10:03:39', NULL),
(138, 46, 'cx_p046_3.jpg', 0, '2026-09-24 10:03:39', NULL),
(139, 47, 'cx_p047_1.jpg', 1, '2026-08-24 10:03:39', NULL),
(140, 47, 'cx_p047_2.jpg', 0, '2026-08-24 10:03:39', NULL),
(141, 47, 'cx_p047_3.jpg', 0, '2026-08-24 10:03:39', NULL),
(142, 48, 'cx_p048_1.jpg', 1, '2026-10-30 10:03:39', NULL),
(143, 48, 'cx_p048_2.jpg', 0, '2026-10-30 10:03:39', NULL),
(144, 48, 'cx_p048_3.jpg', 0, '2026-10-30 10:03:39', NULL),
(145, 49, 'cx_p049_1.jpg', 1, '2026-08-27 10:03:39', NULL),
(146, 49, 'cx_p049_2.jpg', 0, '2026-08-27 10:03:39', NULL),
(147, 49, 'cx_p049_3.jpg', 0, '2026-08-27 10:03:39', NULL),
(148, 50, 'cx_p050_1.jpg', 1, '2026-10-16 10:03:39', NULL),
(149, 50, 'cx_p050_2.jpg', 0, '2026-10-16 10:03:39', NULL),
(150, 50, 'cx_p050_3.jpg', 0, '2026-10-16 10:03:39', NULL),
(151, 51, 'cx_p051_1.jpg', 1, '2026-11-09 10:03:39', NULL),
(152, 51, 'cx_p051_2.jpg', 0, '2026-11-09 10:03:39', NULL),
(153, 51, 'cx_p051_3.jpg', 0, '2026-11-09 10:03:39', NULL),
(154, 52, 'cx_p052_1.jpg', 1, '2026-09-14 10:03:39', NULL),
(155, 52, 'cx_p052_2.jpg', 0, '2026-09-14 10:03:39', NULL),
(156, 52, 'cx_p052_3.jpg', 0, '2026-09-14 10:03:39', NULL),
(157, 53, 'cx_p053_1.jpg', 1, '2026-10-13 10:03:39', NULL),
(158, 53, 'cx_p053_2.jpg', 0, '2026-10-13 10:03:39', NULL),
(159, 53, 'cx_p053_3.jpg', 0, '2026-10-13 10:03:39', NULL),
(160, 54, 'cx_p054_1.jpg', 1, '2026-10-17 10:03:39', NULL),
(161, 54, 'cx_p054_2.jpg', 0, '2026-10-17 10:03:39', NULL),
(162, 54, 'cx_p054_3.jpg', 0, '2026-10-17 10:03:39', NULL),
(163, 55, 'cx_p055_1.jpg', 1, '2026-09-19 10:03:39', NULL),
(164, 55, 'cx_p055_2.jpg', 0, '2026-09-19 10:03:39', NULL),
(165, 55, 'cx_p055_3.jpg', 0, '2026-09-19 10:03:39', NULL),
(166, 56, 'cx_p056_1.jpg', 1, '2026-09-08 10:03:39', NULL),
(167, 56, 'cx_p056_2.jpg', 0, '2026-09-08 10:03:39', NULL),
(168, 56, 'cx_p056_3.jpg', 0, '2026-09-08 10:03:39', NULL),
(169, 57, 'cx_p057_1.jpg', 1, '2026-09-06 10:03:39', NULL),
(170, 57, 'cx_p057_2.jpg', 0, '2026-09-06 10:03:39', NULL),
(171, 57, 'cx_p057_3.jpg', 0, '2026-09-06 10:03:39', NULL),
(172, 58, 'cx_p058_1.jpg', 1, '2026-10-27 10:03:39', NULL),
(173, 58, 'cx_p058_2.jpg', 0, '2026-10-27 10:03:39', NULL),
(174, 58, 'cx_p058_3.jpg', 0, '2026-10-27 10:03:39', NULL),
(175, 59, 'cx_p059_1.jpg', 1, '2026-09-08 10:03:39', NULL),
(176, 59, 'cx_p059_2.jpg', 0, '2026-09-08 10:03:39', NULL),
(177, 59, 'cx_p059_3.jpg', 0, '2026-09-08 10:03:39', NULL),
(178, 60, 'cx_p060_1.jpg', 1, '2026-10-16 10:03:39', NULL),
(179, 60, 'cx_p060_2.jpg', 0, '2026-10-16 10:03:39', NULL),
(180, 60, 'cx_p060_3.jpg', 0, '2026-10-16 10:03:39', NULL),
(181, 61, 'cx_p061_1.jpg', 1, '2026-08-03 10:03:39', NULL),
(182, 61, 'cx_p061_2.jpg', 0, '2026-08-03 10:03:39', NULL),
(183, 61, 'cx_p061_3.jpg', 0, '2026-08-03 10:03:39', NULL),
(184, 62, 'cx_p062_1.jpg', 1, '2026-10-09 10:03:39', NULL),
(185, 62, 'cx_p062_2.jpg', 0, '2026-10-09 10:03:39', NULL),
(186, 62, 'cx_p062_3.jpg', 0, '2026-10-09 10:03:39', NULL),
(187, 63, 'cx_p063_1.jpg', 1, '2026-08-05 10:03:39', NULL),
(188, 63, 'cx_p063_2.jpg', 0, '2026-08-05 10:03:39', NULL),
(189, 63, 'cx_p063_3.jpg', 0, '2026-08-05 10:03:39', NULL),
(190, 64, 'cx_p064_1.jpg', 1, '2026-08-13 10:03:39', NULL),
(191, 64, 'cx_p064_2.jpg', 0, '2026-08-13 10:03:39', NULL),
(192, 64, 'cx_p064_3.jpg', 0, '2026-08-13 10:03:39', NULL),
(193, 65, 'cx_p065_1.jpg', 1, '2026-09-11 10:03:39', NULL),
(194, 65, 'cx_p065_2.jpg', 0, '2026-09-11 10:03:39', NULL),
(195, 65, 'cx_p065_3.jpg', 0, '2026-09-11 10:03:39', NULL),
(196, 66, 'cx_p066_1.jpg', 1, '2026-10-26 10:03:39', NULL),
(197, 66, 'cx_p066_2.jpg', 0, '2026-10-26 10:03:39', NULL),
(198, 66, 'cx_p066_3.jpg', 0, '2026-10-26 10:03:39', NULL),
(199, 67, 'cx_p067_1.jpg', 1, '2026-09-02 10:03:39', NULL),
(200, 67, 'cx_p067_2.jpg', 0, '2026-09-02 10:03:39', NULL),
(201, 67, 'cx_p067_3.jpg', 0, '2026-09-02 10:03:39', NULL),
(202, 68, 'cx_p068_1.jpg', 1, '2026-08-18 10:03:39', NULL),
(203, 68, 'cx_p068_2.jpg', 0, '2026-08-18 10:03:39', NULL),
(204, 68, 'cx_p068_3.jpg', 0, '2026-08-18 10:03:39', NULL),
(205, 69, 'cx_p069_1.jpg', 1, '2026-07-28 10:03:39', NULL),
(206, 69, 'cx_p069_2.jpg', 0, '2026-07-28 10:03:39', NULL),
(207, 69, 'cx_p069_3.jpg', 0, '2026-07-28 10:03:39', NULL),
(208, 70, 'cx_p070_1.jpg', 1, '2026-10-02 10:03:39', NULL),
(209, 70, 'cx_p070_2.jpg', 0, '2026-10-02 10:03:39', NULL),
(210, 70, 'cx_p070_3.jpg', 0, '2026-10-02 10:03:39', NULL),
(211, 71, 'cx_p071_1.jpg', 1, '2026-09-27 10:03:39', NULL),
(212, 71, 'cx_p071_2.jpg', 0, '2026-09-27 10:03:39', NULL),
(213, 71, 'cx_p071_3.jpg', 0, '2026-09-27 10:03:39', NULL),
(214, 72, 'cx_p072_1.jpg', 1, '2026-08-21 10:03:39', NULL),
(215, 72, 'cx_p072_2.jpg', 0, '2026-08-21 10:03:39', NULL),
(216, 72, 'cx_p072_3.jpg', 0, '2026-08-21 10:03:39', NULL),
(217, 73, 'cx_p073_1.jpg', 1, '2026-09-23 10:03:39', NULL),
(218, 73, 'cx_p073_2.jpg', 0, '2026-09-23 10:03:39', NULL),
(219, 73, 'cx_p073_3.jpg', 0, '2026-09-23 10:03:39', NULL),
(220, 74, 'cx_p074_1.jpg', 1, '2026-09-05 10:03:39', NULL),
(221, 74, 'cx_p074_2.jpg', 0, '2026-09-05 10:03:39', NULL),
(222, 74, 'cx_p074_3.jpg', 0, '2026-09-05 10:03:39', NULL),
(223, 75, 'cx_p075_1.jpg', 1, '2026-08-26 10:03:39', NULL),
(224, 75, 'cx_p075_2.jpg', 0, '2026-08-26 10:03:39', NULL),
(225, 75, 'cx_p075_3.jpg', 0, '2026-08-26 10:03:39', NULL),
(226, 76, 'cx_p076_1.jpg', 1, '2026-08-12 10:03:39', NULL),
(227, 76, 'cx_p076_2.jpg', 0, '2026-08-12 10:03:39', NULL),
(228, 76, 'cx_p076_3.jpg', 0, '2026-08-12 10:03:39', NULL),
(229, 77, 'cx_p077_1.jpg', 1, '2026-09-04 10:03:39', NULL),
(230, 77, 'cx_p077_2.jpg', 0, '2026-09-04 10:03:39', NULL),
(231, 77, 'cx_p077_3.jpg', 0, '2026-09-04 10:03:39', NULL),
(232, 78, 'cx_p078_1.jpg', 1, '2026-10-19 10:03:39', NULL),
(233, 78, 'cx_p078_2.jpg', 0, '2026-10-19 10:03:39', NULL),
(234, 78, 'cx_p078_3.jpg', 0, '2026-10-19 10:03:39', NULL),
(235, 79, 'cx_p079_1.jpg', 1, '2026-10-24 10:03:39', NULL),
(236, 79, 'cx_p079_2.jpg', 0, '2026-10-24 10:03:39', NULL),
(237, 79, 'cx_p079_3.jpg', 0, '2026-10-24 10:03:39', NULL),
(238, 80, 'cx_p080_1.jpg', 1, '2026-08-27 10:03:39', NULL),
(239, 80, 'cx_p080_2.jpg', 0, '2026-08-27 10:03:39', NULL),
(240, 80, 'cx_p080_3.jpg', 0, '2026-08-27 10:03:39', NULL),
(241, 81, 'cx_p081_1.jpg', 1, '2026-09-03 10:03:39', NULL),
(242, 81, 'cx_p081_2.jpg', 0, '2026-09-03 10:03:39', NULL),
(243, 81, 'cx_p081_3.jpg', 0, '2026-09-03 10:03:39', NULL),
(244, 82, 'cx_p082_1.jpg', 1, '2026-08-14 10:03:39', NULL),
(245, 82, 'cx_p082_2.jpg', 0, '2026-08-14 10:03:39', NULL),
(246, 82, 'cx_p082_3.jpg', 0, '2026-08-14 10:03:39', NULL),
(247, 83, 'cx_p083_1.jpg', 1, '2026-10-24 10:03:39', NULL),
(248, 83, 'cx_p083_2.jpg', 0, '2026-10-24 10:03:39', NULL),
(249, 83, 'cx_p083_3.jpg', 0, '2026-10-24 10:03:39', NULL),
(250, 84, 'cx_p084_1.jpg', 1, '2026-08-11 10:03:39', NULL),
(251, 84, 'cx_p084_2.jpg', 0, '2026-08-11 10:03:39', NULL),
(252, 84, 'cx_p084_3.jpg', 0, '2026-08-11 10:03:39', NULL),
(253, 85, 'cx_p085_1.jpg', 1, '2026-10-25 10:03:39', NULL),
(254, 85, 'cx_p085_2.jpg', 0, '2026-10-25 10:03:39', NULL),
(255, 85, 'cx_p085_3.jpg', 0, '2026-10-25 10:03:39', NULL),
(256, 86, 'cx_p086_1.jpg', 1, '2026-10-27 10:03:39', NULL),
(257, 86, 'cx_p086_2.jpg', 0, '2026-10-27 10:03:39', NULL),
(258, 86, 'cx_p086_3.jpg', 0, '2026-10-27 10:03:39', NULL),
(259, 87, 'cx_p087_1.jpg', 1, '2026-08-02 10:03:39', NULL),
(260, 87, 'cx_p087_2.jpg', 0, '2026-08-02 10:03:39', NULL),
(261, 87, 'cx_p087_3.jpg', 0, '2026-08-02 10:03:39', NULL),
(262, 88, 'cx_p088_1.jpg', 1, '2026-07-31 10:03:39', NULL),
(263, 88, 'cx_p088_2.jpg', 0, '2026-07-31 10:03:39', NULL),
(264, 88, 'cx_p088_3.jpg', 0, '2026-07-31 10:03:39', NULL),
(265, 89, 'cx_p089_1.jpg', 1, '2026-08-02 10:03:39', NULL),
(266, 89, 'cx_p089_2.jpg', 0, '2026-08-02 10:03:39', NULL),
(267, 89, 'cx_p089_3.jpg', 0, '2026-08-02 10:03:39', NULL),
(268, 90, 'cx_p090_1.jpg', 1, '2026-08-20 10:03:39', NULL),
(269, 90, 'cx_p090_2.jpg', 0, '2026-08-20 10:03:39', NULL),
(270, 90, 'cx_p090_3.jpg', 0, '2026-08-20 10:03:39', NULL),
(271, 91, 'cx_p091_1.jpg', 1, '2026-08-31 10:03:39', NULL),
(272, 91, 'cx_p091_2.jpg', 0, '2026-08-31 10:03:39', NULL),
(273, 91, 'cx_p091_3.jpg', 0, '2026-08-31 10:03:39', NULL),
(274, 92, 'cx_p092_1.jpg', 1, '2026-11-05 10:03:39', NULL),
(275, 92, 'cx_p092_2.jpg', 0, '2026-11-05 10:03:39', NULL),
(276, 92, 'cx_p092_3.jpg', 0, '2026-11-05 10:03:39', NULL),
(277, 93, 'cx_p093_1.jpg', 1, '2026-08-02 10:03:39', NULL),
(278, 93, 'cx_p093_2.jpg', 0, '2026-08-02 10:03:39', NULL),
(279, 93, 'cx_p093_3.jpg', 0, '2026-08-02 10:03:39', NULL),
(280, 94, 'cx_p094_1.jpg', 1, '2026-08-19 10:03:39', NULL),
(281, 94, 'cx_p094_2.jpg', 0, '2026-08-19 10:03:39', NULL),
(282, 94, 'cx_p094_3.jpg', 0, '2026-08-19 10:03:39', NULL),
(283, 95, 'cx_p095_1.jpg', 1, '2026-11-08 10:03:39', NULL),
(284, 95, 'cx_p095_2.jpg', 0, '2026-11-08 10:03:39', NULL),
(285, 95, 'cx_p095_3.jpg', 0, '2026-11-08 10:03:39', NULL),
(286, 96, 'cx_p096_1.jpg', 1, '2026-10-08 10:03:39', NULL),
(287, 96, 'cx_p096_2.jpg', 0, '2026-10-08 10:03:39', NULL),
(288, 96, 'cx_p096_3.jpg', 0, '2026-10-08 10:03:39', NULL),
(289, 97, 'cx_p097_1.jpg', 1, '2026-08-03 10:03:39', NULL),
(290, 97, 'cx_p097_2.jpg', 0, '2026-08-03 10:03:39', NULL),
(291, 97, 'cx_p097_3.jpg', 0, '2026-08-03 10:03:39', NULL),
(292, 98, 'cx_p098_1.jpg', 1, '2026-10-05 10:03:39', NULL),
(293, 98, 'cx_p098_2.jpg', 0, '2026-10-05 10:03:39', NULL),
(294, 98, 'cx_p098_3.jpg', 0, '2026-10-05 10:03:39', NULL),
(295, 99, 'cx_p099_1.jpg', 1, '2026-07-31 10:03:39', NULL),
(296, 99, 'cx_p099_2.jpg', 0, '2026-07-31 10:03:39', NULL),
(297, 99, 'cx_p099_3.jpg', 0, '2026-07-31 10:03:39', NULL),
(298, 100, 'cx_p100_1.jpg', 1, '2026-10-21 10:03:39', NULL),
(299, 100, 'cx_p100_2.jpg', 0, '2026-10-21 10:03:39', NULL),
(300, 100, 'cx_p100_3.jpg', 0, '2026-10-21 10:03:39', NULL),
(301, 101, 'cx_p101_1.jpg', 1, '2026-10-26 10:03:39', NULL),
(302, 101, 'cx_p101_2.jpg', 0, '2026-10-26 10:03:39', NULL),
(303, 101, 'cx_p101_3.jpg', 0, '2026-10-26 10:03:39', NULL),
(304, 102, 'cx_p102_1.jpg', 1, '2026-10-09 10:03:39', NULL),
(305, 102, 'cx_p102_2.jpg', 0, '2026-10-09 10:03:39', NULL),
(306, 102, 'cx_p102_3.jpg', 0, '2026-10-09 10:03:39', NULL),
(307, 103, 'cx_p103_1.jpg', 1, '2026-09-04 10:03:39', NULL),
(308, 103, 'cx_p103_2.jpg', 0, '2026-09-04 10:03:39', NULL),
(309, 103, 'cx_p103_3.jpg', 0, '2026-09-04 10:03:39', NULL),
(310, 104, 'cx_p104_1.jpg', 1, '2026-08-16 10:03:39', NULL),
(311, 104, 'cx_p104_2.jpg', 0, '2026-08-16 10:03:39', NULL),
(312, 104, 'cx_p104_3.jpg', 0, '2026-08-16 10:03:39', NULL),
(313, 105, 'cx_p105_1.jpg', 1, '2026-09-28 10:03:39', NULL),
(314, 105, 'cx_p105_2.jpg', 0, '2026-09-28 10:03:39', NULL),
(315, 105, 'cx_p105_3.jpg', 0, '2026-09-28 10:03:39', NULL),
(316, 106, 'cx_p106_1.jpg', 1, '2026-10-27 10:03:39', NULL),
(317, 106, 'cx_p106_2.jpg', 0, '2026-10-27 10:03:39', NULL),
(318, 106, 'cx_p106_3.jpg', 0, '2026-10-27 10:03:39', NULL),
(319, 107, 'cx_p107_1.jpg', 1, '2026-09-15 10:03:39', NULL),
(320, 107, 'cx_p107_2.jpg', 0, '2026-09-15 10:03:39', NULL),
(321, 107, 'cx_p107_3.jpg', 0, '2026-09-15 10:03:39', NULL),
(322, 108, 'cx_p108_1.jpg', 1, '2026-10-22 10:03:39', NULL),
(323, 108, 'cx_p108_2.jpg', 0, '2026-10-22 10:03:39', NULL),
(324, 108, 'cx_p108_3.jpg', 0, '2026-10-22 10:03:39', NULL),
(325, 109, 'cx_p109_1.jpg', 1, '2026-10-20 10:03:39', NULL),
(326, 109, 'cx_p109_2.jpg', 0, '2026-10-20 10:03:39', NULL),
(327, 109, 'cx_p109_3.jpg', 0, '2026-10-20 10:03:39', NULL),
(328, 110, 'cx_p110_1.jpg', 1, '2026-08-16 10:03:39', NULL),
(329, 110, 'cx_p110_2.jpg', 0, '2026-08-16 10:03:39', NULL),
(330, 110, 'cx_p110_3.jpg', 0, '2026-08-16 10:03:39', NULL),
(331, 111, 'cx_p111_1.jpg', 1, '2026-11-06 10:03:39', NULL),
(332, 111, 'cx_p111_2.jpg', 0, '2026-11-06 10:03:39', NULL),
(333, 111, 'cx_p111_3.jpg', 0, '2026-11-06 10:03:39', NULL),
(334, 112, 'cx_p112_1.jpg', 1, '2026-08-08 10:03:39', NULL),
(335, 112, 'cx_p112_2.jpg', 0, '2026-08-08 10:03:39', NULL),
(336, 112, 'cx_p112_3.jpg', 0, '2026-08-08 10:03:39', NULL),
(337, 113, 'cx_p113_1.jpg', 1, '2026-10-20 10:03:39', NULL),
(338, 113, 'cx_p113_2.jpg', 0, '2026-10-20 10:03:39', NULL),
(339, 113, 'cx_p113_3.jpg', 0, '2026-10-20 10:03:39', NULL),
(340, 114, 'cx_p114_1.jpg', 1, '2026-08-17 10:03:39', NULL),
(341, 114, 'cx_p114_2.jpg', 0, '2026-08-17 10:03:39', NULL),
(342, 114, 'cx_p114_3.jpg', 0, '2026-08-17 10:03:39', NULL),
(343, 115, 'cx_p115_1.jpg', 1, '2026-10-24 10:03:39', NULL),
(344, 115, 'cx_p115_2.jpg', 0, '2026-10-24 10:03:39', NULL),
(345, 115, 'cx_p115_3.jpg', 0, '2026-10-24 10:03:39', NULL),
(346, 116, 'cx_p116_1.jpg', 1, '2026-09-21 10:03:39', NULL),
(347, 116, 'cx_p116_2.jpg', 0, '2026-09-21 10:03:39', NULL),
(348, 116, 'cx_p116_3.jpg', 0, '2026-09-21 10:03:39', NULL),
(349, 117, 'cx_p117_1.jpg', 1, '2026-08-22 10:03:39', NULL),
(350, 117, 'cx_p117_2.jpg', 0, '2026-08-22 10:03:39', NULL),
(351, 117, 'cx_p117_3.jpg', 0, '2026-08-22 10:03:39', NULL),
(352, 118, 'cx_p118_1.jpg', 1, '2026-08-25 10:03:39', NULL),
(353, 118, 'cx_p118_2.jpg', 0, '2026-08-25 10:03:39', NULL),
(354, 118, 'cx_p118_3.jpg', 0, '2026-08-25 10:03:39', NULL),
(355, 119, 'cx_p119_1.jpg', 1, '2026-09-11 10:03:39', NULL),
(356, 119, 'cx_p119_2.jpg', 0, '2026-09-11 10:03:39', NULL),
(357, 119, 'cx_p119_3.jpg', 0, '2026-09-11 10:03:39', NULL),
(358, 120, 'cx_p120_1.jpg', 1, '2026-09-04 10:03:39', NULL),
(359, 120, 'cx_p120_2.jpg', 0, '2026-09-04 10:03:39', NULL),
(360, 120, 'cx_p120_3.jpg', 0, '2026-09-04 10:03:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `product_review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review_text` text DEFAULT NULL,
  `status` enum('Visible','Hidden','Removed') DEFAULT 'Visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`product_review_id`, `product_id`, `reviewer_id`, `transaction_id`, `rating`, `review_text`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 24, 83, 1, 5, 'Great experience, very helpful.', 'Visible', '2026-10-15 10:03:39', '2026-11-18 10:03:39', NULL),
(2, 28, 108, 2, 5, 'Good condition and fair price. Recommended.', 'Visible', '2026-09-13 10:03:39', '2026-11-18 10:03:39', NULL),
(3, 32, 34, 3, 4, 'Good condition and fair price. Recommended.', 'Visible', '2026-09-24 10:03:39', '2026-11-18 10:03:39', NULL),
(4, 36, 107, 4, 4, 'Quick response and easy handover on campus.', 'Visible', '2026-10-08 10:03:39', '2026-11-18 10:03:39', NULL),
(5, 40, 54, 5, 4, 'Quick response and easy handover on campus.', 'Visible', '2026-08-05 10:03:39', '2026-11-18 10:03:39', NULL),
(6, 44, 94, 6, 4, 'Seller was polite and met on time.', 'Visible', '2026-09-19 10:03:39', '2026-11-18 10:03:39', NULL),
(7, 48, 85, 7, 5, 'Honest seller, would buy again.', 'Visible', '2026-11-06 10:03:39', '2026-11-18 10:03:39', NULL),
(8, 52, 5, 8, 4, 'Seller was polite and met on time.', 'Visible', '2026-09-25 10:03:39', '2026-11-18 10:03:39', NULL),
(9, 56, 77, 9, 4, 'Smooth deal, item exactly as described.', 'Visible', '2026-09-15 10:03:39', '2026-11-18 10:03:39', NULL),
(10, 60, 50, 10, 4, 'Item was a bit more used than expected but fine.', 'Visible', '2026-10-19 10:03:39', '2026-11-18 10:03:39', NULL),
(11, 64, 30, 11, 5, 'Seller was polite and met on time.', 'Visible', '2026-08-26 10:03:39', '2026-11-18 10:03:39', NULL),
(12, 68, 63, 12, 5, 'Quick response and easy handover on campus.', 'Visible', '2026-08-21 10:03:39', '2026-11-18 10:03:39', NULL),
(13, 72, 118, 13, 4, 'Good condition and fair price. Recommended.', 'Visible', '2026-08-24 10:03:39', '2026-11-18 10:03:39', NULL),
(14, 76, 115, 14, 5, 'Item was a bit more used than expected but fine.', 'Visible', '2026-08-18 10:03:39', '2026-11-18 10:03:39', NULL),
(15, 80, 110, 15, 5, 'Smooth deal, item exactly as described.', 'Visible', '2026-08-31 10:03:39', '2026-11-18 10:03:39', NULL),
(16, 88, 57, 16, 5, 'Item was a bit more used than expected but fine.', 'Visible', '2026-08-07 10:03:39', '2026-11-18 10:03:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `remember_token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`remember_token_id`, `user_id`, `token_hash`, `expires_at`, `last_used_at`, `revoked_at`, `created_at`) VALUES
(1, 122, '9e43d26fbcae8158fd82d05517b4ee38db98d3fd31b452ba75f0e68d97d7fc62', '2026-12-19 10:19:00', NULL, '2026-11-19 10:29:05', '2026-11-19 04:49:00');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_product_id` int(11) DEFAULT NULL,
  `report_type` enum('User','Product') NOT NULL,
  `report_category` enum('Inappropriate Behavior','Scam/Fraud','Harassment','Spam','Fake/Misleading Listing','Prohibited Product','Other') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Pending','Under Review','Resolved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reporter_id`, `reported_user_id`, `reported_product_id`, `report_type`, `report_category`, `reason`, `description`, `status`, `reviewed_by`, `admin_notes`, `created_at`, `updated_at`, `resolved_at`, `deleted_at`) VALUES
(1, 1, 7, NULL, 'User', 'Scam/Fraud', 'Took advance payment, never delivered', 'I paid Rs 300 as advance for the power bank and the seller stopped replying and blocked me.', 'Resolved', 1, 'Evidence checked. Permanent ban issued. Listing reassigned to an active seller.', '2026-10-29 09:42:56', '2026-11-18 09:42:56', '2026-10-30 15:12:56', NULL),
(2, 4, 13, NULL, 'User', 'Harassment', 'Repeated abusive messages', 'Sent abusive and threatening messages after I refused to lower the price. Screenshots attached.', 'Resolved', 9, 'Permanent ban issued after review of chat screenshots.', '2026-10-31 09:42:56', '2026-11-18 09:42:56', '2026-11-01 15:12:56', NULL),
(3, 2, 11, NULL, 'User', 'Spam', 'Sends promotional links in chat', 'Keeps sending the same promotional link to many students in chat.', 'Resolved', 1, 'Account suspended for 14 days.', '2026-11-06 09:42:56', '2026-11-18 09:42:56', '2026-11-07 15:12:56', NULL),
(4, 6, 18, NULL, 'User', 'Inappropriate Behavior', 'Rude language in chat', 'Used rude and inappropriate language while negotiating.', 'Resolved', 9, 'Account suspended for 7 days.', '2026-11-15 09:42:56', '2026-11-18 09:42:56', '2026-11-16 15:12:56', NULL),
(5, 8, 5, NULL, 'User', 'Inappropriate Behavior', 'Impolite replies', 'Replies were very impolite but no threats.', 'Resolved', 1, 'Warning issued.', '2026-10-24 09:42:56', '2026-11-18 09:42:56', '2026-10-25 15:12:56', NULL),
(6, 3, 1, NULL, 'Product', 'Fake/Misleading Listing', 'Price looks too good', 'I think the laptop price is too low to be genuine.', 'Rejected', 9, 'Listing checked, specs match photos and description. No action.', '2026-11-03 09:42:56', '2026-11-18 09:42:56', '2026-11-04 15:12:56', NULL),
(7, 12, NULL, 5, 'Product', 'Fake/Misleading Listing', 'Item condition differs from photos', 'Listed as Like New but photos show visible wear around the keys.', 'Pending', NULL, NULL, '2026-11-15 09:42:56', '2026-11-18 09:42:56', NULL, NULL),
(8, 14, 16, NULL, 'User', 'Spam', 'Posting same message repeatedly', 'Sends the same message again and again in different chats.', 'Pending', NULL, NULL, '2026-11-16 09:42:56', '2026-11-18 09:42:56', NULL, NULL),
(9, 17, 4, NULL, 'User', 'Harassment', 'Pressuring me to meet at night', 'Kept insisting to meet outside campus at night even after I said no. Reported from chat #4.', 'Under Review', 9, 'Reviewing chat history. Asked reporter for more details.', '2026-11-17 09:42:56', '2026-11-18 09:42:56', NULL, NULL),
(10, 10, NULL, 19, 'Product', 'Prohibited Product', 'Not suitable for resale', 'Used footwear should not be listed, please check the guidelines.', 'Under Review', 9, NULL, '2026-11-17 09:42:56', '2026-11-18 18:57:20', NULL, NULL),
(11, 9, NULL, 3, 'Product', 'Fake/Misleading Listing', 'Book bundle missing one item', 'Description lists 3 books but seller says only 2 are available.', 'Under Review', 1, 'Contacting seller for clarification.', '2026-11-16 09:42:56', '2026-11-18 09:42:56', NULL, NULL),
(12, 2, 33, NULL, 'User', 'Scam/Fraud', 'Seller did not hand over the item after payment', 'Seller did not hand over the item after payment.', 'Resolved', 1, 'Permanent ban issued. Listing reassigned to an active seller.', '2026-10-18 10:03:39', '2026-11-18 10:03:39', '2026-10-19 15:33:39', NULL),
(13, 5, 58, NULL, 'User', 'Harassment', 'Abusive messages after price negotiation', 'Abusive messages after price negotiation.', 'Resolved', 9, 'Permanent ban issued after reviewing chat screenshots.', '2026-10-22 10:03:39', '2026-11-18 10:03:39', '2026-10-23 15:33:39', NULL),
(14, 8, 84, NULL, 'User', 'Scam/Fraud', 'Fake product photos used in listing', 'Fake product photos used in listing.', 'Resolved', 1, 'Permanent ban. Listing reassigned.', '2026-10-26 10:03:39', '2026-11-18 10:03:39', '2026-10-27 15:33:39', NULL),
(15, 3, 41, NULL, 'User', 'Spam', 'Posting the same promotion in many chats', 'Posting the same promotion in many chats.', 'Resolved', 9, 'Account suspended for 10 days.', '2026-11-09 10:03:39', '2026-11-18 10:03:39', '2026-11-10 15:33:39', NULL),
(16, 6, 67, NULL, 'User', 'Inappropriate Behavior', 'Rude replies and name calling', 'Rude replies and name calling.', 'Resolved', 1, 'Account suspended for 7 days.', '2026-11-13 10:03:39', '2026-11-18 10:03:39', '2026-11-14 15:33:39', NULL),
(17, 9, 92, NULL, 'User', 'Harassment', 'Repeated unwanted messages', 'Repeated unwanted messages.', 'Resolved', 9, 'Account suspended for 14 days.', '2026-11-07 10:03:39', '2026-11-18 10:03:39', '2026-11-08 15:33:39', NULL),
(18, 1, 51, 51, 'Product', 'Prohibited Product', 'Bad', 'very bad', 'Pending', NULL, NULL, '2026-11-18 18:51:06', '2026-11-18 18:51:06', NULL, NULL),
(19, 1, 111, 111, 'Product', 'Prohibited Product', 'Fake or misleading photos', 'vaey bad', 'Pending', NULL, NULL, '2026-11-18 18:56:08', '2026-11-18 18:56:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_evidence`
--

CREATE TABLE `report_evidence` (
  `evidence_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_evidence`
--

INSERT INTO `report_evidence` (`evidence_id`, `report_id`, `file_path`, `file_name`, `file_type`, `file_size`, `uploaded_at`, `deleted_at`) VALUES
(1, 1, 'cx_r01_1.jpg', 'payment_screenshot.jpg', 'image/jpeg', 0, '2026-10-29 09:42:56', NULL),
(2, 1, 'cx_r01_2.jpg', 'chat_screenshot.jpg', 'image/jpeg', 0, '2026-10-29 09:42:56', NULL),
(3, 2, 'cx_r02_1.jpg', 'abusive_messages.jpg', 'image/jpeg', 0, '2026-10-31 09:42:56', NULL),
(4, 3, 'cx_r03_1.jpg', 'spam_links.jpg', 'image/jpeg', 0, '2026-11-06 09:42:56', NULL),
(5, 7, 'cx_r07_1.jpg', 'key_wear_photo.jpg', 'image/jpeg', 0, '2026-11-15 09:42:56', NULL),
(6, 9, 'cx_r09_1.jpg', 'chat_screenshot.jpg', 'image/jpeg', 0, '2026-11-17 09:42:56', NULL),
(7, 11, 'cx_r11_1.jpg', 'seller_reply.jpg', 'image/jpeg', 0, '2026-11-16 09:42:56', NULL),
(8, 18, 'b78c840744923e36bf3ec005.png', 'Screenshot 2026-11-19 001616.png', 'image', 190462, '2026-11-18 18:51:06', NULL),
(9, 19, 'd3287b40342afc8f3fd07301.png', 'Screenshot 2026-11-19 001616.png', 'image', 190462, '2026-11-18 18:56:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `security_questions`
--

CREATE TABLE `security_questions` (
  `security_question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_reviews`
--

CREATE TABLE `seller_reviews` (
  `seller_review_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review_text` text DEFAULT NULL,
  `status` enum('Visible','Hidden','Removed') DEFAULT 'Visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_reviews`
--

INSERT INTO `seller_reviews` (`seller_review_id`, `seller_id`, `reviewer_id`, `transaction_id`, `rating`, `review_text`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 24, 83, 1, 5, 'Quick response and easy handover on campus.', 'Visible', '2026-10-15 10:03:39', '2026-11-18 10:03:39', NULL),
(2, 28, 108, 2, 4, 'Great experience, very helpful.', 'Visible', '2026-09-13 10:03:39', '2026-11-18 10:03:39', NULL),
(3, 32, 34, 3, 4, 'Item was a bit more used than expected but fine.', 'Visible', '2026-09-24 10:03:39', '2026-11-18 10:03:39', NULL),
(4, 36, 107, 4, 4, 'Smooth deal, item exactly as described.', 'Visible', '2026-10-08 10:03:39', '2026-11-18 10:03:39', NULL),
(5, 40, 54, 5, 5, 'Honest seller, would buy again.', 'Visible', '2026-08-05 10:03:39', '2026-11-18 10:03:39', NULL),
(6, 44, 94, 6, 5, 'Great experience, very helpful.', 'Visible', '2026-09-19 10:03:39', '2026-11-18 10:03:39', NULL),
(7, 48, 85, 7, 5, 'Seller was polite and met on time.', 'Visible', '2026-11-06 10:03:39', '2026-11-18 10:03:39', NULL),
(8, 52, 5, 8, 5, 'Smooth deal, item exactly as described.', 'Visible', '2026-09-25 10:03:39', '2026-11-18 10:03:39', NULL),
(9, 56, 77, 9, 5, 'Seller was polite and met on time.', 'Visible', '2026-09-15 10:03:39', '2026-11-18 10:03:39', NULL),
(10, 60, 50, 10, 5, 'Item was a bit more used than expected but fine.', 'Visible', '2026-10-19 10:03:39', '2026-11-18 10:03:39', NULL),
(11, 64, 30, 11, 5, 'Product worked perfectly, thank you!', 'Visible', '2026-08-26 10:03:39', '2026-11-18 10:03:39', NULL),
(12, 68, 63, 12, 5, 'Seller was polite and met on time.', 'Visible', '2026-08-21 10:03:39', '2026-11-18 10:03:39', NULL),
(13, 72, 118, 13, 4, 'Great experience, very helpful.', 'Visible', '2026-08-24 10:03:39', '2026-11-18 10:03:39', NULL),
(14, 76, 115, 14, 5, 'Quick response and easy handover on campus.', 'Visible', '2026-08-18 10:03:39', '2026-11-18 10:03:39', NULL),
(15, 80, 110, 15, 5, 'Item was a bit more used than expected but fine.', 'Visible', '2026-08-31 10:03:39', '2026-11-18 10:03:39', NULL),
(16, 88, 57, 16, 4, 'Quick response and easy handover on campus.', 'Visible', '2026-08-07 10:03:39', '2026-11-18 10:03:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('Text','Number','Boolean','Image','Email') DEFAULT 'Text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'site_name', 'Campus Sell & Buy Portal', 'Text', 'Name displayed across the marketplace.', 1, '2026-09-19 21:38:51', '2026-09-19 21:38:51', NULL),
(2, 'maintenance_mode', '0', 'Boolean', 'Controls whether the portal is placed into maintenance mode.', 1, '2026-09-19 21:38:51', '2026-09-19 21:38:51', NULL),
(3, 'user_favicon', 'user_favicon.ico', 'Image', 'Favicon displayed on the user-facing CampusXchange portal.', 1, '2026-09-19 21:38:51', '2026-09-19 21:38:51', NULL),
(4, 'admin_favicon', 'admin_favicon.ico', 'Image', 'Favicon displayed on the administrator dashboard.', 1, '2026-09-19 21:38:51', '2026-09-19 21:38:51', NULL),
(5, 'support_email', 'support@campusportal.edu', 'Email', 'Email address used for general platform support.', 1, '2026-09-19 21:38:51', '2026-09-19 21:38:51', NULL),
(6, 'platform_name', 'CampusXchange', 'Text', 'Name displayed for the platform', 1, '2026-10-29 13:54:55', '2026-10-29 13:58:36', NULL),
(8, 'verification_enabled', '1', 'Boolean', 'Allow user identity verification', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL),
(9, 'require_id_front', '1', 'Boolean', 'Require the front side of the college ID', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL),
(10, 'require_id_back', '1', 'Boolean', 'Require the back side of the college ID', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL),
(11, 'verification_max_file_mb', '5', 'Number', 'Maximum size per verification image in MB', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL),
(12, 'notify_new_reports', '0', 'Boolean', 'Enable admin notifications for new reports', 1, '2026-10-29 13:54:55', '2026-10-29 13:58:49', NULL),
(13, 'notify_verification_requests', '1', 'Boolean', 'Enable admin notifications for verification requests', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL),
(14, 'notify_new_users', '0', 'Boolean', 'Enable admin notifications for new registrations', 1, '2026-10-29 13:54:55', '2026-10-29 13:54:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `transaction_status` enum('Completed','Cancelled','Disputed') DEFAULT 'Completed',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `product_id`, `seller_id`, `buyer_id`, `selling_price`, `transaction_status`, `transaction_date`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 24, 24, 83, 250.00, 'Completed', '2026-10-14 10:03:39', NULL, '2026-10-14 10:03:39', '2026-11-18 10:03:39', NULL),
(2, 28, 28, 108, 450.00, 'Completed', '2026-09-12 10:03:39', NULL, '2026-09-12 10:03:39', '2026-11-18 10:03:39', NULL),
(3, 32, 32, 34, 650.00, 'Completed', '2026-09-23 10:03:39', NULL, '2026-09-23 10:03:39', '2026-11-18 10:03:39', NULL),
(4, 36, 36, 107, 315.00, 'Completed', '2026-10-07 10:03:39', NULL, '2026-10-07 10:03:39', '2026-11-18 10:03:39', NULL),
(5, 40, 40, 54, 450.00, 'Completed', '2026-08-04 10:03:39', NULL, '2026-08-04 10:03:39', '2026-11-18 10:03:39', NULL),
(6, 44, 44, 94, 450.00, 'Completed', '2026-09-18 10:03:39', NULL, '2026-09-18 10:03:39', '2026-11-18 10:03:39', NULL),
(7, 48, 48, 85, 800.00, 'Completed', '2026-11-05 10:03:39', NULL, '2026-11-05 10:03:39', '2026-11-18 10:03:39', NULL),
(8, 52, 52, 5, 108.00, 'Completed', '2026-09-24 10:03:39', NULL, '2026-09-24 10:03:39', '2026-11-18 10:03:39', NULL),
(9, 56, 56, 77, 270.00, 'Completed', '2026-09-14 10:03:39', NULL, '2026-09-14 10:03:39', '2026-11-18 10:03:39', NULL),
(10, 60, 60, 50, 540.00, 'Completed', '2026-10-18 10:03:39', NULL, '2026-10-18 10:03:39', '2026-11-18 10:03:39', NULL),
(11, 64, 64, 30, 150.00, 'Completed', '2026-08-25 10:03:39', NULL, '2026-08-25 10:03:39', '2026-11-18 10:03:39', NULL),
(12, 68, 68, 63, 1045.00, 'Completed', '2026-08-20 10:03:39', NULL, '2026-08-20 10:03:39', '2026-11-18 10:03:39', NULL),
(13, 72, 72, 118, 585.00, 'Completed', '2026-08-23 10:03:39', NULL, '2026-08-23 10:03:39', '2026-11-18 10:03:39', NULL),
(14, 76, 76, 115, 2500.00, 'Completed', '2026-08-17 10:03:39', NULL, '2026-08-17 10:03:39', '2026-11-18 10:03:39', NULL),
(15, 80, 80, 110, 570.00, 'Completed', '2026-08-30 10:03:39', NULL, '2026-08-30 10:03:39', '2026-11-18 10:03:39', NULL),
(16, 88, 88, 57, 400.00, 'Completed', '2026-08-06 10:03:39', NULL, '2026-08-06 10:03:39', '2026-11-18 10:03:39', NULL),
(17, 96, 96, 105, 6000.00, 'Completed', '2026-10-19 10:03:39', NULL, '2026-10-19 10:03:39', '2026-11-18 10:03:39', NULL),
(18, 100, 100, 34, 1260.00, 'Completed', '2026-10-25 10:03:39', NULL, '2026-10-25 10:03:39', '2026-11-18 10:03:39', NULL),
(19, 104, 104, 50, 1425.00, 'Completed', '2026-08-22 10:03:39', NULL, '2026-08-22 10:03:39', '2026-11-18 10:03:39', NULL),
(20, 108, 108, 22, 350.00, 'Completed', '2026-10-29 10:03:39', NULL, '2026-10-29 10:03:39', '2026-11-18 10:03:39', NULL),
(21, 112, 112, 17, 380.00, 'Completed', '2026-08-16 10:03:39', NULL, '2026-08-16 10:03:39', '2026-11-18 10:03:39', NULL),
(22, 116, 116, 30, 900.00, 'Completed', '2026-09-24 10:03:39', NULL, '2026-09-24 10:03:39', '2026-11-18 10:03:39', NULL),
(23, 120, 120, 49, 800.00, 'Completed', '2026-09-16 10:03:39', NULL, '2026-09-16 10:03:39', '2026-11-18 10:03:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pending_email` varchar(150) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('Student','Faculty') DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `status` enum('Pending','Active','Suspended','Banned') DEFAULT 'Pending',
  `session_version` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `pending_email`, `phone`, `role`, `password`, `email_verified_at`, `status`, `session_version`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Aarav Sharma', 'aarav.sharma@campusxchange.test', NULL, '9826000101', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-20 15:12:56', 'Active', 1, '2026-08-19 09:42:56', '2026-09-18 09:42:56', NULL),
(2, 'Priya Verma', 'priya.verma@campusxchange.test', NULL, '9826000102', 'Student', '$2y$12$M8.Kz3zoVf0HYXsYlX506OXSmYqLYWuoycoRq0WpRPoAU6MA.r1NG', '2026-08-24 15:12:56', 'Active', 1, '2026-08-23 09:42:56', '2026-09-22 09:42:56', NULL),
(3, 'Rohan Gupta', 'rohan.gupta@campusxchange.test', NULL, '9826000103', 'Student', '$2y$12$QNfu22wMo3fFMqYppuQeKeouikzKAkqErg/ny1z9JjnxiO7eh63L6', '2026-08-28 15:12:56', 'Active', 1, '2026-08-27 09:42:56', '2026-09-26 09:42:56', NULL),
(4, 'Ananya Singh', 'ananya.singh@campusxchange.test', NULL, '9826000104', 'Student', '$2y$12$nUIzkwZ.3y4hskhKF5xaluZirQbENGQzaM1rTnzIIXYfV3XGJvLJe', '2026-09-01 15:12:56', 'Active', 1, '2026-08-31 09:42:56', '2026-09-30 09:42:56', NULL),
(5, 'Karan Mehta', 'karan.mehta@campusxchange.test', NULL, '9826000105', 'Student', '$2y$12$7M1e5p5MOa7h4oIokq9GK.1eTtRPkQrE7kc22cUnrZg.2LBkXQR8S', '2026-09-05 15:12:56', 'Active', 1, '2026-09-04 09:42:56', '2026-10-04 09:42:56', NULL),
(6, 'Sneha Tiwari', 'sneha.tiwari@campusxchange.test', NULL, '9826000106', 'Student', '$2y$12$vNT/ukbBAqwrQcaHBiCDueqQCA/jrhe2zpo3quFa.PMRGcCPdncpy', '2026-09-09 15:12:56', 'Active', 1, '2026-09-08 09:42:56', '2026-10-08 09:42:56', NULL),
(7, 'Vikram Chauhan', 'vikram.chauhan@campusxchange.test', NULL, '9826000107', 'Student', '$2y$12$GWhYx9U16HQQ0s1MFrBwcu9rjsnIzM/2BmKsHAv0U1bSBBSt2gAUm', '2026-09-13 15:12:56', 'Banned', 1, '2026-09-12 09:42:56', '2026-10-12 09:42:56', NULL),
(8, 'Arjun Pandey', 'arjun.pandey@campusxchange.test', NULL, '9826000108', 'Student', '$2y$12$ru50Kxdytey0Ycuev07nH.AeG4vi.Mzg79Iai6u3gyP1jKVhQeysu', '2026-09-17 15:12:56', 'Active', 1, '2026-09-16 09:42:56', '2026-10-16 09:42:56', NULL),
(9, 'Ishita Kapoor', 'ishita.kapoor@campusxchange.test', NULL, '9826000109', 'Student', '$2y$12$Azf15x/72YcAwcTcHXGo6uuJCJBLh6Xr/f48qddLwhal6ViE2ihXi', '2026-09-21 15:12:56', 'Active', 1, '2026-09-20 09:42:56', '2026-10-20 09:42:56', NULL),
(10, 'Dr. Sanjay Kulshreshtha', 'sanjay.kulshreshtha@campusxchange.test', NULL, '9826000110', 'Faculty', '$2y$12$ltYeaMxKUWJ/nRo4Wo27n.zgGuooe7PzuQ2sm9pFlWYAar1DbJEd2', '2026-09-25 15:12:56', 'Active', 1, '2026-09-24 09:42:56', '2026-10-24 09:42:56', NULL),
(11, 'Mohit Yadav', 'mohit.yadav@campusxchange.test', NULL, '9826000111', 'Student', '$2y$12$6A7kJDgPWYp5c5PQonbguufLCKD0ObrtaqY2dwhltIpWTwTGn7LVG', '2026-09-29 15:12:56', 'Suspended', 1, '2026-09-28 09:42:56', '2026-10-28 09:42:56', NULL),
(12, 'Kritika Saxena', 'kritika.saxena@campusxchange.test', NULL, '9826000112', 'Student', '$2y$12$PphBNC7/eHF/pVCaC0m0HO9Z65ob6/kFPQsMrMPjnjCazXfO1yQQi', '2026-10-03 15:12:56', 'Active', 1, '2026-10-02 09:42:56', '2026-11-01 09:42:56', NULL),
(13, 'Harsh Dubey', 'harsh.dubey@campusxchange.test', NULL, '9826000113', 'Student', '$2y$12$DNXJys8uKtKiwSO9XPw2j.Y9CFxCytmmRuKfwMLEweKjBnHUtksMi', '2026-10-07 15:12:56', 'Banned', 1, '2026-10-06 09:42:56', '2026-11-05 09:42:56', NULL),
(14, 'Pooja Kushwaha', 'pooja.kushwaha@campusxchange.test', NULL, '9826000114', 'Student', '$2y$12$ns0W/MBdM7JQju9K4VH7Oea2yjPJbMX5uwGl1OZjLjRlQqIPPdH7K', '2026-10-11 15:12:56', 'Active', 1, '2026-10-10 09:42:56', '2026-11-09 09:42:56', NULL),
(15, 'Dr. Meenal Agrawal', 'meenal.agrawal@campusxchange.test', NULL, '9826000115', 'Faculty', '$2y$12$2Ir4lfis8AX5rpofFz6ZA.KHE8XqVxIp0Ni370h1Ob8GGRyXCXo82', '2026-10-15 15:12:56', 'Active', 1, '2026-10-14 09:42:56', '2026-11-13 09:42:56', NULL),
(16, 'Aditya Bhadoria', 'aditya.bhadoria@campusxchange.test', NULL, '9826000116', 'Student', '$2y$12$IJ3jMTIlJGRBqXxDVmUkiu5ZSFe0Fb1PGKwLlV4YoE88QrV/n59C6', '2026-10-19 15:12:56', 'Active', 1, '2026-10-18 09:42:56', '2026-11-17 09:42:56', NULL),
(17, 'Divya Rathore', 'divya.rathore@campusxchange.test', NULL, '9826000117', 'Student', '$2y$12$8uhZ/xuujQKuep4JP/sF9uRar8w4TZAd9ValaRJQLQ6teEnjtOirG', '2026-10-23 15:12:56', 'Active', 1, '2026-10-22 09:42:56', '2026-11-17 09:42:56', NULL),
(18, 'Nikhil Parihar', 'nikhil.parihar@campusxchange.test', NULL, '9826000118', 'Student', '$2y$12$w8VAMHX0OC06hsNFz3RaH.Z7SneNejhE7scvHV4Lomzq6RBh17F3.', '2026-10-27 15:12:56', 'Suspended', 1, '2026-10-26 09:42:56', '2026-11-17 09:42:56', NULL),
(19, 'Shreya Joshi', 'shreya.joshi@campusxchange.test', NULL, '9826000119', 'Student', '$2y$12$8VDRiwVbMcluZ9zwh1D.weqkS72l/DR7BIhRHLtDZqFc3H6xCLuae', '2026-10-31 15:12:56', 'Active', 1, '2026-10-30 09:42:56', '2026-11-17 09:42:56', NULL),
(20, 'Prof. Rajesh Dixit', 'rajesh.dixit@campusxchange.test', NULL, '9826000120', 'Faculty', '$2y$12$62zQs5rPKZHwKeEaqDNb6ODrMLqareXprOj0zIjBIDVtWe/zfjUQK', '2026-11-04 15:12:56', 'Active', 1, '2026-11-03 09:42:56', '2026-11-17 09:42:56', NULL),
(21, 'Aditi Agarwal', 'aditi.agarwal@campusxchange.test', NULL, '9826000121', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-16 15:33:39', 'Active', 1, '2026-08-15 10:03:39', '2026-09-04 10:03:39', NULL),
(22, 'Akash Mishra', 'akash.mishra@campusxchange.test', NULL, '9826000122', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-04 15:33:39', 'Active', 1, '2026-09-03 10:03:39', '2026-09-23 10:03:39', NULL),
(23, 'Alok Vyas', 'alok.vyas@campusxchange.test', NULL, '9826000123', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-05 15:33:39', 'Active', 1, '2026-08-04 10:03:39', '2026-08-24 10:03:39', NULL),
(24, 'Amisha Kaur', 'amisha.kaur@campusxchange.test', NULL, '9826000124', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-04 15:33:39', 'Active', 1, '2026-10-03 10:03:39', '2026-10-23 10:03:39', NULL),
(25, 'Anjali Chaturvedi', 'anjali.chaturvedi@campusxchange.test', NULL, '9826000125', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-11 15:33:39', 'Active', 1, '2026-09-10 10:03:39', '2026-09-30 10:03:39', NULL),
(26, 'Ankit Pathak', 'ankit.pathak@campusxchange.test', NULL, '9826000126', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-25 15:33:39', 'Active', 1, '2026-08-24 10:03:39', '2026-09-13 10:03:39', NULL),
(27, 'Ankita Bhatt', 'ankita.bhatt@campusxchange.test', NULL, '9826000127', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-31 15:33:39', 'Active', 1, '2026-10-30 10:03:39', '2026-11-17 10:03:39', NULL),
(28, 'Aryan Nagar', 'aryan.nagar@campusxchange.test', NULL, '9826000128', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-07 15:33:39', 'Active', 1, '2026-09-06 10:03:39', '2026-09-26 10:03:39', NULL),
(29, 'Ashish Gaur', 'ashish.gaur@campusxchange.test', NULL, '9826000129', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-06 15:33:39', 'Active', 1, '2026-09-05 10:03:39', '2026-09-25 10:03:39', NULL),
(30, 'Bhavna Shukla', 'bhavna.shukla@campusxchange.test', NULL, '9826000130', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-27 15:33:39', 'Active', 1, '2026-08-26 10:03:39', '2026-09-15 10:03:39', NULL),
(31, 'Chirag Dave', 'chirag.dave@campusxchange.test', NULL, '9826000131', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-01 15:33:39', 'Active', 1, '2026-09-30 10:03:39', '2026-10-20 10:03:39', NULL),
(32, 'Deepak Purohit', 'deepak.purohit@campusxchange.test', NULL, '9826000132', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-10 15:33:39', 'Active', 1, '2026-09-09 10:03:39', '2026-09-29 10:03:39', NULL),
(33, 'Devansh Kushwah', 'devansh.kushwah@campusxchange.test', NULL, '9826000133', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-02 15:33:39', 'Banned', 1, '2026-10-01 10:03:39', '2026-10-21 10:03:39', NULL),
(34, 'Disha Upadhyay', 'disha.upadhyay@campusxchange.test', NULL, '9826000134', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-19 15:33:39', 'Active', 1, '2026-08-18 10:03:39', '2026-09-07 10:03:39', NULL),
(35, 'Gaurav Jha', 'gaurav.jha@campusxchange.test', NULL, '9826000135', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-29 15:33:39', 'Active', 1, '2026-09-28 10:03:39', '2026-10-18 10:03:39', NULL),
(36, 'Geeta Bansal', 'geeta.bansal@campusxchange.test', NULL, '9826000136', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-26 15:33:39', 'Active', 1, '2026-09-25 10:03:39', '2026-10-15 10:03:39', NULL),
(37, 'Himanshu Narvaria', 'himanshu.narvaria@campusxchange.test', NULL, '9826000137', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-19 15:33:39', 'Active', 1, '2026-10-18 10:03:39', '2026-11-07 10:03:39', NULL),
(38, 'Isha Yadav', 'isha.yadav@campusxchange.test', NULL, '9826000138', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-11-02 15:33:39', 'Active', 1, '2026-11-01 10:03:39', '2026-11-17 10:03:39', NULL),
(39, 'Jatin Malik', 'jatin.malik@campusxchange.test', NULL, '9826000139', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-01 15:33:39', 'Active', 1, '2026-08-31 10:03:39', '2026-09-20 10:03:39', NULL),
(40, 'Juhi Dwivedi', 'juhi.dwivedi@campusxchange.test', NULL, '9826000140', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-01 15:33:39', 'Active', 1, '2026-07-31 10:03:39', '2026-08-20 10:03:39', NULL),
(41, 'Kabir Raghuvanshi', 'kabir.raghuvanshi@campusxchange.test', NULL, '9826000141', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-20 15:33:39', 'Suspended', 1, '2026-10-19 10:03:39', '2026-11-08 10:03:39', NULL),
(42, 'Kavya Chouhan', 'kavya.chouhan@campusxchange.test', NULL, '9826000142', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-06 15:33:39', 'Active', 1, '2026-08-05 10:03:39', '2026-08-25 10:03:39', NULL),
(43, 'Kunal Ojha', 'kunal.ojha@campusxchange.test', NULL, '9826000143', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-10 15:33:39', 'Active', 1, '2026-10-09 10:03:39', '2026-10-29 10:03:39', NULL),
(44, 'Lakshya Jain', 'lakshya.jain@campusxchange.test', NULL, '9826000144', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-05 15:33:39', 'Active', 1, '2026-09-04 10:03:39', '2026-09-24 10:03:39', NULL),
(45, 'Manav Tomar', 'manav.tomar@campusxchange.test', NULL, '9826000145', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-06 15:33:39', 'Active', 1, '2026-09-05 10:03:39', '2026-09-25 10:03:39', NULL),
(46, 'Meera Goyal', 'meera.goyal@campusxchange.test', NULL, '9826000146', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-23 15:33:39', 'Active', 1, '2026-09-22 10:03:39', '2026-10-12 10:03:39', NULL),
(47, 'Mayank Agarwal', 'mayank.agarwal@campusxchange.test', NULL, '9826000147', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-23 15:33:39', 'Active', 1, '2026-08-22 10:03:39', '2026-09-11 10:03:39', NULL),
(48, 'Naman Mishra', 'naman.mishra@campusxchange.test', NULL, '9826000148', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-29 15:33:39', 'Active', 1, '2026-10-28 10:03:39', '2026-11-17 10:03:39', NULL),
(49, 'Neha Vyas', 'neha.vyas@campusxchange.test', NULL, '9826000149', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-26 15:33:39', 'Active', 1, '2026-08-25 10:03:39', '2026-09-14 10:03:39', NULL),
(50, 'Nidhi Kaur', 'nidhi.kaur@campusxchange.test', NULL, '9826000150', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-15 15:33:39', 'Active', 1, '2026-10-14 10:03:39', '2026-11-03 10:03:39', NULL),
(51, 'Om Chaturvedi', 'om.chaturvedi@campusxchange.test', NULL, '9826000151', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-11-08 15:33:39', 'Active', 1, '2026-11-07 10:03:39', '2026-11-17 10:03:39', NULL),
(52, 'Palak Pathak', 'palak.pathak@campusxchange.test', NULL, '9826000152', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-13 15:33:39', 'Active', 1, '2026-09-12 10:03:39', '2026-10-02 10:03:39', NULL),
(53, 'Parth Bhatt', 'parth.bhatt@campusxchange.test', NULL, '9826000153', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-12 15:33:39', 'Active', 1, '2026-10-11 10:03:39', '2026-10-31 10:03:39', NULL),
(54, 'Piyush Nagar', 'piyush.nagar@campusxchange.test', NULL, '9826000154', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-16 15:33:39', 'Active', 1, '2026-10-15 10:03:39', '2026-11-04 10:03:39', NULL),
(55, 'Pranav Gaur', 'pranav.gaur@campusxchange.test', NULL, '9826000155', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-18 15:33:39', 'Active', 1, '2026-09-17 10:03:39', '2026-10-07 10:03:39', NULL),
(56, 'Rachit Shukla', 'rachit.shukla@campusxchange.test', NULL, '9826000156', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-07 15:33:39', 'Active', 1, '2026-09-06 10:03:39', '2026-09-26 10:03:39', NULL),
(57, 'Radhika Dave', 'radhika.dave@campusxchange.test', NULL, '9826000157', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-05 15:33:39', 'Active', 1, '2026-09-04 10:03:39', '2026-09-24 10:03:39', NULL),
(58, 'Rahul Purohit', 'rahul.purohit@campusxchange.test', NULL, '9826000158', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-26 15:33:39', 'Banned', 1, '2026-10-25 10:03:39', '2026-11-14 10:03:39', NULL),
(59, 'Riya Kushwah', 'riya.kushwah@campusxchange.test', NULL, '9826000159', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-07 15:33:39', 'Active', 1, '2026-09-06 10:03:39', '2026-09-26 10:03:39', NULL),
(60, 'Sakshi Upadhyay', 'sakshi.upadhyay@campusxchange.test', NULL, '9826000160', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-15 15:33:39', 'Active', 1, '2026-10-14 10:03:39', '2026-11-03 10:03:39', NULL),
(61, 'Saurabh Jha', 'saurabh.jha@campusxchange.test', NULL, '9826000161', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-02 15:33:39', 'Active', 1, '2026-08-01 10:03:39', '2026-08-21 10:03:39', NULL),
(62, 'Shivam Bansal', 'shivam.bansal@campusxchange.test', NULL, '9826000162', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-08 15:33:39', 'Active', 1, '2026-10-07 10:03:39', '2026-10-27 10:03:39', NULL),
(63, 'Simran Narvaria', 'simran.narvaria@campusxchange.test', NULL, '9826000163', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-04 15:33:39', 'Active', 1, '2026-08-03 10:03:39', '2026-08-23 10:03:39', NULL),
(64, 'Siddharth Yadav', 'siddharth.yadav@campusxchange.test', NULL, '9826000164', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-12 15:33:39', 'Active', 1, '2026-08-11 10:03:39', '2026-08-31 10:03:39', NULL),
(65, 'Tanvi Malik', 'tanvi.malik@campusxchange.test', NULL, '9826000165', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-10 15:33:39', 'Active', 1, '2026-09-09 10:03:39', '2026-09-29 10:03:39', NULL),
(66, 'Tushar Dwivedi', 'tushar.dwivedi@campusxchange.test', NULL, '9826000166', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-25 15:33:39', 'Active', 1, '2026-10-24 10:03:39', '2026-11-13 10:03:39', NULL),
(67, 'Utkarsh Raghuvanshi', 'utkarsh.raghuvanshi@campusxchange.test', NULL, '9826000167', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-01 15:33:39', 'Suspended', 1, '2026-08-31 10:03:39', '2026-09-20 10:03:39', NULL),
(68, 'Vaibhav Chouhan', 'vaibhav.chouhan@campusxchange.test', NULL, '9826000168', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-17 15:33:39', 'Active', 1, '2026-08-16 10:03:39', '2026-09-05 10:03:39', NULL),
(69, 'Yash Ojha', 'yash.ojha@campusxchange.test', NULL, '9826000169', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-07-27 15:33:39', 'Active', 1, '2026-07-26 10:03:39', '2026-08-15 10:03:39', NULL),
(70, 'Zoya Jain', 'zoya.jain@campusxchange.test', NULL, '9826000170', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-01 15:33:39', 'Active', 1, '2026-09-30 10:03:39', '2026-10-20 10:03:39', NULL),
(71, 'Aditi Yadav', 'aditi.yadav@campusxchange.test', NULL, '9826000171', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-26 15:33:39', 'Active', 1, '2026-09-25 10:03:39', '2026-10-15 10:03:39', NULL),
(72, 'Akash Malik', 'akash.malik@campusxchange.test', NULL, '9826000172', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-20 15:33:39', 'Active', 1, '2026-08-19 10:03:39', '2026-09-08 10:03:39', NULL),
(73, 'Alok Dwivedi', 'alok.dwivedi@campusxchange.test', NULL, '9826000173', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-22 15:33:39', 'Active', 1, '2026-09-21 10:03:39', '2026-10-11 10:03:39', NULL),
(74, 'Amisha Raghuvanshi', 'amisha.raghuvanshi@campusxchange.test', NULL, '9826000174', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-04 15:33:39', 'Active', 1, '2026-09-03 10:03:39', '2026-09-23 10:03:39', NULL),
(75, 'Anjali Chouhan', 'anjali.chouhan@campusxchange.test', NULL, '9826000175', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-25 15:33:39', 'Active', 1, '2026-08-24 10:03:39', '2026-09-13 10:03:39', NULL),
(76, 'Ankit Ojha', 'ankit.ojha@campusxchange.test', NULL, '9826000176', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-11 15:33:39', 'Active', 1, '2026-08-10 10:03:39', '2026-08-30 10:03:39', NULL),
(77, 'Ankita Jain', 'ankita.jain@campusxchange.test', NULL, '9826000177', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-03 15:33:39', 'Active', 1, '2026-09-02 10:03:39', '2026-09-22 10:03:39', NULL),
(78, 'Aryan Tomar', 'aryan.tomar@campusxchange.test', NULL, '9826000178', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-18 15:33:39', 'Active', 1, '2026-10-17 10:03:39', '2026-11-06 10:03:39', NULL),
(79, 'Ashish Goyal', 'ashish.goyal@campusxchange.test', NULL, '9826000179', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-23 15:33:39', 'Active', 1, '2026-10-22 10:03:39', '2026-11-11 10:03:39', NULL),
(80, 'Bhavna Agarwal', 'bhavna.agarwal@campusxchange.test', NULL, '9826000180', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-26 15:33:39', 'Active', 1, '2026-08-25 10:03:39', '2026-09-14 10:03:39', NULL),
(81, 'Chirag Mishra', 'chirag.mishra@campusxchange.test', NULL, '9826000181', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-02 15:33:39', 'Active', 1, '2026-09-01 10:03:39', '2026-09-21 10:03:39', NULL),
(82, 'Deepak Vyas', 'deepak.vyas@campusxchange.test', NULL, '9826000182', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-13 15:33:39', 'Active', 1, '2026-08-12 10:03:39', '2026-09-01 10:03:39', NULL),
(83, 'Devansh Kaur', 'devansh.kaur@campusxchange.test', NULL, '9826000183', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-23 15:33:39', 'Active', 1, '2026-10-22 10:03:39', '2026-11-11 10:03:39', NULL),
(84, 'Disha Chaturvedi', 'disha.chaturvedi@campusxchange.test', NULL, '9826000184', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-10 15:33:39', 'Banned', 1, '2026-08-09 10:03:39', '2026-08-29 10:03:39', NULL),
(85, 'Gaurav Pathak', 'gaurav.pathak@campusxchange.test', NULL, '9826000185', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-24 15:33:39', 'Active', 1, '2026-10-23 10:03:39', '2026-11-12 10:03:39', NULL),
(86, 'Geeta Bhatt', 'geeta.bhatt@campusxchange.test', NULL, '9826000186', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-26 15:33:39', 'Active', 1, '2026-10-25 10:03:39', '2026-11-14 10:03:39', NULL),
(87, 'Himanshu Nagar', 'himanshu.nagar@campusxchange.test', NULL, '9826000187', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-01 15:33:39', 'Active', 1, '2026-07-31 10:03:39', '2026-08-20 10:03:39', NULL),
(88, 'Isha Gaur', 'isha.gaur@campusxchange.test', NULL, '9826000188', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-07-30 15:33:39', 'Active', 1, '2026-07-29 10:03:39', '2026-08-18 10:03:39', NULL),
(89, 'Jatin Shukla', 'jatin.shukla@campusxchange.test', NULL, '9826000189', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-01 15:33:39', 'Active', 1, '2026-07-31 10:03:39', '2026-08-20 10:03:39', NULL),
(90, 'Juhi Dave', 'juhi.dave@campusxchange.test', NULL, '9826000190', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-19 15:33:39', 'Active', 1, '2026-08-18 10:03:39', '2026-09-07 10:03:39', NULL),
(91, 'Kabir Purohit', 'kabir.purohit@campusxchange.test', NULL, '9826000191', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-30 15:33:39', 'Active', 1, '2026-08-29 10:03:39', '2026-09-18 10:03:39', NULL),
(92, 'Kavya Kushwah', 'kavya.kushwah@campusxchange.test', NULL, '9826000192', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-11-04 15:33:39', 'Suspended', 1, '2026-11-03 10:03:39', '2026-11-17 10:03:39', NULL),
(93, 'Kunal Upadhyay', 'kunal.upadhyay@campusxchange.test', NULL, '9826000193', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-01 15:33:39', 'Active', 1, '2026-07-31 10:03:39', '2026-08-20 10:03:39', NULL),
(94, 'Lakshya Jha', 'lakshya.jha@campusxchange.test', NULL, '9826000194', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-18 15:33:39', 'Active', 1, '2026-08-17 10:03:39', '2026-09-06 10:03:39', NULL),
(95, 'Manav Bansal', 'manav.bansal@campusxchange.test', NULL, '9826000195', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-11-07 15:33:39', 'Active', 1, '2026-11-06 10:03:39', '2026-11-17 10:03:39', NULL),
(96, 'Meera Narvaria', 'meera.narvaria@campusxchange.test', NULL, '9826000196', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-07 15:33:39', 'Active', 1, '2026-10-06 10:03:39', '2026-10-26 10:03:39', NULL),
(97, 'Mayank Yadav', 'mayank.yadav@campusxchange.test', NULL, '9826000197', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-02 15:33:39', 'Active', 1, '2026-08-01 10:03:39', '2026-08-21 10:03:39', NULL),
(98, 'Naman Malik', 'naman.malik@campusxchange.test', NULL, '9826000198', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-04 15:33:39', 'Active', 1, '2026-10-03 10:03:39', '2026-10-23 10:03:39', NULL),
(99, 'Neha Dwivedi', 'neha.dwivedi@campusxchange.test', NULL, '9826000199', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-07-30 15:33:39', 'Active', 1, '2026-07-29 10:03:39', '2026-08-18 10:03:39', NULL),
(100, 'Nidhi Raghuvanshi', 'nidhi.raghuvanshi@campusxchange.test', NULL, '9826000200', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-20 15:33:39', 'Active', 1, '2026-10-19 10:03:39', '2026-11-08 10:03:39', NULL),
(101, 'Om Chouhan', 'om.chouhan@campusxchange.test', NULL, '9826000201', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-25 15:33:39', 'Active', 1, '2026-10-24 10:03:39', '2026-11-13 10:03:39', NULL),
(102, 'Palak Ojha', 'palak.ojha@campusxchange.test', NULL, '9826000202', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-08 15:33:39', 'Active', 1, '2026-10-07 10:03:39', '2026-10-27 10:03:39', NULL),
(103, 'Parth Jain', 'parth.jain@campusxchange.test', NULL, '9826000203', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-03 15:33:39', 'Active', 1, '2026-09-02 10:03:39', '2026-09-22 10:03:39', NULL),
(104, 'Piyush Tomar', 'piyush.tomar@campusxchange.test', NULL, '9826000204', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-15 15:33:39', 'Active', 1, '2026-08-14 10:03:39', '2026-09-03 10:03:39', NULL),
(105, 'Pranav Goyal', 'pranav.goyal@campusxchange.test', NULL, '9826000205', 'Student', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-27 15:33:39', 'Active', 1, '2026-09-26 10:03:39', '2026-10-16 10:03:39', NULL),
(106, 'Prof. Rachit Agarwal', 'rachit.agarwal@campusxchange.test', NULL, '9826000206', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-26 15:33:39', 'Active', 1, '2026-10-25 10:03:39', '2026-11-14 10:03:39', NULL),
(107, 'Prof. Radhika Mishra', 'radhika.mishra@campusxchange.test', NULL, '9826000207', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-14 15:33:39', 'Active', 1, '2026-09-13 10:03:39', '2026-10-03 10:03:39', NULL),
(108, 'Dr. Rahul Vyas', 'rahul.vyas@campusxchange.test', NULL, '9826000208', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-21 15:33:39', 'Active', 1, '2026-10-20 10:03:39', '2026-11-09 10:03:39', NULL),
(109, 'Dr. Riya Kaur', 'riya.kaur@campusxchange.test', NULL, '9826000209', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-19 15:33:39', 'Active', 1, '2026-10-18 10:03:39', '2026-11-07 10:03:39', NULL),
(110, 'Prof. Sakshi Chaturvedi', 'sakshi.chaturvedi@campusxchange.test', NULL, '9826000210', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-15 15:33:39', 'Active', 1, '2026-08-14 10:03:39', '2026-09-03 10:03:39', NULL),
(111, 'Dr. Saurabh Pathak', 'saurabh.pathak@campusxchange.test', NULL, '9826000211', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-11-05 15:33:39', 'Active', 1, '2026-11-04 10:03:39', '2026-11-17 10:03:39', NULL),
(112, 'Dr. Shivam Bhatt', 'shivam.bhatt@campusxchange.test', NULL, '9826000212', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-07 15:33:39', 'Active', 1, '2026-08-06 10:03:39', '2026-08-26 10:03:39', NULL),
(113, 'Prof. Simran Nagar', 'simran.nagar@campusxchange.test', NULL, '9826000213', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-19 15:33:39', 'Active', 1, '2026-10-18 10:03:39', '2026-11-07 10:03:39', NULL),
(114, 'Prof. Siddharth Gaur', 'siddharth.gaur@campusxchange.test', NULL, '9826000214', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-16 15:33:39', 'Active', 1, '2026-08-15 10:03:39', '2026-09-04 10:03:39', NULL),
(115, 'Dr. Tanvi Shukla', 'tanvi.shukla@campusxchange.test', NULL, '9826000215', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-10-23 15:33:39', 'Active', 1, '2026-10-22 10:03:39', '2026-11-11 10:03:39', NULL),
(116, 'Prof. Tushar Dave', 'tushar.dave@campusxchange.test', NULL, '9826000216', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-20 15:33:39', 'Active', 1, '2026-09-19 10:03:39', '2026-10-09 10:03:39', NULL),
(117, 'Prof. Utkarsh Purohit', 'utkarsh.purohit@campusxchange.test', NULL, '9826000217', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-21 15:33:39', 'Active', 1, '2026-08-20 10:03:39', '2026-09-09 10:03:39', NULL),
(118, 'Prof. Vaibhav Kushwah', 'vaibhav.kushwah@campusxchange.test', NULL, '9826000218', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-08-24 15:33:39', 'Active', 1, '2026-08-23 10:03:39', '2026-09-12 10:03:39', NULL),
(119, 'Prof. Yash Upadhyay', 'yash.upadhyay@campusxchange.test', NULL, '9826000219', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-10 15:33:39', 'Active', 1, '2026-09-09 10:03:39', '2026-09-29 10:03:39', NULL),
(120, 'Dr. Zoya Jha', 'zoya.jha@campusxchange.test', NULL, '9826000220', 'Faculty', '$2y$12$mavEWc7VL5pBrrE/l5AjvewYnSYIxpSn8shvTBpiiJZJ/DQhn7viO', '2026-09-03 15:33:39', 'Active', 1, '2026-09-02 10:03:39', '2026-09-22 10:03:39', NULL),
(122, 'Anuj Kushwaha', 'nikhilr@gmail.com', NULL, '7084771753', 'Student', '$2y$10$Oxnfr/wfsoCUQhjIj2J40evT.OPdTP7nTu5u256HtaKHs0JP6zQFS', '2026-11-19 10:18:46', 'Active', 1, '2026-11-19 04:48:06', '2026-11-21 18:15:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_bans`
--

CREATE TABLE `user_bans` (
  `ban_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `ban_type` enum('Warning','Temporary','Permanent') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `status` enum('Active','Expired','Revoked') DEFAULT 'Active',
  `revoked_by` int(11) DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revocation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_bans`
--

INSERT INTO `user_bans` (`ban_id`, `user_id`, `report_id`, `admin_id`, `ban_type`, `reason`, `description`, `start_date`, `end_date`, `status`, `revoked_by`, `revoked_at`, `revocation_reason`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, 1, 1, 'Permanent', 'Scam/Fraud', 'Took advance payment and did not deliver. Permanent ban after report review.', '2026-10-30 15:12:56', NULL, 'Active', NULL, NULL, NULL, '2026-10-30 09:42:56', '2026-11-18 09:42:56', NULL),
(2, 13, 2, 9, 'Permanent', 'Harassment', 'Abusive and threatening messages to another student.', '2026-11-01 15:12:56', NULL, 'Active', NULL, NULL, NULL, '2026-11-01 09:42:56', '2026-11-18 09:42:56', NULL),
(3, 11, 3, 1, 'Temporary', 'Spam', 'Repeated promotional links in chats. 14 day suspension.', '2026-11-07 15:12:56', '2026-11-21 15:12:56', 'Active', NULL, NULL, NULL, '2026-11-07 09:42:56', '2026-11-18 09:42:56', NULL),
(4, 18, 4, 9, 'Temporary', 'Inappropriate behaviour', 'Rude language during negotiation. 7 day suspension.', '2026-11-16 15:12:56', '2026-11-23 15:12:56', 'Active', NULL, NULL, NULL, '2026-11-16 09:42:56', '2026-11-18 09:42:56', NULL),
(5, 5, 5, 1, 'Warning', 'Inappropriate behaviour', 'Warning for impolite replies.', '2026-10-25 15:12:56', NULL, 'Expired', NULL, NULL, NULL, '2026-10-25 09:42:56', '2026-11-18 09:42:56', NULL),
(6, 33, 12, 1, 'Permanent', 'Scam/Fraud', 'Did not deliver item after payment.', '2026-10-19 15:33:39', NULL, 'Active', NULL, NULL, NULL, '2026-10-19 10:03:39', '2026-11-18 10:03:39', NULL),
(7, 58, 13, 9, 'Permanent', 'Harassment', 'Abusive messages to another student.', '2026-10-23 15:33:39', NULL, 'Active', NULL, NULL, NULL, '2026-10-23 10:03:39', '2026-11-18 10:03:39', NULL),
(8, 84, 14, 1, 'Permanent', 'Scam/Fraud', 'Used fake product photos.', '2026-10-27 15:33:39', NULL, 'Active', NULL, NULL, NULL, '2026-10-27 10:03:39', '2026-11-18 10:03:39', NULL),
(9, 41, 15, 9, 'Temporary', 'Spam', 'Promotional spam in chats. 10 day suspension.', '2026-11-10 15:33:39', '2026-11-20 15:33:39', 'Active', NULL, NULL, NULL, '2026-11-10 10:03:39', '2026-11-18 10:03:39', NULL),
(10, 67, 16, 1, 'Temporary', 'Inappropriate behaviour', 'Rude language. 7 day suspension.', '2026-11-14 15:33:39', '2026-11-21 15:33:39', 'Active', NULL, NULL, NULL, '2026-11-14 10:03:39', '2026-11-18 10:03:39', NULL),
(11, 92, 17, 9, 'Temporary', 'Harassment', 'Repeated unwanted messages. 14 day suspension.', '2026-11-08 15:33:39', '2026-11-22 15:33:39', 'Active', NULL, NULL, NULL, '2026-11-08 10:03:39', '2026-11-18 10:03:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_presence`
--

CREATE TABLE `user_presence` (
  `presence_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_presence`
--

INSERT INTO `user_presence` (`presence_id`, `user_id`, `last_seen_at`, `updated_at`) VALUES
(25, 1, '2026-11-19 10:37:10', '2026-11-19 05:08:10'),
(409, 122, '2026-11-21 23:45:45', '2026-11-21 18:15:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_of_study` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `college_name` varchar(150) DEFAULT NULL,
  `graduation_year` varchar(10) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `live_photo` varchar(255) DEFAULT NULL,
  `profile_completion_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `profile_status` enum('Incomplete','Complete') NOT NULL DEFAULT 'Incomplete',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `user_id`, `profile_picture`, `phone`, `department`, `course`, `year_of_study`, `semester`, `bio`, `location`, `college_name`, `graduation_year`, `address_line`, `city`, `state`, `pincode`, `live_photo`, `profile_completion_percentage`, `profile_status`, `created_at`, `updated_at`) VALUES
(1, 1, 'cx_u01.jpg', '9826000101', 'Computer Science & Engineering', 'B.Tech CSE', '3rd Year', '6', 'Final-semester coding enthusiast. Prefers meeting near the library or the main canteen.', 'Boys Hostel A, Room 214', 'ITM University Gwalior', '2027', 'Boys Hostel A, Room 214', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 83, 'Incomplete', '2026-08-19 09:42:56', '2026-11-18 09:51:17'),
(2, 2, 'cx_u02.jpg', '9826000102', 'Electronics & Communication', 'B.Tech ECE', '2nd Year', '4', 'ECE student who loves signals and systems. Happy to hand over books at the library gate.', 'Girls Hostel B, Room 108', 'ITM University Gwalior', '2028', 'Girls Hostel B, Room 108', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-23 09:42:56', '2026-11-18 09:42:56'),
(3, 3, 'cx_u03.jpg', '9826000103', 'Computer Science & Engineering', 'B.Tech CSE', '4th Year', '8', 'Placed final-year student clearing out semester books before graduation.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-27 09:42:56', '2026-11-18 09:42:56'),
(4, 4, 'cx_u04.jpg', '9826000104', 'Civil Engineering', 'B.Tech Civil', '1st Year', '2', 'First-year civil student. Selling drawing items I no longer need.', 'Girls Hostel A, Room 301', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 301', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-31 09:42:56', '2026-11-18 09:42:56'),
(5, 5, 'cx_u05.jpg', '9826000105', 'Mechanical Engineering', 'B.Tech Mechanical', '3rd Year', '6', 'Mechanical student and robotics club member.', 'Boys Hostel C, Room 125', 'ITM University Gwalior', '2027', 'Boys Hostel C, Room 125', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-04 09:42:56', '2026-11-18 09:42:56'),
(6, 6, 'cx_u06.jpg', '9826000106', 'Computer Applications', 'BCA', '2nd Year', '4', 'BCA student, music lover. Available after 4 PM on weekdays.', 'Girls Hostel B, Room 214', 'ITM University Gwalior', '2028', 'Girls Hostel B, Room 214', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-08 09:42:56', '2026-11-18 09:42:56'),
(7, 7, 'cx_u07.jpg', '9826000107', 'Computer Science & Engineering', 'B.Tech CSE', '2nd Year', '4', 'Hostel resident, upgrading gadgets. Easy to reach on WhatsApp after class.', 'Boys Hostel A, Room 130', 'ITM University Gwalior', '2028', 'Boys Hostel A, Room 130', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-12 09:42:56', '2026-11-18 09:42:56'),
(8, 8, 'cx_u08.jpg', '9826000108', 'Computer Applications', 'MCA', '1st Year', '2', 'MCA student interested in web development and mechanical keyboards.', 'Boys Hostel D, Room 044', 'ITM University Gwalior', '2027', 'Boys Hostel D, Room 044', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-16 09:42:56', '2026-11-18 09:42:56'),
(9, 9, 'cx_u09.jpg', '9826000109', 'Electronics & Communication', 'B.Tech ECE', '2nd Year', '3', 'Embedded systems hobbyist; finished the Arduino lab course.', 'Girls Hostel A, Room 220', 'ITM University Gwalior', '2028', 'Girls Hostel A, Room 220', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-20 09:42:56', '2026-11-18 09:42:56'),
(10, 10, 'cx_u10.jpg', '9826000110', 'Computer Science & Engineering', 'Assistant Professor', NULL, NULL, 'Teaches databases and operating systems. Items can be collected from the CSE department office.', 'Faculty Residence Block 2', 'ITM University Gwalior', NULL, 'Faculty Residence Block 2', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-24 09:42:56', '2026-11-18 09:42:56'),
(11, 11, 'cx_u11.jpg', '9826000111', 'Mechanical Engineering', 'B.Tech Mechanical', '4th Year', '8', 'Final-year student shifting out of the hostel this semester.', 'Boys Hostel C, Room 301', 'ITM University Gwalior', '2026', 'Boys Hostel C, Room 301', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-28 09:42:56', '2026-11-18 09:42:56'),
(12, 12, 'cx_u12.jpg', '9826000112', 'Commerce & Management', 'B.Com', '3rd Year', '5', 'Commerce student. Selling furniture that I bought for my hostel room.', 'Girls Hostel B, Room 312', 'ITM University Gwalior', '2027', 'Girls Hostel B, Room 312', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-02 09:42:56', '2026-11-18 09:42:56'),
(13, 13, 'cx_u13.jpg', '9826000113', 'Civil Engineering', 'B.Tech Civil', '3rd Year', '6', 'Day scholar. Pickup from the main gate parking only.', 'Off-campus, Gwalior city', 'ITM University Gwalior', '2027', 'Off-campus, Gwalior city', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-06 09:42:56', '2026-11-18 09:42:56'),
(14, 14, 'cx_u14.jpg', '9826000114', 'Computer Applications', 'BCA', '1st Year', '2', 'First-year BCA student. Can meet near the hostel gate.', 'Girls Hostel A, Room 117', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 117', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-10 09:42:56', '2026-11-18 09:42:56'),
(15, 15, 'cx_u15.jpg', '9826000115', 'Applied Sciences (Physics)', 'Associate Professor', NULL, NULL, 'Physics faculty. Happy to hand over items from the department between 11 AM and 4 PM.', 'Faculty Residence Block 1', 'ITM University Gwalior', NULL, 'Faculty Residence Block 1', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-14 09:42:56', '2026-11-18 09:42:56'),
(16, 16, 'cx_u16.jpg', '9826000116', 'Electrical Engineering', 'B.Tech EE', '2nd Year', '4', 'Electrical engineering student and cricket team member.', 'Boys Hostel B, Room 207', 'ITM University Gwalior', '2028', 'Boys Hostel B, Room 207', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-18 09:42:56', '2026-11-18 09:42:56'),
(17, 17, 'cx_u17.jpg', '9826000117', 'Pharmacy', 'B.Pharm', '2nd Year', '3', 'Pharmacy student. Lab coat kept washed and ironed.', 'Girls Hostel B, Room 122', 'ITM University Gwalior', '2028', 'Girls Hostel B, Room 122', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-22 09:42:56', '2026-11-18 09:42:56'),
(18, 18, 'cx_u18.jpg', '9826000118', 'Commerce & Management', 'BBA', '2nd Year', '3', 'BBA student; active in the entrepreneurship cell.', 'Boys Hostel D, Room 118', 'ITM University Gwalior', '2028', 'Boys Hostel D, Room 118', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-26 09:42:56', '2026-11-18 09:42:56'),
(19, 19, 'cx_u19.jpg', '9826000119', 'Information Technology', 'B.Tech IT', '3rd Year', '5', 'IT student and morning runner.', 'Girls Hostel A, Room 406', 'ITM University Gwalior', '2027', 'Girls Hostel A, Room 406', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-30 09:42:56', '2026-11-18 09:42:56'),
(20, 20, 'cx_u20.jpg', '9826000120', 'Commerce & Management', 'Professor', NULL, NULL, 'Management faculty. Contact via the campus portal chat to arrange a meeting.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-03 09:42:56', '2026-11-18 09:42:56'),
(21, 21, 'cx_u021.jpg', '9826000121', 'Electronics & Communication', 'B.Tech ECE', '3rd Year', '6', 'Open to reasonable offers, items are well maintained.', 'Day scholar, Morar', 'ITM University Gwalior', '2027', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-15 10:03:39', '2026-11-18 10:03:39'),
(22, 22, 'cx_u022.jpg', '9826000122', 'Pharmacy', 'B.Pharm', '4th Year', '7', 'Prefers meeting at the main canteen after lectures.', 'Boys Hostel A, Room 258', 'ITM University Gwalior', '2026', 'Boys Hostel A, Room 258', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-03 10:03:39', '2026-11-18 10:03:39'),
(23, 23, 'cx_u023.jpg', '9826000123', 'Electronics & Communication', 'B.Tech ECE', '3rd Year', '6', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Boys Hostel A, Room 309', 'ITM University Gwalior', '2027', 'Boys Hostel A, Room 309', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-04 10:03:39', '2026-11-18 10:03:39'),
(24, 24, 'cx_u024.jpg', '9826000124', 'Computer Applications', 'MCA', '2nd Year', '3', 'Responds quickly on chat during evenings.', 'Off-campus, City Centre', 'ITM University Gwalior', '2028', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-03 10:03:39', '2026-11-18 10:03:39'),
(25, 25, 'cx_u025.jpg', '9826000125', 'Management Studies', 'MBA', '1st Year', '1', 'Decluttering my hostel room, fair prices only.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2029', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-10 10:03:39', '2026-11-18 10:03:39'),
(26, 26, 'cx_u026.jpg', '9826000126', 'Commerce & Management', 'BBA', '2nd Year', '3', 'Responds quickly on chat during evenings.', 'Off-campus, City Centre', 'ITM University Gwalior', '2028', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-24 10:03:39', '2026-11-18 10:03:39'),
(27, 27, 'cx_u027.jpg', '9826000127', 'Commerce & Management', 'BBA', '4th Year', '8', 'Prefers meeting at the main canteen after lectures.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2026', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-30 10:03:39', '2026-11-18 10:03:39'),
(28, 28, 'cx_u028.jpg', '9826000128', 'Management Studies', 'MBA', '4th Year', '7', 'Final-year student, selling things I no longer need.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-06 10:03:39', '2026-11-18 10:03:39'),
(29, 29, 'cx_u029.jpg', '9826000129', 'Applied Sciences', 'B.Sc', '4th Year', '8', 'Clearing out things before the semester ends.', 'Girls Hostel B, Room 297', 'ITM University Gwalior', '2026', 'Girls Hostel B, Room 297', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-05 10:03:39', '2026-11-18 10:03:39'),
(30, 30, 'cx_u030.jpg', '9826000130', 'Computer Science & Engineering', 'B.Tech CSE', '1st Year', '1', 'Active member of the coding club, happy to help juniors.', 'Girls Hostel A, Room 224', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 224', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-26 10:03:39', '2026-11-18 10:03:39'),
(31, 31, 'cx_u031.jpg', '9826000131', 'Electronics & Communication', 'B.Tech ECE', '4th Year', '7', 'Quiet hostel resident, easy to coordinate with.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-30 10:03:39', '2026-11-18 10:03:39'),
(32, 32, 'cx_u032.jpg', '9826000132', 'Management Studies', 'MBA', '3rd Year', '5', 'Active member of the coding club, happy to help juniors.', 'Day scholar, Morar', 'ITM University Gwalior', '2027', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-09 10:03:39', '2026-11-18 10:03:39'),
(33, 33, 'cx_u033.jpg', '9826000133', 'Electrical Engineering', 'B.Tech EE', '3rd Year', '6', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel A, Room 268', 'ITM University Gwalior', '2027', 'Girls Hostel A, Room 268', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-01 10:03:39', '2026-11-18 10:03:39'),
(34, 34, 'cx_u034.jpg', '9826000134', 'Commerce & Management', 'BBA', '4th Year', '7', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-18 10:03:39', '2026-11-18 10:03:39'),
(35, 35, 'cx_u035.jpg', '9826000135', 'Commerce & Management', 'BBA', '4th Year', '7', 'Decluttering my hostel room, fair prices only.', 'Boys Hostel A, Room 313', 'ITM University Gwalior', '2026', 'Boys Hostel A, Room 313', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-28 10:03:39', '2026-11-18 10:03:39'),
(36, 36, 'cx_u036.jpg', '9826000136', 'Computer Applications', 'MCA', '4th Year', '8', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-25 10:03:39', '2026-11-18 10:03:39'),
(37, 37, 'cx_u037.jpg', '9826000137', 'Mechanical Engineering', 'B.Tech ME', '4th Year', '7', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-18 10:03:39', '2026-11-18 10:03:39'),
(38, 38, 'cx_u038.jpg', '9826000138', 'Computer Applications', 'MCA', '1st Year', '2', 'Clearing out things before the semester ends.', 'Day scholar, Morar', 'ITM University Gwalior', '2029', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-01 10:03:39', '2026-11-18 10:03:39'),
(39, 39, 'cx_u039.jpg', '9826000139', 'Commerce & Management', 'BBA', '1st Year', '2', 'Open to reasonable offers, items are well maintained.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2029', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-31 10:03:39', '2026-11-18 10:03:39'),
(40, 40, 'cx_u040.jpg', '9826000140', 'Pharmacy', 'B.Pharm', '1st Year', '2', 'Always up for a quick exchange near the library.', 'Boys Hostel B, Room 259', 'ITM University Gwalior', '2029', 'Boys Hostel B, Room 259', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-31 10:03:39', '2026-11-18 10:03:39'),
(41, 41, 'cx_u041.jpg', '9826000141', 'Pharmacy', 'B.Pharm', '3rd Year', '5', 'Open to reasonable offers, items are well maintained.', 'Boys Hostel A, Room 181', 'ITM University Gwalior', '2027', 'Boys Hostel A, Room 181', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-19 10:03:39', '2026-11-18 10:03:39'),
(42, 42, 'cx_u042.jpg', '9826000142', 'Applied Sciences', 'B.Sc', '4th Year', '8', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel B, Room 222', 'ITM University Gwalior', '2026', 'Girls Hostel B, Room 222', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-05 10:03:39', '2026-11-18 10:03:39'),
(43, 43, 'cx_u043.jpg', '9826000143', 'Mechanical Engineering', 'B.Tech ME', '3rd Year', '6', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel A, Room 135', 'ITM University Gwalior', '2027', 'Girls Hostel A, Room 135', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-09 10:03:39', '2026-11-18 10:03:39'),
(44, 44, 'cx_u044.jpg', '9826000144', 'Electrical Engineering', 'B.Tech EE', '4th Year', '7', 'Always up for a quick exchange near the library.', 'Girls Hostel B, Room 197', 'ITM University Gwalior', '2026', 'Girls Hostel B, Room 197', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-04 10:03:39', '2026-11-18 10:03:39'),
(45, 45, 'cx_u045.jpg', '9826000145', 'Electronics & Communication', 'B.Tech ECE', '4th Year', '8', 'Responds quickly on chat during evenings.', 'Off-campus, City Centre', 'ITM University Gwalior', '2026', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-05 10:03:39', '2026-11-18 10:03:39'),
(46, 46, 'cx_u046.jpg', '9826000146', 'Computer Science & Engineering', 'B.Tech CSE', '1st Year', '1', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2029', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-22 10:03:39', '2026-11-18 10:03:39'),
(47, 47, 'cx_u047.jpg', '9826000147', 'Computer Applications', 'MCA', '1st Year', '1', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel A, Room 140', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 140', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-22 10:03:39', '2026-11-18 10:03:39'),
(48, 48, 'cx_u048.jpg', '9826000148', 'Pharmacy', 'B.Pharm', '2nd Year', '3', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Off-campus, City Centre', 'ITM University Gwalior', '2028', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-28 10:03:39', '2026-11-18 10:03:39'),
(49, 49, 'cx_u049.jpg', '9826000149', 'Applied Sciences', 'B.Sc', '2nd Year', '4', 'Always up for a quick exchange near the library.', 'Day scholar, Morar', 'ITM University Gwalior', '2028', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-25 10:03:39', '2026-11-18 10:03:39'),
(50, 50, 'cx_u050.jpg', '9826000150', 'Civil Engineering', 'B.Tech CE', '2nd Year', '4', 'Active member of the coding club, happy to help juniors.', 'Boys Hostel A, Room 216', 'ITM University Gwalior', '2028', 'Boys Hostel A, Room 216', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-14 10:03:39', '2026-11-18 10:03:39'),
(51, 51, 'cx_u051.jpg', '9826000151', 'Commerce & Management', 'BBA', '1st Year', '2', 'Quiet hostel resident, easy to coordinate with.', 'Girls Hostel B, Room 302', 'ITM University Gwalior', '2029', 'Girls Hostel B, Room 302', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-07 10:03:39', '2026-11-18 10:03:39'),
(52, 52, 'cx_u052.jpg', '9826000152', 'Mechanical Engineering', 'B.Tech ME', '2nd Year', '4', 'Final-year student, selling things I no longer need.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2028', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-12 10:03:39', '2026-11-18 10:03:39'),
(53, 53, 'cx_u053.jpg', '9826000153', 'Pharmacy', 'B.Pharm', '4th Year', '8', 'Clearing out things before the semester ends.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-11 10:03:39', '2026-11-18 10:03:39'),
(54, 54, 'cx_u054.jpg', '9826000154', 'Computer Applications', 'MCA', '2nd Year', '3', 'Final-year student, selling things I no longer need.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2028', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-15 10:03:39', '2026-11-18 10:03:39'),
(55, 55, 'cx_u055.jpg', '9826000155', 'Electrical Engineering', 'B.Tech EE', '4th Year', '8', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2026', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-17 10:03:39', '2026-11-18 10:03:39'),
(56, 56, 'cx_u056.jpg', '9826000156', 'Electronics & Communication', 'B.Tech ECE', '3rd Year', '6', 'Prefers meeting at the main canteen after lectures.', 'Girls Hostel B, Room 102', 'ITM University Gwalior', '2027', 'Girls Hostel B, Room 102', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-06 10:03:39', '2026-11-18 10:03:39'),
(57, 57, 'cx_u057.jpg', '9826000157', 'Commerce & Management', 'BBA', '3rd Year', '5', 'Responds quickly on chat during evenings.', 'Girls Hostel A, Room 107', 'ITM University Gwalior', '2027', 'Girls Hostel A, Room 107', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-04 10:03:39', '2026-11-18 10:03:39'),
(58, 58, 'cx_u058.jpg', '9826000158', 'Mechanical Engineering', 'B.Tech ME', '3rd Year', '6', 'Quiet hostel resident, easy to coordinate with.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2027', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-25 10:03:39', '2026-11-18 10:03:39'),
(59, 59, 'cx_u059.jpg', '9826000159', 'Applied Sciences', 'B.Sc', '2nd Year', '3', 'Quiet hostel resident, easy to coordinate with.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2028', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-06 10:03:39', '2026-11-18 10:03:39'),
(60, 60, 'cx_u060.jpg', '9826000160', 'Mechanical Engineering', 'B.Tech ME', '1st Year', '1', 'Clearing out things before the semester ends.', 'Day scholar, Morar', 'ITM University Gwalior', '2029', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-14 10:03:39', '2026-11-18 10:03:39'),
(61, 61, 'cx_u061.jpg', '9826000161', 'Electronics & Communication', 'B.Tech ECE', '1st Year', '1', 'Prefers meeting at the main canteen after lectures.', 'Girls Hostel A, Room 266', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 266', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-01 10:03:39', '2026-11-18 10:03:39'),
(62, 62, 'cx_u062.jpg', '9826000162', 'Pharmacy', 'B.Pharm', '3rd Year', '5', 'Responds quickly on chat during evenings.', 'Boys Hostel B, Room 175', 'ITM University Gwalior', '2027', 'Boys Hostel B, Room 175', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-07 10:03:39', '2026-11-18 10:03:39'),
(63, 63, 'cx_u063.jpg', '9826000163', 'Mechanical Engineering', 'B.Tech ME', '3rd Year', '5', 'Decluttering my hostel room, fair prices only.', 'Boys Hostel A, Room 206', 'ITM University Gwalior', '2027', 'Boys Hostel A, Room 206', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-03 10:03:39', '2026-11-18 10:03:39'),
(64, 64, 'cx_u064.jpg', '9826000164', 'Management Studies', 'MBA', '1st Year', '2', 'Decluttering my hostel room, fair prices only.', 'Girls Hostel B, Room 106', 'ITM University Gwalior', '2029', 'Girls Hostel B, Room 106', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-11 10:03:39', '2026-11-18 10:03:39'),
(65, 65, 'cx_u065.jpg', '9826000165', 'Electronics & Communication', 'B.Tech ECE', '1st Year', '1', 'Open to reasonable offers, items are well maintained.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2029', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-09 10:03:39', '2026-11-18 10:03:39'),
(66, 66, 'cx_u066.jpg', '9826000166', 'Applied Sciences', 'B.Sc', '1st Year', '1', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Girls Hostel B, Room 253', 'ITM University Gwalior', '2029', 'Girls Hostel B, Room 253', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-24 10:03:39', '2026-11-18 10:03:39'),
(67, 67, 'cx_u067.jpg', '9826000167', 'Management Studies', 'MBA', '1st Year', '1', 'Final-year student, selling things I no longer need.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2029', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-31 10:03:39', '2026-11-18 10:03:39'),
(68, 68, 'cx_u068.jpg', '9826000168', 'Electrical Engineering', 'B.Tech EE', '3rd Year', '6', 'Always up for a quick exchange near the library.', 'Day scholar, Morar', 'ITM University Gwalior', '2027', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-16 10:03:39', '2026-11-18 10:03:39'),
(69, 69, 'cx_u069.jpg', '9826000169', 'Electrical Engineering', 'B.Tech EE', '3rd Year', '5', 'Final-year student, selling things I no longer need.', 'Boys Hostel B, Room 177', 'ITM University Gwalior', '2027', 'Boys Hostel B, Room 177', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-26 10:03:39', '2026-11-18 10:03:39'),
(70, 70, 'cx_u070.jpg', '9826000170', 'Computer Applications', 'MCA', '1st Year', '1', 'Prefers meeting at the main canteen after lectures.', 'Boys Hostel A, Room 285', 'ITM University Gwalior', '2029', 'Boys Hostel A, Room 285', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-30 10:03:39', '2026-11-18 10:03:39'),
(71, 71, 'cx_u071.jpg', '9826000171', 'Mechanical Engineering', 'B.Tech ME', '1st Year', '2', 'Responds quickly on chat during evenings.', 'Girls Hostel A, Room 234', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 234', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-25 10:03:39', '2026-11-18 10:03:39'),
(72, 72, 'cx_u072.jpg', '9826000172', 'Applied Sciences', 'B.Sc', '3rd Year', '6', 'Final-year student, selling things I no longer need.', 'Girls Hostel A, Room 192', 'ITM University Gwalior', '2027', 'Girls Hostel A, Room 192', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-19 10:03:39', '2026-11-18 10:03:39'),
(73, 73, 'cx_u073.jpg', '9826000173', 'Management Studies', 'MBA', '4th Year', '8', 'Active member of the coding club, happy to help juniors.', 'Day scholar, Morar', 'ITM University Gwalior', '2026', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-21 10:03:39', '2026-11-18 10:03:39'),
(74, 74, 'cx_u074.jpg', '9826000174', 'Mechanical Engineering', 'B.Tech ME', '2nd Year', '4', 'Decluttering my hostel room, fair prices only.', 'Day scholar, Morar', 'ITM University Gwalior', '2028', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-03 10:03:39', '2026-11-18 10:03:39'),
(75, 75, 'cx_u075.jpg', '9826000175', 'Mechanical Engineering', 'B.Tech ME', '2nd Year', '4', 'Quiet hostel resident, easy to coordinate with.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2028', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-24 10:03:39', '2026-11-18 10:03:39'),
(76, 76, 'cx_u076.jpg', '9826000176', 'Commerce & Management', 'BBA', '4th Year', '7', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel A, Room 261', 'ITM University Gwalior', '2026', 'Girls Hostel A, Room 261', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-10 10:03:39', '2026-11-18 10:03:39'),
(77, 77, 'cx_u077.jpg', '9826000177', 'Electronics & Communication', 'B.Tech ECE', '2nd Year', '4', 'Responds quickly on chat during evenings.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2028', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-02 10:03:39', '2026-11-18 10:03:39'),
(78, 78, 'cx_u078.jpg', '9826000178', 'Pharmacy', 'B.Pharm', '3rd Year', '6', 'Active member of the coding club, happy to help juniors.', 'Boys Hostel A, Room 261', 'ITM University Gwalior', '2027', 'Boys Hostel A, Room 261', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-17 10:03:39', '2026-11-18 10:03:39'),
(79, 79, 'cx_u079.jpg', '9826000179', 'Commerce & Management', 'BBA', '1st Year', '2', 'Final-year student, selling things I no longer need.', 'Girls Hostel A, Room 291', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 291', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-22 10:03:39', '2026-11-18 10:03:39'),
(80, 80, 'cx_u080.jpg', '9826000180', 'Computer Applications', 'MCA', '2nd Year', '3', 'Responds quickly on chat during evenings.', 'Girls Hostel B, Room 196', 'ITM University Gwalior', '2028', 'Girls Hostel B, Room 196', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-25 10:03:39', '2026-11-18 10:03:39'),
(81, 81, 'cx_u081.jpg', '9826000181', 'Commerce & Management', 'BBA', '4th Year', '7', 'Active member of the coding club, happy to help juniors.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2026', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-01 10:03:39', '2026-11-18 10:03:39'),
(82, 82, 'cx_u082.jpg', '9826000182', 'Mechanical Engineering', 'B.Tech ME', '4th Year', '8', 'Quiet hostel resident, easy to coordinate with.', 'Boys Hostel A, Room 191', 'ITM University Gwalior', '2026', 'Boys Hostel A, Room 191', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-12 10:03:39', '2026-11-18 10:03:39'),
(83, 83, 'cx_u083.jpg', '9826000183', 'Management Studies', 'MBA', '1st Year', '2', 'Responds quickly on chat during evenings.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2029', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-22 10:03:39', '2026-11-18 10:03:39'),
(84, 84, 'cx_u084.jpg', '9826000184', 'Pharmacy', 'B.Pharm', '3rd Year', '6', 'Quiet hostel resident, easy to coordinate with.', 'Boys Hostel B, Room 105', 'ITM University Gwalior', '2027', 'Boys Hostel B, Room 105', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-09 10:03:39', '2026-11-18 10:03:39'),
(85, 85, 'cx_u085.jpg', '9826000185', 'Civil Engineering', 'B.Tech CE', '3rd Year', '6', 'Quiet hostel resident, easy to coordinate with.', 'Day scholar, Morar', 'ITM University Gwalior', '2027', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-23 10:03:39', '2026-11-18 10:03:39'),
(86, 86, 'cx_u086.jpg', '9826000186', 'Pharmacy', 'B.Pharm', '2nd Year', '4', 'Final-year student, selling things I no longer need.', 'Girls Hostel A, Room 288', 'ITM University Gwalior', '2028', 'Girls Hostel A, Room 288', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-25 10:03:39', '2026-11-18 10:03:39'),
(87, 87, 'cx_u087.jpg', '9826000187', 'Computer Applications', 'MCA', '3rd Year', '5', 'Decluttering my hostel room, fair prices only.', 'Girls Hostel B, Room 142', 'ITM University Gwalior', '2027', 'Girls Hostel B, Room 142', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-31 10:03:39', '2026-11-18 10:03:39'),
(88, 88, 'cx_u088.jpg', '9826000188', 'Electrical Engineering', 'B.Tech EE', '3rd Year', '5', 'Active member of the coding club, happy to help juniors.', 'Day scholar, Morar', 'ITM University Gwalior', '2027', 'Day scholar, Morar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-29 10:03:39', '2026-11-18 10:03:39'),
(89, 89, 'cx_u089.jpg', '9826000189', 'Computer Science & Engineering', 'B.Tech CSE', '4th Year', '7', 'Quiet hostel resident, easy to coordinate with.', 'Girls Hostel A, Room 285', 'ITM University Gwalior', '2026', 'Girls Hostel A, Room 285', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-31 10:03:39', '2026-11-18 10:03:39'),
(90, 90, 'cx_u090.jpg', '9826000190', 'Computer Science & Engineering', 'B.Tech CSE', '2nd Year', '3', 'Always up for a quick exchange near the library.', 'Girls Hostel A, Room 142', 'ITM University Gwalior', '2028', 'Girls Hostel A, Room 142', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-18 10:03:39', '2026-11-18 10:03:39'),
(91, 91, 'cx_u091.jpg', '9826000191', 'Electronics & Communication', 'B.Tech ECE', '1st Year', '1', 'Decluttering my hostel room, fair prices only.', 'Off-campus, City Centre', 'ITM University Gwalior', '2029', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-29 10:03:39', '2026-11-18 10:03:39'),
(92, 92, 'cx_u092.jpg', '9826000192', 'Civil Engineering', 'B.Tech CE', '2nd Year', '4', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Boys Hostel B, Room 184', 'ITM University Gwalior', '2028', 'Boys Hostel B, Room 184', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-03 10:03:39', '2026-11-18 10:03:39'),
(93, 93, 'cx_u093.jpg', '9826000193', 'Electronics & Communication', 'B.Tech ECE', '1st Year', '1', 'Prefers meeting at the main canteen after lectures.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2029', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-31 10:03:39', '2026-11-18 10:03:39'),
(94, 94, 'cx_u094.jpg', '9826000194', 'Computer Applications', 'MCA', '4th Year', '7', 'Responds quickly on chat during evenings.', 'Girls Hostel B, Room 313', 'ITM University Gwalior', '2026', 'Girls Hostel B, Room 313', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-17 10:03:39', '2026-11-18 10:03:39'),
(95, 95, 'cx_u095.jpg', '9826000195', 'Commerce & Management', 'BBA', '3rd Year', '6', 'Loves sports and the outdoors. Meet me at the ground gate.', 'Boys Hostel B, Room 307', 'ITM University Gwalior', '2027', 'Boys Hostel B, Room 307', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-06 10:03:39', '2026-11-18 10:03:39'),
(96, 96, 'cx_u096.jpg', '9826000196', 'Electrical Engineering', 'B.Tech EE', '3rd Year', '6', 'Final-year student, selling things I no longer need.', 'Off-campus, City Centre', 'ITM University Gwalior', '2027', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-06 10:03:39', '2026-11-18 10:03:39'),
(97, 97, 'cx_u097.jpg', '9826000197', 'Pharmacy', 'B.Pharm', '3rd Year', '5', 'Final-year student, selling things I no longer need.', 'Day scholar, Lashkar', 'ITM University Gwalior', '2027', 'Day scholar, Lashkar', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-01 10:03:39', '2026-11-18 10:03:39'),
(98, 98, 'cx_u098.jpg', '9826000198', 'Computer Applications', 'MCA', '2nd Year', '4', 'Responds quickly on chat during evenings.', 'Girls Hostel B, Room 277', 'ITM University Gwalior', '2028', 'Girls Hostel B, Room 277', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-03 10:03:39', '2026-11-18 10:03:39'),
(99, 99, 'cx_u099.jpg', '9826000199', 'Computer Applications', 'MCA', '4th Year', '7', 'Open to reasonable offers, items are well maintained.', 'Girls Hostel A, Room 214', 'ITM University Gwalior', '2026', 'Girls Hostel A, Room 214', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-07-29 10:03:39', '2026-11-18 10:03:39'),
(100, 100, 'cx_u100.jpg', '9826000200', 'Mechanical Engineering', 'B.Tech ME', '4th Year', '7', 'Decluttering my hostel room, fair prices only.', 'Boys Hostel B, Room 182', 'ITM University Gwalior', '2026', 'Boys Hostel B, Room 182', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-19 10:03:39', '2026-11-18 10:03:39'),
(101, 101, 'cx_u101.jpg', '9826000201', 'Commerce & Management', 'BBA', '3rd Year', '6', 'Active member of the coding club, happy to help juniors.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2027', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-24 10:03:39', '2026-11-18 10:03:39'),
(102, 102, 'cx_u102.jpg', '9826000202', 'Computer Applications', 'MCA', '2nd Year', '4', 'Open to reasonable offers, items are well maintained.', 'Off-campus, Turari Road', 'ITM University Gwalior', '2028', 'Off-campus, Turari Road', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-07 10:03:39', '2026-11-18 10:03:39'),
(103, 103, 'cx_u103.jpg', '9826000203', 'Commerce & Management', 'BBA', '4th Year', '7', 'Final-year student, selling things I no longer need.', 'Off-campus, City Centre', 'ITM University Gwalior', '2026', 'Off-campus, City Centre', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-02 10:03:39', '2026-11-18 10:03:39'),
(104, 104, 'cx_u104.jpg', '9826000204', 'Computer Applications', 'MCA', '1st Year', '1', 'Responds quickly on chat during evenings.', 'Girls Hostel B, Room 209', 'ITM University Gwalior', '2029', 'Girls Hostel B, Room 209', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-14 10:03:39', '2026-11-18 10:03:39'),
(105, 105, 'cx_u105.jpg', '9826000205', 'Computer Applications', 'MCA', '1st Year', '1', 'Decluttering my hostel room, fair prices only.', 'Girls Hostel A, Room 103', 'ITM University Gwalior', '2029', 'Girls Hostel A, Room 103', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-26 10:03:39', '2026-11-18 10:03:39'),
(106, 106, 'cx_u106.jpg', '9826000206', 'Pharmacy', 'Associate Professor', NULL, NULL, 'Open to reasonable offers, items are well maintained.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-25 10:03:39', '2026-11-18 10:03:39'),
(107, 107, 'cx_u107.jpg', '9826000207', 'Applied Sciences', 'Lecturer', NULL, NULL, 'Responds quickly on chat during evenings.', 'Faculty Residence Block 4', 'ITM University Gwalior', NULL, 'Faculty Residence Block 4', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-13 10:03:39', '2026-11-18 10:03:39'),
(108, 108, 'cx_u108.jpg', '9826000208', 'Computer Science & Engineering', 'Associate Professor', NULL, NULL, 'Decluttering my hostel room, fair prices only.', 'Faculty Residence Block 1', 'ITM University Gwalior', NULL, 'Faculty Residence Block 1', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-20 10:03:39', '2026-11-18 10:03:39'),
(109, 109, 'cx_u109.jpg', '9826000209', 'Computer Applications', 'Associate Professor', NULL, NULL, 'Quiet hostel resident, easy to coordinate with.', 'Faculty Residence Block 4', 'ITM University Gwalior', NULL, 'Faculty Residence Block 4', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-18 10:03:39', '2026-11-18 10:03:39'),
(110, 110, 'cx_u110.jpg', '9826000210', 'Electrical Engineering', 'Assistant Professor', NULL, NULL, 'Quiet hostel resident, easy to coordinate with.', 'Faculty Residence Block 2', 'ITM University Gwalior', NULL, 'Faculty Residence Block 2', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-14 10:03:39', '2026-11-18 10:03:39'),
(111, 111, 'cx_u111.jpg', '9826000211', 'Computer Science & Engineering', 'Associate Professor', NULL, NULL, 'Active member of the coding club, happy to help juniors.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-11-04 10:03:39', '2026-11-18 10:03:39'),
(112, 112, 'cx_u112.jpg', '9826000212', 'Commerce & Management', 'Associate Professor', NULL, NULL, 'Prefers meeting at the main canteen after lectures.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-06 10:03:39', '2026-11-18 10:03:39'),
(113, 113, 'cx_u113.jpg', '9826000213', 'Applied Sciences', 'Associate Professor', NULL, NULL, 'Always up for a quick exchange near the library.', 'Faculty Residence Block 1', 'ITM University Gwalior', NULL, 'Faculty Residence Block 1', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-18 10:03:39', '2026-11-18 10:03:39'),
(114, 114, 'cx_u114.jpg', '9826000214', 'Electrical Engineering', 'Assistant Professor', NULL, NULL, 'Quiet hostel resident, easy to coordinate with.', 'Faculty Residence Block 2', 'ITM University Gwalior', NULL, 'Faculty Residence Block 2', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-15 10:03:39', '2026-11-18 10:03:39'),
(115, 115, 'cx_u115.jpg', '9826000215', 'Pharmacy', 'Lecturer', NULL, NULL, 'Responds quickly on chat during evenings.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-10-22 10:03:39', '2026-11-18 10:03:39'),
(116, 116, 'cx_u116.jpg', '9826000216', 'Computer Applications', 'Lecturer', NULL, NULL, 'Loves sports and the outdoors. Meet me at the ground gate.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-19 10:03:39', '2026-11-18 10:03:39'),
(117, 117, 'cx_u117.jpg', '9826000217', 'Computer Applications', 'Assistant Professor', NULL, NULL, 'Clearing out things before the semester ends.', 'Faculty Residence Block 4', 'ITM University Gwalior', NULL, 'Faculty Residence Block 4', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-20 10:03:39', '2026-11-18 10:03:39'),
(118, 118, 'cx_u118.jpg', '9826000218', 'Computer Science & Engineering', 'Professor', NULL, NULL, 'Loves sports and the outdoors. Meet me at the ground gate.', 'Faculty Residence Block 3', 'ITM University Gwalior', NULL, 'Faculty Residence Block 3', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-08-23 10:03:39', '2026-11-18 10:03:39'),
(119, 119, 'cx_u119.jpg', '9826000219', 'Civil Engineering', 'Professor', NULL, NULL, 'Decluttering my hostel room, fair prices only.', 'Faculty Residence Block 4', 'ITM University Gwalior', NULL, 'Faculty Residence Block 4', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-09 10:03:39', '2026-11-18 10:03:39'),
(120, 120, 'cx_u120.jpg', '9826000220', 'Civil Engineering', 'Lecturer', NULL, NULL, 'Quiet hostel resident, easy to coordinate with.', 'Faculty Residence Block 1', 'ITM University Gwalior', NULL, 'Faculty Residence Block 1', 'Gwalior', 'Madhya Pradesh', '475001', NULL, 100, 'Complete', '2026-09-02 10:03:39', '2026-11-18 10:03:39'),
(144, 122, NULL, '7084771753', NULL, 'MCA', NULL, NULL, NULL, 'Orai, Uttar Pradesh', 'Itm University', '2027', '3375, Shivpuri Patel Nagar', 'Orai', 'Uttar Pradesh', '285001', 'abda75045a5ea9e92abd3220.jpg', 100, 'Complete', '2026-11-19 04:48:06', '2026-11-21 17:31:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_verification`
--

CREATE TABLE `user_verification` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_type` enum('Student ID','Faculty ID') DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `college_id_front_image` varchar(255) DEFAULT NULL,
  `college_id_back_image` varchar(255) DEFAULT NULL,
  `verification_status` enum('Not Submitted','Pending','Approved','Rejected') DEFAULT 'Not Submitted',
  `submitted_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_verification`
--

INSERT INTO `user_verification` (`verification_id`, `user_id`, `id_type`, `id_number`, `college_id_front_image`, `college_id_back_image`, `verification_status`, `submitted_at`, `verified_by`, `verified_at`, `rejection_reason`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Student ID', 'ITM23CS014', 'cx_v01_front.jpg', 'cx_v01_back.jpg', 'Approved', '2026-08-20 15:12:56', 1, '2026-08-21 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(2, 2, 'Student ID', 'ITM24EC031', 'cx_v02_front.jpg', 'cx_v02_back.jpg', 'Approved', '2026-08-24 15:12:56', 9, '2026-08-25 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(3, 3, 'Student ID', 'ITM22CS007', 'cx_v03_front.jpg', 'cx_v03_back.jpg', 'Approved', '2026-08-28 15:12:56', 1, '2026-08-29 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(4, 4, 'Student ID', 'ITM25CE042', 'cx_v04_front.jpg', 'cx_v04_back.jpg', 'Approved', '2026-09-01 15:12:56', 9, '2026-09-02 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(5, 5, 'Student ID', 'ITM23ME019', 'cx_v05_front.jpg', 'cx_v05_back.jpg', 'Approved', '2026-09-05 15:12:56', 1, '2026-09-06 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(6, 6, 'Student ID', 'ITM24CA022', 'cx_v06_front.jpg', 'cx_v06_back.jpg', 'Approved', '2026-09-09 15:12:56', 9, '2026-09-10 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(7, 7, 'Student ID', 'ITM24CS058', 'cx_v07_front.jpg', 'cx_v07_back.jpg', 'Approved', '2026-09-13 15:12:56', 1, '2026-09-14 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(8, 8, 'Student ID', 'ITM25MC011', 'cx_v08_front.jpg', 'cx_v08_back.jpg', 'Approved', '2026-09-17 15:12:56', 9, '2026-09-18 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(9, 9, 'Student ID', 'ITM24EC009', 'cx_v09_front.jpg', 'cx_v09_back.jpg', 'Approved', '2026-09-21 15:12:56', 1, '2026-09-22 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(10, 10, 'Faculty ID', 'FAC-CSE-0112', 'cx_v10_front.jpg', 'cx_v10_back.jpg', 'Approved', '2026-09-25 15:12:56', 9, '2026-09-26 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(11, 11, 'Student ID', 'ITM22ME035', 'cx_v11_front.jpg', 'cx_v11_back.jpg', 'Approved', '2026-09-29 15:12:56', 1, '2026-09-30 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(12, 12, 'Student ID', 'ITM23BC017', 'cx_v12_front.jpg', 'cx_v12_back.jpg', 'Approved', '2026-10-03 15:12:56', 9, '2026-10-04 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(13, 13, 'Student ID', 'ITM23CE026', 'cx_v13_front.jpg', 'cx_v13_back.jpg', 'Approved', '2026-10-07 15:12:56', 1, '2026-10-08 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(14, 14, 'Student ID', 'ITM25CA047', 'cx_v14_front.jpg', 'cx_v14_back.jpg', 'Approved', '2026-10-11 15:12:56', 9, '2026-10-12 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(15, 15, 'Faculty ID', 'FAC-PHY-0068', 'cx_v15_front.jpg', 'cx_v15_back.jpg', 'Approved', '2026-10-15 15:12:56', 1, '2026-10-16 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(16, 16, 'Student ID', 'ITM24EE038', 'cx_v16_front.jpg', 'cx_v16_back.jpg', 'Approved', '2026-10-19 15:12:56', 9, '2026-10-20 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(17, 17, 'Student ID', 'ITM24PH013', 'cx_v17_front.jpg', 'cx_v17_back.jpg', 'Approved', '2026-10-23 15:12:56', 1, '2026-10-24 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(18, 18, 'Student ID', 'ITM24BB029', 'cx_v18_front.jpg', 'cx_v18_back.jpg', 'Approved', '2026-10-27 15:12:56', 9, '2026-10-28 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(19, 19, 'Student ID', 'ITM23IT044', 'cx_v19_front.jpg', 'cx_v19_back.jpg', 'Approved', '2026-10-31 15:12:56', 1, '2026-11-01 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(20, 20, 'Faculty ID', 'FAC-MGT-0031', 'cx_v20_front.jpg', 'cx_v20_back.jpg', 'Approved', '2026-11-04 15:12:56', 9, '2026-11-05 15:12:56', NULL, '2026-11-18 09:42:56', '2026-11-18 09:42:56', NULL),
(21, 21, 'Student ID', 'ITM23EC214', 'cx_v021_front.jpg', 'cx_v021_back.jpg', 'Approved', '2026-08-16 15:33:39', 1, '2026-08-17 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(22, 22, 'Student ID', 'ITM22PH222', 'cx_v022_front.jpg', 'cx_v022_back.jpg', 'Approved', '2026-09-04 15:33:39', 9, '2026-09-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(23, 23, 'Student ID', 'ITM23EC330', 'cx_v023_front.jpg', 'cx_v023_back.jpg', 'Approved', '2026-08-05 15:33:39', 1, '2026-08-06 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(24, 24, 'Student ID', 'ITM24CA228', 'cx_v024_front.jpg', 'cx_v024_back.jpg', 'Approved', '2026-10-04 15:33:39', 9, '2026-10-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(25, 25, 'Student ID', 'ITM25MB145', 'cx_v025_front.jpg', 'cx_v025_back.jpg', 'Approved', '2026-09-11 15:33:39', 1, '2026-09-12 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(26, 26, 'Student ID', 'ITM24BB112', 'cx_v026_front.jpg', 'cx_v026_back.jpg', 'Approved', '2026-08-25 15:33:39', 9, '2026-08-26 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(27, 27, 'Student ID', 'ITM22BB304', 'cx_v027_front.jpg', 'cx_v027_back.jpg', 'Approved', '2026-10-31 15:33:39', 1, '2026-11-01 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(28, 28, 'Student ID', 'ITM22MB368', 'cx_v028_front.jpg', 'cx_v028_back.jpg', 'Approved', '2026-09-07 15:33:39', 9, '2026-09-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(29, 29, 'Student ID', 'ITM22SC394', 'cx_v029_front.jpg', 'cx_v029_back.jpg', 'Approved', '2026-09-06 15:33:39', 1, '2026-09-07 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(30, 30, 'Student ID', 'ITM25CS340', 'cx_v030_front.jpg', 'cx_v030_back.jpg', 'Approved', '2026-08-27 15:33:39', 9, '2026-08-28 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(31, 31, 'Student ID', 'ITM22EC465', 'cx_v031_front.jpg', 'cx_v031_back.jpg', 'Approved', '2026-10-01 15:33:39', 1, '2026-10-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(32, 32, 'Student ID', 'ITM23MB305', 'cx_v032_front.jpg', 'cx_v032_back.jpg', 'Approved', '2026-09-10 15:33:39', 9, '2026-09-11 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(33, 33, 'Student ID', 'ITM23EE116', 'cx_v033_front.jpg', 'cx_v033_back.jpg', 'Approved', '2026-10-02 15:33:39', 1, '2026-10-03 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(34, 34, 'Student ID', 'ITM22BB202', 'cx_v034_front.jpg', 'cx_v034_back.jpg', 'Approved', '2026-08-19 15:33:39', 9, '2026-08-20 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(35, 35, 'Student ID', 'ITM22BB385', 'cx_v035_front.jpg', 'cx_v035_back.jpg', 'Approved', '2026-09-29 15:33:39', 1, '2026-09-30 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(36, 36, 'Student ID', 'ITM22CA324', 'cx_v036_front.jpg', 'cx_v036_back.jpg', 'Approved', '2026-09-26 15:33:39', 9, '2026-09-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(37, 37, 'Student ID', 'ITM22ME132', 'cx_v037_front.jpg', 'cx_v037_back.jpg', 'Approved', '2026-10-19 15:33:39', 1, '2026-10-20 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(38, 38, 'Student ID', 'ITM25CA482', 'cx_v038_front.jpg', 'cx_v038_back.jpg', 'Approved', '2026-11-02 15:33:39', 9, '2026-11-03 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(39, 39, 'Student ID', 'ITM25BB100', 'cx_v039_front.jpg', 'cx_v039_back.jpg', 'Approved', '2026-09-01 15:33:39', 1, '2026-09-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(40, 40, 'Student ID', 'ITM25PH226', 'cx_v040_front.jpg', 'cx_v040_back.jpg', 'Approved', '2026-08-01 15:33:39', 9, '2026-08-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(41, 41, 'Student ID', 'ITM23PH179', 'cx_v041_front.jpg', 'cx_v041_back.jpg', 'Approved', '2026-10-20 15:33:39', 1, '2026-10-21 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(42, 42, 'Student ID', 'ITM22SC379', 'cx_v042_front.jpg', 'cx_v042_back.jpg', 'Approved', '2026-08-06 15:33:39', 9, '2026-08-07 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(43, 43, 'Student ID', 'ITM23ME313', 'cx_v043_front.jpg', 'cx_v043_back.jpg', 'Approved', '2026-10-10 15:33:39', 1, '2026-10-11 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(44, 44, 'Student ID', 'ITM22EE176', 'cx_v044_front.jpg', 'cx_v044_back.jpg', 'Approved', '2026-09-05 15:33:39', 9, '2026-09-06 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(45, 45, 'Student ID', 'ITM22EC126', 'cx_v045_front.jpg', 'cx_v045_back.jpg', 'Approved', '2026-09-06 15:33:39', 1, '2026-09-07 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(46, 46, 'Student ID', 'ITM25CS267', 'cx_v046_front.jpg', 'cx_v046_back.jpg', 'Approved', '2026-09-23 15:33:39', 9, '2026-09-24 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(47, 47, 'Student ID', 'ITM25CA338', 'cx_v047_front.jpg', 'cx_v047_back.jpg', 'Approved', '2026-08-23 15:33:39', 1, '2026-08-24 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(48, 48, 'Student ID', 'ITM24PH419', 'cx_v048_front.jpg', 'cx_v048_back.jpg', 'Approved', '2026-10-29 15:33:39', 9, '2026-10-30 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(49, 49, 'Student ID', 'ITM24SC380', 'cx_v049_front.jpg', 'cx_v049_back.jpg', 'Approved', '2026-08-26 15:33:39', 1, '2026-08-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(50, 50, 'Student ID', 'ITM24CE431', 'cx_v050_front.jpg', 'cx_v050_back.jpg', 'Approved', '2026-10-15 15:33:39', 9, '2026-10-16 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(51, 51, 'Student ID', 'ITM25BB430', 'cx_v051_front.jpg', 'cx_v051_back.jpg', 'Approved', '2026-11-08 15:33:39', 1, '2026-11-09 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(52, 52, 'Student ID', 'ITM24ME299', 'cx_v052_front.jpg', 'cx_v052_back.jpg', 'Approved', '2026-09-13 15:33:39', 9, '2026-09-14 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(53, 53, 'Student ID', 'ITM22PH296', 'cx_v053_front.jpg', 'cx_v053_back.jpg', 'Approved', '2026-10-12 15:33:39', 1, '2026-10-13 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(54, 54, 'Student ID', 'ITM24CA337', 'cx_v054_front.jpg', 'cx_v054_back.jpg', 'Approved', '2026-10-16 15:33:39', 9, '2026-10-17 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(55, 55, 'Student ID', 'ITM22EE266', 'cx_v055_front.jpg', 'cx_v055_back.jpg', 'Approved', '2026-09-18 15:33:39', 1, '2026-09-19 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(56, 56, 'Student ID', 'ITM23EC209', 'cx_v056_front.jpg', 'cx_v056_back.jpg', 'Approved', '2026-09-07 15:33:39', 9, '2026-09-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(57, 57, 'Student ID', 'ITM23BB379', 'cx_v057_front.jpg', 'cx_v057_back.jpg', 'Approved', '2026-09-05 15:33:39', 1, '2026-09-06 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(58, 58, 'Student ID', 'ITM23ME156', 'cx_v058_front.jpg', 'cx_v058_back.jpg', 'Approved', '2026-10-26 15:33:39', 9, '2026-10-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(59, 59, 'Student ID', 'ITM24SC388', 'cx_v059_front.jpg', 'cx_v059_back.jpg', 'Approved', '2026-09-07 15:33:39', 1, '2026-09-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(60, 60, 'Student ID', 'ITM25ME149', 'cx_v060_front.jpg', 'cx_v060_back.jpg', 'Approved', '2026-10-15 15:33:39', 9, '2026-10-16 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(61, 61, 'Student ID', 'ITM25EC469', 'cx_v061_front.jpg', 'cx_v061_back.jpg', 'Approved', '2026-08-02 15:33:39', 1, '2026-08-03 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(62, 62, 'Student ID', 'ITM23PH401', 'cx_v062_front.jpg', 'cx_v062_back.jpg', 'Approved', '2026-10-08 15:33:39', 9, '2026-10-09 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(63, 63, 'Student ID', 'ITM23ME132', 'cx_v063_front.jpg', 'cx_v063_back.jpg', 'Approved', '2026-08-04 15:33:39', 1, '2026-08-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(64, 64, 'Student ID', 'ITM25MB149', 'cx_v064_front.jpg', 'cx_v064_back.jpg', 'Approved', '2026-08-12 15:33:39', 9, '2026-08-13 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(65, 65, 'Student ID', 'ITM25EC261', 'cx_v065_front.jpg', 'cx_v065_back.jpg', 'Approved', '2026-09-10 15:33:39', 1, '2026-09-11 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(66, 66, 'Student ID', 'ITM25SC261', 'cx_v066_front.jpg', 'cx_v066_back.jpg', 'Approved', '2026-10-25 15:33:39', 9, '2026-10-26 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(67, 67, 'Student ID', 'ITM25MB356', 'cx_v067_front.jpg', 'cx_v067_back.jpg', 'Approved', '2026-09-01 15:33:39', 1, '2026-09-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(68, 68, 'Student ID', 'ITM23EE476', 'cx_v068_front.jpg', 'cx_v068_back.jpg', 'Approved', '2026-08-17 15:33:39', 9, '2026-08-18 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(69, 69, 'Student ID', 'ITM23EE472', 'cx_v069_front.jpg', 'cx_v069_back.jpg', 'Approved', '2026-07-27 15:33:39', 1, '2026-07-28 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(70, 70, 'Student ID', 'ITM25CA201', 'cx_v070_front.jpg', 'cx_v070_back.jpg', 'Approved', '2026-10-01 15:33:39', 9, '2026-10-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(71, 71, 'Student ID', 'ITM25ME119', 'cx_v071_front.jpg', 'cx_v071_back.jpg', 'Approved', '2026-09-26 15:33:39', 1, '2026-09-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(72, 72, 'Student ID', 'ITM23SC317', 'cx_v072_front.jpg', 'cx_v072_back.jpg', 'Approved', '2026-08-20 15:33:39', 9, '2026-08-21 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(73, 73, 'Student ID', 'ITM22MB497', 'cx_v073_front.jpg', 'cx_v073_back.jpg', 'Approved', '2026-09-22 15:33:39', 1, '2026-09-23 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(74, 74, 'Student ID', 'ITM24ME438', 'cx_v074_front.jpg', 'cx_v074_back.jpg', 'Approved', '2026-09-04 15:33:39', 9, '2026-09-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(75, 75, 'Student ID', 'ITM24ME108', 'cx_v075_front.jpg', 'cx_v075_back.jpg', 'Approved', '2026-08-25 15:33:39', 1, '2026-08-26 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(76, 76, 'Student ID', 'ITM22BB261', 'cx_v076_front.jpg', 'cx_v076_back.jpg', 'Approved', '2026-08-11 15:33:39', 9, '2026-08-12 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(77, 77, 'Student ID', 'ITM24EC432', 'cx_v077_front.jpg', 'cx_v077_back.jpg', 'Approved', '2026-09-03 15:33:39', 1, '2026-09-04 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(78, 78, 'Student ID', 'ITM23PH436', 'cx_v078_front.jpg', 'cx_v078_back.jpg', 'Approved', '2026-10-18 15:33:39', 9, '2026-10-19 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(79, 79, 'Student ID', 'ITM25BB447', 'cx_v079_front.jpg', 'cx_v079_back.jpg', 'Approved', '2026-10-23 15:33:39', 1, '2026-10-24 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(80, 80, 'Student ID', 'ITM24CA275', 'cx_v080_front.jpg', 'cx_v080_back.jpg', 'Approved', '2026-08-26 15:33:39', 9, '2026-08-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(81, 81, 'Student ID', 'ITM22BB444', 'cx_v081_front.jpg', 'cx_v081_back.jpg', 'Approved', '2026-09-02 15:33:39', 1, '2026-09-03 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(82, 82, 'Student ID', 'ITM22ME348', 'cx_v082_front.jpg', 'cx_v082_back.jpg', 'Approved', '2026-08-13 15:33:39', 9, '2026-08-14 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(83, 83, 'Student ID', 'ITM25MB143', 'cx_v083_front.jpg', 'cx_v083_back.jpg', 'Approved', '2026-10-23 15:33:39', 1, '2026-10-24 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(84, 84, 'Student ID', 'ITM23PH465', 'cx_v084_front.jpg', 'cx_v084_back.jpg', 'Approved', '2026-08-10 15:33:39', 9, '2026-08-11 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(85, 85, 'Student ID', 'ITM23CE301', 'cx_v085_front.jpg', 'cx_v085_back.jpg', 'Approved', '2026-10-24 15:33:39', 1, '2026-10-25 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(86, 86, 'Student ID', 'ITM24PH143', 'cx_v086_front.jpg', 'cx_v086_back.jpg', 'Approved', '2026-10-26 15:33:39', 9, '2026-10-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(87, 87, 'Student ID', 'ITM23CA121', 'cx_v087_front.jpg', 'cx_v087_back.jpg', 'Approved', '2026-08-01 15:33:39', 1, '2026-08-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(88, 88, 'Student ID', 'ITM23EE168', 'cx_v088_front.jpg', 'cx_v088_back.jpg', 'Approved', '2026-07-30 15:33:39', 9, '2026-07-31 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(89, 89, 'Student ID', 'ITM22CS282', 'cx_v089_front.jpg', 'cx_v089_back.jpg', 'Approved', '2026-08-01 15:33:39', 1, '2026-08-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(90, 90, 'Student ID', 'ITM24CS376', 'cx_v090_front.jpg', 'cx_v090_back.jpg', 'Approved', '2026-08-19 15:33:39', 9, '2026-08-20 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(91, 91, 'Student ID', 'ITM25EC132', 'cx_v091_front.jpg', 'cx_v091_back.jpg', 'Approved', '2026-08-30 15:33:39', 1, '2026-08-31 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(92, 92, 'Student ID', 'ITM24CE164', 'cx_v092_front.jpg', 'cx_v092_back.jpg', 'Approved', '2026-11-04 15:33:39', 9, '2026-11-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(93, 93, 'Student ID', 'ITM25EC234', 'cx_v093_front.jpg', 'cx_v093_back.jpg', 'Approved', '2026-08-01 15:33:39', 1, '2026-08-02 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(94, 94, 'Student ID', 'ITM22CA161', 'cx_v094_front.jpg', 'cx_v094_back.jpg', 'Approved', '2026-08-18 15:33:39', 9, '2026-08-19 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(95, 95, 'Student ID', 'ITM23BB135', 'cx_v095_front.jpg', 'cx_v095_back.jpg', 'Approved', '2026-11-07 15:33:39', 1, '2026-11-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(96, 96, 'Student ID', 'ITM23EE186', 'cx_v096_front.jpg', 'cx_v096_back.jpg', 'Approved', '2026-10-07 15:33:39', 9, '2026-10-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(97, 97, 'Student ID', 'ITM23PH470', 'cx_v097_front.jpg', 'cx_v097_back.jpg', 'Approved', '2026-08-02 15:33:39', 1, '2026-08-03 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(98, 98, 'Student ID', 'ITM24CA337', 'cx_v098_front.jpg', 'cx_v098_back.jpg', 'Approved', '2026-10-04 15:33:39', 9, '2026-10-05 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(99, 99, 'Student ID', 'ITM22CA208', 'cx_v099_front.jpg', 'cx_v099_back.jpg', 'Approved', '2026-07-30 15:33:39', 1, '2026-07-31 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(100, 100, 'Student ID', 'ITM22ME268', 'cx_v100_front.jpg', 'cx_v100_back.jpg', 'Approved', '2026-10-20 15:33:39', 9, '2026-10-21 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(101, 101, 'Student ID', 'ITM23BB379', 'cx_v101_front.jpg', 'cx_v101_back.jpg', 'Approved', '2026-10-25 15:33:39', 1, '2026-10-26 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(102, 102, 'Student ID', 'ITM24CA237', 'cx_v102_front.jpg', 'cx_v102_back.jpg', 'Approved', '2026-10-08 15:33:39', 9, '2026-10-09 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(103, 103, 'Student ID', 'ITM22BB111', 'cx_v103_front.jpg', 'cx_v103_back.jpg', 'Approved', '2026-09-03 15:33:39', 1, '2026-09-04 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(104, 104, 'Student ID', 'ITM25CA447', 'cx_v104_front.jpg', 'cx_v104_back.jpg', 'Approved', '2026-08-15 15:33:39', 9, '2026-08-16 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(105, 105, 'Student ID', 'ITM25CA470', 'cx_v105_front.jpg', 'cx_v105_back.jpg', 'Approved', '2026-09-27 15:33:39', 1, '2026-09-28 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(106, 106, 'Faculty ID', 'FAC-PH-0185', 'cx_v106_front.jpg', 'cx_v106_back.jpg', 'Approved', '2026-10-26 15:33:39', 9, '2026-10-27 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(107, 107, 'Faculty ID', 'FAC-SC-0186', 'cx_v107_front.jpg', 'cx_v107_back.jpg', 'Approved', '2026-09-14 15:33:39', 1, '2026-09-15 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(108, 108, 'Faculty ID', 'FAC-CS-0187', 'cx_v108_front.jpg', 'cx_v108_back.jpg', 'Approved', '2026-10-21 15:33:39', 9, '2026-10-22 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(109, 109, 'Faculty ID', 'FAC-CA-0188', 'cx_v109_front.jpg', 'cx_v109_back.jpg', 'Approved', '2026-10-19 15:33:39', 1, '2026-10-20 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(110, 110, 'Faculty ID', 'FAC-EE-0189', 'cx_v110_front.jpg', 'cx_v110_back.jpg', 'Approved', '2026-08-15 15:33:39', 9, '2026-08-16 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(111, 111, 'Faculty ID', 'FAC-CS-0190', 'cx_v111_front.jpg', 'cx_v111_back.jpg', 'Approved', '2026-11-05 15:33:39', 1, '2026-11-06 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(112, 112, 'Faculty ID', 'FAC-BB-0191', 'cx_v112_front.jpg', 'cx_v112_back.jpg', 'Approved', '2026-08-07 15:33:39', 9, '2026-08-08 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(113, 113, 'Faculty ID', 'FAC-SC-0192', 'cx_v113_front.jpg', 'cx_v113_back.jpg', 'Approved', '2026-10-19 15:33:39', 1, '2026-10-20 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(114, 114, 'Faculty ID', 'FAC-EE-0193', 'cx_v114_front.jpg', 'cx_v114_back.jpg', 'Approved', '2026-08-16 15:33:39', 9, '2026-08-17 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(115, 115, 'Faculty ID', 'FAC-PH-0194', 'cx_v115_front.jpg', 'cx_v115_back.jpg', 'Approved', '2026-10-23 15:33:39', 1, '2026-10-24 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(116, 116, 'Faculty ID', 'FAC-CA-0195', 'cx_v116_front.jpg', 'cx_v116_back.jpg', 'Approved', '2026-09-20 15:33:39', 9, '2026-09-21 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(117, 117, 'Faculty ID', 'FAC-CA-0196', 'cx_v117_front.jpg', 'cx_v117_back.jpg', 'Approved', '2026-08-21 15:33:39', 1, '2026-08-22 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(118, 118, 'Faculty ID', 'FAC-CS-0197', 'cx_v118_front.jpg', 'cx_v118_back.jpg', 'Approved', '2026-08-24 15:33:39', 9, '2026-08-25 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(119, 119, 'Faculty ID', 'FAC-CE-0198', 'cx_v119_front.jpg', 'cx_v119_back.jpg', 'Approved', '2026-09-10 15:33:39', 1, '2026-09-11 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(120, 120, 'Faculty ID', 'FAC-CE-0199', 'cx_v120_front.jpg', 'cx_v120_back.jpg', 'Approved', '2026-09-03 15:33:39', 9, '2026-09-04 15:33:39', NULL, '2026-11-18 10:03:39', '2026-11-18 10:03:39', NULL),
(121, 122, 'Student ID', 'MCAN1CA25038', '7cff65006ac2790d31c2bf4f.jpg', '19f19938fcbc988bda76826e.jpg', 'Approved', '2026-11-21 23:01:15', 9, '2026-11-21 23:02:02', NULL, '2026-11-19 04:52:57', '2026-11-21 17:32:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `created_at`, `deleted_at`) VALUES
(1, 2, 1, '2026-11-17 09:42:56', NULL),
(2, 3, 1, '2026-11-16 09:42:56', NULL),
(3, 5, 8, '2026-11-15 09:42:56', NULL),
(4, 6, 10, '2026-11-14 09:42:56', NULL),
(5, 9, 8, '2026-11-13 09:42:56', NULL),
(6, 1, 9, '2026-11-12 09:42:56', NULL),
(7, 4, 12, '2026-11-11 09:42:56', NULL),
(8, 10, 2, '2026-11-10 09:42:56', NULL),
(9, 14, 3, '2026-11-09 09:42:56', NULL),
(10, 15, 12, '2026-11-08 09:42:56', NULL),
(11, 16, 14, '2026-11-07 09:42:56', NULL),
(12, 17, 19, '2026-11-06 09:42:56', NULL),
(13, 1, 83, '2026-10-19 10:03:39', NULL),
(14, 6, 63, '2026-10-09 10:03:39', NULL),
(15, 8, 93, '2026-11-07 10:03:39', NULL),
(16, 9, 111, '2026-10-15 10:03:39', NULL),
(17, 10, 34, '2026-10-19 10:03:39', NULL),
(18, 10, 106, '2026-10-17 10:03:39', NULL),
(19, 14, 101, '2026-10-28 10:03:39', NULL),
(20, 15, 65, '2026-10-18 10:03:39', NULL),
(21, 19, 90, '2026-10-31 10:03:39', NULL),
(22, 19, 93, '2026-10-30 10:03:39', NULL),
(23, 22, 58, '2026-10-18 10:03:39', NULL),
(24, 23, 83, '2026-10-23 10:03:39', NULL),
(25, 23, 84, '2026-11-08 10:03:39', NULL),
(26, 23, 115, '2026-11-10 10:03:39', NULL),
(27, 24, 101, '2026-10-24 10:03:39', NULL),
(28, 28, 65, '2026-10-14 10:03:39', NULL),
(29, 29, 75, '2026-11-06 10:03:39', NULL),
(30, 29, 79, '2026-10-17 10:03:39', NULL),
(31, 31, 113, '2026-10-27 10:03:39', NULL),
(32, 32, 75, '2026-11-06 10:03:39', NULL),
(33, 35, 50, '2026-11-12 10:03:39', NULL),
(34, 35, 85, '2026-10-17 10:03:39', NULL),
(35, 37, 63, '2026-10-31 10:03:39', NULL),
(36, 38, 43, '2026-10-16 10:03:39', NULL),
(37, 38, 91, '2026-10-13 10:03:39', NULL),
(38, 38, 118, '2026-10-16 10:03:39', NULL),
(39, 39, 97, '2026-10-25 10:03:39', NULL),
(40, 40, 30, '2026-11-13 10:03:39', NULL),
(41, 40, 66, '2026-10-26 10:03:39', NULL),
(42, 40, 81, '2026-10-11 10:03:39', NULL),
(43, 42, 30, '2026-11-15 10:03:39', NULL),
(44, 43, 33, '2026-10-29 10:03:39', NULL),
(45, 43, 39, '2026-10-25 10:03:39', NULL),
(46, 44, 50, '2026-10-13 10:03:39', NULL),
(47, 46, 55, '2026-10-31 10:03:39', NULL),
(48, 46, 87, '2026-10-17 10:03:39', NULL),
(49, 46, 103, '2026-11-01 10:03:39', NULL),
(50, 48, 114, '2026-10-30 10:03:39', NULL),
(51, 49, 58, '2026-10-27 10:03:39', NULL),
(52, 49, 81, '2026-11-06 10:03:39', NULL),
(53, 53, 82, '2026-10-11 10:03:39', NULL),
(54, 54, 86, '2026-11-17 10:03:39', NULL),
(55, 55, 101, '2026-10-18 10:03:39', NULL),
(56, 59, 114, '2026-10-13 10:03:39', NULL),
(57, 61, 26, '2026-11-01 10:03:39', NULL),
(58, 61, 46, '2026-10-28 10:03:39', NULL),
(59, 61, 77, '2026-10-31 10:03:39', NULL),
(60, 62, 21, '2026-10-19 10:03:39', NULL),
(61, 63, 51, '2026-10-30 10:03:39', NULL),
(62, 66, 57, '2026-10-16 10:03:39', NULL),
(63, 69, 109, '2026-10-26 10:03:39', NULL),
(64, 70, 75, '2026-10-26 10:03:39', NULL),
(65, 70, 111, '2026-10-31 10:03:39', NULL),
(66, 71, 86, '2026-10-26 10:03:39', NULL),
(67, 72, 102, '2026-10-22 10:03:39', NULL),
(68, 76, 26, '2026-10-26 10:03:39', NULL),
(69, 77, 70, '2026-11-06 10:03:39', NULL),
(70, 78, 50, '2026-10-20 10:03:39', NULL),
(71, 80, 35, '2026-10-25 10:03:39', NULL),
(72, 80, 85, '2026-10-27 10:03:39', NULL),
(73, 82, 27, '2026-10-15 10:03:39', NULL),
(74, 83, 93, '2026-11-08 10:03:39', NULL),
(75, 86, 51, '2026-10-15 10:03:39', NULL),
(76, 87, 49, '2026-11-07 10:03:39', NULL),
(77, 87, 77, '2026-11-05 10:03:39', NULL),
(78, 91, 84, '2026-10-25 10:03:39', NULL),
(79, 96, 38, '2026-10-18 10:03:39', NULL),
(80, 100, 63, '2026-10-30 10:03:39', NULL),
(81, 101, 22, '2026-11-12 10:03:39', NULL),
(82, 103, 81, '2026-10-22 10:03:39', NULL),
(83, 106, 55, '2026-11-07 10:03:39', NULL),
(84, 106, 65, '2026-10-09 10:03:39', NULL),
(85, 107, 47, '2026-10-11 10:03:39', NULL),
(86, 108, 26, '2026-10-15 10:03:39', NULL),
(87, 110, 29, '2026-10-22 10:03:39', NULL),
(88, 113, 57, '2026-10-29 10:03:39', NULL),
(89, 114, 77, '2026-10-09 10:03:39', NULL),
(90, 117, 53, '2026-10-13 10:03:39', NULL),
(91, 119, 29, '2026-10-31 10:03:39', NULL),
(92, 119, 75, '2026-11-16 10:03:39', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `pending_email` (`pending_email`);

--
-- Indexes for table `admin_email_verification_tokens`
--
ALTER TABLE `admin_email_verification_tokens`
  ADD PRIMARY KEY (`verification_token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_admin_email_verification` (`admin_id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_admin_log_admin` (`admin_id`);

--
-- Indexes for table `admin_password_reset_tokens`
--
ALTER TABLE `admin_password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_admin_password_reset` (`admin_id`);

--
-- Indexes for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`admin_profile_id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `admin_restrictions`
--
ALTER TABLE `admin_restrictions`
  ADD PRIMARY KEY (`restriction_id`),
  ADD KEY `fk_admin_restriction_target` (`admin_id`),
  ADD KEY `fk_admin_restriction_performer` (`performed_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD UNIQUE KEY `uq_chat_product_buyer_seller` (`product_id`,`buyer_id`,`seller_id`),
  ADD KEY `fk_chat_buyer` (`buyer_id`),
  ADD KEY `fk_chat_seller` (`seller_id`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`verification_token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_email_verification_user` (`user_id`);

--
-- Indexes for table `login_security_activity`
--
ALTER TABLE `login_security_activity`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `fk_security_activity_user` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_chat` (`chat_id`),
  ADD KEY `fk_message_sender` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_user` (`user_id`),
  ADD KEY `fk_notification_admin` (`admin_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_password_reset_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_seller` (`seller_id`),
  ADD KEY `fk_product_category` (`category_id`),
  ADD KEY `fk_product_removed_by_admin` (`removed_by_admin_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_product_image_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`product_review_id`),
  ADD UNIQUE KEY `uq_product_review_transaction` (`transaction_id`),
  ADD KEY `fk_product_review_product` (`product_id`),
  ADD KEY `fk_product_review_reviewer` (`reviewer_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`remember_token_id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `fk_remember_token_user` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_reporter` (`reporter_id`),
  ADD KEY `fk_reported_user` (`reported_user_id`),
  ADD KEY `fk_reported_product` (`reported_product_id`),
  ADD KEY `fk_report_reviewer` (`reviewed_by`);

--
-- Indexes for table `report_evidence`
--
ALTER TABLE `report_evidence`
  ADD PRIMARY KEY (`evidence_id`),
  ADD KEY `fk_evidence_report` (`report_id`);

--
-- Indexes for table `security_questions`
--
ALTER TABLE `security_questions`
  ADD PRIMARY KEY (`security_question_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD PRIMARY KEY (`seller_review_id`),
  ADD UNIQUE KEY `uq_seller_review_transaction` (`transaction_id`),
  ADD KEY `fk_seller_review_seller` (`seller_id`),
  ADD KEY `fk_seller_review_reviewer` (`reviewer_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_setting_admin` (`updated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_transaction_product` (`product_id`),
  ADD KEY `fk_transaction_seller` (`seller_id`),
  ADD KEY `fk_transaction_buyer` (`buyer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `pending_email` (`pending_email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD PRIMARY KEY (`ban_id`),
  ADD KEY `fk_ban_user` (`user_id`),
  ADD KEY `fk_ban_report` (`report_id`),
  ADD KEY `fk_ban_admin` (`admin_id`),
  ADD KEY `fk_ban_revoked_by` (`revoked_by`);

--
-- Indexes for table `user_presence`
--
ALTER TABLE `user_presence`
  ADD PRIMARY KEY (`presence_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD PRIMARY KEY (`verification_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_verification_admin` (`verified_by`),
  ADD KEY `idx_user_verification_status` (`verification_status`),
  ADD KEY `idx_user_verification_submitted_at` (`submitted_at`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `uq_wishlist_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_wishlist_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `admin_email_verification_tokens`
--
ALTER TABLE `admin_email_verification_tokens`
  MODIFY `verification_token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `admin_password_reset_tokens`
--
ALTER TABLE `admin_password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  MODIFY `admin_profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_restrictions`
--
ALTER TABLE `admin_restrictions`
  MODIFY `restriction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `verification_token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_security_activity`
--
ALTER TABLE `login_security_activity`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=361;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `product_review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `remember_token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `report_evidence`
--
ALTER TABLE `report_evidence`
  MODIFY `evidence_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `security_questions`
--
ALTER TABLE `security_questions`
  MODIFY `security_question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  MODIFY `seller_review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `ban_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_presence`
--
ALTER TABLE `user_presence`
  MODIFY `presence_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=652;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `user_verification`
--
ALTER TABLE `user_verification`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_email_verification_tokens`
--
ALTER TABLE `admin_email_verification_tokens`
  ADD CONSTRAINT `fk_admin_email_verification` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `admin_password_reset_tokens`
--
ALTER TABLE `admin_password_reset_tokens`
  ADD CONSTRAINT `fk_admin_password_reset` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD CONSTRAINT `fk_admin_profile_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `admin_restrictions`
--
ALTER TABLE `admin_restrictions`
  ADD CONSTRAINT `fk_admin_restriction_performer` FOREIGN KEY (`performed_by`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admin_restriction_target` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `fk_chat_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `fk_email_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_security_activity`
--
ALTER TABLE `login_security_activity`
  ADD CONSTRAINT `fk_security_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`chat_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_removed_by_admin` FOREIGN KEY (`removed_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_image_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_product_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_review_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON UPDATE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reported_product` FOREIGN KEY (`reported_product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reported_user` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `report_evidence`
--
ALTER TABLE `report_evidence`
  ADD CONSTRAINT `fk_evidence_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON UPDATE CASCADE;

--
-- Constraints for table `security_questions`
--
ALTER TABLE `security_questions`
  ADD CONSTRAINT `fk_security_question_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD CONSTRAINT `fk_seller_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seller_review_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seller_review_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON UPDATE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_setting_admin` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transaction_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD CONSTRAINT `fk_ban_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ban_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ban_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ban_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_presence`
--
ALTER TABLE `user_presence`
  ADD CONSTRAINT `fk_presence_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD CONSTRAINT `fk_verification_admin` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

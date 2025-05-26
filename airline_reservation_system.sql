-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: May 22, 2025 at 03:33 AM
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
-- Database: `airline_reservation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'system_init', NULL, 'Activity logs system initialized', '::1', '2025-05-18 02:40:01'),
(2, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 00:29:07'),
(3, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 00:29:31'),
(4, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 00:54:26'),
(5, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 01:50:52'),
(6, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 02:15:57'),
(7, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 02:18:45'),
(8, 1, 'change_user_status', 3, 'Changed user status: wiksu to suspended', '::1', '2025-05-19 15:54:11'),
(9, 1, 'change_user_status', 3, 'Changed user status: wiksu to active', '::1', '2025-05-19 15:54:16'),
(10, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 15:54:38'),
(11, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 16:36:03'),
(12, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 16:37:19'),
(13, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 17:20:37'),
(14, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 17:34:59'),
(15, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-19 17:39:04'),
(16, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 01:11:15'),
(17, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 01:22:08'),
(18, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 02:00:38'),
(19, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 02:29:02'),
(20, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 03:04:18'),
(21, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 03:06:01'),
(22, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 03:55:01'),
(23, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 03:58:06'),
(24, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 04:19:35'),
(25, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 04:29:49'),
(26, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 04:38:43'),
(27, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 04:43:19'),
(28, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 04:45:19'),
(29, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 05:24:01'),
(30, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 05:36:14'),
(31, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 05:56:10'),
(32, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:08:17'),
(33, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:13:54'),
(34, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:19:50'),
(35, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:21:43'),
(36, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:25:40'),
(37, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-20 06:29:02'),
(38, 1, 'delete_booking', 1, 'Permanently deleted booking', '::1', '2025-05-22 00:19:55'),
(39, 1, 'delete_booking', 4, 'Permanently deleted booking', '::1', '2025-05-22 00:20:11'),
(40, 1, 'cancel_booking', 6, 'Cancelled booking with reason: sadasdasd', '::1', '2025-05-22 00:30:46'),
(41, 1, 'delete_booking', 6, 'Permanently deleted booking', '::1', '2025-05-22 00:30:54'),
(42, 1, 'update_booking_status', 7, 'Updated booking status to pending and payment status to completed', '::1', '2025-05-22 00:31:10'),
(43, 1, 'update_booking_status', 7, 'Updated booking status to confirmed and payment status to completed', '::1', '2025-05-22 00:31:23'),
(44, 1, 'process_refund', 10, 'Processed refund of 350.00 for booking. Reason: asdasd', '::1', '2025-05-22 00:31:31'),
(45, 1, 'cancel_flight', 1, 'Flight cancelled. Reason: adasd. Affected 0 bookings.', '::1', '2025-05-22 00:47:20'),
(46, 1, 'cancel_flight', 15, 'Flight cancelled. Reason: asdasd. Affected 0 bookings.', '::1', '2025-05-22 00:48:19'),
(47, 1, 'delete_booking', 8, 'Permanently deleted booking', '::1', '2025-05-22 00:49:22'),
(48, 1, 'update_flight_status', 15, 'Changed flight status from \'arrived\' to \'boarding\'', '::1', '2025-05-22 00:51:42'),
(49, 1, 'update_flight_status', 15, 'Changed flight status from \'boarding\' to \'departed\'', '::1', '2025-05-22 00:51:50'),
(50, 1, 'update_flight_status', 15, 'Changed flight status from \'departed\' to \'delayed\' with reason: asdasd', '::1', '2025-05-22 00:51:57'),
(51, 1, 'update_flight_status', 15, 'Status changed from \'delayed\' to \'boarding\'', '::1', '2025-05-22 00:55:30'),
(52, 1, 'update_flight_status', 15, 'Status changed from \'boarding\' to \'boarding\'', '::1', '2025-05-22 00:57:50'),
(53, 1, 'update_flight_status', 15, 'Status changed from \'boarding\' to \'departed\'', '::1', '2025-05-22 00:58:11'),
(54, 1, 'update_flight_status', 15, 'Status changed from \'departed\' to \'arrived\'', '::1', '2025-05-22 00:58:20'),
(55, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'departed\' to \'scheduled\'', '::1', '2025-05-22 01:08:53'),
(56, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'scheduled\' to \'delayed\' with reason: 564564', '::1', '2025-05-22 01:09:03'),
(57, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'delayed\' to \'scheduled\'', '::1', '2025-05-22 01:09:08'),
(58, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'scheduled\' to \'boarding\'', '::1', '2025-05-22 01:09:14'),
(59, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'boarding\' to \'departed\'', '::1', '2025-05-22 01:09:19'),
(60, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'departed\' to \'departed\'', '::1', '2025-05-22 01:09:24'),
(61, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'departed\' to \'arrived\'', '::1', '2025-05-22 01:09:30'),
(62, 1, 'update_flight_status', 15, 'Changed flight #SK098 status from \'arrived\' to \'cancelled\' with reason: 45654', '::1', '2025-05-22 01:09:36'),
(63, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-22 01:15:49'),
(64, 1, 'update_settings', NULL, 'Updated payment settings', '::1', '2025-05-22 01:20:11'),
(65, 1, 'update_booking_status', 10, 'Updated booking status to cancelled and payment status to completed', '::1', '2025-05-22 01:26:59'),
(66, 1, 'logout', NULL, 'Admin logout from IP: ::1', '::1', '2025-05-22 01:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `passengers` int(2) NOT NULL DEFAULT 1,
  `seat_numbers` varchar(255) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `check_in_status` varchar(20) DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `payment_status` enum('pending','completed','refunded','failed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `price_per_passenger` decimal(10,2) DEFAULT NULL COMMENT 'Price per passenger',
  `base_fare` decimal(10,2) DEFAULT NULL,
  `taxes_fees` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `flight_id`, `booking_date`, `passengers`, `seat_numbers`, `booking_status`, `check_in_status`, `check_in_time`, `payment_status`, `admin_notes`, `total_amount`, `price_per_passenger`, `base_fare`, `taxes_fees`) VALUES
(2, 4, 4, '2025-05-19 01:39:53', 1, '', 'confirmed', 'completed', '2025-05-19 09:53:47', 'completed', NULL, 340.00, 340.00, 289.00, 51.00),
(3, 4, 2, '2025-05-20 01:19:37', 1, '', 'confirmed', 'completed', '2025-05-20 09:19:57', 'completed', NULL, 480.00, 480.00, 408.00, 72.00),
(7, 4, 19, '2025-05-19 23:58:36', 1, '', 'confirmed', NULL, NULL, 'completed', '', 100.00, 100.00, 85.00, 15.00),
(9, 4, 20, '2025-05-20 00:14:53', 1, '', 'confirmed', 'completed', '2025-05-22 09:16:02', 'completed', NULL, 350.00, 350.00, 297.50, 52.50),
(10, 4, 21, '2025-05-20 00:27:09', 1, '', 'cancelled', 'completed', '2025-05-20 14:27:21', 'completed', '', 350.00, 350.00, NULL, NULL),
(11, 4, 7, '2025-05-21 19:16:59', 1, '', 'confirmed', NULL, NULL, 'completed', NULL, 280.00, NULL, NULL, NULL),
(12, 4, 18, '2025-05-21 19:17:50', 1, '', 'confirmed', NULL, NULL, 'completed', NULL, 657.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` int(11) NOT NULL,
  `flight_number` varchar(20) NOT NULL,
  `aircraft` varchar(100) DEFAULT NULL,
  `airline` varchar(100) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `departure_airport` varchar(100) DEFAULT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `arrival_airport` varchar(100) DEFAULT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `duration` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `seats_available` int(11) NOT NULL DEFAULT 180,
  `economy_seats` int(11) DEFAULT 150,
  `business_seats` int(11) DEFAULT 20,
  `first_class_seats` int(11) DEFAULT 10,
  `total_seats` int(11) NOT NULL DEFAULT 180,
  `available_seats` int(11) NOT NULL,
  `status` enum('scheduled','delayed','cancelled','boarding','departed','arrived') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `flight_number`, `aircraft`, `airline`, `departure_city`, `departure_airport`, `arrival_city`, `arrival_airport`, `departure_time`, `arrival_time`, `duration`, `price`, `base_price`, `seats_available`, `economy_seats`, `business_seats`, `first_class_seats`, `total_seats`, `available_seats`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SK101', NULL, 'SkyWay Airlines', 'Manila', 'Manila', 'Tokyo', 'Tokyo', '2025-05-19 08:00:00', '2025-05-19 13:30:00', '5h 30m', 450.00, 450.00, 180, 150, 20, 10, 180, 0, 'cancelled', '2025-05-17 09:53:28', '2025-05-22 00:47:20'),
(2, 'SK102', NULL, 'SkyWay Airlines', 'Tokyo', 'Tokyo', 'Manila', 'Manila', '2025-05-20 14:30:00', '2025-05-20 18:00:00', '5h 30m', 480.00, 480.00, 180, 150, 20, 10, 180, 144, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(3, 'SK103', NULL, 'SkyWay Airlines', 'Manila', 'Manila', 'Singapore', 'Singapore', '2025-05-18 10:15:00', '2025-05-18 14:00:00', '3h 45m', 320.00, 320.00, 180, 150, 20, 10, 180, 180, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(4, 'SK104', NULL, 'SkyWay Airlines', 'Singapore', 'Singapore', 'Manila', 'Manila', '2025-05-19 15:30:00', '2025-05-19 19:15:00', '3h 45m', 340.00, 340.00, 180, 150, 20, 10, 180, 173, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(5, 'SK105', NULL, 'SkyWay Airlines', 'Manila', 'Manila', 'Dubai', 'Dubai', '2025-05-20 23:15:00', '2025-05-21 05:45:00', '9h 30m', 550.00, 550.00, 180, 150, 20, 10, 180, 160, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(6, 'SK106', NULL, 'SkyWay Airlines', 'Dubai', 'Dubai', 'Manila', 'Manila', '2025-05-22 02:30:00', '2025-05-22 17:00:00', '9h 30m', 580.00, 580.00, 180, 150, 20, 10, 180, 155, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(7, 'SK107', NULL, 'SkyWay Airlines', 'Manila', 'Manila', 'Hong Kong', 'Hong Kong', '2025-05-18 07:45:00', '2025-05-18 10:15:00', '2h 30m', 280.00, 280.00, 180, 150, 20, 10, 180, 185, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(8, 'SK108', NULL, 'SkyWay Airlines', 'Hong Kong', 'Hong Kong', 'Manila', 'Manila', '2025-05-18 17:30:00', '2025-05-18 20:00:00', '2h 30m', 295.00, 295.00, 180, 150, 20, 10, 180, 180, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(9, 'SK109', NULL, 'SkyWay Airlines', 'Manila', 'Manila', 'Seoul', 'Seoul', '2025-05-19 09:30:00', '2025-05-19 14:45:00', '5h 15m', 420.00, 420.00, 180, 150, 20, 10, 180, 170, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(10, 'SK110', NULL, 'SkyWay Airlines', 'Seoul', 'Seoul', 'Manila', 'Manila', '2025-05-20 16:00:00', '2025-05-20 21:15:00', '5h 15m', 440.00, 440.00, 180, 150, 20, 10, 180, 165, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(11, 'SK201', NULL, 'SkyWay Airlines', 'Cebu', 'Cebu', 'Singapore', 'Singapore', '2025-05-18 08:30:00', '2025-05-18 12:00:00', '3h 30m', 310.00, 310.00, 180, 150, 20, 10, 180, 175, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(12, 'SK202', NULL, 'SkyWay Airlines', 'Singapore', 'Singapore', 'Cebu', 'Cebu', '2025-05-19 13:15:00', '2025-05-19 16:45:00', '3h 30m', 325.00, 325.00, 180, 150, 20, 10, 180, 170, 'scheduled', '2025-05-17 09:53:28', '2025-05-20 02:00:23'),
(13, 'SK099', '0', 'SkyWay Airlines', 'manila', 'Manila', 'cebu', 'Cebu', '2025-05-19 05:13:00', '2025-05-20 17:14:00', '', 350.00, 350.00, 450, 150, 20, 10, 450, 0, 'delayed', '2025-05-19 17:18:00', '2025-05-22 00:47:06'),
(14, 'SK099', '0', 'SkyWay Airlines', 'manila', 'Manila', 'cebu', 'Cebu', '2025-05-19 05:13:00', '2025-05-20 17:14:00', '', 350.00, 350.00, 450, 150, 20, 10, 450, 0, 'scheduled', '2025-05-19 17:20:04', '2025-05-20 02:00:23'),
(15, 'SK098', 'Boeing 737', 'SkyWay Airlines', 'Hong Kong', 'Hong Kong', 'Manila', 'Manila', '2025-05-20 09:58:00', '2025-05-21 21:58:00', '', 524.00, 350.00, 180, 180, 90, 180, 450, 0, 'cancelled', '2025-05-20 02:00:29', '2025-05-22 01:09:36'),
(16, 'SK097', 'Boeing 737', 'SkyWay Airlines', 'Cebu', 'Cebu', 'Manila', 'Manila', '2025-05-22 11:05:00', '2025-05-23 23:05:00', '', 303.00, 450.00, 180, 180, 90, 180, 450, 450, 'scheduled', '2025-05-20 03:05:55', '2025-05-22 00:30:46'),
(17, 'SK096', 'Boeing 737', 'SkyWay Airlines', 'Tokyo', 'Tokyo', 'Manila', 'Manila', '2025-05-24 12:42:00', '2025-05-25 00:42:00', '', 632.00, 500.00, 180, 180, 90, 180, 450, 450, 'scheduled', '2025-05-20 04:43:04', '2025-05-20 06:16:54'),
(18, 'SK095', 'Boeing 737', 'SkyWay Airlines', 'Hong Kong', 'Hong Kong', 'Manila', 'Manila', '2025-05-26 12:45:00', '2025-05-27 00:45:00', '', 657.00, 500.00, 180, 60, 60, 60, 180, 180, 'scheduled', '2025-05-20 04:45:17', '2025-05-20 06:16:54'),
(19, 'SK112', 'Boeing 737', 'SkyWay Airlines', 'Dubai', NULL, 'Manila', NULL, '2025-05-24 13:51:00', '2025-05-25 01:51:00', '', 100.00, NULL, 180, 150, 20, 10, 180, 0, 'scheduled', '2025-05-20 05:55:41', '2025-05-20 05:55:41'),
(20, 'SK093', 'Boeing 737', 'SkyWay Airlines', 'Cebu', NULL, 'Dubai', NULL, '2025-05-23 14:13:00', '2025-05-24 02:13:00', '', 350.00, NULL, 180, 150, 20, 10, 180, 0, 'scheduled', '2025-05-20 06:13:39', '2025-05-20 06:13:39'),
(21, 'SK092', 'Boeing 737', 'SkyWay Airlines', 'Manila', NULL, 'Hong Kong', NULL, '2025-05-21 14:25:00', '2025-05-22 02:25:00', '', 350.00, NULL, 180, 150, 20, 10, 180, 1, 'scheduled', '2025-05-20 06:25:29', '2025-05-22 01:26:59');

-- --------------------------------------------------------

--
-- Table structure for table `flight_history`
--

CREATE TABLE `flight_history` (
  `history_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_history`
--

INSERT INTO `flight_history` (`history_id`, `flight_id`, `status`, `notes`, `admin_id`, `created_at`) VALUES
(1, 1, 'active', '', 1, '2025-05-19 16:35:40'),
(2, 13, 'active', '', 1, '2025-05-19 17:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `title` varchar(10) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL,
  `ticket_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seat_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `booking_id`, `title`, `first_name`, `last_name`, `date_of_birth`, `passport_number`, `ticket_number`, `phone_number`, `created_at`, `seat_number`) VALUES
(2, 2, 'Mr', 'Johnrick', 'Nuñeza', '2025-05-19', '', NULL, '0961313564564', '2025-05-19 01:39:53', '10A'),
(3, 3, 'Mr', 'Johnrick', 'Nuñeza', '2002-01-14', '', NULL, '0961313564564', '2025-05-20 01:19:37', '10A'),
(6, 7, 'Mr', 'Johnrick', 'Nuñeza', '2025-05-20', '123123', NULL, NULL, '2025-05-20 05:58:36', NULL),
(8, 9, 'Mr', 'Johnrick', 'Nuñeza', '2025-05-20', '123123', NULL, NULL, '2025-05-20 06:14:53', '10A'),
(9, 10, 'Mr', 'Johnrick', 'Nuñeza', '2025-05-21', '123123', NULL, NULL, '2025-05-20 06:27:10', '10A'),
(10, 11, 'Mr', 'Johnrick', 'Nuñeza', '2025-05-22', '123123', NULL, NULL, '2025-05-22 01:16:59', NULL),
(11, 12, 'Mr', 'asdasd', 'asdasd', '0000-00-00', 'asdasd', NULL, NULL, '2025-05-22 01:17:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','gcash','maya','other') NOT NULL,
  `payment_status` enum('pending','completed','refunded','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `promo_id` int(11) NOT NULL,
  `promo_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL,
  `setting_type` varchar(50) NOT NULL DEFAULT 'text',
  `setting_label` varchar(100) NOT NULL,
  `setting_description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `setting_group`, `setting_type`, `setting_label`, `setting_description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'SkyWay Airlines', 'general', 'text', 'Site Title', 'The name of your airline website', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(2, 'site_description', 'Book your flights easily, travel comfortably, and explore new destinations.', 'general', 'textarea', 'Site Description', 'A brief description of your airline', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(3, 'contact_email', 'info@skywayairlines.com', 'general', 'email', 'Contact Email', 'Public contact email address', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(4, 'contact_phone', '+63 (2) 8123 4567', 'general', 'text', 'Contact Phone', 'Public contact phone number', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(5, 'contact_address', '123 Airport Road, Metro Manila, Philippines', 'general', 'textarea', 'Contact Address', 'Physical address of the airline', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(6, 'advance_booking_days', '365', 'booking', 'number', 'Advance Booking Days', 'How many days in advance customers can book flights', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(7, 'min_hours_before_departure', '3', 'booking', 'number', 'Minimum Hours Before Departure', 'Minimum hours before departure that bookings can be made', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(8, 'cancellation_fee_percentage', '10', 'booking', 'number', 'Cancellation Fee (%)', 'Percentage of ticket price charged as cancellation fee', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(9, 'allow_guest_bookings', '1', 'booking', 'boolean', 'Allow Guest Bookings', 'Allow users to book flights without an account', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(10, 'booking_expiry_minutes', '30', 'booking', 'number', 'Booking Expiry (Minutes)', 'Number of minutes before an unpaid booking expires', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(11, 'currency_code', 'PHP', 'payment', 'text', 'Currency Code', 'Currency code for payments (e.g., USD, EUR)', 0, '2025-05-18 02:35:08', '2025-05-22 01:20:11'),
(12, 'currency_symbol', '₱', 'payment', 'text', 'Currency Symbol', 'Currency symbol for display', 0, '2025-05-18 02:35:08', '2025-05-22 01:20:11'),
(13, 'payment_gateway', 'paypal', 'payment', 'select', 'Payment Gateway', 'Default payment gateway', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(14, 'test_mode', '1', 'payment', 'boolean', 'Test Mode', 'Enable test/sandbox mode for payments', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(15, 'vat_percentage', '12', 'payment', 'number', 'VAT Percentage', 'VAT tax percentage applied to bookings', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(16, 'admin_email', 'admin@skywayairlines.com', 'email', 'email', 'Admin Email', 'Email address for admin notifications', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(17, 'enable_email_notifications', '1', 'email', 'boolean', 'Enable Email Notifications', 'Send email notifications for bookings and updates', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(18, 'email_sender_name', 'SkyWay Airlines', 'email', 'text', 'Email Sender Name', 'Name displayed as the sender of emails', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(19, 'maintenance_mode', '0', 'system', 'boolean', 'Maintenance Mode', 'Put the website in maintenance mode', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(20, 'pagination_limit', '10', 'system', 'number', 'Pagination Limit', 'Number of items to display per page', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(21, 'debug_mode', '0', 'system', 'boolean', 'Debug Mode', 'Enable debug information (not recommended for production)', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(22, 'log_user_activity', '1', 'system', 'boolean', 'Log User Activity', 'Track user activities in the system', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(23, 'session_timeout_minutes', '30', 'system', 'number', 'Session Timeout (Minutes)', 'Minutes of inactivity before user is logged out', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08'),
(24, 'default_theme', 'default', 'system', 'text', 'Default Theme', 'Default theme for the website', 0, '2025-05-18 02:35:08', '2025-05-18 02:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `qr_code` text DEFAULT NULL,
  `status` enum('active','used','cancelled') DEFAULT 'active',
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `account_status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `role`, `created_at`, `updated_at`, `account_status`) VALUES
(1, 'admin', 'admin@airlines.com', '$2y$10$n3Gap08ZFHFQgeRT3RHa4.Wwc0mYl7tnrfbpcar95GiOM94LZiq7e', 'System', 'Administrator', NULL, NULL, 'admin', '2025-05-17 09:53:28', '2025-05-17 09:53:28', 'active'),
(2, 'rick', 'Jangwick5609@gmail.com', '$2y$10$KP2g55URVw70LONv4dqyseO5MuOw6b0JpdwhvHS4dblGMzEil6T92', 'Johnrick', 'Nuñeza', '98498498498', NULL, 'user', '2025-05-17 10:08:43', '2025-05-17 10:08:43', 'active'),
(3, 'wiksu', 'wiksu@gmail.com', '$2y$10$iMuYQNarK/gOqi9TYzuH9.GEXta7Quz8AK70jY3YZHOY/4Ab25/he', 'Johnrick', 'Nuñeza', '948949889', NULL, 'user', '2025-05-17 18:06:58', '2025-05-19 15:54:16', 'active'),
(4, 'wik', 'rick5609@gmail.com', '$2y$10$vgIZxvjJgD8PTT7JLoW6.uw63Zn7f7Hu/gZYkJzrGZUR778b6RURq', 'Johnrick', 'Nuñeza', '0961313564564', NULL, 'user', '2025-05-19 00:30:19', '2025-05-19 00:30:19', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`);

--
-- Indexes for table `flight_history`
--
ALTER TABLE `flight_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `promo_code` (`promo_code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `flight_history`
--
ALTER TABLE `flight_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`);

--
-- Constraints for table `flight_history`
--
ALTER TABLE `flight_history`
  ADD CONSTRAINT `flight_history_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON DELETE CASCADE;

--
-- Constraints for table `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

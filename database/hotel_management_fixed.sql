-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2026 at 01:38 PM
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
-- Database: `hotel_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteAdmin` (IN `p_id` INT)   BEGIN
    DELETE FROM admin WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteBooking` (IN `p_id` INT)   BEGIN
    DELETE FROM bookings WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteCustomer` (IN `p_id` INT)   BEGIN
    DELETE FROM customers WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeletePayment` (IN `p_id` INT)   BEGIN
    DELETE FROM payments WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteRoom` (IN `p_id` INT)   BEGIN
    DELETE FROM rooms WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertAdmin` (IN `p_full_name` VARCHAR(100), IN `p_name` VARCHAR(200), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_role` VARCHAR(50))   BEGIN
    INSERT INTO admin (full_name, name, email, password, role, status)
    VALUES (p_full_name, p_name, p_email, p_password, p_role, 'Active');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertBooking` (IN `p_customer_id` INT, IN `p_room_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_status` VARCHAR(20), IN `p_total_amount` DECIMAL(10,2), IN `p_num_guests` INT)   BEGIN
    INSERT INTO bookings (customer_id, room_id, check_in, check_out, status, total_amount, num_guests)
    VALUES (p_customer_id, p_room_id, p_check_in, p_check_out, p_status, p_total_amount, p_num_guests);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertCustomer` (IN `p_full_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_id_number` VARCHAR(50), IN `p_adress` VARCHAR(255), IN `p_nationality` VARCHAR(100))   BEGIN
    INSERT INTO customers (full_name, email, phone, id_number, adress, nationality)
    VALUES (p_full_name, p_email, p_phone, p_id_number, p_adress, p_nationality);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertPayment` (IN `p_booking_id` INT, IN `p_amount_paid` DECIMAL(10,2), IN `p_payment_method` VARCHAR(50), IN `p_status` VARCHAR(20))   BEGIN
    INSERT INTO payments (booking_id, amount_paid, payment_method, status)
    VALUES (p_booking_id, p_amount_paid, p_payment_method, p_status);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateBooking` (IN `p_id` INT, IN `p_room_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_status` VARCHAR(20), IN `p_total_amount` DECIMAL(10,2), IN `p_num_guests` INT)   BEGIN
    UPDATE bookings 
    SET room_id = p_room_id, 
        check_in = p_check_in, 
        check_out = p_check_out, 
        status = p_status, 
        total_amount = p_total_amount, 
        num_guests = p_num_guests
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomer` (IN `p_id` INT, IN `p_full_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_id_number` VARCHAR(50), IN `p_adress` VARCHAR(255), IN `p_nationality` VARCHAR(100))   BEGIN
    UPDATE customers 
    SET full_name = p_full_name, 
        email = p_email, 
        phone = p_phone, 
        id_number = p_id_number, 
        adress = p_adress, 
        nationality = p_nationality
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateRoom` (IN `p_id` INT, IN `p_room_number` VARCHAR(10), IN `p_room_type_id` INT, IN `p_floor` INT, IN `p_status` VARCHAR(20))   BEGIN
    UPDATE rooms 
    SET room_number = p_room_number,
        room_type_id = p_room_type_id,
        floor = p_floor,
        status = p_status
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateRoomType` (IN `p_id` INT, IN `p_type_name` VARCHAR(50), IN `p_price` DECIMAL(10,2), IN `p_description` TEXT)   BEGIN
    UPDATE room_types 
    SET type_name = p_type_name,
        price_per_night = p_price,
        description = p_description
    WHERE id = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateSettings` (IN `p_name` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_phone` VARCHAR(50), IN `p_address` TEXT)   BEGIN
    UPDATE settings 
    SET hotel_name = p_name, 
        hotel_email = p_email, 
        hotel_phone = p_phone, 
        hotel_address = p_address
    WHERE id = 1;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('Admin','Reception','Finance','HRM') DEFAULT 'Reception',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `full_name` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `created_at`, `role`, `status`, `full_name`, `profile_picture`, `picture`) VALUES
(1, 'Afrik Hotel', 'ifrah@gmail.com', '$2y$10$kKJuI8THamZSs6mHLNKFyOeX/ZDb3SNr2PlZGkO3t5GtLrGj94xQq', '2026-09-19 21:21:39', 'Admin', 'Active', NULL, 'avatar_1_1789852899.jpg', NULL),
(3, 'sahra', 'sahra123@gmail.com', '$2y$10$5fNhVqYcL47pFkSICkxWUuVsnOU79gXwXUOM9IJqVlQ6ErnvFObLS', '2026-09-12 17:03:33', '', 'Active', 'Sahra Huein Ahmed', NULL, NULL),
(4, 'Ramla', 'ramla@gmail.com', '$2y$10$.B8nyn15QAsjKE9NAp.SoOOqSQ86UkfVm85S31soImBD4DvabDEnW', '2026-09-12 17:07:33', '', 'Active', 'Ramla Abdi Ali', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Present',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Checked\r\nin','Checked-out','Cancelled') DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `num_guests` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `room_id`, `check_in`, `check_out`, `total_price`, `status`, `total_amount`, `created_at`, `num_guests`) VALUES
(35064, 1, 101, '2021-02-10', '2021-02-15', 300.00, 'Confirmed', NULL, '2026-09-19 22:47:17', 1),
(35065, 2, 102, '2021-06-12', '2021-06-18', 450.00, 'Checked-out', NULL, '2026-09-19 22:47:17', 1),
(35066, 3, 103, '2022-01-05', '2022-01-10', 250.00, 'Confirmed', NULL, '2026-09-19 22:47:17', 1),
(35067, 4, 104, '2022-08-14', '2022-08-20', 600.00, 'Checked-out', NULL, '2026-09-19 22:47:17', 1),
(35068, 5, 101, '2023-04-11', '2023-04-16', 350.00, 'Confirmed', NULL, '2026-09-19 22:47:18', 1),
(35069, 1, 105, '2023-11-22', '2023-11-28', 700.00, 'Cancelled', NULL, '2026-09-19 22:47:18', 1),
(35070, 2, 102, '2024-03-01', '2024-03-05', 280.00, 'Confirmed', NULL, '2026-09-19 22:47:18', 1),
(35071, 6, 103, '2024-07-19', '2024-07-25', 550.00, 'Checked-out', NULL, '2026-09-19 22:47:18', 1),
(35072, 3, 104, '2025-05-10', '2025-05-15', 400.00, 'Confirmed', NULL, '2026-09-19 22:47:18', 1),
(35073, 4, 105, '2025-12-01', '2025-12-07', 650.00, 'Checked-out', NULL, '2026-09-19 22:47:18', 1),
(35074, 17, 1, '2026-09-20', '2026-09-21', 10.00, 'Confirmed', NULL, '2026-09-19 23:00:14', 1),
(35075, 4, 2, '2025-12-01', '2025-12-07', 60.00, 'Confirmed', NULL, '2026-09-20 10:29:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `booking_services`
--

CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `adress` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nationality` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `password`, `phone`, `id_number`, `adress`, `created_at`, `nationality`, `address`) VALUES
(2, 'Evrah', 'evrah29@gmail.com', NULL, '+252 621267652', 'passport', 'warta-nabada', '2026-09-07 22:39:14', 'somali', NULL),
(3, 'Rahma Moalin Muuse', 'Rahma23@gmail.com', NULL, '+252 615676251', 'passport', 'undefined', '2026-09-07 23:48:48', 'kenayn', NULL),
(4, 'Suhuur Ali Ahmed', 'sahur24@gmail.com', NULL, '+252 621267652', '123165', 'undefined', '2026-09-07 23:51:40', 'somali', NULL),
(5, 'mohamed Ali Afrah', 'mohedafrah@gmail.com', NULL, '+910987654267', 'passport', 'undefined', '2026-09-10 05:20:42', 'indian', NULL),
(6, 'Evrah', 'evrah29@gmail.com', NULL, '+252 615676251', '123165', 'undefined', '2026-09-10 07:36:35', 'somali', NULL),
(7, 'Evrah', 'evrah29@gmail.com', NULL, '+252 615676251', '123165', 'undefined', '2026-09-10 07:37:12', 'somali', NULL),
(8, 'Sahra Mo\'alin Muuse', 'sahra123@gmail.com', NULL, '+252 615654321', '657483', 'warta-nabada', '2026-09-11 15:18:40', 'somali', NULL),
(9, 'Noor Hassan Ahmed', 'Noor243@gmail.com', NULL, '+252 615676532', '674839283', 'undefined', '2026-09-11 15:19:33', 'somali', NULL),
(10, 'Hassan Abdi Faarah', 'Hassanabdi12@gmail.com', NULL, '+252 615676542', '78920211', 'undefined', '2026-09-11 15:24:20', 'somali', NULL),
(11, 'Halima Ahmed Muuse', 'Halima6767@gmail.com', '$2y$10$gFgPJnpmBnPrsBeH3CYUM.vKF9eRxUEzodgVSVkP3YiXd/mByJn8a', '+252 619000909', NULL, NULL, '2026-09-13 06:19:22', NULL, NULL),
(12, 'Muuse Ali Osman', 'aliosman@gmail.com', '$2y$10$YCaZT02H2qRpFSk6c0qo.eCzA8rPMFjEBa9A7zApllTW9gAlW8OBi', '+252 615676543', NULL, NULL, '2026-09-13 06:25:42', NULL, NULL),
(13, 'Hassan Abdinuur Hassan', 'Hassanabdi78@gmail.com', '$2y$10$sf3RmmAi3/X.L1s743h5M.xkMnZdLHF2MUBjlUsc8X2vzXRnKR/FK', '+252 617890000', NULL, NULL, '2026-09-15 10:37:50', NULL, NULL),
(14, 'naxwo ali muuse', 'naxwa23@gmail.com', '$2y$10$OnRLgVN40JxrljmujcBk1OK5SJcnSzrNi8TshsuTCLsmoogvlRcEq', '+252 617890976', NULL, NULL, '2026-09-16 10:16:35', NULL, NULL),
(15, 'Ahmed Ali', 'ahmed@hotel.com', NULL, '252615000000', NULL, NULL, '2026-09-19 22:55:16', NULL, NULL),
(16, 'Fatima Omar', 'fatima@hotel.com', NULL, '252611111111', NULL, NULL, '2026-09-19 22:55:16', NULL, NULL),
(17, 'Muscab Abdi Awale', 'abdiawale@gmail.com', '$2y$10$pmCBRHRiewx1q7uTwB/Ra.ebVacl.KWZpA77O7SZg/f.u42dokyAq', '+252 619999999', NULL, NULL, '2026-09-19 22:59:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_closing`
--

CREATE TABLE `daily_closing` (
  `id` int(11) NOT NULL,
  `closing_date` date NOT NULL,
  `cash_total` decimal(12,2) DEFAULT 0.00,
  `card_total` decimal(12,2) DEFAULT 0.00,
  `bank_total` decimal(12,2) DEFAULT 0.00,
  `expense_total` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_expenses`
--

CREATE TABLE `hotel_expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(40) DEFAULT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_services`
--

CREATE TABLE `hotel_services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_suppliers`
--

CREATE TABLE `hotel_suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping`
--

CREATE TABLE `housekeeping` (
  `id` int(11) NOT NULL,
  `room_no` varchar(30) DEFAULT NULL,
  `task_date` date NOT NULL,
  `task_type` varchar(100) DEFAULT NULL,
  `assigned_to` varchar(150) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_tasks`
--

CREATE TABLE `housekeeping_tasks` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `task_date` date NOT NULL,
  `task_type` varchar(60) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `status` varchar(30) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` decimal(12,2) DEFAULT 0.00,
  `unit` varchar(30) DEFAULT NULL,
  `reorder_level` decimal(12,2) DEFAULT 0.00,
  `unit_cost` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `sku` varchar(80) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `quantity` decimal(12,2) DEFAULT 0.00,
  `reorder_level` decimal(12,2) DEFAULT 0.00,
  `unit_cost` decimal(12,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `status` varchar(40) DEFAULT 'In Stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laundry_orders`
--

CREATE TABLE `laundry_orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `order_date` date NOT NULL,
  `item_count` int(11) DEFAULT 0,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Received',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `room_no` varchar(30) DEFAULT NULL,
  `reported_date` date NOT NULL,
  `issue` varchar(255) NOT NULL,
  `priority` varchar(30) DEFAULT 'Medium',
  `status` varchar(30) DEFAULT 'Open',
  `cost` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `request_date` date NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `status` varchar(30) DEFAULT 'Open',
  `cost` decimal(12,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','Card','Mobile Money') DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `amount_paid`, `payment_method`, `payment_date`, `status`) VALUES
(9, 35074, 10.00, NULL, '', '2026-09-19 23:00:24', 'Paid'),
(10, 35075, 60.00, NULL, '', '2026-09-20 10:29:52', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `payment_refunds`
--

CREATE TABLE `payment_refunds` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `refund_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_orders`
--

CREATE TABLE `restaurant_orders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `table_no` varchar(30) DEFAULT NULL,
  `items` text NOT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `status` enum('Available','Occupied','Reserved','Maintenance') DEFAULT 'Available',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`, `image`) VALUES
(1, '101', 1, 1, '', NULL),
(2, '103', 1, 2, '', NULL),
(3, '203', 2, 5, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_transfers`
--

CREATE TABLE `room_transfers` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `from_room_id` int(11) NOT NULL,
  `to_room_id` int(11) NOT NULL,
  `transfer_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(200) DEFAULT NULL,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `type_name`, `price_per_night`, `description`) VALUES
(1, 'single', 10.00, 'this rooms is for signal person'),
(2, 'double', 50.00, 'this room has double bed'),
(3, 'single', 5.00, 'no AC and no WI-FI'),
(4, 'Standard', 45.00, 'Comfortable standard room'),
(5, 'Deluxe', 70.00, 'Deluxe room with upgraded amenities'),
(6, 'Executive', 95.00, 'Executive room for business travelers'),
(7, 'Family', 120.00, 'Family room with extra sleeping space'),
(8, 'Suite', 160.00, 'Large suite with living area');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `services_name` varchar(200) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_orders`
--

CREATE TABLE `service_orders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `hotel_email` varchar(255) NOT NULL,
  `hotel_phone` varchar(50) NOT NULL,
  `hotel_address` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hotel_logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `hotel_name`, `hotel_email`, `hotel_phone`, `hotel_address`, `updated_at`, `hotel_logo`) VALUES
(1, 'Afrik Hotel', 'info@afrikhotel.com', '+252 61 000 0000', 'Mogadishu, Somalia', '2026-09-19 21:17:20', 'logo_1789852640.png');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `employee_no` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave`
--

CREATE TABLE `staff_leave` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `leave_type` varchar(40) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_payroll`
--

CREATE TABLE `staff_payroll` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `pay_period` varchar(50) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_name` varchar(40) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_pages`
--

CREATE TABLE `system_pages` (
  `id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `icon_class` varchar(100) NOT NULL,
  `page_type` varchar(50) DEFAULT 'Sidebar',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_pages`
--

INSERT INTO `system_pages` (`id`, `page_name`, `url`, `category`, `icon_class`, `page_type`, `created_at`) VALUES
(1, 'Dashboard', 'index.php', 'Main', 'fas fa-home', 'Sidebar', '2026-09-12 16:27:15'),
(2, 'Rooms', 'admin/rooms/rooms.php', 'Hotel Management', 'fas fa-door-open', 'Sidebar', '2026-09-12 16:27:15'),
(3, 'Room Types', 'admin/rooms/room-types.php', 'Hotel Management', 'fas fa-tags', 'Sidebar', '2026-09-12 16:27:15'),
(4, 'Bookings', 'admin/bookings/bookings.php', 'Order Management', 'fas fa-calendar-alt', 'Sidebar', '2026-09-12 16:27:15'),
(5, 'Guests', 'admin/customers/customers.php', 'Order Management', 'fas fa-users', 'Sidebar', '2026-09-12 16:27:15'),
(6, 'Payments', 'admin/payments/payments.php', 'Finance', 'fas fa-credit-card', 'Sidebar', '2026-09-12 16:27:15'),
(7, 'Reports', 'admin/reports/reports.php', 'Finance', 'fas fa-chart-bar', 'Sidebar', '2026-09-12 16:27:15'),
(8, 'Users', 'admin/users/users.php', 'Authentication', 'fas fa-user-shield', 'Sidebar', '2026-09-12 16:27:15'),
(9, 'User Permissions', 'admin/users/user_permissions.php', 'Authentication', 'fas fa-lock', 'Sidebar', '2026-09-12 16:27:15'),
(10, 'Pages', 'admin/Developer/pages.php', 'Developer', 'fas fa-file-code', 'Sidebar', '2026-09-12 16:27:15'),
(11, 'Settings', 'admin/settings/settings.php', 'System', 'fas fa-cog', 'Sidebar', '2026-09-12 16:27:15'),
(19, 'Import Data', 'admin/settings/import_data.php', 'System', 'fas fa-file-csv', 'Sidebar', '2026-09-19 22:04:18'),
(27, 'Staff Management', 'admin/operations/staff.php', 'Hotel Operations', 'fas fa-user-tie', 'Sidebar', '2026-09-20 11:32:40'),
(28, 'Staff Attendance', 'admin/operations/attendance.php', 'Hotel Operations', 'fas fa-user-clock', 'Sidebar', '2026-09-20 11:32:40'),
(29, 'Staff Shifts', 'admin/operations/shifts.php', 'Hotel Operations', 'fas fa-business-time', 'Sidebar', '2026-09-20 11:32:40'),
(30, 'Staff Leave', 'admin/operations/leave.php', 'Hotel Operations', 'fas fa-calendar-minus', 'Sidebar', '2026-09-20 11:32:40'),
(31, 'Housekeeping', 'admin/operations/housekeeping.php', 'Hotel Operations', 'fas fa-broom', 'Sidebar', '2026-09-20 11:32:40'),
(32, 'Maintenance', 'admin/operations/maintenance.php', 'Hotel Operations', 'fas fa-tools', 'Sidebar', '2026-09-20 11:32:40'),
(33, 'Expenses', 'admin/operations/expenses.php', 'Finance', 'fas fa-money-bill-wave', 'Sidebar', '2026-09-20 11:32:40'),
(34, 'Inventory', 'admin/operations/inventory.php', 'Inventory', 'fas fa-boxes', 'Sidebar', '2026-09-20 11:32:40'),
(35, 'Suppliers', 'admin/operations/suppliers.php', 'Inventory', 'fas fa-truck', 'Sidebar', '2026-09-20 11:32:40'),
(36, 'Hotel Services', 'admin/operations/services.php', 'Hotel Operations', 'fas fa-concierge-bell', 'Sidebar', '2026-09-20 11:32:40'),
(37, 'Service Orders', 'admin/operations/service_orders.php', 'Hotel Operations', 'fas fa-concierge-bell', 'Sidebar', '2026-09-20 11:32:40'),
(38, 'Laundry', 'admin/operations/laundry.php', 'Hotel Operations', 'fas fa-tshirt', 'Sidebar', '2026-09-20 11:32:40'),
(39, 'Restaurant Orders', 'admin/operations/restaurant.php', 'Food & Beverage', 'fas fa-utensils', 'Sidebar', '2026-09-20 11:32:40'),
(40, 'Payroll', 'admin/operations/payroll.php', 'Finance', 'fas fa-file-invoice-dollar', 'Sidebar', '2026-09-20 11:32:41'),
(41, 'Refunds', 'admin/operations/refunds.php', 'Finance', 'fas fa-undo', 'Sidebar', '2026-09-20 11:32:41'),
(42, 'Room Transfers', 'admin/operations/room_transfers.php', 'Front Desk', 'fas fa-exchange-alt', 'Sidebar', '2026-09-20 11:32:41'),
(43, 'Daily Closing', 'admin/operations/daily_closing.php', 'Finance', 'fas fa-cash-register', 'Sidebar', '2026-09-20 11:32:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `admin_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`admin_id`, `page_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(3, 2),
(3, 3),
(4, 4),
(4, 5),
(4, 6);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `attendance_date` (`attendance_date`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`customer_id`),
  ADD KEY `room_id2` (`room_id`);

--
-- Indexes for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_services` (`booking_id`),
  ADD KEY `booking_services2` (`service_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_closing`
--
ALTER TABLE `daily_closing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `closing_date` (`closing_date`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_expenses`
--
ALTER TABLE `hotel_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_services`
--
ALTER TABLE `hotel_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_suppliers`
--
ALTER TABLE `hotel_suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `housekeeping`
--
ALTER TABLE `housekeeping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `laundry_orders`
--
ALTER TABLE `laundry_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment` (`booking_id`);

--
-- Indexes for table `payment_refunds`
--
ALTER TABLE `payment_refunds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_orders`
--
ALTER TABLE `restaurant_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_type_id`);

--
-- Indexes for table `room_transfers`
--
ALTER TABLE `room_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_orders`
--
ALTER TABLE `service_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_day` (`staff_id`,`attendance_date`);

--
-- Indexes for table `staff_leave`
--
ALTER TABLE `staff_leave`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_payroll`
--
ALTER TABLE `staff_payroll`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_pages`
--
ALTER TABLE `system_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`admin_id`,`page_id`),
  ADD KEY `page_id` (`page_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35076;

--
-- AUTO_INCREMENT for table `booking_services`
--
ALTER TABLE `booking_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `daily_closing`
--
ALTER TABLE `daily_closing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_expenses`
--
ALTER TABLE `hotel_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_services`
--
ALTER TABLE `hotel_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_suppliers`
--
ALTER TABLE `hotel_suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping`
--
ALTER TABLE `housekeeping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laundry_orders`
--
ALTER TABLE `laundry_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment_refunds`
--
ALTER TABLE `payment_refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_orders`
--
ALTER TABLE `restaurant_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_transfers`
--
ALTER TABLE `room_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_orders`
--
ALTER TABLE `service_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_leave`
--
ALTER TABLE `staff_leave`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_payroll`
--
ALTER TABLE `staff_payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_pages`
--
ALTER TABLE `system_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `booking_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `room_id2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD CONSTRAINT `booking_services` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `booking_services2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payment` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `room_id` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`);

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `system_pages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

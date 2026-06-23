-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 02:04 AM
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
-- Database: `seafood_export`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetQuotationData` (IN `quotationId` INT)   BEGIN
    -- Quotation header
    SELECT 
        q.quotation_number,
        q.valid_until,
        q.payment_terms,
        q.shipping_terms,
        q.total_amount,
        u.company_name,
        u.contact_person,
        u.email,
        u.phone,
        u.address,
        u.city,
        u.country,
        ed.country as destination,
        ed.currency
    FROM export_quotations q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN export_destinations ed ON q.export_destination_id = ed.id
    WHERE q.id = quotationId;
    
    -- Quotation items
    SELECT 
        qi.product_name,
        qi.quantity_kg,
        qi.price_per_kg,
        qi.total_price
    FROM quotation_items qi
    WHERE qi.quotation_id = quotationId;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateInventoryOnOrder` (IN `orderId` INT)   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_product_id INT;
    DECLARE v_batch_id INT;
    DECLARE v_quantity_kg DECIMAL(10,2);
    
    DECLARE cur CURSOR FOR 
        SELECT product_id, batch_id, quantity_kg 
        FROM order_items 
        WHERE order_id = orderId;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_product_id, v_batch_id, v_quantity_kg;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Update batch quantity
        IF v_batch_id IS NOT NULL THEN
            UPDATE inventory_batches 
            SET current_quantity_kg = current_quantity_kg - v_quantity_kg
            WHERE id = v_batch_id;
        END IF;
        
        -- Update product total stock
        UPDATE products 
        SET stock_kg = stock_kg - v_quantity_kg
        WHERE id = v_product_id;
    END LOOP;
    
    CLOSE cur;
    
    -- Update order status
    UPDATE orders 
    SET order_status = 'Processing', 
        processed_date = CURDATE()
    WHERE id = orderId;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','manager','quality_controller','staff') DEFAULT 'staff',
  `phone` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `full_name`, `role`, `phone`, `last_login`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin@seafoodexport.com', 'Administrator', 'super_admin', '9876543210', NULL, '2026-03-26 01:04:10'),
(2, 'quality', '0192023a7bbd73250516f069df18b500', 'quality@seafoodexport.com', 'Quality Manager', 'quality_controller', '9876543211', NULL, '2026-03-26 01:04:10'),
(3, 'export', '0192023a7bbd73250516f069df18b500', 'export@seafoodexport.com', 'Export Manager', 'manager', '9876543212', NULL, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity_kg`, `added_date`) VALUES
(1, 3, 5, 25.00, '2026-03-08 17:30:00'),
(2, 3, 10, 15.00, '2026-03-08 17:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `export_destinations`
--

CREATE TABLE `export_destinations` (
  `id` int(11) NOT NULL,
  `country` varchar(100) NOT NULL,
  `country_code` varchar(3) NOT NULL,
  `region` varchar(50) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `currency_symbol` varchar(5) DEFAULT '$',
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `duty_percentage` decimal(5,2) DEFAULT 0.00,
  `shipping_multiplier` decimal(3,2) DEFAULT 1.00,
  `documentation_requirements` text DEFAULT NULL,
  `restrictions` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `export_destinations`
--

INSERT INTO `export_destinations` (`id`, `country`, `country_code`, `region`, `currency`, `currency_symbol`, `exchange_rate`, `tax_rate`, `duty_percentage`, `shipping_multiplier`, `documentation_requirements`, `restrictions`, `status`, `created_at`) VALUES
(1, 'United States', 'USA', 'North America', 'USD', '$', 83.3300, 0.00, 5.00, 1.50, 'FDA prior notice, HACCP certification', NULL, 1, '2026-03-26 01:04:10'),
(2, 'Japan', 'JPN', 'Asia', 'JPY', '¥', 0.5600, 8.00, 6.00, 1.80, 'JAS certification, export certificate', NULL, 1, '2026-03-26 01:04:10'),
(3, 'China', 'CHN', 'Asia', 'CNY', '¥', 11.5000, 10.00, 12.00, 1.20, 'Health certificate, GACC registration', NULL, 1, '2026-03-26 01:04:10'),
(4, 'European Union', 'EUR', 'Europe', 'EUR', '€', 90.5000, 7.00, 8.00, 1.60, 'EU health certificate, HACCP, traceability', NULL, 1, '2026-03-26 01:04:10'),
(5, 'United Kingdom', 'GBP', 'Europe', 'GBP', '£', 105.2000, 7.00, 8.00, 1.65, 'UK health certificate', NULL, 1, '2026-03-26 01:04:10'),
(6, 'UAE', 'ARE', 'Middle East', 'AED', 'د.إ', 22.7000, 5.00, 5.00, 1.30, 'Halal certificate, health certificate', NULL, 1, '2026-03-26 01:04:10'),
(7, 'Australia', 'AUS', 'Oceania', 'AUD', 'A$', 54.8000, 10.00, 5.00, 1.70, 'AQIS import permit, health certificate', NULL, 1, '2026-03-26 01:04:10'),
(8, 'Canada', 'CAN', 'North America', 'CAD', 'C$', 61.5000, 0.00, 5.00, 1.55, 'CFIA requirements', NULL, 1, '2026-03-26 01:04:10'),
(9, 'Singapore', 'SGP', 'Asia', 'SGD', 'S$', 62.0000, 7.00, 0.00, 1.10, 'AVA import permit', NULL, 1, '2026-03-26 01:04:10'),
(10, 'South Korea', 'KOR', 'Asia', 'KRW', '₩', 0.0620, 10.00, 10.00, 1.40, 'MFDS import clearance', NULL, 1, '2026-03-26 01:04:10'),
(11, 'Domestic', 'IND', 'India', 'INR', '₹', 1.0000, 5.00, 0.00, 1.00, 'GST invoice', NULL, 1, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `export_quotations`
--

CREATE TABLE `export_quotations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `export_destination_id` int(11) DEFAULT NULL,
  `valid_until` date NOT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `shipping_terms` enum('FOB','CIF','CFR','EXW','DDP','DAP') DEFAULT 'FOB',
  `status` enum('Draft','Sent','Accepted','Expired','Converted') DEFAULT 'Draft',
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fish_species`
--

CREATE TABLE `fish_species` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `scientific_name` varchar(150) DEFAULT NULL,
  `local_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `habitat` varchar(100) DEFAULT NULL,
  `season` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_species`
--

INSERT INTO `fish_species` (`id`, `name`, `scientific_name`, `local_name`, `description`, `habitat`, `season`, `image`, `status`, `created_at`) VALUES
(1, 'Indian Mackerel', 'Rastrelliger kanagurta', 'Bangda', 'Rich in omega-3 fatty acids, popular in Asian cuisine', 'Coastal waters', 'August-March', 'mackerel.jpg', 1, '2026-03-26 01:04:10'),
(2, 'Black Tiger Prawn', 'Penaeus monodon', 'Jinga', 'Large size prawn, premium quality for export', 'Estuarine waters', 'September-April', 'tiger_prawn.jpg', 1, '2026-03-26 01:04:10'),
(3, 'White Prawn', 'Litopenaeus vannamei', 'Vannamei', 'Cultured white shrimp, consistently high quality', 'Aquaculture', 'Year round', 'white_prawn.jpg', 1, '2026-03-26 01:04:10'),
(4, 'Yellowfin Tuna', 'Thunnus albacares', 'Kera', 'Sashimi-grade tuna, firm texture', 'Deep sea', 'October-May', 'tuna.jpg', 1, '2026-03-26 01:04:10'),
(5, 'Indian Sardine', 'Sardinella longiceps', 'Mathi/Chalai', 'Oil-rich fish, excellent for canning', 'Coastal waters', 'June-December', 'sardine.jpg', 1, '2026-03-26 01:04:10'),
(6, 'Seer Fish', 'Scomberomorus guttatus', 'Anjal/Surmai', 'Premium fish with firm flesh, no small bones', 'Coastal waters', 'August-March', 'seer.jpg', 1, '2026-03-26 01:04:10'),
(7, 'Indian Squid', 'Loligo duvauceli', 'Kanava', 'Tender calamari, ideal for rings and tubes', 'Coastal waters', 'October-April', 'squid.jpg', 1, '2026-03-26 01:04:10'),
(8, 'Blue Swimmer Crab', 'Portunus pelagicus', 'Nandu', 'Sweet meat, excellent for meat extraction', 'Coastal waters', 'August-March', 'crab.jpg', 1, '2026-03-26 01:04:10'),
(9, 'Bombay Duck', 'Harpadon nehereus', 'Bombil', 'Soft-textured fish, popular dried or fresh', 'Estuarine', 'September-February', 'bombay_duck.jpg', 1, '2026-03-26 01:04:10'),
(10, 'Red Snapper', 'Lutjanus campechanus', 'Chennai', 'Premium white fish, high market value', 'Rocky reefs', 'October-April', 'snapper.jpg', 1, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `catch_date` date NOT NULL,
  `processing_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_quantity_kg` decimal(10,2) NOT NULL,
  `current_quantity_kg` decimal(10,2) NOT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `temperature_log` text DEFAULT NULL,
  `quality_check_status` enum('Pending','Passed','Failed','Quarantine') DEFAULT 'Pending',
  `checked_by` int(11) DEFAULT NULL,
  `check_date` date DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `product_id`, `batch_number`, `catch_date`, `processing_date`, `expiry_date`, `initial_quantity_kg`, `current_quantity_kg`, `storage_location`, `temperature_log`, `quality_check_status`, `checked_by`, `check_date`, `certificate_number`, `notes`, `created_at`) VALUES
(1, 1, 'PRAWN-20260301-B1', '2026-03-01', '2026-03-02', '2027-03-01', 1000.00, 850.50, 'Freezer A-12', 'Consistent -40°C', 'Passed', 2, '2026-03-03', NULL, NULL, '2026-03-26 01:04:10'),
(2, 1, 'PRAWN-20260301-B2', '2026-03-01', '2026-03-02', '2027-03-01', 1000.00, 950.00, 'Freezer A-13', 'Consistent -40°C', 'Passed', 2, '2026-03-03', NULL, NULL, '2026-03-26 01:04:10'),
(3, 1, 'PRAWN-20260301-B3', '2026-03-01', '2026-03-02', '2027-03-01', 1000.00, 700.00, 'Freezer A-14', 'Consistent -40°C', 'Passed', 2, '2026-03-03', NULL, NULL, '2026-03-26 01:04:10'),
(4, 2, 'VAN-20260305-B1', '2026-03-05', '2026-03-06', '2027-03-05', 1200.00, 1200.00, 'Freezer B-05', 'Maintained -40°C', 'Pending', NULL, NULL, NULL, NULL, '2026-03-26 01:04:10'),
(5, 4, 'TUNA-20260304-B1', '2026-03-04', '2026-03-05', '2027-03-04', 600.00, 600.00, 'Freezer C-08', 'Blast frozen, stable', 'Passed', 2, '2026-03-06', NULL, NULL, '2026-03-26 01:04:10'),
(6, 9, 'BOMBAY-20260215-B1', '2026-02-15', '2026-02-18', '2026-08-15', 300.00, 300.00, 'Dry Store Rack 3', 'Ambient, 25°C', 'Passed', 2, '2026-02-20', NULL, NULL, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_status`
-- (See below for the actual view)
--
CREATE TABLE `inventory_status` (
`product_id` int(11)
,`product_name` varchar(200)
,`product_code` varchar(50)
,`species` varchar(100)
,`processing_type` varchar(50)
,`grade` enum('Premium','A','B','C','Standard')
,`size_range` varchar(50)
,`total_stock` decimal(10,2)
,`batch_count` bigint(21)
,`passed_stock` decimal(32,2)
,`pending_qa` decimal(32,2)
,`earliest_expiry` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_sales`
-- (See below for the actual view)
--
CREATE TABLE `monthly_sales` (
`month` varchar(7)
,`order_count` bigint(21)
,`total_revenue_inr` decimal(32,2)
,`total_revenue_foreign` decimal(32,2)
,`avg_order_value` decimal(14,6)
,`unique_customers` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'order_shipped', 'Order Shipped', 'Your order #SEAF-20260308-001 has been shipped via Maersk Line', 'track_shipment.php?id=1', 0, '2026-03-26 01:04:10'),
(2, 2, 'order_confirmed', 'Order Confirmed', 'Your order #SEAF-20260308-002 has been confirmed', 'my_orders.php', 1, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `export_destination_id` int(11) DEFAULT NULL,
  `total_amount_inr` decimal(10,2) NOT NULL,
  `total_amount_foreign` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `shipping_cost_inr` decimal(10,2) DEFAULT 0.00,
  `duty_amount` decimal(10,2) DEFAULT 0.00,
  `insurance_amount` decimal(10,2) DEFAULT 0.00,
  `grand_total_inr` decimal(10,2) NOT NULL,
  `payment_method` enum('Bank Transfer','Letter of Credit','Documentary Credit','COD','Online') DEFAULT 'Bank Transfer',
  `payment_status` enum('Pending','Partial','Completed','Failed','Awaiting LC') DEFAULT 'Pending',
  `payment_due_date` date DEFAULT NULL,
  `order_status` enum('Pending','Confirmed','Processing','Packing','Ready to Ship','Shipped','In Transit','Delivered','Cancelled','On Hold') DEFAULT 'Pending',
  `shipping_address` text NOT NULL,
  `shipping_terms` enum('FOB','CIF','CFR','EXW','DDP','DAP') DEFAULT 'FOB',
  `port_of_loading` varchar(100) DEFAULT NULL,
  `port_of_discharge` varchar(100) DEFAULT NULL,
  `shipping_line` varchar(100) DEFAULT NULL,
  `vessel_name` varchar(100) DEFAULT NULL,
  `container_number` varchar(50) DEFAULT NULL,
  `container_type` varchar(50) DEFAULT NULL,
  `bill_of_lading` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_date` date DEFAULT NULL,
  `packed_date` date DEFAULT NULL,
  `shipped_date` date DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `export_destination_id`, `total_amount_inr`, `total_amount_foreign`, `currency`, `exchange_rate`, `shipping_cost_inr`, `duty_amount`, `insurance_amount`, `grand_total_inr`, `payment_method`, `payment_status`, `payment_due_date`, `order_status`, `shipping_address`, `shipping_terms`, `port_of_loading`, `port_of_discharge`, `shipping_line`, `vessel_name`, `container_number`, `container_type`, `bill_of_lading`, `tracking_number`, `tracking_url`, `invoice_number`, `invoice_date`, `order_date`, `processed_date`, `packed_date`, `shipped_date`, `estimated_delivery`, `delivery_date`, `notes`, `created_by`, `updated_at`) VALUES
(1, 1, 'SEAF-20260308-001', 1, 723750.00, 8685.00, 'USD', 83.3300, 50000.00, 0.00, 15000.00, 788750.00, 'Letter of Credit', 'Completed', NULL, 'Shipped', '123 Harbor Street, Los Angeles, CA 90001, USA', 'CIF', 'Mumbai', 'Los Angeles', 'Maersk Line', NULL, 'MAEU1234567', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-08 16:06:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 'SEAF-20260308-002', 2, 425000.00, 758928.57, 'JPY', 0.5600, 45000.00, 34000.00, 10000.00, 514000.00, 'Bank Transfer', 'Pending', NULL, 'Confirmed', '5-2-1 Tsukiji, Tokyo, Japan', 'FOB', 'Chennai', 'Tokyo', 'NYK Line', NULL, 'NYK7890123', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-08 16:39:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `order_status_change_notification` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_status != OLD.order_status THEN
        INSERT INTO notifications (user_id, type, title, message, link)
        VALUES (
            NEW.user_id,
            'order_status',
            CONCAT('Order ', NEW.order_number, ' Status Updated'),
            CONCAT('Your order status has been updated to: ', NEW.order_status),
            CONCAT('my_orders.php')
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `packaging_type` varchar(50) DEFAULT NULL,
  `packaging_units` int(11) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `size_range` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `batch_id`, `product_name`, `product_code`, `quantity_kg`, `price_per_kg`, `total_price`, `packaging_type`, `packaging_units`, `grade`, `size_range`, `created_at`) VALUES
(1, 1, 1, 1, 'Black Tiger Prawns IQF', 'PRAWN-TIGER-001', 850.00, 850.00, 722500.00, 'Master Carton', 43, 'Premium', '21-25 pcs/kg', '2026-03-26 01:04:10'),
(2, 2, 4, 5, 'Yellowfin Tuna Loins', 'TUNA-YFT-004', 500.00, 850.00, 425000.00, 'Vacuum Pack', 50, 'Premium', '5-10 kg loins', '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_summary`
-- (See below for the actual view)
--
CREATE TABLE `order_summary` (
`id` int(11)
,`order_number` varchar(50)
,`company_name` varchar(200)
,`contact_person` varchar(100)
,`destination_country` varchar(100)
,`order_date` timestamp
,`total_amount_inr` decimal(10,2)
,`currency` varchar(3)
,`total_amount_foreign` decimal(10,2)
,`grand_total_inr` decimal(10,2)
,`order_status` enum('Pending','Confirmed','Processing','Packing','Ready to Ship','Shipped','In Transit','Delivered','Cancelled','On Hold')
,`payment_status` enum('Pending','Partial','Completed','Failed','Awaiting LC')
,`shipping_terms` enum('FOB','CIF','CFR','EXW','DDP','DAP')
,`estimated_delivery` date
);

-- --------------------------------------------------------

--
-- Table structure for table `packaging_types`
--

CREATE TABLE `packaging_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity_kg` decimal(10,2) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packaging_types`
--

INSERT INTO `packaging_types` (`id`, `name`, `description`, `capacity_kg`, `material`, `created_at`) VALUES
(1, 'Master Carton', 'Corrugated cardboard box for bulk export', 20.00, 'Cardboard', '2026-03-26 01:04:10'),
(2, 'Vacuum Pack', 'Vacuum sealed plastic pouch', 1.00, 'Plastic', '2026-03-26 01:04:10'),
(3, 'Tray Pack', 'Thermoformed tray with film', 0.50, 'Plastic', '2026-03-26 01:04:10'),
(4, 'Brine Pack', 'Frozen block in brine solution', 10.00, 'Plastic', '2026-03-26 01:04:10'),
(5, 'Bag-in-Box', 'Plastic liner inside cardboard box', 10.00, 'Composite', '2026-03-26 01:04:10'),
(6, 'Metal Can', 'Hermetically sealed metal container', 1.50, 'Tinplate', '2026-03-26 01:04:10'),
(7, 'Glass Jar', 'Glass container with lid', 0.50, 'Glass', '2026-03-26 01:04:10'),
(8, 'Poly Bag', 'Polyethylene bag', 5.00, 'Plastic', '2026-03-26 01:04:10'),
(9, 'Jute Bag', 'Traditional jute sack', 25.00, 'Jute', '2026-03-26 01:04:10'),
(10, 'Insulated Container', 'EPS foam box with ice packs', 15.00, 'EPS Foam', '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price_history`
--

INSERT INTO `price_history` (`id`, `product_id`, `price_per_kg`, `effective_from`, `effective_to`, `notes`, `updated_by`, `created_at`) VALUES
(1, 1, 820.00, '2026-01-01', '2026-02-28', 'Winter season price', 1, '2026-03-26 01:04:10'),
(2, 1, 850.00, '2026-03-01', NULL, 'Peak season price increase', 1, '2026-03-26 01:04:10'),
(3, 4, 1200.00, '2026-01-01', NULL, 'Stable premium price', 1, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `processing_types`
--

CREATE TABLE `processing_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `shelf_life_days` int(11) DEFAULT NULL,
  `storage_temperature` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `processing_types`
--

INSERT INTO `processing_types` (`id`, `name`, `description`, `shelf_life_days`, `storage_temperature`, `created_at`) VALUES
(1, 'Fresh', 'Fresh catch, stored on ice, delivered within 24-48 hours', 3, '0-4°C', '2026-03-26 01:04:10'),
(2, 'IQF Frozen', 'Individually Quick Frozen, preserved at -40°C', 730, '-20°C to -25°C', '2026-03-26 01:04:10'),
(3, 'Block Frozen', 'Frozen in blocks with glaze', 540, '-18°C to -22°C', '2026-03-26 01:04:10'),
(4, 'Dried', 'Sun-dried or mechanical drying, moisture reduced', 180, 'Ambient, cool dry place', '2026-03-26 01:04:10'),
(5, 'Canned', 'Packed in brine, oil, or sauce, shelf-stable', 1095, 'Ambient', '2026-03-26 01:04:10'),
(6, 'Marinated', 'Pre-marinated with spices, ready-to-cook', 30, '0-4°C', '2026-03-26 01:04:10'),
(7, 'Breaded', 'Coated with breadcrumbs, ready-to-fry', 365, '-18°C', '2026-03-26 01:04:10'),
(8, 'Smoked', 'Hot or cold smoked for flavor', 60, '0-4°C', '2026-03-26 01:04:10'),
(9, 'Surimi', 'Minced fish paste, imitation crab meat', 540, '-20°C', '2026-03-26 01:04:10'),
(10, 'HGP', 'Headless, Gutted, and Gilled', 14, '0-4°C', '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `species_id` int(11) DEFAULT NULL,
  `processing_type_id` int(11) DEFAULT NULL,
  `packaging_type_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `grade` enum('Premium','A','B','C','Standard') DEFAULT 'Standard',
  `size_range` varchar(50) DEFAULT NULL COMMENT 'e.g., 20-30 pcs/kg',
  `catch_area` varchar(100) DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `stock_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_order_kg` decimal(10,2) DEFAULT 10.00,
  `moisture_content` decimal(5,2) DEFAULT NULL,
  `fat_content` decimal(5,2) DEFAULT NULL,
  `protein_content` decimal(5,2) DEFAULT NULL,
  `preservation_method` varchar(100) DEFAULT NULL,
  `certification` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `species_id`, `processing_type_id`, `packaging_type_id`, `product_code`, `name`, `description`, `grade`, `size_range`, `catch_area`, `price_per_kg`, `stock_kg`, `minimum_order_kg`, `moisture_content`, `fat_content`, `protein_content`, `preservation_method`, `certification`, `image`, `featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 1, 'PRAWN-TIGER-001', 'Black Tiger Prawns IQF', 'Premium quality black tiger prawns, individually quick frozen, perfect for export', 'Premium', '21-25 pcs/kg', 'Bay of Bengal', 850.00, 2500.50, 50.00, 76.00, 1.20, 22.50, 'IQF at -40°C', 'HACCP, BRC, MSC', 'tiger_prawns.jpg', 1, 1, '2026-03-26 01:04:10', NULL),
(2, 3, 2, 2, 'PRAWN-VAN-002', 'Vannamei White Prawns PDTO', 'Peeled and deveined white prawns, tail-on, individually frozen', 'A', '31-40 pcs/kg', 'Aquaculture, Andhra Pradesh', 750.00, 3500.75, 25.00, 77.00, 1.10, 21.80, 'IQF', 'HACCP, ASC', 'vannamei.jpg', 1, 1, '2026-03-26 01:04:10', NULL),
(3, 1, 3, 1, 'MACKEREL-BLK-003', 'Frozen Indian Mackerel', 'Whole round mackerel, block frozen, excellent for canning', 'Standard', '15-20 pcs/kg', 'Kerala Coast', 450.00, 1800.00, 50.00, 74.00, 8.50, 17.00, 'Block frozen', 'HACCP', 'mackerel.jpg', 0, 1, '2026-03-26 01:04:10', NULL),
(4, 4, 2, 2, 'TUNA-YFT-004', 'Yellowfin Tuna Loins', 'Sashimi-grade yellowfin tuna loins, vacuum packed', 'Premium', '5-10 kg per loin', 'Indian Ocean', 1250.00, 1200.00, 100.00, 68.00, 2.50, 29.00, 'Blast frozen', 'HACCP, MSC', 'tuna_loins.jpg', 1, 1, '2026-03-26 01:04:10', NULL),
(5, 7, 2, 2, 'SQUID-CAL-005', 'Calamari Rings', 'Cleaned squid tubes cut into rings, individually frozen', 'A', '100-200 rings/kg', 'Gujarat Coast', 650.00, 800.25, 30.00, 82.00, 1.50, 16.50, 'IQF', 'HACCP', 'calamari.jpg', 1, 1, '2026-03-26 01:04:10', NULL),
(6, 6, 1, 3, 'SEER-STK-006', 'Fresh Seer Fish Steaks', 'Fresh-cut seer fish steaks, packed in insulated boxes with ice', 'Premium', '200-300g per steak', 'Mangalore', 950.00, 150.00, 10.00, 75.00, 4.50, 20.00, 'Ice packed', 'HACCP', 'seer_steaks.jpg', 1, 1, '2026-03-26 01:04:10', NULL),
(7, 8, 6, 2, 'CRAB-MAR-007', 'Marinated Crab Clusters', 'Blue crab clusters in special garlic butter marinade', 'A', '4-6 clusters/kg', 'Odisha Coast', 750.00, 200.00, 15.00, 78.00, 2.50, 18.50, 'Chilled', 'HACCP', 'crab_clusters.jpg', 0, 1, '2026-03-26 01:04:10', NULL),
(8, 5, 5, 6, 'SARD-CAN-008', 'Sardines in Olive Oil', 'Premium sardines packed in extra virgin olive oil', 'Standard', '6-8 pcs per 200g can', 'Kerala', 350.00, 5000.00, 120.00, 65.00, 12.00, 22.00, 'Canned', 'FDA, FSSAI', 'canned_sardines.jpg', 0, 1, '2026-03-26 01:04:10', NULL),
(9, 9, 4, 8, 'BOMBAY-DRY-009', 'Dried Bombay Duck', 'Sun-dried Bombay duck, traditional processing, ready to fry', 'Standard', '10-15 pcs/kg', 'Maharashtra Coast', 380.00, 300.00, 20.00, 15.50, 3.20, 70.00, 'Sun-dried', 'Organic', 'bombay_duck_dry.jpg', 0, 1, '2026-03-26 01:04:10', NULL),
(10, 10, 2, 2, 'SNAPPER-FIL-010', 'Red Snapper Fillets', 'Skin-on red snapper fillets, individually frozen', 'A', '200-300g per fillet', 'Tamil Nadu Coast', 890.00, 450.00, 25.00, 76.00, 1.80, 21.50, 'IQF', 'HACCP', 'snapper_fillets.jpg', 1, 1, '2026-03-26 01:04:10', NULL);

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `check_low_stock` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock_kg < 100 AND (OLD.stock_kg >= 100 OR OLD.stock_kg IS NULL) THEN
        INSERT INTO notifications (user_id, type, title, message, link, is_read)
        VALUES (
            NULL, -- Admin notification
            'inventory',
            'Low Stock Alert',
            CONCAT('Product "', NEW.name, '" has low stock: ', NEW.stock_kg, ' kg'),
            'admin/manage_products.php'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `quality_checks`
--

CREATE TABLE `quality_checks` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `check_date` date NOT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `ph_level` decimal(4,2) DEFAULT NULL,
  `organoleptic_score` int(11) DEFAULT NULL COMMENT '1-10 scale',
  `appearance` enum('Excellent','Good','Average','Poor') DEFAULT NULL,
  `odor` enum('Fresh','Neutral','Slightly Off','Offensive') DEFAULT NULL,
  `texture` enum('Firm','Slightly Soft','Soft','Mushy') DEFAULT NULL,
  `microbiological_test` enum('Pass','Fail','Pending') DEFAULT 'Pending',
  `chemical_test` enum('Pass','Fail','Pending') DEFAULT 'Pending',
  `heavy_metals_test` enum('Pass','Fail','Pending') DEFAULT 'Pending',
  `histamine_level` decimal(10,2) DEFAULT NULL COMMENT 'ppm',
  `tvbn_value` decimal(10,2) DEFAULT NULL COMMENT 'mg/100g',
  `remarks` text DEFAULT NULL,
  `next_check_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quality_checks`
--

INSERT INTO `quality_checks` (`id`, `batch_id`, `check_date`, `checked_by`, `temperature`, `ph_level`, `organoleptic_score`, `appearance`, `odor`, `texture`, `microbiological_test`, `chemical_test`, `heavy_metals_test`, `histamine_level`, `tvbn_value`, `remarks`, `next_check_date`, `created_at`) VALUES
(1, 1, '2026-03-03', 2, -40.00, 7.20, 9, 'Excellent', 'Fresh', 'Firm', 'Pass', 'Pass', 'Pass', 5.20, 8.50, 'Premium quality batch, ready for export', NULL, '2026-03-26 01:04:10'),
(2, 3, '2026-03-03', 2, -40.00, 7.10, 8, 'Good', 'Fresh', 'Firm', 'Pass', 'Pass', 'Pass', 6.10, 9.20, 'Good quality, minor size variation', NULL, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_documents`
--

CREATE TABLE `shipping_documents` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `document_type` enum('Invoice','Packing List','Bill of Lading','Certificate of Origin','Health Certificate','Phytosanitary','Insurance','Other') DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `phone_secondary` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `import_license` varchar(100) DEFAULT NULL,
  `business_type` enum('Importer','Distributor','Wholesaler','Retailer','Processor') DEFAULT 'Importer',
  `payment_terms` varchar(50) DEFAULT 'Advance',
  `credit_limit` decimal(10,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_name`, `contact_person`, `email`, `password`, `phone`, `phone_secondary`, `address`, `city`, `state`, `country`, `postal_code`, `gst_number`, `import_license`, `business_type`, `payment_terms`, `credit_limit`, `status`, `last_login`, `created_at`) VALUES
(1, 'Global Seafood Imports Inc', 'John Smith', 'john@globalseafood.com', 'b1471adc34c852d9ca3f03f5f47ff496', '+1-555-1234567', NULL, '123 Harbor Street', 'Los Angeles', 'California', 'USA', '90001', NULL, 'IMP-USA-2026-001', 'Importer', 'Letter of Credit', 100000.00, 1, NULL, '2026-03-26 01:04:10'),
(2, 'Tokyo Fish Market Ltd', 'Tanaka Hiroshi', 'tanaka@tokyofish.jp', 'b1471adc34c852d9ca3f03f5f47ff496', '+81-3-12345678', NULL, '5-2-1 Tsukiji', 'Tokyo', 'Tokyo', 'Japan', '104-0045', NULL, 'JPN-IMP-2026-045', 'Importer', 'LC at sight', 8000000.00, 1, NULL, '2026-03-26 01:04:10'),
(3, 'Mumbai Fresh Seafood', 'Rajesh Patel', 'rajesh@mumbaifresh.in', 'b1471adc34c852d9ca3f03f5f47ff496', '9876543210', NULL, '45 Sassoon Dock', 'Mumbai', 'Maharashtra', 'India', '400005', '27AAAAA0000A1Z5', NULL, 'Wholesaler', 'Advance', 500000.00, 1, NULL, '2026-03-26 01:04:10');

-- --------------------------------------------------------

--
-- Structure for view `inventory_status`
--
DROP TABLE IF EXISTS `inventory_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_status`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, `p`.`product_code` AS `product_code`, `fs`.`name` AS `species`, `pt`.`name` AS `processing_type`, `p`.`grade` AS `grade`, `p`.`size_range` AS `size_range`, `p`.`stock_kg` AS `total_stock`, count(distinct `ib`.`id`) AS `batch_count`, sum(case when `ib`.`quality_check_status` = 'Passed' then `ib`.`current_quantity_kg` else 0 end) AS `passed_stock`, sum(case when `ib`.`quality_check_status` = 'Pending' then `ib`.`current_quantity_kg` else 0 end) AS `pending_qa`, min(`ib`.`expiry_date`) AS `earliest_expiry` FROM (((`products` `p` left join `fish_species` `fs` on(`p`.`species_id` = `fs`.`id`)) left join `processing_types` `pt` on(`p`.`processing_type_id` = `pt`.`id`)) left join `inventory_batches` `ib` on(`p`.`id` = `ib`.`product_id`)) GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `monthly_sales`
--
DROP TABLE IF EXISTS `monthly_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_sales`  AS SELECT date_format(`orders`.`order_date`,'%Y-%m') AS `month`, count(0) AS `order_count`, sum(`orders`.`grand_total_inr`) AS `total_revenue_inr`, sum(`orders`.`total_amount_foreign`) AS `total_revenue_foreign`, avg(`orders`.`grand_total_inr`) AS `avg_order_value`, count(distinct `orders`.`user_id`) AS `unique_customers` FROM `orders` WHERE `orders`.`order_status` <> 'Cancelled' GROUP BY date_format(`orders`.`order_date`,'%Y-%m') ORDER BY date_format(`orders`.`order_date`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure for view `order_summary`
--
DROP TABLE IF EXISTS `order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`order_number` AS `order_number`, `u`.`company_name` AS `company_name`, `u`.`contact_person` AS `contact_person`, `ed`.`country` AS `destination_country`, `o`.`order_date` AS `order_date`, `o`.`total_amount_inr` AS `total_amount_inr`, `o`.`currency` AS `currency`, `o`.`total_amount_foreign` AS `total_amount_foreign`, `o`.`grand_total_inr` AS `grand_total_inr`, `o`.`order_status` AS `order_status`, `o`.`payment_status` AS `payment_status`, `o`.`shipping_terms` AS `shipping_terms`, `o`.`estimated_delivery` AS `estimated_delivery` FROM ((`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `export_destinations` `ed` on(`o`.`export_destination_id` = `ed`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `export_destinations`
--
ALTER TABLE `export_destinations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `country_code` (`country_code`);

--
-- Indexes for table `export_quotations`
--
ALTER TABLE `export_quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_number` (`quotation_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `export_destination_id` (`export_destination_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fish_species`
--
ALTER TABLE `fish_species`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `checked_by` (`checked_by`),
  ADD KEY `idx_inventory_product` (`product_id`),
  ADD KEY `idx_inventory_expiry` (`expiry_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `export_destination_id` (`export_destination_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_date` (`order_date`),
  ADD KEY `idx_orders_destination` (`export_destination_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_batch` (`batch_id`);

--
-- Indexes for table `packaging_types`
--
ALTER TABLE `packaging_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `processing_types`
--
ALTER TABLE `processing_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `species_id` (`species_id`),
  ADD KEY `processing_type_id` (`processing_type_id`),
  ADD KEY `packaging_type_id` (`packaging_type_id`),
  ADD KEY `idx_products_species` (`species_id`),
  ADD KEY `idx_products_processing` (`processing_type_id`),
  ADD KEY `idx_products_status` (`status`);

--
-- Indexes for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `checked_by` (`checked_by`),
  ADD KEY `idx_quality_batch` (`batch_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipping_documents`
--
ALTER TABLE `shipping_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `gst_number` (`gst_number`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `export_destinations`
--
ALTER TABLE `export_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `export_quotations`
--
ALTER TABLE `export_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fish_species`
--
ALTER TABLE `fish_species`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `packaging_types`
--
ALTER TABLE `packaging_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `processing_types`
--
ALTER TABLE `processing_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quality_checks`
--
ALTER TABLE `quality_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_documents`
--
ALTER TABLE `shipping_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `export_quotations`
--
ALTER TABLE `export_quotations`
  ADD CONSTRAINT `export_quotations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `export_quotations_ibfk_2` FOREIGN KEY (`export_destination_id`) REFERENCES `export_destinations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `export_quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `inventory_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_batches_ibfk_2` FOREIGN KEY (`checked_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`export_destination_id`) REFERENCES `export_destinations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `price_history`
--
ALTER TABLE `price_history`
  ADD CONSTRAINT `price_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_history_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`species_id`) REFERENCES `fish_species` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`processing_type_id`) REFERENCES `processing_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD CONSTRAINT `quality_checks_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quality_checks_ibfk_2` FOREIGN KEY (`checked_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `export_quotations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotation_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shipping_documents`
--
ALTER TABLE `shipping_documents`
  ADD CONSTRAINT `shipping_documents_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `update_exchange_rates` ON SCHEDULE EVERY 1 DAY STARTS '2026-03-27 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- In production, this would call an API
    -- For demo, we'll just log it
    INSERT INTO price_history (product_id, price_per_kg, effective_from, notes)
    SELECT id, price_per_kg, CURDATE(), 'Daily rate check'
    FROM products
    WHERE id = 1;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

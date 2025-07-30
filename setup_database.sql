-- Tripti Dry Ice Database Setup
-- Run this SQL to create all necessary tables

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `tripti_dryice` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tripti_dryice`;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL UNIQUE,
  `payment_id` VARCHAR(100) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `customer_address` TEXT NOT NULL,
  `pin_code` VARCHAR(10) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `gst` DECIMAL(10,2) NOT NULL,
  `shipping` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `porter_order_id` VARCHAR(100) NULL,
  `porter_tracking_url` TEXT NULL,
  `porter_status` VARCHAR(50) DEFAULT 'Pending',
  `payment_status` VARCHAR(50) DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_porter_order_id` (`porter_order_id`)
);

-- Order items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `item_price` DECIMAL(10,2) NOT NULL,
  `item_quantity` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`)
);

-- Customer details table (for additional customer info)
CREATE TABLE IF NOT EXISTS `customer_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `landmark` VARCHAR(255) NULL,
  `apartment` VARCHAR(255) NULL,
  `latitude` DECIMAL(10,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`)
);

-- Porter tracking table
CREATE TABLE IF NOT EXISTS `porter_tracking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `porter_order_id` VARCHAR(100) NOT NULL,
  `tracking_url` TEXT NULL,
  `status` VARCHAR(50) DEFAULT 'Booked',
  `estimated_pickup_time` DATETIME NULL,
  `estimated_delivery_time` DATETIME NULL,
  `delivery_charge` DECIMAL(10,2) NULL,
  `currency` VARCHAR(10) DEFAULT 'INR',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_porter_order_id` (`porter_order_id`)
);

-- Payment logs table
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `payment_id` VARCHAR(100) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Razorpay',
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'INR',
  `status` VARCHAR(50) DEFAULT 'Pending',
  `gateway_response` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_payment_id` (`payment_id`)
);

-- System logs table
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NULL,
  `log_type` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `data` JSON NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_log_type` (`log_type`),
  INDEX `idx_created_at` (`created_at`)
);

-- Insert sample data for testing
INSERT INTO `orders` (`order_id`, `payment_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `pin_code`, `subtotal`, `gst`, `shipping`, `total`, `payment_status`) VALUES
('order_test_001', 'pay_test_001', 'Test Customer', '9876543210', 'test@example.com', 'Test Address, Mumbai', '400001', 500.00, 90.00, 50.00, 640.00, 'Success');

INSERT INTO `order_items` (`order_id`, `item_name`, `item_price`, `item_quantity`) VALUES
('order_test_001', 'Dry Ice Nuggets (5kg)', 250.00, 2);

-- Create views for easy querying
CREATE OR REPLACE VIEW `order_summary` AS
SELECT 
    o.order_id,
    o.customer_name,
    o.customer_phone,
    o.customer_email,
    o.total,
    o.payment_status,
    o.porter_status,
    o.created_at,
    COUNT(oi.id) as item_count,
    GROUP_CONCAT(CONCAT(oi.item_name, ' x', oi.item_quantity) SEPARATOR ', ') as items
FROM orders o
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id;

-- Create stored procedure for order creation
DELIMITER //
CREATE PROCEDURE `CreateOrder`(
    IN p_order_id VARCHAR(50),
    IN p_payment_id VARCHAR(100),
    IN p_customer_name VARCHAR(100),
    IN p_customer_phone VARCHAR(20),
    IN p_customer_email VARCHAR(100),
    IN p_customer_address TEXT,
    IN p_pin_code VARCHAR(10),
    IN p_subtotal DECIMAL(10,2),
    IN p_gst DECIMAL(10,2),
    IN p_shipping DECIMAL(10,2),
    IN p_total DECIMAL(10,2)
)
BEGIN
    INSERT INTO orders (
        order_id, payment_id, customer_name, customer_phone, customer_email,
        customer_address, pin_code, subtotal, gst, shipping, total
    ) VALUES (
        p_order_id, p_payment_id, p_customer_name, p_customer_phone, p_customer_email,
        p_customer_address, p_pin_code, p_subtotal, p_gst, p_shipping, p_total
    );
    
    SELECT 'Order created successfully' as message;
END //
DELIMITER ;

-- Create stored procedure for order status update
DELIMITER //
CREATE PROCEDURE `UpdateOrderStatus`(
    IN p_order_id VARCHAR(50),
    IN p_porter_order_id VARCHAR(100),
    IN p_tracking_url TEXT,
    IN p_status VARCHAR(50)
)
BEGIN
    UPDATE orders 
    SET porter_order_id = p_porter_order_id,
        porter_tracking_url = p_tracking_url,
        porter_status = p_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE order_id = p_order_id;
    
    SELECT 'Order status updated successfully' as message;
END //
DELIMITER ;

-- Show table structure
SHOW TABLES;

-- Show sample data
SELECT * FROM order_summary LIMIT 5; 
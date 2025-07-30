-- orders table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `payment_id` VARCHAR(100) NOT NULL,
  `customer_name` VARCHAR(100),
  `customer_phone` VARCHAR(20),
  `customer_email` VARCHAR(100),
  `customer_address` TEXT,
  `pin_code` VARCHAR(10),
  `subtotal` DECIMAL(10,2),
  `gst` DECIMAL(10,2),
  `shipping` DECIMAL(10,2),
  `total` DECIMAL(10,2),
  `porter_order_id` VARCHAR(100),
  `porter_tracking_url` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- order_items table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL,
  `item_name` VARCHAR(255),
  `item_price` DECIMAL(10,2),
  `item_quantity` INT
);

ALTER TABLE `reservations` ADD COLUMN `total_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `status`;
ALTER TABLE `reservations` ADD COLUMN `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending' AFTER `total_price`;
ALTER TABLE `reservations` ADD COLUMN `payment_method` VARCHAR(50) NULL DEFAULT NULL AFTER `payment_status`;
ALTER TABLE `reservations` ADD COLUMN `card_last_digits` VARCHAR(4) NULL DEFAULT NULL AFTER `payment_method`;

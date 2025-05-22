-- Check if columns exist before adding them
SET @check_total_price = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = 'greenwork' 
    AND table_name = 'reservations' 
    AND column_name = 'total_price'
);

SET @sql_total_price = IF(@check_total_price = 0, 
    'ALTER TABLE `reservations` ADD COLUMN `total_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `status`;', 
    'SELECT "Column total_price already exists" AS message;'
);

PREPARE stmt FROM @sql_total_price;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @check_payment_status = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = 'greenwork' 
    AND table_name = 'reservations' 
    AND column_name = 'payment_status'
);

SET @sql_payment_status = IF(@check_payment_status = 0, 
    'ALTER TABLE `reservations` ADD COLUMN `payment_status` ENUM("pending", "completed", "failed") DEFAULT "pending" AFTER `total_price`;', 
    'SELECT "Column payment_status already exists" AS message;'
);

PREPARE stmt FROM @sql_payment_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @check_payment_method = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = 'greenwork' 
    AND table_name = 'reservations' 
    AND column_name = 'payment_method'
);

SET @sql_payment_method = IF(@check_payment_method = 0, 
    'ALTER TABLE `reservations` ADD COLUMN `payment_method` VARCHAR(50) NULL DEFAULT NULL AFTER `payment_status`;', 
    'SELECT "Column payment_method already exists" AS message;'
);

PREPARE stmt FROM @sql_payment_method;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @check_card_last_digits = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = 'greenwork' 
    AND table_name = 'reservations' 
    AND column_name = 'card_last_digits'
);

SET @sql_card_last_digits = IF(@check_card_last_digits = 0, 
    'ALTER TABLE `reservations` ADD COLUMN `card_last_digits` VARCHAR(4) NULL DEFAULT NULL AFTER `payment_method`;', 
    'SELECT "Column card_last_digits already exists" AS message;'
);

PREPARE stmt FROM @sql_card_last_digits;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

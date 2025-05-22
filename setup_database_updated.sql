-- Script para recrear la base de datos desde cero
-- Primero, eliminamos la base de datos si existe
DROP DATABASE IF EXISTS `greenwork`;

-- Creamos la nueva base de datos
CREATE DATABASE `greenwork` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `greenwork`;

-- -----------------------------------------------------
-- Table: users (ahora sin company_id)
-- -----------------------------------------------------
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `preferred_language` ENUM('en','es') NOT NULL DEFAULT 'en',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- -----------------------------------------------------
-- Table: companies (ahora con user_id)
-- -----------------------------------------------------
CREATE TABLE `companies` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `address` VARCHAR(255),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  CONSTRAINT `fk_companies_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: rooms (con campo price)
-- -----------------------------------------------------
CREATE TABLE `rooms` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `capacity` INT NOT NULL,
  `status` VARCHAR(250),
  `description` TEXT,
  `price` DECIMAL(10,2) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_company` (`company_id`),
  CONSTRAINT `fk_rooms_company`
    FOREIGN KEY (`company_id`)
    REFERENCES `companies` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: reservations (con campos de pago)
-- -----------------------------------------------------
CREATE TABLE `reservations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` VARCHAR(250),
  `total_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL DEFAULT NULL,
  `card_last_digits` VARCHAR(4) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_res_user` (`user_id`),
  INDEX `idx_res_room` (`room_id`),
  CONSTRAINT `fk_reservations_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_reservations_room`
    FOREIGN KEY (`room_id`)
    REFERENCES `rooms` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: tokens
-- -----------------------------------------------------
CREATE TABLE `tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `refresh_token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_token_user` (`user_id`),
  CONSTRAINT `fk_tokens_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: images
-- -----------------------------------------------------
CREATE TABLE `greenwork`.`images` (
  `id_image` INT NOT NULL AUTO_INCREMENT,
  `imagescol` BLOB NULL,
  `name` VARCHAR(45) NULL,
  PRIMARY KEY (`id_image`)
);

-- -----------------------------------------------------
-- Mostrar mensaje de éxito
-- -----------------------------------------------------
SELECT 'La base de datos ha sido creada correctamente con todos los campos actualizados (precios y pagos).' AS 'Mensaje';

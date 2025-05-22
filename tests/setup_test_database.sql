-- Script para crear la base de datos de pruebas

-- Primero, eliminamos la base de datos si existe
DROP DATABASE IF EXISTS `greenwork_test`;

-- Creamos la nueva base de datos
CREATE DATABASE `greenwork_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Seleccionamos la base de datos para trabajar con ella
USE `greenwork_test`;

-- Importamos el mismo esquema de la base de datos principal
-- Tabla de usuarios
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

-- Tabla de empresas
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

-- Tabla de salas
CREATE TABLE `rooms` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `capacity` INT NOT NULL,
  `status` VARCHAR(250),
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_company` (`company_id`),
  CONSTRAINT `fk_rooms_company`
    FOREIGN KEY (`company_id`)
    REFERENCES `companies` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- Tabla de reservas
CREATE TABLE `reservations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` VARCHAR(250),
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

-- Table: tokens
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

-- La tabla password_resets ha sido eliminada porque no se utilizará el envío de emails

-- Insertar datos de prueba si es necesario (ej. usuario administrador)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`) VALUES
('Admin', 'Test', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- La contraseña es 'password'

-- Añadir columna profile_image_id a la tabla users
ALTER TABLE `users` 
ADD COLUMN `profile_image_id` INT NULL AFTER `preferred_language`,
ADD INDEX `fk_users_profile_image_idx` (`profile_image_id`),
ADD CONSTRAINT `fk_users_profile_image`
  FOREIGN KEY (`profile_image_id`)
  REFERENCES `images` (`id_image`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

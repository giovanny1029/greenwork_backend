-- Update the images table to change imagescol from BLOB to LONGTEXT
ALTER TABLE `greenwork`.`images` 
MODIFY COLUMN `imagescol` LONGTEXT NULL;

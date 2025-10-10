-- Adds IsDisabled column to accounts table if it does not exist
ALTER TABLE `accounts`
  ADD COLUMN IF NOT EXISTS `IsDisabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `LastLoginAt`;

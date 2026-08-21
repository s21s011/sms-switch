-- ============================================================================
--  SMS Switch — Database Schema  (16 tables + default settings)
--  Engine: InnoDB / utf8mb4.  Table names match the ORM Entity class names.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- User
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `User` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `isAdmin` TINYINT(1) NOT NULL DEFAULT 0,
  `delay` VARCHAR(20) DEFAULT NULL,
  `ussdDelay` VARCHAR(20) DEFAULT NULL,
  `dateAdded` DATETIME DEFAULT NULL,
  `lastLogin` DATETIME DEFAULT NULL,
  `lastLoginIP` VARCHAR(45) DEFAULT NULL,
  `devicesLimit` INT DEFAULT NULL,
  `contactsLimit` INT DEFAULT NULL,
  `apiKey` VARCHAR(255) DEFAULT NULL,
  `reportDelivery` TINYINT(1) NOT NULL DEFAULT 0,
  `autoRetry` TINYINT(1) NOT NULL DEFAULT 0,
  `language` VARCHAR(10) NOT NULL DEFAULT 'en',
  `credits` INT DEFAULT NULL,
  `expiryDate` DATETIME DEFAULT NULL,
  `smsToEmail` TINYINT(1) NOT NULL DEFAULT 0,
  `useProgressiveQueue` TINYINT(1) NOT NULL DEFAULT 0,
  `receivedSmsEmail` TINYINT(1) NOT NULL DEFAULT 0,
  `sleepTime` INT DEFAULT NULL,
  `timeZone` VARCHAR(50) DEFAULT NULL,
  `webHook` VARCHAR(512) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Device
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Device` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `androidID` VARCHAR(255) DEFAULT NULL,
  `token` VARCHAR(512) DEFAULT NULL,
  `model` VARCHAR(191) DEFAULT NULL,
  `androidVersion` VARCHAR(50) DEFAULT NULL,
  `appVersion` VARCHAR(50) DEFAULT NULL,
  `lastSeenAt` DATETIME DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sharedToAll` TINYINT(1) NOT NULL DEFAULT 0,
  `useOwnerSettings` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY `idx_device_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- DeviceUser (device <-> user sharing)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `DeviceUser` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `deviceID` INT UNSIGNED DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY `idx_deviceuser_device` (`deviceID`),
  KEY `idx_deviceuser_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Sim
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Sim` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `carrier` VARCHAR(191) DEFAULT NULL,
  `country` VARCHAR(10) DEFAULT NULL,
  `iccID` VARCHAR(255) DEFAULT NULL,
  `number` VARCHAR(50) DEFAULT NULL,
  `slot` INT NOT NULL DEFAULT 0,
  `deviceID` INT UNSIGNED DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY `idx_sim_device` (`deviceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Message
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Message` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` TEXT,
  `message` TEXT,
  `deviceID` INT UNSIGNED DEFAULT NULL,
  `simSlot` INT DEFAULT NULL,
  `schedule` DATETIME DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  `groupID` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `resultCode` INT DEFAULT NULL,
  `errorCode` VARCHAR(50) DEFAULT NULL,
  `type` VARCHAR(10) NOT NULL DEFAULT 'sms',
  `attachments` TEXT DEFAULT NULL,
  `prioritize` TINYINT(1) NOT NULL DEFAULT 0,
  `retries` INT NOT NULL DEFAULT 0,
  `sentDate` DATETIME DEFAULT NULL,
  `deliveredDate` DATETIME DEFAULT NULL,
  `expiryDate` DATETIME DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_message_user` (`userID`),
  KEY `idx_message_device` (`deviceID`),
  KEY `idx_message_group` (`groupID`),
  KEY `idx_message_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Ussd
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Ussd` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request` TEXT,
  `response` TEXT DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  `deviceID` INT UNSIGNED DEFAULT NULL,
  `simSlot` INT DEFAULT NULL,
  `sentDate` DATETIME DEFAULT NULL,
  `responseDate` DATETIME DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_ussd_user` (`userID`),
  KEY `idx_ussd_device` (`deviceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Contact
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Contact` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `number` VARCHAR(50) DEFAULT NULL,
  `subscribed` TINYINT(1) NOT NULL DEFAULT 1,
  `contactsListID` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_contact_list` (`contactsListID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- ContactsList
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ContactsList` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_contactslist_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Blacklist
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Blacklist` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(50) DEFAULT NULL,
  `userID` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_blacklist_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Response (auto-reply rules)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Response` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` TEXT,
  `response` TEXT,
  `matchType` VARCHAR(20) NOT NULL DEFAULT 'exact',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `userID` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_response_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Template
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Template` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) DEFAULT NULL,
  `message` TEXT,
  `userID` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_template_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Setting
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Setting` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `value` TEXT DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_setting_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `Setting` (`name`, `value`) VALUES
  ('application_title', 'SMS Switch'),
  ('company_name', 'SMS Switch'),
  ('application_version', '2.00.01'),
  ('app_version_code', '49'),
  ('default_language', 'English'),
  ('default_credits', '200'),
  ('default_devices_limit', '2'),
  ('default_contacts_limit', '200'),
  ('default_use_progressive_queue', '1'),
  ('default_delay', '0'),
  ('default_ussd_delay', '0'),
  ('default_auto_retry', '0'),
  ('default_report_delivery', '0'),
  ('default_sms_to_email', '0'),
  ('default_received_sms_email', '0'),
  ('api_enabled', '1'),
  ('enable_stop_command', '0'),
  ('received_message_notification_enabled', '0'),
  ('allowed_messages_status', 'Queued,Pending,Sent,Failed,Scheduled'),
  ('date_format', 'M j, Y'),
  ('time_format', 'g:i A'),
  ('timezone', 'UTC'),
  ('android_api_url', ''),
  ('woo_api_url', ''),
  ('woo_consumer_key', ''),
  ('woo_consumer_secret', ''),
  ('paypal_enabled', '0'),
  ('paypal_mode', 'sandbox'),
  ('paypal_client_id', ''),
  ('paypal_secret', ''),
  ('stripe_enabled', '0'),
  ('stripe_secret_key', ''),
  ('stripe_publishable_key', ''),
  ('currency', 'USD'),
  ('firebase_enabled', '0'),
  ('firebase_server_key', ''),
  ('firebase_service_account_json', ''),
  ('imap_host', ''),
  ('imap_port', '993'),
  ('imap_user', ''),
  ('imap_password', ''),
  ('email', ''),
  ('email_password', ''),
  ('email_provider', 'smtp'),
  ('smtp_host', ''),
  ('smtp_port', '465'),
  ('smtp_encryption', 'ssl'),
  ('smscounter_gsm_7bit', '160'),
  ('smscounter_gsm_7bit_multi', '153'),
  ('smscounter_ucs_2', '70'),
  ('smscounter_ucs_2_multi', '67'),
  ('skin', 'blue'),
  ('logo_src', 'logo.png'),
  ('favicon_src', 'favicon.ico'),
  ('auto_delete_messages', '0'),
  ('auto_delete_messages_days', '90'),
  ('terms_url', ''),
  ('privacy_url', ''),
  ('block_email_tld', ''),
  ('allowed_email_tld', ''),
  ('default_country_code', ''),
  ('language', 'en'),
  ('application_url', ''),
  ('get_credits_url', ''),
  ('company_url', '');

-- ---------------------------------------------------------------------------
-- Plan
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Plan` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL DEFAULT '',
  `devices` INT DEFAULT NULL,
  `contacts` INT DEFAULT NULL,
  `credits` INT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) DEFAULT NULL,
  `frequency` INT DEFAULT NULL,
  `frequencyUnit` VARCHAR(20) DEFAULT NULL,
  `totalCycles` INT DEFAULT NULL,
  `paypalPlanID` VARCHAR(255) DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Subscription
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Subscription` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `planID` INT UNSIGNED DEFAULT NULL,
  `expiryDate` DATETIME DEFAULT NULL,
  `subscribedDate` DATETIME DEFAULT NULL,
  `cyclesCompleted` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `userID` INT UNSIGNED NOT NULL,
  `subscriptionID` VARCHAR(255) DEFAULT NULL,
  `paymentMethod` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_subscription_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Payment
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Payment` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriptionID` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `transactionFee` DECIMAL(10,2) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT NULL,
  `dateAdded` DATETIME DEFAULT NULL,
  `userID` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) DEFAULT NULL,
  `transactionID` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_payment_user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Job (background scheduler)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Job` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `functionName` VARCHAR(191) DEFAULT NULL,
  `arguments` TEXT DEFAULT NULL,
  `lockName` VARCHAR(191) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

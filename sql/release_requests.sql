-- DDL for release_requests table
CREATE TABLE IF NOT EXISTS `release_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requestId` VARCHAR(32) NOT NULL UNIQUE,
  `prisoner_id` INT NOT NULL,
  `request_type` ENUM('Sentence Complete','Parole','Bail','Court Order','Death','Transfer Out','Other') DEFAULT 'Sentence Complete',
  `requested_release_date` DATE DEFAULT NULL,
  `reason` TEXT,
  `status` ENUM('Pending','Approved','Declined') DEFAULT 'Pending',
  `requested_by` INT DEFAULT NULL,
  `requested_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `approved_by` INT DEFAULT NULL,
  `approved_on` DATETIME DEFAULT NULL,
  `decline_reason` TEXT DEFAULT NULL,
  `supporting_document` VARCHAR(255) DEFAULT NULL,
  INDEX (`prisoner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

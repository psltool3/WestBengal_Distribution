-- SQL script to create the WholeSale table for district WB system
-- Run this in your MySQL database to create the WholeSale table

CREATE TABLE IF NOT EXISTS `WholeSale` (
  `id` varchar(50) NOT NULL,
  `district` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `storage` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `uniqueid` varchar(20) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_uniqueid` (`uniqueid`),
  KEY `district_index` (`district`),
  KEY `status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some sample data (optional)
-- INSERT INTO `WholeSale` (`id`, `district`, `name`, `type`, `latitude`, `longitude`, `storage`, `status`, `uniqueid`, `active`) 
-- VALUES 
-- ('WS001', 'hooghly', 'Sample WholeSale Store', 'Regional', '22.5726', '88.3639', 500, 'active', 'WholeSale_001', 1);
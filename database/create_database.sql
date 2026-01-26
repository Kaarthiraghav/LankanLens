-- LankanLens Database Creation Script
-- Execute this in phpMyAdmin or MySQL console

CREATE DATABASE IF NOT EXISTS lankanlens 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Verify database creation
USE lankanlens;

-- Show database charset
SELECT @@character_set_database, @@collation_database;

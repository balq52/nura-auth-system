-- Run this in phpMyAdmin (or `mysql -u root -p < schema.sql`) to set up the database.

CREATE DATABASE IF NOT EXISTS auth_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE auth_system;

CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,   -- stores the bcrypt hash, never plain text
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Note: extra profile fields (name, age, bio, interests) are NOT stored here.
-- They live in MongoDB, in a "profiles" collection, linked by user_id.

-- Create the database if it doesn't exist yet
CREATE DATABASE IF NOT EXISTS campus_maintenance;
USE campus_maintenance;

-- Drop table if it exists to allow for a clean reset during development
DROP TABLE IF EXISTS users;

-- Users Table for Registration and Login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student/Staff', 'Technician', 'Admin') NOT NULL DEFAULT 'Student/Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Username: admin | Password: password123
INSERT INTO users (username, email, password, role) 
VALUES (
    'admin', 
    'admin@campus.edu', 
    '$2y$10$7rK1nZ3e2GkSFl.K9yvPze5mZJgT8p6A9Yf.gqR9eR8XU2HjKe2Ki', 
    'Admin'
);
-- Username: tech1 | Password: password123
INSERT INTO users (username, email, password, role) 
VALUES (
    'tech1', 
    'tech1@campus.edu', 
    '$2y$10$7rK1nZ3e2GkSFl.K9yvPze5mZJgT8p6A9Yf.gqR9eR8XU2HjKe2Ki', 
    'Technician'
);
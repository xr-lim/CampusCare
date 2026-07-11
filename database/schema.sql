-- Create the database if it doesn't exist yet
CREATE DATABASE IF NOT EXISTS campuscare;
USE campuscare;

-- Drop tables in dependency order to allow a clean reset during development.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS status_updates;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users Table for Registration and Login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Staff', 'Technician', 'Admin') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users (username, email, password, role) 
VALUES (
    'admin', 
    'admin@campus.edu', 
    '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 
    'Admin'
);
INSERT INTO users (username, email, password, role) 
VALUES (
    'tech1', 
    'tech1@campus.edu', 
    '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 
    'Technician'
);
INSERT INTO users (username, email, password, role) 
VALUES (
    'John Doe', 
    'johndoe@campus.edu', 
    '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 
    'Staff'
);
INSERT INTO users (username, email, password, role) 
VALUES (
    'Jane Doe', 
    'janedoe@campus.edu', 
    '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 
    'Student'
);

-- ==========================================
-- MEMBER 2: MAINTENANCE REQUEST TABLES
-- ==========================================

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Locations Table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Maintenance Requests Table (With Updated Status ENUM)
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected') NOT NULL DEFAULT 'Pending',
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_technician_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (assigned_technician_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Status Updates Table (With Updated Status ENUM)
CREATE TABLE IF NOT EXISTS status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected') NOT NULL,
    updated_by INT NOT NULL,
    update_notes TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Seed Initial Categories
INSERT IGNORE INTO categories (name) VALUES 
('Plumbing'),
('Electrical'),
('HVAC / Air Conditioning'),
('Furniture & Carpentry'),
('Cleaning & Janitorial'),
('Security / Locksmith'),
('Others');

-- 6. Seed Initial Locations
INSERT IGNORE INTO locations (name) VALUES 
('Block A - Academic Wing'),
('Block B - Laboratories'),
('Block C - Administration'),
('Central Library'),
('Student Activity Center'),
('Main Cafeteria'),
('Sports Complex & Gymnasium'),
('Student Residence Hall 1');

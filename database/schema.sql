-- Create the database if it doesn't exist yet
CREATE DATABASE IF NOT EXISTS campuscare;
USE campuscare;

-- Drop tables in reverse order of dependencies to allow for a clean reset during development
DROP TABLE IF EXISTS status_updates;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users Table for Registration and Login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Staff', 'Technician', 'Admin') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Categories Table for Issue Types
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Locations Table for Building/Room Information
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maintenance Requests Table
CREATE TABLE maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    assigned_to INT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Status Updates Table for Request History
CREATE TABLE status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    updated_by INT,
    status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled') NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for Users
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

-- Seed data for Categories
INSERT INTO categories (name, description) VALUES 
('Electrical', 'Electrical issues including broken lights, power outlets, wiring'),
('Furniture', 'Furniture damage including broken chairs, tables, desks'),
('Air Conditioning', 'Air conditioning and HVAC system problems'),
('Internet/Wi-Fi', 'Network and Wi-Fi connectivity issues'),
('Plumbing', 'Toilet, sink, and water-related problems'),
('Other', 'Other maintenance issues not listed above');

-- Seed data for Locations
INSERT INTO locations (name, type, description) VALUES 
('Faculty of Engineering', 'Faculty', 'Engineering Faculty Building'),
('Faculty of Science', 'Faculty', 'Science Faculty Building'),
('Block A', 'Building', 'Administrative Building'),
('Block B', 'Building', 'Student Center'),
('Lab 101', 'Lab', 'Physics Laboratory'),
('Lab 102', 'Lab', 'Chemistry Laboratory'),
('Room 201', 'Classroom', 'Classroom in Block A'),
('Room 202', 'Classroom', 'Classroom in Block A'),
('Cafeteria', 'Facility', 'Main Campus Cafeteria'),
('Library', 'Facility', 'Central Library');
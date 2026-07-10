CREATE DATABASE IF NOT EXISTS campuscare;
USE campuscare;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS status_updates;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Staff', 'Technician', 'Admin') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    urgency ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected') NOT NULL DEFAULT 'Pending',
    assigned_technician_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_requests_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_requests_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_requests_technician FOREIGN KEY (assigned_technician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE status_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected') NOT NULL,
    new_status ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected') NOT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_request FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users (username, email, password, role) VALUES
    ('admin', 'admin@campus.edu', '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 'Admin'),
    ('tech1', 'tech1@campus.edu', '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 'Technician'),
    ('tech2', 'tech2@campus.edu', '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 'Technician'),
    ('John Doe', 'johndoe@campus.edu', '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 'Staff'),
    ('Jane Doe', 'janedoe@campus.edu', '$2y$10$QCFxpwph.3f/S9XgiMk/luU.rSwXPaYvM4wkDOGc48XrrXqSRCbgW', 'Student');

INSERT INTO categories (name, description) VALUES
    ('Electrical', 'Electrical fixtures, power points, and lighting issues'),
    ('Plumbing', 'Leaks, drains, and water supply issues'),
    ('Facilities', 'Furniture, doors, windows, and room fixtures'),
    ('ICT Support', 'Projectors, network, and lab equipment issues');

INSERT INTO locations (name, description) VALUES
    ('Library - Level 1', 'Study spaces and common area'),
    ('Engineering Block - Lab 2', 'Hardware and practical lab area'),
    ('Hostel A - Floor 3', 'Student accommodation corridor and rooms'),
    ('Cafeteria', 'Dining and public seating area');

INSERT INTO maintenance_requests (
    user_id,
    category_id,
    location_id,
    description,
    urgency,
    status,
    assigned_technician_id
) VALUES
    (5, 1, 1, 'Several ceiling lights near the discussion rooms are flickering continuously.', 'Medium', 'Pending', NULL),
    (4, 2, 3, 'Water is leaking from the washroom sink and creating a slippery floor.', 'High', 'Assigned', 2),
    (5, 4, 2, 'Projector in Lab 2 keeps shutting down after a few minutes of use.', 'High', 'In Progress', 3),
    (4, 3, 4, 'Two cafeteria chairs are broken and unsafe to use.', 'Low', 'Completed', 2),
    (5, 1, 2, 'One wall socket sparked when a charger was plugged in.', 'Critical', 'Rejected', NULL);

INSERT INTO status_updates (request_id, updated_by, old_status, new_status, remarks) VALUES
    (2, 1, 'Pending', 'Assigned', 'Assigned technician to inspect the sink leakage this afternoon.'),
    (3, 1, 'Assigned', 'In Progress', 'Technician has started diagnostics on the projector issue.'),
    (4, 1, 'Pending', 'Assigned', 'Technician assigned for furniture inspection.'),
    (4, 1, 'Assigned', 'Completed', 'Damaged chairs replaced and area marked safe for use.'),
    (5, 1, 'Pending', 'Rejected', 'Duplicate report recorded after facilities team inspection.');

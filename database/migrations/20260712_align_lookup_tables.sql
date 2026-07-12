USE campuscare;

-- Bring older CampusCare databases in line with database/schema.sql
-- without removing existing categories, locations, users, or requests.
ALTER TABLE categories
    MODIFY name VARCHAR(100) NOT NULL,
    ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER name,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE locations
    ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'General' AFTER name,
    ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER type,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

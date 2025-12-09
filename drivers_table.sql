-- Create drivers table for driver management
-- Drop existing table if needed (WARNING: removes all data)
-- DROP TABLE IF EXISTS drivers;

CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license VARCHAR(50) NOT NULL,
    rate_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    experience_years INT NOT NULL DEFAULT 0,
    status ENUM('available', 'assigned') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add missing columns if they don't exist (for existing tables)
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS rate_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS experience_years INT NOT NULL DEFAULT 0;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS status ENUM('available', 'assigned') DEFAULT 'available';

-- Update rentals table to include driver_id
-- Check if column exists before adding
ALTER TABLE rentals 
ADD COLUMN IF NOT EXISTS driver_id INT NULL AFTER vehicle_id;

-- Add foreign key if it doesn't exist
-- ALTER TABLE rentals ADD FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL;

-- Sample data (optional)
INSERT INTO drivers (name, phone, license, rate_per_day, experience_years, status) VALUES
('John Smith', '+1234567890', 'DL123456', 50.00, 5, 'available'),
('Michael Johnson', '+1234567891', 'DL789012', 60.00, 8, 'available'),
('David Williams', '+1234567892', 'DL345678', 45.00, 3, 'available');

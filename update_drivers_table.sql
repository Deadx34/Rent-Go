-- Add missing columns to existing drivers table
ALTER TABLE drivers ADD COLUMN rate_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE drivers ADD COLUMN experience_years INT NOT NULL DEFAULT 0;
ALTER TABLE drivers ADD COLUMN status ENUM('available', 'assigned') DEFAULT 'available';
ALTER TABLE drivers ADD COLUMN photo_url VARCHAR(255) NULL;

-- Update rentals table to include driver_id
ALTER TABLE rentals ADD COLUMN driver_id INT NULL AFTER vehicle_id;

-- Sample data
INSERT INTO drivers (name, phone, license, rate_per_day, experience_years, status) VALUES
('John Smith', '+1234567890', 'DL123456', 50.00, 5, 'available'),
('Michael Johnson', '+1234567891', 'DL789012', 60.00, 8, 'available'),
('David Williams', '+1234567892', 'DL345678', 45.00, 3, 'available');

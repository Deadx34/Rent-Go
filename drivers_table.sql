-- Create drivers table for driver management
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    rate_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    experience_years INT NOT NULL DEFAULT 0,
    status ENUM('available', 'assigned') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update rentals table to include driver_id
ALTER TABLE rentals 
ADD COLUMN driver_id INT NULL AFTER vehicle_id,
ADD FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL;

-- Sample data (optional)
INSERT INTO drivers (name, phone, license_number, rate_per_day, experience_years, status) VALUES
('John Smith', '+1234567890', 'DL123456', 50.00, 5, 'available'),
('Michael Johnson', '+1234567891', 'DL789012', 60.00, 8, 'available'),
('David Williams', '+1234567892', 'DL345678', 45.00, 3, 'available');

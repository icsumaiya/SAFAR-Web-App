-- Add new columns to bookings table safely
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS check_in DATE, 
ADD COLUMN IF NOT EXISTS check_out DATE, 
ADD COLUMN IF NOT EXISTS guests INT DEFAULT 1;

-- Update Admin User
INSERT INTO users (id, name, email, password, role) 
VALUES (1, 'System Admin', 'admin@safar.com', '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', 'admin')
ON DUPLICATE KEY UPDATE password = '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', name = 'System Admin', role = 'admin';

-- Update Traveler User
INSERT INTO users (id, name, email, password, role) 
VALUES (1001, 'Test Traveler', 'user@safar.com', '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', 'traveler')
ON DUPLICATE KEY UPDATE password = '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', name = 'Test Traveler', role = 'traveler';

-- Update Agency User
INSERT INTO users (id, name, email, password, role) 
VALUES (1002, 'Test Agency', 'agency@safar.com', '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', 'agency')
ON DUPLICATE KEY UPDATE password = '$2y$10$e8oYqgKToq7Gv8wD0Qx0Iej7P0nO1g7Jg4bV9N8D3Bv1v/1GzO6yG', name = 'Test Agency', role = 'agency';

-- Make sure agency exists in agencies table
INSERT IGNORE INTO agencies (user_id, company_name, status) VALUES (1002, 'Safar Verified Agency', 'verified');

-- SQL Script to verify database schema for Smart Parking System

-- ============================================================
-- VERIFY EXISTING TABLES
-- ============================================================

-- Check if tables exist
SHOW TABLES;

-- ============================================================
-- CHECK USERS TABLE
-- ============================================================

DESCRIBE users;
-- Expected columns: id, name, phone, email, password, created_on

SELECT COUNT(*) as user_count FROM users;
SELECT * FROM users LIMIT 5;

-- ============================================================
-- CHECK PARKING LOTS TABLE
-- ============================================================

DESCRIBE parking_lots;
-- Expected columns: lot_id, owner_id, lot_name, latitude, longitude, total_slots, address, created_on

SELECT COUNT(*) as lot_count FROM parking_lots;
SELECT * FROM parking_lots;

-- ============================================================
-- CHECK PARKING SLOTS TABLE
-- ============================================================

DESCRIBE parking_slots;
-- Expected columns: id (or slot_id), parking_lot_id, slot_number, vehicle_type, is_available, is_active, hourly_rate

SELECT COUNT(*) as slot_count FROM parking_slots;
SELECT * FROM parking_slots;

-- ============================================================
-- CHECK BOOKINGS TABLE (This is where the issue likely is!)
-- ============================================================

-- Try different possible table names
DESCRIBE bookings;
-- OR
DESCRIBE booking;
-- OR
SHOW CREATE TABLE bookings;

-- Expected columns:
-- id, user_id, slot_id, booking_uid, start_time, end_time, 
-- total_amount, status, payment_status, created_at

SELECT COUNT(*) as booking_count FROM bookings;
SELECT * FROM bookings LIMIT 5;

-- ============================================================
-- CHECK FEEDBACK TABLE
-- ============================================================

DESCRIBE feedback;
-- Expected columns: id, user_id, booking_id, rating, comments, created_at

-- ============================================================
-- CHECK FOREIGN KEYS
-- ============================================================

-- Check if user_id = 1 exists
SELECT * FROM users WHERE id = 1;

-- Check if slot_id = 1 exists  
SELECT * FROM parking_slots WHERE id = 1;

-- ============================================================
-- TEST INSERT (to see what error occurs)
-- ============================================================

-- Try inserting a test booking
INSERT INTO bookings 
(user_id, slot_id, booking_uid, start_time, end_time, total_amount, status, payment_status) 
VALUES 
(1, 1, 'TEST1234', NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR), 100, 'Active', 'Pending');

-- If the above fails, you'll see the exact error!
-- Then delete the test booking:
-- DELETE FROM bookings WHERE booking_uid = 'TEST1234';

-- ============================================================
-- CREATE BOOKINGS TABLE IF MISSING
-- ============================================================

-- If the bookings table doesn't exist, create it:
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_uid VARCHAR(20) UNIQUE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    total_amount DECIMAL(10, 2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Active',
    payment_status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES parking_slots(id)
);

-- ============================================================
-- CREATE FEEDBACK TABLE IF MISSING
-- ============================================================

CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    rating DECIMAL(3, 2) NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- ============================================================
-- VERIFY SCHEMA AFTER CREATION
-- ============================================================

SHOW TABLES;
DESCRIBE bookings;
DESCRIBE feedback;

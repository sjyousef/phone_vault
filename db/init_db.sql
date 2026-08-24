-- Second-Hand Phone Store & Warranty Management System

CREATE DATABASE IF NOT EXISTS second_hand_phones CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE second_hand_phones;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS phones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(100) NOT NULL,
    imei VARCHAR(20) NOT NULL UNIQUE,
    storage VARCHAR(20),
    color VARCHAR(30),
    battery_health TINYINT UNSIGNED NOT NULL DEFAULT 100,
    condition_grade ENUM('Grade A','Grade B','Grade C') NOT NULL DEFAULT 'Grade A',
    cost_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    status ENUM('Available','Sold','Returned') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    phone_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Transfer') NOT NULL DEFAULT 'Cash',
    warranty_duration_days INT NOT NULL DEFAULT 30,
    warranty_end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone_id) REFERENCES phones(id)
);

CREATE TABLE IF NOT EXISTS returns_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    phone_id INT NOT NULL,
    refund_reason VARCHAR(255) NOT NULL,
    defect_description TEXT,
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Pending','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (phone_id) REFERENCES phones(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Seed: Users (password = "admin123" and "staff123")
INSERT IGNORE INTO users (full_name, username, password_hash, role) VALUES
('Admin User', 'admin', '$2y$10$dwNYfYxwqG.lKHFcuEN3eOZSArtebZ2cPTTLjXpCLUkgaIOWHoeG6', 'admin'),
('Staff Member', 'staff', '$2y$10$.daT.AuEXJkqGQiKQeaUcu26eAN.Pa5k4voLK7bKNsrxTrmm1PYAC', 'staff');

-- Seed: Phones
INSERT IGNORE INTO phones (brand, model, imei, storage, color, battery_health, condition_grade, cost_price, selling_price, status) VALUES
('Apple', 'iPhone 13', '354678901234567', '128GB', 'Midnight', 89, 'Grade A', 22500.00, 28990.00, 'Available'),
('Apple', 'iPhone 12', '354678901234568', '64GB', 'Blue', 78, 'Grade B', 14000.00, 18500.00, 'Available'),
('Samsung', 'Galaxy S22', '354678901234569', '256GB', 'Phantom Black', 92, 'Grade A', 18500.00, 24500.00, 'Available'),
('Samsung', 'Galaxy A53', '354678901234570', '128GB', 'White', 65, 'Grade C', 7000.00, 9800.00, 'Available'),
('Google', 'Pixel 7', '354678901234571', '128GB', 'Snow', 95, 'Grade A', 15500.00, 21000.00, 'Available'),
('OnePlus', '10 Pro', '354678901234572', '256GB', 'Emerald Forest', 82, 'Grade B', 13500.00, 17900.00, 'Available'),
('Apple', 'iPhone 11', '354678901234573', '64GB', 'Black', 71, 'Grade B', 10500.00, 14200.00, 'Sold'),
('Samsung', 'Galaxy S21', '354678901234574', '128GB', 'Gray', 88, 'Grade A', 14000.00, 18900.00, 'Sold');

-- Seed: Sales
INSERT IGNORE INTO sales (invoice_no, customer_name, customer_phone, phone_id, total_amount, payment_method, warranty_duration_days, warranty_end_date) VALUES
('INV-20240001', 'John Smith', '555-0101', 7, 14200.00, 'Cash', 30, DATE_ADD(CURDATE(), INTERVAL -10 DAY)),
('INV-20240002', 'Jane Doe', '555-0102', 8, 18900.00, 'Card', 90, DATE_ADD(CURDATE(), INTERVAL 45 DAY));

-- Seed: Returns
INSERT IGNORE INTO returns_refunds (sale_id, phone_id, refund_reason, defect_description, refund_amount, status) VALUES
(1, 7, 'Battery drains too fast', 'Battery health dropped to 60% within 2 weeks of purchase', 14200.00, 'Pending');

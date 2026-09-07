-- =====================================================================
-- PHARMACY MANAGEMENT SYSTEM - DATABASE SCHEMA
-- =====================================================================
-- How to install:
--   1. Open phpMyAdmin (or mysql CLI)
--   2. Run this whole file. It creates the database "pharmacy_db"
--      and all tables, plus some starter data.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pharmacy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

-- ---------------------------------------------------------------------
-- USERS (login for the system)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- NOTE: No default admin user is inserted here on purpose (a hash typed
-- directly into SQL is easy to get wrong). Instead, open install.php in
-- your browser ONE TIME after importing this file - it safely creates
-- the admin account (username: admin / password: admin123) using PHP's
-- own password_hash() function, then tells you to delete it.

-- ---------------------------------------------------------------------
-- MEDICINE CATEGORIES (Syrup, Tablet, Capsule, Drops, Suspension, Drink, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT INTO categories (name, description) VALUES
('Tablet', 'Solid oral dosage form'),
('Capsule', 'Gelatin-shell oral dosage form'),
('Syrup', 'Sweetened liquid medicine'),
('Suspension', 'Liquid with undissolved particles, shake before use'),
('Drops', 'Small liquid doses (eye, ear, nasal, oral drops)'),
('Drink/Sachet', 'Powder or liquid sachets dissolved in water (ORS, etc.)'),
('Injection', 'Injectable medicine (ampoule/vial)'),
('Ointment/Cream', 'Topical external application'),
('Inhaler', 'Inhaled medicine for respiratory use'),
('Other', 'Anything not covered above');

-- ---------------------------------------------------------------------
-- SUPPLIERS (who you buy medicines from)
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CUSTOMERS (optional, for sales memo - walk-in customers can be skipped)
-- ---------------------------------------------------------------------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) DEFAULT 'Walk-in Customer',
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MEDICINES (the master item list)
-- ---------------------------------------------------------------------
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150) DEFAULT NULL,
    category_id INT NOT NULL,
    manufacturer VARCHAR(150) DEFAULT NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',       -- pcs, strip, bottle, box, tube, vial...
    batch_no VARCHAR(50) DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10,          -- low-stock warning threshold
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INVENTORY HISTORY (every stock change is logged here - audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    change_type ENUM('purchase','sale','manual_add','manual_remove','adjustment') NOT NULL,
    quantity_changed INT NOT NULL,      -- positive = stock added, negative = stock removed
    previous_qty INT NOT NULL,
    new_qty INT NOT NULL,
    reference_id INT DEFAULT NULL,      -- links to purchases.id or sales.id when relevant
    reference_type VARCHAR(30) DEFAULT NULL, -- 'purchase' / 'sale' / NULL for manual
    remarks VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PURCHASES (buying stock from suppliers) - header + line items
-- ---------------------------------------------------------------------
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    purchase_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SALES (selling to customers) - header + line items -> this is the memo
-- ---------------------------------------------------------------------
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    memo_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT DEFAULT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payable_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Helpful indexes
-- ---------------------------------------------------------------------
CREATE INDEX idx_medicines_category ON medicines(category_id);
CREATE INDEX idx_medicines_name ON medicines(name);
CREATE INDEX idx_inv_hist_medicine ON inventory_history(medicine_id);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_purchase_items_purchase ON purchase_items(purchase_id);

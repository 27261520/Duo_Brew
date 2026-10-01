-- ============================================================
-- Duo Brew POS & Self-Ordering System - Database Schema
-- MySQL Database: duo_brew_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS duo_brew_db;
USE duo_brew_db;

-- ------------------------------------------------------------
-- Roles Table
-- ------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Users Table
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Categories Table
-- ------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Products Table
-- ------------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    is_seasonal BOOLEAN DEFAULT FALSE,
    is_promotional BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Product Options Table (Sizes, Flavors, Add-ons)
-- ------------------------------------------------------------
CREATE TABLE product_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT DEFAULT NULL,
    option_type ENUM('size', 'flavor', 'addon') NOT NULL,
    option_name VARCHAR(100) NOT NULL,
    price_modifier DECIMAL(10, 2) DEFAULT 0.00,
    is_default BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Inventory Table (Commented out - feature removed)
-- ------------------------------------------------------------
-- CREATE TABLE inventory (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     product_id INT DEFAULT NULL,
--     ingredient_name VARCHAR(200) NOT NULL,
--     quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
--     unit VARCHAR(50) NOT NULL,
--     low_stock_threshold DECIMAL(10, 2) DEFAULT 10,
--     last_restocked TIMESTAMP NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Orders Table
-- ------------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) DEFAULT 'Walk-in Customer',
    customer_phone VARCHAR(20),
    order_type ENUM('pos', 'self') DEFAULT 'pos',
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Order Items Table
-- ------------------------------------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    option_details TEXT COMMENT 'JSON of selected options (size, flavor, addons)',
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Payments Table
-- ------------------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'qr') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    cash_received DECIMAL(10, 2) DEFAULT NULL,
    change_amount DECIMAL(10, 2) DEFAULT NULL,
    qr_reference VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Expenses Table (Commented out - feature removed)
-- ------------------------------------------------------------
-- CREATE TABLE expenses (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     description VARCHAR(255) NOT NULL,
--     amount DECIMAL(10, 2) NOT NULL,
--     category VARCHAR(100) NOT NULL,
--     expense_date DATE NOT NULL,
--     created_by INT,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Activity Logs Table
-- ------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Notifications Table
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('low_stock', 'new_order', 'payment', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Insert Roles
INSERT INTO roles (role_name) VALUES
('Administrator'),
('Cashier/Staff'),
('Customer');

-- Insert Default Users (password for all: admin123)
INSERT INTO users (username, password, full_name, email, phone, role_id, status) VALUES
('admin', '$2y$10$BYQl5/YfBypx9.K06IGiquyTRs5XcqgmOT0KHiAV6ImuPic72AgLC', 'System Administrator', 'admin@duobrew.com', '09171234567', 1, 'active'),
('staff1', '$2y$10$BYQl5/YfBypx9.K06IGiquyTRs5XcqgmOT0KHiAV6ImuPic72AgLC', 'Staff Member 1', 'staff1@duobrew.com', '09171234568', 2, 'active'),
('staff2', '$2y$10$BYQl5/YfBypx9.K06IGiquyTRs5XcqgmOT0KHiAV6ImuPic72AgLC', 'Staff Member 2', 'staff2@duobrew.com', '09171234569', 2, 'active');

-- Insert Sample Categories
INSERT INTO categories (name, description, sort_order) VALUES
('Coffee', 'Premium coffee beverages', 1),
('Milk Tea', 'Delicious milk tea flavors', 2),
('Frappe', 'Blended ice cream drinks', 3),
('Snacks', 'Light snacks and pastries', 4),
('Add-ons', 'Extra toppings and add-ons', 5);

-- Insert Sample Products
INSERT INTO products (category_id, name, description, price, image) VALUES
(1, 'Americano', 'Classic black coffee with rich espresso shots', 55.00, 'americano.jpg'),
(1, 'Cafe Latte', 'Smooth espresso with steamed milk', 65.00, 'cafe_latte.jpg'),
(1, 'Caramel Macchiato', 'Espresso with vanilla syrup and caramel drizzle', 75.00, 'caramel_macchiato.jpg'),
(1, 'Spanish Latte', 'Espresso with condensed milk', 70.00, 'spanish_latte.jpg'),
(2, 'Classic Milk Tea', 'Traditional milk tea with tapioca pearls', 60.00, 'classic_milktea.jpg'),
(2, 'Okinawa Milk Tea', 'Brown sugar milk tea with creamy texture', 65.00, 'okinawa_milktea.jpg'),
(2, 'Matcha Milk Tea', 'Green tea matcha with milk and pearls', 70.00, 'matcha_milktea.jpg'),
(2, 'Taro Milk Tea', 'Purple taro flavored milk tea', 65.00, 'taro_milktea.jpg'),
(3, 'Chocolate Frappe', 'Blended chocolate drink with cream', 80.00, 'choco_frappe.jpg'),
(3, 'Matcha Frappe', 'Blended matcha green tea frappe', 85.00, 'matcha_frappe.jpg'),
(3, 'Strawberry Frappe', 'Blended strawberry cream frappe', 85.00, 'strawberry_frappe.jpg'),
(4, 'Croissant', 'Buttery and flaky croissant', 35.00, 'croissant.jpg'),
(4, 'Cookie', 'Chocolate chip cookie', 20.00, 'cookie.jpg'),
(4, 'Brownies', 'Fudgy chocolate brownies', 30.00, 'brownies.jpg');

-- Insert Product Options (Sizes)
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default) 
SELECT id, 'size', 'Regular', 0.00, TRUE FROM products WHERE category_id IN (1,2,3);
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default) 
SELECT id, 'size', 'Large', 15.00, FALSE FROM products WHERE category_id IN (1,2,3);

-- Insert Product Options (Flavors for Coffee)
INSERT INTO product_options (product_id, option_type, option_name, price_modifier) 
SELECT id, 'flavor', 'Vanilla', 0.00 FROM products WHERE category_id = 1;
INSERT INTO product_options (product_id, option_type, option_name, price_modifier) 
SELECT id, 'flavor', 'Hazelnut', 0.00 FROM products WHERE category_id = 1;
INSERT INTO product_options (product_id, option_type, option_name, price_modifier) 
SELECT id, 'flavor', 'Caramel', 0.00 FROM products WHERE category_id = 1;

-- Insert Add-ons
INSERT INTO product_options (product_id, option_type, option_name, price_modifier) VALUES
(NULL, 'addon', 'Extra Tapioca Pearls', 10.00),
(NULL, 'addon', 'Cream Cheese', 15.00),
(NULL, 'addon', 'Nata De Coco', 10.00),
(NULL, 'addon', 'Coffee Jelly', 10.00),
(NULL, 'addon', 'Extra Shot Espresso', 15.00),
(NULL, 'addon', 'Whipped Cream', 10.00);

-- Insert Sample Inventory (Commented out - feature removed)
-- INSERT INTO inventory (product_id, ingredient_name, quantity, unit, low_stock_threshold) VALUES
-- (NULL, 'Coffee Beans', 5000, 'g', 500),
-- (NULL, 'Milk', 10000, 'ml', 1000),
-- (NULL, 'Sugar Syrup', 3000, 'ml', 500),
-- (NULL, 'Tapioca Pearls', 2000, 'g', 200),
-- (NULL, 'Matcha Powder', 1000, 'g', 100),
-- (NULL, 'Chocolate Syrup', 2000, 'ml', 200),
-- (NULL, 'Cups (Small)', 200, 'pcs', 20),
-- (NULL, 'Cups (Large)', 150, 'pcs', 15),
-- (NULL, 'Straws', 500, 'pcs', 50),
-- (NULL, 'Napkins', 300, 'pcs', 30);


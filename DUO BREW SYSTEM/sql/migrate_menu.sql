-- ============================================================
-- Duo Brew Menu Migration Script
-- Clears old data and inserts the complete Duo Brew menu
-- with correct categories, products, sizes, and pricing
-- ============================================================

USE duo_brew_db;

-- ============================================================
-- STEP 1: Clear existing data (order matters for foreign keys)
-- ============================================================
DELETE FROM payments;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM product_options;
DELETE FROM products;
DELETE FROM categories;

-- Reset auto-increment counters
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE product_options AUTO_INCREMENT = 1;

-- ============================================================
-- STEP 2: Insert Categories
-- ============================================================
INSERT INTO categories (name, description, sort_order, status) VALUES
('MILKTEA', 'Classic and Special Milktea flavors', 1, 'active'),
('FRUIT TEA', 'Refreshing fruit tea flavors', 2, 'active'),
('YOGURT', 'Creamy yogurt drinks', 3, 'active'),
('FRAPPE', 'Blended frappe drinks', 4, 'active'),
('ICED COFFEE', 'Iced coffee beverages', 5, 'active'),
('ICED LATTE', 'Iced latte beverages', 6, 'active'),
('COFFEE FRAPPE', 'Blended coffee frappe', 7, 'active'),
('SODA', 'Refreshing soda drinks', 8, 'active'),
('HOT COFFEE', 'Hot coffee beverages', 9, 'active'),
('HOT CHOCO', 'Hot chocolate drinks', 10, 'active'),
('HOT TEA', 'Hot tea beverages', 11, 'active');

-- ============================================================
-- STEP 3: Helper: Store category IDs in variables
-- Since we can't use variables easily in MySQL, we'll use the IDs
-- MILKTEA = 1, FRUIT TEA = 2, YOGURT = 3, FRAPPE = 4,
-- ICED COFFEE = 5, ICED LATTE = 6, COFFEE FRAPPE = 7,
-- SODA = 8, HOT COFFEE = 9, HOT CHOCO = 10, HOT TEA = 11
-- ============================================================

-- ============================================================
-- STEP 4: Insert Products - MILKTEA Classic (cat=1, base=20)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(1, 'Classic Milktea - Bubble Tea', 'Classic milk tea with bubble tea flavor', 20.00, 'active'),
(1, 'Classic Milktea - Wintermelon', 'Classic milk tea with wintermelon flavor', 20.00, 'active'),
(1, 'Classic Milktea - Vanilla', 'Classic milk tea with vanilla flavor', 20.00, 'active'),
(1, 'Classic Milktea - Strawberry', 'Classic milk tea with strawberry flavor', 20.00, 'active'),
(1, 'Classic Milktea - Brown Sugar', 'Classic milk tea with brown sugar flavor', 20.00, 'active'),
(1, 'Classic Milktea - Mocha', 'Classic milk tea with mocha flavor', 20.00, 'active'),
(1, 'Classic Milktea - Cookies and Cream', 'Classic milk tea with cookies and cream flavor', 20.00, 'active'),
(1, 'Classic Milktea - Taro', 'Classic milk tea with taro flavor', 20.00, 'active'),
(1, 'Classic Milktea - Chocolate', 'Classic milk tea with chocolate flavor', 20.00, 'active');

-- ============================================================
-- STEP 5: Insert Products - MILKTEA Special (cat=1, base=25)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(1, 'Special Milktea - Choco Nutella', 'Special milk tea with choco nutella flavor', 25.00, 'active'),
(1, 'Special Milktea - Dark Chocolate', 'Special milk tea with dark chocolate flavor', 25.00, 'active'),
(1, 'Special Milktea - Mango', 'Special milk tea with mango flavor', 25.00, 'active'),
(1, 'Special Milktea - Matcha', 'Special milk tea with matcha flavor', 25.00, 'active'),
(1, 'Special Milktea - Okinawa', 'Special milk tea with okinawa flavor', 25.00, 'active'),
(1, 'Special Milktea - Avocado', 'Special milk tea with avocado flavor', 25.00, 'active'),
(1, 'Special Milktea - Rocky Road', 'Special milk tea with rocky road flavor', 25.00, 'active'),
(1, 'Special Milktea - Salted Caramel', 'Special milk tea with salted caramel flavor', 25.00, 'active'),
(1, 'Special Milktea - Red Velvet', 'Special milk tea with red velvet flavor', 25.00, 'active'),
(1, 'Special Milktea - Cheesecake', 'Special milk tea with cheesecake flavor', 25.00, 'active'),
(1, 'Special Milktea - Black Forest', 'Special milk tea with black forest flavor', 25.00, 'active');

-- ============================================================
-- STEP 6: Insert Products - FRUIT TEA (cat=2, base=20)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(2, 'Fruit Tea - Blueberry', 'Refreshing blueberry fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Grapes', 'Refreshing grapes fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Green Apple', 'Refreshing green apple fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Honey Peach', 'Refreshing honey peach fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Lemon', 'Refreshing lemon fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Kiwi', 'Refreshing kiwi fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Mango', 'Refreshing mango fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Passion Fruit', 'Refreshing passion fruit fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Strawberry', 'Refreshing strawberry fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Lychee', 'Refreshing lychee fruit tea', 20.00, 'active'),
(2, 'Fruit Tea - Watermelon', 'Refreshing watermelon fruit tea', 20.00, 'active');

-- ============================================================
-- STEP 7: Insert Products - YOGURT (cat=3, base=30)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(3, 'Yogurt - Blueberry', 'Creamy blueberry yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Grapes', 'Creamy grapes yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Green Apple', 'Creamy green apple yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Honey Peach', 'Creamy honey peach yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Lemon', 'Creamy lemon yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Kiwi', 'Creamy kiwi yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Mango', 'Creamy mango yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Passion Fruit', 'Creamy passion fruit yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Strawberry', 'Creamy strawberry yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Lychee', 'Creamy lychee yogurt drink', 30.00, 'active'),
(3, 'Yogurt - Watermelon', 'Creamy watermelon yogurt drink', 30.00, 'active');

-- ============================================================
-- STEP 8: Insert Products - FRAPPE (cat=4, base=60)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(4, 'Frappe - Cookies and Cream', 'Blended cookies and cream frappe', 60.00, 'active'),
(4, 'Frappe - Matcha', 'Blended matcha frappe', 60.00, 'active'),
(4, 'Frappe - Mango Graham', 'Blended mango graham frappe', 60.00, 'active'),
(4, 'Frappe - Choco Nutella', 'Blended choco nutella frappe', 60.00, 'active'),
(4, 'Frappe - Black Forest', 'Blended black forest frappe', 60.00, 'active'),
(4, 'Frappe - Strawberry', 'Blended strawberry frappe', 60.00, 'active'),
(4, 'Frappe - Taro', 'Blended taro frappe', 60.00, 'active'),
(4, 'Frappe - Chocolate', 'Blended chocolate frappe', 60.00, 'active'),
(4, 'Frappe - Rocky Road', 'Blended rocky road frappe', 60.00, 'active'),
(4, 'Frappe - Caramel Vanilla', 'Blended caramel vanilla frappe', 60.00, 'active'),
(4, 'Frappe - Avocado', 'Blended avocado frappe', 60.00, 'active'),
(4, 'Frappe - Dark Chocolate', 'Blended dark chocolate frappe', 60.00, 'active'),
(4, 'Frappe - Cheesecake', 'Blended cheesecake frappe', 60.00, 'active'),
(4, 'Frappe - Red Velvet', 'Blended red velvet frappe', 60.00, 'active');

-- ============================================================
-- STEP 9: Insert Products - ICED COFFEE (cat=5, base=30)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(5, 'Iced Coffee - Americano', 'Iced americano coffee', 30.00, 'active'),
(5, 'Iced Coffee - Iced Mocha', 'Iced mocha coffee', 30.00, 'active'),
(5, 'Iced Coffee - Dark Chocolate', 'Iced dark chocolate coffee', 30.00, 'active'),
(5, 'Iced Coffee - French Vanilla', 'Iced french vanilla coffee', 30.00, 'active'),
(5, 'Iced Coffee - Caramel Macchiato', 'Iced caramel macchiato coffee', 30.00, 'active');

-- ============================================================
-- STEP 10: Insert Products - ICED LATTE (cat=6, base=40)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(6, 'Iced Latte - Choco', 'Iced choco latte', 40.00, 'active'),
(6, 'Iced Latte - Matcha', 'Iced matcha latte', 40.00, 'active'),
(6, 'Iced Latte - Red Velvet', 'Iced red velvet latte', 40.00, 'active'),
(6, 'Iced Latte - Strawberry', 'Iced strawberry latte', 40.00, 'active'),
(6, 'Iced Latte - Taro', 'Iced taro latte', 40.00, 'active'),
(6, 'Iced Latte - Cafe', 'Iced cafe latte', 40.00, 'active');

-- ============================================================
-- STEP 11: Insert Products - COFFEE FRAPPE (cat=7, base=70)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(7, 'Coffee Frappe - Salted Caramel', 'Blended salted caramel coffee frappe', 70.00, 'active'),
(7, 'Coffee Frappe - French Vanilla', 'Blended french vanilla coffee frappe', 70.00, 'active'),
(7, 'Coffee Frappe - Dark Chocolate', 'Blended dark chocolate coffee frappe', 70.00, 'active'),
(7, 'Coffee Frappe - Mocha', 'Blended mocha coffee frappe', 70.00, 'active'),
(7, 'Coffee Frappe - Caramel Macchiato', 'Blended caramel macchiato coffee frappe', 70.00, 'active');

-- ============================================================
-- STEP 12: Insert Products - SODA (cat=8, base=30)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(8, 'Soda - Honey Peach Grapes', 'Refreshing honey peach grapes soda', 30.00, 'active'),
(8, 'Soda - Watermelon Kiwi', 'Refreshing watermelon kiwi soda', 30.00, 'active'),
(8, 'Soda - Mango Strawberry', 'Refreshing mango strawberry soda', 30.00, 'active'),
(8, 'Soda - Green Apple Watermelon', 'Refreshing green apple watermelon soda', 30.00, 'active'),
(8, 'Soda - Blueberry Passion Fruit', 'Refreshing blueberry passion fruit soda', 30.00, 'active');

-- ============================================================
-- STEP 13: Insert Products - HOT COFFEE (cat=9, base=30, fixed price)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(9, 'Hot Coffee - Cafe Macchiato', 'Hot cafe macchiato', 30.00, 'active'),
(9, 'Hot Coffee - Mochaccino', 'Hot mochaccino', 30.00, 'active');

-- ============================================================
-- STEP 14: Insert Products - HOT CHOCO (cat=10, base=30, fixed price)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(10, 'Hot Choco - Chocolate', 'Hot chocolate drink', 30.00, 'active'),
(10, 'Hot Choco - Dark Chocolate', 'Hot dark chocolate drink', 30.00, 'active');

-- ============================================================
-- STEP 15: Insert Products - HOT TEA (cat=11, base=30, fixed price)
-- ============================================================
INSERT INTO products (category_id, name, description, price, status) VALUES
(11, 'Hot Tea - Matcha', 'Hot matcha tea', 30.00, 'active');

-- ============================================================
-- STEP 16: Insert Size Options with price_modifiers
-- Pricing structure:
-- Classic Milktea (base=20): R=+0, M=+10, L=+20, B1T1=+18
-- Special Milktea (base=25): R=+0, M=+10, L=+20, B1T1=+23
-- Fruit Tea (base=20): R=+0, M=+15, L=+25, B1T1=+18
-- Yogurt (base=30): R=+0, M=+15, L=+25, B1T1=+28
-- Frappe (base=60): R=+0, M=+15, L=+25, B1T1=+58
-- Iced Coffee (base=30): R=+0, M=+10, L=+20, B1T1=+28
-- Iced Latte (base=40): R=+0, M=+10, L=+20, B1T1=+38
-- Coffee Frappe (base=70): R=+0, M=+15, L=+25, B1T1=+68
-- Soda (base=30): R=+0, M=+10, L=+20, B1T1=+28
-- Hot Coffee/Hot Choco/Hot Tea (base=30): all sizes same price
-- ============================================================

-- Classic Milktea products (IDs 1-9): R=0, M=+10, L=+20, B1T1=+18
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 1 AND 9;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 10.00, FALSE
FROM products p WHERE p.id BETWEEN 1 AND 9;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 20.00, FALSE
FROM products p WHERE p.id BETWEEN 1 AND 9;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 18.00, FALSE
FROM products p WHERE p.id BETWEEN 1 AND 9;

-- Special Milktea products (IDs 10-20): R=0, M=+10, L=+20, B1T1=+23
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 10 AND 20;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 10.00, FALSE
FROM products p WHERE p.id BETWEEN 10 AND 20;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 20.00, FALSE
FROM products p WHERE p.id BETWEEN 10 AND 20;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 23.00, FALSE
FROM products p WHERE p.id BETWEEN 10 AND 20;

-- Fruit Tea products (IDs 21-31): R=0, M=+15, L=+25, B1T1=+18
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 21 AND 31;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 15.00, FALSE
FROM products p WHERE p.id BETWEEN 21 AND 31;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 25.00, FALSE
FROM products p WHERE p.id BETWEEN 21 AND 31;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 18.00, FALSE
FROM products p WHERE p.id BETWEEN 21 AND 31;

-- Yogurt products (IDs 32-42): R=0, M=+15, L=+25, B1T1=+28
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 32 AND 42;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 15.00, FALSE
FROM products p WHERE p.id BETWEEN 32 AND 42;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 25.00, FALSE
FROM products p WHERE p.id BETWEEN 32 AND 42;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 28.00, FALSE
FROM products p WHERE p.id BETWEEN 32 AND 42;

-- Frappe products (IDs 43-56): R=0, M=+15, L=+25, B1T1=+58
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 43 AND 56;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 15.00, FALSE
FROM products p WHERE p.id BETWEEN 43 AND 56;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 25.00, FALSE
FROM products p WHERE p.id BETWEEN 43 AND 56;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 58.00, FALSE
FROM products p WHERE p.id BETWEEN 43 AND 56;

-- Iced Coffee products (IDs 57-61): R=0, M=+10, L=+20, B1T1=+28
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 57 AND 61;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 10.00, FALSE
FROM products p WHERE p.id BETWEEN 57 AND 61;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 20.00, FALSE
FROM products p WHERE p.id BETWEEN 57 AND 61;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 28.00, FALSE
FROM products p WHERE p.id BETWEEN 57 AND 61;

-- Iced Latte products (IDs 62-67): R=0, M=+10, L=+20, B1T1=+38
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 62 AND 67;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 10.00, FALSE
FROM products p WHERE p.id BETWEEN 62 AND 67;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 20.00, FALSE
FROM products p WHERE p.id BETWEEN 62 AND 67;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 38.00, FALSE
FROM products p WHERE p.id BETWEEN 62 AND 67;

-- Coffee Frappe products (IDs 68-72): R=0, M=+15, L=+25, B1T1=+68
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 68 AND 72;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 15.00, FALSE
FROM products p WHERE p.id BETWEEN 68 AND 72;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 25.00, FALSE
FROM products p WHERE p.id BETWEEN 68 AND 72;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 68.00, FALSE
FROM products p WHERE p.id BETWEEN 68 AND 72;

-- Soda products (IDs 73-77): R=0, M=+10, L=+20, B1T1=+28
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 73 AND 77;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 10.00, FALSE
FROM products p WHERE p.id BETWEEN 73 AND 77;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 20.00, FALSE
FROM products p WHERE p.id BETWEEN 73 AND 77;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 28.00, FALSE
FROM products p WHERE p.id BETWEEN 73 AND 77;

-- Hot Coffee products (IDs 78-79): all sizes same price (modifier=0)
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 78 AND 79;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 78 AND 79;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 78 AND 79;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 78 AND 79;

-- Hot Choco products (IDs 80-81): all sizes same price (modifier=0)
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id BETWEEN 80 AND 81;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 80 AND 81;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 80 AND 81;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 0.00, FALSE
FROM products p WHERE p.id BETWEEN 80 AND 81;

-- Hot Tea product (ID 82): all sizes same price (modifier=0)
INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Regular (12 oz)', 0.00, TRUE
FROM products p WHERE p.id = 82;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Medium (16 oz)', 0.00, FALSE
FROM products p WHERE p.id = 82;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Large (22 oz)', 0.00, FALSE
FROM products p WHERE p.id = 82;

INSERT INTO product_options (product_id, option_type, option_name, price_modifier, is_default)
SELECT p.id, 'size', 'Buy 1 Take 1 (12 oz + 12 oz)', 0.00, FALSE
FROM products p WHERE p.id = 82;

-- ============================================================
-- STEP 17: Insert Global Add-ons
-- ============================================================
INSERT INTO product_options (product_id, option_type, option_name, price_modifier) VALUES
(NULL, 'addon', 'Extra Tapioca Pearls', 5.00),
(NULL, 'addon', 'Cream Cheese', 10.00),
(NULL, 'addon', 'Nata De Coco', 5.00),
(NULL, 'addon', 'Coffee Jelly', 5.00),
(NULL, 'addon', 'Whipped Cream', 5.00),
(NULL, 'addon', 'Extra Sugar', 0.00),
(NULL, 'addon', 'Less Ice', 0.00);

-- ============================================================
-- STEP 18: Verify the data
-- ============================================================
-- Show summary of inserted data
SELECT 'CATEGORIES' as 'DATA', COUNT(*) as 'COUNT' FROM categories
UNION ALL
SELECT 'PRODUCTS', COUNT(*) FROM products
UNION ALL
SELECT 'PRODUCT_OPTIONS', COUNT(*) FROM product_options;

-- Show categories
SELECT id, name, sort_order FROM categories ORDER BY sort_order;

-- Show count of products per category
SELECT c.name as category, COUNT(p.id) as product_count
FROM categories c
LEFT JOIN products p ON p.category_id = c.id
GROUP BY c.id, c.name
ORDER BY c.sort_order;

-- Show sample products with their size options
SELECT p.name as product, po.option_name as size, 
       (p.price + po.price_modifier) as total_price
FROM products p
JOIN product_options po ON po.product_id = p.id
WHERE po.option_type = 'size'
  AND p.id IN (1, 10, 21, 32, 43, 57, 62, 68, 73, 78, 80, 82)
ORDER BY p.id, po.id;


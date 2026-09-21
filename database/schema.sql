-- ============================================================
-- Food Ordering System - STEP 07 Database
-- Database: food_ordering
-- ============================================================

CREATE DATABASE IF NOT EXISTS food_ordering
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE food_ordering;

-- ============================================================
-- 1. USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL DEFAULT 'customer'
);

-- ============================================================
-- 2. CATEGORIES
-- ============================================================

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- ============================================================
-- 3. FOODS
-- ============================================================

CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),

    CONSTRAINT fk_food_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- ============================================================
-- 4. ORDERS
-- ============================================================

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- ============================================================
-- 5. ORDER ITEMS
-- ============================================================

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_order_item_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_order_item_food
        FOREIGN KEY (food_id)
        REFERENCES foods(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- ============================================================
-- 6. REVIEWS
-- ============================================================

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_review_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_review_food
        FOREIGN KEY (food_id)
        REFERENCES foods(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_review_rating
        CHECK (rating BETWEEN 1 AND 5)
);

-- ============================================================
-- 7. COUPONS
-- ============================================================

CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount DECIMAL(10,2) NOT NULL,
    discount_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
    expiry_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active'
);

-- ============================================================
-- SAMPLE CATEGORIES
-- ============================================================

INSERT IGNORE INTO categories (name) VALUES
('Burgers'),
('Pizza'),
('Chicken'),
('Pasta'),
('Drinks'),
('Desserts');

-- ============================================================
-- SAMPLE FOODS
-- ============================================================

INSERT IGNORE INTO foods
(category_id, name, description, price, image)
VALUES
(
    (SELECT id FROM categories WHERE name = 'Burgers' LIMIT 1),
    'Classic Burger',
    'Juicy beef burger with fresh vegetables and special sauce.',
    850.00,
    'burger.jpg'
),
(
    (SELECT id FROM categories WHERE name = 'Pizza' LIMIT 1),
    'Cheese Pizza',
    'Delicious pizza with melted cheese.',
    1200.00,
    'pizza.jpg'
),
(
    (SELECT id FROM categories WHERE name = 'Chicken' LIMIT 1),
    'Fried Chicken',
    'Crispy and delicious fried chicken.',
    950.00,
    'fried-chicken.jpg'
),
(
    (SELECT id FROM categories WHERE name = 'Pasta' LIMIT 1),
    'Chicken Pasta',
    'Creamy pasta with delicious chicken.',
    1100.00,
    'chicken-pasta.jpg'
);

-- ============================================================
-- STEP 07 COMPLETE
-- ============================================================

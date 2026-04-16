CREATE DATABASE IF NOT EXISTS proweblanjut_crud_a12_2025_07414 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE proweblanjut_crud_a12_2025_07414;

-- -------------------------------------------------------------------------
-- Users table
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Remember tokens (for persistent login)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Items table (with user_id for multi-user support)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(15, 2) NOT NULL,
    entry_date DATE NOT NULL,
    image_path VARCHAR(500) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Seed: Default admin user
-- -------------------------------------------------------------------------
-- Username: admin
-- Password: Admin@12345 (Bcrypt hash)
-- -------------------------------------------------------------------------
INSERT INTO
    users (
        username,
        email,
        password_hash
    )
VALUES (
        'admin',
        'admin@localhost',
        '$2y$12$S5q6NT.tibKp.UmbJGVu6ewMJanfIgPb7zAQwOzzaLj9UCiCNenli'
    );

-- -------------------------------------------------------------------------
-- Seed: Sample items (owned by admin, id=1)
-- -------------------------------------------------------------------------
INSERT INTO
    items (
        user_id,
        item_name,
        quantity,
        price,
        entry_date
    )
VALUES (
        1,
        'Wireless Mouse',
        45,
        129000,
        '2025-01-10'
    ),
    (
        1,
        'Mechanical Keyboard',
        3,
        850000,
        '2025-02-14'
    ),
    (
        1,
        'USB-C Hub',
        20,
        320000,
        '2025-03-01'
    ),
    (
        1,
        'HDMI Cable 2m',
        2,
        75000,
        '2025-03-05'
    ),
    (
        1,
        'Laptop Stand',
        12,
        275000,
        '2025-03-08'
    );

-- -------------------------------------------------------------------------
-- Migration: For existing installations, run this to add image_path column:
-- ALTER TABLE items ADD COLUMN image_path VARCHAR(500) NULL DEFAULT NULL AFTER entry_date;
-- -------------------------------------------------------------------------